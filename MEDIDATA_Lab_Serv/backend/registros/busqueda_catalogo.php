<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $sql = "SELECT cuenta, nombre, tipo_cuenta FROM cuentas_catalogo";

        if (!isset($_GET['search']) || empty(trim($_GET['search']))) {
            // Obtener todos los registros sin filtrar
            $sql .= " ORDER BY fecha_registro DESC" . medidata_tablas_mysql_limit_clause();
            $stmt = $connect->prepare($sql);
        } else {
            // Manejar la búsqueda
            $search = trim($_GET['search']);
            $sql .= " WHERE CONCAT_WS(' ', tipo_cuenta, cuenta, nombre) LIKE :search ORDER BY cuenta" . medidata_tablas_mysql_limit_clause();
            $stmt = $connect->prepare($sql);
            $search_param = '%' . $search . '%';
            $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Devolver los resultados en formato JSON
        echo json_encode($results);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
}
