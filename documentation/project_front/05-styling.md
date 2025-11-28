# e-CO Web - CSS Architecture and Styling

**Last Updated:** November 21, 2025

---

## 🎨 CSS Structure Overview

The e-CO Web application uses a **single unified stylesheet** with component-based organization. All styles are in `public/assets/css/style.css` (~904 lines).

---

## 📁 File Organization

### Single Stylesheet Approach

**File:** `public/assets/css/style.css`

**Why single file?**
- ✅ Simple asset management (no bundler needed)
- ✅ Fewer HTTP requests
- ✅ Easy to find styles (no hunting across files)
- ✅ Works well with Symfony Asset Mapper
- ✅ Project is small enough (< 1000 lines)

**Section Organization:**
```css
/* Lines 1-20: Global resets and base styles */
/* Lines 21-40: Layout containers (body, .container, .header) */
/* Lines 41-120: Toolbar and navigation */
/* Lines 121-250: Button system */
/* Lines 251-450: Map viewer components */
/* Lines 451-650: Course creation forms */
/* Lines 651-750: Modal dialogs */
/* Lines 751-850: Homepage layout */
/* Lines 851-904: Responsive design */
```

---

## 🎨 Design System

### Color Palette

```css
/* Primary Colors */
--primary-green: #2c5530;      /* Headers, primary buttons, branding */
--accent-green: #4a7c59;       /* Gradients, highlights */
--dark-green: #1e3a21;         /* Hover states */

/* Semantic Colors */
--success-green: #28a745;      /* Success messages, start markers */
--info-blue: #17a2b8;          /* Info buttons, control markers */
--warning-yellow: #ffc107;     /* Warnings, caution states */
--danger-red: #dc3545;         /* Errors, finish markers */

/* Neutral Colors */
--gray-light: #f5f5f5;         /* Background */
--gray-medium: #ddd;           /* Borders */
--gray-dark: #6c757d;          /* Secondary text */
--white: #fff;                 /* Text on colored backgrounds */
--black: #333;                 /* Body text */

/* Map-Specific Colors */
--boundary-stroke: #2c5530;    /* Course boundary border */
--boundary-fill: #4a7c59;      /* Course boundary fill */
--course-path: #FF6B00;        /* Orange course line */
--teacher-location: #4285F4;   /* Google blue GPS marker */
```

### Typography

```css
/* Font Stack */
font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

/* Font Sizes */
--font-size-small: 0.9rem;     /* 14.4px - Help text, labels */
--font-size-base: 1rem;        /* 16px - Body text */
--font-size-large: 1.1rem;     /* 17.6px - Subheadings */
--font-size-xlarge: 1.5rem;    /* 24px - Section headings */
--font-size-xxlarge: 1.8rem;   /* 28.8px - Page headings */
--font-size-hero: 2rem;        /* 32px - Main headers */

/* Font Weights */
--font-weight-normal: 400;
--font-weight-bold: 700;
```

### Spacing System

```css
/* Spacing Scale (rem-based) */
--space-xs: 0.25rem;    /* 4px */
--space-sm: 0.5rem;     /* 8px */
--space-md: 1rem;       /* 16px */
--space-lg: 1.5rem;     /* 24px */
--space-xl: 2rem;       /* 32px */

/* Common Applications */
padding: var(--space-md);
margin-bottom: var(--space-lg);
gap: var(--space-sm);
```

### Border Radius

```css
--radius-small: 4px;    /* Buttons, inputs */
--radius-medium: 8px;   /* Cards, modals */
--radius-large: 12px;   /* Hero sections */
```

### Shadows

```css
--shadow-light: 0 1px 3px rgba(0,0,0,0.1);
--shadow-medium: 0 2px 4px rgba(0,0,0,0.1);
--shadow-heavy: 0 4px 6px rgba(0,0,0,0.1);
```

---

## 🧱 Component Styles

### Global Resets

```css
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f5f5;
    color: #333;
}
```

### Container System

```css
.container {
    max-width: 100%;
    margin: 0 auto;
    background: white;
    min-height: 100vh;
}

/* Constrained width for forms */
.course-creation-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}
```

---

## 🎯 Button System

### Base Button

