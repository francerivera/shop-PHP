<?php
session_start();
include("connections.php");

if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] != 1) {
    header("Location: index.php");
    exit();
}

$user_id = $_GET['id'];
$query = mysqli_query($connections, "SELECT * FROM mytbl WHERE id='$user_id'");
$user = mysqli_fetch_assoc($query);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $account_type = $_POST['account_type'];

    mysqli_query($connections, "UPDATE mytbl SET name='$name', email='$email', account_type='$account_type' WHERE id='$user_id'");
    header("Location: admin.php");
}
?>

<form method="POST" action="">
    <input type="text" name="name" value="<?php echo $user['name']; ?>">
    <input type="email" name="email" value="<?php echo $user['email']; ?>">
    <select name="account_type">
        <option value="1" <?php echo $user['account_type'] == 1 ? 'selected' : ''; ?>>Admin</option>
        <option value="2" <?php echo $user['account_type'] == 2 ? 'selected' : ''; ?>>User </option>
    </select>
    <input type="submit" value="Update">
</form>