# e-CO Web - Frontend Documentation Overview

**Last Updated:** November 21, 2025  
**Project Type:** Symfony 7.3 + Docker + Vanilla JavaScript Orienteering Course Management  
**Target Users:** French teachers (PC/Desktop interface)  
**Language:** French UI

---

## 📋 Table of Contents

1. **00-overview.md** - This file: Project overview and documentation structure ✅
2. **01-architecture.md** - Technical architecture and stack details ✅
3. **02-pages-routes.md** - All pages, routes, and their purposes ✅
4. **03-javascript-core.md** - JavaScript architecture and OrienteeringApp class ✅
5. **04-map-system.md** - Google Maps integration and course visualization ✅
6. **05-styling.md** - CSS architecture and component styling ✅
7. **06-data-flow.md** - Data management, APIs, and backend integration ⏳ (In Progress)
8. **07-turbo-navigation.md** - Turbo Drive handling and SPA-like behavior ⏳ (In Progress)
9. **08-course-workflow.md** - Complete course creation and management workflow ⏳ (In Progress)
10. **09-development-guide.md** - Development setup, commands, and best practices ✅

---

## 🎯 Project Purpose

**e-CO Web** is a desktop-focused web application for **French teachers** to create, manage, and visualize orienteering courses for their students. The application handles:

### Teacher Workflow (PC/Desktop - French Interface)
- **Create courses** with waypoints, boundaries, and QR codes
- **Manage courses** and sessions (archive, view, edit before finalization)
- **Visualize courses** on interactive Google Maps
- **Download QR codes** for physical waypoint placement
- **Track student progress** through GPS tracking during sessions

### Student Workflow (Smartphone - Separate App)
- **Scan QR codes** at waypoints during orienteering courses
- **GPS tracking** records their paths automatically
- **Start/end courses** by scanning designated QR codes
- Students use a separate mobile application (not covered in this documentation)

---

