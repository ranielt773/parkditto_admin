<?php
include 'config.php';

// Log the raw input for debugging
$raw_input = file_get_contents("php://input");
error_log("Raw input: " . $raw_input);

$data = json_decode($raw_input);

// Check if JSON decoding was successful
if ($data === null) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Invalid JSON data"));
    exit();
}

if(isset($data->username) && isset($data->password)) {
    $username = $data->username;
    $password = $data->password;
    
    error_log("Username: " . $username);
    error_log("Password: " . $password);
    
    $query = "SELECT * FROM users WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(password_verify($password, $row['password'])) {
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Login successful",
                "user" => array(
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "email" => $row['email'],
                    "first_name" => $row['first_name'],
                    "last_name" => $row['last_name'],
                    "type" => $row['type'] // Add user type to response
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("success" => false, "message" => "Invalid password"));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "User not found"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Missing parameters"));
    
    // Log what was actually received
    error_log("Received data: " . print_r($data, true));
    if (!isset($data->username)) error_log("Username is missing");
    if (!isset($data->password)) error_log("Password is missing");
}
?>