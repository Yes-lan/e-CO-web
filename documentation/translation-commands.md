# Translation Management Commands

This document explains how to manage translations in the e-CO application using Symfony's built-in translation commands.

## Overview

The application supports 3 languages:
- **French (fr)** - Default locale
- **English (en)** - Secondary language
- **Basque (eu)** - Euskara support

Translation files are located in `translations/messages.{fr,en,eu}.yaml`

## Essential Commands

### 1. Check Missing Translations

Check which translation keys are missing in a specific locale:

```powershell
# Check missing French translations
docker compose exec php php bin/console debug:translation fr --only-missing

# Check missing English translations
docker compose exec php php bin/console debug:translation en --only-missing

# Check missing Basque translations
docker compose exec php php bin/console debug:translation eu --only-missing
```

### 2. Check Unused Translations

See which translations exist in YAML files but are not used in code:

```powershell
# Check unused French translations
docker compose exec php php bin/console debug:translation fr --only-unused

# Check unused English translations
docker compose exec php php bin/console debug:translation en --only-unused

# Check unused Basque translations
docker compose exec php php bin/console debug:translation eu --only-unused
```

### 3. View All Translation Status

Get a complete overview of all translations for a locale:

```powershell
# Full report for French
docker compose exec php php bin/console debug:translation fr

# Full report for English
docker compose exec php php bin/console debug:translation en

# Full report for Basque
docker compose exec php php bin/console debug:translation eu
```

This shows:
- ✅ Translations that exist and are used
- ⚠️ Missing translations
- 🗑️ Unused translations

### 4. Extract Translations (Dry Run)

Preview what translations would be extracted from code without modifying files:

```powershell
# Preview extracted translations for French
docker compose exec php php bin/console translation:extract --dump-messages --format=yaml fr

# Preview extracted translations for English
docker compose exec php php bin/console translation:extract --dump-messages --format=yaml en

# Preview extracted translations for Basque
docker compose exec php php bin/console translation:extract --dump-messages --format=yaml eu
```

### 5. Extract and Update Translation Files

Automatically update YAML files with missing translation keys:

```powershell
# Extract and add missing keys (with empty values) for French
docker compose exec php php bin/console translation:extract --force --format=yaml --no-fill fr

# Extract and add missing keys for English
docker compose exec php php bin/console translation:extract --force --format=yaml --no-fill en

# Extract and add missing keys for Basque
docker compose exec php php bin/console translation:extract --force --format=yaml --no-fill eu
```

**Note**: `--no-fill` adds keys with empty values so you can fill them in manually.

### 6. Extract with Prefix (Easier Identification)

Add a prefix to new translations to easily identify which ones need to be filled in:

```powershell
# Add "TODO_" prefix to new French translations
docker compose exec php php bin/console translation:extract --force --format=yaml --prefix="TODO_" fr

# Add "TODO_" prefix to new English translations
docker compose exec php php bin/console translation:extract --force --format=yaml --prefix="TODO_" en

# Add "TODO_" prefix to new Basque translations
docker compose exec php php bin/console translation:extract --force --format=yaml --prefix="TODO_" eu
```

This will add entries like:
```yaml
new_key: "TODO_New translation needed"
```

### 7. Clean Unused Translations

Remove translations that are no longer used in code:

```powershell
# Remove unused translations from French file
docker compose exec php php bin/console translation:extract --force --format=yaml --clean fr

# Remove unused translations from English file
docker compose exec php php bin/console translation:extract --force --format=yaml --clean en

# Remove unused translations from Basque file
docker compose exec php php bin/console translation:extract --force --format=yaml --clean eu
```

**⚠️ Warning**: Use `--clean` carefully! It will permanently delete unused translation keys.

### 8. Sort Translations Alphabetically

Keep translation files organized by sorting them alphabetically:

```powershell
# Sort French translations
docker compose exec php php bin/console translation:extract --force --format=yaml --sort=asc fr

# Sort English translations
docker compose exec php php bin/console translation:extract --force --format=yaml --sort=asc en

# Sort Basque translations
docker compose exec php php bin/console translation:extract --force --format=yaml --sort=asc eu
```

## Common Workflows

### Adding New Translation Keys

