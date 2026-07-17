<?php
// AJAX: cambio de estado / edición inline (antes de cualquier output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    while (ob_get_level()) {
        ob_end_clean();
    }

    include_once '../../backend/registros/session_check.php';
    header('Content-Type: application/json; charset=utf-8');

    $action = (string) $_POST['action'];

    if ($action === 'cambiar_estado') {
        $id = (int) ($_POST['id'] ?? 0);
        $nuevo_estado = (string) ($_POST['nuevo_estado'] ?? '');
        $estados_permitidos = ['habilitado', 'deshabilitado'];

        if ($id <= 0 || !in_array($nuevo_estado, $estados_permitidos, true)) {
            echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
            exit;
        }

        try {
            $stmt = $connect->prepare('UPDATE servicios_hospital SET estado = ? WHERE id = ?');
            $ok = $stmt->execute([$nuevo_estado, $id]);
            if ($ok && $stmt->rowCount() > 0) {
                $accion = ($nuevo_estado === 'habilitado') ? 'habilitado' : 'deshabilitado';
                echo json_encode(['success' => true, 'message' => "Servicio $accion correctamente"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el servicio']);
            }
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update_servicio') {
        $id = (int) ($_POST['id'] ?? 0);
        $campo = (string) ($_POST['campo'] ?? '');
        $campos_permitidos = ['precio_costo', 'margen_ganancia', 'nomservicio'];

        if ($id <= 0 || !in_array($campo, $campos_permitidos, true)) {
            echo json_encode(['success' => false, 'message' => 'Campo no válido']);
            exit;
        }

        $valor_actualizar = ($campo === 'nomservicio')
            ? strtoupper(trim((string) ($_POST['valor'] ?? '')))
            : (float) ($_POST['valor'] ?? 0);

        try {
            $sql = "UPDATE servicios_hospital SET {$campo} = ? WHERE id = ?";
            $stmt = $connect->prepare($sql);
            $ok = $stmt->execute([$valor_actualizar, $id]);

            if (!$ok) {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
                exit;
            }

            if ($campo === 'nomservicio') {
                echo json_encode(['success' => true, 'message' => 'Nombre actualizado correctamente']);
                exit;
            }

            $stmt_get = $connect->prepare('SELECT precio_costo, margen_ganancia FROM servicios_hospital WHERE id = ?');
            $stmt_get->execute([$id]);
            $servicio = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if ($campo === 'precio_costo') {
                $nuevo_precio_venta = $valor_actualizar * (1 + ((float) $servicio['margen_ganancia'] / 100));
            } else {
                $nuevo_precio_venta = (float) $servicio['precio_costo'] * (1 + ($valor_actualizar / 100));
            }

            $stmt_update = $connect->prepare('UPDATE servicios_hospital SET precio_venta = ?, total = ? WHERE id = ?');
            $stmt_update->execute([$nuevo_precio_venta, $nuevo_precio_venta, $id]);

            echo json_encode([
                'success' => true,
                'message' => 'Actualizado correctamente',
                'nuevo_precio_venta' => number_format($nuevo_precio_venta, 2),
                'nuevo_total' => number_format($nuevo_precio_venta, 2),
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
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
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="../../backend/css/reporte_compras_datatable.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>

<?php include_once '../it/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once '../it/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? 'Buenos Días' : (($hora_actual >= 12 && $hora_actual < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'lista_servicios_user.php')">Lista de Servicios</button>
        <button class="button" onclick="cambiarColor(this, 'nuevo_servicio_user.php')">Nuevo Servicio</button>

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

<script src="../../backend/js/jquery.min.js"></script>
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
    var tablaServicios = $('#example').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: false,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        ajax: {
            url: '../../backend/registros/get_servicios_hospital.php',
            type: 'GET'
        },
        order: [[10, 'desc']],
        columns: [
            { data: 'codigo_servicio' },
            { data: 'nombre_servicio' },
            {
                data: 'nomservicio',
                render: function(data, type, row) {
                    if (type !== 'display') return data;
                    return '<span class="editable-cell editable-cell-text" data-campo="nomservicio" data-valor="'
                        + $('<div>').text(data).html() + '">' + $('<div>').text(data).html() + '</span>';
                }
            },
            { data: 'categoria_servicio' },
            { data: 'uso_servicio' },
            {
                data: 'precio_costo',
                render: function(data, type, row) {
                    if (type !== 'display') return data;
                    return '<span class="editable-cell" data-campo="precio_costo" data-valor="' + data + '">'
                        + row.precio_costo_fmt + '</span>';
                }
            },
            {
                data: 'margen_ganancia',
                render: function(data, type, row) {
                    if (type !== 'display') return data;
                    return '<span class="editable-cell" data-campo="margen_ganancia" data-valor="' + data + '">'
                        + row.margen_ganancia_fmt + '</span>';
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
                    return '<button type="button" class="btn-estado" data-id="' + row.id
                        + '" data-estado="' + data + '">' + row.btn_estado_texto + '</button>';
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
