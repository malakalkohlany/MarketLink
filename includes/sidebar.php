<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';

$current_page = basename($_SERVER['PHP_SELF']);

$role = $_SESSION['role'] ?? '';

?>

<aside class="sidebar">

    <div class="sidebar-brand">

        <div class="brand-mark">
            M
        </div>

        <div class="brand-name">
            MarketLink
        </div>

    </div>

    <div class="sidebar-role">

        <span class="role-dot"></span>

        <?php
        if ($role === 'customer') {
            echo 'Customer';
        } elseif ($role === 'farmer') {
            echo 'Farmer';
        } elseif ($role === 'admin') {
            echo 'Administrator';
        } else {
            echo 'Account';
        }
        ?>

    </div>

    <?php if ($role === 'customer'): ?>

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Marketplace
            </div>

            <ul class="sidebar-list">
           
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/dashboard.php"
                        class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">⌂</span>
                        Dashboard
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>customer/products.php"
                        class="<?= $current_page === 'products.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">▣</span>
                        Products
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>customer/farmers.php"
                        class="<?= $current_page === 'farmers.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">♙</span>
                        Farmers
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>customer/markets.php"
                        class="<?= $current_page === 'markets.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">⌖</span>
                        Markets
                    </a>
                </li>

            </ul>

        </div>


        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Orders
            </div>

            <ul class="sidebar-list">

                <li>
                    <a
                        href="<?= BASE_URL ?>customer/cart.php"
                        class="<?= $current_page === 'cart.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">▣</span>
                        Cart
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>customer/orders.php"
                        class="<?= $current_page === 'orders.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">✓</span>
                        My Orders
                    </a>
                </li>

            </ul>

        </div>


        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Account
            </div>

            <ul class="sidebar-list">

                <li>
                    <a
                        href="<?= BASE_URL ?>customer/profile.php"
                        class="<?= $current_page === 'profile.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">○</span>
                        Profile
                    </a>
                </li>

            </ul>

        </div>


    <?php elseif ($role === 'farmer'): ?>

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Farmer
            </div>

            <ul class="sidebar-list">

                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/dashboard.php"
                        class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">⌂</span>
                        Dashboard
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/products.php"
                        class="<?= $current_page === 'products.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">▣</span>
                        My Products
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/add_product.php"
                        class="<?= $current_page === 'add_product.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">＋</span>
                        Add Product
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/orders.php"
                        class="<?= $current_page === 'orders.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">✓</span>
                        My Orders
                    </a>
                </li>

            </ul>

        </div>


        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Stall
            </div>

            <ul class="sidebar-list">

                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/stall.php"
                        class="<?= $current_page === 'stall.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">⌂</span>
                        My Stall
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/pickup.php"
                        class="<?= $current_page === 'pickup.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">◷</span>
                        Pickup Slots
                    </a>
                </li>

            </ul>

        </div>


        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Account
            </div>

            <ul class="sidebar-list">

                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/profile.php"
                        class="<?= $current_page === 'profile.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">○</span>
                        Profile
                    </a>
                </li>

            </ul>

        </div>


    <?php elseif ($role === 'admin'): ?>

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Overview
            </div>

            <ul class="sidebar-list">

                <li>
                    <a
                        href="<?= BASE_URL ?>admin/dashboard.php"
                        class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">⌂</span>
                        Dashboard
                    </a>
                </li>

            </ul>

        </div>


        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Management
            </div>

            <ul class="sidebar-list">

                <li>
                    <a
                        href="<?= BASE_URL ?>admin/farmers.php"
                        class="<?= $current_page === 'farmers.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">♙</span>
                        Farmers
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>admin/products.php"
                        class="<?= $current_page === 'products.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">▣</span>
                        Products
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>admin/users.php"
                        class="<?= $current_page === 'users.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">●</span>
                        Users
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>admin/orders.php"
                        class="<?= $current_page === 'orders.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">✓</span>
                        Orders
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>admin/markets.php"
                        class="<?= $current_page === 'markets.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">⌖</span>
                        Markets
                    </a>
                </li>

            </ul>

        </div>


        <div class="sidebar-section">

            <div class="sidebar-section-title">
                System
            </div>

            <ul class="sidebar-list">

                <li>
                    <a
                        href="<?= BASE_URL ?>admin/notifications.php"
                        class="<?= $current_page === 'notifications.php' ? 'active' : '' ?>"
                    >
                        <span class="nav-icon">♢</span>
                        Notifications
                    </a>
                </li>

            </ul>

        </div>

    <?php endif; ?>


    <div class="sidebar-bottom">

        <ul class="sidebar-list">

            <li>
                <a href="<?= BASE_URL ?>auth/logout.php">
                    <span class="nav-icon">↪</span>
                    Log out
                </a>
            </li>

        </ul>

    </div>
</aside>
