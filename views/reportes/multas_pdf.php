<?php
// PDF de multas pendientes de pago. Usa: $filtrosTexto, $usuario y $filas.
require_once __DIR__ . '/pdf.php';

$pdf = new ReportePDF('Multas pendientes', $filtrosTexto, $usuario['nombres'] . ' ' . $usuario['apellidos'], 'L');
$pdf->tabla(['Generada' => 24, 'Cédula' => 24, 'Estudiante' => 55, 'Teléfono' => 24, 'Libro' => 75,
             'Ejemplar' => 24, 'Días' => 18, 'Monto' => 33]);
$par = false;
foreach ($filas as $f) {
    $pdf->fila([fecha($f['fecha_generacion']), $f['cedula'], $f['estudiante'], $f['telefono'], $f['titulo'],
                $f['codigo_inventario'], $f['dias_retraso'], dinero($f['monto'])], $par);
    $par = !$par;
}
if (!$filas) {
    $pdf->vacio('No hay multas pendientes.');
}
$pdf->totales(['Multas pendientes: ' . count($filas), 'Total: ' . dinero(array_sum(array_column($filas, 'monto')))]);
$pdf->mostrar('Multas');
