<?php
// Personal del sistema: administradores y bibliotecarios
class Usuario extends Modelo
{
    const VACIO = ['cedula' => '', 'nombres' => '', 'apellidos' => '', 'correo' => '', 'direccion' => '',
                   'telefono' => '', 'usuario' => '', 'rol' => '', 'activo' => 1, 'editando' => ''];

    /** Devuelve el usuario si el usuario y la contraseña son correctos y la cuenta está activa. */
    public function autenticar($usuario, $clave)
    {
        $usuario = Validacion::obligatorio($usuario, 'usuario');
        Validacion::obligatorio($clave, 'contraseña');
        $u = $this->fila('SELECT cedula, nombres, apellidos, usuario, rol, activo FROM usuarios
                          WHERE usuario = ? AND password_hash = SHA2(?, 256)', [$usuario, $clave]);
        if (!$u) {
            throw new Exception('Usuario o contraseña incorrectos.');
        }
        if (!$u['activo']) {
            throw new Exception('Su cuenta está desactivada.');
        }
        return $u;
    }

    /** Busca usuarios por cédula, nombre o usuario (solo los que coinciden, no todos). */
    public function listar($texto)
    {
        list($donde, $parametros) = $this->condicionBusqueda(['cedula', 'nombres', 'apellidos', 'usuario',
                                                               "CONCAT(nombres, ' ', apellidos)"], $texto);
        return $this->consultar('SELECT * FROM usuarios WHERE 1 = 1' . $donde . ' ORDER BY apellidos, nombres'
                                . $this->limiteBusqueda(), $parametros);
    }

    public function buscar($cedula)
    {
        return $this->fila('SELECT * FROM usuarios WHERE cedula = ?', [$cedula]);
    }

    /** Crea o actualiza un usuario. $yo es el usuario que tiene la sesión abierta. */
    public function guardar($d, $yo)
    {
        $editando = ($d['editando'] ?? '') !== '';
        $cedula = trim($d['cedula'] ?? '');
        $u = [
            'nombres' => Validacion::texto($d['nombres'] ?? '', 'nombres', 60),
            'apellidos' => Validacion::texto($d['apellidos'] ?? '', 'apellidos', 60),
            'correo' => Validacion::correo($d['correo'] ?? ''),
            'direccion' => Validacion::opcional($d['direccion'] ?? '', 'dirección', 150),
            'telefono' => Validacion::telefono($d['telefono'] ?? ''),
            'usuario' => Validacion::obligatorio($d['usuario'] ?? '', 'usuario'),
            'rol' => Validacion::obligatorio($d['rol'] ?? '', 'el rol'),
        ];
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $u['usuario'])) {
            throw new Exception('El usuario debe tener de 3 a 50 caracteres, sin espacios (letras, números, punto, guion).');
        }
        $clave = $d['clave'] ?? '';
        $cambiaClave = !$editando || $clave !== '';
        if ($cambiaClave) {
            if (strlen($clave) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres.');
            }
            if ($clave !== ($d['clave2'] ?? '')) {
                throw new Exception('Las contraseñas no coinciden.');
            }
        }

        if (!$editando) {
            $this->validarCedulaNueva($cedula);
            $this->ejecutar('INSERT INTO usuarios (cedula, nombres, apellidos, correo, direccion, telefono, usuario,
                             password_hash, rol) VALUES (?, ?, ?, ?, ?, ?, ?, SHA2(?, 256), ?)', [$cedula, $u['nombres'],
                             $u['apellidos'], $u['correo'], $u['direccion'], $u['telefono'], $u['usuario'], $clave, $u['rol']]);
            return;
        }
        if ($cedula === $yo['cedula'] && $u['rol'] !== $yo['rol']) {
            throw new Exception('No puede cambiar su propio rol.');
        }
        $this->ejecutar('UPDATE usuarios SET nombres = ?, apellidos = ?, correo = ?, direccion = ?, telefono = ?,
                         usuario = ?, rol = ? WHERE cedula = ?', [$u['nombres'], $u['apellidos'], $u['correo'],
                         $u['direccion'], $u['telefono'], $u['usuario'], $u['rol'], $cedula]);
        if ($cambiaClave) {
            $this->ejecutar('UPDATE usuarios SET password_hash = SHA2(?, 256) WHERE cedula = ?', [$clave, $cedula]);
        }
    }

    private function validarCedulaNueva($cedula)
    {
        Validacion::cedula($cedula);
        if ($this->valor('SELECT COUNT(*) FROM usuarios WHERE cedula = ?', [$cedula])) {
            throw new Exception('Ya existe un usuario con esa cédula.');
        }
        $est = $this->fila('SELECT nombres, apellidos FROM estudiantes WHERE cedula = ?', [$cedula]);
        if ($est) {
            throw new Exception('Esa cédula ya está registrada como estudiante (' . $est['nombres'] . ' ' . $est['apellidos'] . ').');
        }
    }

    /** Activa o desactiva un usuario (nadie puede desactivarse a sí mismo). */
    public function cambiarEstado($cedula, $yo)
    {
        if ($cedula === $yo['cedula']) {
            throw new Exception('No puede desactivar su propia cuenta.');
        }
        $this->ejecutar('UPDATE usuarios SET activo = 1 - activo WHERE cedula = ?', [$cedula]);
    }
}
