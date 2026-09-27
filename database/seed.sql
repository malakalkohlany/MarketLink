-- ============================================================
-- MarketLink Seed Data
-- ============================================================
-- All users use the SAME password hash:
-- $2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W
--
-- Example plaintext password associated with this hash:
-- Use the password you originally generated this hash for.
--
-- IMPORTANT:
-- This file assumes the MarketLink schema already exists.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- Delete dependent records first
DELETE FROM order_status_history;
DELETE FROM reviews;
DELETE FROM order_items;
DELETE FROM orders;

DELETE FROM weekly_stock;
DELETE FROM pickup_slots;

DELETE FROM favorite_products;
DELETE FROM favorite_markets;
DELETE FROM favorite_farmers;

DELETE FROM market_farmer;

DELETE FROM products;
DELETE FROM notifications;
DELETE FROM announcements;
DELETE FROM reports;

DELETE FROM farmers;
DELETE FROM markets;
DELETE FROM categories;
DELETE FROM users;

-- Reset AUTO_INCREMENT values
ALTER TABLE order_status_history AUTO_INCREMENT = 1;
ALTER TABLE reviews AUTO_INCREMENT = 1;
ALTER TABLE order_items AUTO_INCREMENT = 1;
ALTER TABLE orders AUTO_INCREMENT = 1;
ALTER TABLE weekly_stock AUTO_INCREMENT = 1;
ALTER TABLE pickup_slots AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE announcements AUTO_INCREMENT = 1;
ALTER TABLE reports AUTO_INCREMENT = 1;
ALTER TABLE farmers AUTO_INCREMENT = 1;
ALTER TABLE markets AUTO_INCREMENT = 1;
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;


-- ============================================================
-- 1. USERS
-- ============================================================
-- Password hash is intentionally identical for ALL users.

INSERT INTO users
    (id, name, email, password_hash, phone, address, role, status)
VALUES
    (1, 'Alex Morgan', 'alex.morgan@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0101', 'Brooklyn, New York, USA', 'customer', 'active'),

    (2, 'Sophie Bennett', 'sophie.bennett@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0102', 'Cambridge, Massachusetts, USA', 'customer', 'active'),

    (3, 'Daniel Carter', 'daniel.carter@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0103', 'Austin, Texas, USA', 'customer', 'active'),

    (4, 'Emma Wilson', 'emma.wilson@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0104', 'Portland, Oregon, USA', 'customer', 'active'),

    (5, 'Oliver Thompson', 'oliver.thompson@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0105', 'Seattle, Washington, USA', 'farmer', 'active'),

    (6, 'Grace Anderson', 'grace.anderson@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0106', 'Denver, Colorado, USA', 'farmer', 'active'),

    (7, 'Liam Parker', 'liam.parker@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0107', 'Chicago, Illinois, USA', 'farmer', 'active'),

    (8, 'Mia Richardson', 'mia.richardson@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0108', 'San Diego, California, USA', 'farmer', 'active'),

    (9, 'James Mitchell', 'james.mitchell@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gc30AK.gDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0109', 'Boston, Massachusetts, USA', 'admin', 'active');


-- ============================================================
-- 2. FARMERS
-- ============================================================

INSERT INTO farmers
    (id, user_id, stall_name, contact_person, description,
     address, latitude, longitude, approval_status)
VALUES
    (1,
     5,
     'Morgan Valley Farms',
     'Oliver Thompson',
     'A small family farm specializing in seasonal vegetables, herbs, and fresh eggs.',
     'Seattle, Washington, USA',
     47.60620000, -122.33210000,
     'approved'),

    (2,
     6,
     'Green Meadow Organics',
     'Grace Anderson',
     'Organic produce grown using sustainable farming practices with a focus on seasonal crops.',
     'Denver, Colorado, USA',
     39.73920000, -104.99030000,
     'approved'),

    (3,
     7,
     'Parker Family Farm',
     'Liam Parker',
     'Family-owned farm producing fresh vegetables, fruit, honey, and artisan products.',
     'Chicago, Illinois, USA',
     41.87810000, -87.62980000,
     'approved'),

    (4,
     8,
     'Sunrise Harvest Co.',
     'Mia Richardson',
     'A coastal farm offering fresh seasonal produce, herbs, citrus, and natural honey.',
     'San Diego, California, USA',
     32.71570000, -117.16110000,
     'approved');


-- ============================================================
-- 2. CATEGORIES
-- ============================================================

INSERT INTO categories
    (id, name, description, image, status)
