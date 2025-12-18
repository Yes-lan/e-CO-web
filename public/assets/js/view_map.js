/**
 * View Map - Handles Google Maps initialization and runner selection
 * for course and session view pages
 * 
 * Minimal implementation - leverages Doctrine entities and server-side data
 */

// Prevent redeclaration if the script is loaded multiple times (Turbo Drive)
if (typeof window.ViewMapWidget === 'undefined') {

window.ViewMapWidget = class ViewMapWidget {
    constructor(mapId) {
        this.mapId = mapId;
        this.mapElement = document.getElementById(mapId);
        
        if (!this.mapElement) {
            console.error(`Map element with ID "${mapId}" not found`);
            return;
        }

        this.courseId = this.mapElement.dataset.courseId;
        this.sessionId = this.mapElement.dataset.sessionId;
        this.beacons = [];
        
        // Read beacons from JSON script tag inside the map element
        const beaconsScript = this.mapElement.querySelector('script.beacons-data');
        if (beaconsScript) {
            try {
                this.beacons = JSON.parse(beaconsScript.textContent);
                console.log(`Loaded ${this.beacons.length} beacons for map ${mapId}`);
            } catch (e) {
                console.error('Error parsing beacons data:', e);
            }
        } else {
            console.warn(`No beacons script tag found in map ${mapId}`);
        }

        this.map = null;
        this.markers = [];
        this.selectedRunner = null;
        this.runnerMarkers = [];
        this.runnerPath = null;

        this.initMap();
        this.initRunnersList();
    }

    initMap() {
        if (!window.google || !window.google.maps) {
            console.warn('Google Maps not loaded yet, waiting...');
            window.addEventListener('load', () => this.initMap());
            return;
        }

        // Default center (will be adjusted to fit beacons)
        const defaultCenter = { lat: 43.4832, lng: -1.5586 }; // Bayonne, France

        this.map = new google.maps.Map(this.mapElement, {
            zoom: 15,
            center: defaultCenter,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            tilt: 0, // Disable 45° imagery (diagonal/tilt view)
            //rotateControl: false, // Disable rotation control
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
                position: google.maps.ControlPosition.TOP_RIGHT,
                mapTypeIds: [
                    google.maps.MapTypeId.ROADMAP,
                    google.maps.MapTypeId.SATELLITE,
                    google.maps.MapTypeId.HYBRID,
                    google.maps.MapTypeId.TERRAIN
                ]
            },
            streetViewControl: false,
            fullscreenControl: true,
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                },
                {
                    featureType: 'poi.business',
                    stylers: [{ visibility: 'off' }]
                },
                {
                    featureType: 'transit',
                    elementType: 'labels.icon',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });

        // Display beacons/waypoints
        this.displayBeacons();
    }

    displayBeacons() {
        if (!this.beacons || this.beacons.length === 0) {
            console.log('No beacons to display');
            return;
        }

        const bounds = new google.maps.LatLngBounds();
        
        // Check if start and finish beacons are at the same location
        const startBeacon = this.beacons.find(b => b.type === 'start');
        const finishBeacon = this.beacons.find(b => b.type === 'finish');
        let skipFinish = false;
        
        if (startBeacon && finishBeacon) {
            const sameLocation = 
                parseFloat(startBeacon.latitude) === parseFloat(finishBeacon.latitude) &&
                parseFloat(startBeacon.longitude) === parseFloat(finishBeacon.longitude);
            
            if (sameLocation) {
                console.log('Start and finish beacons at same location - merging markers');
                skipFinish = true;
            }
        }

        this.beacons.forEach((beacon, index) => {
            // Skip beacons that are not placed
            if (!beacon.latitude || !beacon.longitude || beacon.latitude === 0 || beacon.longitude === 0) {
                return;
            }
            
            // Skip finish beacon if it's at the same location as start
            if (skipFinish && beacon.type === 'finish') {
                return;
            }

            const position = {
                lat: parseFloat(beacon.latitude),
                lng: parseFloat(beacon.longitude)
            };

            // Determine beacon type icon - use teardrop pin shape
            let pinColor = '#2196F3'; // Blue for control beacons
            let beaconLabel = beacon.name ? beacon.name.toString() : (index + 1).toString();
            
            if (beacon.type === 'start') {
                pinColor = '#000000'; // Black for start
                // If start and finish are at same location, update label
                if (skipFinish) {
                    beaconLabel = 'Start/Finish';
                }
            } else if (beacon.type === 'finish') {
                pinColor = '#000000'; // Black for finish
            }

            const marker = new google.maps.Marker({
                position: position,
                map: this.map,
                icon: {
                    path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z',
                    fillColor: pinColor,
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 1,
                    anchor: new google.maps.Point(0, 0)
                },
                title: beacon.name || `Beacon ${index + 1}`,
                label: {
                    text: beaconLabel,
                    color: 'white',
                    fontSize: '12px',
                    fontWeight: 'bold',
                    className: 'beacon-label-with-outline' // CSS class for text outline
                }
            });

            // Add CSS for text outline if not already added
            if (!document.getElementById('beacon-label-style')) {
                const style = document.createElement('style');
                style.id = 'beacon-label-style';
                style.textContent = `
                    .beacon-label-with-outline {
                        text-shadow: 
                            -1px -1px 0 #000,  
                            1px -1px 0 #000,
                            -1px 1px 0 #000,
                            1px 1px 0 #000,
                            -2px 0 0 #000,
                            2px 0 0 #000,
                            0 -2px 0 #000,
                            0 2px 0 #000 !important;
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Determine info window content
            let beaconType = beacon.type || 'control';
            if (skipFinish && beacon.type === 'start') {
                beaconType = 'start/finish';
            }

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px; text-shadow: 1px 1px 2px rgba(0,0,0,0.8), -1px -1px 2px rgba(255,255,255,0.8);">
                        <h4 style="margin: 0 0 8px 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.8);">${beacon.name || 'Beacon'}</h4>
                        <p style="margin: 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><strong>Type:</strong> ${beaconType}</p>
                        <p style="margin: 4px 0 0 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><small>Lat: ${position.lat.toFixed(6)}, Lng: ${position.lng.toFixed(6)}</small></p>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(this.map, marker);
            });

            this.markers.push(marker);
            bounds.extend(position);
        });

        // Fit map to show all beacons
        if (this.markers.length > 0) {
            this.map.fitBounds(bounds);
        }
    }

    initRunnersList() {
        if (!this.sessionId) {
            return; // No session, no runners list
        }

        const runnersList = document.getElementById(`${this.mapId}-runners-list`);
        if (!runnersList) {
            return;
        }

        const runnerItems = runnersList.querySelectorAll('.runner-item');
        runnerItems.forEach(item => {
            item.addEventListener('click', () => {
                const runnerId = item.dataset.runnerId;
                this.selectRunner(runnerId, item);
            });
        });

        // Close details button
        const closeBtn = document.querySelector(`.close-details-btn[data-map-id="${this.mapId}"]`);
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.deselectRunner();
            });
        }
    }

    async selectRunner(runnerId, runnerElement) {
        // Update active state
        const allRunners = document.querySelectorAll(`#${this.mapId}-runners-list .runner-item`);
        allRunners.forEach(item => item.classList.remove('active'));
        runnerElement.classList.add('active');

        this.selectedRunner = runnerId;

        // Clear previous runner markers and path
        this.clearRunnerData();

        // Show runner details panel
        const detailsPanel = document.getElementById(`${this.mapId}-runner-details`);
        if (detailsPanel) {
            detailsPanel.style.display = 'block';
        }

        // Update runner name in header
        const runnerNameEl = document.getElementById(`${this.mapId}-runner-name`);
        if (runnerNameEl) {
            runnerNameEl.textContent = runnerElement.querySelector('.runner-name span:last-child').textContent;
        }

        // Fetch runner GPS logs from API
        try {
            const response = await fetch(`/runners/${runnerId}/logs`);
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error Response:', response.status, errorText);
                throw new Error(`Failed to fetch runner logs: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            this.displayRunnerPath(data.logs || [], data.waypoints || []);
            this.displayRunnerWaypoints(data.waypoints || []);
        } catch (error) {
            console.error('Error loading runner data:', error);
            // Show error message in table
            const tableBody = document.getElementById(`${this.mapId}-waypoints-table`);
            if (tableBody) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="3" style="text-align: center; color: #f44336;">
                            Error loading runner data: ${error.message}
                        </td>
                    </tr>
                `;
            }
        }
    }

    displayRunnerPath(logs, waypoints) {
        if (!logs || logs.length === 0) {
            return;
        }

        // Combine GPS logs and beacon scans, sorted by timestamp
        const allPoints = [];
        
        // Add GPS points
        logs.forEach(log => {
            allPoints.push({
                lat: parseFloat(log.latitude),
                lng: parseFloat(log.longitude),
                timestamp: log.timestamp,
                type: 'gps'
            });
        });
        
        // Add beacon scan points
        if (waypoints && waypoints.length > 0) {
            waypoints.forEach(wp => {
                allPoints.push({
                    lat: parseFloat(wp.latitude),
                    lng: parseFloat(wp.longitude),
                    timestamp: wp.timestamp,
                    type: 'beacon_scan'
                });
            });
        }
        
        // Sort by timestamp to create accurate path
        allPoints.sort((a, b) => {
            const timeA = a.timestamp ? new Date(a.timestamp) : new Date(0);
            const timeB = b.timestamp ? new Date(b.timestamp) : new Date(0);
            return timeA - timeB;
        });

        const pathCoordinates = allPoints.map(point => ({
            lat: point.lat,
            lng: point.lng
        }));

        // Draw path line in purple
        this.runnerPath = new google.maps.Polyline({
            path: pathCoordinates,
            geodesic: true,
            strokeColor: '#9C27B0', // Purple
            strokeOpacity: 0.8,
            strokeWeight: 3,
            map: this.map
        });

        // Add GPS point markers with info windows
        logs.forEach((log, index) => {
            const position = {
                lat: parseFloat(log.latitude),
                lng: parseFloat(log.longitude)
            };

            // Small circle marker for GPS points
            const marker = new google.maps.Marker({
                position: position,
                map: this.map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 4,
                    fillColor: '#9C27B0', // Purple
                    fillOpacity: 0.7,
                    strokeColor: 'white',
                    strokeWeight: 1
                },
                title: `GPS Point ${index + 1}`
            });

            // Info window for GPS point
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px; text-shadow: 1px 1px 2px rgba(0,0,0,0.8), -1px -1px 2px rgba(255,255,255,0.8);">
                        <h4 style="margin: 0 0 8px 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.8);">GPS Point ${index + 1}</h4>
                        <p style="margin: 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><strong>Time:</strong> ${log.timestamp ? new Date(log.timestamp).toLocaleString('fr-FR') : 'N/A'}</p>
                        <p style="margin: 4px 0 0 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><small>Lat: ${position.lat.toFixed(6)}, Lng: ${position.lng.toFixed(6)}</small></p>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(this.map, marker);
            });

            this.runnerMarkers.push(marker);
        });

        // Fit bounds to show runner's path
        const bounds = new google.maps.LatLngBounds();
        pathCoordinates.forEach(coord => bounds.extend(coord));
        this.map.fitBounds(bounds);
    }

    displayRunnerWaypoints(waypoints) {
        const tableBody = document.getElementById(`${this.mapId}-waypoints-table`);
        if (!tableBody) {
            return;
        }

        if (!waypoints || waypoints.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align: center; color: #999;">
                        No waypoint data available for this runner.
                    </td>
                </tr>
            `;
            return;
        }

        // Add beacon scan markers to map
        waypoints.forEach((wp, index) => {
            const position = {
                lat: parseFloat(wp.latitude),
                lng: parseFloat(wp.longitude)
            };

            const isValid = wp.isValid !== undefined ? wp.isValid : wp.validated;
            const distance = wp.distance !== undefined ? wp.distance : null;
            
            // Marker color: green if valid, red if invalid
            const markerColor = isValid ? '#4CAF50' : '#F44336';

            const marker = new google.maps.Marker({
                position: position,
                map: this.map,
                icon: {
                    path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z',
                    fillColor: markerColor,
                    fillOpacity: 0.9,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 1,
                    anchor: new google.maps.Point(0, 0)
                },
                title: `Beacon Scan: ${wp.beaconName || 'Unknown'}`
            });

            // Info window for beacon scan
            const distanceText = distance !== null ? `${distance.toFixed(1)} m` : 'N/A';
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px; text-shadow: 1px 1px 2px rgba(0,0,0,0.8), -1px -1px 2px rgba(255,255,255,0.8);">
                        <h4 style="margin: 0 0 8px 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.8);">${wp.beaconName || 'Unknown Beacon'}</h4>
                        <p style="margin: 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><strong>Status:</strong> 
                            <span style="color: ${isValid ? '#4CAF50' : '#F44336'}; font-weight: bold;">
                                ${isValid ? '✓ Valid' : '✗ Invalid'}
                            </span>
                        </p>
                        <p style="margin: 4px 0 0 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><strong>Distance:</strong> ${distanceText}</p>
                        <p style="margin: 4px 0 0 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><strong>Time:</strong> ${wp.timestamp ? new Date(wp.timestamp).toLocaleString('fr-FR') : 'N/A'}</p>
                        <p style="margin: 4px 0 0 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><small>Lat: ${position.lat.toFixed(6)}, Lng: ${position.lng.toFixed(6)}</small></p>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(this.map, marker);
            });

            this.runnerMarkers.push(marker);
        });

        // Update table
        tableBody.innerHTML = waypoints.map(wp => {
            const isValid = wp.isValid !== undefined ? wp.isValid : wp.validated;
            const distance = wp.distance !== undefined ? wp.distance : null;
            const distanceText = distance !== null ? `${distance.toFixed(1)} m` : 'N/A';
            
            return `
                <tr>
                    <td>${wp.beaconName || wp.beaconId || 'Unknown'}</td>
                    <td>
                        <span style="color: ${isValid ? '#4CAF50' : '#F44336'}; font-weight: bold;">
                            ${isValid ? '✓ Valid' : '✗ Invalid'}
                        </span>
                    </td>
                    <td>${distanceText}</td>
                    <td>${wp.timestamp ? new Date(wp.timestamp).toLocaleString('fr-FR') : 'N/A'}</td>
                </tr>
            `;
        }).join('');
    }

    deselectRunner() {
        this.selectedRunner = null;

        // Remove active state from all runners
        const allRunners = document.querySelectorAll(`#${this.mapId}-runners-list .runner-item`);
        allRunners.forEach(item => item.classList.remove('active'));

        // Hide details panel
        const detailsPanel = document.getElementById(`${this.mapId}-runner-details`);
        if (detailsPanel) {
            detailsPanel.style.display = 'none';
        }

        // Clear runner data from map
        this.clearRunnerData();

        // Reset map to show all beacons
        if (this.markers.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            this.markers.forEach(marker => bounds.extend(marker.getPosition()));
            this.map.fitBounds(bounds);
        }
    }

    clearRunnerData() {
        // Remove runner path
        if (this.runnerPath) {
            this.runnerPath.setMap(null);
            this.runnerPath = null;
        }

        // Remove runner markers
        this.runnerMarkers.forEach(marker => marker.setMap(null));
        this.runnerMarkers = [];
    }
}

// End of ViewMapWidget class definition guard
}

