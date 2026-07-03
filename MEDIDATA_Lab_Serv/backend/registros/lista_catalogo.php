<?php
header('Content-Type: application/json');

// Incluir la conexión a la base de datos
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

try {
    // Consultar todas las cuentas desde la base de datos, incluyendo el nuevo campo
    $sql = 'SELECT id, tipo_cuenta, cuenta, nombre FROM cuentas_catalogo ORDER BY cuenta' . medidata_tablas_mysql_limit_clause();
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar cuántos registros se han recuperado
    $num_registros = count($cuentas);

    // Devolver las cuentas como un JSON
    echo json_encode([
        'success' => true,
        'num_registros' => $num_registros, // Añadir número de registros en la respuesta
        'cuentas' => $cuentas
    ]);
} catch (PDOException $e) {
    // Devolver un mensaje de error si la consulta falla
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las cuentas: ' . $e->getMessage()
    ]);
}