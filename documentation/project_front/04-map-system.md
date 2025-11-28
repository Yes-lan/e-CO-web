# e-CO Web - Google Maps Integration System

**Last Updated:** November 21, 2025

---

## 🗺️ Google Maps Integration Overview

The e-CO Web application uses **Google Maps JavaScript API v3** with custom extensions for orienteering course visualization. This document covers the complete map system architecture.

---

## 🔑 API Configuration

### API Key Management

**Current Implementation (Development):**
```twig
{# templates/map/index.html.twig #}
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBi8sXKGGafzB837kvxraWuqohlZ-JJRu8&libraries=geometry,places,marker&loading=async&callback=initMap"></script>
```

**Production Implementation (Required):**
```yaml
# .env
GOOGLE_MAPS_API_KEY=your_production_key_here
```

```twig
{# templates/map/index.html.twig #}
<script src="https://maps.googleapis.com/maps/api/js?key={{ google_maps_key }}&libraries=geometry,places,marker&loading=async&callback=initMap"></script>
```

### Required API Libraries

| Library | Purpose | Usage |
|---------|---------|-------|
| **geometry** | Distance/area calculations | `computeDistanceBetween()`, `computeArea()` |
| **places** | Location search (future) | Autocomplete for addresses |
| **marker** | Advanced marker features | Custom SVG markers with labels |

---

## 🚀 Asynchronous Loading Strategy

### Why Async Loading?

```javascript
// ❌ BAD: Synchronous loading blocks page render
<script src="https://maps.googleapis.com/maps/api/js?key=..."></script>
<script>
  // Maps API might not be ready yet
  new google.maps.Map(...)
</script>

// ✅ GOOD: Async loading with callback
<script src="...&loading=async&callback=initMap"></script>
<script>
  function initMap() {
    // Guaranteed that Google Maps API is ready
    window.app.initializeMap();
  }
</script>
```

### Loading Sequence

```
1. Page HTML loads
   ↓
2. Turbo:load event fires
   ↓
3. initializeMapPage() called
   ↓
4. Check if Google Maps already loaded
   ↓
5a. YES: Call initMapApp() immediately
   ↓
5b. NO: Inject <script> tag with callback
   ↓
6. Google Maps loads asynchronously
   ↓
7. Callback initMap() fires
   ↓
8. initMapApp() → app.initializeMap()
   ↓
9. Map instance created
```

### Implementation

```javascript
// templates/map/index.html.twig
function loadGoogleMaps() {
    // Prevent multiple script loading
    if (window.googleMapsLoading) {
        console.log('Google Maps already loading...');
        return;
    }
    window.googleMapsLoading = true;
    
    const script = document.createElement('script');
    script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBi8sXKGGafzB837kvxraWuqohlZ-JJRu8&libraries=geometry,places,marker&loading=async&callback=initMap';
    script.async = true;
    script.defer = true;
    script.onerror = function() {
        console.error('Failed to load Google Maps');
        alert('Erreur de chargement de Google Maps. Veuillez actualiser la page.');
    };
    document.head.appendChild(script);
}

function initMap() {
    console.log('Google Maps API loaded successfully');
    window.googleMapsLoading = false;
    initMapApp();
}

function initMapApp() {
    if (window.app && window.app.initializeMap) {
        window.app.initializeMap();
    }
}
```

---

## 🎨 Map Initialization

### Map Instance Creation

```javascript
// public/assets/js/app.js - OrienteeringApp.initializeMap()
initializeMap() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.error('Map container #map not found');
        return;
    }

    // Get configuration
    const defaultLocation = this.config 
        ? this.config.defaultLocation 
        : { lat: 45.5017, lng: -73.5673 };
    
    const defaultZoom = this.config ? this.config.defaultZoom : 15;
    
    // Create map
    this.map = new google.maps.Map(mapContainer, {
        zoom: defaultZoom,
        center: defaultLocation,
        mapTypeId: google.maps.MapTypeId.HYBRID,
        
        // Controls
        mapTypeControl: true,
        mapTypeControlOptions: {
            style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
            mapTypeIds: [
                google.maps.MapTypeId.ROADMAP,
                google.maps.MapTypeId.SATELLITE,
                google.maps.MapTypeId.HYBRID,
                google.maps.MapTypeId.TERRAIN
            ]
        },
        streetViewControl: false,
        fullscreenControl: true,
        zoomControl: true,
        scaleControl: true,
        
        // Disable 3D for better point visualization
        tilt: 0,
        rotateControl: false,
        heading: 0,
        
        // POI control (initially hidden)
        styles: this.poisVisible ? [] : [
            {
                featureType: "poi",
                elementType: "labels",
                stylers: [{ visibility: "off" }]
            }
        ]
    });

    // Add event listeners
    this.map.addListener('click', (e) => {
        this.updateCoordinatesDisplay(e.latLng.lat(), e.latLng.lng());
    });
}
```

