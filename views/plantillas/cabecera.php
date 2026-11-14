<?php
// Encabezado verde común a las páginas internas (igual a componentes.Encabezado del sistema de escritorio).
// Usa: $titulo; $regresar (ruta del botón Regresar, por ejemplo "c=menu");
//      $menu = true en los menús (botón Cerrar sesión);
//      $persona = ['rol' => .., 'nombre' => .., 'cedula' => ..] para mostrar otra persona (panel del estudiante).
if (empty($persona) && $usuario) {
    $persona = [
        'rol' => !empty($menu) ? 'BIENVENIDO' : ($usuario['rol'] == 'ADMIN' ? 'ADMINISTRADOR' : 'BIBLIOTECARIO'),
        'nombre' => $usuario['nombres'] . ' ' . $usuario['apellidos'],
        'cedula' => $usuario['cedula'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> - Biblioteca FISEI</title>
    <link rel="stylesheet" href="views/assets/css/estilos.css">
</head>
<body>
<header class="encabezado">
    <div class="encabezado-izq">
        <?php if (!empty($menu)): ?>
            <a class="boton-regresar" href="index.php?c=login&a=salir" data-confirmar="¿Desea cerrar la sesión?">
                <img src="views/assets/img/Cerrar.png" alt=""><span>Cerrar sesión</span>
            </a>
        <?php elseif (!empty($regresar)): ?>
            <a class="boton-regresar" href="index.php?<?= e($regresar) ?>">
                <img src="views/assets/img/Regresar.png" alt=""><span>Regresar</span>
            </a>
        <?php endif; ?>
    </div>
    <div class="encabezado-centro">
        <h1>BIBLIOTECA DIGITAL FISEI</h1>
        <h2><?= e($titulo) ?></h2>
    </div>
    <div class="encabezado-der">
        <?php if (!empty($persona)): ?>
            <strong><?= e($persona['rol']) ?></strong>
            <span><?= e($persona['nombre']) ?></span>
            <span>C.I: <?= e($persona['cedula']) ?></span>
        <?php endif; ?>
        <span>Día: <?= date('d/m/Y') ?> &nbsp;&nbsp; Hora: <span id="reloj"><?= date('H:i:s') ?></span></span>
    </div>
</header>
<main class="contenido">
<?php mostrar_aviso($aviso); ?>
