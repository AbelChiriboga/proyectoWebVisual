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
     * $marcados son los ejemplares ya agregados al préstamo y $buscado lo que se escribió en la ventana "Agregar libros".
     * No se cargan todos los libros: solo los agregados y los que coinciden con la búsqueda.
     * $abrirBusqueda = true vuelve a mostrar abierta la ventana "Agregar libros" (después de buscar o limpiar).
     */
    public function index($marcados = [], $buscado = null, $abrirBusqueda = false)
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
            'abrirBusqueda' => $abrirBusqueda,
            'devolverHasta' => $this->prestamos->fechaLimiteHoy(),
        ]);
    }

    /** Lectura automática: el estudiante coloca el dedo; se acepta solo si el puntaje llega al umbral. */
    public function escuchar()
    {
        $this->leerHuella('PRESTAMO', 'prestamo');
    }

    /** Vuelve a mostrar la página con el estudiante ya identificado y los libros agregados (y la ventana si estaba abierta). */
    public function mostrar()
    {
        $this->exigirPost('c=prestamos');
        $this->index($this->marcados(), $this->post('buscar'), $this->post('busqueda_abierta') === '1');
    }

    /** Ventana "Agregar libros", lupa: trae de la base solo los ejemplares disponibles que coinciden. */
    public function buscar()
    {
        $this->exigirPost('c=prestamos');
        $this->index($this->marcados(), $this->post('buscar'), true);
    }

    /** Ventana "Agregar libros", Limpiar: borra lo buscado (la ventana sigue abierta). */
    public function limpiar()
    {
        $this->exigirPost('c=prestamos');
        $this->index($this->marcados(), '', true);
    }

    /** Ventana "Agregar libros", botón Agregar: suma al préstamo los ejemplares elegidos y cierra la ventana. */
    public function agregar()
    {
        $this->exigirPost('c=prestamos');
        $nuevos = array_map('intval', $_POST['agregar'] ?? []);
        if (!$nuevos) {
            $this->avisar('Seleccione al menos un libro.', 'error');
            $this->index($this->marcados(), $this->post('buscar'), true);
            return;
        }
        $this->index(array_values(array_unique(array_merge($this->marcados(), $nuevos))), '');
    }

    /** Botón Quitar de una fila: saca ese ejemplar del préstamo. */
    public function quitar()
    {
        $this->exigirPost('c=prestamos');
        $this->index(array_values(array_diff($this->marcados(), [(int) $this->post('quitar')])), '');
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
