-- ============================================================
-- MARKETLINK - DEMO DATA SEED
-- ============================================================
-- Demo accounts:
--
-- Daniel Brooks
--   users.id = 1
--   customer
--
-- Amanda Foster
--   users.id = 38
--   farmers.id = 7
--   approved
--
-- Marcus Reed
--   users.id = 39
--   admin
--
-- Existing data is preserved.
-- New AUTO_INCREMENT IDs are never manually supplied.
--
-- IMPORTANT:
-- Product image values are FILENAMES ONLY.
-- Example:
--     whole_wheat_flour.jpg
--
-- This assumes the application builds the image URL as:
--     uploads/products/<image>
--
-- ============================================================


START TRANSACTION;


-- ============================================================
-- 1. GET DEMO ACCOUNT IDS
-- ============================================================

SELECT id
INTO @daniel_user_id
FROM users
WHERE email = 'daniel.brooks@marketlink.demo'
LIMIT 1;


SELECT id
INTO @amanda_user_id
FROM users
WHERE email = 'amanda.foster@marketlink.demo'
LIMIT 1;


SELECT id
INTO @marcus_user_id
FROM users
WHERE email = 'marcus.reed@marketlink.demo'
LIMIT 1;


SELECT id
INTO @amanda_farmer_id
FROM farmers
WHERE user_id = @amanda_user_id
LIMIT 1;


-- ============================================================
-- 2. AMANDA -> NORTH HILLS FARMERS MARKET
-- ============================================================
-- Existing verified relationship:
--
-- Amanda farmer_id = 7
-- North Hills Farmers Market = market_id 5
--
-- INSERT IGNORE prevents duplication.


INSERT IGNORE INTO market_farmer
(
    market_id,
    farmer_id
)
VALUES
(
    5,
    @amanda_farmer_id
);


-- ============================================================
-- 3. AMANDA'S PRODUCTS
-- ============================================================
--
-- Existing products 1-25 remain untouched.
--
-- Amanda receives:
--
-- Whole Wheat Flour       -> Grains
-- Local Wheat             -> Grains
-- Homemade Tomato Sauce   -> Pantry and Artisan
-- Mixed Vegetable Pickles-> Pantry and Artisan
-- Homemade Strawberry Jam -> Pantry and Artisan
-- Fresh Chicken           -> Poultry
-- Fresh Goat Meat         -> Meat
--
-- No product IDs are supplied.
-- ============================================================


INSERT INTO products
(
    farmer_id,
    category_id,
    name,
    description,
    price,
    unit,
    stock_quantity,
    image,
    is_available,
    moderation_status
)
SELECT
    @amanda_farmer_id,
    9,
    'Whole Wheat Flour',
    'Freshly milled whole wheat flour made from locally sourced wheat.',
    5.99,
    'kg',
    40.00,
    'whole_wheat_flour.jpg',
    1,
    'approved'
WHERE NOT EXISTS
(
    SELECT 1
    FROM products
    WHERE farmer_id = @amanda_farmer_id
    AND name = 'Whole Wheat Flour'
);


INSERT INTO products
(
    farmer_id,
    category_id,
    name,
    description,
    price,
    unit,
    stock_quantity,
    image,
    is_available,
    moderation_status
)
SELECT
    @amanda_farmer_id,
    9,
    'Local Wheat',
    'Locally grown wheat suitable for cooking, baking, and homemade flour.',
    4.49,
    'kg',
    35.00,
    'local_wheat.jpg',
    1,
    'approved'
WHERE NOT EXISTS
(
    SELECT 1
    FROM products
    WHERE farmer_id = @amanda_farmer_id
    AND name = 'Local Wheat'
);


INSERT INTO products
(
    farmer_id,
    category_id,
    name,
    description,
    price,
    unit,
    stock_quantity,
    image,
    is_available,
    moderation_status
)
SELECT
    @amanda_farmer_id,
    10,
    'Homemade Tomato Sauce',
    'Small-batch tomato sauce prepared with fresh ingredients.',
    6.49,
    'jar',
    25.00,
    'homemade_tomato_sauce.jpg',
    1,
    'approved'
