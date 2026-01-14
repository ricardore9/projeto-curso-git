<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $departamento_id = $_POST['departamento_id'];
    $inicio = $_POST['inicio'];
    $fim = !empty($_POST['fim']) ? $_POST['fim'] : NULL;
    $local_tipo = $_POST['local_tipo']; // 'igreja' or 'externo'
    $local_detalhe = trim($_POST['local_detalhe']);
    $precisa_midia = isset($_POST['precisa_midia']) ? 1 : 0;
    $apoio_ids = $_POST['apoio'] ?? []; // Array
    $observacoes = trim($_POST['observacoes']);

    // Validação Básica
    if (empty($titulo) || empty($inicio) || empty($local_tipo)) {
        $_SESSION['flash_message'] = "Preencha os campos obrigatórios.";
        $_SESSION['flash_type'] = "danger";
        header("Location: novo_evento.php");
        exit;
    }

    // Validação de Segurança: O usuário realmente pertence ao departamento principal?
    // Exceção: Admin pode tudo, mas aqui focamos no líder.
    if ($_SESSION['user_type'] !== 'admin') {
        $stmt = $pdo->prepare("SELECT 1 FROM usuario_departamentos WHERE usuario_id = ? AND departamento_id = ?");
        $stmt->execute([$_SESSION['user_id'], $departamento_id]);
        if (!$stmt->fetch()) {
            $_SESSION['flash_message'] = "Você não tem permissão para criar eventos para este departamento.";
            $_SESSION['flash_type'] = "danger";
            header("Location: novo_evento.php");
            exit;
        }
    }

    try {
        $pdo->beginTransaction();

        // Inserir Evento
        $sql = "INSERT INTO eventos (titulo, inicio, fim, local_tipo, local_detalhe, precisa_midia, observacoes, status, criado_por_id, departamento_principal_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $titulo,
            $inicio,
            $fim,
            $local_tipo,
            $local_detalhe,
            $precisa_midia,
            $observacoes,
            $_SESSION['user_id'],
            $departamento_id
        ]);

        $evento_id = $pdo->lastInsertId();

        // Inserir Apoio
        if (!empty($apoio_ids)) {
            $stmt_apoio = $pdo->prepare("INSERT INTO evento_apoio (evento_id, departamento_id) VALUES (?, ?)");
            foreach ($apoio_ids as $apoio_dept_id) {
                // Evitar duplicidade se o usuário selecionou o mesmo departamento principal como apoio
                if ($apoio_dept_id != $departamento_id) {
                    $stmt_apoio->execute([$evento_id, $apoio_dept_id]);
                }
            }
        }

        $pdo->commit();

        $_SESSION['flash_message'] = "Evento cadastrado com sucesso! Aguardando aprovação.";
        $_SESSION['flash_type'] = "success";
        header("Location: dashboard.php");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = "Erro ao salvar evento: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
        header("Location: novo_evento.php");
        exit;
    }

} else {
    header("Location: dashboard.php");
    exit;
}
?>