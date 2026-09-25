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
     '$2y$10$GBCQn0E9tKL/wy6gcDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0105', 'Seattle, Washington, USA', 'farmer', 'active'),

    (6, 'Grace Anderson', 'grace.anderson@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gcDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0106', 'Denver, Colorado, USA', 'farmer', 'active'),

    (7, 'Liam Parker', 'liam.parker@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gcDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0107', 'Chicago, Illinois, USA', 'farmer', 'active'),

    (8, 'Mia Richardson', 'mia.richardson@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gcDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0108', 'San Diego, California, USA', 'farmer', 'active'),

    (9, 'James Mitchell', 'james.mitchell@example.com',
     '$2y$10$GBCQn0E9tKL/wy6gcDRyxDANNzWTpetmguhxMU6GyYHhl6W',
     '+1-202-555-0109', 'Boston, Massachusetts, USA', 'admin', 'active');


-- ============================================================
-- 2. CATEGORIES
-- ============================================================

INSERT INTO categories
    (id, name, description, image, status)
VALUES
    (1, 'Vegetables',
     'Fresh locally grown vegetables including leafy greens, roots, and seasonal produce.',
     'vegetables.jpg', 'active'),

    (2, 'Fruits',
     'Fresh seasonal fruits sourced directly from local growers.',
     'fruits.jpg', 'active'),

    (3, 'Herbs',
     'Fresh culinary herbs grown and harvested by local farmers.',
     'herbs.jpg', 'active'),

    (4, 'Dairy',
     'Fresh dairy products from independent local producers.',
     'dairy.jpg', 'active'),

    (5, 'Eggs',
     'Fresh farm eggs from local producers.',
     'eggs.jpg', 'active'),

    (6, 'Honey',
     'Natural honey produced by local beekeepers.',
     'honey.jpg', 'active'),

    (7, 'Bakery',
     'Fresh bread and baked goods from independent producers.',
     'bakery.jpg', 'active'),

    (8, 'Organic Produce',
     'Certified and naturally grown organic produce.',
     'organic.jpg', 'active');


-- ============================================================
-- 3. MARKETS
-- ============================================================

INSERT INTO markets
    (id, name, description, address, latitude, longitude,
     opening_time, closing_time, operating_days, map_provider, status)
VALUES
    (1,
     'Riverside Farmers Market',
     'A weekend farmers market featuring fresh produce, dairy, eggs, honey, and artisan products.',
     'Riverside Park, New York, NY, USA',
     40.80070000, -73.97070000,
     '08:00:00', '14:00:00',
     'Saturday,Sunday',
     'OpenStreetMap',
     'active'),

    (2,
     'Greenfield Community Market',
     'A neighborhood market connecting shoppers with independent local growers and producers.',
     'Greenfield Avenue, Portland, OR, USA',
     45.52310000, -122.67650000,
     '09:00:00', '15:00:00',
     'Saturday',
     'OpenStreetMap',
     'active'),

    (3,
     'Central Harvest Market',
     'A large community market offering seasonal produce and locally produced food.',
     'Market Street, Chicago, IL, USA',
     41.88370000, -87.63240000,
     '08:00:00', '15:00:00',
     'Saturday,Sunday',
     'OpenStreetMap',
     'active'),

    (4,
     'Sunrise Organic Market',
     'A market focused on organic produce, natural products, and sustainable farming.',
     'Harbor Drive, San Diego, CA, USA',
     32.71570000, -117.16110000,
     '08:00:00', '14:00:00',
     'Sunday',
     'OpenStreetMap',
     'active');


-- ============================================================
-- 4. FARMERS
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
-- 5. MARKET-FARMER RELATIONSHIPS
-- ============================================================

INSERT INTO market_farmer
    (market_id, farmer_id)
VALUES
    (1, 1),
    (1, 2),
    (1, 3),

    (2, 1),
    (2, 2),

    (3, 2),
    (3, 3),
    (3, 4),

    (4, 3),
    (4, 4);


-- ============================================================
-- 6. PRODUCTS
-- ============================================================

INSERT INTO products
    (id, farmer_id, category_id, name, description, price, unit,
     stock_quantity, image, is_available, moderation_status, moderation_reason)
