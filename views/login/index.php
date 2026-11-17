<?php
// Vista del inicio de sesión (igual a vista.Login). Usa: $pestana ('personal' o 'estudiante') y $aviso.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Biblioteca FISEI</title>
    <link rel="stylesheet" href="views/assets/css/estilos.css">
</head>
<body>
<div class="login">
    <div class="login-marca">
        <img src="views/assets/img/logo.png" alt="Logo">
        <h1>BIBLIOTECA DIGITAL<br>FISEI</h1>
        <p>Préstamos y devoluciones verificados con<br>tu huella dactilar. Nadie presta tu nombre.</p>
    </div>
    <div class="login-panel">
        <div class="login-form">
            <h2>Iniciar Sesión</h2>
            <?php mostrar_aviso($aviso); ?>
            <div class="pestanas">
                <a href="index.php?c=login&tab=personal" class="<?= $pestana === 'personal' ? 'activa' : '' ?>">Personal</a>
                <a href="index.php?c=login&tab=estudiante" class="<?= $pestana === 'estudiante' ? 'activa' : '' ?>">Estudiante</a>
            </div>
            <?php if ($pestana === 'personal'): ?>
                <form method="post" action="index.php?c=login&a=ingresar">
                    <div class="campo">
                        <label for="usuario">Usuario:</label>
                        <input type="text" id="usuario" name="usuario" data-tipo="usuario" maxlength="50" required autofocus>
                    </div>
                    <div class="campo">
                        <label for="clave">Contraseña:</label>
                        <input type="password" id="clave" name="clave" maxlength="50" required>
                    </div>
                    <button class="boton primario">Ingresar</button>
                </form>
            <?php else: ?>
                <form method="post" action="index.php?c=login&a=consultar">
                    <div class="campo">
                        <label for="cedula">Cédula:</label>
                        <input type="text" id="cedula" name="cedula" data-tipo="numeros" maxlength="10" required autofocus>
                    </div>
                    <button class="boton primario">Consultar</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="views/assets/js/app.js"></script>
</body>
</html>
