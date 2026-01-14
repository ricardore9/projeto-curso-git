<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 1. Buscar departamentos do líder (para selecionar qual está promovendo o evento)
try {
    $stmt = $pdo->prepare("
        SELECT d.id, d.nome
        FROM departamentos d
        JOIN usuario_departamentos ud ON d.id = ud.departamento_id
        WHERE ud.usuario_id = ?
        ORDER BY d.nome ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $meus_departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $meus_departamentos = [];
}

// Se o líder não tem departamentos, ele não pode criar eventos
if (empty($meus_departamentos) && $_SESSION['user_type'] !== 'admin') {
    die("Você não está vinculado a nenhum departamento. Contate o administrador.");
}

// 2. Buscar todos departamentos (para selecionar apoio)
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

                        <!-- Datas -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="inicio" class="form-label fs-5">Início</label>
                                <input type="datetime-local" class="form-control form-control-lg" name="inicio" id="inicio" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fim" class="form-label fs-5">Previsão de Término</label>
                                <input type="datetime-local" class="form-control form-control-lg" name="fim" id="fim">
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
                            <input type="text" class="form-control" name="local_detalhe" id="local_detalhe" placeholder="Ex: Templo Principal, Sala 3, ou Endereço Externo">
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
                            <a href="dashboard.php" class="btn btn-secondary btn-lg">Cancelar</a>
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
</script>

<?php require_once '../includes/footer.php'; ?>
