<?php
// Menú principal: cada rol ve sus grupos de opciones y sus tarjetas de resumen (igual al sistema de escritorio)
class MenuController extends Controlador
{
    public function __construct()
    {
        $this->exigirRol(['ADMIN', 'BIBLIOTECARIO']);
    }

    public function index()
    {
        $resumen = new Resumen();
        if ($this->usuario()['rol'] === 'ADMIN') {
            $titulo = 'Menú principal administrador';
            $tarjetas = $resumen->administrador();
            // grupo => [ancho, [nombre => [ruta, icono]]]
            $grupos = [
                'Usuarios' => [false, [
                    'Estudiantes' => ['c=estudiantes', 'Estudiantes.png'],
                    'Usuarios' => ['c=usuarios', 'Bibliotecarios.png'],
                ]],
                'Configuración' => [false, [
                    'Parámetros' => ['c=parametros', 'Parametros.png'],
                    'Bitácora' => ['c=bitacora', 'Bitacora.png'],
                ]],
                'Reportes' => [false, [
                    'Inventario' => ['c=reportes&a=inventario', 'Invetario.png'],
                    'Historial' => ['c=reportes&a=historial', 'Historial.png'],
                ]],
            ];
        } else {
            $titulo = 'Menú principal bibliotecario';
            $tarjetas = $resumen->bibliotecario();
            $grupos = [
                'Gestión de información' => [false, [
                    'Libros' => ['c=libros', 'libros.png'],
                    'Estudiantes' => ['c=consulta', 'Estudiantes.png'],
                ]],
                'Préstamos' => [true, [
                    'Préstamo' => ['c=prestamos', 'Prestamo.png'],
                    'Devolución' => ['c=devoluciones', 'Devolver.png'],
                    'Atrasados' => ['c=atrasos', 'Atrasado.png'],
                ]],
                'Reportes' => [false, [
                    'Inventario' => ['c=reportes&a=inventario', 'Invetario.png'],
                    'Historial' => ['c=reportes&a=historial', 'Historial.png'],
                ]],
            ];
        }
        $this->vista('menu/index', ['titulo' => $titulo, 'tarjetas' => $tarjetas, 'grupos' => $grupos]);
    }
}
