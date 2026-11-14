<?php
declare(strict_types=1);

/**
 * Projeto Repack - Script de Teste de Carga e Estresse Concorrente
 * Simula picos de 100 a 200 requisições simultâneas em produção (Alwaysdata)
 * Mede latência, vazão (RPS), percentis (P50, P95, P99) e estabilidade do MySQL/Apache.
 */

$baseUrl = $argv[1] ?? 'https://financas-facil-atelie.alwaysdata.net/repack';
$baseUrl = rtrim($baseUrl, '/');

echo "========================================================================\n";
echo "       PROJETO REPACK - SIMULAÇÃO DE ESTRESSE E CARGA CONCORRENTE       \n";
echo "========================================================================\n";
echo "Alvo: {$baseUrl}\n";
echo "Início: " . date('Y-m-d H:i:s') . "\n";
echo "------------------------------------------------------------------------\n\n";

/**
 * Executa rajada de requisições concorrentes via curl_multi
 *
 * @param string $url
 * @param int $totalRequests
 * @param int $concurrency
 * @param string $method
 * @param array $headers
 * @return array Estatísticas compiladas
 */
function runStressTest(string $url, int $totalRequests, int $concurrency, string $method = 'GET', array $headers = []): array
{
    $multiHandle = curl_multi_init();
    $activeHandles = [];
    $latencies = [];
    $statusCodes = [];
    $errors = 0;
    $completed = 0;
    $started = 0;

    $overallStart = microtime(true);

    // Função interna para iniciar uma conexão cURL
    $startRequest = function() use ($url, $method, $headers, $multiHandle, &$activeHandles, &$started) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . (strpos($url, '?') === false ? '?' : '&') . '_t=' . bin2hex(random_bytes(4)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'RepackStressTester/1.0 (Simulation)');

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $id = (int)$ch;
        $activeHandles[$id] = [
            'handle' => $ch,
            'start'  => microtime(true)
        ];

        curl_multi_add_handle($multiHandle, $ch);
        $started++;
    };

    // Preenche o pool inicial até a concorrência desejada
    while ($started < $concurrency && $started < $totalRequests) {
        $startRequest();
    }

    echo "Progresso: [";
    $progressBarSteps = 30;
    $lastReportedStep = 0;

    // Loop de execução paralela
    do {
        $status = curl_multi_exec($multiHandle, $active);

        // Processa requisições concluídas
        while ($info = curl_multi_info_read($multiHandle)) {
            $ch = $info['handle'];
            $id = (int)$ch;
            $endTime = microtime(true);

            if (isset($activeHandles[$id])) {
                $durationMs = ($endTime - $activeHandles[$id]['start']) * 1000;
                $latencies[] = $durationMs;

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $statusCodes[$httpCode] = ($statusCodes[$httpCode] ?? 0) + 1;

                if ($httpCode < 200 || $httpCode >= 400 || $info['result'] !== CURLE_OK) {
                    $errors++;
                }

                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
                unset($activeHandles[$id]);
                $completed++;

                // Atualiza barra de progresso visual
                $currentStep = (int)(($completed / $totalRequests) * $progressBarSteps);
                while ($lastReportedStep < $currentStep) {
                    echo "=";
                    $lastReportedStep++;
                }

                // Inicia nova requisição se ainda houver pendentes
                if ($started < $totalRequests) {
                    $startRequest();
                }
            }
        }

        if ($active > 0) {
            curl_multi_select($multiHandle, 0.05);
        }
    } while ($active > 0 || $completed < $totalRequests);

    echo "] 100% ({$completed}/{$totalRequests})\n";

    $overallDuration = microtime(true) - $overallStart;
    curl_multi_close($multiHandle);

    sort($latencies);
    $count = count($latencies);

    $min = $count ? min($latencies) : 0;
    $max = $count ? max($latencies) : 0;
    $avg = $count ? (array_sum($latencies) / $count) : 0;
    $p50 = $count ? $latencies[(int)floor($count * 0.50)] : 0;
    $p90 = $count ? $latencies[(int)floor($count * 0.90)] : 0;
    $p95 = $count ? $latencies[(int)floor($count * 0.95)] : 0;
    $p99 = $count ? $latencies[(int)floor($count * 0.99)] : 0;
    $rps = $overallDuration > 0 ? ($completed / $overallDuration) : 0;

    return [
        'total'           => $totalRequests,
        'completed'       => $completed,
        'concurrency'     => $concurrency,
        'duration_sec'    => $overallDuration,
        'rps'             => $rps,
        'status_codes'    => $statusCodes,
        'errors'          => $errors,
        'latency_min_ms'  => $min,
        'latency_avg_ms'  => $avg,
        'latency_p50_ms'  => $p50,
        'latency_p90_ms'  => $p90,
        'latency_p95_ms'  => $p95,
        'latency_p99_ms'  => $p99,
        'latency_max_ms'  => $max
    ];
}

