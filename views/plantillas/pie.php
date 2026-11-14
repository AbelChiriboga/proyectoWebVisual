</main>
<footer class="pie">
    <span>Sistema de Préstamos biométrico · FISEI</span>
    <?php $estadoLector = $_SESSION['lector_estado'] ?? ''; ?>
    <span id="lector-pie" class="<?= $estadoLector === 'CONECTADO' ? 'conectado' : '' ?>"><span class="punto">&#9679;</span> Lector:
        <strong><?= ['CONECTADO' => 'Conectado', 'DESCONECTADO' => 'Desconectado', 'SIN_DRIVER' => 'Sin driver instalado'][$estadoLector] ?? 'Revisando...' ?></strong></span>
</footer>
<?php if (!empty($_SESSION['usuario'])): ?>
    <!-- Marco oculto que revisa si el lector está conectado (ver "Estado del lector" en app.js) -->
    <iframe id="monitor-lector" hidden title="Estado del lector"></iframe>
<?php endif; ?>
<script src="views/assets/js/app.js"></script>
</body>
</html>
