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
