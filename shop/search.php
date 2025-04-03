<?php
session_start();
include("connections.php");

// Redirect to login if not logged in or not a regular user
if (!isset($_SESSION["user_id"]) || $_SESSION["account_type"] != "2") {
    header("Location: index.php");
    exit();
}

// Initialize variables
$search_query = "";
$products = [];
$search_performed = false;

// Handle search form submission
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["query"])) {
    $search_query = trim($_GET["query"]);
    $search_performed = true;

    // Search products by name
    $query = "SELECT * FROM products WHERE name LIKE ?";
    $search_term = "%" . $search_query . "%";
    $stmt = $connections->prepare($query);
    $stmt->bind_param("s", $search_term); // Only one "s" for a single string
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Products - France Rivera Manila</title>
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
        .search-form {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .search-form input[type="text"] {
            width: 300px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            font-size: 1em;
        }
        .search-form button {
            padding: 10px 20px;
            border: none;
            border-radius: 0 4px 4px 0;
            background-color: rgb(100, 100, 100);
            color: white;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .search-form button:hover {
            background-color: rgb(71, 71, 71);
        }
        .search-results {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .product-item {
            width: 224px;
            text-align: center;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .product-item:hover {
            transform: scale(1.05);
        }
        .product-item img {
            width: 224px;
            height: 168px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .product-item h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1em;
            color: #333;
            margin: 0 0 5px;
            line-height: 1.2;
        }
        .product-item p {
            font-size: 0.9em;
            color: #666;
            margin: 0;
        }
        .product-item a {
            text-decoration: none;
        }
        .no-results {
            text-align: center;
            color: #666;
            font-size: 1.2em;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .search-form input[type="text"] {
                width: 200px;
            }
            .product-item {
                width: 200px;
            }
            .product-item img {
                width: 200px;
                height: 150px;
            }
            .product-item h3 {
                font-size: 1em;
            }
        }
        @media (max-width: 480px) {
            .search-form input[type="text"] {
                width: 150px;
            }
            .product-item {
                width: 180px;
            }
            .product-item img {
                width: 180px;
                height: 135px;
            }
            .product-item h3 {
                font-size: 0.9em;
            }
            .product-item p {
                font-size: 0.8em;
            }
        }
    </style>
</head>
<body>
    <?php include("nav.php"); ?>

    <div class="container">
        <h2>Search Products</h2>
        <form method="GET" action="search.php" class="search-form">
            <input type="text" name="query" placeholder="Search for products..." value="<?php echo htmlspecialchars($search_query); ?>" required>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>

        <?php if ($search_performed) { ?>
            <?php if (empty($products)) { ?>
                <div class="no-results">0 results found for "<?php echo htmlspecialchars($search_query); ?>"</div>
            <?php } else { ?>
                <div class="search-results">
                    <?php foreach ($products as $product) { ?>
                        <div class="product-item">
                            <a href="product.php?id=<?php echo $product['id']; ?>">
                                <img src="get_image.php?id=<?php echo $product['id']; ?>&type=product" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p>$<?php echo number_format($product['price'], 2); ?></p>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</body>
</html>