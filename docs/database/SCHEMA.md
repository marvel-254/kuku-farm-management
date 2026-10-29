# 🗄️ Database Schema

The KUKU Farm Management System uses **SQLite** for local/desktop use and **PostgreSQL** for production/web deployments.

---

## Schema Files

| File | Purpose |
|------|---------|
| `database/create_db.php` | Creates SQLite database and all tables |
| `database/migrations/` | Schema migration scripts |
| `db/migrate.js` | PostgreSQL migration runner |

---

## Core Tables

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `users` | Authentication and user management | `id`, `username`, `password`, `role` |
| `chicken_groups` | Chicken breed/type categories | `id`, `name` |
| `chicken_batches` | Batch management and tracking | `id`, `group_id`, `count`, `date` |
| `chickens` | Individual chicken records | `id`, `batch_id`, `weight`, `health_status` |
| `items` | Inventory items (feeds, medicine, equipment) | `id`, `name`, `category`, `unit_cost` |
| `inventory` | Stock transactions and levels | `id`, `item_id`, `quantity`, `type` |
| `sales` | Sales transactions and revenue | `id`, `item_id`, `quantity`, `total` |
| `expenses` | Expense tracking by category | `id`, `category`, `amount`, `date` |
| `invoices` | Sales/purchase invoices | `id`, `invoice_number`, `total` |
| `debts` | Money owed and money owing | `id`, `party`, `amount`, `status` |
| `earnings` | Additional income sources | `id`, `source`, `amount` |
| `health_records` | Chicken health monitoring | `id`, `chicken_id`, `treatment`, `date` |
| `audit_logs` | Activity logging and audit trails | `id`, `user_id`, `action`, `table_name`, `record_id` |
| `finance_logs` | Financial transaction history | `id`, `type`, `amount`, `description` |

---

## Table Relationships

```
users (1) ──< (N) audit_logs
users (1) ──< (N) chickens (via chicken_batches)

chicken_groups (1) ──< (N) chicken_batches
chicken_batches (1) ──< (N) chickens

items (1) ──< (N) inventory
items (1) ──< (N) sales

inventory (1) ──< (N) sales

chickens (1) ──< (N) health_records

# Feed Formulation Tables (planned)
feed_ingredients (1) ──< (N) feed_recipe_ingredients
feed_recipes (1) ──< (N) feed_recipe_ingredients
feed_recipes (1) ──< (N) feed_batches
chicken_batches (1) ──< (N) feed_batches
```

---

## Database Setup

### SQLite (Local Development)

```bash
php database/create_db.php
```

Creates: `database/kuku_farm.sqlite`

### PostgreSQL (Production)

```bash
node db/migrate.js
```

Reads `DATABASE_URL` from `.env` and runs migrations.

---

## Default Data

After setup, the system creates:
- **Default admin user**: `admin` / `admin123`
- **Roles**: Admin, Manager, User

> **Note**: Change the default admin password after first login.

---

## Security

- Passwords hashed with `bcryptjs`
- Prepared statements prevent SQL injection
- `audit_logs` tracks all data changes with user ID, IP, and timestamp
