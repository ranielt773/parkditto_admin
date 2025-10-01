<?php
include 'includes/auth.php';
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $plan = $_POST['plan'];
    $storm = $_POST['storm'];
    $state = $_POST['state'];
    
    try {
        // Update the record in the database using PDO
        $sql = "UPDATE transactions SET duration_type = ?, storm_pass = ?, status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$plan, $storm, $state, $id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>