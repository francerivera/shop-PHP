<?php
session_start();
include("connections.php");

// Redirect if not logged in or not an admin
if (!isset($_SESSION["user_id"]) || $_SESSION["account_type"] != "1") {
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION["user_id"];
$error = "";
$success = "";

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle form submissions (delete, update, add for users and products)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- User Management ---
    // Delete user
    if (isset($_POST["delete_user"])) {
        $user_id = (int)$_POST["user_id"];
        if ($user_id == $admin_id) {
            $error = "You cannot delete your own account.";
        } else {
            // Begin a transaction to ensure data consistency
            $connections->begin_transaction();

            try {
                // Step 1: Delete related orders from the orders table
                $delete_orders_query = "DELETE FROM orders WHERE user_id = ?";
                $delete_orders_stmt = $connections->prepare($delete_orders_query);
                if (!$delete_orders_stmt) {
                    throw new Exception("Failed to prepare query for deleting orders: " . $connections->error);
                }
                $delete_orders_stmt->bind_param("i", $user_id);
                if (!$delete_orders_stmt->execute()) {
                    throw new Exception("Failed to delete orders: " . $delete_orders_stmt->error);
                }
                $delete_orders_stmt->close();

                // Step 2: Delete the user from the users table
                $delete_query = "DELETE FROM users WHERE id = ?";
                $delete_stmt = $connections->prepare($delete_query);
                if (!$delete_stmt) {
                    throw new Exception("Failed to prepare user deletion query: " . $connections->error);
                }
                $delete_stmt->bind_param("i", $user_id);
                if (!$delete_stmt->execute()) {
                    throw new Exception("Failed to delete user: " . $delete_stmt->error);
                }
                $delete_stmt->close();

                // Commit the transaction
                $connections->commit();
                $success = "User deleted successfully! All related orders have been deleted.";
            } catch (Exception $e) {
                // Roll back the transaction on error
                $connections->rollback();
                $error = "Error deleting user: " . $e->getMessage();
            }
        }
    }

    // Update user (including password)
    if (isset($_POST["update_user"])) {
        error_log("Update user block reached");

        $user_id = (int)$_POST["user_id"];
        $name = trim($_POST["name"]);
        $email = trim($_POST["email"]);
        $account_type = (int)$_POST["account_type"];
        $password = isset($_POST["password"]) ? trim($_POST["password"]) : "";

        error_log("User ID: $user_id, Name: $name, Email: $email, Account Type: $account_type, Password: $password");

        if (empty($name) || empty($email) || !in_array($account_type, [1, 2])) {
            $error = "Please fill in all user fields correctly.";
        } else {
            $email_query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $email_stmt = $connections->prepare($email_query);
            if (!$email_stmt) {
                $error = "Email query preparation failed: " . $connections->error;
            } else {
                $email_stmt->bind_param("si", $email, $user_id);
                $email_stmt->execute();
                $email_result = $email_stmt->get_result();
                if ($email_result->num_rows > 0) {
                    $error = "Email is already in use by another user.";
                } else {
                    if (!empty($password)) {
                        error_log("Updating user with new password");
                        $update_query = "UPDATE users SET name = ?, email = ?, account_type = ?, password = ? WHERE id = ?";
                        $update_stmt = $connections->prepare($update_query);
                        if (!$update_stmt) {
                            $error = "Update user query preparation failed: " . $connections->error;
                        } else {
                            $update_stmt->bind_param("ssisi", $name, $email, $account_type, $password, $user_id);
                            if ($update_stmt->execute()) {
                                $success = "User updated successfully! New password set.";
                            } else {
                                $error = "Failed to update user: " . $update_stmt->error;
                            }
                            $update_stmt->close();
                        }
                    } else {
                        error_log("Updating user without changing password");
                        $update_query = "UPDATE users SET name = ?, email = ?, account_type = ? WHERE id = ?";
                        $update_stmt = $connections->prepare($update_query);
                        if (!$update_stmt) {
                            $error = "Update user query preparation failed: " . $connections->error;
                        } else {
                            $update_stmt->bind_param("ssii", $name, $email, $account_type, $user_id);
                            if ($update_stmt->execute()) {
                                $success = "User updated successfully!";
                            } else {
                                $error = "Failed to update user: " . $update_stmt->error;
                            }
                            $update_stmt->close();
                        }
                    }
                }
                $email_stmt->close();
            }
        }
    }

    // Add new user
    if (isset($_POST["add_user"])) {
        $name = trim($_POST["new_name"]);
        $email = trim($_POST["new_email"]);
        $password = $_POST["new_password"];
        $account_type = (int)$_POST["new_account_type"];

        if (empty($name) || empty($email) || empty($password) || !in_array($account_type, [1, 2])) {
            $error = "Please fill in all user fields correctly.";
        } else {
            $email_query = "SELECT id FROM users WHERE email = ?";
            $email_stmt = $connections->prepare($email_query);
            $email_stmt->bind_param("s", $email);
            $email_stmt->execute();
            $email_result = $email_stmt->get_result();
            if ($email_result->num_rows > 0) {
                $error = "Email is already in use.";
            } else {
                $insert_query = "INSERT INTO users (name, email, password, account_type) VALUES (?, ?, ?, ?)";
                $insert_stmt = $connections->prepare($insert_query);
                $insert_stmt->bind_param("sssi", $name, $email, $password, $account_type);
                if ($insert_stmt->execute()) {
                    $success = "New user added successfully!";
                } else {
                    $error = "Failed to add new user: " . $insert_stmt->error;
                }
                $insert_stmt->close();
            }
            $email_stmt->close();
        }
    }

    // --- Product Management ---
    // Add new product
    if (isset($_POST["add_product"])) {
        $name = trim($_POST["new_product_name"]);
        $price = (float)$_POST["new_product_price"];
        $description = trim($_POST["new_product_description"]);

        // Validate inputs
        if (empty($name) || $price <= 0 || empty($_FILES["new_product_image"]["name"])) {
            $error = "Please fill in all product fields correctly and upload an image.";
        } else {
            // Check if the file was uploaded without errors
            $image = $_FILES["new_product_image"];
            if ($image["error"] !== UPLOAD_ERR_OK) {
                $error = "Image upload failed with error code: " . $image["error"];
            } else {
                // Validate file type (only allow JPG, PNG)
                $allowed_types = ['image/jpeg', 'image/png'];
                $file_type = mime_content_type($image["tmp_name"]);
                if (!in_array($file_type, $allowed_types)) {
                    $error = "Only JPG and PNG images are allowed.";
                } else {
                    // Read the image file as binary data
                    $image_data = file_get_contents($image["tmp_name"]);
                    if ($image_data === false) {
                        $error = "Failed to read the uploaded image.";
                    } else {
                        // Insert product into database with image as BLOB
                        $insert_query = "INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)";
                        $insert_stmt = $connections->prepare($insert_query);
                        if (!$insert_stmt) {
                            $error = "Insert query preparation failed: " . $connections->error;
                        } else {
                            // Bind the image data as a string (binary data)
                            $insert_stmt->bind_param("sdss", $name, $price, $description, $image_data);
                            if ($insert_stmt->execute()) {
                                $success = "New product added successfully!";
                            } else {
                                $error = "Failed to add new product: " . $insert_stmt->error;
                            }
                            $insert_stmt->close();
                        }
                    }
                }
            }
        }
    }

    // Update product
    if (isset($_POST["update_product"])) {
        $product_id = (int)$_POST["product_id"];
        $name = trim($_POST["product_name"]);
        $price = (float)$_POST["product_price"];
        $description = trim($_POST["product_description"]);

        if (empty($name) || $price <= 0) {
            $error = "Please fill in all product fields correctly.";
        } else {
            if (!empty($_FILES["product_image"]["name"])) {
                // Update product with new image
                $image = $_FILES["product_image"];
                if ($image["error"] !== UPLOAD_ERR_OK) {
                    $error = "Image upload failed with error code: " . $image["error"];
                } else {
                    $allowed_types = ['image/jpeg', 'image/png'];
                    $file_type = mime_content_type($image["tmp_name"]);
                    if (!in_array($file_type, $allowed_types)) {
                        $error = "Only JPG and PNG images are allowed.";
                    } else {
                        $image_data = file_get_contents($image["tmp_name"]);
                        if ($image_data === false) {
                            $error = "Failed to read the uploaded image.";
                        } else {
                            $update_query = "UPDATE products SET name = ?, price = ?, description = ?, image = ? WHERE id = ?";
                            $update_stmt = $connections->prepare($update_query);
                            if (!$update_stmt) {
                                $error = "Update query preparation failed: " . $connections->error;
                            } else {
                                $update_stmt->bind_param("sdssi", $name, $price, $description, $image_data, $product_id);
                                if ($update_stmt->execute()) {
                                    $success = "Product updated successfully! Image updated.";
                                } else {
                                    $error = "Failed to update product: " . $update_stmt->error;
                                }
                                $update_stmt->close();
                            }
                        }
                    }
                }
            } else {
                // Update product without changing the image
                $update_query = "UPDATE products SET name = ?, price = ?, description = ? WHERE id = ?";
                $update_stmt = $connections->prepare($update_query);
                if (!$update_stmt) {
                    $error = "Update query preparation failed: " . $connections->error;
                } else {
                    $update_stmt->bind_param("sdsi", $name, $price, $description, $product_id);
                    if ($update_stmt->execute()) {
                        $success = "Product updated successfully!";
                    } else {
                        $error = "Failed to update product: " . $update_stmt->error;
                    }
                    $update_stmt->close();
                }
            }
        }
    }

    // Delete product
    if (isset($_POST["delete_product"])) {
        $product_id = (int)$_POST["product_id"];

        // Begin a transaction to ensure data consistency
        $connections->begin_transaction();

        try {
            // Delete dependent records from related tables
            $related_tables = [
                "orders" => "product_id"
                // Add other tables as needed, e.g., "wishlist" => "product_id"
            ];

            foreach ($related_tables as $table => $column) {
                $delete_related_query = "DELETE FROM $table WHERE $column = ?";
                $delete_related_stmt = $connections->prepare($delete_related_query);
                if (!$delete_related_stmt) {
                    throw new Exception("Failed to prepare query for $table: " . $connections->error);
                }
                $delete_related_stmt->bind_param("i", $product_id);
                if (!$delete_related_stmt->execute()) {
                    throw new Exception("Failed to delete from $table: " . $delete_related_stmt->error);
                }
                $delete_related_stmt->close();
            }

            // Now delete the product
            $delete_query = "DELETE FROM products WHERE id = ?";
            $delete_stmt = $connections->prepare($delete_query);
            if (!$delete_stmt) {
                throw new Exception("Failed to prepare product deletion query: " . $connections->error);
            }
            $delete_stmt->bind_param("i", $product_id);
            if (!$delete_stmt->execute()) {
                throw new Exception("Failed to delete product: " . $delete_stmt->error);
            }
            $delete_stmt->close();

            // Commit the transaction
            $connections->commit();
            $success = "Product deleted successfully!";
        } catch (Exception $e) {
            // Roll back the transaction on error
            $connections->rollback();
            $error = "Error deleting product: " . $e->getMessage();
        }
    }
}

