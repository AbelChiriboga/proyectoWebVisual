<?php
// Autores de los libros (bibliotecario)
class AutorController extends Controlador
{
    private $autores;

    public function __construct()
    {
        $this->exigirRol('BIBLIOTECARIO');
        $this->autores = new Autor();
    }

    public function index($datos = null)
    {
        list($lista, $hayMas) = $this->resultado([$this->autores, 'listar']);
        $this->vista('autores/index', [
            'datos' => $datos ?? Autor::VACIO,
            'autores' => $lista,
            'hayMas' => $hayMas,
        ]);
    }

    public function editar()
    {
        $this->index($this->autores->buscar($this->get('id')) ?: null);
    }

    public function guardar()
    {
        $this->exigirPost('c=autores');
        try {
            $this->autores->guardar($_POST);
            $this->avisar('Autor guardado.');
            $this->redirigir('c=autores');
        } catch (Exception $ex) {
            $this->avisarError($ex);
            $this->index(array_merge(Autor::VACIO, array_intersect_key($_POST, Autor::VACIO)));
        }
    }

    public function eliminar()
    {
        $this->exigirPost('c=autores');
        try {
            $this->autores->eliminar($this->post('id_autor'));
            $this->avisar('Autor eliminado.');
        } catch (Exception $ex) {
            $this->avisarError($ex);
        }
        $this->redirigir('c=autores');
    }
}
