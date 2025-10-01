<?php
include 'includes/auth.php';

// ==== DATABASE CONNECTION ====
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "parkditto";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ==== PROCESS UPDATE ====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = strtolower(trim($_POST['role']));

    // Validate required fields
    if (empty($name) || empty($email) || empty($role)) {
        header("Location: user.php?msg=" . urlencode("All fields are required!"));
        exit();
    }

    // Split name into first_name and last_name
    $parts = explode(" ", $name, 2);
    $first_name = $conn->real_escape_string($parts[0]);
    $last_name = isset($parts[1]) ? $conn->real_escape_string($parts[1]) : "";
    $email = $conn->real_escape_string($email);
    $role = $conn->real_escape_string($role);

    // Check for duplicate email (excluding current user)
    $check_sql = "SELECT id FROM users WHERE email = '$email' AND id != $id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        header("Location: user.php?msg=" . urlencode("Error: Email already taken by another user!"));
        exit();
    }

    // Update user
    $sql = "UPDATE users 
            SET first_name = '$first_name', last_name = '$last_name', email = '$email', type = '$role' 
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        if ($conn->affected_rows > 0) {
            header("Location: user.php?msg=" . urlencode("User updated successfully!"));
        } else {
            header("Location: user.php?msg=" . urlencode("No changes made or user not found."));
        }
        exit();
    } else {
        header("Location: user.php?msg=" . urlencode("Error updating user: " . $conn->error));
        exit();
    }
}

$conn->close();
?>