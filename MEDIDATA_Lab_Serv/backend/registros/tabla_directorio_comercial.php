<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        // Comienza construyendo la consulta SQL
        $sql = "SELECT * FROM proveedor_comercial";
        
        // Si hay un término de búsqueda, añade condiciones a la consulta
        if (!empty($search)) {
            $sql .= " WHERE CONCAT_WS(' ', nombre_empresa, direccion, rtn_comercial, tel_fijo, correo_comercial, cel_whatsapp, nombre_legal, dni_comercial, cel_comercial, cuenta_bac_comercial, tipo_cuenta_comercial, archivo_constancia_comercial, nom_contacto) LIKE :search";
        }

        // Añadir ordenamiento a la consulta
        $sql .= " ORDER BY fecha_registro DESC" . medidata_tablas_mysql_limit_clause();

        $stmt = $connect->prepare($sql);
        
        // Si hay un término de búsqueda, prepara el parámetro
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $stmt->bindParam(':search', $search_param);
        }
        
        // Ejecutar la consulta
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Procesar los resultados
        foreach ($results as &$row) {
            // Verificar y modificar el campo del archivo
            if (!empty($row['archivo_constancia_comercial'])) {
                $row['archivo_constancia_comercial'] = '/uploads/' . basename($row['archivo_constancia_comercial']);
            }
        }
        
        // Retornar resultados en formato JSON
        echo json_encode($results);
    }
} catch (PDOException $e) {
    // Manejo de errores
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
