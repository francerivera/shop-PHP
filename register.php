<?php
session_start();
include("connections.php");

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    if ($_SESSION["account_type"] == "1") {
        header("Location: admin.php");
        exit();
    } else {
        header("Location: shop.php");
        exit();
    }
}

$name = $email = $password = "";
$nameErr = $emailErr = $passwordErr = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty($_POST["name"])) {
        $nameErr = "Name is required!";
    } else {
        $name = $_POST["name"];
    }

    // Validate email
    if (empty($_POST["email"])) {
        $emailErr = "Email is required!";
    } else {
        $email = $_POST["email"];
        // Validate email format (general validation, no .com requirement)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Please enter a valid email address!";
        }
    }

    // Validate password
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required!";
    } else {
        $password = $_POST["password"];
    }

    // Proceed with registration if all validations pass
    if ($name && $email && $password && empty($emailErr)) {
        // Check for duplicate email using a prepared statement to prevent SQL injection
        $check_email_query = "SELECT * FROM users WHERE email = ?";
        $check_email_stmt = $connections->prepare($check_email_query);
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $check_email_result = $check_email_stmt->get_result();
        if ($check_email_result->num_rows > 0) {
            $emailErr = "Email is already registered!";
        } else {
            // Insert the user with the plain-text password (not recommended)
            $query = "INSERT INTO users (name, email, password, account_type) VALUES (?, ?, ?, '2')";
            $stmt = $connections->prepare($query);
            $stmt->bind_param("sss", $name, $email, $password);
            if ($stmt->execute()) {
                // Get the newly created user's ID
                $user_id = $stmt->insert_id;
                
                // Set session variables to log the user in
                $_SESSION["user_id"] = $user_id;
                $_SESSION["account_type"] = "2"; // User account type
                $_SESSION["name"] = $name; // Set the name in the session
                
                // Redirect to shop.php
                header("Location: shop.php");
                exit();
            } else {
                $emailErr = "Error registering user.";
            }
            $stmt->close();
        }
        $check_email_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - France Rivera Manila</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .branding {
            text-decoration: none;
            display: block;
            margin-bottom: 20px;
        }
        .branding h1 {
            font-family: 'Goudy Catalogue', serif;
            font-size: 2em;
            color: #333;
            margin: 0;
            line-height: 1;
        }
        .branding h1 span {
            display: block;
            font-size: 0.6em;
        }
        h2 {
            font-family: Arial, sans-serif;
            font-size: 2em;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .error {
            color: red;
            font-size: 0.9em;
            margin-bottom: 15px;
            text-align: center;
        }
        .success {
            color: green;
            font-size: 0.9em;
            margin-bottom: 15px;
            text-align: center;
        }
        button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: rgb(100, 100, 100);
            color: white;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: rgb(71, 71, 71);
        }
        .login-link {
            margin-top: 15px;
            font-size: 0.9em;
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .register-container {
                padding: 20px;
                max-width: 300px;
            }
            .branding h1 {
                font-size: 2em;
            }
            h2 {
                font-size: 1.5em;
            }
            .form-group input {
                padding: 8px;
                font-size: 0.9em;
            }
            button {
                padding: 8px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="index.php" class="branding">
            <div>
                <h1>France Rivera<br><span>Manila</span></h1>
            </div>
        </a>
        <br>
        <h2>Register</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                <span class="error"><?php echo $nameErr; ?></span>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <span class="error"><?php echo $emailErr; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <span class="error"><?php echo $passwordErr; ?></span>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="index.php">Login</a>
        </div>
    </div>
</body>
</html>