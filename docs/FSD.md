# DOCUMENTO DE ESPECIFICAÇÃO FUNCIONAL (FSD)

## 1. Visão Geral
O **Projeto Repack** é um sistema web responsivo e Progressive Web App (PWA) de uso interno voltado à operação logística do setor de reembalagem (Repack) de uma distribuidora de bebidas. O sistema tem como objetivo erradicar formulários manuais em papel e centralizar três rotinas essenciais:
1. **Lançamento de Boletins de Ocorrência (B.O.) de Avarias com Roteamento Inteligente:** segregação imediata entre produtos passíveis de recuperação para venda interna e perdas absolutas para descarte.
2. **Apontamento de Produtividade Operacional (Cronômetro):** registro do tempo de manuseio e da volumetria de pacotes reembalados pelos operadores da bancada.
3. **Gestão e Vitrine da Lojinha Interna:** controle autônomo do estoque de avarias leves, abertura de lotes de venda restritos por setor e limite de itens por colaborador, bloqueio temporário de itens em carrinho e geração de listas físicas de separação.

O sistema opera sob a **Filosofia da Urna Eletrônica**: interfaces de alto contraste, botões gigantes com alvos de toque de no mínimo 80px, navegação linear passo a passo ("um dado por vez") e etapa obrigatória de conferência ("Corrigir" ou "Confirmar"), mitigando erros operacionais e garantindo usabilidade a colaboradores com luvas de proteção anti-corte e baixa familiaridade tecnológica.

---

## 2. Documentos do Projeto para Implementação (Apenas docs/FSD.md e docs/DESIGN.md)
A implementação do projeto deve ser guiada estritamente por dois documentos normativos presentes no repositório:
- `docs/FSD.md`: Documento de Especificação Funcional que consolida regras de negócio, arquitetura, esquema relacional, migrations, fluxos de dados, segurança e infraestrutura.
- `docs/DESIGN.md`: Especificação do Design System, tokens de cores, tipografia, espaçamentos, regras de toque de 80px e estados de componentes visuais.

Nenhum outro documento ou rascunho anterior deve ser consultado ou tomado como referência durante a codificação.

---

## 3. Stack Definida
- **Backend:** PHP puro (versão 8.1 ou superior), sem frameworks pesados, estruturado sob o padrão arquitetural MVC (Model-View-Controller).
- **Banco de Dados:** MySQL (versão 8.0 ou MariaDB 10.4+), utilizando a extensão PDO com Prepared Statements nativos para todas as operações.
- **Frontend:** HTML5 semântico, CSS3 moderno com Bootstrap 5.3 (arquivos CSS e JS hospedados localmente na aplicação, sem dependência de CDNs externas) e JavaScript Vanilla puro (ES6+).
- **Estratégia Offline-First / PWA:**
  - `manifest.json` com configurações de exibição `standalone`, ícones e tema.
  - `Service Worker` nativo para cache completo de assets estáticos (HTML, CSS, JS, fontes locais e ícones SVG).
  - `IndexedDB` e `localStorage` no navegador do operador para gravação assíncrona local de B.O.s e registros de produtividade quando houver ausência de sinal de rede, com sincronização em lote automática via Background Fetch / Ajax ao restabelecer a conexão.
- **Manipulação de Toque:** Adoção de CSS `touch-action: manipulation` e listeners `touchstart` e `pointerdown` sem delay de 300ms, otimizando o acionamento via luvas anti-corte.

---

## 4. Ambientes do Projeto
- **Raiz Estrutural do Repositório:** `[Diretório do Projeto - Repositório]`.
- **Ambiente de Desenvolvimento Local:**
  - Pacote XAMPP / WampServer (Apache 2.4+, PHP 8.1+, MySQL 8.0+).
  - Acesso local via `http://localhost/projeto_repack` ou Virtual Host local configurado.
  - Banco de dados local: `repack_db`.
- **Ambiente de Homologação:** Não haverá ambiente em nuvem intermediário; a validação funcional e os testes automatizados/manuais de aceitação ocorrem diretamente no ambiente de desenvolvimento local.
- **Ambiente de Produção:**
  - Servidor de hospedagem Linux padrão (Apache/Nginx) com suporte a PHP 8.1+ e MySQL 8.0+.
  - Subdiretório ou raiz de domínio dedicado da intranet corporativa.
  - Estrutura de diretórios organizada de modo que arquivos sensíveis de configuração (`config/config.php`), controllers, models e migrations fiquem fora da exposição pública direta ou protegidos contra leitura direta via diretivas `.htaccess` ou configuração do servidor web.

---

## 5. Arquitetura do Sistema
A arquitetura baseia-se no padrão **MVC (Model-View-Controller)** puro em PHP, dividido em três camadas lógicas bem delimitadas:

