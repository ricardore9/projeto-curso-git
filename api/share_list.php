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
    $target_identifier = trim($_POST['target_user']); // Username or Email
    $permission = $_POST['permission']; // 'view' or 'edit'
    $user_id = getCurrentUserId();

    if (empty($target_identifier)) {
        echo json_encode(['success' => false, 'message' => 'Informe o usuário ou e-mail.']);
        exit;
    }

    // Verify Ownership
    $stmt = $pdo->prepare("SELECT 1 FROM shopping_lists WHERE id = :list_id AND user_id = :user_id");
    $stmt->execute(['list_id' => $list_id, 'user_id' => $user_id]);

    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Você não é dono desta lista']);
        exit;
    }

    // Find Target User
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :ident OR email = :ident");
    $stmt->execute(['ident' => $target_identifier]);
    $target_user = $stmt->fetch();

    if (!$target_user) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
        exit;
    }

    $target_user_id = $target_user['id'];

    if ($target_user_id == $user_id) {
        echo json_encode(['success' => false, 'message' => 'Você não pode compartilhar consigo mesmo.']);
        exit;
    }

    try {
        // Insert or Update Share (Default status is pending for new inserts)
        // If updating an existing share, we keep the current status (unless we want to reset it?)
        // Let's assume re-sharing might update permission but keep status if already accepted.

        $stmt = $pdo->prepare("
            INSERT INTO list_shares (list_id, user_id, permission_level, status)
            VALUES (:list_id, :target_user_id, :permission, 'pending')
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
