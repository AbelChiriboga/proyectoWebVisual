<?php
// Lector de huella ZKTeco REAL.
// PHP usa directamente la librería del driver de ZKTeco (libzkfp.dll) con la extensión FFI de PHP.
// Es la misma librería que usa el sistema en Java, así que las huellas guardadas sirven en los dos.
// Requisito: en C:\xampp\php\php.ini activar  extension=ffi  y  ffi.enable=true  (y reiniciar Apache).
class HuellaLector
{
    const LIBRERIA = 'libzkfp.dll';
    const TAMANO_PLANTILLA = 2048;
    const MUESTRAS_REGISTRO = 3;
    const SEGUNDOS_DEDO = 15;      // tiempo para colocar el dedo antes de empezar de nuevo el registro
    const SEGUNDOS_ESCUCHA = 4;    // cada lectura automática espera el dedo este tiempo y la página vuelve a pedirla
    const SEGUNDOS_LEVANTAR = 5;   // tiempo para levantar el dedo entre muestras
    const PAUSA = 150000;          // 0,15 segundos entre lecturas (en microsegundos)

    // Código de la excepción cuando nadie colocó el dedo (no cuenta como fallo en la bitácora)
    const SIN_DEDO = 1;
    // Código de la excepción cuando otra página pidió el lector y esta lectura ya no sirve (tampoco cuenta como fallo)
    const CANCELADA = 2;
    // Código de la excepción cuando el lector no está listo (desconectado, sin driver, ocupado...)
    const SIN_LECTOR = 5;
    // Respuestas del lector que significan "sigue conectado" (0 = leyó un dedo)
    const RESPUESTAS_CONECTADO = [0, -8, -9, -10, -12, -28];

    // Funciones del driver de ZKTeco que usa el sistema (libzkfp.h)
    const FUNCIONES = '
        typedef void* HANDLE;
        int ZKFPM_Init();
        int ZKFPM_Terminate();
        int ZKFPM_GetDeviceCount();
        HANDLE ZKFPM_OpenDevice(int index);
        int ZKFPM_CloseDevice(HANDLE hDevice);
        int ZKFPM_GetParameters(HANDLE hDevice, int nParamCode, unsigned char* paramValue, unsigned int* cbParamValue);
        int ZKFPM_AcquireFingerprint(HANDLE hDevice, unsigned char* fpImage, unsigned int cbFPImage,
                                     unsigned char* fpTemplate, unsigned int* cbTemplate);
        HANDLE ZKFPM_DBInit();
        int ZKFPM_DBFree(HANDLE hDBCache);
        int ZKFPM_DBMerge(HANDLE hDBCache, unsigned char* temp1, unsigned char* temp2, unsigned char* temp3,
                          unsigned char* regTemp, unsigned int* cbRegTemp);
        int ZKFPM_DBAdd(HANDLE hDBCache, unsigned int fid, unsigned char* fpTemplate, unsigned int cbTemplate);
        int ZKFPM_DBClear(HANDLE hDBCache);
        int ZKFPM_DBIdentify(HANDLE hDBCache, unsigned char* fpTemplate, unsigned int cbTemplate,
                             unsigned int* FID, unsigned int* score);
        int ZKFPM_DBMatch(HANDLE hDBCache, unsigned char* template1, unsigned int cbTemplate1,
                          unsigned char* template2, unsigned int cbTemplate2);
    ';

    private $zk = null;          // la librería del driver
    private $dispositivo = null; // el lector abierto
    private $base = null;        // base de huellas en la memoria del lector
    private $imagen = null;      // espacio para la imagen de la huella
    private $tamanoImagen = 0;
    private $ancho = 0;          // ancho y alto de la imagen que entrega el lector
    private $alto = 0;
    private $ultimaImagen = null; // imagen (BMP) del último dedo leído
    private $ultimoCodigo = 0;
    private $turno = null;       // candado: solo una página usa el lector a la vez
    private $pedido = '';        // identifica esta lectura; si llega otra más nueva, esta se cancela

