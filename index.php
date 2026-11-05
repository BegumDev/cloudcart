<?php
session_start();
require_once 'config.php';

// Fetch products from the database
try {
    $stmt = $conn->query("SELECT * FROM Products WHERE stock_quantity > 0");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CloudCart - Home</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; }
        nav { background: #333; padding: 15px; color: white; display: flex; justify-content: space-between; }
        nav a { color: white; text-decoration: none; margin-left: 15px; }
        .container { padding: 20px; }
        .product-grid { display: flex; gap: 20px; flex-wrap: wrap; }
        .product-card { background: #fff; padding: 15px; border-radius: 8px; width: 250px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        button { background: #007bff; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; width: 100%; margin-top: 10px;}
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <nav>
        <div><strong>CloudCart</strong></div>
        <div>
            <a href="index.php">Catalog</a>
            <a href="cart.php">Cart (<?= $cart_count ?>)</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-left:15px; color:#ddd;">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login / Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <h2>Product Catalog</h2>
        <div class="product-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <p style="color: #28a745; font-weight: bold;">$<?= number_format($product['price'], 2) ?></p>
                        <p><small>In Stock: <?= $product['stock_quantity'] ?></small></p>
                        <form action="cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit">Add to Cart</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>