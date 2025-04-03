<?php
session_start();
include("connections.php");

// Redirect to login if not logged in or not a regular user
if (!isset($_SESSION["user_id"]) || $_SESSION["account_type"] != "2") {
    header("Location: index.php");
    exit();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch products from the database
$query = "SELECT * FROM products";
$result = $connections->query($query);
if (!$result) {
    die("Query failed: " . $connections->error);
}
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Debug: Check if products are fetched
if (empty($products)) {
    echo "No products found in the database.";
    exit();
}

// Fetch hero images from the database
$query = "SELECT * FROM hero_images ORDER BY id ASC LIMIT 5";
$result = $connections->query($query);
if (!$result) {
    die("Query failed: " . $connections->error);
}
$hero_images = [];
while ($row = $result->fetch_assoc()) {
    $hero_images[] = $row;
}

// Debug: Check if hero images are fetched
if (empty($hero_images)) {
    echo "No hero images found in the database.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shop - Home</title>
    <!-- Import Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons (from nav.php) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f4f7f6; 
            margin: 0; 
            padding: 0; 
        }
        .container { 
            max-width: 2500px; /* Increased from 1600px */
            margin: 20px auto; 
            padding: 20px; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }
        h1 { 
            text-align: center; 
            color: #333; 
            font-family: 'Playfair Display', serif;
            font-size: 2.5em; 
            font-weight: 700; 
            letter-spacing: 1px; 
            margin-bottom: 20px; 
        }

        /* Hero Carousel Styles */
        .hero-carousel-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            margin-bottom: 20px; /* Space between carousels */
        }
        .hero-carousel {
            display: flex;
            width: 500%; /* 5 images × 100% */
            transition: transform 0s ease-in-out;
        }
        .hero-carousel-item {
            flex: 0 0 100%; /* Show 1 image at a time */
            box-sizing: border-box;
        }
        .hero-carousel-item img {
            width: auto;
            height: 750px; /* Fixed height for hero images */
            object-fit: cover; /* Crop to fit */
            border-radius: 8px;
            display: block;
        }
        .carousel-indicators {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
        }
        .carousel-indicators .dot {
            width: 12px;
            height: 12px;
            background-color: rgb(58, 58, 58);
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .carousel-indicators .dot.active {
            background-color: #ffffff;
        }

        /* Product Grid Styles */
        .product-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px; /* Space between product items */
        }

        .product-item {
            flex: 0 0 calc(25% - 15px); /* 4 items per row, accounting for gap */
            box-sizing: border-box;
            padding: 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .product-item a {
            text-decoration: none; /* Remove underline from link */
        }

        .product-item img {
            width: 380px; /* Increased from 300px */
            height: 285px; /* Increased from 225px */
            object-fit: contain;
            border-radius: 8px;
            transition: transform 0.3s ease;
            margin: 0 auto;
        }

        .product-item img:hover {
            transform: scale(1.05);
        }

        .product-item h3 {
            font-family: 'Goudy', serif;
            font-size: 1.5em; /* Increased from 1.3em */
            margin: 8px 0 5px;
            color: #333;
            line-height: 1.2;
            max-width: 350px; /* Increased from 300px */
        }

        .product-item p {
            font-size: 1.1em; /* Increased from 1em */
            color: #666;
            margin: 0;
        }

        /* Responsive Adjustments for Hero Carousel */
        @media (max-width: 768px) {
            .hero-carousel-item {
                height: 600px;
            }
        }
        @media (max-width: 480px) {
            .hero-carousel-item {
                height: 400px;
            }
            .carousel-indicators .dot {
                width: 10px;
                height: 10px;
            }
        }

        /* Responsive Adjustments for Product Grid */
        @media (max-width: 768px) {
            .product-item {
                flex: 0 0 calc(50% - 10px); /* 2 items per row */
            }
            .product-item img {
                width: 320px; /* Increased from 260px */
                height: 240px; /* Increased from 195px */
            }
            .product-item h3 {
                max-width: 320px; /* Increased from 260px */
                font-size: 1.4em; /* Increased from 1.2em */
            }
        }

        @media (max-width: 480px) {
            .product-item {
                flex: 0 0 100%; /* 1 item per row */
            }
            .product-item img {
                width: 280px; /* Increased from 220px */
                height: 210px; /* Increased from 165px */
            }
            .product-item h3 {
                max-width: 280px; /* Increased from 220px */
                font-size: 1.3em; /* Increased from 1.1em */
            }
            .product-item p {
                font-size: 1em; /* Increased from 0.9em */
            }
        }
    </style>
</head>
<body>
    <?php include("nav.php"); ?>
    <div class="hero-carousel-container">
        <div class="hero-carousel" id="heroCarousel">
            <?php foreach ($hero_images as $hero_image) { ?>
                <div class="hero-carousel-item">
                    <img src="get_image.php?id=<?php echo $hero_image['id']; ?>&type=hero" alt="<?php echo htmlspecialchars($hero_image['alt_text']); ?>">
                </div>
            <?php } ?>
        </div>
        <div class="carousel-indicators">
            <?php for ($i = 0; $i < count($hero_images); $i++) { ?>
                <span class="dot" onclick="goToHeroSlide(<?php echo $i; ?>)"></span>
            <?php } ?>
        </div>
    </div>
    <h1>Refined. Timeless. Yours, <?php echo $_SESSION["name"]; ?>.</h1>
    <div class="container">
        <!-- Product Grid -->
        <div class="product-grid">
            <?php foreach ($products as $index => $product) { ?>
                <div class="product-item">
                    <a href="product.php?id=<?php echo $product['id']; ?>">
                        <img src="get_image.php?id=<?php echo $product['id']; ?>&type=product" alt="Fragrance <?php echo $index + 1; ?>">
                    </a>
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p>$<?php echo number_format($product['price'], 2); ?></p>
                </div>
            <?php } ?>
        </div>
    </div>

    <script>
        // Hero Carousel (Auto-Swiping with Dots)
        const heroCarousel = document.getElementById('heroCarousel');
        const dots = document.querySelectorAll('.carousel-indicators .dot');
        const totalHeroSlides = <?php echo count($hero_images); ?>;
        let currentHeroSlide = 0;

        function updateHeroSlide() {
            const offset = currentHeroSlide * -100;
            heroCarousel.style.transform = `translateX(${offset}%)`;
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentHeroSlide);
            });
        }

        function goToHeroSlide(index) {
            currentHeroSlide = index;
            updateHeroSlide();
        }

        function autoSwipeHero() {
            currentHeroSlide = (currentHeroSlide + 1) % totalHeroSlides;
            updateHeroSlide();
        }

        setInterval(autoSwipeHero, 3000);
        updateHeroSlide();
    </script>
</body>
</html>