<?php
// Vista de consulta de estudiantes del bibliotecario (igual a vista.BEstudiantes).
// Usa: $filtros, $filtro, $id, $estudiantes, $hayMas, $buscado, $elegido y $sit.
$titulo = 'Consulta de estudiantes';
$regresar = 'c=menu';
require __DIR__ . '/../plantillas/cabecera.php';
$activo = $elegido && $elegido['estado'] === 'ACTIVO';
?>
<div class="fila">
    <section class="grupo">
        <h3>Estudiantes</h3>
        <form method="get" class="barra" id="busqueda">
            <input type="hidden" name="c" value="consulta">
            <?php barra_busqueda($buscado, 'Buscar por cédula o nombre'); ?>
            <label>Estado</label>
            <select name="filtro" onchange="this.form.submit()" style="width: 150px">
                <?php foreach ($filtros as $valor => $nombre): ?>
                    <option value="<?= $valor ?>" <?= $filtro === $valor ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="tabla-caja alta">
            <table id="tabla">
                <thead><tr><th>Cédula</th><th>Estudiante</th><th>Carrera</th><th>Semestre</th><th>Prestados</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($estudiantes as $s): ?>
                    <tr class="<?= $s['id_estudiante'] == $id ? 'elegida' : '' ?>"
                        data-href="index.php?c=consulta&filtro=<?= e($filtro) ?>&id=<?= $s['id_estudiante'] ?><?= con_busqueda($buscado) ?>">
                        <td><?= e($s['cedula']) ?></td><td><?= e($s['nombres'] . ' ' . $s['apellidos']) ?></td>
                        <td><?= e($s['carrera']) ?></td><td><?= $s['semestre'] ?></td><td><?= $s['prestados'] ?></td>
                        <td><?= ucfirst(strtolower($s['estado'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php fila_busqueda($buscado, count($estudiantes), $hayMas, 6); ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="grupo lateral media">
        <h3>Estudiante seleccionado</h3>
        <div class="datos-estudiante">
            <h4><?= $elegido ? e($elegido['nombres'] . ' ' . $elegido['apellidos']) : 'Seleccione un estudiante' ?></h4>
            <p>C.I: <?= $elegido ? e($elegido['cedula']) : '—' ?> &nbsp;·&nbsp; Semestre: <?= $elegido ? $elegido['semestre'] : '—' ?></p>
            <p>Carrera: <?= $elegido ? e($elegido['carrera']) : '—' ?></p>
            <p>Correo: <?= $elegido ? e($elegido['correo']) : '—' ?></p>
        </div>
        <div class="tarjetas dos" style="margin-top: 14px">
            <div class="tarjeta"><span>Prestados</span><strong><?= $elegido ? $sit['cantidad'] . ' de ' . $sit['maximo'] : '—' ?></strong></div>
            <div class="tarjeta alerta"><span>Atrasados</span><strong><?= $elegido ? $sit['atrasados'] : '—' ?></strong></div>
        </div>
        <div class="franja <?= $elegido ? ($activo ? 'ok' : 'mal') : '' ?>"><?= $elegido ? e($elegido['estado']) : 'SIN SELECCIÓN' ?></div>
        <div class="botones repartidos">
            <button class="boton" form="estado" <?= $elegido && !$activo ? '' : 'disabled' ?>>Activar</button>
            <button class="boton" form="estado" <?= $activo ? '' : 'disabled' ?>>Desactivar</button>
        </div>
        <p class="subtitulo">Libros prestados</p>
        <div class="tabla-caja" style="height: calc(100vh - 610px); min-height: 110px">
            <table>
                <thead><tr><th>Libro</th><th>Plazo</th><th>Prestó</th></tr></thead>
                <tbody>
                <?php foreach ($elegido ? $sit['activos'] : [] as $p): ?>
                    <tr><td><?= e($p['titulo']) ?></td><td><?= plazo($p['fecha_limite']) ?></td><td><?= e($p['presto'] ?? '—') ?></td></tr>
                <?php endforeach; ?>
                <?php if ($elegido && !$sit['activos']): ?>
                    <tr><td colspan="3" class="vacio">No tiene libros prestados</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php if ($elegido): ?>
    <form method="post" action="index.php?c=consulta&a=cambiarEstado" id="estado"
          data-confirmar="¿Desea <?= $activo ? 'desactivar' : 'activar' ?> a <?= e($elegido['nombres'] . ' ' . $elegido['apellidos']) ?>?">
        <input type="hidden" name="id" value="<?= $elegido['id_estudiante'] ?>">
        <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
        <?php campo_busqueda($buscado); ?>
        <input type="hidden" name="estado" value="<?= $activo ? 'INACTIVO' : 'ACTIVO' ?>">
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
