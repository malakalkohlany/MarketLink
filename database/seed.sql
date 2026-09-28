SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

INSERT INTO users (
    name,
    email,
    password_hash,
    phone,
    address,
    role,
    status
) VALUES
(
    'Daniel Brooks',
    'daniel.brooks@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(503) 555-0142',
    'Portland, Oregon',
    'customer',
    'active'
),
(
    'Emily Carter',
    'emily.carter@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(206) 555-0187',
    'Seattle, Washington',
    'customer',
    'active'
),
(
    'Michael Turner',
    'michael.turner@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(303) 555-0126',
    'Denver, Colorado',
    'customer',
    'active'
),
(
    'Sophia Bennett',
    'sophia.bennett@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(415) 555-0164',
    'San Francisco, California',
    'customer',
    'active'
),
(
    'James Wilson',
    'james.wilson@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(512) 555-0193',
    'Austin, Texas',
    'customer',
    'active'
),
(
    'Olivia Mitchell',
    'olivia.mitchell@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(617) 555-0138',
    'Boston, Massachusetts',
    'customer',
    'active'
),
(
    'Ethan Parker',
    'ethan.parker@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(312) 555-0175',
    'Chicago, Illinois',
    'customer',
    'active'
),
(
    'Ava Richardson',
    'ava.richardson@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(919) 555-0119',
    'Raleigh, North Carolina',
    'customer',
    'active'
),
(
    'Benjamin Cooper',
    'benjamin.cooper@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(801) 555-0152',
    'Salt Lake City, Utah',
    'customer',
    'active'
),
(
    'Mia Anderson',
    'mia.anderson@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(612) 555-0147',
    'Minneapolis, Minnesota',
    'customer',
    'active'
),
(
    'Lucas Morgan',
    'lucas.morgan@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(404) 555-0181',
    'Atlanta, Georgia',
    'customer',
    'active'
),
(
    'Charlotte Hayes',
    'charlotte.hayes@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(615) 555-0124',
    'Nashville, Tennessee',
    'customer',
    'active'
),
(
    'Henry Foster',
    'henry.foster@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(614) 555-0198',
    'Columbus, Ohio',
    'customer',
    'active'
),
(
    'Amelia Reed',
    'amelia.reed@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(215) 555-0168',
    'Philadelphia, Pennsylvania',
    'customer',
    'active'
),
(
    'Alexander Hughes',
    'alexander.hughes@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(704) 555-0135',
    'Charlotte, North Carolina',
    'customer',
    'active'
),
(
    'Harper Collins',
    'harper.collins@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(602) 555-0172',
    'Phoenix, Arizona',
    'customer',
    'active'
),
(
    'William Sanders',
    'william.sanders@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(816) 555-0116',
    'Kansas City, Missouri',
    'customer',
    'active'
),
(
    'Evelyn Murphy',
    'evelyn.murphy@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(617) 555-0191',
    'Boston, Massachusetts',
    'customer',
    'active'
),
(
    'Noah Jenkins',
    'noah.jenkins@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(503) 555-0161',
    'Portland, Oregon',
    'customer',
    'active'
),
(
    'Abigail Perry',
    'abigail.perry@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(720) 555-0149',
    'Denver, Colorado',
    'customer',
    'active'
),
(
    'Samuel Ross',
    'samuel.ross@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(512) 555-0184',
    'Austin, Texas',
    'customer',
    'active'
),
(
    'Ella Griffin',
    'ella.griffin@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(206) 555-0131',
    'Seattle, Washington',
    'customer',
    'active'
),
(
    'Jack Wallace',
    'jack.wallace@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(415) 555-0196',
    'San Francisco, California',
    'customer',
    'active'
),
(
    'Grace Simmons',
    'grace.simmons@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(312) 555-0158',
    'Chicago, Illinois',
    'customer',
    'active'
),
(
    'Daniela Price',
    'daniela.price@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(919) 555-0174',
    'Raleigh, North Carolina',
    'customer',
    'active'
),
(
    'Matthew Bennett',
    'matthew.bennett@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(801) 555-0128',
    'Salt Lake City, Utah',
    'customer',
    'active'
),
(
    'Lily Coleman',
    'lily.coleman@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(404) 555-0167',
    'Atlanta, Georgia',
    'customer',
    'active'
),
(
    'David Walker',
    'david.walker@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(614) 555-0144',
    'Columbus, Ohio',
    'customer',
    'active'
),
(
    'Nathan Bell',
    'nathan.bell@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(602) 555-0113',
    'Phoenix, Arizona',
    'customer',
    'active'
),
(
    'Sarah Thompson',
    'sarah.thompson@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(913) 555-0186',
    'Overland Park, Kansas',
    'customer',
    'active'
),
(
    'Christopher Evans',
    'christopher.evans@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(615) 555-0137',
    'Nashville, Tennessee',
    'customer',
    'active'
),
(
    'Rachel Morgan',
    'rachel.morgan@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(503) 555-0156',
    'Portland, Oregon',
    'farmer',
    'active'
),
(
    'Andrew Coleman',
    'andrew.coleman@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(206) 555-0118',
    'Seattle, Washington',
    'farmer',
    'active'
),
(
    'Jessica Warren',
    'jessica.warren@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(303) 555-0179',
    'Denver, Colorado',
    'farmer',
    'active'
),
(
    'Thomas Mitchell',
    'thomas.mitchell@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(512) 555-0141',
    'Austin, Texas',
    'farmer',
    'active'
),
(
    'Laura Peterson',
    'laura.peterson@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(919) 555-0192',
    'Raleigh, North Carolina',
    'farmer',
    'active'
),
(
    'Robert Lawson',
    'robert.lawson@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(415) 555-0122',
    'San Francisco, California',
    'farmer',
    'active'
),
(
    'Amanda Foster',
    'amanda.foster@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(312) 555-0189',
    'Chicago, Illinois',
    'farmer',
    'active'
),
(
    'Marcus Reed',
    'marcus.reed@marketlink.demo',
    '$2y$10$BAcwi9TLoUcfzvdyKBacX.4R8zOlGcpw4ZIqFVxRA5b6.6WAMafx.',
    '(503) 555-0109',
    'Portland, Oregon',
    'admin',
    'active'
);

