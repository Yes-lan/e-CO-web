# e-CO Web - JavaScript Core Architecture

**Last Updated:** November 21, 2025

---

## 📦 JavaScript Structure Overview

The e-CO Web application uses **vanilla JavaScript ES6+** with a single-class architecture for the core orienteering functionality.

---

## 🏗️ OrienteeringApp Class

**Location:** `public/assets/js/app.js` (~1689 lines)  
**Pattern:** Singleton-like class instantiated globally  
**Browser Compatibility:** Modern evergreen browsers (ES6+ support required)

### Class Declaration Pattern

```javascript
// Prevent class redeclaration on Turbo navigation
if (typeof OrienteeringApp === 'undefined') {
    window.OrienteeringApp = class OrienteeringApp {
        constructor() {
            // Initialize properties
        }
        // Methods...
    }
}
```

**Why this pattern?**
- Turbo Drive replaces page content but keeps JavaScript scope
- Without protection, class redeclaration causes errors
- `window.OrienteeringApp` makes class globally accessible

---

## 🎯 Class Properties

### Map Components
```javascript
constructor() {
    // Google Maps instance
    this.map = null;
    
    // Marker arrays
    this.markers = [];              // All waypoint markers
    this.currentLocationMarker = null; // Teacher GPS marker
    
    // Drawing components
    this.controlPoints = [];        // Waypoint data objects
    this.boundaryPolygon = null;    // Course boundary polygon
    this.coursePolyline = null;     // Course path line
    this.optimalPathPolyline = null; // Ideal path line
    this.accuracyCircle = null;     // GPS accuracy indicator
}
```

### State Management
```javascript
constructor() {
    // Initialization flags
    this.mapsLoaded = false;        // Google Maps API loaded
    this.initialized = false;       // App initialized
    
    // UI state
    this.isAddingPoint = false;     // Adding waypoint mode
    this.currentEditingPoint = null; // Currently editing waypoint
    this.boundaryFillVisible = true; // Boundary fill visibility
    this.optimalPathVisible = false; // Optimal path visibility
    this.poisVisible = false;       // POIs visibility
    
    // Zoom handling
    this.zoomAdjustmentPending = false;
    this.zoomAdjustmentTimeoutId = null;
}
```

### Configuration
```javascript
constructor() {
    // Boundary settings
    this.courseBounds = null;       // Calculated bounds object
    this.boundaryRestrictionMode = 'soft'; // 'strict' or 'soft'
    this.minZoom = 10;
    this.maxZoom = 20;
    
    // Configuration data (loaded from JSON)
    this.config = null;
}
```

---

## 🔧 Core Methods

### Initialization

#### `async init()`
**Purpose:** Initialize the application  
**Called:** On page load or Turbo navigation to map page

```javascript
async init() {
    // Prevent double initialization
    if (this.initialized) {
        return;
    }
    
    // Load configuration from JSON FIRST (await to ensure completion)
    await this.loadConfiguration();
    
    // Setup event listeners
    this.setupEventListeners();
    
    // Load saved courses
    this.loadSavedCourses();
    
    this.initialized = true;
    
    // If Google Maps already loaded, initialize map
    if (window.google && window.google.maps) {
        this.initializeMap();
    }
}
```

#### `async loadConfiguration()`
**Purpose:** Fetch map configuration from JSON  
**File:** `/assets/data/map-config.json`

```javascript
async loadConfiguration() {
    try {
        const cacheBuster = `?t=${Date.now()}`;
        const response = await fetch(
            `/assets/data/map-config.json${cacheBuster}`,
            { cache: 'no-store' }
        );
        this.config = await response.json();
        console.log('Configuration loaded:', this.config);
    } catch (error) {
        console.error('Failed to load configuration:', error);
        // Set default fallback values
        this.config = {
            defaultLocation: { lat: 45.5017, lng: -73.5673 },
            defaultZoom: 15,
            defaultMapType: 'hybrid',
            boundarySettings: {
                defaultPaddingKm: 0.5,
                defaultMode: 'soft',
                minZoom: 10,
                maxZoom: 20
            }
        };
    }
}
```

