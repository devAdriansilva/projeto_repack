# STATUS DO PROJETO

## Estado Atual
- **Fase Atual:** Sistema em Produção (Modo Manutenção e Evolução Contínua)
- **Status Geral:** Sistema Repack 100% construído, testado, auditado em segurança, documentado e implantado com sucesso no ambiente de produção (Alwaysdata - PHP 8.2, Apache e MySQL). O banco de dados foi migrado via CLI, as 13 tabelas relacionais foram criadas e a aplicação está online e operacional em `https://repack.financas-facil-atelie.alwaysdata.net`. O repositório permanece sob governança estrita de Modo Manutenção em `AGENTS.md`.

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
  - [x] View Sacola: `app/views/lojinha/carrinho.php` (cronômometro regressivo da reserva de 10 minutos, listagem com remoção e botão 80px "Finalizar Pedido")
  - [x] View Comprovante: `app/views/lojinha/sucesso.php` (código de retirada em destaque `headline-xl` 32px, tabela de itens e instruções de galpão)
  - [x] Roteador Central Atualizado: `public/index.php` (rotas `/lojinha/*` e endpoints `/api/carrinho/*` e `/api/estoque/saldo` integrados)
- [x] **Fase 5: Lista de Separação, Relatórios e Testes Integrados (Testada e Validada)**
  - [x] Model Pedido & Separação: `app/models/Pedido.php` (métodos `listarParaSeparacao` com agrupamento por colaborador/matrícula, `atualizarStatus` de pedido e `obterEstatisticasSeparacao`)
  - [x] Model Relatório B.O.: `app/models/ApontamentoBO.php` (método `obterRelatorioSintetico` com apuração de volume recebido, taxa de recuperação para a lojinha, descarte e agrupamentos por tipo e por produto)
  - [x] Model Extrato de Produtividade: `app/models/ApontamentoProdutividade.php` (método `obterExtratoAnalitico` com cálculo de pacotes montados por hora por operador e tempo médio de ciclo por produto)
  - [x] Model Log do Sistema: `app/models/LogSistema.php` (auditoria imediata em `logs/security.log` e contingência no banco de dados)
  - [x] Controller de Relatórios: `app/controllers/RelatorioController.php` (rotas `/gestao/separacao`, `/gestao/separacao/status`, `/gestao/separacao/exportar`, `/gestao/relatorios` e `/gestao/relatorios/exportar`)
  - [x] View Separação Física: `app/views/gestao/separacao.php` (cards analíticos por colaborador, checkboxes de conferência interativos para tablet, alteração assíncrona de status, campos de assinatura manual e CSS de impressão para prancheta física de galpão)
  - [x] View Relatórios e Extratos: `app/views/gestao/relatorios.php` (filtros dinâmicos de período, barras visuais de proporção de avarias, tabelas detalhadas e exportação CSV para Excel)
  - [x] Layout Gestão Atualizado: `app/views/layouts/gestao_header.php` (links diretos para Separação e Relatórios)
  - [x] Roteador Central Atualizado: `public/index.php` (rotas `/gestao/separacao*` e `/gestao/relatorios*` integradas)
  - [x] Testes Integrados Automatizados: `tests/test_fase5.php` e `tests/test_router.php` (35 asserções validadas cobrindo integralmente os 9 critérios de aceitação do `docs/FSD.md` Seção 26)
