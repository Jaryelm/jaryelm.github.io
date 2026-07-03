<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">


    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../auxcontable/menu.php';
// incuir el archivo menu principal
?>

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#">
                <div class="form-group">
                </div>
            </form>
            
           
            <span class="divider"></span>
            <?php
include_once '../auxcontable/perfil.php';
// incuir el archivo menu principal
?>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
        <?php
// Obtener la hora actual
$hora_actual = date('H'); // Obtiene la hora en formato de 24 horas (0-23)

if ($hora_actual >= 6 && $hora_actual < 12) {
    $saludo = "Buenos Días";
} elseif ($hora_actual >= 12 && $hora_actual < 18) {
    $saludo = "Buenas Tardes";
} else {
    $saludo = "Buenas Noches";
}
?>

<h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

<br>
            


<div class="signature-module">
    <h2>Capturar Firma Digital</h2>
    <p>Por favor, firme en el cuadro siguiente:</p>
    
    <div class="signature-container">
        <canvas id="signatureCanvas" width="600" height="200" style="border: 1px solid #000;"></canvas>
        <div class="signature-actions">
            <button id="clearSignature">Limpiar</button>
            <button id="saveSignature">Guardar</button>
            <input type="file" id="uploadSignature" accept="image/png, image/jpeg, image/jpg" style="display: none;">
            <button id="uploadSignatureButton">Subir Firma (PNG)</button>
        </div>
    </div>

    <h3>Firma Registrada</h3>
    <table id="signaturesTable" class="responsive-table">
        <thead>
            <tr>
                <th>ID Usuario</th>
                <th>Nombre</th>
                <th>Firma</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Firmas cargadas dinámicamente -->
        </tbody>
    </table>
</div>

<script>

const canvas = document.getElementById('signatureCanvas');
const ctx = canvas.getContext('2d');
let isDrawing = false;

// Ajustar tamaño del canvas y sincronizar resolución
function resizeCanvas() {
    const parent = canvas.parentElement;

    // Dimensiones visuales (CSS)
    canvas.style.width = `${parent.offsetWidth}px`;
    canvas.style.height = `${parent.offsetWidth / 3}px`;

    // Escalar resolución interna para dispositivos de alta densidad
    const scale = window.devicePixelRatio || 1;
    canvas.width = parent.offsetWidth * scale;
    canvas.height = (parent.offsetWidth / 3) * scale;

    // Configurar el contexto
    ctx.setTransform(1, 0, 0, 1, 0, 0); // Resetear transformaciones previas
    ctx.scale(scale, scale);

    // Configurar estilo de dibujo
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#000';
}

// Función corregida para obtener las coordenadas precisas dentro del canvas
function getCanvasPosition(e) {
    const rect = canvas.getBoundingClientRect(); // Dimensiones reales del canvas en pantalla
    const scaleX = canvas.width / rect.width; // Escala interna/visual horizontal
    const scaleY = canvas.height / rect.height; // Escala interna/visual vertical

    // Coordenadas del evento
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;

    return {
        x: (clientX - rect.left) * scaleX, // Coordenada X escalada
        y: (clientY - rect.top) * scaleY,  // Coordenada Y escalada
    };
}

// Llamar resizeCanvas en eventos relevantes
window.addEventListener('load', resizeCanvas);
window.addEventListener('resize', resizeCanvas);

// Evento para iniciar el dibujo
function startDrawing(e) {
    isDrawing = true;
    const { x, y } = getCanvasPosition(e);
    ctx.beginPath();
    ctx.moveTo(x, y);
    e.preventDefault(); // Evitar comportamiento predeterminado
}

// Evento para dibujar
function draw(e) {
    if (!isDrawing) return;
    const { x, y } = getCanvasPosition(e);
    ctx.lineTo(x, y);
    ctx.stroke();
    e.preventDefault(); // Evitar comportamiento predeterminado
}

// Evento para finalizar el dibujo
function stopDrawing() {
    isDrawing = false;
}

