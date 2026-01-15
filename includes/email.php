<?php
require_once __DIR__ . '/../email_config.php';
require_once __DIR__ . '/SimpleSMTP.php';

function send_email($to, $subject, $message) {
    if (SMTP_PASS === 'SUA_SENHA_AQUI') {
        // Fallback para mail() se não configurado, ou apenas log
        // error_log("SMTP não configurado. Tentando mail() nativo.");
        // Mas o mail() nativo também não está funcionando segundo o usuário.
        // Vamos simular sucesso para não quebrar a aplicação, mas logar o erro.
        // Se estiver em localhost/dev, retorna false visualmente.
        return false;
    }

    $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
    return $smtp->send($to, $subject, $message);
}

function send_admin_notification($subject, $message) {
    $to = "rhsilva198@gmail.com";
    return send_email($to, $subject, $message);
}
?>