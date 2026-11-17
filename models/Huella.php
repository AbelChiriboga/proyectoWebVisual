<?php
// Huellas de los estudiantes con el lector ZKTeco real (ver HuellaLector).
// La plantilla se guarda tal como la entrega el lector, igual que en el sistema de escritorio.
class Huella extends Modelo
{
    // Código de la excepción cuando la huella ya es de otro estudiante
    const DUPLICADA = 3;

    const DEDOS = [
        'PULGAR_DER' => 'Pulgar derecho', 'INDICE_DER' => 'Índice derecho', 'MEDIO_DER' => 'Medio derecho',
        'ANULAR_DER' => 'Anular derecho', 'MENIQUE_DER' => 'Meñique derecho', 'PULGAR_IZQ' => 'Pulgar izquierdo',
        'INDICE_IZQ' => 'Índice izquierdo', 'MEDIO_IZQ' => 'Medio izquierdo', 'ANULAR_IZQ' => 'Anular izquierdo',
        'MENIQUE_IZQ' => 'Meñique izquierdo',
    ];

    /** Dedo que se propone al registrar: primero los índices; si ya tienen huella, el primero libre. */
    public static function primerDedoLibre($registrados)
    {
        foreach (array_merge(['INDICE_DER', 'INDICE_IZQ'], array_keys(self::DEDOS)) as $dedo) {
            if (!in_array($dedo, $registrados)) {
                return $dedo;
            }
        }
        return 'INDICE_DER';
    }

    /** Todas las huellas guardadas: [id_huella => plantilla]. */
    public function todas()
    {
        $huellas = [];
        foreach ($this->consultar('SELECT id_huella, template FROM huellas') as $h) {
            $huellas[$h['id_huella']] = $h['template'];
        }
        return $huellas;
    }

    /**
     * Préstamo o devolución: el estudiante coloca el dedo y se busca de quién es.
     * Solo se acepta si el puntaje llega al umbral. Devuelve ['estudiante' => .., 'puntaje' => ..].
     */
    public function leer($operacion)
    {
        $lector = new HuellaLector();
        try {
            $lector->abrir();
            $lector->cargarHuellas($this->todas());
            $coincidencia = $lector->identificar();
        } catch (Exception $ex) {
            $lector->cerrar();
            throw $ex; // lector desconectado o nadie colocó el dedo: no cuenta como intento
        }
        $lector->cerrar();

        $encontrado = $this->aceptar($coincidencia);
        if (!$encontrado) {
            (new Bitacora())->anotar(null, $operacion, 'FALLO', null, 'Huella no reconocida');
            throw new Exception('Huella no reconocida. Intente de nuevo.');
        }
        return $encontrado;
    }

    // Números para las huellas capturadas que aún no se guardan (las de la base usan su id_huella)
    const PENDIENTE = 1000000000;

    /**
     * Registro: el estudiante coloca el mismo dedo 3 veces y se unen en una sola huella.
     * Cada llamada toma UNA muestra; $muestras son las que ya se tomaron de ese dedo.
     * $dedo es el dedo que se está registrando y $capturadas las huellas de otros dedos que ya se leyeron
     * en este formulario y todavía no se guardan ([dedo => plantilla]).
     * Devuelve ['muestras' => [...]] mientras faltan, o ['plantilla' => ...] cuando ya están las 3 unidas,
     * y en los dos casos 'imagen' (BMP) del dedo que se acaba de leer.
     * Desde la PRIMERA muestra se revisa que esa huella no esté ya registrada:
     *   - como huella de otro estudiante,
     *   - como otro dedo de este mismo estudiante (guardado o recién capturado).
     * Volver a leer el mismo dedo sí se permite: al guardar se reemplaza.
     * $idEstudiante es null cuando el estudiante todavía no se guarda.
     */
    public function muestra($idEstudiante, $muestras, $dedo, $capturadas = [])
    {
        $bitacora = new Bitacora();
        $lector = new HuellaLector();
        $pendientes = []; // número en el lector => dedo capturado
        try {
            $lector->abrir();
            $huellas = $this->todas();
            $n = self::PENDIENTE;
            foreach ($capturadas as $otroDedo => $plantilla) {
                if ($otroDedo !== $dedo) {
                    $huellas[$n] = $plantilla;
                    $pendientes[$n++] = $otroDedo;
                }
            }
            $lector->cargarHuellas($huellas);
        } catch (Exception $ex) {
            $lector->cerrar();
            throw $ex; // el lector no está listo: no cuenta como intento
        }
        try {
            $nueva = $lector->leerDedo();
            $imagen = $lector->ultimaImagen();
            $this->exigirQueNoEsteRegistrada($lector->buscar($nueva), $idEstudiante, $dedo, $pendientes);
            if ($muestras && !$lector->mismoDedo(end($muestras), $nueva)) {
                throw new Exception('Las muestras no son del mismo dedo. Intente de nuevo.');
            }
            $muestras[] = $nueva;
            if (count($muestras) < HuellaLector::MUESTRAS_REGISTRO) {
                $lector->cerrar();
                return ['muestras' => $muestras, 'imagen' => $imagen];
            }
            $plantilla = $lector->unir($muestras);
            $this->exigirQueNoEsteRegistrada($lector->buscar($plantilla), $idEstudiante, $dedo, $pendientes);
        } catch (Exception $ex) {
            $lector->cerrar();
            // Solo los intentos en que sí se puso el dedo van a la bitácora como fallo
            if (!in_array($ex->getCode(), [HuellaLector::SIN_DEDO, HuellaLector::CANCELADA])) {
                $bitacora->anotar($idEstudiante, 'REGISTRO_HUELLA', 'FALLO', null, $ex->getMessage());
            }
            throw $ex;
        }
        $lector->cerrar();
        return ['plantilla' => $plantilla, 'imagen' => $imagen];
    }

