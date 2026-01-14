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
            'local' => $ev['local_tipo'] === 'igreja' ? 'Na Igreja' : 'Externo',
            'detalhe' => $ev['local_detalhe'],
            'descricao' => $ev['observacoes']
        ]
    ];
}
?>

<div class="container mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1 class="display-5"><i class="fas fa-calendar-alt me-2 text-primary"></i>Agenda Oficial</h1>
            <p class="lead text-muted">Acompanhe a programação da igreja.</p>
        </div>
        <div class="col-md-6">
            <form action="index.php" method="GET" class="d-flex">
                <select name="dept" class="form-select me-2" onchange="this.form.submit()">
                    <option value="">Todos os Departamentos</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $dept_id == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <?php if ($dept_id): ?>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">Limpar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Abas de Visualização -->
    <ul class="nav nav-tabs mb-4" id="viewTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" type="button" role="tab"><i class="fas fa-calendar-alt me-2"></i>Calendário</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-view" type="button" role="tab"><i class="fas fa-list me-2"></i>Lista Próximos Eventos</button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">

        <!-- Visão Calendário -->
        <div class="tab-pane fade show active" id="calendar-view" role="tabpanel">
            <div class="card shadow">
                <div class="card-body p-2 p-md-4">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <!-- Visão Lista -->
        <div class="tab-pane fade" id="list-view" role="tabpanel">
            <div class="card shadow">
                <div class="card-body">
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
                                <div class="list-group-item list-group-item-action p-4 <?php echo $opacity; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                        <h4 class="mb-1 text-primary"><?php echo htmlspecialchars($ev['titulo']); ?></h4>
                                        <small class="text-muted text-end">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo $start_dt->format('d/m/Y \à\s H:i'); ?>
                                            <?php if ($ev['data_termino']): ?>
                                                 <br>até <?php echo date('d/m/Y', strtotime($ev['data_termino'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <p class="mb-2"><span class="badge bg-secondary"><?php echo htmlspecialchars($ev['dept_nome']); ?></span></p>
                                    <p class="mb-1">
                                        <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                        <?php echo $ev['local_tipo'] === 'igreja' ? 'Na Igreja' : 'Externo'; ?>
                                        - <strong><?php echo htmlspecialchars($ev['local_detalhe']); ?></strong>
                                    </p>
                                    <?php if (!empty($ev['observacoes'])): ?>
                                        <small class="text-muted mt-2 d-block bg-light p-2 rounded">
                                            <i class="fas fa-info-circle me-1"></i> <?php echo htmlspecialchars($ev['observacoes']); ?>
                                        </small>
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

<!-- Script FullCalendar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth'
        },
        buttonText: {
            today:    'Hoje',
            month:    'Mês',
            week:     'Semana',
            day:      'Dia',
            list:     'Lista'
        },
        height: 'auto',
        events: <?php echo json_encode($calendar_events); ?>,
        eventClick: function(info) {
            var props = info.event.extendedProps;
            var content = '<strong>Local:</strong> ' + props.local + ' - ' + props.detalhe;
            if (props.descricao) {
                content += '<br><br><strong>Obs:</strong> ' + props.descricao;
            }
            // Formatar data para exibição no alert
            var startStr = info.event.start.toLocaleString();
            var endStr = info.event.end ? '\nFim: ' + info.event.end.toLocaleString() : '';

            alert('Evento: ' + info.event.title + '\n\n' +
                  'Início: ' + startStr + endStr + '\n' +
                  'Local: ' + props.local + ' (' + props.detalhe + ')\n\n' +
                  (props.descricao ? 'Obs: ' + props.descricao : '')
            );
        }
    });
    calendar.render();
});
</script>

<?php require_once 'includes/footer.php'; ?>
