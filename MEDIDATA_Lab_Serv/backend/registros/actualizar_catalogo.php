<?php
header('Content-Type: application/json');
// Incluir la hora de Honduras
date_default_timezone_set('America/Tegucigalpa');
// Incluir la conexión a la base de datos
require_once '../../backend/bd/Conexion.php';

// Manejar la actualización
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cuenta-actualizar'])) {
    $cuenta_actualizar = trim($_POST['cuenta-actualizar']);
    $nuevo_tipo_cuenta = trim($_POST['nuevo-tipo-cuenta']);
    $nuevo_nombre = trim($_POST['nuevo-nombre']);
    
    try {
        // Verificar si la cuenta existe
        $sql_check = "SELECT 1 FROM cuentas_catalogo WHERE cuenta = :cuenta";
        $stmt_check = $connect->prepare($sql_check);
        $stmt_check->bindParam(':cuenta', $cuenta_actualizar);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() === 0) {
            // Responder con un error si la cuenta no existe
            echo json_encode([
                'success' => false,
                'message' => 'La cuenta no existe.'
            ]);
        } else {
            // Obtener el nombre actual antes de actualizar para sincronizar con servicios_hospital
            $sql_get_current = "SELECT nombre FROM cuentas_catalogo WHERE cuenta = :cuenta";
            $stmt_get_current = $connect->prepare($sql_get_current);
            $stmt_get_current->bindParam(':cuenta', $cuenta_actualizar);
            $stmt_get_current->execute();
            $nombre_actual = $stmt_get_current->fetchColumn();
            
            // Iniciar transacción para asegurar consistencia de datos
            $connect->beginTransaction();
            
            try {
                // Preparar la consulta SQL para la actualización del catálogo
                $sql = "UPDATE cuentas_catalogo 
                        SET tipo_cuenta = :nuevo_tipo_cuenta, nombre = :nuevo_nombre 
                        WHERE cuenta = :cuenta";
                $stmt = $connect->prepare($sql);
                $stmt->bindParam(':nuevo_tipo_cuenta', $nuevo_tipo_cuenta);
                $stmt->bindParam(':nuevo_nombre', $nuevo_nombre);
                $stmt->bindParam(':cuenta', $cuenta_actualizar);
                
                if ($stmt->execute()) {
                    // Actualizar también en servicios_hospital usando el código de la cuenta
                    // Esto es más confiable que usar el nombre para hacer el match
                    $nuevo_nombre_upper = strtoupper($nuevo_nombre);
                    
                    // Log para debugging - puedes comentar esta línea después de probar
                    error_log("Actualizando SOLO el nombre de servicios con código '$cuenta_actualizar' al nombre: '$nuevo_nombre_upper' (categoría se mantiene sin cambios)");
                    
                    // Verificar primero qué servicios existen con este código
                    $sql_check_servicios = "SELECT id, codigo_servicio, nombre_servicio, categoria_servicio FROM servicios_hospital WHERE codigo_servicio = :cuenta_actualizar";
                    $stmt_check_servicios = $connect->prepare($sql_check_servicios);
                    $stmt_check_servicios->bindParam(':cuenta_actualizar', $cuenta_actualizar);
                    $stmt_check_servicios->execute();
                    $servicios_existentes = $stmt_check_servicios->fetchAll(PDO::FETCH_ASSOC);
                    
                    error_log("Servicios encontrados con código '$cuenta_actualizar': " . count($servicios_existentes));
                    foreach ($servicios_existentes as $servicio) {
                        error_log("ID: {$servicio['id']}, Código: {$servicio['codigo_servicio']}, Nombre: '{$servicio['nombre_servicio']}', Categoría: '{$servicio['categoria_servicio']}'");
                    }
                    
                    // Actualizar SOLO nombre_servicio en servicios_hospital, manteniendo categoria_servicio intacta
                    $sql_update_servicios = "UPDATE servicios_hospital 
                                           SET nombre_servicio = :nuevo_nombre
                                           WHERE codigo_servicio = :cuenta_actualizar";
                    $stmt_update_servicios = $connect->prepare($sql_update_servicios);
                    $stmt_update_servicios->bindParam(':nuevo_nombre', $nuevo_nombre_upper);
                    $stmt_update_servicios->bindParam(':cuenta_actualizar', $cuenta_actualizar);
                    $stmt_update_servicios->execute();
                    
                    // Obtener el número de servicios actualizados
                    $servicios_actualizados = $stmt_update_servicios->rowCount();
                    
                    error_log("Filas actualizadas: $servicios_actualizados");
                    
                    // Verificar el estado después de la actualización
                    $stmt_check_servicios->execute();
                    $servicios_despues = $stmt_check_servicios->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Estado después de actualización:");
                    foreach ($servicios_despues as $servicio) {
                        error_log("ID: {$servicio['id']}, Código: {$servicio['codigo_servicio']}, Nombre actualizado: '{$servicio['nombre_servicio']}', Categoría (SIN CAMBIOS): '{$servicio['categoria_servicio']}'");
                    }
                    
                    // Confirmar la transacción
                    $connect->commit();
                    
                    // Responder con éxito incluyendo información de sincronización
                    $mensaje = 'Cuenta actualizada con éxito.';
                    if ($servicios_actualizados > 0) {
                        $mensaje .= " Se actualizó el nombre de $servicios_actualizados servicios asociados (la categoría se mantuvo sin cambios).";
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => $mensaje,
                        'servicios_actualizados' => $servicios_actualizados
                    ]);
                } else {
                    // Revertir la transacción
                    $connect->rollBack();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error al actualizar la cuenta. Inténtalo de nuevo.'
                    ]);
                }
            } catch (Exception $e) {
                // Revertir la transacción en caso de error
                $connect->rollBack();
                throw $e;
            }
        }
    } catch (PDOException $e) {
        // Responder con un error de base de datos
        echo json_encode([
            'success' => false,
            'message' => 'Ocurrió un problema con la base de datos: ' . $e->getMessage()
        ]);
    }
}
