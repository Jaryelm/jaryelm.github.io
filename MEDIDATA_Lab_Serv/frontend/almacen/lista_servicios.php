<?php
// Procesar cambio de estado via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cambiar_estado') {
    // Limpiar cualquier output buffer previo
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    require_once('../../backend/bd/Conexion.php');
    header('Content-Type: application/json');
    
    $id = intval($_POST['id']);
    $nuevo_estado = $_POST['nuevo_estado'];
    
    // Validar que el estado sea válido
    $estados_permitidos = ['habilitado', 'deshabilitado'];
    if (!in_array($nuevo_estado, $estados_permitidos)) {
        echo json_encode(['success' => false, 'message' => 'Estado no válido']);
        exit;
    }
    
    try {
        $sql = "UPDATE servicios_hospital SET estado = ? WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $resultado = $stmt->execute([$nuevo_estado, $id]);
        
        if ($resultado && $stmt->rowCount() > 0) {
            $accion = ($nuevo_estado === 'habilitado') ? 'habilitado' : 'deshabilitado';
            echo json_encode([
                'success' => true, 
                'message' => "Servicio $accion correctamente"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el servicio']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Procesar guardado de cambios via AJAX antes de cualquier output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_servicio') {
    // Limpiar cualquier output buffer previo
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    require_once('../../backend/bd/Conexion.php');
    header('Content-Type: application/json');
    
    $id = intval($_POST['id']);
    $campo = $_POST['campo'];
    
    // Validar que el campo sea válido
    $campos_permitidos = ['precio_costo', 'margen_ganancia', 'nomservicio'];
    if (!in_array($campo, $campos_permitidos)) {
        echo json_encode(['success' => false, 'message' => 'Campo no válido']);
        exit;
    }
    
    // Valor según tipo de campo
    $valor_actualizar = ($campo === 'nomservicio') ? trim($_POST['valor'] ?? '') : floatval($_POST['valor'] ?? 0);
    
    try {
        // Actualizar el campo específico
        $sql = "UPDATE servicios_hospital SET {$campo} = ? WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $resultado = $stmt->execute([$valor_actualizar, $id]);
        
        if ($resultado) {
            // Nombre: solo confirmar
            if ($campo === 'nomservicio') {
                echo json_encode(['success' => true, 'message' => 'Nombre actualizado correctamente']);
                exit;
            }
            // Si se actualizó el precio_costo o margen_ganancia, recalcular el total
            if ($campo === 'precio_costo' || $campo === 'margen_ganancia') {
                // Obtener los valores actuales
                $stmt_get = $connect->prepare("SELECT precio_costo, margen_ganancia, precio_venta FROM servicios_hospital WHERE id = ?");
                $stmt_get->execute([$id]);
                $servicio = $stmt_get->fetch(PDO::FETCH_ASSOC);
                
                // Recalcular precio de venta y total si cambió el precio_costo o margen
                if ($campo === 'precio_costo' || $campo === 'margen_ganancia') {
                    $nuevo_precio_venta = $valor_actualizar * (1 + ($servicio['margen_ganancia'] / 100));
                    if ($campo === 'margen_ganancia') {
                        $nuevo_precio_venta = $servicio['precio_costo'] * (1 + ($valor_actualizar / 100));
                    }
                    
                    // Actualizar precio_venta y total
                    $stmt_update = $connect->prepare("UPDATE servicios_hospital SET precio_venta = ?, total = ? WHERE id = ?");
                    $stmt_update->execute([$nuevo_precio_venta, $nuevo_precio_venta, $id]);
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Actualizado correctamente',
                        'nuevo_precio_venta' => number_format($nuevo_precio_venta, 2),
                        'nuevo_total' => number_format($nuevo_precio_venta, 2)
                    ]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Actualizado correctamente']);
                }
            } else {
                echo json_encode(['success' => true, 'message' => 'Actualizado correctamente']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

include_once '../../backend/registros/session_check.php';
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
    <link rel="stylesheet" href="../../backend/css/reporte_compras_datatable.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>

<?php
include_once '../admin/menu.php';
// incuir el archivo menu principal
?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#">
            <div class="form-group"></div>
        </form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>


        <!-- Botones de navegación -->
        <button class="button" onclick="cambiarColor(this, 'compra_unificada.php')">Compra e inventario</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_compras.php')">Compras Registradas</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar.php')">Lista de Inventario</button>
        <button class="button" onclick="cambiarColor(this, 'categoria_new.php')">Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'categoria.php')">Lista de Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'nuevo_servicio.php')">Registrar Servicio</button>
        <button class="button" onclick="cambiarColor(this, 'lista_servicios.php')">Lista de Servicios</button>
        <button class="button" onclick="cambiarColor(this, 'reorden.php')">Punto de Reorden</button>
        <button class="button" onclick="cambiarColor(this, 'lista_solicitud_reorden_admin.php')">Autorización Compras Almacen</button>
        <button class="button" onclick="cambiarColor(this, 'lista_requisiciones.php')">Requisiciones</button>

        <div class="catalog-container">
            <h2 class="catalog-title">Lista de Servicios Hospitalarios</h2>
            <p class="catalog-hint">Haga clic en <strong>Nombre del Servicio</strong>, <strong>Precio Costo</strong> o <strong>Margen</strong> para editar. Use <strong>Acciones</strong> para habilitar o deshabilitar.</p>

            <div class="table-container">
                <div class="table-responsive">
                    <table id="example" class="display responsive-table dt-medidata-unificado">
                        <thead>
                            <tr>
                                <th>Código de Servicio</th>
                                <th>Cuenta del Servicio</th>
                                <th>Nombre del Servicio</th>
                                <th>Categoria</th>
                                <th>Uso Servicio</th>
                                <th>Precio Costo</th>
                                <th>Margen de Ganancia (%)</th>
                                <th>Impuesto</th>
                                <th>Precio de Venta</th>
                                <th>Total</th>
                                <th>Fecha de Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</section>

<!-- jQuery -->
<script src="../../backend/js/jquery.min.js"></script>

<!-- DataTables JS -->
<script src="../../backend/js/datatable.js"></script>
<script src="../../backend/js/datatablebuttons.js"></script>
<script src="../../backend/js/jszip.js"></script>
<script src="../../backend/js/pdfmake.js"></script>
<script src="../../backend/js/vfs_fonts.js"></script>
<script src="../../backend/js/buttonshtml5.js"></script>
<script src="../../backend/js/buttonsprint.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

<script type="text/javascript">
$(document).ready(function() {
    $('#example').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: false,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        ajax: {
            url: '../../backend/registros/get_servicios_hospital.php',
            type: 'GET',
            data: function(d) { d.layout = 'full'; }
        },
        order: [[10, 'desc']],
        columns: [
            { data: 'codigo_servicio' },
            { data: 'nombre_servicio' },
            {
                data: 'nomservicio',
                render: function(data, type) {
                    if (type !== 'display') return data;
                    var esc = $('<div>').text(data).html();
                    return '<span class="editable-cell editable-cell-text" data-campo="nomservicio" data-valor="' + esc + '">' + esc + '</span>';
                }
            },
            { data: 'categoria_servicio' },
            { data: 'uso_servicio' },
            {
                data: 'precio_costo',
                render: function(data, type, row) {
                    if (type !== 'display') return data;
                    return '<span class="editable-cell" data-campo="precio_costo" data-valor="' + data + '">' + row.precio_costo_fmt + '</span>';
                }
            },
            {
                data: 'margen_ganancia',
                render: function(data, type, row) {
                    if (type !== 'display') return data;
                    return '<span class="editable-cell" data-campo="margen_ganancia" data-valor="' + data + '">' + row.margen_ganancia_fmt + '</span>';
                }
            },
            { data: 'impuesto' },
            {
                data: 'precio_venta',
                className: 'precio-venta',
                render: function(data, type, row) {
                    return type === 'display' ? row.precio_venta_fmt : data;
                }
            },
            {
                data: 'total',
                className: 'total',
                render: function(data, type, row) {
                    return type === 'display' ? row.total_fmt : data;
                }
            },
            { data: 'fecha_creacion' },
            {
                data: 'estado',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (type !== 'display') return data;
                    return '<button type="button" class="btn-estado" data-id="' + row.id + '" data-estado="' + data + '">' + row.btn_estado_texto + '</button>';
                }
            }
        ],
        createdRow: function(row, data) {
            $(row).attr('data-id', data.id);
        },
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sInfo: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            sInfoEmpty: 'Mostrando 0 a 0 de 0 registros',
            sInfoFiltered: '(filtrado de _MAX_ registros totales)',
            sSearch: 'Buscar:',
            oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' }
        }
    });

    var celdaEditando = null;

    function fmtNum(n, dec) { return parseFloat(n).toFixed(dec); }

    function restaurarCelda($celda, valor, campo) {
        if (campo === 'nomservicio') {
            $celda.text(valor);
        } else {
            var suf = campo === 'margen_ganancia' ? '%' : '';
            $celda.html(fmtNum(valor, 2) + suf);
        }
        $celda.removeClass('editing');
        celdaEditando = null;
    }

    $(document).on('click', '.editable-cell', function() {
        if (celdaEditando) return;

        var $celda = $(this);
        var valorActual = $celda.data('valor');
        var campo = $celda.data('campo');
        var esTexto = (campo === 'nomservicio');

        $celda.addClass('editing');
        celdaEditando = $celda;

        var $input = esTexto
            ? $('<input type="text" class="edit-input" />').val(valorActual)
            : $('<input type="number" class="edit-input" step="0.01" min="0" />').val(valorActual);

        $celda.html($input);
        $input.focus().select();

        function guardarCambios() {
            var nuevoValor = esTexto ? $.trim($input.val()) : (parseFloat($input.val()) || 0);
            if (String(nuevoValor) === String(valorActual)) {
                restaurarCelda($celda, valorActual, campo);
                return;
            }

            $.ajax({
                url: window.location.href,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'update_servicio',
                    id: $celda.closest('tr').data('id'),
                    campo: campo,
                    valor: nuevoValor
                }
            }).done(function(response) {
                if (response.success) {
                    $celda.data('valor', nuevoValor);
                    restaurarCelda($celda, nuevoValor, campo);
                    if (response.nuevo_precio_venta) {
                        $celda.closest('tr').find('.precio-venta').html(response.nuevo_precio_venta);
                    }
                    if (response.nuevo_total) {
                        $celda.closest('tr').find('.total').html(response.nuevo_total);
                    }
                    Swal.fire('¡Actualizado!', response.message, 'success');
                } else {
                    Swal.fire('Error', response.message || 'No se pudo actualizar', 'error');
                    restaurarCelda($celda, valorActual, campo);
                }
            }).fail(function() {
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                restaurarCelda($celda, valorActual, campo);
            });
        }

        $input.on('blur', guardarCambios);
        $input.on('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); guardarCambios(); }
            if (e.key === 'Escape') { restaurarCelda($celda, valorActual, campo); }
        });
    });

    $(document).on('click', '.btn-estado', function() {
        var $boton = $(this);
        var servicioId = $boton.data('id');
        var estadoActual = $boton.data('estado');
        var nuevoEstado = (estadoActual === 'habilitado') ? 'deshabilitado' : 'habilitado';
        var accion = (nuevoEstado === 'habilitado') ? 'habilitar' : 'deshabilitar';
        var textoOriginal = $boton.text();

        Swal.fire({
            title: accion.charAt(0).toUpperCase() + accion.slice(1) + ' servicio',
            text: '¿Está seguro de que desea ' + accion + ' este servicio?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#035c67',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, ' + accion,
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            $boton.prop('disabled', true).text('Procesando...');
            $.ajax({
                url: window.location.href,
                method: 'POST',
                dataType: 'json',
                data: { action: 'cambiar_estado', id: servicioId, nuevo_estado: nuevoEstado }
            }).done(function(response) {
                if (response.success) {
                    $boton.data('estado', nuevoEstado);
                    $boton.text((nuevoEstado === 'habilitado') ? 'Deshabilitar' : 'Habilitar');
                    Swal.fire('¡Actualizado!', response.message, 'success');
                } else {
                    Swal.fire('Error', response.message || 'No se pudo actualizar', 'error');
                    $boton.text(textoOriginal);
                }
            }).fail(function() {
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                $boton.text(textoOriginal);
            }).always(function() {
                $boton.prop('disabled', false);
            });
        });
    });
});
</script>

<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>

</body>
</html>
