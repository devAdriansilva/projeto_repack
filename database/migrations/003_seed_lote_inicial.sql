-- Projeto Repack - Migration 003: Seed Lote Inicial de Vendas e Saldo Inicial de Vitrine
-- Garante a operação da Lojinha de Saldos e fluxo ponta a ponta dos Colaboradores

INSERT INTO lotes_venda (
    titulo, descricao, data_inicio, data_fim, setores_autorizados,
    limite_itens_por_comprador, status, criado_por_usuario_id, created_at, updated_at
) VALUES (
    'Lote Especial de Inauguração',
    'Avarias recuperadas da bancada de repack com descontos corporativos exclusivos.',
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    DATE_ADD(NOW(), INTERVAL 30 DAY),
    '["Armazém", "Transporte", "Administrativo", "Repack", "Logística"]',
    5,
    'aberto',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE status = 'aberto';

-- Inicializa estoque para os produtos cadastrados permitindo compras de teste imediatas
UPDATE estoque_lojinha SET quantidade_total = 15 WHERE quantidade_total < 15;
