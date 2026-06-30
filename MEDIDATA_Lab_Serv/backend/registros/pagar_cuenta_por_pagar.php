<?php
declare(strict_types=1);

/**
 * Registra el PAGO de una cuenta por pagar (proveedor comercial u honorario médico)
 * generando la partida contable balanceada en el Diario General.
 *
 * Comercial: Debe 210200107 (Proveedores Comerciales) / Haber cuenta de salida.  tipo: PAGO_PROVEEDOR
 * Médico:    Debe 210200108 (Honorarios Médicos)      / Haber cuenta de salida.  tipo: PAGO_HONORARIO_MEDICO
 *            + marca honorarios_medicos.estado_pago = 'pagado'.
 *
 * Sistema: MEDIDATA
 */

include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/funciones_diario_general.php';

header('Content-Type: application/json; charset=utf-8');

function pago_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        pago_json(['success' => false, 'message' => 'Método no permitido.'], 405);
    }
    if (!isset($connect) || !($connect instanceof PDO)) {
        pago_json(['success' => false, 'message' => 'No hay conexión a la base de datos.'], 500);
    }

    $modo         = trim((string) ($_POST['modo'] ?? ''));
    
    $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
    if (empty($ids) && isset($_POST['id'])) {
        $ids = [(int)$_POST['id']];
    }
    
    $cuentaSalida = trim((string) ($_POST['cuenta_salida'] ?? ''));
    $usuario      = isset($_SESSION['name']) ? (string) $_SESSION['name'] : 'Sistema';

    // Validar que la cuenta de salida exista en el catálogo
    $stVal = $connect->prepare("SELECT cuenta FROM cuentas_catalogo WHERE cuenta = ? LIMIT 1");
    $stVal->execute([$cuentaSalida]);
    if (!$stVal->fetch()) {
        pago_json(['success' => false, 'message' => 'Cuenta de salida inválida o no existe en el catálogo.'], 400);
    }
    if (empty($ids)) {
        pago_json(['success' => false, 'message' => 'Identificadores inválidos o vacíos.'], 400);
    }
    if ($modo !== 'comercial' && $modo !== 'medico') {
        pago_json(['success' => false, 'message' => 'Modo inválido.'], 400);
    }

    $fechaOcc = date('Y-m-d');
    $lockName = 'pago_cxp_multi_' . preg_replace('/[^a-zA-Z0-9_]/', '', $usuario);

    $lock = $connect->query("SELECT GET_LOCK('" . $lockName . "', 5)")->fetchColumn();
    if ((int) $lock !== 1) {
        pago_json(['success' => false, 'message' => 'Sistema ocupado. Espere unos segundos e intente de nuevo.'], 429);
    }

    try {
        $trans = [];
        $totalGeneral = 0;
        $connect->beginTransaction();
        
        if ($modo === 'comercial') {
            $st = $connect->prepare('SELECT total, dato_fac, prov_datos FROM compras WHERE id_compra = ? LIMIT 1');
            $stp = $connect->prepare(
                "SELECT COALESCE(SUM(debe), 0) FROM diario_general_transacciones
                 WHERE referencia = ? AND cuenta = '210200107'"
            );
            
            foreach ($ids as $id) {
                $st->execute([$id]);
                $compra = $st->fetch(PDO::FETCH_ASSOC);
                if (!$compra) continue;
                
                $total = round((float) $compra['total'], 2);
                $stp->execute(['COMP-' . $id]);
                $pagado = round((float) $stp->fetchColumn(), 2);
                
                $saldo = round($total - $pagado, 2);
                if ($saldo <= 0.005) continue;
                
                $ref  = 'COMP-' . $id;
                $fact = trim((string) ($compra['dato_fac'] ?? ''));
                $prov = trim((string) ($compra['prov_datos'] ?? ''));
                $desc = 'Pago a proveedor OC ' . $id
                    . ($fact !== '' ? ' Fact ' . $fact : '')
                    . ($prov !== '' ? ' | ' . $prov : '');
                    
                $trans[] = [
                    'unidad_servicio' => 'Hospital Medicasa',
                    'cuenta' => '210200107',
                    'nombre_cuenta' => obtenerNombreCuenta('210200107'),
                    'descripcion' => $desc,
                    'debe' => $saldo,
                    'haber' => 0,
                    'turno' => null,
                    'usuario' => $usuario,
                    'tipo_transaccion' => 'PAGO_PROVEEDOR',
                    'referencia' => $ref,
                ];
                
                $trans[] = [
                    'unidad_servicio' => 'Hospital Medicasa',
                    'cuenta' => $cuentaSalida,
                    'nombre_cuenta' => obtenerNombreCuenta($cuentaSalida),
                    'descripcion' => $desc,
                    'debe' => 0,
                    'haber' => $saldo,
                    'turno' => null,
                    'usuario' => $usuario,
                    'tipo_transaccion' => 'PAGO_PROVEEDOR',
                    'referencia' => $ref,
                ];
                
                $totalGeneral += $saldo;
            }
        } else {
            // ---- Médico (honorarios) ----
            $st = $connect->prepare(
                "SELECT hm.id, hm.monto_honorario, hm.estado_pago,
                        CONCAT(d.nodoc, ' ', d.apdoc) AS doctor,
                        o.idord, o.invoice_number
                 FROM honorarios_medicos hm
                 INNER JOIN doctor d ON hm.id_doctor = d.idodc
                 INNER JOIN orders o ON hm.id_factura = o.idord
                 WHERE hm.id = ? LIMIT 1"
            );
            $up = $connect->prepare(
                "UPDATE honorarios_medicos
                 SET estado_pago = 'pagado', fecha_pago = NOW(), updated_by = ?
                 WHERE id = ? AND estado_pago <> 'pagado'"
            );
            
            foreach ($ids as $id) {
                $st->execute([$id]);
                $hon = $st->fetch(PDO::FETCH_ASSOC);
                if (!$hon) continue;
                if (strtolower((string) $hon['estado_pago']) === 'pagado') continue;
                
                $monto = round((float) $hon['monto_honorario'], 2);
                if ($monto <= 0.005) continue;
                
                $ref  = 'HON-' . $id;
                $fact = trim((string) ($hon['invoice_number'] ?? ''));
                $desc = 'Pago honorario médico ' . trim((string) $hon['doctor'])
                    . ' | Orden ' . (string) $hon['idord']
                    . ($fact !== '' ? ' Fact ' . $fact : '');
                    
                $trans[] = [
                    'unidad_servicio' => 'Hospital Medicasa',
                    'cuenta' => '210200108',
                    'nombre_cuenta' => obtenerNombreCuenta('210200108'),
                    'descripcion' => $desc,
                    'debe' => $monto,
                    'haber' => 0,
                    'turno' => null,
                    'usuario' => $usuario,
                    'tipo_transaccion' => 'PAGO_HONORARIO_MEDICO',
                    'referencia' => $ref,
                ];
                
                $trans[] = [
                    'unidad_servicio' => 'Hospital Medicasa',
                    'cuenta' => $cuentaSalida,
                    'nombre_cuenta' => obtenerNombreCuenta($cuentaSalida),
                    'descripcion' => $desc,
                    'debe' => 0,
                    'haber' => $monto,
                    'turno' => null,
                    'usuario' => $usuario,
                    'tipo_transaccion' => 'PAGO_HONORARIO_MEDICO',
                    'referencia' => $ref,
                ];
                
                $up->execute([$usuario, $id]);
                $totalGeneral += $monto;
            }
        }
        
        if ($totalGeneral <= 0) {
            if ($connect->inTransaction()) {
                $connect->rollBack();
            }
            pago_json(['success' => false, 'message' => 'No hay saldo pendiente válido para las facturas seleccionadas o ya fueron pagadas.'], 409);
        }
        
        $numero = registrarPartidaCompleta($trans, $fechaOcc);
        $connect->commit();

        pago_json([
            'success' => true,
            'message' => 'Pago múltiple registrado correctamente. Partida ' . $numero,
            'numero_partida' => $numero,
            'monto' => $totalGeneral,
        ]);
    } finally {
        @$connect->query("SELECT RELEASE_LOCK('" . $lockName . "')");
    }
} catch (Throwable $e) {
    error_log('pagar_cuenta_por_pagar: ' . $e->getMessage());
    pago_json(['success' => false, 'message' => 'Error al registrar el pago. Si persiste, contacte a Soporte TI.'], 500);
}