VALUES
    (
        1,
        'Vegetables',
        'Fresh seasonal vegetables grown by local farmers.',
        NULL,
        'active'
    ),
    (
        2,
        'Fruits',
        'Fresh locally grown seasonal fruits.',
        NULL,
        'active'
    ),
    (
        3,
        'Herbs',
        'Fresh culinary herbs and aromatic greens.',
        NULL,
        'active'
    ),
    (
        4,
        'Eggs & Dairy',
        'Fresh eggs and locally produced dairy products.',
        NULL,
        'active'
    ),
    (
        5,
        'Honey',
        'Natural honey and bee products from local producers.',
        NULL,
        'active'
    ),
    (
        6,
        'Leafy Greens',
        'Fresh lettuce, spinach, kale and other leafy greens.',
        NULL,
        'active'
    ),
    (
        7,
        'Root Vegetables',
        'Fresh potatoes, carrots, onions and other root crops.',
        NULL,
        'active'
    ),
    (
        8,
        'Pantry & Artisan',
        'Locally made preserves, sauces and artisan farm products.',
        NULL,
        'active'
    );


-- ============================================================
-- 3. MARKETS
-- ============================================================

INSERT INTO markets
    (
        id,
        name,
        description,
        address,
        latitude,
        longitude,
        opening_time,
        closing_time,
        operating_days,
        map_provider,
        status
    )
VALUES
    (
        1,
        'Riverside Farmers Market',
        'A lively community market featuring fresh produce, herbs, eggs and artisan farm products.',
        'Riverside Square, Seattle, Washington, USA',
        47.60620000,
        -122.33210000,
        '09:00:00',
        '17:00:00',
        'Wednesday,Saturday,Sunday',
        'OpenStreetMap',
        'active'
    ),
    (
        2,
        'Greenfield Market',
        'A neighborhood farmers market focused on fresh vegetables, fruit and seasonal produce.',
        'Greenfield Community Center, Denver, Colorado, USA',
        39.73920000,
        -104.99030000,
        '09:00:00',
        '17:00:00',
        'Friday,Saturday,Sunday',
        'OpenStreetMap',
        'active'
    ),
    (
        3,
        'Oak Street Market',
        'A friendly neighborhood market with local produce, honey and artisan goods.',
        'Oak Street Plaza, Chicago, Illinois, USA',
        41.87810000,
        -87.62980000,
        '10:00:00',
        '16:00:00',
        'Saturday,Sunday',
        'OpenStreetMap',
        'active'
    ),
    (
        4,
        'Harborview Market',
        'A coastal market featuring fresh vegetables, citrus, herbs and natural honey.',
        'Harborview Community Park, San Diego, California, USA',
        32.71570000,
        -117.16110000,
        '09:00:00',
        '16:00:00',
        'Saturday,Sunday',
        'OpenStreetMap',
        'active'
    );


-- ============================================================
-- 4. FARMER ↔ MARKET RELATIONSHIPS
-- ============================================================

INSERT INTO market_farmer
    (market_id, farmer_id)
VALUES
    -- Oliver / Morgan Valley
    (1, 1),

    -- Grace / Green Meadow
    (1, 2),
    (2, 2),

    -- Liam / Parker Family
    (2, 3),
    (3, 3),

    -- Mia / Sunrise Harvest
    (3, 4),
    (4, 4);


-- ============================================================
-- 5. PRODUCTS
-- ============================================================

INSERT INTO products
    (
        id,
        farmer_id,
        category_id,
        name,
        description,
        price,
        unit,
        stock_quantity,
        image,
        is_available,
        moderation_status,
        moderation_reason
    )