    /** Lanza un error (código DUPLICADA) si la huella leída ya está registrada como otra huella. */
    private function exigirQueNoEsteRegistrada($coincidencia, $idEstudiante, $dedo, $pendientes)
    {
        $umbral = (int) (new Parametro())->obtener('UMBRAL_HUELLA');
        if (!$coincidencia || $coincidencia['puntaje'] < $umbral) {
            return; // no se parece a ninguna
        }
        $id = $coincidencia['id_huella'];
        if (isset($pendientes[$id])) {
            throw new Exception('Esta huella ya la capturó como ' . self::DEDOS[$pendientes[$id]] . '. Use otro dedo.',
                                self::DUPLICADA);
        }
        $h = $this->fila('SELECT h.id_estudiante, h.dedo, e.nombres, e.apellidos FROM huellas h
                          JOIN estudiantes e ON e.id_estudiante = h.id_estudiante WHERE h.id_huella = ?', [$id]);
        if (!$h) {
            return;
        }
        if ($h['id_estudiante'] != $idEstudiante) {
            throw new Exception('Esta huella ya pertenece a ' . $h['nombres'] . ' ' . $h['apellidos'] . '. Use otro dedo.',
                                self::DUPLICADA);
        }
        if ($h['dedo'] !== $dedo) {
            throw new Exception('Esta huella ya está registrada como ' . self::DEDOS[$h['dedo']] . '. Use otro dedo.',
                                self::DUPLICADA);
        }
    }

    /** Dedos que ya tiene registrados un estudiante. */
    public function dedosRegistrados($idEstudiante)
    {
        return array_column($this->consultar('SELECT dedo FROM huellas WHERE id_estudiante = ?', [$idEstudiante]), 'dedo');
    }

    /** Guarda (o reemplaza) la huella de un dedo. */
    public function guardar($idEstudiante, $dedo, $plantilla)
    {
        $this->ejecutar('INSERT INTO huellas (id_estudiante, dedo, template) VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE template = VALUES(template), fecha_registro = NOW()',
                         [$idEstudiante, $dedo, $plantilla]);
    }

    /** Si el puntaje llega al umbral, devuelve el estudiante dueño de la huella y el puntaje; si no, null. */
    private function aceptar($coincidencia)
    {
        $umbral = (int) (new Parametro())->obtener('UMBRAL_HUELLA');
        if (!$coincidencia || $coincidencia['puntaje'] < $umbral) {
            return null;
        }
        $estudiante = $this->fila('SELECT e.*, c.nombre AS carrera FROM huellas h
                                   JOIN estudiantes e ON e.id_estudiante = h.id_estudiante
                                   JOIN carreras c ON c.id_carrera = e.id_carrera
                                   WHERE h.id_huella = ?', [$coincidencia['id_huella']]);
        return $estudiante ? ['estudiante' => $estudiante, 'puntaje' => $coincidencia['puntaje']] : null;
    }
}