VALUES
    (1,
     1, 1,
     'Fresh Tomatoes',
     'Vine-ripened tomatoes harvested at peak freshness.',
     3.50, 'kg',
     45.00,
     'tomatoes.jpg',
     1, 'approved', NULL),

    (2,
     1, 1,
     'Baby Spinach',
     'Freshly harvested baby spinach leaves.',
     4.25, '500g',
     30.00,
     'spinach.jpg',
     1, 'approved', NULL),

    (3,
     1, 3,
     'Fresh Basil',
     'Aromatic fresh basil suitable for cooking and salads.',
     2.50, 'bunch',
     40.00,
     'basil.jpg',
     1, 'approved', NULL),

    (4,
     1, 5,
     'Farm Fresh Eggs',
     'Fresh free-range eggs collected from the farm.',
     5.75, 'dozen',
     60.00,
     'eggs.jpg',
     1, 'approved', NULL),

    (5,
     2, 1,
     'Organic Carrots',
     'Organic carrots grown without synthetic pesticides.',
     3.75, 'kg',
     50.00,
     'carrots.jpg',
     1, 'approved', NULL),

    (6,
     2, 1,
     'Organic Potatoes',
     'Fresh organic potatoes suitable for roasting, baking, and cooking.',
     3.25, 'kg',
     70.00,
     'potatoes.jpg',
     1, 'approved', NULL),

    (7,
     2, 2,
     'Strawberries',
     'Sweet seasonal strawberries picked fresh from the farm.',
     6.50, '500g',
     35.00,
     'strawberries.jpg',
     1, 'approved', NULL),

    (8,
     2, 8,
     'Organic Mixed Greens',
     'A seasonal mix of fresh organic salad greens.',
     5.25, '500g',
     25.00,
     'mixed-greens.jpg',
     1, 'approved', NULL),

    (9,
     3, 2,
     'Honeycrisp Apples',
     'Crisp and naturally sweet seasonal apples.',
     4.50, 'kg',
     45.00,
     'apples.jpg',
     1, 'approved', NULL),

    (10,
     3, 6,
     'Wildflower Honey',
     'Naturally produced honey collected from diverse wildflowers.',
     9.50, '500g',
     25.00,
     'honey.jpg',
     1, 'approved', NULL),

    (11,
     3, 1,
     'Sweet Bell Peppers',
     'Colorful fresh bell peppers with a naturally sweet flavor.',
     5.25, 'kg',
     35.00,
     'peppers.jpg',
     1, 'approved', NULL),

    (12,
     4, 2,
     'Fresh Oranges',
     'Juicy seasonal oranges harvested from the farm.',
     4.75, 'kg',
     55.00,
     'oranges.jpg',
     1, 'approved', NULL),

    (13,
     4, 3,
     'Fresh Rosemary',
     'Fragrant rosemary harvested fresh for culinary use.',
     2.75, 'bunch',
     30.00,
     'rosemary.jpg',
     1, 'approved', NULL),

    (14,
     4, 8,
     'Organic Lettuce',
     'Crisp organic lettuce harvested fresh.',
     2.95, 'head',
     40.00,
     'lettuce.jpg',
     1, 'approved', NULL),

    (15,
     4, 6,
     'Clover Honey',
     'Smooth natural honey with a mild floral flavor.',
     8.75, '500g',
     20.00,
     'clover-honey.jpg',
     1, 'approved', NULL),

    (16,
     2, 1,
     'Organic Cucumbers',
     'Fresh crisp cucumbers grown organically.',
     3.95, 'kg',
     40.00,
     'cucumbers.jpg',
     1, 'approved', NULL);


-- ============================================================
-- 7. PICKUP SLOTS
-- ============================================================

INSERT INTO pickup_slots
    (id, farmer_id, market_id, day_of_week,
     start_time, end_time, cutoff_time, max_orders, is_available)
