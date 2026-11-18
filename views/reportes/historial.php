<?php
// Filtros del reporte de historial (el PDF se abre en otra pestaña). Usa: $carreras.
$titulo = 'Reporte de historial';
$regresar = 'c=menu';
require __DIR__ . '/../plantillas/cabecera.php';
?>
<form method="get" action="index.php" target="_blank" class="grupo" style="max-width: 760px; margin: 12px auto;"
      data-rango-fechas>
    <h3>Filtros</h3>
    <input type="hidden" name="c" value="reportes">
    <input type="hidden" name="a" value="historialPdf">
    <div class="formulario">
        <div class="campo"><label>Desde</label><input type="date" name="desde" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" required></div>
        <div class="campo"><label>Hasta</label><input type="date" name="hasta" value="<?= date('Y-m-d') ?>" required></div>
        <div class="campo"><label>Carrera</label>
            <select name="carrera"><option value="">Todas</option>
                <?php foreach ($carreras as $c): ?>
                    <option value="<?= $c['id_carrera'] ?>"><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="campo"><label>Estado</label>
            <select name="estado"><option value="">Todos</option>
                <option value="ACTIVO">Activo</option>
                <option value="DEVUELTO">Devuelto</option>
            </select></div>
    </div>
    <div class="botones"><button class="boton primario">Generar PDF</button></div>
</form>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