```
[Diretório do Projeto - Repositório]/
│
├── app/
│   ├── config/
│   │   ├── config.php            # Configurações de banco, caminhos e constantes
│   │   └── database.php          # Singleton de conexão PDO com tratamento de falhas
│   │
│   ├── controllers/
│   │   ├── AuthController.php    # Autenticação de Gestão e identificação de Comprador
│   │   ├── AvariaController.php  # Roteamento de B.O., gravação e sincronização
│   │   ├── ProdutividadeController.php # Início, fim e persistência de tempos
│   │   ├── LojinhaController.php # Vitrine, carrinho temporário e checkout interno
│   │   ├── GestaoController.php  # Painel administrativo, lotes, baixas, reservas e produtos
│   │   └── RelatorioController.php # Listas de separação e extratos de esforço
│   │
│   ├── models/
│   │   ├── Usuario.php
│   │   ├── Produto.php
│   │   ├── ApontamentoBO.php
│   │   ├── ApontamentoProdutividade.php
│   │   ├── LoteVenda.php
│   │   ├── EstoqueLojinha.php
│   │   ├── Pedido.php
│   │   └── LogSistema.php
│   │
│   └── views/
│       ├── layouts/
│       │   ├── header.php
│       │   └── footer.php
│       ├── operador/
│       │   ├── home.php          # Urna Eletrônica (Grandes botões + Seletor de operador + Engrenagem oculta)
│       │   ├── avaria_passo1.php # Seleção de produto
│       │   ├── avaria_passo2.php # Tipo de avaria (5 opções)
│       │   ├── avaria_resumo.php # Tela Corrigir / Confirmar
│       │   ├── cronometro.php    # Contagem de tempo e finalização de pacotes
│       │   └── cronometro_resumo.php
│       ├── lojinha/
│       │   ├── vitrine.php       # Catálogo de produtos com fotos
│       │   ├── carrinho.php      # Carrinho com contagem regressiva de trava
│       │   └── sucesso.php       # Comprovante do pedido
│       └── gestao/
│           ├── dashboard.php     # Visão consolidada
│           ├── correcao_bo.php   # Ajuste de lançamentos
│           ├── lotes.php         # Abertura e regras de lote por setor
│           ├── estoque.php       # Controle de saldos, reservas e baixas manuais
│           ├── produtos.php      # Cadastro e edição de produtos com fotos
│           └── separacao.php     # Lista de separação física
│
├── database/
│   ├── migrations/
│   │   ├── 001_create_tables.sql
│   │   └── 002_seed_initial_data.sql
│   └── migrator.php              # Script CLI de execução idempotente de migrations
│
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── bootstrap.min.css # Bootstrap local
│   │   │   └── urna.css          # Estilos do DESIGN.md (alvo de 80px, cores, tipografia)
│   │   ├── js/
│   │   │   ├── bootstrap.bundle.min.js
│   │   │   ├── app-offline.js    # Service Worker, IndexedDB e fila de sincronização
│   │   │   └── touch-handler.js  # Otimizador de toque para luvas anti-corte
│   │   ├── img/                  # Logotipos, ícones SVG e fotos padrão
│   │   └── uploads/              # Imagens de produtos cadastradas pela gestão
│   ├── service-worker.js
│   ├── manifest.json
│   └── index.php                 # Front Controller e roteador da aplicação
│
├── logs/
│   ├── app_errors.log            # Fallback de logs do sistema em arquivo
│   └── security.log              # Tentativas de acesso indevido à área restrita
│
└── docs/
    ├── FSD.md
    └── DESIGN.md
```

---

## 6. Escopo Funcional da Primeira Versão
1. **Lançamento Operacional de Avaria (B.O.) com Roteamento Invisível:**
   - Seleção simplificada do produto manipulado.
   - Digitação facilitada ou seletor numérico grande de quantidade avariada.
   - Escolha obrigatória entre os 5 tipos padronizados: "Amassada", "Def. Rótulo", "Emba. Danificada", "Furo" ou "Micro Furo".
   - Regra de Destinação Automática: itens categorizados como "Amassada", "Def. Rótulo" ou "Emba. Danificada" são creditados no saldo da Lojinha Interna; itens com "Furo" ou "Micro Furo" são debitados como descarte irreversível.
   - Tela de confirmação dupla ("Corrigir" em laranja, "Confirmar" em verde) antes da gravação definitiva.
2. **Apontamento de Produtividade Operacional (Cronômetro):**
   - Escolha do produto a ser retrabalhado.
   - Botão "Iniciar": registra o timestamp de início no frontend e armazena o estado ativo.
   - Durante a operação: exibição contínua do tempo decorrido em tipografia destacada (`headline-xl`).
   - Botão "Finalizar": abre campo numérico gigante para digitação dos pacotes reembalados finalizados.
   - Tela de conferência final com opção de correção de quantidade ou gravação definitiva do ciclo.
