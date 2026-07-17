<?php
/**
 * Mapas de departamentos, niveles salariales y cargos para listas RRHH.
 */
$depto_map = [];
$salary_level_map = [];
$cargo_map = [];

$pdoRrhh = medidata_rrhh_pdo();
if ($pdoRrhh) {
    try {
        $stmt_dept = $pdoRrhh->query("SELECT id, name FROM departaments ORDER BY name ASC");
        while ($row = $stmt_dept->fetch(PDO::FETCH_ASSOC)) {
            $depto_map[$row['id']] = $row['name'];
        }
        $stmt_sl = $pdoRrhh->query("SELECT id, level_name, position_category FROM salary_levels WHERE deleted = 0 ORDER BY level_name ASC");
        while ($row = $stmt_sl->fetch(PDO::FETCH_ASSOC)) {
            $salary_level_map[$row['id']] = $row['level_name'] . ' - ' . $row['position_category'];
        }
    } catch (Exception $e) {}
}

try {
    $stmt_cargo = $connect->query("SELECT id, name FROM positions ORDER BY name ASC");
    while ($row = $stmt_cargo->fetch(PDO::FETCH_ASSOC)) {
        $cargo_map[$row['id']] = $row['name'];
    }
} catch (Exception $e) {}
