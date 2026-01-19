<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 1. Buscar departamentos do líder
try {
    if ($_SESSION['user_type'] === 'admin') {
        // Se for admin, carrega TODOS os departamentos para ele escolher
        $stmt = $pdo->query("SELECT id, nome FROM departamentos ORDER BY nome ASC");
        $meus_departamentos = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("
            SELECT d.id, d.nome
            FROM departamentos d
            JOIN usuario_departamentos ud ON d.id = ud.departamento_id
            WHERE ud.usuario_id = ?
            ORDER BY d.nome ASC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $meus_departamentos = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $meus_departamentos = [];
}

if (empty($meus_departamentos) && $_SESSION['user_type'] !== 'admin') {
    die("Você não está vinculado a nenhum departamento. Contate o administrador.");
}

// 2. Buscar todos departamentos (para apoio)
try {
    $stmt = $pdo->query("SELECT id, nome FROM departamentos ORDER BY nome ASC");
    $todos_departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $todos_departamentos = [];
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Agendar Novo Evento</h3>
                </div>
                <div class="card-body p-4">
                    <form action="salvar_evento.php" method="POST">

                        <!-- Departamento Responsável -->
                        <div class="mb-3">
                            <label for="departamento_id" class="form-label fs-5">Departamento Responsável</label>
                            <select class="form-select form-select-lg" name="departamento_id" id="departamento_id" required>
                                <?php foreach ($meus_departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Título -->
                        <div class="mb-3">
                            <label for="titulo" class="form-label fs-5">Atividade / Evento</label>
                            <input type="text" class="form-control form-control-lg" name="titulo" id="titulo" placeholder="Ex: Ensaio Geral, Culto de Jovens" required>
                        </div>

                        <!-- Data e Hora de Início -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_evento" class="form-label fs-5">Data do Evento</label>
                                <input type="date" class="form-control form-control-lg" name="data_evento" id="data_evento" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hora_inicio" class="form-label fs-5">Horário de Início</label>
                                <input type="time" class="form-control form-control-lg" name="hora_inicio" id="hora_inicio" required>
                            </div>
                        </div>

                        <!-- Data e Hora de Término (Opcional) -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tem_termino" onchange="toggleTermino()">
                                <label class="form-check-label fs-6" for="tem_termino">
                                    Adicionar previsão de término
                                </label>
                            </div>
                        </div>

                        <div class="row d-none" id="div_termino">
                            <div class="col-md-6 mb-3">
                                <label for="data_termino" class="form-label fs-5">Data de Término</label>
                                <input type="date" class="form-control form-control-lg" name="data_termino" id="data_termino">
                                <div class="form-text">Preencha apenas se o evento acabar em outro dia.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hora_termino" class="form-label fs-5">Horário de Término</label>
                                <input type="time" class="form-control form-control-lg" name="hora_termino" id="hora_termino">
                            </div>
                        </div>

                        <!-- Local -->
                        <div class="mb-3">
                            <label class="form-label fs-5">Local</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="local_tipo" id="local_igreja" value="igreja" checked onclick="toggleLocalDetalhe(false)">
                                    <label class="form-check-label" for="local_igreja">Na Igreja</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="local_tipo" id="local_externo" value="externo" onclick="toggleLocalDetalhe(true)">
                                    <label class="form-check-label" for="local_externo">Externo</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="local_detalhe" class="form-label">Detalhe do Local</label>
                            <input type="text" class="form-control" name="local_detalhe" id="local_detalhe" placeholder="Ex: Templo Principal, Sala 3">
                        </div>

                        <hr>

                        <!-- Apoio e Recursos -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="precisa_midia" id="precisa_midia" value="1" style="transform: scale(1.3); margin-right: 10px;">
                                <label class="form-check-label fs-5" for="precisa_midia">Precisa do Departamento de Mídia?</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-5">Precisa de apoio de outros departamentos?</label>
                            <div class="card card-body bg-light" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($todos_departamentos as $dept): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="apoio[]" value="<?php echo $dept['id']; ?>" id="apoio_<?php echo $dept['id']; ?>">
                                        <label class="form-check-label" for="apoio_<?php echo $dept['id']; ?>">
                                            <?php echo htmlspecialchars($dept['nome']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Observações -->
                        <div class="mb-4">
                            <label for="observacoes" class="form-label fs-5">Observações</label>
                            <textarea class="form-control" name="observacoes" id="observacoes" rows="3" placeholder="Detalhes adicionais..."></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo $_SESSION['user_type'] === 'admin' ? '../admin/dashboard.php' : 'dashboard.php'; ?>" class="btn btn-secondary btn-lg">Cancelar</a>
                            <button type="submit" class="btn btn-success btn-lg px-5">Salvar Evento</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleLocalDetalhe(isExterno) {
    const input = document.getElementById('local_detalhe');
    if (isExterno) {
        input.placeholder = "Digite o endereço completo";
        input.required = true;
    } else {
        input.placeholder = "Ex: Templo Principal, Sala 3";
        input.required = false;
    }
}

function toggleTermino() {
    const check = document.getElementById('tem_termino');
    const div = document.getElementById('div_termino');
    if (check.checked) {
        div.classList.remove('d-none');
    } else {
        div.classList.add('d-none');
        document.getElementById('data_termino').value = '';
        document.getElementById('hora_termino').value = '';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
