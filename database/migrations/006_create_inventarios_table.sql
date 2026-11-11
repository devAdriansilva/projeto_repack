-- Projeto Repack - Migration 006: Tabela de Histórico de Inventários e Balanços de Estoque
-- Permite registrar balanços periódicos, inventário inicial e ajustes de contagem parcial

CREATE TABLE IF NOT EXISTS inventarios_estoque_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    saldo_anterior INT NOT NULL DEFAULT 0,
    novo_saldo INT NOT NULL DEFAULT 0,
    diferenca INT NOT NULL DEFAULT 0,
    tipo_operacao ENUM('substituicao', 'incremento') NOT NULL DEFAULT 'substituicao',
    detalhes_contagem VARCHAR(255) NULL, -- Ex: "12 + 6 + 12"
    motivo VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_inventario_produto (produto_id),
    INDEX idx_inventario_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
