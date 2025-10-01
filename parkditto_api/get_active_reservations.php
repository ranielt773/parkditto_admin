<?php
require_once 'config.php';

$parking_space_id = isset($_GET['parking_space_id']) ? $_GET['parking_space_id'] : null;

if (!$parking_space_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parking space ID is required'
    ]);
    exit();
}

try {
    $current_time = date('Y-m-d H:i:s');
    
    $query = "SELECT * FROM transactions 
              WHERE parking_space_id = :parking_space_id 
              AND expiry_time > :current_time 
              AND status in (1,2)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':parking_space_id', $parking_space_id);
    $stmt->bindParam(':current_time', $current_time);
    $stmt->execute();
    
    $reservations = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $reservations
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>