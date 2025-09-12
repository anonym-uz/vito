#!/bin/bash

echo "Fixing PostgreSQL database permissions - removing PUBLIC access..."

# Get all non-template databases
DATABASES=$(sudo -u postgres psql -t -c "SELECT datname FROM pg_database WHERE datistemplate = false;")

for DB in $DATABASES; do
    echo "Processing database: $DB"
    
    # Revoke CONNECT from PUBLIC
    sudo -u postgres psql -c "REVOKE CONNECT ON DATABASE \"$DB\" FROM PUBLIC;" 2>/dev/null || true
    
    # Ensure postgres superuser can still connect
    sudo -u postgres psql -c "GRANT CONNECT ON DATABASE \"$DB\" TO postgres;" 2>/dev/null || true
    
    echo "  - Revoked PUBLIC access from $DB"
done

echo "Restoring access for linked users..."

# Now we need to re-grant access to users who should have it
# This query finds users who have any other privileges on the database
sudo -u postgres psql -c "
DO \$\$
DECLARE
    rec RECORD;
BEGIN
    FOR rec IN 
        SELECT DISTINCT 
            d.datname,
            r.rolname
        FROM pg_database d
        CROSS JOIN pg_roles r
        WHERE d.datistemplate = false
          AND r.rolcanlogin
          AND r.rolname != 'postgres'
          AND EXISTS (
              SELECT 1
              FROM unnest(d.datacl) AS acl
              WHERE acl::text LIKE r.rolname || '=%'
                AND acl::text LIKE '%C%T%'  -- Has CREATE and TEMP privileges
          )
    LOOP
        EXECUTE format('GRANT CONNECT ON DATABASE %I TO %I', rec.datname, rec.rolname);
        RAISE NOTICE 'Restored access for % to %', rec.rolname, rec.datname;
    END LOOP;
END \$\$;"

echo "Permission fix completed!"