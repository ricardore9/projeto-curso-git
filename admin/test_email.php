<?php
require_once '../includes/db.php';
require_once '../includes/email.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'];
    $subject = "Teste de Email - Agenda CESE";
    $message = "<h1>Teste de Envio</h1><p>Se você recebeu este email, o SMTP está configurado corretamente.</p>";

    if (SMTP_PASS === 'SUA_SENHA_AQUI') {
        $msg = "A senha do SMTP ainda não foi configurada em email_config.php.";
        $tipo = "danger";
    } else {
        if (send_email($to, $subject, $message)) {
            $msg = "Email enviado com sucesso para $to!";
            $tipo = "success";
        } else {
            $msg = "Falha ao enviar email. Verifique o log de erros do servidor (ou a saída do SimpleSMTP).";
            $tipo = "danger";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h3 class="mb-0"><i class="fas fa-envelope me-2"></i>Testar Configuração de Email</h3>
                </div>
                <div class="card-body">
                    <?php if ($msg): ?>
                        <div class="alert alert-<?php echo $tipo; ?>"><?php echo $msg; ?></div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <strong>Configuração Atual:</strong><br>
                        Host: <?php echo defined('SMTP_HOST') ? SMTP_HOST : 'Não definido'; ?><br>
                        Porta: <?php echo defined('SMTP_PORT') ? SMTP_PORT : 'Não definido'; ?><br>
                        Usuário: <?php echo defined('SMTP_USER') ? SMTP_USER : 'Não definido'; ?>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Enviar para:</label>
                            <input type="email" name="to" class="form-control" value="rhsilva198@gmail.com" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Enviar Teste</button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="dashboard.php" class="btn btn-link">Voltar ao Painel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
