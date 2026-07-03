<?php
require('../../backend/fpdf/fpdf.php');
// Conexión a la base de datos
require_once '../../backend/bd/Conexion.php';

function convertirACapitalAcentuadas($texto) {
    $reemplazos = [
        'á' => 'Á',
        'é' => 'É',
        'í' => 'Í',
        'ó' => 'Ó',
        'ú' => 'Ú',
        'ñ' => 'Ñ',
        // Agrega más letras si es necesario
    ];
    return strtr($texto, $reemplazos);
}

// Función para convertir números a letras
function num2letras($number) {
    $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
    $numberInWords = $formatter->format(floor($number)); // Parte entera

    // Extraer la parte decimal con dos dígitos de precisión
    $centavos = round(($number - floor($number)) * 100);

    if ($centavos > 0) {
        // Convertir la parte decimal a letras
        $centavosInWords = $formatter->format($centavos);
        return ucfirst($numberInWords) . " lempiras con " . $centavosInWords . " centavos.";
    } else {
        return ucfirst($numberInWords) . " lempiras.";
    }
}

// Función para convertir fecha a formato textual
function formatFecha($fecha) {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    $date = new DateTime($fecha);
    $dia = $date->format('j');
    $mes = $meses[(int)$date->format('n')];
    $anio = $date->format('Y');
    return "$dia de $mes $anio";
}

class PDF extends FPDF {
    // Encabezado del PDF
    function Header() {
        $pageWidth = $this->w;
        $logoWidth = 30;
        $x = ($pageWidth - $logoWidth) / 2;
        $this->Image('../../backend/img/logo_medicasa.png', $x, 10, $logoWidth);
        $this->Ln(20);
    }

