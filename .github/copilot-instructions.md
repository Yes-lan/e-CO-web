# e-CO Web - AI Coding Agent Instructions

**Project:** e-CO Web - Orienteering Course Management System  
**Repository:** Yes-lan/e-CO-web  
**Branch:** benji  
**Last Updated:** November 21, 2025

---

## 🎯 Project Overview

e-CO Web is a Symfony 7.3 web application for French teachers to create and manage orienteering courses. The system uses Docker with FrankenPHP, PostgreSQL, and vanilla JavaScript with Turbo Drive for SPA-like navigation.

**Key Users:**
- **Teachers (Desktop/PC):** Create courses, manage sessions, visualize on maps, download QR codes
- **Students (Mobile):** Scan QR codes at waypoints, GPS tracking (separate mobile app)

---

## 🏗️ Technical Stack

### Backend
- **Framework:** Symfony 7.3.4
- **PHP Version:** 8.4.15
- **Runtime:** FrankenPHP (modern PHP runtime with worker mode)
- **Database:** PostgreSQL 16
- **ORM:** Doctrine 3.x with migrations
- **API:** API Platform 4.2 (REST APIs)
- **Templates:** Twig 3.x
- **Admin:** EasyAdmin 4.x

### Frontend
- **JavaScript:** Vanilla JS (no build step for main app code)
- **CSS:** Custom CSS in `public/assets/css/style.css`
- **Navigation:** Turbo Drive (SPA-like behavior without full page reloads)
- **Maps:** Google Maps JavaScript API
- **Assets:** Symfony Asset Mapper + Webpack Encore (for vendor assets only)

### Infrastructure
- **Container Runtime:** Docker Compose
- **Web Server:** Caddy (via FrankenPHP)
- **Services:** php, database (PostgreSQL), adminer, mailpit, mailer
- **Ports:** 
  - localhost:80 (app)
  - localhost:8080 (Adminer)
  - localhost:8025 (Mailpit)

### Authentication
- **Security:** Symfony Security with custom form authenticator
- **Test Credentials:** test@test.com / password
- **Database:** app / !ChangeMe! / app

---

## 📁 Critical File Locations

### Configuration
- `config/bundles.php` - **CRITICAL:** Bundle registrations (API Platform, EasyAdmin, etc.)
- `config/packages/api_platform.yaml` - API Platform configuration
- `config/routes/redirects.yaml` - **CAUTION:** Legacy redirects, can conflict with controller routes
- `config/packages/security.yaml` - Authentication configuration
- `compose.yaml` + `compose.override.yaml` + `compose.prod.yaml` - Docker services

### Backend Code
- `src/Controller/ParcoursController.php` - Manages **Course** entities (routes: `/parcours/*`)
- `src/Controller/CourseController.php` - Manages **Session** entities (routes: `/courses/*`)
- `src/Controller/HomeController.php` - Homepage
- `src/Controller/SecurityController.php` - Login/logout
- `src/Controller/RedirectController.php` - Legacy redirects
- `src/Security/AuthenticatorECOAuthenticator.php` - Custom form authenticator

### Entities
- `src/Entity/Course.php` - **Orienteering courses** (beacons, boundaries)
- `src/Entity/Session.php` - **Running sessions** (tied to a course, has runners)
- `src/Entity/User.php` - Teachers/administrators
- `src/Entity/Beacon.php` - Waypoints (QR codes)
- `src/Entity/BoundariesCourse.php` - Course boundaries
- `src/Entity/Runner.php` - Students in a session
- `src/Entity/LogSession.php` - GPS tracking logs

### Frontend
- `templates/base.html.twig` - Main layout with header navigation
- `templates/home/index.html.twig` - Homepage
- `templates/courses/index.html.twig` - Sessions list
- `templates/course/index.html.twig` - Courses list (parcours)
- `templates/map/index.html.twig` - Google Maps viewer
- `public/assets/js/translations.js` - Frontend i18n
- `public/assets/css/style.css` - All styling

### Translations
- `translations/messages.fr.yaml` - French (primary)
- `translations/messages.en.yaml` - English
- `translations/messages.eu.yaml` - Basque

### Migrations
- `migrations/Version20251121130534.php` - **ONLY migration file** (cleaned from back branch)

### Documentation
- `documentation/project_front/` - Comprehensive frontend documentation (9 files)
- `documentation/goals/` - Business requirements
- `README.md` - Docker setup instructions

---

## ⚠️ Critical Naming Conventions (AVOID CONFUSION!)

### Controller vs Entity Mismatch

