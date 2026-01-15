<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ações (Approve, Reject) omitidas para brevidade, mantendo lógica existente...
// ... (Copiar lógica POST existente) ...
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
    $filtro_atual = $_POST['filtro_atual'] ?? 'pendente';
    header("Location: eventos.php?filtro=" . $filtro_atual);
    exit;
}

// Filtros e Query
$filtro = $_GET['filtro'] ?? 'pendente';
$where = "";
if ($filtro === 'pendente') $where = "WHERE e.status = 'pendente'";
elseif ($filtro === 'aprovado') $where = "WHERE e.status = 'aprovado'";
elseif ($filtro === 'rejeitado') $where = "WHERE e.status = 'rejeitado'";

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
    ORDER BY e.data_evento ASC, e.hora_inicio ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$eventos = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4 gy-3">
        <div class="col-md-6">
            <h2><i class="fas fa-calendar-check me-2"></i>Gerenciar Eventos</h2>
        </div>
        <div class="col-md-6 text-md-end">
             <div class="btn-group w-100 w-md-auto" role="group">
                <a href="eventos.php?filtro=pendente" class="btn btn-outline-warning <?php echo $filtro === 'pendente' ? 'active' : ''; ?>">Pendentes</a>
                <a href="eventos.php?filtro=aprovado" class="btn btn-outline-success <?php echo $filtro === 'aprovado' ? 'active' : ''; ?>">Aprovados</a>
                <a href="eventos.php?filtro=rejeitado" class="btn btn-outline-danger <?php echo $filtro === 'rejeitado' ? 'active' : ''; ?>">Rejeitados</a>
                <a href="eventos.php?filtro=todos" class="btn btn-outline-secondary <?php echo $filtro === 'todos' ? 'active' : ''; ?>">Todos</a>
             </div>
             <div class="mt-2 d-md-inline-block">
                <a href="dashboard.php" class="btn btn-secondary w-100 w-md-auto">Voltar</a>
             </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body p-0 p-md-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark d-none d-md-table-header-group">
                        <tr>
                            <th>Data</th>
                            <th>Evento</th>
                            <th>Local</th>
                            <th>Status</th>
                            <th class="text-end" style="min-width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eventos)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Nenhum evento encontrado com este filtro.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eventos as $evento): ?>
                                <!-- Layout Responsivo: Linha única no desktop, bloco no mobile -->
                                <tr class="d-block d-md-table-row border-bottom">

                                    <!-- Data -->
                                    <td class="d-block d-md-table-cell px-3 py-2" data-label="Data">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 text-center me-3 d-md-none" style="min-width: 60px;">
                                                <span class="d-block fw-bold"><?php echo date('d', strtotime($evento['data_evento'])); ?></span>
                                                <small class="text-uppercase"><?php echo date('M', strtotime($evento['data_evento'])); ?></small>
                                            </div>
                                            <div>
                                                <strong class="d-none d-md-inline"><?php echo date('d/m/Y', strtotime($evento['data_evento'])); ?></strong><br class="d-none d-md-inline">
                                                <span class="text-muted">
                                                    <i class="far fa-clock d-md-none me-1"></i>
                                                    <?php echo date('H:i', strtotime($evento['hora_inicio'])); ?>
                                                    <?php if ($evento['hora_termino']) echo ' - ' . date('H:i', strtotime($evento['hora_termino'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Evento / Depto -->
                                    <td class="d-block d-md-table-cell px-3 py-2" data-label="Evento">
                                        <strong class="d-block fs-5 fs-md-6"><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                                        <span class="badge bg-info text-dark mb-1"><?php echo htmlspecialchars($evento['dept_principal']); ?></span>
                                        <div class="small text-muted">Líder: <?php echo htmlspecialchars($evento['lider_nome']); ?></div>
                                    </td>

                                    <!-- Local / Detalhes (Ocultar parcialmente em mobile se necessário) -->
                                    <td class="d-block d-md-table-cell px-3 py-2" data-label="Local">
                                        <div class="mb-2">
                                            <?php if ($evento['local_tipo'] === 'igreja'): ?>
                                                <span class="badge bg-secondary"><i class="fas fa-church me-1"></i>Igreja</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark"><i class="fas fa-map-marker-alt me-1"></i>Externo</span>
                                            <?php endif; ?>
                                            <small class="text-muted d-block d-md-inline ms-md-1"><?php echo htmlspecialchars($evento['local_detalhe']); ?></small>
                                        </div>

                                        <button type="button" class="btn btn-sm btn-outline-info w-100 w-md-auto"
                                            onclick="openEventModal(<?php echo htmlspecialchars(json_encode([
                                                'titulo' => $evento['titulo'],
                                                'dept' => $evento['dept_principal'],
                                                'inicio' => date('d/m/Y H:i', strtotime($evento['data_evento'] . ' ' . $evento['hora_inicio'])),
                                                'fim' => ($evento['data_termino'] ? date('d/m/Y', strtotime($evento['data_termino'])) : '') . ' ' . ($evento['hora_termino'] ? date('H:i', strtotime($evento['hora_termino'])) : ''),
                                                'local_tipo' => $evento['local_tipo'] === 'igreja' ? 'Na Igreja' : 'Externo',
                                                'local_detalhe' => $evento['local_detalhe'],
                                                'midia' => $evento['precisa_midia'] ? 'Sim' : 'Não',
                                                'apoio' => $evento['apoio_nomes'] ?? 'Nenhum',
                                                'obs' => $evento['observacoes']
                                            ])); ?>)">
                                            <i class="fas fa-info-circle me-1"></i> Ver Detalhes
                                        </button>
                                    </td>

                                    <!-- Status -->
                                    <td class="d-block d-md-table-cell px-3 py-2" data-label="Status">
                                        <?php
                                        $badge = 'bg-secondary';
                                        if ($evento['status'] === 'pendente') $badge = 'bg-warning text-dark';
                                        if ($evento['status'] === 'aprovado') $badge = 'bg-success';
                                        if ($evento['status'] === 'rejeitado') $badge = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge; ?> w-auto"><?php echo ucfirst($evento['status']); ?></span>
                                    </td>

                                    <!-- Ações -->
                                    <td class="d-block d-md-table-cell px-3 py-3 text-md-end bg-light bg-md-white" data-label="Ações">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <a href="editar_evento.php?id=<?php echo $evento['id']; ?>" class="btn btn-primary flex-fill flex-md-grow-0" title="Editar"><i class="fas fa-edit"></i></a>

                                            <?php if ($evento['status'] === 'pendente' || $evento['status'] === 'rejeitado'): ?>
                                                <form action="eventos.php" method="POST" class="d-inline flex-fill flex-md-grow-0">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="evento_id" value="<?php echo $evento['id']; ?>">
                                                    <input type="hidden" name="filtro_atual" value="<?php echo $filtro; ?>">
                                                    <button type="submit" class="btn btn-success w-100" title="Aprovar"><i class="fas fa-check"></i></button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($evento['status'] === 'pendente' || $evento['status'] === 'aprovado'): ?>
                                                <form action="eventos.php" method="POST" class="d-inline flex-fill flex-md-grow-0">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="evento_id" value="<?php echo $evento['id']; ?>">
                                                    <input type="hidden" name="filtro_atual" value="<?php echo $filtro; ?>">
                                                    <button type="submit" class="btn btn-danger w-100" title="Rejeitar" onsubmit="return confirm('Rejeitar este evento?');"><i class="fas fa-times"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
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

