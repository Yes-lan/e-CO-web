# e-CO Web - Technical Architecture

**Last Updated:** November 21, 2025

---

## 🏗️ System Architecture Overview

e-CO Web follows a **modern Symfony application architecture** with Docker containerization, combining server-side rendering with dynamic client-side interactions.

---

## 📦 Technology Stack Details

### Backend Stack

#### Symfony 7.3
- **Version**: 7.3.x (latest LTS)
- **Runtime**: FrankenPHP (modern PHP runtime with worker mode)
- **Template Engine**: Twig 3.x
- **Asset Management**: Asset Mapper (no Webpack/Encore)
- **Frontend Framework**: Turbo + Stimulus (Hotwired)

#### Database Layer
- **Database**: PostgreSQL 16+
- **ORM**: Doctrine 3.x
- **Migrations**: Doctrine Migrations (configured but not yet implemented)
- **Current Storage**: Temporary JSON files in `public/assets/data/`

#### PHP Configuration
```ini
# Development (frankenphp/conf.d/20-app.dev.ini)
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
display_errors = On
error_reporting = E_ALL

# Production (frankenphp/conf.d/20-app.prod.ini)
memory_limit = 512M
opcache.enable = 1
opcache.memory_consumption = 256
display_errors = Off
```

---

### Frontend Stack

#### JavaScript Architecture
- **Language**: Vanilla JavaScript ES6+
- **Module System**: ES6 Modules
- **No Build Tools**: Direct browser execution
- **Browser Support**: Modern evergreen browsers (Chrome, Firefox, Edge, Safari)

#### Key JavaScript Libraries
```javascript
// Core application
- OrienteeringApp class (custom, ~1689 lines)
  - Course management
  - Map integration
  - UI state management

// Third-party libraries
- Google Maps JavaScript API (v3)
  - Geometry library (distance calculations)
  - Places library (location search)
  - Marker library (advanced markers)
- QRCode.js (qrcode.min.js) - QR code generation
- Stimulus 3.x (via importmap) - Interactive controllers
- Turbo Drive 8.x (via importmap) - SPA-like navigation
```

#### CSS Architecture
```css
/* Single unified stylesheet */
public/assets/css/style.css (~904 lines)

/* Component sections */
1. Global resets and base styles
2. Header and navigation
3. Toolbar and button system
4. Map viewer components
5. Course creation forms
6. Modal dialogs
7. Homepage layout
8. Responsive design (desktop-first)
```

---

## 🐳 Docker Infrastructure

### Container Composition

```yaml
# compose.yaml (base configuration)
services:
  php:
    image: dunglas/frankenphp:latest
    - Symfony application runtime
    - Asset serving
    - Development hot-reload
    
  database:
    image: postgres:16-alpine
    - PostgreSQL database
    - Data persistence
    - Future migration target

# compose.override.yaml (development)
- Volume mounts for live code editing
- PHP development settings
- Xdebug ready (not enabled by default)

# compose.prod.yaml (production)
- Optimized PHP opcache
- FrankenPHP worker mode
- Automatic HTTPS via Caddy
- No volume mounts (immutable containers)
```

### Network Architecture

```
┌─────────────────────────────────────────────┐
│         User Browser (Teacher PC)           │
│         http://localhost or domain          │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│   FrankenPHP + Caddy (Container: php)       │
│   - HTTP/HTTPS termination                  │
│   - PHP-FPM alternative (faster)            │
│   - Asset serving                           │
│   - Symfony application                     │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│   PostgreSQL (Container: database)          │
│   - Port 5432 (internal network)            │
│   - Volume: db-data (persistent)            │
└─────────────────────────────────────────────┘
```

---

## 📁 Directory Structure

### Application Root
```
e-CO-WEB/
├── assets/                  # Stimulus/Turbo assets (importmap)
├── bin/                     # Symfony console, PHPUnit
├── config/                  # Symfony configuration
├── documentation/           # Project documentation
│   ├── database/           # Database schema docs
│   ├── goals/              # Business requirements
│   └── project_front/      # Frontend documentation (this folder)
├── frankenphp/             # FrankenPHP configuration
├── migrations/             # Doctrine migrations (future)
├── public/                 # Web root
│   ├── index.php          # Symfony front controller
│   └── assets/            # Public static assets
│       ├── css/           # Stylesheets
│       ├── js/            # JavaScript files
│       └── data/          # JSON data storage (temporary)
├── src/                    # Symfony application code
│   ├── Controller/        # Route controllers
│   ├── Entity/            # Doctrine entities (future)
│   └── Repository/        # Database repositories (future)
├── templates/              # Twig templates
├── tests/                  # PHPUnit tests
├── translations/           # i18n files (future)
├── var/                    # Cache, logs (container-only)
└── vendor/                 # Composer dependencies
```

