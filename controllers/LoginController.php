<?php
// Inicio de sesión: el personal entra con usuario y contraseña; el estudiante consulta con su cédula
class LoginController extends Controlador
{
    public function index()
    {
        // Al volver al login se cierra la sesión anterior (se conserva solo el aviso pendiente)
        unset($_SESSION['usuario'], $_SESSION['estudiante'], $_SESSION['prestamo_estudiante'],
              $_SESSION['devolucion_estudiante'], $_SESSION['prestamo_puntaje'], $_SESSION['devolucion_puntaje']);
        $pestana = $this->get('tab') === 'estudiante' ? 'estudiante' : 'personal';
        $this->vista('login/index', ['pestana' => $pestana]);
    }

    public function ingresar()
    {
        $this->exigirPost('c=login');
        try {
            $_SESSION['usuario'] = (new Usuario())->autenticar($this->post('usuario'), $_POST['clave'] ?? '');
            $this->redirigir('c=menu');
        } catch (Exception $ex) {
            $this->avisarError($ex);
            $this->redirigir('c=login&tab=personal');
        }
    }

    public function consultar()
    {
        $this->exigirPost('c=login&tab=estudiante');
        try {
            $cedula = Validacion::obligatorio($this->post('cedula'), 'cédula');
            $id = (new Estudiante())->idPorCedula($cedula);
            if (!$id) {
                throw new Exception('No hay un estudiante registrado con esa cédula.');
            }
            $_SESSION['estudiante'] = $id;
            $this->redirigir('c=panel');
        } catch (Exception $ex) {
            $this->avisarError($ex);
            $this->redirigir('c=login&tab=estudiante');
        }
    }

    public function salir()
    {
        session_destroy();
        $this->redirigir('c=login');
    }
}
