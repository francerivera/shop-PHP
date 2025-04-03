<?php
session_start();
include("connections.php");

// Redirect to login if not logged in or not a regular user
if (!isset($_SESSION["user_id"]) || $_SESSION["account_type"] != "2") {
    header("Location: index.php");
    exit();
}

// Fetch user information from the database
$user_id = $_SESSION["user_id"];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $connections->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission to update user information
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $address = trim($_POST["address"]);
    $password = trim($_POST["password"]);

    // Validate inputs (basic validation)
    $errors = [];
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($address)) $errors[] = "Address is required.";

    // If password is not provided, keep the existing password
    $new_password = $user["password"];
    if (!empty($password)) {
        $new_password = $password; // Store password as plain text
    }

    if (empty($errors)) {
        // Update the database
        $update_query = "UPDATE users SET name = ?, email = ?, address = ?, password = ? WHERE id = ?";
        $update_stmt = $connections->prepare($update_query);
        $update_stmt->bind_param("ssssi", $name, $email, $address, $new_password, $user_id);
        if ($update_stmt->execute()) {
            // Update session variable
            $_SESSION["name"] = $name;
            $success = "Information updated successfully!";
            // Refresh user data
            $query = "SELECT * FROM users WHERE id = ?";
            $stmt = $connections->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } else {
            $errors[] = "Failed to update information. Please try again.";
        }
        $update_stmt->close();
    }
}

// Handle sign-out
if (isset($_POST["signout"])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Account - France Rivera Manila</title>
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
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            font-family: 'Playfair Display', serif;
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            height: 80px;
        }
        .error {
            color: red;
            font-size: 0.9em;
            margin-top: 10px;
            text-align: center;
        }
        .success {
            color: green;
            font-size: 0.9em;
            margin-top: 10px;
            text-align: center;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        button[name="signout"] {
            background-color: #f44336;
            color: white;
        }
        button[name="signout"]:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>
    <?php include("nav.php"); ?>

    <div class="container">
        <h2>Your Account</h2>

        <?php if (isset($errors) && !empty($errors)) { ?>
            <div class="error">
                <?php foreach ($errors as $error) echo $error . "<br>"; ?>
            </div>
        <?php } ?>
        <?php if (isset($success)) { ?>
            <div class="success"><?php echo $success; ?></div>
        <?php } ?>

        <form method="POST" action="account.php">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" required><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="password">New Password (leave blank to keep current)</label>
                <input type="password" id="password" name="password" placeholder="Enter new password">
            </div>
            <div class="button-group">
                <button type="submit" name="update">Update Information</button>
                <button type="submit" name="signout">Sign Out</button>
            </div>
        </form>
    </div>
</body>
</html>