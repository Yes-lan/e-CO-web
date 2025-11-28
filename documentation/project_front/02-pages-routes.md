# e-CO Web - Pages and Routes

**Last Updated:** November 21, 2025

---

## 🗺️ Application Routes Overview

All routes are defined in `src/Controller/HomeController.php` with corresponding Twig templates.

---

## 📄 Page Inventory

### 1. Homepage (`/`)

**Route Name:** `app_home`  
**Controller:** `HomeController::index()`  
**Template:** `templates/home/index.html.twig`  
**Purpose:** Landing page for teachers

#### Features
- Welcome message and project description
- Three primary action buttons:
  1. **Créer un Parcours** → Course creation page
  2. **Visualiser les Parcours** → Map viewer page
  3. **Gérer les Parcours** → Course management page

#### UI Elements
```twig
<header class="header">
  <h1>🧭 e-CO Web</h1>
  <p>Créateur de Parcours d'Orientation pour Enseignants</p>
</header>

<section class="hero">
  <h2>Bienvenue sur e-CO Web</h2>
  <div class="cta-section">
    <!-- Three main action buttons -->
  </div>
</section>
```

#### Navigation Flow
```
Homepage (/)
├─→ Create Course (/course/create)
├─→ View Map (/map)
└─→ Manage Courses (/course/manage)
```

---

### 2. Map Viewer (`/map`)

**Route Name:** `app_map`  
**Controller:** `HomeController::map()`  
**Template:** `templates/map/index.html.twig`  
**Purpose:** Visualize courses on interactive Google Maps

#### Features
- **Google Maps integration** with hybrid/satellite/terrain views
- **Course selection** from sidebar list
- **Session selection** to view student GPS paths
- **Teacher GPS location** ("Ma Position" button)
- **Toggle controls** for:
  - Boundary fill visibility
  - Optimal path display
  - POI (Points of Interest) visibility
  - Coordinates display panel

#### Layout Structure
```html
<div class="map-page-container">
  <!-- Header -->
  <header class="header">
    <h1>🗺️ Visualiseur de Parcours d'Orientation</h1>
  </header>

  <!-- Toolbar -->
  <div class="toolbar">
    <button id="loadCourseBtn">📂 Charger un parcours</button>
    <button id="refreshBtn">🔄 Actualiser</button>
    <button id="locationBtn">📍 Ma Position</button>
    <!-- Map type selector -->
    <!-- Toggle switches -->
  </div>

  <!-- Main Content -->
  <div class="map-content">
    <!-- Left Sidebar -->
    <aside class="sidebar">
      <div class="control-points-list"><!-- Waypoints --></div>
      <div class="courses-list"><!-- Available courses --></div>
    </aside>

    <!-- Map Container -->
    <div id="map" class="map-container"></div>

    <!-- Coordinates Display -->
    <div class="coordinates-display"><!-- GPS info --></div>
  </div>
</div>
```

#### JavaScript Integration
```javascript
// templates/map/index.html.twig JavaScript blocks
<script>
  // Turbo navigation handling
  document.addEventListener('turbo:load', ...)
  document.addEventListener('turbo:before-visit', ...)
  
  // Google Maps async loading
  function loadGoogleMaps() { ... }
  function initMap() { ... }
  
  // OrienteeringApp initialization
  async function initializeMapPage() { ... }
</script>
```

#### Key Interactions
1. **Load Course**: Opens modal with course list
2. **Select Course**: Displays waypoints and boundary on map
3. **Select Session**: Shows student GPS paths for that session
4. **Ma Position**: Centers map on teacher's current GPS location
5. **Toggle Controls**: Show/hide boundary fill, optimal path, POIs

---

### 3. Course Creation (`/course/create`)

**Route Name:** `app_course_create`  
**Controller:** `HomeController::createCourse()`  
**Template:** `templates/course/create.html.twig`  
**Purpose:** Create new orienteering courses with waypoints and boundaries

