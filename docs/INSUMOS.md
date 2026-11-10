# INVENTÁRIO DE INSUMOS DO PROJETO

## 1. Visão Geral
Este documento consolida o mapeamento e a validação técnica de todos os insumos e documentos base presentes no diretório `docs/`, auditados previamente à inicialização da estrutura de codificação do **Projeto Repack**.

---

## 2. Inventário de Arquivos e Documentos Base

| Arquivo | O que é | Usado pelo sistema em execução? | Onde será usado | Observações |
| :--- | :--- | :--- | :--- | :--- |
| `docs/FSD.md` | Documento de Especificação Funcional (FSD) consolidado e revisado. | Não (Documentação de Arquitetura e Engenharia). | Referência normativa absoluta para backend, banco de dados, fluxos e APIs. | Documento autossuficiente contendo 28 seções normativas, DDL de migrations, regras de negócio e endpoints. |
| `docs/DESIGN.md` | Especificação técnica do Design System (Urna Eletrônica). | Não (Documentação de UI/UX e Design Tokens). | Referência para construção de folhas de estilo (`public/assets/css/urna.css`) e componentes HTML/JS. | Define paleta de cores (alto contraste), alvos de toque mínimos de 80px, tipografia Inter e mitigadores de sensibilidade para luvas anti-corte. |

---

## 3. Classificação de Insumos

### 3.1 Documentação Normativa de Apoio (Não embarcada no runtime)
- **`docs/FSD.md`**: Guia a modelagem relacional, controllers MVC, validação de regras de concorrência e endpoints PWA.
- **`docs/DESIGN.md`**: Guia a parametrização de CSS, layout responsivo e usabilidade da "Urna Eletrônica".

### 3.2 Assets Estáticos Externos (Imagens, Ícones e Mídias)
- **Status:** Nenhum asset binário adicional (como PNG, SVG ou PDF externo) foi depositado previamente na pasta `docs/`.
- **Tratamento:** 
  - Os ícones operacionais da Home (garrafa/caixa avariada, cronômetro e engrenagem minimalista) serão gerados via SVGs inline puros ou biblioteca local de ícones SVG.
  - Imagens de produtos serão geridas dinamicamente via upload administrativo pela Coordenadora (`/gestao/produtos`), sendo salvas no diretório `public/uploads/produtos/` conforme especificado no FSD.
  - Assets de estilo (Bootstrap 5.3) serão incorporados localmente em `public/assets/` durante a fase de infraestrutura.

---

## 4. Parecer da Auditoria
- **Documentos Obrigatórios:** Presentes e íntegros (`docs/FSD.md` e `docs/DESIGN.md`).
- **Contradições Técnicas ou Bloqueios:** Nenhuma inconsistência detectada entre os documentos de especificação funcional e o design system.
- **Prontidão para Codificação:** Validado. O projeto está apto para a execução do próximo passo do fluxo SDD (`preparar-estrutura-codificacao`).
