<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle Add to Wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = $_POST['product_id'];
    try {
        $stmt = $conn->prepare("INSERT INTO Wishlists (user_id, product_id) VALUES (:user_id, :product_id)");
        $stmt->execute(['user_id' => $user_id, 'product_id' => $product_id]);
        $message = "Added to wishlist!";
    } catch(PDOException $e) {
        $message = "Item is already in your wishlist.";
    }
}

// Handle Remove from Wishlist
if (isset($_GET['remove'])) {
    $stmt = $conn->prepare("DELETE FROM Wishlists WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute(['user_id' => $user_id, 'product_id' => $_GET['remove']]);
    header("Location: wishlist.php");
    exit;
}

// Fetch Wishlist Items
$stmt = $conn->prepare("
    SELECT p.product_id, p.name, p.price, p.description 
    FROM Wishlists w
    JOIN Products p ON w.product_id = p.product_id
    WHERE w.user_id = :user_id
");
$stmt->execute(['user_id' => $user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Wishlist - CloudCart</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="index.php" class="brand">Cloud<span>Cart</span></a>
        <div class="links">
            <a href="index.php">Shop</a>
            <a href="wishlist.php" style="color: #0056b3;">Wishlist</a>
            <a href="cart.php">Cart (<?= $cart_count ?>)</a>
            <span>Hi, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem;">
        <h2 class="section-title">Your Saved Favourites</h2>
        
        <?php if ($message): ?>
            <div style="background: #e6ffe6; color: #28a745; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="product-grid">
            <?php if (empty($wishlist_items)): ?>
                <p>You haven't saved any items yet.</p>
            <?php else: ?>
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="product-card">
                        <div class="product-image">⭐</div>
                        <div class="product-details">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p><?= htmlspecialchars($item['description']) ?></p>
                            <div class="price-row">
                                <span class="product-price">$<?= number_format($item['price'], 2) ?></span>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <form action="cart.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                                </form>
                                <a href="wishlist.php?remove=<?= $item['product_id'] ?>" class="btn btn-outline" style="flex: 1; text-align: center;">Remove</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>