<?php
declare(strict_types=1);

/**
 * Projeto Repack - Layout Footer da Lojinha Virtual (Colaborador)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */
?>
</main>

<footer class="text-center py-4 text-muted" style="font-size: 14px; border-top: 1px solid #E5E7EB; margin-top: 40px;">
    <div class="container">
        <p class="mb-1"><strong><?= sanitize_html(APP_NAME) ?></strong> — Lojinha Interna da Reembalagem</p>
        <p class="mb-0 text-secondary">Avarias leves destinadas à compra exclusiva por colaboradores. Retirada física no galpão logístico.</p>
    </div>
</footer>

<script src="/assets/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/touch-handler.js"></script>

<script>
// Utilitário para adicionar produto via AJAX na Vitrine
function adicionarAoCarrinho(produtoId, btnElement) {
    if (!produtoId) return;

    const originalContent = btnElement.innerHTML;
    btnElement.disabled = true;
    btnElement.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span style="font-size: 16px;">Reservando...</span>
    `;

    fetch('/api/carrinho/adicionar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ produto_id: produtoId, quantidade: 1 })
    })
    .then(async res => {
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.message || 'Falha ao adicionar produto.');
        }
        return data;
    })
    .then(data => {
        // Atualiza contador no cabeçalho
        const contador = document.getElementById('headerCarrinhoContador');
        if (contador && data.resumo) {
            contador.innerText = data.resumo.itens_carrinho;
        }

        // Atualiza banner de cota se presente na tela
        const elLimite = document.getElementById('cotaLimiteRestante');
        if (elLimite && data.resumo) {
            elLimite.innerText = data.resumo.limite_restante;
        }

        // Feedback de sucesso no botão
        btnElement.classList.remove('btn-urna-secondary');
        btnElement.classList.add('btn-urna-primary');
        btnElement.innerHTML = `
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            <span style="font-size: 18px;">Reservado (10 min)!</span>
        `;

        setTimeout(() => {
            window.location.reload();
        }, 800);
    })
    .catch(err => {
        alert(err.message);
        btnElement.disabled = false;
        btnElement.innerHTML = originalContent;
    });
}
</script>

</body>
</html>
