-- ==============================================================================
-- Migration 012: Adicionar coluna pin_hash para PIN de 4 dígitos de compradores
-- Projeto Repack - Baseado estritamente em docs/FSD.md e AGENTS.md
-- ==============================================================================

ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS pin_hash VARCHAR(255) NULL DEFAULT NULL AFTER senha_hash;
