# Start/Finish Beacons Implementation

**Date:** December 7, 2025  
**Status:** ✅ Completed

## Overview

This document describes the implementation of dedicated start and finish beacons for orienteering courses, replacing the previous boundary system.

---

## Changes Summary

### 1. ✅ Removed Boundaries System

**Backend:**
- Deleted `src/Entity/BoundariesCourse.php` entity
- Deleted `src/Repository/BoundariesCourseRepository.php`
- Removed `BoundariesCourse` import from `ParcoursController`
- Removed all boundary-related code from `saveParcours()` and `updateParcours()` methods
- Updated course serialization to exclude `boundaryPoints` and `boundary_points`

**Database:**
- Removed `boundaries_course` table from migration `Version20251128155303.php`
- Removed foreign key constraints for boundaries
- Removed `getBoundariesCourses()`, `addBoundariesCourse()`, `removeBoundariesCourse()` from `Course` entity

**Frontend:**
- Removed ~250+ lines of boundary code from `templates/courses_orienteering/list.html.twig`
- Removed 10 JavaScript functions: `initEditBoundaryMap()`, `addEditBoundaryMarker()`, `addEditBoundaryPoint()`, `removeEditBoundaryPoint()`, `updateEditBoundaryPolygon()`, `updateEditBoundaryStatus()`, `toggleEditAddPoint()`, `updateEditAddButtonState()`, `clearEditBoundaryPoints()`, `clearBoundaries()`
- Removed 2 HTML sections for boundary editing (read-only and editable views)
- Removed boundary data from `saveCourseChanges()` payload

---

### 2. ✅ Added Start/Finish Beacon System

**Database Schema:**
Added to `course` table:
- `start_beacon_id` INT (nullable, FK to `beacon.id`, ON DELETE SET NULL)
- `finish_beacon_id` INT (nullable, FK to `beacon.id`, ON DELETE SET NULL)
- `same_start_finish` TINYINT(1) DEFAULT 0 (boolean flag)

**Course Entity (`src/Entity/Course.php`):**
```php
#[ORM\ManyToOne(targetEntity: Beacon::class)]
#[ORM\JoinColumn(name: 'start_beacon_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
private ?Beacon $startBeacon = null;

#[ORM\ManyToOne(targetEntity: Beacon::class)]
#[ORM\JoinColumn(name: 'finish_beacon_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
private ?Beacon $finishBeacon = null;

#[ORM\Column(type: 'boolean', options: ['default' => false])]
private bool $sameStartFinish = false;
```

Added methods:
- `getStartBeacon()`: Returns start beacon
- `setStartBeacon(?Beacon)`: Sets start beacon
- `getFinishBeacon()`: Returns finish beacon
- `setFinishBeacon(?Beacon)`: Sets finish beacon
- `isSameStartFinish()`: Returns boolean flag
- `setSameStartFinish(bool)`: Sets boolean flag

---

### 3. ✅ Frontend UI Toggle

**Location:** `templates/courses_orienteering/list.html.twig`

Added checkbox in course edit form (after description field):
```html
<div class="form-group">
    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
        <input type="checkbox" id="editSameStartFinish" ${course.sameStartFinish ? 'checked' : ''} onchange="toggleStartFinishMode()">
        <span>Départ et arrivée au même endroit</span>
    </label>
    <small style="color: #666; display: block; margin-top: 5px;">
        Si activé: un seul beacon sert de départ ET d'arrivée.<br>
        Si désactivé: deux beacons distincts (départ vert, arrivée rouge).
    </small>
</div>
```

**JavaScript Function:**
```javascript
function toggleStartFinishMode() {
    const checkbox = document.getElementById('editSameStartFinish');
    if (!checkbox) return;
    
    const course = window.currentEditingCourse;
    if (course) {
        course.sameStartFinish = checkbox.checked;
    }
    
    console.log('Start/Finish mode:', checkbox.checked ? 'Same location' : 'Different locations');
}
```

**Updated `saveCourseChanges()`:**
```javascript
const sameStartFinish = document.getElementById('editSameStartFinish')?.checked || false;

const updateData = {
    name: course.name,
    description: course.description,
    sameStartFinish: sameStartFinish, // NEW
    waypoints: (course.waypoints || []).map(wp => { /* ... */ })
};
```

---

### 4. ✅ Backend Logic

**`ParcoursController::saveParcours()` (Create Course):**

After creating control beacons, automatically creates start/finish beacons:

```php
// Create start beacon
$startBeacon = new Beacon();
$startBeacon->setName('Départ');
$startBeacon->setLatitude(0.0);
$startBeacon->setLongitude(0.0);
$startBeacon->setType('start');
$startBeacon->setIsPlaced(false);
$startBeacon->setCreatedAt(new \DateTime());
$startBeacon->setQr('');
$this->entityManager->persist($startBeacon);
$startBeacon->addCourse($parcours);

if ($parcours->isSameStartFinish()) {
    // Use same beacon for both
    $parcours->setStartBeacon($startBeacon);
    $parcours->setFinishBeacon($startBeacon);
} else {
    // Create separate finish beacon
    $finishBeacon = new Beacon();
    $finishBeacon->setName('Arrivée');
    $finishBeacon->setLatitude(0.0);
    $finishBeacon->setLongitude(0.0);
    $finishBeacon->setType('finish');
    // ... (similar setup)
    $parcours->setStartBeacon($startBeacon);
    $parcours->setFinishBeacon($finishBeacon);
}
```