3. **Vitrine e E-commerce Interno (Lojinha dos Colaboradores):**
   - Acesso móvel e desktop orientado por Matrícula e Setor do Colaborador.
   - Exibição de cards visuais contendo foto do produto, descrição e quantidade disponível.
   - Respeito estrito aos lotes abertos para o setor do comprador e ao teto máximo de unidades permitido por pessoa.
   - Bloqueio temporário no carrinho: ao adicionar um produto, a unidade é reservada e removida da visão pública dos demais compradores por 10 minutos (tempo limite de expiração).
   - Checkout simplificado com emissão de número de pedido e comprovante digital.
4. **Painel de Gestão e Administração Oculto:**
   - Botão discreto em forma de engrenagem cinza clara (`#D1D5DB`) na tela do operador, requerendo autenticação obrigatória (usuário gestor e senha).
   - Módulo de Correção de B.O.: retificação de apontamentos incorretos realizados pelo operador leigo.
   - Módulo de Lotes de Venda: agendamento de aberturas de lotes com vigência (data/hora início e fim), seleção de setores autorizados e definição do teto de compras individual.
   - Módulo de Reserva de Estoque (Estoque Oculto): fixação de quantitativo travado pela gestão que não é exibido nem vendido na vitrine.
   - Módulo de Baixas Administrativas: registro de avarias tardias, vencimentos ou quebras na sala de espera da lojinha, com justificativa e abatimento direto de saldo.
   - Módulo de Produtos: cadastro e edição de produtos, especificação de embalagem padrão e upload de fotos com validações de segurança.
   - Módulo de Separação de Pedidos: emissão da listagem analítica consolidando matrícula, nome, produtos e quantidades para separação física imediata no galpão.

---

## 7. Fora de Escopo
Ficam expressamente vetados e excluídos da arquitetura:
1. Módulos de cobrança, integração financeira ou recebimento de pagamentos digitais (como PIX, Gateway de Cartão de Crédito/Débito, Boleto ou maquininhas).
2. Integrações por API, Webhooks ou rotinas automatizadas com sistemas legados externos (ERP corporativo, Folha de Pagamento, Contabilidade ou Estoque Central da matriz).
3. Botões, tabelas, modais ou links de edição, alteração ou exclusão visíveis na interface do Operador Leigo.
4. Recursos de auto-cadastro externo de novos funcionários ou recuperação pública de senhas via e-mail corporativo.

---

## 8. Perfis de Usuário e Permissões

| Perfil | Identificador Técnico | Permissões de Acesso | Restrições Rígidas |
| :--- | :--- | :--- | :--- |
| **Operador de Repack** | `operador` | Acesso exclusivo à Home "Urna Eletrônica": botões "Registrar Avaria" e "Registrar Produção", e seletor rápido de operador em turno. Inserção de dados com dupla confirmação e gravação offline. | Proibido editar, visualizar relatórios, listar históricos anteriores ou excluir qualquer registro. Sem acesso ao painel de gestão. |
| **Gestão (Coordenadora)** | `gestao` | Acesso total após autenticação na engrenagem: correção de B.O.s, gestão de lotes e setores, controle de reservas ocultas, baixas administrativas, catálogo de produtos e fotos, relatórios e lista de separação. | Não executa integrações externas ou operações financeiras. |
| **Colaborador (Comprador)** | `colaborador` | Acesso à vitrine da lojinha mediante informe de matrícula e setor durante o período de lote aberto compatível com seu setor; adição de itens à sacola até o teto estipulado e confirmação de pedido. | Proibido acessar o catálogo fora da janela do lote ou além do limite de itens; sem acesso às telas operacionais ou de gestão. |

---

## 9. Recursos Estruturais do Sistema
- **PWA e Sincronização Local:** Aplicação dotada de `Service Worker` e banco local `IndexedDB`. Os botões operacionais operam normalmente mesmo com oscilações da intranet do galpão logístico. Ao reconectar, as filas locais despacham os apontamentos de B.O. e de produtividade para o backend PHP via endpoints `/api/bo/sincronizar.php` e `/api/produtividade/sincronizar.php`.
- **Prevenção de Toque Fantasma / Múltiplo Envio:** Bloqueio visual imediato do botão (`disabled` + indicador de loading) ao primeiro clique ou toque para evitar duplicações de registros.
- **Auditoria Transacional:** Rastreamento de autoria, data de criação e data de alteração em todas as tabelas transacionais (`created_at`, `updated_at`, `usuario_id`).
- **Reserva Matemática Garantida:** O cálculo de saldo público de estoque para a vitrine segue a fórmula obrigatória:
  $$\text{Disponível} = \text{Estoque Total} - \text{Reserva Oculta} - \text{Bloqueios em Carrinho Ativo}$$

---

