<?php

include_once '../../backend/registros/session_check.php';

// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');
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
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <title>MEDIDATA</title>
</head>
<body>

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

<main>

<div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Honorarios por Factura</h3>
                </div>
                <div class="table-responsive" style="overflow-x:auto;">
                <?php
                $sql = "
                SELECT 
                    o.idord, 
                    o.invoice_number, 
                    o.processed_by, 
                    o.placed_on, 
                    o.nomcl,
                    o.price_without_discount,
                    o.total_price,
                    o.tipc,
                    o.remitente,
                    (
                        SELECT COALESCE(SUM(rh.monto_comision), 0)
                        FROM remitentes_honorarios rh
                        WHERE rh.id_factura = o.idord
                    ) AS total_comisiones_remitentes
                FROM orders o
                ORDER BY o.placed_on DESC";

                $stmt = $connect->prepare($sql);
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <table id="example" class="responsive-table">
                    <thead>
                        <tr>
                            <th>N. Factura</th>
                            <th>Procesado Por</th>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Especialidad</th>
                            <th>Monto Honorario</th>
                            <th style="display: none;">Ajustes Descuentos</th>
                            <th>Remitentes</th>
                            <th>Estado Pago</th>
                            <th>Fecha Pago</th>
                            <th>Actualizado Por</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($data as $d): ?>
                        <?php 
                        // Buscar el doctor por nombre en el remitente
                        $doctor_data = null;
                        $idodc = null;
                        if (!empty($d['remitente'])) {
                            $stmt_doctor = $connect->prepare("SELECT idodc, nodoc, apdoc, nomesp FROM doctor WHERE CONCAT(nodoc, ' ', apdoc) = ?");
                            $stmt_doctor->execute([$d['remitente']]);
                            $doctor_data = $stmt_doctor->fetch(PDO::FETCH_ASSOC);
                            if ($doctor_data) {
                                $idodc = $doctor_data['idodc'];
                            }
                        }
                        
                        // Obtener datos de honorario si hay doctor
                        $honorario_data = null;
                        if ($idodc) {
                            $stmt_honorario = $connect->prepare("SELECT * FROM honorarios_medicos WHERE id_factura = ? AND id_doctor = ?");
                            $stmt_honorario->execute([$d['idord'], $idodc]);
                            $honorario_data = $stmt_honorario->fetch(PDO::FETCH_ASSOC);
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($d['invoice_number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($d['processed_by'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($d['placed_on'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($d['nomcl'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($d['remitente'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($doctor_data['nomesp'] ?? ''); ?></td>
                            <td>
                                <?php 
                                if ($idodc) {
                                    // Obtener la configuración de honorario para el médico y cada servicio
                                    $stmt_servicios = $connect->prepare("
                                        SELECT od.service_id, od.cantidad, od.total_after_discount, 
                                               hc.porcentaje_honorario, hc.cuota_fija
                                        FROM order_details od
                                        LEFT JOIN honorarios_configuracion hc ON hc.id_doctor = ? AND hc.id_servicio = od.service_id
                                        WHERE od.order_id = ? AND od.item_type = 'servicio'
                                    ");
                                    $stmt_servicios->execute([$idodc, $d['idord']]);
                                    $honorario_total = 0;
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
                                        $honorario_total += $honorario;
                                    }
                                    // Restar descuentos manuales
                                    $total_descuentos = 0;
                                    if ($honorario_data) {
                                        $total_descuentos = ($honorario_data['desc_temporada'] ?? 0) + ($honorario_data['desc_promo'] ?? 0) + ($honorario_data['desc_empleado'] ?? 0) + ($honorario_data['desc_preferencial'] ?? 0);
                                    }
                                    // El monto de honorario del médico es fijo, NO se le resta la comisión de remitentes
                                    $monto_total_honorario = $honorario_total - $total_descuentos;
                                    echo 'LPS. ' . number_format($monto_total_honorario, 2);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td style="display: none;">
                                <?php if ($idodc): ?>
                                     <button class="btn_ver_detalles"
                                        onclick="abrirModalAjustes(this)"
                                        data-id-factura="<?php echo $d['idord']; ?>"
                                        data-id-doctor="<?php echo $idodc; ?>"
                                        data-honorario-servicios="<?php echo number_format($honorario_total ?? 0, 2); ?>"
                                        data-desc-temporada="<?php echo number_format(($honorario_data && isset($honorario_data['desc_temporada'])) ? $honorario_data['desc_temporada'] : 0, 2); ?>"
                                        data-desc-promo="<?php echo number_format(($honorario_data && isset($honorario_data['desc_promo'])) ? $honorario_data['desc_promo'] : 0, 2); ?>"
                                        data-desc-empleado="<?php echo number_format(($honorario_data && isset($honorario_data['desc_empleado'])) ? $honorario_data['desc_empleado'] : 0, 2); ?>"
                                        data-desc-preferencial="<?php echo number_format(($honorario_data && isset($honorario_data['desc_preferencial'])) ? $honorario_data['desc_preferencial'] : 0, 2); ?>"
                                        data-notas-ajuste="<?php echo htmlspecialchars(($honorario_data && isset($honorario_data['notas_ajuste'])) ? $honorario_data['notas_ajuste'] : ''); ?>">
                                        Descuentos
                                    </button>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($idodc): ?>
                                <button class="btn_ver_detalles"
                                    onclick="abrirModalRemitentes(<?php echo $d['idord']; ?>, '<?php echo htmlspecialchars($d['invoice_number'] ?? ''); ?>')"
                                    style="background-color: #035c67;">
                                    Agregar Remitentes
                                </button>
                                <?php else: ?>
                                    <span style="color:#888;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($idodc): ?>
                                <form method="post" action="update_honorario_status.php" class="form-guardar-honorario" style="display:inline;">
                                    <input type="hidden" name="id_factura" value="<?php echo $d['idord']; ?>">
                                    <input type="hidden" name="id_doctor" value="<?php echo $idodc; ?>">
                                    <input type="hidden" name="monto_honorario" value="<?php echo $honorario_total ?? 0; ?>">
                                    <input type="hidden" name="status" value="<?php echo (($honorario_data && isset($honorario_data['estado_pago'])) ? $honorario_data['estado_pago'] : 'pendiente') == 'pagado' ? 'pendiente' : 'pagado'; ?>">
                                    <button type="submit" class="btn_guardar" style="background:none;border:none;padding:0;">
                                        <label class="status-switch">
                                            <input type="checkbox" <?php echo (($honorario_data && isset($honorario_data['estado_pago'])) ? $honorario_data['estado_pago'] : 'pendiente') == 'pagado' ? 'checked' : ''; ?>>
                                            <span class="status-slider"></span>
                                        </label>
                                        <span style="display:none;">Cambiar estado</span>
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span style="color:#888;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo ($honorario_data && $honorario_data['fecha_pago']) ? htmlspecialchars($honorario_data['fecha_pago']) : '-'; ?></td>
                            <td><?php echo ($honorario_data && $honorario_data['updated_by']) ? htmlspecialchars($honorario_data['updated_by']) : '-'; ?></td>
                            <td>
                                <button class="btn_ver_totales btn_ver_detalles" 
                                    data-factura="<?php echo htmlspecialchars($d['invoice_number'] ?? ''); ?>"
                                    data-total-sin="<?php echo number_format($d['price_without_discount'] ?? 0, 2); ?>"
                                    data-total-con="<?php echo number_format($d['total_price'] ?? 0, 2); ?>"
                                    data-idord="<?php echo htmlspecialchars($d['idord']); ?>"
                                    data-tipc="<?php echo htmlspecialchars($d['tipc'] ?? ''); ?>"
                                    onclick="mostrarTotales(this)">
                                    Ver Factura
                                </button>
                                <button class="btn_ver_detalles" onclick="viewDetails(<?php echo htmlspecialchars($d['idord']); ?>)">Ver Detalles</button>
                                <button class="btn_ver_servicios btn_ver_detalles" onclick="verServicios(<?php echo htmlspecialchars($d['idord']); ?>, <?php echo htmlspecialchars($idodc); ?>)">Ver Servicios</button>
                                <button class="btn_ver_detalles" 
                                    onclick="verDetallesRemitentes(<?php echo htmlspecialchars($d['idord']); ?>, '<?php echo htmlspecialchars($d['invoice_number'] ?? ''); ?>')"
                                    style="background-color: #035c67;">
                                    Ver Honorarios y Totales
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php
        // Procesar guardado de ajustes manuales
        if (isset($_POST['guardar_ajustes'])) {
            $id_factura = intval($_POST['id_factura_ajuste']);
            $id_doctor = intval($_POST['id_doctor_ajuste']);
            $usuario = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Desconocido';
            $fecha_actual = date('Y-m-d H:i:s'); // Hora local de Honduras
        
            $desc_temporada = floatval($_POST['desc_temporada'] ?? 0);
            $desc_promo = floatval($_POST['desc_promo'] ?? 0);
            $desc_empleado = floatval($_POST['desc_empleado'] ?? 0);
            $desc_preferencial = floatval($_POST['desc_preferencial'] ?? 0);
            $notas_ajuste = trim($_POST['notas_ajuste'] ?? '');
        
            $sql_ajuste = "INSERT INTO honorarios_medicos (
                                id_factura, id_doctor, desc_temporada, desc_promo, 
                                desc_empleado, desc_preferencial, notas_ajuste, updated_by, updated_at, estado_pago
                           )
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
                           ON DUPLICATE KEY UPDATE
                                desc_temporada = VALUES(desc_temporada),
                                desc_promo = VALUES(desc_promo),
                                desc_empleado = VALUES(desc_empleado),
                                desc_preferencial = VALUES(desc_preferencial),
                                notas_ajuste = VALUES(notas_ajuste),
                                updated_by = VALUES(updated_by),
                                updated_at = VALUES(updated_at)";
            
            $stmt_ajuste = $connect->prepare($sql_ajuste);
            $ok = $stmt_ajuste->execute([
                $id_factura, $id_doctor, $desc_temporada, $desc_promo, 
                $desc_empleado, $desc_preferencial, $notas_ajuste, $usuario, $fecha_actual
            ]);
        
            if ($ok) {
                echo '<script>swal("¡Guardado!", "Los ajustes se han guardado correctamente.", "success").then(function(){ window.location.href = "honorarios.php"; });</script>';
            } else {
                echo '<script>swal("Error", "No se pudieron guardar los ajustes.", "error").then(function(){ window.location.href = "honorarios.php"; });</script>';
            }
        }

        // Procesar guardado de honorario
        if (isset($_POST['guardar_honorario'])) {
            header('Content-Type: application/json');
            session_start();
            $id_factura = intval($_POST['id_factura']);
            $id_doctor = intval($_POST['id_doctor']);
            $fecha_actual = date('Y-m-d H:i:s'); // Hora local de Honduras
            
            // Validar que el doctor existe
            $stmt = $connect->prepare("SELECT COUNT(*) FROM doctor WHERE idodc = ?");
            $stmt->execute([$id_doctor]);
            if ($stmt->fetchColumn() == 0) {
                echo json_encode(['success' => false, 'message' => 'El médico seleccionado no existe en la base de datos.']);
                exit;
            }
            $porcentaje = floatval($_POST['porcentaje_honorario']);
            $usuario = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Desconocido';
            $stmt = $connect->prepare("SELECT total_price FROM orders WHERE idord = ?");
            $stmt->execute([$id_factura]);
            $total = $stmt->fetchColumn();
            $monto = $total * $porcentaje / 100;
            $sql = "INSERT INTO honorarios_medicos (id_factura, id_doctor, porcentaje_honorario, monto_honorario, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE porcentaje_honorario = VALUES(porcentaje_honorario), monto_honorario = VALUES(monto_honorario), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)";
            $stmt = $connect->prepare($sql);
            $ok = $stmt->execute([$id_factura, $id_doctor, $porcentaje, $monto, $usuario, $fecha_actual]);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Honorario actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el honorario.']);
            }
            exit;
        }
        if (isset($_POST['marcar_pagado'])) {
            session_start();
            $id_factura = intval($_POST['id_factura']);
            $id_doctor = intval($_POST['id_doctor']);
            $usuario = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Desconocido';
            $fecha_actual = date('Y-m-d H:i:s'); // Hora local de Honduras
            
            $sql = "UPDATE honorarios_medicos SET estado_pago = 'pagado', fecha_pago = ?, updated_by = ?, updated_at = ? WHERE id_factura = ? AND id_doctor = ?";
            $stmt = $connect->prepare($sql);
            $stmt->execute([$fecha_actual, $usuario, $fecha_actual, $id_factura, $id_doctor]);
            echo '<script>swal("¡Pagado!", "Honorario marcado como pagado", "success").then(function(){location.reload();});</script>';
        }

        // Procesar guardado de remitentes
        if (isset($_POST['guardar_remitente'])) {
            $id_factura = intval($_POST['id_factura_remitente']);
            $id_doctor_remitente = intval($_POST['id_doctor_remitente']);
            $id_servicio = intval($_POST['id_servicio_remitente']);
            $factura = trim($_POST['factura_remitente']);
            $usuario = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Desconocido';
            $fecha_actual = date('Y-m-d H:i:s'); // Hora local de Honduras

            // Verificar si el servicio ya está asignado a un remitente para esta factura
            $stmt_check = $connect->prepare("SELECT COUNT(*) FROM remitentes_honorarios WHERE id_factura = ? AND id_servicio = ?");
            $stmt_check->execute([$id_factura, $id_servicio]);
            
            if ($stmt_check->fetchColumn() > 0) {
                echo '<script>swal("Error", "Este servicio ya está asignado a un remitente para esta factura.", "error").then(function(){ window.location.href = "honorarios.php"; });</script>';
            } else {
                // Obtener si el médico comisiona
                $stmt_doctor = $connect->prepare("SELECT comisiona FROM doctor WHERE idodc = ?");
                $stmt_doctor->execute([$id_doctor_remitente]);
                $comisiona = $stmt_doctor->fetchColumn();
                
                // Calcular monto de comisión
                $monto_comision = ($comisiona == 'SI') ? 350 : 250;

                // Crear tabla si no existe
                $sql_create_table = "CREATE TABLE IF NOT EXISTS remitentes_honorarios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_factura INT NOT NULL,
                    id_servicio INT NOT NULL,
                    id_doctor_remitente INT NOT NULL,
                    factura VARCHAR(50) NOT NULL,
                    monto_comision DECIMAL(10,2) NOT NULL,
                    usuario VARCHAR(100) NOT NULL,
                    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_factura_servicio (id_factura, id_servicio)
                )";
                $connect->exec($sql_create_table);

                $sql_remitente = "INSERT INTO remitentes_honorarios (
                                    id_factura, id_servicio, id_doctor_remitente, factura, 
                                    monto_comision, usuario, fecha_registro
                                 ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt_remitente = $connect->prepare($sql_remitente);
                $ok = $stmt_remitente->execute([
                    $id_factura, $id_servicio, $id_doctor_remitente, $factura, 
                    $monto_comision, $usuario, $fecha_actual
                ]);

                if ($ok) {
                    echo '<script>swal("¡Guardado!", "El remitente se ha guardado correctamente.", "success").then(function(){ window.location.href = "honorarios.php"; });</script>';
                } else {
                    echo '<script>swal("Error", "No se pudo guardar el remitente.", "error").then(function(){ window.location.href = "honorarios.php"; });</script>';
                }
            }
        }
        ?>
        <!-- Modal para Totales -->
        <div id="totalesModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeTotalesModal()">&times;</span>
                <h2>Totales de Factura</h2>
                <div class="table-container">
                    <table class="responsive-table">
                        <tbody id="totalesContent">
                            <!-- Aquí se llenan los datos dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Modal para Detalles -->
        <div id="detailsModal" class="modal">
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="serviciosModal" class="modal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
            <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 700px; border-radius: 8px;">
                <span class="close" onclick="document.getElementById('serviciosModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                <h2>Desglose de Honorarios por Servicio</h2>
                <div id="servicios-details-content" style="margin-top:20px;">
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Cantidad</th>
                                <th>Precio Unit. (LPS)</th>
                                <th>Subtotal (LPS)</th>
                                <th>Tipo Honorario</th>
                                <th>Restante (LPS)</th>
                                <th>Honorario (LPS)</th>
                            </tr>
                        </thead>
                        <tbody id="servicios-tbody">
                        </tbody>
                    </table>
                    <h4 style="text-align: right; margin-top: 15px;" id="total-honorario-servicios"></h4>
                </div>
            </div>
        </div>

        <!-- Modal para Ajustes Manuales -->
        <div id="ajustesModal" class="modal">
            <div class="modal-content" style="max-width: 600px;">
                <span class="close-btn" onclick="cerrarModalAjustes()">&times;</span>
                <h2>Ajustes Descuentos Honorarios</h2>
                <form method="POST" action="">
                    <input type="hidden" name="id_factura_ajuste" id="id_factura_ajuste">
                    <input type="hidden" name="id_doctor_ajuste" id="id_doctor_ajuste">

                    <div class="ajuste-item">
                        <label>Honorario por Servicios:</label>
                        <span id="ajuste_subtotal_servicios">LPS. 0.00</span>
                    </div>
                    <hr>
                    <div class="ajuste-item">
                        <label for="desc_temporada">Descuento Temporada (-):</label>
                        <input type="number" name="desc_temporada" id="desc_temporada" class="ajuste-input" step="0.01" min="0" oninput="calcularTotalAjustado()">
                    </div>
                    <div class="ajuste-item">
                        <label for="desc_promo">Descuento Promoción (-):</label>
                        <input type="number" name="desc_promo" id="desc_promo" class="ajuste-input" step="0.01" min="0" oninput="calcularTotalAjustado()">
                    </div>
                    <div class="ajuste-item">
                        <label for="desc_empleado">Descuento Empleado (-):</label>
                        <input type="number" name="desc_empleado" id="desc_empleado" class="ajuste-input" step="0.01" min="0" oninput="calcularTotalAjustado()">
                    </div>
                    <div class="ajuste-item">
                        <label for="desc_preferencial">Descuento Preferencial (-):</label>
                        <input type="number" name="desc_preferencial" id="desc_preferencial" class="ajuste-input" step="0.01" min="0" oninput="calcularTotalAjustado()">
                    </div>
                    <div class="ajuste-item">
                        <label for="notas_ajuste">Notas:</label>
                        <textarea name="notas_ajuste" id="notas_ajuste" rows="3" style="width: 100%;"></textarea>
                    </div>
                    <hr>
                    <div class="ajuste-item total-final">
                        <label>Total Honorario Final:</label>
                        <span id="ajuste_total_final">LPS. 0.00</span>
                    </div>
                    <br>
                    <button type="submit" name="guardar_ajustes" class="btn_ver_detalles">Guardar Ajustes</button>
                </form>
            </div>
        </div>

        <!-- Modal para Remitentes -->
        <div id="remitentesModal" class="modal">
            <div class="modal-content" style="max-width: 700px;">
                <span class="close-btn" onclick="cerrarModalRemitentes()">&times;</span>
                <h2>Configuración de Remitentes</h2>
                <form method="POST" action="">
                    <input type="hidden" name="id_factura_remitente" id="id_factura_remitente">
                    
                    <div class="remitente-item">
                        <label for="id_doctor_remitente">Médico Remitente:</label>
                        <select name="id_doctor_remitente" id="id_doctor_remitente" class="remitente-select" required>
                            <option value="">Seleccione un médico</option>
                        </select>
                    </div>
                    
                    <div class="remitente-item">
                        <label for="id_servicio_remitente">Estudio (Servicio):</label>
                        <select name="id_servicio_remitente" id="id_servicio_remitente" class="remitente-select" required>
                            <option value="">Seleccione un servicio</option>
                        </select>
                    </div>
                    
                    <div class="remitente-item">
                        <label for="factura_remitente">Factura:</label>
                        <input type="text" name="factura_remitente" id="factura_remitente" class="remitente-input" required>
                    </div>
                    
                    <div class="remitente-item">
                        <label>Monto Comisión:</label>
                        <span id="monto_comision_remitente" style="font-weight: bold; color: #28a745;">LPS. 0.00</span>
                    </div>
                    
                    <br>
                    <button type="submit" name="guardar_remitente" class="btn_ver_detalles">Guardar Remitente</button>
                </form>
                
                <hr style="margin: 20px 0;">
                <h3>Remitentes Registrados</h3>
                <div id="remitentes_registrados">
                    <!-- Aquí se mostrarán los remitentes ya registrados -->
                </div>
            </div>
        </div>

        <!-- Modal para Detalles de Remitentes -->
        <div id="detallesRemitentesModal" class="modal">
            <div class="modal-content" style="max-width: 900px;">
                <span class="close-btn" onclick="cerrarModalDetallesRemitentes()">&times;</span>
                <h2>Detalles de Comisión - Factura: <span id="factura_detalles_remitentes"></span></h2>
                
                <div class="resumen-honorarios">
                    <h3>Resumen de Honorarios</h3>
                    <div class="resumen-item">
                        <label>Honorario por Servicios:</label>
                        <span id="honorario_servicios_detalle">LPS. 0.00</span>
                    </div>
                    <div class="resumen-item" id="fila_total_descuentos" style="display:none;">
                        <label>Total Descuentos:</label>
                        <span id="total_descuentos_detalle">LPS. 0.00</span>
                    </div>
                    <div class="resumen-item">
                        <label>Total Comisiones Remitentes:</label>
                        <span id="total_comisiones_detalle">LPS. 0.00</span>
                    </div>
                    <div class="resumen-item total-final">
                        <label>Total Honorario Final:</label>
                        <span id="total_final_detalle" style="color: #28a745; font-size: 1.1em;">LPS. 0.00</span>
                    </div>
                    <div class="resumen-item total-final">
                        <label>Total Hospital Final:</label>
                        <span id="total_hospital_final" style="color: #035c67; font-size: 1.1em;">LPS. 0.00</span>
                    </div>
                </div>
                
                <hr style="margin: 20px 0;">
                <h3>Desglose de Remitentes por Médico</h3>
                <div id="detalles_remitentes_content">
                    <!-- Aquí se mostrará el desglose detallado -->
                </div>
            </div>
        </div>
    </main>
</section>


<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
    // Índice de la columna 'Detalles' (la última visible)
    var detallesColIndex = 13; // 0-based, la columna de detalles vuelve a ser la 13
    var remitentesColIndex = 8; // columna Remitentes
    var exportarCols = ':not(:eq(7)):not(:eq(' + remitentesColIndex + ')):not(:eq(9)):not(:eq(' + detallesColIndex + '))';
    var tabla = $('#example').DataTable({
        pageLength: 5, // Registros por página
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: 'Copiar',
                title: 'Honorarios Médicos - ' + new Date().toLocaleDateString('es-HN'),
                exportOptions: {
                    columns: exportarCols
                }
            },
            {
                extend: 'csv',
                text: 'CSV',
                title: 'Honorarios_Médicos_' + new Date().toISOString().split('T')[0],
                exportOptions: {
                    columns: exportarCols
                }
            },
            {
                text: 'Excel con Detalles',
                action: function (e, dt, node, config) {
                    exportarConDetalles(dt);
                }
            },
            {
                extend: 'excel',
                className: 'd-none', // Oculto
                title: 'Honorarios_Médicos_' + new Date().toISOString().split('T')[0],
                exportOptions: {
                    columns: exportarCols
                },
                customizeData: function(data) {
                    // Elimina las columnas de Remitentes y Detalles por nombre
                    const columnasAEliminar = [
                        'Remitentes', 'Detalles',
                        'Descuento Temporada (%)', 'Descuento Promoción (%)', 'Descuento Empleado (%)', 'Descuento Preferencial (%)', 'Notas Ajuste', 'Total Descuentos Aplicados (LPS)', 'Fecha Actualización'
                    ];
                    let indicesAEliminar = [];
                    data.header.forEach((col, idx) => {
                        if (columnasAEliminar.includes(col.trim())) {
                            indicesAEliminar.push(idx);
                        }
                    });
                    // Elimina de mayor a menor para no desfasar
                    indicesAEliminar.sort((a, b) => b - a);
                    indicesAEliminar.forEach(idx => {
                        data.header.splice(idx, 1);
                        for (let i = 0; i < data.body.length; i++) {
                            data.body[i].splice(idx, 1);
                        }
                    });
                    // Ahora agrega las columnas extendidas como ya lo haces
                    data.header.push(
                        'Factura', 'Total sin descuento', 'Total con descuento',
                        'Código', 'Nombre', 'Cantidad', 'Total', 'Descuento General', 'Descuento 3ra Edad', 'Descuento 4ta Edad', 'Promoción', 'Otros Descuentos', 'Descuentos Aplicados', 'Total a Pagar Sin ISV',
                        'Servicio', 'Cantidad Servicio', 'Precio Unitario Servicio', '% Honorario', 'Monto Honorario',
                        'Médico Remitente', 'Servicio Remitente', 'Comisión Remitente', 'Fecha Remitente',
                        'Total Hospital Final (LPS)'
                    );
                    for (let i = 0; i < data.body.length; i++) {
                        const row = data.body[i];
                        const factura = (row[0] || '').trim();
                        const ext = window.datosExportacion && window.datosExportacion[factura] ? window.datosExportacion[factura] : null;
                        if (ext) {
                            // Totales
                            row.push(ext.totales.factura, ext.totales.total_sin_descuento, ext.totales.total_con_descuento);
                            // Detalles
                            row.push(ext.detalles.codigo, ext.detalles.nombre, ext.detalles.cantidad, ext.detalles.total, ext.detalles.descuento_general, ext.detalles.descuento_3ra, ext.detalles.descuento_4ta, ext.detalles.promocion, ext.detalles.otros_descuentos, ext.detalles.descuentos_aplicados, ext.detalles.total_pagar);
                            // Servicios
                            row.push(ext.servicios.servicio, ext.servicios.cantidad, ext.servicios.precio_unitario, ext.servicios.porcentaje_honorario, ext.servicios.monto_honorario);
                            // Remitentes
                            row.push(ext.remitentes.medico, ext.remitentes.servicio, ext.remitentes.comision, ext.remitentes.fecha);
                            row.push(ext.total_hospital_final ?? '-');
                        } else {
                            for (let j = 0; j < 22; j++) row.push('-'); // Ajustar cantidad según columnas
                        }
                    }
                }
            },
            {
                extend: 'pdf',
                text: 'PDF',
                title: 'Honorarios Médicos - ' + new Date().toLocaleDateString('es-HN'),
                exportOptions: {
                    columns: exportarCols
                }
            },
            {
                extend: 'print',
                text: 'Imprimir',
                title: 'Honorarios Médicos - ' + new Date().toLocaleDateString('es-HN'),
                exportOptions: {
                    columns: exportarCols
                }
            }
        ],
        order: [[2, 'desc']], // Ordenar por fecha descendente
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
                targets: 2, // Columna de fecha
                type: 'datetime',
                render: function(data, type, row) {
                    return type === 'sort' ? new Date(data).getTime() : data;
                }
            }
        ],
        ordering: true,
        orderMulti: false,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]]
    });
});

