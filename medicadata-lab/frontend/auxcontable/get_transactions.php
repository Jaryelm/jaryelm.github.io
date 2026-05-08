<?php
session_start();
require_once '../../backend/bd/Conexion.php';
header('Content-Type: application/json');

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    exit;
}

try {
    // Obtener los parámetros enviados desde el frontend
    $option = $_GET['option'] ?? 'todos'; // Filtro (general, ventas, etc.)
    $fechaDesde = $_GET['fechaDesde'] ?? null;
    $fechaHasta = $_GET['fechaHasta'] ?? null;

    // Construcción de la consulta SQL base
    $query = "
        SELECT 
            o.placed_on AS fecha,
            o.dni_paciente AS dni,
            od.codpro AS cuenta,
            od.total_after_discount AS debito,
            od.descripcion AS proyecto
        FROM 
            orders o
        INNER JOIN 
            order_details od ON o.idord = od.order_id
        WHERE 1=1
    ";

    // Si no es "todos" ni "ventas", devolver un conjunto vacío
    if ($option !== 'todos' && $option !== 'ventas') {
        echo json_encode([
            'success' => true,
            'data' => [] // Enviar datos vacíos
        ]);
        exit;
    }

    // Filtro de fechas
    if ($fechaDesde) {
        $query .= " AND o.placed_on >= :fechaDesde";
    }
    if ($fechaHasta) {
        $query .= " AND o.placed_on <= :fechaHasta";
    }

    // Preparar la consulta
    $stmt = $connect->prepare($query);

    // Vincular parámetros de fecha si se proporcionaron
    if ($fechaDesde) {
        $stmt->bindParam(':fechaDesde', $fechaDesde);
    }
    if ($fechaHasta) {
        $stmt->bindParam(':fechaHasta', $fechaHasta);
    }

    // Ejecutar la consulta
    $stmt->execute();

    // Obtener los resultados
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retornar los resultados como JSON
    echo json_encode([
        'success' => true,
        'data' => $resultados
    ]);
} catch (Exception $e) {
    // Manejar errores
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los datos: ' . $e->getMessage()
    ]);
}
