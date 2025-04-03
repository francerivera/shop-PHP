<?php // No session_start() here, as it’s handled by the including page
include("connections.php");

// Fetch the number of items in the cart for the logged-in user
$cart_count = 0;
if (isset($_SESSION["user_id"]) && $_SESSION["account_type"] == "2") {
    $user_id = $_SESSION["user_id"];
    $query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'pending'";
    $stmt = $connections->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cart_count = $row['count'];
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<nav>
    <a href="<?php echo $_SESSION['account_type'] == '1' ? 'admin.php' : 'shop.php'; ?>" class="branding">
        <div>
            <h1>France Rivera<br><span>Manila</span></h1>
        </div>
    </a>
    <ul>
        <?php if (isset($_SESSION['user_id'])) { ?>
            <?php if ($_SESSION['account_type'] == "1") { ?>
                <!-- Admin Links -->
                <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i></a></li> <!-- Dashboard icon for admin -->
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i></a></li> <!-- Logout icon -->
            <?php } else { ?>
                <!-- Regular User Links -->
                <li><a href="search.php"><i class="fas fa-search"></i></a></li> <!-- Search icon -->
                <li><a href="account.php"><i class="fas fa-user"></i></a></li> <!-- Person icon for account -->
                <li class="cart-icon">
                    <a href="orders.php">
                        <i class="fas fa-shopping-bag"></i>
                        <?php if ($cart_count > 0) { ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php } ?>
                    </a>
                </li> <!-- Shopping bag icon for orders with cart count -->
            <?php } ?>
        <?php } else { ?>
            <li><a href="search.php"><i class="fas fa-search"></i></a></li> <!-- Search icon -->
            <li><a href="index.php">Login</a></li>
        <?php } ?>
    </ul>
</nav>

<style>
    nav {
        background-color: white;
        padding: 15px 0;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        height: 60px;
    }

    .branding {
        text-align: center;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        text-decoration: none; /* Remove underline from link */
    }

    .branding h1 {
        margin: 0;
        font-family: 'Goudy', serif;
        font-size: 2em;
        font-weight: 700;
        color: black;
        letter-spacing: 1px;
        line-height: 1;
    }

    .branding h1 span {
        font-family: 'Goudy', serif;
        font-size: 0.8em;
        font-weight: 400;
        color: #000000;
        display: block;
    }

    /* Optional: Add hover effect for branding */
    .branding:hover h1 {
        color: #333; /* Slightly darker on hover */
    }

    .branding:hover h1 span {
        color: #000000; /* Slightly darker gray on hover */
    }

    nav ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
        position: absolute;
        right: 50px; /* Increased from 20px to 40px for more padding on the right */
    }

    nav ul li {
        display: inline;
        margin-left: 10px; /* Increased from 20px to 30px for more spacing between icons */
    }

    nav ul li a {
        color: black;
        text-decoration: none;
        padding: 10px;
        font-size: 1.5em;
    }

    nav ul li a i {
        color: #666;
        font-size: 1em; /* Increased icon size from default to 1.5em */
    }

    nav ul li a:hover {
        background-color: #e0e0e0;
        border-radius: 4px;
    }

    /* Styling for the cart icon and count */
    .cart-icon {
        position: relative;
    }

    .cart-count {
        position: absolute;
        top: 0px;
        right: 0px;
        background-color: red;
        color: white;
        font-size: 0.7em;
        font-weight: bold;
        border-radius: 50%;
        padding: 2px 6px;
        line-height: 1;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        nav ul {
            right: 20px; /* Reduce right padding on smaller screens */
        }
        nav ul li {
            margin-left: 15px; /* Reduce spacing between icons on smaller screens */
        }
        nav ul li a {
            padding: 8px;
            font-size: 1em; /* Slightly smaller on mobile */
        }
        nav ul li a i {
            font-size: 1.3em; /* Slightly smaller icons on mobile */
        }
        .branding h1 {
            font-size: 1.5em; /* Adjust logo size on mobile */
        }
        .branding h1 span {
            font-size: 0.7em;
        }
    }
</style>
</body>
</html>