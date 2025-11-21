// Orienteering Course Builder - Main Application
// Check if class is already defined to prevent redeclaration
if (typeof OrienteeringApp === 'undefined') {
    window.OrienteeringApp = class OrienteeringApp {
    constructor() {
        this.map = null;
        this.markers = [];
        this.controlPoints = [];
        this.isAddingPoint = false;
        this.currentEditingPoint = null;
        this.currentLocationMarker = null;
        this.accuracyCircle = null;
        this.mapsLoaded = false;
        this.initialized = false;
        
        // Boundary settings for parkour
        this.courseBounds = null;
        this.boundaryRestrictionMode = 'soft'; // 'strict' or 'soft'
        this.minZoom = 10;
        this.maxZoom = 20;
        
        // Boundary polygon display - always visible border, toggleable fill
        this.boundaryPolygon = null;
        this.boundaryFillVisible = true; // Track fill visibility state
        
        // Course path polyline
        this.coursePolyline = null;
        
        // Optimal path polyline
        this.optimalPathPolyline = null;
        this.optimalPathVisible = false;
        
        // Prevent multiple zoom adjustments
        this.zoomAdjustmentPending = false;
        this.zoomAdjustmentTimeoutId = null;
        
        // POI (Points of Interest) visibility
        this.poisVisible = false; // Start with POIs hidden by default
        
        // Configuration data (will be loaded from JSON)
        this.config = null;
        
        // Don't auto-initialize here, let the template handle it
    }

    async init() {
        // Prevent double initialization
        if (this.initialized) {
            return;
        }
        
        // Load configuration from JSON FIRST (await to ensure it completes)
        await this.loadConfiguration();
        
        this.setupEventListeners();
        this.loadSavedCourses();
        this.initialized = true;
        
        // If Google Maps is already loaded, initialize the map
        if (window.google && window.google.maps) {
            this.initializeMap();
        }
        // Otherwise, wait for the callback from Google Maps API
    }

    /**
     * Load configuration from JSON file
     */
    async loadConfiguration() {
        try {
            // Add cache busting to ensure fresh configuration
            const cacheBuster = `?t=${Date.now()}`;
            const response = await fetch(`/assets/data/map-config.json${cacheBuster}`, {
                cache: 'no-store' // Disable caching
            });
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

    initializeMap() {
        // Check if Google Maps is available
        if (!window.google || !window.google.maps) {
            console.error('Google Maps API not loaded yet');
            return;
        }

        // Check if map is already initialized
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

        // CRITICAL: Ensure config is loaded before proceeding
        if (!this.config) {
            console.error('Configuration not loaded yet! Map initialization aborted.');
            console.error('This will cause the map to use wrong default location.');
            // Don't initialize with wrong location - wait for config
            return;
        }

        console.log('Initializing Google Maps with config:', this.config);
        this.mapsLoaded = true;
        
        // Use configuration (now guaranteed to exist)
        const defaultLocation = this.config.defaultLocation;
        const defaultZoom = this.config.defaultZoom;
        
        // Initialize map
        this.map = new google.maps.Map(mapContainer, {
            zoom: defaultZoom,
            center: defaultLocation,
            mapTypeId: google.maps.MapTypeId.HYBRID, // Good for orienteering
            // mapId removed - conflicts with custom styles for POI hiding
            // mapId: 'ORIENTEERING_MAP',
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
            // Disable 3D tilt/rotation for better point visualization
            tilt: 0,
            rotateControl: false,
            heading: 0,
            // Map styles - initially hide POIs
            styles: this.getMapStyles(false)
        });

        console.log('Map initialized successfully');

        // Force map resize after a short delay to ensure proper rendering
        setTimeout(() => {
            if (this.map) {
                google.maps.event.trigger(this.map, 'resize');
                this.map.setCenter(defaultLocation);
            }
        }, 500);

        // Add click listener for coordinate display only
        this.map.addListener('click', (event) => {
            this.updateCoordinatesDisplay(event.latLng);
        });

        // Show refocus button (replaces location controls)
        this.addLocationControls();
        
        // Geolocation disabled - user position not needed for viewing-only app
        // this.handleGeolocation();
        
        // Add a demo button for testing boundaries (remove this in production)
        this.addDemoBoundaryButton();
    }

    /**
     * Get map styles based on POI visibility
     * @param {boolean} showPOIs - Whether to show points of interest
     */
    getMapStyles(showPOIs) {
        if (showPOIs) {
            return []; // Default map style with all POIs visible
        }
        
        // Hide all POIs and business labels
        return [
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
                featureType: 'poi.attraction',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'poi.government',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'poi.medical',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'poi.park',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'poi.place_of_worship',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'poi.school',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'poi.sports_complex',
                stylers: [{ visibility: 'off' }]
            }
        ];
    }

    /**
     * Set course boundaries for the parkour
     * @param {Array} coordinates - Array of {lat, lng} objects defining the boundary polygon
     * @param {string} mode - 'strict' (hard limit) or 'soft' (allows zoom but limits pan)
     */
    setCourseBounds(coordinates, mode = 'soft') {
        if (!coordinates || coordinates.length < 3) {
            console.error('At least 3 coordinates are required to define boundaries');
            return;
        }

        this.boundaryRestrictionMode = mode;
        
        // Create LatLngBounds from coordinates
        this.courseBounds = new google.maps.LatLngBounds();
        coordinates.forEach(coord => {
            this.courseBounds.extend(new google.maps.LatLng(coord.lat, coord.lng));
        });

        // DO NOT apply boundary restrictions - let user navigate freely
        // this.applyBoundaryRestrictions();
        
        // Optionally draw the boundary on the map
        this.drawBoundaryPolygon(coordinates);
        
        console.log(`Course bounds set in ${mode} mode (visual only - no restrictions)`);
    }

    /**
     * Apply boundary restrictions based on the selected mode
     * DISABLED: No restrictions applied - user can navigate freely
     */
    applyBoundaryRestrictions() {
        // Boundary restrictions disabled - user can navigate anywhere on the map
        // The boundary polygon is still drawn for visual reference only
        console.log('Boundary restrictions disabled - free navigation enabled');
        return;
        
        /* ORIGINAL CODE - DISABLED
        if (!this.courseBounds) return;

        if (this.boundaryRestrictionMode === 'strict') {
            // Strict mode: Hard limits on panning and zooming
            this.map.setOptions({
                restriction: {
                    latLngBounds: this.courseBounds,
                    strictBounds: true
                },
                minZoom: this.minZoom,
                maxZoom: this.maxZoom
            });
        } else {
            // Soft mode: Allow zoom but listen to pan events
            this.map.setOptions({
                minZoom: this.minZoom,
                maxZoom: this.maxZoom
            });
            
            // Add listener to check bounds when panning
            this.map.addListener('center_changed', () => {
                this.checkSoftBounds();
            });
        }
        */
    }

    /**
     * Check soft bounds and gently return to bounds if exceeded
     * DISABLED: No boundary checking
     */
    checkSoftBounds() {
        // Disabled - no boundary checking
        return;
        
        /* ORIGINAL CODE - DISABLED
        if (!this.courseBounds || this.boundaryRestrictionMode !== 'soft') return;

        const center = this.map.getCenter();
        if (!this.courseBounds.contains(center)) {
            // Get the closest point within bounds
            const ne = this.courseBounds.getNorthEast();
            const sw = this.courseBounds.getSouthWest();
            
            const newLat = Math.max(sw.lat(), Math.min(ne.lat(), center.lat()));
            const newLng = Math.max(sw.lng(), Math.min(ne.lng(), center.lng()));
            
            // Smoothly pan back to the boundary
            this.map.panTo(new google.maps.LatLng(newLat, newLng));
        }
        */
    }

    /**
     * Draw the boundary polygon on the map
     */
    /**
     * Draw the boundary polygon connecting the border points
     * Border is always visible, fill can be toggled
     */
    drawBoundaryPolygon(coordinates) {
        // Remove existing boundary polygon if it exists
        if (this.boundaryPolygon) {
            this.boundaryPolygon.setMap(null);
        }

        // Create polygon path from the actual border points
        const polygonPath = coordinates.map(coord => ({
            lat: coord.lat,
            lng: coord.lng
        }));

        // Create and display the polygon (border always visible, fill toggleable)
        this.boundaryPolygon = new google.maps.Polygon({
            paths: polygonPath,
            strokeColor: '#FF6B35',      // Orange border - always visible
            strokeOpacity: 0.9,           // Strong border visibility
            strokeWeight: 3,
            fillColor: '#FF6B35',
            fillOpacity: this.boundaryFillVisible ? 0.15 : 0, // Fill based on toggle state
            editable: false,
            draggable: false
        });

        this.boundaryPolygon.setMap(this.map);
    }

    /**
     * Toggle boundary fill visibility (border stays visible)
     */
    toggleBoundaryFill() {
        if (!this.boundaryPolygon) {
            alert('Aucun parcours chargé. Chargez un parcours d\'abord.');
            return;
        }

        // Toggle fill state
        this.boundaryFillVisible = !this.boundaryFillVisible;
        
        // Update polygon fill opacity
        this.boundaryPolygon.setOptions({
            fillOpacity: this.boundaryFillVisible ? 0.15 : 0
        });
        
        console.log(`Boundary fill ${this.boundaryFillVisible ? 'visible' : 'hidden'}`);
    }

    /**
     * Load and display course boundary points
     */
    loadCourseBoundary(boundaryPoints) {
        if (!boundaryPoints || boundaryPoints.length < 3) {
            console.log('No valid boundary points to display');
            return;
        }

        // Clear existing boundary if any
        if (this.boundaryPolygon) {
            this.boundaryPolygon.setMap(null);
            this.boundaryPolygon = null;
        }

        // Convert boundary points to coordinates format
        const coordinates = boundaryPoints.map(p => ({
            lat: p.lat,
            lng: p.lng
        }));

        // Set the course bounds
        this.setCourseBounds(coordinates, 'soft');

        // Show the boundary fill toggle button
        const toggleBtn = document.getElementById('toggleBoundaryFillBtn');
        if (toggleBtn) {
            toggleBtn.style.display = 'inline-block';
        }

        console.log('Course boundary loaded and displayed');
    }

    /**
     * Remove course boundaries and restore normal map behavior
     */
    removeBounds() {
        this.courseBounds = null;
        this.boundaryRestrictionMode = 'soft';
        
        // Remove map restrictions
        this.map.setOptions({
            restriction: null,
            minZoom: 1,
            maxZoom: 25
        });
        
        // Remove boundary polygon
        if (this.boundaryPolygon) {
            this.boundaryPolygon.setMap(null);
            this.boundaryPolygon = null;
        }
        
        console.log('Course bounds removed');
    }

    /**
     * Fit the map view to the course boundaries with minimal padding
     */
    /**
     * Fit the map view to the course boundaries with minimal padding
     */
    fitToBounds() {
        if (this.courseBounds) {
            this.map.fitBounds(this.courseBounds);
            // Use setTimeout instead of idle event to avoid multiple triggers
            setTimeout(() => {
                const currentZoom = this.map.getZoom();
                this.map.setZoom(currentZoom + 0.5); // Zoom IN for closer view
            }, 300); // Wait for fitBounds animation to complete
        }
    }

    /**
     * Set boundaries automatically based on control points with padding
     */
    setBoundsFromControlPoints(paddingKm = 0.5) {
        if (this.controlPoints.length < 2) {
            console.warn('Need at least 2 control points to set boundaries');
            return;
        }

        // Calculate bounds from control points
        const bounds = new google.maps.LatLngBounds();
        this.controlPoints.forEach(point => {
            bounds.extend(new google.maps.LatLng(point.coordinates.lat, point.coordinates.lng));
        });

        // Add padding (convert km to degrees approximately)
        const paddingDegrees = paddingKm / 111; // Rough conversion
        const ne = bounds.getNorthEast();
        const sw = bounds.getSouthWest();
        
        const coordinates = [
            { lat: sw.lat() - paddingDegrees, lng: sw.lng() - paddingDegrees },
            { lat: ne.lat() + paddingDegrees, lng: sw.lng() - paddingDegrees },
            { lat: ne.lat() + paddingDegrees, lng: ne.lng() + paddingDegrees },
            { lat: sw.lat() - paddingDegrees, lng: ne.lng() + paddingDegrees }
        ];

        this.setCourseBounds(coordinates, 'soft');
    }

    /**
     * Get current location - DISABLED
     * User position not needed for viewing-only application
     */
    getCurrentLocation(forceRequest = false) {
        console.log('Geolocation disabled - viewing-only application');
        return;
    }

    /**
     * Handle geolocation - DISABLED
     * Not needed for viewing-only application
     */
    handleGeolocation() {
        // Geolocation disabled for viewing-only application
        console.log('Geolocation disabled - viewing-only application');
        return;
    }

    /**
     * Show location message - DISABLED
     */
    showLocationMessage(type) {
        // Disabled - no geolocation in viewing app
        return;
    }

    addLocationControls() {
        // Add a custom control for refocusing on parkour
        const refocusButton = document.createElement('button');
        refocusButton.innerHTML = '🎯 Recentrer Parcours';
        refocusButton.className = 'location-control-btn';
        refocusButton.title = 'Recentrer la carte sur le parcours';
        
        refocusButton.addEventListener('click', () => {
            this.refocusOnParkour();
        });
        
        // Add to map controls
        const refocusControlDiv = document.createElement('div');
        refocusControlDiv.className = 'location-control';
        refocusControlDiv.appendChild(refocusButton);
        
        this.map.controls[google.maps.ControlPosition.TOP_RIGHT].push(refocusControlDiv);
    }

    /**
     * Refocus map on parkour/course bounds with maximum zoom
     */
    refocusOnParkour() {
        // Clear any pending zoom adjustment
        if (this.zoomAdjustmentTimeoutId) {
            clearTimeout(this.zoomAdjustmentTimeoutId);
            this.zoomAdjustmentTimeoutId = null;
        }
        
        if (this.courseBounds) {
            // If we have course bounds, fit to those
            this.map.fitBounds(this.courseBounds);
            // Use setTimeout to avoid multiple idle event triggers
            this.zoomAdjustmentPending = true;
            this.zoomAdjustmentTimeoutId = setTimeout(() => {
                if (!this.zoomAdjustmentPending) {
                    return;
                }
                const currentZoom = this.map.getZoom();
                const targetZoom = Math.ceil(currentZoom); // Round UP to next integer
                this.map.setZoom(targetZoom); // Use integer zoom to avoid Google Maps auto-rounding
                this.zoomAdjustmentPending = false;
                this.zoomAdjustmentTimeoutId = null;
            }, 300); // Wait for fitBounds animation to complete
        } else if (this.controlPoints.length > 0) {
            // If we have control points but no bounds, create bounds from points
            const bounds = new google.maps.LatLngBounds();
            this.controlPoints.forEach(point => {
                bounds.extend(point.position);
            });
            this.map.fitBounds(bounds);
            // Use setTimeout to avoid multiple idle event triggers
            this.zoomAdjustmentPending = true;
            this.zoomAdjustmentTimeoutId = setTimeout(() => {
                if (!this.zoomAdjustmentPending) {
                    return;
                }
                const currentZoom = this.map.getZoom();
                const targetZoom = Math.ceil(currentZoom); // Round UP to next integer
                this.map.setZoom(targetZoom); // Use integer zoom to avoid Google Maps auto-rounding
                this.zoomAdjustmentPending = false;
                this.zoomAdjustmentTimeoutId = null;
            }, 300); // Wait for fitBounds animation to complete
        } else {
            // No parkour loaded, go to default location
            const defaultLocation = this.config 
                ? this.config.defaultLocation 
                : { lat: 45.5017, lng: -73.5673 };
            const defaultZoom = this.config ? this.config.defaultZoom : 15;
            
            this.map.setCenter(defaultLocation);
            this.map.setZoom(defaultZoom);
        }
    }

    setupEventListeners() {
        // Load course button
        const loadBtn = document.getElementById('loadCourseBtn');
        if (loadBtn) {
            loadBtn.addEventListener('click', () => {
                this.showLoadCourseDialog();
            });
        }

        // Refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshMap();
            });
        }

        // Modal close
        const closeBtn = document.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }

        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('pointModal');
            if (event.target === modal) {
                this.closeModal();
            }
        });

        // Boundary fill toggle button
        const toggleBoundaryFillBtn = document.getElementById('toggleBoundaryFillBtn');
        if (toggleBoundaryFillBtn) {
            toggleBoundaryFillBtn.addEventListener('click', () => {
                this.toggleBoundaryFill();
                // Update button text based on state using innerHTML for proper emoji rendering
                toggleBoundaryFillBtn.innerHTML = this.boundaryFillVisible 
                    ? '&#x1F3A8; Masquer remplissage' 
                    : '&#x1F3A8; Afficher remplissage';
            });
        }

        // Optimal path button
        const showOptimalPathBtn = document.getElementById('showOptimalPathBtn');
        if (showOptimalPathBtn) {
            showOptimalPathBtn.addEventListener('click', () => {
                this.toggleOptimalPath();
            });
        }
    }

    refreshMap() {
        if (this.map) {
            // Force map resize and refresh
            google.maps.event.trigger(this.map, 'resize');
            // Optionally re-center or update display
            this.updateDisplay();
        }
    }

    /**
     * Handle setting boundaries based on current control points
     */
    handleSetBounds() {
        if (this.controlPoints.length < 2) {
            alert('Vous devez avoir au moins 2 points de contrôle pour définir des limites de parcours.');
            return;
        }

        const paddingKm = prompt('Entrez la marge en kilomètres autour du parcours (par défaut: 0.5):', '0.5');
        if (paddingKm === null) return; // User cancelled

        const padding = parseFloat(paddingKm) || 0.5;
        const mode = document.getElementById('boundaryModeSelect').value;
        
        this.setBoundsFromControlPoints(padding);
        this.boundaryRestrictionMode = mode;
        this.applyBoundaryRestrictions();
        
        alert(`Limites du parcours définies en mode ${mode === 'strict' ? 'strict' : 'souple'} avec une marge de ${padding}km`);
    }

    /**
     * Example: Set custom manual boundaries (you can call this method with specific coordinates)
     * This is useful if you want to define a specific geographic area regardless of control points
     */
    setCustomBoundaries() {
        // Example coordinates for a specific area (replace with your desired boundaries)
        const customBoundaries = [
            { lat: 45.500, lng: -73.570 }, // Northwest corner
            { lat: 45.500, lng: -73.565 }, // Northeast corner  
            { lat: 45.495, lng: -73.565 }, // Southeast corner
            { lat: 45.495, lng: -73.570 }  // Southwest corner
        ];
        
        const mode = document.getElementById('boundaryModeSelect').value;
        this.setCourseBounds(customBoundaries, mode);
        
        console.log('Custom boundaries set for orienteering area');
    }

    /**
     * Add a demo button for testing boundaries (for demonstration purposes)
     */
    addDemoBoundaryButton() {
        const demoButton = document.createElement('button');
        demoButton.textContent = '🧪 Test Limites Parcours';
        demoButton.className = 'btn btn-info';
        demoButton.style.position = 'absolute';
        demoButton.style.top = '10px';
        demoButton.style.right = '10px';
        demoButton.style.zIndex = '1000';
        
        demoButton.addEventListener('click', () => {
            this.loadTestBoundaryPoints();
        });
        
        document.body.appendChild(demoButton);
    }

    /**
     * Load test boundary points and auto-fit map view
     * Uses LatLngBounds.extend() and fitBounds() from Google Maps API
     */
    /**
     * Load test boundary points from JSON file
     * Uses fetch API to load data from /assets/data/test-boundary-points.json
     */
    async loadTestBoundaryPoints() {
        try {
            // Fetch boundary points and waypoints from JSON files
            const [boundaryResponse, waypointsResponse] = await Promise.all([
                fetch('/assets/data/test-boundary-points.json'),
                fetch('/assets/data/test-waypoints.json')
            ]);
            
            const boundaryData = await boundaryResponse.json();
            const waypointsData = await waypointsResponse.json();
            
            const boundaryPoints = boundaryData.points;
            const waypoints = waypointsData.waypoints;
            
            console.log('Loaded boundary points from JSON:', boundaryPoints);
            console.log('Loaded waypoints from JSON:', waypoints);
            
            // Clear existing markers and points
            this.clearAllPoints();
            
            // Create LatLngBounds object to automatically calculate bounds
            const bounds = new google.maps.LatLngBounds();
            
            // Sort waypoints by ID to ensure correct order
            const sortedWaypoints = [...waypoints].sort((a, b) => a.id - b.id);
            
            // Add waypoints first (they are the main course points)
            sortedWaypoints.forEach((pointData, index) => {
                const point = {
                    id: pointData.id || (Date.now() + index),
                    name: this.getWaypointName(pointData.type, pointData.id),
                    description: this.getWaypointDescription(pointData.type, pointData.id),
                    type: pointData.type || 'control',
                    coordinates: { lat: pointData.lat, lng: pointData.lng },
                    position: new google.maps.LatLng(pointData.lat, pointData.lng)
                };
                
                // Add to control points
                this.controlPoints.push(point);
                
                // Create marker
                this.createMarker(point);
                
                // Extend bounds to include this point
                bounds.extend(point.position);
            });
            
            // Path drawing removed for test boundary points - only show markers
            
            // Add boundary points as markers (but don't include in controlPoints - they're just visual boundary)
            boundaryPoints.forEach((pointData, index) => {
                const position = new google.maps.LatLng(pointData.lat, pointData.lng);
                
                // Create simple boundary marker
                new google.maps.Marker({
                    position: position,
                    map: this.map,
                    title: `Limite ${pointData.id || index + 1}`,
                    label: {
                        text: `${pointData.id || index + 1}`,
                        color: 'white',
                        fontSize: '10px',
                        fontWeight: 'bold'
                    },
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 6,
                        fillColor: '#FF6B35',
                        fillOpacity: 0.6,
                        strokeColor: '#fff',
                        strokeWeight: 1
                    }
                });
                
                // Extend bounds to include boundary point
                bounds.extend(position);
            });
            
            // Update UI
            this.updatePointsList();
            this.updateCourseStats();
            
            // Fit map to show all points - no pixel padding, use zoom adjustment instead
            this.map.fitBounds(bounds);
            
            // Clear any pending zoom adjustment
            if (this.zoomAdjustmentTimeoutId) {
                clearTimeout(this.zoomAdjustmentTimeoutId);
                this.zoomAdjustmentTimeoutId = null;
            }
            
            // Use setTimeout to avoid multiple idle event triggers
            this.zoomAdjustmentPending = true;
            this.zoomAdjustmentTimeoutId = setTimeout(() => {
                if (!this.zoomAdjustmentPending) {
                    return;
                }
                const currentZoom = this.map.getZoom();
                const targetZoom = Math.ceil(currentZoom); // Round UP to next integer
                this.map.setZoom(targetZoom); // Use integer zoom to avoid Google Maps auto-rounding
                this.zoomAdjustmentPending = false;
                this.zoomAdjustmentTimeoutId = null;
            }, 300); // Wait for fitBounds animation to complete
            
            // Optional: Set course boundaries with the polygon
            const boundaryCoords = boundaryPoints.map(p => ({ lat: p.lat, lng: p.lng }));
            this.setCourseBounds(boundaryCoords, 'soft');
            
            // Show the boundary fill toggle button now that a parkour is loaded
            this.showBoundaryFillButton();
            
            console.log('Test boundary points and waypoints loaded and map auto-fitted');
            alert(`${waypoints.length} points de passage chargés!\n${boundaryPoints.length} points limites chargés!\nLa carte s'ajuste automatiquement.`);
            
        } catch (error) {
            console.error('Failed to load test boundary points:', error);
            alert('Erreur lors du chargement des points de test. Vérifiez le fichier JSON.');
        }
    }

    /**
     * Get waypoint name based on type
     */
    getWaypointName(type, id) {
        switch(type) {
            case 'start':
                return 'Départ';
            case 'control':
            default:
                return `Balise ${id}`;
        }
    }

    /**
     * Get waypoint description based on type
     */
    getWaypointDescription(type, id) {
        switch(type) {
            case 'start':
                return 'Point de départ du parcours';
            case 'control':
            default:
                return `Point de contrôle ${id}`;
        }
    }

    /**
     * Show the boundary fill toggle button (only when parkour is loaded)
     */
    showBoundaryFillButton() {
        const toggleBtn = document.getElementById('toggleBoundaryFillBtn');
        if (toggleBtn) {
            toggleBtn.style.display = 'inline-block';
        }
    }

    toggleAddingMode() {
        if (!this.mapsLoaded || !this.map) {
            alert('Veuillez attendre que la carte soit complètement chargée');
            return;
        }
        
        this.isAddingPoint = !this.isAddingPoint;
        const btn = document.getElementById('addPointBtn');
        
        if (this.isAddingPoint) {
            btn.textContent = '❌ Annuler l\'ajout';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
            this.map.setOptions({ cursor: 'crosshair' });
        } else {
            btn.textContent = '📍 Add Control Point';
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary');
            this.map.setOptions({ cursor: 'default' });
        }
    }

    addControlPoint(latLng) {
        // Show modal to get point details
        this.currentEditingPoint = {
            position: latLng,
            isNew: true
        };
        
        document.getElementById('pointLat').textContent = latLng.lat().toFixed(6);
        document.getElementById('pointLng').textContent = latLng.lng().toFixed(6);
        
        // Clear form
        document.getElementById('pointForm').reset();
        
        // Show modal
        document.getElementById('pointModal').style.display = 'block';
        
        // Turn off adding mode
        this.toggleAddingMode();
    }

    savePointDetails() {
        const name = document.getElementById('pointName').value;
        const description = document.getElementById('pointDescription').value;
        const type = document.getElementById('pointType').value;
        
        if (!name.trim()) {
            alert('Veuillez entrer un nom pour le point');
            return;
        }

        const point = {
            id: Date.now(),
            name: name,
            description: description,
            type: type,
            position: this.currentEditingPoint.position,
            coordinates: {
                lat: this.currentEditingPoint.position.lat(),
                lng: this.currentEditingPoint.position.lng()
            }
        };

        // Add to control points array
        this.controlPoints.push(point);

        // Create marker
        this.createMarker(point);

        // Update UI
        this.updatePointsList();
        this.updateCourseStats();
        this.closeModal();
    }

    createMarker(point) {
        // For now, use regular markers to avoid Map ID requirement
        // Advanced markers need a Map ID configured in Google Cloud Console
        const marker = new google.maps.Marker({
            position: point.position,
            map: this.map,
            title: point.name,
            draggable: true,
            icon: this.getMarkerIcon(point.type)
        });

        // Add info window
        const infoWindow = new google.maps.InfoWindow({
            content: this.createInfoWindowContent(point)
        });

        marker.addListener('click', () => {
            infoWindow.open(this.map, marker);
        });

        marker.addListener('dragend', (event) => {
            point.position = event.latLng;
            point.coordinates = {
                lat: event.latLng.lat(),
                lng: event.latLng.lng()
            };
            this.updatePointsList();
            this.updateCourseStats();
        });

        // Store reference
        point.marker = marker;
        this.markers.push(marker);
    }

    getMarkerIcon(type) {
        const markerStyles = {
            start: {
                fillColor: '#28a745',
                strokeColor: '#ffffff',
                label: 'S'
            },
            control: {
                fillColor: '#007bff',
                strokeColor: '#ffffff',
                label: 'C'
            },
            finish: {
                fillColor: '#dc3545',
                strokeColor: '#ffffff',
                label: 'F'
            }
        };

        const style = markerStyles[type] || markerStyles.control;
        
        // Create a custom SVG marker with border and better visibility
        const svg = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 40" width="32" height="40">
                <!-- Drop shadow -->
                <ellipse cx="16" cy="38" rx="8" ry="2" fill="rgba(0,0,0,0.3)"/>
                <!-- Main marker shape with white border -->
                <path d="M16 2C10.48 2 6 6.48 6 12c0 7 10 24 10 24s10-17 10-24c0-5.52-4.48-10-10-10z" 
                      fill="${style.fillColor}" 
                      stroke="${style.strokeColor}" 
                      stroke-width="2"/>
                <!-- Inner circle -->
                <circle cx="16" cy="12" r="6" 
                        fill="${style.fillColor}" 
                        stroke="${style.strokeColor}" 
                        stroke-width="1.5"/>
                <!-- Label text -->
                <text x="16" y="16" text-anchor="middle" 
                      fill="${style.strokeColor}" 
                      font-family="Arial, sans-serif" 
                      font-size="10" 
                      font-weight="bold">${style.label}</text>
            </svg>
        `;

        return {
            url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`,
            scaledSize: new google.maps.Size(32, 40),
            anchor: new google.maps.Point(16, 40)
        };
    }

    createInfoWindowContent(point) {
        return `
            <div style="min-width: 200px;">
                <h4>${point.name}</h4>
                <p><strong>Type:</strong> ${point.type.charAt(0).toUpperCase() + point.type.slice(1)}</p>
                ${point.description ? `<p><strong>Description:</strong> ${point.description}</p>` : ''}
                <p><strong>Coordonnées:</strong><br>
                   Lat: ${point.coordinates.lat.toFixed(6)}<br>
                   Lng: ${point.coordinates.lng.toFixed(6)}</p>
                <button onclick="app.removePoint(${point.id})" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; margin-top: 5px;">Remove Point</button>
            </div>
        `;
    }

    removePoint(pointId) {
        const pointIndex = this.controlPoints.findIndex(p => p.id === pointId);
        if (pointIndex === -1) return;

        const point = this.controlPoints[pointIndex];
        
        // Remove marker from map
        if (point.marker) {
            point.marker.setMap(null);
        }

        // Remove from arrays
        this.controlPoints.splice(pointIndex, 1);
        const markerIndex = this.markers.findIndex(m => m === point.marker);
        if (markerIndex !== -1) {
            this.markers.splice(markerIndex, 1);
        }

        // Update UI
        this.updatePointsList();
        this.updateCourseStats();
    }

    updatePointsList() {
        const container = document.getElementById('pointsList');
        
        if (this.controlPoints.length === 0) {
            container.innerHTML = '<p class="no-points">Aucun parcours chargé</p>';
            return;
        }

        const html = this.controlPoints.map(point => `
            <div class="point-item ${point.type}" onclick="app.centerOnPoint(${point.id})">
                <div class="point-name">${point.name}</div>
                <div class="point-coords">${point.coordinates.lat.toFixed(4)}, ${point.coordinates.lng.toFixed(4)}</div>
                ${point.description ? `<div class="point-description">${point.description}</div>` : ''}
            </div>
        `).join('');

        container.innerHTML = html;
    }

    centerOnPoint(pointId) {
        const point = this.controlPoints.find(p => p.id === pointId);
        if (point) {
            this.map.setCenter(point.position);
            this.map.setZoom(18);
            
            // Open info window
            if (point.marker) {
                google.maps.event.trigger(point.marker, 'click');
            }
        }
    }

    updateCourseStats() {
        document.getElementById('totalPoints').textContent = this.controlPoints.length;
        
        // Calculate total distance
        let totalDistance = 0;
        for (let i = 1; i < this.controlPoints.length; i++) {
            const prev = this.controlPoints[i - 1];
            const curr = this.controlPoints[i];
            totalDistance += this.calculateDistance(prev.coordinates, curr.coordinates);
        }
        
        document.getElementById('courseDistance').textContent = `${(totalDistance / 1000).toFixed(2)} km`;
    }

    calculateDistance(point1, point2) {
        const R = 6371000; // Earth's radius in meters
        const dLat = this.toRadians(point2.lat - point1.lat);
        const dLng = this.toRadians(point2.lng - point1.lng);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(this.toRadians(point1.lat)) * Math.cos(this.toRadians(point2.lat)) *
                  Math.sin(dLng / 2) * Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    toRadians(degrees) {
        return degrees * (Math.PI / 180);
    }

    updateCoordinatesDisplay(latLng) {
        const lat = latLng.lat().toFixed(6);
        const lng = latLng.lng().toFixed(6);
        
        document.getElementById('coordinatesDisplay').innerHTML = 
            `<strong>📍 Lat/Lng:</strong> <span style="color: #007bff; font-family: monospace;">${lat}, ${lng}</span>`;
        
        // Show copy button
        const copyBtn = document.getElementById('copyCoordinatesBtn');
        if (copyBtn) {
            copyBtn.style.display = 'inline-block';
            copyBtn.onclick = () => {
                const coordText = `{ "lat": ${lat}, "lng": ${lng} }`;
                navigator.clipboard.writeText(coordText).then(() => {
                    const originalText = copyBtn.innerHTML;
                    copyBtn.innerHTML = '✅';
                    setTimeout(() => {
                        copyBtn.innerHTML = originalText;
                    }, 1500);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    alert('Copie échouée. Coordonnées: ' + coordText);
                });
            };
        }
        
        // Also log to console for easy copy-paste
        console.log(`📍 Clicked coordinates: { "lat": ${lat}, "lng": ${lng} }`);
    }

    clearAllPoints() {
        if (this.controlPoints.length === 0) return;
        
        if (confirm('Êtes-vous sûr de vouloir effacer tous les points de contrôle ?')) {
            // Remove all markers
            this.markers.forEach(marker => marker.setMap(null));
            
            // Clear arrays
            this.markers = [];
            this.controlPoints = [];
            
            // Hide optimal path if visible
            if (this.optimalPathVisible) {
                this.hideOptimalPath();
            }
            
            // Hide optimal path button
            const optimalPathBtn = document.getElementById('showOptimalPathBtn');
            if (optimalPathBtn) {
                optimalPathBtn.style.display = 'none';
            }
            
            // Clear boundary polygon if visible
            if (this.boundaryPolygon) {
                this.boundaryPolygon.setMap(null);
                this.boundaryPolygon = null;
            }
            
            // Hide boundary toggle button
            const toggleBoundaryBtn = document.getElementById('toggleBoundaryFillBtn');
            if (toggleBoundaryBtn) {
                toggleBoundaryBtn.style.display = 'none';
            }
            
            // Update UI
            this.updatePointsList();
            this.updateCourseStats();
        }
    }

    async saveCourse() {
        if (this.controlPoints.length === 0) {
            alert('Aucun point de contrôle à enregistrer !');
            return;
        }

        const courseName = document.getElementById('courseNameInput').value.trim();
        if (!courseName) {
            alert('Veuillez entrer un nom de parcours avant d\'enregistrer !');
            document.getElementById('courseNameInput').focus();
            return;
        }

        const course = {
            id: Date.now().toString(),
            name: courseName,
            created: new Date().toISOString(),
            points: this.controlPoints.map(p => ({
                id: p.id,
                name: p.name,
                description: p.description,
                type: p.type,
                coordinates: p.coordinates
            }))
        };

        // Save to server via API
        try {
            const response = await fetch('/api/course/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(course)
            });

            if (!response.ok) {
                throw new Error('Failed to save course');
            }

            const result = await response.json();
            
            // Update UI
            await this.loadSavedCourses();
            
            alert(`Parcours "${courseName}" enregistré avec succès !`);
            document.getElementById('courseNameInput').value = '';
        } catch (error) {
            console.error('Error saving course:', error);
            alert('Erreur lors de l\'enregistrement du parcours. Veuillez réessayer.');
        }
    }

    async getSavedCourses() {
        // Fetch courses from database via API
        try {
            const cacheBuster = `?t=${Date.now()}`;
            const response = await fetch(`/api/courses${cacheBuster}`, {
                cache: 'no-store'
            });
            if (!response.ok) {
                console.error('Failed to fetch courses from database');
                return [];
            }
            const data = await response.json();
            return data.courses || [];
        } catch (error) {
            console.error('Error loading courses:', error);
            return [];
        }
    }

    async loadSavedCourses() {
        const courses = await this.getSavedCourses();
        const container = document.getElementById('savedCoursesList');
        
        if (!container) {
            console.warn('savedCoursesList container not found');
            return;
        }
        
        if (courses.length === 0) {
            container.innerHTML = '<p class="no-courses">Aucun parcours disponible</p>';
            return;
        }

        const html = courses.map(course => {
            // Handle both old format (with 'points') and new format (with 'waypoints')
            const pointCount = course.points ? course.points.length : (course.waypoints ? course.waypoints.length + 1 : 0);
            const dateStr = course.createdAt || course.created;
            const displayDate = dateStr ? new Date(dateStr).toLocaleDateString('fr-FR') : 'Date inconnue';
            
            return `
                <div class="saved-course-item" onclick="app.loadCourseFromJSON('${course.id}')">
                    <div class="course-name">${course.name}</div>
                    <div class="course-info">${pointCount} points • ${displayDate}</div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    async loadCourseFromJSON(courseId) {
        const courses = await this.getSavedCourses();
        const course = courses.find(c => c.id == courseId);
        
        if (!course) {
            alert('Parcours non trouvé!');
            return;
        }

        if (this.controlPoints.length > 0) {
            if (!confirm('Cela remplacera le parcours actuel. Continuer ?')) {
                return;
            }
        }

        // Clear existing points
        this.clearAllPoints();

        // Handle both old format (points array) and new format (startPoint + waypoints)
        let pointsToLoad = [];
        
        if (course.points) {
            // Old format
            pointsToLoad = course.points.filter(p => p.coordinates && p.coordinates.lat != null && p.coordinates.lng != null);
        } else if (course.waypoints) {
            // New format - convert to points format
            // Add start point first (only if coordinates are valid)
            if (course.startPoint && course.startPoint.lat != null && course.startPoint.lng != null) {
                pointsToLoad.push({
                    id: 1,
                    name: course.startPoint.name || 'Départ',
                    description: course.startPoint.description || '',
                    type: 'start',
                    coordinates: {
                        lat: course.startPoint.lat,
                        lng: course.startPoint.lng
                    }
                });
            }
            
            // Add waypoints (only with valid coordinates)
            course.waypoints.forEach((wp, index) => {
                if (wp.lat != null && wp.lng != null) {
                    pointsToLoad.push({
                        id: wp.id || (index + 2),
                        name: wp.name || `Balise ${wp.id || index + 1}`,
                        description: wp.description || '',
                        type: wp.type || 'control',
                        coordinates: {
                            lat: wp.lat,
                            lng: wp.lng
                        }
                    });
                }
            });
        }
        
        // Validate we have points to load
        if (pointsToLoad.length === 0) {
            alert('Ce parcours n\'a pas de coordonnées GPS valides. Impossible de le charger.');
            return;
        }

        // Load course points
        pointsToLoad.forEach(pointData => {
            const point = {
                ...pointData,
                position: new google.maps.LatLng(pointData.coordinates.lat, pointData.coordinates.lng)
            };
            
            this.controlPoints.push(point);
            this.createMarker(point);
        });

        // Update UI
        this.updatePointsList();
        this.updateCourseStats();
        
        // Show optimal path button when course is loaded
        const optimalPathBtn = document.getElementById('showOptimalPathBtn');
        if (optimalPathBtn) {
            optimalPathBtn.style.display = 'inline-block';
        }
        
        // Load and display boundary points if they exist
        if (course.boundaryPoints && course.boundaryPoints.length >= 3) {
            console.log('Loading boundary points:', course.boundaryPoints);
            this.loadCourseBoundary(course.boundaryPoints);
        }
        
        // Set course name if input exists
        const courseNameInput = document.getElementById('courseNameInput');
        if (courseNameInput) {
            courseNameInput.value = course.name;
        }
        
        // Center map on course
        if (pointsToLoad.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            pointsToLoad.forEach(point => {
                bounds.extend(new google.maps.LatLng(point.coordinates.lat, point.coordinates.lng));
            });
            this.map.fitBounds(bounds);
            
            // Zoom in slightly after fitting
            setTimeout(() => {
                const currentZoom = this.map.getZoom();
                this.map.setZoom(Math.ceil(currentZoom));
            }, 300);
        }
        
        alert(`Parcours "${course.name}" chargé avec succès!`);
    }

    loadCourse(courseId) {
        // Legacy method - redirect to new JSON-based method
        this.loadCourseFromJSON(courseId);
    }

    // Keep old loadCourse implementation for reference (now unused)
    loadCourseOld(courseId) {
        const courses = this.getSavedCourses();
        const course = courses.find(c => c.id === courseId);
        
        if (!course) {
            alert('Parcours non trouvé!');
            return;
        }

        if (this.controlPoints.length > 0) {
            if (!confirm('Cela remplacera le parcours actuel. Continuer ?')) {
                return;
            }
        }

        // Clear existing points
        this.clearAllPoints();

        // Load course points
        course.points.forEach(pointData => {
            const point = {
                ...pointData,
                position: new google.maps.LatLng(pointData.coordinates.lat, pointData.coordinates.lng)
            };
            
            this.controlPoints.push(point);
            this.createMarker(point);
        });

        // Update UI
        this.updatePointsList();
        this.updateCourseStats();
        
        // Set course name
        document.getElementById('courseNameInput').value = course.name;
        
        // Center map on course
        if (course.points.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            course.points.forEach(point => {
                bounds.extend(new google.maps.LatLng(point.coordinates.lat, point.coordinates.lng));
            });
            this.map.fitBounds(bounds);
            
            // Automatically set course boundaries with default padding
            setTimeout(() => {
                this.setBoundsFromControlPoints(0.5); // 500m padding
                const mode = document.getElementById('boundaryModeSelect').value;
                this.boundaryRestrictionMode = mode;
                this.applyBoundaryRestrictions();
            }, 1000); // Wait for map to finish fitting bounds
        }

        alert(`Parcours "${course.name}" chargé avec succès!\nLes limites du parcours ont été automatiquement définies.`);
    }

    showContextMenu(event) {
        // Simple context menu - you can enhance this
        const lat = event.latLng.lat().toFixed(6);
        const lng = event.latLng.lng().toFixed(6);
        
        if (confirm(`Ajouter un point de contrôle aux coordonnées :\n${lat}, ${lng} ?`)) {
            this.addControlPoint(event.latLng);
        }
    }

    closeModal() {
        document.getElementById('pointModal').style.display = 'none';
        this.currentEditingPoint = null;
    }

    cancelPoint() {
        this.closeModal();
    }

    async showLoadCourseDialog() {
        const courses = await this.getSavedCourses();
        if (courses.length === 0) {
            alert('Aucun parcours disponible!');
            return;
        }

        // Simple implementation - you can enhance with a proper dialog
        const courseNames = courses.map((course, index) => {
            const pointCount = course.points ? course.points.length : (course.waypoints ? course.waypoints.length + 1 : 0);
            return `${index + 1}. ${course.name} (${pointCount} points)`;
        }).join('\n');

        const selection = prompt(`Sélectionnez un parcours à charger:\n\n${courseNames}\n\nEntrez le numéro:`);
        
        if (selection && !isNaN(selection)) {
            const index = parseInt(selection) - 1;
            if (index >= 0 && index < courses.length) {
                await this.loadCourseFromJSON(courses[index].id);
            } else {
                alert('Sélection invalide!');
            }
        }
    }

    /**
     * Toggle optimal path display
     */
    toggleOptimalPath() {
        if (this.controlPoints.length < 2) {
            alert('Chargez un parcours d\'abord!');
            return;
        }

        if (this.optimalPathVisible) {
            // Hide optimal path
            this.hideOptimalPath();
        } else {
            // Show optimal path
            this.showOptimalPath();
        }
    }

    /**
     * Calculate and display the optimal path using nearest neighbor algorithm
     */
    showOptimalPath() {
        if (this.controlPoints.length < 2) {
            return;
        }

        // Use nearest neighbor algorithm for TSP approximation
        const optimalOrder = this.calculateOptimalPath();
        
        // Create path from optimal order
        const path = optimalOrder.map(index => ({
            lat: this.controlPoints[index].coordinates.lat,
            lng: this.controlPoints[index].coordinates.lng
        }));

        // Create polyline
        this.optimalPathPolyline = new google.maps.Polyline({
            path: path,
            geodesic: true,
            strokeColor: '#FF6B35',
            strokeOpacity: 0.9,
            strokeWeight: 4,
            map: this.map,
            zIndex: 100
        });

        this.optimalPathVisible = true;

        // Calculate optimal distance
        let optimalDistance = 0;
        for (let i = 1; i < optimalOrder.length; i++) {
            const prev = this.controlPoints[optimalOrder[i - 1]];
            const curr = this.controlPoints[optimalOrder[i]];
            optimalDistance += this.calculateDistance(prev.coordinates, curr.coordinates);
        }

        // Update button text
        const btn = document.getElementById('showOptimalPathBtn');
        if (btn) {
            btn.textContent = '✖️ Masquer chemin optimal';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-danger');
        }

        console.log(`Chemin optimal: ${(optimalDistance / 1000).toFixed(2)} km`);
    }

    /**
     * Hide the optimal path
     */
    hideOptimalPath() {
        if (this.optimalPathPolyline) {
            this.optimalPathPolyline.setMap(null);
            this.optimalPathPolyline = null;
        }

        this.optimalPathVisible = false;

        // Update button text
        const btn = document.getElementById('showOptimalPathBtn');
        if (btn) {
            btn.textContent = '🎯 Afficher chemin optimal';
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-success');
        }
    }

    /**
     * Calculate optimal path using nearest neighbor algorithm (greedy TSP approximation)
     * Returns array of indices representing the order to visit points
     */
    calculateOptimalPath() {
        if (this.controlPoints.length < 2) {
            return [0];
        }

        const n = this.controlPoints.length;
        const visited = new Array(n).fill(false);
        const order = [];
        
        // Start from first point (assuming it's the start point)
        let current = 0;
        order.push(current);
        visited[current] = true;

        // Find nearest unvisited neighbor at each step
        for (let i = 1; i < n; i++) {
            let nearestIndex = -1;
            let minDistance = Infinity;

            for (let j = 0; j < n; j++) {
                if (!visited[j]) {
                    const distance = this.calculateDistance(
                        this.controlPoints[current].coordinates,
                        this.controlPoints[j].coordinates
                    );
                    
                    if (distance < minDistance) {
                        minDistance = distance;
                        nearestIndex = j;
                    }
                }
            }

            if (nearestIndex !== -1) {
                order.push(nearestIndex);
                visited[nearestIndex] = true;
                current = nearestIndex;
            }
        }

        // Return to starting point to complete the circuit
        order.push(0);

        return order;
    }
};
}

// Initialize the app only if not already initialized
if (!window.app) {
    const app = new OrienteeringApp();
    window.app = app;
}

// Function to initialize the app for Turbo navigation
window.initializeOrienteeringApp = async function() {
    // Create app instance if it doesn't exist
    if (!window.app) {
        console.log('Creating new OrienteeringApp instance...');
        window.app = new OrienteeringApp();
    }
    
    // Initialize if not already initialized
    if (!window.app.initialized) {
        console.log('Initializing app and loading configuration...');
        await window.app.init();
        console.log('App initialization complete. Config loaded:', !!window.app.config);
    } else {
        console.log('App already initialized');
    }
    
    return window.app;
};

// Ensure initMap is available globally for Google Maps callback
window.initMap = function() {
    console.log('initMap callback triggered');
    if (window.app && window.app.initializeMap) {
        window.app.initializeMap();
    } else {
        console.log('App not ready yet, retrying in 100ms...');
        setTimeout(() => {
            if (window.app && window.app.initializeMap) {
                window.app.initializeMap();
            }
        }, 100);
    }
};