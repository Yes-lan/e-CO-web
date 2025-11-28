# Frontend Documentation - e-CO Web

**Purpose:** Comprehensive documentation for the e-CO Web frontend application to help new developers understand the project structure, architecture, and development practices.

**Last Updated:** November 21, 2025

---

## 📚 Documentation Structure

This folder contains detailed documentation for the frontend (PC/Desktop teacher interface) of the e-CO Web orienteering course management system.

### Documentation Files

| File | Purpose | Update Frequency |
|------|---------|------------------|
| **00-overview.md** | Project overview, architecture summary, quick start | When project scope changes |
| **01-architecture.md** | Technical stack details, Docker setup, infrastructure | When architecture changes |
| **02-pages-routes.md** | All pages, routes, API endpoints, navigation flow | When routes/pages added/modified |
| **03-javascript-core.md** | OrienteeringApp class, methods, state management | When JavaScript significantly modified |
| **04-map-system.md** | Google Maps integration, markers, geolocation | When map features change |
| **05-styling.md** | CSS architecture and design system | When styles/components change |
| **06-data-flow.md** | Data management and backend integration | When APIs/data structures change |
| **07-turbo-navigation.md** | Turbo Drive handling and SPA behavior | When navigation logic changes |
| **08-course-workflow.md** | Complete course creation/management workflow | When workflows change |
| **09-development-guide.md** | Quick reference, commands, troubleshooting | When workflows change |

---

## 🎯 Who Should Read This Documentation?

### New Frontend Developers
**Read in order:**
1. `00-overview.md` - Understand project goals and structure
2. `01-architecture.md` - Learn the technical stack
3. `02-pages-routes.md` - Explore all pages and routes
4. `03-javascript-core.md` - Master the OrienteeringApp class
5. `09-development-guide.md` - Start coding with quick reference

### Backend Developers Integrating APIs
**Focus on:**
- `02-pages-routes.md` - API endpoint contracts
- `01-architecture.md` - Request lifecycle and data flow
- `00-overview.md` - Mobile integration context

### Project Managers / Product Owners
**Focus on:**
- `00-overview.md` - Project purpose and features
- `02-pages-routes.md` - User-facing pages and workflows

### AI Agents (Copilot, etc.)
**Reference for:**
- Understanding current project state
- Making consistent code changes
- Maintaining documentation accuracy
- Cross-team coordination (frontend/backend)

---

## 🔄 Documentation Maintenance

### Automatic Update Triggers

The `.github/copilot-instructions.md` file contains rules for when to update this documentation:

**Immediate Updates Required:**
- ✅ New page/route added → Update `02-pages-routes.md`
- ✅ OrienteeringApp method modified → Update `03-javascript-core.md`
- ✅ Architecture change (Docker, Symfony, etc.) → Update `01-architecture.md`
- ✅ Development workflow change → Update `09-development-guide.md`
- ✅ Project scope change → Update `00-overview.md`

**Why This Matters:**
- Backend team may make changes affecting frontend
- Documentation must reflect reality for new developers
- AI agents rely on accurate documentation
- Prevents knowledge silos and confusion

---

## 📖 Documentation Philosophy

### Principles

1. **Accuracy Over Quantity**
   - Better to have accurate documentation than extensive but outdated docs
   - Update immediately when code changes

2. **Code Examples Must Work**
   - All code snippets should be copy-pasteable
   - Match actual implementation in codebase
   - Include file paths for context

3. **Clarity for New Developers**
   - Assume reader knows general web development
   - Explain e-CO-specific patterns and decisions
   - Provide context for non-obvious choices

4. **Living Documentation**
   - Docs evolve with codebase
   - Date stamps show currency
   - Clear ownership and maintenance

---

## 🔗 Related Documentation

### In This Repository
- `documentation/database/` - Database schema and structure
- `documentation/goals/` - Business requirements (courses, students, waypoints)
- `.github/copilot-instructions.md` - AI agent instructions
- Root `README.md` - General project overview

