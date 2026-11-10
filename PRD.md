# DOCUMENTO DE REQUISITOS DO PRODUTO (PRD)

## 1. Visão Geral do Produto
O aplicativo "Projeto Repack" é uma ferramenta de uso interno para o setor de reembalagem (Repack) de uma distribuidora de bebidas[cite: 3]. Seu propósito é substituir integralmente o uso de formulários manuais em papel para os registros de descarte, o apontamento de tempo da operação e o controle do estoque e da venda interna de produtos avariados aos colaboradores (a "lojinha virtual")[cite: 1, 3, 4]. O sistema baseia-se em uma arquitetura de duas interfaces distintas, assegurando acessibilidade universal para a operação e precisão na gestão[cite: 1, 3, 4].

## 2. Problema que o Sistema Resolve
O processo atual exige que os apontamentos de avarias graves (descarte) e os controles de produtividade sejam anotados no papel e posteriormente redigitados por terceiros no sistema da empresa[cite: 1, 3, 4]. Além disso, produtos com avarias leves destinados à venda interna exigem constantes contagens manuais do estoque, o que resulta em lentidão no processo de venda, falta de clareza sobre os itens disponíveis, e risco de produtos estragarem no armazenamento por falta de acompanhamento[cite: 1, 3, 4].

## 3. Objetivos do Sistema
- **Objetivo principal:** Digitalizar de ponta a ponta as rotinas do setor de Repack, erradicando o papel na anotação de perdas e tempos, e automatizando a reposição do estoque da lojinha interna[cite: 1, 3, 4].
- **Objetivos específicos:**
  - Garantir o uso fluido para qualquer operador através da filosofia da "urna eletrônica", focada na acessibilidade plena[cite: 3, 4].
  - Interligar o registro de itens levemente avariados ao saldo de estoque disponível para venda sem dependência de terceiros[cite: 3, 4].
  - Permitir flexibilidade total à gestão para aplicar regras restritivas, reservar itens e ajustar erros operacionais de forma restrita[cite: 3, 4].

## 4. Personas e Perfis de Usuário

| Perfil | Descrição simples | Principais ações no sistema | Permissões básicas |
| :--- | :--- | :--- | :--- |
| **Operador de Repack** (Perfil Leigo) | Funcionário do setor logístico com pouca habilidade tecnológica[cite: 1, 3, 4]. | Registrar a destinação das avarias e medir o tempo através do cronômetro produtivo[cite: 1, 3, 4]. | Acesso apenas aos 2 grandes botões de inserção (Home)[cite: 1, 3, 4]. Proibido editar ou excluir dados[cite: 1, 3, 4]. |
| **Gestão** (Coordenadora) | Encarregada do fluxo logístico e das diretrizes da lojinha[cite: 1, 3, 4]. | Aplicar correções, gerir baixas administrativas, impor limites máximos de venda, aplicar privilégios, configurar lotes e emitir separar pedidos[cite: 1, 3, 4]. | Acesso irrestrito a configurações, ferramentas CRUD e visões completas via botão invisibilizado[cite: 1, 3, 4]. |
| **Colaborador** (Comprador) | Funcionário de diversos setores da distribuidora[cite: 1, 3, 4]. | Acessar vitrine digital, verificar disponibilidade e garantir os produtos até o teto estipulado[cite: 1, 3, 4]. | Acesso à loja apenas nos lotes vigentes autorizados para o seu setor[cite: 1, 3, 4]. |

## 5. Escopo da Primeira Versão
- **Lançamento de B.O. com Roteamento Inteligente:** Funcionalidade que cruza as 5 opções simplificadas de avarias para encaminhar o saldo corretamente: itens "Amassada", "Def. Rótulo" ou "Emba. Danificada" integram automaticamente o estoque para vendas internas; já "Furo" ou "Micro Furo" constituem perdas absolutas (descarte)[cite: 1, 3, 4].
- **Registro de Produtividade (Cronômetro):** Interface simplificada baseada no botão "Iniciar" para fixar o horário inicial e no botão "Finalizar" para gravar a quantidade tratada e fixar a data final, dispensando relógios externos e papelada[cite: 1, 3, 4].
- **Vitrine Virtual (Lojinha):** Catálogo online nos formatos celular ou tela de balcão. Apresentará aos colaboradores a oferta visualizada com nomes e ilustrações[cite: 1, 3, 4].
- **Painel de Gestão e Regras de Lote:** Interface robusta para estipular aberturas setoriais com definição dinâmica do número máximo de itens por comprador e aplicação de benefícios especiais[cite: 1, 3, 4].
- **Correção de Lançamentos:** Tela restrita à coordenadora para solucionar incoerências cadastradas pelo operador (sem apagar botões na interface principal)[cite: 1, 3, 4].
- **Gestão de Baixas Diversas:** Ferramenta da gestão para documentar perdas administrativas, avarias supervenientes ou perecimentos ocorridos na sala de espera (removendo as unidades sem registro de venda)[cite: 1, 3, 4].
- **Reserva de Estoque (Estoque Oculto):** Recurso gerencial para travar a disponibilidade pública de quantidades específicas do catálogo antes da disponibilização pública[cite: 1, 3, 4].
- **Geração de Lista de Separação:** Tela ou extrato unificando as matrículas aos itens encomendados, simplificando a preparação física[cite: 1, 3, 4].
- **Painel Oculto de Acesso Rápido:** Uma engrenagem pequena na Home Leiga que serve de chave (login/senha) para as abas avançadas de configurações (CRUD)[cite: 1, 3, 4].

