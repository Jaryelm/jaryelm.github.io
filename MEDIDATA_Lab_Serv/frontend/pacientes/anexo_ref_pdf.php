<?php
require('../../backend/bd/Conexion.php');
require('../../backend/fpdf/fpdf.php');

class PDFWithFooter extends FPDF
{
    function Footer()
    {
        $this->SetY(-30);
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 30);
    }
}

date_default_timezone_set('America/Tegucigalpa');

if (!isset($_GET['idpa']) || empty($_GET['idpa'])) {
    die('Error: ID del paciente no proporcionado.');
}

$idpa = intval($_GET['idpa']);

// 🔹 Obtener datos del paciente
$stmtPatient = $connect->prepare("
    SELECT 
        CONCAT(patients.nompa, ' ', patients.apepa) AS full_name,
        patients.numhs AS dni,
        patients.cump AS fecha_nacimiento,
        patients.sex AS sexo,
        patients.resnamp AS responsable,
        TIMESTAMPDIFF(YEAR, patients.cump, CURDATE()) AS edad
    FROM patients WHERE patients.idpa = :idpa
");
$stmtPatient->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtPatient->execute();
$patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die('Error: Paciente no encontrado.');
}

// 🔹 Obtener datos de referencia
$stmtReferencia = $connect->prepare("
    SELECT * FROM anexo_referencia WHERE idpa = :idpa ORDER BY fecha_registro DESC LIMIT 1
");
$stmtReferencia->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtReferencia->execute();
$referencia = $stmtReferencia->fetch(PDO::FETCH_ASSOC);

if (!$referencia) {
    die('Error: No se encontró un anexo de referencia.');
}

// 🔹 Obtener la firma digital del médico
$userId = $_SESSION['id'] ?? null;
$signatureBlob = null;

if ($userId) {
    $signatureStmt = $connect->prepare("SELECT signature FROM user_signatures WHERE user_id = :user_id LIMIT 1");
    $signatureStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $signatureStmt->execute();
    $signatureResult = $signatureStmt->fetch(PDO::FETCH_ASSOC);
    $signatureBlob = $signatureResult['signature'] ?? null;
}

// 🔹 Crear PDF
$pdf = new PDFWithFooter('P', 'mm', 'Letter');
$pdf->AddPage();

// 🔹 Logo y título
$pdf->Image('../../backend/img/factura_logo.png', 10, 10, 50);
$pdf->Ln(30);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, mb_convert_encoding('HOJA DE REFERENCIA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(10);

// 🔹 Información del paciente
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(90, 7, mb_convert_encoding("Nombre del Paciente: ", 'ISO-8859-1', 'UTF-8') . mb_convert_encoding($patient['full_name'], 'ISO-8859-1', 'UTF-8'), 0, 0);
$pdf->Cell(40, 7, mb_convert_encoding("Edad: ", 'ISO-8859-1', 'UTF-8') . $patient['edad'], 0, 0);
$pdf->Cell(50, 7, mb_convert_encoding("Fecha: ", 'ISO-8859-1', 'UTF-8') . $referencia['fecha_registro'], 0, 1);
$pdf->Cell(90, 7, mb_convert_encoding("DNI: ", 'ISO-8859-1', 'UTF-8') . $patient['dni'], 0, 0);
$pdf->Cell(40, 7, mb_convert_encoding("Sexo: ", 'ISO-8859-1', 'UTF-8') . mb_convert_encoding($patient['sexo'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 0, 0);
$pdf->Cell(50, 7, mb_convert_encoding("Hora: ", 'ISO-8859-1', 'UTF-8') . $referencia['hora_registro'], 0, 1);
$pdf->Cell(90, 7, mb_convert_encoding("Responsable: ", 'ISO-8859-1', 'UTF-8') . mb_convert_encoding($patient['responsable'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 0, 0);
$pdf->Cell(50, 7, mb_convert_encoding("Parentesco: ____________________________", 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->Ln(5);

// 🔹 Información del médico y hospital de referencia
$pdf->Cell(90, 7, mb_convert_encoding("Médico que hace la Referencia: ", 'ISO-8859-1', 'UTF-8') . mb_convert_encoding($referencia['medico_ref'], 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->Cell(90, 7, mb_convert_encoding("Hospital de Referencia: ", 'ISO-8859-1', 'UTF-8') . mb_convert_encoding($referencia['hospital_ref'], 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->Cell(90, 7, mb_convert_encoding("Servicio al que se Refirió: ", 'ISO-8859-1', 'UTF-8') . mb_convert_encoding($referencia['servicio_ref'], 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->Ln(5);

// 🔹 Resumen Clínico
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding("RESUMEN CLÍNICO:", 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 7, mb_convert_encoding($referencia['resumen_clinico'], 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln(5);

// 🔹 Diagnóstico de Referencia
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding("DIAGNÓSTICO DE REFERENCIA:", 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 7, mb_convert_encoding($referencia['diagnostico_ref'], 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln(5);

// 🔹 Condición Clínica
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding("CONDICIÓN CLÍNICA INMEDIATA:", 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 7, mb_convert_encoding("Temperatura: ", 'ISO-8859-1', 'UTF-8') . $referencia['temperatura_ref'], 1, 0);
$pdf->Cell(45, 7, mb_convert_encoding("F.C.: ", 'ISO-8859-1', 'UTF-8') . $referencia['fc_ref'], 1, 0);
$pdf->Cell(50, 7, mb_convert_encoding("F.R.: ", 'ISO-8859-1', 'UTF-8') . $referencia['fr_ref'], 1, 0);
$pdf->Cell(51, 7, mb_convert_encoding("T.A.: ", 'ISO-8859-1', 'UTF-8') . $referencia['ta_ref'], 1, 1);
$pdf->Cell(50, 7, mb_convert_encoding("Llenado Capilar: ", 'ISO-8859-1', 'UTF-8') . $referencia['llenado_capilar'], 1, 0);
$pdf->Cell(50, 7, mb_convert_encoding("SPO2: ", 'ISO-8859-1', 'UTF-8') . $referencia['spo2_ref'], 1, 0);
$pdf->Cell(96, 7, mb_convert_encoding("Escala de Glasgow: ", 'ISO-8859-1', 'UTF-8') . $referencia['escala_glasgow_ref'], 1, 1);
$pdf->Ln(20);

// 🔹 Firma del médico y quien recibe
$pdf->Ln(20);
$pdf->SetX(10);

if ($signatureBlob) {
    // 📌 Guardar la firma en un archivo temporal
    $tempImagePath = tempnam(sys_get_temp_dir(), 'sig') . '.png';
    file_put_contents($tempImagePath, $signatureBlob);

    // Verificar si el archivo es una imagen válida
    if (@getimagesize($tempImagePath) !== false) {
        // 📌 Posicionar la firma justo encima de la línea
        $pdf->Image($tempImagePath, 30, $pdf->GetY() - 20, 60, 30);
        unlink($tempImagePath);
    }
}

// 🔹 Firma del médico y quien recibe
$pdf->Cell(95, 15, "_____________________________", 0, 0, 'C');
$pdf->Cell(95, 15, "_____________________________", 0, 1, 'C');
$pdf->Cell(95, 7, mb_convert_encoding("Firma y Sello del Médico que Refiere", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
$pdf->Cell(95, 7, mb_convert_encoding("Firma de quien Recibe", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// 🔹 Generar PDF
$pdf->Output('D', 'Hoja Referencia.pdf');
?>
