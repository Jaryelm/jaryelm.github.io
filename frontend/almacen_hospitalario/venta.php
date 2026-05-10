<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">

    <title>MEDIDATA</title>
</head>
<body>
<!-- Overlay de carga al recargar página (F5/Ctrl+F5) -->
<div id="page-loading-overlay">
    <div class="spinner"></div>
    <p>Cargando...</p>
</div>
<script>document.addEventListener('DOMContentLoaded',function(){var o=document.getElementById('page-loading-overlay');if(o)o.style.display='none';});</script>

<?php
include_once '../admin/menu.php';
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
include_once '../admin/perfil.php';
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

            <button class="button" onclick="cambiarColor(this, '../almacen/new_sale.php')">Nueva Venta</button>
            <button class="button" onclick="cambiarColor(this, '../almacen/cart.php')">Pagar</button>
            <button class="button" onclick="cambiarColor(this, '#')">Cotizaciones</button>
            <button class="button" onclick="cambiarColor(this, '#')">Estados de Cuenta</button>
            <button class="button" onclick="cambiarColor(this, '../almacen/venta.php')">Resumen de Ventas</button>
            <button class="button" onclick="cambiarColor(this, '../citas/mostrar.php')">Resumen de Citas</button>

            <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Facturación</h3>
                      

                    </div>
                   <div class="table-responsive" style="overflow-x:auto;">
                   <?php 

$sentencia = $connect->prepare("
    SELECT 
        orders.*, 
        GROUP_CONCAT(COALESCE(order_details.codpro, ah.codpro, product.codpro, servicios_hospital.codigo_servicio) SEPARATOR ', ') AS codigos_productos,
        AVG(order_details.discount_percentage) AS avg_discount_percentage,
        GROUP_CONCAT(COALESCE(product.impuesto, ah.impuesto, servicios_hospital.impuesto) SEPARATOR ', ') AS impuestos
    FROM orders 
    LEFT JOIN order_details ON orders.idord = order_details.order_id
    LEFT JOIN product ON order_details.product_id = product.idprcd AND order_details.item_type = 'producto'
    LEFT JOIN almacen_hospitalario ah ON order_details.hospitalario_id = ah.idprcd AND order_details.item_type = 'producto_hospitalario'
    LEFT JOIN servicios_hospital ON order_details.service_id = servicios_hospital.id AND order_details.item_type = 'servicio'
    GROUP BY orders.idord
    ORDER BY orders.placed_on DESC;
");

$sentencia->execute();
$data = [];

if ($sentencia) {
    while ($r = $sentencia->fetchObject()) {
        $data[] = $r;
    }
}
?>

<?php if(count($data) > 0): ?>
<!-- En `venta.php`, dentro de la tabla de resumen de ventas -->
<table id="example" class="responsive-table">
    <thead>
        <tr>
            <th scope="col">N. Factura</th>
            <th scope="col">Procesado Por</th>
            <th scope="col">Fecha</th>
            <th scope="col">Cliente</th>
            <th scope="col">Método de Pago</th>
            <th scope="col">Total Sin Descuento</th>
            <th scope="col">Total con Descuento</th>
            <th scope="col">Factura General</th>
            <th scope="col">Factura Desglosada</th>
            <th scope="col">Detalles</th>
            <th scope="col">Estado</th>
            <th scope="col">Cobrado Por</th>
            <th scope="col">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $d): ?>
            <tr>
                <td data-title="N. Factura"><?php echo htmlspecialchars($d->invoice_number); ?></td>
                <td data-title="Procesado Por"><?php echo htmlspecialchars($d->processed_by); ?></td>
                <td data-title="Fecha"><?php echo htmlspecialchars($d->placed_on); ?></td>
                <td data-title="Cliente"><?php echo htmlspecialchars($d->nomcl); ?></td>
                <td data-title="Método"><?php echo htmlspecialchars($d->method); ?></td>
                <td data-title="Precio Sin Descuento">LPS. <?php echo number_format($d->price_without_discount, 2); ?></td>
                <td data-title="Total con Descuento">LPS. <?php echo number_format($d->total_price, 2); ?></td>
                <td style="text-align: center;">
                    <?php 
                        if ($d->tipc === 'Boleta') {
                            echo '<i class="bx bx-show" title="Ver Factura General" onclick="verPDF(\'../almacen/documento_general.php?id='.htmlspecialchars($d->idord).'\', \'Factura General\')" style="cursor: pointer; color: #06adbf; font-size: 24px; display: inline-block; vertical-align: middle;"></i>';
                        }
                    ?>
                </td>
                <td style="text-align: center;">
                    <?php 
                        if ($d->tipc === 'Boleta') {
                            echo '<i class="bx bx-show" title="Ver Factura Desglosada" onclick="verPDF(\'../almacen/documento.php?id='.htmlspecialchars($d->idord).'\', \'Factura Desglosada\')" style="cursor: pointer; color: #06adbf; font-size: 24px; display: inline-block; vertical-align: middle;"></i>';
                        }
                    ?>
                </td>
                <td>
                    <button class="btn_ver_detalles" onclick="viewDetails(<?php echo htmlspecialchars($d->idord); ?>)">Ver Detalles</button>
                </td>
                <td>
