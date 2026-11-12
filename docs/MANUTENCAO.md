# MANUAL TÉCNICO DE MANUTENÇÃO - PROJETO REPACK

## 1. Visão Geral
O **Projeto Repack** é um sistema web responsivo e Progressive Web App (PWA) de uso interno, desenvolvido em PHP puro e MySQL para o setor de reembalagem (Repack) de uma distribuidora de bebidas.

O sistema centraliza três rotinas essenciais:
1. **Lançamento de Boletins de Ocorrência (B.O.) de Avarias com Roteamento Inteligente:** segregação imediata entre itens leves passíveis de recuperação para a Lojinha Interna ("Amassada", "Def. Rótulo", "Emba. Danificada") e perdas descartadas ("Furo", "Micro Furo").
2. **Apontamento de Produtividade Operacional (Cronômetro):** cronometragem digital do tempo de manuseio e contagem de pacotes reembalados pelos operadores de bancada.
3. **Gestão e Vitrine da Lojinha dos Colaboradores:** controle de estoque, lotes de venda restritos por setor e cota individual, reserva temporária de 10 minutos no carrinho contra concorrência e geração de lista física de separação.

A interface do Operador opera sob a **Filosofia da Urna Eletrônica**: alto contraste, botões gigantes com alvos de toque mínimos de 80px, navegação linear passo a passo ("um dado por vez"), dupla confirmação ("Corrigir" em laranja `#F59E0B` e "Confirmar" em verde `#10B981`) e suporte a luvas anti-corte (`touch-action: manipulation`).

---

## 2. Stack Tecnológica
- **Backend:** PHP puro 8.1+ em padrão arquitetural MVC (Model-View-Controller), sem frameworks externos pesados.
- **Banco de Dados:** MySQL 8.0+ (ou MariaDB 10.4+) via extensão PDO com Prepared Statements nativos obrigatórios (`PDO::ATTR_EMULATE_PREPARES => false`).
- **Frontend:** HTML5 semântico, CSS3 moderno com Bootstrap 5.3 local (sem dependência de CDN externa), CSS customizado (`public/assets/css/urna.css`) e JavaScript Vanilla (ES6+).
- **PWA Offline-First:**
  - `public/manifest.json` para instalação em modo standalone.
  - `public/service-worker.js` para cache estático e offline fallback.
  - `IndexedDB` (`RepackDB`) via `public/assets/js/app-offline.js` para armazenamento local de B.O. e Produtividade em caso de desconexão, com sincronização em lote.
- **Segurança:** Configurações centralizadas em `app/config/config.php` (sem `.env`), tokens CSRF em formulários e mutações, sanitização `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`, proteção de diretórios via `.htaccess`.

---

## 3. Como Rodar o Projeto Localmente

### 3.1 Requisitos
- Servidor Web Apache 2.4+ com módulo `mod_rewrite` habilitado.
- PHP 8.1 ou superior com extensões habilitadas: `pdo_mysql`, `mbstring`, `fileinfo`, `session`, `json`.
- Servidor MySQL 8.0+ ou MariaDB 10.4+.

### 3.2 Passo a Passo de Instalação
1. **Posicionar o repositório:**
   Coloque a pasta do projeto no diretório do servidor web (ex: `C:/xampp/htdocs/projeto_repack`).