WHERE NOT EXISTS
(
    SELECT 1
    FROM products
    WHERE farmer_id = @amanda_farmer_id
    AND name = 'Homemade Tomato Sauce'
);


INSERT INTO products
(
    farmer_id,
    category_id,
    name,
    description,
    price,
    unit,
    stock_quantity,
    image,
    is_available,
    moderation_status
)
SELECT
    @amanda_farmer_id,
    10,
    'Mixed Vegetable Pickles',
    'Homemade pickled vegetables prepared in small batches.',
    5.49,
    'jar',
    20.00,
    'mixed_vegetable_pickles.jpg',
    1,
    'approved'
WHERE NOT EXISTS
(
    SELECT 1
    FROM products
    WHERE farmer_id = @amanda_farmer_id
    AND name = 'Mixed Vegetable Pickles'
);


INSERT INTO products
(
    farmer_id,
    category_id,
    name,
    description,
    price,
    unit,
    stock_quantity,
    image,
    is_available,
    moderation_status
)
SELECT
    @amanda_farmer_id,
    10,
    'Homemade Strawberry Jam',
    'Homemade strawberry jam prepared from ripe strawberries.',
    7.49,
    'jar',
    18.00,
    'homemade_strawberry_jam.jpg',
    1,
    'approved'
WHERE NOT EXISTS
(
    SELECT 1
    FROM products
    WHERE farmer_id = @amanda_farmer_id
    AND name = 'Homemade Strawberry Jam'
);


INSERT INTO products
(
    farmer_id,
    category_id,
    name,
    description,
    price,
    unit,
    stock_quantity,
    image,
    is_available,
    moderation_status
)
SELECT
    @amanda_farmer_id,
    11,
    'Fresh Chicken',
    'Fresh locally sourced chicken prepared for convenient pickup.',
    8.99,
    'kg',
    35.00,
    'fresh_chicken.jpg',
    1,
    'approved'
WHERE NOT EXISTS
(
    SELECT 1
    FROM products
    WHERE farmer_id = @amanda_farmer_id
    AND name = 'Fresh Chicken'
);


INSERT INTO products
(
    farmer_id,
    category_id,
    name,
    description,
    price,
    unit,
    stock_quantity,
    image,
    is_available,
    moderation_status
)
SELECT
    @amanda_farmer_id,
    12,
    'Fresh Goat Meat',
    'Fresh locally sourced goat meat available for market pickup.',
    14.99,
    'kg',
    20.00,
    'fresh_goat_meat.jpg',
    1,
    'approved'
WHERE NOT EXISTS
(
    SELECT 1
    FROM products
    WHERE farmer_id = @amanda_farmer_id
    AND name = 'Fresh Goat Meat'
);


-- ============================================================
-- 4. AMANDA'S WEEKLY STOCK TEMPLATES
-- ============================================================


INSERT INTO weekly_stock_templates
(
    farmer_id,
    product_id,
    default_quantity,
    is_active
)
SELECT
    p.farmer_id,
    p.id,
    CASE p.name
        WHEN 'Whole Wheat Flour' THEN 40.00
        WHEN 'Local Wheat' THEN 35.00
        WHEN 'Homemade Tomato Sauce' THEN 25.00
        WHEN 'Mixed Vegetable Pickles' THEN 20.00
        WHEN 'Homemade Strawberry Jam' THEN 18.00
        WHEN 'Fresh Chicken' THEN 35.00
        WHEN 'Fresh Goat Meat' THEN 20.00
    END,
    1
FROM products p
WHERE p.farmer_id = @amanda_farmer_id
AND p.name IN
(
    'Whole Wheat Flour',
    'Local Wheat',
    'Homemade Tomato Sauce',
    'Mixed Vegetable Pickles',
    'Homemade Strawberry Jam',
    'Fresh Chicken',
    'Fresh Goat Meat'
)
AND NOT EXISTS
(
    SELECT 1
    FROM weekly_stock_templates w
    WHERE w.farmer_id = p.farmer_id
    AND w.product_id = p.id
);