### Map Configuration JSON

**File:** `public/assets/data/map-config.json`

```json
{
  "defaultLocation": {
    "lat": 45.5017,
    "lng": -73.5673,
    "description": "Montreal, Quebec default location"
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

## 🎯 Custom Markers System

### Marker Types and Styling

| Type | Color | Label | Border | Usage |
|------|-------|-------|--------|-------|
| **Start** | #28a745 (Green) | "S" | White | Course starting point |
| **Control** | #007bff (Blue) | Number | White | Intermediate waypoints |
| **Finish** | #dc3545 (Red) | "F" | White | Course ending point |
| **Teacher** | #4285F4 (Google Blue) | Dot | White | Teacher's GPS location |

### Marker Creation

```javascript
createMarker(point, index, type) {
    if (!this.map) return null;

    // Determine marker properties
    let color, label, borderColor;
    switch (type) {
        case 'start':
            color = '#28a745';
            label = 'S';
            borderColor = '#fff';
            break;
        case 'finish':
            color = '#dc3545';
            label = 'F';
            borderColor = '#fff';
            break;
        default: // 'control'
            color = '#007bff';
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

### Info Window Styling

```css
/* public/assets/css/style.css */
.marker-info {
    font-family: 'Segoe UI', sans-serif;
    padding: 0.5rem;
    max-width: 250px;
}

.marker-info h3 {
    margin: 0 0 0.5rem 0;
    color: #2c5530;
    font-size: 1rem;
}

.marker-info p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
    color: #333;
}

.marker-info strong {
    color: #2c5530;
}
```

---

## 📐 Polylines and Polygons

### Course Path Polyline

```javascript
drawCoursePath() {
    // Clear existing polyline
    if (this.coursePolyline) {
        this.coursePolyline.setMap(null);
    }

    if (this.controlPoints.length < 2) return;

    // Create path coordinates
    const pathCoordinates = this.controlPoints.map(point => ({
        lat: point.latitude,
        lng: point.longitude
    }));

    // Draw polyline
    this.coursePolyline = new google.maps.Polyline({
        path: pathCoordinates,
        geodesic: true,           // Follow Earth's curvature
        strokeColor: '#FF6B00',   // Orange
        strokeOpacity: 0.8,
        strokeWeight: 3,
        map: this.map
    });
}
```

### Boundary Polygon

```javascript
displayBoundary(boundaryPoints) {
    // Clear existing polygon
    if (this.boundaryPolygon) {
        this.boundaryPolygon.setMap(null);
    }

    if (boundaryPoints.length < 3) return;

    // Create polygon
    this.boundaryPolygon = new google.maps.Polygon({
        paths: boundaryPoints,
        strokeColor: '#2c5530',   // Dark green
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: '#4a7c59',     // Light green
        fillOpacity: this.boundaryFillVisible ? 0.2 : 0,
        map: this.map,
        clickable: false
    });
}
```

### Optimal Path (Future Feature)

```javascript
displayOptimalPath(waypoints) {
    if (this.optimalPathPolyline) {
        this.optimalPathPolyline.setMap(null);
    }

    // Calculate optimal route (future: use routing API)
    const optimalPath = waypoints.map(wp => ({
        lat: wp.latitude,
        lng: wp.longitude
    }));

    this.optimalPathPolyline = new google.maps.Polyline({
        path: optimalPath,
        geodesic: true,
        strokeColor: '#00FF00',   // Bright green
        strokeOpacity: 0.6,
        strokeWeight: 2,
        strokePattern: [10, 5],   // Dashed line
        map: this.optimalPathVisible ? this.map : null
    });
}
```

---

## 📍 Geolocation Integration

### Teacher GPS Location

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

            // Clear old markers
            if (this.currentLocationMarker) {
                this.currentLocationMarker.setMap(null);
            }
            if (this.accuracyCircle) {
                this.accuracyCircle.setMap(null);
            }

            // Create location marker
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
                title: 'Ma position',
                zIndex: 1000
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
            let errorMsg = 'Impossible d\'obtenir votre position';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg = 'Permission de géolocalisation refusée';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg = 'Position non disponible';
                    break;
                case error.TIMEOUT:
                    errorMsg = 'Délai de géolocalisation dépassé';
                    break;
            }
            
            alert(errorMsg);
            
            if (locationBtn) {
                locationBtn.disabled = false;
                locationBtn.textContent = '📍 Ma Position';
            }
        },
        {
            enableHighAccuracy: true,  // Use GPS if available
            timeout: 10000,            // 10 second timeout
            maximumAge: 0              // Don't use cached position
        }
    );
}
```

---

## 📏 Distance and Area Calculations

### Using Geometry Library

```javascript
// Calculate distance between two points
calculateDistance(point1, point2) {
    const p1 = new google.maps.LatLng(point1.latitude, point1.longitude);
    const p2 = new google.maps.LatLng(point2.latitude, point2.longitude);
    
    // Returns distance in meters
    const distanceMeters = google.maps.geometry.spherical.computeDistanceBetween(p1, p2);
    
    return distanceMeters;
}

