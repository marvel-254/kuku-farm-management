#!/usr/bin/env bash

# Database Backup Script for KUKU Farm Management
# Usage: ./scripts/db-backup.sh [output-file]
# Output file defaults to: backups/kuku_farm_$(date +%Y%m%d_%H%M%S).sql

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="$PROJECT_ROOT/backups"
DATABASE_URL="${DATABASE_URL:-}"

# Load .env if exists
if [[ -f "$PROJECT_ROOT/.env" ]]; then
  set -a
  source "$PROJECT_ROOT/.env"
  set +a
fi

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Default output filename with timestamp
OUTPUT_FILE="${1:-$BACKUP_DIR/kuku_farm_$(date +%Y%m%d_%H%M%S).sql}"

# Validate database URL
if [[ -z "$DATABASE_URL" ]]; then
  echo "ERROR: DATABASE_URL not set."
  echo "Set it in .env or export it before running this script."
  exit 1
fi

# Extract connection details
if [[ "$DATABASE_URL" =~ postgresql://([^:]+):([^@]+)@([^:/]+):?([0-9]*)/(.+) ]]; then
  PGUSER="${BASH_REMATCH[1]}"
  PGPASSWORD="${BASH_REMATCH[2]}"
  PGHOST="${BASH_REMATCH[3]}"
  PGPORT="${BASH_REMATCH[4]:-5432}"
  PGDATABASE="${BASH_REMATCH[5]}"
else
  echo "ERROR: Could not parse DATABASE_URL format."
  echo "Expected: postgresql://user:pass@host:port/dbname"
  exit 1
fi

export PGUSER PGPASSWORD PGHOST PGPORT PGDATABASE

echo "Starting database backup..."
echo "Database: $PGDATABASE on $PGHOST:$PGPORT"
echo "Output:   $OUTPUT_FILE"

# Run pg_dump
if pg_dump --verbose --clean --if-exists --no-owner --no-privileges \
  --format=plain --encoding=UTF8 \
  --file="$OUTPUT_FILE" "$DATABASE_URL"; then

  # Compress the backup
  gzip -f "$OUTPUT_FILE"
  echo "✅ Backup completed successfully!"
  echo "📦 Compressed backup: ${OUTPUT_FILE}.gz"
  echo "📏 Size: $(du -h "${OUTPUT_FILE}.gz" | cut -f1)"

  # Keep only last 30 backups
  cd "$BACKUP_DIR"
  ls -t kuku_farm_*.sql.gz 2>/dev/null | tail -n +31 | xargs -r rm -f
  echo "🗂️  Cleaned old backups (kept last 30)"
else
  echo "❌ Backup failed!"
  exit 1
fi