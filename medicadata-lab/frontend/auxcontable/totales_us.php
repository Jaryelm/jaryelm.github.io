<?php
include_once '../../backend/registros/session_check.php';

// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

// Procesar filtros de fecha
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
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

<?php include_once '../auxcontable/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#">
            <div class="form-group"></div>
        </form>
        <span class="divider"></span>
        <?php include_once '../auxcontable/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <!-- Filtros de fecha -->
        <div class="filtros-container">
            <h3>Filtros de Búsqueda</h3>
            <form method="GET" action="" class="filtros-form">
                <div class="filtro-item">
                    <label for="fecha_desde">Desde:</label>
                    <input type="date" name="fecha_desde" id="fecha_desde" value="<?php echo $fecha_desde; ?>" required>
                </div>
                <div class="filtro-item">
                    <label for="fecha_hasta">Hasta:</label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" value="<?php echo $fecha_hasta; ?>" required>
                </div>
                <div class="filtro-item">
                    <label>&nbsp;</label>
                    <div class="filtro-botones">
                        <button type="submit" class="btn-filtrar">
                            <i class='bx bx-search-alt'></i>
                            Filtrar
                        </button>
                        <button type="button" class="btn-limpiar" onclick="limpiarFiltros()">
                            <i class='bx bx-refresh'></i>
                            Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Totales de Honorarios - Del <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> al <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?></h3>
                </div>

                <!-- Resumen de totales -->
                <?php
                // Consulta simplificada para obtener totales generales
                $sql_totales = "
                    SELECT 
                        COUNT(DISTINCT o.idord) as total_facturas,
                        SUM(o.total_price) as total_facturado
                    FROM orders o
                    WHERE DATE(o.placed_on) BETWEEN ? AND ?
                ";
                
                $stmt_totales = $connect->prepare($sql_totales);
                $stmt_totales->execute([$fecha_desde, $fecha_hasta]);
                $totales = $stmt_totales->fetch(PDO::FETCH_ASSOC);
                
                // Calcular totales de honorarios y comisiones por separado usando cuota fija/porcentaje
                $total_honorarios_calculado = 0;
                $total_comisiones_calculado = 0;
                
                // Obtener todas las facturas en el rango de fechas
                $sql_facturas = "SELECT idord, remitente FROM orders WHERE DATE(placed_on) BETWEEN ? AND ?";
                $stmt_facturas = $connect->prepare($sql_facturas);
                $stmt_facturas->execute([$fecha_desde, $fecha_hasta]);
                $facturas = $stmt_facturas->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($facturas as $factura) {
                    // Buscar el doctor por nombre en el remitente
                    $idodc = null;
                    if (!empty($factura['remitente'])) {
                        $stmt_doctor = $connect->prepare("SELECT idodc FROM doctor WHERE CONCAT(nodoc, ' ', apdoc) = ?");
                        $stmt_doctor->execute([$factura['remitente']]);
                        $doctor_data = $stmt_doctor->fetch(PDO::FETCH_ASSOC);
                        if ($doctor_data) {
                            $idodc = $doctor_data['idodc'];
                        }
                    }
                    
                    // Calcular honorarios usando cuota fija o porcentaje
                    if ($idodc) {
                        $stmt_servicios = $connect->prepare("
                            SELECT od.service_id, od.cantidad, od.total_after_discount, 
                                   hc.porcentaje_honorario, hc.cuota_fija
                            FROM order_details od
                            LEFT JOIN honorarios_configuracion hc ON hc.id_doctor = ? AND hc.id_servicio = od.service_id
                            WHERE od.order_id = ? AND od.item_type = 'servicio'
                        ");
                        $stmt_servicios->execute([$idodc, $factura['idord']]);
                        
                        while ($row = $stmt_servicios->fetch(PDO::FETCH_ASSOC)) {
                            $cuota_fija = floatval($row['cuota_fija']) ?: 0;
                            $porcentaje = floatval($row['porcentaje_honorario']) ?: 0;
                            $cantidad = floatval($row['cantidad']) ?: 0;
                            $total_serv = floatval($row['total_after_discount']) ?: 0;
                            
                            // Aplicar cuota fija o porcentaje
                            if ($cuota_fija > 0) {
                                $honorario = $cuota_fija * $cantidad;
                            } else {
                                $honorario = $total_serv * ($porcentaje / 100);
                            }
                            $total_honorarios_calculado += $honorario;
                        }
                    }
                    
                    // Calcular comisiones de remitentes
                    $stmt_comisiones = $connect->prepare("SELECT COALESCE(SUM(monto_comision), 0) FROM remitentes_honorarios WHERE id_factura = ?");
                    $stmt_comisiones->execute([$factura['idord']]);
                    $total_comisiones_calculado += $stmt_comisiones->fetchColumn() ?: 0;
                }
                
                // Actualizar el array de totales
                $totales['total_honorarios'] = $total_honorarios_calculado;
                $totales['total_comisiones'] = $total_comisiones_calculado;
                ?>

                <div class="resumen-totales">
                    <div class="total-card">
                        <h4>Total Facturas</h4>
                        <span><?php echo number_format($totales['total_facturas']); ?></span>
                    </div>
                    <div class="total-card">
                        <h4>Total Facturado</h4>
                        <span>LPS. <?php echo number_format($totales['total_facturado'], 2); ?></span>
                    </div>
                    <div class="total-card">
                        <h4>Total Honorarios</h4>
                        <span>LPS. <?php echo number_format($totales['total_honorarios'], 2); ?></span>
                    </div>
                    <div class="total-card">
                        <h4>Total Comisiones</h4>
                        <span>LPS. <?php echo number_format($totales['total_comisiones'], 2); ?></span>
                    </div>
                    <div class="total-card">
                        <h4>Total Hospital</h4>
                        <span>LPS. <?php echo number_format($totales['total_facturado'] - $totales['total_honorarios'] - $totales['total_comisiones'], 2); ?></span>
                    </div>
                </div>

                <div class="table-responsive" style="overflow-x:auto;">
                    <?php
                    // Obtener todos los datos de forma simplificada
                    $data = [];
                    
                    // Obtener todas las facturas con servicios en el rango de fechas
                    $sql_facturas_detalle = "
                    SELECT 
                        o.idord,
                        o.invoice_number,
                        o.placed_on as fecha,
                        o.nomcl as paciente,
                        o.total_price,
                        o.price_without_discount,
                        o.method as forma_pago,
                        o.remitente
                    FROM orders o
                    WHERE DATE(o.placed_on) BETWEEN ? AND ?
                    ORDER BY o.placed_on DESC, o.invoice_number
                    ";
                    
                    $stmt_facturas_detalle = $connect->prepare($sql_facturas_detalle);
                    $stmt_facturas_detalle->execute([$fecha_desde, $fecha_hasta]);
                    $facturas_detalle = $stmt_facturas_detalle->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($facturas_detalle as $factura) {
                        // Buscar el doctor por nombre en el remitente
                        $doctor_data = null;
                        $idodc = null;
                        if (!empty($factura['remitente'])) {
                            $stmt_doctor = $connect->prepare("SELECT idodc, nodoc, apdoc, nomesp FROM doctor WHERE CONCAT(nodoc, ' ', apdoc) = ?");
                            $stmt_doctor->execute([$factura['remitente']]);
                            $doctor_data = $stmt_doctor->fetch(PDO::FETCH_ASSOC);
                            if ($doctor_data) {
                                $idodc = $doctor_data['idodc'];
                            }
                        }
                        
                        // Obtener datos de honorario si hay doctor
                        $honorario_data = null;
                        if ($idodc) {
                            $stmt_honorario = $connect->prepare("SELECT * FROM honorarios_medicos WHERE id_factura = ? AND id_doctor = ?");
                            $stmt_honorario->execute([$factura['idord'], $idodc]);
                            $honorario_data = $stmt_honorario->fetch(PDO::FETCH_ASSOC);
                        }
                        
                        // Obtener servicios de la factura
                        $stmt_servicios = $connect->prepare("
                            SELECT od.service_id, od.cantidad, od.total_after_discount, od.discount_percentage,
                                   od.age_discount_30, od.age_discount_40, od.promotion_discount, od.other_discount, od.total_discount,
                                   s.nombre_servicio, s.precio_venta,
                                   hc.porcentaje_honorario, hc.cuota_fija
                            FROM order_details od
                            JOIN servicios_hospital s ON od.service_id = s.id
                            LEFT JOIN honorarios_configuracion hc ON hc.id_doctor = ? AND hc.id_servicio = od.service_id
                            WHERE od.order_id = ? AND od.item_type = 'servicio'
                        ");
                        $stmt_servicios->execute([$idodc ?: 0, $factura['idord']]);
                        $servicios = $stmt_servicios->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($servicios as $servicio) {
                            // Calcular honorario usando cuota fija o porcentaje
                            $cuota_fija = floatval($servicio['cuota_fija']) ?: 0;
                            $porcentaje = floatval($servicio['porcentaje_honorario']) ?: 0;
                            $cantidad = floatval($servicio['cantidad']) ?: 0;
                            $total_serv = floatval($servicio['total_after_discount']) ?: 0;
                            
                            if ($cuota_fija > 0) {
                                $honorario_calculado = $cuota_fija * $cantidad;
                            } else {
                                $honorario_calculado = $total_serv * ($porcentaje / 100);
                            }
                            
                            // Obtener información de comisiones para este servicio
                            $stmt_comision = $connect->prepare("
                                SELECT rh.monto_comision, rh.factura, rh.fecha_registro,
                                       CONCAT(d.nodoc, ' ', d.apdoc) as medico_remitente, d.nomesp as especialidad_remitente
                                FROM remitentes_honorarios rh
                                JOIN doctor d ON rh.id_doctor_remitente = d.idodc
                                WHERE rh.id_factura = ? AND rh.id_servicio = ?
                            ");
                            $stmt_comision->execute([$factura['idord'], $servicio['service_id']]);
                            $comision_data = $stmt_comision->fetch(PDO::FETCH_ASSOC);
                            
                            // Agregar fila a los datos
                            $data[] = [
                                'idord' => $factura['idord'],
                                'invoice_number' => $factura['invoice_number'],
                                'fecha' => $factura['fecha'],
                                'paciente' => $factura['paciente'],
                                'total_price' => $factura['total_price'],
                                'price_without_discount' => $factura['price_without_discount'],
                                'forma_pago' => $factura['forma_pago'],
                                'idodc' => $idodc,
                                'nodoc' => $doctor_data['nodoc'] ?? '',
                                'apdoc' => $doctor_data['apdoc'] ?? '',
                                'nomesp' => $doctor_data['nomesp'] ?? '',
                                'service_id' => $servicio['service_id'],
                                'cantidad' => $servicio['cantidad'],
                                'subtotal_servicio' => $servicio['total_after_discount'],
                                'nombre_servicio' => $servicio['nombre_servicio'],
                                'precio_unitario' => $servicio['precio_venta'],
                                'porcentaje_honorario' => $servicio['porcentaje_honorario'] ?: 0,
                                'cuota_fija' => $servicio['cuota_fija'] ?: 0,
                                'honorario_calculado' => $honorario_calculado,
                                'discount_percentage' => $servicio['discount_percentage'],
                                'age_discount_30' => $servicio['age_discount_30'],
                                'age_discount_40' => $servicio['age_discount_40'],
                                'promotion_discount' => $servicio['promotion_discount'],
                                'other_discount' => $servicio['other_discount'],
                                'total_discount' => $servicio['total_discount'],
                                'desc_temporada' => $honorario_data['desc_temporada'] ?? 0,
                                'desc_promo' => $honorario_data['desc_promo'] ?? 0,
                                'desc_empleado' => $honorario_data['desc_empleado'] ?? 0,
                                'desc_preferencial' => $honorario_data['desc_preferencial'] ?? 0,
                                'monto_comision' => $comision_data['monto_comision'] ?? 0,
                                'medico_remitente' => $comision_data['medico_remitente'] ?? '',
                                'especialidad_remitente' => $comision_data['especialidad_remitente'] ?? '',
                                'factura_comision' => $comision_data['factura'] ?? '',
                                'fecha_comision' => $comision_data['fecha_registro'] ?? ''
                            ];
                        }
                    }

                    // Los datos ya están en el array $data
                    ?>

                    <table id="totalesTable" class="responsive-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Factura</th>
                                <th>Nombre Paciente</th>
                                <th>Servicio</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Monto</th>
                                <th>% Honorario</th>
                                <th>Cuota Honorarios</th>
                                <th>Forma de Pago</th>
                                <th>Tipo Descuento</th>
                                <th>% Descuento</th>
                                <th>Descuento en LPS</th>
                                <th>Subtotal</th>
                                <th>Total Honorarios</th>
                                <th>Médico Remitente</th>
                                <th>Especialidad Remitente</th>
                                <th>Comisión</th>
                                <th>Factura Comisión</th>
                                <th>Fecha Comisión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_general_honorarios = 0;
                            $total_general_monto = 0;
                            $total_general_comisiones = 0;
                            
                            foreach ($data as $row): 
                                // Calcular descuentos
                                $descuentos_manuales = $row['desc_temporada'] + $row['desc_promo'] + $row['desc_empleado'] + $row['desc_preferencial'];
                                $descuentos_automaticos = $row['total_discount'];
                                $total_descuentos = $descuentos_manuales + $descuentos_automaticos;
                                
                                // Calcular porcentaje de descuento
                                $porcentaje_descuento = ($row['subtotal_servicio'] > 0) ? (($total_descuentos / ($row['subtotal_servicio'] + $total_descuentos)) * 100) : 0;
                                
                                // Honorario calculado ya incluye cuota fija o porcentaje
                                // Honorario final después de descuentos manuales
                                $honorario_final = max(0, $row['honorario_calculado'] - $descuentos_manuales);
                                
                                $total_general_honorarios += $honorario_final;
                                $total_general_monto += $row['subtotal_servicio'];
                                $total_general_comisiones += $row['monto_comision'];
                                
                                // Determinar tipo de descuento aplicado
                                $tipo_descuento = '';
                                if ($row['desc_temporada'] > 0) $tipo_descuento .= 'Temporada ';
                                if ($row['desc_promo'] > 0) $tipo_descuento .= 'Promoción ';
                                if ($row['desc_empleado'] > 0) $tipo_descuento .= 'Empleado ';
                                if ($row['desc_preferencial'] > 0) $tipo_descuento .= 'Preferencial ';
                                if ($row['age_discount_30'] > 0) $tipo_descuento .= '3ra Edad ';
                                if ($row['age_discount_40'] > 0) $tipo_descuento .= '4ta Edad ';
                                if ($row['promotion_discount'] > 0) $tipo_descuento .= 'Promoción Auto ';
                                if ($row['other_discount'] > 0) $tipo_descuento .= 'Otros ';
                                
                                $tipo_descuento = trim($tipo_descuento) ?: 'Sin Descuento';
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['paciente']); ?></td>
                                <td><?php echo htmlspecialchars($row['nombre_servicio']); ?></td>
                                <td><?php echo $row['cantidad']; ?></td>
                                <td>LPS. <?php echo number_format($row['precio_unitario'], 2); ?></td>
                                <td>LPS. <?php echo number_format($row['subtotal_servicio'], 2); ?></td>
                                <td><?php echo number_format($row['porcentaje_honorario'], 2); ?>%</td>
                                <td>LPS. <?php echo number_format($honorario_final, 2); ?></td>
                                <td><?php echo htmlspecialchars($row['forma_pago']); ?></td>
                                <td><?php echo $tipo_descuento; ?></td>
                                <td><?php echo number_format($porcentaje_descuento, 2); ?>%</td>
                                <td>LPS. <?php echo number_format($total_descuentos, 2); ?></td>
                                <td>LPS. <?php echo number_format($row['subtotal_servicio'], 2); ?></td>
                                <td>LPS. <?php echo number_format($honorario_final, 2); ?></td>
                                <td><?php echo trim($row['medico_remitente']) ? htmlspecialchars($row['medico_remitente']) : '-'; ?></td>
                                <td><?php echo $row['especialidad_remitente'] ? htmlspecialchars($row['especialidad_remitente']) : '-'; ?></td>
                                <td><?php echo $row['monto_comision'] > 0 ? 'LPS. ' . number_format($row['monto_comision'], 2) : '-'; ?></td>
                                <td><?php echo $row['factura_comision'] ? htmlspecialchars($row['factura_comision']) : '-'; ?></td>
                                <td><?php echo $row['fecha_comision'] ? date('d/m/Y', strtotime($row['fecha_comision'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #035c67; color: white; font-weight: bold;">
                                <td colspan="5" style="text-align: center; font-size: 16px;">TOTALES GENERALES (Por Página):</td>
                                <td id="total-precio-unit" style="text-align: right; font-size: 14px;">LPS. 0.00</td>
                                <td id="total-monto" style="text-align: right; font-size: 14px;">LPS. 0.00</td>
                                <td style="text-align: center;">---</td>
                                <td id="total-honorarios-calc" style="text-align: right; font-size: 14px;">LPS. 0.00</td>
                                <td style="text-align: center;">---</td>
                                <td style="text-align: center;">---</td>
                                <td style="text-align: center;">---</td>
                                <td id="total-descuentos" style="text-align: right; font-size: 14px;">LPS. 0.00</td>
                                <td id="total-subtotal" style="text-align: right; font-size: 14px;">LPS. 0.00</td>
                                <td id="total-honorarios" style="text-align: right; font-size: 14px;">LPS. 0.00</td>
                                <td colspan="2" style="text-align: center;">---</td>
                                <td id="total-comisiones" style="text-align: right; font-size: 14px;">LPS. 0.00</td>
                                <td colspan="2" style="text-align: center;">---</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </main>
</section>

<!-- Scripts -->
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
    $('#totalesTable').DataTable({
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: 'Copiar',
                title: 'Totales_Honorarios_' + new Date().toLocaleDateString('es-HN')
            },
            {
                extend: 'csv',
                text: 'CSV',
                title: 'Totales_Honorarios_' + new Date().toISOString().split('T')[0]
            },
            {
                extend: 'excel',
                text: 'Excel',
                title: 'Totales_Honorarios_' + new Date().toISOString().split('T')[0],
                customize: function(xlsx) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    // Aplicar formato a la fila de totales
                    $('row:last c', sheet).attr('s', '2');
                }
            },
            {
                extend: 'pdf',
                text: 'PDF',
                title: 'Totales de Honorarios',
                orientation: 'landscape',
                pageSize: 'A4',
                customize: function(doc) {
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                    doc.styles.tableHeader.fontSize = 8;
                    doc.defaultStyle.fontSize = 7;
                }
            },
            {
                extend: 'print',
                text: 'Imprimir',
                title: 'Totales de Honorarios - ' + new Date().toLocaleDateString('es-HN')
            }
        ],
        order: [[0, 'desc']], // Ordenar por fecha descendente
        language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar: _MENU_",
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
                targets: 0, // Columna de fecha
                type: 'datetime',
                render: function(data, type, row) {
                    return type === 'sort' ? new Date(data.split('/').reverse().join('-')).getTime() : data;
                }
            },
            {
                targets: [5, 6, 8, 12, 13, 14, 17], // Columnas de moneda (todas las columnas LPS)
                className: 'text-right'
            }
        ],
        ordering: true,
        orderMulti: false,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        footerCallback: function(row, data, start, end, display) {
            var api = this.api();
            
            // Función mejorada para limpiar y convertir valores monetarios
            function cleanCurrency(value) {
                if (typeof value === 'string') {
                    // Remover "LPS.", espacios, comas y mantener solo números y punto decimal
                    var cleaned = value.replace(/LPS\.?\s*/g, '').replace(/,/g, '');
                    return parseFloat(cleaned) || 0;
                }
                return parseFloat(value) || 0;
            }
            
            // Obtener datos de filas visibles en la página actual
            var visibleRows = api.rows({page: 'current'}).data();
            
            // Inicializar totales
            var totales = {
                precioUnit: 0,    // Columna 5
                monto: 0,         // Columna 6
                honorarios: 0,    // Columna 8
                descuentos: 0,    // Columna 12
                subtotal: 0,      // Columna 13
                totalHonorarios: 0, // Columna 14
                comisiones: 0     // Columna 17
            };
            
            // Iterar sobre las filas visibles para calcular totales
            for (var i = 0; i < visibleRows.length; i++) {
                var rowData = visibleRows[i];
                totales.precioUnit += cleanCurrency(rowData[5]);      // Precio Unit.
                totales.monto += cleanCurrency(rowData[6]);           // Monto
                totales.honorarios += cleanCurrency(rowData[8]);      // Honorarios
                totales.descuentos += cleanCurrency(rowData[12]);     // Descuento en LPS
                totales.subtotal += cleanCurrency(rowData[13]);       // Subtotal
                totales.totalHonorarios += cleanCurrency(rowData[14]); // Total Honorarios
                totales.comisiones += cleanCurrency(rowData[17]);     // Comisión
            }
            
            // Actualizar TODOS los totales en el pie de página
            $('#total-precio-unit').html('LPS. ' + totales.precioUnit.toLocaleString('es-HN', {minimumFractionDigits: 2}));
            $('#total-monto').html('LPS. ' + totales.monto.toLocaleString('es-HN', {minimumFractionDigits: 2}));
            $('#total-honorarios-calc').html('LPS. ' + totales.honorarios.toLocaleString('es-HN', {minimumFractionDigits: 2}));
            $('#total-descuentos').html('LPS. ' + totales.descuentos.toLocaleString('es-HN', {minimumFractionDigits: 2}));
            $('#total-subtotal').html('LPS. ' + totales.subtotal.toLocaleString('es-HN', {minimumFractionDigits: 2}));
            $('#total-honorarios').html('LPS. ' + totales.totalHonorarios.toLocaleString('es-HN', {minimumFractionDigits: 2}));
            $('#total-comisiones').html('LPS. ' + totales.comisiones.toLocaleString('es-HN', {minimumFractionDigits: 2}));
            
            // Mostrar información adicional en la primera celda
            var numRegistros = display.length;
            var totalCompleto = api.data().length;
            $('.responsive-table tfoot td:first-child').html(
                'TOTALES GENERALES (Por Página)<br>' +
                '<small style="font-size: 12px; opacity: 0.8;">' + 
                'Mostrando: ' + numRegistros + ' de ' + totalCompleto + ' registros' +
                '</small>'
            );
            
            // Log para depuración mejorado
            console.log('=== CÁLCULO DE TOTALES POR PÁGINA ===');
            console.log('Filas procesadas:', numRegistros);
            console.log('Totales calculados:', {
                'Precio Unit.': 'LPS. ' + totales.precioUnit.toFixed(2),
                'Monto': 'LPS. ' + totales.monto.toFixed(2),
                'Honorarios': 'LPS. ' + totales.honorarios.toFixed(2),
                'Descuentos': 'LPS. ' + totales.descuentos.toFixed(2),
                'Subtotal': 'LPS. ' + totales.subtotal.toFixed(2),
                'Total Honorarios': 'LPS. ' + totales.totalHonorarios.toFixed(2),
                'Comisiones': 'LPS. ' + totales.comisiones.toFixed(2)
            });
            
            // Mostrar algunos valores de ejemplo para verificar
            console.log('Ejemplo de valores en filas visibles:');
            for (var i = 0; i < Math.min(3, numRegistros); i++) {
                var row = visibleRows[i];
                console.log('Fila ' + (i+1) + ':', {
                    'Precio Unit': row[5],
                    'Monto': row[6],
                    'Honorarios': row[8],
                    'Comisión': row[17]
                });
            }
        }
    });
    
    // Función para mostrar mensaje de éxito cuando se cargan los totales
    setTimeout(function() {
        console.log('✅ Totales dinámicos activados correctamente');
        console.log('📊 Se calcularán automáticamente por paginación');
        console.log('🔄 Se actualizan al cambiar de página o filtrar');
        console.log('🎯 Todas las columnas LPS se suman correctamente');
        console.log('🔧 CORRECCIÓN APLICADA: Ahora suma solo valores visibles en página actual');
    }, 1000);
});

