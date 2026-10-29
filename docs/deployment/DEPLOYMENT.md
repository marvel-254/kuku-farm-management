# 🚀 Deployment Guide

This guide explains what is needed to deploy the KUKU Farm Management System in production.

---

## Deployment Options

### Option 1: Desktop Distribution
Best for farms that want a standalone app with offline capability.

| Target | Command | Output |
|--------|---------|--------|
| Windows | `npm run build-win` | `.exe` installer (NSIS) |
| macOS | `npm run build-mac` | `.dmg` installer |
| Linux | `npm run build-linux` | `.AppImage` |

Build settings are configured in `package.json` under `build`:
- `appId`: `com.kuku.farm.management`
- `directories.output`: `dist/`
- Icons at `assets/icons/`

> **Note**: The `package.json` excludes `docs/`, `dist/`, and `database/*.sqlite` from the build using `files: ["**/*", "!docs/**/*", "!database/*.sqlite"]`.

---

### Option 2: Web Server Deployment
Best for multi-user access, remote management, or centralized data storage.

1. **Choose a server**: Ubuntu 20.04+, Debian, or any Linux server.
2. **Install prerequisites**: PHP 8.0+, SQLite3 or PostgreSQL, web server.
3. **Copy project**: Upload `/home/marvel/Downloads/kuku/` to the server (e.g., `/var/www/kuku`).
4. **Set permissions**: `chmod -R 755 public/`, `chmod 644 .env`.
5. **Configure `.env`**: Set production `DATABASE_URL`, `SESSION_SECRET`, and `NODE_ENV=production`.
6. **Run setup**: `npm install`, `php database/create_db.php` (or PostgreSQL migrations).
7. **Run server**: Use `start.sh` or configure a reverse proxy (Nginx/Apache) to `node server.js` or `php -S`.

---

### Option 3: Cloud / Container
Not explicitly configured in this repo, but possible:

- **Docker**: Create a `Dockerfile` with Node.js + PHP + SQLite.
- **Heroku / Netlify / Vercel**: Not directly supported because the project requires both PHP and Node.js environments.
- **PostgreSQL on Neon / Supabase**: Update `.env` with cloud database URL.

---

## Production Checklist

Before going live, verify:

- [ ] `.env` has a strong `SESSION_SECRET` (not the default).
- [ ] `NODE_ENV=production`
- [ ] Database is backed up (`database/kuku_farm.sqlite` or PostgreSQL dump).
- [ ] `secure: true` in session cookie settings when using HTTPS.
- [ ] Web server requires HTTPS (SSL/TLS certificate).
- [ ] Audit logs (`audit_logs` table) are being written.
- [ ] Weather API / local data works (check coordinates).
- [ ] All dependencies installed (`npm install` completed).
- [ ] Build tested (`npm run build` succeeds for target platform).
- [ ] User roles verified (Admin, Manager, User access correct).

---

## Security Notes for Production

- **Never commit `.env`** to version control (already excluded by `.gitignore` patterns in build config).
- **Rotate session secrets** every deployment or when team members change.
- **Enable `secure: true`** for cookies when served over HTTPS.
- **Restrict database access** — if using PostgreSQL, use firewall rules or connection pooling (`connect-pg-simple`).
- **Monitor `audit_logs`** for unauthorized access attempts.

---

## Maintenance After Deployment

- **Backups**: Schedule regular backups of the SQLite file (`database/kuku_farm.sqlite`) or PostgreSQL dumps.
- **Updates**: Update dependencies with `npm update` and test `npm start`.
- **Logs**: Check `logs/` directory and server console output.
- **Health**: Monitor database connection (the `pool.on('error', ...)` handler in `server.js`).
