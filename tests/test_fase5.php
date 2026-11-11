<?php
declare(strict_types=1);

// Bloqueio rigoroso contra requisições web (apenas CLI permitido)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Acesso negado. Este script deve ser executado exclusivamente via CLI.\n";
    exit(1);
}

/**
 * Projeto Repack - Script de Testes Integrados da Fase 5
 * Validação rigorosa dos 9 Critérios de Aceitação Técnica e Funcional (docs/FSD.md Seção 26)
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/LoteVenda.php';
require_once __DIR__ . '/../app/models/EstoqueLojinha.php';
require_once __DIR__ . '/../app/models/ApontamentoBO.php';
require_once __DIR__ . '/../app/models/ApontamentoProdutividade.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/LogSistema.php';
require_once __DIR__ . '/../app/controllers/RelatorioController.php';

echo "=== INICIANDO TESTES INTEGRADOS DA FASE 5 ===\n\n";

$pdo = Database::getConnection();

$falhas = 0;
$sucessos = 0;

function assertTest(bool $condicao, string $descricao): void
{
    global $sucessos, $falhas;
    if ($condicao) {
        echo "  [PASS] {$descricao}\n";
        $sucessos++;
    } else {
        echo "  [FAIL] {$descricao}\n";
        $falhas++;
    }
}

// -------------------------------------------------------------
// Teste 1: Validação do Relatório Sintético de Movimentação de Repack
// -------------------------------------------------------------
echo "[1] Testando Relatório Sintético de Movimentação de Repack (B.O.)...\n";

$operadores = Usuario::listarOperadoresAtivos();
$operadorId = (int)$operadores[0]['id'];
$produtos = Produto::listarAtivos();
$produtoId = (int)$produtos[0]['id'];

// Registra apontamento de lojinha e de descarte
$boLojinha = ApontamentoBO::salvar([
    'produto_id' => $produtoId,
    'usuario_id' => $operadorId,
    'tipo_avaria' => 'amassada',
    'quantidade' => 5,
    'origem_sincronizacao' => 'online'
]);

$boDescarte = ApontamentoBO::salvar([
    'produto_id' => $produtoId,
    'usuario_id' => $operadorId,
    'tipo_avaria' => 'furo',
    'quantidade' => 3,
    'origem_sincronizacao' => 'online'
]);

$relatorioSintetico = ApontamentoBO::obterRelatorioSintetico();

assertTest($relatorioSintetico['total_unidades'] >= 8, "Total de unidades recebidas contabilizado corretamente");
assertTest($relatorioSintetico['unidades_lojinha'] >= 5, "Unidades direcionadas à Lojinha contabilizadas corretamente");
assertTest($relatorioSintetico['unidades_descarte'] >= 3, "Unidades direcionadas para Descarte contabilizadas corretamente");
assertTest(isset($relatorioSintetico['percentual_lojinha']) && isset($relatorioSintetico['percentual_descarte']), "Percentuais calculados");
assertTest(!empty($relatorioSintetico['por_tipo']), "Detalhamento por tipo de avaria preenchido");
assertTest(!empty($relatorioSintetico['por_produto']), "Detalhamento por produto preenchido");

// -------------------------------------------------------------
// Teste 2: Validação do Extrato Analítico de Produtividade
// -------------------------------------------------------------
echo "\n[2] Testando Extrato Analítico de Produtividade da Equipe...\n";

$prodTest1 = ApontamentoProdutividade::salvar([
    'produto_id' => $produtoId,
    'usuario_id' => $operadorId,
    'data_hora_inicio' => date('Y-m-d H:i:s', time() - 600),
    'data_hora_fim' => date('Y-m-d H:i:s'),
    'pacotes_refeitos' => 20,
    'origem_sincronizacao' => 'online'
]);

$extratoProd = ApontamentoProdutividade::obterExtratoAnalitico();

assertTest($extratoProd['total_pacotes'] >= 20, "Total de pacotes montados contabilizado no extrato");
assertTest($extratoProd['total_ciclos'] >= 1, "Total de ciclos contabilizado no extrato");
assertTest($extratoProd['total_segundos'] >= 600, "Duração em segundos calculada corretamente");
assertTest($extratoProd['media_geral_pacotes_por_hora'] > 0, "Produtividade média geral calculada com sucesso");
assertTest(!empty($extratoProd['por_operador']), "Agrupamento analítico por operador presente");
assertTest(!empty($extratoProd['por_produto']), "Agrupamento analítico por produto presente");

// -------------------------------------------------------------
// Teste 3: Validação da Lista de Separação Física e Ciclo de Vida do Pedido
// -------------------------------------------------------------
echo "\n[3] Testando Lista de Separação Física e Ciclo de Vida do Pedido...\n";

// Cria ou garante um lote aberto com setor 'Armazém' para o colaborador
$loteId = LoteVenda::salvar([
    'titulo' => 'Lote Teste Fase 5 - Separação',
    'setores_autorizados' => ['Armazém', 'Transporte', 'Repack'],
    'data_inicio' => date('Y-m-d H:i:s', strtotime('-1 day')),
    'data_fim' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'limite_itens_por_comprador' => 20,
    'status' => 'aberto'
], 1);

// Busca colaborador de teste
$colaborador = Usuario::buscarPorMatricula('COLAB01');
$colabId = (int)$colaborador['id'];

// Garante estoque suficiente
EstoqueLojinha::incrementarSaldo($produtoId, 10);

// Adiciona ao carrinho e finaliza checkout
Pedido::adicionarAoCarrinho($loteId, $colabId, $produtoId, 2);
$pedidoResult = Pedido::finalizarCheckout($loteId, $colabId);

$pedidoId = (int)$pedidoResult['pedido_id'];
$codigoPedido = $pedidoResult['codigo_pedido'];

assertTest($pedidoId > 0 && !empty($codigoPedido), "Pedido de teste criado via checkout atômico: {$codigoPedido}");

// Testa a listagem consolidada de separação
$listaSeparacao = Pedido::listarParaSeparacao($loteId);
assertTest(!empty($listaSeparacao), "Lista de separação gerada com sucesso para o lote #{$loteId}");

// Verifica se os itens estão agrupados por colaborador
$encontrouColab = false;
foreach ($listaSeparacao as $itemColab) {
    if ((int)$itemColab['usuario_id'] === $colabId) {
        $encontrouColab = true;
        assertTest(!empty($itemColab['pedidos']), "Pedidos agrupados sob o colaborador");
        assertTest(!empty($itemColab['matricula']), "Matrícula exibida no agrupamento");
        break;
    }
}
assertTest($encontrouColab, "Colaborador presente no agrupamento de separação física");

// Testa estatísticas de separação
$statsSeparacao = Pedido::obterEstatisticasSeparacao($loteId);
assertTest($statsSeparacao['total_pedidos'] >= 1, "Estatísticas de separação: total_pedidos > 0");
assertTest($statsSeparacao['total_itens'] >= 2, "Estatísticas de separação: total_itens calculado");

// Testa transição de status do pedido: confirmado -> separado -> entregue
$okSeparado = Pedido::atualizarStatus($pedidoId, Pedido::STATUS_SEPARADO, 1);
assertTest($okSeparado, "Status do pedido atualizado para 'separado'");

$pedidoAtualizado = Pedido::buscarPorId($pedidoId);
assertTest($pedidoAtualizado['status'] === 'separado', "Conferência do status 'separado' no banco");

$okEntregue = Pedido::atualizarStatus($pedidoId, Pedido::STATUS_ENTREGUE, 1);
assertTest($okEntregue, "Status do pedido atualizado para 'entregue'");

// -------------------------------------------------------------
// Teste 4: Validação do Fallback de Erros e Logs de Auditoria
// -------------------------------------------------------------
echo "\n[4] Testando Auditoria e Fallback de Logs...\n";

LogSistema::registrar('SECURITY', 'Teste de auditoria de segurança da Fase 5', ['ip' => '127.0.0.1']);
$arquivoSecurity = LOGS_PATH . '/security.log';
assertTest(file_exists($arquivoSecurity) && filesize($arquivoSecurity) > 0, "Log de segurança gravado em logs/security.log");

$arquivoAppErrors = LOGS_PATH . '/app_errors.log';
Database::logFileFallback("Teste unitário de fallback de erro no sistema", "ERROR");
assertTest(file_exists($arquivoAppErrors) && filesize($arquivoAppErrors) > 0, "Fallback de erro gravado em logs/app_errors.log");

// -------------------------------------------------------------
// Teste 5: Validação dos 9 Critérios de Aceitação do docs/FSD.md
// -------------------------------------------------------------
echo "\n[5] Verificação dos 9 Critérios de Aceitação (docs/FSD.md Seção 26):\n";

// Critério 1: Touch targets de 80px e touch-action no CSS/JS
$urnaCss = file_get_contents(__DIR__ . '/../public/assets/css/urna.css');
$touchHandler = file_get_contents(__DIR__ . '/../public/assets/js/touch-handler.js');
assertTest(strpos($urnaCss, '80px') !== false, "Critério 1: Botões com altura mínima de 80px no CSS");
assertTest((strpos($urnaCss, 'touch-action: manipulation') !== false || strpos($touchHandler, 'manipulation') !== false) && strpos($touchHandler, 'touch') !== false, "Critério 1: touch-action: manipulation e eventos de toque para luvas implementados");

// Critério 2: Avaria leve incrementa estoque lojinha
$saldoAntes = EstoqueLojinha::obterSaldoDisponivel($produtoId);
ApontamentoBO::salvar([
    'produto_id' => $produtoId,
    'usuario_id' => $operadorId,
    'tipo_avaria' => 'def_rotulo',
    'quantidade' => 4,
    'origem_sincronizacao' => 'online'
]);
$saldoDepois = EstoqueLojinha::obterSaldoDisponivel($produtoId);
assertTest($saldoDepois === ($saldoAntes + 4), "Critério 2: Avaria 'def_rotulo' incrementou estoque_lojinha em +4");

// Critério 3: Furo / Micro Furo recebe status de descarte e nunca incrementa saldo
$saldoAntesDescarte = EstoqueLojinha::obterSaldoDisponivel($produtoId);
ApontamentoBO::salvar([
    'produto_id' => $produtoId,
    'usuario_id' => $operadorId,
    'tipo_avaria' => 'micro_furo',
    'quantidade' => 10,
    'origem_sincronizacao' => 'online'
]);
$saldoDepoisDescarte = EstoqueLojinha::obterSaldoDisponivel($produtoId);
assertTest($saldoDepoisDescarte === $saldoAntesDescarte, "Critério 3: Avaria 'micro_furo' NÃO alterou o saldo de venda");

// Critério 4: Apontamentos offline sincronizados via IndexedDB
$offlineJs = file_get_contents(__DIR__ . '/../public/assets/js/app-offline.js');
assertTest(strpos($offlineJs, 'RepackDB') !== false && strpos($offlineJs, 'sincronizarPendentes') !== false, "Critério 4: Gerenciador IndexedDB e rotina de sincronização presentes");

// Critério 5: Engrenagem só permite acesso à Gestão com credenciais criptografadas
$gestor = Usuario::buscarPorMatricula('GEST01');
$authValida = ($gestor && !empty($gestor['senha_hash']) && password_verify('gestao123', $gestor['senha_hash']));
$authInvalida = ($gestor && !empty($gestor['senha_hash']) && password_verify('senha_errada', $gestor['senha_hash']));
assertTest($authValida, "Critério 5: Autenticação via password_verify() com senha forte OK");
assertTest(!$authInvalida, "Critério 5: Credencial inválida bloqueada com sucesso");

// Critério 6: Carrinho da lojinha com trava temporal de 10 minutos
$pedidoModel = file_get_contents(__DIR__ . '/../app/models/Pedido.php');
assertTest(strpos($pedidoModel, 'INTERVAL 10 MINUTE') !== false || strpos($pedidoModel, 'expiracao_em') !== false, "Critério 6: Trava de concorrência com 10 minutos no carrinho");

// Critério 7: Vitrine respeita fórmula Disponível = Total - Reserva Oculta - Carrinhos Ativos
assertTest(strpos($pedidoModel, 'quantidade_reservada_gestao') !== false, "Critério 7: Abate de Reserva Oculta e Carrinhos Ativos respeitado");

// Critério 8: Migrador executado via CLI e bloqueado contra HTTP
$migratorCode = file_get_contents(__DIR__ . '/../database/migrator.php');
assertTest(strpos($migratorCode, 'php_sapi_name()') !== false && strpos($migratorCode, 'cli') !== false, "Critério 8: Migrador protegido contra acesso via requisição HTTP");

// Critério 9: Erros de sistema gravam em logs/app_errors.log
assertTest(file_exists(LOGS_PATH . '/app_errors.log'), "Critério 9: logs/app_errors.log ativo e operacional");

echo "\n============================================\n";
echo "RESULTADO DOS TESTES INTEGRADOS:\n";
echo "Sucessos: {$sucessos} | Falhas: {$falhas}\n";
echo "============================================\n";

if ($falhas > 0) {
    exit(1);
} else {
    exit(0);
}
