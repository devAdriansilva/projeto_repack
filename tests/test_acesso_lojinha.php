<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Acesso restrito ao CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/LoteVenda.php';
require_once __DIR__ . '/../app/models/EstoqueLojinha.php';
require_once __DIR__ . '/../app/models/Pedido.php';

echo "=== INICIANDO TESTES DE ACESSO E REGRAS DA LOJINHA ===\n\n";

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

$pdo = Database::getConnection();

// 1. Validação de Usuários e Perfis de Acesso
echo "[1] Testando Perfis de Acesso à Lojinha...\n";
$colab = Usuario::buscarPorMatricula('COLAB01');
assertTest($colab && $colab['tipo_acesso_lojinha'] === 'comum', "COLAB01 possui tipo_acesso_lojinha = 'comum'");

$rh = Usuario::buscarPorMatricula('RH01');
assertTest($rh && $rh['tipo_acesso_lojinha'] === 'setorial', "RH01 possui tipo_acesso_lojinha = 'setorial'");

$adm = Usuario::buscarPorMatricula('ADM01');
assertTest($adm && $adm['tipo_acesso_lojinha'] === 'setorial', "ADM01 possui tipo_acesso_lojinha = 'setorial'");

$gest = Usuario::buscarPorMatricula('GEST01');
assertTest($gest && $gest['tipo_acesso_lojinha'] === 'master', "GEST01 possui tipo_acesso_lojinha = 'master'");

// 2. Validação da Regra 1B (Colaborador Comum: 1 Único Pedido por Lote)
echo "\n[2] Testando Regra 1B: Colaborador Comum limitado a 1 pedido por lote...\n";
$loteId = LoteVenda::salvar([
    'titulo' => 'Lote Teste Regra 1B - ' . time(),
    'setores_autorizados' => ['Armazém', 'Transporte', 'Gente (RH)', 'Administrativo'],
    'data_inicio' => date('Y-m-d H:i:s', strtotime('-1 hour')),
    'data_fim' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'limite_itens_por_comprador' => 10,
    'status' => 'aberto'
], (int)$gest['id']);

$produtos = Produto::listarAtivos();
$produtoId = (int)$produtos[0]['id'];
EstoqueLojinha::incrementarSaldo($produtoId, 50);

// COLAB01 faz o 1º pedido
Pedido::adicionarAoCarrinho($loteId, (int)$colab['id'], $produtoId, 2);
$pedido1 = Pedido::finalizarCheckout($loteId, (int)$colab['id']);
assertTest(!empty($pedido1['codigo_pedido']), "COLAB01 concluiu com sucesso o 1º pedido: {$pedido1['codigo_pedido']}");

$jaComprou = Pedido::usuarioJaComprouNoLote($loteId, (int)$colab['id']);
assertTest($jaComprou === true, "usuarioJaComprouNoLote retornou true para COLAB01");

$resumoColab = Pedido::obterResumoComprador($loteId, (int)$colab['id']);
assertTest($resumoColab['ja_comprou_no_lote'] === true, "Resumo do comprador sinaliza ja_comprou_no_lote = true");

// COLAB01 tenta fazer um 2º pedido no mesmo lote -> DEVE SER BLOQUEADO
$bloqueou2oPedido = false;
try {
    Pedido::adicionarAoCarrinho($loteId, (int)$colab['id'], $produtoId, 1);
} catch (DomainException $e) {
    $bloqueou2oPedido = true;
    assertTest(strpos($e->getMessage(), 'já realizou') !== false, "Tentativa de 2º pedido de COLAB01 foi bloqueada com mensagem didática: " . $e->getMessage());
}
assertTest($bloqueou2oPedido, "Bloqueio estrito de múltiplos pedidos para colaborador comum confirmado");

// 3. Validação de Conta Setorial (RH01: Múltiplos Pedidos no Mesmo Lote)
echo "\n[3] Testando Conta Setorial (RH01: múltiplos pedidos autorizados)...\n";
Pedido::adicionarAoCarrinho($loteId, (int)$rh['id'], $produtoId, 3);
$pedidoRH1 = Pedido::finalizarCheckout($loteId, (int)$rh['id']);
assertTest(!empty($pedidoRH1['codigo_pedido']), "RH01 fez o 1º pedido setorial: {$pedidoRH1['codigo_pedido']}");

// RH01 faz o 2º pedido no mesmo lote -> DEVE SER PERMITIDO
$rhPermitiu2oPedido = false;
try {
    Pedido::adicionarAoCarrinho($loteId, (int)$rh['id'], $produtoId, 4);
    $pedidoRH2 = Pedido::finalizarCheckout($loteId, (int)$rh['id']);
    $rhPermitiu2oPedido = !empty($pedidoRH2['codigo_pedido']);
} catch (\Throwable $e) {
    echo "Erro inesperado RH: " . $e->getMessage() . "\n";
}
assertTest($rhPermitiu2oPedido, "RH01 concluiu com sucesso o 2º pedido setorial ({$pedidoRH2['codigo_pedido']}) no mesmo ciclo");

// 4. Validação de Acesso Master (GEST01: Estoque Total e Reserva)
echo "\n[4] Testando Acesso Master (GEST01)...\n";
// Cria reserva oculta de 15 unidades no produto
EstoqueLojinha::ajustarReservaOculta($produtoId, 15, (int)$gest['id'], 'Teste de reserva');

$vitrineComum = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$colab['id'], false);
$prodComum = null;
foreach ($vitrineComum as $vp) {
    if ((int)$vp['produto_id'] === $produtoId) {
        $prodComum = $vp;
        break;
    }
}

$vitrineMaster = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$gest['id'], true);
$prodMaster = null;
foreach ($vitrineMaster as $vp) {
    if ((int)$vp['produto_id'] === $produtoId) {
        $prodMaster = $vp;
        break;
    }
}

assertTest($prodMaster && (int)$prodMaster['quantidade_reservada_gestao'] === 15, "Perfil Master visualiza o valor exato da reserva oculta (15 un)");
assertTest((int)$prodMaster['saldo_disponivel'] > (int)$prodComum['saldo_disponivel'], "Saldo disponível para Master inclui estoque sem dedução da reserva da gestão");

// 5. Validação de Lote Permanente / Loja Fechada
echo "\n[5] Testando Acesso com Loja Fechada (Lote Corporativo)...\n";
$loteCorp = LoteVenda::obterLoteCorporativo();
assertTest(!empty($loteCorp) && $loteCorp['titulo'] === 'Lote Corporativo e Setorial', "Lote Corporativo permanente existe e está operacional");

Pedido::adicionarAoCarrinho((int)$loteCorp['id'], (int)$adm['id'], $produtoId, 2);
$pedidoAdm = Pedido::finalizarCheckout((int)$loteCorp['id'], (int)$adm['id']);
assertTest(!empty($pedidoAdm['codigo_pedido']), "Conta Setorial ADM01 realizou pedido corporativo permanente com sucesso: {$pedidoAdm['codigo_pedido']}");

echo "\n============================================\n";
echo "RESULTADO DOS TESTES DA LOJINHA:\n";
echo "Sucessos: {$sucessos} | Falhas: {$falhas}\n";
echo "============================================\n";

if ($falhas > 0) {
    exit(1);
} else {
    exit(0);
}
