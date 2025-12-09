# e-CO Web - Guide d'Installation

## 📋 Prérequis

- Docker Desktop installé et en cours d'exécution
- Git
- Node.js (v18 ou supérieur) et npm

## 🚀 Installation Complète (Nouveau Développeur)

### 1. Cloner le projet

```bash
git clone https://github.com/Yes-lan/e-CO-web.git
cd e-CO-web
git checkout benji
```

### 2. Installer les dépendances JavaScript

**IMPORTANT : Cette étape est OBLIGATOIRE !**

```bash
npm install
```

### 3. Construire les assets

```bash
npm run build
```

### 4. Démarrer Docker

```bash
docker compose build --pull --no-cache
docker compose up --wait
```

### 5. Installer les dépendances PHP (si nécessaire)

```bash
docker compose exec php composer install
```

### 6. Générer les clés JWT (OBLIGATOIRE)

**IMPORTANT : Sans ces clés, l'authentification ne fonctionnera pas !**

```bash
docker compose exec php php bin/console lexik:jwt:generate-keypair
```

Cette commande va créer :
- `config/jwt/private.pem`
- `config/jwt/public.pem`

### 7. Créer la base de données

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

### 8. Charger les données de test (optionnel)

Si vous voulez des données de test :

```bash
docker compose exec php php bin/console doctrine:fixtures:load
```

OU importer le fichier SQL de seed :

```bash
# Depuis le conteneur database
docker compose exec database psql -U app -d app -f /path/to/database_seed.sql
```

### 9. Vérifier que tout fonctionne

Ouvrir dans le navigateur :
- **Application principale :** http://localhost/
- **Adminer (base de données) :** http://localhost:8080
- **Mailpit (emails) :** http://localhost:8025

**Identifiants de test :**
- Email : `test@test.com` ou `test@test.fr`
- Mot de passe : `password`

## 🔧 Problèmes Courants

### ❌ Erreur : "Failed to resolve module specifier @symfony/stimulus-bridge"

**Cause :** Les dépendances JavaScript ne sont pas installées.

**Solution :**
```bash
npm install
npm run build
docker compose exec php php bin/console cache:clear
```

### ❌ Erreur : "GET /auth/token 500 (Internal Server Error)"

**Cause principale :** Les clés JWT n'existent pas !

**Solution IMMÉDIATE :**
```bash
docker compose exec php php bin/console lexik:jwt:generate-keypair
docker compose exec php php bin/console cache:clear
```

**Vérifier que les clés sont créées :**
```bash
ls config/jwt/
# Devrait afficher : private.pem  public.pem
```

**Si le problème persiste :**
```bash
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console cache:clear
```

### ❌ Erreur : "GET /api/parcours 401 (Unauthorized)"

**Cause :** Problème d'authentification, JWT tokens non configurés ou utilisateur inexistant.

**Solution :**
1. Vérifier que l'utilisateur existe :
```bash
docker compose exec php php bin/console doctrine:query:sql "SELECT * FROM \"user\""
```

2. Si aucun utilisateur, créer un utilisateur de test :
```bash
docker compose exec php php bin/console security:hash-password
# Entrer : password
# Copier le hash généré
```

3. Créer l'utilisateur manuellement :
```bash
docker compose exec database psql -U app -d app
INSERT INTO "user" (email, roles, password) VALUES ('test@test.com', '["ROLE_USER"]', 'HASH_COPIÉ_CI-DESSUS');
\q
```

### ❌ Les containers ne démarrent pas

**Solution :**
```bash
docker compose down --volumes --remove-orphans
docker compose build --pull --no-cache
docker compose up --wait
```

## 🔄 Commandes Quotidiennes

### Démarrer le projet
```bash
docker compose up --wait
```

### Arrêter le projet
```bash
docker compose down
```

### Voir les logs
```bash
docker compose logs php
docker compose logs database
docker compose logs -f  # Suivre tous les logs
```

### Accéder au conteneur PHP
```bash
docker compose exec php sh
```

### Vider le cache Symfony
```bash
docker compose exec php php bin/console cache:clear
```

### Rebuild des assets JavaScript
```bash
npm run build
# OU pour le mode watch (auto-rebuild)
npm run watch
```

## 📦 Structure des Assets

- **Static assets** (CSS/JS custom) : `public/assets/`
  - Pas besoin de rebuild, juste rafraîchir le navigateur (Ctrl+Shift+R)
  
- **Vendor assets** (Stimulus, Turbo) : `assets/` → compilés dans `public/build/`
  - Nécessite `npm run build` après modification

## 🗃️ Base de Données

**Connection depuis l'extérieur :**
- Host : `localhost`
- Port : `5432` (ou vérifier dans `compose.yaml`)
- Database : `app`
- Username : `app`
- Password : `!ChangeMe!`

**Via Adminer (navigateur) :**
- URL : http://localhost:8080
- Server : `database`
- Username : `app`
- Password : `!ChangeMe!`
- Database : `app`

## 📧 Emails (Développement)

Tous les emails sont capturés par Mailpit :
- URL : http://localhost:8025
- Les emails ne sont PAS envoyés réellement, ils sont interceptés localement

## 🌍 Traductions

Le projet supporte 3 langues :
- Français (FR) - par défaut
- Anglais (EN)
- Basque (EU)

Fichiers de traduction : `translations/messages.{fr,en,eu}.yaml`

## ✅ Checklist Installation Réussie

- [ ] `npm install` exécuté sans erreur
- [ ] `npm run build` terminé avec succès
- [ ] `docker compose up --wait` démarre tous les containers
- [ ] **`lexik:jwt:generate-keypair` exécuté et clés créées dans `config/jwt/`**
- [ ] Migrations de base de données exécutées
- [ ] http://localhost/ affiche la page d'accueil
- [ ] http://localhost/login permet de se connecter avec test@test.com / password
- [ ] **Aucune erreur "GET /auth/token 500" dans la console**
- [ ] Aucune erreur de console JavaScript concernant "@symfony/stimulus-bridge"
- [ ] Les pages `/parcours` et `/courses` sont accessibles après login

## 🆘 Aide

Si les problèmes persistent :

1. **Vérifier les versions :**
```bash
node --version  # Devrait être v18 ou supérieur
npm --version
docker --version
docker compose version
```

2. **Tout réinitialiser :**
```bash
# Arrêter et nettoyer Docker
docker compose down --volumes --remove-orphans
rm -rf var/cache/*
rm -rf public/build/*

# Réinstaller tout
npm install
npm run build
docker compose build --pull --no-cache
docker compose up --wait
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console cache:clear
```

3. **Vérifier les logs :**
```bash
docker compose logs php | tail -50
```

## 👥 Contact

Pour toute question, contacter l'équipe de développement sur le projet GitHub.
