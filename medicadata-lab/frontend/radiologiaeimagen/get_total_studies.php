<?php
header('Content-Type: application/json');

// URL base de la API de Orthanc
$orthanc_url = 'https://medicloud.medicasa.hn/orthanc/studies';

// Configurar las credenciales de Orthanc
$username = 'dev'; // Usuario registrado en Orthanc
$password = 'Mrecords7'; // Contraseña del usuario

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

unset($ch);

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