## 10. Entidades do Sistema
1. **Usuario:** Funcionários da distribuidora (Operadores, Gestão e Compradores gerais).
2. **Produto:** Catálogo de bebidas e embalagens tratadas na reembalagem.
3. **ApontamentoBO:** Registro individual de perdas e avarias identificadas na triagem.
4. **ApontamentoProdutividade:** Registro de horas trabalhadas e pacotes montados na bancada de repack.
5. **LoteVenda:** Ciclo de liberação da lojinha com regras de horário, setores e limites.
6. **EstoqueLojinha:** Saldo consolidado de produtos elegíveis para venda interna.
7. **ReservaGestaoHistorico:** Histórico de bloqueios manuais preventivos efetuados pela coordenadora.
8. **BaixaAdministrativa:** Registro de perdas físicas ocorridas após a entrada na lojinha.
9. **CarrinhoTemporario:** Reserva volátil vinculada à sessão do comprador com timestamp de expiração.
10. **Pedido:** Requisição de compra fechada pelo colaborador para retirada física.
11. **PedidoItem:** Composição de itens e quantidades de cada pedido finalizado.
12. **LogSistema:** Tabela de auditoria técnica e eventos de segurança.

---

## 11. Modelo de Dados Proposto (Incluindo migrations)

### 11.1 Controle de Migrations
O sistema utiliza um script de migração baseado em CLI (`database/migrator.php`), executável via comando de terminal `php database/migrator.php`, totalmente bloqueado contra execuções via protocolo HTTP/navegador. O script verifica a tabela `migrations_log` para assegurar idempotência (execução única de cada script SQL).

### 11.2 Estrutura Completa de Tabelas (DDL)

```sql
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
```

---

## 12. Módulos e Telas

### 12.1 Módulo Operador (Urna Eletrônica)
- **Tela 1: Home do Operador (`/operador`):**
  - Fundo neutro (`#F3F4F6`), design limpo e sem poluição visual.
  - **Barra Superior do Quiosque:**
    - Exibição destacada do operador ativo no turno (ex: "Operador: João Silva").
    - Botão de toque rápido (altura mínima 80px, estilo secundário/suave) "Trocar Operador". Ao ser clicado, abre gaveta modal com grandes botões verticais (80px cada) listando os operadores ativos cadastrados no sistema, permitindo alternar de operador com um único toque sem necessidade de senhas.
    - Ícone de engrenagem minimalista em cinza claro (`#D1D5DB`) fixado discretamente no canto superior direito sem texto, servindo de atalho protegido para a gestão.
  - **Área Central de Ação:** Dois grandes botões centrais (altura 80px, cantos arredondados 16px, texto 24px em negrito):
    1. **"Registrar Avaria"** (Azul `#3B82F6`, ícone de garrafa/caixa avariada).
    2. **"Registrar Produção"** (Azul `#3B82F6`, ícone de cronômetro).
- **Tela 2: Seleção de Produto e Quantidade (`/operador/avaria/produto`):**
  - Lista em cartões amplos com foto e nome dos produtos em tamanho `body-lg`.
  - Campo numérico gigante com botões `+` e `-` ampliados (80px) para definição da quantidade.
  - Botão de avanço "Próximo" (Verde `#10B981`).
- **Tela 3: Seleção do Tipo de Avaria (`/operador/avaria/tipo`):**
  - Cinco botões imensos verticais (80px cada):
    - "Amassada" (Amarelo/Laranja)
    - "Defeito no Rótulo" (Amarelo/Laranja)
    - "Embalagem Danificada" (Amarelo/Laranja)
    - "Furo" (Vermelho Perigo `#EF4444`)
    - "Micro Furo" (Vermelho Perigo `#EF4444`)
- **Tela 4: Conferência de Avaria (`/operador/avaria/conferir`):**
  - Resumo em destaque (`headline-lg`): "Produto X | Qtd: Y | Motivo: Z".
  - Rodapé dividido ao meio:
    - Botão "Corrigir" (Laranja `#F59E0B`, 80px) -> Retorna ao passo anterior sem perder os dados.
    - Botão "Confirmar" (Verde `#10B981`, 80px) -> Salva no banco (ou no IndexedDB se offline) e exibe feedback de sucesso temporizado (2 segundos) antes de retornar à Home.
- **Tela 5: Cronômetro de Produtividade (`/operador/cronometro`):**
  - Passo 1: Seleção do produto a ser reembalado e botão gigante "Iniciar" (Azul `#3B82F6`).
  - Passo 2: Estado rodando com display numérico em `headline-xl` (32px) no formato `HH:MM:SS`. Botão grande "Finalizar" (Verde `#10B981`).
  - Passo 3: Campo numérico gigante para digitação de "Pacotes Montados" e tela de dupla confirmação ("Corrigir" / "Confirmar").

### 12.2 Módulo Lojinha Virtual (Colaborador)
- **Tela 1: Identificação (`/lojinha/login`):**
  - Entrada rápida de Matrícula Corporativa e validação do Setor do colaborador.
