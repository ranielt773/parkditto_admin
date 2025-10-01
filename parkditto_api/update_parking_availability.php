<?php
require_once 'config.php';

// Get the raw POST data
$data = json_decode(file_get_contents("php://input"), true);

// Add output buffering to prevent any HTML output
ob_start();

try {
    // Validate required fields
    $required_fields = ['parking_space_id', 'vehicle_type', 'floor', 'slot_number', 'is_occupied'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit();
        }
    }
    
    // Extract values
    $parking_space_id = $data['parking_space_id'];
    $vehicle_type = $data['vehicle_type'];
    $floor = $data['floor'];
    $slot_number = $data['slot_number'];
    $is_occupied = $data['is_occupied'];
    
    // First, get the current occupied_slots data
    $query = "SELECT occupied_slots FROM parking_spaces WHERE id = :parking_space_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':parking_space_id', $parking_space_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $occupied_slots = json_decode($result['occupied_slots'], true);
    
    // Update the occupied slots data
    if ($is_occupied) {
        // Add to occupied slots
        if (!isset($occupied_slots[$vehicle_type][$floor])) {
            $occupied_slots[$vehicle_type][$floor] = [];
        }
        if (!in_array($slot_number, $occupied_slots[$vehicle_type][$floor])) {
            $occupied_slots[$vehicle_type][$floor][] = $slot_number;
        }
    } else {
        // Remove from occupied slots
        if (isset($occupied_slots[$vehicle_type][$floor])) {
            $index = array_search($slot_number, $occupied_slots[$vehicle_type][$floor]);
            if ($index !== false) {
                array_splice($occupied_slots[$vehicle_type][$floor], $index, 1);
            }
        }
    }
    
    // Update the database
    $update_query = "UPDATE parking_spaces SET occupied_slots = :occupied_slots WHERE id = :parking_space_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':occupied_slots', json_encode($occupied_slots));
    $update_stmt->bindParam(':parking_space_id', $parking_space_id);
    
    if ($update_stmt->execute()) {
        // Clean output buffer before sending JSON
        ob_end_clean();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Parking availability updated successfully'
        ]);
    } else {
        // Clean output buffer before sending JSON
        ob_end_clean();
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update parking availability'
        ]);
    }
    
} catch (PDOException $e) {
    // Clean output buffer before sending JSON
    ob_end_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Ensure no output after JSON
exit();
?>