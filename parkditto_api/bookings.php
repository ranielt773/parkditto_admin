<?php
require 'config.php';

// Get user_id from query parameter if available
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// SQL query to get booking data from transactions and parking_spaces tables
$sql = "SELECT 
            t.id,
            t.status,
            ps.name AS title,
            ps.address AS location,
            t.floor,
            CONCAT('Slot ', t.lot_number) AS slot,
            '' AS image_url,  -- Placeholder for image URL
            CONCAT(
                DATE_FORMAT(t.arrival_time, '%b %e %Y'), 
                ' - ', 
                DATE_FORMAT(t.expiry_time, '%b %e %Y')
            ) AS formatted_date,
            CONCAT(
                TIMESTAMPDIFF(DAY, NOW(), t.expiry_time), ' days ',
                MOD(TIMESTAMPDIFF(HOUR, NOW(), t.expiry_time), 24), ' hours ',
                MOD(TIMESTAMPDIFF(MINUTE, NOW(), t.expiry_time), 60), ' mins ',
                MOD(TIMESTAMPDIFF(SECOND, NOW(), t.expiry_time), 60), ' secs'
            ) AS remaining_time
        FROM transactions t
        INNER JOIN parking_spaces ps ON t.parking_space_id = ps.id";

// Add user filter if user_id is provided
if ($user_id > 0) {
    $sql .= " AND t.user_id = :user_id";
}

$stmt = $conn->prepare($sql);

if ($user_id > 0) {
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
}

$stmt->execute();

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Ensure image_url is never NULL
foreach ($bookings as &$booking) {
    if (empty($booking['image_url'])) {
        $booking['image_url'] = 'assets/placeholder.png'; // fallback asset
    }
}

echo json_encode($bookings, JSON_PRETTY_PRINT);