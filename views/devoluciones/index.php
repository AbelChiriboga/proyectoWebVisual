<?php
// Vista de devolución de libros.
// Usa: $modulo, $estudiante, $sit, $estadoTexto, $estadoOk, $prestamos y $multaDia.
$titulo = 'Devolución de libros';
$regresar = 'c=menu';
require __DIR__ . '/../plantillas/cabecera.php';
?>
<form method="post" action="index.php?c=devoluciones&a=confirmar" class="fila">
    <?php require __DIR__ . '/../plantillas/panel_huella.php'; ?>
    <section class="grupo">
        <h3>Préstamos activos del estudiante</h3>
        <div class="tabla-caja media" id="lista-devolucion">
            <table>
                <thead><tr><th></th><th>Código</th><th>Título</th><th>Prestado</th><th>Fecha límite</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($prestamos as $p): $dias = dias_retraso($p['fecha_limite']); ?>
                    <tr>
                        <td><input type="checkbox" name="prestamos[]" value="<?= $p['id_prestamo'] ?>"
                                   data-dias="<?= $dias ?>" data-monto="<?= $dias * $multaDia ?>"></td>
                        <td><?= e($p['codigo_inventario']) ?></td><td><?= e($p['titulo']) ?></td>
                        <td><?= fecha($p['fecha_prestamo']) ?></td><td><?= fecha($p['fecha_limite']) ?></td>
                        <td><?= $dias > 0 ? "Atrasado $dias día(s)" : 'A tiempo' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="tarjetas tres" style="margin-top: 14px">
            <div class="tarjeta"><span>Días de retraso</span><strong id="total-dias">0</strong></div>
            <div class="tarjeta"><span>Multa por día</span><strong><?= dinero($multaDia) ?></strong></div>
            <div class="tarjeta alerta"><span>Multa a generar</span><strong id="total-pagar">$0.00</strong></div>
        </div>
        <div class="botones derecha">
            <button class="boton" formaction="index.php?c=devoluciones&a=cancelar" formnovalidate>Cancelar</button>
            <button class="boton primario">Confirmar devolución</button>
        </div>
    </section>
</form>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
