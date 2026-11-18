<?php
// Plantilla de los reportes en PDF hechos con FPDF:
// encabezado verde con logo, filtros, tabla con encabezados (se repiten en cada hoja), totales y número de página.
require_once __DIR__ . '/../../fpdf/fpdf.php';

class ReportePDF extends FPDF
{
    private $titulo;
    private $filtros;
    private $usuario;
    private $columnas = [];   // [texto => ancho] de la tabla actual

    /** $orientacion: 'P' vertical o 'L' horizontal (para tablas con muchas columnas). */
    public function __construct($titulo, $filtros, $usuario, $orientacion = 'P')
    {
        parent::__construct($orientacion, 'mm', 'A4');
        $this->titulo = $titulo;
        $this->filtros = $filtros;
        $this->usuario = $usuario;
        $this->SetTitle($titulo, true);
        $this->SetAuthor('Biblioteca Digital FISEI', true);
        $this->AliasNbPages();
        $this->SetMargins(10, 10, 10);
        $this->SetAutoPageBreak(true, 15);
        $this->AddPage();
    }

    /** Encabezado de cada hoja. */
    public function Header()
    {
        $ancho = $this->GetPageWidth() - 20;
        $this->SetFillColor(0, 153, 153);
        $this->Rect(10, 10, $ancho, 22, 'F');
        $this->Image(__DIR__ . '/../assets/img/logo.png', 12, 11, 20);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(35, 13);
        $this->SetFont('Helvetica', 'B', 15);
        $this->Cell($ancho - 85, 7, self::texto('BIBLIOTECA DIGITAL FISEI'));
        $this->SetXY(35, 21);
        $this->SetFont('Helvetica', '', 11);
        $this->Cell($ancho - 85, 6, self::texto(mb_strtoupper($this->titulo, 'UTF-8')));
        $this->SetFont('Helvetica', '', 8);
        $this->SetXY(10 + $ancho - 62, 15);
        $this->Cell(60, 5, self::texto('Generado: ' . date('d/m/Y H:i')), 0, 2, 'R');
        $this->Cell(60, 5, self::texto('Por: ' . $this->usuario), 0, 0, 'R');

        $this->SetXY(10, 34);
        $this->SetTextColor(90, 105, 115);
        $this->SetFont('Helvetica', 'I', 9);
        $this->Cell($ancho, 6, self::texto('Filtros: ' . $this->filtros), 0, 1);
        $this->Ln(1);
        if ($this->columnas) {
            $this->encabezadoTabla();
        }
    }

    /** Pie de cada hoja con el número de página. */
    public function Footer()
    {
        $this->SetY(-12);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(90, 105, 115);
        $this->Cell(0, 6, self::texto('Biblioteca Digital FISEI · Sistema de préstamos biométrico'), 0, 0, 'L');
        $this->Cell(0, 6, self::texto('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    /** Empieza una tabla: $columnas = [texto => ancho en mm]. */
    public function tabla($columnas)
    {
        $this->columnas = $columnas;
        $this->encabezadoTabla();
    }

    /** Una fila de la tabla (las filas pares van con fondo gris claro). */
    public function fila($valores, $par)
    {
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(31, 43, 51);
        $this->SetFillColor(246, 248, 249);
        $i = 0;
        foreach ($this->columnas as $ancho) {
            $this->Cell($ancho, 6, $this->recortar((string) ($valores[$i] ?? ''), $ancho), 'B', 0, 'L', $par);
            $i++;
        }
        $this->Ln();
    }

    /** Título de un grupo dentro de la tabla (por ejemplo, la categoría). */
    public function grupo($texto)
    {
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(0, 122, 122);
        $this->Cell(array_sum($this->columnas), 7, self::texto($texto), 0, 1);
    }

    /** Mensaje cuando la tabla no tiene datos. */
    public function vacio($texto)
    {
        $this->SetFont('Helvetica', 'I', 9);
        $this->SetTextColor(90, 105, 115);
        $this->Cell(array_sum($this->columnas), 10, self::texto($texto), 0, 1, 'C');
    }

    /** Línea final de totales: ['Total: 5', 'Activos: 2', ...]. */
    public function totales($partes)
    {
        $this->Ln(3);
        $this->SetDrawColor(0, 153, 153);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Helvetica', 'B', 9.5);
        $this->SetTextColor(31, 43, 51);
        $this->Cell(0, 6, self::texto(implode('     ·     ', $partes)), 0, 1);
    }

    /** Muestra el PDF en el navegador. */
    public function mostrar($archivo)
    {
        $this->Output('I', $archivo . '.pdf', true);
    }

    private function encabezadoTabla()
    {
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetFillColor(224, 242, 241);
        $this->SetTextColor(31, 43, 51);
        $this->SetDrawColor(200, 211, 214);
        $this->SetLineWidth(0.2);
        foreach ($this->columnas as $texto => $ancho) {
            $this->Cell($ancho, 7, self::texto($texto), 'B', 0, 'L', true);
        }
        $this->Ln();
    }

    /** Corta el texto con "..." si no entra en la celda. */
    private function recortar($texto, $ancho)
    {
        $texto = self::texto($texto);
        if ($this->GetStringWidth($texto) <= $ancho - 2) {
            return $texto;
        }
        while ($texto !== '' && $this->GetStringWidth($texto . '...') > $ancho - 2) {
            $texto = substr($texto, 0, -1);
        }
        return $texto . '...';
    }

    /** FPDF usa las fuentes estándar (Windows-1252): se convierten tildes y ñ desde UTF-8. */
    public static function texto($texto)
    {
        $convertido = @iconv('UTF-8', 'windows-1252//TRANSLIT', (string) $texto);
        return $convertido === false ? (string) $texto : $convertido;
    }
}