INSERT INTO categories (
    name,
    description,
    image,
    status
) VALUES
(
    'Vegetables',
    'Fresh seasonal vegetables grown by local farmers.',
    NULL,
    'active'
),
(
    'Root Vegetables',
    'Fresh root vegetables including carrots, potatoes, beets, and similar crops.',
    NULL,
    'active'
),
(
    'Leafy Greens',
    'Fresh leafy greens harvested from local farms.',
    NULL,
    'active'
),
(
    'Herbs',
    'Fresh culinary herbs grown and harvested locally.',
    NULL,
    'active'
),
(
    'Fruits',
    'Fresh seasonal fruits sourced from local farms.',
    NULL,
    'active'
),
(
    'Eggs',
    'Fresh farm eggs from local producers.',
    NULL,
    'active'
),
(
    'Dairy Products',
    'Fresh locally produced dairy products.',
    NULL,
    'active'
),
(
    'Honey',
    'Locally produced natural honey and honey products.',
    NULL,
    'active'
),
(
    'Grains',
    'Locally produced grains and grain-based farm products.',
    NULL,
    'active'
),
(
    'Pantry and Artisan',
    'Locally made pantry goods and artisan food products.',
    NULL,
    'active'
),
(
    'Poultry',
    'Fresh poultry products from local farms.',
    NULL,
    'active'
),
(
    'Meat',
    'Locally produced farm-fresh meat products.',
    NULL,
    'active'
),
(
    'Other',
    'Other locally produced agricultural and farm products.',
    NULL,
    'active'
);

INSERT INTO markets (
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
) VALUES
(
    'Riverfront Farmers Market',
    'A community farmers market featuring fresh produce, eggs, dairy products, and locally made goods.',
    '1000 SW Naito Parkway, Portland, OR 97204',
    45.51520,
    -122.67340,
    '08:00:00',
    '14:00:00',
    'Saturday,Sunday',
    'OpenStreetMap',
    'active'
),
(
    'Pike Place Community Market',
    'A busy local market connecting shoppers with regional farmers and independent food producers.',
    '85 Pike Street, Seattle, WA 98101',
    47.60870,
    -122.34080,
    '09:00:00',
    '15:00:00',
    'Saturday',
    'OpenStreetMap',
    'active'
),
(
    'Capitol Hill Farmers Market',
    'A neighborhood market offering seasonal produce, farm products, and artisan foods.',
    '7001 E Colfax Avenue, Denver, CO 80220',
    39.74020,
    -104.95950,
    '09:00:00',
    '14:00:00',
    'Saturday',
    'OpenStreetMap',
    'active'
),
(
    'Downtown Austin Farmers Market',
    'A weekly market featuring local farms, fresh produce, specialty foods, and artisan products.',
    '422 Guadalupe Street, Austin, TX 78701',
    30.26760,
    -97.74650,
    '09:00:00',
    '13:00:00',
    'Saturday',
    'OpenStreetMap',
    'active'
),
(
    'North Hills Farmers Market',
    'A community-focused market featuring seasonal produce and products from nearby farms.',
    '4321 Lassiter Mill Road, Raleigh, NC 27609',
    35.85890,
    -78.64310,
    '08:00:00',
    '13:00:00',
    'Saturday',
    'OpenStreetMap',
    'active'
);

COMMIT;

START TRANSACTION;

INSERT INTO farmers (
    user_id,
    stall_name,
    contact_person,
    description,
    address,
    latitude,
    longitude,
    approval_status
)
SELECT
    id,
    'Morgan Family Farm',
    'Rachel Morgan',
    'A family farm offering seasonal vegetables, herbs, eggs, and locally produced pantry goods.',
    '4821 SE Foster Road, Portland, OR 97206',
    45.48481000,
    -122.55892000,
    'approved'
FROM users
WHERE email = 'rachel.morgan@marketlink.demo';

INSERT INTO farmers (
    user_id,
    stall_name,
    contact_person,
    description,
    address,
    latitude,
    longitude,
    approval_status
)
SELECT
    id,
    'Cedar Valley Produce',
    'Andrew Coleman',
    'A small family-run farm specializing in fresh vegetables, leafy greens, root vegetables, and seasonal produce.',
    '1735 NE 119th Avenue, Vancouver, WA 98684',
    45.63742000,
    -122.55791000,
    'approved'
FROM users
WHERE email = 'andrew.coleman@marketlink.demo';

