<?php
// Vista del panel del estudiante (igual a vista.PanelEstudiante). Usa: $est, $sit y $historial.
$titulo = 'Mi panel';
$regresar = 'c=login&a=salir';
$persona = ['rol' => 'ESTUDIANTE', 'nombre' => $est['nombres'] . ' ' . $est['apellidos'], 'cedula' => $est['cedula']];
require __DIR__ . '/../plantillas/cabecera.php';
?>
<div class="fila">
    <section class="grupo lateral">
        <h3>Mis datos</h3>
        <div class="datos-estudiante">
            <h4><?= e($est['nombres'] . ' ' . $est['apellidos']) ?></h4>
            <p>C.I: <?= e($est['cedula']) ?> &nbsp;·&nbsp; Semestre: <?= $est['semestre'] ?></p>
            <p>Carrera: <?= e($est['carrera']) ?></p>
            <p>Correo: <?= e($est['correo']) ?></p>
        </div>
        <div class="tarjetas una" style="margin-top: 14px; gap: 10px">
            <div class="tarjeta"><span>Préstamos activos</span><strong><?= $sit['cantidad'] ?> de <?= $sit['maximo'] ?></strong></div>
            <div class="tarjeta alerta"><span>Atrasados</span><strong><?= $sit['atrasados'] ?></strong></div>
            <div class="tarjeta alerta"><span>Multas sin pagar</span><strong><?= dinero($sit['multas']) ?></strong></div>
        </div>
        <?php
        // Igual que el escritorio: activo y sin multas puede pedir libros
        $puede = $est['estado'] === 'ACTIVO' && $sit['multas'] <= 0;
        $estadoTexto = $puede ? 'PUEDE PEDIR LIBROS' : ($est['estado'] === 'ACTIVO' ? 'PAGUE SUS MULTAS' : $est['estado']);
        ?>
        <div class="franja <?= $puede ? 'ok' : 'mal' ?>"><?= e($estadoTexto) ?></div>
    </section>
    <section class="grupo">
        <h3>Mis préstamos</h3>
        <div class="tabla-caja alta">
            <table>
                <thead><tr><th>Código</th><th>Título</th><th>Prestado</th><th>Fecha límite</th><th>Devuelto</th><th>Estado</th><th>Multa</th></tr></thead>
                <tbody>
                <?php foreach ($historial as $p): ?>
                    <tr>
                        <td><?= e($p['codigo_inventario']) ?></td><td><?= e($p['titulo']) ?></td>
                        <td><?= fecha($p['fecha_prestamo']) ?></td><td><?= fecha($p['fecha_limite']) ?></td>
                        <td><?= fecha($p['fecha_devolucion']) ?></td>
                        <td><?= $p['estado'] === 'ACTIVO' ? (dias_retraso($p['fecha_limite']) > 0 ? 'Atrasado ' . dias_retraso($p['fecha_limite']) . ' día(s)' : 'A tiempo') : 'Devuelto' ?></td>
                        <td><?= $p['monto'] === null ? '—' : dinero($p['monto']) . ($p['pagada'] ? ' (pagada)' : ' (pendiente)') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
