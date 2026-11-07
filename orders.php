<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's orders from the database
$stmt = $conn->prepare("SELECT order_id, total_amount, shipping_address, created_at FROM Orders WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute(['user_id' => $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to call your Azure Function App API with the correct full URL
function fetchLiveOrderStatus($orderId) {
    $functionUrl = "https://cloudcart-function-app-hkaqfva6gwhub8b5.switzerlandnorth-01.azurewebsites.net/api/orderstatus?orderId=" . urlencode($orderId);
    
    $context = stream_context_create([
        "http" => ["method" => "GET", "timeout" => 3]
    ]);
    
    $response = @file_get_contents($functionUrl, false, $context);
    if ($response === FALSE) {
        return ["status" => "API Unreachable"];
    }
    
    return json_decode($response, true);
}

// Fetch itemized products for a specific order
function getOrderItems($conn, $orderId) {
    $itemStmt = $conn->prepare("
        SELECT p.name, oi.quantity, oi.price_at_purchase 
        FROM Order_Items oi
        JOIN Products p ON oi.product_id = p.product_id
        WHERE oi.order_id = :order_id
    ");
    $itemStmt->execute(['order_id' => $orderId]);
    return $itemStmt->fetchAll(PDO::FETCH_ASSOC);
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="wishlist.php">Wishlist</a>
                <a href="orders.php" style="color: #0056b3;">My Orders</a>
                <a href="admin.php">Admin Panel</a>
            <?php endif; ?>
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
        <h2 class="section-title">Order History & Live Tracking</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #e6ffe6; color: #28a745; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
                🎉 Order #<?= htmlspecialchars($_GET['success']) ?> placed successfully! Verified via virtual payment gateway.
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <p>You haven't placed any orders yet. <a href="index.php">Start shopping</a></p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php foreach ($orders as $order): ?>
                    <?php 
                        $apiResult = fetchLiveOrderStatus($order['order_id']);
                        $liveStatus = $apiResult['status'] ?? 'Processing';
                        $items = getOrderItems($conn, $order['order_id']);
                    ?>
                    <div style="background: white; border: 1px solid #ddd; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                            <div>
                                <h3 style="margin-bottom: 0.25rem; color: #333;">Order #<?= $order['order_id'] ?></h3>
                                <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Placed on: <?= $order['created_at'] ?></p>
                                <p style="color: #444; font-size: 0.9rem;"><strong>Ship to:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.2rem; font-weight: bold; color: #111; margin-bottom: 0.5rem;">$<?= number_format($order['total_amount'], 2) ?></div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: flex-end;">
                                    <span style="background: #e1f5fe; color: #0277bd; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold;">
                                        ⚡ Live Status: <?= htmlspecialchars($liveStatus) ?>
                                    </span>
                                    <a href="https://cloudcart-function-app-hkaqfva6gwhub8b5.switzerlandnorth-01.azurewebsites.net/api/orderstatus?orderId=<?= $order['order_id'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; text-decoration: none;" title="Fetch fresh API snapshot">
                                        🔄 Refresh Snapshot
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="font-size: 0.95rem; color: #555; margin-bottom: 0.5rem;">Items Purchased:</h4>
                            <ul style="list-style: none; padding-left: 0; display: flex; flex-direction: column; gap: 0.4rem;">
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $item): ?>
                                        <li style="display: flex; justify-content: space-between; font-size: 0.9rem; background: #f8f9fa; padding: 0.5rem 0.75rem; border-radius: 4px;">
                                            <span><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</span>
                                            <span style="font-weight: 600;">$<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li style="font-size: 0.9rem; color: #888;">No item details linked to this order.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; 2026 CloudCart Inc. Secure Cloud E-Commerce Platform.</p>
    </footer>
</body>
</html>