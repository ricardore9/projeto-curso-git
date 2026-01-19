<?php
// Script de Migração Automática para corrigir o erro "Column not found"
require_once 'includes/db.php';

echo "<h1>Atualização do Banco de Dados</h1>";

try {
    // 1. Verificar se a coluna 'data_evento' já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM eventos LIKE 'data_evento'");
    $existe = $stmt->fetch();

    if ($existe) {
        echo "<div style='color:green; padding:10px; border:1px solid green; background:#edf7ed;'>";
        echo "<strong>Sucesso:</strong> O banco de dados já está atualizado. As novas colunas existem.";
        echo "</div>";
    } else {
        echo "<p>Atualizando estrutura da tabela 'eventos'...</p>";

        $pdo->beginTransaction();

        // 2. Adicionar novas colunas
        $sql1 = "ALTER TABLE eventos
                 ADD COLUMN data_evento DATE NOT NULL AFTER titulo,
                 ADD COLUMN hora_inicio TIME NOT NULL AFTER data_evento,
                 ADD COLUMN data_termino DATE DEFAULT NULL AFTER hora_inicio,
                 ADD COLUMN hora_termino TIME DEFAULT NULL AFTER data_termino";
        $pdo->exec($sql1);
        echo "<p> - Colunas criadas.</p>";

        // 3. Migrar dados antigos (se existirem)
        // Verifica se existem as colunas antigas antes de tentar ler
        $stmt_old = $pdo->query("SHOW COLUMNS FROM eventos LIKE 'inicio'");
        if ($stmt_old->fetch()) {
            $sql2 = "UPDATE eventos SET
                     data_evento = DATE(inicio),
                     hora_inicio = TIME(inicio),
                     data_termino = IF(fim IS NOT NULL, DATE(fim), NULL),
                     hora_termino = IF(fim IS NOT NULL, TIME(fim), NULL)";
            $pdo->exec($sql2);
            echo "<p> - Dados migrados.</p>";

            // 4. Remover colunas antigas
            $pdo->exec("ALTER TABLE eventos DROP COLUMN inicio");
            $pdo->exec("ALTER TABLE eventos DROP COLUMN fim");
            echo "<p> - Colunas antigas removidas.</p>";
        }

        // 5. Criar índice
        $pdo->exec("ALTER TABLE eventos ADD INDEX idx_data_evento (data_evento)");
        echo "<p> - Índices criados.</p>";

        $pdo->commit();

        echo "<div style='color:green; padding:10px; border:1px solid green; background:#edf7ed; margin-top:20px;'>";
        echo "<strong>Atualização Concluída!</strong> O erro deve ter sido resolvido. <a href='index.php'>Voltar ao Início</a>";
        echo "</div>";
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div style='color:red; padding:10px; border:1px solid red; background:#fdeded;'>";
    echo "<strong>Erro Crítico:</strong> " . $e->getMessage();
    echo "</div>";
}
?>