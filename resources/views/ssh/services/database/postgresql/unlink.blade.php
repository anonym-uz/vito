USER_TO_REVOKE='{{ $username }}'
DB_VERSION='{{ $version }}'

# Get all non-template databases
DATABASES=$(sudo -u postgres psql -t -c "SELECT datname FROM pg_database WHERE datistemplate = false AND datname NOT IN ('template0', 'template1');")

for DB in $DATABASES; do
    echo "Revoking privileges in database: $DB"
    sudo -u postgres psql -d "$DB" -c "REVOKE ALL PRIVILEGES ON DATABASE \"$DB\" FROM $USER_TO_REVOKE;" 2>/dev/null || true

    # Check if PostgreSQL version is 15 or greater
    if [ "$DB_VERSION" -ge 15 ]; then
        sudo -u postgres psql -d "$DB" -c "REVOKE USAGE, CREATE ON SCHEMA public FROM $USER_TO_REVOKE;" 2>/dev/null || true
    fi
done

echo "Privileges revoked from $USER_TO_REVOKE"