// Calculate total course distance
calculateTotalDistance() {
    if (this.controlPoints.length < 2) return 0;
    
    let totalDistance = 0;
    for (let i = 0; i < this.controlPoints.length - 1; i++) {
        totalDistance += this.calculateDistance(
            this.controlPoints[i],
            this.controlPoints[i + 1]
        );
    }
    
    // Convert to kilometers
    return (totalDistance / 1000).toFixed(2);
}

// Calculate polygon area
calculateBoundaryArea() {
    if (!this.boundaryPolygon) return 0;
    
    const path = this.boundaryPolygon.getPath();
    const areaMeters = google.maps.geometry.spherical.computeArea(path);
    
    // Convert to square kilometers
    return (areaMeters / 1000000).toFixed(3);
}
```

---

## 🎛️ Map Controls and UI

### Map Type Selector

```javascript
changeMapType(mapType) {
    if (!this.map) return;
    
    const typeMap = {
        'roadmap': google.maps.MapTypeId.ROADMAP,
        'satellite': google.maps.MapTypeId.SATELLITE,
        'hybrid': google.maps.MapTypeId.HYBRID,
        'terrain': google.maps.MapTypeId.TERRAIN
    };
    
    if (typeMap[mapType]) {
        this.map.setMapTypeId(typeMap[mapType]);
    }
}
```

### POI Toggle

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
                },
                {
                    featureType: "poi.business",
                    stylers: [{ visibility: "off" }]
                }
            ]
        });
    }
}
```

### Fit Map to Markers

```javascript
fitMapToMarkers() {
    if (!this.map || this.markers.length === 0) return;
    
    const bounds = new google.maps.LatLngBounds();
    
    // Include all markers
    this.markers.forEach(marker => {
        bounds.extend(marker.getPosition());
    });
    
    // Add padding
    this.map.fitBounds(bounds, {
        top: 50,
        right: 50,
        bottom: 50,
        left: 50
    });
}
```

---

## 🔄 Map State Management

### Cleanup on Navigation

```javascript
// templates/map/index.html.twig
document.addEventListener('turbo:before-visit', function() {
    console.log('Turbo: before-visit - cleaning up map');
    
    if (window.app && window.app.map) {
        // Clear all overlays
        window.app.clearMarkers();
        window.app.clearPolylines();
        
        // Nullify map instance
        window.app.map = null;
        window.app.mapsLoaded = false;
        window.app.initialized = false;
    }
    
    window.mapPageReady = false;
});
```

### Reinitialization

```javascript
// templates/map/index.html.twig
document.addEventListener('turbo:load', function() {
    setTimeout(function() {
        if (document.getElementById('map')) {
            initializeMapPage();
        }
    }, 100);
});
```

---

## 🐛 Common Map Issues and Solutions

### Issue: Map Not Displaying

**Symptoms:** Gray box, no map tiles  
**Causes:**
- API key invalid or restricted
- Container height not set
- Map initialized before container exists

**Solutions:**
```css
/* Ensure container has height */
#map {
    width: 100%;
    height: 500px;
    min-height: 400px;
}
```

```javascript
// Wait for container
const mapContainer = document.getElementById('map');
if (!mapContainer) {
    console.error('Map container not found');
    return;
}
```

### Issue: Markers Not Visible

**Causes:**
- Invalid coordinates (NaN, null, undefined)
- Coordinates outside map bounds
- Markers created before map initialized

**Solutions:**
```javascript
// Validate coordinates
if (isNaN(point.latitude) || isNaN(point.longitude)) {
    console.error('Invalid coordinates:', point);
    return;
}

// Ensure map exists
if (!this.map) {
    console.error('Map not initialized');
    return;
}
```

### Issue: Duplicate Map Instances

**Cause:** Turbo navigation without cleanup  
**Solution:** Use initialization flags

```javascript
if (this.map && this.mapsLoaded) {
    console.log('Map already initialized');
    return;
}
```

---

## 🚀 Future Enhancements

### Planned Features

1. **Route Optimization**
   - Use Google Directions API
   - Calculate optimal waypoint order
   - Display estimated walking time

2. **Elevation Profile**
   - Use Elevation API
   - Show course elevation changes
   - Difficulty rating based on terrain

3. **Street View Integration**
   - Preview waypoint locations
   - Help teachers identify landmarks
   - Virtual course walkthrough

4. **Drawing Tools**
   - Allow teachers to draw custom boundaries
   - Freehand course creation
   - Shape tools (circle, rectangle)

5. **Offline Maps**
   - Cache map tiles for offline use
   - Essential for remote locations
   - Progressive Web App (PWA) support

---

*This map system provides robust visualization capabilities for orienteering courses with room for future enhancements.*
