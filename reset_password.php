<?php
require_once 'includes/db.php';

$token = $_GET['token'] ?? '';
$erro = '';
$sucesso = false;

if (empty($token)) {
    die("Token inválido.");
}

// Verificar token
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Link inválido ou expirado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    if ($nova_senha !== $confirma_senha) {
        $erro = "As senhas não conferem.";
    } else {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        $sucesso = true;
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow mt-5">
            <div class="card-header bg-success text-white text-center">
                <h3 class="mb-0"><i class="fas fa-key me-2"></i>Nova Senha</h3>
            </div>
            <div class="card-body p-4">

                <?php if ($sucesso): ?>
                    <div class="alert alert-success text-center">
                        <h4>Senha alterada com sucesso!</h4>
                        <p>Você já pode acessar o sistema com sua nova senha.</p>
                        <a href="login.php" class="btn btn-primary btn-lg mt-3">Ir para Login</a>
                    </div>
                <?php else: ?>

                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?php echo $erro; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fs-5">Nova Senha</label>
                            <input type="password" class="form-control form-control-lg" name="nova_senha" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fs-5">Confirmar Nova Senha</label>
                            <input type="password" class="form-control form-control-lg" name="confirma_senha" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg-custom">Salvar Nova Senha</button>
                        </div>
                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