VALUES

    -- ========================================================
    -- FARMER 1 - MORGAN VALLEY FARMS
    -- ========================================================

    (
        1,
        1,
        1,
        'Vine Tomatoes',
        'Fresh ripe tomatoes harvested from Morgan Valley Farms.',
        3.50,
        'kg',
        100.00,
        NULL,
        1,
        'approved',
        NULL
    ),

    (
        2,
        1,
        6,
        'Baby Spinach',
        'Tender baby spinach leaves harvested fresh each week.',
        2.75,
        '250 g',
        80.00,
        NULL,
        1,
        'approved',
        NULL
    ),

    (
        3,
        1,
        3,
        'Fresh Basil',
        'Fragrant fresh basil suitable for salads, sauces and cooking.',
        2.25,
        'bunch',
        50.00,
        NULL,
        1,
        'approved',
        NULL
    ),

    (
        4,
        1,
        4,
        'Farm Fresh Eggs',
        'Fresh free-range eggs collected from the farm.',
        5.50,
        'dozen',
        60.00,
        NULL,
        1,
        'approved',
        NULL
    ),


    -- ========================================================
    -- FARMER 2 - GREEN MEADOW ORGANICS
    -- ========================================================

    (
        5,
        2,
        1,
        'Organic Carrots',
        'Crisp organic carrots grown without synthetic pesticides.',
        2.90,
        'kg',
        100.00,
        NULL,
        1,
        'approved',
        NULL
    ),

    (
        6,
        2,
        7,
        'Golden Potatoes',
        'Fresh golden potatoes ideal for roasting, baking and cooking.',
        2.40,
        'kg',
        120.00,
        NULL,
        1,
        'approved',
        NULL
    ),

    (
        7,
        2,
        6,
        'Crisp Lettuce',
        'Fresh crisp heads of locally grown lettuce.',
        2.20,
        'head',
        60.00,
        NULL,
        1,
        'approved',
        NULL
    ),


    -- ========================================================
    -- FARMER 3 - PARKER FAMILY FARM
    -- ========================================================

    (
        8,
        3,
        2,
        'Strawberries',
        'Sweet seasonal strawberries harvested at peak ripeness.',
        4.80,
        '500 g',
        70.00,
        NULL,
        1,
        'approved',
        NULL
    ),

    (
        9,
        3,
        5,
        'Wildflower Honey',
        'Natural honey collected from local wildflower fields.',
        8.50,
        'jar',
        40.00,
        NULL,
        1,
        'approved',
        NULL
    ),

    (
        10,
        3,
        1,
        'Sweet Bell Peppers',
        'Colorful sweet peppers with a crisp texture.',
        4.25,
        'kg',
        75.00,
        NULL,
        1,
        'approved',
        NULL
    ),


    -- ========================================================
    -- FARMER 4 - SUNRISE HARVEST
    -- ========================================================

    (
        11,
        4,
        2,
        'Fresh Oranges',
        'Juicy seasonal oranges grown in the coastal climate.',
        3.80,
        'kg',
        100.00,
        NULL,
        1,
        'approved',
        NULL
    ),

    (
        12,
        4,
        3,
        'Fresh Mint',
        'Aromatic fresh mint harvested throughout the growing season.',
        1.90,
        'bunch',
        60.00,
        NULL,
        1,
        'approved',
        NULL
    ),

    (
        13,
        4,
        5,
        'Coastal Wildflower Honey',
        'Natural local honey produced from coastal wildflowers.',
        9.00,
        'jar',
        35.00,
        NULL,
        1,
        'approved',
        NULL
    );


-- ============================================================
-- 6. WEEKLY STOCK TEMPLATES
--
-- These are recurring defaults.
-- They are NOT the actual current-week quantities.
-- ============================================================

INSERT INTO weekly_stock_templates
    (
        id,
        farmer_id,
        product_id,
        default_quantity,
        is_active
    )
VALUES

    -- Farmer 1
    (1, 1, 1, 60.00, 1),
    (2, 1, 2, 40.00, 1),
    (3, 1, 3, 25.00, 1),
    (4, 1, 4, 30.00, 1),

    -- Farmer 2
    (5, 2, 5, 50.00, 1),
    (6, 2, 6, 70.00, 1),
    (7, 2, 7, 35.00, 1),

    -- Farmer 3
    (8, 3, 8, 40.00, 1),
    (9, 3, 9, 20.00, 1),
    (10, 3, 10, 45.00, 1),

    -- Farmer 4
    (11, 4, 11, 60.00, 1),
    (12, 4, 12, 30.00, 1),
    (13, 4, 13, 20.00, 1);


-- ============================================================
-- 7. CURRENT WEEKLY STOCK
--
-- Current week:
-- 2026-09-21 -> 2026-09-27
--
-- Some quantities intentionally differ from the templates
-- so we can test farmer weekly adjustments.
-- ============================================================

INSERT INTO weekly_stock
    (
        id,
        farmer_id,
        product_id,
        week_start,
        planned_quantity,
        actual_quantity,
        status,
        is_active
    )
