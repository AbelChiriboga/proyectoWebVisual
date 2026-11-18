<?php
// Filtros del reporte de inventario (el PDF se abre en otra pestaña). Usa: $categorias y $estados.
$titulo = 'Reporte de inventario';
$regresar = 'c=menu';
require __DIR__ . '/../plantillas/cabecera.php';
?>
<form method="get" action="index.php" target="_blank" class="grupo" style="max-width: 640px; margin: 12px auto;">
    <h3>Filtros</h3>
    <input type="hidden" name="c" value="reportes">
    <input type="hidden" name="a" value="inventarioPdf">
    <div class="formulario dos">
        <div class="campo"><label>Categoría</label>
            <select name="categoria"><option value="">Todas</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id_categoria'] ?>"><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="campo"><label>Estado</label>
            <select name="estado"><option value="">Todos</option>
                <?php foreach ($estados as $valor => $nombre): ?>
                    <option value="<?= $valor ?>"><?= $nombre ?></option>
                <?php endforeach; ?>
            </select></div>
    </div>
    <div class="botones"><button class="boton primario">Generar PDF</button></div>
</form>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