INSERT INTO farmers (
    user_id,
    stall_name,
    contact_person,
    description,
    address,
    latitude,
    longitude,
    approval_status
)
SELECT
    id,
    'Warren Family Orchard',
    'Jessica Warren',
    'A family orchard producing seasonal fruits, honey, eggs, and handcrafted farm products.',
    '2180 South Chambers Road, Aurora, CO 80014',
    39.67128000,
    -104.80964000,
    'approved'
FROM users
WHERE email = 'jessica.warren@marketlink.demo';

INSERT INTO farmers (
    user_id,
    stall_name,
    contact_person,
    description,
    address,
    latitude,
    longitude,
    approval_status
)
SELECT
    id,
    'Hill Country Harvest',
    'Thomas Mitchell',
    'A Texas farm offering seasonal vegetables, poultry, eggs, herbs, and locally produced pantry items.',
    '14520 FM 973, Manor, TX 78653',
    30.34287000,
    -97.55643000,
    'approved'
FROM users
WHERE email = 'thomas.mitchell@marketlink.demo';

INSERT INTO farmers (
    user_id,
    stall_name,
    contact_person,
    description,
    address,
    latitude,
    longitude,
    approval_status
)
SELECT
    id,
    'Pine Ridge Farm',
    'Laura Peterson',
    'A local farm growing vegetables, leafy greens, herbs, and seasonal fruits for nearby communities.',
    '3912 Poole Road, Raleigh, NC 27610',
    35.75892000,
    -78.55543000,
    'approved'
FROM users
WHERE email = 'laura.peterson@marketlink.demo';

INSERT INTO farmers (
    user_id,
    stall_name,
    contact_person,
    description,
    address,
    latitude,
    longitude,
    approval_status
)
SELECT
    id,
    'Golden Gate Family Farm',
    'Robert Lawson',
    'A Northern California family farm producing vegetables, fruits, herbs, eggs, and artisan farm products.',
    '850 Hillside Boulevard, San Mateo, CA 94402',
    37.56374000,
    -122.32315000,
    'approved'
FROM users
WHERE email = 'robert.lawson@marketlink.demo';

INSERT INTO farmers (
    user_id,
    stall_name,
    contact_person,
    description,
    address,
    latitude,
    longitude,
    approval_status
)
SELECT
    id,
    'Prairie Table Farm',
    'Amanda Foster',
    'A Midwest farm supplying fresh vegetables, grains, eggs, dairy products, and seasonal produce.',
    '6120 West Higgins Road, Chicago, IL 60631',
    41.99842000,
    -87.80631000,
    'approved'
FROM users
WHERE email = 'amanda.foster@marketlink.demo';

INSERT INTO announcements (
    admin_id,
    title,
    message,
    status,
    expires_at
)
SELECT
    id,
    'Welcome to MarketLink',
    'Welcome to MarketLink, your local marketplace for discovering farmers, fresh products, and convenient market pickup options.',
    'published',
    NULL
FROM users
WHERE email = 'marcus.reed@marketlink.demo';

INSERT INTO announcements (
    admin_id,
    title,
    message,
    status,
    expires_at
)
SELECT
    id,
    'Weekly Ordering Is Now Available',
    'Customers can now plan their purchases ahead of time through weekly product availability and scheduled market pickup.',
    'published',
    NULL
FROM users
WHERE email = 'marcus.reed@marketlink.demo';

INSERT INTO announcements (
    admin_id,
    title,
    message,
    status,
    expires_at
)
SELECT
    id,
    'New Farmers Joining MarketLink',
    'New local farmers are joining MarketLink and expanding the selection of fresh products available to customers.',
    'published',
    NULL
FROM users
WHERE email = 'marcus.reed@marketlink.demo';

INSERT INTO announcements (
    admin_id,
    title,
    message,
    status,
    expires_at
)
SELECT
    id,
    'Market Pickup Reminder',
    'Remember to check your selected pickup market, date, and time before placing your weekly order.',
    'published',
    NULL
FROM users
WHERE email = 'marcus.reed@marketlink.demo';

INSERT INTO announcements (
    admin_id,
    title,
    message,
    status,
    expires_at
)
SELECT
    id,
    'Seasonal Produce Updates',
    'Seasonal availability is updated regularly as farmers add new products and adjust their weekly stock.',
    'draft',
    NULL
FROM users
WHERE email = 'marcus.reed@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'system',
    'Welcome to MarketLink',
    'Your MarketLink account is ready. Explore local farmers, markets, and available products.',
    0
FROM users
WHERE email = 'emma.brooks@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'market',
    'New Market Available',
    'A new market has been added to MarketLink. Visit the markets section to explore available pickup locations.',
    1
FROM users
WHERE email = 'daniel.hayes@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'favorite',
    'Favorite Farmer Updated',
    'A farmer you follow has updated their available products.',
    0
FROM users
WHERE email = 'olivia.carter@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'system',
    'Weekly Ordering Available',
    'Weekly product availability is now available for upcoming pickup weeks.',
    0
FROM users
WHERE email = 'ethan.turner@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'market',
    'Pickup Information Updated',
    'Pickup information for one of your favorite markets has been updated.',
    1
FROM users
WHERE email = 'sophia.martin@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'system',
    'New Products Added',
    'New seasonal products have been added by local farmers.',
    0