### Public Assets Structure
```
public/assets/
├── css/
│   └── style.css           # Unified stylesheet (~904 lines)
├── js/
│   ├── app.js              # OrienteeringApp class (~1689 lines)
│   └── qrcode.min.js       # QR code generation library
└── data/                   # Temporary JSON storage
    ├── courses.json        # Course data
    ├── map-config.json     # Map configuration
    ├── test-boundary-points.json
    ├── test-waypoints.json
    └── courses/            # Individual course files
        └── README.md
```

---

## 🔄 Request Lifecycle

### Standard Page Request (Turbo Navigation)

```
1. User clicks link → Turbo intercepts
   ↓
2. Turbo fetches HTML via AJAX
   ↓
3. Symfony Router → Controller → Twig Template
   ↓
4. Turbo replaces <body> content (no full page reload)
   ↓
5. Browser fires turbo:load event
   ↓
6. OrienteeringApp reinitializes if on map page
   ↓
7. Google Maps API initializes (if needed)
   ↓
8. User sees updated page
```

### API Request (Future Backend Integration)

```
1. JavaScript fetch('/api/course/save')
   ↓
2. Symfony Router → HomeController::saveCourse()
   ↓
3. JSON decoding & validation
   ↓
4. Doctrine ORM → PostgreSQL (future)
   ↓ (currently)
5. JSON file write (temporary)
   ↓
6. JSON response to client
   ↓
7. JavaScript updates UI
```

---

## 🗺️ Google Maps Integration Architecture

### Loading Strategy
```javascript
// Async API loading to prevent warnings
1. Page loads → initializeMapPage() called
   ↓
2. Check if Google Maps already loaded
   ↓
3. If not: Dynamically inject <script> tag
   - URL: https://maps.googleapis.com/maps/api/js
   - Libraries: geometry, places, marker
   - Callback: initMap()
   ↓
4. Google Maps loads → calls initMap()
   ↓
5. initMap() → app.initializeMap()
   ↓
6. Map instance created with configuration
```

### Map Configuration Flow
```javascript
// Configuration loading (async)
await app.loadConfiguration()
  → Fetches /assets/data/map-config.json
  → Stores in app.config
  → Used for:
    - Default location (lat/lng)
    - Default zoom level
    - Default map type (hybrid/satellite/etc.)
    - Boundary settings
```

### Map Components
```javascript
OrienteeringApp {
  map: google.maps.Map           // Main map instance
  markers: Array                 // Course waypoint markers
  controlPoints: Array           // Course point data
  boundaryPolygon: Polygon       // Course boundary area
  coursePolyline: Polyline       // Course path line
  optimalPathPolyline: Polyline  // Ideal course path
  currentLocationMarker: Marker  // Teacher GPS location
  accuracyCircle: Circle         // GPS accuracy radius
}
```

---

## 🎨 Styling Architecture

### CSS Organization (style.css)
```css
/* 1. Reset & Base (lines 1-20) */
* { margin: 0; padding: 0; box-sizing: border-box; }

/* 2. Layout Containers (lines 21-120) */
.container, .header, .toolbar

/* 3. Button System (lines 121-250) */
.btn, .btn-primary, .btn-secondary, etc.

/* 4. Map Viewer Components (lines 251-450) */
#map, .sidebar, .control-points-list, .coordinates-display

/* 5. Course Creation Forms (lines 451-650) */
.form-section, .form-group, .waypoint-form

/* 6. Modal System (lines 651-750) */
.modal, .modal-overlay, .modal-content

/* 7. Homepage Layout (lines 751-850) */
.hero, .cta-section

/* 8. Responsive Design (lines 851-904) */
@media queries for tablets and mobile
```

