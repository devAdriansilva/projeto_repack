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
require_once __DIR__ . '/../app/models/EstoqueLojinha.php';
require_once __DIR__ . '/../app/models/ApontamentoBO.php';

echo "=== INICIANDO TESTES DA IMPORTAÇÃO DE B.O. VIA CSV CORPORATIVO ===\n\n";

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

// 1. Teste de Classificação Flexível de Motivos
echo "[1] Testando Classificação de Motivos (Dicionário e Heurísticas)...\n";

$cAmassada = ApontamentoBO::classificarMotivoExterno('Amassada');
assertTest($cAmassada['destino'] === 'lojinha', "Motivo 'Amassada' direcionado para 'lojinha'");

$cRotulo = ApontamentoBO::classificarMotivoExterno('Def. Rótulo');
assertTest($cRotulo['destino'] === 'lojinha', "Motivo 'Def. Rótulo' direcionado para 'lojinha'");

$cEmba = ApontamentoBO::classificarMotivoExterno('Emba. Danificada');
assertTest($cEmba['destino'] === 'lojinha', "Motivo 'Emba. Danificada' direcionado para 'lojinha'");

$cFuro = ApontamentoBO::classificarMotivoExterno('Furo');
assertTest($cFuro['destino'] === 'descarte', "Motivo 'Furo' direcionado para 'descarte'");

$cQuebrada = ApontamentoBO::classificarMotivoExterno('Quebrada');
assertTest($cQuebrada['destino'] === 'descarte', "Motivo 'Quebrada' direcionado para 'descarte'");

// Novo motivo customizado no futuro com sobreposição forçada pelo gestor
$cNovoCustom = ApontamentoBO::classificarMotivoExterno('Avaria Palete Especial', 'lojinha');
assertTest($cNovoCustom['destino'] === 'lojinha' && $cNovoCustom['customizado'] === true, "Novo motivo 'Avaria Palete Especial' aceito com sobreposição manual 'lojinha'");

// 2. Teste do Parser e Prévia Analítica do CSV Real
echo "\n[2] Testando Parse e Prévia Analítica do CSV Corporativo Real...\n";

$csvAmostra = <<<CSV
Código,Quantidade,Prejuízo,Descrição,Fator Hecto,Hecto Total,Preço Unidade,Valor Total Perdido,Local,Área,Ajudante,Motivo,MotivoQuebra,Data,Registrado por
9084,1,938,GUARANA CHP ANTARCTICA LATA 350ML SH C/12 NPAL,0.042,0.0035,2.55,2.55,Armazém,Picking,Pac. Prejuízo,Amassada,-,2026-11-12 15:58:00,José Adriano
9069,2,938,BRAHMA CHOPP LATA 350ML SH C/12 NPAL,0.042,0.007,3.13,6.26,Armazém,Picking,Pac. Prejuízo,Amassada,-,2026-11-12 15:58:00,José Adriano
9068,2,943,SKOL LATA 350ML SH C/12 NPAL,0.042,0.007,2.86,5.72,Armazém,Picking,Pac. Prejuízo,Furo,-,2026-11-12 15:54:00,José Adriano
18152,10,944,GUARANA CHP ANTARCTICA PET 200ML SH C/12,0.024,0.076,1.56,59.28,Armazém,Picking,Pac. Prejuízo,Def. Rótulo,-,2026-11-12 13:28:00,José Adriano
19225,5,944,RED BULL BR LATA 250ML CX C 24 NPAL .,0.06,0.0125,7.13,35.65,Armazém,Picking,Pac. Prejuízo,Emba. Danificada,-,2026-11-12 13:16:00,José Adriano
CSV;

$previa = ApontamentoBO::analisarPreviaCsv($csvAmostra);

assertTest($previa['total_linhas'] === 5, "Total de 5 linhas parseadas com sucesso");
assertTest($previa['unidades_lojinha'] === 18, "Unidades para Lojinha calculadas corretamente: 1+2+10+5 = 18 un");
assertTest($previa['unidades_descarte'] === 2, "Unidades para Descarte calculadas corretamente: 2 un (Furo)");
assertTest(count($previa['motivos']) === 4, "4 motivos distintos identificados no resumo");