### External Resources
- [Symfony Documentation](https://symfony.com/doc/current/)
- [Google Maps JavaScript API](https://developers.google.com/maps/documentation/javascript)
- [Turbo Documentation](https://turbo.hotwired.dev/)
- [Docker Compose](https://docs.docker.com/compose/)

---

## ✏️ Contributing to Documentation

### Making Changes

1. **Identify affected files**
   - What part of the system changed?
   - Which documentation files cover that area?

2. **Update content**
   - Modify relevant sections
   - Add new sections if needed
   - Update code examples to match reality

3. **Update metadata**
   - Change "Last Updated" date at file top
   - Ensure file is saved

4. **Verify accuracy**
   - Cross-check with actual code
   - Test code examples if possible

### Style Guidelines

**Markdown Formatting:**
```markdown
# Top-level heading (file title)
## Section heading
### Subsection heading

**Bold** for emphasis
`inline code` for filenames, variables, short code
```language for code blocks

- Bullet lists for items
1. Numbered lists for steps
```

**Code Examples:**
- Include file paths as comments
- Use actual working code from the project
- Explain what the code does
- Show both good and bad examples when helpful

**File Organization:**
- Clear table of contents at the top
- Logical section progression
- Cross-references to related files
- Consistent formatting throughout

---

## 🚀 Quick Start for Documentation Users

### I'm a New Developer, Where Do I Start?
1. Read `00-overview.md` (10 minutes)
2. Follow `09-development-guide.md` Quick Start section (5 minutes)
3. Start coding with `09-development-guide.md` as reference
4. Dive into `02-pages-routes.md` and `03-javascript-core.md` as needed

### I Need to Understand a Specific Feature
1. Check `02-pages-routes.md` for page/route information
2. Check `03-javascript-core.md` for JavaScript implementation
3. Check actual code files (documentation includes file paths)

### I Need to Fix a Bug
1. Use `09-development-guide.md` Troubleshooting section
2. Check Console/Network tabs as described
3. Reference relevant technical docs for deeper understanding

### I Need to Add a New Feature
1. Review existing patterns in `02-pages-routes.md` and `03-javascript-core.md`
2. Follow code style guidelines in `09-development-guide.md`
3. **Update documentation** after implementing feature

---

## 📊 Documentation Quality Metrics

### How to Know Docs Are Good
- ✅ New developer can set up environment from scratch
- ✅ Code examples work without modification
- ✅ File paths in docs match actual repository
- ✅ "Last Updated" dates are recent
- ✅ Backend team can understand API contracts
- ✅ Questions answered without asking team members

### How to Know Docs Need Update
- ❌ Code examples don't match codebase
- ❌ File paths incorrect or files moved
- ❌ Features documented that don't exist
- ❌ Developers asking questions answered in docs
- ❌ Confusion about project scope or architecture

---

## 🎓 Learning Path Recommendations

### For Frontend Developers (1-2 hours)
```
00-overview.md (20 min)
  ↓
01-architecture.md (30 min)
  ↓
02-pages-routes.md (30 min)
  ↓
03-javascript-core.md (40 min)
  ↓
09-development-guide.md (bookmark for reference)
  ↓
Start coding!
```

### For Backend Developers (30-45 minutes)
```
00-overview.md (15 min)
  ↓
02-pages-routes.md - Focus on API sections (20 min)
  ↓
01-architecture.md - Focus on request lifecycle (15 min)
  ↓
Start integrating!
```

### For Quick Bug Fix (10 minutes)
```
09-development-guide.md - Troubleshooting section
  ↓
Relevant technical doc as needed
  ↓
Fix and test!
```

---

## 📞 Documentation Support

### Have Questions About Documentation?
- Check if answer is in another doc file (use search)
- Look at actual code (documentation includes file paths)
- Ask team members and **update docs with the answer**

### Found Documentation Error?
1. Note what's incorrect
2. Check actual code for truth
3. Update documentation immediately
4. Consider if related sections need updates

### Documentation Feels Incomplete?
1. Identify missing information
2. Add new section or file as needed
3. Link from relevant existing sections
4. Update this README if structure changed

---

## 🏆 Documentation Best Practices

### DO:
✅ Update immediately when code changes  
✅ Include working code examples  
✅ Add file paths for context  
✅ Explain *why* not just *what*  
✅ Cross-reference related documentation  
✅ Use consistent formatting  
✅ Update "Last Updated" dates  

### DON'T:
❌ Leave outdated information (remove or update it)  
❌ Use placeholder text ("TODO: Add later")  
❌ Copy-paste without verifying accuracy  
❌ Assume readers have context you have  
❌ Forget to update docs after code changes  
❌ Use vague descriptions ("the main file", "the thing")  

---

*This documentation system is designed to keep knowledge accessible and current as the e-CO Web project evolves. Maintain it diligently and it will serve the team well.*
