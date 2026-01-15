<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/email.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $departamento_id = $_POST['departamento_id'];

    // Novos campos de data/hora
    $data_evento = $_POST['data_evento'];
    $hora_inicio = $_POST['hora_inicio'];

    // Campos opcionais de término
    $data_termino = !empty($_POST['data_termino']) ? $_POST['data_termino'] : NULL;
    $hora_termino = !empty($_POST['hora_termino']) ? $_POST['hora_termino'] : NULL;

    $local_tipo = $_POST['local_tipo']; // 'igreja' or 'externo'
    $local_detalhe = trim($_POST['local_detalhe']);
    $precisa_midia = isset($_POST['precisa_midia']) ? 1 : 0;
    $apoio_ids = $_POST['apoio'] ?? []; // Array
    $observacoes = trim($_POST['observacoes']);

    // Validação Básica
    if (empty($titulo) || empty($data_evento) || empty($hora_inicio) || empty($local_tipo)) {
        $_SESSION['flash_message'] = "Preencha os campos obrigatórios.";
        $_SESSION['flash_type'] = "danger";
        header("Location: novo_evento.php");
        exit;
    }

    // Validação de Segurança: O usuário realmente pertence ao departamento principal?
    // Exceção: Admin pode criar para qualquer departamento
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

        // Inserir Evento com novas colunas
        $sql = "INSERT INTO eventos (titulo, data_evento, hora_inicio, data_termino, hora_termino, local_tipo, local_detalhe, precisa_midia, observacoes, status, criado_por_id, departamento_principal_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $titulo,
            $data_evento,
            $hora_inicio,
            $data_termino,
            $hora_termino,
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
                if ($apoio_dept_id != $departamento_id) {
                    $stmt_apoio->execute([$evento_id, $apoio_dept_id]);
                }
            }
        }

        $pdo->commit();

        // Notificar Admin
        $assunto = "Novo Evento Criado: " . $titulo;
        $msg = "<h3>Novo Evento Pendente</h3>";
        $msg .= "<p><strong>Evento:</strong> $titulo</p>";
        $msg .= "<p><strong>Data:</strong> " . date('d/m/Y', strtotime($data_evento)) . " às $hora_inicio</p>";
        $msg .= "<p><strong>Criado por:</strong> " . $_SESSION['user_name'] . "</p>";
        $msg .= "<p>Acesse o painel administrativo para aprovar.</p>";

        $email_enviado = send_admin_notification($assunto, $msg);

        if ($email_enviado) {
            $_SESSION['flash_message'] = "Evento cadastrado com sucesso! Aguardando aprovação. Notificação enviada ao admin.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Evento cadastrado, mas <strong>erro ao enviar email de notificação</strong>. Verifique se o SMTP está configurado.";
            $_SESSION['flash_type'] = "warning";
        }

        // Redireciona para o painel correto
        if ($_SESSION['user_type'] === 'admin') {
            header("Location: ../admin/eventos.php?filtro=pendente");
        } else {
            header("Location: dashboard.php");
        }
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