FROM users
WHERE email = 'noah.wilson@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'favorite',
    'Farmer Availability Updated',
    'A farmer in your favorites has updated their product availability for the coming week.',
    1
FROM users
WHERE email = 'ava.richards@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'system',
    'Order Reminder',
    'Remember to review your pickup details before your scheduled collection time.',
    0
FROM users
WHERE email = 'liam.bennett@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'market',
    'Market Schedule Reminder',
    'Check the market schedule before selecting a pickup slot for your next order.',
    1
FROM users
WHERE email = 'grace.mitchell@marketlink.demo';

INSERT INTO notifications (
    user_id,
    type,
    title,
    message,
    is_read
)
SELECT
    id,
    'system',
    'Welcome Back',
    'New products and weekly availability may be available from your favorite farmers.',
    0
FROM users
WHERE email = 'jackson.murphy@marketlink.demo';

INSERT INTO reports (
    generated_by,
    report_type
)
SELECT
    id,
    'market_activity'
FROM users
WHERE email = 'marcus.reed@marketlink.demo';

INSERT INTO reports (
    generated_by,
    report_type
)
SELECT
    id,
    'product_inventory'
FROM users
WHERE email = 'marcus.reed@marketlink.demo';

INSERT INTO reports (
    generated_by,
    report_type
)
SELECT
    id,
    'order_summary'
FROM users
WHERE email = 'marcus.reed@marketlink.demo';

INSERT INTO reports (
    generated_by,
    report_type
)
SELECT
    id,
    'farmer_activity'
FROM users
WHERE email = 'marcus.reed@marketlink.demo';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'sophia.bennett@marketlink.demo'
AND m.name = 'Riverfront Farmers Market';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'daniel.brooks@marketlink.demo'
AND m.name = 'Pike Place Community Market';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'benjamin.cooper@marketlink.demo'
AND m.name = 'Capitol Hill Farmers Market';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'mia.anderson@marketlink.demo'
AND m.name = 'Downtown Austin Farmers Market';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'sophia.martin@marketlink.demo'
AND m.name = 'North Hills Farmers Market';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'charlotte.hayes@marketlink.demo'
AND m.name = 'Riverfront Farmers Market';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'ava.richards@marketlink.demo'
AND m.name = 'Capitol Hill Farmers Market';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'liam.bennett@marketlink.demo'
AND m.name = 'Downtown Austin Farmers Market';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'grace.mitchell@marketlink.demo'
AND m.name = 'Pike Place Community Market';

INSERT INTO favorite_markets (
    customer_id,
    market_id
)
SELECT
    u.id,
    m.id
FROM users u
CROSS JOIN markets m
WHERE u.email = 'jackson.murphy@marketlink.demo'
AND m.name = 'North Hills Farmers Market';

COMMIT;

START TRANSACTION;