**Configuration Structure:**
```json
{
  "defaultLocation": {
    "lat": 45.5017,
    "lng": -73.5673
  },
  "defaultZoom": 15,
  "defaultMapType": "hybrid",
  "boundarySettings": {
    "defaultPaddingKm": 0.5,
    "defaultMode": "soft",
    "minZoom": 10,
    "maxZoom": 20
  }
}
```

---

### Map Initialization

#### `initializeMap()`
**Purpose:** Create Google Maps instance  
**Called:** After Google Maps API loads

```javascript
initializeMap() {
    // Check if Google Maps available
    if (!window.google || !window.google.maps) {
        console.error('Google Maps API not loaded yet');
        return;
    }

    // Check if already initialized
    if (this.map && this.mapsLoaded) {
        console.log('Map already initialized, skipping...');
        return;
    }

    // Ensure map container exists
    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.error('Map container #map not found');
        return;
    }

    console.log('Initializing Google Maps...');
    this.mapsLoaded = true;
    
    // Use configuration or fallback
    const defaultLocation = this.config 
        ? this.config.defaultLocation 
        : { lat: 45.5017, lng: -73.5673 };
    
    const defaultZoom = this.config ? this.config.defaultZoom : 15;
    
    // Initialize map
    this.map = new google.maps.Map(mapContainer, {
        zoom: defaultZoom,
        center: defaultLocation,
        mapTypeId: google.maps.MapTypeId.HYBRID,
        mapTypeControl: true,
        streetViewControl: false,
        fullscreenControl: true,
        zoomControl: true,
        scaleControl: true,
        tilt: 0,
        rotateControl: false,
        heading: 0,
        // Map styles for POI control
        styles: this.poisVisible ? [] : [
            {
                featureType: "poi",
                elementType: "labels",
                stylers: [{ visibility: "off" }]
            }
        ]
    });

    // Add click listener for coordinates
    this.map.addListener('click', (e) => {
        this.updateCoordinatesDisplay(e.latLng.lat(), e.latLng.lng());
    });
}
```

---

### Event Listeners

#### `setupEventListeners()`
**Purpose:** Bind UI button clicks to handler methods

```javascript
setupEventListeners() {
    // Load course button
    const loadBtn = document.getElementById('loadCourseBtn');
    if (loadBtn) {
        loadBtn.addEventListener('click', () => this.showCourseSelector());
    }

    // Refresh button
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => this.loadSavedCourses());
    }

    // Teacher location button
    const locationBtn = document.getElementById('locationBtn');
    if (locationBtn) {
        locationBtn.addEventListener('click', () => this.requestUserLocation());
    }

    // Toggle switches
    const boundaryToggle = document.getElementById('toggleBoundary');
    if (boundaryToggle) {
        boundaryToggle.addEventListener('change', (e) => {
            this.toggleBoundaryFill(e.target.checked);
        });
    }

    const optimalPathToggle = document.getElementById('toggleOptimalPath');
    if (optimalPathToggle) {
        optimalPathToggle.addEventListener('change', (e) => {
            this.toggleOptimalPath(e.target.checked);
        });
    }

    const poisToggle = document.getElementById('togglePOIs');
    if (poisToggle) {
        poisToggle.addEventListener('change', (e) => {
            this.togglePOIs(e.target.checked);
        });
    }

    // Map type selector
    const mapTypeSelect = document.getElementById('mapTypeSelect');
    if (mapTypeSelect) {
        mapTypeSelect.addEventListener('change', (e) => {
            this.changeMapType(e.target.value);
        });
    }
}
```

---

### Course Management

#### `loadSavedCourses()`
**Purpose:** Fetch courses from backend/JSON and populate UI list

