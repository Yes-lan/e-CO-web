# e-CO Web - Development Guide (Quick Reference)

**Last Updated:** November 21, 2025

---

## 🚀 Quick Start Commands

### Start Development Environment
```powershell
cd C:\Users\baill\OneDrive\Documents\course_orientation_projet\course-orientation\e-CO-WEB
docker compose up --wait
```

### Clear Symfony Cache (ALWAYS after code changes)
```powershell
cd C:\Users\baill\OneDrive\Documents\course_orientation_projet\course-orientation\e-CO-WEB
docker compose exec php php bin/console cache:clear
```

### Stop Environment
```powershell
docker compose down
```

### Rebuild Containers (after Dockerfile changes)
```powershell
docker compose build --pull --no-cache
docker compose up --wait
```

---

## 🌐 Access URLs

- **Homepage:** http://localhost/
- **Map Viewer:** http://localhost/map
- **Create Course:** http://localhost/course/create
- **Manage Courses:** http://localhost/course/manage

---

## 📁 Key Files Reference

### Frontend
- `public/assets/js/app.js` - OrienteeringApp class (~1689 lines)
- `public/assets/css/style.css` - All styling (~904 lines)
- `public/assets/data/courses.json` - Course data storage (temporary)
- `public/assets/data/map-config.json` - Map configuration

### Backend
- `src/Controller/HomeController.php` - All routes and API endpoints
- `config/routes.yaml` - Route definitions
- `templates/*.html.twig` - Twig templates

### Documentation
- `documentation/project_front/*.md` - Frontend documentation (you are here!)
- `documentation/database/database-schema.txt` - Database structure
- `documentation/goals/*.txt` - Business requirements
- `.github/copilot-instructions.md` - AI agent instructions

---

## 🔧 Common Development Tasks

### Adding a New Page

1. **Add route in HomeController.php:**
```php
#[Route('/my-page', name: 'app_my_page')]
public function myPage(): Response
{
    return $this->render('my-page/index.html.twig');
}
```

2. **Create template:**
```twig
{% extends 'base.html.twig' %}
{% block title %}My Page - e-CO{% endblock %}
{% block body %}
    <div class="container">
        <!-- Content -->
    </div>
{% endblock %}
```

3. **Clear cache:**
```powershell
docker compose exec php php bin/console cache:clear
```

### Modifying CSS

1. **Edit:** `public/assets/css/style.css`
2. **Hard refresh browser:** Ctrl+Shift+R (Windows) or Ctrl+F5
3. **No cache clear needed** (static file)

### Modifying JavaScript

1. **Edit:** `public/assets/js/app.js`
2. **Hard refresh browser:** Ctrl+Shift+R
3. **Check console for errors:** F12 → Console tab

### Adding Google Maps Features

1. **Locate OrienteeringApp class:** `public/assets/js/app.js`
2. **Add method to class:**
```javascript
newFeature() {
    if (!this.map) return;
    // Your code
}
```
3. **Call from event listener:**
```javascript
setupEventListeners() {
    const btn = document.getElementById('myBtn');
    if (btn) {
        btn.addEventListener('click', () => this.newFeature());
    }
}
```

---

## 🐛 Troubleshooting

### Map Not Displaying

**Symptoms:** Blank map area, no Google Maps  
**Fixes:**
1. Check browser console for JavaScript errors (F12)
2. Verify Google Maps API key in template
3. Ensure `#map` container exists in DOM
4. Check `window.app.initialized` in console

```javascript
// Debug in browser console
console.log('App initialized:', window.app?.initialized);
console.log('Map loaded:', window.app?.mapsLoaded);
console.log('Map instance:', window.app?.map);
```

### Turbo Navigation Issues

**Symptoms:** Page doesn't update properly after navigation  
**Fixes:**
1. Check `turbo:load` and `turbo:before-visit` events
2. Verify initialization flags reset on navigation
3. Add delays if DOM not ready:

