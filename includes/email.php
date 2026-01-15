<?php
require_once __DIR__ . '/../email_config.php';
require_once __DIR__ . '/SimpleSMTP.php';

function send_email($to, $subject, $message, &$errorMsg = '') {
    if (SMTP_PASS === 'SUA_SENHA_AQUI') {
        $errorMsg = "SMTP Password not configured in email_config.php";
        return false;
    }

    $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
    $result = $smtp->send($to, $subject, $message);

    if (!$result) {
        $errorMsg = $smtp->getLastError();
    }

    return $result;
}

function send_admin_notification($subject, $message, &$errorMsg = '') {
    $to = "rhsilva198@gmail.com";
    return send_email($to, $subject, $message, $errorMsg);
}
?>