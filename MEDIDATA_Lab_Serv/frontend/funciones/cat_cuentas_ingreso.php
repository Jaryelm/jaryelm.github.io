<?php 
require '../../backend/bd/Conexion.php';

// Generar opciones con nombres de cuentas de tipo "Ingreso"
echo '<option value="">Seleccione o busque un servicio</option>';

// Consultar cuentas de tipo "Ingresos"
$stmt = $connect->prepare('SELECT * FROM `cuentas_catalogo` WHERE tipo_cuenta = :tipo ORDER BY nombre ASC');
$stmt->execute([':tipo' => 'Ingresos']);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Usar el valor de 'cuenta' como data-cuenta
    echo '<option value="' . $row['nombre'] . '" data-cuenta="' . $row['cuenta'] . '">' . $row['nombre'] . '</option>';
}

// Verificar si "Otros Ingresos" (710100101) existe en la base de datos
$stmtOtrosIngresos = $connect->prepare('SELECT * FROM `cuentas_catalogo` WHERE cuenta = :cuenta LIMIT 1');
$stmtOtrosIngresos->execute([':cuenta' => '710100101']);
$otrosIngresos = $stmtOtrosIngresos->fetch(PDO::FETCH_ASSOC);

// Si existe "Otros Ingresos", agregarlo a la lista (si no está ya incluido)
if ($otrosIngresos) {
    // Verificar si ya fue agregado en la consulta anterior
    $stmtCheck = $connect->prepare('SELECT * FROM `cuentas_catalogo` WHERE cuenta = :cuenta AND tipo_cuenta = :tipo LIMIT 1');
    $stmtCheck->execute([':cuenta' => '710100101', ':tipo' => 'Ingresos']);
    $yaIncluido = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    // Si no está incluido (porque tiene otro tipo_cuenta), agregarlo manualmente
    if (!$yaIncluido) {
        $nombreOtrosIngresos = $otrosIngresos['nombre'] ?? 'Otros Ingresos';
        echo '<option value="' . htmlspecialchars($nombreOtrosIngresos) . '" data-cuenta="710100101">' . htmlspecialchars($nombreOtrosIngresos) . '</option>';
    }
} else {
    // Si no existe en la base de datos, agregarlo manualmente
    echo '<option value="Otros Ingresos" data-cuenta="710100101">Otros Ingresos</option>';
}

// Agregar "Proveedores Comerciales" (210200107) para servicios que lo requieran
$stmtProveedores = $connect->prepare('SELECT * FROM `cuentas_catalogo` WHERE cuenta = :cuenta LIMIT 1');
$stmtProveedores->execute([':cuenta' => '210200107']);
$proveedoresComerciales = $stmtProveedores->fetch(PDO::FETCH_ASSOC);
if ($proveedoresComerciales) {
    $nombreProveedores = $proveedoresComerciales['nombre'] ?? 'Proveedores Comerciales';
    echo '<option value="' . htmlspecialchars($nombreProveedores) . '" data-cuenta="210200107">' . htmlspecialchars($nombreProveedores) . '</option>';
} else {
    echo '<option value="Proveedores Comerciales" data-cuenta="210200107">Proveedores Comerciales</option>';
}
