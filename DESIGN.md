---
name: Projeto Repack Design System (Urna Eletrônica)
colors:
  primary: '#10B981'
  on-primary: '#FFFFFF'
  secondary: '#3B82F6'
  on-secondary: '#FFFFFF'
  warning: '#F59E0B'
  on-warning: '#FFFFFF'
  danger: '#EF4444'
  on-danger: '#FFFFFF'
  background: '#F3F4F6'
  surface: '#FFFFFF'
  on-surface: '#111827'
  on-surface-muted: '#6B7280'
  border: '#D1D5DB'
  gear-icon: '#D1D5DB'
typography:
  font-family: 'Inter, sans-serif'
  headline-xl:
    fontSize: '32px'
    fontWeight: '800'
    lineHeight: '40px'
  headline-lg:
    fontSize: '24px'
    fontWeight: '700'
    lineHeight: '32px'
  button-lg:
    fontSize: '24px'
    fontWeight: '700'
    lineHeight: '32px'
  body-lg:
    fontSize: '18px'
    fontWeight: '500'
    lineHeight: '28px'
  body-md:
    fontSize: '16px'
    fontWeight: '400'
    lineHeight: '24px'
spacing:
  xs: '8px'
  sm: '16px'
  md: '24px'
  lg: '32px'
  xl: '48px'
  touch-target: '80px'
rounded:
  sm: '4px'
  md: '8px'
  lg: '16px'
  full: '9999px'
---

## Identidade Visual e Filosofia (Urna Eletrônica)

O Design System do **Projeto Repack** é construído sobre a premissa de acessibilidade universal, fortemente inspirado na filosofia visual das urnas eletrônicas. A interface do operador (perfil leigo) deve ser à prova de erros, apresentando fluxos de tela única com símbolos óbvios e total ausência de distrações. A interface de gestão será mais detalhada (tabelas e configurações), mas herdará a mesma clareza e estrutura de cores do sistema principal.

## Cores (Uso Objetivo)

A paleta de cores foca no alto contraste e na clareza semântica para dispensar a necessidade de leitura densa:
- **Primary (#10B981 - Verde):** Uso exclusivo para botões de "Confirmar" ações e indicar sucesso.
- **Secondary (#3B82F6 - Azul):** Ações padrão de fluxo, como "Registrar Avaria" ou o botão "Iniciar" do cronômetro.
- **Warning (#F59E0B - Laranja):** Botões de "Corrigir" ou "Voltar" dentro das etapas de dupla checagem.
- **Danger (#EF4444 - Vermelho):** Ações de descarte grave (Furo, Micro Furo) ou paradas críticas.
- **Background (#F3F4F6) e Surface (#FFFFFF):** Fundos do aplicativo em cinza claro com cartões de conteúdo em branco, garantindo contraste máximo com os textos escuros (#111827).
- **Gear Icon (#D1D5DB - Cinza claro):** O ícone de configurações na tela inicial deve se camuflar no fundo, evitando cliques acidentais pelo operador leigo.

## Tipografia

O sistema utiliza a família tipográfica **Inter**, priorizando números e rótulos robustos.
- **headline-xl (32px):** Exclusivo para os marcadores do cronômetro de produtividade e grandes mensagens de status.
- **button-lg (24px):** Todo o texto dentro dos botões operacionais deve ser gigante e em negrito.
- **body-lg (18px):** Usado para descrições de produtos na vitrine e nos formulários de gestão.

## Espaçamento e Layout

- **Touch Target (80px):** A altura mínima de qualquer área clicável (botões) na interface do Operador deve ser de 80px, criando botões gigantes que evitam esbarrões ou toques no local errado.
- **Layout Simples (Home):** A tela inicial do operador deve conter apenas de 2 a 3 botões imensos posicionados ao centro, com distância de `spacing.xl` (48px) entre eles.
- **Layout Complexo (Gestão/Vitrine):** Uso estruturado de listas, cards e tabelas, com margens padronizadas de `spacing.md` (24px).

## Estilo de Componentes

- **Botões de Ação do Operador:** Fundo sólido (Azul ou Verde), bordas fortemente arredondadas (`rounded.lg: 16px`), textos gigantes acompanhados de ícones altamente descritivos.
- **Telas de Corrigir / Confirmar:** Telas limpas exibindo o resumo do apontamento no centro (em `headline-lg`). No rodapé, dividindo a tela, dois botões: "Corrigir" (Laranja) e "Confirmar" (Verde).
- **Ícone Oculto de Acesso Rápido:** Um ícone minimalista de engrenagem, sem texto, posicionado discretamente num canto superior ou inferior da Home.
- **Cards da Lojinha Virtual:** Componentes em fundo branco (`surface`), bordas `rounded.md`, exibindo a foto do produto, limites de quantidade e um botão de adição.
- **Tabelas Gerenciais:** Estrutura limpa baseada em linhas divisórias (`border: #D1D5DB`) sem cores zebradas excessivas, priorizando a visualização do cálculo "Total - Reservas = Disponível".

## Estados Visuais

- **Sucesso:** Feedback imediato utilizando a cor Primary (Verde) e ícones de confirmação grandes.
- **Erro:** Cores em Danger (Vermelho) com textos simples, sem exibição de jargões de sistema.
- **Carregamento:** Bloqueio temporário da tela com um indicador centralizado para evitar múltiplos cliques no mesmo botão de apontamento ou cronômetro.
- **Vazio:** Produtos esgotados na lojinha devem exibir "Esgotado" em cinza (`on-surface-muted`) e desabilitar o botão de interação visualmente.