function exportarConDetalles(dt) {
    // Obtén los números de factura visibles (o todos)
    var facturas = [];
    dt.rows({search: 'applied'}).every(function() {
        var data = this.data();
        facturas.push(data[0]); // Ajusta el índice si la factura no está en la primera columna
    });

    swal({ title: "Preparando exportación...", text: "Por favor espera...", buttons: false, closeOnClickOutside: false, closeOnEsc: false });

    $.ajax({
        url: 'get_datos_exportacion.php',
        type: 'POST',
        data: { facturas: facturas },
        dataType: 'json',
        success: function(respuesta) {
            window.datosExportacion = respuesta;
            swal.close();
            // Lanza el botón de Excel real (el oculto)
            dt.button('.buttons-excel').trigger();
        },
        error: function() {
            swal("Error", "No se pudieron obtener los datos extendidos para exportar.", "error");
        }
    });
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>

<script>
function viewDetails(orderId) {
    const modal = document.getElementById("detailsModal");
    modal.style.display = "flex";
    fetch(`../../backend/registros/obtener_detalles_checkout.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            const detailsTableBody = document.getElementById("detailsTableBody");
            detailsTableBody.innerHTML = "";
            data.forEach(item => {
                const row = document.createElement("tr");
                const formatCurrency = (value) => {
                    const numValue = parseFloat(value) || 0;
                    return `LPS. ${numValue.toLocaleString('es-HN', { 
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2 
                    })}`;
                };
                row.innerHTML = `
                    <td>${item.codigo ?? 'N/A'}</td>
                    <td>${item.impuesto !== null && item.impuesto !== '' ? item.impuesto : 'N/A'}</td>
                    <td>${item.nombre ?? 'N/A'}</td>
                    <td>${item.cantidad ?? 0}</td>
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
        .catch(error => console.error('Error al obtener detalles:', error));
}
function closeModal() {
    const modal = document.getElementById("detailsModal");
    if (modal) {
        modal.style.display = "none";
    }
}
// Cerrar modal si se hace clic fuera de él
window.onclick = function (event) {
    const modal = document.getElementById("detailsModal");
    if (event.target === modal) {
        closeModal();
    }
};
function mostrarTotales(btn) {
    const factura = btn.getAttribute('data-factura');
    const totalSin = btn.getAttribute('data-total-sin');
    const totalCon = btn.getAttribute('data-total-con');
    const idord = btn.getAttribute('data-idord');
    const tipc = btn.getAttribute('data-tipc');
    let facturasHtml = '';
    if (tipc === 'Boleta') {
        facturasHtml = `
            <tr><th>Factura General</th><td><a title="Boleta" href="../almacen/documento_general.php?id=${idord}" class="fa fa-file-text-o" target="_blank"></a></td></tr>
            <tr><th>Factura Desglosada</th><td><a title="Boleta" href="../almacen/documento.php?id=${idord}" class="fa fa-file-text-o" target="_blank"></a></td></tr>
        `;
    }
    const content = `
        <tr><th style='width:50%'>Número de Factura</th><td>${factura}</td></tr>
        <tr><th>Total sin descuento</th><td>LPS. ${totalSin}</td></tr>
        <tr><th>Total con descuento</th><td>LPS. ${totalCon}</td></tr>
        ${facturasHtml}
    `;
    document.getElementById('totalesContent').innerHTML = content;
    document.getElementById('totalesModal').style.display = 'flex';
}
function closeTotalesModal() {
    document.getElementById('totalesModal').style.display = 'none';
}
// Cerrar modal si se hace clic fuera de él
window.onclick = function (event) {
    const modal = document.getElementById('totalesModal');
    if (event.target === modal) {
        closeTotalesModal();
    }
};
function verServicios(idFactura, idDoctor) {
    if (!idDoctor) {
        swal('Información', 'Esta factura no tiene un médico asignado para calcular honorarios.', 'info');
        return;
    }
    $.ajax({
        url: 'get_servicios_honorario.php',
        type: 'GET',
        data: { 
            id_factura: idFactura,
            id_doctor: idDoctor 
        },
        dataType: 'json',
        success: function(data) {
            const tbody = $('#servicios-tbody');
            tbody.empty();
            let totalHonorarios = 0;

            if (data.length > 0) {
                data.forEach(function(servicio) {
                    const subtotal = parseFloat(servicio.subtotal) || 0;
                    const porcentaje = parseFloat(servicio.porcentaje_honorario) || 0;
                    const cuotaFija = parseFloat(servicio.cuota_fija) || 0;
                    const cantidad = parseFloat(servicio.cantidad) || 0;
                    
                    let honorarioLPS, restanteLPS, tipoHonorario;
                    
                    if (cuotaFija > 0) {
                        honorarioLPS = cuotaFija * cantidad;
                        restanteLPS = subtotal - honorarioLPS;
                        tipoHonorario = `LPS. ${cuotaFija.toFixed(2)} (Cuota Fija)`;
                    } else {
                        honorarioLPS = subtotal * (porcentaje / 100);
                        restanteLPS = subtotal - honorarioLPS;
                        tipoHonorario = `${porcentaje.toFixed(2)}% (Porcentaje)`;
                    }
                    
                    totalHonorarios += honorarioLPS;

                    let row = `<tr>
                        <td>${servicio.nombre_servicio}</td>
                        <td>${servicio.cantidad}</td>
                        <td>${parseFloat(servicio.precio_unitario).toFixed(2)}</td>
                        <td>${subtotal.toFixed(2)}</td>
                        <td>${tipoHonorario}</td>
                        <td>${restanteLPS.toFixed(2)}</td>
                        <td>${honorarioLPS.toFixed(2)}</td>
                    </tr>`;
                    tbody.append(row);
                });
            } else {
                tbody.append('<tr><td colspan="8" style="text-align:center;">No se encontraron servicios para esta factura.</td></tr>');
            }
            
            $('#total-honorario-servicios').text('Total Honorarios: LPS. ' + totalHonorarios.toFixed(2));
            $('#serviciosModal').css('display', 'block');
        },
        error: function() {
            Swal.fire('Error', 'No se pudo obtener el desglose de servicios.', 'error');
        }
    });
}
window.onclick = function(event) {
    if (event.target == document.getElementById('serviciosModal')) {
        document.getElementById('serviciosModal').style.display = "none";
    }
}

function abrirModalAjustes(button) {
    // Populate hidden fields
    document.getElementById('id_factura_ajuste').value = button.dataset.idFactura;
    document.getElementById('id_doctor_ajuste').value = button.dataset.idDoctor;

    // Populate visible fields
    document.getElementById('desc_temporada').value = parseFloat(button.dataset.descTemporada.replace(/,/, '')).toFixed(2);
    document.getElementById('desc_promo').value = parseFloat(button.dataset.descPromo.replace(/,/, '')).toFixed(2);
    document.getElementById('desc_empleado').value = parseFloat(button.dataset.descEmpleado.replace(/,/, '')).toFixed(2);
    document.getElementById('desc_preferencial').value = parseFloat(button.dataset.descPreferencial.replace(/,/, '')).toFixed(2);
    document.getElementById('notas_ajuste').value = button.dataset.notasAjuste;
    
    // Store base honorarium in a data attribute of the modal for calculation
    const modal = document.getElementById('ajustesModal');
    modal.dataset.honorarioServicios = button.dataset.honorarioServicios.replace(/,/, '');
    
    // Initial calculation
    calcularTotalAjustado();
    
    // Show modal
    modal.style.display = 'flex';
}

function cerrarModalAjustes() {
    document.getElementById('ajustesModal').style.display = 'none';
}

function calcularTotalAjustado() {
    const modal = document.getElementById('ajustesModal');
    const honorario_servicios = parseFloat(modal.dataset.honorarioServicios) || 0;
    
    const desc_temp = parseFloat(document.getElementById('desc_temporada').value) || 0;
    const desc_promo = parseFloat(document.getElementById('desc_promo').value) || 0;
    const desc_emp = parseFloat(document.getElementById('desc_empleado').value) || 0;
    const desc_pref = parseFloat(document.getElementById('desc_preferencial').value) || 0;
    
    const total_descuentos = desc_temp + desc_promo + desc_emp + desc_pref;
    const total_final = honorario_servicios - total_descuentos;

    document.getElementById('ajuste_subtotal_servicios').textContent = `LPS. ${honorario_servicios.toFixed(2)}`;
    document.getElementById('ajuste_total_final').textContent = `LPS. ${total_final.toFixed(2)}`;
}

// Consolidado para cerrar modales
window.onclick = function(event) {
    const detallesModal = document.getElementById("detailsModal");
    const totalesModal = document.getElementById('totalesModal');
    const serviciosModal = document.getElementById('serviciosModal');
    const ajustesModal = document.getElementById('ajustesModal');
    const remitentesModal = document.getElementById('remitentesModal');
    const detallesRemitentesModal = document.getElementById('detallesRemitentesModal');
    
    if (event.target == detallesModal) {
        closeModal();
    }
    if (event.target == totalesModal) {
        closeTotalesModal();
    }
    if (event.target == serviciosModal) {
        serviciosModal.style.display = "none";
    }
    if (event.target == ajustesModal) {
        cerrarModalAjustes();
    }
    if (event.target == remitentesModal) {
        cerrarModalRemitentes();
    }
    if (event.target == detallesRemitentesModal) {
        cerrarModalDetallesRemitentes();
    }
};

// Funciones para el modal de remitentes
function abrirModalRemitentes(idFactura, numeroFactura) {
    document.getElementById('id_factura_remitente').value = idFactura;
    document.getElementById('factura_remitente').value = numeroFactura;
    
    // Cargar médicos
    cargarMedicos();
    
    // Cargar servicios de la factura
    cargarServiciosFactura(idFactura);
    
    // Cargar remitentes registrados
    cargarRemitentesRegistrados(idFactura);
    
    document.getElementById('remitentesModal').style.display = 'flex';
}

function cerrarModalRemitentes() {
    document.getElementById('remitentesModal').style.display = 'none';
}

function cargarMedicos() {
    $.ajax({
        url: 'get_medicos_remitentes.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const select = $('#id_doctor_remitente');
            select.empty();
            select.append('<option value="">Seleccione un médico</option>');
            
            data.forEach(function(medico) {
                select.append(`<option value="${medico.idodc}" data-comisiona="${medico.comisiona}">${medico.nodoc} ${medico.apdoc} - ${medico.nomesp}</option>`);
            });
            
            // Inicializar Select2
            select.select2({
                placeholder: 'Seleccione un médico',
                allowClear: true
            });
        },
        error: function() {
            swal('Error', 'No se pudieron cargar los médicos.', 'error');
        }
    });
}

function cargarServiciosFactura(idFactura) {
    $.ajax({
        url: 'get_servicios_factura_remitentes.php',
        type: 'GET',
        data: { id_factura: idFactura },
        dataType: 'json',
        success: function(data) {
            const select = $('#id_servicio_remitente');
            select.empty();
            select.append('<option value="">Seleccione un servicio</option>');
            
            data.forEach(function(servicio) {
                select.append(`<option value="${servicio.id}">${servicio.nombre_servicio}</option>`);
            });
            
            // Inicializar Select2
            select.select2({
                placeholder: 'Seleccione un servicio',
                allowClear: true
            });
        },
        error: function() {
            swal('Error', 'No se pudieron cargar los servicios.', 'error');
        }
    });
}

function cargarRemitentesRegistrados(idFactura) {
    $.ajax({
        url: 'get_remitentes_registrados.php',
        type: 'GET',
        data: { id_factura: idFactura },
        dataType: 'json',
        success: function(data) {
            const container = $('#remitentes_registrados');
            container.empty();
            
            if (data.length > 0) {
                let html = '<table class="responsive-table" style="width: 100%; margin-top: 10px;">';
                html += '<thead><tr><th>Médico</th><th>Servicio</th><th>Factura</th><th>Comisión</th><th>Fecha</th></tr></thead><tbody>';
                
                data.forEach(function(remitente) {
                    html += `<tr>
                        <td>${remitente.nombre_medico}</td>
                        <td>${remitente.nombre_servicio}</td>
                        <td>${remitente.factura}</td>
                        <td>LPS. ${parseFloat(remitente.monto_comision).toFixed(2)}</td>
                        <td>${remitente.fecha_registro}</td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                container.html(html);
            } else {
                container.html('<p style="text-align: center; color: #666;">No hay remitentes registrados para esta factura.</p>');
            }
        },
        error: function() {
            $('#remitentes_registrados').html('<p style="text-align: center; color: #666;">Error al cargar remitentes registrados.</p>');
        }
    });
}

// Calcular comisión cuando se selecciona un médico
$(document).on('change', '#id_doctor_remitente', function() {
    const selectedOption = $(this).find('option:selected');
    const comisiona = selectedOption.data('comisiona');
    
    if (comisiona) {
        const montoComision = (comisiona === 'SI') ? 350 : 250;
        $('#monto_comision_remitente').text('LPS. ' + montoComision.toFixed(2));
    } else {
        $('#monto_comision_remitente').text('LPS. 0.00');
    }
});

// Funciones para el modal de detalles de remitentes
function verDetallesRemitentes(idFactura, numeroFactura) {
    document.getElementById('factura_detalles_remitentes').textContent = numeroFactura;
    cargarDetallesRemitentes(idFactura);
    document.getElementById('detallesRemitentesModal').style.display = 'flex';
}

function cerrarModalDetallesRemitentes() {
    document.getElementById('detallesRemitentesModal').style.display = 'none';
}

function cargarDetallesRemitentes(idFactura) {
    $.ajax({
        url: 'get_detalles_remitentes.php',
        type: 'GET',
        data: { id_factura: idFactura },
        dataType: 'json',
        success: function(data) {
            // Actualizar resumen de honorarios
            document.getElementById('honorario_servicios_detalle').textContent = 'LPS. ' + parseFloat(data.honorario_servicios || 0).toFixed(2);
            document.getElementById('total_descuentos_detalle').textContent = 'LPS. ' + parseFloat(data.total_descuentos || 0).toFixed(2);
            document.getElementById('total_comisiones_detalle').textContent = 'LPS. ' + parseFloat(data.total_comisiones || 0).toFixed(2);
            document.getElementById('total_final_detalle').textContent = 'LPS. ' + parseFloat(data.total_honorario_final || 0).toFixed(2);
            document.getElementById('total_hospital_final').textContent = 'LPS. ' + parseFloat(data.total_hospital_final || 0).toFixed(2);
            
            // Mostrar desglose de remitentes
            const detallesContent = document.getElementById('detalles_remitentes_content');
            detallesContent.innerHTML = '';
            
            if (data.remitentes && data.remitentes.length > 0) {
                // Agrupar por médico
                const medicos = {};
                data.remitentes.forEach(function(remitente) {
                    if (!medicos[remitente.id_doctor_remitente]) {
                        medicos[remitente.id_doctor_remitente] = {
                            nombre: remitente.nombre_medico,
                            servicios: [],
                            total: 0
                        };
                    }
                    medicos[remitente.id_doctor_remitente].servicios.push(remitente);
                    medicos[remitente.id_doctor_remitente].total += parseFloat(remitente.monto_comision);
                });
                
                let html = '';
                Object.keys(medicos).forEach(function(doctorId) {
                    const medico = medicos[doctorId];
                    html += '<div class="medico-remitente">';
                    html += '<h4>' + medico.nombre + ' - Total: LPS. ' + medico.total.toFixed(2) + '</h4>';
                    html += '<table class="responsive-table" style="width: 100%; margin: 10px 0;">';
                    html += '<thead><tr><th>Servicio</th><th>Factura</th><th>Comisión</th><th>Fecha</th></tr></thead><tbody>';
                    
                    medico.servicios.forEach(function(servicio) {
                        html += '<tr>';
                        html += '<td>' + servicio.nombre_servicio + '</td>';
                        html += '<td>' + servicio.factura + '</td>';
                        html += '<td>LPS. ' + parseFloat(servicio.monto_comision).toFixed(2) + '</td>';
                        html += '<td>' + servicio.fecha_registro + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                    html += '</div>';
                });
                
                detallesContent.innerHTML = html;
            } else {
                detallesContent.innerHTML = '<p style="text-align: center; color: #666;">No hay remitentes registrados para esta factura.</p>';
            }
        },
        error: function() {
            $('#detalles_remitentes_content').html('<p style="text-align: center; color: #666;">Error al cargar detalles de remitentes.</p>');
        }
    });
}
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

.btn_ver_detalles,
.btn_ver_detalles[style],
.btn_ver_detalles:focus,
.btn_ver_detalles:active {
    background-color: #035c67;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-bottom: 4px;
}
.btn_ver_detalles:last-child {
    margin-bottom: 0;
}
.btn_ver_detalles:hover,
.btn_ver_detalles[style]:hover {
    background-color: #06adbf !important;
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

.btn_devolucion:hover {
    background-color: #06adbf;
}

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
    top: 4px;
    left: 4px;
}

/* Efecto cuando el checkbox está marcado */
.status-switch input:checked + .status-slider {
    background-color: #4CAF50;
}

.status-switch input:checked + .status-slider:before {
    transform: translateX(26px);
}

/* Mejorar el modal de totales para que sea responsivo y visualmente atractivo */
#totalesModal .modal-content {
    max-width: 400px;
    width: 90%;
    margin: 5% auto;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.2);
    background: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
}
#totalesModal .table-container {
    width: 100%;
    overflow-x: auto;
}
#totalesModal .responsive-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
#totalesModal .responsive-table th, #totalesModal .responsive-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}
#totalesModal .responsive-table th {
    background: #f2f2f2;
    font-weight: bold;
    width: 50%;
}
#totalesModal .responsive-table td {
    background: #fff;
}
@media (max-width: 600px) {
    #totalesModal .modal-content {
        padding: 10px;
        max-width: 98vw;
    }
    #totalesModal .responsive-table th, #totalesModal .responsive-table td {
        padding: 6px;
        font-size: 14px;
    }
}

.input-percent {
    width: 80px;
    padding: 5px;
    text-align: right;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.ajuste-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    flex-wrap: wrap;
}
.ajuste-item label {
    font-weight: bold;
    flex-basis: 40%;
}
.ajuste-input {
    flex-basis: 55%;
    width: auto;
}
.ajuste-item input, .ajuste-item textarea, .ajuste-item span {
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.ajuste-item span {
    font-weight: bold;
    color: #035c67;
    border: none;
    text-align: right;
}
.total-final {
    font-size: 1.2em;
    border-top: 2px solid #eee;
    margin-top: 10px;
    padding-top: 10px;
}

/* Estilos para el modal de remitentes */
.remitente-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    flex-wrap: wrap;
}

.remitente-item label {
    font-weight: bold;
    flex-basis: 30%;
    min-width: 150px;
}

.remitente-select, .remitente-input {
    flex-basis: 65%;
    width: auto;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.remitente-item span {
    font-weight: bold;
    color: #28a745;
    border: none;
    text-align: right;
    flex-basis: 65%;
}

/* Estilos para Select2 en el modal de remitentes */
.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    padding-left: 12px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}

/* Responsive para el modal de remitentes */
@media (max-width: 768px) {
    .remitente-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .remitente-item label {
        flex-basis: 100%;
        margin-bottom: 5px;
    }
    
    .remitente-select, .remitente-input, .remitente-item span {
        flex-basis: 100%;
        width: 100%;
    }
}

/* Estilos para el modal de detalles de remitentes */
.resumen-honorarios {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.resumen-honorarios h3 {
    margin-top: 0;
    color: #035c67;
    border-bottom: 2px solid #035c67;
    padding-bottom: 5px;
}

.resumen-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.resumen-item:last-child {
    border-bottom: none;
}

.resumen-item label {
    font-weight: bold;
    color: #495057;
}

.resumen-item span {
    font-weight: bold;
    color: #035c67;
}

.resumen-item.total-final {
    font-size: 1.2em;
    border-top: 2px solid #035c67;
    margin-top: 10px;
    padding-top: 10px;
}

.resumen-item.total-final span {
    color: #28a745;
    font-size: 1.1em;
}

.medico-remitente {
    margin-bottom: 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.medico-remitente h4 {
    background: #035c67;
    color: white;
    margin: 0;
    padding: 10px 15px;
    font-size: 1.1em;
}

.medico-remitente table {
    margin: 0;
}

.medico-remitente th {
    background: #f8f9fa;
    color: #495057;
}

/* Responsive para el modal de detalles */
@media (max-width: 768px) {
    .resumen-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .resumen-item label {
        margin-bottom: 5px;
    }
    
    .medico-remitente h4 {
        font-size: 1em;
        padding: 8px 10px;
    }
}
</style>
</body>
</html> 