- **Tela 2: Vitrine de Ofertas (`/lojinha`):**
  - Exibição do Lote ativo e do limite individual restante (ex: "Você pode escolher até 5 itens").
  - Grade responsiva com cards de produtos: foto em alta definição, nome, volume, indicador de estoque restante real e botão "Adicionar à Sacola" (80px). Se esgotado, card esmaecido com rótulo "Esgotado".
- **Tela 3: Sacola de Compras (`/lojinha/sacola`):**
  - Listagem dos itens reservados com cronômetro regressivo destacado ("Sua reserva expira em 09:45").
  - Botão "Finalizar Pedido" (Verde `#10B981`).
- **Tela 4: Comprovante do Pedido (`/lojinha/sucesso`):**
  - Número do pedido em letras grandes, lista de itens confirmados e orientações de retirada na bancada de repack.

### 12.3 Módulo Gestão Administrativa
- **Tela 1: Login Administrativo (Modal via Engrenagem Oculta):**
  - Modal protegido exigindo usuário e senha criptografada.
- **Tela 2: Painel Consolidado (`/gestao/dashboard`):**
  - Indicadores rápidos de perdas (descarte) versus recuperações (estoque lojinha) e tempo médio por pacote.
- **Tela 3: Auditoria e Correção de B.O. (`/gestao/bo`):**
  - Tabela com filtros por data, produto e operador.
  - Opção de edição de quantidade ou tipo de avaria com justificativa obrigatória registrada em log.
- **Tela 4: Controle de Lotes (`/gestao/lotes`):**
  - Cadastro de lote com data/hora de abertura e fechamento, marcação de múltiplos setores autorizados e definição do teto por colaborador.
- **Tela 5: Gestão de Estoque e Reservas Ocultas (`/gestao/estoque`):**
  - Visão clara: Saldo Físico Total | Saldo Reservado pela Gestão | Saldo Efetivamente Ofertado.
  - Ferramenta para ajuste de "Reserva Oculta" com gravação de motivo.
  - Ferramenta de "Baixa Administrativa" para itens perdidos na sala de espera.
- **Tela 6: Lista de Separação de Pedidos (`/gestao/separacao`):**
  - Filtro por lote encerrado gerando visão unificada para impressão ou conferência em tablet, agrupada por Colaborador/Matrícula com caixas de checagem física de separação.
- **Tela 7: Catálogo de Produtos e Fotos (`/gestao/produtos`):**
  - Listagem de produtos cadastrados com código, nome, pacote padrão, status ativo/inativo e miniatura da foto.
  - Formulário modal para cadastro e edição de produtos contendo:
    - Campo código do produto (único).
    - Campo nome do produto.
    - Seletor de pacote padrão (ex: Unidade, Fardo, Caixa).
    - Campo de upload de imagem respeitando as diretrizes de segurança da Seção 21 (MIME types `image/jpeg`, `image/png`, `image/webp`, limite máximo de 2 MB e renomeação aleatória segura).
    - Alternador de ativação/inativação (soft delete).

---

## 13. Fluxos Funcionais

### 13.1 Fluxo de Registro de B.O. com Roteamento
```
[Operador na Home] 
      │ (Clica em "Registrar Avaria")
      ▼
[Passo 1: Seleciona Produto e Quantidade] 
      │ (Avança)
      ▼
[Passo 2: Clica em 1 dos 5 Botões de Avaria]
      │
      ├─ Se "Amassada", "Def. Rótulo" ou "Emba. Danificada" ──> Destino = 'lojinha'
      └─ Se "Furo" ou "Micro Furo" ───────────────────────────> Destino = 'descarte'
      │
      ▼
[Passo 3: Tela de Conferência - Resumo dos Dados]
      ├──> [Botão Corrigir] ────> Volta ao Passo 2 preservando valores
      └──> [Botão Confirmar] ───> Se Online: Salva via AJAX no MySQL e atualiza estoque_lojinha (se destino='lojinha')
                                  Se Offline: Grava no IndexedDB com flag 'offline'
                                  Feedback verde de sucesso (2s) -> Redireciona para a Home
```

### 13.2 Fluxo de Cronômetro de Produtividade
```
[Operador na Home] 
      │ (Clica em "Registrar Produção")
      ▼
[Passo 1: Seleciona Produto a Reembalar]
      │
      ▼
[Passo 2: Clica no Botão "Iniciar"]
      │ Grava timestamp de início (localStorage e backend)
      │ Inicia contagem visual (HH:MM:SS) em 32px
      ▼
[Operação física de reembalagem na bancada...]
      ▼
[Passo 3: Clica no Botão "Finalizar"]
      │ Captura timestamp final
      │ Exibe campo numérico para digitação dos pacotes refeitos
      ▼
[Passo 4: Tela de Conferência (Tempo Total + Qtd Pacotes)]
      ├──> [Botão Corrigir] ────> Corrige quantidade informada
      └──> [Botão Confirmar] ───> Se Online: Salva via AJAX no MySQL
                                  Se Offline: Grava no IndexedDB com flag 'offline'
                                  Feedback verde de sucesso (2s) -> Redireciona para a Home
```

