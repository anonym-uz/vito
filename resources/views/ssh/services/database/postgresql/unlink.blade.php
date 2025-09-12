USER_TO_REVOKE='{{ $username }}'
DB_VERSION='{{ $version }}'

# Get all non-template databases
DATABASES=$(sudo -u postgres psql -t -c "SELECT datname FROM pg_database WHERE datistemplate = false AND datname NOT IN ('template0', 'template1');")

for DB in $DATABASES; do
    DB=$(echo $DB | xargs)
    if [ -n "$DB" ]; then
        echo "Revoking privileges from database: $DB"
        
        # First ensure PUBLIC doesn't have access (this is critical!)
        sudo -u postgres psql -c "REVOKE CONNECT ON DATABASE \"$DB\" FROM PUBLIC;" 2>/dev/null || true
        sudo -u postgres psql -c "REVOKE ALL ON DATABASE \"$DB\" FROM PUBLIC;" 2>/dev/null || true
        
        # Then revoke user's explicit permissions
        sudo -u postgres psql -c "REVOKE CONNECT ON DATABASE \"$DB\" FROM $USER_TO_REVOKE;" 2>/dev/null || true
        sudo -u postgres psql -c "REVOKE ALL PRIVILEGES ON DATABASE \"$DB\" FROM $USER_TO_REVOKE;" 2>/dev/null || true
        
        # Check if PostgreSQL version is 15 or greater
        if [ "$DB_VERSION" -ge 15 ]; then
            sudo -u postgres psql -d "$DB" -c "REVOKE USAGE, CREATE ON SCHEMA public FROM $USER_TO_REVOKE;" 2>/dev/null || true
        fi
        
        # Always ensure postgres superuser retains access
        sudo -u postgres psql -c "GRANT CONNECT ON DATABASE \"$DB\" TO postgres;" 2>/dev/null || true
    fi
done

echo "Privileges revoked from $USER_TO_REVOKE"