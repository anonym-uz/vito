if ! sudo -u postgres psql -c "SELECT 
    r.rolname AS username,
    '' AS host,
    STRING_AGG(
        CASE 
            WHEN has_database_privilege(r.rolname, d.datname, 'CONNECT') 
                 AND d.datname NOT IN ('template0', 'template1')
            THEN d.datname 
            ELSE NULL 
        END, ',' ORDER BY d.datname
    ) AS databases
FROM pg_roles r
CROSS JOIN pg_database d
WHERE r.rolcanlogin 
  AND d.datistemplate = false
GROUP BY r.rolname
HAVING STRING_AGG(
    CASE 
        WHEN has_database_privilege(r.rolname, d.datname, 'CONNECT') 
             AND d.datname NOT IN ('template0', 'template1')
        THEN d.datname 
        ELSE NULL 
    END, ',' ORDER BY d.datname
) IS NOT NULL
ORDER BY r.rolname;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi