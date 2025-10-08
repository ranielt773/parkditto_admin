<?php
include "includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$transaction_id = $_POST['transaction_id'] ?? null;
$parking_id = $_POST['parking_id'] ?? null;
$plan = $_POST['plan'] ?? null;
$status = $_POST['status'] ?? null;
$storm_pass = $_POST['storm_pass'] ?? null;
$expiry_time = $_POST['expiry_time'] ?? null;

if (!$transaction_id) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
    exit;
}

try {
    // Update the transaction
    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET duration_type = ?, status = ?, storm_pass = ?, expiry_time = ?
        WHERE id = ?
    ");
    
    $success = $stmt->execute([
        $plan,
        $status,
        $storm_pass,
        $expiry_time ? date('Y-m-d H:i:s', strtotime($expiry_time)) : null,
        $transaction_id
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update transaction']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>