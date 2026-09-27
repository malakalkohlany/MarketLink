<?php

require_once __DIR__ . '/../includes/include.php';

requireRole(R_FARMER);
requireApprovedFarmer();

$user_id = getUserId();

$stmt = $conn->prepare("
    SELECT id
    FROM farmers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

if (!$farmer) {
    die("Farmer account not found.");
}

$farmer_id = $farmer['id'];

$stmt->close();

// Reviews Pagination
$reviews_per_page = 10;

$reviews_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($reviews_page < 1) {
    $reviews_page = 1;
}

$reviews_offset = ($reviews_page - 1) * $reviews_per_page;

$count_reviews_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_reviews
    FROM reviews
    WHERE farmer_id = ?
");

$count_reviews_stmt->bind_param("i", $farmer_id);
$count_reviews_stmt->execute();

$count_reviews_result = $count_reviews_stmt->get_result();
$total_reviews = $count_reviews_result->fetch_assoc()['total_reviews'];

$count_reviews_stmt->close();

$total_reviews_pages = ceil($total_reviews / $reviews_per_page);

if ($total_reviews_pages > 0 && $reviews_page > $total_reviews_pages) {
    $reviews_page = $total_reviews_pages;
    $reviews_offset = ($reviews_page - 1) * $reviews_per_page;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $review_id = (int) $_POST['review_id'];
    $farmer_response = trim($_POST['farmer_response']);

    if ($review_id <= 0 || empty($farmer_response)) {
        die("Please enter a valid response.");
    }

    $response_stmt = $conn->prepare("
        UPDATE reviews
        SET
            farmer_response = ?,
            farmer_response_at = NOW()
        WHERE id = ?
          AND farmer_id = ?
    ");

    $response_stmt->bind_param(
        "sii",
        $farmer_response,
        $review_id,
        $farmer_id
    );

    if (!$response_stmt->execute()) {
        die("Failed to save response.");
    }

    $response_stmt->close();

    header("Location: reviews.php");
    exit;
}


$review_stmt = $conn->prepare("
    SELECT
        reviews.id,
        reviews.rating,
        reviews.comment,
        reviews.status,
        reviews.farmer_response,
        reviews.farmer_response_at,
        reviews.created_at,
        users.name AS customer_name,
        products.name AS product_name
    FROM reviews
    INNER JOIN users
        ON reviews.customer_id = users.id
    INNER JOIN products
        ON reviews.product_id = products.id
    WHERE reviews.farmer_id = ?
    ORDER BY reviews.created_at DESC
    LIMIT ? OFFSET ?
");

$review_stmt->bind_param("iii",$farmer_id,$reviews_per_page,$reviews_offset);
$review_stmt->execute();

$reviews = $review_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <h1>Customer Reviews</h1>
        <?php if ($reviews->num_rows === 0): ?>
            <p>No Reviews found.</p>
        <?php else: ?>

            <?php while ($review = $reviews->fetch_assoc()): ?>
            
            <div class="review-card">
                <h2><?= e($review['product_name']) ?></h2>
                <p>Customer:<?= e($review['customer_name']) ?></p>
                <p>Rating:<?= e($review['rating']) ?>/5</p>
                <p>Comment:<?= e($review['comment'] ?? '') ?></p>
                <p>Status:<?= e(ucfirst($review['status'])) ?></p>
                <p>Date:<?= formatDateTime($review['created_at']) ?></p>

                <?php if (!empty($review['farmer_response'])): ?>
                    <h3>Your Response</h3>
                    <p><?= e($review['farmer_response']) ?></p>
                    <p>Response Date:<?= formatDateTime($review['farmer_response_at']) ?></p>
                <?php else: ?>    

                <h3>Respond to Customer</h3>  
                <form method="POST">
                    <input type="hidden" name="review_id" value="<?= e($review['id']) ?>">
                    <textarea name="farmer_response" rows="4" required></textarea>
                    <br><br>

                    <button type="submit">Send Response</button>
                </form>  
                <?php endif; ?>

            </div>

            <?php endwhile; ?>


<?php if ($total_reviews_pages > 1): ?>

    <div class="pagination">

        <?php if ($reviews_page > 1): ?>
            <a href="?page=<?= $reviews_page - 1 ?>">
                Previous
            </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_reviews_pages; $i++): ?>

            <a href="?page=<?= $i ?>"
               <?= $i == $reviews_page ? 'class="active"' : '' ?>>
                <?= $i ?>
            </a>

        <?php endfor; ?>

        <?php if ($reviews_page < $total_reviews_pages): ?>
            <a href="?page=<?= $reviews_page + 1 ?>">
                Next
            </a>
        <?php endif; ?>

    </div>

<?php endif; ?>

        <?php endif; ?>

    </main>
</body>
</html>