```javascript
async loadSavedCourses() {
    const coursesList = document.getElementById('coursesList');
    if (!coursesList) return;

    try {
        // Fetch courses from JSON file
        const response = await fetch('/assets/data/courses.json?t=' + Date.now(), {
            cache: 'no-store'
        });
        const data = await response.json();
        
        if (!data.courses || data.courses.length === 0) {
            coursesList.innerHTML = '<p class="no-courses">Aucun parcours disponible</p>';
            return;
        }

        // Render course list
        coursesList.innerHTML = '';
        data.courses.forEach(course => {
            const courseItem = document.createElement('div');
            courseItem.className = 'course-item';
            courseItem.innerHTML = `
                <div class="course-item-header">
                    <strong>${course.name}</strong>
                    <span class="course-date">${this.formatDate(course.created_at)}</span>
                </div>
                <div class="course-item-body">
                    <p>${course.description || 'Aucune description'}</p>
                    <div class="course-stats">
                        <span>📍 ${course.waypoints ? course.waypoints.length : 0} balises</span>
                    </div>
                </div>
            `;
            
            courseItem.addEventListener('click', () => {
                this.loadCourse(course);
            });
            
            coursesList.appendChild(courseItem);
        });
    } catch (error) {
        console.error('Error loading courses:', error);
        coursesList.innerHTML = '<p class="error">Erreur de chargement</p>';
    }
}
```

#### `loadCourse(courseData)`
**Purpose:** Display selected course on map with waypoints and boundary

```javascript
loadCourse(courseData) {
    if (!this.map) {
        console.error('Map not initialized');
        return;
    }

    // Clear existing markers and polylines
    this.clearMarkers();
    this.clearPolylines();

    // Store course data
    this.controlPoints = courseData.waypoints || [];

    // Display waypoints as markers
    this.displayCoursePoints(courseData.waypoints);

    // Display course boundary
    if (courseData.boundary_points && courseData.boundary_points.length > 2) {
        this.displayBoundary(courseData.boundary_points);
    }

    // Draw course path
    this.drawCoursePath();

    // Fit map to show all markers
    this.fitMapToMarkers();

    // Update control points list in sidebar
    this.updateControlPointsList();
}
```

---

### Marker Management

#### `createMarker(point, index, type)`
**Purpose:** Create custom SVG marker for waypoint  
**Types:** `start`, `control`, `finish`

```javascript
createMarker(point, index, type) {
    if (!this.map) return null;

    // Determine marker properties based on type
    let color, label, borderColor;
    switch (type) {
        case 'start':
            color = '#28a745';      // Green
            label = 'S';
            borderColor = '#fff';
            break;
        case 'finish':
            color = '#dc3545';      // Red
            label = 'F';
            borderColor = '#fff';
            break;
        default: // 'control'
            color = '#007bff';      // Blue
            label = String(index);
            borderColor = '#fff';
    }

    // Create SVG marker icon
    const svgMarker = {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: color,
        fillOpacity: 1,
        strokeColor: borderColor,
        strokeWeight: 3,
        scale: 15
    };

    // Create marker
    const marker = new google.maps.Marker({
        position: { lat: point.latitude, lng: point.longitude },
        map: this.map,
        icon: svgMarker,
        title: point.name,
        label: {
            text: label,
            color: '#fff',
            fontSize: '12px',
            fontWeight: 'bold'
        },
        draggable: false
    });

    // Add info window
    const infoWindow = new google.maps.InfoWindow({
        content: `
            <div class="marker-info">
                <h3>${point.name}</h3>
                <p>${point.description || 'Aucune description'}</p>
                <p><strong>Type:</strong> ${type}</p>
                <p><strong>Coordonnées:</strong></p>
                <p>Lat: ${point.latitude.toFixed(6)}</p>
                <p>Lng: ${point.longitude.toFixed(6)}</p>
            </div>
        `
    });

    marker.addListener('click', () => {
        infoWindow.open(this.map, marker);
    });

    this.markers.push(marker);
    return marker;
}
```

