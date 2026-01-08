<?php
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $user_id = getCurrentUserId();

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Título é obrigatório']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO shopping_lists (user_id, title, description) VALUES (:user_id, :title, :description)");
        if ($stmt->execute(['user_id' => $user_id, 'title' => $title, 'description' => $description])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar lista']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