    /** Abre el lector. Lanza una excepción con un mensaje simple si no se puede. */
    public function abrir()
    {
        $ffi = strtolower((string) ini_get('ffi.enable'));
        if (!extension_loaded('ffi') || !in_array($ffi, ['1', 'true', 'on'])) {
            throw new Exception("Active FFI en C:\\xampp\\php\\php.ini (extension=ffi y ffi.enable=true) y reinicie Apache.", self::SIN_LECTOR);
        }
        // Esta lectura pasa a ser la vigente: si otra página todavía estaba leyendo, se cancela sola
        // y aquí se espera a que suelte el lector
        $this->pedido = uniqid('', true);
        file_put_contents(self::archivo('pedido'), $this->pedido);
        $this->turno = fopen(self::archivo('lock'), 'c');
        flock($this->turno, LOCK_EX);
        try {
            $this->zk = FFI::cdef(self::FUNCIONES, self::LIBRERIA);
        } catch (Throwable $e) {
            throw new Exception('Falta instalar el driver del lector.', self::SIN_LECTOR);
        }
        $this->zk->ZKFPM_Terminate();
        if ($this->zk->ZKFPM_Init() != 0) {
            throw new Exception('No se pudo iniciar el lector.', self::SIN_LECTOR);
        }
        if ($this->zk->ZKFPM_GetDeviceCount() <= 0) {
            $this->zk->ZKFPM_Terminate();
            throw new Exception('Lector desconectado.', self::SIN_LECTOR);
        }
        $this->dispositivo = $this->zk->ZKFPM_OpenDevice(0);
        if (self::vacio($this->dispositivo)) {
            $this->dispositivo = null;
            $this->zk->ZKFPM_Terminate();
            throw new Exception('El lector está siendo usado por otro programa. Cierre el sistema de escritorio.', self::SIN_LECTOR);
        }
        $this->base = $this->zk->ZKFPM_DBInit();
        if (self::vacio($this->base)) {
            $this->base = null;
            $this->cerrar();
            throw new Exception('No se pudo iniciar el lector.', self::SIN_LECTOR);
        }
        // Ancho (parámetro 1) por alto (parámetro 2) = tamaño de la imagen de la huella
        $this->ancho = $this->parametro(1);
        $this->alto = $this->parametro(2);
        $this->tamanoImagen = max(1, $this->ancho * $this->alto);
        $this->imagen = FFI::new('unsigned char[' . $this->tamanoImagen . ']');
    }

    /**
     * Revisión rápida del lector, sin esperar ningún dedo (para el aviso de conectado / desconectado).
     * Devuelve CONECTADO, DESCONECTADO o SIN_DRIVER.
     */
    public static function estado()
    {
        $ffi = strtolower((string) ini_get('ffi.enable'));
        if (!extension_loaded('ffi') || !in_array($ffi, ['1', 'true', 'on'])) {
            return 'DESCONECTADO';
        }
        // Si una página está leyendo una huella, se espera a que suelte el lector
        $turno = fopen(self::archivo('lock'), 'c');
        flock($turno, LOCK_EX);
        try {
            $zk = FFI::cdef(self::FUNCIONES, self::LIBRERIA);
            $zk->ZKFPM_Terminate();
            $estado = $zk->ZKFPM_Init() == 0 && $zk->ZKFPM_GetDeviceCount() > 0 ? 'CONECTADO' : 'DESCONECTADO';
            $zk->ZKFPM_Terminate();
        } catch (Throwable $e) {
            $estado = 'SIN_DRIVER';
        }
        flock($turno, LOCK_UN);
        fclose($turno);
        return $estado;
    }

    /** Carga en el lector las huellas guardadas: [id_huella => plantilla]. */
    public function cargarHuellas($huellas)
    {
        $this->zk->ZKFPM_DBClear($this->base);
        foreach ($huellas as $id => $plantilla) {
            $memoria = $this->aMemoria($plantilla);
            $this->zk->ZKFPM_DBAdd($this->base, (int) $id, $memoria, strlen($plantilla));
        }
    }

    /** Espera el dedo y busca de quién es. Devuelve ['id_huella' => .., 'puntaje' => ..] o null si no coincide. */
    public function identificar()
    {
        return $this->buscar($this->leerDedo());
    }

    /** Busca una plantilla entre las huellas cargadas. Devuelve ['id_huella', 'puntaje'] o null. */
    public function buscar($plantilla)
    {
        $id = FFI::new('unsigned int');
        $puntaje = FFI::new('unsigned int');
        $r = $this->zk->ZKFPM_DBIdentify($this->base, $this->aMemoria($plantilla), strlen($plantilla),
                                         FFI::addr($id), FFI::addr($puntaje));
        return $r == 0 ? ['id_huella' => $id->cdata, 'puntaje' => $puntaje->cdata] : null;
    }

    /** Registro: une las 3 muestras del mismo dedo en una sola huella. Devuelve la plantilla lista para guardar. */
    public function unir($muestras)
    {
        $unida = FFI::new('unsigned char[' . self::TAMANO_PLANTILLA . ']');
        $largo = FFI::new('unsigned int');
        $largo->cdata = self::TAMANO_PLANTILLA;
        $r = $this->zk->ZKFPM_DBMerge($this->base, $this->aMemoria($muestras[0]), $this->aMemoria($muestras[1]),
                                      $this->aMemoria($muestras[2]), $unida, FFI::addr($largo));
        if ($r != 0) {
            throw new Exception('No se pudo registrar la huella. Intente de nuevo.');
        }
        return FFI::string($unida, $largo->cdata);
    }

    /** Libera el lector (se puede llamar varias veces). */
    public function cerrar()
    {
        if ($this->zk !== null) {
            if ($this->base !== null) {
                $this->zk->ZKFPM_DBFree($this->base);
                $this->base = null;
            }
            if ($this->dispositivo !== null) {
                $this->zk->ZKFPM_CloseDevice($this->dispositivo);
                $this->dispositivo = null;
            }
            $this->zk->ZKFPM_Terminate();
            $this->zk = null;
        }
        if ($this->turno !== null) {
            flock($this->turno, LOCK_UN);
            fclose($this->turno);
            $this->turno = null;
        }
    }

    public function __destruct()
    {
        $this->cerrar();
    }

