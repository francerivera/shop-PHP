<?php
include("connections.php");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if ID and type are provided
if (!isset($_GET["id"]) || !is_numeric($_GET["id"]) || !isset($_GET["type"])) {
    header("HTTP/1.0 404 Not Found");
    exit();
}

$image_id = (int)$_GET["id"];
$type = $_GET["type"];

// Determine which table to query based on type
if ($type === "hero") {
    $table = "hero_images";
} elseif ($type === "product") {
    $table = "products";
} else {
    header("HTTP/1.0 404 Not Found");
    exit();
}

// Fetch the image data and MIME type from the database
$query = "SELECT image, mime_type FROM $table WHERE id = ?";
$stmt = $connections->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $connections->error);
}
$stmt->bind_param("i", $image_id);
$stmt->execute();
$result = $stmt->get_result();
$image = $result->fetch_assoc();
$stmt->close();

if ($image && $image["image"]) {
    // Output the image with the correct content type
    header("Content-Type: " . $image["mime_type"]);
    echo $image["image"];
} else {
    header("HTTP/1.0 404 Not Found");
    exit();
}
?>