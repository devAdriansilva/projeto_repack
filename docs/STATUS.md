# STATUS DO PROJETO

## Estado Atual
- **Fase Atual:** Fase 5 - Lista de Separação, Relatórios e Testes Integrados (Pendente de Início)
- **Status Geral:** Fase 4 (Vitrine e Carrinho da Lojinha) construída e validada com sucesso absoluto. Identificação rápida do colaborador por matrícula corporativa com validação de lote aberto e permissão por setor implementada; Catálogo responsivo da Vitrine (`/lojinha`) com cards visuais, fotos, cálculo dinâmico de saldo real ($\text{Disponível} = \text{Total} - \text{Reserva} - \text{Carrinhos Outros}$) e botões operacionais de 80px; Trava temporal de concorrência no carrinho (`carrinho_temporario`) com validade estrita de 10 minutos e rotina automática de limpeza de reservas expiradas; Verificação de teto máximo de compras individual por colaborador no lote; Checkout atômico transacional via PDO (`BEGIN TRANSACTION` / `COMMIT`) com abate definitivo do saldo físico em `estoque_lojinha`, criação de registros em `pedidos` e `pedido_itens` e emissão de código único de pedido (`PED-YYYYMMDD-XXXXXX`); Tela de comprovante digital com instruções de retirada física; e endpoints assíncronos (`/api/carrinho/adicionar`, `/api/carrinho/remover`, `/api/estoque/saldo`).

---

## Progresso das Fases

- [x] **Preparação do Terreno (Estrutura e Governança)**
  - [x] Criação da árvore física de diretórios (MVC, public, database, logs).
  - [x] Mapeamento e validação de insumos (`docs/INSUMOS.md`).
  - [x] Criação dos arquivos vivos de controle (`docs/PLANO.md`, `docs/STATUS.md`, `docs/ERROS.md`, `AGENTS.md`).
  - [x] Configuração de versionamento e segurança Git/GitHub (`.gitignore`, `.gitattributes`, repositório local inicializado e protegido).
- [x] **Fase 1: Fundação e Banco de Dados (Testada e Validada)**
  - [x] Arquivo `app/config/config.php` (configurações globais, sanitização XSS e parâmetros de segurança)
  - [x] Classe singleton PDO `app/config/database.php` (Prepared Statements nativos e fallback em `logs/app_errors.log`)
  - [x] Script de migração CLI `database/migrator.php` (execução idempotente e bloqueio estrito contra acesso HTTP)
  - [x] Migration DDL `database/migrations/001_create_tables.sql` (13 tabelas relacionais criadas com FKs e índices)
  - [x] Seed inicial `database/migrations/002_seed_initial_data.sql` (usuários, perfis com senhas hash e produtos)
  - [x] Front Controller e Roteador base `public/index.php` (rotas `/`, `/api/health` e 404 validadas com headers seguros)
- [x] **Fase 2: Interface do Operador (Urna Eletrônica) (Testada e Validada)**
  - [x] Folhas de estilo locais: `public/assets/css/bootstrap.min.css` e `public/assets/css/urna.css` (tokens de cores, botões de 80px e alto contraste)
  - [x] Otimizador de toque: `public/assets/js/touch-handler.js` (suporte a luvas anti-corte, `touch-action: manipulation`, mitigação de 300ms de atraso)
  - [x] Gerenciador PWA Offline-First: `public/assets/js/app-offline.js` (IndexedDB `RepackDB`, stores para B.O. e Produtividade, fila e sincronização em lote)
  - [x] Manifest e Service Worker PWA: `public/manifest.json` e `public/service-worker.js` (cache offline de rotas operacionais e assets)
  - [x] Layouts base com cabeçalho quiosque: `app/views/layouts/header.php` e `app/views/layouts/footer.php` (troca rápida de operador sem senha, badge de conectividade, engrenagem camuflada `#D1D5DB` e overlay de feedback de 2 segundos)
  - [x] Tela inicial da Urna: `app/views/operador/home.php` (dois grandes botões de 80px: Registrar Avaria e Registrar Produção)
  - [x] Fluxo de Avaria: `app/views/operador/avaria_passo1.php`, `avaria_passo2.php` e `avaria_resumo.php` (seleção de produto, 5 botões de avaria, conferência e roteamento automático)
  - [x] Fluxo de Produtividade: `app/views/operador/cronometro.php` e `cronometro_resumo.php` (cronômetro digital `HH:MM:SS` em 32px, registro de pacotes e conferência dupla)
  - [x] Models MVC: `app/models/Usuario.php`, `app/models/Produto.php`, `app/models/EstoqueLojinha.php`, `app/models/ApontamentoBO.php` e `app/models/ApontamentoProdutividade.php`
  - [x] Controllers MVC: `app/controllers/OperadorController.php`, `app/controllers/AvariaController.php` e `app/controllers/ProdutividadeController.php`
  - [x] Endpoints assíncronos: `/api/bo/salvar.php`, `/api/bo/sincronizar.php`, `/api/produtividade/salvar.php`, `/api/produtividade/sincronizar.php`, `/api/operadores`, `/api/produtos` e `/api/gestao/login`
