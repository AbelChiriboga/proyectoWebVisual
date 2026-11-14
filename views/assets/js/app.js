// JavaScript simple de la Biblioteca FISEI (sin AJAX ni fetch: solo ayuda en el formulario)

// Al volver con el botón Atrás del navegador, la página se pide de nuevo al servidor y empieza limpia
// (el navegador no debe mostrar la copia guardada con los datos anteriores)
window.addEventListener('pageshow', function (ev) {
    if (ev.persisted) {
        location.reload();
    }
});
document.querySelectorAll('form').forEach(function (form) {
    form.setAttribute('autocomplete', 'off');
});

// Reloj del encabezado
var reloj = document.getElementById('reloj');
if (reloj) {
    setInterval(function () {
        var d = new Date();
        reloj.textContent = [d.getHours(), d.getMinutes(), d.getSeconds()]
            .map(function (n) { return (n < 10 ? '0' : '') + n; }).join(':');
    }, 1000);
}

// Los avisos del servidor también salen como alerta (igual que el JOptionPane de Java)
var aviso = document.querySelector('[data-alerta]');
if (aviso) {
    setTimeout(function () { alert(aviso.getAttribute('data-alerta')); }, 100);
}

// Caracteres permitidos en cada caja según su data-tipo
var reglas = {
    numeros: /[^0-9]/g,
    texto: /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g,
    correo: /[^A-Za-z0-9@.]/g,
    isbn: /[^0-9Xx-]/g,
    decimal: /[^0-9.]/g,
    usuario: /[^A-Za-z0-9._-]/g
};
var enMayusculas = ['texto', 'isbn', 'libre'];

document.querySelectorAll('input[data-tipo]').forEach(function (caja) {
    caja.addEventListener('input', function () {
        var tipo = caja.getAttribute('data-tipo');
        var pos = caja.selectionStart;
        var antes = caja.value;
        var nuevo = reglas[tipo] ? antes.replace(reglas[tipo], '') : antes;
        if (tipo === 'decimal') {
            var partes = nuevo.split('.');
            nuevo = partes.shift() + (partes.length ? '.' + partes.join('') : '');
        }
        if (enMayusculas.indexOf(tipo) >= 0) {
            nuevo = nuevo.toUpperCase();
        }
        if (nuevo !== antes) {
            caja.value = nuevo;
            var corrida = antes.length - nuevo.length;
            caja.setSelectionRange(pos - corrida, pos - corrida);
        }
    });
});

// Enter pasa a la siguiente caja; en la última pasa al botón principal
document.querySelectorAll('form.enter').forEach(function (form) {
    form.addEventListener('keydown', function (ev) {
        var actual = ev.target;
        if (ev.key !== 'Enter' || actual.tagName === 'BUTTON' || actual.type === 'submit') {
            return;
        }
        ev.preventDefault();
        if (!actual.checkValidity()) {
            actual.reportValidity();
            return;
        }
        var campos = Array.prototype.filter.call(form.elements, function (el) {
            return !el.disabled && !el.readOnly && el.type !== 'hidden' && el.offsetParent !== null
                && (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.classList.contains('primario'));
        });
        var i = campos.indexOf(actual);
        if (i >= 0 && i + 1 < campos.length) {
            campos[i + 1].focus();
        }
    });
});

// Confirmación antes de acciones importantes (eliminar, desactivar, salir...)
document.querySelectorAll('[data-confirmar]').forEach(function (el) {
    el.addEventListener(el.tagName === 'FORM' ? 'submit' : 'click', function (ev) {
        if (!confirm(el.getAttribute('data-confirmar'))) {
            ev.preventDefault();
        }
    });
});

// Búsqueda en las tablas: Enter en la caja presiona el botón Buscar (no guarda ni confirma nada)
document.querySelectorAll('input[data-enter]').forEach(function (caja) {
    caja.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            ev.stopPropagation();
            document.getElementById(caja.getAttribute('data-enter')).click();
        }
    });
});

