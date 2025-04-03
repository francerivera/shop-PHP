<?php
include("connections.php");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the database connection is successful
if (!$connections) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Array of products
$products = [
    [
        'name' => 'Diptyque Orpheon - Eau de Parfum 30ml',
        'description' => 'A sophisticated fragrance with notes of jasmine, tonka bean, and cedarwood, evoking a timeless elegance.',
        'price' => 120.00,
        'image_path' => 'images/orpheon.png',
        'mime_type' => 'image/png'
    ],
    [
        'name' => 'Le Labo Santal 33 - Eau de Parfum 50ml',
        'description' => 'A woody aromatic scent with sandalwood, cardamom, and leather notes, perfect for a bold statement.',
        'price' => 200.00,
        'image_path' => 'images/santal.png',
        'mime_type' => 'image/png'
    ],
    [
        'name' => 'Nishane Hacivat - Extrait de Parfum 15ml',
        'description' => 'A fresh and fruity fragrance with pineapple, bergamot, and oakmoss, ideal for a vibrant personality.',
        'price' => 115.00,
        'image_path' => 'images/hacivat.png',
        'mime_type' => 'image/png'
    ],
    [
        'name' => 'Acqua di Parma Fico de Amalfi - Eau de Toilette 30ml',
        'description' => 'A refreshing scent with fig, lemon, and cedarwood, capturing the essence of the Amalfi Coast.',
        'price' => 80.00,
        'image_path' => 'images/parma.png',
        'mime_type' => 'image/png'
    ],
    [
        'name' => 'Xerjoff Naxos - Eau de Parfum 100ml',
        'description' => 'A rich fragrance with tobacco, honey, and vanilla, inspired by the Sicilian landscape.',
        'price' => 250.00,
        'image_path' => 'images/naxos.png',
        'mime_type' => 'image/png'
    ],
    [
        'name' => 'MFK Kurky - Eau de Parfum 35ml',
        'description' => 'An elegant fusion of peach, raspberry, and vanilla for a bold, modern allure.',
        'price' => 180.00,
        'image_path' => 'images/kurky.png',
        'mime_type' => 'image/png'
    ],
    [
        'name' => 'SHL God of Fire - Eau de Parfum 50ml',
        'description' => 'A fiery fragrance with oud, amber, and saffron, embodying strength and passion.',
        'price' => 215.00,
        'image_path' => 'images/gof.png',
        'mime_type' => 'image/png'
    ],
    [
        'name' => 'Byredo Bal d\'Afrique - Eau de Parfum 50ml',
        'description' => 'A vibrant scent with neroli, African marigold, and vetiver, celebrating African culture.',
        'price' => 230.00,
        'image_path' => 'images/byredo.png',
        'mime_type' => 'image/png'
    ]
];

// Insert each product into the database
foreach ($products as $product) {
    if (file_exists($product['image_path'])) {
        $image_data = file_get_contents($product['image_path']);
        if ($image_data === false) {
            echo "Failed to read image: " . $product['image_path'] . "<br>";
            continue;
        }

        $query = "INSERT INTO products (name, description, price, image, mime_type) VALUES (?, ?, ?, ?, ?)";
        $stmt = $connections->prepare($query);
        if (!$stmt) {
            echo "Prepare failed for " . $product['name'] . ": " . $connections->error . "<br>";
            continue;
        }
        $stmt->bind_param("ssdss", $product['name'], $product['description'], $product['price'], $image_data, $product['mime_type']);
        if ($stmt->execute()) {
            echo "Inserted product: " . $product['name'] . "<br>";
        } else {
            echo "Failed to insert product: " . $product['name'] . " - Error: " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "Image file not found: " . $product['image_path'] . "<br>";
    }
}

echo "Done.";
?>