<?php
require_once 'config.php';

// Get user_id from query parameters
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit();
}

try {
    $query = "SELECT 
                t.*,
                ps.name as parking_space_name,
                ps.address as parking_space_address
              FROM transactions t
              JOIN parking_spaces ps ON t.parking_space_id = ps.id
              WHERE t.user_id = :user_id
              ORDER BY t.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $transactions
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>