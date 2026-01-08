<?php
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$user_id = getCurrentUserId();

// Return all users except current one
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != :user_id ORDER BY username ASC");
$stmt->execute(['user_id' => $user_id]);
echo json_encode($stmt->fetchAll());
?>