function printResults(string $scenarioName, array $stats): void
{
    echo "\n>>> RESULTADOS: {$scenarioName}\n";
    echo sprintf("  Total de Requisições: %d (Concorrência Simultânea: %d)\n", $stats['total'], $stats['concurrency']);
    echo sprintf("  Tempo Total da Rajada: %.2f segundos\n", $stats['duration_sec']);
    echo sprintf("  Vazão Efetiva (Throughput): %.2f requisições/segundo (RPS)\n", $stats['rps']);
    
    echo "  Códigos HTTP Retornados:\n";
    foreach ($stats['status_codes'] as $code => $count) {
        $pct = ($count / $stats['total']) * 100;
        $tag = ($code >= 200 && $code < 400) ? "OK" : "ALERTA";
        echo sprintf("    - HTTP %d [%s]: %d (%.1f%%)\n", $code, $tag, $count, $pct);
    }
    
    if ($stats['errors'] > 0) {
        echo sprintf("  ⚠️ Total de Falhas/Erros: %d (%.1f%%)\n", $stats['errors'], ($stats['errors'] / $stats['total']) * 100);
    } else {
        echo "  ✅ Taxa de Sucesso: 100.0% (Zero erros ou quedas)\n";
    }

    echo "  Latências de Resposta:\n";
    echo sprintf("    - Mínima:      %6.1f ms\n", $stats['latency_min_ms']);
    echo sprintf("    - Média:       %6.1f ms\n", $stats['latency_avg_ms']);
    echo sprintf("    - Mediana(P50):%6.1f ms\n", $stats['latency_p50_ms']);
    echo sprintf("    - P90 (90%%):   %6.1f ms\n", $stats['latency_p90_ms']);
    echo sprintf("    - P95 (95%%):   %6.1f ms\n", $stats['latency_p95_ms']);
    echo sprintf("    - P99 (99%%):   %6.1f ms\n", $stats['latency_p99_ms']);
    echo sprintf("    - Máxima:      %6.1f ms\n", $stats['latency_max_ms']);
    echo "------------------------------------------------------------------------\n";
}

// -----------------------------------------------------------------------------
// CENÁRIO 1: 100 REQUISIÇÕES CONCORRENTES NA API COM CONSULTA SQL ATIVA (/api/health)
// -----------------------------------------------------------------------------
echo "[CENÁRIO 1] Testando 100 requisições simultâneas em /api/health...\n";
echo "(Simula 100 colaboradores abrindo a aplicação e disparando consultas ao MySQL)\n";
$stats1 = runStressTest($baseUrl . '/api/health', 100, 25);
printResults("100 Requisições Concorrentes - API + MySQL", $stats1);

// Pausa de 3 segundos para estabilização
sleep(3);

// -----------------------------------------------------------------------------
// CENÁRIO 2: 100 REQUISIÇÕES CONCORRENTES NA VITRINE PÚBLICA (/lojinha)
// -----------------------------------------------------------------------------
echo "\n[CENÁRIO 2] Testando 100 requisições simultâneas na Vitrine (/lojinha)...\n";
echo "(Simula 100 colaboradores carregando simultaneamente a vitrine de produtos e saldos)\n";
$stats2 = runStressTest($baseUrl . '/lojinha', 100, 25);
printResults("100 Requisições Concorrentes - Vitrine Completa", $stats2);

// Pausa de 3 segundos para estabilização
sleep(3);

// -----------------------------------------------------------------------------
// CENÁRIO 3: 200 REQUISIÇÕES CONCORRENTES - ESTRESSE MÁXIMO (/api/health)
// -----------------------------------------------------------------------------
echo "\n[CENÁRIO 3] Testando 200 requisições com alta concorrência (/api/health)...\n";
echo "(Simula o pico máximo previsto de 200 colaboradores em rajada simultânea)\n";
$stats3 = runStressTest($baseUrl . '/api/health', 200, 40);
printResults("200 Requisições Concorrentes - Estresse Máximo", $stats3);

echo "\n========================================================================\n";
echo "                    RESUMO GERAL DO TESTE DE CARGA                      \n";
echo "========================================================================\n";
$totalGeral = $stats1['total'] + $stats2['total'] + $stats3['total'];
$totalErros = $stats1['errors'] + $stats2['errors'] + $stats3['errors'];
$sucessoPct = (($totalGeral - $totalErros) / $totalGeral) * 100;

echo sprintf("Total acumulado de requisições disparadas: %d\n", $totalGeral);
echo sprintf("Taxa global de sucesso: %.2f%%\n", $sucessoPct);
echo sprintf("Latência média geral ponderada: %.1f ms\n", ($stats1['latency_avg_ms'] + $stats2['latency_avg_ms'] + $stats3['latency_avg_ms']) / 3);

if ($totalErros === 0) {
    echo "\n🏆 CONCLUSÃO: O servidor suportou 100%% da carga sem qualquer erro 500, 503 ou queda!\n";
} else {
    echo "\n⚠️ ATENÇÃO: Foram identificadas {$totalErros} falhas durante a rajada de carga.\n";
}
echo "========================================================================\n";
