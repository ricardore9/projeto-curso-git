<?php
// Habilitar exibição de erros temporariamente para debug (se necessário, remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar dados atuais do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Erro ao carregar perfil: " . $e->getMessage());
}

// Buscar departamentos (apenas se for líder para editar)
$departamentos = [];
$meus_depts = [];
if ($user['tipo'] === 'lider') {
    try {
        $stmt = $pdo->query("SELECT id, nome FROM departamentos ORDER BY nome ASC");
        $departamentos = $stmt->fetchAll();

        $stmt_meus = $pdo->prepare("SELECT departamento_id FROM usuario_departamentos WHERE usuario_id = ?");
        $stmt_meus->execute([$_SESSION['user_id']]);
        $meus_depts = $stmt_meus->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Erro silencioso ou log
    }
}

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $depts_selecionados = $_POST['departamentos'] ?? [];

    if (empty($nome) || empty($username) || empty($email) || empty($senha_atual)) {
        $erro = "Nome, Usuário, Email e Senha Atual são obrigatórios.";
    } elseif (!password_verify($senha_atual, $user['senha'])) {
        $erro = "Senha atual incorreta.";
    } else {
        try {
            // Verificar unicidade (excluindo o próprio usuário)
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE (email = ? OR username = ?) AND id != ?");
            $stmt->execute([$email, $username, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $erro = "Usuário ou E-mail já está em uso por outra pessoa.";
            } else {
                $pdo->beginTransaction();

                // Construção da Query
                $sql = "UPDATE usuarios SET nome = ?, username = ?, email = ?";
                $params = [$nome, $username, $email];

                if (!empty($nova_senha)) {
                    $sql .= ", senha = ?";
                    $params[] = password_hash($nova_senha, PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id = ?";
                $params[] = $_SESSION['user_id'];

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // Atualizar Departamentos (se for líder)
                if ($user['tipo'] === 'lider') {
                    // Remover todos
                    $pdo->prepare("DELETE FROM usuario_departamentos WHERE usuario_id = ?")->execute([$_SESSION['user_id']]);

                    // Inserir selecionados
                    if (!empty($depts_selecionados)) {
                        $stmt_d = $pdo->prepare("INSERT INTO usuario_departamentos (usuario_id, departamento_id) VALUES (?, ?)");
                        foreach ($depts_selecionados as $did) {
                            $stmt_d->execute([$_SESSION['user_id'], $did]);
                        }
                    }
                }

                $pdo->commit();

                // Atualizar sessão
                $_SESSION['user_name'] = $nome;

                $_SESSION['flash_message'] = "Perfil atualizado com sucesso!";
                $_SESSION['flash_type'] = "success";

                // Redirecionar para recarregar dados
                header("Location: profile.php");
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0"><i class="fas fa-user-edit me-2"></i>Editar Meu Perfil</h3>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
                    <?php endif; ?>

                    <form method="POST">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome de Usuário</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <?php if ($user['tipo'] === 'lider'): ?>
                            <div class="mb-3">
                                <label class="form-label">Meus Departamentos</label>
                                <div class="card card-body bg-light" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($departamentos as $d): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="departamentos[]" value="<?php echo $d['id']; ?>" id="pd_<?php echo $d['id']; ?>"
                                                <?php echo in_array($d['id'], $meus_depts) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pd_<?php echo $d['id']; ?>">
                                                <?php echo htmlspecialchars($d['nome']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-text">Você pode alterar os departamentos que lidera.</div>
                            </div>
                        <?php endif; ?>

                        <hr>
                        <h5 class="mb-3 text-secondary">Segurança</h5>

                        <div class="mb-3">
                            <label class="form-label">Nova Senha (deixe em branco para manter a atual)</label>
                            <input type="password" class="form-control" name="nova_senha" autocomplete="new-password">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-danger">Senha Atual (Obrigatório para salvar alterações)</label>
                            <input type="password" class="form-control border-danger" name="senha_atual" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Salvar Alterações</button>
                            <a href="<?php echo $user['tipo'] === 'admin' ? 'admin/dashboard.php' : 'leader/dashboard.php'; ?>" class="btn btn-secondary">Cancelar</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
