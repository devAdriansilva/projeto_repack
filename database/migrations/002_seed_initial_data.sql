-- Projeto Repack - Migration 002: Seed Inicial de Usuários e Produtos
-- Baseado estritamente em docs/FSD.md Seções 8, 10 e 11

-- Carga Inicial de Usuários
-- Senha do perfil 'gestao' (matricula GEST01): gestao123
INSERT INTO usuarios (matricula, nome, senha_hash, perfil, setor, ativo) VALUES
('GEST01', 'Coordenadora Ana Silva', '$2y$12$mYZXlmpcHTfnaID3bZVruuvbFRSzi/4zHrTRz4/4aA99vYdcm91Y.', 'gestao', 'Logística / Repack', 1),
('OP01', 'João Silva', NULL, 'operador', 'Repack', 1),
('OP02', 'Maria Santos', NULL, 'operador', 'Repack', 1),
('OP03', 'Pedro Oliveira', NULL, 'operador', 'Repack', 1),
('COLAB01', 'Carlos Ferreira', NULL, 'colaborador', 'Armazém', 1),
('COLAB02', 'Mariana Lima', NULL, 'colaborador', 'Transporte', 1),
('COLAB03', 'Roberto Costa', NULL, 'colaborador', 'Administrativo', 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Carga Inicial de Produtos
INSERT INTO produtos (codigo, nome, foto_url, pacote_padrao, ativo) VALUES
('PROD001', 'Cerveja Pilsen Lata 350ml', NULL, 'Unidade', 1),
('PROD002', 'Cerveja Puro Malte Long Neck 355ml', NULL, 'Unidade', 1),
('PROD003', 'Cerveja IPA Lata 473ml', NULL, 'Unidade', 1),
('PROD004', 'Refrigerante Guaraná 2L', NULL, 'Unidade', 1),
('PROD005', 'Refrigerante Cola 350ml', NULL, 'Fardo c/ 12', 1),
('PROD006', 'Água Mineral sem Gás 500ml', NULL, 'Fardo c/ 12', 1),
('PROD007', 'Energético Tropical 250ml', NULL, 'Unidade', 1),
('PROD008', 'Suco de Laranja Integral 1L', NULL, 'Caixa c/ 6', 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Inicialização do Estoque da Lojinha para os Produtos Cadastrados
INSERT IGNORE INTO estoque_lojinha (produto_id, quantidade_total, quantidade_reservada_gestao)
SELECT id, 0, 0 FROM produtos;