<!-- Modal Detalhes (Mantido Igual) -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalTitulo">Detalhes do Evento</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Departamento:</strong> <span id="modalDept"></span></p>
        <p><strong>Início:</strong> <span id="modalInicio"></span></p>
        <p><strong>Término:</strong> <span id="modalFim"></span></p>
        <hr>
        <p><strong>Local:</strong> <span id="modalLocal"></span> <span class="text-muted" id="modalLocalDetalhe"></span></p>
        <p><strong>Precisa de Mídia?</strong> <span id="modalMidia"></span></p>
        <p><strong>Apoio Solicitado:</strong> <span id="modalApoio"></span></p>
        <div class="alert alert-light border">
            <strong>Observações:</strong><br>
            <span id="modalObs"></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
function openEventModal(data) {
    document.getElementById('modalTitulo').innerText = data.titulo;
    document.getElementById('modalDept').innerText = data.dept;
    document.getElementById('modalInicio').innerText = data.inicio;
    document.getElementById('modalFim').innerText = data.fim.trim() !== '' ? data.fim : 'N/A';

    document.getElementById('modalLocal').innerText = data.local_tipo;
    document.getElementById('modalLocalDetalhe').innerText = '(' + data.local_detalhe + ')';

    document.getElementById('modalMidia').innerText = data.midia;
    document.getElementById('modalApoio').innerText = data.apoio;
    document.getElementById('modalObs').innerText = data.obs ? data.obs : 'Nenhuma observação.';

    var myModal = new bootstrap.Modal(document.getElementById('eventModal'));
    myModal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
