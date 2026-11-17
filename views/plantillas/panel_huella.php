<?php
// Recuadro de la huella y ficha del estudiante en Préstamos y Devoluciones (igual a componentes.PanelHuella).
// La huella se lee sola, sin botón: el marco oculto (iframe) le pide la lectura al servidor (ver app.js).
// Usa: $modulo, $estudiante (o null), $sit, $estadoTexto, $estadoOk y $lectura.
?>
<section class="grupo lateral">
    <h3>Huella del estudiante</h3>
    <?php if ($estudiante): ?>
        <div class="recuadro-huella ok">
            <img src="views/assets/img/estudianteVista.png" alt="Estudiante">
        </div>
        <p class="huella-texto">Huella reconocida.</p>
    <?php else: ?>
        <div class="recuadro-huella"><span>Coloque el dedo</span></div>
        <p class="huella-texto" id="huella-texto">Coloque el dedo en el lector.</p>
        <iframe id="lector" hidden title="Lector de huella" data-escuchar="index.php?c=<?= $modulo ?>&a=escuchar&t=<?= $lectura ?>"
                data-al-leer="index.php?c=<?= $modulo ?>&a=mostrar"></iframe>
    <?php endif; ?>
    <div class="ficha">
        <h4><?= $estudiante ? e($estudiante['nombres'] . ' ' . $estudiante['apellidos']) : '—' ?></h4>
        <p>Cédula: <?= $estudiante ? e($estudiante['cedula']) : '—' ?></p>
        <p>Carrera: <?= $estudiante ? e($estudiante['carrera']) : '—' ?></p>
        <p class="espacio">Préstamos activos: <?= $estudiante ? $sit['cantidad'] . ' de ' . $sit['maximo'] : '—' ?></p>
        <div class="franja <?= $estudiante ? ($estadoOk ? 'ok' : 'mal') : '' ?>"><?= e($estadoTexto) ?></div>
    </div>
</section>
