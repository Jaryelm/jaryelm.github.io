<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

/**
 * Endpoint for Job Positions (Puestos de Trabajo)
 * Returns JSON data for the card grid.
 */

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        $sql = "SELECT pd.*, p.name 
                FROM positions_details pd 
                JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id 
                WHERE pd.deleted = 0";
        
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE :search 
                        OR pd.department LIKE :search 
                        OR pd.objective LIKE :search 
                        OR pd.immediate_boss LIKE :search)";
        }

        $sql .= " ORDER BY pd.created_at DESC" . medidata_tablas_mysql_limit_clause();

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
    error_log('Database error in tabla_puestos_trabajo.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