<label class="status-switch">
        <input 
            type="checkbox" 
            data-id="<?php echo htmlspecialchars($d->idord); ?>" 
            data-current-status="<?php echo htmlspecialchars($d->invoice_status); ?>"
            onchange="updateStatus(<?php echo htmlspecialchars($d->idord); ?>, this.checked ? 'Cobrada' : 'Pendiente')" 
            <?php echo $d->invoice_status == 'Cobrada' ? 'checked' : ''; ?>>
        <span class="status-slider"></span>
    </label>
</td>
                <td data-title="Procesado Por"><?php echo htmlspecialchars($d->updated_by); ?></td>
<td>
    <button class="btn_devolucion" onclick="iniciarDevolucion(<?php echo htmlspecialchars($d->idord); ?>)">Devolución</button>
</td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
function updateStatus(id, status) {
    swal({
        title: "¿Estás seguro?",
        text: `Vas a cambiar el estado a "${status}". Esta acción solo puede realizarse una vez y no se puede deshacer.`,
        icon: "warning",
        buttons: ["Cancelar", "Confirmar"],
        dangerMode: true,
    }).then((willChange) => {
        if (willChange) {
            // Si el usuario confirma, envía la solicitud al backend
            $.ajax({
                url: 'update_invoice_status.php',
                method: 'POST',
                data: { id: id, status: status },
                success: function(response) {
                    swal("¡Actualizado!", "El estado de la factura se actualizó correctamente", "success").then(function() {
                        location.reload(); // Recargar la página para reflejar los cambios
                    });
                },
                error: function() {
                    swal("Error!", "Hubo un problema al actualizar el estado", "error").then(function() {
                        location.reload(); // Intentar nuevamente recargando
                    });
                }
            });
        } else {
            // Si el usuario cancela, revertir el cambio en el deslizador
            const slider = document.querySelector(`input[data-id="${id}"]`);
            slider.checked = status === 'Cobrada';
        }
    });
}
</script>

<style>
/* Estilo único para el botón deslizable */
.status-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.status-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.status-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: background-color 0.4s;
    border-radius: 34px;
}

.status-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    border-radius: 50%;
    background-color: white;
    transition: transform 0.4s ease-in-out;
    top: 4px; /* Mantiene el círculo centrado verticalmente */
    left: 4px; /* Posición inicial del círculo */
}

/* Efecto cuando el checkbox está marcado */
.status-switch input:checked + .status-slider {
    background-color: #4CAF50;
}

.status-switch input:checked + .status-slider:before {
    transform: translateX(26px); /* Deslizar a la derecha */
}

