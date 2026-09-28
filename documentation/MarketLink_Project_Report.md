# MarketLink

## Local Farmers and Markets E-Commerce Platform

**Competition:** TechWiz Competition - Aptech
**Team Name:** Potentia

**Team Members:**

* Malak Al-Kohlany
* Leen Al-Shargabi
* Roaa Badi
* Sokina Al-Tahish
* Raghad Al-Sharjbi

---

# Table of Contents

1. Project Overview
2. Problem Definition
3. Proposed Solution
4. Project Objectives
5. Technologies Used
6. Database Design
7. System Architecture
8. User Interface Design
9. Implementation Process
10. Security Features
11. API and Map Integration
12. Installation Guide
13. Testing and Verification
14. Screenshots
15. Conclusion

---

# 1. Project Overview

MarketLink is a web platform we built to connect customers with local farmers and markets in one place.

The idea came from a simple gap: there wasn't really an organized way for someone to find out what local farmers were selling, check prices and stock, and actually place an order without a lot of back and forth. MarketLink tries to fix that by putting product browsing, farmer profiles, market info, and ordering all under one roof.

Customers can browse products, search and filter what they need, save products or farmers to favorites, manage a shopping cart, place orders, and leave reviews. Farmers get their own side of the platform where they can list products, keep stock updated, and handle incoming orders. Admins sit on top of all of it, managing users, approving farmers, and keeping the platform data in order.

We built it with PHP, MySQL/MariaDB, HTML, CSS, and JavaScript, running locally through XAMPP during development.

---

# 2. Problem Definition

If you're a customer, finding local farmers and markets usually means asking around, checking social media, or just showing up somewhere and hoping. There isn't a central place to compare prices, see what's in stock, or find a market near you.

On the other side, farmers don't have many good options for putting their products online or keeping track of orders once customers start reaching out.

MarketLink was our attempt to bring both sides together — a platform where customers can actually search and browse properly, and farmers get real tools to manage what they're selling.

---

# 3. Proposed Solution

We split the platform around three types of users, each with their own set of features.

## Customer Features

* Browse available products
* Search and filter products
* View product details
* Add products, farmers, and markets to favorites
* Add products to the shopping cart
* Place orders
* Track order status
* Submit reviews and ratings

## Farmer Features

* Manage farmer profile information
* Add, edit, and manage products
* Update stock availability
* View and manage customer orders
* Manage market and pickup information
* Receive notifications

## Admin Features

* Manage users
* Approve or reject farmer registrations
* Manage products and categories
* Manage markets
* Manage announcements
* Monitor reviews and reports
* View system statistics

---

# 4. Project Objectives

Going into this project, we set a few goals for ourselves:

1. Connect customers with local farmers and markets through a single platform.
2. Give customers an actual organized way to find agricultural products.
3. Make searching and ordering simple, not something you have to think about.
4. Give farmers real tools to manage their products and orders.
5. Build in a review and feedback system so trust builds over time.
6. Give admins the ability to manage the platform without digging through the database directly.
7. Keep access properly restricted based on each user's role.

---

# 5. Technologies Used

| Technology      | Purpose                              |
| --------------- | ------------------------------------ |
| PHP             | Backend development and system logic |
| MySQL / MariaDB | Database storage                     |
| phpMyAdmin      | Database management                  |
| HTML            | Page structure                       |
| CSS             | Interface styling                    |
| JavaScript      | Interactive features                 |
| XAMPP           | Local development environment        |

We stuck with PHP and MySQL mainly because it was the stack we were most comfortable with as a team, and it gave us everything we needed without overcomplicating the setup.

---

# 6. Database Design

The database behind MarketLink is relational, and we named it:

```
marketlink
```

It's made up of 19 tables in total:

| Table                  | Purpose                                       |
| ---------------------- | --------------------------------------------- |
| users                  | Stores user accounts and roles                |
| farmers                | Stores farmer information and approval status |
| categories             | Stores product categories                     |
| products               | Stores products and stock information         |
| markets                | Stores market information                     |
| market_farmer          | Connects farmers with markets                 |
| favorite_products      | Stores customer product favorites             |
| favorite_farmers       | Stores customer farmer favorites              |
| favorite_markets       | Stores customer market favorites              |
| orders                 | Stores customer orders                        |
| order_items            | Stores products inside orders                 |
| order_status_history   | Stores order status changes                   |
| pickup_slots           | Stores pickup schedules                       |
| weekly_stock           | Stores weekly stock information               |
| weekly_stock_templates | Stores reusable stock templates               |
| reviews                | Stores customer reviews                       |
| notifications          | Stores system notifications                   |
| announcements          | Stores admin announcements                    |
| reports                | Stores generated reports                      |

