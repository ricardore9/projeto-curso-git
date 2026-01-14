<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Buscar eventos do usuário logado
try {
    $stmt = $pdo->prepare("
        SELECT e.*, d.nome as dept_nome
        FROM eventos e
        LEFT JOIN departamentos d ON e.departamento_principal_id = d.id
        WHERE e.criado_por_id = ?
        ORDER BY e.inicio DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $eventos = $stmt->fetchAll();
} catch (PDOException $e) {
    $eventos = [];
    $erro = "Erro ao carregar eventos.";
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2><i class="fas fa-calendar-alt me-2"></i>Meus Eventos</h2>
            <p class="text-muted">Gerencie os eventos dos seus departamentos.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="novo_evento.php" class="btn btn-primary btn-lg-custom shadow">
                <i class="fas fa-plus me-2"></i>Novo Evento
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Evento</th>
                            <th>Departamento</th>
                            <th>Local</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eventos)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                                    Nenhum evento cadastrado. Clique em "Novo Evento" para começar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eventos as $evento): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($evento['inicio'])); ?></strong><br>
                                        <small><?php echo date('H:i', strtotime($evento['inicio'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($evento['dept_nome']); ?></td>
                                    <td>
                                        <?php if ($evento['local_tipo'] === 'igreja'): ?>
                                            <span class="badge bg-secondary">Na Igreja</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">Externo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = 'bg-secondary';
                                        $statusLabel = $evento['status'];
                                        if ($evento['status'] === 'pendente') {
                                            $statusClass = 'bg-warning text-dark';
                                            $statusLabel = 'Aguardando Aprovação';
                                        } elseif ($evento['status'] === 'aprovado') {
                                            $statusClass = 'bg-success';
                                            $statusLabel = 'Aprovado';
                                        } elseif ($evento['status'] === 'rejeitado') {
                                            $statusClass = 'bg-danger';
                                            $statusLabel = 'Rejeitado';
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?> fs-6"><?php echo $statusLabel; ?></span>
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

<?php require_once '../includes/footer.php'; ?>
