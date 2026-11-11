<?php
declare(strict_types=1);

/**
 * Projeto Repack - Layout Header (Urna Eletrônica / Quiosque)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */

require_once __DIR__ . '/../../models/Usuario.php';

// Operador ativo da sessão do quiosque
$operadoresAtivos = Usuario::listarOperadoresAtivos();
$operadorAtivoId = $_SESSION['operador_id'] ?? null;
$operadorAtivoNome = 'Selecionar Operador';

if ($operadorAtivoId) {
    foreach ($operadoresAtivos as $op) {
        if ((int)$op['id'] === (int)$operadorAtivoId) {
            $operadorAtivoNome = $op['nome'];
            break;
        }
    }
} elseif (!empty($operadoresAtivos)) {
    // Se nenhum estiver setado, default para o primeiro operador ativo do quiosque
    $_SESSION['operador_id'] = $operadoresAtivos[0]['id'];
    $_SESSION['operador_nome'] = $operadoresAtivos[0]['nome'];
    $operadorAtivoNome = $operadoresAtivos[0]['nome'];
    $operadorAtivoId = $operadoresAtivos[0]['id'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10B981">
    <title><?= sanitize_html($pageTitle ?? APP_NAME) ?></title>
    
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bootstrap.min.css?v=1.1">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/urna.css?v=<?= @filemtime(__DIR__ . '/../../../public/assets/css/urna.css') ?: time() ?>">
    <style>
        /* Previne flash de conteúdo não estilizado (FOUC) antes do carregamento do CSS */
        .urna-modal-backdrop:not(.ativo),
        .feedback-overlay:not(.ativo) {
            display: none !important;
        }
    </style>
</head>
<body>

<!-- Barra Superior Minimalista do Quiosque -->
<header class="quiosque-header" style="justify-content: flex-end; padding: 10px 18px; min-height: 52px; background-color: var(--surface); border-bottom: 1px solid var(--border);">
    <!-- Botão de Engrenagem com Proteção de Toque Acidental (5 segundos) -->
    <div style="position: relative; display: inline-flex; align-items: center;">
        <button type="button" 
                id="btnEngrenagemQuiosque" 
                class="gear-camuflada" 
                title="Pressione e segure por 5s para abrir configurações" 
                aria-label="Configurações do Repack"
                style="position: relative; width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: transparent; border: none; cursor: pointer; color: #9CA3AF; padding: 0;">
            <!-- Anel SVG de Progresso circular ao segurar -->
            <svg class="gear-progress-ring" width="46" height="46" viewBox="0 0 46 46" style="position: absolute; top: 0; left: 0; transform: rotate(-90deg); pointer-events: none;">
                <circle cx="23" cy="23" r="20" stroke="#E5E7EB" stroke-width="3" fill="none"/>
                <circle id="gearProgressCircle" cx="23" cy="23" r="20" stroke="#3B82F6" stroke-width="3" fill="none" stroke-dasharray="125.66" stroke-dashoffset="125.66" style="transition: stroke-dashoffset 0.05s linear;"/>
            </svg>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
            </svg>
        </button>
        <!-- Dica discreta ao segurar -->
        <span id="gearHintText" style="display: none; position: absolute; right: 54px; white-space: nowrap; font-size: 13px; font-weight: 700; background: #111827; color: #FFFFFF; padding: 4px 10px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.2); z-index: 10;">Segure por 5s...</span>
    </div>
</header>

<!-- Modal Unificado de Configurações e Gestão (Aberto após 5 segundos na Engrenagem) -->
<div class="urna-modal-backdrop" id="modalConfiguracoesBackdrop">
    <div class="urna-modal-content" style="max-width: 520px; text-align: left;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <h2 class="headline-lg" style="margin: 0; font-size: 22px;">⚙️ Painel Operacional</h2>
            <button type="button" onclick="fecharModalConfiguracoes()" style="background: none; border: none; font-size: 26px; cursor: pointer; color: var(--on-surface-muted); line-height: 1;" aria-label="Fechar">✕</button>
        </div>

        <!-- Seção 1: Operador em Turno -->
        <div class="urna-card" style="margin-bottom: 16px; padding: 16px; border-radius: 14px;">
            <div style="font-size: 13px; font-weight: 700; color: var(--on-surface-muted); text-transform: uppercase; margin-bottom: 8px;">Operador em Turno</div>
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: #EFF6FF; color: #1D4ED8; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        👤
                    </div>
                    <div>
                        <strong id="nomeOperadorDisplayModal" style="font-size: 18px; display: block; color: var(--on-surface);"><?= sanitize_html($operadorAtivoNome) ?></strong>
                        <span class="status-badge online" id="statusBadgeModal" style="font-size: 12px; padding: 2px 8px;">● Conectado</span>
                    </div>
                </div>
                <button type="button" class="btn-urna btn-urna-outline" style="min-height: 48px; padding: 8px 16px; font-size: 15px; width: auto;" onclick="abrirModalOperadores()">
                    Trocar Operador
                </button>
            </div>
        </div>

        <!-- Seção 2: Acesso à Gestão -->
        <div class="urna-card" style="margin-bottom: 16px; padding: 16px; border-radius: 14px;">
            <div style="font-size: 13px; font-weight: 700; color: var(--on-surface-muted); text-transform: uppercase; margin-bottom: 8px;">Acesso à Gestão (Coordenadora)</div>
            <form id="formLoginGestao" onsubmit="realizarLoginGestao(event)">
                <div style="margin-bottom: 12px;">
                    <label for="gestao_matricula" class="body-sm" style="font-weight: 700; display: block; margin-bottom: 4px;">Matrícula:</label>
                    <input type="text" id="gestao_matricula" name="matricula" required class="form-control" style="min-height: 48px; font-size: 16px;" placeholder="Ex: GEST01">
                </div>
                <div style="margin-bottom: 16px;">
                    <label for="gestao_senha" class="body-sm" style="font-weight: 700; display: block; margin-bottom: 4px;">Senha:</label>
                    <input type="password" id="gestao_senha" name="senha" required class="form-control" style="min-height: 48px; font-size: 16px;" placeholder="••••••••">
                </div>
                <div id="gestaoLoginErro" style="display: none; color: var(--danger); font-weight: 700; margin-bottom: 12px; text-align: center; font-size: 14px;"></div>
                <button type="submit" class="btn-urna btn-urna-primary" style="min-height: 52px; font-size: 17px; width: 100%;">
                    Entrar no Painel de Gestão ➔
                </button>
            </form>
        </div>

        <div>
            <button type="button" class="btn-urna btn-urna-warning" style="min-height: 48px; font-size: 16px; width: 100%;" onclick="fecharModalConfiguracoes()">
                Voltar à Bancada
            </button>
        </div>
    </div>
</div>

<!-- Modal de Troca Rápida de Operador -->
<div class="urna-modal-backdrop" id="modalOperadoresBackdrop">
    <div class="urna-modal-content">
        <h2 class="headline-lg" style="margin-bottom: 20px; text-align: center;">Selecione o Operador</h2>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($operadoresAtivos as $operador): ?>
                <button type="button" 
                        class="btn-urna btn-urna-outline" 
                        style="justify-content: flex-start; padding: 16px 20px; font-size: 20px;"
                        onclick="selecionarOperador(<?= (int)$operador['id'] ?>, '<?= sanitize_html($operador['nome']) ?>')">
                    <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24" style="color: var(--secondary);">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    <span><?= sanitize_html($operador['nome']) ?> (<?= sanitize_html($operador['matricula']) ?>)</span>
                </button>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 24px;">
            <button type="button" class="btn-urna btn-urna-warning" onclick="fecharModalOperadores()">
                Cancelar
            </button>
        </div>
    </div>
</div>

<!-- Overlay de Feedback Verde Sucesso Temporizado (2 segundos) -->
<div class="feedback-overlay" id="feedbackSucessoOverlay">
    <svg width="80" height="80" fill="currentColor" viewBox="0 0 24 24" style="margin-bottom: 16px;">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
    </svg>
    <h2 class="headline-xl" id="feedbackTitulo" style="color: #FFFFFF; margin-bottom: 8px;">CONFIRMADO COM SUCESSO!</h2>
    <p class="body-lg" id="feedbackSubtitulo" style="color: #FFFFFF;">Retornando à tela inicial...</p>
</div>

<main class="urna-container">