2. **Configurar o Banco de Dados:**
   - Crie uma base de dados vazia no MySQL:
     ```sql
     CREATE DATABASE repack_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Verifique e ajuste as credenciais em `app/config/config.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'repack_db');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     // APP_URL é auto-detectado dinamicamente para raiz ('') ou subdiretórios
     ```
3. **Executar as Migrations e Seeds via CLI:**
   Abra o terminal na raiz do projeto e execute:
   ```bash
   php database/migrator.php
   ```
   O migrador executará `001_create_tables.sql` e `002_seed_initial_data.sql` de forma idempotente, registrando o histórico na tabela `migrations_log`.
4. **Executar a Suíte de Testes:**
   Valide se todos os módulos estão íntegros:
   ```bash
   php tests/test_fase5.php
   php tests/test_router.php
   ```
5. **Acessar a Aplicação no Navegador:**
   - Acesso Operador (Urna Eletrônica): `http://localhost/projeto_repack/public/` ou `http://localhost/projeto_repack/public/operador`
   - Acesso Lojinha: `http://localhost/projeto_repack/public/lojinha/login`
   - Acesso Gestão: Clique na engrenagem cinza clara (`#D1D5DB`) no canto superior direito da tela do Operador ou acesse `http://localhost/projeto_repack/public/gestao/dashboard`

### 3.3 Credenciais Iniciais de Teste
- **Gestão (Coordenadora / Master):** Matrícula `GEST01` / Senha `gestao123` (Possui acesso ao painel de gestão e visão Master da Lojinha com estoque total + reservas).
- **Operadores de Turno:** Matrículas `OP01` (Adriano Silva) e `OP02` (Pedro Cruz) - selecionáveis no quiosque.
- **Colaboradores Comuns (Regra 1B):** Matrícula `COLAB01` (Carlos Ferreira - Armazém), `COLAB02` (Mariana Lima - Transporte). Limitados a 1 único pedido por lote.
- **Contas Setoriais Corporativas (Múltiplos Pedidos / Demandas Internas):**
  - `RH01` - Gente e Gestão (RH)
  - `ADM01` - Setor Administrativo
  - `VENDAS01` - Sala de Vendas
  - `FIN01` - Setor Financeiro

---

## 4. Mapa da Estrutura de Pastas

```
projeto_repack/
│
├── app/
│   ├── config/
│   │   ├── config.php            # Configurações globais, sanitização, CSRF e constantes
│   │   └── database.php          # Singleton PDO com tratamento de erros e fallback de logs
│   │
│   ├── controllers/
│   │   ├── AuthController.php    # Autenticação de Gestão e verificação de sessão
│   │   ├── AvariaController.php  # Fluxo do B.O., roteamento automático e API
│   │   ├── ProdutividadeController.php # Cronômetro, cálculo de tempos e API
│   │   ├── OperadorController.php # Home da Urna e API de listagem de operadores
│   │   ├── LojinhaController.php # Vitrine, carrinho temporário e checkout atômico
│   │   ├── GestaoController.php  # Dashboard, lotes, estoque/reservas, correções e produtos
│   │   └── RelatorioController.php # Lista de separação física, extratos e relatórios CSV
│   │
│   ├── models/
│   │   ├── Usuario.php
│   │   ├── Produto.php
│   │   ├── Configuracao.php
│   │   ├── ApontamentoBO.php
│   │   ├── ApontamentoProdutividade.php
│   │   ├── LoteVenda.php
│   │   ├── EstoqueLojinha.php
│   │   ├── Pedido.php
│   │   └── LogSistema.php
│   │
│   └── views/
│       ├── layouts/
│       │   ├── header.php / footer.php           # Layout quiosque da Urna Eletrônica
│       │   ├── gestao_header.php / gestao_footer.php # Layout do Painel Administrativo
│       │   └── lojinha_header.php / lojinha_footer.php # Layout da Vitrine dos Colaboradores
│       ├── operador/
│       │   ├── home.php, avaria_passo1.php, avaria_passo2.php, avaria_resumo.php
│       │   └── cronometro.php, cronometro_resumo.php
│       ├── lojinha/
│       │   ├── login.php, vitrine.php, carrinho.php, sucesso.php
│       │   └── fechada.php
│       └── gestao/
│           ├── dashboard.php, produtos.php, lotes.php, estoque.php
│           ├── correcao_bo.php, separacao.php, relatorios.php
│           └── login_fallback.php
│
├── database/
│   ├── migrations/
│   │   ├── 001_create_tables.sql # DDL das 13 tabelas relacionais
│   │   └── 002_seed_initial_data.sql # Usuários, produtos e saldo inicial
│   └── migrator.php              # Executor de migrations via CLI
│
├── public/
│   ├── assets/
│   │   ├── css/bootstrap.min.css, urna.css
│   │   ├── js/bootstrap.bundle.min.js, touch-handler.js, app-offline.js
│   │   └── img/
│   ├── uploads/
│   │   └── produtos/             # Imagens cadastradas (MIME validado, renomeação segura)
│   ├── .htaccess                 # Roteamento Front Controller
│   ├── service-worker.js         # Cache PWA
│   ├── manifest.json             # PWA Manifest
│   └── index.php                 # Front Controller e roteador HTTP central
│
├── logs/
│   ├── app_errors.log            # Fallback de exceções e erros de PDO
│   └── security.log              # Auditoria de eventos de segurança e acessos negados
│
├── tests/
│   ├── test_fase5.php            # Testes integrados de aceitação das 5 fases
│   └── test_router.php           # Testes de roteamento HTTP e middlewares
│
└── docs/
    ├── FSD.md                    # Especificação Funcional Normativa
    ├── DESIGN.md                 # Design System e tokens da Urna
    ├── INSUMOS.md                # Governança de assets
    ├── PLANO.md                  # Fases incrementais de desenvolvimento
    ├── STATUS.md                 # Controle de avanço do projeto
    ├── ERROS.md                  # Registro de incidentes e lições aprendidas
    ├── MANUTENCAO.md             # Este manual técnico
    └── COMO-PEDIR-MUDANCAS.md    # Modelos de solicitação de manutenção
```

