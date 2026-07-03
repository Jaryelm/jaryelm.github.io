<?php
/**
 * Pre-clínica: marcar revisión (reviews_by) de un registro de signos vitales.
 */
declare(strict_types=1);

session_start();

date_default_timezone_set('America/Tegucigalpa');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/bd/Conexion.php';

try {
    if (!isset($_SESSION['id'])) {
        echo json_encode(['error' => 'Sesión no válida. Reinicie sesión e intente de nuevo.']);
        exit;
    }

    $userIdSession = (int) $_SESSION['id'];
    $idRegistro = $_POST['id'] ?? '';
    $tipo_paciente = $_POST['tipo'] ?? 'paciente';

    if ($idRegistro === '' || $idRegistro === '0') {
        throw new Exception('El ID del registro es obligatorio.');
    }

    $stmtName = $connect->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
    $stmtName->execute([$userIdSession]);
    $nombreRevisor = trim((string) $stmtName->fetchColumn());

    if ($nombreRevisor === '') {
        throw new Exception('No se pudo identificar al revisor.');
    }

    if ($tipo_paciente === 'paciente') {
        $sql = 'UPDATE signos_vitales SET
                reviews_by = :revisor,
                reviewed_by_user_id = :revisor_uid,
                reviewed_at = NOW()
                WHERE id = :id';
    } else {
        $sql = 'UPDATE signos_vitales_outpatients SET
                reviews_by = :revisor,
                reviewed_by_user_id = :revisor_uid,
                reviewed_at = NOW()
                WHERE id = :id';
    }

    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':revisor' => $nombreRevisor,
        ':revisor_uid' => $userIdSession,
        ':id' => $idRegistro,
    ]);

    echo json_encode([
        'success' => 'Signos vitales aprobados correctamente.',
    ]);
} catch (PDOException $e) {
    error_log('pre_clinica_approve_vitals PDO: ' . $e->getMessage());
    $msg = $e->getMessage();
    if (strpos($msg, 'reviewed_by_user_id') !== false || strpos($msg, 'Unknown column') !== false) {
        echo json_encode([
            'error' => 'La base de datos no tiene las columnas de aprobación (reviewed_by_user_id / reviewed_at). Aplique la migración de firmas del módulo de signos vitales.',
        ]);
    } else {
        echo json_encode(['error' => $msg]);
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