Primary keys identify records, and foreign keys tie related tables together. A few relationships worth pointing out:

* A farmer can have multiple products.
* A customer can place multiple orders.
* Farmers and markets connect through a many-to-many relationship via `market_farmer`.

---

# 7. System Architecture

We kept the architecture fairly simple — three layers, nothing fancy.

## User Interface Layer

This is what customers, farmers, and admins actually see and click through in the browser.

## Application Layer

PHP handles everything happening behind the scenes here: authentication, authorization, processing requests, validating input, and talking to the database.

## Database Layer

MySQL/MariaDB holds all of it — users, products, orders, reviews, markets, notifications, the works.

Roughly, the flow looks like this:

```
User Browser
      |
      v
PHP Application
(Authentication,
Business Logic,
Validation)
      |
      v
MySQL/MariaDB Database
```

---

# 8. User Interface Design

We wanted the interface to feel simple rather than overloaded with options, so we split it by role:

* Public pages for anyone just browsing.
* Customer pages for shopping and tracking orders.
* Farmer pages for managing products and orders.
* Admin pages for running the platform.

Navigation was something we paid extra attention to — it's easy to end up with a UI where users can't find their way back to where they started, so we tried to keep the important actions within easy reach on every page.

---

# 9. Implementation Process

Here's roughly how the build went, stage by stage:

1. Went through the requirements and figured out what each user type actually needed.
2. Designed the database structure around that.
3. Built out the pages and interfaces.
4. Added authentication and role-based access.
5. Built the product, order, favorites, and review features.
6. Added the farmer and admin management tools on top.
7. Tested everything and fixed what broke.

It wasn't a perfectly linear process — we went back and adjusted the database a few times as new requirements came up during frontend and backend work — but this is the general order things came together in.

---

# 10. Security Features

We didn't want to treat security as an afterthought, so a few things were built in from early on:

* User authentication for every protected page.
* Role-based access control, so a customer can't reach farmer or admin pages.
* Password hashing instead of storing anything in plain text.
* Input validation on forms and user-submitted data.
* Session management to keep login state secure.
* Protection on important form submissions.

---

# 11. API and Map Integration

## API

MarketLink includes a small set of API endpoints so system data can be requested in a structured way, using standard JSON responses.

For example:

```
GET /MarketLink/api/products.php
```

This returns product information as JSON.

## Map Integration

Markets store their location as latitude and longitude values in the database, which lets us tie each market to a real place on a map rather than just a text address.

The database keeps track of:

* Latitude
* Longitude
* Map provider information

---

# 12. Installation Guide

## Requirements

Before running the project locally, you'll need:

* XAMPP
* PHP
* MySQL/MariaDB
* A web browser

## Steps

1. Copy the project folder into:

```
C:\xampp\htdocs\
```

2. Make sure the folder is named:

```
MarketLink
```

3. Start Apache and MySQL from the XAMPP Control Panel.

4. Open phpMyAdmin:

```
http://localhost/phpmyadmin
```

5. Create a database called:

```
marketlink
```

6. Import the database SQL file (marketlink.sql) & (seed.sql) from database/.

7. Open the project in your browser:

```
http://localhost/MarketLink/
```

That should get the system running locally.

---

# 13. Testing and Verification

We tested the main workflows as we built them rather than leaving it all to the end, covering things like:

* Registration and login for each role.
* Making sure permissions were actually enforced between roles.
* Product browsing and search.
* Adding and removing favorites.
* Shopping cart behavior.
* Order creation from start to finish.
* Farmer-side order management.
* Admin management tools.
* Database operations.
* General navigation between pages.

This caught a fair number of issues early, which saved us from finding them right before submission.

---

# 14. Screenshots

Diagrams and screenshots of the system are included in the documentation folder:

```
documentation/
```

Included files:

* dfd-level-0.png
* dfd-level-1.png
* entity-relationship-diagram.png
* use-case-diagram.png
* admin-management.png
* customer-order-process.png
* farmer-order-management.png
* farmer-product-management.png

These cover the system design, database relationships, and the main workflows.

---

# 15. Conclusion

MarketLink brings customers, farmers, and markets together in one platform instead of leaving them to figure things out separately.

Building it gave us a chance to actually apply what we'd learned about web development, database design, and system design to something real, rather than just a set of isolated exercises. There's more we'd like to add if we kept working on it, but as it stands, it covers the core problem we set out to solve.