# GUIA DE SOLICITAÇÃO DE MUDANÇAS (COMO PEDIR MUDANÇAS)

Este documento fornece instruções e modelos prontos de prompts para solicitar evoluções, correções ou manutenções no **Projeto Repack** aos agentes de Inteligência Artificial ou desenvolvedores.

---

## 1. Princípios Fundamentais para Pedir Mudanças
Ao solicitar qualquer alteração no código do Projeto Repack, garanta que seu prompt respeite:
1. **Padrão MVC em PHP Puro:** Não solicite inclusão de frameworks pesados (Laravel, Symfony, etc.).
2. **Filosofia da Urna Eletrônica:** Quaisquer alterações na interface do Operador devem preservar alvos de toque mínimos de 80px, botões de alto contraste, dupla confirmação e compatibilidade com luvas anti-corte.
3. **Segurança Obrigatória:** Toda query deve usar Prepared Statements PDO nativos, toda saída HTML deve usar `htmlspecialchars()` e formulários POST devem ter proteção CSRF.
4. **Governança por Documentos:** As alterações devem estar alinhadas com `docs/FSD.md` e `docs/DESIGN.md`, e os arquivos `docs/STATUS.md` e `docs/ERROS.md` devem ser mantidos atualizados.

---

## 2. Modelos Prontos de Prompts

### Modelo 1: Adicionar Novo Campo a uma Entidade Existente
Use este modelo quando precisar acrescentar um novo dado cadastral ou transacional.

```markdown
Por favor, atue como desenvolvedor em Modo Manutenção no Projeto Repack.

Necessidade:
Adicionar o campo `codigo_barras` (VARCHAR 60, opcional) na entidade de Produtos.

Tarefas obrigatórias:
1. Criar a migration `database/migrations/003_adicionar_codigo_barras_produtos.sql` adicionando a coluna na tabela `produtos`.
2. Executar a migration via CLI com `php database/migrator.php`.
3. Atualizar o Model `app/models/Produto.php` para contemplar o novo campo nas operações de listagem, inserção e atualização.
4. Atualizar a View administrativa `app/views/gestao/produtos.php` incluindo o campo no formulário modal de cadastro/edição e na tabela.
5. Garantir que as validações de segurança (sanitização XSS e Prepared Statements) sejam estritamente mantidas.
6. Testar o fluxo e atualizar `docs/STATUS.md`.
```

---

### Modelo 2: Ajuste de Regra de Negócio ou Cálculo
Use este modelo para alterar uma regra de lote, cálculo de estoque ou roteamento de B.O.

```markdown
Por favor, atue como desenvolvedor em Modo Manutenção no Projeto Repack.

Necessidade:
Ajustar o tempo de expiração da reserva temporária de carrinho na Lojinha Interna de 10 minutos para 15 minutos, além de permitir configurar esse valor via constante global.

Tarefas obrigatórias:
1. Atualizar a constante `CARRINHO_TIMEOUT_MINUTOS` em `app/config/config.php` para 15.
2. Revisar o Model `app/models/Pedido.php` e `app/models/EstoqueLojinha.php` garantindo que todas as consultas e limpezas utilizem a constante `CARRINHO_TIMEOUT_MINUTOS`.
3. Atualizar a exibição da contagem regressiva em `app/views/lojinha/carrinho.php`.
4. Executar os testes automatizados com `php tests/test_fase5.php` e validar se a regra permanece consistente.
5. Atualizar o registro em `docs/STATUS.md`.
```

---

### Modelo 3: Criar um Novo Relatório ou Exportação
Use este modelo para criar uma nova tela de consulta ou exportação analítica.

```markdown
Por favor, atue como desenvolvedor em Modo Manutenção no Projeto Repack.

Necessidade:
Criar um novo relatório administrativo de "Histórico de Baixas Administrativas e Avarias Tardias" no painel da Gestão.

Tarefas obrigatórias:
1. Implementar método `obterHistoricoBaixas(filtros)` no Model `app/models/EstoqueLojinha.php` consultando a tabela `baixas_administrativas` com Prepared Statements.
2. Criar a View `app/views/gestao/relatorio_baixas.php` com filtros por período e produto, exibindo data, produto, quantidade, motivo e usuário responsável.
3. Adicionar o método de ação no `app/controllers/RelatorioController.php` protegido pelo guard `AuthController::exigirGestao()`.
4. Registrar as rotas `/gestao/relatorios/baixas` e `/gestao/relatorios/baixas/exportar` no Front Controller `public/index.php`.
5. Adicionar link de acesso no menu de navegação em `app/views/layouts/gestao_header.php`.
6. Atualizar `docs/STATUS.md`.
```

---

### Modelo 4: Correção de Bug ou Falha Técnica
Use este modelo quando um erro for reportado na operação ou nos logs.

```markdown
Por favor, atue como desenvolvedor em Modo Manutenção no Projeto Repack.

Problema Reportado:
[Descreva o sintoma exato, URL onde ocorreu e mensagem de erro se houver].

Tarefas obrigatórias:
1. Consultar os arquivos de log `logs/app_errors.log` e `logs/security.log` para identificar a causa-raiz técnica.
2. Reproduzir a falha localmente sem alterar outros módulos em produção.
3. Aplicar o patch cirúrgico no Model/Controller/View afetado, mantendo a integridade da arquitetura existente.
4. Executar `php tests/test_fase5.php` para garantir ausência de regressões.
5. Documentar o incidente em `docs/ERROS.md` (formato ERR-xxx com data, sintoma, causa-raiz e solução).
6. Atualizar `docs/STATUS.md`.
```

---

### Modelo 5: Ajuste de Interface / Usabilidade na Urna Eletrônica
Use este modelo quando for necessário adaptar elementos da tela do operador fabril.

```markdown
Por favor, atue como desenvolvedor em Modo Manutenção no Projeto Repack.

Necessidade:
[Descreva a melhoria na interface do Operador].

Restrições Mandatórias:
1. Preservar rigorosamente a altura mínima de 80px (`spacing.touch-target`) para todos os botões clicáveis.
2. Manter a paleta de cores de alto contraste definida no `docs/DESIGN.md`:
   - Primário/Confirmar: `#10B981` (Verde)
   - Secundário/Ações: `#3B82F6` (Azul)
   - Alerta/Corrigir: `#F59E0B` (Laranja)
   - Descarte/Perigo: `#EF4444` (Vermelho)
3. Não incluir tabelas, botões de exclusão ou dados complexos na tela do Operador leigo.
4. Manter o listener otimizado para luvas anti-corte (`touch-action: manipulation` e `touchstart`).
5. Atualizar `docs/STATUS.md`.
```
