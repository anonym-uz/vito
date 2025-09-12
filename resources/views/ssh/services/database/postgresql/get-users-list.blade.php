if ! sudo -u postgres psql -c "WITH user_databases AS (
    SELECT 
        r.rolname,
        d.datname
    FROM pg_roles r
    CROSS JOIN pg_database d
    WHERE r.rolcanlogin 
      AND d.datistemplate = false
      AND (
          -- Check if user has explicit CONNECT grant in the database ACL
          d.datacl::text LIKE '%' || r.rolname || '=c/%' 
          -- Or check if user has other privileges (which implies CONNECT)
          OR d.datacl::text LIKE '%' || r.rolname || '=C/%'
          OR d.datacl::text LIKE '%' || r.rolname || '=T/%'
          OR d.datacl::text LIKE '%' || r.rolname || '=CTc/%'
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
