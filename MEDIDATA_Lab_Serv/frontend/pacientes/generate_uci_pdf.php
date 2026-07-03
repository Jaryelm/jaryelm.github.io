<?php
require('../../backend/bd/Conexion.php');
require('../../backend/fpdf/fpdf.php');
session_start();

class PDFWithFooter extends FPDF {
    function Header() {
        // Ubicar el logo a (X=10, Y=10)
        $this->Image('../../backend/img/factura_logo.png', 10, 10, 50);
        // Ajustar posición Y=30 para empezar el texto
        $this->SetY(40);
    }

    function Footer() {
        // Pie de página 30 mm desde el final
        $this->SetY(-30);
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 30);
    }

    // Verificar salto de página para evitar sobreponer el footer
    function CheckPageBreak($height) {
        $marginBottom = 50; // Espacio seguro
        if ($this->GetY() + $height + $marginBottom > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }
}

date_default_timezone_set('America/Tegucigalpa');

// Se espera recibir ?id=XYZ
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Error: ID del registro no proporcionado.');
}

$id = intval($_GET['id']);

// Consulta el registro individual
$stmtRecord = $connect->prepare("SELECT * FROM cuidados_intensivos WHERE id = :id");
$stmtRecord->bindParam(':id', $id, PDO::PARAM_INT);
$stmtRecord->execute();
$record = $stmtRecord->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    die('Error: Registro no encontrado.');
}

// Crear PDF
$pdf = new PDFWithFooter('P','mm','Letter');
$pdf->AddPage();

// Título Principal
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,5, mb_convert_encoding("Reporte UCI - Unidad de Cuidados Intensivos e Intermedios", 'ISO-8859-1','UTF-8'), 0, 1, 'C');
$pdf->Ln();

/**
 * Función auxiliar para dibujar una "mini-tabla" de dos columnas (etiqueta – valor)
 * @param FPDF $pdf
 * @param array $data  (clave => valor)
 * @param string $title  Título de la sección
 */
function drawTwoColumnTable($pdf, $data, $title) {
    if (!empty($title)) {
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(0,8, mb_convert_encoding($title, 'ISO-8859-1','UTF-8'), 0, 1, 'L');
    }
    $pdf->SetFont('Arial','',10);
    $pdf->Ln(2);

    foreach($data as $label => $value) {
        // Verificar si el contenido cabe sin sobreponer el footer
        $pdf->CheckPageBreak(8);

        // Etiqueta
        $pdf->Cell(60, 8, mb_convert_encoding($label . ":", 'ISO-8859-1','UTF-8'), 1, 0, 'L');
        // Valor
        $pdf->Cell(0, 8, mb_convert_encoding($value, 'ISO-8859-1','UTF-8'), 1, 1, 'L');
    }
    $pdf->Ln(5);
}

/* ===========================
   1) DATOS GENERALES
   =========================== */
$datosGenerales = [
    'Nombre del Paciente' => isset($record['nombre_paciente']) && $record['nombre_paciente'] !== '' ? $record['nombre_paciente'] : 'No registrado',
    'Edad'                => isset($record['edad']) && $record['edad'] !== '' ? $record['edad'] : 'No registrado',
    'Fecha de Registro'   => isset($record['fecha_registro']) && $record['fecha_registro'] !== '' ? $record['fecha_registro'] : 'No registrado',
    'DX'                  => isset($record['dx_paciente']) && $record['dx_paciente'] !== '' ? $record['dx_paciente'] : 'No registrado',
    'Médico'              => isset($record['medico_paciente']) && $record['medico_paciente'] !== '' ? $record['medico_paciente'] : 'No registrado',
];
drawTwoColumnTable($pdf, $datosGenerales, "DATOS GENERALES");

/* ===========================
   2) SIGNOS VITALES
   =========================== */
$signosVitales = [
    'Presión Arterial'       => $record['presion_arterial']       ?? 'No registrado',
    'Frecuencia Cardiaca'     => $record['frecuencia_cardiaca']    ?? 'No registrado',
    'Frecuencia Respiratoria' => $record['frecuencia_respiratoria']?? 'No registrado',
    'Temperatura (°C)'        => $record['temperatura']            ?? 'No registrado',
    'Saturación (%)'          => $record['saturacion']             ?? 'No registrado',
    'PVC'                     => $record['pvc']                    ?? 'No registrado',
    'PIC'                     => $record['pic']                    ?? 'No registrado',
    'PIA'                     => $record['pia']                    ?? 'No registrado',
    'Glucometría'             => $record['glucometria']            ?? 'No registrado',
];
drawTwoColumnTable($pdf, $signosVitales, "SIGNOS VITALES");

/* ===========================
   3) SOLUCIONES ENDOVENOSAS
   =========================== */
$soluciones = [
    'Soluciones Endovenosas' => $record['soluciones_endovenosas'] ?? 'No registrado',
];
drawTwoColumnTable($pdf, $soluciones, "SOLUCIONES ENDOVENOSAS");

/* ===========================
   4) MEDICACIÓN (Placeholder)
   =========================== */
// Si aún no existe un campo en la tabla, puedes usar un valor fijo
$medicacion = [
    'Medicación' => 'Pendiente de implementar', // O 'No registrado'
];
drawTwoColumnTable($pdf, $medicacion, "MEDICACIÓN");

/* ===========================
   5) INGESTAS
   =========================== */
$ingestas = [
    'Agua Endógena'   => $record['agua_endogena']   ?? 'No registrado',
    'Alimentación'    => $record['alimentacion']    ?? 'No registrado',
    'Hemoderivados'   => $record['hemoderivados']   ?? 'No registrado',
];
drawTwoColumnTable($pdf, $ingestas, "INGESTAS");

/* ===========================
   6) EXCRETAS
   =========================== */
$excretas = [
    'Pérdidas Insensibles' => $record['perdidas_insensibles'] ?? 'No registrado',
    'Residuo Gástrico'     => $record['residuo_gastrico']     ?? 'No registrado',
    'Hemovac'              => $record['hemovac']              ?? 'No registrado',
    'Succión/Drenos'       => $record['succion_drenos']       ?? 'No registrado',
    'Vómitos/SNG'          => $record['vomitos_sng']          ?? 'No registrado',
    'Heces'                => $record['heces']                ?? 'No registrado',
    'Diuresis por'         => $record['diuresis_por']         ?? 'No registrado',
    'Diuresis Acumulada'   => $record['diuresis_acumulada']   ?? 'No registrado',
];
drawTwoColumnTable($pdf, $excretas, "EXCRETAS");

// Salida del PDF
$pdf->Output('D', 'UCI Registro_'.$id.'.pdf');
