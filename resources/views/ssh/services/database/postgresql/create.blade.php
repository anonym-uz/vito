if ! sudo -u postgres psql -c "CREATE DATABASE \"{{ $name }}\" WITH ENCODING '{{ $charset }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

# Revoke default PUBLIC CONNECT privilege to ensure only explicitly granted users can connect
if ! sudo -u postgres psql -c "REVOKE CONNECT ON DATABASE \"{{ $name }}\" FROM PUBLIC;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

# Grant CONNECT back to the postgres superuser to ensure admin access
if ! sudo -u postgres psql -c "GRANT CONNECT ON DATABASE \"{{ $name }}\" TO postgres;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Database {{ $name }} created"
