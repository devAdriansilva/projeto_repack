<?php
declare(strict_types=1);

/**
 * Projeto Repack - Layout Gestao Footer
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */
?>
</div> <!-- /gestao-container -->

<footer class="mt-auto py-3 text-center text-muted" style="border-top: 1px solid var(--gestao-border); background-color: var(--gestao-surface); font-size: 13px;">
    <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div>
            <strong><?= sanitize_html(APP_NAME) ?></strong> &bull; Módulo de Gestão & Governança &bull; <?= APP_VERSION ?>
        </div>
        <div style="font-size: 12.5px; color: #6B7280;">
            Desenvolvido por <strong style="color: #374151;">AdrianSilvaDev</strong> &bull; &copy; <?= date('Y') ?>
        </div>
    </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>
