<?php
require_once('../../backend/bd/Conexion.php');
require_once('../../backend/php/funciones_diario_general.php');
session_start();

date_default_timezone_set('America/Tegucigalpa');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = isset($data['orderId']) ? intval($data['orderId']) : 0;
$observacion = isset($data['observacion']) ? trim($data['observacion']) : '';

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de orden inválido']);
    exit;
}
if ($observacion === '') {
    echo json_encode(['success' => false, 'message' => 'Debe indicar el motivo de la anulación.']);
    exit;
}

// Crear columnas si no existen ANTES de la transacción (ALTER TABLE hace COMMIT implícito en MySQL)
try {
    $stmtCol = $connect->query("SHOW COLUMNS FROM orders LIKE 'anulada_por'");
    if ($stmtCol->rowCount() == 0) {
        $connect->exec("ALTER TABLE orders ADD COLUMN anulada_por VARCHAR(100) DEFAULT NULL");
    }
    $stmtCol = $connect->query("SHOW COLUMNS FROM orders LIKE 'anulada_at'");
    if ($stmtCol->rowCount() == 0) {
        $connect->exec("ALTER TABLE orders ADD COLUMN anulada_at DATETIME DEFAULT NULL");
    }
    $stmtCol = $connect->query("SHOW COLUMNS FROM orders LIKE 'observacion_anulacion'");
    if ($stmtCol->rowCount() == 0) {
        $connect->exec("ALTER TABLE orders ADD COLUMN observacion_anulacion TEXT DEFAULT NULL");
    }
} catch (Exception $e) { /* ignorar */ }

try {
    $connect->beginTransaction();

    // 1. Verificar que la orden existe y no está ya anulada
    $stmtOrder = $connect->prepare("SELECT idord, invoice_status, invoice_number FROM orders WHERE idord = ?");
    $stmtOrder->execute([$orderId]);
    $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("No se encontró la orden.");
    }
    if ($order['invoice_status'] === 'Anulada') {
        throw new Exception("Esta factura ya fue anulada anteriormente.");
    }

    // 2. Obtener todos los ítems de la orden para restaurar stock
    $stmtDetails = $connect->prepare("
        SELECT id, product_id, hospitalario_id, cantidad, item_type 
        FROM order_details 
        WHERE order_id = ?
    ");
    $stmtDetails->execute([$orderId]);
    $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

    // 3. Restaurar stock de cada ítem (productos y productos hospitalarios; servicios no tienen stock)
    foreach ($details as $item) {
        $cantidad = (int)$item['cantidad'];
        if ($cantidad <= 0) continue;

        if (!empty($item['product_id'])) {
            $stmtRestore = $connect->prepare("UPDATE product SET stock = stock + ? WHERE idprcd = ?");
            $stmtRestore->execute([$cantidad, $item['product_id']]);
        } elseif (!empty($item['hospitalario_id'])) {
            $stmtRestore = $connect->prepare("UPDATE almacen_hospitalario SET stock = stock + ? WHERE idprcd = ?");
            $stmtRestore->execute([$cantidad, $item['hospitalario_id']]);
        }
    }

    $usuario = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Sistema';

    // 5. Marcar la orden como Anulada y guardar observación
    $stmtUpdate = $connect->prepare("
        UPDATE orders 
        SET invoice_status = 'Anulada', anulada_por = ?, anulada_at = NOW(), observacion_anulacion = ? 
        WHERE idord = ?
    ");
    $stmtUpdate->execute([$usuario, $observacion, $orderId]);

    // 6. Reversión contable: crear partidas inversas si la factura tenía registros en el diario
    $invoiceNumber = $order['invoice_number'] ?? null;
    $partidaReversion = $invoiceNumber ? registrarReversionAnulacionFactura($invoiceNumber) : null;

    $connect->commit();
    $msg = 'Factura anulada correctamente. El stock ha sido restaurado.';
    if ($partidaReversion) {
        $msg .= ' Se registró la reversión contable.';
    }
    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Exception $e) {
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
