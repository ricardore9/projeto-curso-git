<?php
require_once 'config.php';
require_once 'auth.php';

$error = '';
$success = '';
$valid_token = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if token exists and is valid
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW()");
    $stmt->execute(['token' => $token]);

    if ($stmt->rowCount() > 0) {
        $valid_token = true;
        $email = $stmt->fetchColumn();
    } else {
        $error = "Link inválido ou expirado.";
    }
} else {
    $error = "Token não fornecido.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password != $confirm_password) {
        $error = "As senhas não coincidem.";
    } else {
        // Update Password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
        if ($stmt->execute(['password' => $hashed_password, 'email' => $email])) {
            $success = "Senha redefinida com sucesso! <a href='login.php'>Faça login aqui</a>.";

            // Delete token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $valid_token = false; // Hide form
        } else {
            $error = "Erro ao atualizar senha.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Lista de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h3 class="text-center mb-4">Redefinir Senha</h3>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if($valid_token): ?>
            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label">Nova Senha</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar Nova Senha</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Salvar Senha</button>
            </form>
        <?php elseif(empty($success)): ?>
            <p class="text-center"><a href="forgot_password.php">Solicitar novo link</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
