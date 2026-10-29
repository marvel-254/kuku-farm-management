# 🔌 API Reference

The web version (`public/api.php`) provides RESTful endpoints for data operations.

---

## Base URL

- Development: `http://localhost:4567/api.php`
- Production: `https://your-server/api.php`

---

## Authentication

Most endpoints require an active session cookie set by `login.php`.

- **Login**: `POST /api.php?endpoint=login`
- **Status Check**: `GET /api.php?endpoint=status`

---

## Endpoints

| Endpoint | Method | Description | Auth Required |
|----------|--------|-------------|---------------|
| `status` | GET | System status and connection info | No |
| `weather` | GET | Local weather data by coordinates (`.env`) | No |
| `login` | POST | User authentication (`username`, `password`) | No |
| `dashboard` | GET | Dashboard statistics and overview | Yes |
| `calculations` | GET | Farming calculator results | Yes |

---

## Key Endpoints Explained

### `/api.php?endpoint=status`
Returns server health, database connection status, and version info.

### `/api.php?endpoint=weather`
Returns weather data based on `WEATHER_LAT` and `WEATHER_LON` from `.env`.

### `/api.php?endpoint=login`
Accepts `username` and `password`, verifies with bcrypt, and creates a session.

### `/api.php?endpoint=dashboard`
Returns aggregated statistics for the dashboard (chicken counts, inventory levels, financial summaries).

---

## Web Pages vs API

The application has two interfaces:
- **HTML pages** (`*.php`, `*.html`) for users
- **API endpoints** (`api.php`) for programmatic access or frontend data fetch

The desktop Electron app may also call the same backend through local server or embedded APIs.
