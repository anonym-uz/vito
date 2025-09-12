USER_TO_REVOKE='{{ $username }}'
DB_NAME='{{ $database }}'
DB_VERSION='{{ $version }}'

# Only revoke from the specific database being unlinked
echo "Revoking privileges from database: $DB_NAME"
# Revoke CONNECT privilege first to prevent access
sudo -u postgres psql -c "REVOKE CONNECT ON DATABASE \"$DB_NAME\" FROM $USER_TO_REVOKE;" 2>/dev/null || true
# Then revoke all other privileges
sudo -u postgres psql -c "REVOKE ALL PRIVILEGES ON DATABASE \"$DB_NAME\" FROM $USER_TO_REVOKE;" 2>/dev/null || true

# Check if PostgreSQL version is 15 or greater
if [ "$DB_VERSION" -ge 15 ]; then
    sudo -u postgres psql -d "$DB_NAME" -c "REVOKE USAGE, CREATE ON SCHEMA public FROM $USER_TO_REVOKE;" 2>/dev/null || true
fi

echo "Privileges revoked from $USER_TO_REVOKE"
