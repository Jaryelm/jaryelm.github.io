<?php
    session_start();
    require '../backend/bd/Conexion.php';
    
    // Verificar si el usuario tiene un turno activo (solo para perfiles Caja y Facturación)
    $rol = $_SESSION['rol'] ?? '';
    $usuarioActual = $_SESSION['username'] ?? '';
    
    if (in_array($rol, ['Caja', 'Facturación']) && !empty($usuarioActual)) {
        try {
            // Obtener fecha local de Honduras
            date_default_timezone_set('America/Tegucigalpa');
            $fechaHonduras = date('Y-m-d');
            
            // Verificar si el usuario tiene un turno activo HOY sin cerrar
            $stmt = $connect->prepare("
                SELECT 
                    t.id,
                    t.fecha_inicio,
                    t.turno
                FROM turnos_iniciados t
                WHERE t.usuario = ?
                AND DATE(t.fecha_inicio) = ?
                AND NOT EXISTS (
                    SELECT 1 FROM cierre_caja c 
                    WHERE c.id_turno_iniciado = t.id
                )
                ORDER BY t.fecha_inicio DESC
                LIMIT 1
            ");
            
            $stmt->execute([$usuarioActual, $fechaHonduras]);
            $turno = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($turno) {
                // Hay un turno activo sin cerrar - no permitir cerrar sesión
                // Redirigir con mensaje de error
                $_SESSION['errMsg'] = 'No puede cerrar sesión sin realizar el cierre de caja. Por favor, realice el cierre de caja antes de salir.';
                
                // Determinar la ruta de redirección según el perfil
                if ($rol === 'Caja') {
                    header('Location: caja/escritorio.php');
                } elseif ($rol === 'Facturación') {
                    header('Location: facturacion/escritorio.php');
                } else {
                    header('Location: login.php');
                }
                exit;
            }
        } catch (Exception $e) {
            // En caso de error, permitir cerrar sesión (no bloquear)
            error_log('Error al verificar turno en salir.php: ' . $e->getMessage());
        }
    }
    
    // Si no hay turno activo o no es perfil Caja/Facturación, proceder con el cierre de sesión
    session_destroy();
    header('Location: login.php');