**IMPORTANT:** The controller names DO NOT match what they manage!

| Controller | Entity Managed | Routes | UI Display |
|------------|---------------|--------|------------|
| `ParcoursController` | **Course** | `/parcours/*` | "Courses"/"Cours" |
| `CourseController` | **Session** | `/courses/*` | "Sessions" |

**Why?** Historical reasons. The French word "parcours" means "course", but the entity is named `Course`. Sessions are called "courses" in routes but managed by `CourseController`.

**Always check:**
- `ParcoursController` → manages `Course` entity → orienteering courses with beacons
- `CourseController` → manages `Session` entity → running sessions with runners

---

## 🚫 Common Pitfalls & How to Avoid Them

### 1. Route Conflicts with redirects.yaml

**Problem:** Routes in `config/routes/redirects.yaml` take precedence over PHP attribute routes in controllers.

**Example of Conflict:**
```yaml
# redirects.yaml - This will OVERRIDE controller routes!
redirect_parcours:
    path: /parcours
    controller: Symfony\Bundle\FrameworkBundle\Controller\RedirectController
    defaults:
        route: 'app_parcours_list'
        permanent: true
```

If a controller has `#[Route('/parcours', name: 'app_parcours_list')]`, the YAML route wins, causing redirect loops.

**Solution:**
- Delete conflicting routes from `redirects.yaml`
- Keep only truly legacy redirects (like `/course/manage` → `/parcours`)
- Test routes with: `docker compose exec php php bin/console debug:router | grep "route_name"`
- Test route matching: `docker compose exec php php bin/console router:match /parcours`

**Current Safe Redirects:**
```yaml
# Only these are allowed (legacy URL support)
redirect_course_manage:
    path: /course/manage
    defaults:
        route: 'app_parcours_list'

redirect_course_create:
    path: /course/create
    defaults:
        route: 'app_parcours_create'
```

### 2. Locale Parameters (_locale)

**Problem:** Routes DO NOT use `_locale` parameters, but some code may try to pass them.

**Wrong:**
```php
return $this->redirectToRoute('app_home', ['_locale' => 'fr']);
```

**Correct:**
```php
return $this->redirectToRoute('app_home');
```

**Why?** The application supports translations via `Accept-Language` headers, but routes don't have `/{_locale}/` prefixes.

**Translation System:**
- Backend: Symfony translator with YAML files
- Frontend: JavaScript `translations.js` file
- No URL-based locale switching

### 3. Missing Bundle Registrations

**Problem:** Adding new Symfony packages requires manual bundle registration.

**Symptoms:**
- PHP container restarts constantly
- Errors about missing bundles in logs
- `docker compose logs php` shows class not found

**Solution:**
Always check `config/bundles.php` after `composer require`:
```php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class => ['all' => true],
    // ... ensure new packages are registered
];
```

### 4. Asset Compilation

**Problem:** JavaScript/CSS changes not appearing.

**Asset Types:**
- **Static assets** (`public/assets/`) - No compilation needed, just browser hard refresh (Ctrl+Shift+R)
- **Vendor assets** (Stimulus, Turbo) - Require Webpack Encore build

**Commands:**
```powershell
# Rebuild vendor assets only
npm install
npm run build

# No need for this if only editing public/assets/ files!
```

### 5. Cache Clearing

**ALWAYS clear cache after:**
- Changing routes
- Modifying services
- Updating configuration files
- Changing entity mappings

```powershell
docker compose exec php php bin/console cache:clear
```

**Don't need cache clear for:**
- Template changes (Twig auto-reloads in dev)
- Static CSS/JS in `public/assets/`
- Adding new controller methods (only routes need cache clear)

---

## 🔧 Essential Commands

### Docker Operations

```powershell
# Start all services
docker compose up --wait

# Stop all services
docker compose down

# View logs
docker compose logs php
docker compose logs database
docker compose logs -f  # Follow all logs

# Rebuild containers (after Dockerfile changes)
docker compose build --pull --no-cache
docker compose up --wait

# Access PHP container shell
docker compose exec php sh

# Access database
docker compose exec database psql -U app -d app
```

### Symfony Console

```powershell
# Clear cache (CRITICAL after config changes)
docker compose exec php php bin/console cache:clear

# List all routes
docker compose exec php php bin/console debug:router

# Test route matching
docker compose exec php php bin/console router:match /parcours

# Database migrations
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console doctrine:migrations:status

# Create new migration
docker compose exec php php bin/console make:migration

# Create test user
docker compose exec php php bin/console security:hash-password
```

