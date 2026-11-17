<?php
// Vista de gestión de estudiantes (igual a vista.CRUDEstudiantes).
// Usa: $datos, $editando, $registradas, $capturadas, $retiro, $pregunta, $dedos, $dedoElegido, $carreras,
//      $estudiantes, $lectura y $modoPedido.
$titulo = 'Gestión de estudiantes';
$regresar = 'c=menu';
$modo = modo_formulario($modoPedido, $editando);
require __DIR__ . '/../plantillas/cabecera.php';
?>
<form method="post" action="index.php?c=estudiantes&a=guardar" class="enter fila" id="formulario" data-crud="<?= $modo ?>">
    <input type="hidden" name="id_estudiante" value="<?= e($datos['id_estudiante']) ?>">
    <input type="hidden" name="modo" value="">
    <?php campo_busqueda($buscado); ?>
    <div class="columna">
        <section class="grupo">
            <h3>Datos del estudiante</h3>
            <div class="formulario">
                <div class="campo">
                    <label>Cédula</label>
                    <input type="text" name="cedula" value="<?= e($datos['cedula']) ?>" data-tipo="numeros" maxlength="10"
                           required data-fijo>
                </div>
                <div class="campo">
                    <label>Nombres</label>
                    <input type="text" name="nombres" value="<?= e($datos['nombres']) ?>" data-tipo="texto" maxlength="60" required>
                </div>
                <div class="campo">
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" value="<?= e($datos['apellidos']) ?>" data-tipo="texto" maxlength="60" required>
                </div>
                <div class="campo">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="<?= e($datos['telefono']) ?>" data-tipo="numeros" maxlength="10" required>
                </div>
                <div class="campo ancho2">
                    <label>Correo electrónico</label>
                    <input type="text" name="correo" value="<?= e($datos['correo']) ?>" data-tipo="correo" maxlength="100" required>
                </div>
                <div class="campo">
                    <label>Carrera</label>
                    <select name="id_carrera" required>
                        <option value=""></option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= $c['id_carrera'] ?>" <?= $c['id_carrera'] == $datos['id_carrera'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label>Semestre</label>
                    <select name="semestre" required>
                        <option value=""></option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option <?= $i == $datos['semestre'] ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="botones repartidos">
                <a class="boton" href="index.php?c=estudiantes&modo=nuevo<?= con_busqueda($buscado) ?>" data-modos="consulta seleccion">Nuevo</a>
                <button class="boton" data-modos="nuevo edicion">Guardar</button>
                <button type="button" class="boton" data-editar="formulario" data-modos="seleccion">Editar</button>
                <button class="boton" form="retiro" data-modos="seleccion" data-de><?= $retiro ?></button>
                <a class="boton" href="index.php?c=estudiantes<?= con_busqueda($buscado) ?>" data-modos="seleccion nuevo edicion">Cancelar</a>
            </div>
        </section>

        <section class="grupo">
            <h3>Estudiantes registrados</h3>
            <div class="barra">
                <?php barra_busqueda($buscado, 'Buscar por cédula o nombre'); ?>
            </div>
            <div class="tabla-caja baja">
                <table id="tabla">
                    <thead><tr><th>Cédula</th><th>Nombres</th><th>Apellidos</th><th>Carrera</th><th>Semestre</th><th>Huellas</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($estudiantes as $s): ?>
                        <tr class="<?= $s['id_estudiante'] == $datos['id_estudiante'] ? 'elegida' : '' ?>"
                            data-href="index.php?c=estudiantes&a=editar&id=<?= $s['id_estudiante'] ?><?= con_busqueda($buscado) ?>">
                            <td><?= e($s['cedula']) ?></td><td><?= e($s['nombres']) ?></td><td><?= e($s['apellidos']) ?></td>
                            <td><?= e($s['carrera']) ?></td><td><?= e($s['semestre']) ?></td><td><?= e($s['huellas']) ?></td>
                            <td><?= ucfirst(strtolower($s['estado'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php fila_busqueda($buscado, count($estudiantes), $hayMas, 7); ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="grupo lateral" id="registro-huella">
        <h3>Huella</h3>
        <div class="recuadro-huella <?= $registradas ? 'ok' : '' ?>" id="huella-recuadro">
            <span><?= $registradas ? count($registradas) . ' huella(s)<br>registrada(s)' : 'Sin huella' ?></span>
        </div>
        <div class="campo">
            <label>Dedo</label>
            <select name="dedo">
                <?php foreach ($dedos as $valor => $nombre): ?>
                    <option value="<?= $valor ?>" data-nombre="<?= e($nombre) ?>" <?= $dedoElegido === $valor ? 'selected' : '' ?>
                        <?= in_array($valor, $registradas) ? 'data-registrado' : '' ?> <?= in_array($valor, $capturadas) ? 'data-capturado' : '' ?>>
                        <?= e($nombre) ?><?= in_array($valor, $capturadas) ? ' (capturado)' : (in_array($valor, $registradas) ? ' (registrado)' : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <p class="ayuda" id="nota-dedo" hidden>Este dedo ya está registrado; si lo lee, se reemplaza al guardar.</p>
        <div class="muestras" id="huella-muestras">0 de 3 muestras</div>
        <p class="huella-texto" id="huella-texto">&nbsp;</p>
        <div class="botones repartidos" style="margin: 0 0 10px">
            <button type="button" class="boton" id="huella-otra" hidden>Registrar otra vez</button>
        </div>
        <div class="ficha">
            <p id="huella-info">
                <?php if ($registradas): ?>
                    Registradas: <?= e(implode(', ', array_map(function ($d) use ($dedos) { return $dedos[$d]; }, $registradas))) ?>.
                <?php else: ?>
                    Sin huellas registradas.
                <?php endif; ?>
            </p>
        </div>
        <!-- En Nuevo o Editar la huella se lee sola: este marco oculto le pide cada muestra al servidor (ver app.js) -->
        <iframe id="lector" hidden title="Lector de huella" data-escuchar="index.php?c=estudiantes&a=escuchar&t=<?= $lectura ?>"></iframe>
    </section>
</form>

<?php if ($editando): ?>
    <form method="post" action="index.php?c=estudiantes&a=retirar" id="retiro" data-confirmar="<?= e($pregunta) ?>">
        <?php campo_busqueda($buscado); ?>
        <input type="hidden" name="id_estudiante" value="<?= e($datos['id_estudiante']) ?>">
    </form>
<?php endif; ?>
<?php form_busqueda('estudiantes'); ?>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
