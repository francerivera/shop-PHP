<?php
include("connections.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price = (float)$_POST["price"];

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";
    if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != 0) {
        $errors[] = "Image is required.";
    }

    if (empty($errors)) {
        // Read the uploaded image file
        $image_data = file_get_contents($_FILES["image"]["tmp_name"]);

        // Insert into the database
        $query = "INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)";
        $stmt = $connections->prepare($query);
        $stmt->bind_param("ssds", $name, $description, $price, $image_data);
        if ($stmt->execute()) {
            $success = "Product added successfully!";
        } else {
            $errors[] = "Failed to add product. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            height: 100px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Product</h2>

        <?php if (isset($errors) && !empty($errors)) { ?>
            <div class="error">
                <?php foreach ($errors as $error) echo $error . "<br>"; ?>
            </div>
        <?php } ?>
        <?php if (isset($success)) { ?>
            <div class="success"><?php echo $success; ?></div>
        <?php } ?>

        <form method="POST" action="add_product.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>
            <button type="submit">Add Product</button>
        </form>
    </div>
</body>
</html>