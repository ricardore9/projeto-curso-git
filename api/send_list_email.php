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
    $target_email = trim($_POST['target_email']);
    $user_id = getCurrentUserId();

    if (!empty($target_email) && !filter_var($target_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'E-mail inválido.']);
        exit;
    }

    // Verify Access
    $stmt = $pdo->prepare("
        SELECT sl.*, u.email as user_email FROM shopping_lists sl
        JOIN users u ON u.id = :user_id
        LEFT JOIN list_shares ls ON sl.id = ls.list_id
        WHERE sl.id = :list_id AND (sl.user_id = :user_id OR ls.user_id = :user_id)
    ");
    $stmt->execute(['list_id' => $list_id, 'user_id' => $user_id]);
    $list_info = $stmt->fetch();

    if (!$list_info) {
        echo json_encode(['success' => false, 'message' => 'Lista não encontrada ou permissão negada.']);
        exit;
    }

    // Use provided email or fallback to user's registered email
    $email = !empty($target_email) ? $target_email : $list_info['user_email'];
    $list_title = htmlspecialchars($list_info['title']);

    // Fetch Items
    $stmt = $pdo->prepare("SELECT * FROM list_items WHERE list_id = :list_id ORDER BY created_at ASC");
    $stmt->execute(['list_id' => $list_id]);
    $items = $stmt->fetchAll();

    // Build Email HTML
    $html = "<h2>$list_title</h2>";
    $html .= "<p>" . htmlspecialchars($list_info['description']) . "</p>";
    $html .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    $html .= "<thead><tr><th>Item</th><th>Qtd</th><th>Status</th><th>Preço</th></tr></thead><tbody>";

    $total = 0;
    foreach ($items as $item) {
        $status = $item['is_purchased'] ? "Comprado" : "Pendente";
        $price = $item['is_purchased'] ? "R$ " . number_format($item['price_paid'], 2, ',', '.') : "-";
        $total += ($item['is_purchased'] ? $item['price_paid'] : 0);

        $html .= "<tr>";
        $html .= "<td>" . htmlspecialchars($item['item_name']) . "</td>";
        $html .= "<td>" . $item['quantity'] . "</td>";
        $html .= "<td>$status</td>";
        $html .= "<td>$price</td>";
        $html .= "</tr>";
    }

    $html .= "</tbody><tfoot><tr><th colspan='3' align='right'>Total Pago</th><th>R$ " . number_format($total, 2, ',', '.') . "</th></tr></tfoot>";
    $html .= "</table>";

    // Headers
    $host = $_SERVER['HTTP_HOST'];
    $subject = "Sua Lista de Compras: $list_title";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@$host" . "\r\n";

    if (mail($email, $subject, $html, $headers)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar e-mail.']);
    }
}
?>