### Asset Management

```powershell
# Install dependencies
npm install

# Build vendor assets (Stimulus, Turbo)
npm run build

# Watch mode (auto-rebuild on changes)
npm run watch

# Production build
npm run build:prod
```

### Database Access

- **Adminer:** http://localhost:8080
  - Server: `database`
  - Username: `app`
  - Password: `!ChangeMe!`
  - Database: `app`

---

## 🚀 Workflow Patterns

### Adding a New Route

1. **Add route in controller:**
```php
#[Route('/my-route', name: 'app_my_route')]
public function myRoute(): Response
{
    return $this->render('my_route/index.html.twig');
}
```

2. **Clear cache:**
```powershell
docker compose exec php php bin/console cache:clear
```

3. **Test route:**
```powershell
docker compose exec php php bin/console router:match /my-route
```

4. **Check for conflicts:**
```powershell
docker compose exec php php bin/console debug:router | grep "my-route"
```

### Debugging Route Issues

1. **Check if route exists:**
```powershell
docker compose exec php php bin/console debug:router | grep "route_name"
```

2. **Test route matching:**
```powershell
docker compose exec php php bin/console router:match /path/to/test
```

3. **Check redirects.yaml:**
```powershell
# Read the file
cat config/routes/redirects.yaml

# Look for conflicting paths
```

4. **Check container logs:**
```powershell
docker compose logs php | tail -100
```

### Modifying Entities

1. **Edit entity class** (e.g., `src/Entity/Course.php`)

2. **Create migration:**
```powershell
docker compose exec php php bin/console make:migration
```

3. **Review migration** in `migrations/` folder

4. **Run migration:**
```powershell
docker compose exec php php bin/console doctrine:migrations:migrate
```

5. **Clear cache:**
```powershell
docker compose exec php php bin/console cache:clear
```

### Adding Translations

1. **Backend (Symfony):**
   - Edit `translations/messages.{fr,en,eu}.yaml`
   - Use in Twig: `{{ 'key.name'|trans }}`
   - Use in PHP: `$this->translator->trans('key.name')`

2. **Frontend (JavaScript):**
   - Edit `public/assets/js/translations.js`
   - Use: `translations.get('key.name', locale)`

3. **No cache clear needed** for translation changes in dev

---

## 🧪 Testing Changes

### Manual Testing Checklist

1. **Start fresh:**
```powershell
docker compose down
docker compose up --wait
docker compose exec php php bin/console cache:clear
```

2. **Test routes:**
   - Homepage: http://localhost/
   - Login: http://localhost/login
   - Courses list: http://localhost/parcours
   - Sessions list: http://localhost/courses
   - Map: http://localhost/map

3. **Test authentication:**
   - Login with test@test.com / password
   - Check redirect after login (should go to `/parcours`)
   - Test logout

4. **Check logs:**
```powershell
docker compose logs php | tail -50
```

### Route Conflict Detection

```powershell
# List all routes with same path
docker compose exec php php bin/console debug:router | grep "/parcours"

# If you see multiple routes for same path, investigate:
# - Check config/routes/redirects.yaml
# - Check controller attribute routes
# - Remove conflicts in redirects.yaml
```

### Migration Testing

```powershell
# Check migration status
docker compose exec php php bin/console doctrine:migrations:status

# Rollback last migration
docker compose exec php php bin/console doctrine:migrations:migrate prev

# Re-run migration
docker compose exec php php bin/console doctrine:migrations:migrate
```

---

## 🗃️ Database Schema (Key Tables)

### Core Entities

```
course
├── id (PK)
├── name (course name)
├── description
├── created_at
├── beacons (OneToMany → beacon)
└── boundaries_course (OneToMany → boundaries_course)

beacon
├── id (PK)
├── course_id (FK → course)
├── name (waypoint name)
├── latitude
├── longitude
├── qr_code (base64 QR code image)
└── order_number

boundaries_course
├── id (PK)
├── course_id (FK → course)
├── latitude
└── longitude

session
├── id (PK)
├── course_id (FK → course)
├── name (session name)
├── start_date
├── end_date
└── runners (OneToMany → runner)

runner
├── id (PK)
├── id_session (FK → session)
├── first_name
├── last_name
└── log_sessions (OneToMany → log_session)

log_session
├── id (PK)
├── runner_id (FK → runner)
├── latitude
├── longitude
└── timestamp
```

### Relationships

- **Course** has many **Beacons** (waypoints)
- **Course** has many **BoundariesCourse** (boundary polygon)
- **Session** belongs to one **Course**
- **Session** has many **Runners** (students)
- **Runner** has many **LogSessions** (GPS tracking)