### 13.3 Fluxo de Compra e Trava de Carrinho
```
[Colaborador informa Matrícula]
      │
      ▼
[Sistema valida: Há Lote Aberto no horário atual E Setor está na lista autorizada?]
      ├──> Não: Exibe tela amigável informando status do lote e bloqueia acesso.
      └──> Sim: Verifica compras anteriores do colaborador neste lote.
            │
            ▼
[Vitrine Aberta]
      │ Mostra produtos com Estoque Disponível > 0
      │ Limite restante = Limite_Lote - Itens_Comprados - Itens_No_Carrinho
      ▼
[Colaborador clica "Adicionar"]
      │ Insere registro em `carrinho_temporario` com expiracao_em = NOW() + 10 MIN
      │ Abate temporariamente da visualização de todos
      ▼
[Sacola de Compras]
      ├──> Se tempo expirar (>10 min): Carrinho é liberado via rotina e itens voltam à vitrine.
      └──> Colaborador clica em "Confirmar Pedido":
            │ Validação transacional (BEGIN TRANSACTION no PDO):
            │ 1. Checa se itens continuam no tempo de reserva.
            │ 2. Cria registro em `pedidos` e `pedido_itens`.
            │ 3. Abate definitivamente do campo `quantidade_total` em `estoque_lojinha`.
            │ 4. Remove itens do `carrinho_temporario`.
            │ 5. COMMIT.
            ▼
[Gera Cupom/Comprovante Digital na Tela]
```

---

## 14. Validações e Regras de Negócio
1. **Regra de Não-Contaminação da Lojinha:** Itens marcados como "Furo" ou "Micro Furo" têm seu destino forçado como `descarte` no backend. Qualquer tentativa de direcionamento para estoque da lojinha é interceptada e abortada.
2. **Inviolabilidade da Interface do Operador:** Nenhuma tela acessível ao operador possui ações destrutivas (UPDATE ou DELETE). Todo apontamento confirmado gera registro append-only.
3. **Trava Temporal de Concorrência no Carrinho:** A reserva em carrinho é nominal ao `usuario_id` e à sessão, possuindo validade de exatos 10 minutos. O cron do sistema ou a consulta de saldo executa a limpeza de reservas expiradas:
   ```sql
   DELETE FROM carrinho_temporario WHERE expiracao_em < NOW();
   ```
4. **Cumprimento Rigoroso do Teto por Comprador:**
   A soma dos itens em `pedidos` (status != 'cancelado') com os itens em `carrinho_temporario` ativo para um determinado `usuario_id` e `lote_id` nunca pode exceder o valor `limite_itens_por_comprador` configurado no lote.
5. **Cálculo de Duração do Cronômetro:** O campo `duracao_segundos` é calculado no backend através da diferença `TIMESTAMPDIFF(SECOND, data_hora_inicio, data_hora_fim)` para evitar distorções de relógio do cliente.

---

## 15. Autenticação e Sessão
- **Sessão Operador:** Modo de terminal quiosque (Kiosk). O operador ativo é identificado no cabeçalho e selecionado via lista rápida na barra superior sem necessidade de digitação complexa de senhas. Essa seleção vincula o `usuario_id` ativo no navegador para todos os lançamentos de B.O. e Produtividade.
- **Sessão Gestão:** Autenticação restrita com `matricula` e `senha_hash` (processada com algoritmo `password_hash(PASSWORD_DEFAULT)`). O cookie de sessão PHP é configurado com flags de segurança `HttpOnly`, `SameSite=Strict` e tempo limite de inatividade de 30 minutos.
- **Sessão Comprador:** Validação sem senha complexa por matrícula corporativa cadastrada, vinculando a sessão temporária à matrícula e setor enquanto navegar na lojinha.

---

## 16. Controle de Acesso
O sistema implementa uma camada de Middleware / Guard no Controller central:
- Rotas `/gestao/*`: Exigem `$_SESSION['perfil'] === 'gestao'`. Tentativas de acesso sem credencial redirecionam para a Home com log de alerta de segurança.
- Rotas `/operador/*`: Bloqueadas para execução de funções administrativas.
- Rotas `/lojinha/*`: Exigem verificação de lote aberto ativo e pertencimento do setor do usuário aos setores liberados no lote.

---

## 17. Auditoria e Histórico
- Toda alteração corretiva efetuada pela Coordenadora em um B.O. preenche obrigatoriamente os campos `corrigido_por_usuario_id`, `motivo_correcao` e atualiza o `updated_at`.
- As movimentações de reserva oculta e baixas administrativas são armazenadas em tabelas históricas independentes (`reservas_gestao_historico` e `baixas_administrativas`), registrando quem realizou a intervenção, o motivo formal e a quantidade alterada.

