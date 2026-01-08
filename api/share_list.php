<?php
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $list_id = $_POST['list_id'];
    $target_user_id = $_POST['user_id'];
    $permission = $_POST['permission']; // 'view' or 'edit'
    $user_id = getCurrentUserId();

    // Verify Ownership
    $stmt = $pdo->prepare("SELECT 1 FROM shopping_lists WHERE id = :list_id AND user_id = :user_id");
    $stmt->execute(['list_id' => $list_id, 'user_id' => $user_id]);

    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Você não é dono desta lista']);
        exit;
    }

    try {
        // Insert or Update Share
        $stmt = $pdo->prepare("
            INSERT INTO list_shares (list_id, user_id, permission_level)
            VALUES (:list_id, :target_user_id, :permission)
            ON DUPLICATE KEY UPDATE permission_level = :permission
        ");

        if ($stmt->execute(['list_id' => $list_id, 'target_user_id' => $target_user_id, 'permission' => $permission])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao compartilhar']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
