<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart action
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

// Handle Clear Cart action
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CloudCart - Cart</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f4f4f9; }
        .actions { margin-top: 20px; }
        .btn { padding: 10px 15px; text-decoration: none; color: white; border-radius: 5px; background: #007bff; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <h2>Your Shopping Cart</h2>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Product ID</th>
                <th>Quantity</th>
            </tr>
            <?php foreach ($_SESSION['cart'] as $id => $qty): ?>
            <tr>
                <td><?= htmlspecialchars($id) ?></td>
                <td><?= htmlspecialchars($qty) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="actions">
            <a href="cart.php?action=clear" class="btn btn-danger">Clear Cart</a>
            <a href="#" class="btn" style="background: #28a745;" onclick="alert('Simulated Checkout Complete!')">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
    
    <br><br>
    <a href="index.php">Back to Shopping</a>
</body>
</html>