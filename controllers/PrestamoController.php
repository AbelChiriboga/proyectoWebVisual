<?php
// Préstamo de libros: el estudiante se identifica con su huella en el lector ZKTeco
class PrestamoController extends Controlador
{
    private $prestamos;

    public function __construct()
    {
        $this->exigirRol('BIBLIOTECARIO');
        $this->prestamos = new Prestamo();
    }

    /**
     * $marcados son los ejemplares que el bibliotecario ya había marcado y $buscado lo que escribió para buscar.
     * No se cargan todos los libros: solo los marcados y los que coinciden con la búsqueda.
     */
    public function index($marcados = [], $buscado = null)
    {
        $buscado = $buscado ?? $this->buscado() ?? '';
        $ejemplares = new Ejemplar();
        $elegidos = $ejemplares->disponiblesPorId($marcados);
        $encontrados = [];
        foreach ($buscado === '' ? [] : $ejemplares->buscarDisponibles($buscado) as $ej) {
            if (!in_array($ej['id_ejemplar'], $marcados)) {
                $encontrados[] = $ej;
            }
        }
        $hayMas = count($encontrados) > Ejemplar::MAXIMO_BUSQUEDA;

        $estudiante = null;
        $sit = null;
        $estadoTexto = 'SIN IDENTIFICAR';
        $estadoOk = false;
        $maximo = (int) (new Parametro())->obtener('MAX_PRESTAMOS');
        if (!empty($_SESSION['prestamo_estudiante'])) {
            $estudiante = (new Estudiante())->buscar($_SESSION['prestamo_estudiante']);
            $sit = $this->prestamos->situacion($estudiante['id_estudiante']);
            $motivo = $this->prestamos->motivoBloqueo($estudiante, $sit, 1);
            $estadoOk = $motivo === null;
            $estadoTexto = $motivo ?? 'PUEDE LLEVAR ' . $sit['cupos'] . ' LIBRO(S)';
            $maximo = $sit['cupos'];
        }
        $this->vista('prestamos/index', [
            'modulo' => 'prestamos',
            'lectura' => $estudiante ? '' : $this->nuevaLectura(),
            'estudiante' => $estudiante,
            'sit' => $sit,
            'estadoTexto' => $estadoTexto,
            'estadoOk' => $estadoOk,
            'maximo' => $maximo,
            'buscado' => $buscado,
            'elegidos' => $elegidos,
            'encontrados' => array_slice($encontrados, 0, Ejemplar::MAXIMO_BUSQUEDA),
            'hayMas' => $hayMas,
            'tope' => Ejemplar::MAXIMO_BUSQUEDA,
            'devolverHasta' => $this->prestamos->fechaLimiteHoy(),
        ]);
    }

    /** Lectura automática: el estudiante coloca el dedo; se acepta solo si el puntaje llega al umbral. */
    public function escuchar()
    {
        $this->leerHuella('PRESTAMO', 'prestamo');
    }

    /** Vuelve a mostrar la página con el estudiante ya identificado, los libros marcados y la búsqueda. */
    public function mostrar()
    {
        $this->exigirPost('c=prestamos');
        $this->index($this->marcados(), $this->post('buscar'));
    }

    /** Botón Limpiar: borra lo buscado (conserva los libros marcados y el estudiante identificado). */
    public function limpiar()
    {
        $this->exigirPost('c=prestamos');
        $this->index($this->marcados(), '');
    }

    /** Botón Buscar: trae de la base solo los ejemplares disponibles que coinciden (conserva los marcados). */
    public function buscar()
    {
        $this->mostrar();
    }

    public function cancelar()
    {
        unset($_SESSION['prestamo_estudiante'], $_SESSION['prestamo_puntaje']);
        $this->redirigir('c=prestamos');
    }

    public function confirmar()
    {
        $this->exigirPost('c=prestamos');
        $id = $_SESSION['prestamo_estudiante'] ?? null;
        if (!$id) {
            // Sin huella no se puede prestar: se limpian los libros marcados
            $this->avisar('Ingrese la huella del estudiante.', 'error');
            $this->redirigir('c=prestamos');
        }
        try {
            $r = $this->prestamos->registrar($id, $this->marcados(), $this->usuario()['cedula'], $_SESSION['prestamo_puntaje'] ?? null);
            $texto = "Préstamo registrado.\n";
            foreach ($r['detalle'] as $d) {
                $texto .= "\n• " . $d['codigo_inventario'] . '  ' . $d['titulo'];
            }
            $this->avisar($texto . "\n\nDevolver hasta: " . fecha($r['limite']));
            unset($_SESSION['prestamo_estudiante'], $_SESSION['prestamo_puntaje']);
            $this->redirigir('c=prestamos');
        } catch (Exception $ex) {
            $this->avisarError($ex);
            $this->index($this->marcados(), $this->post('buscar'));
        }
    }

    private function marcados()
    {
        return array_map('intval', $_POST['ejemplares'] ?? []);
    }
}