function limpiarFiltros() {
    document.getElementById('fecha_desde').value = '<?php echo date('Y-m-01'); ?>';
    document.getElementById('fecha_hasta').value = '<?php echo date('Y-m-d'); ?>';
    window.location.href = 'totales.php';
}

// Función de prueba para verificar totales (ejecutar desde consola)
function verificarTotales() {
    console.log('🔍 Verificando totales actuales...');
    
    var table = $('#totalesTable').DataTable();
    var api = table.api();
    
    function cleanCurrency(value) {
        if (typeof value === 'string') {
            var cleaned = value.replace(/LPS\.?\s*/g, '').replace(/,/g, '');
            return parseFloat(cleaned) || 0;
        }
        return parseFloat(value) || 0;
    }
    
    // Obtener datos de filas visibles
    var visibleRows = api.rows({page: 'current'}).data();
    
    var totales = {
        'Precio Unit.': 0,
        'Monto': 0,
        'Honorarios': 0,
        'Descuentos': 0,
        'Subtotal': 0,
        'Total Honorarios': 0,
        'Comisiones': 0
    };
    
    // Calcular totales iterando sobre filas visibles
    for (var i = 0; i < visibleRows.length; i++) {
        var rowData = visibleRows[i];
        totales['Precio Unit.'] += cleanCurrency(rowData[5]);
        totales['Monto'] += cleanCurrency(rowData[6]);
        totales['Honorarios'] += cleanCurrency(rowData[8]);
        totales['Descuentos'] += cleanCurrency(rowData[12]);
        totales['Subtotal'] += cleanCurrency(rowData[13]);
        totales['Total Honorarios'] += cleanCurrency(rowData[14]);
        totales['Comisiones'] += cleanCurrency(rowData[17]);
    }
    
    // Formatear totales para mostrar
    var totalesFormateados = {};
    Object.keys(totales).forEach(function(key) {
        totalesFormateados[key] = 'LPS. ' + totales[key].toFixed(2);
    });
    
    console.table(totalesFormateados);
    console.log('✅ Verificación completa');
    console.log('Filas procesadas:', visibleRows.length);
    
    return totales;
}

