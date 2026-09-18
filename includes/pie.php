<?php
/* ============================================================
   PIE DE PÁGINA COMÚN
   ============================================================ */
?>
</main>

<footer class="pie">
    <div class="greca greca-oscura" aria-hidden="true"></div>
    <div class="basamento">
        <p class="pie-titulo"><?= e(APP_NOMBRE) ?></p>
        <p>Anno <?= romano((int)date('Y')) ?> · Hecho en PHP y MySQL</p>
    </div>
</footer>

<script>
/* Confirmación antes de acciones destructivas (eliminar, devolver) */
document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
    formulario.addEventListener('submit', function (evento) {
        if (!confirm(formulario.dataset.confirmar)) {
            evento.preventDefault();
        }
    });
});

/* Los avisos desaparecen solos después de unos segundos */
document.querySelectorAll('.aviso').forEach(function (aviso) {
    setTimeout(function () { aviso.classList.add('oculto'); }, 6000);
});
</script>
</body>
</html>
