<?php
// Reportes en PDF generados con FPDF (se abren en otra pestaña del navegador)
class ReporteController extends Controlador
{
    public function index()
    {
        $this->redirigir('c=menu');
    }

    /** Filtros del inventario. */
    public function inventario()
    {
        $this->exigirRol(['ADMIN', 'BIBLIOTECARIO']);
        $this->vista('reportes/inventario', [
            'categorias' => (new Categoria())->opciones(),
            'estados' => Ejemplar::ESTADOS,
        ]);
    }

    /** PDF del inventario de ejemplares agrupados por categoría. */
    public function inventarioPdf()
    {
        $this->exigirRol(['ADMIN', 'BIBLIOTECARIO']);
        $categoria = $this->get('categoria');
        $estado = $this->get('estado');
        $filas = (new Ejemplar())->inventario($categoria, $estado);

        $nombreCategoria = 'Todas';
        foreach ((new Categoria())->opciones() as $c) {
            if ($c['id_categoria'] == $categoria) {
                $nombreCategoria = $c['nombre'];
            }
        }
        $this->vista('reportes/inventario_pdf', [
            'filtrosTexto' => 'Categoría: ' . $nombreCategoria . '   ·   Estado: ' . (Ejemplar::ESTADOS[$estado] ?? 'Todos'),
            'filas' => $filas,
            'estados' => Ejemplar::ESTADOS,
            'conteo' => array_count_values(array_column($filas, 'estado')),
        ]);
    }

    /** Filtros del historial. */
    public function historial()
    {
        $this->exigirRol(['ADMIN', 'BIBLIOTECARIO']);
        $this->vista('reportes/historial', ['carreras' => (new Estudiante())->carreras()]);
    }

    /** PDF del historial de préstamos entre dos fechas, por carrera y estado. */
    public function historialPdf()
    {
        $this->exigirRol(['ADMIN', 'BIBLIOTECARIO']);
        $desde = $this->get('desde', date('Y-m-d', strtotime('-30 days')));
        $hasta = $this->get('hasta', date('Y-m-d'));
        $carrera = $this->get('carrera');
        $estado = $this->get('estado');
        $filas = $desde > $hasta ? [] : (new Prestamo())->historial($desde, $hasta, $carrera, $estado);

        $activos = 0;
        foreach ($filas as $f) {
            if ($f['estado'] === 'ACTIVO') {
                $activos++;
            }
        }
        $nombreCarrera = 'Todas';
        foreach ((new Estudiante())->carreras() as $c) {
            if ($c['id_carrera'] == $carrera) {
                $nombreCarrera = $c['nombre'];
            }
        }
        $nombreEstado = $estado === 'ACTIVO' ? 'Activo' : ($estado === 'DEVUELTO' ? 'Devuelto' : 'Todos');
        $this->vista('reportes/historial_pdf', [
            'filtrosTexto' => 'Desde: ' . fecha($desde) . '   ·   Hasta: ' . fecha($hasta) . '   ·   Carrera: '
                . $nombreCarrera . '   ·   Estado: ' . $nombreEstado
                . ($desde > $hasta ? '   (la fecha Desde no puede ser mayor que Hasta)' : ''),
            'filas' => $filas,
            'activos' => $activos,
        ]);
    }

    /** PDF de préstamos atrasados con la multa estimada a hoy. */
    public function atrasos()
    {
        $this->exigirRol('BIBLIOTECARIO');
        $this->vista('reportes/atrasos_pdf', [
            'filtrosTexto' => 'Multa por día: ' . dinero((new Parametro())->obtener('MULTA_POR_DIA'))
                . '   ·   Calculado al ' . date('d/m/Y'),
            'filas' => (new Multa())->atrasados(),
        ]);
    }

    /** PDF de multas pendientes de pago. */
    public function multas()
    {
        $this->exigirRol('BIBLIOTECARIO');
        $this->vista('reportes/multas_pdf', [
            'filtrosTexto' => 'Solo multas sin pagar',
            'filas' => (new Multa())->pendientes(),
        ]);
    }
}
