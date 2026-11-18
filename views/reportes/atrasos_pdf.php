<?php
// PDF de préstamos atrasados con la multa estimada a hoy. Usa: $filtrosTexto, $usuario y $filas.
require_once __DIR__ . '/pdf.php';

$pdf = new ReportePDF('Préstamos atrasados', $filtrosTexto, $usuario['nombres'] . ' ' . $usuario['apellidos'], 'L');
$pdf->tabla(['Cédula' => 24, 'Estudiante' => 52, 'Teléfono' => 24, 'Libro' => 68, 'Ejemplar' => 22,
             'Prestado' => 22, 'Fecha límite' => 24, 'Días' => 15, 'Multa' => 26]);
$par = false;
foreach ($filas as $f) {
    $pdf->fila([$f['cedula'], $f['estudiante'], $f['telefono'], $f['titulo'], $f['codigo_inventario'],
                fecha($f['fecha_prestamo']), fecha($f['fecha_limite']), $f['dias_retraso'], dinero($f['monto'])], $par);
    $par = !$par;
}
if (!$filas) {
    $pdf->vacio('No hay préstamos atrasados.');
}
$pdf->totales(['Préstamos atrasados: ' . count($filas), 'Total estimado: ' . dinero(array_sum(array_column($filas, 'monto')))]);
$pdf->mostrar('Atrasos');
