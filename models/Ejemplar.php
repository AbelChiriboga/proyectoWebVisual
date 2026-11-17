<?php
// Copias físicas de los libros
class Ejemplar extends Modelo
{
    const ESTADOS = ['DISPONIBLE' => 'Disponible', 'PRESTADO' => 'Prestado', 'DANADO' => 'Dañado', 'PERDIDO' => 'Perdido'];
    const VACIO = ['id_ejemplar' => '', 'codigo_inventario' => '', 'ubicacion' => '', 'estado' => 'DISPONIBLE'];

    public function listarPorLibro($idLibro)
    {
        return $this->consultar('SELECT * FROM ejemplares WHERE id_libro = ? ORDER BY codigo_inventario', [$idLibro]);
    }

    public function buscar($id)
    {
        return $this->fila('SELECT * FROM ejemplares WHERE id_ejemplar = ?', [$id]);
    }

    const SQL_DISPONIBLES = "SELECT ej.id_ejemplar, ej.codigo_inventario, ej.ubicacion, l.titulo,
                             CONCAT(a.nombres, ' ', a.apellidos) AS autor
                             FROM ejemplares ej JOIN libros l ON l.id_libro = ej.id_libro
                             JOIN autores a ON a.id_autor = l.id_autor
                             WHERE ej.estado = 'DISPONIBLE'";

    /**
     * Préstamo: busca ejemplares disponibles por código, título o autor.
     * No se cargan todos los libros: la base de datos devuelve solo los que coinciden
     * (como máximo MAXIMO_BUSQUEDA + 1, para saber si hay más).
     */
    public function buscarDisponibles($texto)
    {
        $parecido = '%' . $texto . '%';
        return $this->consultar(self::SQL_DISPONIBLES . " AND (ej.codigo_inventario LIKE ? OR l.titulo LIKE ?
                                 OR CONCAT(a.nombres, ' ', a.apellidos) LIKE ?)
                                 ORDER BY l.titulo, ej.codigo_inventario LIMIT " . (self::MAXIMO_BUSQUEDA + 1),
                                 [$parecido, $parecido, $parecido]);
    }

    /** Préstamo: los ejemplares ya marcados (solo los que siguen disponibles). */
    public function disponiblesPorId($ids)
    {
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        return $this->consultar(self::SQL_DISPONIBLES . " AND ej.id_ejemplar IN ($marcas)
                                 ORDER BY l.titulo, ej.codigo_inventario", $ids);
    }

    /** Siguiente código de inventario libre: LIB-0001, LIB-0002... */
    public function siguienteCodigo()
    {
        $ultimo = (int) $this->valor("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo_inventario, '-', -1) AS UNSIGNED)), 0)
                                      FROM ejemplares");
        return 'LIB-' . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }

    /** Estados que se pueden elegir a mano al editar (Prestado lo pone solo el sistema). */
    const ESTADOS_EDITABLES = ['DISPONIBLE' => 'Disponible', 'DANADO' => 'Dañado', 'PERDIDO' => 'Perdido'];

    /** Crea un ejemplar con el siguiente código libre. Un ejemplar nuevo nace Disponible. */
    public function crear($idLibro, $ubicacion, $estado = 'DISPONIBLE')
    {
        $this->ejecutar('INSERT INTO ejemplares (id_libro, codigo_inventario, ubicacion, estado) VALUES (?, ?, ?, ?)',
                        [$idLibro, $this->siguienteCodigo(), $ubicacion, $estado]);
    }

    /** Revisa la ubicación y, al editar, el estado elegido. Devuelve ['ubicacion' => .., 'estado' => ..]. */
    public static function validarDatos($d, $editando)
    {
        $ubicacion = Validacion::opcional($d['ubicacion'] ?? '', 'ubicación', 50);
        if (!$editando) {
            return ['ubicacion' => $ubicacion, 'estado' => 'DISPONIBLE'];
        }
        $estado = Validacion::obligatorio($d['estado'] ?? '', 'el estado');
        if (!isset(self::ESTADOS_EDITABLES[$estado])) {
            throw new Exception('Ese estado no se puede elegir.');
        }
        return ['ubicacion' => $ubicacion, 'estado' => $estado];
    }

    public function guardar($idLibro, $d)
    {
        $id = trim($d['id_ejemplar'] ?? '');
        $ubicacion = Validacion::opcional($d['ubicacion'] ?? '', 'ubicación', 50);
        if ($id === '') {
            $this->crear($idLibro, $ubicacion);
            return;
        }
        $this->validarNoPrestado($id);
        $estado = Validacion::obligatorio($d['estado'] ?? '', 'el estado');
        if (!isset(self::ESTADOS[$estado]) || $estado === 'PRESTADO') {
            throw new Exception('Ese estado no se puede elegir.');
        }
        $this->ejecutar('UPDATE ejemplares SET ubicacion = ?, estado = ? WHERE id_ejemplar = ?', [$ubicacion, $estado, $id]);
    }

    public function eliminar($id)
    {
        $this->validarNoPrestado($id);
        if ($this->valor('SELECT COUNT(*) FROM prestamos WHERE id_ejemplar = ?', [$id])) {
            throw new Exception('No se puede eliminar: el ejemplar tiene historial de préstamos. Márquelo como Dañado o Perdido.');
        }
        $this->ejecutar('DELETE FROM ejemplares WHERE id_ejemplar = ?', [$id]);
    }

    private function validarNoPrestado($id)
    {
        $actual = $this->buscar($id);
        if ($actual && $actual['estado'] === 'PRESTADO') {
            throw new Exception('El ejemplar está prestado. Espere a que lo devuelvan.');
        }
    }

    /** Inventario para el reporte, con filtros de categoría y estado. */
    public function inventario($categoria, $estado)
    {
        $sql = "SELECT c.nombre AS categoria, ej.codigo_inventario, l.titulo, CONCAT(a.nombres, ' ', a.apellidos) AS autor,
                       ej.ubicacion, ej.estado
                FROM ejemplares ej JOIN libros l ON l.id_libro = ej.id_libro
                JOIN autores a ON a.id_autor = l.id_autor JOIN categorias c ON c.id_categoria = l.id_categoria WHERE 1 = 1";
        $parametros = [];
        if ($categoria !== '') {
            $sql .= ' AND c.id_categoria = ?';
            $parametros[] = $categoria;
        }
        if ($estado !== '') {
            $sql .= ' AND ej.estado = ?';
            $parametros[] = $estado;
        }
        return $this->consultar($sql . ' ORDER BY c.nombre, l.titulo, ej.codigo_inventario', $parametros);
    }
}