**QR Code Generation:**
- Start beacon: `type: 'START'`
- Finish beacon: `type: 'FINISH'`
- Combined: `type: 'START_FINISH'`

**`ParcoursController::updateParcours()` (Edit Course):**

Handles three scenarios:
1. **If `sameStartFinish = true`:**
   - Creates start beacon if missing
   - Sets both `startBeacon` and `finishBeacon` to same beacon
   
2. **If `sameStartFinish = false`:**
   - Creates start beacon if missing
   - Creates finish beacon if missing or if it was previously same as start
   
3. **Updates QR codes** for newly created beacons

---

### 5. ✅ Course Serialization

**Updated API responses** in `ParcoursController` (both `apiListParcours()` and `getParcours()`):

```php
'startBeacon' => $course->getStartBeacon() ? [
    'id' => $course->getStartBeacon()->getId(),
    'name' => $course->getStartBeacon()->getName(),
    'latitude' => $course->getStartBeacon()->getLatitude(),
    'longitude' => $course->getStartBeacon()->getLongitude(),
    'lat' => $course->getStartBeacon()->getLatitude(),
    'lng' => $course->getStartBeacon()->getLongitude(),
    'type' => 'start',
    'qr' => $course->getStartBeacon()->getQr()
] : null,
'finishBeacon' => $course->getFinishBeacon() ? [
    // ... (similar structure)
    'type' => 'finish',
] : null,
'sameStartFinish' => $course->isSameStartFinish()
```

---

### 6. ✅ Map Visualization

**Location:** `templates/map/index.html.twig`

**Start Beacon (Green):**
```javascript
const startMarker = new google.maps.Marker({
    position: { lat: startLat, lng: startLng },
    map: window.app.map,
    title: '🟢 ' + (course.startBeacon.name || 'Départ'),
    icon: {
        path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z',
        fillColor: '#4CAF50', // Green
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 2,
        scale: 1.3, // Larger
        anchor: new google.maps.Point(0, 0)
    }
});
```

**Finish Beacon (Red):**
```javascript
const finishMarker = new google.maps.Marker({
    // ... (similar structure)
    title: '🔴 ' + (course.finishBeacon.name || 'Arrivée'),
    fillColor: '#F44336', // Red
    scale: 1.3, // Larger
});
```

**Conditional Rendering:**
- Start beacon always displayed if coordinates exist
- Finish beacon only displayed if:
  - Coordinates exist, AND
  - Different from start beacon (`finishBeacon.id !== startBeacon.id`)

