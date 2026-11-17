<?php
// Consulta de estudiantes: su situación, sus libros prestados y activar / desactivar (bibliotecario)
class ConsultaController extends Controlador
{
    private $estudiantes;

    public function __construct()
    {
        $this->exigirRol('BIBLIOTECARIO');
        $this->estudiantes = new Estudiante();
    }

    public function index()
    {
        $filtro = $this->get('filtro', 'TODOS');
        $id = $this->get('id');
        $elegido = $id !== '' ? $this->estudiantes->buscar($id) : null;
        // Solo los estudiantes que coinciden con la búsqueda y el filtro (no se cargan todos)
        list($lista, $hayMas) = $this->resultado(function ($texto) use ($filtro) {
            return $this->estudiantes->listarConPrestamos($filtro, $texto);
        });
        $this->vista('consulta/index', [
            'filtros' => Estudiante::FILTROS,
            'filtro' => $filtro,
            'id' => $id,
            'estudiantes' => $lista,
            'hayMas' => $hayMas,
            'elegido' => $elegido,
            'sit' => $elegido ? (new Prestamo())->situacion($id) : null,
        ]);
    }

    public function cambiarEstado()
    {
        $this->exigirPost('c=consulta');
        $this->avisar($this->estudiantes->cambiarEstado($this->post('id'), $this->post('estado')));
        $this->redirigir('c=consulta&filtro=' . urlencode($this->post('filtro')) . '&id=' . urlencode($this->post('id')));
    }
}