/* Etiquetas de texto */
.slider:before {
    content: attr(data-off);
    position: absolute;
    top: 50%;
    left: 5px;
    transform: translateY(-50%);
    color: #fff;
    font-size: 12px;
}

input:checked + .slider:before {
    content: attr(data-on);
    left: 30px;
}
</style>

<?php else: ?>
    <div class="alert">
        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
        <strong>Danger!</strong> No hay datos.
    </div>
<?php endif; ?>

<!-- Modal para Detalles -->
<div id="detailsModal" class="modal" style="display: none !important;">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2>Detalles de Productos y Servicios</h2>
        <div class="table-container">
            <table id="detailsTable" class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Impuesto</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Total</th>
                        <th scope="col">Descuento General</th>
                        <th scope="col">Descuento 3ra Edad</th>
                        <th scope="col">Descuento 4ta Edad</th>
                        <th scope="col">Promoción</th>
                        <th scope="col">Otros Descuentos</th>
                        <th scope="col">Descuentos Aplicados</th>
                        <th scope="col">Total a Pagar Sin I.S.V</th>
                    </tr>
                </thead>
                <tbody id="detailsTableBody">
                    <!-- Los datos se llenarán dinámicamente con JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de carga -->
<div id="loading-modal" style="display: none !important;">
    <div class="spinner"></div>
    <p>Cargando devolución...</p>
</div>

<!-- Modal para Devolución -->
<div id="devolutionModal" class="modal" style="display: none !important;">
    <div class="modal-content">
        <span class="close-btn" onclick="closeDevolutionModal()">&times;</span>
        <h2>Devolución de Productos</h2>
        <div class="table-container">
            <table id="devolutionTable" class="responsive-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Cantidad Original</th>
                        <th>Total con Descuento</th>
                        <th>Cantidad a Devolver</th>
                        <th>Motivo</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="devolutionTableBody">
                    <!-- Se llenará dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Visualizar PDF -->
<div id="pdfModal" class="modal-pdf">
    <div class="modal-pdf-content">
        <div class="modal-pdf-header">
            <h2 id="pdfModalTitle">Visualizar Documento</h2>
            <span class="close-pdf-btn" onclick="cerrarPDFModal()">&times;</span>
        </div>
        <div class="modal-pdf-body">
            <iframe id="pdfFrame" src="" frameborder="0"></iframe>
        </div>
        <div class="modal-pdf-footer">
            <button class="btn-descargar-pdf" onclick="descargarPDFActual()">
                <i class="bx bx-download"></i> Descargar PDF
            </button>
        </div>
    </div>
</div>

