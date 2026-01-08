-- Atualização segura do banco de dados (Sem perder dados)

-- 1. Adicionar a coluna 'email' na tabela 'users' caso ela não exista
-- Como o MySQL não tem um "IF NOT EXISTS" direto para colunas em versões antigas,
-- rodamos o comando. Se a coluna já existir, ele dará um erro inofensivo que pode ser ignorado,
-- ou você pode verificar visualmente antes.
ALTER TABLE users ADD COLUMN email VARCHAR(100) NOT NULL UNIQUE AFTER username;

-- 2. Criar a tabela de recuperação de senha (apenas se não existir)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
