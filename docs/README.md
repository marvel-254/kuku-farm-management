# 📚 KUKU Farm Management System — Documentation Plan

> **Status**: In Progress  
> **Created**: October 29, 2025  
> **Author**: Development Team

---

## 🎯 Goals

This documentation directory exists to explain:

1. **What the system does** — Farm management for chicken batches, inventory, sales, expenses, invoices, debts, health records, and weather tracking.
2. **How it is built** — Dual-application architecture (Desktop Electron + Web PHP) backed by SQLite/PostgreSQL.
3. **How to make it work** — Setup steps, environment variables, build commands, deployment options, and troubleshooting guides.
4. **How to maintain it** — Database migrations, security practices, audit logging, and backup strategies.

---

## 🗂️ Directory Structure

```
docs/
├── README.md                    # This file — plan overview and index
├── architecture/
│   └── OVERVIEW.md              # System architecture and technology choices
├── setup/
│   └── INSTALLATION.md          # Step-by-step installation instructions
├── deployment/
│   └── DEPLOYMENT.md            # Build, release, and hosting guides
├── api/
│   └── ENDPOINTS.md             # API reference for web version
├── database/
│   └── SCHEMA.md                # Tables, relationships, and migrations
├── contributing/
│   └── GUIDELINES.md            # How to contribute and code standards
├── troubleshooting/
│   └── COMMON_ISSUES.md         # Known problems and fixes
├── academy/
│   └── PLANNED_FEATURES.md      # Educational feature: seasons, meds, resources
├── features/
│   └── FEED_FORMULATION.md      # Feed formulation & nutrition calculator
└── roadmap/
    └── PLANNED_FEATURES.md      # Future roadmap and milestones
```

---

## 🚀 Featured Feature Plans

### 1. Feed Formulation & Nutrition Calculator (`docs/features/FEED_FORMULATION.md`)
- Custom feed recipes with target protein/energy
- Nutrition analysis (crude protein, ME, minerals)
- Cost optimization and substitution logic
- Growth correlation: feed formula vs. batch weight gain

### 2. KUKU Academy (`docs/academy/PLANNED_FEATURES.md`)
- Seasonal medication schedules
- YouTube videos, PDFs, blog summaries
- Public veterinary/extension resources

### 3. High-Level Roadmap (`docs/roadmap/PLANNED_FEATURES.md`)
- Multi-farm support, AI insights, PWA/offline sync, breeding programs, market integration.

---

## 📋 What is Needed for This to Work

### 1. Prerequisites

| Component | Requirement | Why |
|-----------|-------------|-----|
| **Desktop App** | Node.js v16+, Electron 25+, npm | To build and run the Electron desktop application |
| **Web App** | PHP 8.0+, SQLite3 extension, Apache/Nginx | To serve the PHP web interface |
| **Database** | PostgreSQL (production) or SQLite (local) | For persistent data storage |
| **System** | Windows/macOS/Linux, 2GB RAM, 100MB storage | Minimum hardware requirements |

### 2. Key Configuration Files

- `.env` — Defines `PORT`, `NODE_ENV`, `DATABASE_URL`, `SESSION_SECRET`, and weather coordinates.
- `package.json` — Lists dependencies (`express`, `pg`, `bcryptjs`, `electron`, etc.) and build scripts.
- `database/create_db.php` — Creates SQLite database and initial schema.
- `db/migrate.js` — Handles PostgreSQL migrations.

### 3. Core Application Components

| Component | File / Directory | Purpose |
|-----------|------------------|---------|
| **Desktop Main Process** | `electron-main.js` | Electron main process, window management |
| **Web Entry Point** | `public/index.php`, `public/index.html` | Main entry for web users |
| **Backend Server** | `server.js` | Express server, session management, API routes |
| **Desktop UI** | `desktop/index.html`, `desktop/renderer/` | Electron frontend interface |
| **Web UI** | `public/*.php`, `public/*.html` | PHP-based web pages |
| **Database Schema** | `database/migrations/` | SQL schema files |
| **Web Models** | `app/models/` | PHP model classes (Chicken, Expense, Sale, etc.) |
| **Desktop Features** | `desktop/FEATURES.md` | Detailed feature descriptions |

