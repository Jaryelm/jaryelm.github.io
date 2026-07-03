<?php 
require_once('../../backend/bd/Conexion.php');

// Configura la zona horaria local
date_default_timezone_set('America/Tegucigalpa'); // Cambia esto a tu zona horaria local

// Configuración de logs
ini_set('error_log', __DIR__ . '/error_log.log');

if (isset($_POST['add_appointment'])) {
    error_log("Datos POST recibidos: " . json_encode($_POST));

    $title = trim($_POST['appnam']);
    $idpa = trim($_POST['apppac']);
    $idodc = trim($_POST['appdoc']);
    $idlab = trim($_POST['applab']);
    $color = trim($_POST['appco']);
    $start_date = $_POST['appini']; // Fecha inicial
    $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : "00:00"; // Hora de inicio por defecto
    $end_date = $_POST['appfin'];   // Fecha final
    $monto = $_POST['appmont'];
    $chec = $_POST['appreal'];
    $duration = !empty($_POST['duration']) ? floatval($_POST['duration']) : null;

    // Nuevos campos
    $room_number = !empty(trim($_POST['room_number'])) ? trim($_POST['room_number']) : "N/A";
    $insurer = !empty(trim($_POST['insurer'])) ? trim($_POST['insurer']) : "N/A";
    $policy_number = !empty(trim($_POST['policy_number'])) ? trim($_POST['policy_number']) : "N/A";
    $certificate_number = !empty(trim($_POST['certificate_number'])) ? trim($_POST['certificate_number']) : "N/A";

    // Nuevos campos
    $surgery = !empty(trim($_POST['surgery'])) ? trim($_POST['surgery']) : "N/A";
    $hospitalization = !empty(trim($_POST['hospitalization'])) ? trim($_POST['hospitalization']) : "N/A";
    $assistant = !empty(trim($_POST['assistant'])) ? trim($_POST['assistant']) : "N/A";
    $anesthetist = !empty(trim($_POST['anesthetist'])) ? trim($_POST['anesthetist']) : "N/A";
    $circulating = !empty(trim($_POST['circulating'])) ? trim($_POST['circulating']) : "N/A";
    $technician = !empty(trim($_POST['technician'])) ? trim($_POST['technician']) : "N/A";
    $instrumentist = !empty(trim($_POST['instrumentist'])) ? trim($_POST['instrumentist']) : "N/A";
    $evaluation = !empty(trim($_POST['evaluation'])) ? trim($_POST['evaluation']) : "N/A";    

    // Validar el formato de la hora
    $timeObject = DateTime::createFromFormat('H:i', $start_time);
    if (!$timeObject) {
        error_log("Error: Hora de inicio no válida. Hora=$start_time");
        echo '<script type="text/javascript">
        swal("Error!", "La hora de inicio no es válida.", "error").then(function() {
            window.location = "../../frontend/citas/nueva.php";
        });
        </script>';
        exit();
    }

    // Combinar fecha y hora
    $start = $start_date . " " . $start_time . ":00"; // Aseguramos segundos
    $startDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $start);

    if (!$startDateTime) {
        error_log("Error: Fecha y hora de inicio no válidas. Start=$start");
        echo '<script type="text/javascript">
        swal("Error!", "La fecha y hora de inicio no son válidas.", "error").then(function() {
            window.location = "../../frontend/citas/nueva.php";
        });
        </script>';
        exit();
    }

    // Determinar hora de finalización
    if ($duration) {
        $endDateTime = clone $startDateTime;
        $endDateTime->modify("+{$duration} hours");
        $end = $endDateTime->format('Y-m-d H:i:s');
    } else {
        // Evento de 24 horas si no se especifica duración
        $start = $startDateTime->format('Y-m-d 00:00:00');
        $end = (new DateTime($end_date))->format('Y-m-d 23:59:59');
    }

    // Validación para asegurar que la fecha final sea mayor o igual que la inicial
    if (strtotime($start) > strtotime($end)) {
        error_log("Error: Fecha final menor que la inicial. Start=$start, End=$end");
        echo '<script type="text/javascript">
        swal("Error!", "La fecha final debe ser igual o posterior a la inicial.", "error").then(function() {
            window.location = "../../frontend/citas/nueva.php";
        });
        </script>';
        exit();
    }

    // Validación para verificar la clave foránea de `idlab`
    $query = $connect->prepare("SELECT COUNT(*) FROM laboratory WHERE idlab = :idlab");
    $query->bindParam(':idlab', $idlab);
    $query->execute();
    $exists = $query->fetchColumn();
    if (!$exists) {
        error_log("Error: idlab ($idlab) no existe en la tabla laboratory.");
        echo '<script type="text/javascript">
        swal("Error!", "El área de servicio seleccionada no es válida.", "error").then(function() {
            window.location = "../../frontend/citas/nueva.php";
        });
        </script>';
        exit();
    }

