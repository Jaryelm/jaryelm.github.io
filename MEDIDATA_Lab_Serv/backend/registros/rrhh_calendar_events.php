<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_guard.php';

$events = medidata_rrhh_fetch_eventos_calendario();

// FullCalendar enviará start y end como parámetros GET si se desea filtrar en backend.
// Por ahora, devolvemos todo el conjunto para mantener simplicidad, 
// pero en formato JSON para que el plugin lo maneje nativamente.

echo json_encode($events);
