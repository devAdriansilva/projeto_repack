<?php
declare(strict_types=1);

/**
 * Projeto Repack - Layout Gestao Footer
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */
?>
</div> <!-- /gestao-container -->

<footer class="mt-auto py-3 text-center text-muted" style="border-top: 1px solid var(--gestao-border); background-color: var(--gestao-surface); font-size: 13px;">
    <div class="container">
        <strong><?= sanitize_html(APP_NAME) ?></strong> &bull; Módulo de Gestão & Governança &bull; &copy; <?= date('Y') ?>
    </div>
</footer>

<script src="/assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>
