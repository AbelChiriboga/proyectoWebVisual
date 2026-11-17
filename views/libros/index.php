<?php
// Vista del catálogo de libros (igual a vista.CRUDLibros).
// Usa: $datos, $editando, $pendientes, $autores, $categorias, $libros y $modoPedido.
$titulo = 'Gestión de libros';
$regresar = 'c=menu';
$modo = modo_formulario($modoPedido, $editando);
require __DIR__ . '/../plantillas/cabecera.php';
?>
<div class="fila">
    <form method="post" action="index.php?c=libros&a=guardar" class="enter grupo lateral media" id="formulario" data-crud="<?= $modo ?>">
        <h3>Datos del libro</h3>
        <input type="hidden" name="id_libro" value="<?= e($datos['id_libro']) ?>">
        <input type="hidden" name="modo" value="">
        <?php campo_busqueda($buscado); ?>
        <div class="formulario dos">
            <div class="campo ancho2"><label>ISBN</label>
                <input type="text" name="isbn" value="<?= e($datos['isbn']) ?>" data-tipo="isbn" maxlength="17" required data-fijo></div>
            <div class="campo ancho2"><label>Título</label>
                <input type="text" name="titulo" value="<?= e($datos['titulo']) ?>" data-tipo="libre" maxlength="150" required></div>
            <div class="campo ancho2"><label>Autor</label>
                <div class="campo-con-boton">
                    <select name="id_autor" required>
                        <option value=""></option>
                        <?php foreach ($autores as $a): ?>
                            <option value="<?= $a['id_autor'] ?>" <?= $a['id_autor'] == $datos['id_autor'] ? 'selected' : '' ?>><?= e($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="boton" formaction="index.php?c=libros&a=agregar&ir=autores" formnovalidate title="Agregar autor" data-modos="nuevo edicion">+</button>
                </div></div>
            <div class="campo ancho2"><label>Categoría</label>
                <div class="campo-con-boton">
                    <select name="id_categoria" required>
                        <option value=""></option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id_categoria'] ?>" <?= $c['id_categoria'] == $datos['id_categoria'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="boton" formaction="index.php?c=libros&a=agregar&ir=categorias" formnovalidate title="Agregar categoría" data-modos="nuevo edicion">+</button>
                </div></div>
            <div class="campo"><label>Editorial</label>
                <input type="text" name="editorial" value="<?= e($datos['editorial']) ?>" data-tipo="libre" maxlength="100"></div>
            <div class="campo"><label>Año</label>
                <input type="text" name="anio_publicacion" value="<?= e($datos['anio_publicacion']) ?>" data-tipo="numeros" maxlength="4"></div>
        </div>
        <div class="botones rejilla">
            <a class="boton" href="index.php?c=libros&modo=nuevo<?= con_busqueda($buscado) ?>" data-modos="consulta seleccion">Nuevo</a>
            <button class="boton" data-modos="nuevo edicion">Guardar</button>
            <button type="button" class="boton" data-editar="formulario" data-modos="seleccion">Editar</button>
            <button class="boton" form="eliminar" data-modos="seleccion">Eliminar</button>
            <a class="boton" href="index.php?c=libros<?= con_busqueda($buscado) ?>" data-modos="nuevo edicion">Cancelar</a>
            <!-- Igual que en Java: Ejemplares solo en Nuevo o Editar. En un libro nuevo quedan pendientes hasta Guardar. -->
            <button class="boton" formaction="index.php?c=libros&a=ejemplares" data-modos="nuevo edicion"
                    title="<?= $modo === 'nuevo' ? 'Ejemplares por guardar: ' . $pendientes : 'Ejemplares del libro' ?>">
                <img src="views/assets/img/libros.png" alt="">Ejemplares</button>
        </div>
    </form>

    <section class="grupo">
        <h3>Libros registrados</h3>
        <div class="barra">
            <?php barra_busqueda($buscado, 'Buscar por título, autor o ISBN'); ?>
        </div>
        <div class="tabla-caja alta con-nota">
            <table id="tabla">
                <thead><tr><th>ISBN</th><th>Título</th><th>Autor</th><th>Categoría</th><th>Año</th><th>Disponibles</th></tr></thead>
                <tbody>
                <?php foreach ($libros as $l): ?>
                    <tr class="<?= $l['id_libro'] == $datos['id_libro'] ? 'elegida' : '' ?>"
                        data-href="index.php?c=libros&a=editar&id=<?= $l['id_libro'] ?><?= con_busqueda($buscado) ?>">
                        <td><?= e($l['isbn']) ?></td><td><?= e($l['titulo']) ?></td><td><?= e($l['autor']) ?></td>
                        <td><?= e($l['categoria']) ?></td><td><?= e($l['anio_publicacion']) ?></td>
                        <td><?= (int) $l['disponibles'] ?> / <?= $l['total_ejemplares'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php fila_busqueda($buscado, count($libros), $hayMas, 6); ?>
                </tbody>
            </table>
        </div>
        <p class="resumen">Seleccione un libro y presione Ejemplares para gestionar sus copias físicas.</p>
    </section>
</div>

<?php if ($editando): ?>
    <form method="post" action="index.php?c=libros&a=eliminar" id="eliminar" data-confirmar="¿Eliminar el libro <?= e($datos['titulo']) ?>?">
        <?php campo_busqueda($buscado); ?>
        <input type="hidden" name="id_libro" value="<?= e($datos['id_libro']) ?>">
    </form>
<?php endif; ?>
<?php form_busqueda('libros'); ?>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
