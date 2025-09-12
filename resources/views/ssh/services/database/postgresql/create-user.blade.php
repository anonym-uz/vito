if ! sudo -u postgres psql -c "CREATE ROLE \"{{ $username }}\" WITH LOGIN PASSWORD '{{ $password }}';"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

# Revoke default CONNECT privileges from all databases to ensure user starts with no access
DATABASES=$(sudo -u postgres psql -t -c "SELECT datname FROM pg_database WHERE datistemplate = false;")
for DB in $DATABASES; do
    sudo -u postgres psql -c "REVOKE CONNECT ON DATABASE \"$DB\" FROM \"{{ $username }}\";" 2>/dev/null || true
done

echo "User {{ $username }} created"
