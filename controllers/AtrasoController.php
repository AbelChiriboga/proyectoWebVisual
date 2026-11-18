<?php
// Préstamos atrasados y multas pendientes; aquí se registra el pago de una o varias multas (bibliotecario)
class AtrasoController extends Controlador
{
    public function __construct()
    {
        $this->exigirRol('BIBLIOTECARIO');
    }

    public function index()
    {
        $ver = $this->get('ver') === 'multas' ? 'multas' : 'atrasados';
        $multas = new Multa();
        // Solo los que coinciden con la búsqueda (no se cargan todos); los totales se cuentan en la base
        list($filas, $hayMas) = $this->resultado($ver === 'atrasados' ? [$multas, 'atrasados'] : [$multas, 'pendientes']);
        $resumen = $ver === 'atrasados' ? $multas->resumenAtrasados() : $multas->resumenPendientes();
        $this->vista('atrasos/index', [
            'ver' => $ver,
            'filas' => $filas,
            'hayMas' => $hayMas,
            'cantidad' => $resumen['cantidad'],
            'total' => $resumen['total'],
        ]);
    }

    public function pagar()
    {
        $this->exigirPost('c=atrasos&ver=multas');
        try {
            $r = (new Multa())->pagar($_POST['multas'] ?? []);
            $this->avisar("Pago registrado.\n" . $r['cantidad'] . ' multa(s) de ' . $r['estudiante'] . ', total ' . dinero($r['total']) . '.');
        } catch (Exception $ex) {
            $this->avisarError($ex);
        }
        $this->redirigir('c=atrasos&ver=multas');
    }
}
