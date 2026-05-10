<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');
require_once '../../backend/bd/Conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['empresa'])) {
    // Sanitización y validación
    $nombre_empresa = strtoupper(trim($_POST['empresa']));
    $direccion = strtoupper(trim($_POST['direccion']));
    $rtn_comercial = strtoupper(trim($_POST['rtn_comercial']));
    $tel_fijo = isset($_POST['tel_fijo']) ? strtoupper(trim($_POST['tel_fijo'])) : null;
    $correo_comercial = strtoupper(trim($_POST['correo_comercial']));
    $cel_whatsapp = isset($_POST['cel_whatsapp']) ? strtoupper(trim($_POST['cel_whatsapp'])) : null;
    $nombre_legal = strtoupper(trim($_POST['nombre_legal']));
    $dni_comercial = strtoupper(trim($_POST['dni_comercial']));
    $cel_comercial = isset($_POST['cel_comercial']) ? strtoupper(trim($_POST['cel_comercial'])) : null;
    $cuenta_bac_comercial = isset($_POST['cuenta_bac_comercial']) ? strtoupper(trim($_POST['cuenta_bac_comercial'])) : null;
    $cuenta_bac_si = isset($_POST['cuenta_bac_si']) ? strtoupper(trim($_POST['cuenta_bac_si'])) : null;
    $cuenta_bac_no = isset($_POST['cuenta_bac_no']) ? strtoupper(trim($_POST['cuenta_bac_no'])) : null;
    $tipo_cuenta_comercial = isset($_POST['tipo_cuenta_comercial']) ? strtoupper(trim($_POST['tipo_cuenta_comercial'])) : null;
    $nom_contacto = isset($_POST['nom_contacto']) ? strtoupper(trim($_POST['nom_contacto'])) : null;
    $fecha_registro = date('Y-m-d H:i:s');

    // Nuevos campos
    $ref1_bac_comercial = isset($_POST['1_refbac_comercial']) ? strtoupper(trim($_POST['1_refbac_comercial'])) : null;
    $ref1_bac_comercial_tel = isset($_POST['1_refbac_comercial_tel']) ? strtoupper(trim($_POST['1_refbac_comercial_tel'])) : null;
    $ref2_bac_comercial = isset($_POST['2_refbac_comercial']) ? strtoupper(trim($_POST['2_refbac_comercial'])) : null;
    $ref2_bac_comercial_tel = isset($_POST['2_refbac_comercial_tel']) ? strtoupper(trim($_POST['2_refbac_comercial_tel'])) : null;
    $ref1_bac_contacto = isset($_POST['1_refbac_contacto']) ? strtoupper(trim($_POST['1_refbac_contacto'])) : null;
    $ref1_bac_contacto_tel = isset($_POST['1_refbac_contacto_tel']) ? strtoupper(trim($_POST['1_refbac_contacto_tel'])) : null;

    // Validar correo electrónico
    if (!filter_var($correo_comercial, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Formato de correo no válido.']);
        exit;
    }

    try {
        // Verificar si ya existe el proveedor
        $sql_check = "SELECT 1 FROM proveedor_comercial WHERE rtn_comercial = :rtn_comercial OR nombre_empresa = :nombre_empresa";
        $stmt_check = $connect->prepare($sql_check);
        $stmt_check->bindParam(':rtn_comercial', $rtn_comercial);
        $stmt_check->bindParam(':nombre_empresa', $nombre_empresa);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'El proveedor comercial ya está registrado.']);
            exit;
        }

        // Manejo del archivo "archivo_constancia_comercial"
        $archivo_constancia_comercial = null;
        if (isset($_FILES['archivo-constancia-comercial']) && $_FILES['archivo-constancia-comercial']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '/home4/medic9ue/medidata.medicasa.hn/uploads/';
            $nombre_empresa_sanitizado = preg_replace('/[^a-zA-Z0-9\s]/', '', $nombre_empresa); 
            $archivo_extension = pathinfo($_FILES['archivo-constancia-comercial']['name'], PATHINFO_EXTENSION);
            $nuevo_nombre_archivo = $nombre_empresa_sanitizado . '.' . $archivo_extension;
            $archivo_ruta = $upload_dir . $nuevo_nombre_archivo;
            if (move_uploaded_file($_FILES['archivo-constancia-comercial']['tmp_name'], $archivo_ruta)) {
                $archivo_constancia_comercial = '/uploads/' . $nuevo_nombre_archivo;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al subir el archivo de constancia.']);
                exit;
            }
        }

        // Manejo del archivo "firma_digital_comercial"
        $firma_digital_comercial = null;
        if (isset($_FILES['firma-digital-comercial']) && $_FILES['firma-digital-comercial']['error'] == UPLOAD_ERR_OK) {
            $firmas_dir = '/home4/medic9ue/medidata.medicasa.hn/uploads/firmas/';
            $nuevo_nombre_firma = $nombre_empresa . '.png'; 
            $firma_ruta = $firmas_dir . $nuevo_nombre_firma;
            if (move_uploaded_file($_FILES['firma-digital-comercial']['tmp_name'], $firma_ruta)) {
                $firma_digital_comercial = '/uploads/firmas/' . $nuevo_nombre_firma;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al subir la firma digital.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se recibió la firma digital.']);
            exit;
        }

        // Inserción de datos en la tabla
        $sql = "INSERT INTO proveedor_comercial (
                    nombre_empresa, direccion, rtn_comercial, tel_fijo, correo_comercial, cel_whatsapp, nombre_legal, dni_comercial, 
                    cel_comercial, cuenta_bac_comercial, cuenta_bac_si, cuenta_bac_no, tipo_cuenta_comercial, archivo_constancia_comercial, 
                    nom_contacto, fecha_registro, firma_digital_comercial, 
                    1_refbac_comercial, 1_refbac_comercial_tel, 2_refbac_comercial, 2_refbac_comercial_tel, 
                    1_refbac_contacto, 1_refbac_contacto_tel) 
                VALUES (
                    :nombre_empresa, :direccion, :rtn_comercial, :tel_fijo, :correo_comercial, :cel_whatsapp, :nombre_legal, :dni_comercial, 
                    :cel_comercial, :cuenta_bac_comercial, :cuenta_bac_si, :cuenta_bac_no, :tipo_cuenta_comercial, :archivo_constancia_comercial, 
                    :nom_contacto, :fecha_registro, :firma_digital_comercial, 
                    :ref1_bac_comercial, :ref1_bac_comercial_tel, :ref2_bac_comercial, :ref2_bac_comercial_tel, 
                    :ref1_bac_contacto, :ref1_bac_contacto_tel)";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':nombre_empresa', $nombre_empresa);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':rtn_comercial', $rtn_comercial);
        $stmt->bindParam(':tel_fijo', $tel_fijo);
        $stmt->bindParam(':correo_comercial', $correo_comercial);
        $stmt->bindParam(':cel_whatsapp', $cel_whatsapp);
        $stmt->bindParam(':nombre_legal', $nombre_legal);
        $stmt->bindParam(':dni_comercial', $dni_comercial);
        $stmt->bindParam(':cel_comercial', $cel_comercial);
        $stmt->bindParam(':cuenta_bac_comercial', $cuenta_bac_comercial);
        $stmt->bindParam(':cuenta_bac_si', $cuenta_bac_si);
        $stmt->bindParam(':cuenta_bac_no', $cuenta_bac_no);
        $stmt->bindParam(':tipo_cuenta_comercial', $tipo_cuenta_comercial);
        $stmt->bindParam(':archivo_constancia_comercial', $archivo_constancia_comercial);
        $stmt->bindParam(':nom_contacto', $nom_contacto);
        $stmt->bindParam(':fecha_registro', $fecha_registro);
        $stmt->bindParam(':firma_digital_comercial', $firma_digital_comercial);
        $stmt->bindParam(':ref1_bac_comercial', $ref1_bac_comercial);
        $stmt->bindParam(':ref1_bac_comercial_tel', $ref1_bac_comercial_tel);
        $stmt->bindParam(':ref2_bac_comercial', $ref2_bac_comercial);
        $stmt->bindParam(':ref2_bac_comercial_tel', $ref2_bac_comercial_tel);
        $stmt->bindParam(':ref1_bac_contacto', $ref1_bac_contacto);
        $stmt->bindParam(':ref1_bac_contacto_tel', $ref1_bac_contacto_tel);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Registro comercial guardado con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ocurrió un error al guardar el registro. Inténtalo de nuevo.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ocurrió un problema con la base de datos: ' . $e->getMessage()]);
    }
}