---

## 🎨 Frontend Architecture

### Turbo Drive Navigation

- **Behavior:** SPA-like navigation without full page reloads
- **How it works:** Intercepts link clicks, fetches via AJAX, replaces `<body>`
- **JavaScript state:** Preserved across navigations (OrienteeringApp singleton)

**Important for AI Agents:**
- Don't assume page reloads destroy state
- Use `turbo:load` event for initialization
- Check if already initialized before creating instances

### JavaScript Structure

```javascript
// public/assets/js/app.js (injected via Asset Mapper)
class OrienteeringApp {
    constructor() { /* ... */ }
    initializeMap() { /* ... */ }
    loadCourses() { /* ... */ }
}

// Initialization
document.addEventListener('turbo:load', () => {
    if (!window.orienteeringApp) {
        window.orienteeringApp = new OrienteeringApp();
    }
});
```

### CSS Architecture

- **Single file:** `public/assets/css/style.css` (~900 lines)
- **Structure:** Utility classes + component-specific styles
- **No preprocessor:** Plain CSS
- **Responsive:** Desktop-first (teachers use PCs)

---

## 📝 Code Style Guidelines

### PHP (Symfony)

```php
// Use readonly constructor promotion
public function __construct(
    private readonly EntityManagerInterface $entityManager,
    private readonly CourseRepository $courseRepository
) {}

// Use PHP 8 attributes for routes
#[Route('/parcours', name: 'app_parcours_list')]
public function listParcours(): Response
{
    // ...
}

// Type hints everywhere
public function createCourse(Request $request): JsonResponse
{
    // ...
}

// Use named arguments for clarity
return $this->json(
    data: $course,
    status: Response::HTTP_CREATED,
    headers: ['Content-Type' => 'application/json']
);
```

### Twig Templates

```twig
{# Always extend base #}
{% extends 'base.html.twig' %}

{# Set page title #}
{% block title %}My Page - e-CO{% endblock %}

{# Use translation filter #}
<h1>{{ 'page.title'|trans }}</h1>

{# Use path() for routes #}
<a href="{{ path('app_parcours_list') }}">{{ 'nav.parcours'|trans }}</a>
```

### JavaScript

```javascript
// Use modern ES6+ syntax
class MyComponent {
    constructor() {
        this.state = {};
    }

    async fetchData() {
        try {
            const response = await fetch('/api/endpoint');
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error:', error);
        }
    }
}

// Use const/let, never var
const apiUrl = '/api/courses';
let currentCourse = null;

// Use template literals
const html = `<div class="course-item">${course.name}</div>`;
```

---

## 🚨 Emergency Troubleshooting

### PHP Container Keeps Restarting

```powershell
# Check logs
docker compose logs php | tail -100

# Common causes:
# 1. Missing bundle in config/bundles.php
# 2. Syntax error in PHP files
# 3. Missing required services in services.yaml

# Solution: Fix the error, then restart
docker compose down
docker compose up --wait
```

### ERR_TOO_MANY_REDIRECTS

```powershell
# Likely cause: Route conflict in redirects.yaml

# 1. Check which route is matching
docker compose exec php php bin/console router:match /problematic-path

# 2. Check redirects.yaml
cat config/routes/redirects.yaml

# 3. Remove conflicting redirect
# 4. Clear cache
docker compose exec php php bin/console cache:clear

# 5. Test again
docker compose exec php php bin/console router:match /problematic-path
```

### Database Connection Failed

```powershell
# Check if database container is running
docker compose ps

# Restart database
docker compose restart database

# Check connection from PHP container
docker compose exec php php bin/console doctrine:query:sql "SELECT 1"

# If fails, check DATABASE_URL in .env.local or compose.yaml
```

### Asset Not Found (404)

```powershell
# For static assets (CSS/JS in public/assets/)
# - Just hard refresh browser (Ctrl+Shift+R)
# - No build needed

# For vendor assets (Stimulus, Turbo)
npm install
npm run build

# Check if entrypoints.json exists
ls public/build/entrypoints.json
```

### Login Redirect Loop

```powershell
# Check AuthenticatorECOAuthenticator.php
# onAuthenticationSuccess() should return RedirectResponse, not throw exception

# Should look like:
return new RedirectResponse($this->urlGenerator->generate('app_parcours_list'));

# NOT like:
throw new \Exception('...');
```

---

## 📚 Documentation References

### Comprehensive Docs in Repository

