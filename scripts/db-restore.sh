#!/usr/bin/env bash

# Database Restore Script for KUKU Farm Management
# Usage: ./scripts/db-restore.sh <backup-file>
# Example: ./scripts/db-restore.sh backups/kuku_farm_20240101_120000.sql.gz

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DATABASE_URL="${DATABASE_URL:-}"

if [[ -f "$PROJECT_ROOT/.env" ]]; then
  set -a
  source "$PROJECT_ROOT/.env"
  set +a
fi

# Validate input
if [[ -z "${1:-}" ]]; then
  echo "USAGE: $0 <backup-file>"
  echo "Example: $0 backups/kuku_farm_20240101_120000.sql.gz"
  exit 1
fi

BACKUP_FILE="$1"

# Validate file exists
if [[ ! -f "$BACKUP_FILE" ]]; then
  echo "ERROR: Backup file not found: $BACKUP_FILE"
  exit 1
fi

# Validate database URL
if [[ -z "$DATABASE_URL" ]]; then
  echo "ERROR: DATABASE_URL not set."
  exit 1
fi

echo "WARNING: This will restore the database from $BACKUP_FILE"
echo "ALL CURRENT DATA WILL BE REPLACED!"
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [[ "$CONFIRM" != "yes" ]]; then
  echo "Restore cancelled."
  exit 0
fi

# Handle compressed vs plain backup
if [[ "$BACKUP_FILE" == *.gz ]]; then
  echo "Decompressing backup..."
  gunzip -c "$BACKUP_FILE" > /tmp/kuku_restore_$$.sql
  SQL_FILE="/tmp/kuku_restore_$$.sql"
else
  SQL_FILE="$BACKUP_FILE"
fi

echo "Restoring database from: $BACKUP_FILE"

# Run psql to restore
if psql -v ON_ERROR_STOP=1 --single-transaction \
  --dbname="$DATABASE_URL" < "$SQL_FILE"; then
  echo "✅ Database restored successfully!"
else
  echo "❌ Restore failed! Database may be in inconsistent state."
  # Cleanup temp file
  if [[ -f "/tmp/kuku_restore_$$.sql" ]]; then
    rm -f "/tmp/kuku_restore_$$.sql"
  fi
  exit 1
fi

# Cleanup
if [[ -f "/tmp/kuku_restore_$$.sql" ]]; then
  rm -f "/tmp/kuku_restore_$$.sql"
fi

echo "Restore complete."