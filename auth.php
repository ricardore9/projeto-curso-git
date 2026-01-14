<?php
session_start();
require_once 'includes/db.php';

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $nome = trim($_POST['nome']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];
    $departamentos = $_POST['departamentos'] ?? [];

    if ($senha !== $confirma_senha) {
        $_SESSION['flash_message'] = "As senhas não conferem.";
        $_SESSION['flash_type'] = "danger";
        header("Location: register.php");
        exit;
    }

    if (empty($departamentos)) {
        $_SESSION['flash_message'] = "Selecione pelo menos um departamento.";
        $_SESSION['flash_type'] = "danger";
        header("Location: register.php");
        exit;
    }

    try {
        // Verificar se email ou username já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $_SESSION['flash_message'] = "E-mail ou Usuário já cadastrado.";
            $_SESSION['flash_type'] = "danger";
            header("Location: register.php");
            exit;
        }

        $pdo->beginTransaction();

        // Inserir usuário
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        // Status padrão 'pendente'
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, username, email, senha, tipo, status) VALUES (?, ?, ?, ?, 'lider', 'pendente')");
        $stmt->execute([$nome, $username, $email, $senha_hash]);
        $user_id = $pdo->lastInsertId();

        // Inserir departamentos
        $stmt_dept = $pdo->prepare("INSERT INTO usuario_departamentos (usuario_id, departamento_id) VALUES (?, ?)");
        foreach ($departamentos as $dept_id) {
            $stmt_dept->execute([$user_id, $dept_id]);
        }

        $pdo->commit();

        $_SESSION['flash_message'] = "Cadastro realizado com sucesso! Aguarde a aprovação do administrador.";
        $_SESSION['flash_type'] = "success";
        header("Location: login.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = "Erro ao cadastrar: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
        header("Location: register.php");
        exit;
    }

} elseif ($action === 'login') {
    $username = trim($_POST['username']); // Agora usamos username
    $senha = $_POST['senha'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            if ($user['status'] !== 'ativo') {
                $_SESSION['flash_message'] = "Seu cadastro ainda está pendente de aprovação.";
                $_SESSION['flash_type'] = "warning";
                header("Location: login.php");
                exit;
            }

            // Login Sucesso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_type'] = $user['tipo'];

            // Redirecionamento
            if ($user['tipo'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: leader/dashboard.php");
            }
            exit;

        } else {
            $_SESSION['flash_message'] = "Usuário ou senha inválidos.";
            $_SESSION['flash_type'] = "danger";
            header("Location: login.php");
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Erro no servidor.";
        $_SESSION['flash_type'] = "danger";
        header("Location: login.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>