<?php
// Préstamos atrasados y multas
class Multa extends Modelo
{
    const CONSULTA = "SELECT p.id_prestamo, p.fecha_prestamo, p.fecha_limite, p.fecha_devolucion, e.cedula,
                             CONCAT(e.nombres, ' ', e.apellidos) AS estudiante, e.correo, e.telefono,
                             l.titulo, ej.codigo_inventario, m.id_multa, m.dias_retraso, m.monto, m.fecha_generacion, p.id_estudiante
                      FROM prestamos p
                      JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                      JOIN ejemplares ej ON ej.id_ejemplar = p.id_ejemplar
                      JOIN libros l ON l.id_libro = ej.id_libro
                      LEFT JOIN multas m ON m.id_prestamo = p.id_prestamo ";

    const COLUMNAS_BUSQUEDA = ['e.cedula', "CONCAT(e.nombres, ' ', e.apellidos)", 'l.titulo', 'ej.codigo_inventario'];
    const ATRASADOS = "WHERE p.estado = 'ACTIVO' AND DATE(p.fecha_limite) < CURDATE()";
    const PENDIENTES = 'WHERE m.pagada = 0';

    /**
     * Préstamos activos que pasaron la fecha límite, con la multa estimada a hoy.
     * Con $texto (pantalla Atrasados) busca por cédula, estudiante o libro y trae solo los que coinciden;
     * sin $texto (reportes) trae todos.
     */
    public function atrasados($texto = null)
    {
        $multaDia = (float) (new Parametro())->obtener('MULTA_POR_DIA');
        list($donde, $parametros, $limite) = $this->busqueda($texto);
        $filas = $this->consultar(self::CONSULTA . self::ATRASADOS . $donde . ' ORDER BY p.fecha_limite' . $limite, $parametros);
        foreach ($filas as $i => $f) {
            $dias = (int) floor((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($f['fecha_limite'])))) / 86400);
            $filas[$i]['dias_retraso'] = $dias;
            $filas[$i]['monto'] = $dias * $multaDia;
        }
        return $filas;
    }

    /** Multas generadas que todavía no se pagan. */
    public function pendientes($texto = null)
    {
        list($donde, $parametros, $limite) = $this->busqueda($texto);
        return $this->consultar(self::CONSULTA . self::PENDIENTES . $donde . ' ORDER BY m.fecha_generacion' . $limite, $parametros);
    }

    /** Cuántos préstamos atrasados hay en total y cuánto suman sus multas a hoy (sin cargarlos todos). */
    public function resumenAtrasados()
    {
        $r = $this->fila("SELECT COUNT(*) AS cantidad, COALESCE(SUM(DATEDIFF(CURDATE(), DATE(p.fecha_limite))), 0) AS dias
                          FROM prestamos p " . self::ATRASADOS);
        return ['cantidad' => (int) $r['cantidad'],
                'total' => $r['dias'] * (float) (new Parametro())->obtener('MULTA_POR_DIA')];
    }

    /** Cuántas multas pendientes hay en total y cuánto suman (sin cargarlas todas). */
    public function resumenPendientes()
    {
        $r = $this->fila('SELECT COUNT(*) AS cantidad, COALESCE(SUM(m.monto), 0) AS total FROM multas m ' . self::PENDIENTES);
        return ['cantidad' => (int) $r['cantidad'], 'total' => (float) $r['total']];
    }

    /** Condición y límite de la búsqueda; sin texto (null) no hay búsqueda ni límite. */
    private function busqueda($texto)
    {
        if ($texto === null) {
            return ['', [], ''];
        }
        list($donde, $parametros) = $this->condicionBusqueda(self::COLUMNAS_BUSQUEDA, $texto);
        return [$donde, $parametros, $this->limiteBusqueda()];
    }

    /**
     * Paga una o varias multas del MISMO estudiante: se pagan todas o ninguna (transacción).
     * Devuelve la cantidad, el total y el nombre del estudiante.
     */
    public function pagar($idsMulta)
    {
        $ids = array_values(array_unique(array_map('intval', (array) $idsMulta)));
        if (!$ids) {
            throw new Exception('Seleccione al menos una multa.');
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $multas = $this->consultar("SELECT m.id_multa, m.monto, m.pagada, p.id_estudiante,
                                           CONCAT(e.nombres, ' ', e.apellidos) AS estudiante
                                    FROM multas m JOIN prestamos p ON p.id_prestamo = m.id_prestamo
                                    JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                                    WHERE m.id_multa IN ($marcas)", $ids);
        if (count($multas) != count($ids)) {
            throw new Exception('Una de las multas ya no existe.');
        }
        if (count(array_unique(array_column($multas, 'id_estudiante'))) > 1) {
            throw new Exception('Seleccione multas de un solo estudiante.');
        }
        foreach ($multas as $m) {
            if ($m['pagada']) {
                throw new Exception('Una de las multas ya está pagada.');
            }
        }
        $this->cn->begin_transaction();
        try {
            foreach ($ids as $id) {
                $this->ejecutar('UPDATE multas SET pagada = 1, fecha_pago = NOW() WHERE id_multa = ? AND pagada = 0', [$id]);
            }
            $this->cn->commit();
        } catch (Exception $ex) {
            $this->cn->rollback();
            throw $ex;
        }
        return [
            'cantidad' => count($ids),
            'total' => array_sum(array_column($multas, 'monto')),
            'estudiante' => $multas[0]['estudiante'],
        ];
    }
}
