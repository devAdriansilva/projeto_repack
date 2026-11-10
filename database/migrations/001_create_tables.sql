-- Projeto Repack - Migration 001: Criação Estrutural de Tabelas
-- Baseado estritamente em docs/FSD.md Seção 11.2

-- 1. Tabela de Controle de Migrations
CREATE TABLE IF NOT EXISTS migrations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(150) NOT NULL UNIQUE,
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    senha_hash VARCHAR(255) NULL, -- Obrigatória para perfil 'gestao'
    perfil ENUM('operador', 'gestao', 'colaborador') NOT NULL DEFAULT 'colaborador',
    setor VARCHAR(100) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_usuarios_matricula (matricula),
    INDEX idx_usuarios_setor (setor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de Produtos
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    foto_url VARCHAR(255) NULL,
    pacote_padrao VARCHAR(50) NOT NULL DEFAULT 'Unidade',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_produtos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabela de Apontamentos de B.O. (Avarias)
CREATE TABLE IF NOT EXISTS apontamentos_bo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo_avaria ENUM('amassada', 'def_rotulo', 'emba_danificada', 'furo', 'micro_furo') NOT NULL,
    quantidade INT NOT NULL,
    destino ENUM('lojinha', 'descarte') NOT NULL,
    origem_sincronizacao ENUM('online', 'offline') NOT NULL DEFAULT 'online',
    motivo_correcao TEXT NULL,
    corrigido_por_usuario_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (corrigido_por_usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_bo_produto (produto_id),
    INDEX idx_bo_data (created_at),
    INDEX idx_bo_destino (destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabela de Apontamentos de Produtividade (Cronômetro)
CREATE TABLE IF NOT EXISTS apontamentos_produtividade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_hora_inicio DATETIME NOT NULL,
    data_hora_fim DATETIME NOT NULL,
    pacotes_refeitos INT NOT NULL,
    duracao_segundos INT NOT NULL,
    origem_sincronizacao ENUM('online', 'offline') NOT NULL DEFAULT 'online',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_produtividade_usuario (usuario_id),
    INDEX idx_produtividade_data (data_hora_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabela de Lotes de Venda
CREATE TABLE IF NOT EXISTS lotes_venda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(120) NOT NULL,
    descricao TEXT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    setores_autorizados TEXT NOT NULL, -- Lista formatada ou JSON com setores aptos
    limite_itens_por_comprador INT NOT NULL DEFAULT 5,
    status ENUM('planejado', 'aberto', 'encerrado') NOT NULL DEFAULT 'planejado',
    criado_por_usuario_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (criado_por_usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_lotes_datas (data_inicio, data_fim),
    INDEX idx_lotes_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabela de Saldo de Estoque da Lojinha
CREATE TABLE IF NOT EXISTS estoque_lojinha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL UNIQUE,
    quantidade_total INT NOT NULL DEFAULT 0,
    quantidade_reservada_gestao INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabela de Reservas Manuais da Gestão (Estoque Oculto)
CREATE TABLE IF NOT EXISTS reservas_gestao_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    quantidade INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabela de Baixas Administrativas (Perdas na Sala de Espera)
CREATE TABLE IF NOT EXISTS baixas_administrativas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    quantidade INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tabela de Carrinho Temporário (Trava de Concorrência)
CREATE TABLE IF NOT EXISTS carrinho_temporario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lote_id INT NOT NULL,
    usuario_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    expiracao_em DATETIME NOT NULL,
    session_token VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lote_id) REFERENCES lotes_venda(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    INDEX idx_carrinho_expiracao (expiracao_em),
    INDEX idx_carrinho_usuario_lote (usuario_id, lote_id),
    INDEX idx_carrinho_produto_expiracao (produto_id, expiracao_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Tabela de Pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_pedido VARCHAR(30) NOT NULL UNIQUE,
    lote_id INT NOT NULL,
    usuario_id INT NOT NULL,
    quantidade_total INT NOT NULL,
    status ENUM('confirmado', 'separado', 'entregue', 'cancelado') NOT NULL DEFAULT 'confirmado',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (lote_id) REFERENCES lotes_venda(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_pedidos_lote (lote_id),
    INDEX idx_pedidos_usuario (usuario_id),
    INDEX idx_pedidos_usuario_lote_status (usuario_id, lote_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Tabela de Itens do Pedido
CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Tabela de Logs do Sistema
CREATE TABLE IF NOT EXISTS logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nivel ENUM('INFO', 'WARNING', 'ERROR', 'SECURITY') NOT NULL,
    mensagem TEXT NOT NULL,
    contexto_json JSON NULL,
    ip VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_nivel (nivel),
    INDEX idx_logs_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
