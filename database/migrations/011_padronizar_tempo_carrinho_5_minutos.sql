-- Migration 011: Padroniza tempo de reserva do carrinho em 5 minutos
ALTER TABLE lotes_venda MODIFY COLUMN tempo_carrinho_minutos INT NOT NULL DEFAULT 5;
UPDATE lotes_venda SET tempo_carrinho_minutos = 5 WHERE tempo_carrinho_minutos = 10;
