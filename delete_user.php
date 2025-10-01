<?php
include 'includes/auth.php';

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "parkditto";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Require an id param
if (!isset($_GET['id'])) {
    header("Location: user.php?msg=" . urlencode("No user specified."));
    exit();
}

$id = intval($_GET['id']);

// Prepare and execute delete
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
if (!$stmt) {
    header("Location: user.php?msg=" . urlencode("Prepare failed: " . $conn->error));
    exit();
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        header("Location: user.php?msg=" . urlencode("User deleted successfully!"));
        exit();
    } else {
        // id existed but 0 rows affected (not found)
        header("Location: user.php?msg=" . urlencode("User not found or already deleted."));
        exit();
    }
} else {
    header("Location: user.php?msg=" . urlencode("Delete failed: " . $stmt->error));
    exit();
}

// close resources (won't be reached because of exits above, but good to have)
$stmt->close();
$conn->close();
?>
