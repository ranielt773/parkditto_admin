<?php
require_once 'config.php';

try {
    $query = "SELECT 
                ps.id, 
                ps.name, 
                ps.address, 
                ps.latitude, 
                ps.longitude, 
                ps.total_spaces, 
                ps.available_spaces,
                ps.vehicle_types,
                ps.floors,
                ps.occupied_slots,
                CONCAT(po.firstname, ' ', po.lastname) AS owner_name
              FROM parking_spaces ps
              JOIN parking_owners po ON ps.partner_id = po.id
              WHERE ps.available_spaces > 0";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $parking_spots = $stmt->fetchAll();
    
    // Convert JSON strings to arrays
    foreach ($parking_spots as &$spot) {
        $spot['vehicle_types'] = json_decode($spot['vehicle_types'], true);
        $spot['floors'] = json_decode($spot['floors'], true);
        $spot['occupied_slots'] = json_decode($spot['occupied_slots'], true);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $parking_spots
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>