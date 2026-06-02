<?php
declare(strict_types=1);

require_once __DIR__ . '/../bd/Conexion.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

function medidata_diario_json_error(string $msg, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    medidata_diario_json_error('Metodo no permitido.', 405);
}

$numeroPartida = trim((string) ($_POST['numero_partida'] ?? ''));
$referencia = trim((string) ($_POST['referencia'] ?? ''));
$tipoTransaccion = strtoupper(trim((string) ($_POST['tipo_transaccion'] ?? '')));
$fechaNueva = trim((string) ($_POST['fecha_ocurrencia'] ?? ''));
$motivo = trim((string) ($_POST['motivo'] ?? ''));
$sincronizarCompra = isset($_POST['sync_compra_fecha_emision']) && $_POST['sync_compra_fecha_emision'] === '1';

if ($numeroPartida === '' || $referencia === '' || $tipoTransaccion === '') {
    medidata_diario_json_error('Parametros incompletos.');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNueva)) {
    medidata_diario_json_error('Fecha invalida. Use formato YYYY-MM-DD.');
}
if (!in_array($tipoTransaccion, ['COMPRA_PROVEEDOR', 'CIERRE_VENTA'], true)) {
    medidata_diario_json_error('Tipo de transaccion no editable.');
}
if ($motivo === '') {
    medidata_diario_json_error('Debe ingresar el motivo de correccion.');
}

$usuarioUsername = trim((string) ($_SESSION['username'] ?? ''));
$usuarioNombre = trim((string) ($_SESSION['name'] ?? ''));
$usuarioAudit = $usuarioNombre !== '' ? $usuarioNombre : ($usuarioUsername !== '' ? $usuarioUsername : 'usuario_desconocido');

try {
    // IMPORTANTE: DDL (CREATE TABLE) hace commit implicito en MySQL.
    // Debe ejecutarse fuera de la transaccion de negocio.
    $connect->exec(
        'CREATE TABLE IF NOT EXISTS diario_general_ediciones (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            numero_partida VARCHAR(30) NOT NULL,
            referencia VARCHAR(120) NULL,
            tipo_transaccion VARCHAR(60) NULL,
            fecha_anterior DATE NULL,
            fecha_nueva DATE NOT NULL,
            motivo VARCHAR(255) NOT NULL,
            usuario VARCHAR(120) NOT NULL,
            username VARCHAR(120) NULL,
            ip_origen VARCHAR(64) NULL,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $connect->beginTransaction();

    $stPrev = $connect->prepare(
        'SELECT MIN(fecha_ocurrencia) AS fecha_actual, COUNT(*) AS total
         FROM diario_general_transacciones
         WHERE numero_partida = ? AND referencia = ? AND tipo_transaccion = ?'
    );
    $stPrev->execute([$numeroPartida, $referencia, $tipoTransaccion]);
    $prev = $stPrev->fetch(PDO::FETCH_ASSOC);
    $total = (int) ($prev['total'] ?? 0);
    if ($total <= 0) {
        throw new Exception('No se encontraron renglones para la partida seleccionada.');
    }
    $fechaActual = (string) ($prev['fecha_actual'] ?? '');
    if ($fechaActual === '') {
        $fechaActual = $fechaNueva;
    }

    $stUp = $connect->prepare(
        'UPDATE diario_general_transacciones
         SET fecha_ocurrencia = ?
         WHERE numero_partida = ? AND referencia = ? AND tipo_transaccion = ?'
    );
    $stUp->execute([$fechaNueva, $numeroPartida, $referencia, $tipoTransaccion]);
    $renglonesAfectados = $stUp->rowCount();

    $idCompra = null;
    if ($sincronizarCompra && $tipoTransaccion === 'COMPRA_PROVEEDOR' && preg_match('/^COMP-(\d+)$/i', $referencia, $m)) {
        $idCompra = (int) $m[1];
        if ($idCompra > 0) {
            $stCompra = $connect->prepare('UPDATE compras SET fecha_emision = ? WHERE id_compra = ?');
            $stCompra->execute([$fechaNueva, $idCompra]);
        }
    }

    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $stAudit = $connect->prepare(
        'INSERT INTO diario_general_ediciones
         (numero_partida, referencia, tipo_transaccion, fecha_anterior, fecha_nueva, motivo, usuario, username, ip_origen)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stAudit->execute([
        $numeroPartida,
        $referencia,
        $tipoTransaccion,
        $fechaActual,
        $fechaNueva,
        $motivo,
        $usuarioAudit,
        $usuarioUsername !== '' ? $usuarioUsername : null,
        $ip !== '' ? $ip : null,
    ]);

    $connect->commit();
    echo json_encode([
        'ok' => true,
        'message' => 'Partida actualizada correctamente.',
        'renglones' => $renglonesAfectados,
        'compra_sincronizada' => $idCompra,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    medidata_diario_json_error('No se pudo actualizar la partida: ' . $e->getMessage(), 500);
}