---

## 5. Arquitetura do Banco de Dados

### 5.1 Tabelas Estruturais (13 tabelas)
1. `migrations_log`: Registro de scripts SQL executados para garantir idempotência.
2. `usuarios`: Matrículas, senhas com `password_hash`, perfis (`operador`, `gestao`, `colaborador`), setores e soft delete.
3. `produtos`: Código único, nome, foto, embalagem padrão e soft delete.
4. `apontamentos_bo`: Lançamento de avarias com destino automático (`lojinha` ou `descarte`), flags de sincronização offline e histórico de correções.
5. `apontamentos_produtividade`: Ciclos de repack com timestamps, pacotes montados e duração em segundos.
6. `lotes_venda`: Abertura e vigência de lotes com lista JSON de setores autorizados e limite individual de itens.
7. `estoque_lojinha`: Saldo físico total e reserva manual oculta por produto.
8. `reservas_gestao_historico`: Histórico com justificativa formal de reservas manuais.
9. `baixas_administrativas`: Histórico com justificativa de perdas na sala de espera.
10. `carrinho_temporario`: Trava de concorrência com expiração exata em 10 minutos.
11. `pedidos`: Cabeçalho do pedido confirmado pelo colaborador.
12. `pedido_itens`: Itens e quantidades vinculadas a cada pedido.
13. `logs_sistema`: Auditoria interna de eventos (`INFO`, `WARNING`, `ERROR`, `SECURITY`).

### 5.2 Regras de Concorrência e Saldo da Vitrine
O saldo público exibido aos compradores na vitrine segue a fórmula:
$$\text{Disponível} = \text{Total Físico} - \text{Reserva Oculta} - \text{Carrinhos Ativos (< 10 min)}$$

O checkout utiliza transação atômica (`BEGIN TRANSACTION`, `SELECT ... FOR UPDATE`, verificação de validade temporal, `COMMIT`) para garantir que nunca ocorra overselling.

---

## 6. Fluxos de Autenticação e Sessão

