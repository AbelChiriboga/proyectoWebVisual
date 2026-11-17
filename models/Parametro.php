<?php
// Parámetros del sistema: días de préstamo, máximo de préstamos, multa por día y umbral de huella
class Parametro extends Modelo
{
    // clave => [nombre, tipo de caja, máximo de caracteres]
    const EDITABLES = [
        // clave => [nombre, tipo de caja, máximo de caracteres, ayuda]
        'DIAS_PRESTAMO' => ['Días de préstamo', 'numeros', 2, 'Días que tiene el estudiante para devolver.'],
        'MAX_PRESTAMOS' => ['Máximo de préstamos', 'numeros', 2, 'Libros que puede tener a la vez.'],
        'MULTA_POR_DIA' => ['Multa por día (USD)', 'decimal', 5, 'Se cobra por cada día de atraso.'],
        'UMBRAL_HUELLA' => ['Umbral de huella', 'numeros', 3, 'Puntaje mínimo para aceptar la huella (1 a 100).'],
    ];

    public function obtener($clave)
    {
        return $this->valor('SELECT valor FROM parametros WHERE clave = ?', [$clave]);
    }

    /** Valores actuales de los parámetros editables. */
    public function listar()
    {
        $valores = [];
        foreach (array_keys(self::EDITABLES) as $clave) {
            $valores[$clave] = $this->obtener($clave);
        }
        return $valores;
    }

    public function guardar($datos)
    {
        $nuevos = [
            'DIAS_PRESTAMO' => Validacion::entero($datos['DIAS_PRESTAMO'] ?? '', 'días de préstamo', 1, 60),
            'MAX_PRESTAMOS' => Validacion::entero($datos['MAX_PRESTAMOS'] ?? '', 'máximo de préstamos', 1, 10),
            'UMBRAL_HUELLA' => Validacion::entero($datos['UMBRAL_HUELLA'] ?? '', 'umbral de huella', 1, 100),
        ];
        $multa = Validacion::obligatorio(str_replace(',', '.', $datos['MULTA_POR_DIA'] ?? ''), 'multa por día');
        if (!is_numeric($multa) || $multa < 0) {
            throw new Exception('Multa por día debe ser un número.');
        }
        if ($multa > 50) {
            throw new Exception('La multa por día no puede pasar de $50.');
        }
        $nuevos['MULTA_POR_DIA'] = number_format((float) $multa, 2, '.', '');

        foreach ($nuevos as $clave => $valor) {
            $this->ejecutar('UPDATE parametros SET valor = ? WHERE clave = ?', [$valor, $clave]);
        }
    }
}
