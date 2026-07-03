<?php
include_once '../../backend/bd/Conexion.php';

// Definir cuentas principales con código y nombre
$cuentas_principales = [
    'activos' => ['codigo' => '1-1000', 'nombre' => 'Activos Corrientes'],
    'pasivos' => ['codigo' => '2-1000', 'nombre' => 'Pasivos Corrientes'],
    'capital' => ['codigo' => '3-1000', 'nombre' => 'Patrimonio'],
    'ingresos' => ['codigo' => '4-1000', 'nombre' => 'Ingresos Corrientes']
];

// Consulta para obtener todas las cuentas desde `order_details`
$stmt = $connect->prepare("
    SELECT codpro, SUM(total_after_discount) as total_balance
    FROM order_details
    GROUP BY codpro
");
$stmt->execute();
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar las cuentas por tipo
$categorias = [
    'activos' => [],
    'pasivos' => [],
    'capital' => [],
    'ingresos' => []
];

// Variables para almacenar los totales
$totales = [
    'activos' => 0,
    'pasivos' => 0,
    'capital' => 0,
    'ingresos' => 0
];

foreach ($cuentas as $cuenta) {
    $codpro = $cuenta['codpro'];
    $balance = floatval($cuenta['total_balance']);

    if (strpos($codpro, '1-') === 0) {
        $categorias['activos'][] = [
            'cuenta' => $codpro,
            'clase' => 'Detalle de Cuenta',
            'enlazada' => '1-1000 Activos Corrientes',
            'balance' => number_format($balance, 2)
        ];
        $totales['activos'] += $balance;
    } elseif (strpos($codpro, '2-') === 0) {
        $categorias['pasivos'][] = [
            'cuenta' => $codpro,
            'clase' => 'Detalle de Cuenta',
            'enlazada' => '2-1000 Pasivos Corrientes',
            'balance' => number_format($balance, 2)
        ];
        $totales['pasivos'] += $balance;
    } elseif (strpos($codpro, '3-') === 0) {
        $categorias['capital'][] = [
            'cuenta' => $codpro,
            'clase' => 'Detalle de Cuenta',
            'enlazada' => '3-1000 Patrimonio',
            'balance' => number_format($balance, 2)
        ];
        $totales['capital'] += $balance;
    } elseif (strpos($codpro, '4-') === 0) {
        $categorias['ingresos'][] = [
            'cuenta' => $codpro,
            'clase' => 'Detalle de Cuenta',
            'enlazada' => '4-1000 Ingresos Corrientes',
            'balance' => number_format($balance, 2)
        ];
        $totales['ingresos'] += $balance;
    }
}

// Asegurar que el encabezado se agregue correctamente ANTES de los detalles
foreach ($categorias as $key => &$lista) {
    if (isset($cuentas_principales[$key])) {
        $codigo_principal = $cuentas_principales[$key]['codigo'];
        $nombre_principal = $cuentas_principales[$key]['nombre'];
        $total_balance = number_format($totales[$key], 2);

        // Si hay cuentas de detalle, se coloca el encabezado de primero
        if (!empty($lista)) {
            array_unshift($lista, [
                'cuenta' => $codigo_principal, // Ahora correctamente asignado como "4-1000"
                'clase' => 'Encabezado',
                'enlazada' => '-',
                'balance' => $total_balance
            ]);
        }
    }
}

// Convertir a JSON y enviarlo al frontend
header('Content-Type: application/json');
echo json_encode($categorias, JSON_PRETTY_PRINT);
?>