<script>
// Función para abrir el modal y cargar los detalles
function viewDetails(orderId) {
    showLoadingModal();
    const loadingP = document.getElementById("loading-modal")?.querySelector("p");
    if (loadingP) loadingP.textContent = "Cargando detalles...";

    fetch(`../../backend/registros/obtener_detalles_checkout.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            hideLoadingModal();
            if (loadingP) loadingP.textContent = "Cargando devolución...";

            const modal = document.getElementById("detailsModal");
            modal.style.display = "flex";

            const detailsTableBody = document.getElementById("detailsTableBody");
            detailsTableBody.innerHTML = "";
            
            data.forEach(item => {
                const row = document.createElement("tr");
                
                // Función auxiliar para formatear números
                const formatCurrency = (value) => {
                    // Si el valor es null, undefined o NaN, mostrar 0.00
                    const numValue = parseFloat(value) || 0;
                    return `LPS. ${numValue.toLocaleString('es-HN', { 
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2 
                    })}`;
                };

                row.innerHTML = `
                    <td>${item.codigo || 'N/A'}</td>
                    <td>${item.impuesto || 'N/A'}</td>
                    <td>${item.nombre || 'N/A'}</td>
                    <td>${item.cantidad || 0}</td>
                    <td>${formatCurrency(item.total_original)}</td>
                    <td>${formatCurrency(item.discount_percentage)}</td>
                    <td>${formatCurrency(item.age_discount_30)}</td>
                    <td>${formatCurrency(item.age_discount_40)}</td>
                    <td>${formatCurrency(item.promotion_discount)}</td>
                    <td>${formatCurrency(item.other_discount)}</td>
                    <td>${formatCurrency(item.total_discount)}</td>
                    <td>${formatCurrency(item.total_after_discount)}</td>
                `;
                detailsTableBody.appendChild(row);
            });
        })
        .catch(err => {
            hideLoadingModal();
            if (loadingP) loadingP.textContent = "Cargando devolución...";
            console.error('Error al obtener detalles:', err);
            swal("Error", "No se pudieron cargar los detalles", "error");
        });
}

// Función para cerrar el modal
function closeModal() {
    const modal = document.getElementById("detailsModal");
    if (modal) {
        modal.style.display = "none";
    }
}

// Funciones para el modal de devolución
function openDevolutionModal(orderId) {
    const modal = document.getElementById("devolutionModal");
    modal.style.display = "flex";

    // Lógica para cargar datos de devolución si es necesario
    // Puedes hacer un fetch aquí si necesitas cargar datos específicos para la devolución
}

function showLoadingModal() {
    const loading = document.getElementById("loading-modal");
    if (loading) loading.style.display = "flex";
}
function hideLoadingModal() {
    const loading = document.getElementById("loading-modal");
    if (loading) loading.style.display = "none";
}

function iniciarDevolucion(orderId) {
    showLoadingModal();
    const devModal = document.getElementById("devolutionModal");

    fetch(`../../backend/registros/obtener_detalles_checkout.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            hideLoadingModal();
            devModal.style.display = "flex";

            const tbody = document.getElementById("devolutionTableBody");
            tbody.innerHTML = "";

            data.forEach(item => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>${item.codigo}</td>
                    <td>${item.nombre}</td>
                    <td>${item.cantidad}</td>
                    <td>LPS. ${parseFloat(item.total_after_discount).toFixed(2)}</td>
                    <td>
                        <input type="number" 
                               class="devolution-input" 
                               min="1" 
                               max="${item.cantidad}"
                               data-product-id="${item.codigo}">
                    </td>
                    <td>
                        <select class="devolution-reason" onchange="toggleOtroMotivo(this)">
                            <option value="">Seleccione motivo</option>
                            <option value="defectuoso">Producto defectuoso</option>
                            <option value="error_pedido">Error en pedido</option>
                            <option value="solicitud_paciente">Solicitud Paciente</option>
                            <option value="otros">Otros</option>
                        </select>
                        <textarea 
                            class="otro-motivo" 
                            style="display:none; margin-top:5px; width:100%; resize:vertical;" 
                            placeholder="Especifique el motivo"
                            rows="2"
                        ></textarea>
                    </td>
                    <td>
                        <button class="btn-devolver" 
                                onclick="procesarDevolucion(${orderId}, '${item.codigo}', this)">
                            Devolver
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(err => {
            hideLoadingModal();
            console.error("Error al cargar devolución:", err);
            swal("Error", "No se pudieron cargar los datos de devolución", "error");
        });
}

// Función para procesar la devolución
function procesarDevolucion(orderId, productId, buttonElement) {
    const row = buttonElement.closest('tr');
    const cantidadInput = row.querySelector('.devolution-input');
    const motivoSelect = row.querySelector('.devolution-reason');
    const otroMotivoTextarea = row.querySelector('.otro-motivo');
    
    const cantidad = parseInt(cantidadInput.value);
    let motivo = motivoSelect.value;

    // Si el motivo es "otros", usar el texto del textarea
    if (motivo === 'otros') {
        const otroMotivo = otroMotivoTextarea.value.trim();
        if (!otroMotivo) {
            swal("Error", "Por favor especifique el motivo de la devolución", "error");
            return;
        }
        motivo = `Otros: ${otroMotivo}`;
    }

    // Validaciones
    if (!cantidad || !motivo) {
        swal("Error", "Por favor complete todos los campos", "error");
        return;
    }

    // Confirmar devolución
    swal({
        title: "¿Está seguro?",
        text: "Esta acción no se puede deshacer",
        icon: "warning",
        buttons: ["Cancelar", "Sí, devolver"],
        dangerMode: true,
    }).then((willReturn) => {
        if (willReturn) {
            // Enviar solicitud al servidor
            fetch('../../backend/registros/procesar_devolucion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    orderId: orderId,
                    productId: productId,
                    cantidad: cantidad,
                    motivo: motivo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    swal("¡Éxito!", "Devolución procesada correctamente", "success")
                    .then(() => {
                        location.reload();
                    });
                } else {
                    swal("Error", data.message || "Error al procesar la devolución", "error");
                }
            });
        }
    });
}

