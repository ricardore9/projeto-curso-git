<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Filtro de Departamento
$dept_id = filter_input(INPUT_GET, 'dept', FILTER_VALIDATE_INT);

// Buscar Departamentos para o filtro
$dept_stmt = $pdo->query("SELECT * FROM departamentos ORDER BY nome ASC");
$departamentos = $dept_stmt->fetchAll();

// Buscar Eventos Aprovados
$sql = "SELECT e.*, d.nome as dept_nome, d.id as dept_id
        FROM eventos e
        JOIN departamentos d ON e.departamento_principal_id = d.id
        WHERE e.status = 'aprovado'";

$params = [];
if ($dept_id) {
    $sql .= " AND d.id = ?";
    $params[] = $dept_id;
}

$sql .= " ORDER BY e.data_evento ASC, e.hora_inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$eventos = $stmt->fetchAll();

// Preparar dados para o FullCalendar
$calendar_events = [];
foreach ($eventos as $ev) {
    $color = '#3788d8'; // Default blue

    // Concatenar Data e Hora para ISO8601
    $start = $ev['data_evento'] . 'T' . $ev['hora_inicio'];

    $end = null;
    if ($ev['data_termino'] && $ev['hora_termino']) {
        $end = $ev['data_termino'] . 'T' . $ev['hora_termino'];
    } elseif ($ev['hora_termino']) {
        // Se tem hora de termino mas não data (mesmo dia)
        $end = $ev['data_evento'] . 'T' . $ev['hora_termino'];
    }

    $calendar_events[] = [
        'title' => $ev['titulo'] . ' (' . $ev['dept_nome'] . ')',
        'start' => $start,
        'end' => $end,
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'dept' => $ev['dept_nome'],
            'local' => $ev['local_tipo'] === 'igreja' ? 'Na Igreja' : 'Externo',
            'detalhe' => $ev['local_detalhe'],
            'descricao' => $ev['observacoes']
        ]
    ];
}
?>

<style>
    /* Estilos para Mobile do FullCalendar */
    @media (max-width: 768px) {
        .fc-header-toolbar {
            flex-direction: column;
            gap: 10px;
        }
        .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        .fc-toolbar-title {
            font-size: 1.2rem !important;
        }
    }
</style>