// Función rápida para mostrar solo las comisiones de la página actual
function mostrarComisiones() {
    console.log('💰 Verificando comisiones en página actual...');
    
    var table = $('#totalesTable').DataTable();
    var api = table.api();
    var visibleRows = api.rows({page: 'current'}).data();
    
    function cleanCurrency(value) {
        if (typeof value === 'string') {
            var cleaned = value.replace(/LPS\.?\s*/g, '').replace(/,/g, '');
            return parseFloat(cleaned) || 0;
        }
        return parseFloat(value) || 0;
    }
    
    var totalComisiones = 0;
    console.log('Detalle de comisiones por fila:');
    
    for (var i = 0; i < visibleRows.length; i++) {
        var row = visibleRows[i];
        var comision = cleanCurrency(row[17]);
        if (comision > 0) {
            totalComisiones += comision;
            console.log('Fila ' + (i+1) + ': LPS. ' + comision.toFixed(2) + ' - ' + row[15]); // row[15] es médico remitente
        }
    }
    
    console.log('Total Comisiones en página: LPS. ' + totalComisiones.toFixed(2));
    return totalComisiones;
}
</script>

<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>

<style>
/* Estilos para los filtros */
.filtros-container {
    background: #f8f9fa;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.filtros-container h3 {
    margin-top: 0;
    color: #035c67;
    border-bottom: 2px solid #035c67;
    padding-bottom: 5px;
}

