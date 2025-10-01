<?php
// register.php - Updated version with user type
include 'config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"));

// Log received data for debugging
error_log("Received registration data: " . print_r($data, true));

if(isset($data->username) && isset($data->email) && isset($data->password)) {
    $username = trim($data->username);
    $email = trim($data->email);
    $password = $data->password;
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Invalid email format"));
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Password must be at least 6 characters"));
        exit();
    }
    
    // Check if user already exists
    $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':username', $username);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(array("success" => false, "message" => "Username or email already exists"));
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Set default user type to 'user'
        $user_type = 'user';
        
        $query = "INSERT INTO users (username, email, password, type) VALUES (:username, :email, :password, :type)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':type', $user_type);
        
        if($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("success" => true, "message" => "User registered successfully"));
        } else {
            // Get PDO error info
            $errorInfo = $stmt->errorInfo();
            error_log("Database error: " . print_r($errorInfo, true));
            
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Database error: " . $errorInfo[2]));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Missing parameters. Required: username, email, password"));
}
?>