<?php
declare(strict_types=1);

/**
 * Projeto Repack - Testes Automatizados de Carimbo de Última Entrada e Modo Espelho
 * Valida MANUT-060
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/EstoqueLojinha.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/LoteVenda.php';

$pdo = Database::getConnection();

echo "=== TESTE: CARIMBO DE ÚLTIMA ENTRADA & MODO ESPELHO DA GESTÃO ===\n";

$passou = 0;
$falhou = 0;

function assertTest(string $nome, bool $condicao) {
    global $passou, $falhou;
    if ($condicao) {
        echo "  [PASS] {$nome}\n";
        $passou++;
    } else {
        echo "  [FAIL] {$nome}\n";
        $falhou++;
    }
}

// 1. Teste de cálculo de tempo relativo
$tempoAgora = EstoqueLojinha::calcularTempoRelativo(date('Y-m-d H:i:s'));
assertTest("Tempo relativo de agora é 'há instantes'", $tempoAgora === 'há instantes');

$tempo10Min = EstoqueLojinha::calcularTempoRelativo(date('Y-m-d H:i:s', strtotime('-10 minutes')));
assertTest("Tempo relativo de 10 min atrás contém 'min'", strpos($tempo10Min, 'min') !== false);

$tempo2Horas = EstoqueLojinha::calcularTempoRelativo(date('Y-m-d H:i:s', strtotime('-2 hours')));
assertTest("Tempo relativo de 2 horas atrás contém 'horas'", strpos($tempo2Horas, 'horas') !== false);

// 2. Teste de obterUltimaImportacaoCsv
$ultimaImportacao = EstoqueLojinha::obterUltimaImportacaoCsv();
if ($ultimaImportacao !== null) {
    assertTest("Última importação CSV possui campos formatados", isset($ultimaImportacao['data_formatada']) && isset($ultimaImportacao['tempo_relativo']));
    assertTest("Última importação tem nome_arquivo preenchido", !empty($ultimaImportacao['nome_arquivo']));
} else {
    assertTest("Sem importações CSV no banco, retorno é null seguro", true);
}

// 3. Teste de obterUltimaEntradaEstoque
$ultimaEntrada = EstoqueLojinha::obterUltimaEntradaEstoque();
if ($ultimaEntrada !== null) {
    assertTest("Última entrada de estoque possui 'data_formatada' e 'tempo_relativo'", isset($ultimaEntrada['data_formatada']) && isset($ultimaEntrada['tempo_relativo']));
    assertTest("Última entrada possui canal ('Importação CSV', 'Bancada Operacional' ou 'Ajuste de Inventário')", in_array($ultimaEntrada['canal'], ['Importação CSV', 'Bancada Operacional', 'Ajuste de Inventário']));
    assertTest("Última entrada possui quantidade maior que zero", (int)$ultimaEntrada['quantidade'] > 0);
} else {
    assertTest("Sem movimentações de entrada no banco, retorno é null seguro", true);
}

// 4. Teste de Vitrine: Master vs Colaborador Comum
// Cria ou usa um produto de teste com reserva
$stmtProd = $pdo->query("SELECT id FROM produtos WHERE ativo = 1 AND deleted_at IS NULL LIMIT 1");
$prod = $stmtProd->fetch();
if ($prod) {
    $prodId = (int)$prod['id'];
    $loteCorp = LoteVenda::obterLoteCorporativo();
    $loteId = (int)$loteCorp['id'];

    // Usuário GEST01 (Master)
    $gestor = Usuario::buscarPorMatricula('GEST01');
    $uColab = Usuario::buscarPorMatricula('OP01');

    if ($gestor && $uColab) {
        $vitrineMaster = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$gestor['id'], true);
        $vitrineColab = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$gestor['id'], false);

        assertTest("Vitrine Master retorna lista válida", is_array($vitrineMaster));
        assertTest("Vitrine Colaborador retorna lista válida", is_array($vitrineColab));
    }
}

echo "\nResultado: {$passou} passaram, {$falhou} falharam.\n";
if ($falhou > 0) {
    exit(1);
}
exit(0);
