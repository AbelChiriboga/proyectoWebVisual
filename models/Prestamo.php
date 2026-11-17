<?php
// Préstamos y devoluciones. Los triggers de la base exigen la huella, cambian el estado
// del ejemplar (prestado / disponible) y generan la multa al devolver con atraso.
class Prestamo extends Modelo
{
    /** Situación de un estudiante: préstamos activos, atrasados, cupos libres y multas pendientes. */
    public function situacion($idEstudiante)
    {
        $maximo = (int) (new Parametro())->obtener('MAX_PRESTAMOS');
        $activos = $this->consultar("SELECT p.*, l.titulo, ej.codigo_inventario, CONCAT(u.nombres, ' ', u.apellidos) AS presto
                                     FROM prestamos p
                                     JOIN ejemplares ej ON ej.id_ejemplar = p.id_ejemplar
                                     JOIN libros l ON l.id_libro = ej.id_libro
                                     LEFT JOIN usuarios u ON u.cedula = p.cedula_usuario
                                     WHERE p.id_estudiante = ? AND p.estado = 'ACTIVO' ORDER BY p.fecha_limite",
                                     [$idEstudiante]);
        $atrasados = 0;
        foreach ($activos as $p) {
            if (date('Y-m-d', strtotime($p['fecha_limite'])) < date('Y-m-d')) {
                $atrasados++;
            }
        }
        $multas = (float) $this->valor('SELECT COALESCE(SUM(m.monto), 0) FROM multas m
                                        JOIN prestamos p ON p.id_prestamo = m.id_prestamo
                                        WHERE p.id_estudiante = ? AND m.pagada = 0', [$idEstudiante]);
        return [
            'activos' => $activos,
            'cantidad' => count($activos),
            'atrasados' => $atrasados,
            'maximo' => $maximo,
            'cupos' => max(0, $maximo - count($activos)),
            'multas' => $multas,
        ];
    }

    /** Reglas para prestar: estudiante activo, sin libros atrasados, sin multas y sin pasar el máximo. Devuelve el motivo o null. */
    public function motivoBloqueo($estudiante, $sit, $cantidad)
    {
        if ($estudiante['estado'] !== 'ACTIVO') {
            return 'El estudiante está ' . strtolower($estudiante['estado']) . '.';
        }
        if ($sit['atrasados'] > 0) {
            return 'El estudiante tiene ' . $sit['atrasados'] . ' libro(s) atrasado(s). Debe devolverlos antes de un nuevo préstamo.';
        }
        if ($sit['multas'] > 0) {
            return 'El estudiante tiene multas sin pagar ($' . number_format($sit['multas'], 2) . ').';
        }
        if ($sit['cupos'] == 0) {
            return 'El estudiante ya tiene ' . $sit['cantidad'] . ' libros prestados (máximo ' . $sit['maximo'] . ').';
        }
        if ($cantidad > $sit['cupos']) {
            return 'Solo puede llevar ' . $sit['cupos'] . ' libro(s) más.';
        }
        return null;
    }

    /** Fecha hasta la que se debe devolver un préstamo hecho hoy. */
    public function fechaLimiteHoy()
    {
        return date('Y-m-d', strtotime('+' . (new Parametro())->obtener('DIAS_PRESTAMO') . ' days'));
    }

    /** Registra el préstamo de varios ejemplares. Devuelve los libros prestados y la fecha límite. */
    public function registrar($idEstudiante, $ejemplares, $cedulaUsuario, $puntaje = null)
    {
        if (!$ejemplares) {
            throw new Exception('Seleccione al menos un libro.');
        }
        $estudiante = (new Estudiante())->buscar($idEstudiante);
        $motivo = $this->motivoBloqueo($estudiante, $this->situacion($idEstudiante), count($ejemplares));
        if ($motivo) {
            throw new Exception($motivo);
        }

        $this->cn->begin_transaction();
        try {
            $detalle = [];
            foreach ($ejemplares as $idEjemplar) {
                $this->ejecutar('INSERT INTO prestamos (id_estudiante, id_ejemplar, cedula_usuario, huella_prestamo)
                                 VALUES (?, ?, ?, 1)', [$idEstudiante, $idEjemplar, $cedulaUsuario]);
                $detalle[] = $this->fila('SELECT ej.codigo_inventario, l.titulo FROM ejemplares ej
                                          JOIN libros l ON l.id_libro = ej.id_libro WHERE ej.id_ejemplar = ?', [$idEjemplar]);
            }
            $codigos = implode(', ', array_column($detalle, 'codigo_inventario'));
            (new Bitacora())->anotar($idEstudiante, 'PRESTAMO', 'EXITO', $puntaje, 'Préstamo de ' . count($detalle) . ' libro(s): ' . $codigos);
            $this->cn->commit();
        } catch (Exception $ex) {
            $this->cn->rollback();
            throw $ex;
        }
        $limite = $this->valor('SELECT MAX(fecha_limite) FROM prestamos WHERE id_estudiante = ?', [$idEstudiante]);
        return ['detalle' => $detalle, 'limite' => $limite];
    }

    /** Registra la devolución de varios préstamos. Devuelve los códigos devueltos y la multa generada. */
    public function devolver($idEstudiante, $prestamos, $puntaje = null)
    {
        if (!$prestamos) {
            throw new Exception('Seleccione al menos un libro.');
        }
        $this->cn->begin_transaction();
        try {
            $codigos = [];
            foreach ($prestamos as $idPrestamo) {
                $p = $this->fila("SELECT p.*, ej.codigo_inventario FROM prestamos p
                                  JOIN ejemplares ej ON ej.id_ejemplar = p.id_ejemplar
                                  WHERE p.id_prestamo = ? AND p.id_estudiante = ? AND p.estado = 'ACTIVO'",
                                  [$idPrestamo, $idEstudiante]);
                if (!$p) {
                    throw new Exception('Uno de los libros no está prestado a este estudiante.');
                }
                // La fecha del equipo no puede ser anterior al día en que se prestó el libro
                if (time() < strtotime($p['fecha_prestamo'])) {
                    throw new Exception('No se puede devolver el libro ' . $p['codigo_inventario']
                        . " antes de la fecha en que se prestó.\nFecha del préstamo: "
                        . date('d/m/Y H:i', strtotime($p['fecha_prestamo']))
                        . "\nFecha del equipo: " . date('d/m/Y H:i') . "\nRevise la fecha y hora del equipo.");
                }
                $this->ejecutar("UPDATE prestamos SET estado = 'DEVUELTO', huella_devolucion = 1, fecha_devolucion = NOW()
                                 WHERE id_prestamo = ?", [$idPrestamo]);
                $codigos[] = $p['codigo_inventario'];
            }
            (new Bitacora())->anotar($idEstudiante, 'DEVOLUCION', 'EXITO', $puntaje,
                'Devolución de ' . count($codigos) . ' libro(s): ' . implode(', ', $codigos));
            $this->cn->commit();
        } catch (Exception $ex) {
            $this->cn->rollback();
            throw $ex;
        }
        $marcas = implode(',', array_fill(0, count($prestamos), '?'));
        $multa = (float) $this->valor("SELECT COALESCE(SUM(monto), 0) FROM multas WHERE id_prestamo IN ($marcas)", $prestamos);
        return ['codigos' => $codigos, 'multa' => $multa];
    }

    /** Todos los préstamos de un estudiante, con su multa (panel del estudiante). */
    public function historialEstudiante($idEstudiante)
    {
        return $this->consultar('SELECT p.*, l.titulo, ej.codigo_inventario, m.monto, m.pagada FROM prestamos p
                                 JOIN ejemplares ej ON ej.id_ejemplar = p.id_ejemplar
                                 JOIN libros l ON l.id_libro = ej.id_libro
                                 LEFT JOIN multas m ON m.id_prestamo = p.id_prestamo
                                 WHERE p.id_estudiante = ? ORDER BY p.fecha_prestamo DESC', [$idEstudiante]);
    }

    /** Historial para el reporte, con filtros de fechas, carrera y estado. */
    public function historial($desde, $hasta, $carrera, $estado)
    {
        $sql = "SELECT p.fecha_prestamo, p.fecha_limite, p.fecha_devolucion, p.estado, e.cedula,
                       CONCAT(e.nombres, ' ', e.apellidos) AS estudiante, l.titulo, ej.codigo_inventario,
                       IFNULL(m.monto, 0) AS multa
                FROM prestamos p JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                JOIN ejemplares ej ON ej.id_ejemplar = p.id_ejemplar JOIN libros l ON l.id_libro = ej.id_libro
                LEFT JOIN multas m ON m.id_prestamo = p.id_prestamo
                WHERE DATE(p.fecha_prestamo) BETWEEN ? AND ?";
        $parametros = [$desde, $hasta];
        if ($carrera !== '') {
            $sql .= ' AND e.id_carrera = ?';
            $parametros[] = $carrera;
        }
        if ($estado !== '') {
            $sql .= ' AND p.estado = ?';
            $parametros[] = $estado;
        }
        return $this->consultar($sql . ' ORDER BY p.fecha_prestamo DESC', $parametros);
    }
}
