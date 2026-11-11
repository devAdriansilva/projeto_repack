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
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/urna.css">
    <style>
        /* Previne flash de conteúdo não estilizado (FOUC) antes do carregamento do CSS */
        .urna-modal-backdrop:not(.ativo),
        .feedback-overlay:not(.ativo) {
            display: none !important;
        }
    </style>
</head>
<body>

<!-- Barra Superior do Quiosque -->
<header class="quiosque-header">
    <div class="quiosque-info">
        <span class="quiosque-operador-badge" id="badgeOperadorAtivo">
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            Operador: <strong id="nomeOperadorDisplay"><?= sanitize_html($operadorAtivoNome) ?></strong>
        </span>

        <button type="button" class="btn-trocar-operador" onclick="abrirModalOperadores()">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path d="M16 17.01V10h-2v7.01h-3L15 21l4-3.99h-3zM9 3L5 6.99h3V14h2V6.99h3L9 3z"/>
            </svg>
            Trocar Operador
        </button>

        <span class="status-badge online" id="statusBadge">● Conectado</span>
    </div>

    <!-- Engrenagem camuflada da Gestão -->
    <div>
        <button type="button" class="gear-camuflada" title="Configurações" onclick="abrirModalGestao()" aria-label="Acesso Gerencial">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
            </svg>
        </button>
    </div>
</header>

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

<!-- Modal de Acesso à Gestão (Acionado pela Engrenagem) -->
<div class="urna-modal-backdrop" id="modalGestaoBackdrop">
    <div class="urna-modal-content">
        <h2 class="headline-lg" style="margin-bottom: 16px; text-align: center;">Acesso Administrativo</h2>
        <p class="body-md" style="color: var(--on-surface-muted); text-align: center; margin-bottom: 20px;">
            Área restrita à Coordenadora. Informe matrícula e senha para prosseguir.
        </p>
        <form id="formLoginGestao" onsubmit="realizarLoginGestao(event)">
            <div style="margin-bottom: 16px;">
                <label for="gestao_matricula" class="body-md" style="font-weight: 700; display: block; margin-bottom: 6px;">Matrícula:</label>
                <input type="text" id="gestao_matricula" name="matricula" required class="form-control" style="min-height: 54px; font-size: 18px;" placeholder="Ex: GEST01">
            </div>
            <div style="margin-bottom: 24px;">
                <label for="gestao_senha" class="body-md" style="font-weight: 700; display: block; margin-bottom: 6px;">Senha:</label>
                <input type="password" id="gestao_senha" name="senha" required class="form-control" style="min-height: 54px; font-size: 18px;" placeholder="••••••••">
            </div>
            <div id="gestaoLoginErro" style="display: none; color: var(--danger); font-weight: 700; margin-bottom: 16px; text-align: center;"></div>
            <div style="display: flex; gap: 12px;">
                <button type="button" class="btn-urna btn-urna-warning" style="font-size: 18px;" onclick="fecharModalGestao()">
                    Voltar
                </button>
                <button type="submit" class="btn-urna btn-urna-primary" style="font-size: 18px;">
                    Entrar
                </button>
            </div>
        </form>
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
