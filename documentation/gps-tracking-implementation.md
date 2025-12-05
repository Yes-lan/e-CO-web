# GPS Tracking & Beacon Validation Implementation Guide

## 📊 Database Schema for Beacon Validation

### Option 1: Dedicated `beacon_validation` Table (RECOMMENDED)

This approach creates a clear record of each beacon scan/validation by a runner.

```sql
CREATE TABLE beacon_validation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    runner_id INT NOT NULL,
    beacon_id INT NOT NULL,
    validated_at DATETIME NOT NULL,
    validation_type ENUM('gps_proximity', 'qr_scan', 'manual') NOT NULL DEFAULT 'gps_proximity',
    distance_from_beacon FLOAT NULL COMMENT 'Distance in meters when validated',
    is_valid BOOLEAN DEFAULT TRUE COMMENT 'False if runner skipped or went out of order',
    latitude DECIMAL(10, 8) NULL COMMENT 'Runner position when validated',
    longitude DECIMAL(11, 8) NULL COMMENT 'Runner position when validated',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (runner_id) REFERENCES runner(id) ON DELETE CASCADE,
    FOREIGN KEY (beacon_id) REFERENCES beacon(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_runner_beacon (runner_id, beacon_id),
    INDEX idx_runner (runner_id),
    INDEX idx_beacon (beacon_id),
    INDEX idx_validated_at (validated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Benefits:**
- ✅ **Clear validation records** - Each row = one beacon scanned by one runner
- ✅ **Validation metadata** - Store how validation occurred (GPS vs QR scan)
- ✅ **Distance tracking** - Record how close runner was to beacon
- ✅ **Invalid validations** - Mark beacons that were skipped or done out of order
- ✅ **Timestamp precision** - Know exactly when each beacon was reached
- ✅ **Position snapshot** - Record runner's exact location at validation

**Example Data:**
```sql
INSERT INTO beacon_validation (runner_id, beacon_id, validated_at, validation_type, distance_from_beacon, latitude, longitude) VALUES
(1, 5, '2025-12-05 10:15:23', 'gps_proximity', 8.5, 43.300123, -1.500456),
(1, 3, '2025-12-05 10:18:45', 'gps_proximity', 12.3, 43.301234, -1.501567),
(2, 5, '2025-12-05 10:20:10', 'qr_scan', 2.1, 43.300145, -1.500478);
```

### Option 2: Enhanced `log_session` Table (ALTERNATIVE)

Extend the existing `log_session` table to include beacon validation flags.

```sql
ALTER TABLE log_session 
ADD COLUMN beacon_id INT NULL AFTER runner_id,
ADD COLUMN is_beacon_validation BOOLEAN DEFAULT FALSE,
ADD COLUMN validation_distance FLOAT NULL COMMENT 'Distance from beacon in meters',
ADD FOREIGN KEY (beacon_id) REFERENCES beacon(id) ON DELETE SET NULL,
ADD INDEX idx_beacon_validation (runner_id, is_beacon_validation, beacon_id);
```

**Benefits:**
- ✅ Uses existing GPS tracking infrastructure
- ✅ No new table needed
- ✅ All runner data in one place

**Drawbacks:**
- ❌ Mixes GPS tracking with validation logic
- ❌ Harder to query "which beacons were validated"
- ❌ No explicit validation type field

---

## 🗺️ GPS Path Display with Timeline Slider

### UI Components to Add

#### 1. Slider Control (below map)

```html
<!-- Add after map panel, before beacon validation panel -->
<div class="gps-timeline-control">
    <div class="timeline-header">
        <h3>📍 Progression du coureur</h3>
        <div class="timeline-info">
            <span id="timelineTime">00:00</span>
            <span id="timelineProgress">0%</span>
        </div>
    </div>
    <div class="slider-container">
        <button id="playPauseBtn" class="btn-play">▶️</button>
        <input 
            type="range" 
            id="gpsSlider" 
            min="0" 
            max="100" 
            value="100" 
            step="1"
            class="gps-slider"
        />
        <button id="resetBtn" class="btn-reset">🔄</button>
    </div>
    <div class="timeline-labels">
        <span class="timeline-start">Départ</span>
        <span class="timeline-end">Arrivée</span>
    </div>
</div>
```

#### 2. CSS Styles

```css
/* GPS Timeline Control */
.gps-timeline-control {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.timeline-header h3 {
    font-size: 1rem;
    color: #1976d2;
    margin: 0;
}

.timeline-info {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    color: #666;
}

.slider-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-play, .btn-reset {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    background: #1976d2;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-play:hover, .btn-reset:hover {
    background: #1565c0;
}

.gps-slider {
    flex: 1;
    height: 8px;
    border-radius: 4px;
    background: #e0e0e0;
    outline: none;
    -webkit-appearance: none;
}

.gps-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #1976d2;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.gps-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #1976d2;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.timeline-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #999;
}
```

#### 3. JavaScript Implementation

```javascript
// GPS tracking state
let gpsPoints = [];
let gpsPolyline = null;
let runnerMarker = null;
let isPlaying = false;
let playbackInterval = null;
let currentPointIndex = 0;