- **Frontend:** `documentation/project_front/` (9 detailed files)
  - 00-overview.md - Project purpose
  - 01-architecture.md - Tech stack details
  - 02-pages-routes.md - All routes and pages
  - 03-javascript-core.md - JS architecture
  - 04-map-system.md - Google Maps integration
  - 05-styling.md - CSS structure
  - 09-development-guide.md - Quick reference

- **Business Requirements:** `documentation/goals/`
  - courses.txt - Course management goals
  - students.txt - Student workflow goals
  - waypoints.txt - Waypoint/beacon goals

- **Docker Setup:** `README.md` (root)
- **Database Schema:** `database.dbml`

### External Documentation

- [Symfony 7.3 Docs](https://symfony.com/doc/7.3/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [API Platform](https://api-platform.com/docs/core/)
- [Turbo Drive](https://turbo.hotwired.dev/handbook/drive)
- [Google Maps JavaScript API](https://developers.google.com/maps/documentation/javascript)

---

## ✅ Best Practices Summary

### DO:
✅ Always clear cache after config/route changes  
✅ Check `config/routes/redirects.yaml` for conflicts  
✅ Use `router:match` to test routes  
✅ Remember `ParcoursController` → Course, `CourseController` → Session  
✅ Test with docker compose logs to catch errors early  
✅ Use absolute paths when referencing files  
✅ Check bundle registration in `config/bundles.php` after composer require  
✅ Use Doctrine migrations for database changes  
✅ Keep translation keys consistent across FR/EN/EU  
✅ Test authentication flow after security changes  

### DON'T:
❌ Don't pass `_locale` parameters to routes (not used)  
❌ Don't assume controller names match entity names  
❌ Don't add routes in redirects.yaml unless truly legacy  
❌ Don't edit Twig/CSS without hard refresh testing  
❌ Don't create multiple migrations without checking back branch  
❌ Don't throw exceptions in authentication success handlers  
❌ Don't use app_course_manage route (doesn't exist, use app_parcours_list)  
❌ Don't run npm build for static CSS/JS changes  
❌ Don't forget to run migrations after creating them  
❌ Don't assume page reloads destroy JS state (Turbo Drive!)  

---

## 🤖 AI Agent Specific Guidance

### When Making Code Changes:

1. **Always read context first** - Check existing patterns before proposing changes
2. **Use exact strings** - When using replace_string_in_file, include 3+ lines context
3. **Test incrementally** - Clear cache and test after each change
4. **Check logs immediately** - `docker compose logs php` after any backend change
5. **Preserve patterns** - Match existing code style and structure

### Common Agent Tasks:

**Adding a new entity:**
1. Create entity class in `src/Entity/`
2. Add repository in `src/Repository/`
3. Run `make:migration`
4. Review and run migration
5. Clear cache

**Fixing a route:**
1. Check `debug:router` for conflicts
2. Check `redirects.yaml` for overrides
3. Use `router:match` to verify
4. Clear cache and test

**Adding a feature:**
1. Read related controllers/entities
2. Check existing patterns in templates
3. Add translations to all 3 YAML files
4. Test with real data
5. Document in comments

**Debugging an issue:**
1. Check `docker compose logs php`
2. Use `router:match` for route issues
3. Check `bundles.php` for missing bundles
4. Verify database with Adminer
5. Test with curl or browser DevTools

### Multi-Agent Collaboration:

If another agent previously worked on this codebase:
- **Trust their fixes** - Don't revert working solutions
- **Read git history** - Check recent commits for context
- **Verify current state** - Test routes/features before assuming broken
- **Build on progress** - Continue from where they left off

---

## 📞 Key Contacts & Resources

- **Repository:** https://github.com/Yes-lan/e-CO-web
- **Branch:** benji
- **Test User:** test@test.com / password
- **Database:** localhost:8080 (Adminer) - app / !ChangeMe! / app
- **App URL:** http://localhost/
- **Docker Compose:** Standard Docker Compose CLI

---

## 🎓 Learning Resources

If you need to understand specific parts:
1. **Routes/Controllers:** Read `documentation/project_front/02-pages-routes.md`
2. **JavaScript:** Read `documentation/project_front/03-javascript-core.md`
3. **Database:** Check `database.dbml` for schema
4. **Docker Setup:** Read root `README.md`
5. **Business Logic:** Read `documentation/goals/*.txt`

---

**Remember:** This is a French teacher-focused desktop app. Prioritize clarity, reliability, and ease of use. Teachers are not developers - the system must "just work" without technical knowledge.
