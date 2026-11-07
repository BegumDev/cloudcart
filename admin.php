<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Helper function to query the Azure Function App API for a fresh snapshot
function fetchLiveOrderStatus($orderId) {
    $functionUrl = "cloudcart-function-app-hkaqfva6gwhub8b5.switzerlandnorth-01.azurewebsites.net" . urlencode($orderId);
    
    $context = stream_context_create([
        "http" => ["method" => "GET", "timeout" => 3]
    ]);
    
    $response = @file_get_contents($functionUrl, false, $context);
    if ($response === FALSE) {
        return "API Unreachable";
    }
    $data = json_decode($response, true);
    return $data['status'] ?? 'Unknown';
}

// Fetch all orders from database
$stmt = $conn->prepare("
    SELECT o.order_id, o.user_id, u.username, o.total_amount, o.status, o.shipping_address, o.created_at 
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch itemized products for orders
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
    <title>Admin Dashboard - CloudCart</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="index.php" class="brand">Cloud<span>Cart Admin</span></a>
        <div class="links">
            <a href="index.php">View Store</a>
            <a href="admin.php" style="color: #0056b3;">Admin Dashboard</a>
            <a href="cart.php">Cart (<?= $cart_count ?>)</a>
            <span>Hi, <?= htmlspecialchars($_SESSION['username']) ?> (Admin)</span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem;">
        <h2 class="section-title">Shop Management & Live Order Monitoring</h2>
        <p style="color: #666; margin-bottom: 2rem;">Inspect global customer orders, verify itemized purchases, and query real-time serverless tracking snapshots.</p>

        <?php if (empty($all_orders)): ?>
            <p>No orders have been placed on the platform yet.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php foreach ($all_orders as $order): ?>
                    <?php 
                        $liveStatus = fetchLiveOrderStatus($order['order_id']);
                        $items = getOrderItems($conn, $order['order_id']);
                    ?>
                    <div style="background: white; border: 1px solid #ddd; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                            <div>
                                <h3 style="margin-bottom: 0.25rem; color: #333;">Order #<?= $order['order_id'] ?> <span style="font-size: 0.85rem; color: #666; font-weight: normal;">(Customer: <?= htmlspecialchars($order['username']) ?>)</span></h3>
                                <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">Placed on: <?= $order['created_at'] ?></p>
                                <p style="color: #444; font-size: 0.9rem;"><strong>Ship to:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.2rem; font-weight: bold; color: #111; margin-bottom: 0.5rem;">$<?= number_format($order['total_amount'], 2) ?></div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: flex-end;">
                                    <span style="background: #fff3cd; color: #856404; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold;">
                                        ⚡ Live Status: <?= htmlspecialchars($liveStatus) ?>
                                    </span>
                                    <a href="https://cloudcart-function-app.azurewebsites.net/api/orderstatus?orderId=<?= $order['order_id'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; text-decoration: none;" title="Get fresh API snapshot">
                                        🔄 Refresh Snapshot
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="font-size: 0.95rem; color: #555; margin-bottom: 0.5rem;">Itemized Order Contents:</h4>
                            <ul style="list-style: none; padding-left: 0; display: flex; flex-direction: column; gap: 0.4rem;">
                                <?php foreach ($items as $item): ?>
                                    <li style="display: flex; justify-content: space-between; font-size: 0.9rem; background: #f8f9fa; padding: 0.5rem 0.75rem; border-radius: 4px;">
                                        <span><?= htmlspecialchars($item['name']) ?> (Qty: <?= $item['quantity'] ?>)</span>
                                        <span style="font-weight: 600;">$<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; 2026 CloudCart Inc. Admin Management Console.</p>
    </footer>
</body>
</html>