<?php
require('../../backend/fpdf/fpdf.php');
require_once '../../backend/bd/Conexion.php';

function convertirACapitalAcentuadas($texto) {
    $reemplazos = ['á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó', 'ú' => 'Ú', 'ñ' => 'Ñ'];
    return strtr($texto, $reemplazos);
}

function generarPDFComercial($proveedorData) {
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
    $pdf->SetTextColor(6, 173, 191);
    $pdf->Cell(0, 8, mb_convert_encoding('FORMULARIO DE GESTIÓN DE PAGOS A PROVEEDORES COMERCIALES', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $pdf->Ln(5);

    // Datos del proveedor
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 8, mb_convert_encoding('Datos del Proveedor:', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 10);

    $col1Width = 80;
    $col2Width = 100;
    $datos = [
        'Nombre de la Empresa:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['nombre_empresa'], 'ISO-8859-1', 'UTF-8')),
        'Dirección:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['direccion'], 'ISO-8859-1', 'UTF-8')),
        'RTN Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['rtn_comercial'], 'ISO-8859-1', 'UTF-8')),
        'Teléfono Fijo:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['tel_fijo'], 'ISO-8859-1', 'UTF-8')),
        'Correo Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['correo_comercial'], 'ISO-8859-1', 'UTF-8')),
        'Celular WhatsApp:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['cel_whatsapp'], 'ISO-8859-1', 'UTF-8')),
        'Nombre Legal:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['nombre_legal'], 'ISO-8859-1', 'UTF-8')),
        'DNI Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['dni_comercial'], 'ISO-8859-1', 'UTF-8')),
        'Celular Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['cel_comercial'], 'ISO-8859-1', 'UTF-8')),
        'Cuenta BAC Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['cuenta_bac_comercial'], 'ISO-8859-1', 'UTF-8')),
        'Cuenta BAC Si:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['cuenta_bac_si'], 'ISO-8859-1', 'UTF-8')),
        'Cuenta BAC No:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['cuenta_bac_no'], 'ISO-8859-1', 'UTF-8')),
        'Tipo de Cuenta Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['tipo_cuenta_comercial'], 'ISO-8859-1', 'UTF-8')),
        'Nombre de Contacto:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['nom_contacto'], 'ISO-8859-1', 'UTF-8')),
        '1ra Referencia BAC Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['1_refbac_comercial'], 'ISO-8859-1', 'UTF-8')),
        'Tel. 1ra Referencia BAC Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['1_refbac_comercial_tel'], 'ISO-8859-1', 'UTF-8')),
        '2da Referencia BAC Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['2_refbac_comercial'], 'ISO-8859-1', 'UTF-8')),
        'Tel. 2da Referencia BAC Comercial:' => convertirACapitalAcentuadas(mb_convert_encoding($proveedorData['2_refbac_comercial_tel'], 'ISO-8859-1', 'UTF-8')),
    ];

    foreach ($datos as $titulo => $valor) {
        $pdf->SetX(10);
        $pdf->Cell($col1Width, 7, mb_convert_encoding($titulo, 'ISO-8859-1', 'UTF-8'), 0, 0);
        $pdf->Cell($col2Width, 7, mb_convert_encoding($valor, 'ISO-8859-1', 'UTF-8'), 0, 1);
    }
    
    // Información Importante
    $pdf->Ln(6);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 8, mb_convert_encoding('Información Importante:', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(3);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(255, 0, 0);

    $infoImportante = [
        mb_convert_encoding('1. Por seguridad, para acortar tiempos y para evitar la espera por gestiones manuales de pago, MEDICASA asegura su pago por transferencia bancaria.', 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding('2. Si tiene constancia de pagos a cuenta, NO se le hace la retención del 12.5%.', 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding('3. Favor verificar que los datos que nos proporcionó sean los correctos.', 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding('4. Una vez acreditado, se le enviaría notificación a su correo electrónico.', 'ISO-8859-1', 'UTF-8'),
    ];

    foreach ($infoImportante as $punto) {
        $pdf->MultiCell(0, 7, $punto);
        $pdf->Ln(1);
    }

// Espacio antes del título
$pdf->Ln(5); // Reducir el espacio antes del título
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(0, 0, 0); // Color negro
$pdf->Cell(0, 10, mb_convert_encoding('Firma Digital del Proveedor Comercial', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

$pdf->SetFont('Arial', '', 10); // Regresar a la fuente normal
$pdf->SetTextColor(0, 0, 0); // Asegurarse de que el texto de la firma también sea negro

// Firma digital imagen en el pie de página
$nombreEmpresa = $proveedorData['nombre_empresa'];
$rutaFirma = '../../uploads/firmas/' . $nombreEmpresa . '.png';

if (file_exists($rutaFirma)) {
    $pdf->Image($rutaFirma, 10, 270, 80); // Ajustar la posición vertical de la imagen
} else {
    $pdf->Cell(0, 10, 'Firma digital no disponible', 0, 1, 'L'); // Alineado a la izquierda
}

    $pdf->Output();
}

try {
    if (isset($_GET['nombre_empresa'])) {
        $nombreEmpresa = $_GET['nombre_empresa'];
        $sql = "SELECT * FROM proveedor_comercial WHERE nombre_empresa = :nombre_empresa";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':nombre_empresa', $nombreEmpresa);
        $stmt->execute();
        $proveedorData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($proveedorData) {
            generarPDFComercial($proveedorData);
        } else {
            echo "No se encontró proveedor con el nombre: " . htmlspecialchars($nombreEmpresa);
        }
    } else {
        echo "Nombre de empresa no proporcionado.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