### Design System
```css
/* Color Palette */
--primary-green: #2c5530      /* Headers, primary buttons */
--accent-green: #4a7c59       /* Gradients, highlights */
--success-green: #28a745      /* Success states */
--info-blue: #17a2b8          /* Info messages */
--warning-yellow: #ffc107     /* Warnings */
--danger-red: #dc3545         /* Errors, finish markers */

/* Typography */
Font Family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif
Base Size: 1rem (16px)
Headers: 2rem, 1.8rem, 1.5rem

/* Spacing System */
0.25rem, 0.5rem, 1rem, 1.5rem, 2rem
```

---

## 🔐 Security Architecture

### Authentication (Future Implementation)
```php
// config/packages/security.yaml
// Currently minimal, planned:
- User entity with roles (TEACHER, ADMIN)
- Form-based login
- Password hashing (bcrypt/argon2)
- CSRF protection (enabled via csrf.yaml)
```

### CORS & API Security
```php
// Currently local-only
// Production will require:
- CORS configuration for mobile app API access
- API token authentication
- Rate limiting
- Input validation & sanitization
```

### Google Maps API Security
```twig
{# Current: Hardcoded in templates #}
AIzaSyBi8sXKGGafzB837kvxraWuqohlZ-JJRu8

{# Production: Move to .env #}
GOOGLE_MAPS_API_KEY=your_key_here
{{ google_maps_key }}

{# Add HTTP referrer restrictions in Google Cloud Console #}
```

---

## 🧪 Testing Architecture

### PHPUnit Configuration
```xml
<!-- phpunit.dist.xml -->
<testsuites>
    <testsuite name="Project Test Suite">
        <directory>tests</directory>
    </testsuite>
</testsuites>

<!-- Currently disabled in CI (if: false) -->
```

### Manual Testing Strategy
```
1. Browser Testing (primary)
   - Chrome DevTools for debugging
   - Firefox for cross-browser validation
   - Edge for Windows-specific issues

2. Map Functionality
   - Manual testing required (Google Maps visual validation)
   - Coordinate accuracy checks
   - Marker interaction testing

3. Docker Testing
   docker compose up --wait
   # Test all features manually
   docker compose down
```

---

## 📊 Performance Considerations

### Frontend Optimization
```javascript
// Async Google Maps loading
- Prevents blocking page render
- Callback-based initialization
- Lazy marker creation

// LocalStorage caching
- Course data cached locally
- Reduces server requests
- Fallback for offline viewing

// Turbo Drive optimization
- Partial page replacements
- Browser history management
- Faster perceived performance
```

### Backend Optimization (Production)
```yaml
# FrankenPHP Worker Mode
- Keeps Symfony kernel in memory
- Faster request processing
- Reduced cold start times

# PHP Opcache
- Bytecode caching
- Reduced file I/O
- Production-only (disabled in dev)
```

---

## 🔧 Development vs Production

| Aspect | Development | Production |
|--------|------------|------------|
| **Runtime** | FrankenPHP standard | FrankenPHP worker mode |
| **Cache** | Disabled/cleared often | Enabled + opcache |
| **Errors** | Displayed on screen | Logged to files |
| **HTTPS** | HTTP only (localhost) | Automatic HTTPS (Caddy) |
| **Volumes** | Mounted for live editing | Immutable containers |
| **Assets** | Served dynamically | Pre-built in container |
| **Database** | Shared volume (db-data) | Persistent volume |

---

## 🚀 Deployment Architecture (Production)

```bash
# Build production containers
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache

# Start with environment variables
SERVER_NAME=courses.example.com \
APP_SECRET=your_secret_here \
DATABASE_URL=postgresql://user:pass@db:5432/courses \
docker compose -f compose.yaml -f compose.prod.yaml up --wait

# Caddy automatically provisions Let's Encrypt SSL
# Symfony runs in FrankenPHP worker mode
# Containers restart on failure
```

---

## 📈 Scalability Considerations

### Current Limitations
- Single-server architecture
- No load balancing
- Single PostgreSQL instance
- Session storage in PHP (filesystem)

### Future Scalability Options
```
1. Multi-container PHP deployment
   - Load balancer (Traefik/nginx)
   - Shared Redis session storage
   - Horizontal scaling

2. Database optimization
   - PostgreSQL replication (read replicas)
   - Connection pooling (PgBouncer)
   - Query optimization with indexes

3. Asset CDN
   - Static assets on CDN
   - Google Maps API direct from Google
   - Reduced server load
```

---

*This architecture supports the current needs of French teachers creating orienteering courses while allowing for future growth and backend integration.*
