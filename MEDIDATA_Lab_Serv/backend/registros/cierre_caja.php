<?php
session_start();
require_once '../../backend/bd/Conexion.php';
require_once '../php/funciones_diario_general.php';
require_once '../php/funciones_cuadre_caja.php';

// Configurar la zona horaria a Honduras
date_default_timezone_set('America/Tegucigalpa');

try {
    $connect->beginTransaction();

    // Usuario actual
    $usuarioActual = $_SESSION['username'] ?? 'Usuario no disponible';
    $nombreActual = $_SESSION['name'] ?? 'Nombre no disponible';
    
    // Efectivo físico y sobrante siempre serán 0.00 (campos eliminados del sistema)
    $efectivoFisicoFinal = 0.00;
    $sobranteCaja = 0.00;
    
    // Obtener el ID del turno iniciado HOY SIN cerrar Y su efectivo inicial (usando fecha local Honduras)
    $fechaHonduras = date('Y-m-d');
    

    
    $stmtTurno = $connect->prepare("
        SELECT id, turno, fecha_inicio
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
    $stmtTurno->execute([$usuarioActual, $fechaHonduras]);
    $turnoData = $stmtTurno->fetch(PDO::FETCH_ASSOC);
    
    // VALIDACIÓN CRÍTICA: Verificar que existe un turno activo
    if (!$turnoData) {

        echo '<script>
            Swal.fire("Error", "No hay un turno activo para cerrar. Debe iniciar un turno antes de hacer cierre de caja.", "error")
            .then(function() {
                window.location = "../../frontend/caja/escritorio.php";
            });
        </script>';
        exit;
    }
    
    $idTurnoActual = (int) $turnoData['id'];
    $efectivoInicial = 0.00; // Efectivo inicial siempre será 0.00 (campo eliminado del sistema)
    $turno = $turnoData['turno'] ?? null;
    $fechaInicioTurno = $turnoData['fecha_inicio'] ?? date('Y-m-d H:i:s');

    // Ventana del cierre: turno actual → ahora (evita omitir cobros del turno por filtros globales)
    $fechaCierre = date('Y-m-d H:i:s');
    $fechaDesdeCierre = $fechaInicioTurno;

    $stmtUltimoCierreTurno = $connect->prepare("
        SELECT MAX(fecha_cierre) AS fecha_cierre
        FROM cierre_caja
        WHERE id_turno_iniciado = ?
    ");
    $stmtUltimoCierreTurno->execute([$idTurnoActual]);
    $ultimoCierreTurno = $stmtUltimoCierreTurno->fetch(PDO::FETCH_ASSOC);
    if (!empty($ultimoCierreTurno['fecha_cierre'])) {
        $fechaDesdeCierre = $ultimoCierreTurno['fecha_cierre'];
    }

    error_log("DEBUG cierre_caja: Ventana turno id=$idTurnoActual desde=$fechaDesdeCierre hasta=$fechaCierre");

    // Facturas del día del turno, cobradas en la ventana del turno y atribuibles al cajero
    $facturasCobradas = medidata_facturas_cierre_turno(
        $connect,
        $usuarioActual,
        $nombreActual,
        $fechaInicioTurno,
        $fechaCierre,
        $fechaDesdeCierre
    );
    
    // Log para ver qué facturas se están procesando
    error_log("===== FACTURAS PARA CIERRE =====");
    foreach ($facturasCobradas as $idx => $fac) {
        error_log("Factura #" . ($idx + 1) . " - ID: {$fac['idord']}, Método: {$fac['method']}, Banco Emisor: '" . ($fac['banco_emisor'] ?? 'NULL') . "'");
    }
    error_log("=================================");
    
    error_log("DEBUG cierre_caja: Facturas cobradas encontradas: " . count($facturasCobradas));
    error_log("DEBUG cierre_caja: Rango de fecha - Desde: $fechaDesdeCierre, Hasta: $fechaCierre");
    error_log("DEBUG cierre_caja: Usuario actual: $usuarioActual");
    if (count($facturasCobradas) > 0) {
        error_log("DEBUG cierre_caja: Primera factura: " . json_encode($facturasCobradas[0]));
    }

    // Total de ventas del usuario actual
    $ventasUsuario = array_sum(array_column($facturasCobradas, 'total_price'));

    // NOTA: Se eliminó la validación de duplicados para permitir múltiples cierres de caja por día
    // Cada turno puede tener su propio cierre independientemente del total de ventas

    // Total de facturas cobradas
    $totalFacturasCobradas = count($facturasCobradas);

    // Facturas pendientes globales
    $stmtPendientes = $connect->prepare("
        SELECT COUNT(*) AS total 
        FROM orders 
        WHERE invoice_status = 'Pendiente'
    ");
    $stmtPendientes->execute();

    $totalFacturasPendientes = $stmtPendientes->fetchColumn();

    // Totales por método calculados desde las mismas facturas (evita descuadres)
    $totalPorMetodo = json_encode(medidata_totales_por_metodo_desde_facturas($facturasCobradas));

    	// Registrar el cierre en la base de datos
	$insertCierre = $connect->prepare("
		INSERT INTO cierre_caja (
			fecha_cierre, 
			total_ventas, 
			total_facturas, 
			facturas_cobradas, 
			facturas_pendientes, 
			total_por_metodo, 
			usuario_cierre, 
			nombre_completo,
			sobrante_caja,
			id_turno_iniciado
		) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	");

	
	// Preparar datos para el INSERT ($fechaCierre ya definida al calcular la ventana)
	$datosInsert = [
		$fechaCierre,
		$ventasUsuario,
		$totalFacturasCobradas + $totalFacturasPendientes,
		$totalFacturasCobradas,
		$totalFacturasPendientes,
		$totalPorMetodo,
		$usuarioActual,
		$nombreActual,
		$sobranteCaja,
		$idTurnoActual
	];
	
	// Log para debugging
	error_log("DEBUG cierre_caja: Intentando insertar cierre - Usuario: $usuarioActual, ID Turno: $idTurnoActual, Fecha: $fechaCierre");
	
	// Ejecutar el INSERT del cierre de caja
	$resultadoInsert = $insertCierre->execute($datosInsert);
	
	if (!$resultadoInsert) {
		$connect->rollBack();
		$errorInfo = $insertCierre->errorInfo();
		error_log("ERROR cierre_caja: Fallo al insertar - " . print_r($errorInfo, true));
		
		echo '<script>
            Swal.fire("Error", "Hubo un problema al registrar el cierre de caja: ' . addslashes($errorInfo[2] ?? 'Error desconocido') . '", "error")
            .then(function() {
                window.location = "../../frontend/caja/escritorio.php";
            });
        </script>';
		exit;
	}
	
	// Log de éxito
	$idCierreInsertado = $connect->lastInsertId();
	error_log("DEBUG cierre_caja: Cierre insertado exitosamente - ID: $idCierreInsertado, ID Turno: $idTurnoActual");
	
	// Confirmar el cierre de caja PRIMERO (antes de registrar transacciones contables)
	// Esto asegura que el cierre se guarde incluso si hay un error en el diario general
	$connect->commit();
	error_log("DEBUG cierre_caja: Transacción confirmada - ID Cierre: $idCierreInsertado");
	
	// Registrar transacciones contables en el Diario General (FUERA de la transacción principal)
	// Esto evita que un error en el diario general revierta el cierre de caja
	try {
		$fechaCierre = date('Y-m-d');
		error_log("DEBUG cierre_caja: Antes de llamar registrarTransaccionesCierreCaja - Fecha: $fechaCierre, Usuario: $nombreActual, Facturas: " . count($facturasCobradas) . ", Turno: " . ($turno ?? 'null'));
		error_log("DEBUG cierre_caja: Facturas cobradas: " . json_encode($facturasCobradas));
		
		if (empty($facturasCobradas)) {
			error_log("WARNING cierre_caja: No hay facturas cobradas para registrar en el diario general");
		} else {
			$resultado = registrarTransaccionesCierreCaja($fechaCierre, $nombreActual, $facturasCobradas, $turno);
			error_log("DEBUG cierre_caja: Después de llamar registrarTransaccionesCierreCaja - Resultado: " . ($resultado ?? 'null'));
		}
	} catch (Exception $e) {
		// Registrar error pero no interrumpir el cierre de caja (ya está confirmado)
		error_log("ERROR cierre_caja: Error al registrar transacciones contables: " . $e->getMessage());
		error_log("ERROR cierre_caja: Stack trace: " . $e->getTraceAsString());
	}

	echo '<script>
        Swal.fire("¡Éxito!", "Cierre de caja realizado con éxito.", "success")
        .then(function() {
            // Forzar recarga completa para actualizar el estado del botón
            window.location.href = "../../frontend/caja/escritorio.php";
        });
    </script>';
} catch (Exception $e) {
	// Revertir transacción solo si está activa
	if ($connect->inTransaction()) {
		$connect->rollBack();
	}
	
	// Error silencioso para producción
    echo '<script>
        Swal.fire("Error", "Error al realizar el cierre: ' . addslashes($e->getMessage()) . '", "error")
        .then(function() {
            window.location = "../../frontend/caja/escritorio.php";
        });
    </script>';
}
?>