-- ============================================================
-- 5. AMANDA'S WEEKLY STOCK
-- ============================================================
--
-- Creates:
--   current week
--   +11 future weeks
--
-- The current week's actual quantity is populated.
-- Future weeks have planned quantities but actual_quantity = 0.
--
-- Dates are calculated dynamically from CURDATE().
-- ============================================================


INSERT INTO weekly_stock
(
    farmer_id,
    product_id,
    week_start,
    planned_quantity,
    actual_quantity,
    status,
    is_active
)
SELECT
    p.farmer_id,
    p.id,

    DATE_ADD(
        DATE_SUB(
            CURDATE(),
            INTERVAL WEEKDAY(CURDATE()) DAY
        ),
        INTERVAL weeks.week_number WEEK
    ),

    CASE p.name
        WHEN 'Whole Wheat Flour' THEN 40.00
        WHEN 'Local Wheat' THEN 35.00
        WHEN 'Homemade Tomato Sauce' THEN 25.00
        WHEN 'Mixed Vegetable Pickles' THEN 20.00
        WHEN 'Homemade Strawberry Jam' THEN 18.00
        WHEN 'Fresh Chicken' THEN 35.00
        WHEN 'Fresh Goat Meat' THEN 20.00
    END,

    CASE
        WHEN weeks.week_number = 0 THEN
            CASE p.name
                WHEN 'Whole Wheat Flour' THEN 40.00
                WHEN 'Local Wheat' THEN 35.00
                WHEN 'Homemade Tomato Sauce' THEN 25.00
                WHEN 'Mixed Vegetable Pickles' THEN 20.00
                WHEN 'Homemade Strawberry Jam' THEN 18.00
                WHEN 'Fresh Chicken' THEN 35.00
                WHEN 'Fresh Goat Meat' THEN 20.00
            END
        ELSE 0.00
    END,

    'available',
    1

FROM products p

CROSS JOIN
(
    SELECT 0 AS week_number
    UNION ALL SELECT 1
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
    UNION ALL SELECT 6
    UNION ALL SELECT 7
    UNION ALL SELECT 8
    UNION ALL SELECT 9
    UNION ALL SELECT 10
    UNION ALL SELECT 11
) weeks

WHERE p.farmer_id = @amanda_farmer_id

AND p.name IN
(
    'Whole Wheat Flour',
    'Local Wheat',
    'Homemade Tomato Sauce',
    'Mixed Vegetable Pickles',
    'Homemade Strawberry Jam',
    'Fresh Chicken',
    'Fresh Goat Meat'
)

AND NOT EXISTS
(
    SELECT 1
    FROM weekly_stock ws
    WHERE ws.product_id = p.id
    AND ws.week_start =
        DATE_ADD(
            DATE_SUB(
                CURDATE(),
                INTERVAL WEEKDAY(CURDATE()) DAY
            ),
            INTERVAL weeks.week_number WEEK
        )
);


-- ============================================================
-- 6. DANIEL FAVORITES
-- ============================================================
--
-- Existing:
--   favorite farmer  = 6
--   favorite market  = 2
--   favorite product = 1
--
-- We ADD Amanda to those existing favorites.
-- ============================================================


INSERT IGNORE INTO favorite_farmers
(
    customer_id,
    farmer_id
)
VALUES
(
    @daniel_user_id,
    @amanda_farmer_id
);


INSERT IGNORE INTO favorite_markets
(
    customer_id,
    market_id
)
VALUES
(
    @daniel_user_id,
    5
);


INSERT IGNORE INTO favorite_products
(
    customer_id,
    product_id
)
SELECT
    @daniel_user_id,
    p.id
FROM products p
WHERE p.farmer_id = @amanda_farmer_id
AND p.name = 'Whole Wheat Flour'
AND p.moderation_status = 'approved';


-- ============================================================
-- 7. COMPLETED DANIEL -> AMANDA ORDER
-- ============================================================
--
-- Amanda:
--   farmer_id = 7
--
-- North Hills:
--   market_id = 5
--
-- Amanda's existing pickup slot:
--   pickup_slot_id = 10
--
-- Previous Saturday:
--   current week's Monday - 2 days
--
-- Items:
--   2 kg Whole Wheat Flour       = 11.98
--   1 jar Homemade Tomato Sauce  =  6.49
--   1 jar Homemade Strawberry Jam=  7.49
--
-- TOTAL = 25.96
-- ============================================================


