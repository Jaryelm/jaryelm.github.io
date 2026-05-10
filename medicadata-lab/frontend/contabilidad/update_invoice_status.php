<?php
require_once('../../backend/bd/Conexion.php');
require_once('../../backend/php/funciones_diario_general.php');
session_start();

// Establecer zona horaria de Honduras
date_default_timezone_set('America/Tegucigalpa');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $usuarioActual = $_SESSION['username']; // Usuario actual desde la sesión

    if ($id > 0 && in_array($status, ['Pendiente', 'Cobrada'])) {
        // Verificar si ya se cambió a "Cobrada" anteriormente
        $stmtCheck = $connect->prepare("
            SELECT invoice_status 
            FROM orders 
            WHERE idord = :id
        ");
        $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtCheck->execute();
        $currentStatus = $stmtCheck->fetchColumn();

        if ($currentStatus === 'Cobrada' && $status === 'Pendiente') {
            echo '<script type="text/javascript">
            swal("Operación no permitida", "No se puede revertir el estado de una factura cobrada.", "warning").then(function() {
                window.location.reload();
            });
            </script>';
            exit;
        }

        if ($currentStatus === 'Cobrada' && $status === 'Cobrada') {
            echo '<script type="text/javascript">
            swal("Factura ya cobrada", "Esta factura ya fue cobrada. No se puede volver a actualizar.", "info").then(function() {
                window.location.reload();
            });
            </script>';
            exit;
        }

        // Actualizar el estado y manejar `updated_by` y `updated_at`
        $stmtUpdate = $connect->prepare("
            UPDATE orders 
            SET invoice_status = :status, 
                updated_by = :updated_by, 
                updated_at = :updated_at
            WHERE idord = :id
        ");
        $stmtUpdate->bindParam(':status', $status, PDO::PARAM_STR);
        $stmtUpdate->bindValue(':updated_by', $status === 'Cobrada' ? $usuarioActual : null, PDO::PARAM_STR);
        $stmtUpdate->bindValue(':updated_at', $status === 'Cobrada' ? date('Y-m-d H:i:s') : null, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmtUpdate->execute()) {
            // Registrar transacciones contables cuando se marca como "Cobrada"
            if ($status === 'Cobrada') {
                try {
                    registrarTransaccionesFacturaCobrada($id);
                } catch (Exception $e) {
                    error_log("Error al registrar transacciones de factura cobrada: " . $e->getMessage());
                }
            }
            
            echo '<script type="text/javascript">
            swal("¡Actualizado!", "Estado actualizado correctamente. Esta acción no se puede deshacer.", "success").then(function() {
                window.location.reload();
            });
            </script>';
        } else {
            echo '<script type="text/javascript">
            swal("Error!", "Error al actualizar el estado", "error").then(function() {
                window.location.reload();
            });
            </script>';
        }
    } else {
        echo '<script type="text/javascript">
        swal("Datos inválidos!", "Verifica los datos proporcionados", "warning").then(function() {
            window.location.reload();
        });
        </script>';
    }
} else {
    echo '<script type="text/javascript">
    swal("Método no permitido!", "Usa el método POST para esta acción", "error").then(function() {
        window.location.reload();
    });
    </script>';
}
?>
