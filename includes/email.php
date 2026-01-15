<?php
function send_admin_notification($subject, $message) {
    $to = "rhsilva198@gmail.com";

    // Configurar Headers para HTML e UTF-8
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@igrejacese.com.br" . "\r\n";

    // Tentar enviar
    // Nota: Em localhost sem SMTP configurado isso pode falhar ou ir para log,
    // mas em produção funcionará se o servidor permitir mail().
    @mail($to, $subject, $message, $headers);
}
?>