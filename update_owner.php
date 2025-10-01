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
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate required fields
    if (empty($name) || empty($username) || empty($email)) {
        header("Location: user.php?msg=" . urlencode("All fields are required!"));
        exit();
    }

    // Split name into firstname and lastname
    $parts = explode(" ", $name, 2);
    $firstname = $conn->real_escape_string($parts[0]);
    $lastname = isset($parts[1]) ? $conn->real_escape_string($parts[1]) : "";
    $username = $conn->real_escape_string($username);
    $email = $conn->real_escape_string($email);

    // Check for duplicate username (excluding current owner)
    $check_username_sql = "SELECT id FROM parking_owners WHERE username = '$username' AND id != $id";
    $check_username_result = $conn->query($check_username_sql);
    
    if ($check_username_result->num_rows > 0) {
        header("Location: user.php?msg=" . urlencode("Error: Username already taken by another owner!"));
        exit();
    }

    // Check for duplicate email (excluding current owner)
    $check_email_sql = "SELECT id FROM parking_owners WHERE email = '$email' AND id != $id";
    $check_email_result = $conn->query($check_email_sql);
    
    if ($check_email_result->num_rows > 0) {
        header("Location: user.php?msg=" . urlencode("Error: Email already taken by another owner!"));
        exit();
    }

    // Build update query
    if (!empty($password)) {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE parking_owners 
                SET firstname = '$firstname', lastname = '$lastname', username = '$username', 
                    email = '$email', password = '$hashed_password' 
                WHERE id = $id";
    } else {
        // Keep the current password
        $sql = "UPDATE parking_owners 
                SET firstname = '$firstname', lastname = '$lastname', username = '$username', email = '$email' 
                WHERE id = $id";
    }

    if ($conn->query($sql) === TRUE) {
        if ($conn->affected_rows > 0) {
            header("Location: user.php?msg=" . urlencode("Owner updated successfully!"));
        } else {
            header("Location: user.php?msg=" . urlencode("No changes made or owner not found."));
        }
        exit();
    } else {
        header("Location: user.php?msg=" . urlencode("Error updating owner: " . $conn->error));
        exit();
    }
}

$conn->close();
?>