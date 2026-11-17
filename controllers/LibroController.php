<?php
// Catálogo de libros (bibliotecario), igual a vista.CRUDLibros.
// Un libro NUEVO se guarda junto con sus ejemplares: se agregan con el botón Ejemplares y quedan
// pendientes (en la sesión) hasta presionar Guardar. En un libro que ya existe, los ejemplares se guardan al momento.
class LibroController extends Controlador
{
    private $libros;

    public function __construct()
    {
        $this->exigirRol('BIBLIOTECARIO');
        $this->libros = new Libro();
    }

    public function index($datos = null)
    {
        $datos = $datos ?? Libro::VACIO;
        list($lista, $hayMas) = $this->resultado([$this->libros, 'listar']);
        $this->vista('libros/index', [
            'datos' => $datos,
            'editando' => $datos['id_libro'] !== '',
            'pendientes' => count($_SESSION['libro_nuevo']['ejemplares'] ?? []),
            'autores' => (new Autor())->opciones(),
            'categorias' => (new Categoria())->opciones(),
            'libros' => $lista,
            'hayMas' => $hayMas,
        ]);
    }

    public function editar()
    {
        $libro = $this->libros->buscar($this->get('id'));
        $this->index($libro ? array_merge(Libro::VACIO, $libro) : null);
    }

    /** Al regresar de Ejemplares en un libro nuevo: el formulario sigue en Nuevo con lo que se escribió. */
    public function volver()
    {
        if (empty($_SESSION['libro_nuevo'])) {
            $this->redirigir('c=libros');
        }
        $this->index($_SESSION['libro_nuevo']['datos']);
    }

    /**
     * Botón Ejemplares (solo en Nuevo o Editar): primero revisa los datos del libro.
     * Libro nuevo: guarda lo escrito y abre sus ejemplares pendientes. Libro existente: abre sus ejemplares.
     */
    public function ejemplares()
    {
        $this->exigirPost('c=libros');
        $datos = $this->datosEscritos();
        $_SESSION['libros_buscar'] = $this->buscado(); // al regresar se vuelve a la misma búsqueda
        try {
            $libro = $this->libros->validar($_POST);
            if ($libro['id_libro'] !== '') {
                $this->redirigir('c=ejemplares&libro=' . $libro['id_libro']);
            }
            $_SESSION['libro_nuevo']['datos'] = $datos;
            $_SESSION['libro_nuevo']['ejemplares'] = $_SESSION['libro_nuevo']['ejemplares'] ?? [];
            $this->redirigir('c=ejemplares&nuevo=1');
        } catch (Exception $ex) {
            $this->avisarError($ex);
            $this->index($datos);
        }
    }

    public function guardar()
    {
        $this->exigirPost('c=libros');
        try {
            if ($this->post('id_libro') === '') {
                $cantidad = $this->libros->crear($_POST, $_SESSION['libro_nuevo']['ejemplares'] ?? []);
                unset($_SESSION['libro_nuevo']);
            } else {
                $cantidad = $this->libros->actualizar($_POST);
            }
            $this->avisar("Libro guardado correctamente\n" . $cantidad . ($cantidad == 1 ? ' ejemplar' : ' ejemplares'));
            $this->redirigir('c=libros');
        } catch (Exception $ex) {
            $this->avisarError($ex);
            $this->index($this->datosEscritos());
        }
    }

    public function eliminar()
    {
        $this->exigirPost('c=libros');
        try {
            $this->libros->eliminar($this->post('id_libro'));
            $this->avisar('Libro eliminado.');
        } catch (Exception $ex) {
            $this->avisarError($ex);
        }
        $this->redirigir('c=libros');
    }

    /**
     * Botones "+" de Autor y Categoría: guarda lo escrito y abre Autores o Categorías.
     * Al regresar, el formulario sigue igual (como la ventana de Java, que queda abierta detrás).
     */
    public function agregar()
    {
        $this->exigirPost('c=libros');
        $_SESSION['libro_formulario'] = ['datos' => $this->datosEscritos(), 'modo' => $this->post('modo')];
        $_SESSION['libros_buscar'] = $this->buscado(); // al regresar se vuelve a la misma búsqueda
        $this->redirigir('c=' . ($this->get('ir') === 'categorias' ? 'categorias' : 'autores'));
    }

    /** Al regresar de Autores o Categorías: vuelve el formulario con lo escrito y la lista actualizada. */
    public function retomar()
    {
        $guardado = $_SESSION['libro_formulario'] ?? null;
        unset($_SESSION['libro_formulario']);
        if (!$guardado) {
            $this->redirigir('c=libros');
        }
        $this->index($guardado['datos']);
    }

    /** Lo que se escribió en el formulario. */
    private function datosEscritos()
    {
        return array_merge(Libro::VACIO, array_intersect_key($_POST, Libro::VACIO));
    }
}
