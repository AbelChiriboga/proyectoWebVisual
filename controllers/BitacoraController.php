<?php
// Bitácora de autenticación biométrica (solo el administrador)
class BitacoraController extends Controlador
{
    public function __construct()
    {
        $this->exigirRol('ADMIN');
    }

    public function index()
    {
        $desde = $this->get('desde', date('Y-m-d', strtotime('-6 days')));
        $hasta = $this->get('hasta', date('Y-m-d'));
        $operacion = $this->get('operacion');
        if (!isset(Bitacora::OPERACIONES[$operacion])) {
            $operacion = ''; // Todas
        }
        $resultado = $this->get('resultado');
        if ($desde > $hasta) {
            $this->avisar('La fecha Desde no puede ser mayor que Hasta.', 'error');
            $desde = $hasta;
        }

        $bitacora = new Bitacora();
        $registros = $bitacora->listar($desde, $hasta, $operacion, $resultado);
        $this->vista('bitacora/index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'operacion' => $operacion,
            'resultado' => $resultado,
            'operaciones' => Bitacora::OPERACIONES,
            'registros' => $registros,
            'resumen' => $bitacora->resumen($registros, $desde, $hasta),
            'umbral' => (new Parametro())->obtener('UMBRAL_HUELLA'),
        ]);
    }
}
