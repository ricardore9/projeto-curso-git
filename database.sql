-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'lider') NOT NULL DEFAULT 'lider',
    status ENUM('pendente', 'ativo') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Departamentos
CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Associação: Usuário pode liderar vários departamentos
CREATE TABLE IF NOT EXISTS usuario_departamentos (
    usuario_id INT NOT NULL,
    departamento_id INT NOT NULL,
    PRIMARY KEY (usuario_id, departamento_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Eventos
CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    inicio DATETIME NOT NULL,
    fim DATETIME DEFAULT NULL,
    local_tipo ENUM('igreja', 'externo') NOT NULL,
    local_detalhe VARCHAR(255),
    precisa_midia TINYINT(1) DEFAULT 0,
    observacoes TEXT,
    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    criado_por_id INT NOT NULL,
    departamento_principal_id INT NOT NULL, -- O departamento "dono" do evento
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (departamento_principal_id) REFERENCES departamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Apoio: Eventos podem ter apoio de outros departamentos
CREATE TABLE IF NOT EXISTS evento_apoio (
    evento_id INT NOT NULL,
    departamento_id INT NOT NULL,
    PRIMARY KEY (evento_id, departamento_id),
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserção de Departamentos Iniciais (Exemplos)
INSERT INTO departamentos (nome) VALUES
('Mídia'),
('Louvor'),
('Infantil'),
('Jovens'),
('Mulheres'),
('Homens'),
('Missões'),
('Escola Bíblica'),
('Diaconia')
ON DUPLICATE KEY UPDATE nome=nome;