INSERT INTO products (
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
SELECT f.id, c.id, 'tomatoes', 'Fresh locally grown tomatoes harvested in season.', 3.49, 'kg', 50.00, 'uploads/products/tomatoes.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Vegetables'
WHERE u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'bell peppers', 'Fresh crisp bell peppers suitable for cooking and salads.', 4.25, 'kg', 35.00, 'uploads/products/bell_peppers.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Vegetables'
WHERE u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'cucumbers', 'Fresh locally grown cucumbers.', 2.99, 'kg', 40.00, 'uploads/products/cucumbers.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Vegetables'
WHERE u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'zucchini', 'Tender seasonal zucchini harvested fresh from the farm.', 3.25, 'kg', 30.00, 'uploads/products/zucchini.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Vegetables'
WHERE u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'eggplant', 'Fresh seasonal eggplant with a firm texture.', 3.75, 'kg', 28.00, 'uploads/products/eggplant.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Vegetables'
WHERE u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'potatoes', 'Fresh farm-grown potatoes.', 2.79, 'kg', 60.00, 'uploads/products/potatoes.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Root Vegetables'
WHERE u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'carrots', 'Fresh crisp carrots harvested locally.', 2.89, 'kg', 45.00, 'uploads/products/carrots.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Root Vegetables'
WHERE u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'red onions', 'Fresh red onions grown on the farm.', 3.19, 'kg', 40.00, 'uploads/products/red_onions.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Root Vegetables'
WHERE u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'spinach', 'Fresh leafy spinach harvested for the week.', 2.49, 'bunch', 45.00, 'uploads/products/spinach.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Leafy Greens'
WHERE u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'romaine lettuce', 'Fresh crisp romaine lettuce.', 2.79, 'head', 35.00, 'uploads/products/romaine_lettuce.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Leafy Greens'
WHERE u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'kale', 'Fresh locally grown kale.', 2.69, 'bunch', 30.00, 'uploads/products/kale.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Leafy Greens'
WHERE u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'mint', 'Fresh aromatic mint harvested locally.', 2.25, 'bunch', 25.00, 'uploads/products/mint.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Herbs'
WHERE u.email = 'laura.peterson@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'basil', 'Fresh fragrant basil leaves.', 2.50, 'bunch', 25.00, 'uploads/products/basil.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Herbs'
WHERE u.email = 'laura.peterson@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'coriander', 'Fresh locally grown coriander.', 2.25, 'bunch', 25.00, 'uploads/products/coriander.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Herbs'
WHERE u.email = 'laura.peterson@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'bananas', 'Ripe fresh bananas selected for quality.', 2.49, 'kg', 45.00, 'uploads/products/bananas.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Fruits'
WHERE u.email = 'jessica.warren@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'oranges', 'Juicy seasonal oranges.', 3.29, 'kg', 40.00, 'uploads/products/oranges.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Fruits'
WHERE u.email = 'jessica.warren@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'apples', 'Fresh crisp seasonal apples.', 3.99, 'kg', 40.00, 'uploads/products/apples.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Fruits'
WHERE u.email = 'jessica.warren@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'mangoes', 'Sweet seasonal mangoes.', 4.49, 'kg', 30.00, 'uploads/products/mangoes.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Fruits'
WHERE u.email = 'jessica.warren@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'eggs', 'Fresh farm eggs collected regularly.', 5.49, 'dozen', 40.00, 'uploads/products/eggs.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Eggs'
WHERE u.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'free-range eggs', 'Fresh free-range eggs from pasture-raised hens.', 6.99, 'dozen', 35.00, 'uploads/products/free-range_eggs.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Eggs'
WHERE u.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'cow milk', 'Fresh locally produced cow milk.', 4.99, 'liter', 30.00, 'uploads/products/cow_milk.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Dairy Products'
WHERE u.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'homemade yogurt', 'Creamy homemade yogurt prepared from fresh farm milk.', 5.49, 'kg', 25.00, 'uploads/products/homemade_yogurt.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Dairy Products'
WHERE u.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'white cheese', 'Fresh homemade white cheese.', 7.49, 'kg', 20.00, 'uploads/products/white_cheese.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Dairy Products'
WHERE u.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'local honey', 'Naturally produced local honey.', 9.99, 'jar', 25.00, 'uploads/products/local_honey.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Honey'
WHERE u.email = 'robert.lawson@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'raw mountain honey', 'Raw honey collected from mountain-area hives.', 13.99, 'jar', 20.00, 'uploads/products/raw_mountain_honey.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Honey'
WHERE u.email = 'robert.lawson@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'whole wheat flour', 'Locally milled whole wheat flour.', 4.99, 'kg', 35.00, 'uploads/products/whole_wheat_flour.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Grains'
WHERE u.email = 'amanda.foster@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'local wheat', 'Locally grown wheat grain.', 3.99, 'kg', 40.00, 'uploads/products/local_wheat.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Grains'
WHERE u.email = 'amanda.foster@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'homemade tomato sauce', 'Homemade tomato sauce prepared from locally grown tomatoes.', 6.49, 'jar', 25.00, 'uploads/products/homemade_tomato_sauce.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Pantry and Artisan'
WHERE u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'mixed vegetables pickles', 'Homemade pickled mixed vegetables.', 6.99, 'jar', 25.00, 'uploads/products/mixed_vegetables_pickles.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Pantry and Artisan'
WHERE u.email = 'laura.peterson@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'homemade strawberry jam', 'Small-batch strawberry jam made with seasonal fruit.', 7.49, 'jar', 20.00, 'uploads/products/homemade_strawberry_jam.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Pantry and Artisan'
WHERE u.email = 'jessica.warren@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'fresh chicken', 'Fresh locally raised chicken.', 8.99, 'kg', 25.00, 'uploads/products/fresh_chicken.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Poultry'
WHERE u.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO products (
    farmer_id, category_id, name, description, price, unit, stock_quantity, image, is_available, moderation_status
)
SELECT f.id, c.id, 'fresh goat meat', 'Fresh locally sourced goat meat.', 13.99, 'kg', 20.00, 'uploads/products/fresh_goat_meat.jpg', 1, 'approved'
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN categories c ON c.name = 'Meat'
WHERE u.email = 'robert.lawson@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'emily.carter@marketlink.demo'
AND fu.email = 'amanda.foster@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'daniel.brooks@marketlink.demo'
AND fu.email = 'robert.lawson@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'sophia.bennett@marketlink.demo'
AND fu.email = 'amanda.foster@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'sophia.bennett@marketlink.demo'
AND fu.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'james.wilson@marketlink.demo'
AND fu.email = 'laura.peterson@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'noah.wilson@marketlink.demo'
AND fu.email = 'robert.lawson@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'ava.richards@marketlink.demo'
AND fu.email = 'amanda.foster@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'liam.bennett@marketlink.demo'
AND fu.email = 'rachel.morgan@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'grace.mitchell@marketlink.demo'
AND fu.email = 'sarah.thompson@marketlink.demo';

INSERT INTO favorite_farmers (customer_id, farmer_id)
SELECT u.id, f.id
FROM users u
JOIN farmers f
JOIN users fu ON fu.id = f.user_id
WHERE u.email = 'jackson.murphy@marketlink.demo'
AND fu.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'Riverfront Farmers Market'
AND u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'Riverfront Farmers Market'
AND u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'Pike Place Community Market'
AND u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'Pike Place Community Market'
AND u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'Capitol Hill Farmers Market'
AND u.email = 'jessica.warren@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'Capitol Hill Farmers Market'
AND u.email = 'laura.peterson@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'Downtown Austin Farmers Market'
AND u.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'Downtown Austin Farmers Market'
AND u.email = 'robert.lawson@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'North Hills Farmers Market'
AND u.email = 'laura.peterson@marketlink.demo';

INSERT INTO market_farmer (market_id, farmer_id)
SELECT m.id, f.id
FROM markets m
JOIN farmers f
JOIN users u ON u.id = f.user_id
WHERE m.name = 'North Hills Farmers Market'
AND u.email = 'amanda.foster@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Saturday', '09:00:00', '12:00:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'Riverfront Farmers Market'
WHERE u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Saturday', '09:00:00', '12:00:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'Riverfront Farmers Market'
WHERE u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Sunday', '10:00:00', '13:00:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'Pike Place Community Market'
WHERE u.email = 'rachel.morgan@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Sunday', '10:00:00', '13:00:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'Pike Place Community Market'
WHERE u.email = 'andrew.coleman@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Saturday', '08:30:00', '11:30:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'Capitol Hill Farmers Market'
WHERE u.email = 'jessica.warren@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Saturday', '08:30:00', '11:30:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'Capitol Hill Farmers Market'
WHERE u.email = 'laura.peterson@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Saturday', '09:00:00', '12:00:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'Downtown Austin Farmers Market'
WHERE u.email = 'thomas.mitchell@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Saturday', '09:00:00', '12:00:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'Downtown Austin Farmers Market'
WHERE u.email = 'robert.lawson@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Saturday', '08:00:00', '11:00:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'North Hills Farmers Market'
WHERE u.email = 'laura.peterson@marketlink.demo';

INSERT INTO pickup_slots (
    farmer_id,
    market_id,
    day_of_week,
    start_time,
    end_time,
    cutoff_time,
    max_orders,
    is_available
)
SELECT f.id, m.id, 'Saturday', '08:00:00', '11:00:00', '18:00:00', 20, 1
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN markets m ON m.name = 'North Hills Farmers Market'
WHERE u.email = 'amanda.foster@marketlink.demo';

COMMIT;

START TRANSACTION;

INSERT INTO weekly_stock_templates (farmer_id, product_id, default_quantity)
SELECT f.id, p.id, p.stock_quantity
FROM farmers f
JOIN users u ON u.id = f.user_id
JOIN products p ON p.farmer_id = f.id;

INSERT INTO weekly_stock (farmer_id, product_id, week_start, planned_quantity, actual_quantity, status, is_active)
SELECT
    p.farmer_id,
    p.id,
    DATE_ADD(
        DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY),
        INTERVAL w.week_offset WEEK
    ),
    CASE
        WHEN p.stock_quantity >= 40 THEN p.stock_quantity
        ELSE p.stock_quantity * 2
    END,
    CASE
        WHEN w.week_offset = 0 THEN p.stock_quantity
        ELSE 0
    END,
    'available',
    1
