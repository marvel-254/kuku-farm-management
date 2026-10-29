# 🛠️ Troubleshooting Guide

Common problems and how to fix them when running KUKU Farm Management.

---

## Desktop Application Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| `Module not found: Can't resolve 'pg'` | Missing PostgreSQL dependency | Run `npm install` |
| `Cannot find module 'electron'` | Dependencies not installed | Run `npm install` |
| `Database connection failed` | `.env` `DATABASE_URL` wrong | Check `.env` has correct PostgreSQL URL |
| `Port 3000 already in use` | Another process on that port | Change `PORT` in `.env` or kill the process |
| `Electron window won't open` | Node.js version incompatible | Use Node.js v16+ |
| `npm run build fails` | Electron-builder config issue | Check `package.json` build config and assets |

---

## Web Application Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| `404: Page not found` | Wrong URL or file missing | Check `public/` folder structure |
| `SQLite3 not enabled` | PHP missing SQLite extension | Install `php-sqlite3` or use PostgreSQL |
| `Session cookie not set` | `NODE_ENV` not configured | Set `NODE_ENV` in `.env` |
| `Database connection error` | PostgreSQL not running | Start PostgreSQL service |
| `login.php returns error` | Wrong credentials | Default: `admin` / `admin123` |
| `Weather data not loading` | Wrong lat/lon in `.env` | Check `WEATHER_LAT=-1.2921`, `WEATHER_LON=36.8219` |

---

## Database Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| `SQLITE_FILE is locked` | Another process has DB open | Close other apps, then restart |
| `Table users not found` | Database not initialized | Run `php database/create_db.php` |
| `Migrations failed` | PostgreSQL connection wrong | Fix `.env DATABASE_URL` |
| `Foreign key constraint errors` | Inserting data in wrong order | Follow migration order or use transactions |

---

## Environment & Configuration Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| `SESSION_SECRET` error | Using default secret | Generate a new, random secret |
| `Weather coordinates wrong` | Default lat/lon in `.env` | Update to your farm's coordinates |
| `Port 4567 conflict` | Another service on 4567 | Change `PORT` in `.env`, restart server |
| `Dependencies outdated` | `npm install` hasn't run | Run `npm install` |

---

## Debugging Commands

```bash
# Check Node.js version
node --version

# Check PHP version
php --version

# Test SQLite extension
php -m | grep sqlite

# Check .env is being read
cat .env

# Verify npm dependencies
npm ls

# Restart from scratch (desktop)
rm -rf node_modules package-lock.json
npm install
php database/create_db.php
npm start

# Debug server startup
node server.js 2>&1 | head -30
```

---

## Where to Find Logs

- `logs/` directory — application and error logs
- Server console output — when running `npm start` or `./start.sh`
- `audit_logs` table in the database — tracks user actions

---

## Getting Help

1. Check this troubleshooting guide.
2. Review the `logs/` directory.
3. Verify `.env` configuration matches system setup.
4. Ensure prerequisites are met (Node.js, PHP, SQLite3/PostgreSQL).
5. If still stuck, check the `README.md` for full feature list and setup steps.