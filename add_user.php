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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first_name = $conn->real_escape_string($_POST['first_name']);
  $last_name = $conn->real_escape_string($_POST['last_name']);
  $username = $conn->real_escape_string($_POST['username']);
  $password = $_POST['password']; // Don't escape password before hashing
  $email = $conn->real_escape_string($_POST['email']);
  $role = $conn->real_escape_string($_POST['role']);

  // Hash the password
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  // Check for duplicate username and email
  $check_sql = "";
  if ($role == 'owner') {
    $check_sql = "SELECT * FROM parking_owners WHERE username = '$username' OR email = '$email'";
  } else {
    $check_sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
  }

  $result = $conn->query($check_sql);
  
  if ($result->num_rows > 0) {
    // Check which field is duplicate
    $duplicate_fields = [];
    while($row = $result->fetch_assoc()) {
      if ($row['username'] == $username) {
        $duplicate_fields[] = "username";
      }
      if ($row['email'] == $email) {
        $duplicate_fields[] = "email";
      }
    }
    
    $msg = "Error: " . implode(" and ", $duplicate_fields) . " already taken!";
    header("Location: user.php?msg=" . urlencode($msg) . "&msg_type=error");
    exit();
  }

  // If no duplicates, proceed with insertion
  if ($role == 'owner') {
    $sql = "INSERT INTO parking_owners (firstname, lastname, username, email, password) 
          VALUES ('$first_name', '$last_name', '$username', '$email', '$hashed_password')";

    if ($conn->query($sql) === TRUE) {
      header("Location: user.php?msg=Owner added successfully!&msg_type=success");
      exit();
    } else {
      header("Location: user.php?msg=Error adding user: " . $conn->error . "&msg_type=error");
      exit();
    }
  } else {
    $sql = "INSERT INTO users (first_name, last_name, username, email, password, type) 
          VALUES ('$first_name', '$last_name', '$username', '$email', '$hashed_password', '$role')";

    if ($conn->query($sql) === TRUE) {
      header("Location: user.php?msg=User added successfully!&msg_type=success");
      exit();
    } else {
      header("Location: user.php?msg=Error adding user: " . $conn->error . "&msg_type=error");
      exit();
    }
  }
}
?>