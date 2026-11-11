-- Projeto Repack - Migration 005: Configuração de Operadores Oficiais e Operador Padrão
-- Garante os 2 operadores ativos (Adriano Silva e Pedro Cruz) e tabela de configurações

CREATE TABLE IF NOT EXISTS configuracoes_sistema (
    chave VARCHAR(50) NOT NULL PRIMARY KEY,
    valor TEXT NOT NULL,
    descricao VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Desativa operadores legados de teste para deixar apenas os 2 oficiais ativos
UPDATE usuarios SET ativo = 0 WHERE perfil = 'operador';

-- Garante Adriano Silva como operador ativo
INSERT INTO usuarios (matricula, nome, senha_hash, perfil, setor, ativo)
VALUES ('OP01', 'Adriano Silva', NULL, 'operador', 'Repack', 1)
ON DUPLICATE KEY UPDATE nome = 'Adriano Silva', perfil = 'operador', setor = 'Repack', ativo = 1;

-- Garante Pedro Cruz como operador ativo
INSERT INTO usuarios (matricula, nome, senha_hash, perfil, setor, ativo)
VALUES ('OP02', 'Pedro Cruz', NULL, 'operador', 'Repack', 1)
ON DUPLICATE KEY UPDATE nome = 'Pedro Cruz', perfil = 'operador', setor = 'Repack', ativo = 1;

-- Grava configuração inicial de Operador Padrão (Adriano Silva)
INSERT INTO configuracoes_sistema (chave, valor, descricao)
VALUES ('operador_padrao_id', (SELECT id FROM usuarios WHERE matricula = 'OP01' LIMIT 1), 'ID do Operador Padrão da bancada de Repack')
ON DUPLICATE KEY UPDATE valor = (SELECT id FROM usuarios WHERE matricula = 'OP01' LIMIT 1);