// 3. Teste de Efetivação da Importação e Abastecimento da Lojinha
echo "\n[3] Testando Efetivação de Importação, Saldo da Lojinha e Auditoria...\n";

$gestor = Usuario::buscarPorMatricula('GEST01');
$gestorId = (int)$gestor['id'];

// Captura saldo atual do produto 9084 antes da importação
$prod9084 = Produto::buscarPorCodigo('9084');
assertTest(!empty($prod9084), "Produto 9084 encontrado no catálogo oficial");
$saldoAntes9084 = EstoqueLojinha::obterSaldoDisponivel((int)$prod9084['id']);

$nomeArquivoTeste = 'teste_importacao_' . uniqid() . '.csv';
$csvAmostraExecucao = $csvAmostra . "\n# teste_run_" . microtime(true);

$resultadoImportacao = ApontamentoBO::processarImportacaoCsv(
    $csvAmostraExecucao,
    $gestorId,
    $nomeArquivoTeste,
    [],
    false
);

assertTest($resultadoImportacao['status'] === 'success', "Importação executada com sucesso");
assertTest($resultadoImportacao['unidades_lojinha'] === 18, "18 unidades abastecidas na Lojinha");
assertTest($resultadoImportacao['unidades_descarte'] === 2, "2 unidades registradas como descarte");

// Verifica se o saldo do produto 9084 incrementou em +1
$saldoDepois9084 = EstoqueLojinha::obterSaldoDisponivel((int)$prod9084['id']);
assertTest($saldoDepois9084 === ($saldoAntes9084 + 1), "Estoque do produto 9084 incrementou exatamente em +1 un ({$saldoAntes9084} -> {$saldoDepois9084})");

// Verifica se os apontamentos de B.O. foram gerados com arquivo_origem e motivo_original
$stmtCheckBO = $pdo->prepare("
    SELECT COUNT(*) 
    FROM apontamentos_bo 
    WHERE arquivo_origem = :arq
");
$stmtCheckBO->execute(['arq' => $nomeArquivoTeste]);
$totalBOGerados = (int)$stmtCheckBO->fetchColumn();
assertTest($totalBOGerados === 5, "5 registros de B.O. auditados gerados com arquivo_origem vinculado");

// 4. Teste de Prevenção de Duplicidade
echo "\n[4] Testando Prevenção contra Importação Acidental Duplicada...\n";
$bloqueouDuplicado = false;
try {
    ApontamentoBO::processarImportacaoCsv(
        $csvAmostra,
        $gestorId,
        $nomeArquivoTeste,
        [],
        false // sem forçar
    );
} catch (DomainException $e) {
    $bloqueouDuplicado = true;
    assertTest(strpos($e->getMessage(), 'já foi importado') !== false, "Tentativa de reimportar mesmo CSV bloqueada com sucesso: " . $e->getMessage());
}
assertTest($bloqueouDuplicado, "Bloqueio estrito de reimportação duplicada validado");

// Forçar reimportação deve ser permitido
$reimportouForcado = false;
try {
    $resForcado = ApontamentoBO::processarImportacaoCsv(
        $csvAmostra,
        $gestorId,
        $nomeArquivoTeste,
        [],
        true // forçar
    );
    $reimportouForcado = ($resForcado['status'] === 'success');
} catch (\Throwable $e) {
    echo "Erro ao forçar: " . $e->getMessage() . "\n";
}
assertTest($reimportouForcado, "Reimportação com flag 'forcarReimportacao = true' aceita com sucesso");

echo "\n============================================\n";
echo "RESULTADO DOS TESTES DE IMPORTAÇÃO CSV:\n";
echo "Sucessos: {$sucessos} | Falhas: {$falhas}\n";
echo "============================================\n";

if ($falhas > 0) {
    exit(1);
} else {
    exit(0);
}
