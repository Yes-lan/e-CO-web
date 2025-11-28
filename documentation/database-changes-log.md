# Database Structure Changes Log

**Date:** November 28, 2025  
**Branch:** benji  
**Database:** MySQL 8.0

---

## Changes Applied

### 1. PostgreSQL to MySQL Migration

**Changed:** Docker Compose configuration  
**Files Modified:**
- `compose.yaml` - Changed from PostgreSQL to MySQL service
- `compose.override.yaml` - Updated port from 5432 to 3306
- `Dockerfile` - Changed `pdo_pgsql` to `pdo_mysql`

**Environment Variables:**
- `DATABASE_URL`: Changed from `postgresql://` to `mysql://`
- Database port: `5432` → `3306`

---

### 2. Fixed TIME_MUTABLE → DATETIME_MUTABLE

**Issue:** Entity fields were incorrectly using `Types::TIME_MUTABLE` (HH:MM:SS) instead of `Types::DATETIME_MUTABLE` (YYYY-MM-DD HH:MM:SS)

**Entities Fixed:**

#### Course Entity (`src/Entity/Course.php`)
- `createAt`: TIME → DATETIME
- `placementCompletedAt`: TIME → DATETIME  
- `updateAt`: TIME → DATETIME

#### Beacon Entity (`src/Entity/Beacon.php`)
- `createdAt`: TIME → DATETIME (nullable)
- `placedAt`: TIME → DATETIME (nullable)
- **Added:** `isPlaced` field (bool, NOT NULL, default: false)

#### Runner Entity (`src/Entity/Runner.php`)
- `departure`: TIME → DATETIME
- `arrival`: TIME → DATETIME

#### LogSession Entity (`src/Entity/LogSession.php`)
- `time`: TIME → DATETIME

**Database Alterations:**
```sql
ALTER TABLE course 
  MODIFY create_at DATETIME NOT NULL, 
  MODIFY placement_completed_at DATETIME NOT NULL, 
  MODIFY update_at DATETIME NOT NULL;

ALTER TABLE beacon 
  MODIFY created_at DATETIME NULL, 
  MODIFY placed_at DATETIME NULL;

ALTER TABLE runner 
  MODIFY departure DATETIME NULL, 
  MODIFY arrival DATETIME NULL;

ALTER TABLE log_session 
  MODIFY time DATETIME NULL;
```

---

### 3. Fixed BIGINT → FLOAT for Coordinates

**Issue:** Latitude/longitude were stored as `Types::BIGINT` (string integers) instead of `Types::FLOAT` (decimal numbers)

**Entities Fixed:**

#### Beacon Entity (`src/Entity/Beacon.php`)
- `latitude`: BIGINT (string) → FLOAT (float)
- `longitude`: BIGINT (string) → FLOAT (float)
- Updated getters/setters to use `float` type

#### BoundariesCourse Entity (`src/Entity/BoundariesCourse.php`)
- `latitude`: BIGINT (string) → FLOAT (float)
- `longitude`: BIGINT (string) → FLOAT (float)
- Updated getters/setters to use `float` type

#### LogSession Entity (`src/Entity/LogSession.php`)
- `latitude`: BIGINT (string, nullable) → FLOAT (float, nullable)
- `longitude`: BIGINT (string, nullable) → FLOAT (float, nullable)
- Updated getters/setters to use `float` type

**Controller Updated:**
- `src/Controller/ParcoursController.php`: Changed casts from `(string)` to `(float)` for coordinates

**Database Type:**
- MySQL stores these as `DOUBLE PRECISION` (equivalent to DOUBLE)
- Allows decimal precision for GPS coordinates (e.g., 45.827132999942066)

---

### 4. Schema Synchronization

**Command Used:**
```bash
docker compose exec php php bin/console doctrine:schema:update --force
```

**Result:** 27 queries executed to align database with entity definitions

**Validation:**
```bash
docker compose exec php php bin/console doctrine:schema:validate
```
✅ Mapping files are correct  
✅ Database schema is in sync with mapping files

---

## Current Database Schema

### Core Tables

#### `course`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `name` VARCHAR(255) NOT NULL
- `description` VARCHAR(255) NOT NULL
- `status` VARCHAR(255) NOT NULL
- `create_at` DATETIME NOT NULL
- `placement_completed_at` DATETIME NOT NULL
- `update_at` DATETIME NOT NULL

#### `beacon`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `name` VARCHAR(255) NOT NULL
- `latitude` DOUBLE PRECISION NOT NULL
- `longitude` DOUBLE PRECISION NOT NULL
- `type` VARCHAR(255) NOT NULL
- `is_placed` TINYINT(1) NOT NULL
- `placed_at` DATETIME NULL
- `created_at` DATETIME NULL
- `qr` VARCHAR(255) NOT NULL

#### `boundaries_course`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `latitude` DOUBLE PRECISION NOT NULL
- `longitude` DOUBLE PRECISION NOT NULL

#### `session`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `session_name` VARCHAR(255) NOT NULL
- `nb_runner` INT NOT NULL
- `id_course_id` INT (FK → course.id)

#### `runner`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `name` VARCHAR(255) NOT NULL
- `departure` DATETIME NOT NULL
- `arrival` DATETIME NOT NULL
- `id_session_id` INT (FK → session.id)

#### `log_session`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `type` VARCHAR(255) NOT NULL
- `time` DATETIME NOT NULL
- `latitude` DOUBLE PRECISION NULL
- `longitude` DOUBLE PRECISION NULL
- `additional_data` LONGTEXT NULL
- `position` BIGINT NOT NULL

#### `user`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `email` VARCHAR(180) NOT NULL UNIQUE
- `roles` JSON NOT NULL
- `password` VARCHAR(255) NOT NULL

---

## Key Fixes Applied

1. ✅ Method name typo: `addBoundariesCours()` → `addBoundariesCourse()`
2. ✅ Added `isPlaced` field to Beacon entity (was missing, causing NULL constraint violation)
3. ✅ All datetime fields now properly use DATETIME instead of TIME
4. ✅ All GPS coordinates now use DOUBLE PRECISION instead of BIGINT
5. ✅ Type hints updated throughout entities to match column types

---

## Test User Created

**Email:** test@test.com  
**Password:** 123 (hashed with bcrypt)

---

## Notes

- All changes maintain compatibility with the migration files from the `back` branch
- The schema is now fully synchronized and validated
- GPS coordinates can now store decimal precision as required for mapping functionality
