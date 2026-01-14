<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Contadores
$pendentes_eventos = $pdo->query("SELECT COUNT(*) FROM eventos WHERE status = 'pendente'")->fetchColumn();
$pendentes_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE status = 'pendente'")->fetchColumn();
$total_eventos = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-tachometer-alt me-2"></i>Painel do Administrador</h2>
        </div>
    </div>

    <div class="row">
        <!-- Card Eventos Pendentes -->
        <div class="col-md-4 mb-4">
            <div class="card bg-warning text-dark h-100 shadow">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-clock me-2"></i>Eventos Pendentes</h5>
                    <h2 class="display-4"><?php echo $pendentes_eventos; ?></h2>
                    <p class="card-text">Eventos aguardando sua aprovação.</p>
                    <a href="eventos.php?filtro=pendente" class="btn btn-light w-100">Ver Eventos</a>
                </div>
            </div>
        </div>

        <!-- Card Usuários Pendentes -->
        <div class="col-md-4 mb-4">
            <div class="card bg-info text-white h-100 shadow">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-clock me-2"></i>Líderes Pendentes</h5>
                    <h2 class="display-4"><?php echo $pendentes_usuarios; ?></h2>
                    <p class="card-text">Novos cadastros aguardando liberação.</p>
                    <a href="usuarios.php" class="btn btn-light w-100">Gerenciar Usuários</a>
                </div>
            </div>
        </div>

        <!-- Card Total Eventos -->
        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white h-100 shadow">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-calendar-check me-2"></i>Total de Eventos</h5>
                    <h2 class="display-4"><?php echo $total_eventos; ?></h2>
                    <p class="card-text">Eventos cadastrados no sistema.</p>
                    <a href="eventos.php" class="btn btn-light w-100">Todos os Eventos</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6 mb-3">
            <a href="departamentos.php" class="btn btn-secondary btn-lg w-100 p-4">
                <i class="fas fa-layer-group fa-2x mb-2 d-block"></i>
                Gerenciar Departamentos
            </a>
        </div>
        <div class="col-md-6 mb-3">
            <a href="usuarios.php" class="btn btn-primary btn-lg w-100 p-4">
                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                Gerenciar Usuários
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
