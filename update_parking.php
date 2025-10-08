<?php
// update_parking.php
include 'includes/config.php';

// Define upload directory
$upload_dir = __DIR__ . '/uploads/parking_spaces/';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parking_id'])) {
    try {
        $parking_id = $_POST['parking_id'];
        $address = $_POST['address'] ?? '';
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        
        // Validate required fields
        if (empty($address) || empty($latitude) || empty($longitude)) {
            throw new Exception("All required fields must be filled.");
        }
        
        // Get existing parking details first
        $stmt = $pdo->prepare("SELECT * FROM parking_spaces WHERE id = ?");
        $stmt->execute([$parking_id]);
        $existing_parking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing_parking) {
            throw new Exception("Parking space not found");
        }
        
        // Decode existing data
        $existing_vehicle_types = json_decode($existing_parking['vehicle_types'], true);
        $existing_floors = json_decode($existing_parking['floors'], true);
        $existing_floor_capacity = json_decode($existing_parking['floor_capacity'], true);
        $existing_available_per_floor = json_decode($existing_parking['available_per_floor'], true);
        
        // Process additional capacity
        $additional_capacity = [];
        $total_additional_spaces = 0;
        
        $floor_configs = [
            'ground' => 'Ground',
            'second' => '2nd Floor', 
            'third' => '3rd Floor'
        ];
        
        foreach ($floor_configs as $floor_key => $floor_name) {
            // Check if this floor exists in the parking space and checkbox is checked
            if (in_array($floor_name, $existing_floors) && isset($_POST["{$floor_key}_floor"])) {
                $car_capacity = intval($_POST["{$floor_key}_car"] ?? 0);
                $motorcycle_capacity = intval($_POST["{$floor_key}_motorcycle"] ?? 0);
                $mini_truck_capacity = intval($_POST["{$floor_key}_mini_truck"] ?? 0);
                
                // Only add if at least one capacity value is greater than 0
                if ($car_capacity > 0 || $motorcycle_capacity > 0 || $mini_truck_capacity > 0) {
                    $additional_capacity[$floor_name] = [
                        "Car" => $car_capacity,
                        "Motorcycle" => $motorcycle_capacity,
                        "Mini Truck" => $mini_truck_capacity
                    ];
                    
                    $total_additional_spaces += $car_capacity + $motorcycle_capacity + $mini_truck_capacity;
                }
            }
        }
        
        // Initialize updated arrays with existing data
        $updated_floor_capacity = $existing_floor_capacity;
        $updated_available_per_floor = $existing_available_per_floor;
        $updated_vehicle_types = $existing_vehicle_types;
        
        // Update if there's actual additional capacity
        if (!empty($additional_capacity)) {
            foreach ($additional_capacity as $floor_name => $capacities) {
                foreach ($capacities as $vehicle_type => $additional_capacity_value) {
                    if ($additional_capacity_value > 0) {
                        // Update floor capacity
                        $updated_floor_capacity[$floor_name][$vehicle_type] = 
                            ($updated_floor_capacity[$floor_name][$vehicle_type] ?? 0) + $additional_capacity_value;
                        
                        // Update available per floor
                        $updated_available_per_floor[$floor_name][$vehicle_type] = 
                            ($updated_available_per_floor[$floor_name][$vehicle_type] ?? 0) + $additional_capacity_value;
                        
                        // Update vehicle types total
                        $updated_vehicle_types[$vehicle_type]['total'] += $additional_capacity_value;
                        $updated_vehicle_types[$vehicle_type]['available'] += $additional_capacity_value;
                    }
                }
            }
            
            // Calculate new totals only if additional capacity was added
            $new_total_spaces = $existing_parking['total_spaces'] + $total_additional_spaces;
            $new_available_spaces = $existing_parking['available_spaces'] + $total_additional_spaces;
        } else {
            // No additional capacity added, keep existing totals
            $new_total_spaces = $existing_parking['total_spaces'];
            $new_available_spaces = $existing_parking['available_spaces'];
        }
        
        // Handle image upload
        $image_url = $existing_parking['image_url'];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_file = $_FILES['image'];
            $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            // Validate file type
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                // Generate unique filename
                $filename = 'parking_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($image_file['tmp_name'], $target_path)) {
                    // Delete old image if it's not the default
                    if ($image_url !== 'uploads/parking_spaces/default_parking.jpg' && file_exists(__DIR__ . '/' . $image_url)) {
                        unlink(__DIR__ . '/' . $image_url);
                    }
                    $image_url = 'uploads/parking_spaces/' . $filename;
                } else {
                    throw new Exception("Failed to upload image file.");
                }
            } else {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.");
            }
        }
        
        // Update database
        $sql = "UPDATE parking_spaces 
                SET address = ?, latitude = ?, longitude = ?, 
                    total_spaces = ?, available_spaces = ?,
                    vehicle_types = ?, floor_capacity = ?, available_per_floor = ?,
                    image_url = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $address, $latitude, $longitude,
            $new_total_spaces, $new_available_spaces,
            json_encode($updated_vehicle_types), 
            json_encode($updated_floor_capacity), 
            json_encode($updated_available_per_floor),
            $image_url,
            $parking_id
        ]);
        
        // Redirect back to parking management with success message
        $_SESSION['success_message'] = "Parking space updated successfully!";
        header("Location: parkingManagement.php?parking_id=" . $parking_id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: parkingManagement.php?parking_id=" . ($_POST['parking_id'] ?? ''));
        exit;
    }
} else {
    header("Location: parkingManagement.php");
    exit;
}
?>