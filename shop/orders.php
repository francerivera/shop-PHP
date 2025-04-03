<?php
session_start();
include("connections.php");

// Redirect to login if not logged in or not a regular user
if (!isset($_SESSION["user_id"]) || $_SESSION["account_type"] != "2") {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Handle actions (remove, update quantity, checkout)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["remove"])) {
        $order_id = (int)$_POST["order_id"];
        $delete_query = "DELETE FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'";
        $delete_stmt = $connections->prepare($delete_query);
        $delete_stmt->bind_param("ii", $order_id, $user_id);
        if ($delete_stmt->execute()) {
            $success = "Product removed from cart successfully!";
        } else {
            $error = "Failed to remove product from cart.";
        }
        $delete_stmt->close();
    } elseif (isset($_POST["update_quantity"])) {
        $order_id = (int)$_POST["order_id"];
        $quantity = (int)$_POST["quantity"];
        if ($quantity < 1) {
            $error = "Quantity must be at least 1.";
        } else {
            $update_query = "UPDATE orders SET quantity = ? WHERE id = ? AND user_id = ? AND status = 'pending'";
            $update_stmt = $connections->prepare($update_query);
            $update_stmt->bind_param("iii", $quantity, $order_id, $user_id);
            if ($update_stmt->execute()) {
                $success = "Quantity updated successfully!";
            } else {
                $error = "Failed to update quantity.";
            }
            $update_stmt->close();
        }
    } elseif (isset($_POST["checkout"])) {
        // Mark all pending orders as completed
        $update_query = "UPDATE orders SET status = 'completed' WHERE user_id = ? AND status = 'pending'";
        $update_stmt = $connections->prepare($update_query);
        $update_stmt->bind_param("i", $user_id);
        if ($update_stmt->execute()) {
            $success = "Checkout successful! Your order has been placed.";
        } else {
            $error = "Checkout failed. Please try again.";
        }
        $update_stmt->close();
    }
}

// Fetch the user's pending orders (cart items)
$query = "SELECT o.id, o.product_id, o.quantity, p.name, p.price, p.image, p.mime_type 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          WHERE o.user_id = ? AND o.status = 'pending'";
$stmt = $connections->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
$total_price = 0;
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $total_price += $row['price'] * $row['quantity'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Cart - France Rivera Manila</title>
    <!-- Import Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1000px;
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
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item img {
            width: 100px;
            height: 75px;
            object-fit: contain;
            border-radius: 4px;
            margin-right: 20px;
        }
        .order-details {
            flex: 1;
        }
        .order-details h3 {
            font-family: 'Goudy', serif;
            font-size: 1.2em;
            color: #333;
            margin: 0 0 5px;
        }
        .order-details p {
            margin: 5px 0;
            color: #666;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        .quantity-controls button {
            background-color: rgb(100, 100, 100);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .quantity-controls button:hover {
            background-color: rgb(71, 71, 71);
        }
        .quantity-controls input {
            width: 50px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .remove-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .remove-btn:hover {
            background-color: #c0392b;
        }
        .total-price {
            font-size: 1.2em;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            color: #333;
        }
        .checkout-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px;
            background-color: rgb(100, 100, 100);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .checkout-btn:hover {
            background-color: rgb(71, 71, 71);
        }
        .empty-cart {
            text-align: center;
            color: #666;
            font-size: 1.2em;
            margin-top: 20px;
        }
        .error {
            color: red;
            font-size: 0.9em;
            text-align: center;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            font-size: 0.9em;
            text-align: center;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .order-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .order-item img {
                width: 80px;
                height: 60px;
                margin-right: 0;
            }
            .quantity-controls {
                margin: 10px 0;
            }
            .total-price {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include("nav.php"); ?>

    <div class="container">
        <h2>Your Cart</h2>

        <?php if (isset($error)) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>
        <?php if (isset($success)) { ?>
            <div class="success"><?php echo $success; ?></div>
        <?php } ?>

        <?php if (empty($orders)) { ?>
            <div class="empty-cart">Your cart is empty. <a href="shop.php">Start shopping now!</a></div>
        <?php } else { ?>
            <?php foreach ($orders as $order) { ?>
                <div class="order-item">
                    <img src="get_image.php?id=<?php echo $order['product_id']; ?>&type=product" alt="<?php echo htmlspecialchars($order['name']); ?>">
                    <div class="order-details">
                        <h3><?php echo htmlspecialchars($order['name']); ?></h3>
                        <p>Price: $<?php echo number_format($order['price'], 2); ?></p>
                        <form method="POST" action="orders.php" class="quantity-controls">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="update_quantity" onclick="this.form.quantity.value--">-</button>
                            <input type="number" name="quantity" value="<?php echo $order['quantity']; ?>" min="1" readonly>
                            <button type="submit" name="update_quantity" onclick="this.form.quantity.value++">+</button>
                        </form>
                        <p>Subtotal: $<?php echo number_format($order['price'] * $order['quantity'], 2); ?></p>
                    </div>
                    <form method="POST" action="orders.php">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" name="remove" class="remove-btn">Remove</button>
                    </form>
                </div>
            <?php } ?>

            <div class="total-price">
                Total: $<?php echo number_format($total_price, 2); ?>
            </div>

            <form method="POST" action="orders.php">
                <button type="submit" name="checkout" class="checkout-btn">Checkout</button>
            </form>
        <?php } ?>
    </div>
</body>
</html>