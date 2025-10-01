<?php
session_start();
include 'config.php';

// Check if function already exists before declaring
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('login')) {
    function login($username, $password) {
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND type IN ('staff', 'admin')");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['type'];
            return true;
        }
        return false;
    }
}

if (!function_exists('redirectIfNotLoggedIn')) {
    function redirectIfNotLoggedIn() {
        if (!isLoggedIn()) {
            header("Location: ../index.php");
            exit();
        }
    }
}

if (!function_exists('redirectIfNotAdmin')) {
    function redirectIfNotAdmin() {
        if ($_SESSION['user_type'] !== 'admin') {
            header("Location: dashboard.php");
            exit();
        }
    }
}

?>