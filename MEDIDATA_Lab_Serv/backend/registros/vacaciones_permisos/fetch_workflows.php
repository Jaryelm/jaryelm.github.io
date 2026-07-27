<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($connect) || !$connect) {
        throw new Exception("Error de conexión a la base de datos.");
    }

    $stmt = $connect->query("SELECT workflow_id, name, description, status FROM hr_approval_workflows ORDER BY workflow_id DESC");
    $data = $stmt->fetchAll();

    // Departamentos asignados a cada flujo (nombres resueltos desde el catálogo de
    // departamentos; degrada con gracia si la migración 20260726-02 no se ha aplicado).
    $deptsByWorkflow = [];
    try {
        $depNames = [];
        foreach ($connect->query("SELECT id, name FROM medic9ue_medi_rrhh_interviews.departaments")->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $depNames[(int) $d['id']] = (string) $d['name'];
        }
        foreach ($connect->query("SELECT workflow_id, department_id FROM hr_workflow_departments")->fetchAll(PDO::FETCH_ASSOC) as $wd) {
            $depId = (int) $wd['department_id'];
            $deptsByWorkflow[(int) $wd['workflow_id']][] = [
                'id'   => $depId,
                'name' => $depNames[$depId] ?? ('Departamento ' . $depId),
            ];
        }
    } catch (Throwable $e) { /* sin tabla de asignación aún: columnas vacías */ }

    foreach ($data as &$wf) {
        $deps = $deptsByWorkflow[(int) $wf['workflow_id']] ?? [];
        $wf['departments'] = $deps;
        $wf['departments_label'] = $deps ? implode(', ', array_column($deps, 'name')) : '';
    }
    unset($wf);

    echo json_encode($data);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Internal error: ' . $e->getMessage()]);
}