- [x] **Fase 3: Painel Oculto da Gestão (Testada e Validada)**
  - [x] Autenticação e Segurança: `app/controllers/AuthController.php` (login via modal engrenagem, `password_verify()`, controle de inatividade de 30 min, middleware guard `exigirGestao()` e tokens CSRF)
  - [x] Controller Administrativo: `app/controllers/GestaoController.php` (métodos para dashboard, produtos, lotes, estoque/reservas e retificação de B.O.)
  - [x] Model Log e Auditoria: `app/models/LogSistema.php` (gravação em `logs_sistema` com fallback para `logs/app_errors.log` e `logs/security.log`)
  - [x] Model Lotes de Venda: `app/models/LoteVenda.php` (regras de vigência, status, limite por comprador e setores autorizados em JSON)
  - [x] Aprimoramento de Models: `app/models/Produto.php`, `app/models/EstoqueLojinha.php`, `app/models/ApontamentoBO.php` e `app/models/Usuario.php` (CRUD seguro, unicidade de SKU, histórico de reservas/baixas e retificação com rebalanceamento de estoque)
  - [x] Layouts Administrativos: `app/views/layouts/gestao_header.php` e `app/views/layouts/gestao_footer.php` (navegação superior, identificação da Coordenadora, alertas flash e retorno ao quiosque)
  - [x] View Dashboard: `app/views/gestao/dashboard.php` (indicadores de perdas vs. recuperação, taxa %, saldos da lojinha, tempo de bancada e atalhos)
  - [x] View Produtos: `app/views/gestao/produtos.php` (tabela de catálogo, miniaturas, ativação/inativação e modal com upload de fotos até 2MB)
  - [x] View Lotes: `app/views/gestao/lotes.php` (planejamento, abertura/fechamento instantâneo, definição de setores via checkboxes e limites)
  - [x] View Estoque & Reservas: `app/views/gestao/estoque.php` (fórmula Total - Reserva = Disponível, histórico de ajustes de reservas e baixas administrativas)
  - [x] View Correção de B.O.: `app/views/gestao/correcao_bo.php` (filtros por período/produto/operador/destino e modal com justificativa obrigatória)
  - [x] Roteador Central Atualizado: `public/index.php` (rotas `/gestao/*` integradas com segurança)
- [x] **Fase 4: Vitrine e Carrinho da Lojinha (Testada e Validada)**
  - [x] Model Pedido & Carrinho: `app/models/Pedido.php` (reservas temporárias de 10 min, limpeza automática de expirações, validação de teto por comprador, checkout atômico transacional `FOR UPDATE` e busca de pedidos)
  - [x] Aprimoramento de Lotes e Estoque: `app/models/LoteVenda.php` e `app/models/EstoqueLojinha.php` (método `buscarLoteAtivoParaSetor`, catálogo `listarProdutosVitrine` com fórmula de concorrência, `obterSaldoDisponivel` e helpers de log)
  - [x] Controller da Lojinha: `app/controllers/LojinhaController.php` (identificação de colaborador, vitrine, sacola com contagem regressiva, checkout transacional, comprovante digital e endpoints assíncronos)
  - [x] Layouts da Lojinha: `app/views/layouts/lojinha_header.php` e `app/views/layouts/lojinha_footer.php` (badge de identificação de setor, contador dinâmico de sacola e atalho de saída)
  - [x] View Identificação: `app/views/lojinha/login.php` (entrada rápida por matrícula corporativa e verificação de lotes abertos para o setor)
  - [x] View Vitrine: `app/views/lojinha/vitrine.php` (banner de vigência e cota do lote, cards de produtos com fotos e botões de 80px)
  - [x] View Sacola: `app/views/lojinha/carrinho.php` (cronômetro regressivo da reserva de 10 minutos, listagem com remoção e botão 80px "Finalizar Pedido")
  - [x] View Comprovante: `app/views/lojinha/sucesso.php` (código de retirada em destaque `headline-xl` 32px, tabela de itens e instruções de galpão)
  - [x] Roteador Central Atualizado: `public/index.php` (rotas `/lojinha/*` e endpoints `/api/carrinho/*` e `/api/estoque/saldo` integrados)
- [ ] **Fase 5: Lista de Separação, Relatórios e Testes Integrados**

---

## Próximo Passo
Iniciar a implementação técnica da **Fase 5: Lista de Separação, Relatórios e Testes Integrados**, construindo a lista unificada de separação física no galpão agrupada por matrícula e colaborador (`/gestao/separacao`), o `RelatorioController` com extratos de esforço/produtividade e perdas de repack, validação final dos logs e execução dos testes integrados de aceitação.
