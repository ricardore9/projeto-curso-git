<?php
require_once 'config.php';
require_once 'auth.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Por favor, informe seu e-mail.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "E-mail inválido.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            // Generate Token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Delete old requests
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
            $stmt->execute(['email' => $email]);

            // Insert new request
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
            if ($stmt->execute(['email' => $email, 'token' => $token, 'expires' => $expires])) {

                // Construct Link
                // Assuming the script is in the root, we try to detect the protocol and host
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                // Get the current path but remove the filename
                $path = dirname($_SERVER['REQUEST_URI']);
                // Ensure no double slashes if path is root
                if ($path === '/' || $path === '\\') $path = '';

                $link = "$protocol://$host$path/reset_password.php?token=$token";

                // Email content
                $subject = "Recuperar Senha - Lista de Compras";
                $message = "Olá,\n\nClique no link abaixo para redefinir sua senha:\n\n$link\n\nEste link expira em 1 hora.\n\nSe você não solicitou isso, ignore este e-mail.";
                $headers = "From: no-reply@$host\r\n" .
                           "Reply-To: no-reply@$host\r\n" .
                           "X-Mailer: PHP/" . phpversion();

                if (mail($email, $subject, $message, $headers)) {
                    $success = "Um e-mail foi enviado com as instruções para redefinir sua senha.";
                } else {
                    $error = "Erro ao enviar e-mail. Tente novamente mais tarde.";
                }
            } else {
                $error = "Erro ao processar solicitação.";
            }
        } else {
            // For security, don't reveal if email doesn't exist, or say it doesn't exist if you prefer UX over security in this simple app.
            // The user prompt implied simplicity, so I'll be transparent.
            $error = "E-mail não encontrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Lista de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h3 class="text-center mb-4">Recuperar Senha</h3>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
            <p class="text-center text-muted">Informe seu e-mail cadastrado para receber o link de redefinição.</p>
            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Enviar E-mail</button>
            </form>
        <?php endif; ?>
        <p class="mt-3 text-center"><a href="login.php">Voltar para Login</a></p>
    </div>
</body>
</html>
