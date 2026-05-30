<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

/**
 * Endpoint for Vacancies (Vacantes de Trabajo)
 * Returns JSON data for the card grid.
 */

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        $sql = "SELECT vp.*, p.name as position_name,
                (SELECT COUNT(*) FROM candidates c WHERE c.id_vacant_position = vp.id AND c.deleted = 0) as total_applicants
                FROM vacant_positions vp 
                JOIN positions_details pd ON vp.id_position = pd.id 
                JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id 
                WHERE vp.deleted = 0";
        
        if (!empty($search)) {
            $sql .= " AND (vp.vacant_name LIKE :search 
                        OR p.name LIKE :search 
                        OR vp.requesting_department LIKE :search 
                        OR vp.reason LIKE :search)";
        }

        $sql .= " ORDER BY 
                    CASE priority 
                        WHEN 'Urgente' THEN 1 
                        WHEN 'Alta' THEN 2 
                        WHEN 'Media' THEN 3 
                        WHEN 'Baja' THEN 4 
                    END, 
                    vp.created_at DESC" . medidata_tablas_mysql_limit_clause();

        $stmt = $connect_rrhh->prepare($sql);
        
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $stmt->bindParam(':search', $search_param);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($results);
    }
} catch (PDOException $e) {
    error_log('Database error in tabla_vacantes_trabajo.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
