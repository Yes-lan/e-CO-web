# Fichiers de Données Temporaires

Ce dossier contient les données temporaires codées en JSON, faciles à modifier sans toucher au code JavaScript.

## Fichiers disponibles

### 1. `map-config.json`
Configuration générale de la carte Google Maps.

**Structure:**
```json
{
  "description": "Configuration de la carte",
  "defaultLocation": {
    "lat": 45.5017,
    "lng": -73.5673,
    "description": "Position par défaut de la carte"
  },
  "defaultZoom": 15,
  "defaultMapType": "hybrid",
  "boundarySettings": {
    "defaultPaddingKm": 0.5,
    "defaultMode": "soft",
    "minZoom": 10,
    "maxZoom": 20
  }
}
```

**Paramètres modifiables:**
- `defaultLocation.lat` / `defaultLocation.lng`: Position de départ de la carte (latitude/longitude)
- `defaultZoom`: Niveau de zoom initial (1-20)
- `defaultMapType`: Type de carte (`"roadmap"`, `"satellite"`, `"hybrid"`, `"terrain"`)
- `boundarySettings.defaultPaddingKm`: Marge par défaut autour du parcours en km
- `boundarySettings.defaultMode`: Mode de restriction (`"soft"` ou `"strict"`)
- `boundarySettings.minZoom` / `maxZoom`: Limites de zoom

### 2. `test-boundary-points.json`
Points de limite de test pour le parkour (4 points minimum recommandés).

**Structure:**
```json
{
  "description": "Points de limite de test pour le parkour",
  "points": [
    {
      "lat": 45.5017,
      "lng": -73.5673,
      "name": "Point Nord-Ouest"
    }
  ]
}
```

**Comment modifier:**
1. Ajoutez ou modifiez les points dans le tableau `points`
2. Chaque point doit avoir:
   - `lat`: Latitude (nombre décimal)
   - `lng`: Longitude (nombre décimal)
   - `name`: Nom descriptif du point
3. Minimum 3-4 points recommandés pour définir une zone

**Exemple pour ajouter un 5ème point:**
```json
{
  "lat": 45.5020,
  "lng": -73.5660,
  "name": "Point Central"
}
```

## Comment utiliser

### Modifier les données
1. Ouvrez le fichier JSON que vous voulez modifier
2. Changez les valeurs selon vos besoins
3. Sauvegardez le fichier
4. Rafraîchissez la page web (F5) - aucun cache clear nécessaire!

### Tester les modifications
- Pour les points de limite: Cliquez sur le bouton "🧪 Test Limites Parcours" sur la carte
- Pour la configuration: Rechargez la page `/map`

## Notes importantes

- **Format JSON**: Respectez la syntaxe JSON (guillemets doubles, virgules, accolades)
- **Coordonnées GPS**: Utilisez le format décimal (ex: 45.5017, pas 45°30'6.12"N)
- **Validation**: Si le fichier JSON est invalide, l'application utilisera des valeurs par défaut
- **Pas de cache Symfony**: Ces fichiers sont dans `/public/assets/`, donc accessibles directement sans cache

## Obtenir des coordonnées GPS

Pour obtenir les coordonnées d'un lieu:
1. Allez sur [Google Maps](https://maps.google.com)
2. Cliquez droit sur la carte à l'endroit désiré
3. Sélectionnez les coordonnées qui apparaissent (format: latitude, longitude)
4. Collez dans le fichier JSON

## Futur: Intégration backend

Ces fichiers sont temporaires. Une fois que le backend sera prêt, les données seront chargées depuis la base de données PostgreSQL via l'API Symfony.
