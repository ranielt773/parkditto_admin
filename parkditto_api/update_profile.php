<?php
include 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->id) && isset($data->first_name) && isset($data->last_name)) {
    $id = $data->id;
    $first_name = $data->first_name;
    $last_name = $data->last_name;
    
    // Build query based on what fields are provided
    $query = "UPDATE users SET first_name = :first_name, last_name = :last_name";
    
    if (isset($data->display_photo)) {
        $query .= ", display_photo = :display_photo";
    }
    
    if (isset($data->id_picture)) {
        $query .= ", id_picture = :id_picture";
    }
    
    $query .= " WHERE id = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    
    if (isset($data->display_photo)) {
        $stmt->bindParam(':display_photo', $data->display_photo);
    }
    
    if (isset($data->id_picture)) {
        $stmt->bindParam(':id_picture', $data->id_picture);
    }
    
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("success" => true, "message" => "Profile updated successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Unable to update profile"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Missing parameters"));
}
?>