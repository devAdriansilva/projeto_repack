<?php
declare(strict_types=1);

/**
 * Projeto Repack - Testes Automatizados do Controle de PIN de Comprador
 * Baseado estritamente em docs/FSD.md e AGENTS.md
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Usuario.php';

echo "=== INICIANDO TESTES DO CONTROLE DE PIN DE COMPRADORES ===\n\n";

$sucessos = 0;
$falhas = 0;

function afirmar(bool $condicao, string $descricao, &$sucessos, &$falhas): void
{
    if ($condicao) {
        echo "  [PASS] {$descricao}\n";
        $sucessos++;
    } else {
        echo "  [FAIL] {$descricao}\n";
        $falhas++;
    }
}

// 1. Criação/Identificação de um usuário de teste
$pdo = Database::getConnection();
$matriculaTeste = 'TESTPIN' . time();
$pdo->prepare("
    INSERT INTO usuarios (matricula, nome, perfil, setor, ativo) 
    VALUES (:mat, 'Colaborador Teste PIN', 'colaborador', 'Distribuição', 1)
")->execute(['mat' => $matriculaTeste]);

$usuario = Usuario::buscarPorMatricula($matriculaTeste);
afirmar($usuario !== null, "Usuário de teste criado com sucesso", $sucessos, $falhas);
afirmar(empty($usuario['pin_hash']), "Novo usuário inicia com pin_hash NULL (pendente de cadastro)", $sucessos, $falhas);

$usuarioId = (int)$usuario['id'];

// 2. Teste de Validações de PIN Inválido
afirmar(!Usuario::definirPin($usuarioId, '123'), "Rejeita PIN com menos de 4 dígitos", $sucessos, $falhas);
afirmar(!Usuario::definirPin($usuarioId, '12345'), "Rejeita PIN com mais de 4 dígitos", $sucessos, $falhas);
afirmar(!Usuario::definirPin($usuarioId, 'abcd'), "Rejeita PIN não-numérico", $sucessos, $falhas);
afirmar(!Usuario::definirPin($usuarioId, '12a4'), "Rejeita PIN alfanumérico", $sucessos, $falhas);

// 3. Teste de Definição de PIN Válido
$definido = Usuario::definirPin($usuarioId, '4826');
afirmar($definido === true, "PIN '4826' definido com sucesso", $sucessos, $falhas);

// 4. Teste de Verificação de PIN
afirmar(Usuario::verificarPin($usuarioId, '4826') === true, "Validação de PIN correto '4826' retorna true", $sucessos, $falhas);
afirmar(Usuario::verificarPin($usuarioId, '0000') === false, "Validação de PIN incorreto '0000' retorna false", $sucessos, $falhas);
afirmar(Usuario::verificarPin($usuarioId, '482') === false, "Validação de PIN com tamanho inválido retorna false", $sucessos, $falhas);

// 5. Teste de Alteração de PIN
$alterado = Usuario::definirPin($usuarioId, '9012');
afirmar($alterado === true, "PIN alterado com sucesso para '9012'", $sucessos, $falhas);
afirmar(Usuario::verificarPin($usuarioId, '9012') === true, "Novo PIN '9012' verificado com sucesso", $sucessos, $falhas);
afirmar(Usuario::verificarPin($usuarioId, '4826') === false, "PIN anterior '4826' é rejeitado", $sucessos, $falhas);

// 6. Teste da Listagem de Gestão
$lista = Usuario::listarColaboradoresParaGestao();
$itemNaLista = null;
foreach ($lista as $colab) {
    if ((int)$colab['id'] === $usuarioId) {
        $itemNaLista = $colab;
        break;
    }
}
afirmar($itemNaLista !== null, "Usuário de teste listado na tela de Gestão", $sucessos, $falhas);
afirmar((int)($itemNaLista['tem_pin'] ?? 0) === 1, "Usuário com PIN exibe status tem_pin = 1", $sucessos, $falhas);

// 7. Teste de Reset de PIN pela Gestão
$resetou = Usuario::resetarPin($usuarioId);
afirmar($resetou === true, "Reset de PIN executado com sucesso", $sucessos, $falhas);

$usuarioAposReset = Usuario::buscarPorId($usuarioId);
afirmar(empty($usuarioAposReset['pin_hash']), "Após o reset, pin_hash volta a ser NULL", $sucessos, $falhas);
afirmar(Usuario::verificarPin($usuarioId, '9012') === false, "Após o reset, verificação do PIN antigo retorna false", $sucessos, $falhas);

// 8. Limpeza do usuário de teste
$pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute(['id' => $usuarioId]);
afirmar(true, "Limpeza de usuário de teste executada com sucesso", $sucessos, $falhas);

echo "\n============================================\n";
echo "RESULTADO DOS TESTES DE PIN:\n";
echo "Sucessos: {$sucessos} | Falhas: {$falhas}\n";
echo "============================================\n";

if ($falhas > 0) {
    exit(1);
}