## 6. Funcionalidades Fora de Escopo
- Módulos para recebimento financeiro ou transações digitais nativas (Dinheiro, PIX, Cartão)[cite: 1, 3, 4].
- Integrações, vínculos ou espelhamentos automatizados com os pacotes contábeis, estoques gerais e sistemas de folhas de pagamento da distribuidora (todos os itens já configuram estoque finalizado)[cite: 1, 3, 4].
- Opções, botões ou telas de edição/remoção na interface central da conta de operação leiga[cite: 1, 3, 4].

## 7. Regras de Negócio
- **Trava Imediata no Carrinho:** A ação de selecionar um produto reverte em remoção temporária da oferta primária para os demais compradores (evita sobreposição)[cite: 1, 3, 4].
- **Ciclos Limitados com Variação Dinâmica:** Não há lote global de compras. A venda interna depende dos aberturas seletivas da coordenadora, que determinam quem compra (setor) e até quanto cada indivíduo daquele setor poderá pedir no evento[cite: 1, 3, 4].
- **Restrição Estrita de Avarias Leves:** Itens furados, quebrados ou violados de forma grave nunca alimentarão os saldos visíveis e invisíveis da plataforma da Lojinha[cite: 3, 4].
- **Filosofia de Usabilidade Absoluta (A Urna Eletrônica):** A versão operada pelo encarregado de reembalagem baseará seus apontamentos exclusivamente em fluxos que exijam "Confirmações e Correções" na etapa de salvamento (focados num passo de cada vez)[cite: 1, 3, 4].
- **Gestão de Diferenças Reservadas:** A quantidade ofertada publicamente não ultrapassará nunca o `Estoque Total - Quantia Reservada Ocultamente pela Gestão`[cite: 1, 3, 4].

## 8. Informações que o Sistema Precisa Controlar
| Informação | Para que serve no sistema | Observações importantes |
| :--- | :--- | :--- |
| **Produtos** | Lista mestre central para lançamentos e vitrine[cite: 1, 3, 4]. | Inclui códigos, nomes técnicos, foto representativa e pacote padrão[cite: 1, 3, 4]. |
| **Pessoas Autorizadas** | Base de potenciais adquirentes e controle de vitrines[cite: 1, 3, 4]. | Identificação será viabilizada tanto por busca nominal quanto matrícula corporativa e Setor[cite: 1, 3, 4]. |
| **Movimentos de B.O.** | Alimentação do histórico da separação dos avariados (seja lixo, seja lojinha)[cite: 1, 3, 4]. | Salva o código, quantidade e o problema selecionado nos 5 tipos listados[cite: 1, 3, 4]. |
| **Apontamentos Horários** | Cálculos operacionais diretos do esforço de recuperação[cite: 1, 3, 4]. | Documenta o produto manipulado, início exato, fim exato, e produção gerada por servidor[cite: 1, 3, 4]. |
| **Inventário Flexível da Loja** | Manutenção paralela não-integrada dos lotes liberáveis[cite: 1, 3, 4]. | Rastreia a métrica global, a cota isolada e a porção publicável simultaneamente[cite: 1, 3, 4]. |
| **Eventos Fora do Lote (Baixas)** | Regularização virtual sobre perdas e desvios administrativos confirmados[cite: 1, 3, 4]. | Salva descrição temporal do porquê os produtos não geraram pedido final[cite: 1, 3, 4]. |
| **Compras Definitivas** | Controle do cupom para distribuição[cite: 1, 3, 4]. | Grava vínculo entre colaborador, momento da solicitação e detalhamento por item[cite: 1, 3, 4]. |

