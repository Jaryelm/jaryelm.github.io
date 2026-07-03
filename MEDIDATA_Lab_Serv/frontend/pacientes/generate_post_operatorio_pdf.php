<?php
require('../../backend/bd/Conexion.php');
require('../../backend/fpdf/fpdf.php');
session_start();

class PDFWithHeaderFooter extends FPDF
{
    function Header()
    {
        // Agregar logo
        $this->Image('../../backend/img/factura_logo.png', 10, 5, 50);
        $this->Ln(20);
    }

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

// Consultar información del paciente incluyendo los nuevos campos
$stmtPatient = $connect->prepare("
    SELECT 
        CONCAT(patients.nompa, ' ', patients.apepa) AS full_name, -- Especificar tabla para evitar ambigüedad
        patients.numhs AS dni,
        patients.cump AS fecha_nacimiento,
        TIMESTAMPDIFF(YEAR, patients.cump, CURDATE()) AS edad,
        consult.servicio AS servicio,
        consult.habitacion_no, -- Especificar la tabla consult
        consult.fecha_hora_ingreso, -- Especificar la tabla consult
        consult.fecha_hora_egreso, -- Especificar la tabla consult
        consult.medico_tratante,
        consult.especialidad
    FROM 
        patients
    LEFT JOIN 
        consult ON patients.idpa = consult.idpa
    WHERE 
        patients.idpa = :idpa
    ORDER BY 
        consult.fere DESC 
    LIMIT 1
");
$stmtPatient->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtPatient->execute();
$patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die('Error: Paciente no encontrado.');
}

// Obtener datos del período post operatorio
$stmtPostOp = $connect->prepare("SELECT * FROM periodo_post_operativo WHERE idpa = :idpa ORDER BY created_at DESC LIMIT 1");
$stmtPostOp->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtPostOp->execute();
$postOp = $stmtPostOp->fetch(PDO::FETCH_ASSOC);

if (!$postOp) {
    die('Error: No se encontraron registros de período post operatorio.');
}

// 🔹 Crear PDF
$pdf = new PDFWithHeaderFooter('P', 'mm', 'Letter');
$pdf->AddPage();

// Controlar el salto de página manualmente si el espacio es insuficiente
function checkPageBreak($pdf, $height = 10)
{
    if ($pdf->GetY() + $height > $pdf->GetPageHeight() - 30) { // Se restan 30 para dejar espacio al footer
        $pdf->AddPage();
    }
}


// Agregar logo e información del encabezado
$pdf->Image('../../backend/img/factura_logo.png', 10, 5, 50); 
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, -5, mb_convert_encoding(mb_strtoupper('DATOS PACIENTE', 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Datos del paciente en formato homogéneo
$fields = [
    'NOMBRE COMPLETO' => $patient['full_name'] ?? 'No registrado',
    'DNI' => $patient['dni'] ?? 'No registrado',
    'FECHA DE NACIMIENTO' => $patient['fecha_nacimiento'] ?? 'No registrado',
    'EDAD' => ($patient['edad'] ?? '-') . ' años',
    'SERVICIO' => $patient['servicio'] ?? 'No registrado',
    'HABITACIÓN NO' => $patient['habitacion_no'] ?? 'No registrado',
    'MÉDICO TRATANTE' => $patient['medico_tratante'] ?? 'No registrado',
    'ESPECIALIDAD' => $patient['especialidad'] ?? 'No registrado',
    'FECHA / HORA DE INGRESO' => $patient['fecha_hora_ingreso'] ?? 'No registrado',
    'FECHA / HORA DE EGRESO' => $patient['fecha_hora_egreso'] ?? 'No registrado',
];

// Configuración de diseño para 2 columnas con ajuste dinámico
$pageWidth = $pdf->GetPageWidth();
$colWidth = ($pageWidth - 15) / 2; // Dos columnas con márgenes laterales ajustados
$rowHeight = 7; // Altura de las filas ajustada
$offset = 2; // Ajuste adicional para correr hacia la derecha
$startX = ($pageWidth - (2 * $colWidth)) / 2 + $offset; // Centramos la tabla con un desplazamiento
$startY = $pdf->GetY() + 10; // Posición inicial debajo del contenido anterior
$labelWidthRatio = 0.40; // Proporción del ancho para las etiquetas
$valueWidthRatio = 1 - $labelWidthRatio; // Proporción del ancho para los valores
$col = 0; // Contador de columnas

$pdf->SetFillColor(240, 240, 240); // Fondo para etiquetas
$pdf->SetFont('Arial', 'B', 8); // Fuente para etiquetas

// Iterar sobre los campos y dibujar en dos columnas
foreach ($fields as $label => $value) {
    // Posicionar columna
    $xPos = $startX + ($col * $colWidth);
    $pdf->SetXY($xPos, $startY);

    // Calcular anchos dinámicamente
    $labelWidth = $colWidth * $labelWidthRatio;
    $valueWidth = $colWidth * $valueWidthRatio;

    // Dibujar etiqueta
    $labelText = mb_convert_encoding(mb_strtoupper("$label:", 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $pdf->MultiCell($labelWidth, $rowHeight, $labelText, 0, 'L', true);

    // Dibujar valor
    $pdf->SetXY($xPos + $labelWidth, $startY);
    $valueText = mb_convert_encoding(mb_strtoupper($value, 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $pdf->MultiCell($valueWidth, $rowHeight, $valueText, 0, 'L', false);

    // Ajustar posición para siguiente celda
    $col++;
    if ($col == 2) { // Si ya se completaron dos columnas, pasar a la siguiente fila
        $startY += $rowHeight;
        $col = 0;
    }
}
$pdf->Ln(10);

// Evaluación del Riesgo de Caídas
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('EVALUACIÓN DEL RIESGO DE CAÍDAS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
checkPageBreak($pdf);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(195, 7, mb_convert_encoding($postOp['riesgo_caidas'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
$pdf->Ln(5);

// Medidas de Seguridad
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('MEDIDAS DE SEGURIDAD UTILIZADAS EN LA PREVENCIÓN DE CAÍDAS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
checkPageBreak($pdf);
$pdf->SetFont('Arial', '', 10);

// Decodificar las medidas de seguridad y convertirlas en una lista vertical
$medidas_seguridad = !empty($postOp['medidas_seguridad']) ? json_decode($postOp['medidas_seguridad'], true) : [];
$lista_medidas = !empty($medidas_seguridad) ? $medidas_seguridad : ["No se seleccionaron medidas."];

// Definir el ancho total y columnas
$cellWidth = 200;  // Ancho total de la tabla
$checkWidth = 10;  // Ancho del check
$textWidth = $cellWidth - $checkWidth - 5; // Ajuste de ancho para el texto

foreach ($lista_medidas as $medida) {
    checkPageBreak($pdf);

    // Agregar el ícono de check (imagen PNG)
    $pdf->Cell($checkWidth, 7, '', 0, 0, 'C'); // Celda vacía para el ícono
    $pdf->Image('../../backend/img/check_icon.png', $pdf->GetX() - $checkWidth, $pdf->GetY() + 1, 4, 4); // Ajustar posición y tamaño del ícono

    // Texto alineado a la izquierda
    $pdf->Cell($textWidth, 7, mb_convert_encoding($medida, 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}

$pdf->Ln(5);

// Evaluación del Dolor
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('EVALUACIÓN DEL DOLOR', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
checkPageBreak($pdf);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(49, 7, mb_convert_encoding('Hora', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(49, 7, mb_convert_encoding('Grado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(48.8, 7, mb_convert_encoding('Localización', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(48, 7, mb_convert_encoding('Actividad', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
$pdf->Cell(49, 7, mb_convert_encoding($postOp['hora_dolor'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(49, 7, mb_convert_encoding($postOp['grado_dolor'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(48.8, 7, mb_convert_encoding($postOp['localizacion_dolor'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(48, 7, mb_convert_encoding($postOp['actividad_dolor'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
$pdf->Ln(5);

// 🔹 Escala Visual Análoga (EVA)
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, mb_convert_encoding('ESCALA VISUAL ANALOGA (EVA)', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
checkPageBreak($pdf);

// Centrando la escala EVA en la página
$page_width = $pdf->GetPageWidth();
$scale_length = 120;  // Longitud de la escala
$x_start = ($page_width - $scale_length) / 2;  // Centrar horizontalmente
$y_start = $pdf->GetY() + 10;

// Dibujar la línea horizontal de la escala EVA (para los números)
$pdf->Line($x_start, $y_start, $x_start + $scale_length, $y_start);

// 📌 **Mover las líneas de colores más abajo**
$y_line_colors = $y_start + 12;  // Ajustado para estar debajo de los números

// Dibujar líneas de colores debajo de los números para indicar el nivel de dolor con efecto flecha
$pdf->SetLineWidth(1); // Grosor de las líneas de colores

// **Flecha negra indicando "Sin dolor"**
$pdf->SetDrawColor(0, 0, 0); // Negro
$pdf->Line($x_start, $y_line_colors, $x_start + ($scale_length / 11), $y_line_colors);
$pdf->Line($x_start + ($scale_length / 11) - 2, $y_line_colors - 1, $x_start + ($scale_length / 11), $y_line_colors); // Flecha

// **Dolor suave (1-3)**
$pdf->SetDrawColor(0, 200, 0); // Verde
$pdf->Line($x_start + ($scale_length / 11), $y_line_colors, $x_start + ($scale_length * 4 / 11), $y_line_colors);
$pdf->Line($x_start + ($scale_length * 4 / 11) - 2, $y_line_colors - 1, $x_start + ($scale_length * 4 / 11), $y_line_colors); // Flecha

// **Dolor moderado (4-6)**
$pdf->SetDrawColor(255, 200, 0); // Amarillo
$pdf->Line($x_start + ($scale_length * 4 / 11), $y_line_colors, $x_start + ($scale_length * 7 / 11), $y_line_colors);
$pdf->Line($x_start + ($scale_length * 7 / 11) - 2, $y_line_colors - 1, $x_start + ($scale_length * 7 / 11), $y_line_colors); // Flecha

// **Dolor intenso (7-10)**
$pdf->SetDrawColor(255, 0, 0); // Rojo
$pdf->Line($x_start + ($scale_length * 7 / 11), $y_line_colors, $x_start + $scale_length, $y_line_colors);
$pdf->Line($x_start + $scale_length - 2, $y_line_colors - 1, $x_start + $scale_length, $y_line_colors); // Flecha

// Restaurar grosor y color de línea
$pdf->SetLineWidth(0.2);
$pdf->SetDrawColor(0, 0, 0);

// Marcas en la escala de 0 a 10 con colores y líneas verticales
for ($i = 0; $i <= 10; $i++) {
    $x_pos = $x_start + ($i * ($scale_length / 10));

    // Definir colores por zona
    if ($i == 0) {
        $pdf->SetTextColor(0, 0, 0);  // Negro para "sin dolor"
    } elseif ($i <= 3) {
        $pdf->SetTextColor(0, 200, 0);  // Verde para dolor suave (1-3)
    } elseif ($i <= 6) {
        $pdf->SetTextColor(255, 200, 0);  // Amarillo para dolor moderado (4-6)
    } else {
        $pdf->SetTextColor(255, 0, 0);  // Rojo para dolor intenso (7-10)
    }

    // Dibujar número de la escala
    $pdf->Text($x_pos - 2, $y_start + 6, $i);

    // Dibujar línea vertical en cada número para mejor visualización
    $pdf->Line($x_pos, $y_start - 3, $x_pos, $y_start + 3);
}

// Restaurar color de texto a negro
$pdf->SetTextColor(0, 0, 0);

// 📌 **Mover etiquetas más abajo (de +10 a +15)**
$pdf->SetFont('Arial', '', 8);
$pdf->Text($x_start - 5, $y_line_colors + 10, 'Sin dolor');
$pdf->Text($x_start + ($scale_length / 6), $y_line_colors + 10, 'Dolor suave');
$pdf->Text($x_start + ($scale_length * 4 / 10), $y_line_colors + 10, 'Dolor moderado');
$pdf->Text($x_start + ($scale_length * 7 / 10), $y_line_colors + 10, 'Dolor intenso');

// Indicar el nivel de dolor en la escala con un cuadro negro
if (!empty($postOp['grado_dolor']) && is_numeric($postOp['grado_dolor'])) {
    $grado = intval($postOp['grado_dolor']);
    if ($grado >= 0 && $grado <= 10) {
        $x_pos = $x_start + ($grado * ($scale_length / 10));

        // Dibujar un marcador en la escala con un rectángulo negro
        $pdf->SetFillColor(0, 0, 0); // Negro
        $pdf->Rect($x_pos - 2, $y_start - 7, 4, 4, 'F');
    }
}

$pdf->Ln(5);

$pdf->AddPage();

// Escala de Valoración de Aldrete
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('ESCALA DE VALORACIÓN DE ALDRETE', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
checkPageBreak($pdf);

$col1_width = 55;  // Ancho para los títulos
$col2_width = 119; // Ancho para las respuestas seleccionadas
$col3_width = 20;  // Ancho para los valores

// Datos con las respuestas registradas en la tabla
$aldrete_items = [
    ["ACTIVIDAD MUSCULAR", [
        ["Movimientos Voluntarios (4 extremidades)", 2],
        ["Movimientos Voluntarios (2 extremidades)", 1],
        ["Completamente Inmóvil", 0]
    ], $postOp["actividad_muscular"] ?? "No registrado"],

    ["RESPIRACIÓN", [
        ["Respiraciones Amplias y Capaz de Toser", 2],
        ["Respiraciones Limitadas y Tos Débil", 1],
        ["APNEA", 0]
    ], $postOp["respiracion"] ?? "No registrado"],

    ["CIRCULACIÓN", [
        ["Tensión Arterial ≤ 20% de Cifras Control", 2],
        ["Tensión Arterial 20-50% de Cifras Control", 1],
        ["Tensión Arterial ≥ 50% de Cifras Control", 0]
    ], $postOp["circulacion"] ?? "No registrado"],

    ["ESTADO DE CONCIENCIA", [
        ["Completamente Despierto", 2],
        ["Responde Cuando se le Llama", 1],
        ["No Responde", 0]
    ], $postOp["estado_conciencia"] ?? "No registrado"],

    ["COLORACIÓN", [
        ["Mucosas Rosadas", 2],
        ["Palidez", 1],
        ["Cianosis", 0]
    ], $postOp["coloracion"] ?? "No registrado"],
];

// Generar la tabla con solo las respuestas seleccionadas
foreach ($aldrete_items as $section) {
    $title = $section[0]; // Nombre visual del título
    $options = $section[1]; // Opciones disponibles
    $selected_value = $section[2]; // Valor registrado

    // **Título en la primera celda**
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell($col1_width, 7, mb_convert_encoding($title, 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');

    $found = false;

    foreach ($options as $option) {
        list($text, $value) = $option;

        if ($text === $selected_value || strval($value) === strval($selected_value)) {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(200, 200, 200); // Fondo gris para la opción seleccionada
            $pdf->Cell($col2_width, 7, mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
            $pdf->Cell($col3_width, 7, strval($value), 1, 1, 'C', true);
            $pdf->SetFillColor(255, 255, 255); // Restaurar fondo
            $found = true;
            break; // No imprimimos más opciones, solo la elegida
        }
    }

    // Si no hay una respuesta válida, mostrar "No registrado"
    if (!$found) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($col2_width, 7, mb_convert_encoding("No registrado", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        $pdf->Cell($col3_width, 7, "", 1, 1, 'C');
    }
}

$pdf->Ln(5);

// 🔹 Sala de Recuperación
checkPageBreak($pdf, 20);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('SALA DE RECUPERACIÓN', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// **Definir las claves asociadas a cada columna en la base de datos**
$titulos = [
    "AL SALIR" => "al_salir",
    "20 MINUTOS" => "20_minutos",
    "60 MINUTOS" => "60_minutos",
    "90 MINUTOS" => "90_minutos",
    "120 MINUTOS" => "120_minutos"
];

$column_width = 38.8; // Ancho de cada columna
$row_height = 7; // Altura de cada fila
$total_columns = count($titulos);

// **Encabezado superior con QUIRÓFANO y SALA**
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($column_width, $row_height, mb_convert_encoding('QUIRÓFANO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');

// Celda combinada para "SALA" que cubre el resto de las columnas
$pdf->Cell($column_width * ($total_columns - 1), $row_height, mb_convert_encoding('SALA', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Ln();

// **Encabezados en negrita**
$pdf->SetFont('Arial', 'B', 10);
foreach ($titulos as $titulo => $campo) {
    $pdf->Cell($column_width, $row_height, mb_convert_encoding($titulo, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
}
$pdf->Ln();

// **Obtener los datos de la tabla de recuperación**
$pdf->SetFont('Arial', '', 10);
$salaRecuperacion = !empty($postOp['sala_recuperacion']) ? json_decode($postOp['sala_recuperacion'], true) : [];

// **Cantidad máxima de registros a mostrar por columna**
$max_rows = 8; // Cantidad de cuadros de valores por cada título

// **Construir la tabla con los valores correctos de la base de datos**
for ($i = 0; $i < $max_rows; $i++) {
    foreach ($titulos as $campo) {
        // Obtener el valor correspondiente usando la clave correcta
        $valor = isset($salaRecuperacion[$campo][$i]) ? $salaRecuperacion[$campo][$i] : '';
        $pdf->Cell($column_width, $row_height, mb_convert_encoding($valor, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    }
    $pdf->Ln();
}

// **Fila de Totales al final**
$pdf->SetFont('Arial', 'B', 10);
foreach ($titulos as $campo) {
    // Sumar valores en cada columna correctamente
    $total = isset($salaRecuperacion[$campo]) ? array_sum($salaRecuperacion[$campo]) : 0;
    $pdf->Cell($column_width, $row_height, mb_convert_encoding($total, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
}
$pdf->Ln(10);

checkPageBreak($pdf);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('ALTA DEL PACIENTE', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

// Encabezados
$pdf->Cell(64, 7, mb_convert_encoding('Alta SI / NO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(68, 7, mb_convert_encoding('A Su Cuarto / A Su Domicilio', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(62, 7, mb_convert_encoding('Hora de Alta', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');

// Valores
$alta_si_no = ($postOp['alta_si'] ?? 'No') . " / " . ($postOp['alta_no'] ?? 'No');
$destino = ($postOp['a_cuarto'] ?? 'No') . " / " . ($postOp['a_domicilio'] ?? 'No');
$hora_alta = $postOp['hora_alta'] ?? 'No registrada';

$pdf->Cell(64, 7, mb_convert_encoding($alta_si_no, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(68, 7, mb_convert_encoding($destino, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(62, 7, mb_convert_encoding($hora_alta, 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
$pdf->Ln(5);

$pdf->Ln(5);

// 🔹 Firma de Enfermería
$pdf->Ln(15); // Espaciado superior
$pdf->Cell(0, 15, "_____________________________", 0, 1, 'C');
$pdf->Cell(0, 7, mb_convert_encoding("Firma de Enfermería", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Generar PDF
$pdf->Output('D', 'Periodo Post Operatorio.pdf');
?>
