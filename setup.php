<?php
require_once 'includes/db.php';

echo "<h1>Configuração Inicial</h1>";

try {
    // 1. Criar Tabelas (Carregar do arquivo SQL se necessário, mas assumindo que o usuário rodou o SQL)
    // Vamos verificar se a tabela usuarios existe
    $pdo->query("SELECT 1 FROM usuarios LIMIT 1");
} catch (PDOException $e) {
    die("Erro: As tabelas não parecem existir. Por favor, importe o arquivo database.sql no seu banco de dados primeiro.<br>Erro original: " . $e->getMessage());
}

// 2. Criar Admin
$email = 'admin@igreja.com';
$senha = '123456';

$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo "<p style='color:orange'>O usuário Admin ($email) já existe.</p>";
} else {
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, status) VALUES (?, ?, ?, 'admin', 'ativo')");
    $stmt->execute(['Administrador', $email, $senha_hash]);
    echo "<p style='color:green'>Usuário Admin criado com sucesso!</p>";
    echo "<ul><li>Email: <strong>$email</strong></li><li>Senha: <strong>$senha</strong></li></ul>";
}

echo "<p>Por favor, apague este arquivo (setup.php) após o uso por segurança.</p>";
echo "<a href='login.php'>Ir para Login</a>";
?>