<div class="container mt-3 mt-md-4">
    <div class="row mb-4 align-items-center g-3">
        <div class="col-md-6 text-center text-md-start">
            <h1 class="display-6 display-md-5"><i class="fas fa-calendar-alt me-2 text-primary"></i>Agenda Oficial</h1>
            <p class="lead text-muted small md-normal">Acompanhe a programação da igreja.</p>
        </div>
        <div class="col-md-6">
            <form action="index.php" method="GET" class="d-flex flex-column flex-sm-row gap-2">
                <select name="dept" class="form-select flex-grow-1" onchange="this.form.submit()">
                    <option value="">Todos os Departamentos</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $dept_id == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="d-grid d-sm-block">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <?php if ($dept_id): ?>
                        <a href="index.php" class="btn btn-outline-secondary ms-2">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Abas de Visualização -->
    <ul class="nav nav-tabs mb-4 nav-fill" id="viewTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" type="button" role="tab"><i class="fas fa-calendar-alt me-2"></i>Calendário</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-view" type="button" role="tab"><i class="fas fa-list me-2"></i>Lista</button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">

        <!-- Visão Calendário -->
        <div class="tab-pane fade show active" id="calendar-view" role="tabpanel">
            <div class="card shadow border-0">
                <div class="card-body p-2 p-md-4">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <!-- Visão Lista -->
        <div class="tab-pane fade" id="list-view" role="tabpanel">
            <div class="card shadow border-0">
                <div class="card-body p-2 p-md-4">
                    <?php if (empty($eventos)): ?>
                        <p class="text-center py-5 text-muted">Nenhum evento encontrado.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php
                            foreach ($eventos as $ev):
                                $start_dt = new DateTime($ev['data_evento'] . ' ' . $ev['hora_inicio']);
                                $now = new DateTime();
                                $is_past = $start_dt < $now;
                                $opacity = $is_past ? 'opacity-50' : '';
                            ?>
                                <div class="list-group-item list-group-item-action p-3 <?php echo $opacity; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($ev['titulo']); ?></h5>
                                            <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($ev['dept_nome']); ?></span>
                                        </div>
                                        <small class="text-muted text-end text-nowrap ms-2">
                                            <i class="far fa-clock me-1"></i><br>
                                            <?php echo $start_dt->format('d/m/Y'); ?><br>
                                            <strong><?php echo $start_dt->format('H:i'); ?></strong>
                                        </small>
                                    </div>
                                    <p class="mb-1 small">
                                        <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                        <?php echo $ev['local_tipo'] === 'igreja' ? 'Na Igreja' : 'Externo'; ?>
                                        - <strong><?php echo htmlspecialchars($ev['local_detalhe']); ?></strong>
                                    </p>
                                    <?php if (!empty($ev['observacoes'])): ?>
                                        <div class="mt-2 p-2 bg-light rounded small text-muted">
                                            <i class="fas fa-info-circle me-1"></i> <?php echo htmlspecialchars($ev['observacoes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes (Público) -->
<div class="modal fade" id="publicEventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="pubModalTitulo">Detalhes do Evento</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h4 id="pubModalNome" class="text-primary mb-3"></h4>

        <p class="mb-2"><i class="fas fa-layer-group me-2 text-muted"></i> <strong>Departamento:</strong> <span id="pubModalDept"></span></p>
        <p class="mb-2"><i class="far fa-clock me-2 text-muted"></i> <strong>Início:</strong> <span id="pubModalInicio"></span></p>
        <p class="mb-2" id="pubDivFim"><i class="far fa-clock me-2 text-muted"></i> <strong>Término:</strong> <span id="pubModalFim"></span></p>

        <hr>

        <p class="mb-2"><i class="fas fa-map-marker-alt me-2 text-danger"></i> <strong>Local:</strong> <span id="pubModalLocal"></span> <span class="text-muted" id="pubModalLocalDetalhe"></span></p>

        <div id="pubDivObs" class="alert alert-light border mt-3">
            <strong><i class="fas fa-info-circle me-1"></i> Observações:</strong><br>
            <span id="pubModalObs"></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Script FullCalendar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    // Configuração responsiva da toolbar
    var isMobile = window.innerWidth < 768;
    var headerToolbarConfig = isMobile ? {
        left: 'prev,next',
        center: 'title',
        right: 'dayGridMonth,listMonth' // Remove views complexas no mobile
    } : {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listMonth'
    };

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: headerToolbarConfig,
        buttonText: {
            today:    'Hoje',
            month:    'Mês',
            week:     'Semana',
            day:      'Dia',
            list:     'Lista'
        },
        height: 'auto',
        contentHeight: isMobile ? 500 : 'auto', // Altura fixa no mobile evita pulos
        events: <?php echo json_encode($calendar_events); ?>,
        eventClick: function(info) {
            var props = info.event.extendedProps;

            // Preencher Modal
            document.getElementById('pubModalNome').innerText = info.event.title;
            document.getElementById('pubModalDept').innerText = props.dept;

            // Formatar datas
            var start = info.event.start;
            var end = info.event.end;

            document.getElementById('pubModalInicio').innerText = start.toLocaleString();

            if (end) {
                document.getElementById('pubModalFim').innerText = end.toLocaleString();
                document.getElementById('pubDivFim').style.display = 'block';
            } else {
                document.getElementById('pubDivFim').style.display = 'none';
            }

            document.getElementById('pubModalLocal').innerText = props.local;
            document.getElementById('pubModalLocalDetalhe').innerText = '(' + props.detalhe + ')';

            if (props.descricao) {
                document.getElementById('pubModalObs').innerText = props.descricao;
                document.getElementById('pubDivObs').style.display = 'block';
            } else {
                document.getElementById('pubDivObs').style.display = 'none';
            }

            var myModal = new bootstrap.Modal(document.getElementById('publicEventModal'));
            myModal.show();
        }
    });
    calendar.render();
});
</script>

<?php require_once 'includes/footer.php'; ?>
