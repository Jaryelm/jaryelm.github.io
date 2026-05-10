<?php
require('../../backend/bd/Conexion.php');
require('../../backend/fpdf/fpdf.php');
session_start();

class PDFWithFooter extends FPDF
{
    function Footer()
    {
        // Posición a 15 mm del final de la página
        $this->SetY(-30);
        // Agregar imagen del footer
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 30);
    }
}

// Crear instancia del PDF con tamaño Letter
$pdf = new PDFWithFooter('P', 'mm', 'Letter');

// Obtener datos del paciente y solicitud
$idpa = $_GET['idpa'] ?? null;
$userId = $_SESSION['id'] ?? null;

if (!$idpa || !$userId) {
    die('Error: Falta ID del paciente o usuario no autenticado.');
}

// Obtener datos del paciente
$stmtPatient = $connect->prepare("SELECT p.nompa, p.apepa, p.numhs FROM patients p WHERE p.idpa = :idpa");
$stmtPatient->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtPatient->execute();
$patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die('Error: No se encontraron datos del paciente.');
}

// Obtener datos de la consulta (ingreso y egreso)
$stmtConsult = $connect->prepare("SELECT c.fecha_hora_ingreso, c.fecha_hora_egreso FROM consult c WHERE c.idpa = :idpa");
$stmtConsult->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtConsult->execute();
$consult = $stmtConsult->fetch(PDO::FETCH_ASSOC);

// Obtener motivo de la solicitud de alta
$stmtAlta = $connect->prepare("SELECT diagnostico, motivo FROM solicitud_alta WHERE idpa = :idpa");
$stmtAlta->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtAlta->execute();
$alta = $stmtAlta->fetch(PDO::FETCH_ASSOC);

if (!$alta) {
    die('Error: No se encontró el motivo de alta.');
}

// Generar PDF
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Image('../../backend/img/factura_logo.png', 10, 10, 50); // Agregar logo

$pdf->Ln(30);

// Título
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, mb_convert_encoding('SOLICITUD DE ALTA EXIGIDA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(10);

// Datos del paciente
$pdf->SetFont('Arial', '', 12);

// Construcción correcta del texto con negritas
$pdf->SetFont('Arial', '', 12);
$pdf->Write(8, mb_convert_encoding("Yo, ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'B', 12);
$pdf->Write(8, mb_convert_encoding("{$patient['nompa']} {$patient['apepa']}", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', '', 12);
$pdf->Write(8, mb_convert_encoding(", con identidad No.: ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'B', 12);
$pdf->Write(8, mb_convert_encoding("{$patient['numhs']}", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', '', 12);
$pdf->Write(8, mb_convert_encoding(", ingresado en este centro hospitalario en fecha: ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'B', 12);
$pdf->Write(8, mb_convert_encoding("{$consult['fecha_hora_ingreso']}", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', '', 12);
$pdf->Write(8, mb_convert_encoding(", Por diagnóstico de: ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'B', 12);
$pdf->Write(8, mb_convert_encoding("{$alta['diagnostico']}", 'ISO-8859-1', 'UTF-8'));

// 🔹 RESTABLECER LA FUENTE A NORMAL DESPUÉS DE LA ÚLTIMA FRASE EN NEGRITA
$pdf->SetFont('Arial', '', 12); 

$pdf->Ln(15);

// Declaración legal con motivo en negrita
$pdf->SetFont('Arial', '', 12);
$pdf->Write(8, mb_convert_encoding("Hago constar que mis familiares y yo hemos sido informados por el medico tratante de manera clara y precisa sobre mi estado de salud actual, detallando los posibles riesgos y complicaciones asociados a mi enfermedad. Estoy consciente de haber recibido atención médica, de enfermería y tratamiento adecuado durante mi tiempo de hospitalización. Por motivo de: ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'B', 12);
$pdf->Write(8, mb_convert_encoding("{$alta['motivo']}", 'ISO-8859-1', 'UTF-8'));

$pdf->Ln(15);

$pdf->SetFont('Arial', '', 12);
$pdf->Write(8, mb_convert_encoding("He tomado la decisión de solicitar el alta exigida, liberando de cualquier responsabilidad civil y penal al personal médico tratante y administrativo.", 'ISO-8859-1', 'UTF-8'));

$pdf->Ln(15);

// Configurar zona horaria local
date_default_timezone_set('America/Tegucigalpa');

// Obtener los componentes de la fecha
$dia = date('j'); // Día del mes sin ceros iniciales
$meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];
$mes = $meses[date('n') - 1]; // Obtener el nombre del mes
$anio = date('Y'); // Año en formato completo

// Texto dinámico para la fecha
$textoFecha = "Comayaguela, M.D.C. a los {$dia} días del mes de {$mes} del {$anio}.";

// Fecha y lugar
$pdf->MultiCell(0, 8, mb_convert_encoding($textoFecha, 'ISO-8859-1', 'UTF-8'), 0, 'J');
$pdf->Ln(15);

$pdf->Cell(95, 15, mb_convert_encoding("FIRMA Y HUELLA DEL PACIENTE", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
$pdf->Cell(95, 15, mb_convert_encoding("FIRMA Y HUELLA DEL FAMILIAR", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Cell(95, 20, mb_convert_encoding("__________________________", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
$pdf->Cell(95, 20, mb_convert_encoding("__________________________", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Agregar Footer dinámico
$pdf->Output('D', 'Solicitud Alta Exigida.pdf');
