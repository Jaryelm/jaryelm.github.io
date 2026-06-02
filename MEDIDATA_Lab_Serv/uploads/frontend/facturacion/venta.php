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
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">

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
include_once '../facturacion/menu.php';
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
include_once '../facturacion/perfil.php';
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

                <button class="button" onclick="cambiarColor(this, '../facturacion/new_sale.php')">Nueva Venta</button>
                <button class="button" onclick="cambiarColor(this, '../facturacion/cart.php')">Procesar Venta</button>
                <button class="button" onclick="cambiarColor(this, '#')">Cotizaciones</button>
                <button class="button" onclick="cambiarColor(this, '#')">Estados de Cuenta</button>
                <button class="button" onclick="cambiarColor(this, '../facturacion/venta.php')">Resumen de Ventas</button>
                <button class="button" onclick="cambiarColor(this, '../facturacion/mostrar.php')">Resumen de Citas</button>

            <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Facturación</h3>
                      

                    </div>
                   <div class="table-responsive" style="overflow-x:auto;">
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
    <tbody></tbody>
</table>

<script>
function updateStatus(id, status) {
    const slider = document.querySelector(`input[data-id="${id}"]`);

    // Regla de negocio: solo se permite marcar a Cobrada una vez.
    if (status !== 'Cobrada') {
        if (slider) slider.checked = true;
        Swal.fire({
            title: "Acción no permitida",
            text: "Esta factura ya fue cobrada y no puede volver a Pendiente.",
            icon: "warning",
            confirmButtonText: "Aceptar"
        });
        return;
    }

    Swal.fire({
        title: "¿Estás seguro?",
        text: 'Vas a cambiar el estado a "Cobrada". Esta acción solo puede realizarse una vez y no se puede deshacer.',
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Confirmar",
        cancelButtonText: "Cancelar",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'update_invoice_status.php',
                method: 'POST',
                data: { id: id, status: status },
                success: function(html) {
                    var $tmp = $('<div>').html(html);
                    $tmp.find('script').each(function() {
                        $.globalEval(this.text || this.textContent || this.innerHTML || '');
                    });
                },
                error: function() {
                    Swal.fire({
                        title: "Error!",
                        text: "Hubo un problema al actualizar el estado",
                        icon: "error",
                        confirmButtonText: "Aceptar"
                    }).then(function() {
                        location.reload();
                    });
                }
            });
        } else {
            if (slider) slider.checked = false;
        }
    });
}
</script>

<!-- Modal para Detalles (oculto hasta pulsar Ver Detalles) -->
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

<!-- Modal de carga (spinner como MH-PACS) -->
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
    document.getElementById("loading-modal").querySelector("p").textContent = "Cargando detalles...";

    fetch(`../../backend/registros/obtener_detalles_checkout.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            hideLoadingModal();
            document.getElementById("loading-modal").querySelector("p").textContent = "Cargando devolución...";

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
                    <td>${item.impuesto !== null && item.impuesto !== '' ? item.impuesto : 'N/A'}</td>
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
            document.getElementById("loading-modal").querySelector("p").textContent = "Cargando devolución...";
            console.error('Error al obtener detalles:', err);
            Swal.fire({
                title: "Error",
                text: "No se pudieron cargar los detalles",
                icon: "error",
                confirmButtonText: "Aceptar"
            });
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
            Swal.fire({
                title: "Error",
                text: "No se pudieron cargar los datos de devolución",
                icon: "error",
                confirmButtonText: "Aceptar"
            });
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
            Swal.fire({
                title: "Error",
                text: "Por favor especifique el motivo de la devolución",
                icon: "error",
                confirmButtonText: "Aceptar"
            });
            return;
        }
        motivo = `Otros: ${otroMotivo}`;
    }

    // Validaciones
    if (!cantidad || !motivo) {
        Swal.fire({
            title: "Error",
            text: "Por favor complete todos los campos",
            icon: "error",
            confirmButtonText: "Aceptar"
        });
        return;
    }

    // Confirmar devolución
    Swal.fire({
        title: "¿Está seguro?",
        text: "Esta acción no se puede deshacer",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, devolver",
        cancelButtonText: "Cancelar",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
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
                    Swal.fire({
                        title: "¡Éxito!",
                        text: "Devolución procesada correctamente",
                        icon: "success",
                        confirmButtonText: "Aceptar"
                    })
                    .then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: "Error",
                        text: data.message || "Error al procesar la devolución",
                        icon: "error",
                        confirmButtonText: "Aceptar"
                    });
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

function verPDF(url) {
    const separator = url.includes('?') ? '&' : '?';
    const defaultZoom = 125;
    window.open(url + separator + 'view=inline#zoom=' + defaultZoom, '_blank');
}
</script>

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
        processing: true,
        serverSide: true,
        searchDelay: 500,
        pageLength: 10, // Registros por página
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        ajax: {
            url: '../../backend/registros/ventas_datatable.php',
            type: 'POST',
            data: function (d) {
                return {
                    draw: d.draw,
                    start: d.start,
                    length: d.length,
                    search: d.search ? d.search.value : '',
                    order_column: (d.order && d.order[0]) ? d.order[0].column : 2,
                    order_dir: (d.order && d.order[0]) ? d.order[0].dir : 'desc',
                    include_anular: false
                };
            },
            dataFilter: function (rawResponse) {
                const text = (rawResponse || '').toString().trim();
                if (!text) return text;
                const firstBrace = text.indexOf('{');
                const lastBrace = text.lastIndexOf('}');
                if (firstBrace !== -1 && lastBrace !== -1 && lastBrace > firstBrace) {
                    return text.slice(firstBrace, lastBrace + 1);
                }
                return text;
            },
            error: function (xhr, textStatus, errorThrown) {
                const responseText = (xhr && xhr.responseText ? xhr.responseText : '').toString();
                console.error('DataTables AJAX error debug =>', {
                    url: xhr.responseURL,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    textStatus: textStatus,
                    errorThrown: errorThrown,
                    responseText: responseText
                });
                const dtInfo = document.querySelector('#example_info');
                if (dtInfo) {
                    dtInfo.textContent = 'Error de búsqueda. Revisa consola para detalle.';
                    dtInfo.style.color = '#b42318';
                }
            }
        },
        order: [[2, 'desc']],
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
                targets: [7, 8, 9, 10, 12],
                orderable: false,
                searchable: false
            },
            { targets: [5, 6], className: 'dt-body-right' }
        ],
        "ordering": true,
        "orderMulti": false
    });

    const table = $('#example').DataTable();
    const $searchInput = $('div.dataTables_filter input');
    $searchInput.off('.DT');
    $searchInput.on('keyup.DT', function (e) {
        if (e.key === 'Enter') {
            table.search(this.value).draw();
        }
    });
});
</script>

    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <!-- Función Cierre de Caja -->
    <script src='../../backend/registros/script/cierre_caja.js'></script>

</body>
</html>