```javascript
document.addEventListener('turbo:load', function() {
    setTimeout(initializeMapPage, 100);
});
```

### Cache Problems

**Symptoms:** Changes not visible after code edit  
**Fixes:**
```powershell
# Clear Symfony cache
docker compose exec php php bin/console cache:clear

# Hard refresh browser
Ctrl+Shift+R (Windows) or Ctrl+F5

# Check file timestamps
ls -l public/assets/js/app.js
```

### Docker Permission Issues

**Symptoms:** Can't edit files, "permission denied" errors  
**Fixes:**
```powershell
# Fix permissions (Linux/Mac)
docker compose exec php chown -R $(id -u):$(id -g) .

# Windows: Run PowerShell as Administrator
# Or use Docker Desktop volume management
```

### JSON File Not Loading

**Symptoms:** "Aucun parcours disponible" message  
**Fixes:**
1. Check file exists: `public/assets/data/courses.json`
2. Verify JSON is valid (use JSONLint)
3. Check browser Network tab (F12) for 404 errors
4. Ensure cache-busting query parameter used:

```javascript
fetch('/assets/data/courses.json?t=' + Date.now())
```

---

## 📊 Development Workflow Best Practices

### 1. Before Starting Work
```powershell
# Start environment
docker compose up --wait

# Verify containers running
docker compose ps

# Check logs if issues
docker compose logs php
```