VALUES

    -- ========================================================
    -- FARMER 1
    -- ========================================================

    (
        1,
        1,
        1,
        '2026-09-21',
        60.00,
        60.00,
        'available',
        1
    ),

    (
        2,
        1,
        2,
        '2026-09-21',
        40.00,
        27.50,
        'available',
        1
    ),

    (
        3,
        1,
        3,
        '2026-09-21',
        25.00,
        0.00,
        'sold_out',
        1
    ),

    (
        4,
        1,
        4,
        '2026-09-21',
        30.00,
        30.00,
        'unavailable',
        1
    ),


    -- ========================================================
    -- FARMER 2
    -- ========================================================

    (
        5,
        2,
        5,
        '2026-09-21',
        50.00,
        50.00,
        'available',
        1
    ),

    (
        6,
        2,
        6,
        '2026-09-21',
        70.00,
        48.00,
        'available',
        1
    ),

    (
        7,
        2,
        7,
        '2026-09-21',
        35.00,
        0.00,
        'sold_out',
        1
    ),


    -- ========================================================
    -- FARMER 3
    -- ========================================================

    (
        8,
        3,
        8,
        '2026-09-21',
        40.00,
        32.00,
        'available',
        1
    ),

    (
        9,
        3,
        9,
        '2026-09-21',
        20.00,
        20.00,
        'available',
        1
    ),

    (
        10,
        3,
        10,
        '2026-09-21',
        45.00,
        45.00,
        'available',
        1
    ),


    -- ========================================================
    -- FARMER 4
    -- ========================================================

    (
        11,
        4,
        11,
        '2026-09-21',
        60.00,
        60.00,
        'available',
        1
    ),

    (
        12,
        4,
        12,
        '2026-09-21',
        30.00,
        18.00,
        'available',
        1
    ),

    (
        13,
        4,
        13,
        '2026-09-21',
        20.00,
        0.00,
        'sold_out',
        1
    );


-- ============================================================
-- 8. PICKUP SLOTS
--
-- IMPORTANT:
-- Today = Sunday, September 27, 2026.
--
-- These Sunday slots intentionally have FUTURE cutoff times
-- so you can test checkout right now.
--
-- Current local time is around 13:07.
-- ============================================================

INSERT INTO pickup_slots
    (
        id,
        farmer_id,
        market_id,
        day_of_week,
        start_time,
        end_time,
        cutoff_time,
        max_orders,
        is_available
    )
VALUES

    -- ========================================================
    -- FARMER 1 / RIVERSIDE MARKET
    -- ========================================================

    (
        1,
        1,
        1,
        'Sunday',
        '14:00:00',
        '16:00:00',
        '13:30:00',
        20,
        1
    ),

    (
        2,
        1,
        1,
        'Sunday',
        '16:30:00',
        '18:00:00',
        '16:00:00',
        20,
        1
    ),


    -- ========================================================
    -- FARMER 2 / RIVERSIDE MARKET
    -- ========================================================

    (
        3,
        2,
        1,
        'Sunday',
        '14:00:00',
        '16:00:00',
        '13:30:00',
        15,
        1
    ),

    (
        4,
        2,
        1,
        'Sunday',
        '16:30:00',
        '18:00:00',
        '16:00:00',
        15,
        1
    ),


    -- ========================================================
    -- FARMER 2 / GREENFIELD MARKET
    -- ========================================================

    (
        5,
        2,
        2,
        'Sunday',
        '14:30:00',
        '16:30:00',
        '14:00:00',
        15,
        1
    ),

    (
        6,
        2,
        2,
        'Sunday',
        '17:00:00',
        '18:30:00',
        '16:30:00',
        15,
        1
    ),


    -- ========================================================
    -- FARMER 3 / GREENFIELD MARKET
    -- ========================================================

    (
        7,
        3,
        2,
        'Sunday',
        '14:00:00',
        '16:00:00',
        '13:30:00',
        15,
        1
    ),

    (
        8,
        3,
        2,
        'Sunday',
        '16:30:00',
        '18:00:00',
        '16:00:00',
        15,
        1
    ),


    -- ========================================================
    -- FARMER 3 / OAK STREET MARKET
    -- ========================================================

    (
        9,
        3,
        3,
        'Sunday',
        '14:30:00',
        '16:30:00',
        '14:00:00',
        15,
        1
    ),

    (
        10,
        3,
        3,
        'Sunday',
        '17:00:00',
        '18:30:00',
        '16:30:00',
        15,
        1
    ),


    -- ========================================================
    -- FARMER 4 / OAK STREET MARKET
    -- ========================================================

    (
        11,
        4,
        3,
        'Sunday',
        '14:00:00',
        '16:00:00',
        '13:30:00',
        15,
        1
    ),

    (
        12,
        4,
        3,
        'Sunday',
        '16:30:00',
        '18:00:00',
        '16:00:00',
        15,
        1
    ),


    -- ========================================================
    -- FARMER 4 / HARBORVIEW MARKET
    -- ========================================================

    (
        13,
        4,
        4,
        'Sunday',
        '14:30:00',
        '16:30:00',
        '14:00:00',
        15,
        1
    ),

    (
        14,
        4,
        4,
        'Sunday',
        '17:00:00',
        '18:30:00',
        '16:30:00',
        15,
        1
    );


