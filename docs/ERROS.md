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
