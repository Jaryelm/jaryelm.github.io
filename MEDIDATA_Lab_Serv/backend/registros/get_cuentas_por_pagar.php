<?php
declare(strict_types=1);

require_once __DIR__ . '/../bd/Conexion.php';
include_once __DIR__ . '/session_check.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function cuentas_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!isset($connect) || !($connect instanceof PDO)) {
    cuentas_json(['data' => [], 'error' => 'No hay conexión a la base de datos.'], 500);
}

$tipo = $_POST['tipo'] ?? 'comercial';
$fecha_inicio = $_POST['fechaDesde'] ?? $_POST['fecha_inicio'] ?? '2020-01-01';
$fecha_fin = $_POST['fechaHasta'] ?? $_POST['fecha_fin'] ?? date('Y-m-t');
$proveedor_filtro = $_POST['proveedor'] ?? '';
$accion = $_POST['accion'] ?? '';

if (!$connect) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($accion === 'ver_partidas') {
    $id_ref = $_POST['id_referencia'] ?? '';
    $detalles = [];
    
    if ($tipo === 'comercial') {
        $stmt = $connect->prepare("CALL sp_rep_pagos_factura_comercial(?, ?)");
        $stmt->execute(['2000-01-01', '2099-12-31']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            if ($r['id_compra'] == $id_ref) {
                $detalles[] = [
                    htmlspecialchars((string)$r['PartidaPago'], ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)$r['FechaPago'], ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)$r['DescripcionPago'], ENT_QUOTES, 'UTF-8'),
                    'L. ' . number_format((float)$r['MontoAbonado'], 2)
                ];
            }
        }
    } else {
        $stmt = $connect->prepare("CALL sp_rep_pagos_honorario_medico(?, ?)");
        $stmt->execute(['2000-01-01', '2099-12-31']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            if ($r['IdHonorario'] == $id_ref) {
                $detalles[] = [
                    htmlspecialchars((string)$r['NumeroOrden'], ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)$r['FechaEfectivaPago'], ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)$r['PagadoPorUsuario'], ENT_QUOTES, 'UTF-8'),
                    'L. ' . number_format((float)$r['Total_Honorario'], 2)
                ];
            }
        }
    }
    
    echo json_encode(['data' => $detalles]);
    exit;
}

