# MarketLink

MarketLink is a web-based marketplace that connects local farmers and markets with customers. Customers can browse products, check out farmers and markets, and place orders, while farmers manage their own products and orders on their side of the system.

There are three user roles: **Customer**, **Farmer**, and **Admin**.

## What MarketLink Can Do

### Customers

* Register and log in
* Browse products, farmers, and markets
* Search and filter products
* View product and farmer details
* Add products to the cart
* Place orders
* View and manage their orders
* Confirm or cancel orders
* Add products, farmers, and markets to favorites
* Leave reviews and ratings
* View notifications
* Manage their profile

### Farmers

* Register as a farmer
* Manage their profile and stall information
* Add new products
* Edit and manage existing products
* Update stock
* Manage pickup slots
* View customer orders
* Update order status
* View inventory and sales information
* Receive notifications

### Admin

The admin side runs the platform as a whole:

* User management
* Customer management
* Farmer management and approval
* Product management
* Category management
* Market management
* Announcements
* Notifications
* Reports
* Review management
* Dashboard statistics

## Technologies Used

* PHP 8.x — application logic and backend
* MySQL / MariaDB — database
* HTML5 — page structure
* CSS3 — styling
* JavaScript — interactive features
* XAMPP — local development environment
* Apache — web server
* phpMyAdmin — database management
* Leaflet + OpenStreetMap — maps

## Project Structure

```text
MarketLink/
│
├── config/          Configuration and database connection
├── includes/        Shared PHP files and helpers
├── actions/         Notification-related actions
├── auth/            Registration, login, logout, and password pages
├── customer/        Customer pages and actions
├── farmer/          Farmer pages and actions
├── admin/           Admin pages and management tools
├── api/             API endpoints
├── database/        Database and seed SQL files
├── public/          Public-facing pages
├── assets/          CSS, JavaScript, and images
├── uploads/         Uploaded files
├── documentation/   Project report, diagrams, and screenshots
├── index.php        Main page
└── README.md
```

## Requirements

* XAMPP
* PHP 8.x or later
* Apache
* MySQL or MariaDB
* A modern web browser

## Running the Project

Drop the project folder into:

```text
C:\xampp\htdocs\
```

so the final path looks like:

```text
C:\xampp\htdocs\MarketLink
```

Start Apache and MySQL from the XAMPP Control Panel.

The database is called:

```text
marketlink
```

and the SQL files you need are in:

```text
MarketLink/database/
```

Full installation steps, including the database setup, are also in Section 12 — Installation Guide of:

```text
MarketLink/documentation/MarketLink_Project_Report.md
```

Once that's done, open:

```text
http://localhost/MarketLink/
```

## Demo Accounts

There are several test accounts in the database. Here's one for each role:

### Admin

```text
Email: marcus.reed@marketlink.demo
Password: Demo123!
```

### Farmer

```text
Email: amanda.foster@marketlink.demo
Password: Demo123!
```

### Customer

```text
Email: daniel.brooks@marketlink.demo
Password: Demo123!
```

These are just sample accounts for testing — there are more in the database if you need them.

## Database

The database is `marketlink` and has 19 tables, covering things like:

* Users and farmers
* Products and categories
* Markets
* Farmer-market relationships
* Orders and order items
* Order status history
* Pickup slots
* Weekly stock
* Favorites
* Reviews
* Notifications
* Announcements
* Reports

Database files:

```text
MarketLink/database/marketlink.sql
MarketLink/database/seed.sql
```

The full ERD and table breakdown is in the documentation folder.

## API

There's a small PHP API for product data, for example:

```text
GET /MarketLink/api/products.php
```

Returns JSON.

## Maps

Markets store latitude and longitude in the database, and those locations show up on a map using Leaflet and OpenStreetMap.

## Security

We tried to bake security in as we went rather than bolting it on at the end. That includes:

* Password hashing and verification
* Prepared SQL statements
* Session-based authentication
* Role-based access control
* CSRF protection
* Input validation
* Output escaping
* File upload validation
* File type and size restrictions

## Testing

We tested the main parts of the system as we built them — registration, login, permissions, products, favorites, cart, orders, farmer management, admin functions, database operations, navigation, and error handling.

We also ran security checks for things like SQL injection, unauthorized access, invalid IDs, form submissions, and file uploads.

More detail on the testing process is in:

```text
MarketLink/documentation/MarketLink_Project_Report.md
```

under Section 13 — Testing and Verification.

## Documentation

The `documentation` folder has the full project report plus the diagrams and workflow images used in it.

Main report:

```text
MarketLink/documentation/MarketLink_Project_Report.md
```

It covers everything too long for this README — problem definition, objectives, database design, system architecture, implementation process, security details, API and map integration, installation guide, testing, and diagrams.

Right now the folder looks like this:

```text
documentation/
├── MarketLink_Project_Report.md
├── dfd-level-0.png
├── dfd-level-1.png
├── entity-relationship-diagram.png
├── use-case-diagram.png
├── admin-management.png
├── customer-order-process.png
├── farmer-order-management.png
└── farmer-product-management.png
```

## Notes

MarketLink was built and tested locally with XAMPP. The submission includes the source code, database files, uploaded files, and documentation needed to run and review the system.