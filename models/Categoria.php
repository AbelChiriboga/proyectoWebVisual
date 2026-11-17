<?php
// Categorías de los libros
class Categoria extends Modelo
{
    const VACIO = ['id_categoria' => '', 'nombre' => ''];

    /** Busca categorías por nombre (solo las que coinciden, no todas). */
    public function listar($texto)
    {
        list($donde, $parametros) = $this->condicionBusqueda(['c.nombre'], $texto);
        return $this->consultar('SELECT c.*,
                                 (SELECT COUNT(*) FROM libros l WHERE l.id_categoria = c.id_categoria) AS libros,
                                 (SELECT COUNT(*) FROM ejemplares ej JOIN libros l ON l.id_libro = ej.id_libro
                                  WHERE l.id_categoria = c.id_categoria) AS ejemplares
                                 FROM categorias c WHERE 1 = 1' . $donde . ' ORDER BY c.nombre'
                                 . $this->limiteBusqueda(), $parametros);
    }

    /** Categorías para las listas desplegables. */
    public function opciones()
    {
        return $this->consultar('SELECT id_categoria, nombre FROM categorias ORDER BY nombre');
    }

    public function buscar($id)
    {
        return $this->fila('SELECT * FROM categorias WHERE id_categoria = ?', [$id]);
    }

    public function guardar($d)
    {
        $nombre = Validacion::texto($d['nombre'] ?? '', 'nombre de la categoría', 60);
        $id = trim($d['id_categoria'] ?? '');
        if ($id === '') {
            $this->ejecutar('INSERT INTO categorias (nombre) VALUES (?)', [$nombre]);
        } else {
            $this->ejecutar('UPDATE categorias SET nombre = ? WHERE id_categoria = ?', [$nombre, $id]);
        }
    }

    public function eliminar($id)
    {
        if ($this->valor('SELECT COUNT(*) FROM libros WHERE id_categoria = ?', [$id])) {
            throw new Exception('No se puede eliminar: la categoría tiene libros.');
        }
        $this->ejecutar('DELETE FROM categorias WHERE id_categoria = ?', [$id]);
    }
}