// Slider event listener
document.getElementById('gpsSlider').addEventListener('input', function(e) {
    const sliderValue = parseInt(e.target.value);
    updateGPSDisplay(sliderValue);
});

// Play/Pause button
document.getElementById('playPauseBtn').addEventListener('click', function() {
    if (isPlaying) {
        pausePlayback();
    } else {
        startPlayback();
    }
});

// Reset button
document.getElementById('resetBtn').addEventListener('click', function() {
    pausePlayback();
    document.getElementById('gpsSlider').value = 100;
    updateGPSDisplay(100);
});

// Load GPS data for runner
async function loadRunnerPath(runnerId) {
    try {
        // Clear existing paths
        if (gpsPolyline) gpsPolyline.setMap(null);
        if (runnerMarker) runnerMarker.setMap(null);
        runnerPaths.forEach(path => path.setMap(null));
        runnerPaths = [];
        
        // Fetch GPS data from API
        const response = await fetch(`/api/runners/${runnerId}/gps`);
        if (!response.ok) throw new Error('Failed to load GPS data');
        
        const data = await response.json();
        gpsPoints = data.gps_logs || [];
        
        if (gpsPoints.length === 0) {
            console.log('No GPS data for this runner');
            return;
        }
        
        // Initialize slider
        const slider = document.getElementById('gpsSlider');
        slider.max = gpsPoints.length - 1;
        slider.value = gpsPoints.length - 1; // Start at end (full path)
        
        // Display full path initially
        updateGPSDisplay(100);
        
    } catch (error) {
        console.error('Error loading runner path:', error);
    }
}

// Update GPS display based on slider value
function updateGPSDisplay(percentage) {
    if (gpsPoints.length === 0) return;
    
    // Calculate how many points to show
    const pointCount = Math.max(1, Math.floor((percentage / 100) * gpsPoints.length));
    const visiblePoints = gpsPoints.slice(0, pointCount);
    
    // Update polyline path
    if (gpsPolyline) {
        gpsPolyline.setMap(null);
    }
    
    const pathCoords = visiblePoints.map(point => ({
        lat: parseFloat(point.latitude),
        lng: parseFloat(point.longitude)
    }));
    
    gpsPolyline = new google.maps.Polyline({
        path: pathCoords,
        geodesic: true,
        strokeColor: '#4285F4',
        strokeOpacity: 0.8,
        strokeWeight: 4,
        map: map
    });
    
    // Update runner position marker
    if (runnerMarker) {
        runnerMarker.setMap(null);
    }
    
    const lastPoint = visiblePoints[visiblePoints.length - 1];
    runnerMarker = new google.maps.Marker({
        position: {
            lat: parseFloat(lastPoint.latitude),
            lng: parseFloat(lastPoint.longitude)
        },
        map: map,
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 10,
            fillColor: '#4285F4',
            fillOpacity: 1,
            strokeColor: 'white',
            strokeWeight: 3
        },
        zIndex: 1000
    });
    
    // Update timeline info
    updateTimelineInfo(pointCount);
    
    // Check beacon validations at this point
    updateBeaconValidations(visiblePoints);
}

// Update timeline information display
function updateTimelineInfo(pointCount) {
    if (gpsPoints.length === 0) return;
    
    const currentPoint = gpsPoints[pointCount - 1];
    const startTime = new Date(gpsPoints[0].time);
    const currentTime = new Date(currentPoint.time);
    
    const elapsedMs = currentTime - startTime;
    const minutes = Math.floor(elapsedMs / 60000);
    const seconds = Math.floor((elapsedMs % 60000) / 1000);
    
    document.getElementById('timelineTime').textContent = 
        `${minutes}:${seconds.toString().padStart(2, '0')}`;
    
    const progress = ((pointCount / gpsPoints.length) * 100).toFixed(0);
    document.getElementById('timelineProgress').textContent = `${progress}%`;
}

// Start automated playback
function startPlayback() {
    isPlaying = true;
    document.getElementById('playPauseBtn').textContent = '⏸️';
    
    const slider = document.getElementById('gpsSlider');
    let currentValue = parseInt(slider.value);
    
    playbackInterval = setInterval(() => {
        currentValue += 1;
        
        if (currentValue > slider.max) {
            pausePlayback();
            return;
        }
        
        slider.value = currentValue;
        const percentage = (currentValue / slider.max) * 100;
        updateGPSDisplay(percentage);
    }, 100); // Update every 100ms (adjust for speed)
}

// Pause playback
function pausePlayback() {
    isPlaying = false;
    document.getElementById('playPauseBtn').textContent = '▶️';
    
    if (playbackInterval) {
        clearInterval(playbackInterval);
        playbackInterval = null;
    }
}

