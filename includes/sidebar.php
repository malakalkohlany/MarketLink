<?php

require_once __DIR__ . '/include.php';

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';

function isPage(array $pages): bool
{
    global $current_page;

    return in_array($current_page, $pages, true);
}

function isActive(array $pages): string
{
    return isPage($pages) ? 'active' : '';
}

function isSubActive(string $page): string
{
    global $current_page;

    return $current_page === $page ? 'active' : '';
}

function hasSubmenu(array $pages): bool
{
    return isPage($pages);
}
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
    <span>
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
    </span>
</div>

    <?php if ($role === 'customer'): ?>

        <!-- Marketplace -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Marketplace
            </div>

            <ul class="sidebar-list">

                <!-- Dashboard -->
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/dashboard.php"
                        class="<?= isActive(['dashboard.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="layout-dashboard"></i>
                        </span>

                        <span>Dashboard</span>
                    </a>
                </li>


                <!-- Products -->
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/products.php"
                        class="<?= isActive([
                            'products.php',
                            'product_details.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="package"></i>
                        </span>

                        <span>Products</span>
                    </a>
                </li>


                <!-- Farmers -->
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/farmers.php"
                        class="<?= isActive([
                            'farmers.php',
                            'farmer_details.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="sprout"></i>
                        </span>

                        <span>Farmers</span>
                    </a>
                </li>


                <!-- Markets -->
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/markets.php"
                        class="<?= isActive([
                            'markets.php',
                            'market_details.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="map-pin"></i>
                        </span>

                        <span>Markets</span>
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>customer/favorites.php"
                        class="<?= isActive(['favorites.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="heart"></i>
                        </span>

                        <span>Favorites</span>
                    </a>
                </li>

            </ul>

        </div>


        <!-- Orders -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Orders
            </div>

            <ul class="sidebar-list">

                <!-- Cart -->
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/cart.php"
                        class="<?= isActive(['cart.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="shopping-basket"></i>
                        </span>

                        <span>Cart</span>
                    </a>
                </li>


                <!-- Orders -->
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/orders.php"
                        class="<?= isActive(['orders.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="clipboard-list"></i>
                        </span>

                        <span>My Orders</span>
                    </a>
                </li>
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/reviews.php"
                        class="<?= isActive(['reviews.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="message-square-heart"></i>
                        </span>

                        <span>My Reviews</span>
                    </a>
                </li>


            </ul>

        </div>


        <!-- Account -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Account
            </div>

            <ul class="sidebar-list">

                <!-- Profile -->
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/profile.php"
                        class="<?= isActive([
                            'profile.php',
                            'edit_profile.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="user-round"></i>
                        </span>

                        <span>Profile</span>
                    </a>
                </li>

                <?php if (hasSubmenu([
                        'profile.php',
                        'edit_profile.php'
                    ])): ?>

                        <div class="sidebar-submenu">

                            <a
                                href="<?= BASE_URL ?>customer/edit_profile.php"
                                class="sidebar-sublink <?= isSubActive('edit_profile.php') ?>"
                            >
                                <span class="nav-subicon">
                                    <i data-lucide="user-pen"></i>
                                </span>

                                <span>Edit Profile</span>
                            </a>

                        </div>

                    <?php endif; ?>



                <!-- Notifications -->
                <li>
                    <a
                        href="<?= BASE_URL ?>customer/notifications.php"
                        class="<?= isActive(['notifications.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="bell"></i>
                        </span>

                        <span>Notifications</span>
                    </a>
                </li>

            </ul>

        </div>


    <?php elseif ($role === 'farmer'): ?>

        <!-- Overview -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Overview
            </div>

            <ul class="sidebar-list">

                <!-- Dashboard -->
                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/dashboard.php"
                        class="<?= isActive(['dashboard.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="layout-dashboard"></i>
                        </span>

                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Analytics -->
                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/analytics.php"
                        class="<?= isActive(['analytics.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="chart-no-axes-combined"></i>
                        </span>

                        <span>Analytics</span>
                    </a>
                </li>

            </ul>

        </div>


        <!-- Products -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Products
            </div>

            <ul class="sidebar-list">

                <!-- Products -->
                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/products.php"
                        class="<?= isActive([
                            'products.php',
                            'add_product.php',
                            'edit_product.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="package"></i>
                        </span>

                        <span>My Products</span>
                    </a>

                    <?php if (hasSubmenu([
                        'products.php',
                        'add_product.php',
                        'edit_product.php'
                    ])): ?>

                        <div class="sidebar-submenu">

                            <a
                                href="<?= BASE_URL ?>farmer/add_product.php"
                                class="sidebar-sublink <?= isSubActive('add_product.php') ?>"
                            >
                                <span class="nav-subicon">
                                    <i data-lucide="plus"></i>
                                </span>

                                <span>Add Product</span>
                            </a>

                        </div>

                    <?php endif; ?>
                </li>

                <!-- Inventory -->
                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/inventory.php"
                        class="<?= isActive(['inventory.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="boxes"></i>
                        </span>

                        <span>Inventory</span>
                    </a>
                </li>

            </ul>

        </div>


        <!-- Orders -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Orders
            </div>

            <ul class="sidebar-list">

                <!-- Orders -->
                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/orders.php"
                        class="<?= isActive([
                            'orders.php',
                            'order_details.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="clipboard-list"></i>
                        </span>

                        <span>My Orders</span>
                    </a>
                </li>

            </ul>

        </div>


        <!-- Stall -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Stall
            </div>

            <ul class="sidebar-list">



                <!-- Markets -->
                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/markets.php"
                        class="<?= isActive([
                            'markets.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="map-pin"></i>
                        </span>

                        <span>Markets</span>
                    </a>
                </li>


                <!-- Pickup Slots -->
                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/pickup_slots.php"
                        class="<?= isActive(['pickup_slots.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="clock-3"></i>
                        </span>

                        <span>Pickup Slots</span>
                    </a>
                </li>


            </ul>

        </div>


        <!-- Account -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Account
            </div>

            <ul class="sidebar-list">

                <!-- Profile -->
                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/profile.php"
                        class="<?= isActive([
                            'profile.php',
                            'edit_profile.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="user-round"></i>
                        </span>

                        <span>Profile</span>
                    </a>

                    <?php if (hasSubmenu([
                        'profile.php',
                        'edit_profile.php'
                    ])): ?>

                        <div class="sidebar-submenu">

                            <a
                                href="<?= BASE_URL ?>farmer/edit_profile.php"
                                class="sidebar-sublink <?= isSubActive('edit_profile.php') ?>"
                            >
                                <span class="nav-subicon">
                                    <i data-lucide="user-pen"></i>
                                </span>

                                <span>Edit Profile</span>
                            </a>

                        </div>

                    <?php endif; ?>
                </li>


                <!-- Notifications -->
                <li>
                    <a
                        href="<?= BASE_URL ?>farmer/notifications.php"
                        class="<?= isActive(['notifications.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="bell"></i>
                        </span>

                        <span>Notifications</span>
                    </a>
                </li>

            </ul>

        </div>

    <?php elseif ($role === 'admin'): ?>

        <!-- Overview -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Overview
            </div>

            <ul class="sidebar-list">

                <!-- Dashboard -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/dashboard.php"
                        class="<?= isActive(['dashboard.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="layout-dashboard"></i>
                        </span>

                        <span>Dashboard</span>
                    </a>
                </li>

            </ul>

        </div>


        <!-- Management -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Management
            </div>

            <ul class="sidebar-list">

                <!-- Farmers -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/farmers.php"
                        class="<?= isActive([
                            'farmers.php',
                            'farmer_details.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="sprout"></i>
                        </span>

                        <span>Farmers</span>
                    </a>
                </li>


                <!-- Customers -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/customers.php"
                        class="<?= isActive([
                            'customers.php',
                            'customer_details.php',
                            'users.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="users"></i>
                        </span>

                        <span>Customers</span>
                    </a>

                    <?php if (isPage([
                        'customers.php',
                            'customer_details.php',
                            'users.php'
                    ])): ?>

                        <div class="sidebar-submenu">

                            <a
                                href="<?= BASE_URL ?>admin/users.php"
                                class="sidebar-sublink <?= isSubActive('users.php') ?>"
                            >
                                <span class="nav-subicon">
                                    <i data-lucide="pencil"></i>
                                </span>

                                <span>Manages Users</span>
                            </a>


                        </div>

                    <?php endif; ?>
                </li>


                <!-- Products -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/products.php"
                        class="<?= isActive(['products.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="package"></i>
                        </span>

                        <span>Products</span>
                    </a>
                </li>

                <li>
                    <a
                        href="<?= BASE_URL ?>admin/orders.php"
                        class="<?= isActive(['orders.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="shopping-basket"></i>
                        </span>

                        <span>Orders</span>
                    </a>
                </li>


                <!-- Categories -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/categories.php"
                        class="<?= isActive(['categories.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="tags"></i>
                        </span>

                        <span>Categories</span>
                    </a>
                </li>


                <!-- Markets -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/markets.php"
                        class="<?= isActive([
                            'markets.php',
                            'add_market.php',
                            'edit_market.php'
                        ]) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="map-pin"></i>
                        </span>

                        <span>Markets</span>
                    </a>

                    <!-- Market sub-links -->
                    <?php if (isPage([
                        'markets.php',
                        'add_market.php',
                        'edit_market.php'
                    ])): ?>

                        <div class="sidebar-submenu">

                            <a
                                href="<?= BASE_URL ?>admin/add_market.php"
                                class="sidebar-sublink <?= isSubActive('add_market.php') ?>"
                            >
                                <span class="nav-subicon">
                                    <i data-lucide="plus"></i>
                                </span>

                                <span>Add Market</span>
                            </a>


                        </div>

                    <?php endif; ?>

                </li>

            </ul>

        </div>


        <!-- System -->
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                System
            </div>

            <ul class="sidebar-list">

                <!-- Announcements -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/announcements.php"
                        class="<?= isActive(['announcements.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="megaphone"></i>
                        </span>

                        <span>Announcements</span>
                    </a>
                </li>


                <!-- Notifications -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/notifications.php"
                        class="<?= isActive(['notifications.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="bell"></i>
                        </span>

                        <span>Notifications</span>
                    </a>
                </li>


                <!-- Reviews -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/reviews.php"
                        class="<?= isActive(['reviews.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="message-square-heart"></i>
                        </span>

                        <span>Reviews</span>
                    </a>
                </li>


                <!-- Reports -->
                <li>
                    <a
                        href="<?= BASE_URL ?>admin/reports.php"
                        class="<?= isActive(['reports.php']) ?>"
                    >
                        <span class="nav-icon">
                            <i data-lucide="file-chart-column"></i>
                        </span>

                        <span>Reports</span>
                    </a>
                </li>

            </ul>

        </div>

    <?php endif; ?>

    <div class="sidebar-bottom">

        <ul class="sidebar-list">

            <li>
                <a href="<?= BASE_URL ?>auth/logout.php">

                    <span class="nav-icon">
                        <i data-lucide="log-out"></i>
                    </span>

                    <span>Log out</span>

                </a>
            </li>

        </ul>

    </div>

</aside>

<script src="../assets/js/lucide.js"></script>

<script>
    lucide.createIcons();
</script>