<?php
// Vista de ejemplares de un libro (igual a vista.CRUDEjemplares).
// Usa: $libro, $nuevo, $datos, $editando, $codigo, $estados, $editables, $ejemplares, $disponibles, $volver,
//      $regresarA y $modoPedido.
$titulo = 'Ejemplares del libro';
$regresar = $regresarA;
$modo = modo_formulario($modoPedido, $editando);
require __DIR__ . '/../plantillas/cabecera.php';
?>
<div class="barra-libro">
    <span><strong>Libro:</strong> <?= e($libro['titulo']) ?></span>
    <span><strong>Autor:</strong> <?= e($libro['autor']) ?></span>
    <span><strong>ISBN:</strong> <?= e($libro['isbn'] ?: '—') ?></span>
</div>
<div class="fila">
    <form method="post" action="index.php?c=ejemplares&a=guardar" class="enter grupo lateral" id="formulario" data-crud="<?= $modo ?>">
        <h3>Datos del ejemplar</h3>
        <input type="hidden" name="libro" value="<?= e($libro['id_libro']) ?>">
        <input type="hidden" name="nuevo" value="<?= $nuevo ? 1 : '' ?>">
        <input type="hidden" name="id_ejemplar" value="<?= e($datos['id_ejemplar']) ?>">
        <input type="hidden" name="modo" value="">
        <div class="formulario uno">
            <div class="campo"><label>Código de inventario</label>
                <input type="text" value="<?= $modo === 'consulta' ? '' : e($codigo) ?>" data-siempre-fijo></div>
            <div class="campo"><label>Ubicación</label>
                <input type="text" name="ubicacion" value="<?= e($datos['ubicacion']) ?>" data-tipo="libre" maxlength="50"></div>
            <div class="campo"><label>Estado</label>
                <?php if ($editando): ?>
                    <select name="estado" required>
                        <?php foreach ($editables as $valor => $nombre): ?>
                            <option value="<?= $valor ?>" <?= $datos['estado'] === $valor ? 'selected' : '' ?>><?= $nombre ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <!-- Un ejemplar nuevo siempre nace Disponible -->
                    <select data-siempre-fijo><option><?= $modo === 'nuevo' ? 'Disponible' : '' ?></option></select>
                <?php endif; ?>
            </div>
        </div>
        <div class="botones rejilla">
            <a class="boton" href="index.php?<?= e($volver) ?>&modo=nuevo" data-modos="consulta seleccion">Nuevo</a>
            <button class="boton" data-modos="nuevo edicion">Guardar</button>
            <button type="button" class="boton" data-editar="formulario" data-modos="seleccion">Editar</button>
            <button class="boton" form="eliminar" data-modos="seleccion">Eliminar</button>
            <a class="boton todo" href="index.php?<?= e($volver) ?>" data-modos="seleccion nuevo edicion">Cancelar</a>
        </div>
    </form>
    <section class="grupo">
        <h3>Ejemplares de este libro</h3>
        <div class="tabla-caja alta con-nota">
            <table>
                <thead><tr><th>Código de inventario</th><th>Ubicación</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($ejemplares as $x): ?>
                    <tr class="<?= $x['id_ejemplar'] == $datos['id_ejemplar'] ? 'elegida' : '' ?>"
                        <?php if ($x['estado'] !== 'PRESTADO'): ?>data-href="index.php?<?= e($volver) ?>&a=editar&id=<?= $x['id_ejemplar'] ?>"<?php endif; ?>>
                        <td><?= e($x['codigo_inventario']) ?></td><td><?= e($x['ubicacion']) ?></td><td><?= $estados[$x['estado']] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="resumen"><?= count($ejemplares) ?> ejemplar(es), <?= $disponibles ?> disponible(s).
            <?= $nuevo ? 'Se guardan junto con el libro al presionar Guardar.' : 'Un ejemplar prestado no se puede editar hasta que se devuelva.' ?></p>
    </section>
</div>

<?php if ($editando): ?>
    <form method="post" action="index.php?c=ejemplares&a=eliminar" id="eliminar"
          data-confirmar="¿Eliminar el ejemplar <?= e($datos['codigo_inventario']) ?>?">
        <input type="hidden" name="libro" value="<?= e($libro['id_libro']) ?>">
        <input type="hidden" name="nuevo" value="<?= $nuevo ? 1 : '' ?>">
        <input type="hidden" name="id_ejemplar" value="<?= e($datos['id_ejemplar']) ?>">
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
