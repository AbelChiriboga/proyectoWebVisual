<?php
// Estudiantes de la biblioteca (con su carrera y sus huellas)
class Estudiante extends Modelo
{
    const VACIO = ['id_estudiante' => '', 'cedula' => '', 'nombres' => '', 'apellidos' => '', 'telefono' => '',
                   'correo' => '', 'id_carrera' => '', 'semestre' => '', 'estado' => 'ACTIVO'];

    const FILTROS = ['TODOS' => 'Todos', 'ACTIVOS' => 'Activos', 'INACTIVOS' => 'Inactivos',
                     'ATRASOS' => 'Con atrasos', 'MULTAS' => 'Con multas'];

    const COLUMNAS_BUSQUEDA = ['e.cedula', 'e.nombres', 'e.apellidos', "CONCAT(e.nombres, ' ', e.apellidos)"];

    /** Busca estudiantes por cédula o nombre (solo los que coinciden, no todos). */
    public function listar($texto)
    {
        list($donde, $parametros) = $this->condicionBusqueda(self::COLUMNAS_BUSQUEDA, $texto);
        return $this->consultar('SELECT e.*, c.nombre AS carrera,
                                 (SELECT COUNT(*) FROM huellas h WHERE h.id_estudiante = e.id_estudiante) AS huellas
                                 FROM estudiantes e JOIN carreras c ON c.id_carrera = e.id_carrera
                                 WHERE 1 = 1' . $donde . ' ORDER BY e.apellidos, e.nombres' . $this->limiteBusqueda(), $parametros);
    }

    /** Consulta del bibliotecario: busca estudiantes con sus préstamos, atrasos y multas, según el filtro. */
    public function listarConPrestamos($filtro, $texto)
    {
        $prestados = "(SELECT COUNT(*) FROM prestamos p WHERE p.id_estudiante = e.id_estudiante AND p.estado = 'ACTIVO')";
        $atrasados = "(SELECT COUNT(*) FROM prestamos p WHERE p.id_estudiante = e.id_estudiante AND p.estado = 'ACTIVO'
                       AND DATE(p.fecha_limite) < CURDATE())";
        $multas = "(SELECT COUNT(*) FROM multas m JOIN prestamos p ON p.id_prestamo = m.id_prestamo
                    WHERE p.id_estudiante = e.id_estudiante AND m.pagada = 0)";
        $filtros = ['ACTIVOS' => " AND e.estado = 'ACTIVO'", 'INACTIVOS' => " AND e.estado <> 'ACTIVO'",
                    'ATRASOS' => " AND $atrasados > 0", 'MULTAS' => " AND $multas > 0"];
        list($donde, $parametros) = $this->condicionBusqueda(self::COLUMNAS_BUSQUEDA, $texto);
        return $this->consultar("SELECT e.*, c.nombre AS carrera, $prestados AS prestados, $atrasados AS atrasados,
                                 $multas AS multas
                                 FROM estudiantes e JOIN carreras c ON c.id_carrera = e.id_carrera
                                 WHERE 1 = 1" . ($filtros[$filtro] ?? '') . $donde
                                 . ' ORDER BY e.apellidos, e.nombres' . $this->limiteBusqueda(), $parametros);
    }

    /** Estudiante con el nombre de su carrera, o null. */
    public function buscar($id)
    {
        return $this->fila('SELECT e.*, c.nombre AS carrera FROM estudiantes e
                            JOIN carreras c ON c.id_carrera = e.id_carrera WHERE e.id_estudiante = ?', [$id]);
    }

    public function idPorCedula($cedula)
    {
        return $this->valor('SELECT id_estudiante FROM estudiantes WHERE cedula = ?', [$cedula]);
    }

    public function carreras()
    {
        return $this->consultar('SELECT id_carrera, nombre FROM carreras ORDER BY nombre');
    }

    public function tieneHistorial($id)
    {
        return $this->valor('SELECT COUNT(*) FROM prestamos WHERE id_estudiante = ?', [$id]) > 0;
    }

    /**
     * Guarda el estudiante y las huellas capturadas con el lector ($huellas = [dedo => plantilla]), todo junto.
     * Ningún estudiante se guarda sin huella: debe tener una registrada o una recién capturada.
     */
    public function guardar($d, $huellas = [])
    {
        $id = trim($d['id_estudiante'] ?? '');
        $nuevo = $id === '';
        $e = [
            'nombres' => Validacion::texto($d['nombres'] ?? '', 'nombres', 60),
            'apellidos' => Validacion::texto($d['apellidos'] ?? '', 'apellidos', 60),
            'correo' => Validacion::correo($d['correo'] ?? ''),
            'telefono' => Validacion::telefono($d['telefono'] ?? ''),
            'id_carrera' => Validacion::obligatorio($d['id_carrera'] ?? '', 'la carrera'),
            'semestre' => Validacion::entero($d['semestre'] ?? '', 'semestre', 1, 10),
        ];
        $cedula = trim($d['cedula'] ?? '');
        if ($nuevo) {
            $this->validarCedulaNueva($cedula);
        }

        // La huella se captura antes con el botón "Capturar huella" del lector
        $modeloHuella = new Huella();
        $tieneHuella = !$nuevo && $modeloHuella->dedosRegistrados($id);
        if (!$tieneHuella && !$huellas) {
            throw new Exception('Registre la huella del estudiante.');
        }
        $bitacora = new Bitacora();

        $this->cn->begin_transaction();
        try {
            if ($nuevo) {
                $id = $this->ejecutar('INSERT INTO estudiantes (cedula, nombres, apellidos, correo, telefono, id_carrera, semestre)
                                       VALUES (?, ?, ?, ?, ?, ?, ?)', [$cedula, $e['nombres'], $e['apellidos'],
                                       $e['correo'], $e['telefono'], $e['id_carrera'], $e['semestre']]);
            } else {
                $this->ejecutar('UPDATE estudiantes SET nombres = ?, apellidos = ?, correo = ?, telefono = ?,
                                 id_carrera = ?, semestre = ? WHERE id_estudiante = ?', [$e['nombres'], $e['apellidos'],
                                 $e['correo'], $e['telefono'], $e['id_carrera'], $e['semestre'], $id]);
            }
            foreach ($huellas as $dedo => $plantilla) {
                $modeloHuella->guardar($id, $dedo, $plantilla);
                $bitacora->anotar($id, 'REGISTRO_HUELLA', 'EXITO', null, Huella::DEDOS[$dedo] . ', 3 muestras');
            }
            $this->cn->commit();
        } catch (Exception $ex) {
            $this->cn->rollback();
            throw $ex;
        }
    }

    /** La cédula debe ser válida y no estar usada por otro estudiante ni por el personal. */
    private function validarCedulaNueva($cedula)
    {
        Validacion::cedula($cedula);
        $otro = $this->fila('SELECT nombres, apellidos FROM estudiantes WHERE cedula = ?', [$cedula]);
        if ($otro) {
            throw new Exception('Esa cédula ya es de ' . $otro['nombres'] . ' ' . $otro['apellidos'] . '.');
        }
        if ($this->valor('SELECT COUNT(*) FROM usuarios WHERE cedula = ?', [$cedula])) {
            throw new Exception('Esa cédula ya está registrada como usuario del personal.');
        }
    }

    /** Sin préstamos se elimina; con historial solo se desactiva o se activa. Devuelve el mensaje. */
    public function retirar($id)
    {
        $estudiante = $this->buscar($id);
        if (!$estudiante) {
            throw new Exception('Seleccione un estudiante.');
        }
        if (!$this->tieneHistorial($id)) {
            $this->ejecutar('DELETE FROM estudiantes WHERE id_estudiante = ?', [$id]);
            return 'Estudiante eliminado.';
        }
        return $this->cambiarEstado($id, $estudiante['estado'] === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO');
    }

    /** Activa o desactiva al estudiante. Devuelve el mensaje. */
    public function cambiarEstado($id, $estado)
    {
        $estado = $estado === 'ACTIVO' ? 'ACTIVO' : 'INACTIVO';
        $this->ejecutar('UPDATE estudiantes SET estado = ? WHERE id_estudiante = ?', [$estado, $id]);
        return $estado === 'ACTIVO' ? 'Estudiante activado.' : 'Estudiante desactivado.';
    }
}
