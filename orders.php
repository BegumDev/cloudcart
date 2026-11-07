<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT order_id, total_amount, shipping_address, created_at FROM Orders WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute(['user_id' => $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function fetchLiveOrderStatus($orderId) {
    $functionUrl = "https://cloudcart-function-app.azurewebsites.net/api/orderstatus?orderId=" . urlencode($orderId);
    
    $context = stream_context_create([
        "http" => ["method" => "GET", "timeout" => 3]
    ]);
    
    $response = @file_get_contents($functionUrl, false, $context);
    if ($response === FALSE) {
        return ["status" => "API Unreachable"];
    }
    
    return json_decode($response, true);
}

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - CloudCart</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="index.php" class="brand">Cloud<span>Cart</span></a>
        <div class="links">
            <a href="index.php">Shop</a>
            <a href="orders.php" style="color: #0056b3;">My Orders</a>
            <a href="wishlist.php">Wishlist</a>
            <a href="cart.php">Cart (<?= $cart_count ?>)</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem;">
        <h2 class="section-title">Order History & Tracking</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #e6ffe6; color: #28a745; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
                🎉 Order #<?= htmlspecialchars($_GET['success']) ?> placed successfully! Payment verified via virtual gateway.
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <p>You haven't placed any orders yet. <a href="index.php">Start shopping</a></p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($orders as $order): ?>
                    <?php 
                        $apiResult = fetchLiveOrderStatus($order['order_id']);
                        $liveStatus = $apiResult['status'] ?? 'Unknown';
                    ?>
                    <div style="background: white; border: 1px solid #ddd; padding: 1.5rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div>
                            <h3 style="margin-bottom: 0.25rem; color: #333;">Order #<?= $order['order_id'] ?></h3>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;">Placed on: <?= $order['created_at'] ?></p>
                            <p style="color: #444; font-size: 0.9rem;"><strong>Ship to:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.2rem; font-weight: bold; color: #111; margin-bottom: 0.5rem;">$<?= number_format($order['total_amount'], 2) ?></div>
                            <span style="background: #e1f5fe; color: #0277bd; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold;">
                                ⚡ API Status: <?= htmlspecialchars($liveStatus) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>