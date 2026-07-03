<?php 
session_start();
require '../../backend/fpdf/fpdf.php';
date_default_timezone_set('America/Tegucigalpa');

class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        $this->setY(12);
        $this->setX(10);
        $this->Image('../../backend/img/logo_medicasa.png', 25, 5, 33);
        $this->SetFont('times', 'B', 13);
        $this->Text(75, 15, mb_convert_encoding('Hospital MEDICASA', 'ISO-8859-1', 'UTF-8'));
        $this->Text(75, 20, mb_convert_encoding('Dr(a): ' . $_SESSION['name'], 'ISO-8859-1', 'UTF-8'));
        $this->Text(75, 25, mb_convert_encoding('Tel: +504 2242-6271 - 2242-6272', 'ISO-8859-1', 'UTF-8'));
        $this->Text(75, 30, mb_convert_encoding('atencionalcliente@medicasa.hn', 'ISO-8859-1', 'UTF-8'));
        $this->Image('../../backend/img/icon.png', 160, 5, 33);

        $this->SetFont('Arial', 'B', 10);
        $this->Text(10, 48, mb_convert_encoding('Fecha:', 'ISO-8859-1', 'UTF-8'));
        $this->SetFont('Arial', '', 10);
        $this->Text(25, 48, date('d/m/Y'));

        $this->SetFont('Arial', 'B', 10);
        $this->Text(10, 54, mb_convert_encoding('Médico:', 'ISO-8859-1', 'UTF-8'));
        $this->SetFont('Arial', '', 10);
        $this->Text(30, 54, mb_convert_encoding($_SESSION['name'], 'ISO-8859-1', 'UTF-8'));

        $this->Ln(50);
    }

    // Pie de página
    function Footer()
    {
        $this->SetFont('helvetica', 'B', 8);
        $this->SetY(-15);
        $this->Cell(95, 5, mb_convert_encoding('Página ' . $this->PageNo() . ' / {nb}', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Cell(95, 5, date('d/m/Y | g:i:a'), 0, 1, 'R');
        $this->Line(10, 287, 200, 287);
        $this->Cell(0, 5, mb_convert_encoding('MEDICASA © Todos los Derechos Reservados.', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetAutoPageBreak(true, 20);
$pdf->SetTopMargin(15);
$pdf->SetLeftMargin(10);
$pdf->SetRightMargin(10);

$pdf->setY(60);
$pdf->setX(135);
$pdf->Ln();

// Encabezados de la tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(65, 7, mb_convert_encoding('Motivo', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', 0);
$pdf->Cell(55, 7, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', 0);
$pdf->Cell(70, 7, mb_convert_encoding('Fecha', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', 0);
$pdf->SetFont('Arial', '', 10);

// Conexión y consulta a la base de datos
require '../../backend/bd/Conexion.php';
$id = $_GET['id'];
$stmt = $connect->prepare("SELECT events.id, events.title, patients.nompa, patients.apepa, events.start, events.end, events.monto FROM events INNER JOIN patients ON events.idpa = patients.idpa WHERE events.id = ?");
$stmt->bindParam(1, $id);
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute();

// Imprimir los datos de la consulta
while ($row = $stmt->fetch()) {
    $pdf->Cell(65, 7, mb_convert_encoding($row['title'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', 0);
    $pdf->Cell(55, 7, mb_convert_encoding($row['nompa'] . "\n" . $row['apepa'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', 0);
    $pdf->Cell(70, 7, mb_convert_encoding($row['start'] . "\n" . $row['end'], 'ISO-8859-1', 'UTF-8'), 1, 1, 'R', 0);

    // Subtotales y totales
    $pdf->Ln(50);
    $pdf->setX(95);
    $pdf->Cell(40, 6, 'Subtotal', 1, 0);
    $pdf->Cell(60, 6, 'LPS ' . $row['monto'], 1, 1, 'R');
    $pdf->setX(95);
    $pdf->Cell(40, 6, 'Total', 1, 0);
    $pdf->Cell(60, 6, 'LPS ' . $row['monto'], 1, 1, 'R');
}

$pdf->Output('boleta.pdf', 'D');