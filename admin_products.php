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
                $flash_success = "Product #{$product_id} (\"" . htmlspecialchars($name) . "\") updated.";
            } catch (PDOException $e) {
                $flash_error = "Failed to update product: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_product') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        if ($product_id > 0) {
            try {
                $wStmt = $conn->prepare("DELETE FROM Wishlists WHERE product_id = :product_id");
                $wStmt->execute(['product_id' => $product_id]);

                $stmt = $conn->prepare("DELETE FROM Products WHERE product_id = :product_id");
                $stmt->execute(['product_id' => $product_id]);
                $flash_success = "Product #{$product_id} removed from catalog.";
            } catch (PDOException $e) {
                $flash_error = "Cannot delete Product #{$product_id} because it belongs to existing customer orders. Edit it and set stock to 0 to hide it from the storefront.";
            }
        }
    }
}

// Check if editing a specific product via ?edit=ID
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $eStmt = $conn->prepare("SELECT product_id, name, description, price, stock_quantity, category FROM Products WHERE product_id = :id");
    $eStmt->execute(['id' => $edit_id]);
    $edit_product = $eStmt->fetch(PDO::FETCH_ASSOC);
}

$show_add_form = isset($_GET['new']);

// Fetch all products
$prodStmt = $conn->query("SELECT product_id, name, description, price, stock_quantity, category FROM Products ORDER BY product_id DESC");
$all_products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - CloudCart Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .inline-btn {
            width: auto !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 0.9rem;
            font-size: 0.85rem;
            text-decoration: none;
            cursor: pointer;
        }
        .catalog-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .catalog-table th,
        .catalog-table td {
            padding: 0.85rem 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .catalog-table th {
            background: #f8f9fa;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #555;
            border-bottom: 1px solid #ddd;
        }
        .catalog-table tr:hover {
            background: #fafbfc;
        }
        .desc-truncate {
            font-size: 0.82rem;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 0.2rem;
        }
        .badge-cat {
            display: inline-block;
            background: #eef4ff;
            color: #0056b3;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .badge-stock-ok {
            color: #155724;
            background: #e6ffe6;
            padding: 0.2rem 0.55rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-stock-low {
            color: #856404;
            background: #fff3cd;
            padding: 0.2rem 0.55rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-stock-out {
            color: #721c24;
            background: #ffe6e6;
            padding: 0.2rem 0.55rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
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

    <div class="container" style="margin-top: 2.5rem;">
        <!-- Clean Header Bar -->
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <h2 class="section-title" style="margin-bottom: 0.25rem;">Product Catalog (<?= count($all_products) ?>)</h2>
                <p style="color: #666; font-size: 0.92rem;">Manage inventory, pricing, and categories across your storefront.</p>
            </div>
            <div style="display: flex; gap: 0.6rem; align-items: center;">
                <a href="admin.php" class="btn btn-outline inline-btn">&larr; Dashboard</a>
                <?php if (!$show_add_form && !$edit_product): ?>
                    <a href="admin_products.php?new=1" class="btn btn-primary inline-btn">+ Add Product</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($flash_success): ?>
            <div style="background: #e6ffe6; color: #155724; border: 1px solid #c3e6cb; padding: 0.9rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.92rem;">
                <?= $flash_success ?>
            </div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <div style="background: #ffe6e6; color: #721c24; border: 1px solid #f5c6cb; padding: 0.9rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.92rem;">
                <?= htmlspecialchars($flash_error) ?>
            </div>
        <?php endif; ?>

        <!-- EDIT PRODUCT DRAWER (Shown only when Edit is clicked) -->
        <?php if ($edit_product): ?>
            <div style="background: #f8fbff; border: 1px solid #b8daff; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.05rem; color: #004085;">Edit Product #<?= $edit_product['product_id'] ?></h3>
                    <a href="admin_products.php" style="font-size: 0.85rem; color: #555; text-decoration: none;">✕ Cancel</a>
                </div>
                <form action="admin_products.php" method="POST" style="display: grid; grid-template-columns: 2fr 1.2fr 1fr 1fr; gap: 1rem; align-items: end;">
                    <input type="hidden" name="action" value="update_product">
                    <input type="hidden" name="product_id" value="<?= $edit_product['product_id'] ?>">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Product Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($edit_product['name']) ?>" required maxlength="100">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Category</label>
                        <input type="text" name="category" value="<?= htmlspecialchars($edit_product['category'] ?? 'General') ?>" required maxlength="50">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Price ($)</label>
                        <input type="number" name="price" step="0.01" min="0.01" value="<?= number_format((float)$edit_product['price'], 2, '.', '') ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Stock</label>
                        <input type="number" name="stock_quantity" min="0" value="<?= (int)$edit_product['stock_quantity'] ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; grid-column: 1 / -1;">
                        <label>Description</label>
                        <input type="text" name="description" value="<?= htmlspecialchars($edit_product['description'] ?? '') ?>">
                    </div>
                    <div style="grid-column: 1 / -1; display: flex; gap: 0.6rem;">
                        <button type="submit" class="btn btn-primary inline-btn">Save Changes</button>
                        <a href="admin_products.php" class="btn btn-outline inline-btn">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- ADD NEW PRODUCT DRAWER (Shown when + Add Product is clicked) -->
        <?php if ($show_add_form): ?>
            <div style="background: white; border: 1px solid #ddd; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.05rem;">Add New Product</h3>
                    <a href="admin_products.php" style="font-size: 0.85rem; color: #555; text-decoration: none;">✕ Cancel</a>
                </div>
                <form action="admin_products.php" method="POST" style="display: grid; grid-template-columns: 2fr 1.2fr 1fr 1fr; gap: 1rem; align-items: end;">
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
                    <div style="grid-column: 1 / -1; display: flex; gap: 0.6rem;">
                        <button type="submit" class="btn btn-primary inline-btn">+ Create Product</button>
                        <a href="admin_products.php" class="btn btn-outline inline-btn">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- CLEAN FULL-WIDTH CATALOG TABLE (NO HORIZONTAL SCROLL) -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <table class="catalog-table">
                <thead>
                    <tr>
                        <th style="width: 7%;">ID</th>
                        <th style="width: 39%;">Product</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 11%;">Price</th>
                        <th style="width: 12%;">Stock</th>
                        <th style="width: 16%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_products)): ?>
                        <tr>
                            <td colspan="6" style="padding: 2rem; text-align: center; color: #666;">
                                No products in the catalog yet. <a href="admin_products.php?new=1">Add your first product</a>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_products as $prod): ?>
                            <?php $qty = (int) $prod['stock_quantity']; ?>
                            <tr>
                                <td style="color: #666; font-size: 0.88rem;">#<?= $prod['product_id'] ?></td>
                                <td>
                                    <div style="font-weight: 600; color: #222;"><?= htmlspecialchars($prod['name']) ?></div>
                                    <?php if (!empty($prod['description'])): ?>
                                        <div class="desc-truncate" title="<?= htmlspecialchars($prod['description']) ?>">
                                            <?= htmlspecialchars($prod['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-cat"><?= htmlspecialchars($prod['category'] ?? 'General') ?></span>
                                </td>
                                <td style="font-weight: 600; color: #111;">
                                    $<?= number_format((float)$prod['price'], 2) ?>
                                </td>
                                <td>
                                    <?php if ($qty === 0): ?>
                                        <span class="badge-stock-out">Out of Stock</span>
                                    <?php elseif ($qty <= 10): ?>
                                        <span class="badge-stock-low"><?= $qty ?> left</span>
                                    <?php else: ?>
                                        <span class="badge-stock-ok"><?= $qty ?> in stock</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="admin_products.php?edit=<?= $prod['product_id'] ?>" class="btn btn-outline inline-btn" style="padding: 0.32rem 0.65rem; font-size: 0.8rem;">Edit</a>
                                    <form action="admin_products.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($prod['name'])) ?>?');">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?= $prod['product_id'] ?>">
                                        <button type="submit" class="btn btn-outline inline-btn" style="padding: 0.32rem 0.65rem; font-size: 0.8rem; color: #dc3545; border-color: #dc3545;">Delete</button>
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