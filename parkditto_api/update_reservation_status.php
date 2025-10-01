<?php
require_once 'config.php';

try {
    // Get current time
    $current_time = date('Y-m-d H:i:s');
    
    // Find reservations that should be activated (arrival time reached but still pending)
    $query = "SELECT * FROM transactions 
              WHERE transaction_type = 'reservation' 
              AND status = 1 
              AND arrival_time <= CURRENT_TIME";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $reservations_to_activate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reservations_to_activate as $reservation) {
        // Update status to ongoing
        $update_query = "UPDATE transactions SET status = 2 WHERE id = :id";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':id', $reservation['id']);
        $update_stmt->execute();
        
        // Update parking availability
        $update_availability_query = "UPDATE parking_spaces 
                                     SET available_spaces = available_spaces - 1 
                                     WHERE id = :parking_space_id";
        $update_availability_stmt = $conn->prepare($update_availability_query);
        $update_availability_stmt->bindParam(':parking_space_id', $reservation['parking_space_id']);
        $update_availability_stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Updated ' . count($reservations_to_activate) . ' reservations'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>