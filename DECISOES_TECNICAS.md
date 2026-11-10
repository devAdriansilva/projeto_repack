# DECISÕES TÉCNICAS DO PROJETO

## 1. Documentos recebidos
Foram recebidos com sucesso o `PRD.md` e o `DESIGN.md` referentes ao "Projeto Repack".

## 2. Identificação do sistema
**Nome:** Projeto Repack
**Objetivo principal:** Digitalizar de ponta a ponta as rotinas do setor de Repack, erradicando o papel na anotação de perdas e tempos, e automatizando a reposição do estoque da lojinha interna.
**Público usuário:** Operadores de Repack (perfil leigo), Gestão (Coordenadora) e Colaboradores em geral (Compradores).
**Resumo funcional:** O sistema fará o lançamento de B.O. com roteamento inteligente, o registro de produtividade (cronômetro) e a gestão de uma vitrine virtual (lojinha) com restrições e gestão de lotes, através de duas interfaces distintas baseadas na filosofia de "urna eletrônica".

## 3. Decisões técnicas confirmadas
- **Ausência de Integrações Complexas:** O sistema funcionará desconectado do ERP e financeiro principal da empresa, sendo um pilar logístico isolado.

## 4. Decisões adotadas por padrão (com ajustes do usuário)
Foram adotados os padrões de segurança e alta compatibilidade (PHP/MySQL) somados à decisão crítica do usuário de tornar o aplicativo resiliente a oscilações de internet e adaptado a condições físicas adversas (uso de luvas anti-corte).

## 5. Stack e ambientes
- **Linguagem Backend:** PHP puro.
- **Banco de Dados:** MySQL.
- **Frontend (Interface):** HTML/CSS e JavaScript puros com framework CSS Bootstrap (carregado localmente).
- **Estratégia Frontend (Offline-First):** PWA (Progressive Web App) utilizando **Service Workers** para cache da interface e **localStorage/IndexedDB** no JavaScript para salvar apontamentos de avaria e produtividade localmente em caso de queda de internet. A sincronização com o banco MySQL ocorrerá automaticamente em background quando o sinal retornar.
- **Ambiente Local:** XAMPP com PHP e MySQL locais.
- **Homologação:** Não haverá ambiente separado; os testes ocorrerão localmente.
- **Produção:** Hospedagem padrão (ex: Hostnet) com suporte a PHP e MySQL.

## 6. Arquitetura obrigatória
O sistema seguirá a organização estrutural **MVC (Model-View-Controller)** para separação clara de responsabilidades no PHP.

## 7. Recursos estruturais definidos
- **Autenticação:** Baseada em matrícula/nome e senha, com proteção rigorosa para a interface complexa (via ícone de engrenagem) e acesso simplificado na visão da lojinha.
- **Soft Delete:** Aplicado (Sim) para os registros principais, garantindo que nada seja efetivamente apagado do banco de dados, especialmente B.O.s e cadastros de produtos.
- **Auditoria:** Controle básico com `created_at` e `updated_at`, além de logs simples de erros.
- **RBAC (Controle de Acesso):** Perfis muito bem definidos separando Operador (sem acesso a CRUD/edição), Gestão (acesso total) e Comprador (acesso apenas à vitrine nos lotes abertos).

## 8. Perfis e permissões em nível alto
- **Operador Leigo:** Apenas inserção nos botões grandes via frontend PWA.
- **Coordenadora/Gestão:** Acesso irrestrito ao painel oculto, edições e configurações.
- **Comprador:** Acesso restrito à vitrine durante os lotes.

## 9. Entidades prováveis em nível alto
- Usuários/Colaboradores
- Produtos
- Lotes de Venda
- Apontamentos de B.O (Avarias/Descarte)
- Estoque Lojinha / Reservas
- Apontamentos de Produtividade
- Pedidos/Carrinho

## 10. Módulos, telas e fluxos esperados em nível alto
O FSD deverá detalhar:
- Tela Home "Urna Eletrônica" para o Operador (adaptada para PWA).
- Fluxo de Lançamento de Avaria (com roteamento inteligente invisível e salvamento offline).
- Fluxo de Cronômetro/Produtividade (com salvamento offline).
- Vitrine Virtual e Carrinho de Compras (celular).
- Painel Administrativo Oculto (Abertura de lotes, reservas, baixas manuais, correção de B.O.).

## 11. Alertas para relatórios, consultas, exportações e desempenho
O sistema prevê a emissão de uma "Lista de Separação" (formato extrato/cupom) ao final de um lote de vendas.

## 12. Alertas para uploads, anexos e arquivos
O sistema deve lidar com fotos representativas no cadastro de produtos para alimentar a vitrine visual.

## 13. Alertas para logs, auditoria e segurança
Dada a interface compartilhada, a transição para a área de Gestão via ícone de engrenagem deve exigir validação de credenciais rigorosa, impedindo acesso acidental.

## 14. Itens que não devem ser inventados
O FSD é proibido de adicionar:
- Integrações com ERP/Contabilidade/Folha de pagamento da distribuidora.
- Módulos de pagamento financeiro nativo (Pix/Cartão/Boleto).
- Telas complexas, grids ou botões de "excluir" na área visível ao Operador Leigo.

## 15. Pendências não bloqueantes
- **Tratamento de Toque com EPI (Luvas Anti-corte):** Como o material da luva reduz a capacitância e dificulta o reconhecimento do toque pela tela, o frontend (JavaScript/CSS) deverá implementar mitigadores. O FSD deve orientar o uso de `touchstart` e a remoção de atrasos (`touch-action: manipulation` no CSS) para aumentar a responsividade do toque, assegurando que contatos curtos ou de menor pressão sejam registrados como cliques válidos na área de `80px` do botão.

## 16. Pronto para o FSD
As decisões técnicas estão concluídas. O próximo passo será gerar o `docs/FSD.md`.