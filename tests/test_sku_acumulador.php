<?php
declare(strict_types=1);

/**
 * Projeto Repack - Teste Automatizado de SKU Acumulador na Lojinha (MANUT-062)
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/EstoqueLojinha.php';
require_once __DIR__ . '/../app/models/ApontamentoBO.php';
require_once __DIR__ . '/../app/models/LoteVenda.php';

$pdo = Database::getConnection();

echo "=== TESTE: SKU ACUMULADOR / MULTIPACK NA LOJINHA ===\n";

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

// 1. Verificar se a coluna produto_pai_id existe e se os pares iniciais foram vinculados
$prodSkolPai = Produto::buscarPorCodigo('9068');
$prodSkolFilho = Produto::buscarPorCodigo('34608');

assertTest("SKU 9068 (Skol Lata) existe", !empty($prodSkolPai));
assertTest("SKU 34608 (Skol Multipack) existe", !empty($prodSkolFilho));
assertTest("SKU 34608 possui produto_pai_id apontando para o ID de 9068", (int)($prodSkolFilho['produto_pai_id'] ?? 0) === (int)$prodSkolPai['id']);

$acumulador = Produto::obterIdAcumulador((int)$prodSkolFilho['id']);
assertTest("Produto::obterIdAcumulador(34608) retorna o ID de 9068", $acumulador === (int)$prodSkolPai['id']);

$acumuladorPai = Produto::obterIdAcumulador((int)$prodSkolPai['id']);
assertTest("Produto::obterIdAcumulador(9068) retorna o próprio ID de 9068", $acumuladorPai === (int)$prodSkolPai['id']);

// 2. Testar lançamento de B.O. leve sob o SKU Multipack (34608)
// O B.O. deve registrar 34608, mas o saldo da lojinha deve creditar em 9068
$saldoAntesPai = EstoqueLojinha::obterSaldoDisponivel((int)$prodSkolPai['id']);
$saldoAntesFilho = EstoqueLojinha::obterSaldoDisponivel((int)$prodSkolFilho['id']);

$opStmt = $pdo->query("SELECT id FROM usuarios WHERE perfil = 'operador' AND ativo = 1 LIMIT 1");
$operador = $opStmt->fetch();
$opId = $operador ? (int)$operador['id'] : 1;

$resBO = ApontamentoBO::salvar([
    'produto_id' => (int)$prodSkolFilho['id'], // Aponta no Multipack
    'usuario_id' => $opId,
    'tipo_avaria' => 'amassada', // Leve -> destino lojinha
    'quantidade' => 6,
    'origem_sincronizacao' => 'online'
]);

assertTest("B.O. gerado com sucesso para o Multipack", $resBO['status'] === 'success');
assertTest("B.O. destino foi 'lojinha'", $resBO['destino'] === 'lojinha');

// Checa no banco se o B.O. gravou o produto_id = 34608 (rastreabilidade contábil)
$stmtChecaBO = $pdo->prepare("SELECT produto_id FROM apontamentos_bo WHERE id = :id");
$stmtChecaBO->execute(['id' => $resBO['id']]);
$boCriado = $stmtChecaBO->fetch();
assertTest("Apontamento de B.O. preservou o produto_id original do Multipack (34608)", (int)$boCriado['produto_id'] === (int)$prodSkolFilho['id']);

// Checa se o saldo da lojinha foi creditado no produto PAI (9068) e NÃO no filho (34608)
$saldoDepoisPai = EstoqueLojinha::obterSaldoDisponivel((int)$prodSkolPai['id']);
$saldoDepoisFilho = EstoqueLojinha::obterSaldoDisponivel((int)$prodSkolFilho['id']);

assertTest("Saldo do produto pai (9068) aumentou exatamente em +6 un", $saldoDepoisPai === ($saldoAntesPai + 6));
assertTest("Saldo do produto filho (34608) permaneceu inalterado/zerado ({$saldoDepoisFilho})", $saldoDepoisFilho === $saldoAntesFilho);

// 3. Testar se o produto filho (Multipack) está oculto da Vitrine e da Gestão de Estoque
$loteCorp = LoteVenda::obterLoteCorporativo();
$vitrine = EstoqueLojinha::listarProdutosVitrine((int)$loteCorp['id'], $opId, false);

$idsNaVitrine = array_column($vitrine, 'produto_id');
assertTest("Produto pai (9068) aparece na vitrine da lojinha", in_array((int)$prodSkolPai['id'], $idsNaVitrine));
assertTest("Produto filho Multipack (34608) NÃO aparece na vitrine da lojinha", !in_array((int)$prodSkolFilho['id'], $idsNaVitrine));

$visaoGeral = EstoqueLojinha::listarVisaoGeral();
$idsNaVisaoGeral = array_column($visaoGeral, 'produto_id');
assertTest("Produto pai (9068) aparece na visão geral de estoque", in_array((int)$prodSkolPai['id'], $idsNaVisaoGeral));
assertTest("Produto filho Multipack (34608) NÃO aparece como item isolado na visão geral", !in_array((int)$prodSkolFilho['id'], $idsNaVisaoGeral));

// 4. Testar listagem e vinculação de produtos filhos
$filhos = Produto::listarFilhosAcumulados((int)$prodSkolPai['id']);
assertTest("Produto pai (9068) possui ao menos 1 filho acumulado listado", count($filhos) >= 1);

echo "\nResultado: {$passou} passaram, {$falhou} falharam.\n";
if ($falhou > 0) {
    exit(1);
}
exit(0);
