-- MoDokana dashboard account mapping check
-- Run in phpMyAdmin / MySQL before and after the patch.

SELECT
    u.id AS user_id,
    u.name AS user_name,
    u.email,
    u.mobile,
    u.role,
    u.status AS user_status,
    u.shop_id,
    s.id AS matched_shop_id,
    s.owner_id,
    s.name AS shop_name,
    s.status AS shop_status
FROM users u
LEFT JOIN shops s
    ON s.id = u.shop_id
ORDER BY u.id DESC;

-- Find owners that can be repaired from shops.owner_id:
SELECT
    u.id AS user_id,
    u.name AS user_name,
    u.shop_id,
    s.id AS owned_shop_id,
    s.name AS owned_shop_name
FROM users u
JOIN shops s
    ON s.owner_id = u.id
WHERE u.shop_id IS NULL
   OR u.shop_id = 0;

-- If the repair migration cannot be used, this MySQL statement
-- performs the same safe owner mapping:
UPDATE users u
JOIN shops s
    ON s.owner_id = u.id
SET u.shop_id = s.id
WHERE u.shop_id IS NULL
   OR u.shop_id = 0;
