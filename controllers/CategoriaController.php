<?php
// Categorías de los libros (bibliotecario)
class CategoriaController extends Controlador
{
    private $categorias;

    public function __construct()
    {
        $this->exigirRol('BIBLIOTECARIO');
        $this->categorias = new Categoria();
    }

    public function index($datos = null)
    {
        list($lista, $hayMas) = $this->resultado([$this->categorias, 'listar']);
        $this->vista('categorias/index', [
            'datos' => $datos ?? Categoria::VACIO,
            'categorias' => $lista,
            'hayMas' => $hayMas,
        ]);
    }

    public function editar()
    {
        $this->index($this->categorias->buscar($this->get('id')) ?: null);
    }

    public function guardar()
    {
        $this->exigirPost('c=categorias');
        try {
            $this->categorias->guardar($_POST);
            $this->avisar('Categoría guardada.');
            $this->redirigir('c=categorias');
        } catch (Exception $ex) {
            $this->avisarError($ex);
            $this->index(array_merge(Categoria::VACIO, array_intersect_key($_POST, Categoria::VACIO)));
        }
    }

    public function eliminar()
    {
        $this->exigirPost('c=categorias');
        try {
            $this->categorias->eliminar($this->post('id_categoria'));
            $this->avisar('Categoría eliminada.');
        } catch (Exception $ex) {
            $this->avisarError($ex);
        }
        $this->redirigir('c=categorias');
    }
}