// Ajustar el canvas al cargar la página y al redimensionar
window.addEventListener('load', resizeCanvas);
window.addEventListener('resize', resizeCanvas);

// Eventos para mouse
canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDrawing);
canvas.addEventListener('mouseout', stopDrawing);

// Eventos para dispositivos táctiles
canvas.addEventListener('touchstart', startDrawing);
canvas.addEventListener('touchmove', draw);
canvas.addEventListener('touchend', stopDrawing);
canvas.addEventListener('touchcancel', stopDrawing);

// Botón para limpiar la firma
document.getElementById('clearSignature').addEventListener('click', () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
});

// Botón para guardar la firma
document.getElementById('saveSignature').addEventListener('click', async () => {
    const isCanvasBlank = () => {
        const emptyCanvas = document.createElement('canvas');
        emptyCanvas.width = canvas.width;
        emptyCanvas.height = canvas.height;
        return canvas.toDataURL() === emptyCanvas.toDataURL();
    };

    if (isCanvasBlank()) {
        Swal.fire('Error', 'El canvas está vacío. Por favor, firma antes de guardar.', 'error');
        return;
    }

    const signature = canvas.toDataURL('image/png');
    try {
        const response = await fetch('../../backend/registros/save_signature.php', {
            method: 'POST',
            body: JSON.stringify({ signature }),
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
        });

        const result = await response.json();
        if (result.success) {
            Swal.fire('Éxito', 'Firma guardada exitosamente.', 'success');
            loadSignatures();
        } else {
            Swal.fire('Error', result.message || 'Error al guardar la firma.', 'error');
        }
    } catch (error) {
        console.error('Error al guardar la firma:', error);
        Swal.fire('Error', 'Hubo un problema al guardar la firma.', 'error');
    }
});

// Cargar firmas guardadas
async function loadSignatures() {
    try {
        const response = await fetch('../../backend/registros/fetch_signatures.php', {
            credentials: 'include',
        });
        const data = await response.json();

        if (!data || data.length === 0) {
            console.log('No hay firmas registradas.');
            return;
        }

        const tbody = document.querySelector('#signaturesTable tbody');
        tbody.innerHTML = '';

        data.forEach((signature) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${signature.user_id}</td>
                <td>${signature.name}</td>
                <td>
                    <img src="data:image/png;base64,${signature.signature}" alt="Firma" width="150">
                </td>
                <td>
                    <button class="action-button" onclick="deleteSignature(${signature.id})">Eliminar</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        console.error('Error al cargar firmas:', error);
    }
}

// Eliminar firmas
async function deleteSignature(id) {
    try {
        const confirmation = await Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción eliminará la firma de forma permanente.',
            icon: 'warning',
            buttons: ['Cancelar', 'Eliminar'],
            dangerMode: true,
        });

        if (!confirmation) {
            return;
        }

        const response = await fetch('../../backend/registros/delete_signature.php', {
            method: 'POST',
            body: JSON.stringify({ id }),
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
        });

        const result = await response.json();
        if (result.success) {
            await Swal.fire('Eliminado', 'La firma ha sido eliminada correctamente.', 'success');
            loadSignatures();
        } else {
            await Swal.fire('Error', result.message || 'No se pudo eliminar la firma.', 'error');
        }
    } catch (error) {
        console.error('Error al eliminar la firma:', error);
        await Swal.fire('Error', 'Hubo un problema al eliminar la firma.', 'error');
    }
}

loadSignatures();

// Botón para abrir el selector de archivos
document.getElementById('uploadSignatureButton').addEventListener('click', () => {
    document.getElementById('uploadSignature').click();
});