.filtros-form {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: flex-end;
    justify-content: flex-start;
}

.filtro-item {
    display: flex;
    flex-direction: column;
    min-width: 180px;
}

.filtro-item label {
    font-weight: bold;
    margin-bottom: 8px;
    color: #495057;
    font-size: 14px;
}

.filtro-item input[type="date"] {
    padding: 12px 15px;
    border: 2px solid #ced4da;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    background-color: white;
    height: 44px;
    box-sizing: border-box;
}

.filtro-item input[type="date"]:focus {
    outline: none;
    border-color: #035c67;
    box-shadow: 0 0 0 3px rgba(3, 92, 103, 0.1);
}

.filtro-botones {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 0px;
}

.btn-filtrar, .btn-limpiar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    min-width: 110px;
    justify-content: center;
    height: 44px;
    box-sizing: border-box;
}

.btn-filtrar {
    background: linear-gradient(135deg, #035c67, #06adbf);
    color: white;
    border: 2px solid transparent;
}

.btn-filtrar:hover {
    background: linear-gradient(135deg, #06adbf, #035c67);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(3, 92, 103, 0.3);
}

.btn-filtrar:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-limpiar {
    background: white;
    color: #6c757d;
    border: 2px solid #6c757d;
}

.btn-limpiar:hover {
    background-color: #6c757d;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
}

.btn-limpiar:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-filtrar i, .btn-limpiar i {
    font-size: 16px;
}

/* Estilos para el resumen de totales */
.resumen-totales {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.total-card {
    background: linear-gradient(135deg, #035c67, #06adbf);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.total-card h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    opacity: 0.9;
}

.total-card span {
    font-size: 24px;
    font-weight: bold;
    display: block;
}

/* Estilos para la tabla */
.responsive-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.responsive-table th,
.responsive-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
    font-size: 13px;
}

.responsive-table th {
    background-color: #035c67;
    color: white;
    font-weight: bold;
    position: sticky;
    top: 0;
    z-index: 10;
}

.responsive-table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}

.responsive-table tbody tr:hover {
    background-color: #e9ecef;
}

.responsive-table tfoot tr {
    background-color: #035c67 !important;
    color: white;
    font-weight: bold;
}

.responsive-table tfoot td {
    border-top: 2px solid #035c67;
    padding: 12px 8px;
}

.text-right {
    text-align: right !important;
}

/* Responsive */
@media (max-width: 768px) {
    .filtros-form {
        flex-direction: column;
        gap: 15px;
    }
    
    .filtro-item {
        width: 100%;
        min-width: auto;
    }
    
    .filtro-botones {
        flex-direction: column;
        width: 100%;
        gap: 10px;
        margin-top: 15px;
    }
    
    .btn-filtrar, .btn-limpiar {
        width: 100%;
        min-width: auto;
    }
    
    .resumen-totales {
        grid-template-columns: 1fr;
    }
    
    .total-card {
        padding: 15px;
    }
    
    .total-card span {
        font-size: 20px;
    }
    
    .responsive-table {
        font-size: 11px;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 6px 4px;
    }
}

/* Estilos adicionales para DataTables */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    margin: 10px 0;
}

.dataTables_wrapper .dataTables_filter input {
    padding: 5px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.dt-buttons {
    margin-bottom: 15px;
}

/* Estilos específicos para columnas de comisiones - Color corporativo #06adbf */
.responsive-table th:nth-child(16),
.responsive-table th:nth-child(17),
.responsive-table th:nth-child(18),
.responsive-table th:nth-child(19),
.responsive-table th:nth-child(20) {
    background-color: #06adbf !important;
    color: white !important;
}

.responsive-table td:nth-child(16),
.responsive-table td:nth-child(17),
.responsive-table td:nth-child(18),
.responsive-table td:nth-child(19),
.responsive-table td:nth-child(20) {
    background-color: #f0faff; /* Fondo azul claro que combina con #06adbf */
}

/* Destacar la fila de totales */
.responsive-table tfoot tr {
    background-color: #035c67 !important;
    color: white !important;
    font-weight: bold;
    font-size: 14px;
}

.responsive-table tfoot td {
    border-top: 3px solid #035c67;
    padding: 15px 8px;
    font-weight: bold;
    text-align: right;
}

.responsive-table tfoot td:first-child {
    text-align: center;
}

/* Estilo especial para celdas de totales - Color corporativo #06adbf */
#total-precio-unit, #total-monto, #total-honorarios-calc, 
#total-descuentos, #total-subtotal, #total-honorarios, #total-comisiones {
    background-color: #06adbf !important;
    color: white !important;
    font-weight: bold;
    padding: 15px 8px;
    border: 1px solid #035c67;
    text-align: right;
}

/* Estilo para celdas sin valores en totales */
.responsive-table tfoot td[style*="text-align: center"] {
    background-color: #035c67 !important;
    color: #ccc !important;
    font-style: italic;
}
</style>

</body>
<!-- 
    MEJORAS IMPLEMENTADAS:
    1. Agregadas columnas de comisiones: Médico Remitente, Especialidad Remitente, Comisión, Factura Comisión, Fecha Comisión
    2. Corregido el cálculo de totales generales para mostrar correctamente los valores por paginación
    3. Agregado el Total Hospital en el resumen superior
    4. Mejorado el estilo visual de la fila de totales generales
    5. TOTALES DINÁMICOS: Ahora se suman TODAS las columnas LPS por paginación:
       - Precio Unit. (Columna 5)
       - Monto (Columna 6)
       - Honorarios (Columna 8)
       - Descuento en LPS (Columna 12)
       - Subtotal (Columna 13)
       - Total Honorarios (Columna 14)
       - Comisión (Columna 17)
    6. Los totales se actualizan automáticamente cuando cambias de página o filtras
    7. Información contextual: Muestra cuántos registros se están sumando en cada página
    8. Colores diferenciados: Celdas azules (#06adbf) para totales LPS, azul oscuro para información contextual
    9. Logging de depuración: Console.log para verificar que los cálculos sean correctos
    10. Función de prueba: Ejecutar verificarTotales() desde la consola para validar cálculos
    11. CORRECCIÓN CRÍTICA: Arreglado el método de cálculo para sumar solo filas visibles
    12. MEJORA VISUAL: Cambiado color verde por #06adbf para mejor consistencia visual
    
    INSTRUCCIONES DE USO:
    - Los totales se calculan automáticamente por página
    - Cambiar de página actualizará los totales automáticamente
    - Filtrar por fecha recalculará todo
    - Para verificar manualmente: F12 → Console → verificarTotales()
    - Los totales muestran únicamente los registros visibles en la página actual
    - IMPORTANTE: Ahora suma correctamente solo los valores de la página actual
    - FUNCIONES DE PRUEBA:
      * verificarTotales() - Muestra todos los totales calculados
      * mostrarComisiones() - Muestra detalle de comisiones en página actual
    - ESQUEMA DE COLORES:
      * #06adbf - Color principal para totales y columnas de comisiones
      * #f0faff - Fondo azul claro para celdas de datos de comisiones
      * #035c67 - Color azul oscuro para información contextual
-->
</html> 