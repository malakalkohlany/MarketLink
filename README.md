# MarketLink

MarketLink is a web-based local marketplace platform that connects
customers with local farmers and helps farmers promote and sell their
products within their community.

## Features



## Technologies



## Project Structure

```text
MarketLink/
│
├── config/
│   ├── config.php
│   ├── constants.php
│   └── database.php
│
├── includes/
│   ├── auth.php
│   ├── csrf.php
│   ├── flash.php
│   ├── functions.php
│   ├── include.php
│   ├── navbar.php
│   ├── session.php
│   ├── sidebar.php
│   └── validation.php
│
├── actions/
│   ├── get_notifications_count.php
│   ├── mark_all_notifications_read.php
│   └── mark_notifications_read.php
│
├── auth/
│   ├── register.php
│   ├── register_process.php
│   ├── forgot_password.php
│   ├── login.php
│   ├── login_process.php
│   └── logout.php
│
├── customer/
│   ├── add_to_cart.php
│   ├── cart.php
│   ├── checkout.php
│   ├── dashboard.php
│   ├── farmers.php
│   ├── farmer_details.php
│   ├── favorites.php
│   ├── markets.php
│   ├── market_details.php
│   ├── notifications.php
│   ├── orders.php
│   ├── products.php
│   ├── product_details.php
│   ├── profile.php
│   ├── remove_from_cart.php
│   ├── update_cart.php
│   └── reviews.php
│
├── farmer/
│   ├── add_products.php
│   ├── analytics.php
│   ├── dashboard.php
│   ├── edit_product.php
│   ├── edit_profile.php
│   ├── inventory.php
│   ├── markets.php
│   ├── notifications.php
│   ├── orders.php
│   ├── order_details.php
│   ├── pending.php
│   ├── pickup_slots.php
│   ├── products.php
│   ├── profile.php
│   ├── stall.php
│   └── rejected.php
│
├── admin/
│   ├── add_market.php
│   ├── announcements.php
│   ├── categories.php
│   ├── customer_details.php
│   ├── customers.php
│   ├── dashboard.php
│   ├── edit_market.php
│   ├── farmers.php
│   ├── farmer_details.php
│   ├── markets.php
│   ├── notifications.php
│   ├── products.php
│   ├── reports.php
│   └── reviews.php
│
├── database/
│   ├── marketlink.sql
│   └── seed.sql
│
├── public/
│   ├── about.php
│   ├── contact.php
│   ├── farmers.php
│   └── markets.php
│
├── assets/
│   ├── css/
│   │   ├── auth.css
│   │   ├── base.css
│   │   ├── components.css
│   │   ├── dashboard.css
│   │   ├── leaflet.css
│   │   ├── navbar.css
│   │   └── sidebar.css
│   │
│   ├── js/
│   │   ├── app.js
│   │   ├── cart.js
│   │   ├── dashboard.js
│   │   ├── leaflet.js
│   │   ├── login.js
│   │   ├── navbar.css
│   │   └── register.js
│   │
│   └── images/
│
├── uploads/
│
├── index.php
└── README.md
```

## Requirements

Before running MarketLink, install:

- XAMPP
- Apache
- MySQL
- PHP 8.x or later
- A modern web browser