// Manejo de la carga del archivo
document.getElementById('uploadSignature').addEventListener('change', async (event) => {
    const file = event.target.files[0];
    if (!file) {
        Swal.fire('Error', 'Por favor, selecciona un archivo PNG.', 'error');
        return;
    }

    if (file.type !== 'image/png') {
        Swal.fire('Error', 'Solo se permiten imágenes PNG.', 'error');
        return;
    }

    const reader = new FileReader();

    reader.onload = async (e) => {
        const image = new Image();
        image.src = e.target.result;

        image.onload = () => {
            // Dibujar la imagen en el canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

            // Convertir el canvas a formato PNG (con fondo transparente eliminado)
            const signature = canvas.toDataURL('image/png');
            sendSignatureToBackend(signature);
        };
    };

    reader.readAsDataURL(file);
});

// Función para enviar la firma procesada al backend
async function sendSignatureToBackend(signature) {
    try {
        const response = await fetch('../../backend/registros/save_signature.php', {
            method: 'POST',
            body: JSON.stringify({ signature }),
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
        });

        const result = await response.json();
        if (result.success) {
            Swal.fire('Éxito', 'Firma cargada y guardada exitosamente.', 'success');
            loadSignatures();
        } else {
            Swal.fire('Error', result.message || 'Error al guardar la firma.', 'error');
        }
    } catch (error) {
        console.error('Error al guardar la firma:', error);
        Swal.fire('Error', 'Hubo un problema al guardar la firma.', 'error');
    }
}

</script>


<style>
.signature-module {
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    max-width: 100%; /* Aseguramos que no sobresalga */
}

.signature-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

canvas {
    max-width: 100%; /* Escala el canvas automáticamente */
    height: auto; /* Mantiene la proporción */
    border: 1px solid #000;
}

.signature-actions {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap; /* Permite que los botones se ajusten en pantallas pequeñas */
    gap: 10px;
    justify-content: center;
}

.signature-actions button {
    padding: 10px 15px;
    background: #035c67;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    min-width: 120px; /* Ancho mínimo para botones */
}

.signature-actions button:hover {
    background: #06adbf;
}

table.responsive-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table.responsive-table th, table.responsive-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

table.responsive-table th {
    background: #06adbf;
    color: #fff;
}

table.responsive-table img {
    border: 1px solid #ddd;
    border-radius: 4px;
    max-width: 100%; /* Escala las imágenes */
}

/* Media query para pantallas pequeñas */
@media screen and (max-width: 768px) {
    .signature-module {
        padding: 10px;
    }

    canvas {
        width: 100%; /* Usa todo el ancho disponible */
        height: 150px; /* Altura fija para pantallas pequeñas */
    }

    .signature-actions button {
        width: 100%; /* Botones ocupan todo el ancho */
        margin: 5px 0;
    }

    table.responsive-table th, table.responsive-table td {
        font-size: 12px; /* Texto más pequeño en pantallas pequeñas */
    }

    table.responsive-table img {
        max-width: 70%; /* Reduce el tamaño de las imágenes */
    }
}

/* Media query para pantallas muy pequeñas (teléfonos compactos) */
@media screen and (max-width: 480px) {
    canvas {
        height: 120px; /* Altura más compacta */
    }

    .signature-actions {
        flex-direction: column; /* Botones apilados verticalmente */
    }

    table.responsive-table img {
        max-width: 100px; /* Tamaño fijo para imágenes pequeñas */
    }
}

/* Mantener el estilo original del botón de eliminar */
.responsive-table .action-button {
    padding: 10px 15px;
    background: #ff4d4d; /* Color rojo original para destacar eliminación */
    color: #fff; /* Texto blanco */
    border: none;
    border-radius: 5px; /* Bordes redondeados */
    cursor: pointer;
    text-align: center;
    font-weight: bold; /* Resalta el texto */
    display: inline-block; /* Para centrar mejor */
    width: 100px; /* Mantener ancho consistente */
}

/* Estilo al pasar el ratón (hover) */
.responsive-table .action-button:hover {
    background: #ff1a1a; /* Rojo más intenso */
}

/* Centrar contenido dentro de la celda */
table.responsive-table td {
    padding: 10px;
    text-align: center; /* Centrado horizontal */
    vertical-align: middle; /* Centrado vertical */
}

</style>



        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

</body>
</html>