1. Add the translation token to your Twig template:
   ```twig
   {{ 'my_new_key'|trans }}
   ```

2. Extract to see what's missing:
   ```powershell
   docker compose exec php php bin/console debug:translation fr --only-missing
   ```

3. Add the translation manually to all 3 YAML files:
   ```yaml
   # messages.fr.yaml
   my_new_key: "Ma nouvelle traduction"
   
   # messages.en.yaml
   my_new_key: "My new translation"
   
   # messages.eu.yaml
   my_new_key: "Nire itzulpen berria"
   ```

4. Clear cache:
   ```powershell
   docker compose exec php php bin/console cache:clear
   ```

### Syncing Translations Across Locales

1. Check English for missing keys that exist in French:
   ```powershell
   docker compose exec php php bin/console debug:translation en
   ```

2. Manually add missing translations to `messages.en.yaml`

3. Repeat for Basque:
   ```powershell
   docker compose exec php php bin/console debug:translation eu
   ```

### Cleaning Up Old Translations

1. Find unused translations:
   ```powershell
   docker compose exec php php bin/console debug:translation fr --only-unused
   ```

2. Review the list and verify they're truly unused

3. Remove them:
   ```powershell
   docker compose exec php php bin/console translation:extract --force --format=yaml --clean fr
   docker compose exec php php bin/console translation:extract --force --format=yaml --clean en
   docker compose exec php php bin/console translation:extract --force --format=yaml --clean eu
   ```

## Translation File Structure

Current translation structure:

```yaml
nav:
    courses: "Sessions"
    parcours: "Courses"
    
header:
    logout: "Logout"
    settings: "Settings"
    
courses:
    title: "Sessions"
    new_course: "New Session"
    # ... more course translations
    
    modal:
        create_title: "Create a New Session"
        # ... modal translations
    
    status:
        active: "Active"
    
    info:
        runners: "runner(s)"
    
    messages:
        created: "Session created successfully!"
        # ... message translations

parcours:
    title: "Courses"
    # ... parcours translations

login:
    title: "Login"
    # ... login translations

common:
    save: "Save"
    cancel: "Cancel"
    # ... common translations
```

## Best Practices

1. **Always use translation tokens** instead of hardcoded text in templates
2. **Use descriptive keys** with namespaces: `courses.modal.create_title`
3. **Keep translations synchronized** across all 3 locales
4. **Run cache clear** after modifying translation files
5. **Use parameters** for dynamic content: `'message.hello'|trans({'%name%': user.name})`
6. **Check for missing translations** before deploying
7. **Document translation structure** for new developers

## Troubleshooting

### Translation Not Appearing

1. Clear cache:
   ```powershell
   docker compose exec php php bin/console cache:clear
   ```

2. Check if the key exists:
   ```powershell
   docker compose exec php php bin/console debug:translation fr
   ```

3. Verify correct locale is active in the URL: `/fr/`, `/en/`, or `/eu/`

### New Keys Not Detected

The translation extractor scans:
- Twig templates (`templates/**/*.twig`)
- PHP files with `trans()` calls
- JavaScript files with translation functions

If keys aren't detected, ensure they use standard Symfony syntax:
```twig
{{ 'key'|trans }}
{{ 'key'|trans({'%param%': value}) }}
```

### "Unused" Warning for Active Translations

Some translations may show as "unused" if:
- They're used in JavaScript (translations.js)
- They're in templates not loaded during scan
- They're dynamically generated

These warnings can usually be ignored if you know the translations are actually used.

## Related Files

- `translations/messages.fr.yaml` - French translations
- `translations/messages.en.yaml` - English translations
- `translations/messages.eu.yaml` - Basque translations
- `config/packages/translation.yaml` - Translation configuration
- `src/EventSubscriber/LocaleSubscriber.php` - Locale switching logic
- `public/assets/js/translations.js` - Client-side translations
- `templates/base.html.twig` - Language switcher in header

## Additional Resources

- [Symfony Translation Documentation](https://symfony.com/doc/current/translation.html)
- [Translation Best Practices](https://symfony.com/doc/current/best_practices.html#use-the-yaml-format-for-translation-files)
- [ICU Message Format](https://symfony.com/doc/current/translation/message_format.html) for pluralization
