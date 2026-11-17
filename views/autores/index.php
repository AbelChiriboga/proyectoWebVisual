<?php
// Vista de autores (igual a vista.CRUDAutores). Usa: $datos, $autores y $modoPedido.
$titulo = 'Gestión de autores';
// Si se abrió con el "+" de Libros, Regresar vuelve al formulario del libro tal como estaba
$regresar = empty($_SESSION['libro_formulario']) ? 'c=libros'
    : 'c=libros&a=retomar&modo=' . urlencode($_SESSION['libro_formulario']['modo']) . con_busqueda($_SESSION['libros_buscar'] ?? null);
$editando = $datos['id_autor'] !== '';
$modo = modo_formulario($modoPedido, $editando);
require __DIR__ . '/../plantillas/cabecera.php';
?>
<div class="fila">
    <form method="post" action="index.php?c=autores&a=guardar" class="enter grupo lateral" id="formulario" data-crud="<?= $modo ?>">
        <h3>Datos del autor</h3>
        <input type="hidden" name="id_autor" value="<?= e($datos['id_autor']) ?>">
        <input type="hidden" name="modo" value="">
        <?php campo_busqueda($buscado); ?>
        <div class="formulario uno">
            <div class="campo"><label>Nombres</label>
                <input type="text" name="nombres" value="<?= e($datos['nombres']) ?>" data-tipo="texto" maxlength="60" required></div>
            <div class="campo"><label>Apellidos</label>
                <input type="text" name="apellidos" value="<?= e($datos['apellidos']) ?>" data-tipo="texto" maxlength="60" required></div>
            <div class="campo"><label>Nacionalidad</label>
                <input type="text" name="nacionalidad" value="<?= e($datos['nacionalidad']) ?>" data-tipo="texto" maxlength="50"></div>
        </div>
        <div class="botones rejilla">
            <a class="boton" href="index.php?c=autores&modo=nuevo<?= con_busqueda($buscado) ?>" data-modos="consulta seleccion">Nuevo</a>
            <button class="boton" data-modos="nuevo edicion">Guardar</button>
            <button type="button" class="boton" data-editar="formulario" data-modos="seleccion">Editar</button>
            <button class="boton" form="eliminar" data-modos="seleccion">Eliminar</button>
            <a class="boton todo" href="index.php?c=autores<?= con_busqueda($buscado) ?>" data-modos="seleccion nuevo edicion">Cancelar</a>
        </div>
    </form>
    <section class="grupo">
        <h3>Autores registrados</h3>
        <div class="barra">
            <?php barra_busqueda($buscado, 'Buscar por nombre o nacionalidad'); ?>
        </div>
        <div class="tabla-caja alta">
            <table id="tabla">
                <thead><tr><th>Nombres</th><th>Apellidos</th><th>Nacionalidad</th></tr></thead>
                <tbody>
                <?php foreach ($autores as $a): ?>
                    <tr class="<?= $a['id_autor'] == $datos['id_autor'] ? 'elegida' : '' ?>"
                        data-href="index.php?c=autores&a=editar&id=<?= $a['id_autor'] ?><?= con_busqueda($buscado) ?>">
                        <td><?= e($a['nombres']) ?></td><td><?= e($a['apellidos']) ?></td><td><?= e($a['nacionalidad']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php fila_busqueda($buscado, count($autores), $hayMas, 3); ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php if ($editando): ?>
    <form method="post" action="index.php?c=autores&a=eliminar" id="eliminar"
          data-confirmar="¿Eliminar a <?= e($datos['nombres'] . ' ' . $datos['apellidos']) ?>?">
        <?php campo_busqueda($buscado); ?>
        <input type="hidden" name="id_autor" value="<?= e($datos['id_autor']) ?>">
    </form>
<?php endif; ?>
<?php form_busqueda('autores'); ?>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