FROM products p
CROSS JOIN (
    SELECT 0 AS week_offset
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
    UNION ALL SELECT 12
    UNION ALL SELECT 13
    UNION ALL SELECT 14
    UNION ALL SELECT 15
    UNION ALL SELECT 16
    UNION ALL SELECT 17
    UNION ALL SELECT 18
    UNION ALL SELECT 19
    UNION ALL SELECT 20
    UNION ALL SELECT 21
    UNION ALL SELECT 22
    UNION ALL SELECT 23
    UNION ALL SELECT 24
    UNION ALL SELECT 25
) w;

COMMIT;

START TRANSACTION;

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'tomatoes'
WHERE u.email = 'daniel.brooks@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'bell peppers'
WHERE u.email = 'emily.carter@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'apples'
WHERE u.email = 'michael.turner@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'free-range eggs'
WHERE u.email = 'sophia.bennett@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'spinach'
WHERE u.email = 'james.wilson@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'local honey'
WHERE u.email = 'olivia.mitchell@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'whole wheat flour'
WHERE u.email = 'ethan.parker@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'fresh chicken'
WHERE u.email = 'ava.richardson@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'homemade strawberry jam'
WHERE u.email = 'benjamin.cooper@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'potatoes'
WHERE u.email = 'mia.anderson@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'cow milk'
WHERE u.email = 'lucas.morgan@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'mangoes'
WHERE u.email = 'charlotte.hayes@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'kale'
WHERE u.email = 'henry.foster@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'raw mountain honey'
WHERE u.email = 'amelia.reed@marketlink.demo';

