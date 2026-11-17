<?php
declare(strict_types=1);

/**
 * Projeto Repack - Gestão de Colaboradores e Controle de PIN de Compras
 * Baseado estritamente em docs/FSD.md (Seções 12.3 e 15) e docs/DESIGN.md
 */

$pageTitle = 'Gestão de Colaboradores & PINs - Repack';
$rotaAtual = '/gestao/colaboradores';
require_once __DIR__ . '/../layouts/gestao_header.php';
?>

<div class="container-fluid px-3 px-md-4 py-4">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold text-white mb-0">Gestão de Colaboradores & PINs</h1>
                <span class="badge bg-primary"><?= count($colaboradores) ?> colaboradores</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Controle de acesso à Lojinha dos Colaboradores e autoatendimento de redefinição de PIN de 4 dígitos.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <input type="text" 
                   id="filtroColaborador" 
                   class="form-control form-control-sm text-white" 
                   placeholder="🔍 Buscar por nome, matrícula ou setor..." 
                   style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.2); min-width: 280px; border-radius: 8px;">
        </div>
    </div>

    <?php if (!empty($mensagemSucesso)): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #6EE7B7; border-radius: 12px;">
            <span class="me-2 fs-5">✅</span>
            <div><?= sanitize_html($mensagemSucesso) ?></div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensagemErro)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #FCA5A5; border-radius: 12px;">
            <span class="me-2 fs-5">⚠️</span>
            <div><?= sanitize_html($mensagemErro) ?></div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabela de Colaboradores -->
    <div class="card border-0 shadow-sm" style="background: #1E293B; border-radius: 16px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle" id="tabelaColaboradores">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(255, 255, 255, 0.1); background: #0F172A; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #94A3B8;">
                        <th class="py-3 px-3">Matrícula</th>
                        <th class="py-3 px-3">Nome do Colaborador</th>
                        <th class="py-3 px-3">Setor</th>
                        <th class="py-3 px-3">Perfil</th>
                        <th class="py-3 px-3 text-center">Status do PIN</th>
                        <th class="py-3 px-3 text-end">Ações de Suporte</th>
                    </tr>
                </thead>
                <tbody style="font-size: 14px;">
                    <?php if (empty($colaboradores)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Nenhum colaborador localizado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($colaboradores as $c): ?>
                            <tr class="linha-colaborador" style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                <td class="px-3">
                                    <span class="badge" style="background: rgba(255, 255, 255, 0.1); color: #F1F5F9; font-size: 13px; font-weight: 700;">
                                        <?= sanitize_html($c['matricula']) ?>
                                    </span>
                                </td>
                                <td class="px-3 fw-bold text-white colab-nome">
                                    <?= sanitize_html($c['nome']) ?>
                                </td>
                                <td class="px-3 text-light colab-setor">
                                    <span class="badge bg-secondary" style="font-size: 12px; font-weight: 600;">
                                        <?= sanitize_html($c['setor']) ?>
                                    </span>
                                </td>
                                <td class="px-3 text-muted" style="text-transform: capitalize;">
                                    <?= sanitize_html($c['perfil']) ?>
                                </td>
                                <td class="px-3 text-center">
                                    <?php if (!empty($c['tem_pin'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1" style="font-size: 12px; font-weight: 700; border-radius: 6px;">
                                            🔒 PIN Ativo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1" style="font-size: 12px; font-weight: 700; border-radius: 6px;">
                                            ⚠️ Pendente de Cadastro
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 text-end">
                                    <?php if (!empty($c['tem_pin'])): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning fw-bold px-3 py-1.5" 
                                                onclick="abrirModalResetPin(<?= (int)$c['id'] ?>, '<?= sanitize_html(addslashes($c['nome'])) ?>', '<?= sanitize_html(addslashes($c['matricula'])) ?>')"
                                                style="border-radius: 8px; font-size: 12.5px;">
                                            <span>🔄</span> Resetar PIN
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Criação no 1º acesso</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Reset de PIN -->
<div class="modal fade" id="modalConfirmarResetPin" tabindex="-1" aria-labelledby="modalResetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #0F172A; color: #FFFFFF; border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 18px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-warning d-flex align-items-center gap-2" id="modalResetLabel">
                    <span>🔄</span> Confirmar Reset de PIN
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-3" style="font-size: 15px; color: #E2E8F0;">
                    Tem certeza de que deseja resetar o PIN do colaborador abaixo?
                </p>
                <div class="p-3 rounded-3 mb-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="fw-bold text-white fs-5" id="resetModalNome">-</div>
                    <div class="text-muted small mt-1">Matrícula: <span class="fw-bold text-info" id="resetModalMatricula">-</span></div>
                </div>
                <div class="alert alert-info py-2 px-3 small mb-0" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #93C5FD; border-radius: 8px;">
                    ℹ️ O PIN anterior será removido e o colaborador poderá <strong>cadastrar um novo PIN de 4 dígitos</strong> imediatamente em seu próximo acesso à Lojinha.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <form method="POST" action="<?= APP_URL ?>/gestao/colaboradores/reset-pin" class="w-100 d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= AuthController::gerarCsrfToken() ?>">
                    <input type="hidden" name="usuario_id" id="resetModalUsuarioId" value="0">

                    <button type="button" class="btn btn-secondary flex-fill fw-bold py-2" data-bs-dismiss="modal" style="border-radius: 10px;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning flex-fill fw-bold py-2 text-dark" style="border-radius: 10px;">
                        Confirmar Reset
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Filtro em tempo real de colaboradores
document.getElementById('filtroColaborador').addEventListener('input', function(e) {
    const termo = e.target.value.toLowerCase().trim();
    const linhas = document.querySelectorAll('.linha-colaborador');

    linhas.forEach(linha => {
        const texto = linha.innerText.toLowerCase();
        linha.style.display = texto.includes(termo) ? '' : 'none';
    });
});

// Abertura do modal de reset
function abrirModalResetPin(id, nome, matricula) {
    document.getElementById('resetModalUsuarioId').value = id;
    document.getElementById('resetModalNome').textContent = nome;
    document.getElementById('resetModalMatricula').textContent = matricula;

    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarResetPin'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../layouts/gestao_footer.php'; ?>