VALUES
    (1, 1, 1, 'Saturday',
     '09:00:00', '10:00:00', '08:00:00', 10, 1),

    (2, 1, 1, 'Saturday',
     '10:00:00', '11:00:00', '09:00:00', 10, 1),

    (3, 1, 2, 'Saturday',
     '11:00:00', '12:00:00', '10:00:00', 8, 1),

    (4, 2, 1, 'Sunday',
     '09:00:00', '10:00:00', '08:00:00', 12, 1),

    (5, 2, 3, 'Saturday',
     '10:00:00', '11:00:00', '09:00:00', 12, 1),

    (6, 2, 3, 'Sunday',
     '11:00:00', '12:00:00', '10:00:00', 10, 1),

    (7, 3, 3, 'Saturday',
     '09:00:00', '10:00:00', '08:00:00', 10, 1),

    (8, 3, 4, 'Sunday',
     '09:00:00', '10:00:00', '08:00:00', 8, 1),

    (9, 4, 4, 'Sunday',
     '10:00:00', '11:00:00', '09:00:00', 10, 1),

    (10, 4, 3, 'Sunday',
     '12:00:00', '13:00:00', '11:00:00', 10, 1);


-- ============================================================
-- 8. FAVORITE FARMERS
-- ============================================================

INSERT INTO favorite_farmers
    (customer_id, farmer_id)
VALUES
    (1, 1),
    (1, 2),
    (2, 2),
    (2, 4),
    (3, 3),
    (4, 1),
    (4, 4);


-- ============================================================
-- 9. FAVORITE MARKETS
-- ============================================================

INSERT INTO favorite_markets
    (customer_id, market_id)
VALUES
    (1, 1),
    (1, 3),
    (2, 2),
    (3, 3),
    (3, 4),
    (4, 1),
    (4, 4);


-- ============================================================
-- 10. FAVORITE PRODUCTS
-- ============================================================

INSERT INTO favorite_products
    (customer_id, product_id)
VALUES
    (1, 1),
    (1, 4),
    (1, 10),
    (2, 5),
    (2, 7),
    (2, 12),
    (3, 9),
    (3, 11),
    (4, 2),
    (4, 14),
    (4, 15);


-- ============================================================
-- 11. ORDERS
-- ============================================================
-- Orders are distributed across customers, farmers and markets.
-- Completed orders are used for review data.

INSERT INTO orders
    (id, customer_id, farmer_id, market_id, pickup_slot_id,
     status, subtotal, notes, created_at)
VALUES
    (1,
     1, 1, 1, 1,
     'completed', 14.50,
     'Please pack the vegetables separately.',
     '2026-09-18 09:15:00'),

    (2,
     2, 2, 3, 5,
     'completed', 19.25,
     'Please use paper bags if available.',
     '2026-09-19 10:30:00'),

    (3,
     3, 3, 3, 7,
     'ready', 18.50,
     'I will collect the order during the selected slot.',
     '2026-09-23 11:00:00'),

    (4,
     4, 4, 4, 9,
     'preparing', 17.45,
     'Please keep the honey upright.',
     '2026-09-24 14:20:00'),

    (5,
     1, 2, 1, 4,
     'accepted', 13.75,
     'Thank you!',
     '2026-09-24 16:10:00'),

    (6,
     2, 1, 2, 3,
     'pending', 10.25,
     'Fresh produce preferred.',
     '2026-09-25 08:30:00');


-- ============================================================
-- 12. ORDER ITEMS
-- ============================================================

INSERT INTO order_items
    (id, order_id, product_id, quantity, unit_price, subtotal)
VALUES
    -- Order 1
    (1, 1, 1, 2.00, 3.50, 7.00),
    (2, 1, 3, 1.00, 2.50, 2.50),
    (3, 1, 4, 1.00, 5.00, 5.00),

    -- Order 2
    (4, 2, 5, 2.00, 3.75, 7.50),
    (5, 2, 7, 1.00, 6.50, 6.50),
    (6, 2, 8, 1.00, 5.25, 5.25),

    -- Order 3
    (7, 3, 9, 2.00, 4.50, 9.00),
    (8, 3, 10, 1.00, 9.50, 9.50),

    -- Order 4
    (9, 4, 12, 1.00, 4.75, 4.75),
    (10, 4, 13, 1.00, 2.75, 2.75),
    (11, 4, 15, 1.00, 8.75, 8.75),

    -- Order 5
    (12, 5, 6, 2.00, 3.25, 6.50),
    (13, 5, 16, 1.00, 3.95, 3.95),
    (14, 5, 5, 1.00, 3.30, 3.30),

    -- Order 6
    (15, 6, 2, 1.00, 4.25, 4.25),
    (16, 6, 3, 1.00, 2.50, 2.50),
    (17, 6, 1, 1.00, 3.50, 3.50);


-- ============================================================
-- 13. ORDER STATUS HISTORY
-- ============================================================