#### `clearMarkers()`
**Purpose:** Remove all markers from map

```javascript
clearMarkers() {
    this.markers.forEach(marker => {
        marker.setMap(null);
    });
    this.markers = [];
}
```

---

### Path Drawing

#### `drawCoursePath()`
**Purpose:** Draw polyline connecting waypoints in sequence

```javascript
drawCoursePath() {
    if (this.coursePolyline) {
        this.coursePolyline.setMap(null);
    }

    if (this.controlPoints.length < 2) return;

    // Create path coordinates array
    const pathCoordinates = this.controlPoints.map(point => ({
        lat: point.latitude,
        lng: point.longitude
    }));

    // Create polyline
    this.coursePolyline = new google.maps.Polyline({
        path: pathCoordinates,
        geodesic: true,
        strokeColor: '#FF6B00',
        strokeOpacity: 0.8,
        strokeWeight: 3,
        map: this.map
    });
}
```

#### `displayBoundary(boundaryPoints)`
**Purpose:** Draw polygon showing course boundaries

```javascript
displayBoundary(boundaryPoints) {
    if (this.boundaryPolygon) {
        this.boundaryPolygon.setMap(null);
    }

    if (boundaryPoints.length < 3) return;

    // Create polygon
    this.boundaryPolygon = new google.maps.Polygon({
        paths: boundaryPoints,
        strokeColor: '#2c5530',
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: '#4a7c59',
        fillOpacity: this.boundaryFillVisible ? 0.2 : 0,
        map: this.map
    });
}
```

---

### GPS Location

#### `requestUserLocation()`
**Purpose:** Get teacher's current GPS position and display on map

```javascript
requestUserLocation() {
    if (!navigator.geolocation) {
        alert('La géolocalisation n\'est pas supportée par votre navigateur');
        return;
    }

    const locationBtn = document.getElementById('locationBtn');
    if (locationBtn) {
        locationBtn.disabled = true;
        locationBtn.textContent = '📡 Localisation...';
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const pos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            // Remove old location marker
            if (this.currentLocationMarker) {
                this.currentLocationMarker.setMap(null);
            }
            if (this.accuracyCircle) {
                this.accuracyCircle.setMap(null);
            }

            // Add new location marker
            this.currentLocationMarker = new google.maps.Marker({
                position: pos,
                map: this.map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: '#4285F4',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2
                },
                title: 'Ma position'
            });

            // Add accuracy circle
            this.accuracyCircle = new google.maps.Circle({
                strokeColor: '#4285F4',
                strokeOpacity: 0.5,
                strokeWeight: 1,
                fillColor: '#4285F4',
                fillOpacity: 0.2,
                map: this.map,
                center: pos,
                radius: position.coords.accuracy
            });

            // Center map on location
            this.map.setCenter(pos);
            this.map.setZoom(17);

            if (locationBtn) {
                locationBtn.disabled = false;
                locationBtn.textContent = '📍 Ma Position';
            }

            alert(`Position trouvée avec une précision de ${Math.round(position.coords.accuracy)} mètres`);
        },
        (error) => {
            console.error('Geolocation error:', error);
            alert('Impossible d\'obtenir votre position');
            
            if (locationBtn) {
                locationBtn.disabled = false;
                locationBtn.textContent = '📍 Ma Position';
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}
```

---

### Toggle Functions

#### `toggleBoundaryFill(visible)`
```javascript
toggleBoundaryFill(visible) {
    this.boundaryFillVisible = visible;
    if (this.boundaryPolygon) {
        this.boundaryPolygon.setOptions({
            fillOpacity: visible ? 0.2 : 0
        });
    }
}
```

#### `toggleOptimalPath(visible)`
```javascript
toggleOptimalPath(visible) {
    this.optimalPathVisible = visible;
    if (this.optimalPathPolyline) {
        this.optimalPathPolyline.setMap(visible ? this.map : null);
    }
}
```