#### Features
- **Multi-section form** for course configuration
- **Interactive boundary map** for defining course area
- **Waypoint management** with custom properties
- **QR code generation** preview
- **GPS coordinate assignment** (automatic or manual)
- **Course validation** before finalization

#### Form Sections

##### Section 1: General Information
```html
<div class="form-section">
  <h2>📋 Informations Générales</h2>
  
  <input id="courseName" name="courseName" required>
  <textarea id="courseDescription" name="courseDescription"></textarea>
</div>
```

##### Section 2: Start/End Point
```html
<div class="form-section">
  <h2>🚩 Point de Départ/Arrivée</h2>
  
  <input id="startPointName" name="startPointName" required>
  <textarea id="startPointDescription"></textarea>
  <p>📱 GPS coordinates registered via QR scan on field</p>
</div>
```

##### Section 3: Course Boundaries
```html
<div class="form-section">
  <h2>🗺️ Limites du Parcours</h2>
  
  <!-- Interactive Google Map -->
  <div id="boundaryMap" style="height: 400px;"></div>
  
  <button id="addBoundaryPoint">📍 Ajouter un point de limite</button>
  <button id="clearBoundaryPoints">🗑️ Effacer les limites</button>
  <button id="loadTestBoundary">🧪 Charger limites de test</button>
  
  <div id="boundaryPointsList"><!-- Boundary point list --></div>
</div>
```

**Boundary Map Features:**
- Click to add boundary points (when button active)
- Drag to move existing points
- Right-click to delete points
- Automatic polygon drawing (3+ points)
- Test data loading for quick setup

##### Section 4: Waypoint Configuration
```html
<div class="form-section">
  <h2>🎯 Configuration des Balises</h2>
  
  <div class="form-group">
    <label>Nombre de balises de contrôle</label>
    <input type="number" id="numControlPoints" min="1" max="50" value="5">
    <button id="generateWaypointsBtn">✨ Générer les balises</button>
  </div>
  
  <div id="waypointsContainer">
    <!-- Dynamically generated waypoint forms -->
  </div>
</div>
```

**Waypoint Form Structure (per waypoint):**
```html
<div class="waypoint-form">
  <h3>🔵 Balise de Contrôle #1</h3>
  
  <input name="name" placeholder="Nom de la balise">
  <textarea name="description"></textarea>
  <input type="number" name="latitude" step="0.00000001">
  <input type="number" name="longitude" step="0.00000001">
  
  <button class="assignRandomCoords">🎲 Coordonnées aléatoires</button>
  <button class="generateQRCode">📱 Générer QR Code</button>
  <button class="removeWaypoint">🗑️ Supprimer</button>
  
  <div class="qr-code-preview"><!-- QR code display --></div>
</div>
```

##### Section 5: Course Preview & Finalization
```html
<div class="form-section">
  <h2>👁️ Aperçu et Finalisation</h2>
  
  <!-- Preview map showing all waypoints and boundary -->
  <div id="previewMap" style="height: 400px;"></div>
  
  <div class="course-stats">
    <p><strong>Points de contrôle:</strong> <span id="statsControlPoints">0</span></p>
    <p><strong>Distance estimée:</strong> <span id="statsDistance">0 km</span></p>
    <p><strong>Surface du parcours:</strong> <span id="statsArea">0 km²</span></p>
  </div>
  
  <button type="submit" class="btn btn-success">
    💾 Enregistrer le Parcours
  </button>
</div>
```

#### Course Creation Workflow
```
1. Enter course name and description
   ↓
2. Define start/end point details
   ↓
3. Draw course boundary on map (3+ points)
   ↓
4. Specify number of control waypoints
   ↓
5. Generate waypoint forms
   ↓
6. For each waypoint:
   - Enter name and description
   - Assign GPS coordinates (auto/manual)
   - Generate QR code
   ↓
7. Review course preview and statistics
   ↓
8. Submit form → Save course to database/JSON
```