function closeDevolutionModal() {
    const modal = document.getElementById("devolutionModal");
    modal.style.display = "none";
}

// Asegurarse de que el modal esté oculto al cargar la página
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("detailsModal");
    if (modal) {
        modal.style.display = "none"; // Ocultar modal al cargar la página
    }

    const devolutionModal = document.getElementById("devolutionModal");
    if (devolutionModal) {
        devolutionModal.style.display = "none"; // Ocultar modal de devolución al cargar la página
    }
});

// Cerrar modal si se hace clic fuera de él
window.onclick = function (event) {
    const modal = document.getElementById("detailsModal");
    if (event.target === modal) {
        closeModal();
    }

    const devolutionModal = document.getElementById("devolutionModal");
    if (event.target === devolutionModal) {
        closeDevolutionModal();
    }
};

function toggleOtroMotivo(selectElement) {
    const textArea = selectElement.nextElementSibling;
    textArea.style.display = selectElement.value === 'otros' ? 'block' : 'none';
    if (selectElement.value !== 'otros') {
        textArea.value = '';
    }
}

// Funciones para el modal de visualización de PDF
let pdfUrlActual = '';

function verPDF(url, titulo) {
    // Agregar parámetro view=inline para mostrar el PDF en línea
    const separator = url.includes('?') ? '&' : '?';
    const urlConView = url + separator + 'view=inline';
    
    pdfUrlActual = url; // Guardar URL original para descarga
    const modal = document.getElementById('pdfModal');
    const frame = document.getElementById('pdfFrame');
    const title = document.getElementById('pdfModalTitle');
    
    title.textContent = titulo;
    frame.src = urlConView;
    modal.style.display = 'flex';
    
    // Prevenir scroll del body cuando el modal está abierto
    document.body.style.overflow = 'hidden';
}

function cerrarPDFModal() {
    const modal = document.getElementById('pdfModal');
    const frame = document.getElementById('pdfFrame');
    
    modal.style.display = 'none';
    frame.src = ''; // Limpiar el iframe
    pdfUrlActual = '';
    
    // Restaurar scroll del body
    document.body.style.overflow = 'auto';
}

function descargarPDFActual() {
    if (pdfUrlActual) {
        window.open(pdfUrlActual, '_blank');
    }
}

// Cerrar modal al hacer clic fuera de él
window.addEventListener('click', function(event) {
    const pdfModal = document.getElementById('pdfModal');
    if (event.target === pdfModal) {
        cerrarPDFModal();
    }
});
</script>

<!-- Estilos del Modal y Botón -->
<style>

.modal-content {
    max-height: 90%;
    overflow-y: auto; /* Permite desplazamiento si hay muchos elementos */
    overflow-x: hidden; /* Evita desbordamientos horizontales */
}

/* Ajustar el modal */
.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-height: 90%; /* Limita el alto del modal */
    overflow: hidden; /* Evita el desbordamiento inicial */
    position: relative; /* Asegura que los elementos internos se ajusten correctamente */
}