try {
    $data = [];
    if ($tipo === 'comercial') {
        $stmt = $connect->prepare("CALL sp_rep_compras_encabezado(?, ?)");
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = [];
        $detalles = [];
        foreach ($rows as $r) {
            $prov = $r['Proveedor'] ?? 'Desconocido';
            if (!trim((string)$prov)) $prov = 'Desconocido';
            
            if ($proveedor_filtro && $proveedor_filtro !== $prov) {
                continue;
            }
            
            if ($proveedor_filtro) {
                $id = $r['id_compra'] ?? '';
                $valorFactura = (float)($r['ValorFactura'] ?? 0);
                $totalSaldado = (float)($r['TotalSaldado'] ?? 0);
                $saldoNeto = $valorFactura - $totalSaldado;
                
                $btnPagar = ($saldoNeto > 0.005)
                    ? '<button type="button" class="btn_ver_detalles btn_pagar" data-id="' . htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') . '" data-modo="comercial" data-saldo="' . $saldoNeto . '">Pagar</button>'
                    : '';
                $btnPartidas = '<button type="button" class="btn_ver_detalles btn_ver_partidas" data-id="' . htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') . '" data-modo="comercial">Ver Partidas</button>';
                
                $detalles[] = [
                    htmlspecialchars((string)($r['Fecha'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($r['NumeroFactura'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($r['Fecha_Vencimiento'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'L. ' . number_format($valorFactura, 2),
                    'L. ' . number_format($totalSaldado, 2),
                    'L. ' . number_format($saldoNeto, 2),
                    htmlspecialchars((string)($r['Estado'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    '<div class="acciones-wrap">' . $btnPagar . ' ' . $btnPartidas . '</div>'
                ];
            } else {
                if (!isset($summary[$prov])) {
                    $summary[$prov] = ['saldado' => 0, 'debe' => 0];
                }
                $summary[$prov]['debe'] += (float)($r['ValorFactura'] ?? 0);
                $summary[$prov]['saldado'] += (float)($r['TotalSaldado'] ?? 0);
            }
        }
        
        if ($proveedor_filtro) {
            echo json_encode(['data' => $detalles]);
            exit;
        }
        
        foreach ($summary as $prov => $tot) {
            $saldoNeto = $tot['debe'] - $tot['saldado'];
            $estado = (round($saldoNeto, 2) <= 0) ? 'Balanceado' : 'Pendiente';
            
            $btn = '<div class="acciones-wrap"><button type="button" class="btn_ver_detalles" data-modo="comercial" data-prov="' . htmlspecialchars((string)$prov, ENT_QUOTES, 'UTF-8') . '">Ver facturas</button></div>';
            
            $data[] = [
                htmlspecialchars((string)$prov, ENT_QUOTES, 'UTF-8'),
                'L. ' . number_format($tot['saldado'], 2),
                'L. ' . number_format($tot['debe'], 2),
                'L. ' . number_format($saldoNeto, 2),
                $estado,
                $btn,
                $tot['saldado'],
                $tot['debe'],
                $saldoNeto
            ];
        }
    } else {
        $stmt = $connect->prepare("CALL sp_rep_honorarios_encabezado(?, ?)");
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = [];
        $detalles = [];
        foreach ($rows as $r) {
            $prov = $r['Proveedor'] ?? 'Desconocido';
            if (!trim((string)$prov)) $prov = 'Desconocido';
            
            if ($proveedor_filtro && $proveedor_filtro !== $prov) {
                continue;
            }
            
            if ($proveedor_filtro) {
                $id = $r['IdHonorario'] ?? '';
                $valorFactura = (float)($r['ValorFactura'] ?? 0);
                $totalSaldado = (float)($r['TotalSaldado'] ?? 0);
                $saldoNeto = $valorFactura - $totalSaldado;
                
                $btnPagar = ($saldoNeto > 0.005)
                    ? '<button type="button" class="btn_ver_detalles btn_pagar" data-id="' . htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') . '" data-modo="medico" data-saldo="' . $saldoNeto . '">Pagar</button>'
                    : '';
                $btnPartidas = '<button type="button" class="btn_ver_detalles btn_ver_partidas" data-id="' . htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') . '" data-modo="medico">Historial Pagos</button>';
                
                $detalles[] = [
                    htmlspecialchars((string)($r['Fecha'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($r['NumeroFactura'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($r['NombrePaciente'] ?? '') . ' - ' . (string)($r['Estudio'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'L. ' . number_format($valorFactura, 2),
                    'L. ' . number_format($totalSaldado, 2),
                    'L. ' . number_format($saldoNeto, 2),
                    htmlspecialchars((string)($r['Estado'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    '<div class="acciones-wrap">' . $btnPagar . ' ' . $btnPartidas . '</div>'
                ];
            } else {
                if (!isset($summary[$prov])) {
                    $summary[$prov] = ['saldado' => 0, 'debe' => 0];
                }
                $summary[$prov]['debe'] += (float)($r['ValorFactura'] ?? 0);
                $summary[$prov]['saldado'] += (float)($r['TotalSaldado'] ?? 0);
            }
        }
        
        if ($proveedor_filtro) {
            echo json_encode(['data' => $detalles]);
            exit;
        }
        
        foreach ($summary as $prov => $tot) {
            $saldoNeto = $tot['debe'] - $tot['saldado'];
            $estado = (round($saldoNeto, 2) <= 0) ? 'Balanceado' : 'Pendiente';
            
            $btn = '<div class="acciones-wrap"><button type="button" class="btn_ver_detalles" data-modo="medico" data-prov="' . htmlspecialchars((string)$prov, ENT_QUOTES, 'UTF-8') . '">Ver facturas</button></div>';
            
            $data[] = [
                htmlspecialchars((string)$prov, ENT_QUOTES, 'UTF-8'),
                'L. ' . number_format($tot['saldado'], 2),
                'L. ' . number_format($tot['debe'], 2),
                'L. ' . number_format($saldoNeto, 2),
                $estado,
                $btn,
                $tot['saldado'],
                $tot['debe'],
                $saldoNeto
            ];
        }
    }
    
    cuentas_json(['data' => $data]);
} catch (Throwable $e) {
    cuentas_json(['data' => [], 'error' => $e->getMessage()], 500);
}
