<?php 

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../includes/session.php';

requireRole('farmer');
requireApprovedFarmer();

$user_id = getUserId();

$farmer = null;

$stmt = $conn->prepare("
    SELECT
        id,
        stall_name,
        description,
        address,
        approval_status
    FROM farmers
    WHERE id = ? AND user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $farmer_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

$stmt->close();


$orders = [];

$stmt = $conn->prepare("
    SELECT
        o.id,
        u.name AS customer_name,
        m.name AS market_name,
        o.status,
        o.subtotal,
        o.created_at
    FROM orders o
    JOIN users u
        ON o.customer_id = u.id
    JOIN markets m
        ON o.market_id = m.id
    WHERE o.farmer_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");

$stmt->bind_param("i", $farmer_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();

$products = [];

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.unit,
        p.stock_quantity,
        p.image,
        p.is_available,
        p.moderation_status,
        p.created_at
    FROM products p
    WHERE p.farmer_id = ?
    ORDER BY p.created_at DESC
    LIMIT 4
");

$stmt->bind_param("i", $farmer_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="main-content">

        <section class="welcome">

            <div>

                <h1>
                    Welcome,
                    <?= htmlspecialchars($_SESSION['name']) ?>!
                </h1>

                <p>
                    Manage your stall, products, and customer orders.
                </p>

            </div>

        </section>


        <section class="quick-actions">

            <a href="add_product.php" class="dashboard-action">

                <h3>Add Product</h3>

                <p>
                    Add a new product to your stall.
                </p>

            </a>


            <a href="products.php" class="dashboard-action">

                <h3>Manage Products</h3>

                <p>
                    View and manage your listed products.
                </p>

            </a>


            <a href="orders.php" class="dashboard-action">

                <h3>View Orders</h3>

                <p>
                    Review customer orders and pickup requests.
                </p>

            </a>


            <a href="profile.php" class="dashboard-action">

                <h3>Manage Stall</h3>

                <p>
                    Update your stall information and details.
                </p>

            </a>

        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <h2>Recent Orders</h2>

                <a href="orders.php">
                    View all →
                </a>

            </div>


            <?php if (empty($orders)): ?>

                <div class="empty-state">

                    <h3>No orders yet</h3>

                    <p>
                        Customer orders will appear here once they place an order.
                    </p>

                </div>

            <?php else: ?>

                <div class="orders-table-wrapper">

                    <table class="orders-table">

                        <thead>

                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Market</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Pickup</th>
                                <th>Status</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($orders as $order): ?>

                                <tr>

                                    <td>
                                        #<?= (int) $order['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $order['customer_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $order['market_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            'M j, Y',
                                            strtotime($order['created_at'])
                                        ) ?>
                                    </td>

                                    <td>
                                        $<?= number_format(
                                            (float) $order['subtotal'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        Pickup
                                    </td>

                                    <td>

                                        <span
                                            class="order-status <?= htmlspecialchars(
                                                $order['status']
                                            ) ?>"
                                        >
                                            <?= ucfirst(
                                                htmlspecialchars(
                                                    $order['status']
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <h2>My Products</h2>

                <a href="products.php">
                    View all →
                </a>

            </div>


            <?php if (empty($products)): ?>

                <div class="empty-state">

                    <h3>No products yet</h3>

                    <p>
                        Add your first product to start selling.
                    </p>

                    <a href="add_product.php">
                        Add Product
                    </a>

                </div>

            <?php else: ?>

                <div class="product-grid">

                    <?php foreach ($products as $product): ?>

                        <a
                            href="product_edit.php?id=<?= (int) $product['id'] ?>"
                            class="product-card"
                        >

                            <div class="product-image">

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="../uploads/products/<?= htmlspecialchars(
                                            $product['image']
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product['name']
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <span>
                                        No image
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="product-info">

                                <h3>
                                    <?= htmlspecialchars(
                                        $product['name']
                                    ) ?>
                                </h3>


                                <div class="product-price">

                                    <strong>
                                        $<?= number_format(
                                            (float) $product['price'],
                                            2
                                        ) ?>
                                    </strong>

                                    <span>
                                        / <?= htmlspecialchars(
                                            $product['unit']
                                        ) ?>
                                    </span>

                                </div>


                                <p class="product-stock">

                                    Stock:
                                    <?= number_format(
                                        (float) $product['stock_quantity'],
                                        2
                                    ) ?>

                                </p>


                                <span
                                    class="product-status <?= htmlspecialchars(
                                        $product['moderation_status']
                                    ) ?>"
                                >
                                    <?= ucfirst(
                                        htmlspecialchars(
                                            $product['moderation_status']
                                        )
                                    ) ?>
                                </span>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <h2>My Stall</h2>

                <a href="profile.php">
                    Edit →
                </a>

            </div>


            <?php if ($farmer): ?>

                <div class="stall-summary">

                    <div class="farmer-icon">
                        <?= strtoupper(
                            substr(
                                $farmer['stall_name'],
                                0,
                                1
                            )
                        ) ?>
                    </div>


                    <div class="farmer-info">

                        <h3>
                            <?= htmlspecialchars(
                                $farmer['stall_name']
                            ) ?>
                        </h3>


                        <?php if (!empty($farmer['address'])): ?>

                            <p class="farmer-address">
                                <?= htmlspecialchars(
                                    $farmer['address']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <?php if (!empty($farmer['description'])): ?>

                            <p class="farmer-description">
                                <?= htmlspecialchars(
                                    $farmer['description']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <span
                            class="farmer-approval <?= htmlspecialchars(
                                $farmer['approval_status']
                            ) ?>"
                        >
                            Stall:
                            <?= ucfirst(
                                htmlspecialchars(
                                    $farmer['approval_status']
                                )
                            ) ?>
                        </span>

                    </div>

                </div>

            <?php endif; ?>

        </section>

    </main>


    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/dashboard.js"></script>

</body>
</html>