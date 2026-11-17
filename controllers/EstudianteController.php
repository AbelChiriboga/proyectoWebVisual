<?php
// Gestión de estudiantes con su huella (solo el administrador)
class EstudianteController extends Controlador
{
    private $estudiantes;

    public function __construct()
    {
        $this->exigirRol('ADMIN');
        $this->estudiantes = new Estudiante();
    }

    /** Lista y formulario. $datos trae lo escrito cuando hubo un error al guardar. */
    public function index($datos = null)
    {
        if ($datos === null) {
            // Formulario nuevo: se olvidan las huellas capturadas que no se guardaron
            unset($_SESSION['huellas_capturadas']);
        }
        unset($_SESSION['registro']);
        $datos = $datos ?? Estudiante::VACIO;
        $editando = $datos['id_estudiante'] !== '';
        $registradas = $editando ? (new Huella())->dedosRegistrados($datos['id_estudiante']) : [];

        // Sin préstamos se elimina; con historial solo se desactiva o se activa
        $retiro = 'Eliminar';
        if ($editando && $this->estudiantes->tieneHistorial($datos['id_estudiante'])) {
            $retiro = $datos['estado'] === 'ACTIVO' ? 'Desactivar' : 'Activar';
        }
        $nombre = $datos['nombres'] . ' ' . $datos['apellidos'];
        // Solo los estudiantes que coinciden con la búsqueda (no se cargan todos)
        list($lista, $hayMas) = $this->resultado([$this->estudiantes, 'listar']);
        $preguntas = [
            'Eliminar' => '¿Eliminar a ' . $nombre . '?',
            'Desactivar' => $datos['nombres'] . ' tiene historial de préstamos y no se puede eliminar. ¿Desea desactivarlo?',
            'Activar' => '¿Desea activar a ' . $nombre . '?',
        ];

        $this->vista('estudiantes/index', [
            'datos' => $datos,
            'editando' => $editando,
            'registradas' => $registradas,
            'retiro' => $retiro,
            'pregunta' => $preguntas[$retiro],
            'dedos' => Huella::DEDOS,
            'lectura' => $this->nuevaLectura(),
            'dedoElegido' => $_POST['dedo'] ?? Huella::primerDedoLibre($registradas),
            'capturadas' => array_keys($this->capturadas($datos)),
            'carreras' => $this->estudiantes->carreras(),
            'estudiantes' => $lista,
            'hayMas' => $hayMas,
        ]);
    }

    public function editar()
    {
        unset($_SESSION['huellas_capturadas']);
        $this->index($this->estudiantes->buscar($this->get('id')) ?: null);
    }

