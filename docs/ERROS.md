# REGISTRO DE ERROS E LIÇÕES APRENDIDAS

Este documento é o arquivo vivo para catalogação de incidentes, falhas técnicas, causas-raiz e contramedidas aplicadas durante o ciclo de desenvolvimento do **Projeto Repack**.

---

## Histórico de Erros Catalogados

### ERR-001: Commit de Transação em DDL no Migrador CLI
- **Data/Hora:** 2026-11-10 06:19
- **Fase de Ocorrência:** Fase 1 - Fundação e Banco de Dados
- **Sintoma:** O migrador falhou ao aplicar `001_create_tables.sql` com a exceção PDO `There is no active transaction`.
- **Causa-Raiz:** No MySQL/MariaDB, instruções DDL (`CREATE TABLE`, `ALTER TABLE`, etc.) realizam *commit implícito* imediato da transação ativa no servidor de banco de dados. Ao tentar invocar `$pdo->commit()` posteriormente no script PHP, a transação não existia mais no contexto do PDO.
- **Solução Aplicada:** Ajustado o método de execução no `database/migrator.php` para não forçar encapsulamento transacional em scripts que contenham comandos DDL estruturais, registrando o log na tabela `migrations_log` imediatamente após o `exec()` bem-sucedido.
- **Como Evitar:** Lembrar que operações DDL no padrão ANSI/MySQL são atômicas por definição do engine InnoDB e não suportam rollback transacional de múltiplos `CREATE TABLE`.

### ERR-002: Parâmetros Nomeados Duplicados em Queries PDO com Emulação Desativada
- **Data/Hora:** 2026-11-10 07:53
- **Fase de Ocorrência:** Fase 4 - Vitrine e Carrinho da Lojinha
- **Sintoma:** Falha na execução da query dinâmica de saldo em vitrine com a exceção PDO `SQLSTATE[HY093]: Invalid parameter number`.
- **Causa-Raiz:** Com `PDO::ATTR_EMULATE_PREPARES => false`, o driver nativo do MySQL não permite reutilizar o mesmo placeholder nomeado (ex: `:usuario_id` ou `:produto_id`) em múltiplas subqueries da mesma instrução SQL preparada.
- **Solução Aplicada:** Desdobramento dos parâmetros com sufixos identificadores únicos (ex: `:uid_outros_1`, `:uid_meu`, `:uid_outros_2`, `:produto_id_ct`, `:produto_id_p`) e passagem do array associativo correspondente no `execute()`.
- **Como Evitar:** Em consultas com múltiplas subqueries na camada PDO pura, sempre atribuir aliases únicos para cada parâmetro nomeado.

### ERR-003: Exposição de Diretórios Sensíveis e Falta de CSRF no Checkout da Lojinha
- **Data/Hora:** 2026-11-10 09:00
- **Fase de Ocorrência:** Revisão de Segurança
- **Sintoma:** Arquivos sensíveis de configuração (`app/config/config.php`), logs (`logs/*.log`), migrations e scripts CLI poderiam ser lidos diretamente via HTTP sob Apache desconfigurado, e ações de checkout/carrinho da Lojinha não validavam token CSRF.
- **Causa-Raiz:** Ausência de arquivos `.htaccess` de controle de acesso nos diretórios internos e de verificação de token nos formulários do colaborador.
- **Solução Aplicada:** Criação de regras `.htaccess` bloqueando acesso web a `app/`, `database/`, `logs/`, `tests/` e desativando engine PHP em `public/uploads/`. Implementação e validação de tokens CSRF nas rotas de checkout, remoção e esvaziamento do carrinho da lojinha, além de ocultação de mensagens internas de exceção em respostas JSON de erro.
- **Como Evitar:** Aplicar princípio de defesa em profundidade em todas as camadas (servidor web, middleware PHP e formulários de frontend).

### ERR-004: Quebra Visual (CSS/JS) e Rotas por Inconsistência do Prefixo APP_URL
- **Data/Hora:** 2026-11-10 21:00
- **Fase de Ocorrência:** Modo Manutenção (Ajuste de UI e Navegação)
- **Sintoma:** Interface renderizando como HTML cru (fontes padrão serifadas, modais abertos na tela) e páginas adicionais (Lojinha e Gestão) inacessíveis ou gerando erro 404.
- **Causa-Raiz:** `APP_URL` estava fixado estaticamente como `'/projeto_repack'`, forçando chamadas a `/projeto_repack/assets/css/urna.css` mesmo em servidores configurados na raiz do domínio (como Alwaysdata ou servidores locais). Sem carregar o CSS, os modais perdiam o `display: none` e o layout desmoronava.
- **Solução Aplicada:** Implementada detecção dinâmica inteligente de `APP_URL` em `app/config/config.php` que se adapta à raiz (`""`), Alwaysdata ou subdiretórios; adicionada proteção anti-FOUC em `app/views/layouts/header.php`; padronizados links em views e layouts; e inserido menu de navegação rápida na Home para alternar com 1 clique entre Urna, Lojinha e Gestão.
- **Como Evitar:** Nunca fixar prefixos de URL de forma estática; utilizar auto-detecção desacoplada com suporte tanto para raiz de domínio quanto subpastas.

### ERR-005: Erro Fatal `Call to undefined method AuthController::exigirAutenticacao()` na Separação
- **Data/Hora:** 2026-11-14 05:15
- **Fase de Ocorrência:** Modo Manutenção (Substituição de Itens na Separação)
- **Sintoma:** Ao tentar aplicar a substituição de item de um pedido na aba Pedidos/Separação em produção, o sistema gerava erro fatal: `Fatal error: Uncaught Error: Call to undefined method AuthController::exigirAutenticacao() in RelatorioController.php on line 129`.
- **Causa-Raiz:** O controller `AuthController` possui o método guard `exigirGestao()`. Ao implementar o endpoint de substituição em `RelatorioController.php`, foi chamada a assinatura inexistente `AuthController::exigirAutenticacao()`.
- **Solução Aplicada:** Corrigida a chamada em `RelatorioController.php` para `AuthController::exigirGestao()`, adicionada validação de token CSRF, e implementado o método estático `public static function exigirAutenticacao(): void { self::exigirGestao(); }` em `AuthController.php` como blindagem retrocompatível.
- **Como Evitar:** Sempre conferir os métodos da classe `AuthController` ou criar aliases defensivos quando mais de um nome de método guard for comum entre desenvolvedores.
