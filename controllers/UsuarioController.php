<?php
// Gestión del personal: administradores y bibliotecarios (solo el administrador)
class UsuarioController extends Controlador
{
    private $usuarios;

    public function __construct()
    {
        $this->exigirRol('ADMIN');
        $this->usuarios = new Usuario();
    }

    public function index($datos = null)
    {
        $datos = $datos ?? Usuario::VACIO;
        list($lista, $hayMas) = $this->resultado([$this->usuarios, 'listar']);
        $this->vista('usuarios/index', [
            'datos' => $datos,
            'editando' => $datos['editando'] !== '',
            'usuarios' => $lista,
            'hayMas' => $hayMas,
        ]);
    }

    public function editar()
    {
        $u = $this->usuarios->buscar($this->get('id'));
        $this->index($u ? array_merge($u, ['editando' => '1']) : null);
    }

    public function guardar()
    {
        $this->exigirPost('c=usuarios');
        try {
            $this->usuarios->guardar($_POST, $this->usuario());
            $this->avisar('Usuario guardado.');
            $this->redirigir('c=usuarios');
        } catch (Exception $ex) {
            $this->avisarError($ex);
            $this->index(array_merge(Usuario::VACIO, array_intersect_key($_POST, Usuario::VACIO)));
        }
    }

    public function cambiarEstado()
    {
        $this->exigirPost('c=usuarios');
        try {
            $this->usuarios->cambiarEstado($this->post('cedula'), $this->usuario());
            $this->avisar('Estado del usuario actualizado.');
        } catch (Exception $ex) {
            $this->avisarError($ex);
        }
        $this->redirigir('c=usuarios');
    }
}
