<?php
// Controlador frontal: todas las páginas entran por aquí.
// La dirección indica el controlador y la acción: index.php?c=libros&a=guardar
session_start();
date_default_timezone_set('America/Guayaquil');

require_once 'models/Conexion.php';
require_once 'views/plantillas/ayudas.php';

// Carga automática de clases: primero busca en models/ y luego en controllers/
spl_autoload_register(function ($clase) {
    foreach (['models', 'controllers'] as $carpeta) {
        $archivo = __DIR__ . '/' . $carpeta . '/' . $clase . '.php';
        if (is_file($archivo)) {
            require_once $archivo;
            return;
        }
    }
});

// Nombre en la dirección => clase del controlador
$rutas = [
    'login' => 'LoginController',
    'menu' => 'MenuController',
    'estudiantes' => 'EstudianteController',
    'usuarios' => 'UsuarioController',
    'parametros' => 'ParametroController',
    'bitacora' => 'BitacoraController',
    'autores' => 'AutorController',
    'categorias' => 'CategoriaController',
    'libros' => 'LibroController',
    'ejemplares' => 'EjemplarController',
    'prestamos' => 'PrestamoController',
    'devoluciones' => 'DevolucionController',
    'atrasos' => 'AtrasoController',
    'consulta' => 'ConsultaController',
    'panel' => 'PanelController',
    'reportes' => 'ReporteController',
    'lector' => 'LectorController',
];

$c = $_GET['c'] ?? '';
// Sin una página válida siempre se abre el login (como al abrir el sistema de escritorio)
if (!isset($rutas[$c])) {
    $c = 'login';
}
$controlador = new $rutas[$c]();

// Solo se pueden llamar las acciones públicas del controlador
$a = $_GET['a'] ?? 'index';
if ($a === '' || $a[0] === '_' || !is_callable([$controlador, $a])) {
    $a = 'index';
}
// (La revisión del estado del lector, c=lector, no cuenta como cambio de página)
if ($c !== 'lector') {
    // Igual que al cerrar una ventana en el sistema de escritorio: al salir de una página (o al volver a abrirla)
    // se olvida lo que quedó a medias, y la página empieza limpia.
    $abrePagina = $a === 'index' && $_SERVER['REQUEST_METHOD'] === 'GET';
    if ($c !== 'prestamos' || $abrePagina) {
        unset($_SESSION['prestamo_estudiante'], $_SESSION['prestamo_puntaje']);
    }
    if ($c !== 'devoluciones' || $abrePagina) {
        unset($_SESSION['devolucion_estudiante'], $_SESSION['devolucion_puntaje']);
    }
    if ($c !== 'estudiantes') {
        unset($_SESSION['huellas_capturadas'], $_SESSION['registro']);
    }
    if ($c !== 'panel') {
        unset($_SESSION['estudiante']);
    }
    // Libro nuevo con ejemplares pendientes: se olvida al salir, al abrir Libros otra vez o al Cancelar
    if (!in_array($c, ['libros', 'ejemplares', 'autores', 'categorias']) || ($c === 'libros' && $abrePagina)) {
        unset($_SESSION['libro_nuevo'], $_SESSION['libro_formulario'], $_SESSION['libros_buscar']);
    }
    // Al cambiar de página, la lectura de huella que quedó esperando en la página anterior deja de valer
    if ($a !== 'escuchar') {
        unset($_SESSION['lectura']);
    }
}
$controlador->$a();
