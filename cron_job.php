<?php
require __DIR__ . '/includes/config.php';

// Auto-update expired transactions to completed
try {
    $update_stmt = $pdo->prepare("
        UPDATE transactions 
        SET status = 'completed' 
        WHERE expiry_time <= NOW() 
        AND status = 'ongoing'
    ");
    $update_stmt->execute();
    
    $affected_rows = $update_stmt->rowCount();
    
    // Log the update
    if ($affected_rows > 0) {
        error_log("[" . date('Y-m-d H:i:s') . "] Auto-completed $affected_rows expired transactions");
    }
    
    echo json_encode(['success' => true, 'updated' => $affected_rows]);
    
} catch(PDOException $e) {
    error_log("Error updating expired transactions: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>