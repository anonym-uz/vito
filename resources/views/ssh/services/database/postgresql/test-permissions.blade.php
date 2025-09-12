#!/bin/bash

echo "=== PostgreSQL Permission Diagnostic ==="
echo ""

# 1. Check PUBLIC permissions on all databases
echo "1. PUBLIC permissions on databases:"
sudo -u postgres psql -c "
SELECT 
    datname,
    CASE 
        WHEN datacl IS NULL THEN 'DEFAULT (PUBLIC has CONNECT)'
        WHEN datacl::text LIKE '%=c/%' OR datacl::text LIKE '%=C/%' THEN 'PUBLIC has CONNECT'
        ELSE 'PUBLIC blocked'
    END as public_status
FROM pg_database 
WHERE datistemplate = false
ORDER BY datname;"

echo ""
echo "2. User explicit permissions (excluding PUBLIC inheritance):"
sudo -u postgres psql -c "
SELECT 
    r.rolname AS user,
    d.datname AS database,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM unnest(d.datacl) AS acl 
            WHERE acl::text LIKE r.rolname || '=%'
        ) THEN 'HAS EXPLICIT ACCESS'
        ELSE 'NO EXPLICIT ACCESS'
    END as status
FROM pg_roles r
CROSS JOIN pg_database d
WHERE r.rolcanlogin 
  AND NOT r.rolsuper
  AND d.datistemplate = false
  AND d.datname NOT IN ('template0', 'template1')
  AND EXISTS (
      SELECT 1 FROM unnest(d.datacl) AS acl 
      WHERE acl::text LIKE r.rolname || '=%'
  )
ORDER BY r.rolname, d.datname;"

echo ""
echo "3. Template1 PUBLIC status (affects new databases):"
sudo -u postgres psql -c "
SELECT 
    datname,
    CASE 
        WHEN datacl IS NULL THEN 'DEFAULT (PUBLIC has CONNECT)'
        WHEN datacl::text LIKE '%=c/%' OR datacl::text LIKE '%=C/%' THEN 'PUBLIC has CONNECT'
        ELSE 'PUBLIC blocked'
    END as public_status
FROM pg_database 
WHERE datname = 'template1';"

echo ""
echo "=== DIAGNOSIS ==="
echo "If you see 'PUBLIC has CONNECT' or 'DEFAULT' above, that's the problem!"
echo "Run these commands to fix:"
echo ""
echo "1. Fix template1 (for new databases):"
echo "   sudo -u postgres psql -c \"REVOKE CONNECT ON DATABASE template1 FROM PUBLIC;\""
echo ""
echo "2. Fix all existing databases:"
echo "   for DB in \$(sudo -u postgres psql -t -c \"SELECT datname FROM pg_database WHERE datistemplate = false;\"); do"
echo "     sudo -u postgres psql -c \"REVOKE CONNECT ON DATABASE \\\"\$DB\\\" FROM PUBLIC;\""
echo "   done"