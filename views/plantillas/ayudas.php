<?php
// Funciones de apoyo para las vistas: escapar texto y dar formato a fechas y dinero

/** Escapa texto para mostrarlo en HTML. */
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function fecha($valor)
{
    return $valor ? date('d/m/Y', strtotime($valor)) : '—';
}

function fecha_hora($valor)
{
    return $valor ? date('d/m/Y H:i', strtotime($valor)) : '—';
}

function dinero($valor)
{
    return '$' . number_format((float) $valor, 2);
}

/** Texto del plazo de un préstamo: "Faltan 3 días", "Vence hoy" o "Atrasado 2 días". */
function plazo($fechaLimite)
{
    $dias = (int) floor((strtotime(date('Y-m-d', strtotime($fechaLimite))) - strtotime(date('Y-m-d'))) / 86400);
    if ($dias > 1) return "Faltan $dias días";
    if ($dias == 1) return 'Falta 1 día';
    if ($dias == 0) return 'Vence hoy';
    return 'Atrasado ' . (-$dias) . ($dias == -1 ? ' día' : ' días');
}

/** Días de retraso de un préstamo a la fecha de hoy (0 si está a tiempo). */
function dias_retraso($fechaLimite)
{
    return max(0, (int) floor((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($fechaLimite)))) / 86400));
}

/** Mensaje que dejó el controlador (texto verde o rojo con su icono); también sale como alerta del navegador. */
function mostrar_aviso($aviso)
{
    if ($aviso) {
        echo '<div class="aviso aviso-' . e($aviso['tipo']) . '" data-alerta="' . e($aviso['texto']) . '">'
            . '<span>' . nl2br(e($aviso['texto'])) . '</span></div>';
    }
}

/**
 * Modo de un formulario CRUD (igual a componentes.ControlCrud del sistema de escritorio):
 * consulta (todo bloqueado, solo Nuevo), seleccion (se eligió una fila: Editar / Eliminar),
 * nuevo o edicion (campos habilitados: Guardar / Cancelar).
 */
function modo_formulario($modoPedido, $haySeleccion)
{
    if (in_array($modoPedido, ['nuevo', 'edicion'])) {
        return $modoPedido;
    }
    return $haySeleccion ? 'seleccion' : 'consulta';
}

// ---------- Búsqueda en las tablas ----------
// No se cargan todos los registros: se escribe, se presiona Buscar (o Enter) y la base devuelve solo los que
// coinciden (como máximo 50). Buscar con la caja vacía muestra los primeros 50. Limpiar borra lo buscado.

/** Caja de búsqueda y botones Buscar y Limpiar. Van con el formulario oculto "busqueda" (ver form_busqueda). */
function barra_busqueda($buscado, $ayuda)
{
    echo '<input type="text" name="buscar" form="busqueda" value="' . e($buscado ?? '') . '" placeholder="' . e($ayuda) . '"'
        . ' data-tipo="libre" maxlength="100" data-libre data-al-editar data-enter="boton-buscar">'
        . '<button class="boton solo-icono" form="busqueda" id="boton-buscar" title="Buscar" data-al-editar>'
        . '<img src="views/assets/img/Buscar.png" alt="Buscar"></button>'
        . '<a class="boton solo-icono" href="' . e(sin_busqueda()) . '" title="Limpiar lo buscado" data-al-editar>'
        . '<img src="views/assets/img/Limpiar.png" alt="Limpiar"></a>';
}

/** La misma página sin lo buscado (se mantiene lo demás: fila elegida, filtro...). */
function sin_busqueda()
{
    $parametros = $_GET;
    unset($parametros['buscar']);
    return 'index.php?' . http_build_query($parametros);
}

/** Formulario de la búsqueda (va fuera de los otros formularios). $extras = otros datos que se mantienen. */
function form_busqueda($c, $extras = [])
{
    echo '<form method="get" action="index.php" id="busqueda"><input type="hidden" name="c" value="' . e($c) . '">';
    foreach ($extras as $nombre => $valor) {
        echo '<input type="hidden" name="' . e($nombre) . '" value="' . e($valor) . '">';
    }
    echo '</form>';
}

/** Para los enlaces: mantiene la búsqueda al elegir una fila, Nuevo, Cancelar... */
function con_busqueda($buscado)
{
    return $buscado === null ? '' : '&buscar=' . urlencode($buscado);
}

/** Campo oculto para que, después de guardar o eliminar, se vuelva a la misma búsqueda. */
function campo_busqueda($buscado)
{
    if ($buscado !== null) {
        echo '<input type="hidden" name="buscar" value="' . e($buscado) . '">';
    }
}

/** Fila con el mensaje de la búsqueda: qué hacer, sin resultados o si hay más de los que se muestran. */
function fila_busqueda($buscado, $cantidad, $hayMas, $columnas)
{
    $texto = '';
    if ($buscado === null) {
        $texto = 'Escriba lo que busca y presione Enter o la lupa.';
    } elseif ($cantidad === 0) {
        $texto = $buscado === '' ? 'No hay registros.' : 'No se encontraron resultados con "' . $buscado . '".';
    } elseif ($hayMas) {
        $texto = 'Se muestran los primeros ' . Modelo::MAXIMO_BUSQUEDA . '. Escriba algo más para encontrar el registro.';
    }
    if ($texto !== '') {
        echo '<tr><td colspan="' . $columnas . '" class="vacio">' . e($texto) . '</td></tr>';
    }
}