INSERT INTO favorite_products (customer_id, product_id)
SELECT u.id, p.id
FROM users u
JOIN products p ON p.name = 'homemade tomato sauce'
WHERE u.email = 'alexander.hughes@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 2 DAY),
    'completed',
    20.19,
    'Please have the order ready for pickup.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'rachel.morgan@marketlink.demo'
)
JOIN markets m ON m.name = 'Riverfront Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'daniel.brooks@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 2 DAY),
    'completed',
    20.69,
    'Thank you.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'andrew.coleman@marketlink.demo'
)
JOIN markets m ON m.name = 'Riverfront Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'emily.carter@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 2 DAY),
    'completed',
    23.94,
    'Please keep the fruit together.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'jessica.warren@marketlink.demo'
)
JOIN markets m ON m.name = 'Capitol Hill Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'michael.turner@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 2 DAY),
    'completed',
    20.97,
    'Fresh items preferred.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'thomas.mitchell@marketlink.demo'
)
JOIN markets m ON m.name = 'Downtown Austin Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'sophia.bennett@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 2 DAY),
    'completed',
    14.45,
    'Please package carefully.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'laura.peterson@marketlink.demo'
)
JOIN markets m ON m.name = 'North Hills Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'james.wilson@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 2 DAY),
    'ready',
    19.98,
    'I will pick this up during the morning slot.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'robert.lawson@marketlink.demo'
)
JOIN markets m ON m.name = 'Downtown Austin Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'olivia.mitchell@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 2 DAY),
    'preparing',
    14.97,
    'Looking forward to the order.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'amanda.foster@marketlink.demo'
)
JOIN markets m ON m.name = 'North Hills Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'ethan.parker@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 5 DAY),
    'accepted',
    18.47,
    'Please have everything ready by pickup time.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'rachel.morgan@marketlink.demo'
)
JOIN markets m ON m.name = 'Riverfront Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'ava.richardson@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 5 DAY),
    'pending',
    17.98,
    'Please confirm the order when possible.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'jessica.warren@marketlink.demo'
)
JOIN markets m ON m.name = 'Capitol Hill Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'benjamin.cooper@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 5 DAY),
    'pending',
    13.95,
    'Morning pickup requested.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'andrew.coleman@marketlink.demo'
)
JOIN markets m ON m.name = 'Riverfront Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'mia.anderson@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 5 DAY),
    'cancelled',
    9.98,
    'Customer cancelled before preparation.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'thomas.mitchell@marketlink.demo'
)
JOIN markets m ON m.name = 'Downtown Austin Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'lucas.morgan@marketlink.demo';

INSERT INTO orders (customer_id, farmer_id, market_id, pickup_slot_id, pickup_date, status, subtotal, notes)
SELECT
    u.id,
    f.id,
    m.id,
    ps.id,
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 5 DAY),
    'accepted',
    16.47,
    'Weekly pickup order.'
FROM users u
JOIN farmers f ON f.user_id = (
    SELECT id FROM users WHERE email = 'jessica.warren@marketlink.demo'
)
JOIN markets m ON m.name = 'Capitol Hill Farmers Market'
JOIN pickup_slots ps
    ON ps.farmer_id = f.id
    AND ps.market_id = m.id
    AND ps.day_of_week = 'Saturday'
WHERE u.email = 'charlotte.hayes@marketlink.demo';

COMMIT;

START TRANSACTION;

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    5,
    p.price,
    ROUND(5 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'rachel.morgan@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    1,
    p.price,
    p.price
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'rachel.morgan@marketlink.demo'
)
AND o.status = 'completed'
LIMIT 1;

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    1,
    p.price,
    p.price
FROM orders o
JOIN products p ON p.name = 'mint'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'rachel.morgan@marketlink.demo'
)
AND o.status = 'completed'
LIMIT 1;

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    5,
    p.price,
    ROUND(5 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'cucumbers'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'emily.carter@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'andrew.coleman@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    1,
    p.price,
    p.price
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'emily.carter@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'andrew.coleman@marketlink.demo'
)
AND o.status = 'completed'
LIMIT 1;

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    1,
    p.price,
    p.price
FROM orders o
JOIN products p ON p.name = 'mint'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'emily.carter@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'andrew.coleman@marketlink.demo'
)
AND o.status = 'completed'
LIMIT 1;

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    3,
    p.price,
    ROUND(3 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'michael.turner@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'jessica.warren@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    3,
    p.price,
    ROUND(3 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'mangoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'michael.turner@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'jessica.warren@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    2,
    p.price,
    ROUND(2 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'cow milk'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'sophia.bennett@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'thomas.mitchell@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    1,
    p.price,
    p.price
FROM orders o
JOIN products p ON p.name = 'white cheese'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'sophia.bennett@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'thomas.mitchell@marketlink.demo'
)
AND o.status = 'completed'
LIMIT 1;

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    2,
    p.price,
    ROUND(2 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'james.wilson@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'laura.peterson@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    3,
    p.price,
    ROUND(3 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'bananas'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'james.wilson@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'laura.peterson@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    2,
    p.price,
    ROUND(2 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'olivia.mitchell@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'robert.lawson@marketlink.demo'
)
AND o.status = 'ready';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    4,
    p.price,
    ROUND(4 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'zucchini'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'olivia.mitchell@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'robert.lawson@marketlink.demo'
)
AND o.status = 'ready';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    3,
    p.price,
    ROUND(3 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'ethan.parker@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'amanda.foster@marketlink.demo'
)
AND o.status = 'preparing';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    2,
    p.price,
    ROUND(2 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'mint'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'ethan.parker@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'amanda.foster@marketlink.demo'
)
AND o.status = 'preparing';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    1,
    p.price,
    p.price
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'ava.richardson@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'rachel.morgan@marketlink.demo'
)
AND o.status = 'accepted';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    2,
    p.price,
    ROUND(2 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'white cheese'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'ava.richardson@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'rachel.morgan@marketlink.demo'
)
AND o.status = 'accepted';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    4,
    p.price,
    ROUND(4 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'zucchini'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'benjamin.cooper@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'jessica.warren@marketlink.demo'
)
AND o.status = 'pending';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    2,
    p.price,
    ROUND(2 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'bananas'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'benjamin.cooper@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'jessica.warren@marketlink.demo'
)
AND o.status = 'pending';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    3,
    p.price,
    ROUND(3 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'cucumbers'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'mia.anderson@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'andrew.coleman@marketlink.demo'
)
AND o.status = 'pending';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    2,
    p.price,
    ROUND(2 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'bananas'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'mia.anderson@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'andrew.coleman@marketlink.demo'
)
AND o.status = 'pending';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    1,
    p.price,
    p.price
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'lucas.morgan@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'thomas.mitchell@marketlink.demo'
)
AND o.status = 'cancelled';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    1,
    p.price,
    p.price
FROM orders o
JOIN products p ON p.name = 'homemade tomato sauce'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'lucas.morgan@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'thomas.mitchell@marketlink.demo'
)
AND o.status = 'cancelled';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    1,
    p.price,
    p.price
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'charlotte.hayes@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'jessica.warren@marketlink.demo'
)
AND o.status = 'accepted';

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
SELECT
    o.id,
    p.id,
    2,
    p.price,
    ROUND(2 * p.price, 2)
