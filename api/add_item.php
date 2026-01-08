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
    $item_name = trim($_POST['item_name']);
    $quantity = (int)$_POST['quantity'];
    $user_id = getCurrentUserId();

    // Check Permissions
    $stmt = $pdo->prepare("
        SELECT 1 FROM shopping_lists sl
        LEFT JOIN list_shares ls ON sl.id = ls.list_id
        WHERE sl.id = :list_id
        AND (sl.user_id = :user_id OR (ls.user_id = :user_id AND ls.permission_level = 'edit'))
    ");
    $stmt->execute(['list_id' => $list_id, 'user_id' => $user_id]);

    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Permissão negada']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO list_items (list_id, item_name, quantity) VALUES (:list_id, :item_name, :quantity)");
        if ($stmt->execute(['list_id' => $list_id, 'item_name' => $item_name, 'quantity' => $quantity])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar item']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