// Update beacon validation status based on GPS points shown
function updateBeaconValidations(visiblePoints) {
    if (!currentCourse || !currentCourse.waypoints) return;
    
    // Calculate which beacons have been validated
    const VALIDATION_RADIUS = 15; // meters
    const validatedBeacons = new Set();
    
    currentCourse.waypoints.forEach((beacon, idx) => {
        const beaconLat = parseFloat(beacon.lat);
        const beaconLng = parseFloat(beacon.lng);
        
        for (const point of visiblePoints) {
            const distance = calculateDistance(
                parseFloat(point.latitude),
                parseFloat(point.longitude),
                beaconLat,
                beaconLng
            );
            
            if (distance <= VALIDATION_RADIUS) {
                validatedBeacons.add(idx);
                break;
            }
        }
    });
    
    // Update table cells
    const validationRow = document.querySelector('.beacon-validation-table tbody tr:nth-child(1)');
    if (validationRow) {
        const cells = validationRow.querySelectorAll('td');
        cells.forEach((cell, idx) => {
            if (idx === 0) return; // Skip first column (label)
            
            const beaconIdx = idx - 1;
            if (validatedBeacons.has(beaconIdx)) {
                cell.innerHTML = '<span class="status-valid">✓</span>';
                cell.className = 'status-valid';
            } else {
                cell.innerHTML = '<span class="status-pending">-</span>';
                cell.className = 'status-pending';
            }
        });
    }
}

// Calculate distance between two GPS coordinates (Haversine formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;
    
    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    
    return R * c; // Distance in meters
}
```

---

## 🔌 Backend API Endpoint

Create a new endpoint to fetch GPS data for a runner:

### Controller: `src/Controller/RunnerController.php`

```php
<?php

namespace App\Controller;

use App\Repository\RunnerRepository;
use App\Repository\LogSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RunnerController extends AbstractController
{
    public function __construct(
        private RunnerRepository $runnerRepository,
        private LogSessionRepository $logSessionRepository
    ) {}

    #[Route('/api/runners/{id}/gps', name: 'api_runner_gps', methods: ['GET'])]
    public function getRunnerGPS(int $id): JsonResponse
    {
        $runner = $this->runnerRepository->find($id);
        
        if (!$runner) {
            return new JsonResponse(['error' => 'Runner not found'], 404);
        }
        
        // Get all GPS log sessions for this runner, ordered by time
        $logSessions = $this->logSessionRepository->findBy(
            ['runner' => $runner, 'type' => 'gps'],
            ['time' => 'ASC']
        );
        
        $gpsData = array_map(function($log) {
            return [
                'id' => $log->getId(),
                'latitude' => $log->getLatitude(),
                'longitude' => $log->getLongitude(),
                'time' => $log->getTime()?->format('Y-m-d H:i:s'),
                'accuracy' => $log->getAccuracy() ?? null,
                'altitude' => $log->getAltitude() ?? null
            ];
        }, $logSessions);
        
        return new JsonResponse([
            'runner_id' => $runner->getId(),
            'runner_name' => $runner->getName(),
            'gps_logs' => $gpsData,
            'total_points' => count($gpsData)
        ]);
    }
    
    #[Route('/api/runners/{id}/validations', name: 'api_runner_validations', methods: ['GET'])]
    public function getRunnerValidations(int $id): JsonResponse
    {
        $runner = $this->runnerRepository->find($id);
        
        if (!$runner) {
            return new JsonResponse(['error' => 'Runner not found'], 404);
        }
        
        // Get beacon validations (if using Option 1 schema)
        $validations = $this->entityManager->getRepository(BeaconValidation::class)->findBy(
            ['runner' => $runner],
            ['validated_at' => 'ASC']
        );
        
        $validationData = array_map(function($val) {
            return [
                'beacon_id' => $val->getBeacon()->getId(),
                'beacon_name' => $val->getBeacon()->getName(),
                'validated_at' => $val->getValidatedAt()?->format('Y-m-d H:i:s'),
                'validation_type' => $val->getValidationType(),
                'distance' => $val->getDistanceFromBeacon(),
                'is_valid' => $val->isValid()
            ];
        }, $validations);
        
        return new JsonResponse([
            'runner_id' => $runner->getId(),
            'validations' => $validationData,
            'total_validated' => count($validationData)
        ]);
    }
}
```

---

## 📝 Summary

### Database Schema Recommendation
**Use Option 1: `beacon_validation` table** for clean separation of GPS tracking and validation logic.

### Implementation Steps
1. ✅ Create `beacon_validation` table migration
2. ✅ Create `BeaconValidation` entity
3. ✅ Add `RunnerController` with GPS and validation endpoints
4. ✅ Add GPS timeline slider UI to map template
5. ✅ Implement JavaScript GPS path display with slider
6. ✅ Calculate beacon validations from GPS proximity
7. ✅ Update beacon validation table dynamically as slider moves

### Key Features
- 📍 **Full GPS path visualization** on map
- ⏯️ **Playback control** with play/pause
- 🔄 **Timeline slider** to review any point in the run
- ✅ **Dynamic validation** showing which beacons were reached
- ⏱️ **Time display** showing elapsed time at slider position
- 📊 **Validation table** updates in real-time with slider
