<?php
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/staff_colaborador_bootstrap.php';
require_once __DIR__ . '/users_rrhh_extra_lib.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
        exit;
    }

    // Si el archivo supera post_max_size, PHP descarta $_POST y $_FILES.
    if (empty($_POST) && empty($_FILES)
        && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'El archivo es demasiado grande para el servidor.']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $table = trim($_POST['table'] ?? '');

    // Cuentas tipo "Usuario": el contrato se guarda en users_rrhh_extra (BLOB).
    if ($table === 'users') {
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID incorrecto']);
            exit;
        }
        $action = $_POST['action'] ?? 'upload';
        try {
            if ($action === 'delete') {
                medidata_users_rrhh_extra_save_contrato($connect, $id, null);
                echo json_encode(['status' => 'success', 'message' => 'Contrato eliminado']);
                exit;
            }
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $err = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
                $msg = ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE)
                    ? 'El archivo excede el tamaño máximo permitido.'
                    : 'No se recibió ningún archivo válido.';
                echo json_encode(['status' => 'error', 'message' => $msg]);
                exit;
            }
            $fileContent = file_get_contents($_FILES['file']['tmp_name']);
            $ok = medidata_users_rrhh_extra_save_contrato($connect, $id, $fileContent);
            echo json_encode($ok
                ? ['status' => 'success', 'message' => 'Contrato subido']
                : ['status' => 'error', 'message' => 'Error al guardar el archivo']);
        } catch (Exception $e) {
            error_log('upload_inline_contract.php (users): ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el archivo']);
        }
        exit;
    }

    // El nombre de la columna ID se deriva de la tabla (no se confia en el cliente).
    $tablas_permitidas = [
        'staff_administrative'   => 'idadm',
        'staff_general_services' => 'idsg',
        'nurse'                  => 'idnur',
        'doctor'                 => 'idodc',
    ];

    if (!array_key_exists($table, $tablas_permitidas)) {
        echo json_encode(['status' => 'error', 'message' => 'Tabla no permitida']);
        exit;
    }

    $id_col = $tablas_permitidas[$table];

    $action = $_POST['action'] ?? 'upload';

    if ($action === 'delete') {
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID incorrecto']);
            exit;
        }
        try {
            $sql = "UPDATE {$table} SET url_contrato = NULL WHERE {$id_col} = :id";
            $stmt = $connect->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'Contrato eliminado']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar']);
        }
        exit;
    }

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID incorrecto']);
        exit;
    }

    if (!isset($_FILES['file'])) {
        echo json_encode(['status' => 'error', 'message' => 'No se recibió ningún archivo']);
        exit;
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $msg = ($_FILES['file']['error'] === UPLOAD_ERR_INI_SIZE
                || $_FILES['file']['error'] === UPLOAD_ERR_FORM_SIZE)
            ? 'El archivo excede el tamaño máximo permitido.'
            : 'Archivo inválido o error al subir.';
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit;
    }

    try {
        $fileContent = file_get_contents($_FILES['file']['tmp_name']);
        
        $sql = "UPDATE {$table} SET url_contrato = :file WHERE {$id_col} = :id";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':file', $fileContent, PDO::PARAM_LOB);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode(['status' => 'success', 'message' => 'Contrato subido']);
    } catch (Exception $e) {
        error_log('Error en upload_inline_contract.php: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar el archivo']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