// Fetch all users from the database
$query = "SELECT id, name, email, account_type, password, created_at FROM users ORDER BY created_at DESC";
$result = $connections->query($query);
if (!$result) {
    die("Query failed: " . $connections->error);
}
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Fetch all products from the database
$product_query = "SELECT id, name, price, description FROM products ORDER BY id DESC";
$product_result = $connections->query($product_query);
if (!$product_result) {
    die("Product query failed: " . $connections->error);
}
$products = [];
while ($row = $product_result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - France Rivera Manila</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2em;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .add-user-form, .add-product-form {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .add-user-form h3, .add-product-form h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5em;
            color: #333;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
            padding-right: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 400px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .error {
            color: red;
            font-size: 0.9em;
            text-align: center;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            font-size: 0.9em;
            text-align: center;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: rgb(100, 100, 100);
            color: white;
            font-family: 'Playfair Display', serif;
            font-size: 1.1em;
        }
        table td {
            color: #333;
        }
        table tr:hover {
            background-color: #f5f5f5;
        }
        .account-type {
            font-weight: bold;
        }
        .account-type.admin {
            color: #e74c3c;
        }
        .account-type.user {
            color: #2ecc71;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 4px;
        }
        .action-buttons form {
            display: inline-block;
            margin-right: 5px;
        }
        .action-buttons button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.9em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .action-buttons .edit-btn {
            background-color: #3498db;
            color: white;
        }
        .action-buttons .edit-btn:hover {
            background-color: #2980b9;
        }
        .action-buttons .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        .action-buttons .delete-btn:hover {
            background-color: #c0392b;
        }
        .no-users, .no-products {
            text-align: center;
            color: #666;
            font-size: 1.2em;
            margin-top: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            position: relative;
        }
        .modal-content h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5em;
            color: #333;
            margin-bottom: 15px;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5em;
            color: #666;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            table th, table td {
                padding: 8px;
                font-size: 0.9em;
            }
            .action-buttons button {
                padding: 4px 8px;
                font-size: 0.8em;
            }
            .form-group input, .form-group select, .form-group textarea {
                width: 100%;
                padding: 8px;
            }
            .product-image {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <?php include("nav.php"); ?>

    <div class="container">
        <h2>Admin Dashboard</h2>

        <?php if ($error) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>
        <?php if ($success) { ?>
            <div class="success"><?php echo $success; ?></div>
        <?php } ?>

        <!-- User Management Section -->
        <h2>User Management</h2>
        <!-- Add New User Form -->
        <div class="add-user-form">
            <h3>Add New User</h3>
            <form method="POST" action="admin.php">
                <div class="form-group">
                    <label for="new_name">Name</label>
                    <input type="text" id="new_name" name="new_name" required>
                </div>
                <div class="form-group">
                    <label for="new_email">Email</label>
                    <input type="email" id="new_email" name="new_email" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password</label>
                    <input type="text" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="new_account_type">Account Type</label>
                    <select id="new_account_type" name="new_account_type" required>
                        <option value="1">Admin</option>
                        <option value="2" selected>User</option>
                    </select>
                </div>
                <button type="submit" name="add_user">Add User</button>
            </form>
        </div>

        <!-- User Table -->
        <?php if (empty($users)) { ?>
            <div class="no-users">No users found.</div>
        <?php } else { ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Account Type</th>
                        <th>Password</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) { ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="account-type <?php echo $user['account_type'] == '1' ? 'admin' : 'user'; ?>">
                                    <?php echo $user['account_type'] == '1' ? 'Admin' : 'User'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user['password']); ?></td>
                            <td><?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?></td>
                            <td class="action-buttons">
                                <button class="edit-btn" onclick="openEditUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>', '<?php echo $user['account_type']; ?>')">Edit</button>
                                <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>

        <!-- Product Management Section -->
        <h2>Product Management</h2>
        <!-- Add New Product Form -->
        <div class="add-product-form">
            <h3>Add New Product</h3>
            <form method="POST" action="admin.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="new_product_name">Product Name</label>
                    <input type="text" id="new_product_name" name="new_product_name" required>
                </div>
                <div class="form-group">
                    <label for="new_product_price">Price</label>
                    <input type="number" step="0.01" id="new_product_price" name="new_product_price" required>
                </div>
                <div class="form-group">
                    <label for="new_product_description">Description</label>
                    <textarea id="new_product_description" name="new_product_description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="new_product_image">Product Image</label>
                    <input type="file" id="new_product_image" name="new_product_image" accept="image/*" required>
                </div>
                <button type="submit" name="add_product">Add Product</button>
            </form>
        </div>

        <!-- Product Table -->
        <?php if (empty($products)) { ?>
            <div class="no-products">No products found.</div>
        <?php } else { ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) { ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <img src="get_image.php?id=<?php echo $product['id']; ?>&type=product" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                            <td class="action-buttons">
                                <button class="edit-btn" onclick="openEditProductModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>', '<?php echo $product['price']; ?>', '<?php echo htmlspecialchars($product['description'], ENT_QUOTES); ?>')">Edit</button>
                                <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditUserModal()">×</span>
            <h3>Edit User</h3>
            <form method="POST" action="admin.php">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_name">Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_account_type">Account Type</label>
                    <select id="edit_account_type" name="account_type" required>
                        <option value="1">Admin</option>
                        <option value="2">User</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_password">New Password (Leave blank to keep unchanged)</label>
                    <input type="text" id="edit_password" name="password" placeholder="Enter new password">
                </div>
                <button type="submit" name="update_user">Update User</button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditProductModal()">×</span>
            <h3>Edit Product</h3>
            <form method="POST" action="admin.php" enctype="multipart/form-data">
                <input type="hidden" id="edit_product_id" name="product_id">
                <div class="form-group">
                    <label for="edit_product_name">Product Name</label>
                    <input type="text" id="edit_product_name" name="product_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_product_price">Price</label>
                    <input type="number" step="0.01" id="edit_product_price" name="product_price" required>
                </div>
                <div class="form-group">
                    <label for="edit_product_description">Description</label>
                    <textarea id="edit_product_description" name="product_description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_product_image">New Image (Leave blank to keep unchanged)</label>
                    <input type="file" id="edit_product_image" name="product_image" accept="image/*">
                </div>
                <button type="submit" name="update_product">Update Product</button>
            </form>
        </div>
    </div>

    <script>
        // User Modal Scripts
        function openEditUserModal(userId, name, email, accountType) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_account_type').value = accountType;
            document.getElementById('edit_password').value = '';
            document.getElementById('editUserModal').style.display = 'flex';
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Product Modal Scripts
        function openEditProductModal(productId, name, price, description) {
            document.getElementById('edit_product_id').value = productId;
            document.getElementById('edit_product_name').value = name;
            document.getElementById('edit_product_price').value = price;
            document.getElementById('edit_product_description').value = description;
            document.getElementById('editProductModal').style.display = 'flex';
        }

        function closeEditProductModal() {
            document.getElementById('editProductModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const userModal = document.getElementById('editUserModal');
            const productModal = document.getElementById('editProductModal');
            if (event.target == userModal) {
                userModal.style.display = 'none';
            }
            if (event.target == productModal) {
                productModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>