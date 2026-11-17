<?php
// Autores de los libros
class Autor extends Modelo
{
    const VACIO = ['id_autor' => '', 'nombres' => '', 'apellidos' => '', 'nacionalidad' => ''];

    /** Busca autores por nombre o nacionalidad (solo los que coinciden, no todos). */
    public function listar($texto)
    {
        list($donde, $parametros) = $this->condicionBusqueda(['a.nombres', 'a.apellidos', 'a.nacionalidad',
                                                               "CONCAT(a.nombres, ' ', a.apellidos)"], $texto);
        return $this->consultar('SELECT a.*, (SELECT COUNT(*) FROM libros l WHERE l.id_autor = a.id_autor) AS libros
                                 FROM autores a WHERE 1 = 1' . $donde . ' ORDER BY a.apellidos, a.nombres'
                                 . $this->limiteBusqueda(), $parametros);
    }

    /** Autores para la lista desplegable de libros. */
    public function opciones()
    {
        return $this->consultar("SELECT id_autor, CONCAT(nombres, ' ', apellidos) AS nombre FROM autores
                                 ORDER BY nombres, apellidos");
    }

    public function buscar($id)
    {
        return $this->fila('SELECT * FROM autores WHERE id_autor = ?', [$id]);
    }

    public function guardar($d)
    {
        $datos = [
            Validacion::texto($d['nombres'] ?? '', 'nombres', 60),
            Validacion::texto($d['apellidos'] ?? '', 'apellidos', 60),
            Validacion::opcional($d['nacionalidad'] ?? '', 'nacionalidad', 50),
        ];
        $id = trim($d['id_autor'] ?? '');
        if ($id === '') {
            $this->ejecutar('INSERT INTO autores (nombres, apellidos, nacionalidad) VALUES (?, ?, ?)', $datos);
        } else {
            $datos[] = $id;
            $this->ejecutar('UPDATE autores SET nombres = ?, apellidos = ?, nacionalidad = ? WHERE id_autor = ?', $datos);
        }
    }

    public function eliminar($id)
    {
        if ($this->valor('SELECT COUNT(*) FROM libros WHERE id_autor = ?', [$id])) {
            throw new Exception('No se puede eliminar: el autor tiene libros.');
        }
        $this->ejecutar('DELETE FROM autores WHERE id_autor = ?', [$id]);
    }
}
