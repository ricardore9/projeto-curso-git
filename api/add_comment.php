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
    $message = trim($_POST['message']);
    $user_id = getCurrentUserId();

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Mensagem vazia.']);
        exit;
    }

    // Verify Access (Owner or Shared Accepted)
    $stmt = $pdo->prepare("
        SELECT 1 FROM shopping_lists sl
        LEFT JOIN list_shares ls ON sl.id = ls.list_id
        WHERE sl.id = :list_id
        AND (sl.user_id = :user_id OR (ls.user_id = :user_id AND ls.status = 'accepted'))
    ");
    $stmt->execute(['list_id' => $list_id, 'user_id' => $user_id]);

    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Permissão negada']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO list_comments (list_id, user_id, message) VALUES (:list_id, :user_id, :message)");
        if ($stmt->execute(['list_id' => $list_id, 'user_id' => $user_id, 'message' => $message])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar comentário']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