#### `togglePOIs(visible)`
```javascript
togglePOIs(visible) {
    this.poisVisible = visible;
    if (this.map) {
        this.map.setOptions({
            styles: visible ? [] : [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ]
        });
    }
}
```

---

## 🔄 Utility Methods

### Date Formatting
```javascript
formatDate(dateString) {
    if (!dateString) return 'Date inconnue';
    
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return date.toLocaleDateString('fr-FR', options);
}
```

### Distance Calculation
```javascript
calculateDistance(point1, point2) {
    // Uses Google Maps Geometry library
    const p1 = new google.maps.LatLng(point1.latitude, point1.longitude);
    const p2 = new google.maps.LatLng(point2.latitude, point2.longitude);
    
    // Returns distance in meters
    return google.maps.geometry.spherical.computeDistanceBetween(p1, p2);
}
```

### Total Course Distance
```javascript
calculateTotalDistance() {
    if (this.controlPoints.length < 2) return 0;
    
    let totalDistance = 0;
    for (let i = 0; i < this.controlPoints.length - 1; i++) {
        totalDistance += this.calculateDistance(
            this.controlPoints[i],
            this.controlPoints[i + 1]
        );
    }
    
    // Convert meters to kilometers
    return (totalDistance / 1000).toFixed(2);
}
```

---

## 📊 State Management Pattern

### Initialization State
```javascript
// Check before operations
if (!this.initialized) {
    await this.init();
}

if (!this.mapsLoaded) {
    console.error('Google Maps not loaded');
    return;
}
```

### UI State Synchronization
```javascript
// Update UI to match internal state
updateUIState() {
    const boundaryToggle = document.getElementById('toggleBoundary');
    if (boundaryToggle) {
        boundaryToggle.checked = this.boundaryFillVisible;
    }

    const optimalPathToggle = document.getElementById('toggleOptimalPath');
    if (optimalPathToggle) {
        optimalPathToggle.checked = this.optimalPathVisible;
    }

    const poisToggle = document.getElementById('togglePOIs');
    if (poisToggle) {
        poisToggle.checked = this.poisVisible;
    }
}
```

---

## 🎯 Global Instance Management

### App Instantiation
```javascript
// Global initialization function (called from template)
window.initializeOrienteeringApp = async function() {
    if (!window.app) {
        window.app = new OrienteeringApp();
    }
    await window.app.init();
    return window.app;
};
```

### Template Integration
```twig
{# templates/map/index.html.twig #}
<script>
    async function initializeMapPage() {
        // Initialize app
        if (window.initializeOrienteeringApp) {
            await window.initializeOrienteeringApp();
        }
        
        // Load Google Maps
        if (window.google && window.google.maps) {
            initMapApp();
        } else {
            loadGoogleMaps();
        }
    }

    function initMapApp() {
        if (window.app && window.app.initializeMap) {
            window.app.initializeMap();
        }
    }
</script>
```

---

## 🐛 Debugging Tips

### Console Logging
```javascript
// Enable verbose logging
console.log('Map initialized:', this.map);
console.log('Control points:', this.controlPoints);
console.log('Markers:', this.markers);
```

### Common Issues
```javascript
// Issue: Map not displaying
// Check: Container exists before initialization
const mapContainer = document.getElementById('map');
if (!mapContainer) {
    console.error('Map container #map not found');
    return;
}

// Issue: Markers not showing
// Check: Valid coordinates
if (isNaN(point.latitude) || isNaN(point.longitude)) {
    console.error('Invalid coordinates:', point);
    return;
}

// Issue: Double initialization
// Check: Initialization flag
if (this.initialized) {
    console.log('Already initialized, skipping');
    return;
}
```

---

*This JavaScript architecture provides a robust foundation for the e-CO Web orienteering course visualization system.*
