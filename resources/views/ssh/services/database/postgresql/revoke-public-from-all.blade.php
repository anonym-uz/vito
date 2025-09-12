#!/bin/bash

echo "=== Revoking PUBLIC access from ALL databases ==="

# Revoke from all user databases
DATABASES=$(sudo -u postgres psql -t -c "SELECT datname FROM pg_database WHERE datistemplate = false;")
for DB in $DATABASES; do
    DB=$(echo $DB | xargs)
    if [ -n "$DB" ]; then
        echo "Revoking PUBLIC from: $DB"
        sudo -u postgres psql -c "REVOKE ALL ON DATABASE \"$DB\" FROM PUBLIC;" 2>/dev/null || true
        sudo -u postgres psql -c "REVOKE CONNECT ON DATABASE \"$DB\" FROM PUBLIC;" 2>/dev/null || true
    fi
done

# CRITICAL: Also revoke from template databases to prevent inheritance
echo "Revoking PUBLIC from template1..."
sudo -u postgres psql -c "REVOKE CONNECT ON DATABASE template1 FROM PUBLIC;" 2>/dev/null || true

echo "Done! PUBLIC access revoked from all databases."