// Track initialized maps to prevent duplicates
if (!window.initializedMaps) {
    window.initializedMaps = new Set();
}

// Initialize all map widgets when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Google Maps to load
    const initAllMaps = () => {
        if (!window.google || !window.google.maps) {
            console.log('Waiting for Google Maps API...');
            setTimeout(initAllMaps, 100);
            return;
        }

        // Find all map widgets and initialize them
        const mapElements = document.querySelectorAll('.google-map');
        mapElements.forEach(mapEl => {
            const mapId = mapEl.id;
            if (mapId && !window.initializedMaps.has(mapId)) {
                console.log(`Initializing map widget: ${mapId}`);
                window.initializedMaps.add(mapId);
                new window.ViewMapWidget(mapId);
            }
        });
    };

    // Start initialization
    initAllMaps();
});

// Support for Turbo Drive (if used)
document.addEventListener('turbo:load', function() {
    if (!window.google || !window.google.maps) {
        console.log('Turbo: Waiting for Google Maps API...');
        return;
    }
    
    const mapElements = document.querySelectorAll('.google-map');
    mapElements.forEach(mapEl => {
        const mapId = mapEl.id;
        if (mapId && !window.initializedMaps.has(mapId)) {
            console.log(`Turbo: Initializing map widget: ${mapId}`);
            window.initializedMaps.add(mapId);
            new window.ViewMapWidget(mapId);
        }
    });
});
