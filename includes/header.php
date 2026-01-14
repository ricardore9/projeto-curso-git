<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Define o caminho base se não estiver definido
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('SITE_NAME') ? SITE_NAME : 'Agenda da CESE'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- FullCalendar CSS (apenas carregado se necessário, mas pode deixar global) -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

    <style>
        body { font-size: 1.1rem; } /* Fonte um pouco maior para acessibilidade */
        .navbar-brand { font-size: 1.5rem; font-weight: bold; }
        .btn-lg-custom { padding: 10px 20px; font-size: 1.2rem; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>">
            <img src="<?php echo BASE_URL; ?>assets/img/logo-cese-br.png" alt="Logo CESE" style="height: 40px;" class="me-2">
            Agenda da CESE
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link text-white" href="<?php echo BASE_URL; ?>">Início</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold" href="<?php echo BASE_URL; ?>profile.php">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                            <a class="btn btn-light ms-2" href="<?php echo BASE_URL; ?>admin/dashboard.php">Painel Admin</a>
                        <?php else: ?>
                            <a class="btn btn-light ms-2" href="<?php echo BASE_URL; ?>leader/dashboard.php">Meus Eventos</a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-danger ms-2" href="<?php echo BASE_URL; ?>logout.php">Sair</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>login.php">Entrar</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-warning ms-2" href="<?php echo BASE_URL; ?>register.php">Cadastrar Líder</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container flex-grow-1">
    <!-- Mensagens Flash -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
