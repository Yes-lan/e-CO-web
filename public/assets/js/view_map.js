/**
 * View Map - Handles Google Maps initialization and runner selection
 * for course and session view pages
 * 
 * Minimal implementation - leverages Doctrine entities and server-side data
 */

class ViewMapWidget {
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
            } catch (e) {
                console.error('Error parsing beacons data:', e);
            }
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

        this.beacons.forEach((beacon, index) => {
            // Skip beacons that are not placed
            if (!beacon.latitude || !beacon.longitude || beacon.latitude === 0 || beacon.longitude === 0) {
                return;
            }

            const position = {
                lat: parseFloat(beacon.latitude),
                lng: parseFloat(beacon.longitude)
            };

            // Determine beacon type icon
            let icon = {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: '#2196F3',
                fillOpacity: 1,
                strokeColor: 'white',
                strokeWeight: 2
            };

            if (beacon.type === 'start') {
                icon.fillColor = '#000000'; // Black for start
            } else if (beacon.type === 'finish') {
                icon.fillColor = '#000000'; // Black for finish
            }

            const marker = new google.maps.Marker({
                position: position,
                map: this.map,
                icon: icon,
                title: beacon.name || `Beacon ${index + 1}`,
                label: {
                    text: beacon.name ? beacon.name.toString() : (index + 1).toString(),
                    color: 'white',
                    fontSize: '12px',
                    fontWeight: 'bold'
                }
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px;">
                        <h4 style="margin: 0 0 8px 0;">${beacon.name || 'Beacon'}</h4>
                        <p style="margin: 0;"><strong>Type:</strong> ${beacon.type || 'control'}</p>
                        <p style="margin: 4px 0 0 0;"><small>Lat: ${position.lat.toFixed(6)}, Lng: ${position.lng.toFixed(6)}</small></p>
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
            this.displayRunnerPath(data.logs || []);
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

    displayRunnerPath(logs) {
        if (!logs || logs.length === 0) {
            return;
        }

        const pathCoordinates = logs.map(log => ({
            lat: parseFloat(log.latitude),
            lng: parseFloat(log.longitude)
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
                    <div style="padding: 8px;">
                        <h4 style="margin: 0 0 8px 0;">GPS Point ${index + 1}</h4>
                        <p style="margin: 0;"><strong>Time:</strong> ${log.timestamp ? new Date(log.timestamp).toLocaleString('fr-FR') : 'N/A'}</p>
                        <p style="margin: 4px 0 0 0;"><small>Lat: ${position.lat.toFixed(6)}, Lng: ${position.lng.toFixed(6)}</small></p>
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
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: markerColor,
                    fillOpacity: 0.9,
                    strokeColor: 'white',
                    strokeWeight: 2
                },
                title: `Beacon Scan: ${wp.beaconName || 'Unknown'}`
            });

            // Info window for beacon scan
            const distanceText = distance !== null ? `${distance.toFixed(1)} m` : 'N/A';
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px;">
                        <h4 style="margin: 0 0 8px 0;">${wp.beaconName || 'Unknown Beacon'}</h4>
                        <p style="margin: 0;"><strong>Status:</strong> 
                            <span style="color: ${isValid ? '#4CAF50' : '#F44336'}; font-weight: bold;">
                                ${isValid ? '✓ Valid' : '✗ Invalid'}
                            </span>
                        </p>
                        <p style="margin: 4px 0 0 0;"><strong>Distance:</strong> ${distanceText}</p>
                        <p style="margin: 4px 0 0 0;"><strong>Time:</strong> ${wp.timestamp ? new Date(wp.timestamp).toLocaleString('fr-FR') : 'N/A'}</p>
                        <p style="margin: 4px 0 0 0;"><small>Lat: ${position.lat.toFixed(6)}, Lng: ${position.lng.toFixed(6)}</small></p>
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
            if (mapId) {
                console.log(`Initializing map widget: ${mapId}`);
                new ViewMapWidget(mapId);
            }
        });
    };

    // Start initialization
    initAllMaps();
});

// Support for Turbo Drive (if used)
document.addEventListener('turbo:load', function() {
    const mapElements = document.querySelectorAll('.google-map');
    mapElements.forEach(mapEl => {
        const mapId = mapEl.id;
        if (mapId && window.google && window.google.maps) {
            new ViewMapWidget(mapId);
        }
    });
});
