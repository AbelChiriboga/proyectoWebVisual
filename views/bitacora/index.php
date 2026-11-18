<?php
// Vista de la bitácora de autenticación (igual a vista.Bitacora).
// Usa: $desde, $hasta, $operacion, $resultado, $operaciones, $registros, $resumen y $umbral.
$titulo = 'Bitácora de autenticación';
$regresar = 'c=menu';
require __DIR__ . '/../plantillas/cabecera.php';
$r = $resumen;
$sinDato = function ($valor) { return $valor === null ? '—' : $valor; };
// Escala del gráfico: 0 .. $r['mayor'] (de 1 en 1, como el gráfico del escritorio)
$alto = 170;
$nombresOperacion = ['PRESTAMO' => 'Préstamos', 'DEVOLUCION' => 'Devoluciones', 'REGISTRO_HUELLA' => 'Registro de huella'];
?>
<form method="get" class="grupo">
    <h3>Filtros</h3>
    <input type="hidden" name="c" value="bitacora">
    <div class="formulario filtros-bitacora">
        <div class="campo"><label>Desde</label><input type="date" name="desde" value="<?= e($desde) ?>"></div>
        <div class="campo"><label>Hasta</label><input type="date" name="hasta" value="<?= e($hasta) ?>"></div>
        <div class="campo">
            <label>Operación</label>
            <select name="operacion">
                <option value="">Todas</option>
                <?php foreach ($operaciones as $valor => $nombre): ?>
                    <option value="<?= $valor ?>" <?= $operacion === $valor ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label>Resultado</label>
            <select name="resultado">
                <option value="">Todos</option>
                <option value="EXITO" <?= $resultado === 'EXITO' ? 'selected' : '' ?>>Éxito</option>
                <option value="FALLO" <?= $resultado === 'FALLO' ? 'selected' : '' ?>>Fallo</option>
            </select>
        </div>
        <div class="campo"><label>&nbsp;</label><button class="boton primario">Aplicar</button></div>
    </div>
</form>

<div class="tarjetas" style="margin-top: 16px">
    <div class="tarjeta"><span>Intentos hoy</span><strong><?= $r['intentosHoy'] ?></strong><small><?= $r['total'] ?> en el periodo</small></div>
    <div class="tarjeta"><span>Tasa de éxito</span><strong><?= $r['tasa'] ?>%</strong><small><?= $r['exitos'] ?> de <?= $r['total'] ?> en el periodo</small></div>
    <div class="tarjeta alerta"><span>Fallidos hoy</span><strong><?= $r['fallosHoy'] ?></strong><small><?= $r['fallos'] ?> en el periodo</small></div>
    <div class="tarjeta"><span>Puntaje promedio</span><strong><?= $sinDato($r['puntajeAceptados']) ?></strong>
        <small>Rechazados: <?= $sinDato($r['puntajeRechazados']) ?> &nbsp;·&nbsp; Umbral: <?= e($umbral) ?></small></div>
</div>

<div class="fila" style="margin-top: 22px">
    <section class="grupo">
        <h3>Intentos por día</h3>
        <div class="leyenda"><span><i style="background:#009999"></i>Éxitos</span><span><i style="background:#d4692b"></i>Fallos</span></div>
        <div class="grafico-caja">
            <div class="eje">
                <?php for ($v = $r['mayor']; $v >= 0; $v--): ?><span><?= $v ?></span><?php endfor; ?>
            </div>
            <div class="grafico">
                <?php foreach ($r['porDia'] as $dia => $c): ?>
                    <div class="dia">
                        <div class="barras">
                            <div class="barra-exito" style="height: <?= round($c['EXITO'] * $alto / $r['mayor']) ?>px" title="Éxitos: <?= $c['EXITO'] ?>"></div>
                            <div class="barra-fallo" style="height: <?= round($c['FALLO'] * $alto / $r['mayor']) ?>px" title="Fallos: <?= $c['FALLO'] ?>"></div>
                        </div>
                        <?= date('d/m', strtotime($dia)) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <section class="grupo lateral ancha">
        <h3>Por operación</h3>
        <?php foreach ($nombresOperacion as $op => $nombre):
            $o = $r['porOperacion'][$op];
            $tasa = $o['total'] ? round($o['exitos'] * 100 / $o['total']) : 0; ?>
            <div class="operacion">
                <div><span><?= $nombre ?></span><strong><?= $o['total'] ?> &nbsp;(<?= $tasa ?>% éxito)</strong></div>
                <div class="progreso"><div style="width: <?= $tasa ?>%"></div></div>
            </div>
        <?php endforeach; ?>
        <p class="ayuda">Puntaje promedio de los rechazados: <?= $sinDato($r['puntajeRechazados']) ?></p>
    </section>
</div>

<section class="grupo" style="margin-top: 22px">
    <h3>Detalle de intentos</h3>
    <div class="tabla-caja baja">
        <table>
            <thead><tr><th>Fecha y hora</th><th>Cédula</th><th>Estudiante</th><th>Operación</th><th>Resultado</th><th>Puntaje</th><th>Detalle</th></tr></thead>
            <tbody>
            <?php foreach ($registros as $reg): ?>
                <tr>
                    <td><?= fecha_hora($reg['fecha']) ?></td><td><?= e($reg['cedula'] ?? '—') ?></td>
                    <td><?= e($reg['estudiante'] ?? '—') ?></td><td><?= $operaciones[$reg['operacion']] ?? e($reg['operacion']) ?></td>
                    <td><?= $reg['resultado'] === 'EXITO' ? 'Éxito' : 'Fallo' ?></td>
                    <td><?= e($reg['puntaje'] ?? '—') ?></td><td><?= e($reg['detalle']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$registros): ?><tr><td colspan="7" class="vacio">No hay intentos en ese rango.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
