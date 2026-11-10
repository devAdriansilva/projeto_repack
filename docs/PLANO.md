# PLANO DE IMPLEMENTAÇÃO INCREMENTAL

Este documento descreve as 5 fases de desenvolvimento do **Projeto Repack**, extraídas diretamente da Seção 25 do `docs/FSD.md`.

---

## Fase 1: Fundação e Banco de Dados
- **Objetivo:** Estabelecer a infraestrutura de backend, conexão segura PDO e esquema relacional do MySQL via migrations CLI.
- **Entregáveis:**
  - `app/config/config.php` (configurações do sistema, credenciais de banco e constantes protegidas).
  - `app/config/database.php` (classe singleton PDO com tratamento de erros e fallback).
  - `database/migrator.php` (script CLI idempotente com proteção contra acesso HTTP).
  - `database/migrations/001_create_tables.sql` (criação de todas as 13 tabelas, PKs, FKs e índices compostos).
  - `database/migrations/002_seed_initial_data.sql` (carga inicial de usuários operadores, gestor e produtos de teste).
  - `public/index.php` (Front Controller base e roteador de requisições).
- **Critério de Conclusão:** Migrations executadas com sucesso via terminal sem erros, tabela `migrations_log` preenchida e conexão PDO validada.

---

## Fase 2: Interface do Operador (Urna Eletrônica)
- **Objetivo:** Construir a interface responsiva da reembalagem com foco em acessibilidade universal, alvos de toque de 80px e operação offline.
- **Entregáveis:**
  - `public/assets/css/bootstrap.min.css` e `public/assets/css/urna.css` (tokens de cores e regras do `docs/DESIGN.md`).
  - `public/assets/js/touch-handler.js` (otimização de toque para luvas anti-corte: `touch-action: manipulation` e `touchstart`).
  - `public/assets/js/app-offline.js` (gerenciador de `IndexedDB` e fila de sincronização em segundo plano).
  - `public/service-worker.js` e `public/manifest.json` (cache offline da interface PWA).
  - `app/views/operador/home.php` (cabeçalho quiosque com troca rápida de operador e os 2 grandes botões de 80px).
  - `app/views/operador/avaria_passo1.php`, `avaria_passo2.php` e `avaria_resumo.php` (roteamento inteligente das 5 avarias e conferência Corrigir/Confirmar).
  - `app/views/operador/cronometro.php` e `cronometro_resumo.php` (cronômetro com visor 32px e registro de pacotes).
  - `app/controllers/AvariaController.php` e `app/controllers/ProdutividadeController.php`.
  - Endpoints assíncronos: `/api/bo/salvar.php`, `/api/bo/sincronizar.php`, `/api/produtividade/salvar.php` e `/api/produtividade/sincronizar.php`.
- **Critério de Conclusão:** Lançamentos de B.O. com roteamento automático (lojinha vs. descarte) e apontamentos de produtividade salvos online e sincronizados em caso de desconexão.

---

## Fase 3: Painel Oculto da Gestão
- **Objetivo:** Implementar o ambiente administrativo restrito da Coordenadora acessível pela engrenagem minimalista.
- **Entregáveis:**
  - Modal de autenticação segura via credenciais com `password_verify()` em `app/controllers/AuthController.php`.
  - `app/views/gestao/dashboard.php` (painel de indicadores consolidados de avarias, tempos e saldos).
  - `app/views/gestao/produtos.php` (cadastro e edição de produtos com upload validado de imagens até 2MB).
  - `app/views/gestao/lotes.php` (planejamento, abertura e encerramento de lotes de venda com restrição por setor e limite por pessoa).
  - `app/views/gestao/estoque.php` (controle de saldo total, reservas ocultas da gestão e baixas administrativas com justificativa).
  - `app/views/gestao/correcao_bo.php` (retificação de apontamentos incorretos de operadores com histórico).
  - `app/controllers/GestaoController.php`.
- **Critério de Conclusão:** Área gerencial totalmente protegida por sessão restrita, manipulação de lotes, reservas ocultas e produtos funcional.

---

## Fase 4: Vitrine e Carrinho da Lojinha
- **Objetivo:** Disponibilizar o e-commerce interno corporativo para colaboradores com controle estrito de lotes e reserva temporária.
- **Entregáveis:**
  - `app/views/lojinha/login.php` (identificação rápida por matrícula e setor).
  - `app/views/lojinha/vitrine.php` (cards de produtos, fotos, saldos públicos reais calculados com fórmula de reserva).
  - `app/views/lojinha/carrinho.php` (sacola com contagem regressiva de 10 minutos para liberação de trava).
  - `app/views/lojinha/sucesso.php` (comprovante digital do pedido com número único).
  - `app/controllers/LojinhaController.php`.
  - Endpoints: `/api/carrinho/adicionar.php`, `/api/carrinho/remover.php` e `/api/estoque/saldo.php`.
  - Mecanismo transacional de checkout (BEGIN TRANSACTION / COMMIT) com abate definitivo do estoque.
- **Critério de Conclusão:** Concorrência controlada por travas de 10 minutos, teto de compra por colaborador respeitado e pedidos gravados com sucesso.

---

## Fase 5: Lista de Separação, Relatórios e Testes Integrados
- **Objetivo:** Finalizar os extratos de separação física, validações de contingência e testes de aceitação ponta a ponta.
- **Entregáveis:**
  - `app/views/gestao/separacao.php` (lista unificada para conferência e separação física no galpão, agrupada por matrícula e colaborador).
  - `app/controllers/RelatorioController.php` (extrato analítico de esforço/produtividade e relatório de perdas).
  - Validação do fallback de erros para `logs/app_errors.log` e auditoria em `logs/security.log`.
  - Testes de validação offline (PWA) e simulação de toques com luva.
- **Critério de Conclusão:** Todos os 9 critérios de aceitação técnica e funcional do `docs/FSD.md` validados e aprovados.
