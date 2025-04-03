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

$email = $password = "";
$emailErr = $passwordErr = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["email"])) {
        $emailErr = "Email is required!";
    } else {
        $email = $_POST["email"];
    }

    if (empty($_POST["password"])) {
        $passwordErr = "Password is required!";
    } else {
        $password = $_POST["password"];
    }

    if ($email && $password) {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $connections->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Compare the entered password directly with the stored plain text password
            if ($password === $row["password"]) {
                $_SESSION["user_id"] = $row["id"];
                $_SESSION["account_type"] = $row["account_type"];
                $_SESSION["email"] = $row["email"];
                $_SESSION["name"] = $row["name"];
                if ($row["account_type"] == "1") {
                    header("Location: admin.php");
                    exit();
                } else {
                    header("Location: shop.php");
                    exit();
                }
            } else {
                $passwordErr = "Password is incorrect!";
            }
        } else {
            $emailErr = "Email is not registered!";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - France Rivera Manila</title>
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
        .login-container {
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
            font-family: Arial, Helvetica, sans-serif;
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
        .register-link {
            margin-top: 15px;
            font-size: 0.9em;
        }
        .register-link a {
            color: #007bff;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .login-container {
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
    <div class="login-container">
        <a href="index.php" class="branding">
            <div>
                <h1>France Rivera<br><span>Manila</span></h1>
            </div>
        </a>
        <br>
        <h2>Login</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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
            <button type="submit">Login</button>
        </form>
        <div class="register-link">
            New to my shop? <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>