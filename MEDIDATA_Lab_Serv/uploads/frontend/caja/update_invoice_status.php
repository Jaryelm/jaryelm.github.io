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
            SELECT invoice_status, invoice_number 
            FROM orders 
            WHERE idord = :id
        ");
        $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtCheck->execute();
        $ordenRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $currentStatus = $ordenRow['invoice_status'] ?? null;
        $referencia = trim((string) ($ordenRow['invoice_number'] ?? ''));
        if ($referencia === '') {
            $referencia = (string) $id;
        }

        if ($currentStatus === 'Cobrada' && $status === 'Pendiente') {
            if (medidata_existe_partida_diario_factura($referencia)) {
                echo '<script type="text/javascript">
                Swal.fire("Operación no permitida", "No se puede revertir el estado de una factura cobrada.", "warning").then(function() {
                    window.location.reload();
                });
                </script>';
                exit;
            }
        }

        if ($currentStatus === 'Cobrada' && $status === 'Cobrada') {
            if (!medidata_existe_partida_diario_factura($referencia)) {
                $diario = medidata_asegurar_partida_diario_factura_cobrada($id);
                if ($diario['ok'] && !$diario['skipped']) {
                    $msg = 'Se registró la partida en Diario General: ' . ($diario['numero_partida'] ?? '');
                    $icon = 'success';
                } elseif ($diario['ok'] && $diario['skipped']) {
                    $msg = 'La partida ya existía en el Diario General.';
                    $icon = 'info';
                } else {
                    $msg = 'No se pudo registrar en Diario General: ' . $diario['message'];
                    $icon = 'error';
                }
                $msgJs = addslashes($msg);
                echo '<script type="text/javascript">
                Swal.fire("' . ($icon === 'error' ? 'Error' : 'Diario General') . '", "' . $msgJs . '", "' . $icon . '").then(function() {
                    window.location.reload();
                });
                </script>';
                exit;
            }
            echo '<script type="text/javascript">
            Swal.fire("Factura ya cobrada", "Esta factura ya fue cobrada. No se puede volver a actualizar.", "info").then(function() {
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
            if ($status === 'Cobrada') {
                $diario = medidata_asegurar_partida_diario_factura_cobrada($id);
                if (!$diario['ok']) {
                    // Consistencia crítica: no permitir estado Cobrada sin asiento contable.
                    $stmtRollback = $connect->prepare("UPDATE orders SET invoice_status = 'Pendiente', updated_by = NULL, updated_at = NULL WHERE idord = :id");
                    $stmtRollback->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmtRollback->execute();

                    $msgJs = addslashes($diario['message']);
                    echo '<script type="text/javascript">
                    Swal.fire("No se completó el cobro", "No se generó la partida en Diario General. La factura volvió a estado Pendiente. Detalle: ' . $msgJs . '", "error").then(function() {
                        window.location.reload();
                    });
                    </script>';
                    exit;
                }
                $extra = $diario['skipped'] ? '' : ' Partida: ' . ($diario['numero_partida'] ?? '') . '.';
                echo '<script type="text/javascript">
                Swal.fire("¡Actualizado!", "Estado actualizado correctamente.' . addslashes($extra) . '", "success").then(function() {
                    window.location.reload();
                });
                </script>';
                exit;
            }

            echo '<script type="text/javascript">
            Swal.fire("¡Actualizado!", "Estado actualizado correctamente.", "success").then(function() {
                window.location.reload();
            });
            </script>';
        } else {
            echo '<script type="text/javascript">
            Swal.fire("Error!", "Error al actualizar el estado", "error").then(function() {
                window.location.reload();
            });
            </script>';
        }
    } else {
        echo '<script type="text/javascript">
        Swal.fire("Datos inválidos!", "Verifica los datos proporcionados", "warning").then(function() {
            window.location.reload();
        });
        </script>';
    }
} else {
    echo '<script type="text/javascript">
    Swal.fire("Método no permitido!", "Usa el método POST para esta acción", "error").then(function() {
        window.location.reload();
    });
    </script>';
}
?>
