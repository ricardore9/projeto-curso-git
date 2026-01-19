<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evento_id = filter_input(INPUT_POST, 'evento_id', FILTER_VALIDATE_INT);
    $titulo = trim($_POST['titulo']);
    $departamento_id = $_POST['departamento_id'];

    $data_evento = $_POST['data_evento'];
    $hora_inicio = $_POST['hora_inicio'];

    $data_termino = !empty($_POST['data_termino']) ? $_POST['data_termino'] : NULL;
    $hora_termino = !empty($_POST['hora_termino']) ? $_POST['hora_termino'] : NULL;

    $local_tipo = $_POST['local_tipo'];
    $local_detalhe = trim($_POST['local_detalhe']);
    $precisa_midia = isset($_POST['precisa_midia']) ? 1 : 0;
    $apoio_ids = $_POST['apoio'] ?? [];
    $observacoes = trim($_POST['observacoes']);

    if (!$evento_id || empty($titulo) || empty($data_evento) || empty($hora_inicio)) {
        $_SESSION['flash_message'] = "Preencha os campos obrigatórios.";
        $_SESSION['flash_type'] = "danger";
        header("Location: editar_evento.php?id=" . $evento_id);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Atualizar Evento
        $sql = "UPDATE eventos SET
                titulo = ?,
                data_evento = ?,
                hora_inicio = ?,
                data_termino = ?,
                hora_termino = ?,
                local_tipo = ?,
                local_detalhe = ?,
                precisa_midia = ?,
                observacoes = ?,
                departamento_principal_id = ?
                WHERE id = ?";

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
            $departamento_id,
            $evento_id
        ]);

        // Atualizar Apoio (Remover todos e inserir novamente)
        $pdo->prepare("DELETE FROM evento_apoio WHERE evento_id = ?")->execute([$evento_id]);

        if (!empty($apoio_ids)) {
            $stmt_apoio = $pdo->prepare("INSERT INTO evento_apoio (evento_id, departamento_id) VALUES (?, ?)");
            foreach ($apoio_ids as $apoio_dept_id) {
                if ($apoio_dept_id != $departamento_id) {
                    $stmt_apoio->execute([$evento_id, $apoio_dept_id]);
                }
            }
        }

        $pdo->commit();

        $_SESSION['flash_message'] = "Evento atualizado com sucesso!";
        $_SESSION['flash_type'] = "success";
        header("Location: eventos.php");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = "Erro ao atualizar evento: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
        header("Location: editar_evento.php?id=" . $evento_id);
        exit;
    }

} else {
    header("Location: eventos.php");
    exit;
}
?>