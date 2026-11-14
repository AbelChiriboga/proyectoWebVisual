<?php
// Validaciones de datos (mismas reglas que el sistema en Java)
class Validacion
{
    public static function obligatorio($valor, $campo)
    {
        if (trim((string) $valor) === '') {
            throw new Exception(ucfirst($campo) . ' es obligatorio.');
        }
        return trim($valor);
    }

    public static function maximo($valor, $max, $campo)
    {
        if (mb_strlen($valor) > $max) {
            throw new Exception(ucfirst($campo) . " admite máximo $max caracteres.");
        }
        return $valor;
    }

    public static function mayus($texto)
    {
        return mb_strtoupper(trim((string) $texto), 'UTF-8');
    }

    /** Texto obligatorio, en mayúsculas y con un máximo de caracteres. */
    public static function texto($valor, $campo, $max)
    {
        return self::maximo(self::mayus(self::obligatorio($valor, $campo)), $max, $campo);
    }

    /** Texto opcional, en mayúsculas y con un máximo de caracteres. */
    public static function opcional($valor, $campo, $max)
    {
        return self::maximo(self::mayus($valor), $max, $campo);
    }

    /** Cédula ecuatoriana: 10 dígitos, provincia válida y dígito verificador. */
    public static function cedula($cedula)
    {
        self::obligatorio($cedula, 'cédula');
        if (!preg_match('/^\d{10}$/', $cedula)) {
            throw new Exception('La cédula debe tener 10 dígitos.');
        }
        $provincia = (int) substr($cedula, 0, 2);
        if ((($provincia < 1 || $provincia > 24) && $provincia != 30) || (int) $cedula[2] >= 6) {
            throw new Exception('La cédula no es válida.');
        }
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $d = (int) $cedula[$i] * ($i % 2 == 0 ? 2 : 1);
            $suma += $d > 9 ? $d - 9 : $d;
        }
        if ((10 - $suma % 10) % 10 != (int) $cedula[9]) {
            throw new Exception('La cédula no es válida.');
        }
        return $cedula;
    }

    public static function correo($correo)
    {
        self::obligatorio($correo, 'correo');
        if (!preg_match('/^[\w.+-]+@[\w-]+(\.[\w-]+)+$/', $correo)) {
            throw new Exception('El correo electrónico no es válido.');
        }
        return self::maximo(trim($correo), 100, 'correo');
    }

    public static function telefono($telefono)
    {
        self::obligatorio($telefono, 'teléfono');
        if (!preg_match('/^\d{7,10}$/', trim($telefono))) {
            throw new Exception('El teléfono debe tener entre 7 y 10 dígitos.');
        }
        return trim($telefono);
    }

    public static function entero($valor, $campo, $min, $max)
    {
        $valor = self::obligatorio($valor, $campo);
        if (!preg_match('/^\d+$/', $valor) || $valor < $min || $valor > $max) {
            throw new Exception(ucfirst($campo) . " debe estar entre $min y $max.");
        }
        return (int) $valor;
    }
}
