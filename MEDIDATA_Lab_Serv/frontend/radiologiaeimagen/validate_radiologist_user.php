<?php
require_once('../../backend/bd/Conexion.php');
// Removido header() para evitar conflictos cuando se incluye en otros archivos

/**
 * Función para validar y obtener el user_id correspondiente a un doctor_id
 * @param int $doctor_id - ID del doctor (idodc)
 * @return array|null - Array con doctor_id, user_id, doctor_name, specialty o null si no encuentra
 */
function validateAndGetUserIds($doctor_id) {
    global $connect;
    
    try {
        // 1. Obtener datos del doctor
        $stmt = $connect->prepare("SELECT idodc, nodoc, apdoc, nomesp FROM doctor WHERE idodc = ?");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctor) {
            return null;
        }
        
        // 2. Buscar usuario correspondiente por nombre completo (búsqueda exacta)
        $full_name = trim($doctor['nodoc'] . ' ' . $doctor['apdoc']);
        $stmt = $connect->prepare("SELECT id, name FROM users WHERE name = ? AND rol = 'Radiologo'");
        $stmt->execute([$full_name]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 3. Si no se encuentra con búsqueda exacta, buscar coincidencias parciales
        if (!$user) {
            // Buscar por nombre y apellido por separado
            $first_name = trim($doctor['nodoc']);
            $last_name = trim($doctor['apdoc']);
            
            // Buscar usuarios que contengan el nombre y apellido
            $stmt = $connect->prepare("
                SELECT id, name 
                FROM users 
                WHERE rol = 'Radiologo' 
                AND (name LIKE ? OR name LIKE ? OR name LIKE ?)
                ORDER BY name
            ");
            $search_pattern1 = '%' . $first_name . '%' . $last_name . '%';
            $search_pattern2 = '%' . $last_name . '%' . $first_name . '%';
            $search_pattern3 = '%' . $first_name . '%';
            
            $stmt->execute([$search_pattern1, $search_pattern2, $search_pattern3]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si encontramos usuarios, usar el primero
            if (!empty($users)) {
                $user = $users[0];
                error_log("Usuario encontrado por búsqueda parcial: " . $user['name'] . " para doctor: " . $full_name);
            }
        }
        
        return [
            'doctor_id' => $doctor['idodc'],
            'user_id' => $user ? $user['id'] : null,
            'doctor_name' => $full_name,
            'specialty' => $doctor['nomesp'],
            'found_user' => $user ? true : false,
            'user_name_found' => $user ? $user['name'] : null
        ];
        
    } catch (Exception $e) {
        error_log("Error en validateAndGetUserIds: " . $e->getMessage());
        return null;
    }
}

/**
 * Función para crear usuario automáticamente si no existe
 * @param int $doctor_id - ID del doctor (idodc)
 * @return int|null - user_id creado o null si falla
 */
function createUserIfNotExists($doctor_id) {
    global $connect;
    
    try {
        // Obtener datos del doctor
        $stmt = $connect->prepare("SELECT idodc, nodoc, apdoc, nomesp FROM doctor WHERE idodc = ?");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctor) {
            return null;
        }
        
        $full_name = trim($doctor['nodoc'] . ' ' . $doctor['apdoc']);
        $username = strtolower(str_replace(' ', '.', $full_name));
        $email = $username . '@medicasa.hn';
        $password = password_hash('default123', PASSWORD_DEFAULT);
        
        // Verificar si el usuario ya existe
        $stmt = $connect->prepare("SELECT id FROM users WHERE name = ?");
        $stmt->execute([$full_name]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_user) {
            return $existing_user['id'];
        }
        
        // Crear nuevo usuario
        $stmt = $connect->prepare("INSERT INTO users (username, name, email, password, rol, created_at) VALUES (?, ?, ?, ?, 'Radiologo', NOW())");
        $stmt->execute([$username, $full_name, $email, $password]);
        
        return $connect->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Error en createUserIfNotExists: " . $e->getMessage());
        return null;
    }
}

// Si se llama directamente, devolver información de debug
if (isset($_GET['doctor_id'])) {
    header('Content-Type: application/json');
    $doctor_id = intval($_GET['doctor_id']);
    $result = validateAndGetUserIds($doctor_id);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Doctor no encontrado'
        ]);
    }
}
?> 