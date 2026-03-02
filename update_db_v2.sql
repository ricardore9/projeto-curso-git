-- Atualização V2 do banco de dados

-- 1. Adicionar status no compartilhamento (Pendente/Aceito)
-- Se a coluna já existir, isso pode dar erro, mas assumindo atualização sequencial.
-- O padrão é 'pending' para novos, mas 'accepted' para os já existentes para não quebrar compatibilidade.
ALTER TABLE list_shares ADD COLUMN status ENUM('pending', 'accepted') DEFAULT 'pending';
UPDATE list_shares SET status = 'accepted'; -- Define todos os antigos como aceitos

-- 2. Adicionar campo de observações na lista
ALTER TABLE shopping_lists ADD COLUMN notes TEXT;

-- 3. Criar tabela de comentários
CREATE TABLE IF NOT EXISTS list_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES shopping_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