```css
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.btn:active {
    transform: translateY(0);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
```

### Button Variants

```css
/* Primary - Main actions */
.btn-primary {
    background: #2c5530;
    color: white;
}
.btn-primary:hover {
    background: #1e3a21;
}

/* Secondary - Alternative actions */
.btn-secondary {
    background: #6c757d;
    color: white;
}
.btn-secondary:hover {
    background: #5a6268;
}

/* Success - Positive actions */
.btn-success {
    background: #28a745;
    color: white;
}
.btn-success:hover {
    background: #218838;
}

/* Info - Informational actions */
.btn-info {
    background: #17a2b8;
    color: white;
}
.btn-info:hover {
    background: #138496;
}

/* Warning - Caution actions */
.btn-warning {
    background: #ffc107;
    color: #333;
}
.btn-warning:hover {
    background: #e0a800;
}

/* Danger - Destructive actions */
.btn-danger {
    background: #dc3545;
    color: white;
}
.btn-danger:hover {
    background: #c82333;
}
```

### Button Sizes

```css
.btn-small {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.btn-large {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}
```

---

## 🗺️ Map Components

### Map Container

```css
.map-container {
    flex: 1;
    height: 100%;
    min-height: 500px;
    position: relative;
}

#map {
    width: 100%;
    height: 100%;
    border: 2px solid #ddd;
    border-radius: 8px;
}
```

### Sidebar

```css
.sidebar {
    width: 300px;
    background: #fff;
    border-right: 1px solid #ddd;
    overflow-y: auto;
    padding: 1rem;
}

/* Control Points List */
.control-points-list {
    margin-bottom: 2rem;
}

.control-points-list h2 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #2c5530;
}

.control-point-item {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 4px solid #007bff;
}

.control-point-item.start {
    border-left-color: #28a745;
}

.control-point-item.finish {
    border-left-color: #dc3545;
}

.control-point-item strong {
    display: block;
    color: #2c5530;
    margin-bottom: 0.25rem;
}

.control-point-item p {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
}
```

### Coordinates Display

```css
.coordinates-display {
    position: absolute;
    bottom: 20px;
    left: 20px;
    background: rgba(255, 255, 255, 0.95);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    font-family: monospace;
    font-size: 0.9rem;
    z-index: 1000;
}

.coordinates-display.hidden {
    display: none;
}

.coordinates-display h3 {
    font-size: 0.9rem;
    margin: 0 0 0.5rem 0;
    color: #2c5530;
}

.coordinates-display p {
    margin: 0.25rem 0;
    color: #333;
}
```

---

## 📝 Form Components

### Form Sections

```css
.form-section {
    background: #fff;
    padding: 2rem;
    margin-bottom: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-section h2 {
    font-size: 1.5rem;
    color: #2c5530;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #4a7c59;
}
```

### Form Groups

```css
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #2c5530;
    box-shadow: 0 0 0 3px rgba(44, 85, 48, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}
```

### Info Boxes

```css
.info-box {
    background: #e7f3ff;
    border-left: 4px solid #17a2b8;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
}

.info-box p {
    margin: 0;
    color: #0c5460;
}

.info-box ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.info-box li {
    margin: 0.25rem 0;
}
```

### Waypoint Forms

```css
.waypoint-form {
    background: #f8f9fa;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 8px;
    border: 2px solid #ddd;
}

.waypoint-form h3 {
    color: #007bff;
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.waypoint-form.start h3 {
    color: #28a745;
}

.waypoint-form.finish h3 {
    color: #dc3545;
}
```

---

## 🎭 Modal Dialogs

### Modal Structure

```css
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s ease;
}

.modal.active {
    display: flex;
    justify-content: center;
    align-items: center;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
```

### Modal Content

```css
.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #ddd;
}

.modal-header h2 {
    margin: 0;
    color: #2c5530;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #dc3545;
}
```

---

## 🏠 Homepage Styles

### Hero Section

```css
.hero {
    text-align: center;
    padding: 3rem 2rem;
    background: linear-gradient(135deg, #f5f5f5 0%, #e8f5e9 100%);
    border-radius: 12px;
    margin: 2rem;
}

.hero h2 {
    font-size: 2rem;
    color: #2c5530;
    margin-bottom: 1rem;
}

.hero p {
    font-size: 1.2rem;
    color: #666;
    margin-bottom: 2rem;
}
```

