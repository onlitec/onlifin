#!/bin/bash
# ===========================================
# Onlifin Database Migration Runner
# ===========================================
# This script runs all pending migrations against the database.
# It waits for the database to be ready before running.

set -e

echo "🚀 Onlifin Migration Runner Starting..."

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
until PGPASSWORD=$POSTGRES_PASSWORD psql -h $POSTGRES_HOST -U $POSTGRES_USER -d $POSTGRES_DB -c '\q' 2>/dev/null; do
  echo "   Database not ready yet. Retrying in 2 seconds..."
  sleep 2
done

echo "✅ Database is ready!"

# Run all migration files in order
MIGRATIONS_DIR="/migrations"

if [ -d "$MIGRATIONS_DIR" ]; then
  for migration in $(ls -1 $MIGRATIONS_DIR/*.sql 2>/dev/null | sort); do
    echo "📄 Running migration: $(basename $migration)"
    PGPASSWORD=$POSTGRES_PASSWORD psql -h $POSTGRES_HOST -U $POSTGRES_USER -d $POSTGRES_DB -f "$migration"
    echo "   ✅ Done!"
  done
else
  echo "⚠️ No migrations directory found at $MIGRATIONS_DIR"
fi

echo "🎉 All migrations completed successfully!"
exit 0