---

## 🏗️ System Overview

The KUKU Farm Management System supports two deployment modes:

### Desktop Mode (Electron + Node.js)
- Works **offline** using a local SQLite database.
- Provides faster performance and native OS integration.
- Uses `electron-main.js` and desktop renderer HTML files.

### Web Mode (PHP 8.0 + PostgreSQL/SQLite)
- Works **through a browser** on any device.
- Provides real-time updates and remote access.
- Uses `public/*.php` pages with `app/` backend models.

Both modes share the same core concepts:
- **Users & Authentication** — Role-based access (Admin, Manager, User)
- **Chicken Management** — Batches, individual tracking, health records
- **Inventory** — Feeds, medicines, equipment with stock monitoring
- **Financial Tracking** — Sales, expenses, invoices, debts, earnings
- **Weather Integration** — Local weather data for farm planning
- **Audit & Security** — Activity logging, SQL injection prevention, CSRF protection

---

## 🛠️ Implementation Plan

### Phase 1: Setup and Environment
- [ ] Verify prerequisites (Node.js, PHP 8.0, SQLite3)
- [ ] Install dependencies (`npm install`)
- [ ] Configure `.env` file with database URL and session secret
- [ ] Initialize database (`php database/create_db.php` or `node db/migrate.js`)

### Phase 2: Documentation Files
- [ ] `docs/README.md` — Complete this file
- [ ] `docs/architecture/OVERVIEW.md` — Technology choices and architecture diagrams
- [ ] `docs/setup/INSTALLATION.md` — Detailed setup for desktop and web
- [ ] `docs/deployment/DEPLOYMENT.md` — Building desktop packages, deploying web server
- [ ] `docs/api/ENDPOINTS.md` — Document REST endpoints in `public/api.php`
- [ ] `docs/database/SCHEMA.md` — Document tables (`users`, `chickens`, `inventory`, `sales`, etc.)
- [ ] `docs/contributing/GUIDELINES.md` — Code style and contribution process
- [ ] `docs/troubleshooting/COMMON_ISSUES.md` — Errors and fixes from setup to production

### Phase 3: Verification
- [ ] Run desktop app (`npm start`)
- [ ] Run web server (`./start.sh` or `node server.js`)
- [ ] Confirm database connections
- [ ] Confirm documentation renders clearly

---

## 📚 References and Existing Resources

| Resource | Path | Description |
|----------|------|-------------|
| Project README | `README.md` | Full feature list, installation steps, usage guide |
| Desktop Features | `desktop/FEATURES.md` | Detailed desktop feature descriptions |
| Desktop Build Guide | `desktop/BUILD.md` | Building Electron apps for all platforms |
| Desktop Development | `desktop/DEVELOPMENT.md` | Development setup for desktop |
| Desktop Structure | `desktop/STRUCTURE.md` | File and folder organization |
| Server Code | `server.js` | Express backend, routes, middleware |
| Package Config | `package.json` | Scripts, dependencies, build settings |
| Web API | `public/api.php` | REST endpoint definitions |
| Database Setup | `database/create_db.php` | SQLite initialization |
| Migration Script | `db/migrate.js` | PostgreSQL schema management |

---

## ✅ Success Criteria

- [ ] `docs/` directory exists with organized subfolders.
- [ ] Every subfolder contains at least one `.md` file.
- [ ] Documentation explains the project goals clearly.
- [ ] Documentation explains everything needed for the system to work (prerequisites, setup, deployment, maintenance).
- [ ] A new developer can follow the docs and run the system without external help.

---

*This plan is a living document. Update it as the codebase evolves.*
