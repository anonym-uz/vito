if ! sudo -u postgres psql -c "
WITH user_db_access AS (
    SELECT 
        r.rolname,
        d.datname,
        -- Check if user has explicit permission in datacl (not inherited from PUBLIC)
        CASE 
            WHEN d.datacl IS NULL THEN false  -- No explicit ACL means only PUBLIC has access
            WHEN d.datacl::text ~ (r.rolname || '=[^/]*[CT]') THEN true  -- User has explicit CONNECT or CREATE
            ELSE false
        END as has_explicit_access
    FROM pg_roles r
    CROSS JOIN pg_database d
    WHERE r.rolcanlogin 
      AND d.datistemplate = false
      AND d.datname NOT IN ('template0', 'template1')
)
SELECT 
    rolname AS username,
    '' AS host,
    STRING_AGG(
        CASE WHEN has_explicit_access THEN datname ELSE NULL END, 
        ',' ORDER BY datname
    ) AS databases
FROM user_db_access
GROUP BY rolname
HAVING STRING_AGG(
    CASE WHEN has_explicit_access THEN datname ELSE NULL END, 
    ','
) IS NOT NULL
ORDER BY rolname;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi