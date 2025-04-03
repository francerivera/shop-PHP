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

// Array of hero images
$hero_images = [
    [
        'image_path' => 'images/bg4.jpg',
        'mime_type' => 'image/jpeg',
        'alt_text' => 'lv'
    ],
    [
        'image_path' => 'images/bg2.jpg',
        'mime_type' => 'image/jpeg',
        'alt_text' => 'MFK Kurky'
    ],
    [
        'image_path' => 'images/bg3.jpg',
        'mime_type' => 'image/jpeg',
        'alt_text' => 'Le labo'
    ],
    [
        'image_path' => 'images/bg5.jpg',
        'mime_type' => 'image/jpeg',
        'alt_text' => 'replica'
    ],
    [
        'image_path' => 'images/bg6.jpg',
        'mime_type' => 'image/jpeg',
        'alt_text' => 'diptyque'
    ],
    
];

// Insert each hero image into the database
foreach ($hero_images as $hero_image) {
    if (file_exists($hero_image['image_path'])) {
        $image_data = file_get_contents($hero_image['image_path']);
        if ($image_data === false) {
            echo "Failed to read image: " . $hero_image['image_path'] . "<br>";
            continue;
        }

        $query = "INSERT INTO hero_images (image, mime_type, alt_text) VALUES (?, ?, ?)";
        $stmt = $connections->prepare($query);
        if (!$stmt) {
            echo "Prepare failed for " . $hero_image['alt_text'] . ": " . $connections->error . "<br>";
            continue;
        }
        $stmt->bind_param("sss", $image_data, $hero_image['mime_type'], $hero_image['alt_text']);
        if ($stmt->execute()) {
            echo "Inserted hero image: " . $hero_image['alt_text'] . "<br>";
        } else {
            echo "Failed to insert hero image: " . $hero_image['alt_text'] . " - Error: " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "Image file not found: " . $hero_image['image_path'] . "<br>";
    }
}

echo "Done.";
?>