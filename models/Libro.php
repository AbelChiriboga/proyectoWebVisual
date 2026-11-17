<?php
// Catálogo de libros. Un libro nuevo se guarda junto con sus ejemplares (mínimo uno), igual que en Java.
class Libro extends Modelo
{
    const VACIO = ['id_libro' => '', 'isbn' => '', 'titulo' => '', 'id_autor' => '', 'id_categoria' => '',
                   'editorial' => '', 'anio_publicacion' => ''];

    /** Busca libros por título, autor o ISBN, con sus totales de ejemplares (solo los que coinciden, no todos). */
    public function listar($texto)
    {
        list($donde, $parametros) = $this->condicionBusqueda(['l.titulo', "CONCAT(a.nombres, ' ', a.apellidos)", 'l.isbn'], $texto);
        return $this->consultar("SELECT l.id_libro, l.isbn, l.titulo, CONCAT(a.nombres, ' ', a.apellidos) AS autor,
                                 c.nombre AS categoria, l.anio_publicacion,
                                 (SELECT COUNT(*) FROM ejemplares ej WHERE ej.id_libro = l.id_libro) AS total_ejemplares,
                                 (SELECT COUNT(*) FROM ejemplares ej WHERE ej.id_libro = l.id_libro
                                  AND ej.estado = 'DISPONIBLE') AS disponibles
                                 FROM libros l JOIN autores a ON a.id_autor = l.id_autor
                                 JOIN categorias c ON c.id_categoria = l.id_categoria
                                 WHERE 1 = 1" . $donde . ' ORDER BY l.titulo' . $this->limiteBusqueda(), $parametros);
    }

    public function buscar($id)
    {
        return $this->fila('SELECT * FROM libros WHERE id_libro = ?', [$id]);
    }

    /** Libro con el nombre de su autor (para la página de ejemplares). */
    public function buscarConAutor($id)
    {
        return $this->fila("SELECT l.*, CONCAT(a.nombres, ' ', a.apellidos) AS autor FROM libros l
                            JOIN autores a ON a.id_autor = l.id_autor WHERE l.id_libro = ?", [$id]);
    }

    /**
     * Revisa los datos escritos del libro (lo que hace el formulario de Java antes de abrir Ejemplares o guardar).
     * Devuelve los datos limpios.
     */
    public function validar($d)
    {
        $l = [
            'id_libro' => trim($d['id_libro'] ?? ''),
            'isbn' => Validacion::mayus(Validacion::obligatorio($d['isbn'] ?? '', 'ISBN')),
            'titulo' => Validacion::texto($d['titulo'] ?? '', 'título', 150),
            'id_autor' => Validacion::obligatorio($d['id_autor'] ?? '', 'el autor'),
            'id_categoria' => Validacion::obligatorio($d['id_categoria'] ?? '', 'la categoría'),
            'editorial' => Validacion::opcional($d['editorial'] ?? '', 'editorial', 100),
            'anio_publicacion' => trim($d['anio_publicacion'] ?? '') === ''
                ? null : Validacion::entero($d['anio_publicacion'], 'año', 0, 9999),
        ];
        if (!preg_match('/^(\d{9}[\dX]|\d{13})$/', str_replace('-', '', $l['isbn']))) {
            throw new Exception('El ISBN debe tener 10 o 13 dígitos.');
        }
        return $l;
    }

    /** Guarda los cambios de un libro que ya existe. Devuelve cuántos ejemplares tiene. */
    public function actualizar($d)
    {
        $l = $this->validar($d);
        $this->ejecutar('UPDATE libros SET titulo = ?, id_autor = ?, id_categoria = ?, editorial = ?,
                         anio_publicacion = ? WHERE id_libro = ?',
                         [$l['titulo'], $l['id_autor'], $l['id_categoria'], $l['editorial'], $l['anio_publicacion'], $l['id_libro']]);
        return (int) $this->valor('SELECT COUNT(*) FROM ejemplares WHERE id_libro = ?', [$l['id_libro']]);
    }

    /**
     * Guarda un libro NUEVO junto con sus ejemplares (los que se agregaron con el botón Ejemplares).
     * Debe tener al menos uno. $ejemplares = [['ubicacion' => .., 'estado' => ..], ...]
     */
    public function crear($d, $ejemplares)
    {
        $l = $this->validar($d);
        if (!$ejemplares) {
            throw new Exception('Agregue al menos un ejemplar con el botón Ejemplares.');
        }
        $modeloEjemplar = new Ejemplar();
        $this->cn->begin_transaction();
        try {
            $id = $this->ejecutar('INSERT INTO libros (isbn, titulo, id_autor, id_categoria, editorial, anio_publicacion)
                                   VALUES (?, ?, ?, ?, ?, ?)', [$l['isbn'], $l['titulo'], $l['id_autor'],
                                   $l['id_categoria'], $l['editorial'], $l['anio_publicacion']]);
            foreach ($ejemplares as $ej) {
                $modeloEjemplar->crear($id, $ej['ubicacion'], $ej['estado']);
            }
            $this->cn->commit();
        } catch (Exception $ex) {
            $this->cn->rollback();
            throw $ex;
        }
        return count($ejemplares);
    }

    public function eliminar($id)
    {
        if ($this->valor('SELECT COUNT(*) FROM ejemplares WHERE id_libro = ?', [$id])) {
            throw new Exception('No se puede eliminar: el libro tiene ejemplares.');
        }
        $this->ejecutar('DELETE FROM libros WHERE id_libro = ?', [$id]);
    }
}
