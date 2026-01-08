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
    $notes = $_POST['notes'];
    $user_id = getCurrentUserId();

    // Verify Access (Owner or Edit Permission)
    $stmt = $pdo->prepare("
        SELECT 1 FROM shopping_lists sl
        LEFT JOIN list_shares ls ON sl.id = ls.list_id
        WHERE sl.id = :list_id
        AND (sl.user_id = :user_id OR (ls.user_id = :user_id AND ls.permission_level = 'edit' AND ls.status = 'accepted'))
    ");
    $stmt->execute(['list_id' => $list_id, 'user_id' => $user_id]);

    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Permissão negada']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE shopping_lists SET notes = :notes WHERE id = :list_id");
        if ($stmt->execute(['notes' => $notes, 'list_id' => $list_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar observações']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
