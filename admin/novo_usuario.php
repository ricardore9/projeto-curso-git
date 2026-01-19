<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Buscar departamentos
try {
    $stmt = $pdo->query("SELECT id, nome FROM departamentos ORDER BY nome ASC");
    $departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $departamentos = [];
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo'];
    $depts = $_POST['departamentos'] ?? [];

    if (empty($nome) || empty($username) || empty($email) || empty($senha)) {
        $erro = "Todos os campos são obrigatórios.";
    } elseif ($tipo === 'lider' && empty($depts)) {
        $erro = "Um líder deve ter pelo menos um departamento.";
    } else {
        try {
            // Verificar duplicidade
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $erro = "Usuário ou E-mail já existe.";
            } else {
                $pdo->beginTransaction();

                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, username, email, senha, tipo, status) VALUES (?, ?, ?, ?, ?, 'ativo')");
                $stmt->execute([$nome, $username, $email, $hash, $tipo]);
                $user_id = $pdo->lastInsertId();

                if (!empty($depts)) {
                    $stmt_d = $pdo->prepare("INSERT INTO usuario_departamentos (usuario_id, departamento_id) VALUES (?, ?)");
                    foreach ($depts as $did) {
                        $stmt_d->execute([$user_id, $did]);
                    }
                }

                $pdo->commit();
                $_SESSION['flash_message'] = "Usuário criado com sucesso!";
                $_SESSION['flash_type'] = "success";
                header("Location: usuarios.php");
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao criar: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Novo Usuário</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger"><?php echo $erro; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" name="nome" required value="<?php echo $_POST['nome'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome de Usuário (Login)</label>
                                <input type="text" class="form-control" name="username" required value="<?php echo $_POST['username'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" required value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha Inicial</label>
                                <input type="password" class="form-control" name="senha" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Acesso</label>
                                <select class="form-select" name="tipo" id="tipo" onchange="toggleDepts()">
                                    <option value="lider">Líder</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3" id="div-depts">
                            <label class="form-label">Departamentos</label>
                            <div class="card card-body bg-light" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($departamentos as $d): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="departamentos[]" value="<?php echo $d['id']; ?>" id="d_<?php echo $d['id']; ?>">
                                        <label class="form-check-label" for="d_<?php echo $d['id']; ?>">
                                            <?php echo htmlspecialchars($d['nome']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Criar Usuário</button>
                            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDepts() {
    const tipo = document.getElementById('tipo').value;
    const div = document.getElementById('div-depts');
    if (tipo === 'admin') {
        div.style.display = 'none';
    } else {
        div.style.display = 'block';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
