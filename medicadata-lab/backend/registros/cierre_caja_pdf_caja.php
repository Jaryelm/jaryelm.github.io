<?php
session_start();
require('../../backend/fpdf/fpdf.php');
require_once '../../backend/bd/Conexion.php';

// Establecer zona horaria de Honduras
date_default_timezone_set('America/Tegucigalpa');

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

// Función para convertir a mayúsculas manteniendo acentos españoles
function convertirMayusculas($texto) {
    return mb_strtoupper($texto, 'UTF-8');
}

// Verificar que se recibieron las fechas
if (!isset($_GET['fecha_desde']) || !isset($_GET['fecha_hasta'])) {
    die('Error: Fechas no especificadas');
}

$fecha_desde = $_GET['fecha_desde'];
$fecha_hasta = $_GET['fecha_hasta'];

// Obtener usuario actual de la sesión
$usuario_actual = $_SESSION['username'] ?? '';

// Obtener nombre completo del usuario actual
try {
    $stmtUsuario = $connect->prepare("SELECT name FROM users WHERE username = ?");
    $stmtUsuario->execute([$usuario_actual]);
    $usuarioInfo = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
    $nombre_completo_usuario = $usuarioInfo['name'] ?? $usuario_actual;
} catch (Exception $e) {
    $nombre_completo_usuario = $usuario_actual;
}

// Consultar datos de cierre de caja en el rango de fechas SOLO del usuario logueado
try {
    $stmt = $connect->prepare("
        SELECT 
            fecha_cierre, 
            total_ventas, 
            total_facturas, 
            facturas_cobradas, 
            facturas_pendientes, 
            total_por_metodo, 
            usuario_cierre,
            nombre_completo,
            sobrante_caja
        FROM cierre_caja 
        WHERE DATE(fecha_cierre) BETWEEN ? AND ? 
        AND usuario_cierre = ?
        ORDER BY fecha_cierre ASC
    ");
    $stmt->execute([$fecha_desde, $fecha_hasta, $usuario_actual]);
    $cierres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cierres)) {
        die('No se encontraron cierres de caja en el rango de fechas especificado');
    }

} catch (Exception $e) {
    die('Error al consultar datos: ' . $e->getMessage());
}

// Crear clase PDF personalizada para CAJA (versión simplificada)
class PDF extends FPDF
{
    private $fechaDesde;
    private $fechaHasta;
    
    public function __construct($fechaDesde, $fechaHasta)
    {
        parent::__construct();
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
    }

