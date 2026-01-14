<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($user_id) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['flash_message'] = "Usuário aprovado com sucesso.";
            $_SESSION['flash_type'] = "success";
        } elseif ($action === 'delete') {
            // Cuidado ao excluir admins
            $stmt = $pdo->prepare("SELECT tipo FROM usuarios WHERE id = ?");
            $stmt->execute([$user_id]);
            $u = $stmt->fetch();

            if ($u && $u['tipo'] !== 'admin') {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['flash_message'] = "Usuário removido.";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Não é possível remover um administrador por aqui.";
                $_SESSION['flash_type'] = "danger";
            }
        }
    }
    header("Location: usuarios.php");
    exit;
}

// Buscar usuários e seus departamentos
$sql = "SELECT u.*, GROUP_CONCAT(d.nome SEPARATOR ', ') as departamentos_nomes
        FROM usuarios u
        LEFT JOIN usuario_departamentos ud ON u.id = ud.usuario_id
        LEFT JOIN departamentos d ON ud.departamento_id = d.id
        GROUP BY u.id
        ORDER BY u.status ASC, u.nome ASC"; // Pendentes primeiro

$stmt = $pdo->query($sql);
$usuarios = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-users me-2"></i>Gerenciar Líderes e Usuários</h2>
        </div>
        <div class="col-md-4 text-end">
             <a href="dashboard.php" class="btn btn-secondary">Voltar ao Painel</a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Departamentos</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $user): ?>
                            <tr class="<?php echo $user['status'] === 'pendente' ? 'table-warning' : ''; ?>">
                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['tipo'] === 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Líder</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['departamentos_nomes'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($user['status'] === 'ativo'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($user['status'] === 'pendente'): ?>
                                        <form action="usuarios.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success" title="Aprovar"><i class="fas fa-check"></i> Aprovar</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($user['tipo'] !== 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                                        <form action="usuarios.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este usuário?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Remover"><i class="fas fa-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
