<?php
header('Content-Type: application/json');

$__o = require __DIR__ . '/../../backend/bd/orthanc_laboratorio.config.php';
$orthanc_url = rtrim($__o['curl_base'], '/') . '/studies';
$username = $__o['curl_user'];
$password = $__o['curl_pass'];

// Iniciar cURL para obtener los estudios
$ch = curl_init($orthanc_url);

// Configurar las opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Devolver la respuesta como string
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password"); // Agregar credenciales
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // Usar autenticación básica
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignorar verificación del certificado (opcional)

// Ejecutar la solicitud
$response = curl_exec($ch);

// Verificar si hubo un error en la solicitud
if (curl_errno($ch)) {
    error_log("cURL error: " . curl_error($ch)); // Registrar el error
    die(json_encode(['error' => 'Error al obtener los estudios desde Orthanc']));
}

// Obtener el código de estado HTTP
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Cerrar cURL
curl_close($ch);

// Verificar el código de estado HTTP
if ($http_status != 200) {
    error_log("HTTP error: $http_status"); // Registrar el código de error
    die(json_encode(['error' => 'Error al obtener los estudios desde Orthanc']));
}

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Calcular el número total de estudios
$totalStudies = is_array($data) ? count($data) : 0;

// Devolver el total de estudios
echo json_encode(['total' => $totalStudies]);
?>