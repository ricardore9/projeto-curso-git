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
        ORDER BY e.data_evento DESC, e.hora_inicio DESC
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
    <div class="row mb-4 align-items-center g-3">
        <div class="col-md-8 text-center text-md-start">
            <h2><i class="fas fa-calendar-alt me-2"></i>Meus Eventos</h2>
            <p class="text-muted mb-0">Gerencie os eventos dos seus departamentos.</p>
        </div>
        <div class="col-md-4 text-center text-md-end">
            <a href="novo_evento.php" class="btn btn-primary btn-lg-custom shadow w-100 w-md-auto">
                <i class="fas fa-plus me-2"></i>Novo Evento
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body p-0 p-md-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light d-none d-md-table-header-group">
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
                                <!-- Layout Responsivo Leader: Stacked on mobile -->
                                <tr class="d-block d-md-table-row border-bottom p-3">

                                    <!-- Data -->
                                    <td class="d-flex d-md-table-cell justify-content-between align-items-center px-3 py-2" data-label="Data">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 text-center me-3 d-md-none" style="min-width: 60px;">
                                                <span class="d-block fw-bold"><?php echo date('d', strtotime($evento['data_evento'])); ?></span>
                                                <small class="text-uppercase"><?php echo date('M', strtotime($evento['data_evento'])); ?></small>
                                            </div>
                                            <div>
                                                <strong class="d-none d-md-inline"><?php echo date('d/m/Y', strtotime($evento['data_evento'])); ?></strong><br class="d-none d-md-inline">
                                                <small class="text-muted">
                                                    <i class="far fa-clock d-md-none me-1"></i>
                                                    <?php echo date('H:i', strtotime($evento['hora_inicio'])); ?>
                                                    <?php if ($evento['hora_termino']): ?>
                                                        - <?php echo date('H:i', strtotime($evento['hora_termino'])); ?>
                                                    <?php endif; ?>
                                                </small>
                                                <?php if ($evento['data_termino']): ?>
                                                    <br><small class="text-muted">Fim: <?php echo date('d/m', strtotime($evento['data_termino'])); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Status Badge (Moved to top right on mobile for visibility) -->
                                        <div class="d-md-none">
                                            <?php
                                            $statusClass = 'bg-secondary';
                                            $statusLabel = $evento['status'];
                                            if ($evento['status'] === 'pendente') {
                                                $statusClass = 'bg-warning text-dark';
                                                $statusLabel = 'Pendente'; // Encurtado para mobile
                                            } elseif ($evento['status'] === 'aprovado') {
                                                $statusClass = 'bg-success';
                                                $statusLabel = 'Aprovado';
                                            } elseif ($evento['status'] === 'rejeitado') {
                                                $statusClass = 'bg-danger';
                                                $statusLabel = 'Rejeitado';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                        </div>
                                    </td>

                                    <!-- Evento -->
                                    <td class="d-block d-md-table-cell px-3 py-1 py-md-2" data-label="Evento">
                                        <strong class="fs-5 fs-md-6"><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                                    </td>

                                    <!-- Departamento -->
                                    <td class="d-block d-md-table-cell px-3 py-1 py-md-2 text-muted" data-label="Departamento">
                                        <small class="d-md-none text-uppercase fw-bold" style="font-size: 0.7rem;">Departamento:</small>
                                        <?php echo htmlspecialchars($evento['dept_nome']); ?>
                                    </td>

                                    <!-- Local -->
                                    <td class="d-block d-md-table-cell px-3 py-1 py-md-2 text-muted" data-label="Local">
                                        <small class="d-md-none text-uppercase fw-bold" style="font-size: 0.7rem;">Local:</small>
                                        <?php if ($evento['local_tipo'] === 'igreja'): ?>
                                            <span class="badge bg-secondary d-none d-md-inline">Na Igreja</span>
                                            <span class="d-md-none">Igreja</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark d-none d-md-inline">Externo</span>
                                            <span class="d-md-none">Externo</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status (Desktop Only - Mobile is handled in Data cell) -->
                                    <td class="d-none d-md-table-cell px-3 py-2">
                                        <?php
                                        // Recalcular labels longos para desktop
                                        if ($evento['status'] === 'pendente') $statusLabel = 'Aguardando Aprovação';
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
