/**
 * View Map - Handles Google Maps initialization and runner selection
 * for course and session view pages
 * 
 * Supports two modes:
 * 1. ViewMapWidget - Embedded maps in course/session detail pages
 * 2. MapViewer - Full-page dedicated map viewer with session selection
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
                },
                zIndex: 2000 // Keep beacons on top of scan markers and GPS path
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
                    .beacon-scan-label-outline {
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
            const beaconName = wp.beaconName || 'Unknown';

            const markerOptions = {
                position: position,
                map: this.map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: markerColor,
                    fillOpacity: 0.8,
                    strokeColor: '#ffffff',
                    strokeWeight: 3,
                    scale: 12
                },
                title: `Beacon Scan: ${beaconName}`,
                zIndex: 1000
            };

            // Only show label on invalid scans
            if (!isValid) {
                markerOptions.label = {
                    text: beaconName,
                    color: 'white',
                    fontSize: '11px',
                    fontWeight: 'bold',
                    className: 'beacon-scan-label-outline'
                };
            }

            const marker = new google.maps.Marker(markerOptions);

            // Info window for beacon scan
            const distanceText = distance !== null ? `${distance.toFixed(1)} m` : 'N/A';
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px; text-shadow: 1px 1px 2px rgba(0,0,0,0.8), -1px -1px 2px rgba(255,255,255,0.8);">
                        <h4 style="margin: 0 0 8px 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.8);">Scan: ${beaconName}</h4>
                        <p style="margin: 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><strong>Status:</strong> 
                            <span style="color: ${isValid ? '#4CAF50' : '#F44336'}; font-weight: bold;">
                                ${isValid ? '✓ Valid' : '✗ Invalid'}
                            </span>
                        </p>
                        <p style="margin: 4px 0 0 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><strong>Distance:</strong> ${distanceText}</p>
                        <p style="margin: 4px 0 0 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><strong>Time:</strong> ${wp.timestamp ? new Date(wp.timestamp).toLocaleString('fr-FR') : 'N/A'}</p>
                        <p style="margin: 4px 0 0 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"><small>Scan location: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}</small></p>
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

// MapViewer - Full-page map viewer for dedicated map page
if (typeof window.MapViewer === 'undefined') {

window.MapViewer = class MapViewer {
    constructor() {
        this.currentSession = null;
        this.currentCourse = null;
        this.selectedRunnerId = null;
        this.map = null;
        this.markers = [];
        this.labels = [];
        this.runnerPaths = [];
        this.scanMarkers = [];
        
        // Wait for Google Maps to load before initializing
        if (window.google && window.google.maps) {
            this.init();
        } else {
            window.addEventListener('load', () => this.init());
        }
    }
    
    init() {
        this.initMap();
        this.loadSessionData();
        
        // Make selectRunner globally accessible for onclick handlers
        window.selectRunner = (runnerId) => this.selectRunner(runnerId);
    }
    
    // Translation helper
    trans(key) {
        if (typeof translations !== 'undefined' && translations.get) {
            return translations.get(key, document.documentElement.lang || 'fr');
        }
        return key; // Fallback to key if translations not available
    }
    
    // Initialize Google Maps
    initMap() {
        this.map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: 43.3, lng: -1.5 },
            zoom: 13,
            mapTypeId: 'roadmap',
            streetViewControl: false,
            rotateControl: false,
            tilt: 0,
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                },
                {
                    featureType: 'transit',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });
    }
    
    // Load session data based on URL parameters
    loadSessionData() {
        const urlParams = new URLSearchParams(window.location.search);
        const sessionId = urlParams.get('sessionId');
        const courseId = urlParams.get('courseId');
        
        if (courseId && !sessionId) {
            this.showCourseOnlyView();
            this.loadCourseOnlyData(courseId);
        } else if (courseId) {
            this.showSessionView();
            this.loadSessionsForCourse(courseId, sessionId);
        } else if (sessionId) {
            this.showSessionView();
            this.loadSessions(sessionId);
        } else {
            this.showSessionView();
            this.loadSessions();
        }
    }
    
    showCourseOnlyView() {
        document.getElementById('leftColumn').style.display = 'none';
        document.getElementById('beaconValidationPanel').style.display = 'none';
        document.getElementById('courseInfoPanel').style.display = 'block';
        document.getElementById('mapPanel').style.flex = '1';
    }
    
    showSessionView() {
        document.getElementById('leftColumn').style.display = 'block';
        document.getElementById('beaconValidationPanel').style.display = 'block';
        document.getElementById('courseInfoPanel').style.display = 'none';
        document.getElementById('mapPanel').style.flex = '2';
    }
    
    async loadCourseOnlyData(courseId) {
        try {
            const courseRes = await (window.AuthManager ? AuthManager.fetch(`/api/parcours/${courseId}`) : fetch(`/api/parcours/${courseId}`));
            const courseData = await courseRes.json();
            const course = courseData.parcours || courseData;
            
            this.currentCourse = course;
            this.displayCourseInfo(course);
            this.updateMap();
        } catch (error) {
            console.error('Error loading course data:', error);
        }
    }
    
    displayCourseInfo(course) {
        const content = document.getElementById('courseInfoContent');
        const createdAt = course.createdAt ? new Date(course.createdAt).toLocaleDateString('fr-FR') : 'N/A';
        const beaconCount = course.waypoints ? course.waypoints.length : 0;
        
        content.innerHTML = `
            <div class="info-item">
                <strong>Nom:</strong> ${course.name || 'N/A'}
            </div>
            <div class="info-item">
                <strong>Description:</strong> ${course.description || 'Aucune description'}
            </div>
            <div class="info-item">
                <strong>Statut:</strong> 
                <span class="status-badge ${course.status}">${course.status === 'finished' ? 'Terminé' : 'En cours'}</span>
            </div>
            <div class="info-item">
                <strong>Date de création:</strong> ${createdAt}
            </div>
            <div class="info-item">
                <strong>Nombre de balises:</strong> ${beaconCount}
            </div>
            ${course.waypoints && course.waypoints.length > 0 ? `
            <div class="info-item">
                <strong>Balises:</strong>
                <ul class="beacon-list">
                    ${course.waypoints.map((w, i) => `
                        <li>
                            <span class="beacon-number">${i + 1}</span>
                            <span class="beacon-name">${w.name || 'Balise ' + (i + 1)}</span>
                        </li>
                    `).join('')}
                </ul>
            </div>
            ` : ''}
        `;
    }
    
    async loadSessions(autoSelectSessionId = null) {
        try {
            if (autoSelectSessionId) {
                await this.loadSession(autoSelectSessionId);
            }
        } catch (error) {
            console.error('Error loading sessions:', error);
        }
    }
    
    async loadSessionsForCourse(courseId, autoSelectSessionId = null) {
        try {
            const courseRes = await (window.AuthManager ? AuthManager.fetch(`/api/parcours/${courseId}`) : fetch(`/api/parcours/${courseId}`));
            const courseData = await courseRes.json();
            const course = courseData.parcours || courseData;
            
            this.currentCourse = course;
            this.updateMap();
            
            if (autoSelectSessionId) {
                await this.loadSession(autoSelectSessionId);
            }
        } catch (error) {
            console.error('Error loading sessions for course:', error);
        }
    }
    
    async loadSession(sessionId) {
        if (!sessionId) {
            this.clearData();
            return;
        }
        
        try {
            const response = await (window.AuthManager ? AuthManager.fetch(`/api/sessions/${sessionId}`) : fetch(`/api/sessions/${sessionId}`));
            this.currentSession = await response.json();
            
            if (this.currentSession.course) {
                await this.loadCourseData(this.currentSession.course.id);
            }
            
            this.displayRunners();
            this.updateMap();
        } catch (error) {
            console.error('Error loading session:', error);
        }
    }
    
    async loadCourseData(courseId) {
        try {
            const response = await (window.AuthManager ? AuthManager.fetch(`/api/parcours/${courseId}`) : fetch(`/api/parcours/${courseId}`));
            const data = await response.json();
            this.currentCourse = data.parcours || data;
        } catch (error) {
            console.error('Error loading course:', error);
        }
    }
    
    displayRunners() {
        const tbody = document.getElementById('runnersTableBody');
        
        if (!this.currentSession || !this.currentSession.runners || this.currentSession.runners.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" class="no-data">${this.trans('map.no_runners')}</td></tr>`;
            return;
        }
        
        tbody.innerHTML = this.currentSession.runners.map(runner => {
            const beaconsValidated = this.calculateBeaconsValidated(runner);
            const completionTime = this.calculateCompletionTime(runner);
            
            return `
                <tr onclick="selectRunner(${runner.id})" id="runner-${runner.id}">
                    <td>${runner.name}</td>
                    <td>${beaconsValidated} / ${this.currentCourse ? this.currentCourse.waypoints.length : 0}</td>
                    <td>${completionTime}</td>
                </tr>
            `;
        }).join('');
    }
    
    calculateBeaconsValidated(runner) {
        if (!runner.logSessions || !this.currentCourse) return 0;
        
        const allBeacons = [];
        if (this.currentCourse.startBeacon) allBeacons.push(this.currentCourse.startBeacon);
        if (this.currentCourse.finishBeacon && !this.currentCourse.sameStartFinish) allBeacons.push(this.currentCourse.finishBeacon);
        if (this.currentCourse.waypoints) allBeacons.push(...this.currentCourse.waypoints);
        
        const beaconScans = runner.logSessions.filter(log => log.type === 'beacon_scan');
        const validBeacons = new Set();
        
        beaconScans.forEach(scan => {
            const beaconId = scan.additionalData;
            const beacon = allBeacons.find(b => b.id == beaconId);
            
            if (beacon && scan.latitude && scan.longitude) {
                // Beacons use 'lat'/'lng', scans use 'latitude'/'longitude'
                const beaconLat = beacon.latitude || beacon.lat;
                const beaconLng = beacon.longitude || beacon.lng;
                
                if (!beaconLat || !beaconLng) return;
                
                const distance = this.calculateDistance(
                    scan.latitude, scan.longitude,
                    beaconLat, beaconLng
                );
                
                if (distance <= 20) {
                    validBeacons.add(beaconId);
                }
            }
        });
        
        return validBeacons.size;
    }
    
    calculateCompletionTime(runner) {
        if (!runner.departure || !runner.arrival) return '-';
        
        const departure = new Date(runner.departure);
        const arrival = new Date(runner.arrival);
        const diff = arrival - departure;
        
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
    
    selectRunner(runnerId) {
        this.selectedRunnerId = runnerId;
        
        document.querySelectorAll('.runners-table tbody tr').forEach(tr => {
            tr.classList.remove('selected');
        });
        document.getElementById(`runner-${runnerId}`)?.classList.add('selected');
        
        const runner = this.currentSession.runners.find(r => r.id === runnerId);
        const badge = document.getElementById('runnerNameBadge');
        if (runner) {
            badge.textContent = runner.name;
            badge.style.display = 'block';
        }
        
        this.loadRunnerPath(runnerId);
        this.displayBeaconValidation(runnerId);
    }
    
    async loadRunnerPath(runnerId) {
        try {
            this.runnerPaths.forEach(path => path.setMap(null));
            this.runnerPaths = [];
            this.scanMarkers.forEach(marker => marker.setMap(null));
            this.scanMarkers = [];
            
            const runner = this.currentSession.runners.find(r => r.id === runnerId);
            if (!runner || !runner.logSessions) return;
            
            const gpsLogs = runner.logSessions
                .filter(log => log.type === 'gps' && log.latitude && log.longitude)
                .sort((a, b) => new Date(a.time) - new Date(b.time));
            
            const beaconScans = runner.logSessions
                .filter(log => log.type === 'beacon_scan' && log.latitude && log.longitude)
                .sort((a, b) => new Date(a.time) - new Date(b.time));
            
            const allLogs = [...gpsLogs, ...beaconScans].sort((a, b) => new Date(a.time) - new Date(b.time));
            
            if (allLogs.length > 0) {
                let pathSegment = [];
                
                for (let i = 0; i < allLogs.length; i++) {
                    const log = allLogs[i];
                    pathSegment.push({ lat: log.latitude, lng: log.longitude });
                    
                    if (log.type === 'beacon_scan' || i === allLogs.length - 1) {
                        if (pathSegment.length >= 2) {
                            const polyline = new google.maps.Polyline({
                                path: pathSegment,
                                geodesic: true,
                                strokeColor: '#9C27B0',
                                strokeOpacity: 0.9,
                                strokeWeight: 3,
                                map: this.map,
                                icons: [{
                                    icon: {
                                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                                        scale: 4,
                                        strokeColor: '#FFFFFF',
                                        strokeWeight: 1.5,
                                        fillColor: '#9C27B0',
                                        fillOpacity: 1
                                    },
                                    offset: '50%'
                                }]
                            });
                            this.runnerPaths.push(polyline);
                        }
                        pathSegment = log.type === 'beacon_scan' ? [{ lat: log.latitude, lng: log.longitude }] : [];
                    }
                }
            }
            
            gpsLogs.forEach(log => {
                const logTime = new Date(log.time);
                const sessionStartTime = runner.departure ? new Date(runner.departure) : null;
                const elapsedSeconds = sessionStartTime ? Math.floor((logTime - sessionStartTime) / 1000) : null;
                const elapsedFormatted = elapsedSeconds !== null ? this.formatElapsedTime(elapsedSeconds) : '-';
                
                const gpsMarker = new google.maps.Marker({
                    position: { lat: log.latitude, lng: log.longitude },
                    map: this.map,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 3,
                        fillColor: '#9C27B0',
                        fillOpacity: 1,
                        strokeColor: '#FFFFFF',
                        strokeWeight: 1
                    },
                    title: `GPS: ${logTime.toLocaleTimeString('fr-FR')}`,
                    zIndex: 500
                });
                
                const gpsInfoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 4px; font-size: 12px; min-width: 120px;">
                            ${this.trans('map.time')}: ${logTime.toLocaleTimeString('fr-FR')}<br>
                            ${this.trans('map.elapsed')}: ${elapsedFormatted}
                        </div>
                    `
                });
                
                gpsMarker.addListener('click', () => {
                    gpsInfoWindow.open(this.map, gpsMarker);
                });
                
                this.scanMarkers.push(gpsMarker);
            });
            
            beaconScans.forEach(scan => {
                const beaconId = parseInt(scan.additionalData);
                
                const allBeacons = [];
                if (this.currentCourse.startBeacon) allBeacons.push(this.currentCourse.startBeacon);
                if (this.currentCourse.finishBeacon && !this.currentCourse.sameStartFinish) allBeacons.push(this.currentCourse.finishBeacon);
                if (this.currentCourse.waypoints) allBeacons.push(...this.currentCourse.waypoints);
                
                const beacon = allBeacons.find(b => b.id === beaconId);
                
                if (beacon) {
                    // Beacons use 'lat'/'lng', scans use 'latitude'/'longitude'
                    const beaconLat = beacon.latitude || beacon.lat;
                    const beaconLng = beacon.longitude || beacon.lng;
                    
                    if (!beaconLat || !beaconLng) return;
                    
                    const distance = this.calculateDistance(
                        scan.latitude, scan.longitude,
                        beaconLat, beaconLng
                    );
                    
                    const isValid = distance <= 20;
                    const markerColor = isValid ? '#4CAF50' : '#F44336';
                    
                    const scanTime = new Date(scan.time);
                    const sessionStartTime = runner.departure ? new Date(runner.departure) : null;
                    const elapsedSeconds = sessionStartTime ? Math.floor((scanTime - sessionStartTime) / 1000) : null;
                    const elapsedFormatted = elapsedSeconds !== null ? this.formatElapsedTime(elapsedSeconds) : '-';
                    
                    const scanMarker = new google.maps.Marker({
                        position: { lat: scan.latitude, lng: scan.longitude },
                        map: this.map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8,
                            fillColor: markerColor,
                            fillOpacity: 0.9,
                            strokeColor: '#FFFFFF',
                            strokeWeight: 2
                        },
                        title: `Scan ${beacon.name} - ${isValid ? 'Valid' : 'Invalid'} (${distance.toFixed(1)}m)`,
                        zIndex: 1000
                    });
                    
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 4px; font-size: 12px; min-width: 140px;">
                                <strong>${beacon.name}</strong><br>
                                ${this.trans('map.distance')}: ${distance.toFixed(1)}m<br>
                                ${this.trans('map.status')}: <span style="color: ${markerColor}; font-weight: bold;">
                                    ${isValid ? '✓ ' + this.trans('map.valid') : '✗ ' + this.trans('map.invalid')}
                                </span><br>
                                ${this.trans('map.time')}: ${scanTime.toLocaleTimeString('fr-FR')}<br>
                                ${this.trans('map.elapsed')}: ${elapsedFormatted}
                            </div>
                        `
                    });
                    
                    scanMarker.addListener('click', () => {
                        infoWindow.open(this.map, scanMarker);
                    });
                    
                    this.scanMarkers.push(scanMarker);
                }
            });
            
            if (allLogs.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                allLogs.forEach(log => {
                    bounds.extend({ lat: log.latitude, lng: log.longitude });
                });
                
                if (this.currentCourse && this.currentCourse.waypoints) {
                    this.currentCourse.waypoints.forEach(beacon => {
                        if (beacon.lat && beacon.lng) {
                            bounds.extend({ lat: beacon.lat, lng: beacon.lng });
                        }
                    });
                }
                
                this.map.fitBounds(bounds);
                const padding = { top: 50, right: 50, bottom: 50, left: 50 };
                this.map.fitBounds(bounds, padding);
            }
            
        } catch (error) {
            console.error('Error loading runner path:', error);
        }
    }
    
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3;
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;
        
        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        
        return R * c;
    }
    
    formatElapsedTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
    
    displayBeaconValidation(runnerId) {
        if (!this.currentCourse || !this.currentCourse.waypoints) {
            document.getElementById('beaconValidationContent').innerHTML = `<div class="no-data">${this.trans('map.no_course_data')}</div>`;
            return;
        }
        
        const runner = this.currentSession.runners.find(r => r.id === runnerId);
        if (!runner) return;
        
        const beaconScans = runner.logSessions ? runner.logSessions.filter(log => log.type === 'beacon_scan') : [];
        
        console.log('Runner:', runner.name);
        console.log('Beacon scans:', beaconScans);
        console.log('Current course:', this.currentCourse);
        
        const allBeacons = [];
        if (this.currentCourse.startBeacon) allBeacons.push(this.currentCourse.startBeacon);
        if (this.currentCourse.finishBeacon && !this.currentCourse.sameStartFinish) allBeacons.push(this.currentCourse.finishBeacon);
        if (this.currentCourse.waypoints) allBeacons.push(...this.currentCourse.waypoints);
        
        console.log('All beacons:', allBeacons);
        
        const scanMap = {};
        beaconScans.forEach(scan => {
            const beaconId = scan.additionalData;
            console.log('Looking for beacon ID:', beaconId, 'Type:', typeof beaconId);
            const beacon = allBeacons.find(b => {
                console.log('Checking beacon:', b.id, 'Type:', typeof b.id, 'Match:', b.id == beaconId);
                return b.id == beaconId;
            });
            
            console.log('Found beacon:', beacon);
            
            if (beacon && scan.latitude && scan.longitude) {
                // Beacons use 'lat'/'lng', scans use 'latitude'/'longitude'
                const beaconLat = beacon.latitude || beacon.lat;
                const beaconLng = beacon.longitude || beacon.lng;
                
                console.log('Beacon coords:', beaconLat, beaconLng);
                
                if (!beaconLat || !beaconLng) return;
                
                const distance = this.calculateDistance(
                    scan.latitude, scan.longitude,
                    beaconLat, beaconLng
                );
                const isValid = distance <= 20;
                
                if (!scanMap[beaconId]) {
                    scanMap[beaconId] = { scan, beacon, distance, isValid };
                }
            }
        });
        
        const tableHTML = `
            <table class="beacon-validation-table">
                <thead>
                    <tr>
                        <th>${this.trans('map.status')}</th>
                        ${allBeacons.map((beacon, idx) => `<th>${beacon.name || 'Balise ' + (idx + 1)}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>${this.trans('map.validation')}</strong></td>
                        ${allBeacons.map(beacon => {
                            const scanData = scanMap[beacon.id];
                            if (scanData && scanData.isValid) return '<td class="status-validated">✓</td>';
                            if (scanData && !scanData.isValid) return '<td class="status-invalid">✗</td>';
                            return '<td class="status-pending">-</td>';
                        }).join('')}
                    </tr>
                    <tr>
                        <td><strong>${this.trans('map.scan_time')}</strong></td>
                        ${allBeacons.map(beacon => {
                            const scanData = scanMap[beacon.id];
                            if (scanData && scanData.scan.time) {
                                const time = new Date(scanData.scan.time);
                                return `<td>${time.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit', second: '2-digit'})}</td>`;
                            }
                            return '<td>-</td>';
                        }).join('')}
                    </tr>
                    <tr>
                        <td><strong>${this.trans('map.distance')}</strong></td>
                        ${allBeacons.map(beacon => {
                            const scanData = scanMap[beacon.id];
                            if (scanData) {
                                const distClass = scanData.isValid ? 'status-validated' : 'status-invalid';
                                return `<td class="${distClass}">${scanData.distance.toFixed(1)}m</td>`;
                            }
                            return '<td>-</td>';
                        }).join('')}
                    </tr>
                </tbody>
            </table>
        `;
        
        document.getElementById('beaconValidationContent').innerHTML = tableHTML;
    }
    
    updateMap() {
        if (!this.map || !this.currentCourse || !this.currentCourse.waypoints) return;
        
        this.markers.forEach(m => m.setMap(null));
        this.labels.forEach(l => l.setMap(null));
        this.markers = [];
        this.labels = [];
        
        const bounds = new google.maps.LatLngBounds();
        
        // Add start beacon
        if (this.currentCourse.startBeacon && this.currentCourse.startBeacon.lat && this.currentCourse.startBeacon.lng) {
            const lat = parseFloat(this.currentCourse.startBeacon.lat);
            const lng = parseFloat(this.currentCourse.startBeacon.lng);
            
            const marker = new google.maps.Marker({
                position: { lat, lng },
                map: this.map,
                title: '🟢 ' + (this.currentCourse.startBeacon.name || 'Départ'),
                icon: {
                    path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z',
                    fillColor: '#4CAF50',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 1.3,
                    anchor: new google.maps.Point(0, 0)
                },
                zIndex: 100
            });
            this.markers.push(marker);
            bounds.extend({ lat, lng });
            
            const labelOverlay = this.createBeaconLabel(lat, lng, this.currentCourse.startBeacon.name || 'Départ', '#4CAF50', -65);
            labelOverlay.setMap(this.map);
            this.labels.push(labelOverlay);
        }
        
        // Add finish beacon
        if (this.currentCourse.finishBeacon && this.currentCourse.finishBeacon.lat && this.currentCourse.finishBeacon.lng &&
            (!this.currentCourse.startBeacon || this.currentCourse.finishBeacon.id !== this.currentCourse.startBeacon.id)) {
            const lat = parseFloat(this.currentCourse.finishBeacon.lat);
            const lng = parseFloat(this.currentCourse.finishBeacon.lng);
            
            const marker = new google.maps.Marker({
                position: { lat, lng },
                map: this.map,
                title: '🔴 ' + (this.currentCourse.finishBeacon.name || 'Arrivée'),
                icon: {
                    path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z',
                    fillColor: '#F44336',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 1.3,
                    anchor: new google.maps.Point(0, 0)
                },
                zIndex: 100
            });
            this.markers.push(marker);
            bounds.extend({ lat, lng });
            
            const labelOverlay = this.createBeaconLabel(lat, lng, this.currentCourse.finishBeacon.name || 'Arrivée', '#F44336', -65);
            labelOverlay.setMap(this.map);
            this.labels.push(labelOverlay);
        }
        
        // Add waypoints
        this.currentCourse.waypoints.forEach((beacon, idx) => {
            if (!beacon.lat || !beacon.lng) return;
            
            const lat = parseFloat(beacon.lat);
            const lng = parseFloat(beacon.lng);
            
            const marker = new google.maps.Marker({
                position: { lat, lng },
                map: this.map,
                title: beacon.name || `Balise ${idx + 1}`,
                icon: {
                    path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z',
                    fillColor: '#2196F3',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 1,
                    anchor: new google.maps.Point(0, 0)
                }
            });
            
            const labelOverlay = this.createBeaconLabel(lat, lng, beacon.name || `Balise ${idx + 1}`, '#2196F3', -45);
            labelOverlay.setMap(this.map);
            
            this.markers.push(marker);
            this.labels.push(labelOverlay);
            bounds.extend({ lat, lng });
        });
        
        if (!bounds.isEmpty()) {
            this.map.fitBounds(bounds);
        }
    }
    
    createBeaconLabel(lat, lng, text, color, yOffset) {
        const labelDiv = document.createElement('div');
        labelDiv.style.position = 'absolute';
        labelDiv.style.fontSize = yOffset === -65 ? '16px' : '14px';
        labelDiv.style.fontWeight = 'bold';
        labelDiv.style.color = color;
        labelDiv.style.textShadow = `-2px -2px 0 #fff, 2px -2px 0 #fff, -2px 2px 0 #fff, 2px 2px 0 #fff, -2px 0 0 #fff, 2px 0 0 #fff, 0 -2px 0 #fff, 0 2px 0 #fff`;
        labelDiv.style.whiteSpace = 'nowrap';
        labelDiv.style.transform = `translate(-50%, ${yOffset}px)`;
        labelDiv.style.pointerEvents = 'none';
        labelDiv.style.userSelect = 'none';
        labelDiv.style.zIndex = yOffset === -65 ? '10000' : '1000';
        labelDiv.textContent = text;
        
        class LabelOverlay extends google.maps.OverlayView {
            constructor(position, labelElement) {
                super();
                this.position = position;
                this.labelElement = labelElement;
            }
            
            onAdd() {
                const pane = yOffset === -65 ? this.getPanes().floatPane : this.getPanes().overlayMouseTarget;
                pane.appendChild(this.labelElement);
            }
            
            draw() {
                const projection = this.getProjection();
                const point = projection.fromLatLngToDivPixel(this.position);
                if (point) {
                    this.labelElement.style.left = point.x + 'px';
                    this.labelElement.style.top = point.y + 'px';
                }
            }
            
            onRemove() {
                if (this.labelElement.parentElement) {
                    this.labelElement.parentElement.removeChild(this.labelElement);
                }
            }
        }
        
        return new LabelOverlay(new google.maps.LatLng(lat, lng), labelDiv);
    }
    
    clearData() {
        this.currentSession = null;
        this.currentCourse = null;
        this.selectedRunnerId = null;
        
        document.getElementById('runnersTableBody').innerHTML = `<tr><td colspan="3" class="no-data">${this.trans('map.select_session_first')}</td></tr>`;
        document.getElementById('beaconValidationContent').innerHTML = `<div class="no-data">${this.trans('map.select_runner_to_view')}</div>`;
        document.getElementById('runnerNameBadge').style.display = 'none';
        
        this.markers.forEach(m => m.setMap(null));
        this.labels.forEach(l => l.setMap(null));
        this.runnerPaths.forEach(p => p.setMap(null));
        this.markers = [];
        this.labels = [];
        this.runnerPaths = [];
    }
}

} // End of MapViewer class definition guard

// Track initialized maps to prevent duplicates
if (!window.initializedMaps) {
    window.initializedMaps = new Set();
}

// Initialize all map widgets when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if this is the dedicated map viewer page
    if (document.getElementById('map') && document.getElementById('runnersTableBody')) {
        console.log('Initializing MapViewer for dedicated map page');
        // Wait for Google Maps to load
        const initMapViewer = () => {
            if (!window.google || !window.google.maps) {
                console.log('Waiting for Google Maps API...');
                setTimeout(initMapViewer, 100);
                return;
            }
            new window.MapViewer();
        };
        initMapViewer();
        return;
    }
    
    // Otherwise initialize ViewMapWidget instances
    const initAllMaps = () => {
        if (!window.google || !window.google.maps) {
            console.log('Waiting for Google Maps API...');
            setTimeout(initAllMaps, 100);
            return;
        }

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

    initAllMaps();
});

// Support for Turbo Drive (if used)
document.addEventListener('turbo:load', function() {
    if (!window.google || !window.google.maps) {
        console.log('Turbo: Waiting for Google Maps API...');
        return;
    }
    
    // Check for dedicated map viewer
    if (document.getElementById('map') && document.getElementById('runnersTableBody')) {
        if (!window.initializedMaps.has('mapViewer')) {
            console.log('Turbo: Initializing MapViewer');
            window.initializedMaps.add('mapViewer');
            new window.MapViewer();
        }
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