    /** Espera unos segundos a que se coloque un dedo y devuelve su plantilla. */
    public function leerDedo()
    {
        $limite = microtime(true) + self::SEGUNDOS_ESCUCHA;
        while (microtime(true) < $limite) {
            if ($this->cancelada()) {
                throw new Exception('Captura cancelada.', self::CANCELADA);
            }
            $plantilla = $this->leerUnaVez();
            if ($plantilla !== null) {
                $this->ultimaImagen = $this->imagenBmp(); // antes de levantar el dedo
                $this->esperarQueLevante();
                return $plantilla;
            }
            if (!in_array($this->ultimoCodigo, self::RESPUESTAS_CONECTADO)) {
                throw new Exception('Lector desconectado.', self::SIN_LECTOR);
            }
            usleep(self::PAUSA);
        }
        throw new Exception('No se detectó el dedo. Intente de nuevo.', self::SIN_DEDO);
    }

    /** Imagen del último dedo leído (archivo BMP en escala de grises), o null. Para mostrarla en la página. */
    public function ultimaImagen()
    {
        return $this->ultimaImagen;
    }

    /**
     * Convierte la imagen del lector (un byte de gris por punto) en un archivo BMP,
     * que el navegador muestra sin necesitar ninguna librería de imágenes.
     */
    private function imagenBmp()
    {
        if ($this->ancho <= 0 || $this->alto <= 0) {
            return null;
        }
        $puntos = FFI::string($this->imagen, $this->ancho * $this->alto);
        $fila = ($this->ancho + 3) & ~3; // cada fila del BMP mide un múltiplo de 4
        $relleno = str_repeat("\0", $fila - $this->ancho);
        $datos = '';
        for ($y = $this->alto - 1; $y >= 0; $y--) { // el BMP guarda las filas de abajo hacia arriba
            $datos .= substr($puntos, $y * $this->ancho, $this->ancho) . $relleno;
        }
        $paleta = '';
        for ($i = 0; $i < 256; $i++) {
            $paleta .= chr($i) . chr($i) . chr($i) . "\0";
        }
        $inicio = 14 + 40 + 1024;
        return 'BM' . pack('V', $inicio + strlen($datos)) . pack('v2', 0, 0) . pack('V', $inicio)
            . pack('V3v2V6', 40, $this->ancho, $this->alto, 1, 8, 0, strlen($datos), 2835, 2835, 256, 256)
            . $paleta . $datos;
    }

    /** true si otra página pidió el lector después de esta lectura (cambió de página o de dedo). */
    private function cancelada()
    {
        $vigente = @file_get_contents(self::archivo('pedido'));
        return $vigente !== false && $vigente !== '' && $vigente !== $this->pedido;
    }

    private static function archivo($nombre)
    {
        return sys_get_temp_dir() . '/biblioteca_lector.' . $nombre;
    }

    /** Una lectura del lector: devuelve la plantilla si había un dedo, o null. */
    private function leerUnaVez()
    {
        $plantilla = FFI::new('unsigned char[' . self::TAMANO_PLANTILLA . ']');
        $largo = FFI::new('unsigned int');
        $largo->cdata = self::TAMANO_PLANTILLA;
        $this->ultimoCodigo = $this->zk->ZKFPM_AcquireFingerprint($this->dispositivo, $this->imagen,
                                                                  $this->tamanoImagen, $plantilla, FFI::addr($largo));
        return $this->ultimoCodigo == 0 ? FFI::string($plantilla, $largo->cdata) : null;
    }

    /** Espera a que se levante el dedo, para que la siguiente muestra sea una lectura nueva. */
    private function esperarQueLevante()
    {
        $limite = microtime(true) + self::SEGUNDOS_LEVANTAR;
        while (microtime(true) < $limite && $this->leerUnaVez() !== null) {
            usleep(self::PAUSA);
        }
    }

    /** true si las dos muestras son del mismo dedo. */
    public function mismoDedo($a, $b)
    {
        return $this->zk->ZKFPM_DBMatch($this->base, $this->aMemoria($a), strlen($a), $this->aMemoria($b), strlen($b)) > 0;
    }

    /** Lee un parámetro del lector (número de 4 bytes). */
    private function parametro($codigo)
    {
        $valor = FFI::new('unsigned char[4]');
        $tamano = FFI::new('unsigned int');
        $tamano->cdata = 4;
        $this->zk->ZKFPM_GetParameters($this->dispositivo, $codigo, $valor, FFI::addr($tamano));
        return $valor[0] | ($valor[1] << 8) | ($valor[2] << 16) | ($valor[3] << 24);
    }

    /** El driver devuelve null (o un puntero vacío) cuando no pudo abrir algo. */
    private static function vacio($puntero)
    {
        return $puntero === null || FFI::isNull($puntero);
    }

    /** Copia una plantilla (texto binario de PHP) a la memoria que entiende el driver. */
    private function aMemoria($plantilla)
    {
        $largo = max(1, strlen($plantilla));
        $memoria = FFI::new('unsigned char[' . $largo . ']');
        FFI::memcpy($memoria, $plantilla, strlen($plantilla));
        return $memoria;
    }
}
