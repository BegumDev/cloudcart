<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$checkout_success = '';

// Handle Add to Cart
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

// Handle Clear Cart
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit;
}

// Handle Simulated Checkout (Assignment Requirement #7)
if (isset($_GET['action']) && $_GET['action'] === 'checkout') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    // Simulate transaction processing and empty the cart
    $_SESSION['cart'] = [];
    $checkout_success = "Payment successful! Your virtual transaction was approved. Order #1004 is now processing.";
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
            <?php if ($checkout_success): ?>
                <div style="background: #e6ffe6; color: #28a745; padding: 1.5rem; border-radius: 4px; margin-bottom: 2rem; font-size: 1rem; border: 1px solid #c3e6cb;">
                    <strong>✅ <?= $checkout_success ?></strong> <br><br>
                    You can track this using our <a href="https://cloudcart-function-app.azurewebsites.net/api/orderstatus?orderId=1004" target="_blank" style="color: #155724; font-weight: bold;">Order Tracking API</a>.
                </div>
            <?php endif; ?>

            <?php if (empty($_SESSION['cart'])): ?>
                <div style="text-align: center; padding: 2rem 0;">
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
                    <a href="cart.php?action=checkout" class="btn btn-success" style="width: auto; background-color: #28a745;">Complete Virtual Payment</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2026 CloudCart Inc. Secure Cloud E-Commerce Platform.</p>
    </footer>
</body>
</html>