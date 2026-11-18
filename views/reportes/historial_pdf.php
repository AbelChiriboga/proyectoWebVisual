<?php
// PDF del historial de préstamos. Usa: $filtrosTexto, $usuario, $filas y $activos.
require_once __DIR__ . '/pdf.php';

$pdf = new ReportePDF('Historial de préstamos', $filtrosTexto, $usuario['nombres'] . ' ' . $usuario['apellidos'], 'L');
$pdf->tabla(['Prestado' => 22, 'Cédula' => 24, 'Estudiante' => 50, 'Libro' => 65, 'Ejemplar' => 22,
             'Fecha límite' => 24, 'Devuelto' => 22, 'Estado' => 22, 'Multa' => 26]);
$par = false;
foreach ($filas as $f) {
    $pdf->fila([fecha($f['fecha_prestamo']), $f['cedula'], $f['estudiante'], $f['titulo'], $f['codigo_inventario'],
                fecha($f['fecha_limite']), fecha($f['fecha_devolucion']),
                $f['estado'] === 'ACTIVO' ? 'Activo' : 'Devuelto', dinero($f['multa'])], $par);
    $par = !$par;
}
if (!$filas) {
    $pdf->vacio('No hay préstamos con esos filtros.');
}
$pdf->totales(['Préstamos: ' . count($filas), 'Activos: ' . $activos, 'Devueltos: ' . (count($filas) - $activos),
               'Total en multas: ' . dinero(array_sum(array_column($filas, 'multa')))]);
$pdf->mostrar('Historial');
