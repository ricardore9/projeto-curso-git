<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$evento_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$evento_id) {
    header("Location: eventos.php");
    exit;
}

// Buscar dados do evento
try {
    $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
    $stmt->execute([$evento_id]);
    $evento = $stmt->fetch();

    if (!$evento) {
        die("Evento não encontrado.");
    }

    // Buscar departamentos de apoio
    $stmt_apoio = $pdo->prepare("SELECT departamento_id FROM evento_apoio WHERE evento_id = ?");
    $stmt_apoio->execute([$evento_id]);
    $apoio_ids = $stmt_apoio->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Erro ao carregar evento.");
}

// Buscar todos departamentos
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
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Evento</h3>
                </div>
                <div class="card-body p-4">
                    <form action="salvar_edicao.php" method="POST">
                        <input type="hidden" name="evento_id" value="<?php echo $evento['id']; ?>">

                        <!-- Departamento Responsável -->
                        <div class="mb-3">
                            <label for="departamento_id" class="form-label fs-5">Departamento Responsável</label>
                            <select class="form-select form-select-lg" name="departamento_id" id="departamento_id" required>
                                <?php foreach ($todos_departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $dept['id'] == $evento['departamento_principal_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Título -->
                        <div class="mb-3">
                            <label for="titulo" class="form-label fs-5">Atividade / Evento</label>
                            <input type="text" class="form-control form-control-lg" name="titulo" id="titulo" value="<?php echo htmlspecialchars($evento['titulo']); ?>" required>
                        </div>

                        <!-- Data e Hora de Início -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_evento" class="form-label fs-5">Data do Evento</label>
                                <input type="date" class="form-control form-control-lg" name="data_evento" id="data_evento" value="<?php echo $evento['data_evento']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hora_inicio" class="form-label fs-5">Horário de Início</label>
                                <input type="time" class="form-control form-control-lg" name="hora_inicio" id="hora_inicio" value="<?php echo $evento['hora_inicio']; ?>" required>
                            </div>
                        </div>

                        <!-- Data e Hora de Término (Opcional) -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tem_termino" onchange="toggleTermino()" <?php echo ($evento['hora_termino'] || $evento['data_termino']) ? 'checked' : ''; ?>>
                                <label class="form-check-label fs-6" for="tem_termino">
                                    Adicionar previsão de término
                                </label>
                            </div>
                        </div>

                        <div class="row <?php echo ($evento['hora_termino'] || $evento['data_termino']) ? '' : 'd-none'; ?>" id="div_termino">
                            <div class="col-md-6 mb-3">
                                <label for="data_termino" class="form-label fs-5">Data de Término</label>
                                <input type="date" class="form-control form-control-lg" name="data_termino" id="data_termino" value="<?php echo $evento['data_termino']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hora_termino" class="form-label fs-5">Horário de Término</label>
                                <input type="time" class="form-control form-control-lg" name="hora_termino" id="hora_termino" value="<?php echo $evento['hora_termino']; ?>">
                            </div>
                        </div>

                        <!-- Local -->
                        <div class="mb-3">
                            <label class="form-label fs-5">Local</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="local_tipo" id="local_igreja" value="igreja" <?php echo $evento['local_tipo'] == 'igreja' ? 'checked' : ''; ?> onclick="toggleLocalDetalhe(false)">
                                    <label class="form-check-label" for="local_igreja">Na Igreja</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="local_tipo" id="local_externo" value="externo" <?php echo $evento['local_tipo'] == 'externo' ? 'checked' : ''; ?> onclick="toggleLocalDetalhe(true)">
                                    <label class="form-check-label" for="local_externo">Externo</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="local_detalhe" class="form-label">Detalhe do Local</label>
                            <input type="text" class="form-control" name="local_detalhe" id="local_detalhe" value="<?php echo htmlspecialchars($evento['local_detalhe']); ?>" placeholder="Ex: Templo Principal, Sala 3">
                        </div>

                        <hr>

                        <!-- Apoio e Recursos -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="precisa_midia" id="precisa_midia" value="1" <?php echo $evento['precisa_midia'] ? 'checked' : ''; ?> style="transform: scale(1.3); margin-right: 10px;">
                                <label class="form-check-label fs-5" for="precisa_midia">Precisa do Departamento de Mídia?</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-5">Precisa de apoio de outros departamentos?</label>
                            <div class="card card-body bg-light" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($todos_departamentos as $dept): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="apoio[]" value="<?php echo $dept['id']; ?>" id="apoio_<?php echo $dept['id']; ?>" <?php echo in_array($dept['id'], $apoio_ids) ? 'checked' : ''; ?>>
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
                            <textarea class="form-control" name="observacoes" id="observacoes" rows="3"><?php echo htmlspecialchars($evento['observacoes']); ?></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="eventos.php" class="btn btn-secondary btn-lg">Cancelar</a>
                            <button type="submit" class="btn btn-success btn-lg px-5">Salvar Alterações</button>
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
