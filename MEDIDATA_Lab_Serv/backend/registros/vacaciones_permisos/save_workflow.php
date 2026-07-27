<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/audit_lib.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    if (!isset($connect) || !$connect) {
        throw new Exception("Error de conexión a la base de datos.");
    }

    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    // Departamentos que usarán este flujo (el flujo se determina por el departamento
    // del solicitante; un departamento solo puede tener UN flujo asignado).
    $department_ids = $_POST['department_ids'] ?? [];
    if (!is_array($department_ids)) {
        $department_ids = ($department_ids === '' || $department_ids === null) ? [] : [$department_ids];
    }
    $department_ids = array_values(array_unique(array_filter(array_map('intval', $department_ids), fn($v) => $v > 0)));

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
        exit;
    }

    $actor = (int) ($_SESSION['id'] ?? 0);

    $connect->beginTransaction();

    if ($id) {
        $stmt = $connect->prepare("UPDATE hr_approval_workflows SET name = ?, description = ? WHERE workflow_id = ?");
        $stmt->execute([$name, $description, $id]);
        $workflow_id = (int) $id;
        $accion = 'UPDATE_WORKFLOW';
    } else {
        $stmt = $connect->prepare("INSERT INTO hr_approval_workflows (name, description, status) VALUES (?, ?, 1)");
        $stmt->execute([$name, $description]);
        $workflow_id = (int) $connect->lastInsertId();
        $accion = 'CREATE_WORKFLOW';
    }

    // Un departamento no puede pertenecer a dos flujos: avisar cuáles están ocupados.
    if ($department_ids) {
        $in = implode(',', array_fill(0, count($department_ids), '?'));
        $stmt = $connect->prepare("
            SELECT department_id FROM hr_workflow_departments
            WHERE department_id IN ($in) AND workflow_id <> ?
        ");
        $stmt->execute(array_merge($department_ids, [$workflow_id]));
        $ocupados = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if ($ocupados) {
            $nombres = [];
            try {
                $inOc = implode(',', array_fill(0, count($ocupados), '?'));
                $stmtN = $connect->prepare("SELECT name FROM medic9ue_medi_rrhh_interviews.departaments WHERE id IN ($inOc)");
                $stmtN->execute($ocupados);
                $nombres = $stmtN->fetchAll(PDO::FETCH_COLUMN);
            } catch (Throwable $e) { /* si no se resuelven nombres, se muestran ids */ }
            $lista = $nombres ? implode(', ', $nombres) : implode(', ', $ocupados);
            throw new Exception("Estos departamentos ya tienen otro flujo asignado: " . $lista . ". Quítalos de ese flujo primero.");
        }
    }

    // Reemplazar la asignación de departamentos de este flujo
    $connect->prepare("DELETE FROM hr_workflow_departments WHERE workflow_id = ?")->execute([$workflow_id]);
    if ($department_ids) {
        $stmtIns = $connect->prepare("INSERT INTO hr_workflow_departments (workflow_id, department_id) VALUES (?, ?)");
        foreach ($department_ids as $dep) {
            $stmtIns->execute([$workflow_id, $dep]);
        }
    }

    $connect->commit();

    medidata_audit_log($connect, $actor, $accion, 'hr_approval_workflows', $workflow_id, null, [
        'name'           => $name,
        'description'    => $description,
        'department_ids' => $department_ids,
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if (isset($connect) && $connect instanceof PDO && $connect->inTransaction()) {
        $connect->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
