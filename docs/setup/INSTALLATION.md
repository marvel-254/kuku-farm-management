# 🛠️ Setup & Installation Guide

This guide explains everything needed to make the KUKU Farm Management System work from scratch.

---

## Prerequisites

### For Desktop Mode (Recommended)

| Requirement | Command / Source |
|-------------|------------------|
| Node.js v16+ | `node --version` (https://nodejs.org/) |
| npm (comes with Node) | `npm --version` |
| OS: Windows/macOS/Linux | Any modern desktop OS |
| RAM: 2GB minimum | Check system settings |
| Storage: 100MB free | Check disk space |

### For Web Mode

| Requirement | Command / Source |
|-------------|------------------|
| PHP 8.0+ | `php --version` |
| SQLite3 extension | `php -m \| grep sqlite` |
| Web server (optional) | Apache / Nginx / PHP built-in |

---

## Step 1: Clone / Download Project

The project is already in `/home/marvel/Downloads/kuku/`. If you need to move it:

```bash
cd /home/marvel/Downloads/kuku/
```

---

## Step 2: Install Dependencies (Desktop)

```bash
npm install
```

This installs:
- `express` (web server framework)
- `electron` (desktop runtime)
- `electron-builder` (packaging)
- `bcryptjs` (password hashing)
- `pg` (PostgreSQL client)
- `express-session` (session management)
- `connect-pg-simple` (session store)
- `session-file-store` (file-based sessions)

---

## Step 3: Configure Environment

Edit `.env` (or copy `.env.example` to `.env`):

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```
PORT=3000
NODE_ENV=development
DATABASE_URL=postgresql://kuku_farm:kuku_farm_2024@localhost:5432/kuku_farm
SESSION_SECRET=kuku-farm-local-dev-secret-change-in-production
WEATHER_LAT=-1.2921
WEATHER_LON=36.8219
```

> **Important**: Change `SESSION_SECRET` in production to a long, random string.

---

## Step 4: Initialize Database

### Option A: SQLite (Local / Development)

```bash
php database/create_db.php
```

This creates `database/kuku_farm.sqlite` and sets up all tables (`users`, `chickens`, `inventory`, `sales`, etc.).

### Option B: PostgreSQL (Production / Web)

```bash
node db/migrate.js
```

This runs migrations on the database defined in `DATABASE_URL`.

---

## Step 5: Verify Database Connection

Check that the server connects successfully:

```bash
node server.js
```

You should see:

```
✅ Connected to PostgreSQL
```

If using SQLite, the server will use the local SQLite file automatically.

---

## Step 6: Run the Application

### Desktop Mode

```bash
npm start
```

This launches the Electron desktop application with the main process (`electron-main.js`).

To build for distribution:

```bash
npm run build          # Current platform
npm run build-win     # Windows (.exe / NSIS)
npm run build-mac     # macOS (.dmg)
npm run build-linux   # Linux (.AppImage)
```

### Web Mode

```bash
# Using PHP built-in server
php -S localhost:4567 -t public/

# Or using the provided script
./start.sh
```

Access the web app at:
- `http://localhost:4567` (default port)
- Default login: `admin` / `admin123`

---

## Step 7: Verify Everything Works

1. **Login**: Go to the login page and sign in with default credentials.
2. **Dashboard**: Confirm the dashboard loads with charts.
3. **Add Data**: Try adding a chicken batch or inventory item.
4. **Audit**: Check that actions are logged (`database/` or PostgreSQL `audit_logs` table).
5. **Weather**: Confirm weather data loads for the coordinates in `.env`.

---

## Common Setup Errors and Fixes

| Error | Fix |
|-------|-----|
| `Module not found` | Run `npm install` again |
| `Database connection failed` | Check `.env` `DATABASE_URL` and that PostgreSQL is running |
| `Port already in use` | Change `PORT` in `.env` or kill the process using that port |
| `sqlite3 is not enabled` | Install PHP SQLite3 extension (`php-sqlite3`) |
| `Permission denied (start.sh)` | Run `chmod +x start.sh` |

---

## Quick Reference — Commands

```bash
# Setup
npm install
cp .env.example .env
php database/create_db.php

# Run desktop
npm start

# Build desktop
npm run build

# Run web server
./start.sh

# Migrate PostgreSQL DB
node db/migrate.js

# Test database connection
node server.js
```
