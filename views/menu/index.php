<?php
// Vista del menú principal (igual a MenuAdmin / MenuBibliotecario). Usa: $titulo, $grupos y $tarjetas.
$menu = true;
require __DIR__ . '/../plantillas/cabecera.php';
?>
<div class="menu-grupos">
    <?php foreach ($grupos as $grupo => [$ancho, $opciones]): ?>
        <section class="grupo <?= $ancho ? 'ancho' : '' ?>">
            <h3><?= e($grupo) ?></h3>
            <div class="menu-botones">
                <?php foreach ($opciones as $nombre => [$ruta, $icono]): ?>
                    <a class="boton-menu" href="index.php?<?= $ruta ?>">
                        <img src="views/assets/img/<?= $icono ?>" alt=""><?= e($nombre) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
<div class="tarjetas menu-tarjetas">
    <?php foreach ($tarjetas as $nombre => [$numero, $alerta]): ?>
        <div class="tarjeta centrada <?= $alerta ? 'alerta' : '' ?>">
            <span><?= e($nombre) ?></span>
            <strong><?= e($numero) ?></strong>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../plantillas/pie.php'; ?>