### CTA Section

```css
.cta-section {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.cta-section .btn-large {
    min-width: 200px;
}
```

---

## 📱 Responsive Design

### Desktop-First Approach

```css
/* Base styles target desktop (1280px+) */

/* Tablet adjustments (768px - 1279px) */
@media (max-width: 1280px) {
    .sidebar {
        width: 250px;
    }
    
    .form-section {
        padding: 1.5rem;
    }
}

/* Mobile adjustments (< 768px) */
@media (max-width: 768px) {
    /* Stack layout */
    .map-content {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        max-height: 300px;
        border-right: none;
        border-bottom: 1px solid #ddd;
    }
    
    .map-container {
        min-height: 400px;
    }
    
    /* Adjust toolbar */
    .toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-section {
        width: 100%;
        justify-content: center;
    }
    
    /* Button adjustments */
    .btn {
        width: 100%;
        text-align: center;
    }
    
    .cta-section {
        flex-direction: column;
    }
    
    .cta-section .btn-large {
        width: 100%;
    }
    
    /* Form adjustments */
    .form-section {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Modal adjustments */
    .modal-content {
        width: 95%;
        padding: 1rem;
    }
}
```

---

## 🎨 Utility Classes

### Spacing Utilities

```css
.mt-1 { margin-top: 0.5rem; }
.mt-2 { margin-top: 1rem; }
.mt-3 { margin-top: 1.5rem; }

.mb-1 { margin-bottom: 0.5rem; }
.mb-2 { margin-bottom: 1rem; }
.mb-3 { margin-bottom: 1.5rem; }

.p-1 { padding: 0.5rem; }
.p-2 { padding: 1rem; }
.p-3 { padding: 1.5rem; }
```

### Display Utilities

```css
.hidden {
    display: none !important;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.flex {
    display: flex;
}

.flex-column {
    flex-direction: column;
}

.gap-1 { gap: 0.5rem; }
.gap-2 { gap: 1rem; }
.gap-3 { gap: 1.5rem; }
```

### Status Badges

```css
.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.archived {
    background: #f8d7da;
    color: #721c24;
}

.status-badge.draft {
    background: #fff3cd;
    color: #856404;
}
```

---

## 🔍 CSS Best Practices

### Naming Conventions

```css
/* Component-based naming */
.component-name { }
.component-name__element { }
.component-name--modifier { }

/* Examples */
.course-card { }
.course-card__header { }
.course-card__body { }
.course-card--highlighted { }
```

### Avoid Over-Specificity

```css
/* ❌ Bad - Too specific */
div.container .sidebar ul li.course-item a.btn {
    color: blue;
}

/* ✅ Good - Appropriate specificity */
.course-item .btn {
    color: blue;
}
```

### Use Variables for Consistency

```css
/* ❌ Bad - Hardcoded values */
.button {
    background: #2c5530;
}
.header {
    background: #2c5530;
}

/* ✅ Good - Use consistent values */
.button {
    background: var(--primary-green);
}
.header {
    background: var(--primary-green);
}
```

---

## 🚀 Performance Considerations

### CSS Optimization

1. **Single file** reduces HTTP requests
2. **No preprocessor** (SASS/LESS) - vanilla CSS
3. **Minimal specificity** - faster selector matching
4. **No unused styles** - keep CSS lean

### Loading Strategy

```html
<!-- Render-blocking (intentional for core styles) -->
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
```

---

## 🎯 Future CSS Enhancements

### Planned Improvements

1. **CSS Custom Properties**
   - Convert colors/spacing to CSS variables
   - Enable theme switching (light/dark mode)

2. **Component Isolation**
   - Consider splitting into separate files if grows > 2000 lines
   - Maintain current simplicity for now

3. **Animation Library**
   - Reusable animation classes
   - Micro-interactions for better UX

4. **Print Styles**
   - Optimize for course printouts
   - QR code printing support

---

*This styling system provides a solid, maintainable foundation for the e-CO Web interface with clear organization and consistent design patterns.*