// Máximo de libros que se pueden marcar (préstamos)
var lista = document.querySelector('[data-max]');
if (lista) {
    lista.addEventListener('change', function (ev) {
        if (ev.target.type !== 'checkbox') {
            return;
        }
        ev.target.closest('tr').classList.toggle('marcada', ev.target.checked);
        if (!ev.target.checked) {
            contarMarcados();
            return;
        }
        var max = parseInt(lista.getAttribute('data-max'), 10);
        var marcados = lista.querySelectorAll('input[type=checkbox]:checked').length;
        if (marcados > max) {
            ev.target.checked = false;
            ev.target.closest('tr').classList.remove('marcada');
            alert(max === 0 ? 'Este estudiante ya no puede llevar más libros.'
                : 'Ya seleccionó el máximo de ' + max + ' libro(s).');
        }
        contarMarcados();
    });
}

function contarMarcados() {
    var texto = document.getElementById('marcados');
    if (texto && lista) {
        texto.textContent = lista.querySelectorAll('input[type=checkbox]:checked').length;
    }
}

// Devoluciones: días de retraso y total a pagar de los libros marcados
var devolucion = document.getElementById('lista-devolucion');
if (devolucion) {
    devolucion.addEventListener('change', function () {
        var dias = 0, total = 0;
        devolucion.querySelectorAll('input[type=checkbox]:checked').forEach(function (c) {
            dias += parseInt(c.getAttribute('data-dias'), 10);
            total += parseFloat(c.getAttribute('data-monto'));
        });
        document.getElementById('total-dias').textContent = dias;
        document.getElementById('total-pagar').textContent = '$' + total.toFixed(2);
    });
}

// ---------- Lector de huella automático (sin AJAX ni fetch) ----------
// La huella se lee sola, sin botón. La página tiene un marco oculto (iframe) que abre la acción "escuchar":
// el servidor espera el dedo unos segundos y responde una mini página que llama a lector({...}).
// Si nadie puso el dedo, el marco vuelve a pedir la lectura, y así la página siempre está escuchando.
var marco = document.getElementById('lector');
var textoLector = document.getElementById('huella-texto');
var datosLectura = '';   // datos extra de la lectura (en Estudiantes: el dedo y el estudiante)
var pedidos = 0;         // número de pedido, para que el navegador no repita una respuesta guardada
var turnoLectura = 0;    // cambia al empezar otra lectura; los avisos pendientes de la anterior se descartan
var escuchando = false;  // true mientras la página espera un dedo
var sinLector = false;   // true si la lectura se detuvo porque el lector no está conectado
var textoInicial = textoLector ? textoLector.textContent : '';

var pedidoPendiente = null; // lectura pedida antes de que la página termine de cargar

function escuchar(extra) {
    escuchando = true;
    sinLector = false;
    var pedido = marco.getAttribute('data-escuchar') + datosLectura + (extra || '') + '&n=' + (++pedidos);
    if (document.readyState === 'complete') {
        marco.src = pedido;
    } else {
        pedidoPendiente = pedido; // se pide cuando la página termine de cargar, para no dejarla "cargando"
    }
}

function dejarDeEscuchar() {
    escuchando = false;
    pedidoPendiente = null;
    marco.src = 'about:blank';
}

window.addEventListener('load', function () {
    setTimeout(function () {
        if (pedidoPendiente) {
            marco.src = pedidoPendiente;
        }
    }, 0);
});

function avisoLector(texto, error) {
    textoLector.textContent = texto;
    textoLector.classList.toggle('mal', !!error);
}

