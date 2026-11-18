<?php
// Clase base de los controladores: vistas, permisos, mensajes y navegación
class Controlador
{
    /** Muestra una vista de la carpeta views/ con los datos que se le pasan. */
    protected function vista($nombre, $datos = [])
    {
        extract($datos);
        $usuario = $this->usuario();
        $aviso = $_SESSION['aviso'] ?? null;
        unset($_SESSION['aviso']);
        // Modo pedido para los formularios CRUD (Nuevo / Editar), como ControlCrud del sistema de escritorio
        $modoPedido = $_POST['modo'] ?? $_GET['modo'] ?? '';
        // Lo que se buscó en la tabla (null = todavía no se buscó), si el controlador no lo mandó
        if (!array_key_exists('buscado', $datos)) {
            $buscado = $this->buscado();
        }
        require __DIR__ . '/../views/' . $nombre . '.php';
    }

    /**
     * Va a otra página: $ruta es lo que va después de "index.php?", por ejemplo "c=libros".
     * Después de guardar o eliminar se vuelve a la misma búsqueda; con $mantenerBusqueda = false (Cancelar) se limpia.
     */
    protected function redirigir($ruta, $mantenerBusqueda = true)
    {
        if ($mantenerBusqueda && isset($_POST['buscar']) && strpos($ruta, 'buscar=') === false) {
            $ruta .= '&buscar=' . urlencode(trim($_POST['buscar']));
        }
        header('Location: index.php?' . $ruta);
        exit;
    }

    /** Guarda un mensaje para mostrarlo en la siguiente página. */
    protected function avisar($texto, $tipo = 'exito')
    {
        $_SESSION['aviso'] = ['texto' => $texto, 'tipo' => $tipo];
    }

    /** Muestra el error como un mensaje entendible. */
    protected function avisarError(Exception $ex)
    {
        $texto = $ex instanceof mysqli_sql_exception ? Modelo::mensajeError($ex) : $ex->getMessage();
        $this->avisar($texto, 'error');
    }

    protected function usuario()
    {
        return $_SESSION['usuario'] ?? null;
    }

    /** Solo deja pasar al personal con alguno de los roles indicados. */
    protected function exigirRol($roles)
    {
        $u = $this->usuario();
        if (!$u) {
            $this->redirigir('c=login');
        }
        if (!in_array($u['rol'], (array) $roles)) {
            $this->avisar('No tiene permiso para entrar a esa opción.', 'error');
            $this->redirigir('c=menu');
        }
    }

    /** Las acciones que guardan o eliminan solo se aceptan desde un formulario (POST). */
    protected function exigirPost($volver)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir($volver);
        }
    }

    // ---------- Lectura automática de la huella (sin AJAX) ----------
    // La página tiene un marco oculto (iframe) que le pide la lectura al servidor con la acción "escuchar".
    // El servidor espera el dedo unos segundos y responde una mini página que le avisa a la página qué pasó.

    /** Empieza una lectura para la página que se va a mostrar. Devuelve su clave. */
    protected function nuevaLectura()
    {
        return $_SESSION['lectura'] = uniqid();
    }

    /** true si la página que pidió la lectura sigue abierta (no se cambió de página). */
    protected function lecturaVigente()
    {
        return $this->get('t') !== '' && $this->get('t') === ($_SESSION['lectura'] ?? '');
    }

    /**
     * Responde al marco oculto y termina. Sin datos no le avisa nada a la página.
     * Con $estado (CONECTADO, DESCONECTADO o SIN_DRIVER) también le dice cómo está el lector y si cambió
     * desde la última vez, para que la página muestre "Lector conectado" o "Lector desconectado".
     */
    protected function responderLector($respuesta = null, $estado = null)
    {
        if ($respuesta && $estado) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $anterior = $_SESSION['lector_estado'] ?? null;
            $_SESSION['lector_estado'] = $estado;
            $respuesta['lector'] = $estado;
            $respuesta['cambio'] = $anterior !== null && ($anterior === 'CONECTADO') !== ($estado === 'CONECTADO');
        }
        if ($respuesta) {
            require __DIR__ . '/../views/plantillas/lector.php';
        }
        exit;
    }

    /** Préstamos y Devoluciones: espera el dedo y deja identificado al estudiante dueño de la huella. */
    protected function leerHuella($operacion, $clave)
    {
        if (!$this->lecturaVigente()) {
            $this->responderLector();
        }
        // Se suelta la sesión mientras se espera el dedo, para que las otras páginas no se queden esperando
        session_write_close();
        try {
            $lectura = (new Huella())->leer($operacion);
        } catch (Exception $ex) {
            if ($ex->getCode() === HuellaLector::CANCELADA) {
                $this->responderLector(); // otra página pidió el lector
            }
            // Nadie puso el dedo: se sigue escuchando. Otro problema: se avisa y se vuelve a intentar.
            $this->responderLector($ex->getCode() === HuellaLector::SIN_DEDO
                ? ['accion' => 'seguir'] : ['accion' => 'aviso', 'texto' => $ex->getMessage()],
                $ex->getCode() === HuellaLector::SIN_LECTOR ? 'DESCONECTADO' : 'CONECTADO');
        }
        session_start();
        if (!$this->lecturaVigente()) {
            $this->responderLector();
        }
        $_SESSION[$clave . '_estudiante'] = $lectura['estudiante']['id_estudiante'];
        $_SESSION[$clave . '_puntaje'] = $lectura['puntaje'];
        $this->responderLector(['accion' => 'identificado'], 'CONECTADO');
    }

    /** Lo que se escribió para buscar en la tabla, o null si todavía no se buscó. */
    protected function buscado()
    {
        $texto = $_GET['buscar'] ?? $_POST['buscar'] ?? null;
        return $texto === null ? null : trim($texto);
    }

    /**
     * Resultado de una búsqueda para la vista: como máximo Modelo::MAXIMO_BUSQUEDA filas.
     * Devuelve [filas, hayMas]. Sin búsqueda (null) la tabla queda vacía.
     */
    protected function resultado($buscar)
    {
        $buscado = $this->buscado();
        if ($buscado === null) {
            return [[], false];
        }
        $filas = $buscar($buscado);
        return [array_slice($filas, 0, Modelo::MAXIMO_BUSQUEDA), count($filas) > Modelo::MAXIMO_BUSQUEDA];
    }

    protected function post($campo)
    {
        return trim($_POST[$campo] ?? '');
    }

    protected function get($campo, $defecto = '')
    {
        return trim($_GET[$campo] ?? $defecto);
    }
}
