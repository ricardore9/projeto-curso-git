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
    $action = $_POST['action']; // 'accept' or 'reject'
    $user_id = getCurrentUserId();

    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE list_shares SET status = 'accepted' WHERE list_id = :list_id AND user_id = :user_id");
        if ($stmt->execute(['list_id' => $list_id, 'user_id' => $user_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao aceitar lista.']);
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("DELETE FROM list_shares WHERE list_id = :list_id AND user_id = :user_id");
        if ($stmt->execute(['list_id' => $list_id, 'user_id' => $user_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao rejeitar lista.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    }
}
?>
