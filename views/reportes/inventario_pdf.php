<?php
// PDF del inventario de ejemplares agrupado por categoría. Usa: $filtrosTexto, $usuario, $filas, $estados y $conteo.
require_once __DIR__ . '/pdf.php';

$pdf = new ReportePDF('Inventario de ejemplares', $filtrosTexto, $usuario['nombres'] . ' ' . $usuario['apellidos']);
$pdf->tabla(['Código' => 28, 'Título' => 70, 'Autor' => 45, 'Ubicación' => 25, 'Estado' => 22]);
$grupo = null;
$par = false;
foreach ($filas as $f) {
    if ($f['categoria'] !== $grupo) {
        $grupo = $f['categoria'];
        $pdf->grupo('Categoría: ' . $grupo);
        $par = false;
    }
    $pdf->fila([$f['codigo_inventario'], $f['titulo'], $f['autor'], $f['ubicacion'], $estados[$f['estado']]], $par);
    $par = !$par;
}
if (!$filas) {
    $pdf->vacio('No hay ejemplares con esos filtros.');
}
$totales = ['Total de ejemplares: ' . count($filas)];
foreach ($estados as $valor => $nombre) {
    $totales[] = $nombre . 's: ' . ($conteo[$valor] ?? 0);
}
$pdf->totales($totales);
$pdf->mostrar('Inventario');
