-- Projeto Repack - Migration 009: Carga de Usuários Adicionais para Testes da Lojinha
-- Baseado em docs/FSD.md e no atendimento à solicitação de mais credenciais para testes

-- 1. Inserção de novos colaboradores e contas setoriais para testes
INSERT INTO usuarios (matricula, nome, senha_hash, perfil, setor, tipo_acesso_lojinha, ativo) VALUES
-- Colaboradores Comuns (Armazém, Transporte, Repack)
('COLAB04', 'Juliana Mendes', NULL, 'colaborador', 'Armazém', 'comum', 1),
('COLAB05', 'Lucas Souza', NULL, 'colaborador', 'Transporte', 'comum', 1),
('COLAB06', 'Beatriz Santos', NULL, 'colaborador', 'Repack', 'comum', 1),

-- Colaboradores Comuns (Outros Setores Operacionais / Administrativos)
('COLAB07', 'Fernando Dias', NULL, 'colaborador', 'Logística', 'comum', 1),
('COLAB08', 'Camila Rocha', NULL, 'colaborador', 'Qualidade', 'comum', 1),
('COLAB09', 'Rodrigo Alves', NULL, 'colaborador', 'Produção', 'comum', 1),
('COLAB10', 'Patricia Ramos', NULL, 'colaborador', 'Administrativo', 'comum', 1),

-- Contas Setoriais Adicionais (Acesso ilimitado via Lote Corporativo permanente)
('SEG01', 'Segurança do Trabalho', NULL, 'colaborador', 'Segurança', 'setorial', 1),
('DIRETORIA01', 'Diretoria Executiva', NULL, 'colaborador', 'Diretoria', 'setorial', 1)
ON DUPLICATE KEY UPDATE 
    nome = VALUES(nome),
    setor = VALUES(setor),
    tipo_acesso_lojinha = VALUES(tipo_acesso_lojinha),
    ativo = 1;

-- 2. Garantir um Lote de Venda Amplo e Aberto cobrindo todos os setores para testes da Lojinha
INSERT INTO lotes_venda (
    titulo, descricao, data_inicio, data_fim, setores_autorizados,
    limite_itens_por_comprador, status, criado_por_usuario_id, created_at, updated_at
)
SELECT 
    'Lote Geral de Testes - Todos os Setores',
    'Lote aberto com acesso amplo a todos os setores para validação e testes da vitrine da lojinha.',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 30 DAY),
    '["Armazém","Transporte","Repack","Logística","Qualidade","Produção","Administrativo","*"]',
    10,
    'aberto',
    u.id,
    NOW(),
    NOW()
FROM usuarios u
WHERE u.matricula = 'GEST01'
  AND NOT EXISTS (
      SELECT 1 FROM lotes_venda 
      WHERE titulo = 'Lote Geral de Testes - Todos os Setores' 
        AND status = 'aberto' 
        AND deleted_at IS NULL
  )
LIMIT 1;
