-- Projeto Repack - Migration 007: Ajuste de Perfis de Acesso à Lojinha e Contas Setoriais
-- Baseado nas regras de governança e no atendimento a demandas de setores corporativos

-- 1. Adição da coluna de perfil de acesso na Lojinha Virtual
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS tipo_acesso_lojinha ENUM('comum', 'setorial', 'master') NOT NULL DEFAULT 'comum' AFTER setor;

-- 2. Atualização da usuária Coordenadora (GEST01) para perfil Master
UPDATE usuarios 
SET tipo_acesso_lojinha = 'master' 
WHERE matricula = 'GEST01';

-- 3. Cadastro/Atualização das Contas Setoriais Institucionais
INSERT INTO usuarios (matricula, nome, senha_hash, perfil, setor, tipo_acesso_lojinha, ativo) VALUES
('ADM01', 'Setor Administrativo', NULL, 'colaborador', 'Administrativo', 'setorial', 1),
('RH01', 'Gente e Gestão (RH)', NULL, 'colaborador', 'Gente (RH)', 'setorial', 1),
('VENDAS01', 'Sala de Vendas', NULL, 'colaborador', 'Sala de Vendas', 'setorial', 1),
('FIN01', 'Setor Financeiro', NULL, 'colaborador', 'Financeiro', 'setorial', 1)
ON DUPLICATE KEY UPDATE 
    nome = VALUES(nome),
    setor = VALUES(setor),
    tipo_acesso_lojinha = VALUES(tipo_acesso_lojinha),
    ativo = 1;

-- 4. Criação do Lote Institucional / Setorial Permanente (para compras mesmo com a loja pública fechada)
INSERT INTO lotes_venda (
    titulo, descricao, data_inicio, data_fim, setores_autorizados,
    limite_itens_por_comprador, status, criado_por_usuario_id, created_at, updated_at
)
SELECT 
    'Lote Corporativo e Setorial',
    'Lote institucional contínuo para requisições de setores internos e gestão master.',
    '2026-01-01 00:00:00',
    '2099-12-31 23:59:59',
    '["*"]',
    9999,
    'aberto',
    u.id,
    NOW(),
    NOW()
FROM usuarios u
WHERE u.matricula = 'GEST01'
  AND NOT EXISTS (
      SELECT 1 FROM lotes_venda WHERE titulo = 'Lote Corporativo e Setorial' AND deleted_at IS NULL
  )
LIMIT 1;
