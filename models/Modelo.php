<?php
// Clase base de los modelos: consultas preparadas sobre la base biblioteca_huella
class Modelo
{
    // Búsquedas: no se cargan todos los registros. La base devuelve solo los que coinciden con lo escrito,
    // como máximo MAXIMO_BUSQUEDA (se pide uno más para saber si hay más resultados).
    const MAXIMO_BUSQUEDA = 50;

    /** Parte del WHERE que busca $texto en cualquiera de las $columnas. Devuelve [sql, parámetros]. */
    protected function condicionBusqueda($columnas, $texto)
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return ['', []];
        }
        $partes = [];
        foreach ($columnas as $columna) {
            $partes[] = $columna . ' LIKE ?';
        }
        return [' AND (' . implode(' OR ', $partes) . ')', array_fill(0, count($columnas), '%' . $texto . '%')];
    }

    /** Límite de las búsquedas (uno más que el máximo, para saber si hay más). */
    protected function limiteBusqueda()
    {
        return ' LIMIT ' . (self::MAXIMO_BUSQUEDA + 1);
    }

    protected $cn;

    public function __construct()
    {
        $this->cn = Conexion::conectar();
    }

    /** Ejecuta un SELECT y devuelve todas las filas. */
    protected function consultar($sql, $parametros = [])
    {
        $resultado = $this->preparar($sql, $parametros)->get_result();
        return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Devuelve la primera fila o null. */
    protected function fila($sql, $parametros = [])
    {
        $filas = $this->consultar($sql, $parametros);
        return $filas ? $filas[0] : null;
    }

    /** Devuelve el primer valor de la primera fila o null. */
    protected function valor($sql, $parametros = [])
    {
        $f = $this->fila($sql, $parametros);
        return $f ? reset($f) : null;
    }

    /** Ejecuta un INSERT, UPDATE o DELETE. Devuelve el id generado (si lo hay). */
    protected function ejecutar($sql, $parametros = [])
    {
        $this->preparar($sql, $parametros);
        return $this->cn->insert_id;
    }

    private function preparar($sql, $parametros)
    {
        $st = $this->cn->prepare($sql);
        if ($parametros) {
            $st->bind_param(str_repeat('s', count($parametros)), ...$parametros);
        }
        $st->execute();
        return $st;
    }

    /** Convierte un error de MySQL en un mensaje entendible. */
    public static function mensajeError(mysqli_sql_exception $e)
    {
        $codigo = $e->getCode();
        if ($codigo == 1062) {
            preg_match("/entry '([^']*)'/", $e->getMessage(), $m);
            return 'Ya existe un registro con ' . ($m[1] ?? 'ese dato') . '.';
        }
        if ($codigo == 1451) {
            return 'No se puede eliminar: tiene registros relacionados.';
        }
        if ($codigo == 1644) {
            return $e->getMessage();
        }
        return 'Error en la base de datos: ' . $e->getMessage();
    }
}