// Hora local actual
$current_time = date('Y-m-d H:i:s');

$sql = "INSERT INTO events(
    title, idpa, idodc, idlab, color, start, end, state, monto, chec, duration, 
    room_number, insurer, policy_number, certificate_number, 
    surgery, hospitalization, assistant, anesthetist, circulating, technician, instrumentist, evaluation, fere
) 
VALUES(
    :title, :idpa, :idodc, :idlab, :color, :start, :end, 1, :monto, :chec, :duration, 
    :room_number, :insurer, :policy_number, :certificate_number, 
    :surgery, :hospitalization, :assistant, :anesthetist, :circulating, :technician, :instrumentist, :evaluation, :fere
)";
    
    $sql = $connect->prepare($sql);
    
    $sql->bindParam(':title', $title);
    $sql->bindParam(':idpa', $idpa);
    $sql->bindParam(':idodc', $idodc);
    $sql->bindParam(':idlab', $idlab);
    $sql->bindParam(':color', $color);
    $sql->bindParam(':start', $start);
    $sql->bindParam(':end', $end);
    $sql->bindParam(':monto', $monto);
    $sql->bindParam(':chec', $chec);
    $sql->bindParam(':duration', $duration);
    $sql->bindParam(':room_number', $room_number);
    $sql->bindParam(':insurer', $insurer);
    $sql->bindParam(':policy_number', $policy_number);
    $sql->bindParam(':certificate_number', $certificate_number);
    $sql->bindParam(':surgery', $surgery);
    $sql->bindParam(':hospitalization', $hospitalization);
    $sql->bindParam(':assistant', $assistant);
    $sql->bindParam(':anesthetist', $anesthetist);
    $sql->bindParam(':circulating', $circulating);
    $sql->bindParam(':technician', $technician);
    $sql->bindParam(':instrumentist', $instrumentist);
    $sql->bindParam(':evaluation', $evaluation);
    $sql->bindParam(':fere', $current_time); // Fecha local

    if ($sql->execute()) {
        $lastInsertId = $connect->lastInsertId();
        if ($lastInsertId > 0) {
            error_log("Registro insertado correctamente. ID: $lastInsertId");
            echo '<script type="text/javascript">
            swal("¡Registrado!", "Se reservó la cita correctamente", "success").then(function() {
                window.location = "../citas/calendario.php";
            });
            </script>';
        } else {
            error_log("Fallo al obtener el último ID. Verifica la base de datos.");
            echo '<script type="text/javascript">
            swal("Error!", "No se pueden agregar datos, comuníquese con el administrador.", "error").then(function() {
                window.location = "../../frontend/citas/nueva.php";
            });
            </script>';
        }
    } else {
        $errorInfo = $sql->errorInfo();
        error_log("Error en la consulta SQL: " . implode(", ", $errorInfo));
        echo '<script type="text/javascript">
        swal("Error!", "Error en la consulta, comuníquese con el administrador.", "error").then(function() {
            window.location = "../../frontend/citas/nueva.php";
        });
        </script>';
    }
} else {
    error_log("El formulario no fue enviado correctamente.");
}
?>
