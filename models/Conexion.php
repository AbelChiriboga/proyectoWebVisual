<?php
// Conexión a la base de datos biblioteca_huella (XAMPP: usuario root sin contraseña)
class Conexion
{
    private static $cn = null;

    /** Devuelve siempre la misma conexión (se abre una sola vez por petición). */
    public static function conectar()
    {
        if (self::$cn === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            self::$cn = new mysqli('localhost', 'root', '', 'biblioteca_huella');
            self::$cn->set_charset('utf8mb4');
        }
        return self::$cn;
    }
}
