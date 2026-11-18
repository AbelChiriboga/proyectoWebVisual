<?php
// Vista de préstamos atrasados y multas pendientes (igual a vista.Atrasos).
// En "Multas pendientes" se eligen una o varias filas con un clic y se registra el pago.
// No se cargan todos: solo los que coinciden con la búsqueda. Usa: $ver, $filas, $hayMas, $buscado, $cantidad y $total.
$titulo = 'Préstamos atrasados y multas';
$regresar = 'c=menu';
require __DIR__ . '/../plantillas/cabecera.php';
$multas = $ver === 'multas';
?>
<section class="grupo">
    <h3>Préstamos atrasados y multas</h3>
    <form method="get" class="barra" id="busqueda">
        <input type="hidden" name="c" value="atrasos">
        <label>Ver</label>
        <select name="ver" onchange="this.form.submit()" style="width: 180px">
            <option value="atrasados" <?= !$multas ? 'selected' : '' ?>>Préstamos atrasados</option>
            <option value="multas" <?= $multas ? 'selected' : '' ?>>Multas pendientes</option>
        </select>
        <?php barra_busqueda($buscado, 'Buscar por cédula, estudiante o libro'); ?>
        <a class="boton" href="index.php?c=atrasos&ver=<?= $ver ?><?= con_busqueda($buscado) ?>">Actualizar</a>
        <button class="boton primario" form="pago" id="boton-pago" disabled>Registrar pago</button>
        <a class="boton" target="_blank" href="index.php?c=reportes&a=<?= $multas ? 'multas' : 'atrasos' ?>">Generar reporte</a>
    </form>
    <div class="tabla-caja alta con-nota">
        <table id="tabla">
            <thead><tr><th>Cédula</th><th>Estudiante</th><th>Correo</th><th>Teléfono</th><th>Libro</th><th>Ejemplar</th>
                <th>Fecha límite</th><th>Días</th><th>Multa</th></tr></thead>
            <tbody>
            <?php foreach ($filas as $f): ?>
                <tr class="<?= $multas ? 'seleccionable' : '' ?>">
                    <td><?php if ($multas): ?><input type="checkbox" name="multas[]" value="<?= $f['id_multa'] ?>" form="pago" hidden
                               data-monto="<?= $f['monto'] ?>" data-estudiante="<?= $f['id_estudiante'] ?>"><?php endif; ?><?= e($f['cedula']) ?></td>
                    <td><?= e($f['estudiante']) ?></td><td><?= e($f['correo']) ?></td>
                    <td><?= e($f['telefono']) ?></td><td><?= e($f['titulo']) ?></td><td><?= e($f['codigo_inventario']) ?></td>
                    <td><?= fecha($f['fecha_limite']) ?></td><td><?= $f['dias_retraso'] ?></td><td><?= dinero($f['monto']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php fila_busqueda($buscado, count($filas), $hayMas, 9); ?>
            </tbody>
        </table>
    </div>
    <p class="resumen">Registros: <?= $cantidad ?> &nbsp;·&nbsp; Total: <?= dinero($total) ?>
        <?php if ($multas): ?>
            <span class="ayuda" style="margin-left: 24px">Haga clic en una o varias multas del mismo estudiante:
                <span id="pago-cantidad">0</span> seleccionada(s), total <span id="pago-total">$0.00</span></span>
        <?php endif; ?></p>
    <?php if ($multas): ?>
        <form method="post" action="index.php?c=atrasos&a=pagar" id="pago"><?php campo_busqueda($buscado); ?></form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
