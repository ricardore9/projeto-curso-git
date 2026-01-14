<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if ($email) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(32));
            // Expira em 1 hora
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expires, $email]);

            // Simulação de envio de email
            // Em produção: mail($to, $subject, $message, $headers);

            $link = BASE_URL . "reset_password.php?token=" . $token;

            // Como não temos SMTP real, vamos exibir o link em uma mensagem flash (para debug/teste)
            $_SESSION['flash_message'] = "Link de recuperação (Simulação): <a href='$link'>$link</a>";
            $_SESSION['flash_type'] = "info";

            // Mas também vamos mostrar a mensagem "oficial"
            // $_SESSION['flash_message'] = "Se o e-mail existir, um link foi enviado.";
            // $_SESSION['flash_type'] = "success";
        } else {
            // Por segurança, não dizemos se existe ou não (mas aqui vou avisar)
            $_SESSION['flash_message'] = "E-mail não encontrado.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "E-mail inválido.";
        $_SESSION['flash_type'] = "danger";
    }
}

header("Location: forgot_password.php");
exit;
?>