INSERT INTO orders
(
    customer_id,
    farmer_id,
    market_id,
    pickup_slot_id,
    pickup_date,
    status,
    subtotal,
    notes
)
SELECT
    @daniel_user_id,
    @amanda_farmer_id,
    5,
    10,

    DATE_SUB(
        DATE_SUB(
            CURDATE(),
            INTERVAL WEEKDAY(CURDATE()) DAY
        ),
        INTERVAL 2 DAY
    ),

    'completed',
    25.96,
    'Please have the order ready for pickup.'

WHERE NOT EXISTS
(
    SELECT 1
    FROM orders o
    WHERE o.customer_id = @daniel_user_id
    AND o.farmer_id = @amanda_farmer_id
    AND o.pickup_slot_id = 10
    AND o.pickup_date =
        DATE_SUB(
            DATE_SUB(
                CURDATE(),
                INTERVAL WEEKDAY(CURDATE()) DAY
            ),
            INTERVAL 2 DAY
        )
    AND o.notes = 'Please have the order ready for pickup.'
);


-- Retrieve the actual generated order ID safely.
SELECT id
INTO @completed_amanda_order_id
FROM orders
WHERE customer_id = @daniel_user_id
AND farmer_id = @amanda_farmer_id
AND pickup_slot_id = 10
AND pickup_date =
    DATE_SUB(
        DATE_SUB(
            CURDATE(),
            INTERVAL WEEKDAY(CURDATE()) DAY
        ),
        INTERVAL 2 DAY
    )
AND notes = 'Please have the order ready for pickup.'
ORDER BY id DESC
LIMIT 1;


-- ============================================================
-- 8. COMPLETED ORDER ITEMS
-- ============================================================


INSERT INTO order_items
(
    order_id,
    product_id,
    quantity,
    unit_price,
    subtotal
)
SELECT
    @completed_amanda_order_id,
    p.id,
    2.00,
    p.price,
    11.98
FROM products p
WHERE p.farmer_id = @amanda_farmer_id
AND p.name = 'Whole Wheat Flour'
AND NOT EXISTS
(
    SELECT 1
    FROM order_items oi
    WHERE oi.order_id = @completed_amanda_order_id
    AND oi.product_id = p.id
);


INSERT INTO order_items
(
    order_id,
    product_id,
    quantity,
    unit_price,
    subtotal
)
SELECT
    @completed_amanda_order_id,
    p.id,
    1.00,
    p.price,
    6.49
FROM products p
WHERE p.farmer_id = @amanda_farmer_id
AND p.name = 'Homemade Tomato Sauce'
AND NOT EXISTS
(
    SELECT 1
    FROM order_items oi
    WHERE oi.order_id = @completed_amanda_order_id
    AND oi.product_id = p.id
);


INSERT INTO order_items
(
    order_id,
    product_id,
    quantity,
    unit_price,
    subtotal
)
SELECT
    @completed_amanda_order_id,
    p.id,
    1.00,
    p.price,
    7.49
FROM products p
WHERE p.farmer_id = @amanda_farmer_id
AND p.name = 'Homemade Strawberry Jam'
AND NOT EXISTS
(
    SELECT 1
    FROM order_items oi
    WHERE oi.order_id = @completed_amanda_order_id
    AND oi.product_id = p.id
);


-- ============================================================
-- 9. COMPLETED ORDER STATUS HISTORY
-- ============================================================


INSERT INTO order_status_history
(
    order_id,
    status,
    changed_by
)
SELECT
    @completed_amanda_order_id,
    'pending',
    @daniel_user_id
WHERE NOT EXISTS
(
    SELECT 1
    FROM order_status_history
    WHERE order_id = @completed_amanda_order_id
    AND status = 'pending'
);


INSERT INTO order_status_history
(
    order_id,
    status,
    changed_by
)
SELECT
    @completed_amanda_order_id,
    'accepted',
    @amanda_user_id