INSERT INTO order_status_history
    (id, order_id, status, changed_by, created_at)
VALUES
    -- Order 1
    (1, 1, 'pending', 1, '2026-09-18 09:15:00'),
    (2, 1, 'accepted', 5, '2026-09-18 09:30:00'),
    (3, 1, 'preparing', 5, '2026-09-18 10:00:00'),
    (4, 1, 'ready', 5, '2026-09-19 08:00:00'),
    (5, 1, 'completed', 1, '2026-09-19 09:15:00'),

    -- Order 2
    (6, 2, 'pending', 2, '2026-09-19 10:30:00'),
    (7, 2, 'accepted', 6, '2026-09-19 11:00:00'),
    (8, 2, 'preparing', 6, '2026-09-19 12:00:00'),
    (9, 2, 'ready', 6, '2026-09-20 09:00:00'),
    (10, 2, 'completed', 2, '2026-09-20 10:15:00'),

    -- Order 3
    (11, 3, 'pending', 3, '2026-09-23 11:00:00'),
    (12, 3, 'accepted', 7, '2026-09-23 11:20:00'),
    (13, 3, 'preparing', 7, '2026-09-24 08:30:00'),
    (14, 3, 'ready', 7, '2026-09-25 08:00:00'),

    -- Order 4
    (15, 4, 'pending', 4, '2026-09-24 14:20:00'),
    (16, 4, 'accepted', 8, '2026-09-24 14:45:00'),
    (17, 4, 'preparing', 8, '2026-09-25 08:30:00'),

    -- Order 5
    (18, 5, 'pending', 1, '2026-09-24 16:10:00'),
    (19, 5, 'accepted', 6, '2026-09-24 17:00:00'),

    -- Order 6
    (20, 6, 'pending', 2, '2026-09-25 08:30:00');


-- ============================================================
-- 14. REVIEWS
-- ============================================================

INSERT INTO reviews
    (id, customer_id, farmer_id, product_id, order_id,
     rating, comment, status, farmer_response, farmer_response_at, created_at)
VALUES
    (1,
     1, 1, 1, 1,
     5,
     'The tomatoes were fresh and tasted excellent. I will definitely order again.',
     'approved',
     'Thank you for the kind review! We are glad you enjoyed the tomatoes.',
     '2026-09-20 10:00:00',
     '2026-09-20 09:30:00'),

    (2,
     2, 2, 7, 2,
     4,
     'The strawberries were fresh and sweet. Packaging was also very good.',
     'approved',
     'Thank you! We appreciate your feedback.',
     '2026-09-21 11:00:00',
     '2026-09-21 10:30:00'),

    (3,
     1, 1, 4, 1,
     5,
     'The eggs were excellent and arrived in perfect condition.',
     'approved',
     NULL,
     NULL,
     '2026-09-20 09:45:00');


-- ============================================================
-- 15. NOTIFICATIONS
-- ============================================================

INSERT INTO notifications
    (id, user_id, type, title, message, is_read, created_at)
VALUES
    (1,
     1,
     'order',
     'Order Completed',
     'Your order #1 has been completed. Thank you for shopping with MarketLink!',
     1,
     '2026-09-19 09:20:00'),

    (2,
     1,
     'review',
     'Review Published',
     'Your review for Morgan Valley Farms has been published.',
     1,
     '2026-09-20 09:35:00'),

    (3,
     2,
     'order',
     'Order Completed',
     'Your order #2 is ready for pickup and has been completed.',
     1,
     '2026-09-20 10:20:00'),

    (4,
     2,
     'announcement',
     'New Market Announcement',
     'Central Harvest Market has added new weekend pickup slots.',
     0,
     '2026-09-22 09:00:00'),

    (5,
     3,
     'order',
     'Order Ready',
     'Your order #3 is ready for pickup.',
     0,
     '2026-09-25 08:05:00'),

    (6,
     4,
     'order',
     'Order Being Prepared',
     'Your order #4 is currently being prepared by Sunrise Harvest Co.',
     0,
     '2026-09-25 08:35:00'),

    (7,
     5,
     'system',
     'New Order',
     'You have received a new order (#6).',
     0,
     '2026-09-25 08:35:00'),

    (8,
     6,
     'system',
     'New Order',
     'You have received a new order (#5).',
     1,
     '2026-09-24 17:05:00'),

    (9,
     9,
     'system',
     'Weekly Activity',
     'Your MarketLink weekly activity report is ready.',
     0,
     '2026-09-25 08:00:00');


