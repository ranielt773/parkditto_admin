<?php
include "includes/config.php";

header('Content-Type: application/json');

if (!isset($_GET['transaction_id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
    exit;
}

$transaction_id = intval($_GET['transaction_id']);

try {
    $stmt = $pdo->prepare("
        SELECT expiry_time, storm_pass, duration_type, status 
        FROM transactions 
        WHERE id = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction) {
        echo json_encode([
            'success' => true,
            'expiry_time' => $transaction['expiry_time'],
            'storm_pass' => $transaction['storm_pass'],
            'duration_type' => $transaction['duration_type'],
            'status' => $transaction['status']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>