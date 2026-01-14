<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'covesa26_agendaCese');
define('DB_USER', 'covesa26_agendaCese');
define('DB_PASS', '@cese2209');

// Configurações Gerais
define('SITE_NAME', 'Agenda da CESE');

// Detecção automática da URL Base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);

// Remove pastas admin/leader do path se estiver rodando dentro delas
$scriptPath = str_replace(['/admin', '/leader'], '', $scriptPath);

// Garante que termina com barra
$baseUrl = $protocol . $domainName . rtrim($scriptPath, '/') . '/';

define('BASE_URL', $baseUrl);
?>