| Perfil | Mecanismo de Acesso | Duração / Comportamento |
| :--- | :--- | :--- |
| **Operador** | Quiosque compartilhado sem senha. Troca rápida via modal de toque na barra superior. | Persistente no navegador do tablet via cookie de sessão. |
| **Gestão / Master** | Acesso protegido via ícone de engrenagem cinza clara (`#D1D5DB`) ou azul. Exige matrícula (`GEST01`) e senha validadas com `password_verify()`. Na Lojinha, possui perfil `master` (enxerga estoque total físico + reservas da gestão e realiza retiradas a qualquer momento). | Sessão com expiração automática por inatividade de 30 minutos. Protegido pelo guard `AuthController::exigirGestao()`. |
| **Colaborador Comum** | Identificação por matrícula cadastrada (`COLAB01`, etc.). Sujeito à vigência de lote aberto e **Regra 1B (estritamente 1 único pedido por lote)**. | Sessão temporária restrita ao lote em andamento. Após checkout, a vitrine é bloqueada para novas compras no ciclo. |
| **Conta Setorial** | Identificação por matrícula institucional do setor (`RH01`, `ADM01`, `VENDAS01`, `FIN01`). | Sem trava de 1 pedido (múltiplas compras permitidas no ciclo). Enxerga estoque disponível (sem reservas) e pode operar mesmo com loja pública fechada via Lote Corporativo. |

---

## 7. Como Estender o Sistema (Passo a Passo)

### 7.1 Adicionando um Novo Campo no Banco de Dados
1. Crie uma nova migration numerada sequencialmente em `database/migrations/` (ex: `003_adicionar_campo_x.sql`):
   ```sql
   ALTER TABLE produtos ADD COLUMN codigo_barras VARCHAR(60) NULL AFTER codigo;
   ```
2. Execute o migrador via CLI:
   ```bash
   php database/migrator.php
   ```
3. Atualize o Model correspondente em `app/models/` com os novos métodos e atributos.
4. Atualize a View correspondente em `app/views/`.

### 7.2 Adicionando uma Nova Tela ou Funcionalidade
1. **Model:** Crie ou adapte métodos com Prepared Statements em `app/models/`.
2. **View:** Crie o arquivo visual em `app/views/{modulo}/{nome_da_tela}.php`, herdando o cabeçalho e rodapé adequados (`app/views/layouts/`).
3. **Controller:** Crie o método de ação no Controller responsável em `app/controllers/`. Se for rota administrativa, aplique `AuthController::exigirGestao()`.
4. **Roteamento:** Adicione a rota no switch/case de `public/index.php`.

---

## 8. Diretrizes Rígidas de Segurança e Usabilidade
1. **Prepared Statements Nativos:** Jamais concatene variáveis diretamente em instruções SQL. Utilize placeholders nomeados únicos (ex: `:prod_id`).
2. **Sanitização de Saída:** Todas as variáveis renderizadas em HTML devem passar por `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
3. **Proteção CSRF:** Todo formulário POST deve gerar e validar tokens com `csrf_token()` e `validar_csrf()`.
4. **Alvos de Toque (Urna Eletrônica):** Na interface do operador, preserve a altura mínima de 80px para botões, `touch-action: manipulation` e contraste rígido.
5. **Fallback de Logs:** Em caso de exceção de banco, o tratador global redireciona a falha para `logs/app_errors.log`. Tentativas de violação de rota gravam em `logs/security.log`.
6. **Proteção Web Server:** O arquivo `.htaccess` na raiz e nas pastas internas impede acesso HTTP direto aos códigos-fonte PHP fora da pasta `public/`.
7. **Isolamento de PWA e Subdiretórios:** Quando a aplicação rodar em subpastas ou compartilhar a mesma hospedagem com outro site (ex: Alwaysdata), o `public/manifest.json` deve manter escopo e caminhos estritamente relativos (`scope: "./"`, `start_url: "./operador"` e ícones em `assets/img/...`). O Service Worker deve ser registrado com `{ scope: BASE_APP_URL + '/' }`, e todos os layouts devem declarar explicitamente favicons locais para evitar captura de ícones de aplicações vizinhas na raiz do servidor.
