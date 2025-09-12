# First, let's debug by showing the raw ACL data
echo "=== DEBUG: Raw database ACL data ===" >&2
sudo -u postgres psql -c "SELECT datname, datacl FROM pg_database WHERE datistemplate = false;" >&2

echo "=== DEBUG: Checking has_database_privilege for each user-database pair ===" >&2
sudo -u postgres psql -c "
SELECT 
    r.rolname,
    d.datname,
    has_database_privilege(r.rolname, d.datname, 'CONNECT') as has_connect,
    d.datacl::text as acl_text
FROM pg_roles r
CROSS JOIN pg_database d
WHERE r.rolcanlogin 
  AND d.datistemplate = false
ORDER BY r.rolname, d.datname;" >&2

echo "=== DEBUG: Running actual query ===" >&2

# The actual query for getting user list
if ! sudo -u postgres psql -c "WITH user_databases AS (
    SELECT 
        r.rolname,
        d.datname
    FROM pg_roles r
    CROSS JOIN pg_database d
    WHERE r.rolcanlogin 
      AND d.datistemplate = false
      AND d.datacl IS NOT NULL
      AND (
          -- Check for any explicit grant to this user in the ACL
          -- PostgreSQL ACL format: username=privileges/grantor
          d.datacl::text ~ (r.rolname || '=[^/]*/')
      )
)
SELECT 
    r.rolname AS username,
    '' AS host,
    COALESCE(STRING_AGG(ud.datname, ',' ORDER BY ud.datname), '') AS databases
FROM pg_roles r
LEFT JOIN user_databases ud ON r.rolname = ud.rolname
WHERE r.rolcanlogin
GROUP BY r.rolname
ORDER BY r.rolname;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