#### JavaScript Functionality
```javascript
// Course creation specific JS (~800 lines in template)
- Boundary map initialization
- Waypoint form generation
- QR code generation (QRCode.js)
- Random coordinate assignment within boundary
- Course preview map
- Distance/area calculations
- Form validation
- AJAX submission to /api/course/save
```

---

### 4. Course Management (`/course/manage`)

**Route Name:** `app_course_manage`  
**Controller:** `HomeController::manageCourses()`  
**Template:** `templates/course/manage.html.twig`  
**Purpose:** View, edit, archive, and manage existing courses

#### Features
- **Course list view** with filtering
- **Archive courses** (instead of deletion)
- **Edit courses** (before finalization only)
- **Session management** for each course
- **QR code download** for printing
- **Course statistics** display

#### Layout Structure
```html
<div class="course-management-container">
  <header class="header">
    <h1>🗂️ Gestion des Parcours</h1>
  </header>

  <!-- Filters and Actions -->
  <div class="management-toolbar">
    <input type="text" id="searchCourses" placeholder="🔍 Rechercher...">
    <select id="filterStatus">
      <option value="all">Tous les parcours</option>
      <option value="active">Actifs</option>
      <option value="archived">Archivés</option>
      <option value="draft">Brouillons</option>
    </select>
    <button id="createNewCourse">➕ Nouveau Parcours</button>
  </div>

  <!-- Course Cards Grid -->
  <div class="courses-grid">
    <!-- Individual course cards -->
  </div>
</div>
```

#### Course Card Structure
```html
<div class="course-card">
  <div class="course-card-header">
    <h3>{{ course.name }}</h3>
    <span class="status-badge">{{ course.status }}</span>
  </div>
  
  <div class="course-card-body">
    <p class="description">{{ course.description }}</p>
    <div class="course-stats">
      <span>📍 {{ course.waypoints.length }} balises</span>
      <span>📅 {{ course.created_at }}</span>
      <span>👥 {{ course.sessions.length }} sessions</span>
    </div>
  </div>
  
  <div class="course-card-actions">
    <button class="btn-view">👁️ Visualiser</button>
    <button class="btn-edit">✏️ Modifier</button>
    <button class="btn-sessions">📋 Sessions</button>
    <button class="btn-qrcodes">📱 QR Codes</button>
    <button class="btn-archive">📦 Archiver</button>
  </div>
</div>
```

#### Session Management Modal
```html
<div class="modal" id="sessionModal">
  <div class="modal-content">
    <h2>📋 Sessions - {{ course.name }}</h2>
    
    <!-- Existing sessions list -->
    <div class="sessions-list">
      <div class="session-item">
        <span>Session du {{ date }}</span>
        <span>{{ students.length }} groupes</span>
        <button>👁️ Voir</button>
        <button>📊 Statistiques</button>
      </div>
    </div>
    
    <!-- Create new session -->
    <button id="createSessionBtn">➕ Nouvelle Session</button>
  </div>
</div>
```

---

## 🔌 API Endpoints (Backend Routes)

### POST `/api/course/save`

**Route Name:** `api_course_save`  
**Controller:** `HomeController::saveCourse()`  
**Method:** POST  
**Content-Type:** application/json

#### Request Body
```json
{
  "name": "Forest Challenge",
  "description": "Orienteering course in forest",
  "start_point": {
    "name": "Parking entrance",
    "description": "Main parking lot",
    "latitude": null,
    "longitude": null
  },
  "boundary_points": [
    {"lat": 45.5017, "lng": -73.5673},
    {"lat": 45.5020, "lng": -73.5680},
    {"lat": 45.5015, "lng": -73.5685}
  ],
  "waypoints": [
    {
      "name": "Control Point 1",
      "description": "Near the oak tree",
      "latitude": 45.50175,
      "longitude": -73.56750,
      "type": "control",
      "sequence_order": 1,
      "qr_code_data": "unique_qr_string"
    }
  ],
  "created_at": "2025-11-21T10:30:00Z"
}
```

