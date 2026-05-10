<?php
require('../../backend/fpdf/fpdf.php');
require_once '../../backend/bd/Conexion.php';

function convertirACapitalAcentuadas($texto) {
    $reemplazos = [
        'á' => 'Á',
        'é' => 'É',
        'í' => 'Í',
        'ó' => 'Ó',
        'ú' => 'Ú',
        'ñ' => 'Ñ',
    ];
    return strtr($texto, $reemplazos);
}

function generarPDFProveedor($proveedorData) {
    $pdf = new FPDF();
    $pdf->AddPage();

    // Logo de la empresa
    $logoWidth = 50; // Ancho del logo
    $paginaAncho = $pdf->GetPageWidth(); // Ancho de la página
    $xLogo = ($paginaAncho - $logoWidth) / 2; // Calcular la posición centrada
    $pdf->Image('../../backend/img/logo_medicasa.png', $xLogo, 10, $logoWidth);
    $pdf->Ln(35);

    // Título del documento
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(6, 173, 191); // Color del título (hex: #06adbf)
    $pdf->Cell(0, 8, mb_convert_encoding('FORMULARIO DE GESTIÓN DE PAGOS A PROVEEDORES MÉDICOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $pdf->Ln(5); // Espacio reducido debajo del título

    // Datos del Proveedor
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0); // Color negro para el texto
    $pdf->Cell(0, 8, mb_convert_encoding('Datos del Proveedor:', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(2); // Espacio reducido debajo del subtítulo
    $pdf->SetFont('Arial', '', 10); // Fuente más pequeña para mejor ajuste

    // Calcular el ancho de las columnas
    $col1Width = 80; // Ancho ajustado para la primera columna (títulos)
    $col2Width = 100; // Ancho para la segunda columna (valores)

    // Definir los datos del proveedor en dos columnas
    $datos = [
        'Nombre Completo Proveedor:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['nombre_proveedor'], 'ISO-8859-1', 'UTF-8')),
        'Especialidad:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['especialidad'], 'ISO-8859-1', 'UTF-8')),
        'Numero de Identidad:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['identidad'], 'ISO-8859-1', 'UTF-8')),
        'Numero Colegiado:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['colegiado'], 'ISO-8859-1', 'UTF-8')),
        'RTN:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['rtn'], 'ISO-8859-1', 'UTF-8')),
        'Numero Celular:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['celular'], 'ISO-8859-1', 'UTF-8')),
        'Correo Electronico:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['correo'], 'ISO-8859-1', 'UTF-8')),
        'Tiene Cuenta BAC:' => ($proveedorData['cuenta_bac']),
        'Numero de Cuenta BAC:' => ($proveedorData['cuenta_si']),
        'Numero de Cuenta Otro Banco:' => ($proveedorData['cuenta_no']),
        'Tipo de Cuenta:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['tipo_cuenta'], 'ISO-8859-1', 'UTF-8')),
        'Constancia de Pagos:' => ($proveedorData['constancia_pagos']),
        'Solicitud de Constancia:' => ($proveedorData['solicitud_constancia']),
        'Constancia Vigente:' => ($proveedorData['constancia_vigente']),
        'Fecha de Registro:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['fecha_registro'], 'ISO-8859-1', 'UTF-8'))
    ];

    // Imprimir los datos en dos columnas
    foreach ($datos as $titulo => $valor) {
        $pdf->SetX(10);
        $pdf->Cell($col1Width, 7, mb_convert_encoding($titulo, 'ISO-8859-1', 'UTF-8'), 0, 0);
        $pdf->Cell($col2Width, 7, mb_convert_encoding($valor, 'ISO-8859-1', 'UTF-8'), 0, 1);
    }

    $pdf->Ln(6); // Espacio reducido debajo de los datos del proveedor
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0); // Color negro para la información importante
    $pdf->Cell(0, 8, mb_convert_encoding('Informacion Importante:', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(3); // Espacio reducido debajo del subtítulo
    $pdf->SetFont('Arial', '', 10); // Fuente más pequeña para la información importante
    $pdf->SetTextColor(255, 0, 0); // Color rojo para los números

    // Información importante
    $infoImportante = [
        mb_convert_encoding('1. Por seguridad, para acortar tiempos y para evitar la espera por gestiones manuales de pago, MEDICASA asegura su pago por transferencia bancaria.', 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding('2. Apóyese con su secretaria elaborando y dejando los recibos de honorarios médicos según pacientes atendidos.', 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding('3. Si tiene constancia de pagos a cuenta, NO se le hace la retención del 12.5%.', 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding('4. Favor verificar que los datos que nos proporcionó sean los correctos.', 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding('5. Una vez debitado, se le enviaría notificación a su correo electrónico.', 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding('6. A todo proveedor médico se le retendrá el 2% por gestión administrativa según el monto acumulado a cancelar (trabajo contable no correspondiente a nuestro personal por falta de constancia de pagos a cuenta y, posteriormente, por emisión de constancia de retención por honorarios dirigida a la SAR).', 'ISO-8859-1', 'UTF-8')        
    ];

    foreach ($infoImportante as $punto) {
        $pdf->MultiCell(0, 7, $punto);
        $pdf->Ln(1);
    }

    // Espacio antes del título de la firma
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, mb_convert_encoding('Firma Digital del Proveedor Médico', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Firma digital imagen en el pie de página
    $nombreProveedor = $proveedorData['nombre_proveedor'];
    $rutaFirma = '../../uploads/firmas/' . $nombreProveedor . '.png';
    if (file_exists($rutaFirma)) {
        $pdf->Image($rutaFirma, 10, 270, 80);
    } else {
        $pdf->Cell(0, 10, 'Firma digital no disponible', 0, 1, 'L');
    }

    $pdf->Output();
}

try {
    if (isset($_GET['nombre_proveedor'])) {
        $nombreProveedor = $_GET['nombre_proveedor'];
        $sql = "SELECT * FROM proveedor_data WHERE nombre_proveedor = :nombre_proveedor";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':nombre_proveedor', $nombreProveedor);
        $stmt->execute();
        $proveedorData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($proveedorData) {
            generarPDFProveedor($proveedorData);
        } else {
            echo 'Proveedor no encontrado.';
        }
    } else {
        echo 'No se ha especificado el nombre del proveedor.';
    }
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}