-- Atualização de Schema para Datas Separadas

-- 1. Adicionar novas colunas
ALTER TABLE eventos ADD COLUMN data_evento DATE NOT NULL AFTER titulo;
ALTER TABLE eventos ADD COLUMN hora_inicio TIME NOT NULL AFTER data_evento;
ALTER TABLE eventos ADD COLUMN data_termino DATE DEFAULT NULL AFTER hora_inicio;
ALTER TABLE eventos ADD COLUMN hora_termino TIME DEFAULT NULL AFTER data_termino;

-- 2. Migrar dados existentes (se houver)
UPDATE eventos SET
    data_evento = DATE(inicio),
    hora_inicio = TIME(inicio),
    data_termino = IF(fim IS NOT NULL, DATE(fim), NULL),
    hora_termino = IF(fim IS NOT NULL, TIME(fim), NULL);

-- 3. Remover colunas antigas
ALTER TABLE eventos DROP COLUMN inicio;
ALTER TABLE eventos DROP COLUMN fim;

-- 4. Criar índice para performance em buscas por data
ALTER TABLE eventos ADD INDEX idx_data_evento (data_evento);
