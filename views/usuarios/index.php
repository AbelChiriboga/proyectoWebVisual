<?php
// Vista de gestión de usuarios del personal (igual a vista.CRUDBibliotecarios). Usa: $datos, $editando, $usuarios y $modoPedido.
$titulo = 'Gestión de usuarios';
$regresar = 'c=menu';
$modo = modo_formulario($modoPedido, $editando);
require __DIR__ . '/../plantillas/cabecera.php';
?>
<div class="fila">
    <form method="post" action="index.php?c=usuarios&a=guardar" class="enter grupo lateral amplia" id="formulario" data-crud="<?= $modo ?>">
        <h3>Datos del usuario</h3>
        <input type="hidden" name="editando" value="<?= $editando ? '1' : '' ?>">
        <input type="hidden" name="modo" value="">
        <?php campo_busqueda($buscado); ?>
        <div class="formulario dos">
            <div class="campo">
                <label>Cédula</label>
                <input type="text" name="cedula" value="<?= e($datos['cedula']) ?>" data-tipo="numeros" maxlength="10" required data-fijo>
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
                <label>Correo electrónico</label>
                <input type="text" name="correo" value="<?= e($datos['correo']) ?>" data-tipo="correo" maxlength="100" required>
            </div>
            <div class="campo">
                <label>Dirección</label>
                <input type="text" name="direccion" value="<?= e($datos['direccion']) ?>" data-tipo="libre" maxlength="150">
            </div>
            <div class="campo">
                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?= e($datos['telefono']) ?>" data-tipo="numeros" maxlength="10" required>
            </div>
            <div class="separador">Datos de la cuenta</div>
            <div class="campo">
                <label>Usuario</label>
                <input type="text" name="usuario" value="<?= e($datos['usuario']) ?>" data-tipo="usuario" maxlength="50" required>
            </div>
            <div class="campo">
                <label>Rol</label>
                <select name="rol" required>
                    <option value=""></option>
                    <option value="ADMIN" <?= $datos['rol'] === 'ADMIN' ? 'selected' : '' ?>>Administrador</option>
                    <option value="BIBLIOTECARIO" <?= $datos['rol'] === 'BIBLIOTECARIO' ? 'selected' : '' ?>>Bibliotecario</option>
                </select>
            </div>
            <div class="campo">
                <label>Contraseña</label>
                <input type="password" name="clave" maxlength="50" data-fuerza="fuerza" <?= $editando ? '' : 'required' ?>>
            </div>
            <div class="campo">
                <label>Repetir contraseña</label>
                <input type="password" name="clave2" maxlength="50" <?= $editando ? '' : 'required' ?>>
            </div>
            <div class="campo ancho-todo">
                <span class="ayuda" id="fuerza-texto"><?= $editando ? 'Deje la contraseña vacía para no cambiarla.' : 'Seguridad de la contraseña' ?></span>
                <div class="progreso"><div id="fuerza" style="width: 0"></div></div>
            </div>
        </div>
        <div class="botones repartidos">
            <a class="boton" href="index.php?c=usuarios&modo=nuevo<?= con_busqueda($buscado) ?>" data-modos="consulta seleccion">Nuevo</a>
            <button class="boton" data-modos="nuevo edicion">Guardar</button>
            <button type="button" class="boton" data-editar="formulario" data-modos="seleccion">Editar</button>
            <button class="boton" form="estado" data-modos="seleccion"><?= $datos['activo'] ? 'Desactivar' : 'Activar' ?></button>
            <a class="boton" href="index.php?c=usuarios<?= con_busqueda($buscado) ?>" data-modos="seleccion nuevo edicion">Cancelar</a>
        </div>
    </form>

    <section class="grupo">
        <h3>Usuarios registrados</h3>
        <div class="barra">
            <?php barra_busqueda($buscado, 'Buscar por cédula, nombre o usuario'); ?>
        </div>
        <div class="tabla-caja alta">
            <table id="tabla">
                <thead><tr><th>Cédula</th><th>Nombres</th><th>Apellidos</th><th>Usuario</th><th>Rol</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr class="<?= $u['cedula'] === $datos['cedula'] ? 'elegida' : '' ?>"
                        data-href="index.php?c=usuarios&a=editar&id=<?= e($u['cedula']) ?><?= con_busqueda($buscado) ?>">
                        <td><?= e($u['cedula']) ?></td><td><?= e($u['nombres']) ?></td><td><?= e($u['apellidos']) ?></td>
                        <td><?= e($u['usuario']) ?></td><td><?= $u['rol'] === 'ADMIN' ? 'Administrador' : 'Bibliotecario' ?></td>
                        <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php fila_busqueda($buscado, count($usuarios), $hayMas, 6); ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php if ($editando): ?>
    <form method="post" action="index.php?c=usuarios&a=cambiarEstado" id="estado"
          data-confirmar="¿Desea <?= $datos['activo'] ? 'desactivar' : 'activar' ?> al usuario <?= e($datos['nombres']) ?>?">
        <?php campo_busqueda($buscado); ?>
        <input type="hidden" name="cedula" value="<?= e($datos['cedula']) ?>">
    </form>
<?php endif; ?>
<?php form_busqueda('usuarios'); ?>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
