# MODO MANUTENÇÃO - CONTEXTO E REGRAS PARA AGENTES IA (PROJETO REPACK)

## 1. Estado Operacional: Modo Manutenção Ativo
O desenvolvimento inicial das 5 fases do **Projeto Repack** foi concluído com 100% de cobertura dos critérios funcionais e testes automatizados. O repositório agora opera sob **Modo de Manutenção e Evolução Contínua**.

> **ATENÇÃO - PROIBIÇÃO DE REESCRITAS TOTAIS:**  
> É estritamente proibido refatorar ou reescrever módulos inteiros, mudar a arquitetura MVC pura em PHP, introduzir frameworks pesados ou alterar padrões consolidados. Quaisquer modificações devem ser pontuais, incrementais e cirúrgicas.

---

## 2. Documentos Normativos Obrigatórios
Antes de realizar qualquer intervenção de código ou responder a solicitações de manutenção, você deve ler obrigatoriamente:
- `docs/MANUTENCAO.md`: Manual técnico com visão geral da stack, credenciais locais, mapa de pastas, migrations e passos de extensão.
- `docs/COMO-PEDIR-MUDANCAS.md`: Modelos padronizados de solicitação de manutenção.
- `docs/FSD.md`: Especificação funcional normativa, esquema relacional e regras de negócio.
- `docs/DESIGN.md`: Tokens do design system e filosofia da Urna Eletrônica (botões de 80px e alto contraste).
- `docs/STATUS.md`: Histórico e estado atual das manutenções do projeto.
- `docs/ERROS.md`: Registro de incidentes técnicos e contramedidas aplicadas.

> **Regra Estrita de Links:** Todos os caminhos de arquivos citados por você devem ser estritamente relativos à raiz do projeto (ex: `docs/FSD.md`, `app/config/config.php`). É terminantemente proibido o uso de caminhos absolutos ou esquemas `file:///`.

---

## 3. Diretrizes Arquiteturais e de Segurança Inegociáveis
1. **Stack Tecnológica:**
   - Backend: PHP puro 8.1+ em padrão MVC.
   - Banco de Dados: MySQL 8.0+ via PDO com Prepared Statements nativos obrigatórios (`PDO::ATTR_EMULATE_PREPARES => false`). Jamais concatene strings em queries SQL.
   - Frontend: HTML5 semântico, CSS3 com Bootstrap 5.3 local (sem CDNs externas) e JavaScript Vanilla puro.
   - PWA Offline-First: Service Worker e IndexedDB (`RepackDB`) para contingência na bancada de repack.
2. **Segurança e Hardening:**
   - Proibido o uso de arquivos `.env` para credenciais. Use `app/config/config.php` protegido.
   - Sanitização mandatória em saídas HTML via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
   - Proteção CSRF obrigatória nos formulários de gestão e checkout da lojinha.
   - Migrations executadas exclusivamente via CLI (`php database/migrator.php`) com controle de idempotência em `migrations_log`.
   - Fallback de logs: falhas de PDO gravam em `logs/app_errors.log` e alertas em `logs/security.log`.
3. **Usabilidade Operacional (Urna Eletrônica):**
   - Altura mínima de 80px para qualquer botão de ação na interface do Operador (`spacing.touch-target`).
   - Suporte a luvas anti-corte com `touch-action: manipulation` e listeners `touchstart`.
   - Dupla confirmação linear: "Corrigir" em laranja (`#F59E0B`) e "Confirmar" em verde (`#10B981`).
   - Acesso administrativo restrito camuflado via ícone de engrenagem cinza clara (`#D1D5DB`) sem texto.

---

## 4. Governança de Manutenções
- Toda nova migração de banco deve ser criada em `database/migrations/` e executada via CLI.
- Após qualquer intervenção de código, execute os testes automatizados com `php tests/test_fase5.php`.
- Atualize sempre `docs/STATUS.md` detalhando a manutenção executada.
- Se ocorrer qualquer falha durante a manutenção, documente-a imediatamente em `docs/ERROS.md`.