// La llama la respuesta del servidor desde el marco oculto
function lector(r) {
    estadoLector(r);
    if (r.accion === 'estado') {
        if (sinLector && r.lector === 'CONECTADO') {
            // El lector volvió: la página sigue leyendo la huella donde se quedó
            if (dedo) {
                leerDedo(false);
            } else {
                avisoLector(textoInicial, false);
                escuchar();
            }
        }
        setTimeout(revisarLector, 2000);
        return;
    }
    var muestras = document.getElementById('huella-muestras');
    if (muestras && r.muestras !== undefined) {
        muestras.textContent = r.muestras + ' de 3 muestras';
    }
    if (r.imagen) {
        mostrarImagen(r.imagen); // la huella que se acaba de leer
    }
    if (r.accion === 'seguir') {
        // Nadie puso el dedo todavía, o falta otra muestra: se sigue escuchando
        if (r.texto) {
            avisoLector(r.texto, false);
        } else if (textoLector.classList.contains('mal')) {
            avisoLector(textoInicial, false); // el lector volvió: se quita el aviso en rojo
        }
        escuchar();
    } else if (r.accion === 'aviso') {
        // Huella no reconocida o problema con el lector: se avisa y se vuelve a intentar
        var turno = turnoLectura;
        avisoLector(r.texto, true);
        if (r.lector !== 'CONECTADO') {
            // Lector desconectado: se deja de leer hasta que la revisión del lector avise que volvió
            escuchando = false;
            sinLector = true;
            return;
        }
        setTimeout(function () {
            if (turno === turnoLectura) {
                escuchar();
            }
        }, 2000);
    } else if (r.accion === 'identificado') {
        // Préstamos y Devoluciones: se muestra el estudiante (sin perder los libros marcados)
        var form = marco.closest('form');
        form.action = marco.getAttribute('data-al-leer');
        form.submit();
    } else if (r.accion === 'capturada') {
        escuchando = false;
        dedo.selectedOptions[0].setAttribute('data-capturado', '');
        imagenes[dedo.value] = r.imagen;
        mostrarCapturada();
    } else if (r.accion === 'alerta') {
        // La huella ya es de otro estudiante: se espera a que elija otro dedo o pulse "Registrar otra vez"
        escuchando = false;
        avisoLector(r.texto, true);
        document.getElementById('huella-otra').hidden = false;
    }
}

// ---------- Estado del lector (como MonitorLector del sistema de escritorio) ----------
// Cada respuesta del servidor dice si el lector está conectado. Cuando cambia, sale el mensaje
// "Lector conectado" o "Lector desconectado", y el pie de página muestra el estado.
function estadoLector(r) {
    var pie = document.getElementById('lector-pie');
    if (!r.lector || !pie) {
        return;
    }
    var conectado = r.lector === 'CONECTADO';
    pie.classList.toggle('conectado', conectado);
    pie.querySelector('strong').textContent = conectado ? 'Conectado'
        : (r.lector === 'SIN_DRIVER' ? 'Sin driver instalado' : 'Desconectado');
    if (r.cambio) {
        setTimeout(function () { alert(conectado ? 'Lector conectado' : 'Lector desconectado'); }, 50);
    }
}

// Mientras la página no está esperando un dedo, un marco oculto pregunta cada 2 segundos por el lector
var monitor = document.getElementById('monitor-lector');
function revisarLector() {
    if (escuchando) {
        setTimeout(revisarLector, 2000); // la lectura de la huella ya informa el estado
    } else {
        monitor.src = 'index.php?c=lector&a=estado&n=' + (++pedidos);
    }
}
if (monitor) {
    window.addEventListener('load', function () { setTimeout(revisarLector, 0); });
}

// Préstamos y Devoluciones: se empieza a escuchar apenas se abre la página
if (marco && !document.getElementById('registro-huella')) {
    escuchar();
}

// Estudiantes: en Nuevo o Editar se lee solo el dedo elegido (3 muestras); se pueden capturar varios dedos
var dedo = document.querySelector('#registro-huella select[name="dedo"]');

function nombreDedo(opcion) {
    return opcion.getAttribute('data-nombre');
}

var imagenes = {}; // imagen de la huella capturada de cada dedo (como las vistas previas de Java)

function pintarRecuadro(texto, listo) {
    var recuadro = document.getElementById('huella-recuadro');
    recuadro.classList.toggle('ok', listo);
    recuadro.innerHTML = '<span></span>';
    recuadro.querySelector('span').textContent = texto;
}

// Muestra en el recuadro la imagen de la huella leída por el lector
function mostrarImagen(src) {
    var recuadro = document.getElementById('huella-recuadro');
    var img = document.createElement('img');
    img.src = src;
    img.alt = 'Huella';
    img.className = 'imagen-huella';
    recuadro.innerHTML = '';
    recuadro.appendChild(img);
}

