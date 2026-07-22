<?php
require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
require_once __DIR__ . '/staff_doc_manage_lib.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $table = isset($_POST['table']) ? trim($_POST['table']) : '';
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if ($id <= 0 || $table === '') {
        throw new Exception('ID o tabla no proporcionados.');
    }
    
    global $connect;
    if (!isset($connect) || !$connect) {
        throw new Exception('Sin conexión a base de datos.');
    }
    
    // Obtener información del colaborador usando la librería existente
    $staff = medidata_staff_doc_fetch_staff_row($connect, $table, $id);
    if (!$staff) {
        throw new Exception('Colaborador no encontrado.');
    }

    $numide = trim((string) ($staff['numide'] ?? ''));
    if ($numide === '') {
        throw new Exception('El colaborador no tiene Número de Identidad asignado.');
    }

    $pdoRrhh = medidata_rrhh_pdo();
    if (!$pdoRrhh) {
        throw new Exception('Servicio RRHH no disponible.');
    }

    // Buscar si ya existe el candidato
    $stmt = $pdoRrhh->prepare('SELECT id, email, fullname FROM candidates WHERE dni = ? LIMIT 1');
    $stmt->execute([$numide]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidate) {
        // Usar los datos de $staff que ya están cargados para evitar problemas con nombres de columnas
        $nom = trim((string)($staff['nomadm'] ?? $staff['nomsg'] ?? $staff['nommf'] ?? $staff['nomodc'] ?? $staff['nomdoc'] ?? $staff['nomnur'] ?? $staff['nombres'] ?? $staff['name'] ?? ''));
        $ape = trim((string)($staff['apeadm'] ?? $staff['apesg'] ?? $staff['apemf'] ?? $staff['apeodc'] ?? $staff['apedoc'] ?? $staff['apenur'] ?? $staff['apellidos'] ?? $staff['lastname'] ?? ''));
        
        $fullname = trim($nom . ' ' . $ape);
        if ($fullname === '') {
            $fullname = 'Colaborador ' . $numide;
        }
        
        $email = trim((string) ($staff['correo_personal'] ?? $staff['email'] ?? $staff['correo'] ?? ''));
        
        $pdoRrhh->prepare("INSERT INTO candidates (dni, fullname, email, status, created_by) VALUES (?, ?, ?, 'Contratado', 'sistema_empleados')")
                ->execute([$numide, $fullname, $email]);
        $candidateId = (int) $pdoRrhh->lastInsertId();
    } else {
        $candidateId = (int) $candidate['id'];
        $email = trim((string) ($candidate['email'] ?? ''));
        $fullname = trim((string) ($candidate['fullname'] ?? ''));
        
        if ($email === '') {
            $correoPersonal = trim((string) ($staff['correo_personal'] ?? $staff['email'] ?? $staff['correo'] ?? ''));
            if ($correoPersonal !== '') {
                $pdoRrhh->prepare("UPDATE candidates SET email = ? WHERE id = ?")->execute([$correoPersonal, $candidateId]);
                $email = $correoPersonal;
            }
        }
    }

    require_once __DIR__ . '/rrhh_employee_form_lib.php';
    $issue = medidata_rrhh_employee_form_issue_link($candidateId, 'sistema_empleados', false);
    if (!$issue['success']) {
        throw new Exception($issue['message']);
    }
    
    $url = $issue['url'];

    if ($action === 'send_email') {
        if (empty($email)) {
            throw new Exception('No hay correo electrónico registrado para enviar el enlace.');
        }

        require_once __DIR__ . '/../registros/rrhh_aplica_bridge.php';

        $mensaje = "<p>Estimado/a {$fullname},</p>
                    <p>Haga clic en el siguiente enlace para llenar su <strong>Formulario de Empleado</strong>:</p>
                    <p><a href=\"{$url}\">Completar Formulario</a></p>
                    <p>Enlace directo: <br>{$url}</p>";

        $enviado = medidata_rrhh_send_notification_email($email, "Formulario de Empleado - MEDICASA", $mensaje);
        
        if (!$enviado) {
            throw new Exception('El correo no pudo ser enviado (Error de servidor de correo).');
        }

        echo json_encode(['success' => true, 'message' => 'Enlace enviado al correo: ' . $email]);
        exit;
    }

    // Acción por defecto: devolver el enlace y los datos
    echo json_encode([
        'success' => true,
        'url' => $url,
        'email' => $email,
        'candidate_id' => $candidateId
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
