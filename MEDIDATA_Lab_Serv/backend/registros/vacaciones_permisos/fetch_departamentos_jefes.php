<?php
/**
 * Lista los departamentos con su jefe asignado (id_jefe + nombre). Solo RRHH / Administrador.
 * Degrada con gracia si la columna id_jefe aún no existe (migración 20260724-03 no aplicada).
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['data' => [], 'error' => 'Acceso denegado']);
    exit;
}

try {
    if (!isset($connect) || !$connect) throw new Exception('Sin conexión a BD.');

    // Mapa id_user => nombre (para mostrar el nombre del jefe).
    $nombres = [];
    try {
        foreach ($connect->query("SELECT id, name, username FROM users")->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $nom = trim((string) ($u['name'] ?? ''));
            if ($nom === '') $nom = trim((string) ($u['username'] ?? ''));
            $nombres[(int) $u['id']] = $nom;
        }
    } catch (Throwable $e) { /* si falla, se muestra el id */ }

    // Departamentos con id_jefe (si la columna no existe, se consulta sin ella).
    $rows = [];
    try {
        $rows = $connect->query("SELECT id, name, id_jefe FROM medic9ue_medi_rrhh_interviews.departaments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $rows = $connect->query("SELECT id, name FROM medic9ue_medi_rrhh_interviews.departaments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    $data = [];
    foreach ($rows as $r) {
        $idJefe = isset($r['id_jefe']) && $r['id_jefe'] !== null && $r['id_jefe'] !== '' ? (int) $r['id_jefe'] : null;
        $data[] = [
            'id'          => (int) $r['id'],
            'name'        => (string) $r['name'],
            'id_jefe'     => $idJefe,
            'jefe_nombre' => $idJefe !== null ? ($nombres[$idJefe] ?? ('Usuario ' . $idJefe)) : null,
        ];
    }

    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
