<?php
// Devolución de libros: solo con la huella del mismo estudiante que los llevó
class DevolucionController extends Controlador
{
    private $prestamos;

    public function __construct()
    {
        $this->exigirRol('BIBLIOTECARIO');
        $this->prestamos = new Prestamo();
    }

    public function index()
    {
        $estudiante = null;
        $sit = null;
        $estadoTexto = 'SIN IDENTIFICAR';
        $estadoOk = false;
        $lista = [];
        if (!empty($_SESSION['devolucion_estudiante'])) {
            $estudiante = (new Estudiante())->buscar($_SESSION['devolucion_estudiante']);
            $sit = $this->prestamos->situacion($estudiante['id_estudiante']);
            $lista = $sit['activos'];
            $estadoOk = $sit['cantidad'] > 0 && $sit['atrasados'] == 0;
            $estadoTexto = $sit['cantidad'] == 0 ? 'NO TIENE LIBROS POR DEVOLVER'
                : $sit['cantidad'] . ' LIBRO(S) POR DEVOLVER' . ($sit['atrasados'] ? ' · ' . $sit['atrasados'] . ' con atraso' : '');
        }
        $this->vista('devoluciones/index', [
            'modulo' => 'devoluciones',
            'lectura' => $estudiante ? '' : $this->nuevaLectura(),
            'estudiante' => $estudiante,
            'sit' => $sit,
            'estadoTexto' => $estadoTexto,
            'estadoOk' => $estadoOk,
            'prestamos' => $lista,
            'multaDia' => (float) (new Parametro())->obtener('MULTA_POR_DIA'),
        ]);
    }

    /** Lectura automática: el estudiante coloca el dedo; se acepta solo si el puntaje llega al umbral. */
    public function escuchar()
    {
        $this->leerHuella('DEVOLUCION', 'devolucion');
    }

    /** Vuelve a mostrar la página con el estudiante ya identificado. */
    public function mostrar()
    {
        $this->exigirPost('c=devoluciones');
        $this->index();
    }

    public function cancelar()
    {
        unset($_SESSION['devolucion_estudiante'], $_SESSION['devolucion_puntaje']);
        $this->redirigir('c=devoluciones');
    }

    public function confirmar()
    {
        $this->exigirPost('c=devoluciones');
        $id = $_SESSION['devolucion_estudiante'] ?? null;
        if (!$id) {
            $this->avisar('Ingrese la huella del estudiante.', 'error');
            $this->redirigir('c=devoluciones');
        }
        try {
            $r = $this->prestamos->devolver($id, array_map('intval', $_POST['prestamos'] ?? []), $_SESSION['devolucion_puntaje'] ?? null);
            $this->avisar("Devolución registrada.\n\n• " . implode("\n• ", $r['codigos'])
                . ($r['multa'] > 0 ? "\n\nMulta generada: " . dinero($r['multa']) . ' (se paga en Atrasos).' : "\n\nSin multa."));
            unset($_SESSION['devolucion_estudiante'], $_SESSION['devolucion_puntaje']);
        } catch (Exception $ex) {
            // Se vuelve a mostrar la página con el mismo estudiante
            $this->avisarError($ex);
            $this->index();
            return;
        }
        $this->redirigir('c=devoluciones');
    }
}