---

## 18. Soft Delete e Exclusões
- Aplicado integralmente nas entidades centrais: `usuarios`, `produtos`, `apontamentos_bo`, `apontamentos_produtividade`, `lotes_venda` e `pedidos` através da coluna `deleted_at DATETIME NULL`.
- Consultas padrão utilizam cláusula obrigatória `WHERE deleted_at IS NULL`.
- É expressamente proibida a execução de comandos `DELETE FROM` em dados transacionais de apontamentos e pedidos. A exclusão lógica apenas oculta o item das telas operacionais preservando a integridade histórica.

---

## 19. Logs
O sistema possui duplo mecanismo de registro de eventos:
1. **Logs em Banco de Dados (`logs_sistema`):**
   - Registra eventos de níveis `INFO`, `WARNING`, `ERROR` e `SECURITY`.
   - Armazena IP do cliente, payload em formato JSON e rota requisitada.
2. **Plano de Contingência em Arquivo (`logs/app_errors.log` e `logs/security.log`):**
   - Em caso de queda de conexão com o banco MySQL ou erro crítico de PDO, o tratador global de exceções desvia a gravação do erro imediatamente para o arquivo de texto no disco:
   ```php
   file_put_contents(
       __DIR__ . '/../../logs/app_errors.log', 
       "[" . date('Y-m-d H:i:s') . "] " . $errorMessage . PHP_EOL, 
       FILE_APPEND | LOCK_EX
   );
   ```

---

## 20. Configurações Globais
O sistema armazena suas definições no arquivo nativo `app/config/config.php`, estruturado em código PHP puro e protegido de visualização externa:
- Proibido o uso de arquivos `.env` soltos no servidor para evitar vazamento em servidores web mal configurados.
- Definição de constantes essenciais:
  - `DB_HOST`: Host do MySQL (ex: `localhost`).
  - `DB_NAME`: Nome da base de dados (`repack_db`).
  - `DB_USER` e `DB_PASS`: Credenciais seguras de acesso ao banco.
  - `APP_URL`: Caminho base da aplicação.
  - `CARRINHO_TIMEOUT_MINUTOS`: 10 minutos.
  - `PATH_UPLOADS`: Diretório físico de gravação de imagens.

---

## 21. Uploads, Anexos e Arquivos
- **Imagens de Produtos:** Cadastradas exclusivamente pela Coordenadora no módulo de gestão (`/gestao/produtos`).
- **Diretório de Armazenamento:** `public/uploads/produtos/`.
- **Validações de Segurança de Arquivo:**
  - Tipos MIME estritamente aceitos: `image/jpeg`, `image/png`, `image/webp`.
  - Limite máximo por imagem: 2 MB.
  - Renomeação automática no upload com hash único gerado aleatoriamente (ex: `prod_65e12f0a1bc.webp`) para impedir ataques de Path Traversal ou execução de scripts maliciosos.

---

## 22. Relatórios, Consultas e Exportações
1. **Lista de Separação Física (Extrato de Pedidos):**
   - Módulo gerencial para impressão ou visualização em tela.
   - Lista consolidada agrupando os pedidos finalizados de um lote fechado por Matrícula e Nome do colaborador, detalhando os produtos adquiridos, quantidades e campo para visto/assinatura manual de recebimento.
2. **Relatório Sintético de Movimentação de Repack:**
   - Total de unidades recebidas, percentual direcionado à Lojinha versus percentual descartado (Furo/Micro Furo).
3. **Extrato Analítico de Produtividade da Equipe:**
   - Produtividade média por operador (pacotes montados por hora e tempo médio de ciclo por produto).

---

## 23. APIs e Integrações Externas
O sistema opera isolado de integrações com sistemas externos corporativos (ERP/Folha). Possui apenas endpoints internos de consumo assíncrono (JSON) para suporte à interface e PWA:
- `POST /api/bo/salvar.php`: Recebe dados do apontamento de avaria e retorna confirmação.
- `POST /api/bo/sincronizar.php`: Endpoint em lote que recebe array de apontamentos de B.O. acumulados offline pelo IndexedDB.
- `POST /api/produtividade/salvar.php`: Recebe dados do ciclo de produtividade (produto, timestamps, pacotes montados, duração) e retorna confirmação.
- `POST /api/produtividade/sincronizar.php`: Endpoint em lote que processa array de registros de produtividade acumulados offline pelo IndexedDB.
- `POST /api/carrinho/adicionar.php`: Registra a trava temporária de item (10 minutos).
- `POST /api/carrinho/remover.php`: Libera trava temporária de item antes da expiração.
- `GET /api/estoque/saldo.php`: Consulta saldo dinâmico atualizado de produto em vitrine.

---

