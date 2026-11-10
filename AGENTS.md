# CONTEXTO E REGRAS PARA AGENTES IA (PROJETO REPACK)

## 1. Visão Geral
Você está operando no repositório do **Projeto Repack**, um sistema web e PWA interno em PHP puro e MySQL para o setor de reembalagem de uma distribuidora de bebidas.

## 2. Documentos Normativos Obrigatórios
Toda e qualquer decisão de código, regra de negócio, fluxo ou layout deve obedecer estritamente aos documentos:
- `docs/FSD.md`: Especificação funcional, modelos de dados, endpoints e regras de arquitetura.
- `docs/DESIGN.md`: Tokens de design system, usabilidade da Urna Eletrônica (botões de 80px, alto contraste).
- `docs/INSUMOS.md`: Inventário e governança de assets.
- `docs/PLANO.md`: Fases incrementais de desenvolvimento.
- `docs/STATUS.md`: Estado atual e controle de avanço do projeto.
- `docs/ERROS.md`: Registro de incidentes e contramedidas técnicas.

> **Regra Estrita de Links:** Todos os caminhos de arquivos citados por você devem ser estritamente relativos à raiz do projeto (ex: `docs/FSD.md`, `app/config/config.php`). É terminantemente proibido o uso de caminhos absolutos ou esquemas `file:///`.

## 3. Diretrizes Arquiteturais e de Segurança
1. **Stack Tecnológica:**
   - Backend: PHP puro 8.1+ em padrão MVC. Não use frameworks pesados externos.
   - Banco de Dados: MySQL 8.0+ via PDO com Prepared Statements nativos obrigatórios em todas as queries. Proibida concatenação direta de SQL.
   - Frontend: HTML5, CSS3 com Bootstrap 5.3 local (sem dependência de CDN externa) e JavaScript Vanilla.
   - PWA Offline-First: Service Worker para cache e IndexedDB para gravação local de apontamentos de B.O. e produtividade em caso de desconexão.
2. **Segurança e Configurações:**
   - Proibido o uso de arquivos `.env` para credenciais sensíveis. Use `app/config/config.php` protegido.
   - Sanitização obrigatória de saídas HTML com `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
   - Proteção CSRF obrigatória nos formulários da Gestão.
   - Migrations devem ser executadas exclusivamente via CLI (`php database/migrator.php`) e possuir proteção contra execução duplicada e bloqueio de requisições web.
   - Fallback de logs: caso o banco falhe, registre os erros em `logs/app_errors.log`.
3. **Usabilidade Operacional (Urna Eletrônica):**
   - Altura mínima de 80px para qualquer botão de ação na interface do Operador.
   - Uso de `touch-action: manipulation` e listeners `touchstart` para suporte responsivo a luvas anti-corte.
   - Fluxos lineares passo a passo com telas de dupla confirmação ("Corrigir" em laranja `#F59E0B` e "Confirmar" em verde `#10B981`).
   - Acesso gerencial protegido por ícone de engrenagem minimalista em cinza claro (`#D1D5DB`) sem texto.

## 4. Governança e Regras de Atualização
- Não avance de fase sem testar e validar os critérios da fase anterior.
- Atualize sempre `docs/STATUS.md` ao concluir uma etapa.
- Se ocorrer qualquer falha durante a implementação, documente em `docs/ERROS.md`.
