# Query based on explicit ACL entries only
# This ignores inherited permissions and only shows explicitly granted access
if ! sudo -u postgres psql -c "WITH user_databases AS (
    SELECT DISTINCT
        r.rolname,
        d.datname
    FROM pg_roles r
    CROSS JOIN pg_database d
    WHERE r.rolcanlogin 
      AND d.datistemplate = false
      AND (
          -- Check if ACL contains an entry for this specific user
          -- PostgreSQL ACL format: grantee=privileges/grantor
          CASE 
              WHEN d.datacl IS NULL THEN 
                  -- NULL ACL means default permissions (owner has all, PUBLIC has CONNECT)
                  d.datdba = r.oid
              ELSE
                  -- Check if user appears in the ACL with any privileges
                  array_to_string(d.datacl, ',') ~ (r.rolname || '=[^/]+/')
          END
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