## 9. Fluxos Principais de Uso
1. **Lançamento Produtivo B.O.:** O trabalhador acessa "Registrar Avaria", escolhe/digita a descrição e a medida; Em seguida seleciona dentre 5 botões grandes o evento adverso ("Amassada"). Aparece a consolidação ("Corrigir / Confirmar")[cite: 1, 2, 3, 4].
2. **Registro Rápido de Esforço (Cronômetro):** Ao acessar "Registrar Produção", o colaborador especifica o item. Aperta o Iniciar (a hora começa a gravar). Na finalização física ele volta, preenche os pacotes refeitos e aperta Finalizar. A última checagem ("Corrigir / Confirmar") salva os horários definidos[cite: 1, 2, 3, 4].
3. **Manejo Comercial do Estoque Interno:** A gestora, via botão engrenagem oculto, entra na sua área, cria "Reserva" da cerveja A, dita que o setor X fará pedido com teto de "7 unidades" e confirma a abertura. O colaborador (comprador X) adentra a visão simplificada e-commerce, enche os carrinhos, o qual já subtrai disponibilidade para o comprador Y em tempo imediato, e gera seu pedido. A coordenadora gera a lista pós-evento e separa fisicamente[cite: 1, 3, 4].

## 10. Histórias de Usuário
- "Como **Operador de Repack**, desejo registrar com clareza as ocorrências perante o estoque (B.O e Cronômetro) via fluxos gigantescos de validação constante para atuar velozmente mesmo possuindo poucas habilidades tecnológicas"[cite: 3, 4].
- "Como **Coordenadora de Repack**, necessito de funcionalidades ocultas e isoladas na interface para ajustar divergências no estoque e impor limites rigorosos a lotes específicos, eliminando furos sem prejudicar a tela das operações da base logística"[cite: 3, 4].
- "Como **Funcionário Diversos Setores (Comprador)**, exijo uma busca com nome/foto que traga praticidade ao reservar meus itens até as regras diárias se cumprirem para que meu pedido não me roube tempo e assegure justiça na distribuição do pacote promocional"[cite: 3, 4].

## 11. Critérios de Aceitação
- [ ] O fluxo operacional principal ("Home") deverá se limitar a exibir botões amplos, eliminando menus e subtelas confusas[cite: 1, 2, 3, 4].
- [ ] Todas as edições passadas cadastradas por operador são editáveis com uso único do login da gestão superior no ambiente restrito[cite: 1, 3, 4].
- [ ] Colaboradores no estágio Venda devem sofrer restrições matemáticas perante os Itens Reservados de Forma Oculta[cite: 1, 3, 4].

## 12. Consultas, Relatórios e Indicadores
- Relatório Sintético de Estoque Dinâmico (Total, Limpo e Oculto).
- Painel para rastrear Consumos Internos vs Ocorrências Lojinha sem vendas.
- Extrato Analítico diário e geral do Esforço da Equipe (O que e Quanto se empacotou sobre Tempo e Operador)[cite: 1, 3, 4].
- Painel Resumo Físico de Sacolas Encomendadas pela base compradora[cite: 1, 3, 4].

## 13. Permissões e Segurança Funcional
| Perfil | Pode fazer | Não pode fazer | Observações |
| :--- | :--- | :--- | :--- |
| **Nível Básico** | Emitir eventos (perda e esforço temporal)[cite: 1, 3, 4]. | Corrigir seus equívocos ou de outros, consultar gerência. | Segurança de que não quebrarão lógica do sistema[cite: 1, 3, 4]. |
| **Nível VIP Gestão** | Editar tudo, ocultar, delimitar perdas forçadas, abrir lojas virtuais setorizadas[cite: 1, 3, 4]. | Realizar integrações fora do ecossistema e financeiro. | As chaves garantem o ajuste dinâmico perfeito do fluxo[cite: 1, 3, 4]. |
| **Visitante** | Fazer aquisições visuais limitadas as regras impostas, limitadas à reserva temporária em sacola[cite: 1, 3, 4]. | Visualizar ou acessar os lotes que não estejam com teto garantido ao seu grupo[cite: 1, 3, 4]. | Limitado perante sua matrícula nominal na listagem de liberação[cite: 1, 3, 4]. |

## 14. Limitações da Primeira Versão
- O programa constitui pilar logístico isolado da infraestrutura corporativa tributária/sistêmica pesada para manter eficiência no uso tático e operacional no ambiente restrito da reembalagem da Distribuidora[cite: 1, 3, 4].

## 15. Pontos Pendentes Antes do FSD
Não há dúvidas funcionais críticas. O planejamento funcional absorveu com eficácia a separação dual e arquitetônica da ferramenta simplificada baseada na "Filosofia da Urna Eletrônica" unindo segurança da equipe especializada da Lojinha contra o acesso de operação não instruída[cite: 1, 3, 4].

## 16. Resumo Final do PRD
Em sua totalidade a etapa 1 se firma em digitalizar sem criar barreiras tecnológicas a anotações complexas (Esforço versus Tempo versus Produtos Finais e Lixo), mantendo uma lógica restritiva nos benefícios concedidos às compras corporativas da base operária[cite: 1, 3, 4]. Toda a funcionalidade reflete um pilar funcional imune, garantindo avanço e consolidação sem margem de questionamento com robustez perante a fase arquitetural do documento técnico que sucederá o PRD.