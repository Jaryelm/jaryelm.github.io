<?php
require_once __DIR__ . '/../bd/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Prioridad: id de producto → stock operativo en tabla product (misma fuente que ventas/caja)
    if (isset($_POST['producto_id']) && $_POST['producto_id'] !== '' && $_POST['producto_id'] !== '0') {
        $id = (int) $_POST['producto_id'];
        $stmt = $connect->prepare('SELECT CAST(stock AS UNSIGNED) AS s FROM product WHERE idprcd = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success' => true, 'stock_disponible' => (int) $row['s']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
        }
        exit;
    }

    // Compatibilidad: por nombre (legacy) — preferir producto_id en formularios nuevos
    if (isset($_POST['producto_nombre']) && $_POST['producto_nombre'] !== '') {
        $nombre = trim($_POST['producto_nombre']);
        $stmt = $connect->prepare('SELECT idprcd, CAST(stock AS UNSIGNED) AS s FROM product WHERE nompro = ? AND state = ? ORDER BY idprcd DESC LIMIT 1');
        $stmt->execute([$nombre, '1']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode([
                'success' => true,
                'stock_disponible' => (int) $row['s'],
                'producto_id' => (int) $row['idprcd'],
            ]);
        } else {
            echo json_encode(['success' => true, 'stock_disponible' => 0]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'No se proporcionó producto_id ni producto_nombre']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
