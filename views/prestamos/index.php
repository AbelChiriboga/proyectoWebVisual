<?php
// Vista de préstamo de libros.
// Usa: $modulo, $estudiante, $sit, $estadoTexto, $estadoOk, $maximo, $buscado, $elegidos (ya marcados),
//      $encontrados (resultado de la búsqueda), $hayMas, $tope y $devolverHasta.
$titulo = 'Nuevo préstamo';
$regresar = 'c=menu';
require __DIR__ . '/../plantillas/cabecera.php';
?>
<form method="post" action="index.php?c=prestamos&a=confirmar" class="fila">
    <?php require __DIR__ . '/../plantillas/panel_huella.php'; ?>
    <section class="grupo">
        <h3>Ejemplares a prestar</h3>
        <!-- No se cargan todos los libros: se busca en la base y solo llegan los que coinciden -->
        <div class="barra">
            <input type="text" name="buscar" value="<?= e($buscado) ?>" placeholder="Buscar por código, título o autor"
                   data-tipo="libre" maxlength="100" data-enter="boton-buscar" <?= $elegidos || $buscado !== '' ? '' : 'autofocus' ?>>
            <button class="boton solo-icono" id="boton-buscar" formaction="index.php?c=prestamos&a=buscar" formnovalidate title="Buscar">
                <img src="views/assets/img/Buscar.png" alt="Buscar"></button>
            <button class="boton solo-icono" formaction="index.php?c=prestamos&a=limpiar" formnovalidate title="Limpiar lo buscado">
                <img src="views/assets/img/Limpiar.png" alt="Limpiar"></button>
        </div>
        <div class="tabla-caja" style="height: calc(100vh - 345px)" data-max="<?= $maximo ?>">
            <table id="tabla">
                <thead><tr><th></th><th>Código</th><th>Título</th><th>Autor</th><th>Ubicación</th></tr></thead>
                <tbody>
                <?php foreach (array_merge($elegidos, $encontrados) as $d): ?>
                    <?php $marcado = in_array($d, $elegidos); ?>
                    <tr class="<?= $marcado ? 'marcada' : '' ?>">
                        <td><input type="checkbox" name="ejemplares[]" value="<?= $d['id_ejemplar'] ?>" <?= $marcado ? 'checked' : '' ?>></td>
                        <td><?= e($d['codigo_inventario']) ?></td><td><?= e($d['titulo']) ?></td>
                        <td><?= e($d['autor']) ?></td><td><?= e($d['ubicacion']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($buscado === '' && !$elegidos): ?>
                    <tr><td colspan="5" class="vacio">Escriba el código, el título o el autor del libro y presione Enter o la lupa.</td></tr>
                <?php elseif ($buscado !== '' && !$encontrados): ?>
                    <tr><td colspan="5" class="vacio">No hay <?= $elegidos ? 'más ' : '' ?>ejemplares disponibles con "<?= e($buscado) ?>".</td></tr>
                <?php elseif ($hayMas): ?>
                    <tr><td colspan="5" class="vacio">Se muestran los primeros <?= $tope ?>. Escriba algo más para encontrar el libro.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="botones" style="justify-content: space-between; align-items: center; flex-wrap: nowrap">
            <span class="resumen" style="margin: 0">Seleccionados: <span id="marcados"><?= count($elegidos) ?></span><?= $estudiante ? ' de ' . $maximo . ' permitidos' : '' ?>
                &nbsp;·&nbsp; Devolver hasta: <?= fecha($devolverHasta) ?></span>
            <span class="botones" style="margin: 0">
                <button class="boton" formaction="index.php?c=prestamos&a=cancelar" formnovalidate>Cancelar</button>
                <button class="boton primario">Confirmar préstamo</button>
            </span>
        </div>
    </section>
</form>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
