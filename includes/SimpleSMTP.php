<?php
class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $debug = false;
    private $lastError = '';

    public function __construct($host, $port, $user, $pass) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function send($to, $subject, $message, $fromName = "Agenda CESE") {
        $socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        if (!$socket) {
            $this->lastError = "Connection Error: $errstr ($errno)";
            error_log("SMTP Connect Error: $errstr ($errno)");
            return false;
        }

        $this->read($socket); // Welcome

        if (!$this->cmd($socket, "EHLO " . $_SERVER['SERVER_NAME'])) return false;

        // Auth
        if (!$this->cmd($socket, "AUTH LOGIN")) return false;
        if (!$this->cmd($socket, base64_encode($this->user))) return false;
        if (!$this->cmd($socket, base64_encode($this->pass))) return false;

        // Mail
        if (!$this->cmd($socket, "MAIL FROM: <" . $this->user . ">")) return false;
        if (!$this->cmd($socket, "RCPT TO: <" . $to . ">")) return false;
        if (!$this->cmd($socket, "DATA")) return false;

        // Headers & Body
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $fromName . " <" . $this->user . ">\r\n";
        $headers .= "To: <" . $to . ">\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "\r\n";
        $headers .= $message . "\r\n";
        $headers .= ".";

        if (!$this->cmd($socket, $headers)) return false;

        $this->cmd($socket, "QUIT");
        fclose($socket);
        return true;
    }

    private function cmd($socket, $cmd) {
        fwrite($socket, $cmd . "\r\n");
        $response = $this->read($socket);
        // Códigos de sucesso SMTP começam com 2 ou 3
        if (!preg_match('/^[23]/', $response)) {
            $this->lastError = "Command Failed: $cmd | Response: $response";
            error_log("SMTP Error: " . $this->lastError);
            return false;
        }
        return true;
    }

    private function read($socket) {
        $response = "";
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $response;
    }
}
?>