    /**
     * Registro automático de la huella (lo pide el marco oculto de la página en Nuevo o Editar).
     * Cada llamada toma UNA muestra del dedo elegido; con 3 muestras del mismo dedo la huella queda
     * capturada y lista para guardarse con el estudiante.
     */
    public function escuchar()
    {
        $dedo = $this->get('dedo');
        if (!$this->lecturaVigente() || !isset(Huella::DEDOS[$dedo])) {
            $this->responderLector();
        }
        $datos = ['id_estudiante' => $this->get('id')];
        if (isset($_GET['otra'])) {
            // "Registrar otra vez": se borra la captura de este dedo y se vuelve a leer
            unset($_SESSION['huellas_capturadas']['dedos'][$dedo], $_SESSION['registro']);
        }
        $registro = $this->registro($dedo, $this->clave($datos));
        $_SESSION['registro'] = $registro;
        $listas = count($registro['muestras']);

        // Se suelta la sesión mientras se espera el dedo, para que las otras páginas no se queden esperando
        session_write_close();
        try {
            $id = $datos['id_estudiante'] === '' ? null : (int) $datos['id_estudiante'];
            $r = (new Huella())->muestra($id, array_map('base64_decode', $registro['muestras']), $dedo, $this->capturadas($datos));
        } catch (Exception $ex) {
            if ($ex->getCode() === HuellaLector::CANCELADA) {
                $this->responderLector(); // se cambió de dedo o de página
            }
            if ($ex->getCode() === HuellaLector::SIN_DEDO) {
                $this->responderLector(['accion' => 'seguir', 'muestras' => $listas], 'CONECTADO');
            }
            // Falló con el dedo puesto: se empieza de nuevo desde la primera muestra
            session_start();
            unset($_SESSION['registro']);
            $this->responderLector(['accion' => $ex->getCode() === Huella::DUPLICADA ? 'alerta' : 'aviso',
                                    'texto' => $ex->getMessage(), 'muestras' => 0],
                                   $ex->getCode() === HuellaLector::SIN_LECTOR ? 'DESCONECTADO' : 'CONECTADO');
        }
        session_start();
        if (!$this->lecturaVigente() || ($_SESSION['registro'] ?? null) != $registro) {
            $this->responderLector(); // se cambió de dedo o de página mientras se leía
        }
        if (isset($r['plantilla'])) {
            unset($_SESSION['registro']);
            $_SESSION['huellas_capturadas']['para'] = $this->clave($datos);
            $_SESSION['huellas_capturadas']['dedos'][$dedo] = base64_encode($r['plantilla']);
            $this->responderLector(['accion' => 'capturada', 'muestras' => HuellaLector::MUESTRAS_REGISTRO,
                                    'imagen' => $this->imagen($r)], 'CONECTADO');
        }
        $registro['muestras'] = array_map('base64_encode', $r['muestras']);
        $registro['desde'] = time();
        $_SESSION['registro'] = $registro;
        $listas = count($registro['muestras']);
        $this->responderLector(['accion' => 'seguir', 'muestras' => $listas, 'imagen' => $this->imagen($r),
            'texto' => 'Coloque el dedo otra vez (' . ($listas + 1) . ' de ' . HuellaLector::MUESTRAS_REGISTRO . ').'], 'CONECTADO');
    }

    /** La imagen del dedo leído, lista para ponerla en un <img> (o null si el lector no la dio). */
    private function imagen($r)
    {
        return empty($r['imagen']) ? null : 'data:image/bmp;base64,' . base64_encode($r['imagen']);
    }

    /** Muestras ya tomadas del dedo. Se olvidan si es otro dedo u otro estudiante, o si pasó el tiempo sin dedo. */
    private function registro($dedo, $para)
    {
        $r = $_SESSION['registro'] ?? null;
        if ($r && $r['dedo'] === $dedo && $r['para'] === $para && time() - $r['desde'] <= HuellaLector::SEGUNDOS_DEDO) {
            return $r;
        }
        return ['dedo' => $dedo, 'para' => $para, 'muestras' => [], 'desde' => time()];
    }

    public function guardar()
    {
        $this->exigirPost('c=estudiantes');
        try {
            $this->estudiantes->guardar($_POST, $this->capturadas($this->datosEscritos()));
            unset($_SESSION['huellas_capturadas']);
            $this->avisar('Estudiante guardado.');
            $this->redirigir('c=estudiantes');
        } catch (Exception $ex) {
            // Se vuelve a mostrar el formulario con lo que se escribió
            $this->avisarError($ex);
            $this->index($this->datosEscritos());
        }
    }

    /** Lo que se escribió en el formulario. */
    private function datosEscritos()
    {
        return array_merge(Estudiante::VACIO, array_intersect_key($_POST, Estudiante::VACIO));
    }

    /** Las huellas capturadas se guardan solo para el mismo estudiante del formulario. */
    private function clave($datos)
    {
        return $datos['id_estudiante'] === '' ? 'nuevo' : 'id' . $datos['id_estudiante'];
    }

    /** Huellas capturadas y aún no guardadas de este estudiante: [dedo => plantilla]. */
    private function capturadas($datos)
    {
        $c = $_SESSION['huellas_capturadas'] ?? null;
        if (!$c || ($c['para'] ?? '') !== $this->clave($datos)) {
            return [];
        }
        return array_map('base64_decode', $c['dedos'] ?? []);
    }

    public function retirar()
    {
        $this->exigirPost('c=estudiantes');
        try {
            $this->avisar($this->estudiantes->retirar($this->post('id_estudiante')));
        } catch (Exception $ex) {
            $this->avisarError($ex);
        }
        $this->redirigir('c=estudiantes');
    }
}