## 24. Segurança Funcional
- **Prevenção contra SQL Injection:** Uso mandatório de PDO com consultas preparadas (`prepare()` e `execute()`) sem concatenação de strings em queries.
- **Proteção contra Cross-Site Scripting (XSS):** Sanitização obrigatória de toda e qualquer saída em tela através de `htmlspecialchars($dado, ENT_QUOTES, 'UTF-8')`.
- **Proteção contra CSRF:** Formulários da área de gestão incluem token CSRF verificado no backend antes do processamento de ações POST.
- **Camuflagem da Engrenagem:** Ícone de acesso gerencial sem qualquer texto descritivo, em tom de cinza claro próximo ao fundo, prevenindo toques curiosos ou acidentais de operadores na linha de produção.

---

## 25. Organização Sugerida da Implementação
A execução do desenvolvimento deve seguir um roteiro incremental de 5 fases interdependentes:
1. **Fase 1 - Fundação e Banco de Dados:**
   - Criação da árvore física de diretórios.
   - Configuração de `config.php`, classe de conexão `database.php` e script `migrator.php`.
   - Execução das migrations para geração do esquema completo de tabelas e carga inicial de produtos e usuários.
2. **Fase 2 - Interface do Operador (Urna Eletrônica):**
   - Construção dos layouts com CSS Bootstrap local e tokens do `DESIGN.md`.
   - Implementação do cabeçalho com seleção/troca ágil de operador no quiosque.
   - Implementação das telas de B.O. (Passo 1, Passo 2 com 5 tipos e tela de Corrigir/Confirmar).
   - Implementação do Cronômetro com registro de pacotes montados e sincronização offline.
   - Configuração do Service Worker, IndexedDB e fila de sincronização offline.
3. **Fase 3 - Painel Oculto da Gestão:**
   - Modal de autenticação da engrenagem.
   - Catálogo de produtos com upload seguro de imagens (`/gestao/produtos`).
   - Telas de cadastro/abertura de lotes e setores.
   - Telas de auditoria/retificação de B.O. e baixas administrativas.
   - Controle de reservas ocultas de estoque.
4. **Fase 4 - Vitrine e Carrinho da Lojinha:**
   - Identificação do colaborador por matrícula e filtro de acesso por setor do lote ativo.
   - Vitrine de produtos com fotos e verificação de saldo disponível.
   - Lógica de trava de carrinho por 10 minutos e checkout com geração de pedido.
5. **Fase 5 - Lista de Separação, Relatórios e Testes Integrados:**
   - Tela de extração da lista de separação física.
   - Auditoria de logs e validação do fallback de erros em arquivo.
   - Testes de toque responsivo com luva e simulação de perda de sinal de rede (offline).

---

## 26. Critérios de Aceitação Técnica e Funcional
- [ ] A interface do Operador exibe botões com altura mínima de 80px e reage sem lentidão a toques com luvas (`touch-action: manipulation`).
- [ ] Toda avaria cadastrada como "Amassada", "Def. Rótulo" ou "Emba. Danificada" incrementa automaticamente o saldo de `estoque_lojinha`.
- [ ] Toda avaria cadastrada como "Furo" ou "Micro Furo" recebe status de descarte e nunca incrementa saldo de venda.
- [ ] Apontamentos realizados sem conexão de internet são retidos no navegador e sincronizados automaticamente assim que o sinal for restabelecido.
- [ ] A engrenagem da tela inicial só permite acesso à Gestão após conferência rigorosa de credenciais criptografadas.
- [ ] O carrinho da lojinha impede que dois compradores comprem a mesma unidade simultaneamente, aplicando trava de 10 minutos.
- [ ] A vitrine respeita o cálculo: $\text{Disponível} = \text{Total} - \text{Reserva Oculta} - \text{Carrinhos Ativos}$.
- [ ] O migrador de banco é executado via linha de comando e não pode ser acionado por requisições HTTP públicas.
- [ ] Os erros de sistema gravam no arquivo local `logs/app_errors.log` caso ocorra falha de banco de dados.

---

## 27. Pontos Pendentes e Decisões Futuras
Não restam pendências técnicas impeditivas para a codificação da versão 1.0. Para versões futuras (fases posteriores), ficam mapeadas como melhorias opcionais:
1. Possibilidade de exportação da lista de separação diretamente em formato PDF ou planilha para impressão remota.
2. Módulo de inventário periódico com bipagem física de código de barras para conciliação de saldo da lojinha.
3. Notificações sonoras simples estilo urna eletrônica na confirmação dos apontamentos do operador.

---

## 28. Conclusão
O **FSD do Projeto Repack** encontra-se integralmente consolidado, autossuficiente e alinhado aos padrões técnicos e de design estabelecidos. Com a incorporação das correções técnicas validadas na revisão independente, o sistema está plenamente especificado para garantir acessibilidade universal na ponta fabril, integridade contábil nas perdas e eficiência no ciclo de vendas internas, 100% pronto para o início imediato da implementação pelo time de engenharia de software.
