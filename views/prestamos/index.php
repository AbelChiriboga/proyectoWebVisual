<?php
// Vista de préstamo de libros.
// Los libros se agregan con el botón "Agregar libros", que abre una ventana para buscarlos y elegirlos.
// Usa: $modulo, $estudiante, $sit, $estadoTexto, $estadoOk, $maximo, $buscado, $elegidos (ya agregados),
//      $encontrados (resultado de la búsqueda), $hayMas, $tope, $abrirBusqueda y $devolverHasta.
$titulo = 'Nuevo préstamo';
$regresar = 'c=menu';
require __DIR__ . '/../plantillas/cabecera.php';
?>
<form method="post" action="index.php?c=prestamos&a=confirmar" class="fila">
    <?php require __DIR__ . '/../plantillas/panel_huella.php'; ?>
    <section class="grupo">
        <h3>Ejemplares a prestar</h3>
        <div class="barra">
            <!-- Abre la ventana para buscar y elegir los ejemplares (no se cargan todos los libros) -->
            <button type="button" class="boton agregar-libros" data-abrir="ventana-libros">
                <img src="views/assets/img/AgregarLibros.png" alt="">Agregar libros</button>
        </div>
        <div class="tabla-caja" style="height: calc(100vh - 345px)">
            <table>
                <thead><tr><th>Código</th><th>Título</th><th>Autor</th><th>Ubicación</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($elegidos as $d): ?>
                    <tr>
                        <td><input type="hidden" name="ejemplares[]" value="<?= $d['id_ejemplar'] ?>"><?= e($d['codigo_inventario']) ?></td>
                        <td><?= e($d['titulo']) ?></td><td><?= e($d['autor']) ?></td><td><?= e($d['ubicacion']) ?></td>
                        <td class="accion"><button class="boton quitar" name="quitar" value="<?= $d['id_ejemplar'] ?>"
                                formaction="index.php?c=prestamos&a=quitar" formnovalidate title="Quitar del préstamo">Quitar</button></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$elegidos): ?>
                    <tr><td colspan="5" class="vacio">Presione Agregar libros para buscar y elegir los ejemplares.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="botones" style="justify-content: space-between; align-items: center; flex-wrap: nowrap">
            <span class="resumen" style="margin: 0">Seleccionados: <?= count($elegidos) ?><?= $estudiante ? ' de ' . $maximo . ' permitidos' : '' ?>
                &nbsp;·&nbsp; Devolver hasta: <?= fecha($devolverHasta) ?></span>
            <span class="botones" style="margin: 0">
                <button class="boton" formaction="index.php?c=prestamos&a=cancelar" formnovalidate>Cancelar</button>
                <button class="boton primario">Confirmar préstamo</button>
            </span>
        </div>
    </section>

    <!-- Ventana "Agregar libros": se busca en la base, se marcan los ejemplares y se agregan al préstamo -->
    <div class="ventana-fondo" id="ventana-libros" <?= $abrirBusqueda ? '' : 'hidden' ?>>
        <div class="ventana" role="dialog" aria-labelledby="ventana-libros-titulo">
            <div class="ventana-titulo">
                <span id="ventana-libros-titulo">Agregar libros</span>
                <button type="button" class="ventana-cerrar" data-cerrar-ventana title="Cerrar">&times;</button>
            </div>
            <input type="hidden" name="busqueda_abierta" value="<?= $abrirBusqueda ? 1 : 0 ?>">
            <div class="barra">
                <input type="text" name="buscar" value="<?= e($buscado) ?>" placeholder="Buscar por código, título o autor"
                       data-tipo="libre" maxlength="100" data-enter="boton-buscar">
                <button class="boton solo-icono" id="boton-buscar" formaction="index.php?c=prestamos&a=buscar" formnovalidate title="Buscar">
                    <img src="views/assets/img/Buscar.png" alt="Buscar"></button>
                <button class="boton solo-icono" formaction="index.php?c=prestamos&a=limpiar" formnovalidate title="Limpiar lo buscado">
                    <img src="views/assets/img/Limpiar.png" alt="Limpiar"></button>
            </div>
            <!-- data-max = cuántos libros más se pueden elegir -->
            <div class="tabla-caja ventana-tabla" data-max="<?= max(0, $maximo - count($elegidos)) ?>">
                <table>
                    <thead><tr><th></th><th>Código</th><th>Título</th><th>Autor</th><th>Ubicación</th></tr></thead>
                    <tbody>
                    <?php foreach ($encontrados as $d): ?>
                        <tr class="seleccionable-libro">
                            <td><input type="checkbox" name="agregar[]" value="<?= $d['id_ejemplar'] ?>"></td>
                            <td><?= e($d['codigo_inventario']) ?></td><td><?= e($d['titulo']) ?></td>
                            <td><?= e($d['autor']) ?></td><td><?= e($d['ubicacion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($buscado === ''): ?>
                        <tr><td colspan="5" class="vacio">Escriba el código, el título o el autor del libro y presione Enter o la lupa.</td></tr>
                    <?php elseif (!$encontrados): ?>
                        <tr><td colspan="5" class="vacio">No hay <?= $elegidos ? 'más ' : '' ?>ejemplares disponibles con "<?= e($buscado) ?>".</td></tr>
                    <?php elseif ($hayMas): ?>
                        <tr><td colspan="5" class="vacio">Se muestran los primeros <?= $tope ?>. Escriba algo más para encontrar el libro.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="botones" style="justify-content: space-between; align-items: center; flex-wrap: nowrap">
                <span class="resumen" style="margin: 0">Elegidos: <span id="marcados">0</span></span>
                <span class="botones" style="margin: 0">
                    <button type="button" class="boton" data-cerrar-ventana>Cerrar</button>
                    <button class="boton primario" formaction="index.php?c=prestamos&a=agregar" formnovalidate>Agregar al préstamo</button>
                </span>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
