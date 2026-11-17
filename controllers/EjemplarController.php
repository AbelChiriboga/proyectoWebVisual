<?php
// Copias físicas de un libro (bibliotecario), igual a vista.CRUDEjemplares.
// Dos casos, como EjemplaresGuardados / EjemplaresPendientes del sistema de escritorio:
//   - Libro que ya existe (libro=ID): cada cambio se guarda en la base al momento.
//   - Libro NUEVO (nuevo=1): los ejemplares quedan pendientes en la sesión y se guardan junto con el libro.
class EjemplarController extends Controlador
{
    private $ejemplares;
    private $libro;
    private $nuevo; // true = libro nuevo (ejemplares pendientes)

    public function __construct()
    {
        $this->exigirRol('BIBLIOTECARIO');
        $this->ejemplares = new Ejemplar();
        $this->nuevo = !empty($_GET['nuevo']) || !empty($_POST['nuevo']);
        if ($this->nuevo) {
            $this->libro = $this->libroNuevo();
        } else {
            $this->libro = (new Libro())->buscarConAutor($_GET['libro'] ?? $_POST['libro'] ?? '');
        }
        if (!$this->libro) {
            $this->avisar('Seleccione un libro.', 'error');
            $this->redirigir('c=libros');
        }
    }

    public function index($datos = null)
    {
        $datos = $datos ?? Ejemplar::VACIO;
        $editando = $datos['id_ejemplar'] !== '';
        $lista = $this->lista();
        $disponibles = 0;
        foreach ($lista as $x) {
            if ($x['estado'] === 'DISPONIBLE') {
                $disponibles++;
            }
        }
        $this->vista('ejemplares/index', [
            'libro' => $this->libro,
            'nuevo' => $this->nuevo,
            'datos' => $datos,
            'editando' => $editando,
            'codigo' => $editando ? $datos['codigo_inventario'] : $this->siguienteCodigo(),
            'estados' => Ejemplar::ESTADOS,
            'editables' => Ejemplar::ESTADOS_EDITABLES,
            'ejemplares' => $lista,
            'disponibles' => $disponibles,
            'volver' => $this->volver(),
            'regresarA' => ($this->nuevo ? 'c=libros&a=volver&modo=nuevo' : 'c=libros&a=editar&id=' . $this->libro['id_libro'] . '&modo=edicion')
                           . con_busqueda($_SESSION['libros_buscar'] ?? null),
        ]);
    }

    public function editar()
    {
        $id = $this->get('id');
        foreach ($this->lista() as $x) {
            if ((string) $x['id_ejemplar'] === $id) {
                $this->index($x);
                return;
            }
        }
        $this->index();
    }

    public function guardar()
    {
        $this->exigirPost($this->volver());
        try {
            $id = $this->post('id_ejemplar');
            if ($this->nuevo) {
                $ej = Ejemplar::validarDatos($_POST, $id !== '');
                if ($id === '') {
                    $_SESSION['libro_nuevo']['ejemplares'][] = $ej;
                } elseif (isset($_SESSION['libro_nuevo']['ejemplares'][$id])) {
                    $_SESSION['libro_nuevo']['ejemplares'][$id] = $ej;
                }
            } else {
                $this->ejemplares->guardar($this->libro['id_libro'], $_POST);
            }
            $this->avisar('Ejemplar guardado.');
        } catch (Exception $ex) {
            $this->avisarError($ex);
        }
        $this->redirigir($this->volver());
    }

    public function eliminar()
    {
        $this->exigirPost($this->volver());
        try {
            if ($this->nuevo) {
                unset($_SESSION['libro_nuevo']['ejemplares'][$this->post('id_ejemplar')]);
                $_SESSION['libro_nuevo']['ejemplares'] = array_values($_SESSION['libro_nuevo']['ejemplares']);
            } else {
                $this->ejemplares->eliminar($this->post('id_ejemplar'));
            }
            $this->avisar('Ejemplar eliminado.');
        } catch (Exception $ex) {
            $this->avisarError($ex);
        }
        $this->redirigir($this->volver());
    }

    /** Ejemplares del libro: los guardados en la base o, si el libro es nuevo, los pendientes. */
    private function lista()
    {
        if (!$this->nuevo) {
            return $this->ejemplares->listarPorLibro($this->libro['id_libro']);
        }
        // Los pendientes reciben los códigos que tendrán al guardar (siguientes libres de la base)
        $lista = [];
        $numero = $this->numero($this->ejemplares->siguienteCodigo());
        foreach ($_SESSION['libro_nuevo']['ejemplares'] as $i => $ej) {
            $lista[] = ['id_ejemplar' => $i, 'codigo_inventario' => $this->codigo($numero + $i),
                        'ubicacion' => $ej['ubicacion'], 'estado' => $ej['estado']];
        }
        return $lista;
    }

    /** El siguiente código libre, contando también los pendientes. */
    private function siguienteCodigo()
    {
        $siguiente = $this->ejemplares->siguienteCodigo();
        return $this->nuevo ? $this->codigo($this->numero($siguiente) + count($_SESSION['libro_nuevo']['ejemplares'])) : $siguiente;
    }

    private function numero($codigo)
    {
        return (int) substr($codigo, strrpos($codigo, '-') + 1);
    }

    private function codigo($numero)
    {
        return 'LIB-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /** Datos del libro nuevo que se está escribiendo (todavía no está en la base). */
    private function libroNuevo()
    {
        if (empty($_SESSION['libro_nuevo']['datos'])) {
            return null;
        }
        $d = $_SESSION['libro_nuevo']['datos'];
        $autor = (new Autor())->buscar($d['id_autor']);
        return ['id_libro' => '', 'titulo' => $d['titulo'], 'isbn' => $d['isbn'],
                'autor' => $autor ? $autor['nombres'] . ' ' . $autor['apellidos'] : ''];
    }

    private function volver()
    {
        return $this->nuevo ? 'c=ejemplares&nuevo=1' : 'c=ejemplares&libro=' . $this->libro['id_libro'];
    }
}
