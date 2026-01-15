<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/email.php';

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

            $link = BASE_URL . "reset_password.php?token=" . $token;
            $msg = "<h3>Recuperação de Senha</h3>";
            $msg .= "<p>Clique no link abaixo para redefinir sua senha:</p>";
            $msg .= "<p><a href='$link'>$link</a></p>";
            $msg .= "<p>O link expira em 1 hora.</p>";

            if (send_email($email, "Recuperar Senha - Agenda CESE", $msg)) {
                $_SESSION['flash_message'] = "Um link foi enviado para seu e-mail.";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Erro ao enviar e-mail. Verifique se o SMTP está configurado em email_config.php.";
                $_SESSION['flash_type'] = "danger";
                // Em dev, mostrar link
                if (defined('DB_HOST') && DB_HOST === 'localhost') {
                     $_SESSION['flash_message'] .= " (DEV LINK: <a href='$link'>$link</a>)";
                }
            }
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