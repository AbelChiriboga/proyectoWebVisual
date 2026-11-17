<?php
// Parámetros del sistema (solo el administrador)
class ParametroController extends Controlador
{
    private $parametros;

    public function __construct()
    {
        $this->exigirRol('ADMIN');
        $this->parametros = new Parametro();
    }

    public function index()
    {
        $this->vista('parametros/index', [
            'editables' => Parametro::EDITABLES,
            'valores' => $this->parametros->listar(),
        ]);
    }

    public function guardar()
    {
        $this->exigirPost('c=parametros');
        try {
            $this->parametros->guardar($_POST);
            $this->avisar('Parámetros guardados.');
        } catch (Exception $ex) {
            $this->avisarError($ex);
        }
        $this->redirigir('c=parametros');
    }
}