function leerDedo(otraVez) {
    var form = document.getElementById('formulario');
    var modo = form.getAttribute('data-crud');
    var opcion = dedo.selectedOptions[0];
    turnoLectura++;
    document.getElementById('nota-dedo').hidden = !opcion.hasAttribute('data-registrado');
    document.getElementById('huella-otra').hidden = true;
    if (modo !== 'nuevo' && modo !== 'edicion') {
        return; // solo se lee al crear o editar un estudiante
    }
    if (otraVez) {
        opcion.removeAttribute('data-capturado');
    }
    if (opcion.hasAttribute('data-capturado')) {
        dejarDeEscuchar();
        mostrarCapturada();
        return;
    }
    opcion.textContent = nombreDedo(opcion) + (opcion.hasAttribute('data-registrado') ? ' (registrado)' : '');
    pintarRecuadro('Coloque el dedo', false);
    document.getElementById('huella-muestras').textContent = '0 de 3 muestras';
    avisoLector('Coloque el dedo en el lector (1 de 3).', false);
    datosLectura = '&dedo=' + dedo.value + '&id=' + form.id_estudiante.value;
    escuchar(otraVez ? '&otra=1' : '');
}

function mostrarCapturada() {
    var opcion = dedo.selectedOptions[0];
    var porGuardar = [];
    Array.prototype.forEach.call(dedo.options, function (o) {
        if (o.hasAttribute('data-capturado')) {
            porGuardar.push(nombreDedo(o));
        }
    });
    opcion.textContent = nombreDedo(opcion) + ' (capturado)';
    pintarRecuadro('Huella capturada', true);
    if (imagenes[dedo.value]) {
        mostrarImagen(imagenes[dedo.value]);
    }
    document.getElementById('huella-muestras').textContent = '3 de 3 muestras';
    avisoLector('Huella capturada.', false);
    document.getElementById('huella-info').textContent = 'Por guardar: ' + porGuardar.join(', ')
        + '. Puede elegir otro dedo o presionar Guardar.';
    document.getElementById('huella-otra').hidden = false;
}

if (dedo) {
    dedo.addEventListener('change', function () { leerDedo(false); });
    document.getElementById('huella-otra').addEventListener('click', function () { leerDedo(true); });
}

// Atrasos: cantidad y total de las multas marcadas; solo se pagan multas de un mismo estudiante
var pago = document.getElementById('pago');
if (pago) {
    var marcadas = function () {
        return document.querySelectorAll('input[name="multas[]"]:checked');
    };
    var resumirPago = function () {
        var total = 0;
        marcadas().forEach(function (c) { total += parseFloat(c.getAttribute('data-monto')); });
        document.getElementById('pago-cantidad').textContent = marcadas().length;
        document.getElementById('pago-total').textContent = '$' + total.toFixed(2);
        var boton = document.getElementById('boton-pago');
        if (boton) {
            boton.disabled = marcadas().length === 0;
        }
        return total;
    };
    // Un clic en la fila la elige o la quita (como Ctrl + clic en la tabla del escritorio)
    document.querySelectorAll('tr.seleccionable').forEach(function (fila) {
        fila.addEventListener('click', function () {
            var c = fila.querySelector('input[name="multas[]"]');
            c.checked = !c.checked;
            fila.classList.toggle('marcada', c.checked);
            resumirPago();
        });
    });
    pago.addEventListener('submit', function (ev) {
        var lista = marcadas();
        var estudiantes = {};
        lista.forEach(function (c) { estudiantes[c.getAttribute('data-estudiante')] = true; });
        var total = resumirPago();
        if (lista.length === 0) {
            alert('Seleccione al menos una multa.');
            ev.preventDefault();
        } else if (Object.keys(estudiantes).length > 1) {
            alert('Seleccione multas de un solo estudiante.');
            ev.preventDefault();
        } else if (!confirm('¿Registrar el pago de ' + lista.length + ' multa(s)?\nTotal: $' + total.toFixed(2))) {
            ev.preventDefault();
        }
    });
}

// Reportes: la fecha Desde no puede ser mayor que Hasta
document.querySelectorAll('form[data-rango-fechas]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
        if (form.desde.value > form.hasta.value) {
            alert('La fecha Desde no puede ser mayor que Hasta.');
            ev.preventDefault();
        }
    });
});

