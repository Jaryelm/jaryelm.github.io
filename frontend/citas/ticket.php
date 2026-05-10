<?php
require('../../backend/fpdf/fpdf.php');
date_default_timezone_set('America/Tegucigalpa');

// Podemos definir el ancho en una variable para que no les cueste cambiarlo después
$ancho = 5;

// Definimos la orientación de la página y el array indica el tamaño de la hoja
$pdf = new FPDF('P', 'mm', array(80, 150));
$pdf->AddPage(); 
$pdf->SetFont('Arial', 'B', 10);

$pdf->setY(2);
$pdf->setX(15);

// Agregar el logo centrado
$pdf->Image('../../backend/img/logo_medicasa.png', 25, 10, 30);
$pdf->Ln(30);  // Ajustar el espacio después del logo

$pdf->Cell(60, $ancho, mb_convert_encoding('CITA', 'ISO-8859-1', 'UTF-8'), 'B', 0, 'C');
$pdf->Ln(6);
$pdf->SetFont('Arial', '', 8);   

$pdf->setX(5);

// Encabezado
$pdf->Cell(15, $ancho + 10, mb_convert_encoding('Paciente:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C', 0);
$pdf->Cell(-17, $ancho + 35, mb_convert_encoding('Médico:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C', 0);

// Datos
$pdf->setX(5);

require '../../backend/bd/Conexion.php';
$id = $_GET['id'];
$stmt = $connect->prepare("SELECT events.id, events.title, patients.idpa, patients.numhs,patients.nompa, patients.apepa, doctor.idodc, doctor.ceddoc, doctor.nodoc, doctor.apdoc, laboratory.idlab, laboratory.nomlab, events.start, events.end, events.color, events.state,events.chec,events.monto FROM events INNER JOIN patients ON events.idpa = patients.idpa INNER JOIN doctor ON events.idodc = doctor.idodc INNER JOIN laboratory ON events.idlab = laboratory.idlab WHERE events.id= '$id'");
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute();

while ($row = $stmt->fetch()) {

    $pdf->Cell(45, 25, mb_convert_encoding($row['nompa'] . "\n" . $row['apepa'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'C', 0);
    $pdf->Cell(-45, 50, mb_convert_encoding($row['nodoc'] . "\n" . $row['apdoc'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'C', 0);

    $pdf->Ln(5);

    // Total
    $pdf->setX(5.5);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(45, 60, 'TOTAL:', 0, 0, 'L', 0);
    
    $pdf->setX(20);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(55, 60, 'LPS ' . ($row['monto']));
}

$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 8);
$pdf->setX(18.5);
$pdf->Cell(5, 2 + 55, mb_convert_encoding('¡POR TI ABRAZAMOS LA VIDA!', 'ISO-8859-1', 'UTF-8'));
$pdf->setX(19.7);
$pdf->Cell(5, 2 + 65, mb_convert_encoding('¡GRACIAS POR SU COMPRA!', 'ISO-8859-1', 'UTF-8'));

$pdf->Output('ticket.pdf', 'D');