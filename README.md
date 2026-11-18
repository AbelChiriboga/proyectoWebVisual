# Biblioteca Digital FISEI — versión web (MVC)

Versión web del sistema de préstamos de la biblioteca (el mismo proyecto de Java, ahora en PHP).
Usa solo **PHP, HTML, CSS y JavaScript**, sin frameworks, sin AJAX ni fetch: cada acción es un formulario que se envía al servidor.

## Cómo levantarlo

1. Encender **Apache** y **MySQL** en XAMPP.
2. Tener creada la base `biblioteca_huella` (el mismo script del proyecto en Java).
3. Abrir en el navegador: `http://localhost/proyectoVisual/`

Usuarios de prueba: `admin` / `admin123` (administrador) y `biblio` / `biblio123` (bibliotecario).
El estudiante entra en la pestaña **Estudiante** solo con su cédula.

## Estructura MVC

```
index.php        Controlador frontal: toda petición entra por aquí
models/          Datos y reglas del negocio (consultas a MySQL y validaciones)
views/           Lo que se ve: páginas HTML, plantillas, CSS, JS e imágenes
controllers/     Reciben la petición, llaman al modelo y eligen la vista
```

**Cómo viaja una petición:** `index.php?c=libros&a=guardar`

1. `index.php` busca el controlador de `c` (`libros` → `LibroController`).
2. Llama a la acción `a` (`guardar`). Si no viene, llama a `index`.
3. El controlador revisa el permiso, pide los datos al **modelo** (`Libro`) y muestra la **vista** (`views/libros/index.php`).

| Carpeta | Archivos |
|---|---|
| `models/` | `Conexion` (conexión única), `Modelo` (clase base con consultas preparadas), `Validacion` (cédula, correo, teléfono…), `HuellaLector` (lector ZKTeco con FFI) y un modelo por tabla: `Usuario`, `Estudiante`, `Huella`, `Bitacora`, `Autor`, `Categoria`, `Libro`, `Ejemplar`, `Prestamo`, `Multa`, `Parametro`, `Resumen` |
| `controllers/` | `Controlador` (clase base: vistas, permisos, mensajes) y un controlador por pantalla: `Login`, `Menu`, `Estudiante`, `Usuario`, `Parametro`, `Bitacora`, `Autor`, `Categoria`, `Libro`, `Ejemplar`, `Prestamo`, `Devolucion`, `Atraso`, `Consulta`, `Panel`, `Reporte` |
| `views/` | Una carpeta por pantalla (`views/libros/index.php`…), `plantillas/` (encabezado, pie, recuadro de huella), `reportes/` (filtros y PDF con FPDF) y `assets/` (`css`, `js`, `img`) |

Reglas que se cumplen en todo el proyecto:

- Solo los **modelos** hablan con la base de datos.
- Las **vistas** solo muestran datos; no consultan ni guardan nada.
- Los **controladores** no tienen SQL ni HTML: unen modelo y vista.

## Lector de huella ZKTeco (real)

PHP usa directamente la librería del driver de ZKTeco (`libzkfp.dll`) mediante la extensión **FFI** de PHP
(`models/HuellaLector.php`). Es la misma librería que usa el sistema en Java: las huellas registradas en uno sirven en el otro.

**Requisitos (una sola vez):**

1. Tener instalado el driver del lector ZKTeco (el mismo del sistema de escritorio).
2. En `C:\xampp\php\php.ini` activar estas dos líneas (quitar el `;` si lo tienen) y reiniciar Apache:
   ```
   extension=ffi
   ffi.enable=true
   ```
3. No tener abierto el sistema de escritorio al mismo tiempo: solo un programa puede usar el lector.

**Cómo se usa:**

- La huella se lee **sola, sin presionar ningún botón**, igual que en el sistema de escritorio.
- **Registrar** (Estudiantes): al pulsar *Nuevo* o *Editar* se empieza a leer el dedo elegido; el estudiante coloca
  el mismo dedo 3 veces (levantándolo entre cada lectura). Se pueden capturar varios dedos y luego pulsar *Guardar*.
- **Préstamo y Devolución**: al abrir la página el lector ya está esperando; el estudiante coloca el dedo y aparece
  su ficha. Se acepta si el puntaje llega al umbral de Parámetros.
- Cómo funciona sin AJAX: la página tiene un marco oculto (`iframe`) que abre la acción `escuchar`. El servidor
  espera el dedo unos segundos y responde una mini página (`views/plantillas/lector.php`) que le avisa a la página
  qué pasó; si nadie puso el dedo, el marco vuelve a pedir la lectura.
- Cada intento queda en la bitácora; si nadie coloca el dedo no se cuenta como fallo.

## Reportes

Se generan en PDF con **FPDF** (carpeta `fpdf/`): inventario, historial, préstamos atrasados y multas pendientes.
La plantilla común está en `views/reportes/pdf.php`.
