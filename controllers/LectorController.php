<?php
// Estado del lector de huella (como biometria.MonitorLector del sistema de escritorio).
// Todas las páginas tienen un marco oculto que pregunta cada 2 segundos si el lector está conectado;
// cuando el estado cambia, la página muestra el mensaje "Lector conectado" o "Lector desconectado".
class LectorController extends Controlador
{
    public function __construct()
    {
        if (!$this->usuario()) {
            exit;
        }
    }

    public function index()
    {
        $this->estado();
    }

    public function estado()
    {
        // Se suelta la sesión mientras se revisa el lector, para no detener a las otras páginas
        session_write_close();
        $this->responderLector(['accion' => 'estado'], HuellaLector::estado());
    }
}