// Formularios CRUD (como ControlCrud del sistema de escritorio):
// consulta = todo bloqueado; seleccion = datos de la fila elegida; nuevo / edicion = se puede escribir.
// Cada botón dice en qué modos está activo con data-modos="nuevo edicion".
function aplicarModo(form, modo) {
    form.setAttribute('data-crud', modo);
    var escribir = modo === 'nuevo' || modo === 'edicion';
    form.querySelectorAll('input, select').forEach(function (c) {
        if (c.type === 'hidden' || c.type === 'checkbox' || c.hasAttribute('data-libre')) {
            return;
        }
        var bloquear = !escribir || (modo === 'edicion' && c.hasAttribute('data-fijo')) || c.hasAttribute('data-siempre-fijo');
        if (c.tagName === 'SELECT') {
            c.classList.toggle('bloqueado', bloquear);
            c.tabIndex = bloquear ? -1 : 0;
        } else {
            c.readOnly = bloquear;
        }
    });
    document.querySelectorAll('[data-modos]').forEach(function (b) {
        if (b.closest('form[data-crud]') !== form && b.getAttribute('form') !== form.id && !b.hasAttribute('data-de')) {
            return;
        }
        var activo = b.getAttribute('data-modos').split(' ').indexOf(modo) >= 0;
        b.classList.toggle('deshabilitado', !activo);
        if (b.tagName === 'BUTTON') {
            b.disabled = !activo;
        }
    });
    // Igual que en Java: mientras se crea o edita, la tabla y la búsqueda quedan bloqueadas
    document.body.classList.toggle('editando', escribir);
    document.querySelectorAll('[data-al-editar]').forEach(function (c) {
        c.disabled = escribir;
        c.classList.toggle('deshabilitado', escribir);
    });
    var campoModo = form.querySelector('input[name=modo]');
    if (campoModo) {
        campoModo.value = escribir ? modo : '';
    }
    // Estudiantes: al pasar a Nuevo o Editar empieza sola la lectura de la huella
    if (dedo && form.id === 'formulario') {
        leerDedo(false);
    }
}
document.querySelectorAll('form[data-crud]').forEach(function (form) {
    aplicarModo(form, form.getAttribute('data-crud'));
    if (form.getAttribute('data-crud') === 'nuevo' || form.getAttribute('data-crud') === 'edicion') {
        var primero = form.querySelector('input:not([type=hidden]):not([readonly])');
        if (primero) primero.focus();
    }
});
// Botón Editar: habilita los campos de la fila elegida
document.querySelectorAll('[data-editar]').forEach(function (b) {
    b.addEventListener('click', function () {
        var form = document.getElementById(b.getAttribute('data-editar'));
        aplicarModo(form, 'edicion');
        var primero = form.querySelector('input:not([type=hidden]):not([readonly])');
        if (primero) primero.focus();
    });
});
// Filas de las tablas: un clic elige la fila (como seleccionar en la JTable)
document.querySelectorAll('tr[data-href]').forEach(function (fila) {
    fila.addEventListener('click', function () {
        if (document.body.classList.contains('editando')) {
            return; // primero Guardar o Cancelar
        }
        location.href = fila.getAttribute('data-href');
    });
});

// Usuarios: seguridad de la contraseña (mismos puntos que util.Seguridad del sistema de escritorio)
document.querySelectorAll('input[data-fuerza]').forEach(function (caja) {
    var barra = document.getElementById(caja.getAttribute('data-fuerza'));
    var texto = document.getElementById(caja.getAttribute('data-fuerza') + '-texto');
    caja.addEventListener('input', function () {
        var c = caja.value;
        var puntos = 0;
        if (c.length > 0) {
            puntos = Math.min(c.length, 12) * 4;
            puntos += /[a-z]/.test(c) ? 10 : 0;
            puntos += /[A-Z]/.test(c) ? 14 : 0;
            puntos += /\d/.test(c) ? 14 : 0;
            puntos += /[^A-Za-z0-9]/.test(c) ? 14 : 0;
            puntos = Math.min(puntos, 100);
        }
        barra.style.width = puntos + '%';
        barra.style.background = puntos < 40 ? '#c62828' : (puntos < 70 ? '#e0a106' : '#009999');
        texto.textContent = puntos === 0 ? 'Seguridad de la contraseña'
            : 'Seguridad de la contraseña: ' + (puntos < 40 ? 'Débil' : (puntos < 70 ? 'Media' : 'Fuerte'));
    });
});
