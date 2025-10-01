<?php
require_once 'config.php';

// Get the raw POST data
$data = json_decode(file_get_contents("php://input"), true);

try {
    // Validate required fields
    $required_fields = ['parking_space_id', 'user_id', 'lot_number', 'transaction_type', 'amount', 'payment_method'];
    
    // For reservations, arrival_time is required
    if (isset($data['transaction_type']) && $data['transaction_type'] == 'reservation') {
        $required_fields[] = 'arrival_time';
    }
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit();
        }
    }
    
    // Generate a unique reference number
    $ref_number = substr(str_shuffle("0123456789"), 0, 13);
    
    // Extract values into variables for binding
    $parking_space_id = $data['parking_space_id'];
    $user_id = $data['user_id'];
    $lot_number = $data['lot_number'];
    $transaction_type = $data['transaction_type'];
    
    // Set arrival time based on transaction type
    if ($transaction_type == 'reservation') {
        $arrival_time = $data['arrival_time'];
    } else {
        // For regular bookings, use current time
        $arrival_time = date('Y-m-d H:i:s');
    }
    
    $departure_time = isset($data['departure_time']) ? $data['departure_time'] : null;
    $amount = $data['amount'];
    $payment_method = $data['payment_method'];
    $vehicle_type = isset($data['vehicle_type']) ? $data['vehicle_type'] : null;
    $floor = isset($data['floor']) ? $data['floor'] : null;
    $duration_type = isset($data['duration_type']) ? $data['duration_type'] : null;
    $duration_value = isset($data['duration_value']) ? $data['duration_value'] : null;
    
    // Calculate expiry time based on duration type and value
    $expiry_time = null;
    if ($duration_type && $duration_value) {
        $arrival_datetime = new DateTime($arrival_time);
        
        switch ($duration_type) {
            case 'hourly':
                $expiry_time = $arrival_datetime->modify("+{$duration_value} hours")->format('Y-m-d H:i:s');
                break;
            case 'daily':
                $expiry_time = $arrival_datetime->modify("+{$duration_value} days")->format('Y-m-d H:i:s');
                break;
            case 'weekly':
                $expiry_time = $arrival_datetime->modify("+{$duration_value} weeks")->format('Y-m-d H:i:s');
                break;
            case 'monthly':
                $expiry_time = $arrival_datetime->modify("+{$duration_value} months")->format('Y-m-d H:i:s');
                break;
            case 'yearly':
                $expiry_time = $arrival_datetime->modify("+{$duration_value} years")->format('Y-m-d H:i:s');
                break;
            default:
                // Default to 1 year if unknown duration type
                $expiry_time = $arrival_datetime->modify("+1 year")->format('Y-m-d H:i:s');
                break;
        }
    } else {
        // Default to 1 year if no duration specified
        $arrival_datetime = new DateTime($arrival_time);
        $expiry_time = $arrival_datetime->modify("+1 year")->format('Y-m-d H:i:s');
    }
    
    // Set the status based on transaction type
    if ($transaction_type == 'reservation') {
        $status = 'pending'; // Status for reservations (will be updated later)
    } else {
        $status = 'ongoing'; // Status for regular bookings
    }
    
    // Prepare the insert query
    $query = "INSERT INTO transactions 
          (parking_space_id, user_id, lot_number, transaction_type, arrival_time, 
           departure_time, amount, payment_method, ref_number, status, vehicle_type, floor, 
           duration_type, duration_value, expiry_time) 
          VALUES (:parking_space_id, :user_id, :lot_number, :transaction_type, :arrival_time, 
                  :departure_time, :amount, :payment_method, :ref_number, :status, :vehicle_type, :floor, 
                  :duration_type, :duration_value, :expiry_time)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':parking_space_id', $parking_space_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':lot_number', $lot_number);
    $stmt->bindParam(':transaction_type', $transaction_type);
    $stmt->bindParam(':arrival_time', $arrival_time);
    $stmt->bindParam(':departure_time', $departure_time);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':payment_method', $payment_method);
    $stmt->bindParam(':ref_number', $ref_number);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':vehicle_type', $vehicle_type);
    $stmt->bindParam(':floor', $floor);
    $stmt->bindParam(':duration_type', $duration_type);
    $stmt->bindParam(':duration_value', $duration_value);
    $stmt->bindParam(':expiry_time', $expiry_time);
    
    if ($stmt->execute()) {
        // Only update available spaces for immediate bookings, not reservations
        if ($transaction_type != 'reservation') {
            $update_query = "UPDATE parking_spaces SET available_spaces = available_spaces - 1 WHERE id = :parking_space_id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':parking_space_id', $parking_space_id);
            $update_stmt->execute();
        }
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Transaction created successfully',
            'ref_number' => $ref_number,
            'transaction_id' => $conn->lastInsertId(),
            'expiry_time' => $expiry_time,
            'status' => $status,
            'arrival_time' => $arrival_time
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create transaction'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>