<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'Virtual Credit Card';

    if (empty($shipping_address)) {
        $error = "Shipping address is required.";
    } else {
        try {
            $conn->beginTransaction();

            $total_amount = 0;
            $item_details = [];

            foreach ($cart as $product_id => $qty) {
                $stmt = $conn->prepare("SELECT price, stock_quantity FROM Products WHERE product_id = :id");
                $stmt->execute(['id' => $product_id]);
                $prod = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$prod || $prod['stock_quantity'] < $qty) {
                    throw new Exception("Product ID {$product_id} is out of stock or unavailable.");
                }

                $subtotal = $prod['price'] * $qty;
                $total_amount += $subtotal;
                $item_details[] = [
                    'product_id' => $product_id,
                    'quantity' => $qty,
                    'price' => $prod['price']
                ];
            }

            // Insert Order
            $orderStmt = $conn->prepare("
                INSERT INTO Orders (user_id, total_amount, status, shipping_address) 
                VALUES (:user_id, :total_amount, 'Processing', :shipping_address)
            ");
            $orderStmt->execute([
                'user_id' => $user_id,
                'total_amount' => $total_amount,
                'shipping_address' => $shipping_address
            ]);
            
            $orderId = $conn->lastInsertId();

            // Insert Order Items and update stock
            foreach ($item_details as $item) {
                $itemStmt = $conn->prepare("
                    INSERT INTO Order_Items (order_id, product_id, quantity, price_at_purchase) 
                    VALUES (:order_id, :product_id, :quantity, :price)
                ");
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);

                $stockStmt = $conn->prepare("UPDATE Products SET stock_quantity = stock_quantity - :qty WHERE product_id = :id");
                $stockStmt->execute(['qty' => $item['quantity'], 'id' => $item['product_id']]);
            }

            // Record Virtual Payment
            $payStmt = $conn->prepare("
                INSERT INTO Payments (order_id, payment_method, transaction_status, amount) 
                VALUES (:order_id, :method, 'Success', :amount)
            ");
            $payStmt->execute([
                'order_id' => $orderId,
                'method' => $payment_method,
                'amount' => $total_amount
            ]);

            $conn->commit();
            
            $_SESSION['cart'] = [];
            header("Location: orders.php?success=" . $orderId);
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Checkout failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - CloudCart</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="index.php" class="brand">Cloud<span>Cart</span></a>
        <div class="links">
            <a href="index.php">Shop</a>
            <a href="cart.php">Back to Cart</a>
        </div>
    </nav>

    <div class="container" style="max-width: 600px; margin-top: 3rem;">
        <h2 class="section-title">Finalize Checkout</h2>
        
        <?php if ($error): ?>
            <div style="background: #ffe6e6; color: #dc3545; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="checkout.php" method="POST" class="auth-card" style="width: 100%; box-sizing: border-box; max-width: 100%;">
            <div class="form-group">
                <label>Shipping Address</label>
                <textarea name="shipping_address" rows="3" required placeholder="123 Cloud Street, Tech City" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid #ccd0d5; border-radius: 4px; font-family: inherit;"></textarea>
            </div>
            
            <div class="form-group" style="margin-top: 1.5rem;">
                <label>Virtual Payment Method</label>
                <select name="payment_method" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid #ccd0d5; border-radius: 4px; font-family: inherit; background: white;">
                    <option value="Virtual Credit Card (Visa)">Virtual Credit Card (Visa ending in 4242)</option>
                    <option value="CloudPay Wallet">CloudPay Wallet Balance ($5,000.00)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 2rem;">Authorize Payment & Place Order</button>
        </form>
    </div>
</body>
</html>