/* Contenedor desplazable para la tabla */
.table-container {
    max-height: 60vh; /* Limitar el contenedor de la tabla */
    overflow-y: auto; /* Desplazamiento vertical */
    overflow-x: auto; /* Desplazamiento horizontal */
    margin-top: 10px;
    flex-grow: 1; /* Asegura que la tabla ocupe todo el espacio restante */
}

/* Estilo general para la tabla */
.responsive-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}
.responsive-table th, .responsive-table td {
    padding: 10px;
    border: 1px solid #ddd;
}
.responsive-table th {
    background-color: #f2f2f2;
    font-weight: bold;
}

.modal {
    display: none; /* Ocultar modal al cargar */
    position: fixed;
    z-index: 1000;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex; /* Usamos flexbox para centrar */
    justify-content: center; /* Centrado horizontal */
    align-items: center; /* Centrado vertical */
}

.modal-content {
    background-color: #fefefe;
    border: 1px solid #888;
    padding: 20px;
    width: 80%;
    max-height: 90%; /* Limitar el alto máximo del modal */
    overflow: hidden; /* Evitar desbordamientos */
    position: relative;
    display: flex;
    flex-direction: column; /* Asegura que los hijos se apilen en columnas */
}

.close-btn {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.close-btn:hover, .close-btn:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.btn_ver_detalles {
    background-color: #035c67;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.btn_devolucion {
    background-color: #035c67;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn_ver_detalles:hover {
    background-color: #06adbf;
}
.btn_devolucion:hover {
    background-color: #06adbf;
}

/* Estilos para el modal de visualización de PDF */
.modal-pdf {
    display: none;
    position: fixed;
    z-index: 2000;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.85);
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal-pdf-content {
    background-color: #fff;
    width: 95%;
    max-width: 1200px;
    height: 90vh;
    display: flex;
    flex-direction: column;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-pdf-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #035c67;
    color: white;
    border-radius: 8px 8px 0 0;
}

.modal-pdf-header h2 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.close-pdf-btn {
    color: white;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
    transition: color 0.3s ease;
}

.close-pdf-btn:hover {
    color: #ff6b6b;
}

.modal-pdf-body {
    flex: 1;
    padding: 0;
    overflow: hidden;
    position: relative;
}

.modal-pdf-body iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

.modal-pdf-footer {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 8px 8px;
    text-align: center;
}

.btn-descargar-pdf {
    background-color: #035c67;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-descargar-pdf:hover {
    background-color: #06adbf;
}

.btn-descargar-pdf i {
    font-size: 1.2rem;
}

/* Responsive para móviles */
@media (max-width: 768px) {
    .modal-pdf-content {
        width: 100%;
        height: 100vh;
        border-radius: 0;
    }
    
    .modal-pdf-header {
        border-radius: 0;
    }
    
    .modal-pdf-footer {
        border-radius: 0;
    }
}
</style>
                    </div>
                </div>
            </div>  

        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>
    
    <!-- Data Tables -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script type="text/javascript">
$(document).ready(function() {
    $('#example').DataTable({
        pageLength: 10, // Registros por página
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        order: [[1, 'desc']], // Asegúrate de que el índice de la columna de fecha sea correcto (por ejemplo, 1 si es la segunda columna)
        language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
            "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
            "sSearch": "Buscar:",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        },
        columnDefs: [
            {
                targets: 1, // Ajustar al índice correcto de la columna de fecha
                type: 'datetime', // Configurar la columna como datetime para un orden correcto
                render: function(data, type, row) {
                    // Asegurarte de que la fecha esté en el formato correcto para la ordenación
                    return type === 'sort' ? new Date(data).getTime() : data;
                }
            }
        ],
        "ordering": true, // Asegúrate de que la opción de ordenar está habilitada
        "orderMulti": false // Desactivar ordenación múltiple si no se necesita
    });
});
</script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

</body>
</html>