    // Pie de página del PDF
    function Footer() {
        $this->SetY(-40);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, mb_convert_encoding('Numero DNI: ___________________________', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $totalWidth = $this->w - 20;
        $fieldWidth = ($totalWidth - 20) / 3;
        $this->SetX(10);
        $this->Cell($fieldWidth, 10, mb_convert_encoding('Recibido por: ___________________________', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->SetX(10 + 1.1 * $fieldWidth);
        $this->Cell($fieldWidth, 10, mb_convert_encoding('Fecha: ___________________________', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
        $this->SetX(10 + 2.4 * $fieldWidth);
        $this->Cell($fieldWidth, 10, mb_convert_encoding('Firma del autorizado: ___________________________', 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
        $this->SetXY(10, $this->GetY() + 10);
        $this->Cell(190, 10, mb_convert_encoding('Comayaguela M.D.C. ' . formatFecha(date('Y-m-d')), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    }
}

try {
    // Validar si se pasa el parámetro chequeNo
    if (isset($_GET['chequeNo'])) {
        $chequeNo = $_GET['chequeNo'];

        // Consulta a la base de datos para obtener los datos del cheque
        $sql = "SELECT * FROM emitir_cheques WHERE cheque_no = :chequeNo";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':chequeNo', $chequeNo, PDO::PARAM_STR);
        $stmt->execute();
        $cheque = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cheque) {
            $cuenta = $cheque['cuenta'];
            $asignar_monto = $cheque['asignar_monto'];

            $sqlCuentaCatalogo = "SELECT nombre FROM cuentas_catalogo WHERE cuenta = :cuenta";
            $stmtCuentaCatalogo = $connect->prepare($sqlCuentaCatalogo);
            $stmtCuentaCatalogo->bindParam(':cuenta', $cuenta, PDO::PARAM_STR);
            $stmtCuentaCatalogo->execute();
            $cuentaCatalogo = $stmtCuentaCatalogo->fetch(PDO::FETCH_ASSOC);

            $nombreCuenta = $cuentaCatalogo ? $cuentaCatalogo['nombre'] : 'Nombre no encontrado';

            $sqlAsignarMontoCatalogo = "SELECT nombre FROM cuentas_catalogo WHERE cuenta = :asignarMonto";
            $stmtAsignarMontoCatalogo = $connect->prepare($sqlAsignarMontoCatalogo);
            $stmtAsignarMontoCatalogo->bindParam(':asignarMonto', $asignar_monto, PDO::PARAM_STR);
            $stmtAsignarMontoCatalogo->execute();
            $asignarMontoCatalogo = $stmtAsignarMontoCatalogo->fetch(PDO::FETCH_ASSOC);

            $nombreAsignarMonto = $asignarMontoCatalogo ? $asignarMontoCatalogo['nombre'] : 'Nombre no encontrado';

// Crear nuevo PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Título cheque emitido (fuera de la caja)
$pdf->SetXY(10, 25); // Cambia la posición del primer título
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(190, 10, mb_convert_encoding('DESCRIPCIÓN CHEQUE EMITIDO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Información del cheque
//$pdf->Ln(5);
//$pdf->SetXY(25, 45); // Posición del cheque No
//$pdf->SetFont('Arial', 'B', 10);
//$pdf->Cell(30, 8, mb_convert_encoding('Cheque No:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
//$pdf->SetFont('Arial', '', 10);
//$pdf->Cell(40, 8, $cheque['cheque_no'], 0, 1, 'L');

// Duplicar el campo de la fecha y colocarlo 1 cm arriba del monto
$pdf->SetXY(120, 53); // Nueva posición 1 cm arriba del monto
$pdf->SetFont('Arial', 'I', 10); // Cambia la fuente a cursiva para distinguirla
$pdf->Cell(40, 8, mb_convert_encoding('Comayaguela M.D.C. ' . formatFecha(date('Y-m-d')), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

// Nueva posición para el monto
$pdf->SetXY(165, 69); // Moved 2 cm down and 1 cm right
$pdf->SetFont('Arial', 'B', 10);
//$pdf->Cell(30, 8, mb_convert_encoding('Monto:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(40, 8, 'LPS. ' . number_format($cheque['monto'], 2), 0, 1, 'L');

// Nueva posición para "Pagar a:"
$pdf->SetXY(15, 71); // Moved 3 cm down and 1 cm right
$pdf->SetFont('Arial', 'B', 10);
//$pdf->Cell(30, 8, mb_convert_encoding('Pagar a:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(40, 8, mb_convert_encoding($cheque['pagar'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

//$pdf->SetXY(90, 55); // Posición del concepto
//$pdf->SetFont('Arial', 'B', 10);
//$pdf->Cell(30, 8, mb_convert_encoding('Concepto:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
//$pdf->SetFont('Arial', '', 10);
//$pdf->Cell(40, 8, mb_convert_encoding($cheque['concepto'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

// Monto en letras
$nextY = $pdf->GetY();
$pdf->SetXY(15, $nextY + 8 - 3); // Mover medio cm (5 mm) hacia arriba
$pdf->SetFont('Arial', 'B', 10);
//$pdf->Cell(30, 8, mb_convert_encoding('Monto en letras:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$montoEnLetras = num2letras($cheque['monto']);
$pdf->MultiCell(150, 8, mb_convert_encoding('*** ' . $montoEnLetras . ' ***', 'ISO-8859-1', 'UTF-8'));
$pdf->Ln(20);

// Título VAUCHER DESCRIPCIÓN
$pdf->SetY(137); // Ajusta la posición Y del título VAUCHER DESCRIPCIÓN
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(190, 10, mb_convert_encoding('VAUCHER DESCRIPCIÓN', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Información de las cuentas en la caja adicional
$pdf->SetXY(25, $pdf->GetY() + 10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 2, mb_convert_encoding('Cuenta:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(20, 2, mb_convert_encoding($cuenta, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L'); // Agregado el número de cuenta
$pdf->Cell(130, 2, mb_convert_encoding($nombreCuenta, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L'); // Nombre de la cuenta
$pdf->SetXY(25, $pdf->GetY() + 10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 2, mb_convert_encoding('Asignar a:', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(20, 2, mb_convert_encoding($asignar_monto, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L'); // Agregado el monto asignado
$pdf->Cell(130, 2, mb_convert_encoding($nombreAsignarMonto, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L'); // Nombre del monto asignado

// Salida del PDF
$pdf->Output('I', 'cheque_'.$chequeNo.'.pdf');
        } else {
            echo "Cheque no encontrado.";
        }
    } else {
        echo "Parámetro 'chequeNo' no proporcionado.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}