<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'customer') !== 'admin') {
    header("Location: index.php");
    exit;
}

$flash_success = '';
$flash_error   = '';

// Handle Product Catalog CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category'] ?? 'General');
        $price       = (float) ($_POST['price'] ?? 0);
        $stock       = (int) ($_POST['stock_quantity'] ?? 0);

        if ($name === '' || $price <= 0 || $stock < 0) {
            $flash_error = "Please provide a valid product name, positive price, and non-negative stock quantity.";
        } else {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO Products (name, description, price, stock_quantity, category)
                    VALUES (:name, :description, :price, :stock_quantity, :category)
                ");
                $stmt->execute([
                    'name'           => $name,
                    'description'    => $description,
                    'price'          => $price,
                    'stock_quantity' => $stock,
                    'category'       => $category !== '' ? $category : 'General'
                ]);
                $flash_success = "Product \"" . htmlspecialchars($name) . "\" added to catalog.";
            } catch (PDOException $e) {
                $flash_error = "Failed to add product: " . $e->getMessage();
            }
        }
    } elseif ($action === 'update_product') {
        $product_id  = (int) ($_POST['product_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category'] ?? 'General');
        $price       = (float) ($_POST['price'] ?? 0);
        $stock       = (int) ($_POST['stock_quantity'] ?? 0);

        if ($product_id <= 0 || $name === '' || $price <= 0 || $stock < 0) {
            $flash_error = "Please provide a valid name, positive price, and non-negative stock value.";
        } else {
            try {
                $stmt = $conn->prepare("
                    UPDATE Products
                    SET name = :name,
                        description = :description,
                        category = :category,
                        price = :price,
                        stock_quantity = :stock_quantity
                    WHERE product_id = :product_id
                ");
                $stmt->execute([
                    'name'           => $name,
                    'description'    => $description,
                    'category'       => $category !== '' ? $category : 'General',
                    'price'          => $price,
                    'stock_quantity' => $stock,
                    'product_id'     => $product_id
                ]);
                $flash_success = "Product #{$product_id} updated successfully.";
            } catch (PDOException $e) {
                $flash_error = "Failed to update product: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_product') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        if ($product_id > 0) {
            try {
                // Remove from wishlists first so FK does not block deletion
                $wStmt = $conn->prepare("DELETE FROM Wishlists WHERE product_id = :product_id");
                $wStmt->execute(['product_id' => $product_id]);

                $stmt = $conn->prepare("DELETE FROM Products WHERE product_id = :product_id");
                $stmt->execute(['product_id' => $product_id]);
                $flash_success = "Product #{$product_id} removed from catalog.";
            } catch (PDOException $e) {
                $flash_error = "Cannot delete Product #{$product_id} because it is referenced in existing customer orders. Set stock to 0 instead to hide it from the storefront.";
            }
        }
    }
}

// Fetch all products (including 0-stock items so admin can restock them)
$prodStmt = $conn->query("SELECT product_id, name, description, price, stock_quantity, category FROM Products ORDER BY category ASC, product_id DESC");
$all_products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - CloudCart Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="index.php" class="brand">Cloud<span>Cart Admin</span></a>
        <div class="links">
            <a href="index.php">View Store</a>
            <a href="admin.php">Admin Dashboard</a>
            <a href="admin_products.php" style="color: #0056b3;">Manage Products</a>
            <a href="cart.php">Cart (<?= $cart_count ?>)</a>
            <span>Hi, <?= htmlspecialchars($_SESSION['username']) ?> (Admin)</span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h2 class="section-title" style="margin-bottom: 0.25rem;">Product Catalog Management</h2>
                <p style="color: #666;">Add, edit, organize categories, and update real-time stock levels.</p>
            </div>
            <a href="admin.php" class="btn btn-outline" style="text-decoration: none;">&larr; Back to Admin Dashboard</a>
        </div>

        <?php if ($flash_success): ?>
            <div style="background: #e6ffe6; color: #155724; border: 1px solid #c3e6cb; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                <?= $flash_success ?>
            </div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <div style="background: #ffe6e6; color: #721c24; border: 1px solid #f5c6cb; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($flash_error) ?>
            </div>
        <?php endif; ?>

        <!-- ADD NEW PRODUCT FORM -->
        <div style="background: white; border: 1px solid #ddd; padding: 1.5rem; border-radius: 8px; margin-bottom: 2.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Add New Product</h3>
            <form action="admin_products.php" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
                <input type="hidden" name="action" value="add_product">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Product Name</label>
                    <input type="text" name="name" placeholder="e.g. USB-C Dock" required maxlength="100">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Category</label>
                    <input type="text" name="category" placeholder="e.g. Accessories" required maxlength="50">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Price ($)</label>
                    <input type="number" name="price" step="0.01" min="0.01" placeholder="49.99" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Initial Stock</label>
                    <input type="number" name="stock_quantity" min="0" value="25" required>
                </div>
                <div class="form-group" style="margin-bottom: 0; grid-column: 1 / -1;">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="Short product summary...">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">+ Add Product</button>
                </div>
            </form>
        </div>

        <!-- EXISTING PRODUCTS CRUD TABLE -->
        <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Existing Catalog (<?= count($all_products) ?> items)</h3>
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; overflow-x: auto; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 1px solid #ddd;">
                        <th style="padding: 0.85rem;">ID</th>
                        <th style="padding: 0.85rem;">Name &amp; Description</th>
                        <th style="padding: 0.85rem;">Category</th>
                        <th style="padding: 0.85rem;">Price ($)</th>
                        <th style="padding: 0.85rem;">Stock</th>
                        <th style="padding: 0.85rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_products)): ?>
                        <tr>
                            <td colspan="6" style="padding: 1.25rem; text-align: center; color: #666;">No products in the catalog yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_products as $prod): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 0.85rem; color: #666; vertical-align: top;">#<?= $prod['product_id'] ?></td>
                                <form action="admin_products.php" method="POST">
                                    <input type="hidden" name="action" value="update_product">
                                    <input type="hidden" name="product_id" value="<?= $prod['product_id'] ?>">
                                    <td style="padding: 0.85rem;">
                                        <input type="text" name="name" value="<?= htmlspecialchars($prod['name']) ?>" style="width: 100%; min-width: 180px; padding: 0.35rem; border: 1px solid #ccc; border-radius: 4px; font-weight: 600; margin-bottom: 0.35rem;" required maxlength="100">
                                        <input type="text" name="description" value="<?= htmlspecialchars($prod['description'] ?? '') ?>" placeholder="Description..." style="width: 100%; min-width: 180px; padding: 0.35rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem; color: #555;">
                                    </td>
                                    <td style="padding: 0.85rem; vertical-align: top;">
                                        <input type="text" name="category" value="<?= htmlspecialchars($prod['category'] ?? 'General') ?>" style="width: 120px; padding: 0.35rem; border: 1px solid #ccc; border-radius: 4px;" required maxlength="50">
                                    </td>
                                    <td style="padding: 0.85rem; vertical-align: top;">
                                        <input type="number" name="price" step="0.01" min="0.01" value="<?= number_format((float)$prod['price'], 2, '.', '') ?>" style="width: 90px; padding: 0.35rem; border: 1px solid #ccc; border-radius: 4px;" required>
                                    </td>
                                    <td style="padding: 0.85rem; vertical-align: top;">
                                        <input type="number" name="stock_quantity" min="0" value="<?= (int)$prod['stock_quantity'] ?>" style="width: 75px; padding: 0.35rem; border: 1px solid #ccc; border-radius: 4px;" required>
                                        <?php if ((int)$prod['stock_quantity'] === 0): ?>
                                            <div style="font-size: 0.75rem; color: #dc3545; font-weight: bold; margin-top: 0.25rem;">Out of Stock</div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.85rem; white-space: nowrap; vertical-align: top;">
                                        <button type="submit" class="btn btn-primary" style="padding: 0.35rem 0.7rem; font-size: 0.82rem;">Save</button>
                                </form>
                                        <form action="admin_products.php" method="POST" style="display: inline;" onsubmit="return confirm('Remove this product from the catalog?');">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?= $prod['product_id'] ?>">
                                            <button type="submit" class="btn btn-outline" style="padding: 0.35rem 0.7rem; font-size: 0.82rem; color: #dc3545; border-color: #dc3545;">Delete</button>
                                        </form>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 CloudCart Inc. Admin Product Management.</p>
    </footer>
</body>
</html>