#### Response (Success)
```json
{
  "success": true,
  "message": "Parcours enregistré avec succès",
  "course_id": 123,
  "qr_codes": [
    {
      "waypoint_id": 1,
      "qr_code_url": "/assets/qrcodes/course_123_wp_1.png"
    }
  ]
}
```

#### Response (Error)
```json
{
  "error": "Invalid data",
  "details": "Course name is required"
}
```

#### Current Implementation
```php
// src/Controller/HomeController.php
public function saveCourse(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    if (!$data || !isset($data['name'])) {
        return new JsonResponse(['error' => 'Invalid data'], 400);
    }

    // Currently saves to JSON file
    $coursesFile = $this->getParameter('kernel.project_dir') 
                 . '/public/assets/data/courses.json';
    
    // Read existing courses
    $coursesData = ['courses' => []];
    if (file_exists($coursesFile)) {
        $coursesData = json_decode(file_get_contents($coursesFile), true);
    }
    
    // Add new course
    $data['id'] = count($coursesData['courses']) + 1;
    $data['created_at'] = date('Y-m-d H:i:s');
    $coursesData['courses'][] = $data;
    
    // Save back to file
    file_put_contents($coursesFile, json_encode($coursesData, JSON_PRETTY_PRINT));
    
    return new JsonResponse([
        'success' => true,
        'course_id' => $data['id']
    ]);
}
```

---

## 🧭 Navigation Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      Homepage (/)                            │
│                    Landing Page                              │
└─────┬───────────────────┬───────────────────┬───────────────┘
      │                   │                   │
      │                   │                   │
      ▼                   ▼                   ▼
┌─────────────┐   ┌──────────────┐   ┌──────────────────┐
│   /map      │   │/course/create│   │ /course/manage   │
│  Map Viewer │   │Course Builder│   │Course Management │
└─────────────┘   └──────────────┘   └──────────────────┘
      │                   │                   │
      │                   │                   │
      ├─→ Load Course ────┘                   │
      │                                        │
      └─→ View Sessions ←─────────────────────┘
```

### Cross-Page Navigation

```javascript
// All pages have navigation links
<a href="{{ path('app_home') }}">← Retour à l'accueil</a>
<a href="{{ path('app_map') }}">🗺️ Visualiser</a>
<a href="{{ path('app_course_create') }}">➕ Créer</a>
<a href="{{ path('app_course_manage') }}">🗂️ Gérer</a>

// Turbo handles navigation without full page reloads
// Except for map page which has special reinitialization
```

---

## 📱 Responsive Behavior

All pages adapt to different screen sizes:

### Desktop (1280px+)
- Full sidebar and main content side-by-side
- Large map viewport
- Multi-column course grids

### Tablet (768px - 1279px)
- Sidebar collapses to toggle menu
- Single-column course cards
- Adjusted map height

### Mobile (< 768px)
- Stacked layout (sidebar above/below map)
- Touch-optimized buttons
- Simplified navigation

```css
/* style.css responsive breakpoints */
@media (max-width: 1280px) { /* Tablet adjustments */ }
@media (max-width: 768px)  { /* Mobile adjustments */ }
```

---

## 🔐 Route Protection (Future)

Currently, all routes are publicly accessible. Planned security:

```php
// config/packages/security.yaml (future)
access_control:
    - { path: ^/course/create, roles: ROLE_TEACHER }
    - { path: ^/course/manage, roles: ROLE_TEACHER }
    - { path: ^/api/, roles: ROLE_TEACHER }
    - { path: ^/map, roles: IS_AUTHENTICATED_ANONYMOUSLY }
```

---

*This page inventory covers all teacher-facing routes in the e-CO Web application. Mobile app routes are handled separately by the mobile development team.*
