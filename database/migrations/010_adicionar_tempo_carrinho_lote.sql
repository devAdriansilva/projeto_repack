-- Migration 010: Adiciona coluna tempo_carrinho_minutos na tabela lotes_venda
-- Permite que cada lote de vendas defina dinamicamente o tempo de reserva do carrinho

ALTER TABLE lotes_venda 
ADD COLUMN tempo_carrinho_minutos INT NOT NULL DEFAULT 10 AFTER limite_itens_por_comprador;