### 2. During Development
- **Test frequently** in browser (don't wait until finished)
- **Check console** for JavaScript errors (F12)
- **Clear cache** after backend changes
- **Hard refresh** after frontend changes
- **Use descriptive commit messages**

### 3. Before Committing
```powershell
# Test the application works
# Clear any debugging code
# Update documentation if needed

# Check what changed
git status
git diff

# Unstage copilot-instructions.md if present
git reset HEAD .github/copilot-instructions.md

# Commit
git add .
git commit -m "feat: descriptive message"
```

---

## 🧪 Testing Checklist

### Map Viewer Testing
- [ ] Map loads correctly
- [ ] Course list populates
- [ ] Selecting course displays waypoints
- [ ] Markers show correct colors (Start=green, Control=blue, Finish=red)
- [ ] Boundary polygon displays
- [ ] Toggle buttons work (boundary, optimal path, POIs)
- [ ] "Ma Position" button gets GPS location
- [ ] Coordinates display updates on map click

### Course Creation Testing
- [ ] Form renders correctly
- [ ] Boundary map initializes
- [ ] Can add/remove boundary points
- [ ] Can drag boundary points
- [ ] Waypoint forms generate correctly
- [ ] Random coordinates button works
- [ ] QR code generation works
- [ ] Preview map displays course
- [ ] Form submission saves course
- [ ] Success message shows

### Cross-Browser Testing
- [ ] Chrome (primary)
- [ ] Firefox
- [ ] Edge
- [ ] Safari (if available)

---

## 📝 Code Style Guidelines

### PHP (Symfony)
```php
// Use strict typing
declare(strict_types=1);

// Type hints for parameters and returns
public function saveCourse(Request $request): JsonResponse
{
    // Clear method names
    // Early returns for validation
    // Meaningful variable names
}
```

### JavaScript
```javascript
// Use ES6+ features
const myFunction = async () => {
    // Async/await over promises
    // Arrow functions
    // Template literals
    // Destructuring
};

// Clear method names
createMarker(point, index, type) {
    // Descriptive variable names
    // Comments for complex logic
}
```

### CSS
```css
/* Component-based organization */
.course-card {
    /* Grouped related properties */
    /* Consistent spacing units (rem) */
    /* Descriptive class names */
}

/* Use existing classes before creating new ones */
/* Check for duplicate styles */
```

### Twig Templates
```twig
{# Comments for clarity #}
{% extends 'base.html.twig' %}

{# Descriptive block names #}
{% block title %}Page Title - e-CO{% endblock %}

{# Indentation for readability #}
{% block body %}
    <div class="container">
        {{ content }}
    </div>
{% endblock %}
```

---

## 🔍 Debugging Tools

### Browser DevTools (F12)
- **Console:** JavaScript errors and logs
- **Network:** AJAX requests, file loading
- **Elements:** Inspect HTML/CSS
- **Sources:** Set JavaScript breakpoints
- **Application:** Check localStorage

### Chrome DevTools Shortcuts
```
F12              - Open/close DevTools
Ctrl+Shift+C     - Inspect element mode
Ctrl+Shift+R     - Hard refresh (bypass cache)
Ctrl+Shift+I     - Open DevTools
```

### Symfony Profiler (Development)
```
# Access web profiler (after visiting page)
http://localhost/_profiler

# View last request
http://localhost/_profiler/latest

# Clear cache with profiler info
docker compose exec php php bin/console cache:clear --verbose
```

---

## 📦 Package Management

### Installing PHP Dependencies
```powershell
docker compose exec php composer require vendor/package
docker compose exec php composer update
```

### Installing JavaScript Libraries (Asset Mapper)
```powershell
# Edit importmap.php to add libraries
# No npm needed - uses importmap system
```

---

## 🚨 Critical Reminders

### ALWAYS Clear Cache After Backend Changes
```powershell
docker compose exec php php bin/console cache:clear
```

### NEVER Commit These Files
- `.github/copilot-instructions.md` (local only, unstage before commit)
- `documentation/database/*` (sensitive schema info)
- `var/` folder (cache and logs)
- `.env.local` (environment secrets)

### ALWAYS Update Documentation When
- Adding new pages/routes
- Modifying OrienteeringApp class significantly
- Changing database structure
- Adding major features
- Changing project scope

---

## 🎯 Performance Tips

### Frontend Optimization
- **Lazy load images** if adding many
- **Minimize DOM manipulations** (batch updates)
- **Use event delegation** for dynamic elements
- **Cache DOM queries:**
```javascript
// Bad
document.getElementById('btn').addEventListener(...)
document.getElementById('btn').textContent = '...'

// Good
const btn = document.getElementById('btn');
btn.addEventListener(...)
btn.textContent = '...'
```

### Backend Optimization
- **Minimize database queries** (use joins)
- **Cache frequently accessed data**
- **Use indexed columns** for searches
- **Paginate large result sets**

---

## 🔐 Security Reminders

### Current Limitations (Development)
- Google Maps API key exposed in templates
- No authentication on routes
- CORS not configured
- No rate limiting

### Before Production
- [ ] Move API keys to environment variables
- [ ] Implement user authentication
- [ ] Configure CORS for mobile app
- [ ] Add rate limiting to API endpoints
- [ ] Enable HTTPS (automatic with Caddy)
- [ ] Review security.yaml configuration

---

## 📞 Getting Help

### Documentation Order
1. **Project README.md** - General overview
2. **This folder (project_front/)** - Detailed frontend docs
3. **Goals folder** - Business requirements
4. **Database folder** - Schema details
5. **Symfony docs** - https://symfony.com/doc/current/
6. **Google Maps API docs** - https://developers.google.com/maps/documentation/javascript

### Common Questions

**Q: How do I add a new button to the map toolbar?**  
A: Edit `templates/map/index.html.twig` toolbar section, add button HTML, then add event listener in `app.js` `setupEventListeners()` method.

**Q: Where are courses stored?**  
A: Temporarily in `public/assets/data/courses.json`. Future: PostgreSQL database.

**Q: How do I change the default map location?**  
A: Edit `public/assets/data/map-config.json` `defaultLocation` coordinates.

**Q: Can I use npm/Webpack?**  
A: Currently using Symfony Asset Mapper. Could migrate to Webpack Encore if needed, but current system works well.

---

*This guide provides quick reference for common development tasks in e-CO Web. Refer to detailed documentation files for in-depth technical information.*