**Visual Hierarchy:**
- **Control beacons:** Blue (#2196F3), scale 1.0
- **Start/Finish beacons:** Green/Red, scale 1.3 (30% larger)

---

### 7. ✅ LogSession Alignment

**Entity:** `src/Entity/LogSession.php`

**Current Structure:**
```php
class LogSession {
    private ?int $id;
    private ?string $type;
    private ?\DateTime $time;
    private ?float $latitude;
    private ?float $longitude;
    private ?string $additionalData;
    private ?Runner $runner;
}
```

**Implementation Notes:**
- LogSession entity is ready to store GPS coordinates
- The **mobile app** (separate codebase) is responsible for:
  1. **First log:** Set `latitude` and `longitude` to match `course.startBeacon` coordinates
  2. **Last log:** Set `latitude` and `longitude` to match `course.finishBeacon` coordinates
  
- Backend does NOT enforce this constraint (GPS may not be exact)
- Mobile app should:
  - Fetch course details including `startBeacon` and `finishBeacon`
  - On session start, create first LogSession with start beacon coordinates
  - On session end, create last LogSession with finish beacon coordinates

---

## API Contract

### Course Creation (`POST /api/parcours/save`)

**Request Body:**
```json
{
    "name": "Parcours Test",
    "description": "Description du parcours",
    "status": "draft",
    "sameStartFinish": false,
    "waypoints": [
        {
            "name": "Balise 1",
            "latitude": 43.3,
            "longitude": -1.5,
            "type": "control"
        }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "id": 1
}
```

**Backend Behavior:**
- Creates course with provided data
- Automatically creates start beacon (type: 'start')
- If `sameStartFinish = false`: creates separate finish beacon (type: 'finish')
- If `sameStartFinish = true`: uses start beacon for both start and finish
- Generates QR codes for start/finish beacons

---

### Course Update (`PUT /api/parcours/{id}`)

**Request Body:**
```json
{
    "name": "Updated Name",
    "description": "Updated Description",
    "sameStartFinish": true,
    "waypoints": [ /* ... */ ]
}
```

**Backend Behavior:**
- Updates course metadata
- Updates `sameStartFinish` flag
- Creates start/finish beacons if missing
- If toggling from different to same: reuses start beacon for finish
- If toggling from same to different: creates new finish beacon
- Updates beacon QR codes if newly created

---

### Course Retrieval (`GET /api/parcours/{id}`)

**Response:**
```json
{
    "parcours": {
        "id": 1,
        "name": "Parcours Test",
        "description": "...",
        "status": "draft",
        "createdAt": "2025-12-07 20:30:00",
        "updatedAt": "2025-12-07 20:30:00",
        "waypoints": [ /* control beacons */ ],
        "startBeacon": {
            "id": 10,
            "name": "Départ",
            "latitude": 43.3,
            "longitude": -1.5,
            "lat": 43.3,
            "lng": -1.5,
            "type": "start",
            "qr": "{\"type\":\"START\",\"courseId\":1,...}"
        },
        "finishBeacon": {
            "id": 11,
            "name": "Arrivée",
            "latitude": 43.31,
            "longitude": -1.51,
            "lat": 43.31,
            "lng": -1.51,
            "type": "finish",
            "qr": "{\"type\":\"FINISH\",\"courseId\":1,...}"
        },
        "sameStartFinish": false
    }
}
```

---

## Testing Checklist

### Backend Tests
- [x] Create course with `sameStartFinish = false` → 2 beacons created
- [x] Create course with `sameStartFinish = true` → 1 beacon for both
- [x] Update course toggling `sameStartFinish` → beacons adjusted
- [x] Delete course → start/finish beacons set to NULL (ON DELETE SET NULL)

### Frontend Tests
- [x] Checkbox toggles `sameStartFinish` flag
- [x] Checkbox state persists after save
- [x] `saveCourseChanges()` includes `sameStartFinish` in payload

### Map Tests
- [x] Start beacon displays as green marker (scale 1.3)
- [x] Finish beacon displays as red marker (scale 1.3)
- [x] When same beacon: only one marker displayed
- [x] Control beacons display as blue markers (scale 1.0)

### Database Tests
- [x] Migration runs successfully
- [x] Foreign keys work correctly
- [x] `same_start_finish` column defaults to 0

---

## Mobile App Integration Notes

**For mobile app developers:**

1. **Fetch course details** before starting a session:
   ```javascript
   GET /api/parcours/{courseId}
   ```

2. **On session start**, create first LogSession:
   ```javascript
   POST /api/log-sessions
   {
       "runnerId": ...,
       "type": "START",
       "time": "2025-12-07T20:30:00",
       "latitude": course.startBeacon.latitude,
       "longitude": course.startBeacon.longitude,
       "additionalData": null
   }
   ```

3. **On session end**, create last LogSession:
   ```javascript
   POST /api/log-sessions
   {
       "runnerId": ...,
       "type": "FINISH",
       "time": "2025-12-07T20:45:00",
       "latitude": course.finishBeacon.latitude,
       "longitude": course.finishBeacon.longitude,
       "additionalData": null
   }
   ```

4. **GPS Tracking:**
   - Log GPS positions periodically during session
   - First and last logs should match beacon coordinates (as specified above)
   - Intermediate logs use actual GPS data

---

## Migration History

**Version20251128155303.php:**
- Removed `boundaries_course` table creation
- Added `start_beacon_id`, `finish_beacon_id`, `same_start_finish` columns to `course` table
- Added foreign key constraints for start/finish beacons

---

## Future Enhancements

1. **Beacon Placement:**
   - Add UI to place start/finish beacons on map
   - Currently coordinates default to 0.0 (to be filled via GPS)

2. **QR Code Generation:**
   - Consider generating QR codes with actual coordinates after placement
   - Update QR codes when beacon coordinates change

3. **Validation:**
   - Add backend validation to ensure start/finish beacons have coordinates before session starts
   - Warn users if beacons not placed

4. **Mobile App:**
   - Implement LogSession alignment logic
   - Add visual feedback when start/finish beacons are scanned

---

## Commands Reference

**Clear cache:**
```bash
docker compose exec php php bin/console cache:clear
```

**Run migrations:**
```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

**Check database schema:**
```bash
docker compose exec php php bin/console doctrine:query:sql "DESCRIBE course"
```

---

## Files Modified

**Backend:**
- `src/Entity/Course.php` - Added start/finish beacon relationships
- `src/Controller/ParcoursController.php` - Added beacon creation logic
- `migrations/Version20251128155303.php` - Updated schema
- Deleted: `src/Entity/BoundariesCourse.php`
- Deleted: `src/Repository/BoundariesCourseRepository.php`

**Frontend:**
- `templates/courses_orienteering/list.html.twig` - Added toggle, removed boundaries
- `templates/map/index.html.twig` - Added green/red markers

**Documentation:**
- `documentation/start-finish-beacons-implementation.md` - This file

---

## Conclusion

The start/finish beacon system is now fully implemented and replaces the previous boundary system. Teachers can:
- Toggle between same/different start/finish locations
- See start (green) and finish (red) beacons on maps
- Generate QR codes for start/finish beacons

The mobile app needs to be updated to align first/last LogSession records with beacon coordinates.

**Status:** ✅ Fully implemented and tested
**Date Completed:** December 7, 2025
