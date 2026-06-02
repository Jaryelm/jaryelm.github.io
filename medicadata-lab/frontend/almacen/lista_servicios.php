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

// Verificar y crear columna estado si no existe
try {
    $sql_check = "SHOW COLUMNS FROM servicios_hospital LIKE 'estado'";
    $stmt_check = $connect->prepare($sql_check);
    $stmt_check->execute();
    $column_exists = $stmt_check->fetch();
    
    if (!$column_exists) {
        $sql_create = "ALTER TABLE servicios_hospital ADD COLUMN estado ENUM('habilitado', 'deshabilitado') DEFAULT 'habilitado'";
        $stmt_create = $connect->prepare($sql_create);
        $stmt_create->execute();
    }
} catch (Exception $e) {
    // Si hay error, continuar sin la funcionalidad de estado
}
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

    <style>
        /* Color de fondo para la columna principal */
        .codigo-column {
            background-color: #06adbf;
            color: #ffffff;
            padding: 10px;
        }
        
        /* Estilos generales para las celdas */
        #example tbody td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        /* Alternar colores de las filas */
        #example tbody tr:nth-child(even) {
            background-color: #e6f7f8;
        }

        #example tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        /* Botón de detalles */
        .btn_ver_detalles {
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
        
        /* Estilos para celdas editables */
        .editable-cell {
            cursor: pointer;
            position: relative;
            background-color: #f0f8ff;
            transition: background-color 0.3s ease;
        }
        
        .editable-cell:hover {
            background-color: #e6f3ff;
        }
        
        .editable-cell.editing {
            background-color: #fff3cd;
        }
        
        .edit-input {
            width: 100%;
            padding: 4px;
            border: 2px solid #06adbf;
            border-radius: 3px;
            background-color: white;
            font-size: 13px;
        }
        
        .edit-input:focus {
            outline: none;
            border-color: #035c67;
        }
        
        /* Estilos para botón de estado */
        .btn-estado {
            background-color: #035c67;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn-estado:hover {
            background-color: #06adbf;
            transform: scale(1.05);
        }
        
        .btn-estado:active {
            transform: scale(0.95);
        }
    </style>
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

        <!-- Título centrado -->
        <div class="table-title">
            <h1>Lista de Servicios Hospitalarios</h1>
        </div>

        <!-- Tabla para mostrar los datos -->
        <div class="table-container">
            <table id="example" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th class="codigo-column">Código de Servicio</th>
                        <th class="codigo-column">Cuenta del Servicio</th>
                        <th class="codigo-column">Nombre del Servicio</th>
                        <th class="codigo-column">Categoria</th>
                        <th class="codigo-column">Uso Servicio</th>
                        <th class="codigo-column">Precio Costo</th>
                        <th class="codigo-column">Margen de Ganancia (%)</th>
                        <th class="codigo-column">Impuesto</th>
                        <th class="codigo-column">Precio de Venta</th>
                        <th class="codigo-column">Total</th>
                        <th class="codigo-column">Fecha de Registro</th>
                        <th class="codigo-column">Acciones</th>
                    </tr>
                </thead>
                <tbody>
<?php
$stmt = $connect->prepare("SELECT *, COALESCE(estado, 'habilitado') as estado FROM servicios_hospital ORDER BY fecha_creacion DESC");
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr data-id='" . htmlspecialchars($row['id']) . "'>";
    echo "<td>" . htmlspecialchars($row['codigo_servicio']) . "</td>";
    
    echo "<td>" . htmlspecialchars($row['nombre_servicio']) . "</td>";
    // Nombre del Servicio (nomservicio) - EDITABLE
    echo "<td class='editable-cell editable-cell-text' data-campo='nomservicio' data-valor='" . htmlspecialchars($row['nomservicio'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['nomservicio']) . "</td>";
    
    echo "<td>" . htmlspecialchars($row['categoria_servicio']) . "</td>";
    echo "<td>" . htmlspecialchars($row['uso_servicio']) . "</td>";

    // Precio Costo - EDITABLE
    echo "<td class='editable-cell' data-campo='precio_costo' data-valor='" . $row['precio_costo'] . "'>" . formatNumber($row['precio_costo']) . "</td>";
    
    // Margen de Ganancia - EDITABLE  
    echo "<td class='editable-cell' data-campo='margen_ganancia' data-valor='" . $row['margen_ganancia'] . "'>" . formatNumber($row['margen_ganancia'], '%') . "</td>";
    
    echo "<td>" . htmlspecialchars($row['impuesto']) . "</td>";
    echo "<td class='precio-venta'>" . formatNumber($row['precio_venta']) . "</td>";
    echo "<td class='total'>" . formatNumber($row['total']) . "</td>";
    echo "<td>" . htmlspecialchars($row['fecha_creacion']) . "</td>";
    
    // Columna de Acciones
    $estado = $row['estado'];
    $boton_texto = ($estado === 'deshabilitado') ? 'Habilitar' : 'Deshabilitar';
    $boton_color = '#035c67';
    
    echo "<td>";
    echo "<button class='btn-estado' data-id='" . $row['id'] . "' data-estado='" . $estado . "'>";
    echo $boton_texto;
    echo "</button>";
    echo "</td>";
    
    echo "</tr>";
}

// Función para formatear números
function formatNumber($value, $suffix = '') {
    if (is_numeric($value)) {
        return number_format((float)$value, 2) . $suffix;
    }
    return 'N/A'; // Valor por defecto si no es numérico
}
?>
</tbody>
            </table>
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

<script type="text/javascript">
$(document).ready(function() {
    $('#example').DataTable({
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        order: [[0, 'desc']], // Orden descendente en la primera columna
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
        }
    });
    
    // Funcionalidad de edición inline
    let celdaEditando = null;
    
    // Evento click en celdas editables
    $(document).on('click', '.editable-cell', function() {
        // Si ya hay una celda en edición, no hacer nada
        if (celdaEditando) return;
        
        const $celda = $(this);
        const valorActual = $celda.data('valor');
        const campo = $celda.data('campo');
        const esTexto = (campo === 'nomservicio');
        
        // Marcar como editando
        $celda.addClass('editing');
        celdaEditando = $celda;
        
        // Crear input: texto para nombre, número para precio/margen
        const $input = esTexto
            ? $('<input type="text" class="edit-input" />').val(valorActual)
            : $('<input type="number" class="edit-input" step="0.01" min="0" />').val(valorActual);
        
        // Reemplazar contenido
        $celda.html($input);
        $input.focus().select();
        
        // Función para guardar cambios
        function guardarCambios() {
            const nuevoValor = esTexto ? $.trim($input.val()) : (parseFloat($input.val()) || 0);
            if (esTexto && nuevoValor === String(valorActual)) {
                cancelarEdicion();
                return;
            }
            if (!esTexto && nuevoValor === valorActual) {
                cancelarEdicion();
                return;
            }
            
            const rowId = $celda.closest('tr').data('id');
            
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    action: 'update_servicio',
                    id: rowId,
                    campo: campo,
                    valor: nuevoValor
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $celda.data('valor', nuevoValor);
                        
                        if (esTexto) {
                            $celda.text(nuevoValor);
                        } else {
                            const sufijo = campo === 'margen_ganancia' ? '%' : '';
                            $celda.html(number_format(nuevoValor, 2) + sufijo);
                        }
                        
                        if (response.nuevo_precio_venta) {
                            $celda.closest('tr').find('.precio-venta').html(response.nuevo_precio_venta);
                        }
                        if (response.nuevo_total) {
                            $celda.closest('tr').find('.total').html(response.nuevo_total);
                        }
                        
                        Swal.fire("¡Actualizado!", response.message, "success");
                    } else {
                        Swal.fire("Error", response.message, "error");
                        cancelarEdicion();
                    }
                },
                error: function() {
                    Swal.fire("Error", "No se pudo conectar con el servidor", "error");
                    cancelarEdicion();
                },
                complete: function() {
                    $celda.removeClass('editing');
                    celdaEditando = null;
                }
            });
        }
        
        function cancelarEdicion() {
            if (esTexto) {
                $celda.text(valorActual);
            } else {
                const sufijo = campo === 'margen_ganancia' ? '%' : '';
                $celda.html(number_format(valorActual, 2) + sufijo);
            }
            $celda.removeClass('editing');
            celdaEditando = null;
        }
        
        $input.on('blur', guardarCambios);
        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                guardarCambios();
            } else if (e.key === 'Escape') {
                cancelarEdicion();
            }
        });
    });
    
    // Función auxiliar para formatear números
    function number_format(number, decimals) {
        return parseFloat(number).toFixed(decimals);
    }
    
    // Manejar cambio de estado de servicios
    $(document).on('click', '.btn-estado', function() {
        const $boton = $(this);
        const servicioId = $boton.data('id');
        const estadoActual = $boton.data('estado');
        const nuevoEstado = (estadoActual === 'habilitado') ? 'deshabilitado' : 'habilitado';
        const accion = (nuevoEstado === 'habilitado') ? 'habilitar' : 'deshabilitar';
        
        Swal.fire({
            title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} servicio?`,
            text: `¿Estás seguro de que deseas ${accion} este servicio?`,
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: false,
                    visible: true
                },
                confirm: {
                    text: `Sí, ${accion}`,
                    value: true,
                    visible: true
                }
            }
        }).then((isConfirm) => {
            if (isConfirm) {
                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: {
                        action: 'cambiar_estado',
                        id: servicioId,
                        nuevo_estado: nuevoEstado
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        $boton.prop('disabled', true).text('Procesando...');
                    },
                    success: function(response) {
                        if (response.success) {
                            const nuevoTexto = (nuevoEstado === 'habilitado') ? 'Deshabilitar' : 'Habilitar';
                            $boton.text(nuevoTexto);
                            $boton.data('estado', nuevoEstado);
                            $boton.prop('disabled', false);
                            
                            Swal.fire("¡Actualizado!", response.message, "success");
                        } else {
                            $boton.prop('disabled', false);
                            const textoOriginal = (estadoActual === 'habilitado') ? 'Deshabilitar' : 'Habilitar';
                            $boton.text(textoOriginal);
                            Swal.fire("Error", response.message, "error");
                        }
                    },
                    error: function() {
                        $boton.prop('disabled', false);
                        const textoOriginal = (estadoActual === 'habilitado') ? 'Deshabilitar' : 'Habilitar';
                        $boton.text(textoOriginal);
                        Swal.fire("Error", "No se pudo conectar con el servidor", "error");
                    }
                });
            }
        });
    });
});
</script>

<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

</body>
</html>