WHERE NOT EXISTS
(
    SELECT 1
    FROM order_status_history
    WHERE order_id = @completed_amanda_order_id
    AND status = 'accepted'
);


INSERT INTO order_status_history
(
    order_id,
    status,
    changed_by
)
SELECT
    @completed_amanda_order_id,
    'preparing',
    @amanda_user_id
WHERE NOT EXISTS
(
    SELECT 1
    FROM order_status_history
    WHERE order_id = @completed_amanda_order_id
    AND status = 'preparing'
);


INSERT INTO order_status_history
(
    order_id,
    status,
    changed_by
)
SELECT
    @completed_amanda_order_id,
    'ready',
    @amanda_user_id
WHERE NOT EXISTS
(
    SELECT 1
    FROM order_status_history
    WHERE order_id = @completed_amanda_order_id
    AND status = 'ready'
);


INSERT INTO order_status_history
(
    order_id,
    status,
    changed_by
)
SELECT
    @completed_amanda_order_id,
    'completed',
    @amanda_user_id
WHERE NOT EXISTS
(
    SELECT 1
    FROM order_status_history
    WHERE order_id = @completed_amanda_order_id
    AND status = 'completed'
);


-- ============================================================
-- 10. PENDING DANIEL -> AMANDA ORDER
-- ============================================================
--
-- Next Saturday:
--
--   1 kg Local Wheat          =  4.49
--   2 jars Mixed Pickles      = 10.98
--   1 kg Fresh Chicken        =  8.99
--
-- TOTAL = 24.46
-- ============================================================


INSERT INTO orders
(
    customer_id,
    farmer_id,
    market_id,
    pickup_slot_id,
    pickup_date,
    status,
    subtotal,
    notes
)
SELECT
    @daniel_user_id,
    @amanda_farmer_id,
    5,
    10,

    DATE_ADD(
        DATE_SUB(
            CURDATE(),
            INTERVAL WEEKDAY(CURDATE()) DAY
        ),
        INTERVAL 5 DAY
    ),

    'pending',
    24.46,
    'Please confirm the order when possible.'

WHERE NOT EXISTS
(
    SELECT 1
    FROM orders o
    WHERE o.customer_id = @daniel_user_id
    AND o.farmer_id = @amanda_farmer_id
    AND o.pickup_slot_id = 10
    AND o.pickup_date =
        DATE_ADD(
            DATE_SUB(
                CURDATE(),
                INTERVAL WEEKDAY(CURDATE()) DAY
            ),
            INTERVAL 5 DAY
        )
    AND o.notes = 'Please confirm the order when possible.'
);


SELECT id
INTO @pending_amanda_order_id
FROM orders
WHERE customer_id = @daniel_user_id
AND farmer_id = @amanda_farmer_id
AND pickup_slot_id = 10
AND pickup_date =
    DATE_ADD(
        DATE_SUB(
            CURDATE(),
            INTERVAL WEEKDAY(CURDATE()) DAY
        ),
        INTERVAL 5 DAY
    )
AND notes = 'Please confirm the order when possible.'
ORDER BY id DESC
LIMIT 1;


-- ============================================================
-- 11. PENDING ORDER ITEMS
-- ============================================================


INSERT INTO order_items
(
    order_id,
    product_id,
    quantity,
    unit_price,
    subtotal
)
SELECT
    @pending_amanda_order_id,
    p.id,
    1.00,
    p.price,
    4.49
FROM products p
WHERE p.farmer_id = @amanda_farmer_id
AND p.name = 'Local Wheat'
AND NOT EXISTS
(
    SELECT 1
    FROM order_items oi
    WHERE oi.order_id = @pending_amanda_order_id
    AND oi.product_id = p.id
);


INSERT INTO order_items
(
    order_id,
    product_id,
    quantity,
    unit_price,
    subtotal
)
SELECT
    @pending_amanda_order_id,
    p.id,
    2.00,
    p.price,
    10.98
FROM products p
WHERE p.farmer_id = @amanda_farmer_id
AND p.name = 'Mixed Vegetable Pickles'
AND NOT EXISTS
(
    SELECT 1
    FROM order_items oi
    WHERE oi.order_id = @pending_amanda_order_id
    AND oi.product_id = p.id
);


