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

// Query (Ordenada por data_evento e hora_inicio)
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
                            <th>Data/Hora</th>
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
                                        <strong><?php echo date('d/m/Y', strtotime($evento['data_evento'])); ?></strong><br>
                                        <?php echo date('H:i', strtotime($evento['hora_inicio'])); ?>
                                        <?php if ($evento['hora_termino']): ?>
                                            - <?php echo date('H:i', strtotime($evento['hora_termino'])); ?>
                                        <?php endif; ?>
                                        <?php if ($evento['data_termino']): ?>
                                            <br><small class="text-muted">até <?php echo date('d/m', strtotime($evento['data_termino'])); ?></small>
                                        <?php endif; ?>
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
                                        <!-- Botão Modal -->
                                        <button type="button" class="btn btn-sm btn-info text-white"
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
                                            <i class="fas fa-eye me-1"></i> Ver Detalhes
                                        </button>
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
                                        <a href="editar_evento.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-primary mb-1" title="Editar"><i class="fas fa-edit"></i></a>

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

<!-- Modal Detalhes -->
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