-- ============================================================
-- 16. ANNOUNCEMENTS
-- ============================================================

INSERT INTO announcements
    (id, admin_id, title, message, status, created_at, expires_at)
VALUES
    (1,
     9,
     'Welcome to MarketLink',
     'Discover fresh local products, connect with farmers, and schedule convenient market pickups through MarketLink.',
     'published',
     '2026-09-01 09:00:00',
     '2026-12-31 23:59:59'),

    (2,
     9,
     'Weekend Market Update',
     'Several participating markets have added new weekend pickup slots. Check your favorite markets for updated availability.',
     'published',
     '2026-09-20 09:00:00',
     '2026-10-20 23:59:59'),

    (3,
     9,
     'Seasonal Produce Available',
     'New seasonal fruits and vegetables are now available from participating farmers.',
     'published',
     '2026-09-22 10:00:00',
     '2026-10-15 23:59:59'),

    (4,
     9,
     'System Maintenance Notice',
     'A short maintenance period is scheduled for upcoming system improvements.',
     'draft',
     '2026-09-24 12:00:00',
     NULL);


-- ============================================================
-- 17. REPORTS
-- ============================================================

INSERT INTO reports
    (id, generated_by, report_type, generated_at)
VALUES
    (1, 9, 'sales_summary', '2026-09-01 09:00:00'),
    (2, 9, 'farmer_activity', '2026-09-10 09:30:00'),
    (3, 9, 'market_activity', '2026-09-15 10:00:00'),
    (4, 9, 'order_summary', '2026-09-20 10:30:00'),
    (5, 9, 'product_inventory', '2026-09-25 08:00:00');


-- ============================================================
-- 18. WEEKLY STOCK
-- ============================================================

INSERT INTO weekly_stock
    (id, farmer_id, product_id, week_start, planned_quantity, is_active)
VALUES
    (1, 1, 1, '2026-09-21', 60.00, 1),
    (2, 1, 2, '2026-09-21', 40.00, 1),
    (3, 1, 3, '2026-09-21', 50.00, 1),
    (4, 1, 4, '2026-09-21', 80.00, 1),

    (5, 2, 5, '2026-09-21', 70.00, 1),
    (6, 2, 6, '2026-09-21', 90.00, 1),
    (7, 2, 7, '2026-09-21', 45.00, 1),
    (8, 2, 8, '2026-09-21', 35.00, 1),
    (9, 2, 16, '2026-09-21', 50.00, 1),

    (10, 3, 9, '2026-09-21', 60.00, 1),
    (11, 3, 10, '2026-09-21', 30.00, 1),
    (12, 3, 11, '2026-09-21', 45.00, 1),

    (13, 4, 12, '2026-09-21', 70.00, 1),
    (14, 4, 13, '2026-09-21', 40.00, 1),
    (15, 4, 14, '2026-09-21', 50.00, 1),
    (16, 4, 15, '2026-09-21', 25.00, 1);


-- ============================================================
-- FINISH
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

-- ============================================================
-- OPTIONAL QUICK CHECKS
-- ============================================================

SELECT 'Users' AS table_name, COUNT(*) AS records FROM users
UNION ALL
SELECT 'Categories', COUNT(*) FROM categories
UNION ALL
SELECT 'Markets', COUNT(*) FROM markets
UNION ALL
SELECT 'Farmers', COUNT(*) FROM farmers
UNION ALL
SELECT 'Products', COUNT(*) FROM products
UNION ALL
SELECT 'Pickup Slots', COUNT(*) FROM pickup_slots
UNION ALL
SELECT 'Orders', COUNT(*) FROM orders
UNION ALL
SELECT 'Order Items', COUNT(*) FROM order_items
UNION ALL
SELECT 'Reviews', COUNT(*) FROM reviews
UNION ALL
SELECT 'Notifications', COUNT(*) FROM notifications
UNION ALL
SELECT 'Announcements', COUNT(*) FROM announcements
UNION ALL
SELECT 'Reports', COUNT(*) FROM reports
UNION ALL
SELECT 'Weekly Stock', COUNT(*) FROM weekly_stock;