INSERT INTO order_items
(
    order_id,
    product_id,
    quantity,
    unit_price,
    subtotal
)
SELECT
    @pending_amanda_order_id,
    p.id,
    1.00,
    p.price,
    8.99
FROM products p
WHERE p.farmer_id = @amanda_farmer_id
AND p.name = 'Fresh Chicken'
AND NOT EXISTS
(
    SELECT 1
    FROM order_items oi
    WHERE oi.order_id = @pending_amanda_order_id
    AND oi.product_id = p.id
);


INSERT INTO order_status_history
(
    order_id,
    status,
    changed_by
)
SELECT
    @pending_amanda_order_id,
    'pending',
    @daniel_user_id
WHERE NOT EXISTS
(
    SELECT 1
    FROM order_status_history
    WHERE order_id = @pending_amanda_order_id
    AND status = 'pending'
);


-- ============================================================
-- 12. DANIEL'S REVIEW
-- ============================================================


INSERT INTO reviews
(
    customer_id,
    farmer_id,
    product_id,
    order_id,
    rating,
    comment,
    status
)
SELECT
    @daniel_user_id,
    @amanda_farmer_id,
    p.id,
    @completed_amanda_order_id,
    5,
    'Fresh, well prepared, and exactly as described. I would definitely order from this farm again.',
    'approved'
FROM products p
WHERE p.farmer_id = @amanda_farmer_id
AND p.name = 'Whole Wheat Flour'
AND NOT EXISTS
(
    SELECT 1
    FROM reviews r
    WHERE r.customer_id = @daniel_user_id
    AND r.order_id = @completed_amanda_order_id
    AND r.product_id = p.id
);



UPDATE reviews r
INNER JOIN products p
    ON p.id = r.product_id
SET
    r.farmer_response =
        'Thank you for your kind review. We are glad you enjoyed the flour and look forward to serving you again.',
    r.farmer_response_at = NOW()
WHERE r.customer_id = @daniel_user_id
AND r.farmer_id = @amanda_farmer_id
AND r.order_id = @completed_amanda_order_id
AND p.name = 'Whole Wheat Flour'
AND r.status = 'approved';


