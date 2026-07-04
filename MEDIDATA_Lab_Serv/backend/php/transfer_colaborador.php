<?php
/**
 * Endpoint para trasladar un colaborador de una tabla a otra
 * Sistema: MEDIDATA - RRHH
 */

require_once __DIR__ . '/../bd/Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $id = intval($_POST['id'] ?? 0);
    $source_table = trim((string) ($_POST['source_table'] ?? ''));
    $target_table = trim((string) ($_POST['target_table'] ?? ''));
    $new_state = isset($_POST['new_state']) ? intval($_POST['new_state']) : null;

    if ($id <= 0 || !$source_table || !$target_table) {
        throw new Exception("Faltan parámetros requeridos para el traslado.");
    }

    $valid_tables = [
        'staff_administrative' => [
            'idcol' => 'idadm', 'ident' => 'numide', 'nom' => 'nomadm', 'ape' => 'apeadm', 'sex' => 'sexadm', 'nac' => 'nacadm'
        ],
        'doctor' => [
            'idcol' => 'idodc', 'ident' => 'ceddoc', 'nom' => 'nodoc', 'ape' => 'apdoc', 'sex' => 'sexd', 'nac' => 'nacd'
        ],
        'nurse' => [
            'idcol' => 'idnur', 'ident' => 'numide', 'nom' => 'nomnur', 'ape' => 'apenur', 'sex' => 'sexnur', 'nac' => 'nacinur'
        ],
        'staff_general_services' => [
            'idcol' => 'idsg', 'ident' => 'numide', 'nom' => 'nomsg', 'ape' => 'apesg', 'sex' => 'sexsg', 'nac' => 'nacsg'
        ],
        'staff_medifarma' => [
            'idcol' => 'idmf', 'ident' => 'numide', 'nom' => 'nommf', 'ape' => 'apemf', 'sex' => 'sexmf', 'nac' => 'nacmf'
        ]
    ];

    if (!isset($valid_tables[$source_table]) || !isset($valid_tables[$target_table])) {
        throw new Exception("Tabla origen o destino inválida.");
    }

    $sourceMeta = $valid_tables[$source_table];
    $targetMeta = $valid_tables[$target_table];

    // 1. Iniciar transacción
    $connect->beginTransaction();

    // 2. Si son la misma tabla, solo actualizar el estado (si se envió)
    if ($source_table === $target_table) {
        if ($new_state !== null) {
            $stmt = $connect->prepare("UPDATE `$source_table` SET state = :st WHERE `" . $sourceMeta['idcol'] . "` = :id");
            $stmt->execute([':st' => $new_state, ':id' => $id]);
        }
        $connect->commit();
        echo json_encode(['success' => true, 'message' => 'Estado actualizado exitosamente.']);
        exit;
    }

    // 3. Obtener el registro origen
    $stmtSource = $connect->prepare("SELECT * FROM `$source_table` WHERE `" . $sourceMeta['idcol'] . "` = :id LIMIT 1");
    $stmtSource->execute([':id' => $id]);
    $row = $stmtSource->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception("Registro original no encontrado.");
    }

    // 3.5 Check if record already exists in target table
    $stmtCheck = $connect->prepare("SELECT COUNT(*) FROM `$target_table` WHERE `" . $targetMeta['ident'] . "` = :ident");
    $stmtCheck->execute([':ident' => $row[$sourceMeta['ident']]]);
    if ((int)$stmtCheck->fetchColumn() > 0) {
        throw new Exception("Ya existe un registro con este DNI/Identificación en el área de destino. Puede que esté como excolaborador, verifique y elimínelo antes de trasladar.");
    }

    // 4. Preparar inserción en destino
    $commonFields = [
        'num_empleado', 'tipo_empleado', 'id_departamento', 'id_cargo', 'id_salary_level', 
        'salario', 'cuenta_bac', 'fecha_ingreso', 'telefono', 'correo_personal', 
        'correo_institucional', 'id_biometrico', 'num_locker', 'url_contrato', 'state'
    ];

    $insertFields = [];
    $insertPlaceholders = [];
    $insertParams = [];

    // Mapear campos específicos
    $insertFields[] = '`' . $targetMeta['ident'] . '`';
    $insertPlaceholders[] = ':ident';
    $insertParams[':ident'] = $row[$sourceMeta['ident']];

    $insertFields[] = '`' . $targetMeta['nom'] . '`';
    $insertPlaceholders[] = ':nom';
    $insertParams[':nom'] = $row[$sourceMeta['nom']];

    $insertFields[] = '`' . $targetMeta['ape'] . '`';
    $insertPlaceholders[] = ':ape';
    $insertParams[':ape'] = $row[$sourceMeta['ape']];

    $insertFields[] = '`' . $targetMeta['sex'] . '`';
    $insertPlaceholders[] = ':sex';
    $insertParams[':sex'] = $row[$sourceMeta['sex']];

    $insertFields[] = '`' . $targetMeta['nac'] . '`';
    $insertPlaceholders[] = ':nac';
    $insertParams[':nac'] = $row[$sourceMeta['nac']];

    // Mapear campos comunes
    foreach ($commonFields as $field) {
        if (array_key_exists($field, $row)) {
            $insertFields[] = "`$field`";
            $insertPlaceholders[] = ":$field";
            
            // Si hay un nuevo estado, aplicarlo
            if ($field === 'state' && $new_state !== null) {
                $insertParams[":$field"] = $new_state;
            } else {
                $insertParams[":$field"] = $row[$field];
            }
        }
    }

    // Para médicos, incluir especialidad, dirección, phd y correo (los campos no permiten nulos)
    if ($target_table === 'doctor') {
        $extra_fields = ['`nomesp`', '`direcd`', '`phd`', '`corr`'];
        foreach ($extra_fields as $efield) {
            if (!in_array($efield, $insertFields)) {
                $insertFields[] = $efield;
                $param = ':' . trim($efield, '`');
                $insertPlaceholders[] = $param;
                $insertParams[$param] = '';
            }
        }
    }

    $sqlInsert = "INSERT INTO `$target_table` (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
    $stmtInsert = $connect->prepare($sqlInsert);
    $stmtInsert->execute($insertParams);

    // 5. Eliminar el registro origen
    $sqlDelete = "DELETE FROM `$source_table` WHERE `" . $sourceMeta['idcol'] . "` = :id";
    $stmtDelete = $connect->prepare($sqlDelete);
    $stmtDelete->execute([':id' => $id]);

    $connect->commit();
    echo json_encode(['success' => true, 'message' => 'Traslado realizado exitosamente.']);

} catch (Exception $e) {
    if (isset($connect) && $connect->inTransaction()) {
        $connect->rollBack();
    }
    error_log('Error en transfer_colaborador.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
