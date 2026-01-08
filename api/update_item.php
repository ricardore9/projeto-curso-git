<?php
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $is_purchased = (int)$_POST['is_purchased'];
    $price = (float)$_POST['price'];
    $user_id = getCurrentUserId();

    // Check Permissions (Verify user can edit the list containing this item)
    $stmt = $pdo->prepare("
        SELECT li.list_id FROM list_items li
        JOIN shopping_lists sl ON li.list_id = sl.id
        LEFT JOIN list_shares ls ON sl.id = ls.list_id
        WHERE li.id = :id
        AND (sl.user_id = :user_id OR (ls.user_id = :user_id AND ls.permission_level = 'edit'))
    ");
    $stmt->execute(['id' => $id, 'user_id' => $user_id]);

    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Permissão negada']);
        exit;
    }

    $list_id = $stmt->fetchColumn();

    try {
        $stmt = $pdo->prepare("UPDATE list_items SET is_purchased = :is_purchased, price_paid = :price WHERE id = :id");
        if ($stmt->execute(['is_purchased' => $is_purchased, 'price' => $price, 'id' => $id])) {

            // Calculate new total for the list
            $stmt = $pdo->prepare("SELECT SUM(price_paid) FROM list_items WHERE list_id = :list_id AND is_purchased = 1");
            $stmt->execute(['list_id' => $list_id]);
            $new_total = $stmt->fetchColumn() ?: 0;

            echo json_encode(['success' => true, 'new_total' => number_format($new_total, 2, ',', '.')]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar item']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