-- ============================================================
-- 9. FAVORITES
-- ============================================================

INSERT INTO favorite_farmers
    (customer_id, farmer_id)
VALUES
    (1, 1),
    (1, 2),
    (2, 2),
    (2, 3),
    (3, 3),
    (4, 4);


INSERT INTO favorite_markets
    (customer_id, market_id)
VALUES
    (1, 1),
    (2, 2),
    (3, 3),
    (4, 4);


INSERT INTO favorite_products
    (customer_id, product_id)
VALUES
    (1, 1),
    (1, 4),
    (2, 5),
    (2, 8),
    (3, 9),
    (4, 11);


-- ============================================================
-- 10. NOTIFICATIONS
-- ============================================================

INSERT INTO notifications
    (
        id,
        user_id,
        type,
        title,
        message,
        is_read
    )
VALUES
    (
        1,
        1,
        'weekly_stock_updated',
        'Weekly Stock Updated',
        'Morgan Valley Farms has updated their stock for this week.',
        0
    ),
    (
        2,
        1,
        'announcement',
        'Welcome to MarketLink',
        'Discover fresh products from local farmers and shop your weekly market.',
        1
    ),
    (
        3,
        2,
        'weekly_stock_updated',
        'Weekly Stock Updated',
        'Green Meadow Organics has updated their stock for this week.',
        0
    ),
    (
        4,
        3,
        'weekly_stock_updated',
        'Weekly Stock Updated',
        'Parker Family Farm has updated their stock for this week.',
        0
    ),
    (
        5,
        4,
        'weekly_stock_updated',
        'Weekly Stock Updated',
        'Sunrise Harvest Co. has updated their stock for this week.',
        1
    ),
    (
        6,
        5,
        'weekly_stock_reminder',
        'Weekly Stock Reminder',
        'It is a new week! Please review and update your weekly stock.',
        0
    ),
    (
        7,
        6,
        'weekly_stock_reminder',
        'Weekly Stock Reminder',
        'It is a new week! Please review and update your weekly stock.',
        0
    ),
    (
        8,
        7,
        'weekly_stock_reminder',
        'Weekly Stock Reminder',
        'It is a new week! Please review and update your weekly stock.',
        1
    ),
    (
        9,
        8,
        'weekly_stock_reminder',
        'Weekly Stock Reminder',
        'It is a new week! Please review and update your weekly stock.',
        0
    );


-- ============================================================
-- 11. ANNOUNCEMENTS
-- ============================================================

INSERT INTO announcements
    (
        id,
        admin_id,
        title,
        message,
        status,
        expires_at
    )
VALUES
    (
        1,
        9,
        'Welcome to MarketLink',
        'MarketLink connects customers with local farmers and their weekly fresh stock.',
        'published',
        '2026-12-31 23:59:59'
    ),
    (
        2,
        9,
        'Weekly Stock Is Now Available',
        'Farmers have updated their stock for the current week. Browse products and plan your pickup.',
        'published',
        '2026-10-04 23:59:59'
    ),
    (
        3,
        9,
        'MarketLink Test Announcement',
        'This is a development announcement for testing the notification and announcement system.',
        'draft',
        NULL
    );


-- ============================================================
-- 12. RESET AUTO-INCREMENT VALUES
-- ============================================================

ALTER TABLE categories
    AUTO_INCREMENT = 9;

ALTER TABLE markets
    AUTO_INCREMENT = 5;

ALTER TABLE products
    AUTO_INCREMENT = 14;

ALTER TABLE weekly_stock_templates
    AUTO_INCREMENT = 14;

ALTER TABLE weekly_stock
    AUTO_INCREMENT = 14;

ALTER TABLE pickup_slots
    AUTO_INCREMENT = 15;

ALTER TABLE notifications
    AUTO_INCREMENT = 10;

ALTER TABLE announcements
    AUTO_INCREMENT = 4;


-- ============================================================
-- FINISH
-- ============================================================

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;