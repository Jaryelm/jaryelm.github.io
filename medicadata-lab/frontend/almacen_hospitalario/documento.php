<?php
/* Sesión opcional para firma y coherencia con factura PDF */
session_start();

/* Llamar a la librería FPDF */
require('../../backend/fpdf/fpdf.php');

class PDF_MC extends FPDF {
    public $invoice_number = '';
    public $is_first_page = true;

    function Header() {
        // Header hospital
        $this->Image('../../backend/img/factura_logo.png', 10, 10, 50);
        $this->SetXY(65, 15);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, mb_convert_encoding('HOSPITAL MEDICASA S. D R.L.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $this->SetXY(65, 20); 
        $this->Cell(0, 5, mb_convert_encoding('RTN : 08019995294814', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $this->SetXY(65, 25);
        $this->Cell(0, 5, mb_convert_encoding('Rango autorizado: 000-001-01-00382801 AL 000-001-01-00412800', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $this->SetXY(65, 30);
        $this->Cell(0, 5, mb_convert_encoding('Fecha límite de emisión: 04/08/2026', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $this->SetXY(65, 35);
        $this->Cell(0, 5, mb_convert_encoding('CAI: 3B8BA5-53CA59-8A06E0-63BE03-09091E-9A', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        
        // Espacio suficiente después del header para que el contenido no se monte
        $this->Ln(30); // Ajusta este valor según la altura real de tu header
    }

    function Footer() {
        $this->SetY(-40);
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 40);
    }
}

/* Crear un nuevo PDF con tamaño A3 */
$pdf = new PDF_MC('P', 'mm', 'A3');
$pdf->AddPage();

/* Agregar el logo */
$pdf->Image('../../backend/img/factura_logo.png', 10, 10, 50);

/* Mantener la posición del texto a la derecha del logo en la misma línea */
$pdf->SetXY(65, 15);
$pdf->SetFont('Arial', 'B', 12);

/* Añadir bloque de texto alineado a la izquierda */
$pdf->Cell(0, 5, mb_convert_encoding('HOSPITAL MEDICASA S. D R.L.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->SetXY(65, 20); 
$pdf->Cell(0, 5, mb_convert_encoding('RTN : 08019995294814', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->SetXY(65, 25);
$pdf->Cell(0, 5, mb_convert_encoding('Rango autorizado: 000-001-01-00382801 AL 000-001-01-00412800', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->SetXY(65, 30);
$pdf->Cell(0, 5, mb_convert_encoding('Fecha límite de emisión: 04/08/2026', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->SetXY(65, 35);
$pdf->Cell(0, 5, mb_convert_encoding('CAI: 3B8BA5-53CA59-8A06E0-63BE03-09091E-9A', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

/* Conexión a la base de datos */
require_once dirname(__DIR__, 2) . '/backend/bd/Conexion.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    exit('Solicitud inválida.');
}
$stmt = $connect->prepare("
SELECT 
    o.*, 
    od.cantidad, 
    od.codpro,
    od.discount_percentage,
    od.total_after_discount,
    od.age_discount_30,
    od.age_discount_40,
    od.promotion_discount,
    od.other_discount,
    CASE 
        WHEN od.item_type = 'producto' THEN p.nompro 
        WHEN od.item_type = 'servicio' THEN s.nombre_servicio 
    END AS descripcion,
    CASE 
        WHEN od.item_type = 'producto' THEN p.precio_venta 
        WHEN od.item_type = 'servicio' THEN s.total 
    END AS precio_unitario,
    CASE 
        WHEN od.item_type = 'producto' THEN p.impuesto 
        WHEN od.item_type = 'servicio' THEN s.impuesto 
    END AS impuesto
    FROM orders o
    JOIN order_details od ON o.idord = od.order_id
    LEFT JOIN product p ON p.idprcd = od.product_id AND od.item_type = 'producto'
    LEFT JOIN servicios_hospital s ON s.id = od.service_id AND od.item_type = 'servicio'
    WHERE o.idord = :id
");

$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute();

$order = $stmt->fetchAll();

if (!empty($order)) {
    /* Número de Factura Único */
    $invoice_number = $order[0]['invoice_number'];
    $dni_paciente = $order[0]['dni_paciente'];
    
    // Mostrar número de factura solo en la primera página
    if ($pdf->is_first_page) {
        $pdf->SetXY(-80, 15);
        $pdf->SetFont('Arial', 'B', 14); // Fuente en negrita
        $pdf->SetTextColor(0, 0, 0); // Texto en negro
        $pdf->Cell(20, 5, mb_convert_encoding('Factura Nº:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
        $pdf->Cell(0, 5, mb_convert_encoding($invoice_number, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L'); // Número en negrita y negro
        $pdf->is_first_page = false;
        
        /* Espaciado adicional después del número de factura */
        $pdf->Ln(14);
    }
    
    // Establecer el número de factura en el PDF después de obtenerlo de la base de datos
    $pdf->invoice_number = $invoice_number;
    
// Consultar el número de teléfono del paciente
$phoneStmt = $connect->prepare("SELECT phon FROM patients WHERE numhs = :numhs LIMIT 1");
$phoneStmt->bindParam(':numhs', $dni_paciente, PDO::PARAM_STR);
$phoneStmt->execute();
$phoneResult = $phoneStmt->fetch(PDO::FETCH_ASSOC);

// Validar si se encontró el teléfono
$telefonoPaciente = $phoneResult['phon'] ?? 'N/A';
    
/* Línea completa: paciente arriba del cuadro (ancho del bloque para nombre completo) */
$nombrePacientePdf = mb_convert_encoding(mb_strtoupper($order[0]['nomcl'], 'UTF-8'), 'ISO-8859-1', 'UTF-8');

/* Cuadro de 3 columnas: mismos datos que antes, sin PACIENTE en la rejilla */
$fields = [
    'DNI PACIENTE' => $dni_paciente,
    'NÚMERO DE CUENTA' => $order[0]['num_cuenta'],
    'FECHA DE INGRESO' => 'Modulo',
    'FECHA DE SALIDA' => 'Modulo',
    'FECHA DE FACTURA' => $order[0]['placed_on'],
    'COBERTURA DEL SEGURO' => '0.00',
    'N° DE POLIZA' => $order[0]['num_poliza'],
    'N° DE CERTIFICADO' => $dni_paciente,
    'PAGADOR' => $order[0]['pagador'],
    'METODO DE PAGO' => $order[0]['method'],
    'RTN PAGADOR' => $order[0]['rtn_pagador'],
    'EDAD PACIENTE' => $order[0]['edad'],
    'TIPO' => $order[0]['tipo'],
    'REMITENTE' => $order[0]['remitente'],
    'TELEFONO' => $telefonoPaciente,
];

/* Configuración para las columnas */
$pageWidth = $pdf->GetPageWidth();
$colWidth = 97; // Ancho de cada columna
$rowHeight = 5; // Altura base para filas
$startX = ($pageWidth - ($colWidth * 3)) / 2; // Centrado
$bloqueAncho = $colWidth * 3;
$anchoEtiquetaPaciente = $colWidth / 2;
$anchoNombrePaciente = $bloqueAncho - $anchoEtiquetaPaciente;

$startY = 58;
$pdf->SetXY($startX, $startY);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($anchoEtiquetaPaciente, 7, mb_convert_encoding('PACIENTE:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L', true);
$pdf->SetXY($startX + $anchoEtiquetaPaciente, $startY);
$pdf->SetFont('Arial', 'B', 11);
$pdf->MultiCell($anchoNombrePaciente, 6, $nombrePacientePdf, 0, 'L', false);
$startY = $pdf->GetY() + 4;

$pdf->SetFillColor(240, 240, 240);
$col = 0;

foreach ($fields as $label => $value) {
    $x = $startX + $col * $colWidth; // Posición X de la columna
    $y = $startY; // Mantener posición Y

    // Configurar fuentes para etiqueta y valor
    $pdf->SetFont('Arial', 'B', 8);
    $labelText = mb_convert_encoding(mb_strtoupper("$label:", 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $pdf->SetXY($x, $y);

    // Calcular altura necesaria para la celda del valor
    $pdf->SetFont('Arial', 'B', 8);
    $valueText = mb_convert_encoding(mb_strtoupper($value, 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $valueHeight = $pdf->GetStringWidth($valueText) > ($colWidth / 2) ? $rowHeight * ceil($pdf->GetStringWidth($valueText) / ($colWidth / 2)) : $rowHeight;

    // Determinar la altura máxima entre etiqueta y valor
    $cellHeight = max($rowHeight, $valueHeight);

    // Dibujar celda de etiqueta
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->MultiCell($colWidth / 2, $rowHeight, $labelText, 0, 'L', true);

    // Dibujar celda de valor
    $pdf->SetXY($x + ($colWidth / 2), $y);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->MultiCell($colWidth / 2, $rowHeight, $valueText, 0, 'L', false);

    $col++;
    if ($col == 3) { // Si se alcanzan 3 columnas, pasa a la siguiente fila
        $startY += $cellHeight; // Incrementar posición Y por la altura más alta
        $col = 0; // Reiniciar columna
    }
}

// Si hay información pendiente al finalizar las columnas
if ($col > 0) {
    $startY += $rowHeight; // Ajustar posición Y si queda contenido sin completar
}
    
    
    /* Segunda tabla: Detalle de transacciones */
    $pdf->Ln(15);
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);

/* Encabezados de la tabla */
$headers = ['Código', 'Impuesto', 'Descripción', 'Cantidad', 'Precio Unitario', 'Descuento', 'Total'];
$colWidths = [35, 20, 115, 25, 30, 30, 36]; // Ajustar el ancho de las columnas

$pdf->SetX($startX);
foreach ($headers as $i => $header) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell($colWidths[$i], 8, mb_convert_encoding($header, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
}
$pdf->Ln();
$pdf->SetTextColor(0, 0, 0);

/* Inicializar acumuladores */
$subtotal_general_sin_descuento = 0; // Subtotal sin descuentos
$subtotal_general_con_descuento = 0; // Subtotal con descuentos
$isv_15 = 0;                         // ISV acumulado (15%)
$descuento_general_total = 0;        // Total descuento general
$descuento_tercera_edad_total = 0;   // Total descuento tercera edad
$descuento_cuarta_edad_total = 0;    // Total descuento cuarta edad
$descuento_promocion_total = 0;      // Total descuento promoción
$descuento_otros_total = 0;          // Total otros descuentos
$exento_gravado_15 = 0;              // Total exento gravado al 15% (campo adicional)

$rowHeight = 8;
$footerHeight = 45;
$maxY = $pdf->GetPageHeight() - $footerHeight;

/* Procesar cada ítem */
foreach ($order as $item) {
    if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - $footerHeight) {
        $pdf->AddPage();
        imprimirEncabezadoTabla($pdf, $headers, $colWidths, $startX);
    }
    // Subtotal por ítem basado en cantidad y precio unitario
    $subtotal_item = $item['cantidad'] * $item['precio_unitario'];

    // Capturar descuentos del ítem
    $descuento_general = $item['discount_percentage'] ?? 0; // Descuento general del ítem
    $descuento_tercera_edad = $item['age_discount_30'] ?? 0;
    $descuento_cuarta_edad = $item['age_discount_40'] ?? 0;
    $descuento_promocion = $item['promotion_discount'] ?? 0;
    $descuento_otros = $item['other_discount'] ?? 0;

    // Total de descuentos aplicados
    $total_descuentos_item = $descuento_general + $descuento_tercera_edad + $descuento_cuarta_edad + $descuento_promocion + $descuento_otros;

    // Capturar total después de descuentos
    $total_after_discount = $item['total_after_discount'] ?? ($subtotal_item - $total_descuentos_item);

    // Calcular ISV solo si aplica (impuesto 'G')
    $isv_item = 0;
    if (strtoupper($item['impuesto']) === 'G') {
        $isv_item = bcmul($total_after_discount, '0.15', 2);
        $isv_15 += $isv_item; // Acumular ISV global

        // Acumular el total gravado (sin ISV pero aplicable al 15%)
        $exento_gravado_15 += $total_after_discount;
    }

    // Acumular subtotales y descuentos globales
    $subtotal_general_sin_descuento += $subtotal_item;
    $subtotal_general_con_descuento += $total_after_discount; // Total con descuentos aplicados
    $descuento_general_total += $descuento_general;
    $descuento_tercera_edad_total += $descuento_tercera_edad;
    $descuento_cuarta_edad_total += $descuento_cuarta_edad;
    $descuento_promocion_total += $descuento_promocion;
    $descuento_otros_total += $descuento_otros;

    // Mostrar la fila del ítem en la tabla del PDF
    $fila = [
        $item['codpro'], // Código
        $item['impuesto'], // Impuesto
        $item['descripcion'], // Descripción
        $item['cantidad'], // Cantidad
        'LPS. ' . number_format($item['precio_unitario'], 2), // Precio unitario
        'LPS. ' . number_format($descuento_general, 2), // Mostrar `total_discount` en esta columna
        'LPS. ' . number_format($total_after_discount, 2) // Mostrar `total_after_discount` en esta columna
    ];
    $pdf->SetX($startX); // Posición inicial para la tabla
    foreach ($fila as $i => $cell) {
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell($colWidths[$i], 8, mb_convert_encoding($cell, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    }
    $pdf->Ln(); // Salto de línea para la próxima fila
}

/* Totales y detalles adicionales alineados */
$pdf->Ln(10);
$pdf->SetX($startX);

// Calcular el total a pagar
$totalPagar = $subtotal_general_sin_descuento // Subtotal sin descuentos
    - $descuento_general_total                // Restar descuento general
    - $descuento_tercera_edad_total           // Restar descuento tercera edad
    - $descuento_cuarta_edad_total            // Restar descuento cuarta edad
    - $descuento_promocion_total              // Restar descuento por promoción
    - $descuento_otros_total                  // Restar otros descuentos
    + $isv_15;                                // Sumar ISV acumulado (15%)

// Mostrar totales en el PDF
$totals = [
    'TOTAL' => 'LPS. ' . number_format($subtotal_general_sin_descuento, 2),
    'DESCUENTO GENERAL' => 'LPS. ' . number_format($descuento_general_total, 2),
    'DESCUENTO TERCERA EDAD' => 'LPS. ' . number_format($descuento_tercera_edad_total, 2),
    'DESCUENTO CUARTA EDAD' => 'LPS. ' . number_format($descuento_cuarta_edad_total, 2),
    'DESCUENTO PROMOCIÓN' => 'LPS. ' . number_format($descuento_promocion_total, 2),
    'DESCUENTO OTROS' => 'LPS. ' . number_format($descuento_otros_total, 2),
    'SUB TOTAL' => 'LPS. ' . number_format($subtotal_general_con_descuento, 2),
    'MONTO GRAVADO' => 'LPS. ' . number_format($exento_gravado_15, 2),
    'I.S.V. 15%' => 'LPS. ' . number_format($isv_15, 2),
    'TOTAL A PAGAR' => 'LPS. ' . number_format($totalPagar, 2)
];

// Lista de etiquetas que deben estar en negrita
$bold_labels = ['TOTAL', 'SUB TOTAL', 'TOTAL A PAGAR'];

// --- Control de salto de página para totales y firma ---
$espacioTotalesFirma = 60; // mm, ajusta según lo que ocupe tu bloque de totales+firma
if ($pdf->GetY() + $espacioTotalesFirma > $pdf->GetPageHeight() - $footerHeight) {
    $pdf->AddPage();
    // Si quieres repetir el encabezado de la tabla en la nueva página, descomenta la siguiente línea:
    // imprimirEncabezadoTabla($pdf, $headers, $colWidths, $startX);
}

foreach ($totals as $label => $amount) {
    $pdf->SetX($startX);
    // Verificar si el texto debe estar en negrita
    if (in_array($label, $bold_labels)) {
        $pdf->SetFont('Arial', 'B', 11); // Negrita
    } else {
        $pdf->SetFont('Arial', '', 11); // Sin negrita
    }
    $pdf->Cell(225, 8, mb_convert_encoding($label, 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(66, 8, mb_convert_encoding($amount, 'ISO-8859-1', 'UTF-8'), 1, 1, 'R');
}


/* Función de números a letras con centavos expresados como fracción */
function numToWords($number) {
    $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    if ($number == 0) {
        return 'CERO LEMPIRAS 00/100';
    }

    $integerPart = floor($number); // Parte entera
    $decimalPart = round(($number - $integerPart) * 100); // Parte decimal

    $convertir = function($n) use ($unidades, $decenas, $centenas) {
        $resultado = '';

        if ($n == 0) {
            return '';
        }

        if ($n >= 100) {
            if ($n == 100) {
                return 'CIEN';
            }
            $resultado .= $centenas[floor($n / 100)] . ' ';
            $n %= 100;
        }

        if ($n >= 20) {
            $resultado .= $decenas[floor($n / 10)];
            $n %= 10;
            if ($n > 0) {
                $resultado .= ' Y ';
            }
        } elseif ($n >= 10) {
            $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
            return $resultado . $especiales[$n - 10];
        }

        if ($n > 0) {
            $resultado .= $unidades[$n];
        }

        return trim($resultado);
    };

    $processSection = function($number, $divider, $singular, $plural) use ($convertir) {
        $quantity = floor($number / $divider);
        if ($quantity > 0) {
            $words = $convertir($quantity);
            // Aquí evitamos agregar "UNO" antes de "MIL" cuando es 1000 o más
            if ($quantity == 1 && $singular == 'MIL') {
                return 'MIL';
            }
            return $words . ' ' . ($quantity > 1 ? $plural : $singular);
        }
        return '';
    };

    $millones = $processSection($integerPart, 1000000, 'MILLÓN', 'MILLONES');
    $integerPart %= 1000000;

    $miles = $processSection($integerPart, 1000, 'MIL', 'MIL');
    $integerPart %= 1000;

    $centenasYDecenas = $convertir($integerPart);

    $integerWords = trim("$millones $miles $centenasYDecenas");

    // Añadir centavos como fracción de 100
    $centavos = str_pad($decimalPart, 2, '0', STR_PAD_LEFT);

    if ($decimalPart > 0) {
        return strtoupper($integerWords) . ' LEMPIRAS CON ' . $centavos . '/100';
    }

    return strtoupper($integerWords) . ' LEMPIRAS 00/100';
}

/* Convertir el total a letras */
$totalEnLetras = numToWords($totalPagar);

/* Información adicional */
$pdf->Ln(5);
$additionalInfo = [
    "N° CORRELATIVO DE ORDEN DE COMPRA EXENTA ________________________________________________",
    "N° CORRELATIVO DE CONSTANCIA DE REGISTRO EXONERADO ________________________________________________",
    "N° IDENTIFICATIVO DEL REGISTRO DE LA SAG. ________________________________________________",
    "TOTAL A PAGAR: " . mb_convert_encoding($totalEnLetras, 'ISO-8859-1', 'UTF-8'),
    "LA FACTURA ES BENEFICIO DE TODOS \"EXIJALA\" "
];

$lineasAdicionales = count($additionalInfo);
$altoBloqueAdicional = $lineasAdicionales * 8; // 8mm por línea, ajusta si usas otro alto

if ($pdf->GetY() + $altoBloqueAdicional > $pdf->GetPageHeight() - $footerHeight) {
    $pdf->AddPage();
}

foreach ($additionalInfo as $index => $line) {
    $pdf->SetX($startX);

    if ($index === 3) { // Línea "TOTAL A PAGAR"
        $parts = explode(": ", $line);
        $label = $parts[0] . ": "; // "TOTAL A PAGAR: "
        $value = $parts[1];        // Total en letras

        // Ajustar el ancho del Cell para "TOTAL A PAGAR:"
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(35, 8, mb_convert_encoding($label, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');

        // Colocar el valor más cerca del texto "TOTAL A PAGAR:"
        $pdf->SetFont('Arial', 'BU', 15); // Negrita y subrayado
        $pdf->Cell(105, 8, mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    } elseif ($index < 3) {
        // Sin negrita para los primeros tres títulos
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(235, 8, mb_convert_encoding($line, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    } else {
        // Negrita para las demás líneas
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(235, 8, mb_convert_encoding($line, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    }
}

$signatureBlob = null;
$userIdRaw = $_SESSION['id'] ?? ($order[0]['user_id'] ?? null);
$userIdForSig = is_numeric($userIdRaw) ? (int) $userIdRaw : 0;
if ($userIdForSig > 0) {
    $signatureStmt = $connect->prepare("SELECT signature FROM user_signatures WHERE user_id = :user_id LIMIT 1");
    $signatureStmt->bindParam(':user_id', $userIdForSig, PDO::PARAM_INT);
    $signatureStmt->execute();
    $signatureRow = $signatureStmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($signatureRow) && isset($signatureRow['signature'])) {
        $signatureBlob = $signatureRow['signature'];
    }
}

// Espacio adicional antes de "FIRMA CAJERO"
$pdf->Ln(10);
$pdf->SetX($startX);
$pdf->SetFont('Arial', 'B', 10); // Cambiar a negrita

if ($signatureBlob) {
    // Mostrar el título "FIRMA CAJERO"
    $pdf->Cell(230, 8, mb_convert_encoding("FIRMA CAJERO", 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    
    // Crear un archivo temporal para almacenar la firma
    $tempImagePath = tempnam(sys_get_temp_dir(), 'sig') . '.png';
    file_put_contents($tempImagePath, $signatureBlob);

    // Validar si el archivo creado es una imagen válida
    if (@getimagesize($tempImagePath) !== false) {
        // Procesar la imagen para eliminar el fondo blanco
        $image = imagecreatefrompng($tempImagePath);
        $width = imagesx($image);
        $height = imagesy($image);
        $processedImage = imagecreatetruecolor($width, $height);
        imagesavealpha($processedImage, true);
        $transparentColor = imagecolorallocatealpha($processedImage, 0, 0, 0, 127);
        imagefill($processedImage, 0, 0, $transparentColor);

        // Quitar el fondo blanco
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($image, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                // Si el color es blanco (o cercano a blanco), hacerlo transparente
                if ($r > 200 && $g > 200 && $b > 200) {
                    imagesetpixel($processedImage, $x, $y, $transparentColor);
                } else {
                    imagesetpixel($processedImage, $x, $y, $color);
                }
            }
        }

        // Guardar la imagen procesada como PNG
        imagepng($processedImage, $tempImagePath);
        unset($image, $processedImage);

        // Obtener posición actual
        $currentY = $pdf->GetY();

        // Dibujar la firma sobre la línea
        $pdf->Image($tempImagePath, $startX + 5, $currentY - 5, 100, 50); // Ajustar tamaño y posición
        $pdf->Ln(20); // Espacio después de la firma

        // Eliminar archivo temporal
        unlink($tempImagePath);

        // Dibujar la línea debajo de la firma
        $pdf->SetX($startX);
        $pdf->Cell(230, 8, mb_convert_encoding("____________________________________________________", 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    } else {
        // Si no es una imagen válida, mostrar línea predeterminada
        unlink($tempImagePath); // Eliminar archivo inválido
        $pdf->Cell(230, 8, mb_convert_encoding("____________________________________________________", 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    }
} else {
    // Si no existe firma, mostrar título y línea por defecto
    $pdf->Cell(230, 8, mb_convert_encoding("FIRMA CAJERO ________________________________________________", 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
}


    /* Footer with Increased Height PNG */
    $pdf->SetY(-40);
    $pdf->Image('../../backend/img/footer_factura.png', 0, $pdf->GetY(), $pdf->GetPageWidth(), 40);
}

/* Formatear el nombre del paciente para el archivo */
$nombre_paciente = $order[0]['nomcl'];

// Convertir a mayúsculas y asegurar la codificación correcta
$nombre_paciente = mb_strtoupper($nombre_paciente, 'UTF-8');
$nombre_paciente = iconv('UTF-8', 'Windows-1252', $nombre_paciente);

// Crear el nombre del archivo
$nombre_archivo = sprintf('Factura %s.pdf', $nombre_paciente);

/* Salida del PDF */
$pdf->Output($nombre_archivo, 'D');

function imprimirEncabezadoTabla($pdf, $headers, $colWidths, $startX) {
    $pdf->SetX($startX);
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);
    foreach ($headers as $i => $header) {
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell($colWidths[$i], 8, mb_convert_encoding($header, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0, 0, 0);
}