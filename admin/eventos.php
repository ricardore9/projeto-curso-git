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
    $evento_id = filter_input(INPUT_POST, 'evento_id', FILTER_VALIDATE_INT);

    if ($evento_id) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE eventos SET status = 'aprovado' WHERE id = ?");
            $stmt->execute([$evento_id]);
            $_SESSION['flash_message'] = "Evento aprovado e publicado!";
            $_SESSION['flash_type'] = "success";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE eventos SET status = 'rejeitado' WHERE id = ?");
            $stmt->execute([$evento_id]);
            $_SESSION['flash_message'] = "Evento rejeitado.";
            $_SESSION['flash_type'] = "warning";
        }
    }
    // Manter o filtro atual
    $filtro_atual = $_POST['filtro_atual'] ?? 'pendente';
    header("Location: eventos.php?filtro=" . $filtro_atual);
    exit;
}

// Filtros
$filtro = $_GET['filtro'] ?? 'pendente';
$where = "";
$params = [];

if ($filtro === 'pendente') {
    $where = "WHERE e.status = 'pendente'";
} elseif ($filtro === 'aprovado') {
    $where = "WHERE e.status = 'aprovado'";
} elseif ($filtro === 'rejeitado') {
    $where = "WHERE e.status = 'rejeitado'";
}

// Query
$sql = "
    SELECT
        e.*,
        d.nome as dept_principal,
        u.nome as lider_nome,
        GROUP_CONCAT(da.nome SEPARATOR ', ') as apoio_nomes
    FROM eventos e
    JOIN departamentos d ON e.departamento_principal_id = d.id
    JOIN usuarios u ON e.criado_por_id = u.id
    LEFT JOIN evento_apoio ea ON e.id = ea.evento_id
    LEFT JOIN departamentos da ON ea.departamento_id = da.id
    $where
    GROUP BY e.id
    ORDER BY e.inicio ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$eventos = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-calendar-check me-2"></i>Gerenciar Eventos</h2>
        </div>
        <div class="col-md-6 text-end">
             <div class="btn-group" role="group">
                <a href="eventos.php?filtro=pendente" class="btn btn-outline-warning <?php echo $filtro === 'pendente' ? 'active' : ''; ?>">Pendentes</a>
                <a href="eventos.php?filtro=aprovado" class="btn btn-outline-success <?php echo $filtro === 'aprovado' ? 'active' : ''; ?>">Aprovados</a>
                <a href="eventos.php?filtro=rejeitado" class="btn btn-outline-danger <?php echo $filtro === 'rejeitado' ? 'active' : ''; ?>">Rejeitados</a>
                <a href="eventos.php?filtro=todos" class="btn btn-outline-secondary <?php echo $filtro === 'todos' ? 'active' : ''; ?>">Todos</a>
             </div>
             <a href="dashboard.php" class="btn btn-secondary ms-2">Voltar</a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Data</th>
                            <th>Evento / Depto</th>
                            <th>Local</th>
                            <th>Detalhes</th>
                            <th>Status</th>
                            <th class="text-end" style="min-width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eventos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Nenhum evento encontrado com este filtro.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eventos as $evento): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($evento['inicio'])); ?></strong><br>
                                        <?php echo date('H:i', strtotime($evento['inicio'])); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong><br>
                                        <small class="text-primary"><?php echo htmlspecialchars($evento['dept_principal']); ?></small>
                                        <div class="small text-muted">Líder: <?php echo htmlspecialchars($evento['lider_nome']); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($evento['local_tipo'] === 'igreja'): ?>
                                            <span class="badge bg-secondary">Igreja</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">Externo</span>
                                        <?php endif; ?>
                                        <br>
                                        <small><?php echo htmlspecialchars($evento['local_detalhe']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($evento['precisa_midia']): ?>
                                            <span class="badge bg-primary mb-1"><i class="fas fa-video me-1"></i>Mídia</span><br>
                                        <?php endif; ?>
                                        <?php if (!empty($evento['apoio_nomes'])): ?>
                                            <small><strong>Apoio:</strong> <?php echo htmlspecialchars($evento['apoio_nomes']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($evento['observacoes'])): ?>
                                            <button type="button" class="btn btn-sm btn-link p-0 d-block" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($evento['observacoes']); ?>">
                                                Ver Obs.
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badge = 'bg-secondary';
                                        if ($evento['status'] === 'pendente') $badge = 'bg-warning text-dark';
                                        if ($evento['status'] === 'aprovado') $badge = 'bg-success';
                                        if ($evento['status'] === 'rejeitado') $badge = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($evento['status']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($evento['status'] === 'pendente' || $evento['status'] === 'rejeitado'): ?>
                                            <form action="eventos.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="evento_id" value="<?php echo $evento['id']; ?>">
                                                <input type="hidden" name="filtro_atual" value="<?php echo $filtro; ?>">
                                                <button type="submit" class="btn btn-sm btn-success mb-1" title="Aprovar"><i class="fas fa-check"></i></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($evento['status'] === 'pendente' || $evento['status'] === 'aprovado'): ?>
                                            <form action="eventos.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="evento_id" value="<?php echo $evento['id']; ?>">
                                                <input type="hidden" name="filtro_atual" value="<?php echo $filtro; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger mb-1" title="Rejeitar" onsubmit="return confirm('Rejeitar este evento?');"><i class="fas fa-times"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar tooltips do Bootstrap
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})
</script>

<?php require_once '../includes/footer.php'; ?>
