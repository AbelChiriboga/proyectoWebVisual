<?php
// Números de las tarjetas del menú principal
class Resumen extends Modelo
{
    /** Tarjetas del administrador: nombre => [número, en rojo]. */
    public function administrador()
    {
        return [
            'Estudiantes registrados' => [$this->valor('SELECT COUNT(*) FROM estudiantes'), false],
            'Bibliotecarios activos' => [$this->valor("SELECT COUNT(*) FROM usuarios WHERE rol = 'BIBLIOTECARIO' AND activo = 1"), false],
            'Préstamos activos' => [$this->valor("SELECT COUNT(*) FROM prestamos WHERE estado = 'ACTIVO'"), false],
            'Huellas fallidas hoy' => [$this->valor("SELECT COUNT(*) FROM registro_autenticacion
                                                     WHERE resultado = 'FALLO' AND DATE(fecha) = CURDATE()"), true],
        ];
    }

    /** Tarjetas del bibliotecario: nombre => [número, en rojo]. */
    public function bibliotecario()
    {
        return [
            'Devoluciones hoy' => [$this->valor('SELECT COUNT(*) FROM prestamos WHERE DATE(fecha_devolucion) = CURDATE()'), false],
            'Ejemplares disponibles' => [$this->valor("SELECT COUNT(*) FROM ejemplares WHERE estado = 'DISPONIBLE'"), false],
            'Préstamos atrasados' => [$this->valor('SELECT COUNT(*) FROM v_prestamos_activos WHERE dias_restantes < 0'), true],
            'Préstamos activos' => [$this->valor("SELECT COUNT(*) FROM prestamos WHERE estado = 'ACTIVO'"), false],
        ];
    }
}
