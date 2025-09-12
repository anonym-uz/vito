if ! sudo -u postgres psql -c "
WITH user_permissions AS (
    SELECT 
        r.rolname,
        d.datname,
        d.datacl,
        -- Check if this specific user has an explicit ACL entry
        EXISTS (
            SELECT 1 
            FROM unnest(d.datacl) AS acl 
            WHERE acl::text LIKE r.rolname || '=%' 
        ) AS has_explicit_permission
    FROM pg_roles r
    CROSS JOIN pg_database d
    WHERE r.rolcanlogin 
      AND NOT r.rolsuper  -- Exclude superusers like postgres
      AND d.datistemplate = false
      AND d.datname NOT IN ('template0', 'template1')
)
SELECT 
    rolname AS username,
    '' AS host,
    COALESCE(
        STRING_AGG(
            CASE 
                WHEN has_explicit_permission THEN datname 
                ELSE NULL 
            END, 
            ',' ORDER BY datname
        ),
        ''
    ) AS databases
FROM user_permissions
GROUP BY rolname
ORDER BY rolname;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi