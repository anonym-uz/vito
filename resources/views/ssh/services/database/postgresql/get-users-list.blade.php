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
