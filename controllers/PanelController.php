<?php
// Panel del estudiante: entra con su cédula y ve sus datos, préstamos y multas
class PanelController extends Controlador
{
    public function index()
    {
        if (empty($_SESSION['estudiante'])) {
            $this->redirigir('c=login&tab=estudiante');
        }
        $estudiante = (new Estudiante())->buscar($_SESSION['estudiante']);
        $prestamos = new Prestamo();
        $sit = $prestamos->situacion($estudiante['id_estudiante']);
        $this->vista('panel/index', [
            'est' => $estudiante,
            'sit' => $sit,
            'historial' => $prestamos->historialEstudiante($estudiante['id_estudiante']),
        ]);
    }
}
