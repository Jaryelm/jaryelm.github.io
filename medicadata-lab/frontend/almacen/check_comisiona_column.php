<?php
require '../../backend/bd/Conexion.php';

try {
    // Verificar si la columna comisiona existe
    $stmt = $connect->prepare("SHOW COLUMNS FROM doctor LIKE 'comisiona'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // La columna no existe, agregarla
        $sql = "ALTER TABLE doctor ADD COLUMN comisiona ENUM('SI', 'NO') DEFAULT 'NO'";
        $connect->exec($sql);
        echo "Columna 'comisiona' agregada a la tabla doctor.\n";
    } else {
        echo "La columna 'comisiona' ya existe en la tabla doctor.\n";
    }
    
    // Verificar si la tabla remitentes_honorarios existe
    $stmt = $connect->prepare("SHOW TABLES LIKE 'remitentes_honorarios'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // La tabla no existe, crearla
        $sql = "CREATE TABLE remitentes_honorarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_factura INT NOT NULL,
            id_servicio INT NOT NULL,
            id_doctor_remitente INT NOT NULL,
            factura VARCHAR(50) NOT NULL,
            monto_comision DECIMAL(10,2) NOT NULL,
            usuario VARCHAR(100) NOT NULL,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_factura_servicio (id_factura, id_servicio)
        )";
        $connect->exec($sql);
        echo "Tabla 'remitentes_honorarios' creada.\n";
    } else {
        echo "La tabla 'remitentes_honorarios' ya existe.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 