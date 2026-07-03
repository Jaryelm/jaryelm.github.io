<?php
session_start();
require_once '../bd/Conexion.php';

// Configurar la zona horaria a Honduras
date_default_timezone_set('America/Tegucigalpa');

$usuarioActual = $_SESSION['username'] ?? '';
$nombreActual = $_SESSION['name'] ?? '';
// Efectivo inicial siempre será 0.00 (campo eliminado del sistema)
$efectivoInicial = 0.00;
$turno = isset($_POST['turno']) ? trim($_POST['turno']) : '';

	try {
	$fechaActual = date('Y-m-d H:i:s');
	$fechaHoy = date('Y-m-d');
	
	// Verificar si ya tiene un turno iniciado HOY SIN cerrar
	$stmtTurnoActivo = $connect->prepare("
		SELECT COUNT(*) as total
		FROM turnos_iniciados t
		WHERE t.usuario = ? 
		AND DATE(t.fecha_inicio) = ?
		AND NOT EXISTS (
			SELECT 1 FROM cierre_caja c 
			WHERE c.id_turno_iniciado = t.id
		)
	");
	$stmtTurnoActivo->execute([$usuarioActual, $fechaHoy]);
	$turnoActivo = $stmtTurnoActivo->fetchColumn();
	
	if ($turnoActivo > 0) {
		echo '<script>
			Swal.fire("Información", "Ya tiene un turno iniciado sin cerrar. Debe cerrar el turno actual antes de iniciar uno nuevo.", "info");
		</script>';
		exit;
	}
	
	// Crear tabla si no existe (sin transacción para DDL)
	$connect->exec("
        CREATE TABLE IF NOT EXISTS turnos_iniciados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario VARCHAR(50) NOT NULL,
            nombre_completo VARCHAR(100),
            fecha_inicio DATETIME NOT NULL,
            turno VARCHAR(50) DEFAULT NULL,
            INDEX idx_usuario_fecha (usuario, fecha_inicio)
        )
    ");
	
	// Agregar columna turno si no existe
	try {
		$connect->exec("ALTER TABLE turnos_iniciados ADD COLUMN turno VARCHAR(50) DEFAULT NULL");
	} catch (Exception $e) {
		// La columna ya existe, ignorar error
	}
	
	// Ahora iniciar transacción para el INSERT
	$connect->beginTransaction();
	
	// Validar que se haya seleccionado un turno
	if (empty($turno)) {
		echo '<script>
			Swal.fire("Error", "Debe seleccionar un turno.", "error");
		</script>';
		exit;
	}
	
	// Registrar inicio de turno
	$insertTurno = $connect->prepare("
        INSERT INTO turnos_iniciados (
            usuario, 
            nombre_completo, 
            fecha_inicio,
            turno
        ) VALUES (?, ?, ?, ?)
    ");
	
	if ($insertTurno->execute([
		$usuarioActual,
		$nombreActual,
		$fechaActual,
		$turno
	])) {
		$connect->commit();
		
		$idTurno = $connect->lastInsertId();
		
		echo '<script>
			Swal.fire("¡Turno Iniciado!", "Turno iniciado correctamente.\\nFecha: ' . $fechaActual . '", "success")
			.then(function() {
				window.location.reload();
			});
		</script>';
	} else {
		$connect->rollBack();
		echo '<script>
			Swal.fire("Error", "Error al registrar el inicio de turno.", "error");
		</script>';
	}
	
} catch (Exception $e) {
	if ($connect->inTransaction()) {
		$connect->rollBack();
	}
	// Error silencioso para producción
	echo '<script>
		Swal.fire("Error", "Error al iniciar turno. Intente nuevamente.", "error");
	</script>';
}
?>