FROM orders o
JOIN products p ON p.name = 'homemade tomato sauce'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'charlotte.hayes@marketlink.demo'
)
AND o.farmer_id = (
    SELECT f.id
    FROM farmers f
    JOIN users u ON u.id = f.user_id
    WHERE u.email = 'jessica.warren@marketlink.demo'
)
AND o.status = 'accepted';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT
    o.id,
    'pending',
    o.customer_id
FROM orders o
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT
    o.id,
    'accepted',
    f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT
    o.id,
    'preparing',
    f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT
    o.id,
    'ready',
    f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT
    o.id,
    'completed',
    f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'pending', o.customer_id
FROM orders o
WHERE o.status = 'completed'
AND o.customer_id <> (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'accepted', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'completed'
AND o.customer_id <> (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'preparing', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'completed'
AND o.customer_id <> (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'ready', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'completed'
AND o.customer_id <> (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'completed', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'completed'
AND o.customer_id <> (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
);

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'pending', o.customer_id
FROM orders o
WHERE o.status = 'ready';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'accepted', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'ready';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'preparing', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'ready';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'ready', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'ready';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'pending', o.customer_id
FROM orders o
WHERE o.status = 'preparing';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'accepted', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'preparing';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'preparing', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'preparing';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'pending', o.customer_id
FROM orders o
WHERE o.status = 'accepted';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'accepted', f.user_id
FROM orders o
JOIN farmers f ON f.id = o.farmer_id
WHERE o.status = 'accepted';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'pending', o.customer_id
FROM orders o
WHERE o.status = 'pending';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'pending', o.customer_id
FROM orders o
WHERE o.status = 'cancelled';

INSERT INTO order_status_history (order_id, status, changed_by)
SELECT o.id, 'cancelled', o.customer_id
FROM orders o
WHERE o.status = 'cancelled';

INSERT INTO reviews (customer_id, farmer_id, product_id, order_id, rating, comment, status, farmer_response, farmer_response_at)
SELECT
    o.customer_id,
    o.farmer_id,
    p.id,
    o.id,
    5,
    'The tomatoes were fresh and excellent quality.',
    'approved',
    'Thank you for your kind review.',
    NOW()
FROM orders o
JOIN products p ON p.name = 'tomatoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'daniel.brooks@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO reviews (customer_id, farmer_id, product_id, order_id, rating, comment, status, farmer_response, farmer_response_at)
SELECT
    o.customer_id,
    o.farmer_id,
    p.id,
    o.id,
    4,
    'Everything was fresh and well prepared.',
    'approved',
    'We appreciate your feedback.',
    NOW()
FROM orders o
JOIN products p ON p.name = 'cucumbers'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'emily.carter@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO reviews (customer_id, farmer_id, product_id, order_id, rating, comment, status, farmer_response, farmer_response_at)
SELECT
    o.customer_id,
    o.farmer_id,
    p.id,
    o.id,
    5,
    'The fruit was fresh and flavorful.',
    'approved',
    'Thank you. We are glad you enjoyed it.',
    NOW()
FROM orders o
JOIN products p ON p.name = 'mangoes'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'michael.turner@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO reviews (customer_id, farmer_id, product_id, order_id, rating, comment, status, farmer_response, farmer_response_at)
SELECT
    o.customer_id,
    o.farmer_id,
    p.id,
    o.id,
    5,
    'The dairy products were fresh and carefully packaged.',
    'approved',
    'Thank you for supporting our farm.',
    NOW()
FROM orders o
JOIN products p ON p.name = 'cow milk'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'sophia.bennett@marketlink.demo'
)
AND o.status = 'completed';

INSERT INTO reviews (customer_id, farmer_id, product_id, order_id, rating, comment, status, farmer_response, farmer_response_at)
SELECT
    o.customer_id,
    o.farmer_id,
    p.id,
    o.id,
    4,
    'The produce was fresh and good quality.',
    'pending',
    NULL,
    NULL
FROM orders o
JOIN products p ON p.name = 'bananas'
WHERE o.customer_id = (
    SELECT id FROM users WHERE email = 'james.wilson@marketlink.demo'
)
AND o.status = 'completed';

COMMIT;

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
    'uploads/products/whole_wheat_flour.jpg',
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
    'uploads/products/local_wheat.jpg',
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
    'uploads/products/homemade_tomato_sauce.jpg',
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
    'uploads/products/mixed_vegetable_pickles.jpg',
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
    'uploads/products/homemade_strawberry_jam.jpg',
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
    'uploads/products/fresh_chicken.jpg',
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
    'uploads/products/fresh_goat_meat.jpg',
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

