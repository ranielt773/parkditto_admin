<?php
require_once 'config.php';

try {
    $current_time = date('Y-m-d H:i:s');
    
    error_log("Checking expired reservations at: " . $current_time);
    
    // Find all expired reservations
    $query = "SELECT t.*, ps.occupied_slots, ps.id as parking_space_id
              FROM transactions t
              JOIN parking_spaces ps ON t.parking_space_id = ps.id
              WHERE t.expiry_time <= :current_time 
              AND t.status = 'confirmed'
              AND t.transaction_type = 'booking'";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':current_time', $current_time);
    $stmt->execute();
    
    $expired_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $processed_count = 0;
    
    error_log("Found " . count($expired_transactions) . " expired transactions");
    
    foreach ($expired_transactions as $transaction) {
        error_log("Processing transaction ID: " . $transaction['id']);
        
        // Mark as completed
        $update_query = "UPDATE transactions SET status = 'completed' WHERE id = :id";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':id', $transaction['id']);
        $update_stmt->execute();
        
        // Update available spaces
        $update_spaces_query = "UPDATE parking_spaces 
                               SET available_spaces = available_spaces + 1
                               WHERE id = :parking_space_id";
        $update_spaces_stmt = $conn->prepare($update_spaces_query);
        $update_spaces_stmt->bindParam(':parking_space_id', $transaction['parking_space_id']);
        $update_spaces_stmt->execute();
        
        // Extract slot information from lot_number (format: "Slot X")
        $lot_number = $transaction['lot_number'];
        preg_match('/Slot (\d+)/', $lot_number, $matches);
        
        if (isset($matches[1])) {
            $slot_number = (int)$matches[1];
            $vehicle_type = $transaction['vehicle_type'] ?? 'Car';
            $floor = $transaction['floor'] ?? 'Ground';
            
            error_log("Removing slot $slot_number for $vehicle_type on $floor");
            
            // Get the CURRENT occupied slots from parking space
            $get_slots_query = "SELECT occupied_slots FROM parking_spaces WHERE id = :parking_space_id";
            $get_slots_stmt = $conn->prepare($get_slots_query);
            $get_slots_stmt->bindParam(':parking_space_id', $transaction['parking_space_id']);
            $get_slots_stmt->execute();
            $current_slots_data = $get_slots_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current_slots_data && !empty($current_slots_data['occupied_slots'])) {
                $occupied_slots = json_decode($current_slots_data['occupied_slots'], true);
                
                error_log("Current occupied slots: " . print_r($occupied_slots, true));
                
                // Remove the slot from occupied slots if it exists
                if (isset($occupied_slots[$vehicle_type][$floor])) {
                    $slot_index = array_search($slot_number, $occupied_slots[$vehicle_type][$floor]);
                    if ($slot_index !== false) {
                        // Remove the slot from the array
                        array_splice($occupied_slots[$vehicle_type][$floor], $slot_index, 1);
                        
                        error_log("After removal: " . print_r($occupied_slots[$vehicle_type][$floor], true));
                        
                        // Update the occupied slots in the database
                        $update_slots_query = "UPDATE parking_spaces 
                                             SET occupied_slots = :occupied_slots 
                                             WHERE id = :parking_space_id";
                        $update_slots_stmt = $conn->prepare($update_slots_query);
                        $json_occupied_slots = json_encode($occupied_slots);
                        $update_slots_stmt->bindParam(':occupied_slots', $json_occupied_slots);
                        $update_slots_stmt->bindParam(':parking_space_id', $transaction['parking_space_id']);
                        
                        if ($update_slots_stmt->execute()) {
                            $processed_count++;
                            error_log("Successfully updated occupied slots for parking space: " . $transaction['parking_space_id']);
                        } else {
                            error_log("Failed to update occupied slots");
                        }
                    } else {
                        error_log("Slot $slot_number not found in occupied slots array");
                    }
                } else {
                    error_log("No occupied slots found for $vehicle_type on $floor");
                }
            } else {
                error_log("No occupied slots data found for parking space: " . $transaction['parking_space_id']);
            }
        } else {
            error_log("Could not extract slot number from: $lot_number");
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Expired transactions processed successfully',
        'count' => $processed_count
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>