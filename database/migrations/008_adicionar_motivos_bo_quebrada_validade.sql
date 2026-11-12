-- ==============================================================================
-- Migration 008: Adicionar motivos 'quebrada' e 'validade' nos apontamentos de B.O.
-- Projeto Repack - Modo Manutenção v1.49
-- ==============================================================================

-- 1. Expande o ENUM de tipo_avaria na tabela apontamentos_bo
ALTER TABLE apontamentos_bo 
MODIFY COLUMN tipo_avaria ENUM(
    'amassada', 
    'def_rotulo', 
    'emba_danificada', 
    'furo', 
    'micro_furo', 
    'quebrada', 
    'validade'
) NOT NULL;
