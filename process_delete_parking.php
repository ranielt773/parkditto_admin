<?php
session_start();

$host = "localhost";
$dbname = "parkditto";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $parkingId = intval($_POST['parking_id']);
        $adminPassword = $_POST['admin_password'];

        // ✅ i-check kung current user ay admin
        $stmt = $pdo->prepare("SELECT password, type FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['type'] !== 'admin') {
            header("Location: parkingManagement.php?msg=Unauthorized action");
            exit();
        }

        // ✅ Verify password
        if (!password_verify($adminPassword, $user['password'])) {
            header("Location: parkingManagement.php?msg=Invalid admin password");
            exit();
        }

        // ✅ Delete parking space
        $delete = $pdo->prepare("DELETE FROM parking_spaces WHERE id = :id");
        $delete->execute(['id' => $parkingId]);

        header("Location: parkingManagement.php?msg=Parking space deleted successfully!");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
