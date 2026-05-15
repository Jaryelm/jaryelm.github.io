<?php
require_once('../../backend/bd/Conexion.php');
require_once('../../backend/fpdf/fpdf.php');

// Crear carpeta temp si no existe
$qrDir = '../../backend/temp/';
if (!file_exists($qrDir)) {
    mkdir($qrDir, 0777, true);
}

$report_id = $_GET['report_id'] ?? null;

if (!$report_id) {
    die('ID de informe no proporcionado');
}

// CONSULTA COMPLETA - TODOS LOS DATOS DE TRANSCRIPCIÓN
$stmt = $connect->prepare("
    SELECT 
        rt.*,
        w.patient_name,
        w.patient_id,
        w.study_description,
        w.modality,
        w.study_date,
        w.study_id,
        u.name AS transcriber_name
    FROM report_transcriptions rt
    INNER JOIN radiology_reports r ON rt.report_id = r.id
    INNER JOIN worklist w ON r.study_id = w.study_id
    LEFT JOIN users u ON rt.transcriber_id = u.id
    WHERE rt.report_id = ?
    AND rt.status = 'completed'
");
$stmt->execute([$report_id]);
$transcription = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transcription) {
    die('Transcripción completada no encontrada');
}

// Función para formatear fecha
function formatearFecha($fecha) {
    if (!$fecha) return '';
    $fechaObj = new DateTime($fecha);
    $meses = [
        1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
        5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
        9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
    ];
    return $fechaObj->format('j') . ' DE ' . $meses[(int)$fechaObj->format('n')] . ' DE ' . $fechaObj->format('Y');
}

// Función para limpiar y formatear nombre del paciente
function limpiarNombrePaciente($nombre) {
    // Eliminar caracteres especiales como ^, *, etc.
    $nombre = preg_replace('/[\^\\*]/', ' ', $nombre);
    
    // Eliminar espacios múltiples y trim
    $nombre = preg_replace('/\s+/', ' ', trim($nombre));
    
    // Convertir a formato título (primera letra de cada palabra en mayúscula)
    $nombre = ucwords(strtolower($nombre));
    
    return $nombre;
}

// Función para calcular edad desde la identidad del paciente
function calcularEdadDesdeIdentidad($identidad) {
    if (empty($identidad)) return 'N/A';
    
    // Buscar el patrón: XXXX-YYYY-ZZZZZ donde YYYY es el año de nacimiento
    if (preg_match('/^\d{4}-(\d{4})-\d{5}$/', $identidad, $matches)) {
        $anioNacimiento = (int)$matches[1];
        $anioActual = (int)date('Y');
        $edad = $anioActual - $anioNacimiento;
        
        // Validar que la edad sea razonable (entre 0 y 120 años)
        if ($edad >= 0 && $edad <= 120) {
            return $edad . ' años';
        }
    }
    
    return 'N/A';
}