## 🏗️ High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    e-CO Web (PC/Desktop)                     │
│                 French Teacher Interface                     │
├─────────────────────────────────────────────────────────────┤
│  Frontend Layer                                              │
│  - Symfony 7.3 Twig Templates (French UI)                   │
│  - Vanilla JavaScript ES6+ (OrienteeringApp class)          │
│  - Google Maps JavaScript API (course visualization)        │
│  - Turbo Drive (SPA-like navigation)                        │
│  - Stimulus Controllers (interactive components)            │
├─────────────────────────────────────────────────────────────┤
│  Backend Layer                                               │
│  - Symfony Controllers (routing, API endpoints)             │
│  - Doctrine ORM (database interactions)                     │
│  - PostgreSQL Database                                       │
│  - JSON temporary storage (courses.json)                    │
├─────────────────────────────────────────────────────────────┤
│  Infrastructure Layer                                        │
│  - Docker Compose (development environment)                 │
│  - FrankenPHP (PHP runtime with worker mode)               │
│  - Caddy Server (automatic HTTPS in production)            │
└─────────────────────────────────────────────────────────────┘
```

---

## 🌟 Key Features

### 1. Course Creation
- Interactive map-based course boundary definition
- Waypoint management with customizable properties
- Automatic GPS coordinate assignment within boundaries
- QR code generation for each waypoint
- Support for start/end point as same location

### 2. Course Management
- Archive courses instead of deletion (preserve names for reuse)
- View all courses in organized list
- Edit courses before finalization
- Create multiple sessions from a single course
- Courses become immutable after finalization

### 3. Course Visualization
- Display courses on Google Maps (Hybrid/Satellite/Terrain views)
- Show student paths from GPS tracking data
- Select individual or all student groups in a session
- Real-time teacher GPS location ("Ma Position" feature)
- Toggle course boundaries, optimal paths, and POIs

### 4. Session Management
- Sessions represent course instances with student groups
- Students selected per session (not tied to course)
- Multiple sessions can reuse the same finalized course
- Session-based GPS path tracking and visualization

---

## 📱 Mobile Integration (Context)

While this documentation focuses on the **PC/Desktop frontend**, understanding the mobile integration is important:

- **Separate mobile app** (not part of this codebase) handles student interactions
- **QR code scanning** by students validates waypoint visits
- **Teacher QR scanning** during setup registers actual GPS coordinates
- **GPS tracking** on student phones records paths saved to backend
- **Session coordination** between PC and mobile apps via shared backend

---

## 🎨 UI/UX Principles

### French Language First
- All user-facing text in French (buttons, labels, messages, alerts)
- Terminology: "Parcours" (course), "Balise" (waypoint), "Session", etc.
- French date/time formatting where applicable

### Desktop-Optimized
- Designed for PC/laptop screens (1280px+ primary target)
- Responsive fallback for tablet/mobile viewing
- Keyboard shortcuts and mouse interactions prioritized
- Large, accessible buttons and controls

### Education-Focused
- Clean, professional appearance suitable for academic use
- Clear visual hierarchy and intuitive navigation
- Helpful tooltips and guidance text throughout
- Color-coded waypoint types (Start=green, Control=blue, Finish=red)

---

## 🔧 Technology Stack Summary

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend Framework** | Symfony 7.3 | PHP framework, routing, templating |
| **Database** | PostgreSQL | Data persistence |
| **ORM** | Doctrine | Database abstraction |
| **Frontend JS** | Vanilla ES6+ | OrienteeringApp class, map interactions |
| **Maps** | Google Maps API | Interactive course visualization |
| **Navigation** | Turbo Drive | SPA-like page transitions |
| **CSS** | Custom CSS | Component-based styling |
| **Runtime** | FrankenPHP | Modern PHP runtime in Docker |
| **Containerization** | Docker Compose | Development environment |
| **Web Server** | Caddy | Production HTTPS handling |

---

## 📂 Documentation Structure

Each documentation file covers a specific aspect of the frontend:

- **Architecture files (01-03)**: Technical setup, routing, JavaScript structure
- **Feature files (04-06)**: Maps, styling, data management
- **Integration files (07-08)**: Turbo navigation, complete workflows
- **Developer guide (09)**: Setup, commands, best practices

---

## 🚀 Quick Start Reference

```powershell
# Navigate to project directory
cd C:\Users\baill\OneDrive\Documents\course_orientation_projet\course-orientation\e-CO-WEB

# Start development environment
docker compose up --wait

# Clear Symfony cache after code changes
docker compose exec php php bin/console cache:clear

# Access application
# Homepage: http://localhost/
# Map viewer: http://localhost/map
# Create course: http://localhost/course/create
# Manage courses: http://localhost/course/manage
```

---

## 📝 Important Notes

### Current Development Status (November 2025)
- ✅ Frontend UI complete with French interface
- ✅ Google Maps integration functional
- ✅ Course creation workflow implemented
- ✅ Temporary JSON storage operational
- ⚠️ Backend database integration in progress
- ⚠️ QR code generation ready, download feature pending
- ⚠️ Session management UI built, backend pending
- ⚠️ Student GPS path visualization ready, data integration pending

### Known Limitations
- Google Maps API key hardcoded in templates (move to .env for production)
- Courses stored in JSON files temporarily (database migration in progress)
- PHPUnit tests disabled in CI pipeline
- Some features have UI but await backend API implementation

---

## 🔗 Related Documentation

- **Database Schema**: `documentation/database/database-schema.txt`
- **Project Goals**: `documentation/goals/*.txt` (courses, students, waypoints)
- **Copilot Instructions**: `.github/copilot-instructions.md`
- **Docker Setup**: `docs/*.md` (docker configuration guides)

---

## 📞 Development Team Coordination

This application is part of a larger **orienteering education platform**:

- **Frontend Team** (this codebase): Teacher PC/Desktop interface
- **Backend Team**: Shared API, database, business logic
- **Mobile Team**: Student smartphone application

**Critical**: Always sync with backend team on API contracts, data structures, and session coordination logic.

---

*This overview provides the roadmap for navigating the e-CO Web frontend documentation. Refer to specific numbered files for detailed technical information.*
