<?php
require_once('../../backend/bd/Conexion.php');

// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

if (!isset($_POST['id'])) {
    echo "ID de requisición no proporcionado";
    exit;
}

$id = $_POST['id'];

try {
    // Obtener información de la requisición
    $stmt = $connect->prepare("
        SELECT r.*, 
        u1.name as nombre_solicitante,
        u2.name as nombre_autorizador
        FROM requisiciones r
        LEFT JOIN users u1 ON r.solicitante_id = u1.id
        LEFT JOIN users u2 ON r.usuario_autorizacion = u2.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $requisicion = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener los detalles de los productos
    $stmt = $connect->prepare("
        SELECT rd.*, p.nompro, p.preprd, p.codpro
        FROM requisicion_detalles rd
        JOIN product p ON rd.producto_id = p.idprcd
        WHERE rd.requisicion_id = ?
    ");
    $stmt->execute([$id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar el HTML de los detalles
    ?>
    <div class="detalles-requisicion">
        <div class="info-general">
            <div class="info-row">
                <div class="info-column">
                    <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($requisicion['nombre_solicitante']); ?></p>
                    <p><strong>Fecha Solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($requisicion['fecha_solicitud'])); ?></p>
                    <p><strong>Estado:</strong> 
                        <span class="badge badge-<?php echo $requisicion['estado'] === 'pendiente' ? 'warning' : 
                            ($requisicion['estado'] === 'aprobado' ? 'success' : 'danger'); ?>">
                            <?php echo ucfirst($requisicion['estado']); ?>
                        </span>
                    </p>
                </div>
                <div class="info-column">
                    <p><strong>Bodega Descargo:</strong> <?php echo htmlspecialchars($requisicion['bodega_descargo']); ?></p>
                    <p><strong>Bodega Cargo:</strong> <?php echo htmlspecialchars($requisicion['bodega_cargo']); ?></p>
                    <?php if ($requisicion['estado'] !== 'pendiente'): ?>
                        <p><strong>Entregado por:</strong> <?php echo htmlspecialchars($requisicion['nombre_autorizador']); ?></p>
                        <p><strong>Fecha Autorización:</strong> <?php echo date('d/m/Y H:i', strtotime($requisicion['fecha_autorizacion'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="justificacion">
                <strong>Justificación:</strong><br>
                <?php echo nl2br(htmlspecialchars($requisicion['justificacion'])); ?>
            </div>
        </div>

        <h3>Artículos Solicitados</h3>
        <table class="detalles-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Artículo</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                foreach ($detalles as $detalle):
                    $subtotal = $detalle['cantidad'] * $detalle['preprd'];
                    $total += $subtotal;
                ?>
                <tr>
                    <td><?php echo $detalle['codpro']; ?></td>
                    <td><?php echo htmlspecialchars($detalle['nompro']); ?></td>
                    <td><?php echo $detalle['cantidad']; ?></td>
                    <td>L. <?php echo number_format($detalle['preprd'], 2); ?></td>
                    <td>L. <?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong>L. <?php echo number_format($total, 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <style>
    .detalles-requisicion {
        padding: 20px;
    }

    .info-general {
        margin-bottom: 30px;
    }

    .info-row {
        display: flex;
        gap: 40px;
        margin-bottom: 20px;
    }

    .info-column {
        flex: 1;
    }

    .info-column p {
        margin: 5px 0;
    }

    .justificacion {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin-top: 15px;
    }

    .detalles-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .detalles-table th,
    .detalles-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .detalles-table th {
        background-color: #06adbf;
        color: white;
    }

    .detalles-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .detalles-table tfoot tr td {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    @media (max-width: 768px) {
        .info-row {
            flex-direction: column;
            gap: 20px;
        }
    }
    </style>
    <?php
} catch (Exception $e) {
    echo "Error al obtener los detalles: " . $e->getMessage();
}
?> 