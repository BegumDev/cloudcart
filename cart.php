<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = $_POST['product_id'];
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
    header("Location: index.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit;
}

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart - CloudCart</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="index.php" class="brand">Cloud<span>Cart</span></a>
        <div class="links">
            <a href="index.php">Shop</a>
            <a href="cart.php">Cart (<?= $cart_count ?>)</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Hi, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Log In</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem;">
        <h2 class="section-title">Secure Checkout</h2>
        
        <div class="cart-wrapper">
            <?php if (empty($_SESSION['cart'])): ?>
                <div style="text-align: center; padding: 3rem 0;">
                    <h3 style="margin-bottom: 1.5rem; color: #666; font-weight: 400;">Your cart is empty.</h3>
                    <a href="index.php" class="btn btn-primary" style="width: auto;">Continue Shopping</a>
                </div>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Item ID</th>
                        <th style="text-align: right;">Quantity</th>
                    </tr>
                    <?php foreach ($_SESSION['cart'] as $id => $qty): ?>
                    <tr>
                        <td><strong>Product #<?= htmlspecialchars($id) ?></strong></td>
                        <td style="text-align: right;"><?= htmlspecialchars($qty) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <div class="cart-actions">
                    <a href="cart.php?action=clear" class="btn btn-outline" style="width: auto;">Empty Cart</a>
                    <a href="#" class="btn" style="width: auto;" onclick="alert('Checkout integration pending!')">Proceed to Payment</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2026 CloudCart Inc. Demo Store for Azure Deployment.</p>
    </footer>
</body>
</html>