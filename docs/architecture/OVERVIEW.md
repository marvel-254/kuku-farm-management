# 🏗️ Architecture Overview

## System Design

KUKU Farm Management uses a **dual-mode architecture** to serve different use cases:

```
┌─────────────────────────────────────────────────────────────┐
│                  KUKU Farm Management                        │
│                                                              │
│  ┌─────────────────────┐      ┌──────────────────────────┐   │
│  │  DESKTOP MODE      │      │  WEB MODE                │   │
│  │  Electron + Node.js│      │  PHP 8.0 + PostgreSQL    │   │
│  │                     │      │  (or SQLite for local)   │   │
│  │  • Works offline    │      │  • Browser accessible    │   │
│  │  • Native OS feel   │      │  • Real-time sync        │   │
│  │  • SQLite local DB  │      │  • Remote access         │   │
│  └─────────────────────┘      └──────────────────────────┘   │
│                     Shared Concepts                          │
│  • User Authentication (bcrypt, sessions)                    │
│  • Chicken / Batch / Health Tracking                        │
│  • Inventory (feeds, meds, equipment)                       │
│  • Financial (sales, expenses, invoices, debts)             │
│  • Audit Logging & Security                                  │
└─────────────────────────────────────────────────────────────┘
```

---

## Technology Stack

### Desktop Mode

| Layer | Technology | Version / Details |
|-------|------------|-------------------|
| **Runtime** | Electron | v25.0.0 |
| **Backend** | Node.js / Express | v16+ |
| **Frontend** | HTML5, CSS3, JavaScript | Custom responsive design |
| **UI Library** | Chart.js | Interactive data charts |
| **Session** | express-session | With cookie settings |
| **Auth** | bcryptjs | Password hashing |
| **DB (local)** | SQLite3 | `database/kuku_farm.sqlite` |

### Web Mode

| Layer | Technology | Version / Details |
|-------|------------|-------------------|
| **Server** | PHP 8.0+ | With SQLite3 extension |
| **Web Server** | Apache / Nginx / PHP built-in | `start.sh` configures port |
| **Frontend** | HTML5, CSS3, JavaScript, PHP templates | `public/*.php` |
| **Database** | SQLite (local) / PostgreSQL (production) | Configurable via `.env` |

---

## Key Components

### 1. Main Process (`electron-main.js`)
Controls Electron window creation, menu bars, and desktop-specific features.

### 2. Backend Server (`server.js`)
Express server handling:
- Session management (`express-session`)
- Authentication (`requireAuth`, `requireRole`)
- Database queries (PostgreSQL via `pg` pool)
- REST endpoints (`/api.php` in web mode)

### 3. Database Layer
- `database/create_db.php` — SQLite schema creation
- `database/migrations/` — Schema version files
- `db/migrate.js` — PostgreSQL migration runner
- Tables: `users`, `chicken_groups`, `chicken_batches`, `chickens`, `items`, `inventory`, `sales`, `expenses`, `invoices`, `debts`, `earnings`, `health_records`, `audit_logs`, `finance_logs`

### 4. Web Application (`public/`)
PHP pages for:
- Login (`login.php`)
- Dashboard (`dashboard.php`, `dashboard.html`)
- Chickens (`chickens.php`, `chickens.html`, `chicken-add.php`)
- Inventory (`inventory.php`, `inventory.html`, `inventory-add.php`)
- Sales (`sales.php`, `sales.html`, `sale-add.php`)
- Expenses (`expenses.php`, `expenses.html`, `expense-add.php`)
- Invoices (`invoices.php`, `invoices.html`, `invoice-add.php`)
- Debts (`debts.php`, `debts.html`, `debt-add.php`)
- Reports (`reports.php`, `reports.html`)
- Financial (`financial.php`, `financial.html`)

### 5. Desktop Application (`desktop/`)
Electron renderer pages matching the web functionality but optimized for desktop:
- `desktop/index.html`
- `desktop/FEATURES.md` — Detailed feature descriptions
- `desktop/BUILD.md` — Build instructions

---

## Security Design

- **Password Hashing**: `bcryptjs` (salted, one-way)
- **SQL Injection Prevention**: Prepared statements with `pg` / parameterized queries
- **XSS Protection**: `sanitize()` function strips `<` and `>` from inputs
- **CSRF Protection**: Required for sensitive POST operations
- **Session Security**: `httpOnly`, `sameSite`, `secure` cookies based on environment
- **Audit Trail**: `audit_logs` table records user actions with IP and user agent
- **Role-Based Access**: `requireRole(role)` middleware restricts endpoints

---

## Data Flow

```
User Request (Desktop or Web)
        ↓
  Session / Auth Check
        ↓
  Route Handler (Express or PHP)
        ↓
  Database Query (PostgreSQL / SQLite)
        ↓
  Response (JSON / HTML / Chart Data)
        ↓
Audit Log Entry (optional, on sensitive actions)
```
