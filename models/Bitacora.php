<?php
// Bitácora de autenticación biométrica: cada intento con la huella queda registrado
class Bitacora extends Modelo
{
    // Operaciones que usan la huella (el inicio de sesión es con usuario y contraseña, no va aquí)
    const OPERACIONES = [
        'PRESTAMO' => 'Préstamo',
        'DEVOLUCION' => 'Devolución',
        'REGISTRO_HUELLA' => 'Registro de huella',
    ];

    public function anotar($idEstudiante, $operacion, $resultado, $puntaje, $detalle)
    {
        $this->ejecutar('INSERT INTO registro_autenticacion (id_estudiante, operacion, resultado, puntaje, detalle)
                         VALUES (?, ?, ?, ?, ?)', [$idEstudiante, $operacion, $resultado, $puntaje, $detalle]);
    }

    public function listar($desde, $hasta, $operacion, $resultado)
    {
        $sql = "SELECT r.*, e.cedula, CONCAT(e.nombres, ' ', e.apellidos) AS estudiante
                FROM registro_autenticacion r LEFT JOIN estudiantes e ON e.id_estudiante = r.id_estudiante
                WHERE DATE(r.fecha) BETWEEN ? AND ?";
        $parametros = [$desde, $hasta];
        if ($operacion !== '') {
            $sql .= ' AND r.operacion = ?';
            $parametros[] = $operacion;
        }
        if ($resultado !== '') {
            $sql .= ' AND r.resultado = ?';
            $parametros[] = $resultado;
        }
        return $this->consultar($sql . ' ORDER BY r.fecha DESC', $parametros);
    }

    /**
     * Cálculos del panel (igual a ResumenBitacora del sistema de escritorio): totales, tasa de éxito,
     * intentos y fallos de hoy, puntaje promedio, resumen por operación y éxitos/fallos por día (máximo 14 días).
     */
    public function resumen($registros, $desde, $hasta)
    {
        $hoy = date('Y-m-d');
        $inicio = max($desde, date('Y-m-d', strtotime($hasta . ' -13 days')));
        $porDia = [];
        for ($d = $inicio; $d <= $hasta; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            $porDia[$d] = ['EXITO' => 0, 'FALLO' => 0];
        }
        $exitos = $intentosHoy = $fallosHoy = 0;
        $puntajes = ['EXITO' => [], 'FALLO' => []];
        $porOperacion = [];
        foreach (array_keys(self::OPERACIONES) as $op) {
            $porOperacion[$op] = ['total' => 0, 'exitos' => 0];
        }
        foreach ($registros as $r) {
            $dia = date('Y-m-d', strtotime($r['fecha']));
            $exito = $r['resultado'] === 'EXITO';
            $exitos += $exito ? 1 : 0;
            if ($dia === $hoy) {
                $intentosHoy++;
                $fallosHoy += $exito ? 0 : 1;
            }
            if (isset($porDia[$dia])) {
                $porDia[$dia][$r['resultado']]++;
            }
            if ($r['puntaje'] !== null) {
                $puntajes[$r['resultado']][] = (int) $r['puntaje'];
            }
            $porOperacion[$r['operacion']]['total']++;
            $porOperacion[$r['operacion']]['exitos'] += $exito ? 1 : 0;
        }
        $mayor = 1;
        foreach ($porDia as $c) {
            $mayor = max($mayor, $c['EXITO'], $c['FALLO']);
        }
        $total = count($registros);
        return [
            'total' => $total,
            'exitos' => $exitos,
            'fallos' => $total - $exitos,
            'tasa' => $total ? round($exitos * 100 / $total) : 0,
            'intentosHoy' => $intentosHoy,
            'fallosHoy' => $fallosHoy,
            'puntajeAceptados' => $puntajes['EXITO'] ? round(array_sum($puntajes['EXITO']) / count($puntajes['EXITO'])) : null,
            'puntajeRechazados' => $puntajes['FALLO'] ? round(array_sum($puntajes['FALLO']) / count($puntajes['FALLO'])) : null,
            'porOperacion' => $porOperacion,
            'porDia' => $porDia,
            'mayor' => max(5, $mayor),
        ];
    }
}
