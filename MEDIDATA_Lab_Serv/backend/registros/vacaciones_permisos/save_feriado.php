<?php
/**
 * Alta / edición de un feriado. Fuente única: hr_holiday_calendar
 * (columna `date` es UNIQUE). Al guardarse se refleja automáticamente en los
 * calendarios del sistema que consumen fetch_calendario.php.
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/audit_lib.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

try {
    global $connect;
    if (!$connect) throw new Exception("No db connection");
    $pdo = $connect;

    $holiday_id  = $_POST['holiday_id'] ?? '';
    $date        = trim($_POST['date'] ?? '');
    $end_date    = trim($_POST['end_date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($date === '' || $description === '') {
        echo json_encode(['success' => false, 'message' => 'La fecha y la descripción son obligatorias.']);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        echo json_encode(['success' => false, 'message' => 'Fecha inválida.']);
        exit;
    }
    // Fecha de fin opcional: si viene vacía, es un feriado de un solo día.
    if ($end_date === '') {
        $end_date = $date;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) || !strtotime($end_date)) {
        echo json_encode(['success' => false, 'message' => 'Fecha de fin inválida.']);
        exit;
    }
    if ($end_date < $date) {
        echo json_encode(['success' => false, 'message' => 'La fecha de fin no puede ser anterior a la fecha de inicio.']);
        exit;
    }
    if (mb_strlen($description) > 255) {
        $description = mb_substr($description, 0, 255);
    }

    if (!empty($holiday_id)) {
        // Edición: cada fila es un único día. Se edita ese día puntual.
        $chk = $pdo->prepare("SELECT holiday_id FROM hr_holiday_calendar WHERE `date` = ?");
        $chk->execute([$date]);
        $existente = $chk->fetch(PDO::FETCH_ASSOC);
        if ($existente && (int) $existente['holiday_id'] !== (int) $holiday_id) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otro feriado registrado en esa fecha.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE hr_holiday_calendar SET `date` = ?, description = ? WHERE holiday_id = ?");
        $stmt->execute([$date, $description, $holiday_id]);
        medidata_audit_log($pdo, (int) ($_SESSION['id'] ?? 0), 'UPDATE_HOLIDAY', 'hr_holiday_calendar', $holiday_id, null, ['date' => $date, 'description' => $description]);
        echo json_encode(['success' => true, 'message' => 'Feriado actualizado correctamente.']);
        exit;
    }

    // Alta: puede ser un solo día o un rango. Un rango se guarda como una fila por día.
    $inicio = new DateTime($date);
    $fin    = new DateTime($end_date);
    $totalDias = (int) $inicio->diff($fin)->days + 1;

    if ($totalDias > 366) {
        echo json_encode(['success' => false, 'message' => 'El rango de feriados no puede exceder un año (366 días).']);
        exit;
    }

    // Insertar sólo los días que aún no existan, sin sobrescribir descripciones previas.
    $ins  = $pdo->prepare("INSERT INTO hr_holiday_calendar (`date`, description) VALUES (?, ?)");
    $chkD = $pdo->prepare("SELECT 1 FROM hr_holiday_calendar WHERE `date` = ?");

    $agregados = 0;
    $omitidos  = 0;
    $periodo   = new DatePeriod($inicio, new DateInterval('P1D'), (clone $fin)->modify('+1 day'));
    foreach ($periodo as $d) {
        $ymd = $d->format('Y-m-d');
        $chkD->execute([$ymd]);
        if ($chkD->fetchColumn()) {
            $omitidos++;
            continue;
        }
        $ins->execute([$ymd, $description]);
        $agregados++;
    }

    if ($agregados === 0) {
        echo json_encode(['success' => false, 'message' => 'Las fechas seleccionadas ya estaban registradas como feriado.']);
        exit;
    }

    $msg = $totalDias > 1
        ? "Feriado agregado para $agregados día(s)."
        : 'Feriado agregado correctamente.';
    if ($omitidos > 0) {
        $msg .= " ($omitidos ya existían y se conservaron).";
    }
    medidata_audit_log($pdo, (int) ($_SESSION['id'] ?? 0), 'CREATE_HOLIDAY', 'hr_holiday_calendar', null, null, ['date' => $date, 'end_date' => $end_date, 'description' => $description, 'dias_agregados' => $agregados]);
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
