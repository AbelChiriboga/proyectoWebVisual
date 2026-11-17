<?php
// Vista de parámetros del sistema (igual a vista.Parametros). Usa: $editables y $valores.
$titulo = 'Parámetros del sistema';
$regresar = 'c=menu';
require __DIR__ . '/../plantillas/cabecera.php';
?>
<form method="post" action="index.php?c=parametros&a=guardar" class="enter grupo parametros"
      data-confirmar="¿Guardar los cambios en los parámetros?">
    <h3>Reglas del sistema</h3>
    <div class="formulario dos">
        <?php foreach ($editables as $clave => [$nombre, $tipo, $max, $ayuda]): ?>
            <div class="campo">
                <label><strong><?= e($nombre) ?></strong></label>
                <input type="text" name="<?= $clave ?>" value="<?= e($valores[$clave]) ?>" class="centrado"
                       data-tipo="<?= $tipo ?>" maxlength="<?= $max ?>" required>
                <span class="ayuda"><?= e($ayuda) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="franja-info">Los cambios se aplican a los préstamos nuevos. Los que ya existen conservan su fecha límite.</div>
    <div class="botones derecha linea-arriba">
        <a class="boton" href="index.php?c=parametros">Cancelar</a>
        <button class="boton primario">Guardar cambios</button>
    </div>
</form>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
