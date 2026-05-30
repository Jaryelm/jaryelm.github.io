<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $id_vacante = isset($_GET['id_vacante']) ? (int)$_GET['id_vacante'] : 0;
        
        $sql = "SELECT c.id, c.fullname, c.dni, c.phonenumber, c.email, c.status, c.created_at, c.overall_score
                FROM candidates c 
                WHERE c.deleted = 0";
                
        if ($id_vacante > 0) {
            $sql .= " AND c.id_vacant_position = :id_vacante";
        }
        
        $sql .= " ORDER BY c.created_at DESC" . medidata_tablas_mysql_limit_clause();

        $stmt = $connect_rrhh->prepare($sql);
        
        if ($id_vacante > 0) {
            $stmt->bindParam(':id_vacante', $id_vacante, PDO::PARAM_INT);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['data' => $results]);
    }
} catch (PDOException $e) {
    error_log('Database error in tabla_postulantes_vacante.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
