-- Projeto Repack - Migration 009: SKU Acumulador para Itens Promocionais / Multipack
-- Permite unificar o estoque e a vitrine da Lojinha no SKU principal enquanto preserva o B.O. original

-- 1. Adiciona a coluna produto_pai_id na tabela produtos
ALTER TABLE produtos ADD COLUMN IF NOT EXISTS produto_pai_id INT NULL DEFAULT NULL AFTER pacote_padrao;

-- 2. Cria índice e chave estrangeira auto-referencial
SET @existe_fk = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
      AND CONSTRAINT_NAME = 'fk_produtos_produto_pai'
);
SET @sql_fk = IF(@existe_fk = 0, 
    'ALTER TABLE produtos ADD CONSTRAINT fk_produtos_produto_pai FOREIGN KEY (produto_pai_id) REFERENCES produtos(id) ON DELETE SET NULL', 
    'SELECT 1'
);
PREPARE stmt_fk FROM @sql_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;

-- 3. Vinculação inicial dos pares clássicos conhecidos (Multipack -> SKU Principal)
-- Par 1: Skol Lata 350ml (34608 -> 9068)
UPDATE produtos p_filho
JOIN produtos p_pai ON p_pai.codigo = '9068'
SET p_filho.produto_pai_id = p_pai.id
WHERE p_filho.codigo = '34608';

-- Par 2: Brahma Chopp Lata 350ml (33820 -> 9069)
UPDATE produtos p_filho
JOIN produtos p_pai ON p_pai.codigo = '9069'
SET p_filho.produto_pai_id = p_pai.id
WHERE p_filho.codigo = '33820';

-- Par 3: Guaraná Antarctica Lata 350ml (34027 -> 9084)
UPDATE produtos p_filho
JOIN produtos p_pai ON p_pai.codigo = '9084'
SET p_filho.produto_pai_id = p_pai.id
WHERE p_filho.codigo = '34027';

-- Par 4: Guaraná Antarctica Zero/Diet Lata 350ml (34320 -> 9085)
UPDATE produtos p_filho
JOIN produtos p_pai ON p_pai.codigo = '9085'
SET p_filho.produto_pai_id = p_pai.id
WHERE p_filho.codigo = '34320';

-- 4. Consolidação de estoques preexistentes da lojinha (se houver saldo em produto_filho, transfere para produto_pai)
UPDATE estoque_lojinha el_pai
JOIN produtos p_filho ON p_filho.produto_pai_id = el_pai.produto_id
JOIN estoque_lojinha el_filho ON el_filho.produto_id = p_filho.id
SET el_pai.quantidade_total = el_pai.quantidade_total + el_filho.quantidade_total,
    el_filho.quantidade_total = 0
WHERE el_filho.quantidade_total > 0;
