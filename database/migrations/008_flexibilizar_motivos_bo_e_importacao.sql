-- Projeto Repack - Migration 008: Flexibilização de Motivos de B.O. e Tabela de Importações CSV
-- Permite novos motivos de avaria externos e auditoria de planilhas importadas

-- 1. Flexibilização da coluna tipo_avaria para aceitar novos motivos textuais
ALTER TABLE apontamentos_bo MODIFY COLUMN tipo_avaria VARCHAR(100) NOT NULL;

-- 2. Adição de coluna para armazenar o motivo textual original vindo de sistemas legados/CSV
ALTER TABLE apontamentos_bo ADD COLUMN IF NOT EXISTS motivo_original VARCHAR(255) NULL AFTER tipo_avaria;

-- 3. Adição de coluna para registrar o nome do arquivo ou lote de importação
ALTER TABLE apontamentos_bo ADD COLUMN IF NOT EXISTS arquivo_origem VARCHAR(255) NULL AFTER motivo_correcao;

-- 4. Tabela de histórico e auditoria de importações de arquivos CSV
CREATE TABLE IF NOT EXISTS importacoes_csv_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_arquivo VARCHAR(255) NOT NULL,
    hash_conteudo VARCHAR(64) NOT NULL,
    total_linhas INT NOT NULL DEFAULT 0,
    unidades_lojinha INT NOT NULL DEFAULT 0,
    unidades_descarte INT NOT NULL DEFAULT 0,
    usuario_id INT NOT NULL,
    detalhes_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_importacoes_hash (hash_conteudo),
    INDEX idx_importacoes_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
