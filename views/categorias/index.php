<?php
// Vista de categorías (igual a vista.CRUDCategoria). Usa: $datos, $categorias y $modoPedido.
$titulo = 'Gestión de categorías';
// Si se abrió con el "+" de Libros, Regresar vuelve al formulario del libro tal como estaba
$regresar = empty($_SESSION['libro_formulario']) ? 'c=libros'
    : 'c=libros&a=retomar&modo=' . urlencode($_SESSION['libro_formulario']['modo']) . con_busqueda($_SESSION['libros_buscar'] ?? null);
$editando = $datos['id_categoria'] !== '';
$modo = modo_formulario($modoPedido, $editando);
require __DIR__ . '/../plantillas/cabecera.php';
?>
<div class="fila">
    <form method="post" action="index.php?c=categorias&a=guardar" class="enter grupo lateral" id="formulario" data-crud="<?= $modo ?>">
        <h3>Datos de la categoría</h3>
        <input type="hidden" name="id_categoria" value="<?= e($datos['id_categoria']) ?>">
        <input type="hidden" name="modo" value="">
        <?php campo_busqueda($buscado); ?>
        <div class="formulario uno">
            <div class="campo"><label>Nombre de la categoría</label>
                <input type="text" name="nombre" value="<?= e($datos['nombre']) ?>" data-tipo="texto" maxlength="60" required></div>
        </div>
        <div class="botones rejilla">
            <a class="boton" href="index.php?c=categorias&modo=nuevo<?= con_busqueda($buscado) ?>" data-modos="consulta seleccion">Nuevo</a>
            <button class="boton" data-modos="nuevo edicion">Guardar</button>
            <button type="button" class="boton" data-editar="formulario" data-modos="seleccion">Editar</button>
            <button class="boton" form="eliminar" data-modos="seleccion">Eliminar</button>
            <a class="boton todo" href="index.php?c=categorias<?= con_busqueda($buscado) ?>" data-modos="seleccion nuevo edicion">Cancelar</a>
        </div>
    </form>
    <section class="grupo">
        <h3>Categorías registradas</h3>
        <div class="barra">
            <?php barra_busqueda($buscado, 'Buscar por nombre'); ?>
        </div>
        <div class="tabla-caja alta">
            <table id="tabla">
                <thead><tr><th>Categoría</th><th>Libros</th><th>Ejemplares</th></tr></thead>
                <tbody>
                <?php foreach ($categorias as $c): ?>
                    <tr class="<?= $c['id_categoria'] == $datos['id_categoria'] ? 'elegida' : '' ?>"
                        data-href="index.php?c=categorias&a=editar&id=<?= $c['id_categoria'] ?><?= con_busqueda($buscado) ?>">
                        <td><?= e($c['nombre']) ?></td><td><?= $c['libros'] ?></td><td><?= $c['ejemplares'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php fila_busqueda($buscado, count($categorias), $hayMas, 3); ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php if ($editando): ?>
    <form method="post" action="index.php?c=categorias&a=eliminar" id="eliminar" data-confirmar="¿Eliminar la categoría <?= e($datos['nombre']) ?>?">
        <?php campo_busqueda($buscado); ?>
        <input type="hidden" name="id_categoria" value="<?= e($datos['id_categoria']) ?>">
    </form>
<?php endif; ?>
<?php form_busqueda('categorias'); ?>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