    // Encabezado optimizado
    function Header() {
        // Logo a la izquierda
        $this->Image('../../backend/img/logo_medicasa.png', 10, 10, 25);
        
        // Posicionar cursor para los títulos centrados
        $this->SetY(12);
        
        // Título principal centrado
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 6, mb_convert_encoding('CIERRE DE CAJA - REPORTE CAJERO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
        // Rango de fechas centrado
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 5, mb_convert_encoding('del ' . date('d/m/Y', strtotime($this->fechaDesde)) . ' al ' . date('d/m/Y', strtotime($this->fechaHasta)), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
        // Fecha y hora de generación centrada
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, mb_convert_encoding('Reporte generado el ' . formatFecha(date('Y-m-d')) . ' ' . date('H:i'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(5);
    }

    // Pie de página
    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 8);
        
        // Línea de separación
        $this->Cell(0, 0, '', 'T', 1);
        $this->Ln(3);
        
        // Información del sistema
        $this->Cell(0, 4, mb_convert_encoding('Sistema: MEDIDATA - Gestión Hospitalaria - Reporte Cajero', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 4, mb_convert_encoding('Comayaguela M.D.C. - ' . formatFecha(date('Y-m-d')), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
        // Número de página
        $this->SetY(-10);
        $this->Cell(0, 10, mb_convert_encoding('Página ' . $this->PageNo() . '/{nb}', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    }

    // Función para crear encabezado de tabla específico por tipo de pago
    function TableHeaderEfectivo()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(28, 8, mb_convert_encoding('Fecha y Hora', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(35, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(45, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(57, 8, mb_convert_encoding('Cajero', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
    }

    function TableHeaderTarjeta()
    {
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(23, 8, mb_convert_encoding('Fecha y Hora', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(30, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(23, 8, mb_convert_encoding('Tipo Tarjeta', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(23, 8, mb_convert_encoding('Banco Emisor', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(23, 8, mb_convert_encoding('POS Cobrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(32, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(20, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(16, 8, mb_convert_encoding('Cajero', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 7);
    }

    function TableHeaderTransferencia()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(23, 8, mb_convert_encoding('Fecha y Hora', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(32, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(24, 8, mb_convert_encoding('Banco', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(28, 8, mb_convert_encoding('# de Referencia', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(38, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(20, 8, mb_convert_encoding('Cajero', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
    }

    function TableHeaderCredito()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(28, 8, mb_convert_encoding('Fecha y Hora', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(35, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(45, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(57, 8, mb_convert_encoding('Cajero', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
    }

    function TableHeaderPagoMixto()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(23, 8, mb_convert_encoding('Fecha y Hora', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(32, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(33, 8, mb_convert_encoding('Tipo Pago', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(37, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(40, 8, mb_convert_encoding('Cajero', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
    }

    function TableHeaderBotonPago()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(23, 8, mb_convert_encoding('Fecha y Hora', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(32, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(30, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Banco', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('# de Referencia', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(30, 8, mb_convert_encoding('Cajero', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
    }

    function TableHeaderTransferenciaLocal()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(23, 8, mb_convert_encoding('Fecha y Hora', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(32, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(30, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Banco', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('# de Referencia', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(30, 8, mb_convert_encoding('Cajero', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
    }

    function TableHeaderTransferenciaInternacional()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(23, 8, mb_convert_encoding('Fecha y Hora', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(32, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(30, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Banco', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('# de Referencia', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(25, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(30, 8, mb_convert_encoding('Cajero', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
    }

    // Función para llamar al encabezado específico según el tipo de pago
    function llamarEncabezadoEspecifico($metodo) {
        $metodoNormalizado = strtoupper(trim($metodo));
        
        switch ($metodoNormalizado) {
            case 'EFECTIVO':
                $this->TableHeaderEfectivo();
                break;
            case 'TARJETA':
                $this->TableHeaderTarjeta();
                break;
            case 'TRANSFERENCIA':
                $this->TableHeaderTransferencia();
                break;
            case 'CREDITO':
                $this->TableHeaderCredito();
                break;
            case 'CREDITO COLABORADOR':
                $this->TableHeaderCredito();
                break;
            case 'PAGO MIXTO':
            case 'MIXTO':
                $this->TableHeaderPagoMixto();
                break;
            case 'BOTON DE PAGO':
                $this->TableHeaderBotonPago();
                break;
            case 'TRANSFERENCIA LOCAL':
                $this->TableHeaderTransferenciaLocal();
                break;
            case 'TRANSFERENCIA INTERNACIONAL':
                $this->TableHeaderTransferenciaInternacional();
                break;
            default:
                $this->TableHeaderEfectivo(); // Por defecto
                break;
        }
    }

    // Función para mostrar fila específica según el tipo de pago
    function mostrarFilaEspecifica($metodo, $transaccion) {
        $metodoNormalizado = strtoupper(trim($metodo));
        
        switch ($metodoNormalizado) {
            case 'EFECTIVO':
                $this->mostrarFilaEfectivo($transaccion);
                break;
            case 'TARJETA':
                $this->mostrarFilaTarjeta($transaccion);
                break;
            case 'TRANSFERENCIA':
                $this->mostrarFilaTransferencia($transaccion);
                break;
            case 'CREDITO':
                $this->mostrarFilaCredito($transaccion);
                break;
            case 'CREDITO COLABORADOR':
                $this->mostrarFilaCredito($transaccion);
                break;
            case 'PAGO MIXTO':
            case 'MIXTO':
                $this->mostrarFilaPagoMixto($transaccion);
                break;
            case 'BOTON DE PAGO':
                $this->mostrarFilaBotonPago($transaccion);
                break;
            case 'TRANSFERENCIA LOCAL':
                $this->mostrarFilaTransferenciaLocal($transaccion);
                break;
            case 'TRANSFERENCIA INTERNACIONAL':
                $this->mostrarFilaTransferenciaInternacional($transaccion);
                break;
            default:
                $this->mostrarFilaEfectivo($transaccion); // Por defecto
                break;
        }
    }

    function mostrarFilaEfectivo($transaccion) {
        $this->Cell(28, 6, $transaccion['fecha'], 1, 0, 'C');
        $this->Cell(35, 6, $transaccion['codigo'], 1, 0, 'C');
        $this->Cell(45, 6, mb_convert_encoding($this->truncateText($transaccion['paciente'], 23), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        $this->Cell(25, 6, number_format($transaccion['monto'], 2), 1, 0, 'R');
        $this->Cell(57, 6, mb_convert_encoding($this->truncateText($transaccion['responsable'], 20), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
    }

    function mostrarFilaTarjeta($transaccion) {
        $this->Cell(23, 6, $transaccion['fecha'], 1, 0, 'C');
        $this->Cell(30, 6, $transaccion['codigo'], 1, 0, 'C');
        
        $tipoTarjeta = !empty($transaccion['tipo_tarjeta']) ? $transaccion['tipo_tarjeta'] : 'N/A';
        $bancoEmisor = !empty($transaccion['banco_emisor']) ? $transaccion['banco_emisor'] : 'N/A';
        $posCobrado = !empty($transaccion['pos_cobrado']) ? $transaccion['pos_cobrado'] : 'N/A';
        
        $this->Cell(23, 6, mb_convert_encoding($this->truncateText($tipoTarjeta, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(23, 6, mb_convert_encoding($this->truncateText($bancoEmisor, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(23, 6, mb_convert_encoding($this->truncateText($posCobrado, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(32, 6, mb_convert_encoding($this->truncateText($transaccion['paciente'], 15), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        $this->Cell(20, 6, number_format($transaccion['monto'], 2), 1, 0, 'R');
        $this->Cell(16, 6, mb_convert_encoding($this->truncateText($transaccion['responsable'], 8), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
    }

    function mostrarFilaTransferencia($transaccion) {
        $this->Cell(23, 6, $transaccion['fecha'], 1, 0, 'C');
        $this->Cell(32, 6, $transaccion['codigo'], 1, 0, 'C');
        
        $bancoTransferencia = !empty($transaccion['banco_transferencia']) ? $transaccion['banco_transferencia'] : 'N/A';
        $numReferencia = !empty($transaccion['num_referencia']) ? $transaccion['num_referencia'] : 'N/A';
        
        $this->Cell(24, 6, mb_convert_encoding($this->truncateText($bancoTransferencia, 12), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(28, 6, mb_convert_encoding($this->truncateText($numReferencia, 12), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(38, 6, mb_convert_encoding($this->truncateText($transaccion['paciente'], 18), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        $this->Cell(25, 6, number_format($transaccion['monto'], 2), 1, 0, 'R');
        $this->Cell(20, 6, mb_convert_encoding($this->truncateText($transaccion['responsable'], 10), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
    }

    function mostrarFilaCredito($transaccion) {
        $this->Cell(28, 6, $transaccion['fecha'], 1, 0, 'C');
        $this->Cell(35, 6, $transaccion['codigo'], 1, 0, 'C');
        $this->Cell(45, 6, mb_convert_encoding($this->truncateText($transaccion['paciente'], 23), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        $this->Cell(25, 6, number_format($transaccion['monto'], 2), 1, 0, 'R');
        $this->Cell(57, 6, mb_convert_encoding($this->truncateText($transaccion['responsable'], 20), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
    }

    function mostrarFilaPagoMixto($transaccion) {
        $this->Cell(23, 6, $transaccion['fecha'], 1, 0, 'C');
        $this->Cell(32, 6, $transaccion['codigo'], 1, 0, 'C');
        
        $tipoPagoMixto = !empty($transaccion['tipo_pago_mixto']) ? $transaccion['tipo_pago_mixto'] : 'N/A';
        
        $this->Cell(33, 6, mb_convert_encoding($this->truncateText($tipoPagoMixto, 15), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(37, 6, mb_convert_encoding($this->truncateText($transaccion['paciente'], 17), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        $this->Cell(25, 6, number_format($transaccion['monto'], 2), 1, 0, 'R');
        $this->Cell(40, 6, mb_convert_encoding($this->truncateText($transaccion['responsable'], 16), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
    }

    function mostrarFilaBotonPago($transaccion) {
        $this->Cell(23, 6, $transaccion['fecha'], 1, 0, 'C');
        $this->Cell(32, 6, $transaccion['codigo'], 1, 0, 'C');
        $this->Cell(30, 6, mb_convert_encoding($this->truncateText($transaccion['paciente'], 15), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        
        $bancoBotonPago = !empty($transaccion['banco_boton_pago']) ? $transaccion['banco_boton_pago'] : 'N/A';
        $numReferencia = !empty($transaccion['num_referencia_boton_pago']) ? $transaccion['num_referencia_boton_pago'] : 'N/A';
        
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($bancoBotonPago, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($numReferencia, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(25, 6, number_format($transaccion['monto'], 2), 1, 0, 'R');
        $this->Cell(30, 6, mb_convert_encoding($this->truncateText($transaccion['responsable'], 12), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
    }

    function mostrarFilaTransferenciaLocal($transaccion) {
        $this->Cell(23, 6, $transaccion['fecha'], 1, 0, 'C');
        $this->Cell(32, 6, $transaccion['codigo'], 1, 0, 'C');
        $this->Cell(30, 6, mb_convert_encoding($this->truncateText($transaccion['paciente'], 15), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        
        $bancoTransferenciaLocal = !empty($transaccion['banco_transferencia_local']) ? $transaccion['banco_transferencia_local'] : 'N/A';
        $numReferenciaLocal = !empty($transaccion['num_referencia_transferencia_local']) ? $transaccion['num_referencia_transferencia_local'] : 'N/A';
        
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($bancoTransferenciaLocal, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($numReferenciaLocal, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(25, 6, number_format($transaccion['monto'], 2), 1, 0, 'R');
        $this->Cell(30, 6, mb_convert_encoding($this->truncateText($transaccion['responsable'], 12), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
    }

    function mostrarFilaTransferenciaInternacional($transaccion) {
        $this->Cell(23, 6, $transaccion['fecha'], 1, 0, 'C');
        $this->Cell(32, 6, $transaccion['codigo'], 1, 0, 'C');
        $this->Cell(30, 6, mb_convert_encoding($this->truncateText($transaccion['paciente'], 15), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        
        $bancoTransferenciaInternacional = !empty($transaccion['banco_transferencia_internacional']) ? $transaccion['banco_transferencia_internacional'] : 'N/A';
        $numReferenciaInternacional = !empty($transaccion['num_referencia_transferencia_internacional']) ? $transaccion['num_referencia_transferencia_internacional'] : 'N/A';
        
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($bancoTransferenciaInternacional, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($numReferenciaInternacional, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(25, 6, number_format($transaccion['monto'], 2), 1, 0, 'R');
        $this->Cell(30, 6, mb_convert_encoding($this->truncateText($transaccion['responsable'], 12), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
    }

    // Función para mostrar total específico según el tipo de pago
    function mostrarTotalEspecifico($metodo, $total) {
        $metodoNormalizado = strtoupper(trim($metodo));
        
        switch ($metodoNormalizado) {
            case 'EFECTIVO':
            case 'CREDITO':
                $this->Cell(133, 6, mb_convert_encoding('Total ' . convertirMayusculas($metodo), 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
                $this->Cell(57, 6, number_format($total, 2), 1, 1, 'R');
                break;
            case 'TARJETA':
                $this->Cell(174, 6, mb_convert_encoding('Total Tarjetas', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
                $this->Cell(16, 6, number_format($total, 2), 1, 1, 'R');
                break;
            case 'CREDITO COLABORADOR':
                $this->Cell(133, 6, mb_convert_encoding('Total Crédito Colaborador', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
                $this->Cell(57, 6, number_format($total, 2), 1, 1, 'R');
                break;
            case 'TRANSFERENCIA':
                $this->Cell(170, 6, mb_convert_encoding('Total Transferencias', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
                $this->Cell(20, 6, number_format($total, 2), 1, 1, 'R');
                break;
            case 'PAGO MIXTO':
            case 'MIXTO':
                $this->Cell(150, 6, mb_convert_encoding('Total pago mixto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
                $this->Cell(40, 6, number_format($total, 2), 1, 1, 'R');
                break;
            case 'BOTON DE PAGO':
                $this->Cell(160, 6, mb_convert_encoding('Total Botón de Pago', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
                $this->Cell(30, 6, number_format($total, 2), 1, 1, 'R');
                break;
            case 'TRANSFERENCIA LOCAL':
                $this->Cell(160, 6, mb_convert_encoding('Total Transferencia Local', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
                $this->Cell(30, 6, number_format($total, 2), 1, 1, 'R');
                break;
            case 'TRANSFERENCIA INTERNACIONAL':
                $this->Cell(160, 6, mb_convert_encoding('Total Transferencia Internacional', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
                $this->Cell(30, 6, number_format($total, 2), 1, 1, 'R');
                break;
            default:
                $this->Cell(133, 6, mb_convert_encoding('TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
                $this->Cell(57, 6, number_format($total, 2), 1, 1, 'R');
                break;
        }
    }

    // Función para mostrar detalles consolidados por método de pago
    function ShowPaymentDetailsConsolidated($metodo, $totalMetodo, $fechaDesde, $fechaHasta, $connect)
    {
        // VERIFICACIÓN CRÍTICA DE ESPACIO - RESPETAR FOOTER
        // El footer está en Y = -25, necesitamos al menos 40mm libres para esta sección completa
        if ($this->GetY() > 250) {
            $this->AddPage();
        }
        
        // Título del tipo de pago
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(0, 0, 0);
        
        // Normalizar el título para mostrar "CREDITO" en lugar de "CREDITO COLABORADOR"
        $tituloMetodo = $metodo;
        if (strtoupper(trim($metodo)) === 'CREDITO COLABORADOR') {
            $tituloMetodo = 'CREDITO';
        }
        
        $this->Cell(0, 8, mb_convert_encoding('Tipo de Pago: ' . convertirMayusculas($tituloMetodo), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        
        $this->Ln(2);
        
        // Encabezado de tabla específico por tipo de pago
        $this->llamarEncabezadoEspecifico($metodo);
        
        // Obtener transacciones reales del usuario actual
        global $usuario_actual;
        $transacciones = $this->obtenerTransaccionesReales($metodo, $fechaDesde, $fechaHasta, $connect, $usuario_actual);
        
        $totalVerificado = 0;
        foreach ($transacciones as $transaccion) {
            // VERIFICACIÓN PROFESIONAL DE ESPACIO - RESPETAR FOOTER COMPLETAMENTE
            // Altura del footer: 25mm + margen de seguridad: 15mm = 40mm mínimo
            // Cada fila de datos: 6mm, header: 8mm, título: 10mm
            // Total mínimo necesario: 24mm + margen de seguridad
            if ($this->GetY() > 240) {
                $this->AddPage();
                $this->llamarEncabezadoEspecifico($metodo);
            }
            
            // Mostrar fila específica según el tipo de pago
            $this->mostrarFilaEspecifica($metodo, $transaccion);
            
            $totalVerificado += $transaccion['monto'];
        }
        
        $this->Ln(3);
        
        // Total del método consolidado
        $this->SetFont('Arial', 'B', 9);
        $this->mostrarTotalEspecifico($metodo, $totalVerificado);
        
        $this->Ln(5);
    }
    
    // Función para truncar texto si es muy largo
    function truncateText($text, $maxLength) {
        if (strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength - 3) . '...';
        }
        return $text;
    }
    
    // Función para obtener transacciones reales de la base de datos
    function obtenerTransaccionesReales($metodo, $fechaDesde, $fechaHasta, $connect, $usuarioCierre = null) {
        try {
            // Mapear métodos de pago para la consulta
            $metodosMap = [
                'EFECTIVO' => 'EFECTIVO',
                'TARJETA' => 'TARJETA',
                'efectivo' => 'EFECTIVO',
                'tarjeta' => 'TARJETA',
                'CREDITO' => 'CREDITO',
                'credito' => 'CREDITO',
                'Efectivo' => 'EFECTIVO',
                'Tarjeta' => 'TARJETA',
                'Credito' => 'CREDITO',
                'Boton de Pago' => 'BOTON DE PAGO',
                'BOTON DE PAGO' => 'BOTON DE PAGO',
                'TRANSFERENCIA LOCAL' => 'TRANSFERENCIA LOCAL',
                'TRANSFERENCIA INTERNACIONAL' => 'TRANSFERENCIA INTERNACIONAL'
            ];
            
            $metodoConsulta = $metodosMap[$metodo] ?? $metodo;
            
            // Construir consulta
            $whereClause = "o.method = ? AND DATE(o.placed_on) BETWEEN ? AND ? AND o.invoice_status = 'Cobrada'";
            $params = [$metodoConsulta, $fechaDesde, $fechaHasta];
            
            if ($usuarioCierre) {
                $whereClause .= " AND o.updated_by = ?";
                $params[] = $usuarioCierre;
            }
            
            $stmt = $connect->prepare("
                SELECT 
                    o.invoice_number,
                    o.nomcl,
                    o.processed_by,
                    o.updated_by,
                    o.total_price,
                    o.updated_at,
                    o.placed_on,
                    o.method,
                    o.tipo,
                    o.invoice_status,
                    o.tipo_tarjeta,
                    o.banco_emisor,
                    o.pos_cobrado,
                    o.banco_transferencia,
                    o.num_referencia,
                    o.tipo_pago_mixto,
                    o.banco_boton_pago,
                    o.num_referencia_boton_pago,
                    o.banco_transferencia_local,
                    o.num_referencia_transferencia_local,
                    o.banco_transferencia_internacional,
                    o.num_referencia_transferencia_internacional
                FROM orders o
                WHERE $whereClause
                ORDER BY o.updated_at ASC
            ");
            
            $stmt->execute($params);
            $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $transacciones = [];
            
            foreach ($ordenes as $orden) {
                $fechaString = $orden['placed_on'] ?? $orden['updated_at'];
                $fecha = new DateTime($fechaString, new DateTimeZone('America/Tegucigalpa'));
                $monto = floatval($orden['total_price']);
                
                $transacciones[] = [
                    'fecha' => $fecha->format('d/m/Y H:i'),
                    'tipo' => 'FACTURA',
                    'paciente' => convertirMayusculas($orden['nomcl']),
                    'responsable' => convertirMayusculas($orden['updated_by'] ?? $orden['processed_by'] ?? 'SISTEMA'),
                    'codigo' => $orden['invoice_number'] ?? 'N/A',
                    'monto' => $monto,
                    'tipo_tarjeta' => $orden['tipo_tarjeta'] ?? '',
                    'banco_emisor' => $orden['banco_emisor'] ?? '',
                    'pos_cobrado' => $orden['pos_cobrado'] ?? '',
                    'banco_transferencia' => $orden['banco_transferencia'] ?? '',
                    'num_referencia' => $orden['num_referencia'] ?? '',
                    'tipo_pago_mixto' => $orden['tipo_pago_mixto'] ?? '',
                    'banco_boton_pago' => $orden['banco_boton_pago'] ?? '',
                    'num_referencia_boton_pago' => $orden['num_referencia_boton_pago'] ?? '',
                    'banco_transferencia_local' => $orden['banco_transferencia_local'] ?? '',
                    'num_referencia_transferencia_local' => $orden['num_referencia_transferencia_local'] ?? '',
                    'banco_transferencia_internacional' => $orden['banco_transferencia_internacional'] ?? '',
                    'num_referencia_transferencia_internacional' => $orden['num_referencia_transferencia_internacional'] ?? ''
                ];
            }
            
            // Si no hay transacciones reales, crear mensaje informativo
            if (empty($transacciones)) {
                $transacciones[] = [
                    'fecha' => date('d/m/Y H:i'),
                    'tipo' => 'INFO',
                    'paciente' => 'NO HAY TRANSACCIONES',
                    'responsable' => $usuarioCierre ? strtoupper($usuarioCierre) : 'SISTEMA',
                    'codigo' => 'N/A',
                    'monto' => 0.00,
                    'tipo_tarjeta' => '',
                    'banco_emisor' => '',
                    'pos_cobrado' => '',
                    'banco_transferencia' => '',
                    'num_referencia' => '',
                    'tipo_pago_mixto' => ''
                ];
            }
            
            return $transacciones;
            
        } catch (Exception $e) {
            // En caso de error, devolver transacción de error
            return [[
                'fecha' => date('d/m/Y H:i'),
                'tipo' => 'ERROR',
                'paciente' => 'ERROR EN CONSULTA',
                'responsable' => 'SISTEMA',
                'codigo' => 'ERR',
                'monto' => 0.00,
                'tipo_tarjeta' => '',
                'banco_emisor' => '',
                'pos_cobrado' => '',
                'banco_transferencia' => '',
                'num_referencia' => '',
                'tipo_pago_mixto' => ''
            ]];
        }
    }

    // Función para agregar la nueva sección de Resumen de Cierre de Caja
    function agregarResumenCierreCaja($fechaDesde, $fechaHasta, $usuarioActual, $connect, $metodosTotales) {
        // VERIFICACIÓN CRÍTICA DE ESPACIO - RESPETAR FOOTER
        // El footer está en Y = -25, necesitamos al menos 60mm libres para esta sección completa
        if ($this->GetY() > 200) {
            $this->AddPage();
        } else {
            $this->Ln(10);
        }

        // Título de la nueva sección
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(6, 173, 191);
        $this->Cell(0, 8, mb_convert_encoding('RESUMEN DE CIERRE DE CAJA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(5);

        // Obtener facturas pendientes (faltantes)
        $faltantes = $this->obtenerFaltantes($fechaDesde, $fechaHasta, $usuarioActual, $connect);
        
        // Obtener sobrante de caja del período
        $sobranteCaja = $this->obtenerSobranteCaja($fechaDesde, $fechaHasta, $usuarioActual, $connect);
        
        // Header de la tabla de resumen
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(95, 8, mb_convert_encoding('Concepto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(45, 8, mb_convert_encoding('# Facturas', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(50, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);

        $totalFacturas = 0;
        $totalMonto = 0;

        // Mostrar métodos de pago cobrados
        foreach ($metodosTotales as $metodo => $monto) {
            if ($monto > 0) {
                // Obtener número de facturas para este método
                $numFacturas = $this->obtenerNumeroFacturas($metodo, $fechaDesde, $fechaHasta, $usuarioActual, $connect);
                
                $this->Cell(95, 6, mb_convert_encoding(convertirMayusculas($metodo), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
                $this->Cell(45, 6, number_format($numFacturas), 1, 0, 'C');
                $this->Cell(50, 6, 'LPS ' . number_format($monto, 2), 1, 1, 'R');
                
                $totalFacturas += $numFacturas;
                $totalMonto += $monto;
            }
        }

        // Mostrar sobrante de caja si existe
        if ($sobranteCaja > 0) {
            $this->SetFillColor(220, 53, 69); // Rojo
            $this->SetTextColor(255, 255, 255); // Texto blanco
            $this->Cell(95, 6, mb_convert_encoding('SOBRANTE DE CAJA', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
            $this->Cell(45, 6, '-', 1, 0, 'C', true);
            $this->Cell(50, 6, 'LPS ' . number_format($sobranteCaja, 2), 1, 1, 'R', true);
            
            // Restaurar colores normales
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0, 0, 0);
            
            // Agregar sobrante al total
            $totalMonto += $sobranteCaja;
        }

        // Mostrar faltantes con franja roja si existen
        if ($faltantes['cantidad'] > 0) {
            $this->SetFillColor(220, 53, 69); // Rojo
            $this->SetTextColor(255, 255, 255); // Texto blanco
            $this->Cell(95, 6, mb_convert_encoding('FALTANTES', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
            $this->Cell(45, 6, number_format($faltantes['cantidad']), 1, 0, 'C', true);
            $this->Cell(50, 6, 'LPS ' . number_format($faltantes['monto'], 2), 1, 1, 'R', true);
            
            // Restaurar colores normales
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0, 0, 0);
        }

        // Línea de subtotal
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(95, 8, mb_convert_encoding('Subtotal de ventas', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
        $this->Cell(45, 8, number_format($totalFacturas), 1, 0, 'C', true);
        $this->Cell(50, 8, 'LPS ' . number_format($totalMonto, 2), 1, 1, 'R', true);

        $this->Ln(5);
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(95, 10, mb_convert_encoding('TOTAL FACTURADO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R', true);
        $this->Cell(95, 10, 'LPS ' . number_format($totalMonto, 2), 1, 1, 'R', true);
        $this->SetTextColor(0, 0, 0);
    }

    // Función para obtener sobrante de caja
    function obtenerSobranteCaja($fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        try {
            $stmt = $connect->prepare("
                SELECT COALESCE(SUM(sobrante_caja), 0) as total_sobrante
                FROM cierre_caja 
                WHERE DATE(fecha_cierre) BETWEEN ? AND ?
                AND usuario_cierre = ?
            ");
            $stmt->execute([$fechaDesde, $fechaHasta, $usuarioActual]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total_sobrante'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    // Función para obtener facturas pendientes (faltantes)
    function obtenerFaltantes($fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        try {
            $stmt = $connect->prepare("
                SELECT 
                    COUNT(*) as cantidad,
                    COALESCE(SUM(total_price), 0) as monto
                FROM orders 
                WHERE DATE(placed_on) BETWEEN ? AND ?
                AND processed_by = ?
                AND invoice_status = 'Pendiente'
            ");
            $stmt->execute([$fechaDesde, $fechaHasta, $usuarioActual]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'cantidad' => $result['cantidad'] ?? 0,
                'monto' => $result['monto'] ?? 0
            ];
        } catch (Exception $e) {
            return ['cantidad' => 0, 'monto' => 0];
        }
    }

    // Función para obtener número de facturas por método de pago
    function obtenerNumeroFacturas($metodo, $fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        try {
            $metodosMap = [
                'EFECTIVO' => 'EFECTIVO',
                'TARJETA' => 'TARJETA',
                'efectivo' => 'EFECTIVO',
                'tarjeta' => 'TARJETA',
                'CREDITO' => 'CREDITO',
                'credito' => 'CREDITO',
                'BOTON DE PAGO' => 'BOTON DE PAGO',
                'TRANSFERENCIA LOCAL' => 'TRANSFERENCIA LOCAL',
                'TRANSFERENCIA INTERNACIONAL' => 'TRANSFERENCIA INTERNACIONAL'
            ];
            
            $metodoConsulta = $metodosMap[$metodo] ?? $metodo;
            
            $stmt = $connect->prepare("
                SELECT COUNT(*) as cantidad
                FROM orders 
                WHERE DATE(placed_on) BETWEEN ? AND ?
                AND updated_by = ?
                AND invoice_status = 'Cobrada'
                AND method = ?
            ");
            $stmt->execute([$fechaDesde, $fechaHasta, $usuarioActual, $metodoConsulta]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['cantidad'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }






}

// Crear documento PDF
$pdf = new PDF($fecha_desde, $fecha_hasta);
$pdf->AliasNbPages();
$pdf->AddPage();

// Hacer la variable usuario_actual global
global $usuario_actual;

// Variables para totales generales
$totalVentasGeneral = 0;
$totalFacturasGeneral = 0;
$totalCobradasGeneral = 0;
$totalPendientesGeneral = 0;
$metodosTotales = [];

// Nota específica para el usuario
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(102, 102, 102);
$pdf->Cell(0, 5, mb_convert_encoding('Reporte individual para: ' . strtoupper($nombre_completo_usuario), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

// Calcular totales reales directamente de las transacciones del usuario actual
try {
    // Obtener totales reales por método de pago del usuario logueado
    $stmtTotalesReales = $connect->prepare("
        SELECT 
            o.method,
            COUNT(*) as num_facturas,
            SUM(o.total_price) as total_monto
        FROM orders o
        WHERE DATE(o.placed_on) BETWEEN ? AND ?
            AND o.invoice_status = 'Cobrada'
            AND o.updated_by = ?
        GROUP BY o.method
        ORDER BY total_monto DESC
    ");
    $stmtTotalesReales->execute([$fecha_desde, $fecha_hasta, $usuario_actual]);
    $totalesReales = $stmtTotalesReales->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir array de métodos totales
    foreach ($totalesReales as $total) {
        $metodosTotales[$total['method']] = $total['total_monto'];
        $totalVentasGeneral += $total['total_monto'];
        $totalFacturasGeneral += $total['num_facturas'];
    }
    
    // Obtener facturas cobradas y pendientes del usuario actual
    $stmtEstadisticas = $connect->prepare("
        SELECT 
            COUNT(CASE WHEN invoice_status = 'Cobrada' THEN 1 END) as cobradas,
            COUNT(CASE WHEN invoice_status = 'Pendiente' THEN 1 END) as pendientes
        FROM orders o
        WHERE DATE(o.placed_on) BETWEEN ? AND ?
        AND o.updated_by = ?
    ");
    $stmtEstadisticas->execute([$fecha_desde, $fecha_hasta, $usuario_actual]);
    $estadisticas = $stmtEstadisticas->fetch(PDO::FETCH_ASSOC);
    
    $totalCobradasGeneral = $estadisticas['cobradas'];
    $totalPendientesGeneral = $estadisticas['pendientes'];
    
} catch (Exception $e) {
    // Fallback: usar datos de cierre_caja si hay error
    foreach ($cierres as $cierre) {
        $totalVentasGeneral += $cierre['total_ventas'];
        $totalFacturasGeneral += $cierre['total_facturas'];
        $totalCobradasGeneral += $cierre['facturas_cobradas'];
        $totalPendientesGeneral += $cierre['facturas_pendientes'];
        
        $metodos = json_decode($cierre['total_por_metodo'], true);
        if (is_array($metodos)) {
            foreach ($metodos as $metodo => $monto) {
                if ($monto > 0) {
                    if (!isset($metodosTotales[$metodo])) {
                        $metodosTotales[$metodo] = 0;
                    }
                    $metodosTotales[$metodo] += $monto;
                }
            }
        }
    }
}

// Mostrar cada método de pago con sus transacciones
foreach ($metodosTotales as $metodo => $totalMetodo) {
    if ($totalMetodo > 0) {
        // VERIFICACIÓN CRÍTICA DE ESPACIO - RESPETAR FOOTER
        // El footer está en Y = -25, necesitamos al menos 40mm libres para esta sección completa
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
        }
        
        $pdf->ShowPaymentDetailsConsolidated(
            $metodo,
            $totalMetodo,
            $fecha_desde,
            $fecha_hasta,
            $connect
        );
    }
}

// VERIFICACIÓN CRÍTICA DE ESPACIO PARA RESUMEN - RESPETAR FOOTER
// El footer está en Y = -25, necesitamos al menos 50mm libres para esta sección completa
if ($pdf->GetY() > 250) {
    $pdf->AddPage();
}

// Resumen general (SIMPLIFICADO para cajeros)
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(6, 173, 191);
$pdf->Cell(0, 8, mb_convert_encoding('RESUMEN GENERAL', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(5);

// Tabla resumen simplificada
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(6, 173, 191);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(95, 8, mb_convert_encoding('CONCEPTO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
$pdf->Cell(95, 8, mb_convert_encoding('TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(95, 8, mb_convert_encoding('Total Ventas del Período', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell(95, 8, 'LPS ' . number_format($totalVentasGeneral, 2), 1, 1, 'R');

$pdf->Cell(95, 8, mb_convert_encoding('Total Facturas Cobradas', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell(95, 8, number_format($totalCobradasGeneral), 1, 1, 'R');

$pdf->Cell(95, 8, mb_convert_encoding('Total Facturas Pendientes', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell(95, 8, number_format($totalPendientesGeneral), 1, 1, 'R');

$pdf->Ln(6);

// Nueva sección: Resumen de Cierre de Caja
$pdf->agregarResumenCierreCaja($fecha_desde, $fecha_hasta, $usuario_actual, $connect, $metodosTotales);

// Salida del PDF
try {
    $filename = 'Cierre_Caja_Cajero_' . date('Y-m-d', strtotime($fecha_desde)) . '_al_' . date('Y-m-d', strtotime($fecha_hasta)) . '.pdf';
    $pdf->Output('D', $filename);
} catch (Exception $e) {
    echo 'Error al generar el PDF: ' . $e->getMessage();
}
?>
