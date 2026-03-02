<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin();

$user_id = getCurrentUserId();
$success = '';
$error = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT username, email, password FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password first for any change
    if (!password_verify($current_password, $user['password'])) {
        $error = "Senha atual incorreta.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Update Email if changed
            if ($email != $user['email']) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("E-mail inválido.");
                }

                // Check uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $stmt->execute(['email' => $email, 'id' => $user_id]);
                if ($stmt->rowCount() > 0) {
                    throw new Exception("Este e-mail já está em uso.");
                }

                $stmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :id");
                $stmt->execute(['email' => $email, 'id' => $user_id]);
                $user['email'] = $email; // Update local variable
            }

            // 2. Update Password if provided
            if (!empty($new_password)) {
                if ($new_password != $confirm_password) {
                    throw new Exception("As novas senhas não coincidem.");
                }

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute(['password' => $hashed_password, 'id' => $user_id]);

                // Update local variable hash just in case needed later
                $user['password'] = $hashed_password;
            }

            $pdo->commit();
            $success = "Perfil atualizado com sucesso!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Lista de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Lista de Materiais</a>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">Voltar</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-user-cog"></i> Editar Perfil</h4>
                    </div>
                    <div class="card-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">Usuário</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <div class="form-text">O nome de usuário não pode ser alterado.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3">Alterar Senha (Opcional)</h5>

                            <div class="mb-3">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Deixe em branco para manter a atual">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Senha Atual (Obrigatório para salvar)</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Salvar Alterações</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