class PDFWithFooter extends FPDF {
    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(120,120,120);
        $this->SetX(20);
        $this->Cell(0, 10, mb_convert_encoding('Este informe es confidencial y solo para uso médico.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    }
}

$pdf = new PDFWithFooter('P', 'mm', 'Letter');
$pdf->AddPage();

// HEADER PROFESIONAL CON LOGO Y DIRECCIÓN
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0,0,0);

// Dirección a la izquierda
$pdf->SetXY(20, 15);
$pdf->Cell(0, 5, mb_convert_encoding('Comayagüela 7ma ave. Entre 10 y 11 calle edificio 949', 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetX(20);
$pdf->Cell(0, 5, mb_convert_encoding('Esquina Opuesta Mercado Mama Chepa Tel. 2242-6281/2242-6272(1)', 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetX(20);
$pdf->Cell(0, 5, mb_convert_encoding('Honduras, Centro América', 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetX(20);
$pdf->Cell(0, 5, mb_convert_encoding('www.medicasa.hn', 'ISO-8859-1', 'UTF-8'), 0, 1);



// Logo a la derecha
$pdf->Image('../../backend/img/logo_medicasa.png', 145, 10, 45);

// TÍTULO PRINCIPAL
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(3, 92, 103); // Color #035c67
$pdf->SetXY(0, 45);
$pdf->Cell(216, 12, mb_convert_encoding($transcription['report_title'] ?? 'TRANSCRIPCIÓN', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

$pdf->Ln(5);

// DATOS DEL PACIENTE
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(0,0,0);
$pdf->SetXY(20, $pdf->GetY());

// Definir ancho fijo para las etiquetas
$labelWidth = 25;
$dataStartX = 20 + $labelWidth;

// NOMBRE
$pdf->SetX(20);
$pdf->Cell($labelWidth, 4, mb_convert_encoding('NOMBRE:', 'ISO-8859-1', 'UTF-8'), 0, 0);
$pdf->SetX($dataStartX);
$pdf->Cell(0, 4, mb_convert_encoding(limpiarNombrePaciente($transcription['patient_name']), 'ISO-8859-1', 'UTF-8'), 0, 1);

// EDAD
$pdf->SetX(20);
$pdf->Cell($labelWidth, 4, mb_convert_encoding('EDAD:', 'ISO-8859-1', 'UTF-8'), 0, 0);
$pdf->SetX($dataStartX);
$pdf->Cell(0, 4, mb_convert_encoding(calcularEdadDesdeIdentidad($transcription['patient_id']), 'ISO-8859-1', 'UTF-8'), 0, 1);

// IDENTIDAD
$pdf->SetX(20);
$pdf->Cell($labelWidth, 4, mb_convert_encoding('IDENTIDAD:', 'ISO-8859-1', 'UTF-8'), 0, 0);
$pdf->SetX($dataStartX);
$pdf->Cell(0, 4, mb_convert_encoding($transcription['patient_id'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 0, 1);

// FECHA
$pdf->SetX(20);
$pdf->Cell($labelWidth, 4, mb_convert_encoding('FECHA:', 'ISO-8859-1', 'UTF-8'), 0, 0);
$pdf->SetX($dataStartX);
$pdf->Cell(0, 4, mb_convert_encoding(formatearFecha($transcription['study_date']), 'ISO-8859-1', 'UTF-8'), 0, 1);

// Espacio de separación
$pdf->Ln(8);

// HISTORIA CLÍNICA
if (!empty($transcription['clinical_history'])) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(3, 92, 103);
    $pdf->SetX(20);
    $pdf->Cell(0, 10, mb_convert_encoding('INDICIO:', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetX(20);
    $pdf->SetRightMargin(20);
    $pdf->MultiCell(0, 6, mb_convert_encoding($transcription['clinical_history'], 'ISO-8859-1', 'UTF-8'), 0, 'J');
    $pdf->SetRightMargin(0);
    $pdf->Ln(8);
}

// HALLAZGOS
if (!empty($transcription['findings'])) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(3, 92, 103);
    $pdf->SetX(20);
    $pdf->Cell(0, 10, mb_convert_encoding('HALLAZGOS:', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetX(20);
    $pdf->SetRightMargin(20);
    $pdf->MultiCell(0, 6, mb_convert_encoding($transcription['findings'], 'ISO-8859-1', 'UTF-8'), 0, 'J');
    $pdf->SetRightMargin(0);
    $pdf->Ln(8);
}

// IMPRESIÓN DIAGNÓSTICA
if (!empty($transcription['impression'])) {
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetX(20);
    $pdf->SetRightMargin(20);
    $pdf->MultiCell(0, 6, mb_convert_encoding($transcription['impression'], 'ISO-8859-1', 'UTF-8'), 0, 'J');
    $pdf->SetRightMargin(0);
    $pdf->Ln(8);
}

// RECOMENDACIONES
if (!empty($transcription['comments'])) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(3, 92, 103);
    $pdf->SetX(20);
    $pdf->Cell(0, 10, mb_convert_encoding('RECOMENDACIONES:', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetX(20);
    $pdf->SetRightMargin(20);
    $pdf->MultiCell(0, 6, mb_convert_encoding($transcription['comments'], 'ISO-8859-1', 'UTF-8'), 0, 'J');
    $pdf->SetRightMargin(0);
    $pdf->Ln(8);
}



// Espacio antes del QR
$pdf->Ln(10);

// GENERAR CÓDIGO QR PARA EL ESTUDIO
$qrUrl = 'https://dev:Mrecords7@medicloud.medicasa.hn/orthanc/studies/' . $transcription['study_id'] . '/archive';
$qrFile = $qrDir . 'qr_' . $transcription['id'] . '.png';
require_once('../../backend/phpqrcode/qrlib.php');
QRcode::png($qrUrl, $qrFile, QR_ECLEVEL_L, 10);

// Insertar QR en esquina inferior derecha
$pageWidth = 216; // Ancho fijo de página Letter en mm
$qrSize = 30;
$qrX = $pageWidth - 50; // Posición X del QR
$qrY = 279 - 50; // Posición Y del QR (altura Letter - margen)
$pdf->Image($qrFile, $qrX, $qrY, $qrSize, $qrSize);

// Texto "QR Estudio" centrado sobre el QR
$pdf->SetXY($qrX, $qrY - 8);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(6, 173, 191);
$pdf->Cell($qrSize, 5, mb_convert_encoding('QR Estudio', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Eliminar archivo QR temporal
unlink($qrFile);

// Generar nombre del archivo
$filename = 'Informe_' . limpiarNombrePaciente($transcription['patient_name']) . '_' . date('Y-m-d', strtotime($transcription['study_date'])) . '.pdf';
$filename = str_replace(' ', '_', $filename);

$pdf->Output('D', $filename);
exit;
?>
