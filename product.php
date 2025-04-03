<?php
session_start();
include("connections.php");

// Redirect to login if not logged in or not a regular user
if (!isset($_SESSION["user_id"]) || $_SESSION["account_type"] != "2") {
    header("Location: index.php");
    exit();
}

// Check if product ID is provided
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: shop.php");
    exit();
}

$product_id = (int)$_GET["id"];

// Fetch product details from the database
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $connections->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

// If product not found, redirect to shop.php
if (!$product) {
    header("Location: shop.php");
    exit();
}

// Handle form submission to add to order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_order"])) {
    $quantity = (int)$_POST["quantity"];
    $user_id = $_SESSION["user_id"];

    // Validate quantity
    if ($quantity < 1) {
        $error = "Please enter a valid quantity (at least 1).";
    } else {
        // Check if the product is already in the cart
        $check_query = "SELECT id, quantity FROM orders WHERE user_id = ? AND product_id = ? AND status = 'pending'";
        $check_stmt = $connections->prepare($check_query);
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $existing_order = $check_result->fetch_assoc();
        $check_stmt->close();

        if ($existing_order) {
            // Update quantity if the product is already in the cart
            $new_quantity = $existing_order['quantity'] + $quantity;
            $update_query = "UPDATE orders SET quantity = ? WHERE id = ?";
            $update_stmt = $connections->prepare($update_query);
            $update_stmt->bind_param("ii", $new_quantity, $existing_order['id']);
            if ($update_stmt->execute()) {
                $success = "Product quantity updated in your cart!";
            } else {
                $error = "Failed to update product quantity in cart.";
            }
            $update_stmt->close();
        } else {
            // Insert into orders table with status 'pending'
            $insert_query = "INSERT INTO orders (user_id, product_id, quantity, status) VALUES (?, ?, ?, 'pending')";
            $insert_stmt = $connections->prepare($insert_query);
            $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
            if ($insert_stmt->execute()) {
                $success = "Product added to your cart successfully!";
            } else {
                $error = "Failed to add product to cart. Please try again.";
            }
            $insert_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['name']); ?> - France Rivera Manila</title>
    <!-- Import Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons (already included in nav.php) -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 2500px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
            justify-content: center; /* Center the flex items horizontally */
            align-items: center; /* Center the flex items vertically */
        }
        .product-image {
            text-align: center; /* Center the image within its container */
        }
        .product-image img {
            width: 300px;
            height: 225px;
            object-fit: contain;
            border-radius: 8px;
        }
        .product-details {
            flex: 1;
            text-align: center; /* Center the text within product details */
            max-width: 1000px; /* Optional: Limit the width of the details for better readability */
        }
        .product-details h2 {
            font-family: 'Goudy', serif; /* Fixed font name from 'Goudy' to 'Playfair Display' */
            font-size: 2em;
            color: #333;
            margin: 0 0 10px;
        }
        .product-details .price {
            font-size: 1.5em;
            color: rgb(65, 65, 65);
            margin-bottom: 15px;
        }
        .product-details .description {
            font-size: 1.5em;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 15px;
            display: flex;
            justify-content: center; /* Center the form elements */
            align-items: center;
            gap: 10px;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 0; /* Remove bottom margin since we're aligning horizontally */
            color: #333;
        }
        .form-group input {
            width: 100px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }
        .error {
            color: red;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            background-color: rgb(100, 100, 100);
            color: white;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: rgb(71, 71, 71);
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                align-items: center;
            }
            .product-image img {
                width: 200px;
                height: 150px;
            }
            .product-details {
                max-width: 100%; /* Allow full width on smaller screens */
            }
        }
    </style>
</head>
<body>
    <?php include("nav.php"); ?>

    <div class="container">
        <div class="product-image">
            <img src="get_image.php?id=<?php echo $product_id; ?>&type=product" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="product-details">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <div class="price">$<?php echo number_format($product['price'], 2); ?></div>
            <div class="description"><?php echo htmlspecialchars($product['description']); ?></div>

            <?php if (isset($error)) { ?>
                <div class="error"><?php echo $error; ?></div>
            <?php } ?>
            <?php if (isset($success)) { ?>
                <div class="success"><?php echo $success; ?></div>
            <?php } ?>

            <form method="POST" action="product.php?id=<?php echo $product_id; ?>">
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" required>
                    <button type="submit" name="add_to_order">Add to Cart</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>