INSERT INTO notifications
(
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    @daniel_user_id,
    'order',
    'Order completed',
    'Your order from Prairie Table Farm has been completed and is available in your order history.',
    0
WHERE NOT EXISTS
(
    SELECT 1
    FROM notifications
    WHERE user_id = @daniel_user_id
    AND title = 'Order completed'
);


INSERT INTO notifications
(
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    @daniel_user_id,
    'order',
    'Order submitted',
    'Your new order from Prairie Table Farm has been submitted and is awaiting confirmation.',
    0
WHERE NOT EXISTS
(
    SELECT 1
    FROM notifications
    WHERE user_id = @daniel_user_id
    AND title = 'Order submitted'
);


INSERT INTO notifications
(
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    @daniel_user_id,
    'review',
    'Review published',
    'Your review for Whole Wheat Flour has been approved and published.',
    1
WHERE NOT EXISTS
(
    SELECT 1
    FROM notifications
    WHERE user_id = @daniel_user_id
    AND title = 'Review published'
);


-- ============================================================
-- 15. AMANDA NOTIFICATIONS
-- ============================================================


INSERT INTO notifications
(
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    @amanda_user_id,
    'order',
    'New order received',
    'Daniel Brooks has placed a new order for pickup at North Hills Farmers Market.',
    0
WHERE NOT EXISTS
(
    SELECT 1
    FROM notifications
    WHERE user_id = @amanda_user_id
    AND title = 'New order received'
);


INSERT INTO notifications
(
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    @amanda_user_id,
    'review',
    'New customer review',
    'Daniel Brooks left a 5-star review for Whole Wheat Flour.',
    0
WHERE NOT EXISTS
(
    SELECT 1
    FROM notifications
    WHERE user_id = @amanda_user_id
    AND title = 'New customer review'
);


INSERT INTO notifications
(
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    @amanda_user_id,
    'stock',
    'Weekly stock is ready',
    'Your weekly stock schedule has been prepared from your active product templates.',
    1
WHERE NOT EXISTS
(
    SELECT 1
    FROM notifications
    WHERE user_id = @amanda_user_id
    AND title = 'Weekly stock is ready'
);


-- ============================================================
-- 16. MARCUS ADMIN NOTIFICATIONS
-- ============================================================


INSERT INTO notifications
(
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    @marcus_user_id,
    'farmer',
    'Farmer activity update',
    'Prairie Table Farm is an approved farmer currently selling through North Hills Farmers Market.',
    0
WHERE NOT EXISTS
(
    SELECT 1
    FROM notifications
    WHERE user_id = @marcus_user_id
    AND title = 'Farmer activity update'
);


INSERT INTO notifications
(
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    @marcus_user_id,
    'product',
    'New products added',
    'Prairie Table Farm now has additional approved products available in the catalog.',
    1
WHERE NOT EXISTS
(
    SELECT 1
    FROM notifications
    WHERE user_id = @marcus_user_id
    AND title = 'New products added'
);


INSERT INTO notifications
(
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    @marcus_user_id,
    'system',
    'MarketLink activity',
    'Recent customer orders, reviews, and weekly stock activity are available in the administration dashboard.',
    0
WHERE NOT EXISTS
(
    SELECT 1
    FROM notifications
    WHERE user_id = @marcus_user_id
    AND title = 'MarketLink activity'
);


INSERT INTO announcements
(
    admin_id,
    title,
    message,
    status,
    expires_at
)
SELECT
    @marcus_user_id,
    'Welcome to the New Market Week',
    'Customers can browse fresh local products and arrange convenient market pickups through MarketLink.',
    'published',
    DATE_ADD(NOW(), INTERVAL 30 DAY)
WHERE NOT EXISTS
(
    SELECT 1
    FROM announcements
    WHERE admin_id = @marcus_user_id
    AND title = 'Welcome to the New Market Week'
);


INSERT INTO announcements
(
    admin_id,
    title,
    message,
    status,
    expires_at
)
SELECT
    @marcus_user_id,
    'Weekly Stock Planning Reminder',
    'Farmers are encouraged to keep their weekly stock quantities updated so customers can plan their market pickups.',
    'draft',
    NULL
WHERE NOT EXISTS
(
    SELECT 1
    FROM announcements
    WHERE admin_id = @marcus_user_id
    AND title = 'Weekly Stock Planning Reminder'
);


INSERT INTO announcements
(
    admin_id,
    title,
    message,
    status,
    expires_at
)
SELECT
    @marcus_user_id,
    'MarketLink Launch Notice',
    'MarketLink connects customers with local farmers and participating community markets.',
    'archived',
    DATE_SUB(NOW(), INTERVAL 30 DAY)
WHERE NOT EXISTS
(
    SELECT 1
    FROM announcements
    WHERE admin_id = @marcus_user_id
    AND title = 'MarketLink Launch Notice'
);


INSERT INTO reports
(
    generated_by,
    report_type
)
SELECT
    @marcus_user_id,
    'sales'
WHERE NOT EXISTS
(
    SELECT 1
    FROM reports
    WHERE generated_by = @marcus_user_id
    AND report_type = 'sales'
);


INSERT INTO reports
(
    generated_by,
    report_type
)
SELECT
    @marcus_user_id,
    'orders'
WHERE NOT EXISTS
(
    SELECT 1
    FROM reports
    WHERE generated_by = @marcus_user_id
    AND report_type = 'orders'
);


INSERT INTO reports
(
    generated_by,
    report_type
)
SELECT
    @marcus_user_id,
    'farmers'
WHERE NOT EXISTS
(
    SELECT 1
    FROM reports
    WHERE generated_by = @marcus_user_id
    AND report_type = 'farmers'
);


INSERT INTO reports
(
    generated_by,
    report_type
)
SELECT
    @marcus_user_id,
    'products'
WHERE NOT EXISTS
(
    SELECT 1
    FROM reports
    WHERE generated_by = @marcus_user_id
    AND report_type = 'products'
);

COMMIT;

