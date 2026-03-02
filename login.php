<?php
require_once 'config.php';
require_once 'auth.php';

$error = '';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['username']); // Can be username or email
    $password = $_POST['password'];

    if (empty($login_input) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        // Prepare statement to check both username and email
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :input OR email = :input");
        $stmt->execute(['input' => $login_input]);

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch();
            if (password_verify($password, $row['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Senha inválida.";
            }
        } else {
            $error = "Usuário ou e-mail não encontrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lista de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h3 class="text-center mb-4">Login</h3>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label">Usuário ou E-mail</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Senha</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
        <div class="text-center mt-3">
            <p class="mb-1"><a href="forgot_password.php">Esqueci minha senha</a></p>
            <p>Não tem conta? <a href="register.php">Cadastre-se</a></p>
        </div>
    </div>
</body>
</html>
