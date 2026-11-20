<?php
session_start();
require_once 'config.php';

try {
    $stmt = $conn->query("SELECT * FROM Products WHERE stock_quantity > 0");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$is_admin   = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CloudCart - Official Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="index.php" class="brand">Cloud<span>Cart</span></a>
        <div class="links">
            <a href="index.php">Shop</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="wishlist.php">Wishlist</a>
                <a href="orders.php">My Orders</a>
                <?php if ($is_admin): ?>
                    <a href="admin.php" style="color: #0056b3; font-weight: bold;">Admin Panel</a>
                <?php endif; ?>
            <?php endif; ?>
            
            <a href="cart.php">Cart (<?= $cart_count ?>)</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Hi, <?= htmlspecialchars($_SESSION['username']) ?><?= $is_admin ? ' (Admin)' : '' ?></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Log In</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero">
        <h1>Next-Gen Tech, Delivered.</h1>
        <p>Explore our premium selection of electronics with seamless cloud-powered checkout.</p>
    </div>

    <div class="container">
        <h2 class="section-title">Trending Products</h2>
        <div class="product-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">📦</div>
                        <div class="product-details">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <div class="price-row">
                                <span class="product-price">$<?= number_format($product['price'], 2) ?></span>
                                <span class="product-stock"><?= $product['stock_quantity'] ?> left</span>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <form action="cart.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                                </form>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <form action="wishlist.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn btn-outline">⭐ Save</button>
                                </form>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Inventory is currently updating. Please check back soon.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2026 CloudCart Inc. Secure Cloud E-Commerce Platform.</p>
    </footer>
</body>
</html>