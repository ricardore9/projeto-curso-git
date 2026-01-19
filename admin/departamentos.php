<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nome = trim($_POST['nome']);
        if (!empty($nome)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO departamentos (nome) VALUES (?)");
                $stmt->execute([$nome]);
                $_SESSION['flash_message'] = "Departamento adicionado!";
                $_SESSION['flash_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = "Erro ao adicionar (pode já existir).";
                $_SESSION['flash_type'] = "danger";
            }
        }
    } elseif ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM departamentos WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash_message'] = "Departamento removido.";
                $_SESSION['flash_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = "Erro ao remover (pode estar em uso).";
                $_SESSION['flash_type'] = "danger";
            }
        }
    }
    header("Location: departamentos.php");
    exit;
}

// Buscar departamentos
try {
    $stmt = $pdo->query("SELECT * FROM departamentos ORDER BY nome ASC");
    $departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erro ao carregar departamentos.";
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-layer-group me-2"></i>Gerenciar Departamentos</h2>
        </div>
        <div class="col-md-4 text-end">
             <a href="dashboard.php" class="btn btn-secondary">Voltar ao Painel</a>
        </div>
    </div>

    <!-- Formulário de Adicionar -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Novo Departamento</h5>
            <form action="departamentos.php" method="POST" class="row g-3">
                <input type="hidden" name="action" value="add">
                <div class="col-md-9">
                    <input type="text" class="form-control" name="nome" placeholder="Nome do Departamento (ex: Teatro)" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100">Adicionar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista -->
    <div class="card shadow">
        <div class="card-body">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($departamentos)): ?>
                        <?php foreach ($departamentos as $dept): ?>
                            <tr>
                                <td><?php echo $dept['id']; ?></td>
                                <td><?php echo htmlspecialchars($dept['nome']); ?></td>
                                <td class="text-end">
                                    <form action="departamentos.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este departamento? Isso pode afetar eventos existentes.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">Nenhum departamento encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
