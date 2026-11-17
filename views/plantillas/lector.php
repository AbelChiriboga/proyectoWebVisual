<?php
// Respuesta al marco oculto (iframe) del lector de huella: le avisa a la página qué pasó.
// La página lo recibe en la función lector() de app.js. Usa: $respuesta.
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body>
<script>parent.lector(<?= json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>);</script>
</body>
</html>