- [x] **Revisão de Segurança e Mitigação de Vulnerabilidades (Auditoria Concluída e Aprovada)**
  - [x] Proteção Web Server (.htaccess): Bloqueio rigoroso de acesso web direto às pastas `app/`, `database/`, `logs/`, `tests/` e arquivos ocultos; desativação de engine PHP em `public/uploads/` para prevenção de execução de scripts arbitrários.
  - [x] Proteção CSRF na Lojinha: Adição e validação estrita de tokens CSRF em checkout, remoção e esvaziamento de carrinho (`app/controllers/LojinhaController.php` e `app/views/lojinha/carrinho.php`).
  - [x] Prevenção de Information Disclosure: Ocultação de traces e mensagens brutas de exceções de banco de dados em endpoints de API (`/api/health`, `/api/bo/*`, `/api/produtividade/*`).
  - [x] Proteção de Scripts CLI: Bloqueio contra execução HTTP em `tests/test_fase5.php` e `tests/test_router.php`.
  - [x] Hardening de Sessão: Configuração dinâmica de `session.cookie_secure` em ambientes HTTPS além de `HttpOnly`, `SameSite=Strict` e `gc_maxlifetime`.
  - [x] Integridade de Rotas e Subdiretórios: Parametrização consistente de `APP_URL` em layouts e formulários da Lojinha e Gestão.
- [x] **Documentação de Manutenção e Governança Contínua (Concluída e Ativa)**
  - [x] Criação de `docs/MANUTENCAO.md` (manual técnico detalhado com visão geral, stack, passos locais, mapa de pastas, banco de dados, fluxos de autenticação, extensão do sistema e diretrizes de segurança).
  - [x] Criação de `docs/COMO-PEDIR-MUDANCAS.md` (modelos padronizados de prompts para novas features, novos campos, relatórios, correções de bugs e ajustes de interface).
  - [x] Atualização de `AGENTS.md` para o Modo Manutenção com proibição estrita de reescritas totais e leitura obrigatória dos novos manuais.
  - [x] Modo Manutenção formalmente ativo e registrado.
- [x] **Preparação, Orquestração e Conclusão de Deploy (Alwaysdata / Produção)**
  - [x] Criação de `deploy-exclude.txt` aplicando a Regra de Ouro (isolamento de `docs/`, `tests/`, `.git/`, `.env`, dumps e logs locais).
  - [x] Parametrização dinâmica de banco e URLs em `app/config/config.php` via variáveis de ambiente com fallback retrocompatível.
  - [x] Criação da automação de CI/CD em `.github/workflows/deploy.yml` (testes automatizados, rsync seguro via SSH, migrations CLI e permissões).
  - [x] Criação do script de deploy alternativo via CLI em `scripts/deploy_alwaysdata.sh`.
  - [x] Guia passo a passo de configuração na Alwaysdata documentado.
  - [x] Execução e homologação do deploy em produção concluída com sucesso em 10/11/2026.
  - [x] Banco de dados migrado via CLI (`001_create_tables.sql` e `002_seed_initial_data.sql`) com integridade referencial.
  - [x] Aplicação online, segura e operacional em `https://repack.financas-facil-atelie.alwaysdata.net`.

- [x] **Manutenção e Evolução Contínua (Registro de Intervenções)**
  - [x] **MANUT-001 (10/11/2026):** Resolução do carregamento de assets estáticos (CSS/JS) e roteamento de páginas adicionais.
    - Implementação de auto-detecção dinâmica de `APP_URL` em `app/config/config.php` (compatível com raiz `/`, subpastas, localhost e Alwaysdata).
    - Normalização do Front Controller em `public/index.php` para suporte a múltiplos contextos de roteamento.
    - Proteção inline anti-FOUC em `app/views/layouts/header.php` para impedir vazamento visual de modais.
    - Sincronização de todos os scripts, chamadas fetch assíncronas e links de layouts com `APP_URL`.
    - Adição de barra de navegação rápida na Urna (`app/views/operador/home.php`) facilitando acesso direto à Lojinha de Saldos e ao Painel de Gestão.
    - Catalogação da falha e contramedida em `docs/ERROS.md` (ERR-004) e validação da suíte de 35 testes automatizados com 100% de sucesso.

---

## Próximo Passo
Sistema publicado em produção com sucesso. Repositório em Modo de Manutenção e Evolução Contínua para suporte operacional, monitoramento de logs (`logs/app_errors.log`, `logs/security.log`) e atendimento a solicitações de melhorias via `docs/COMO-PEDIR-MUDANCAS.md`.
