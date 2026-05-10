<?php
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
    // Usar mb_strtoupper con UTF-8 para manejar correctamente los acentos
    return mb_strtoupper($texto, 'UTF-8');
}

// Verificar que se recibieron las fechas
if (!isset($_GET['fecha_desde']) || !isset($_GET['fecha_hasta'])) {
    die('Error: Fechas no especificadas');
}

$fecha_desde = $_GET['fecha_desde'];
$fecha_hasta = $_GET['fecha_hasta'];
$usuario_caja = isset($_GET['usuario_caja']) ? trim($_GET['usuario_caja']) : null;
if ($usuario_caja === '') {
    $usuario_caja = null;
}

// Consultar datos de cierre de caja en el rango de fechas (REPORTE ADMINISTRATIVO o por usuario de caja)
try {
    $sql_cierres = "
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
    ";
    $params_cierres = [$fecha_desde, $fecha_hasta];
    if ($usuario_caja) {
        $sql_cierres .= " AND nombre_completo = ?";
        $params_cierres[] = $usuario_caja;
    }
    $sql_cierres .= " ORDER BY fecha_cierre ASC";
    $stmt = $connect->prepare($sql_cierres);
    $stmt->execute($params_cierres);
    $cierres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cierres) && !$usuario_caja) {
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
    private $usuarioCaja;
    
    public function __construct($fechaDesde, $fechaHasta, $usuarioCaja = null)
    {
        parent::__construct();
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
        $this->usuarioCaja = $usuarioCaja;
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
        $this->Cell(0, 6, mb_convert_encoding('CIERRE DE CAJA - REPORTE ADMINISTRATIVO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
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
        $this->Cell(0, 4, mb_convert_encoding('Sistema: MEDIDATA - Gestión Hospitalaria - Reporte Administrativo', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
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
        $numReferencia = !empty($transaccion['num_referencia_transferencia_local']) ? $transaccion['num_referencia_transferencia_local'] : 'N/A';
        
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($bancoTransferenciaLocal, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($numReferencia, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        
        $this->Cell(25, 6, number_format($transaccion['monto'], 2), 1, 0, 'R');
        $this->Cell(30, 6, mb_convert_encoding($this->truncateText($transaccion['responsable'], 12), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
    }

    function mostrarFilaTransferenciaInternacional($transaccion) {
        $this->Cell(23, 6, $transaccion['fecha'], 1, 0, 'C');
        $this->Cell(32, 6, $transaccion['codigo'], 1, 0, 'C');
        $this->Cell(30, 6, mb_convert_encoding($this->truncateText($transaccion['paciente'], 15), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        
        $bancoTransferenciaInternacional = !empty($transaccion['banco_transferencia_internacional']) ? $transaccion['banco_transferencia_internacional'] : 'N/A';
        $numReferencia = !empty($transaccion['num_referencia_transferencia_internacional']) ? $transaccion['num_referencia_transferencia_internacional'] : 'N/A';
        
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($bancoTransferenciaInternacional, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Cell(25, 6, mb_convert_encoding($this->truncateText($numReferencia, 10), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        
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
                $this->Cell(133, 6, mb_convert_encoding('Total Crédito', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
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
        // Verificar si necesitamos una nueva página
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
        
        // Obtener transacciones reales (todos los cajeros o filtrado por usuario de caja)
        $transacciones = $this->obtenerTransaccionesReales($metodo, $fechaDesde, $fechaHasta, $connect, $this->usuarioCaja);
        
        $totalVerificado = 0;
        foreach ($transacciones as $transaccion) {
            // Verificar espacio antes de cada fila
            if ($this->GetY() > 270) {
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
    
    // Función para obtener transacciones reales de la base de datos (REPORTE ADMINISTRATIVO)
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
            
            // Construir consulta (todos los cajeros o por usuario de caja)
            $whereClause = "o.method = ? AND DATE(o.placed_on) BETWEEN ? AND ? AND o.invoice_status = 'Cobrada'";
            $params = [$metodoConsulta, $fechaDesde, $fechaHasta];
            
            // Filtrar por usuario de caja si se especifica (reporte individual)
            if ($usuarioCierre) {
                $whereClause .= " AND (o.processed_by = ? OR o.updated_by = ?)";
                $params[] = $usuarioCierre;
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
                    'responsable' => $usuarioCierre ? strtoupper($usuarioCierre) : 'TODOS LOS CAJEROS',
                    'codigo' => 'N/A',
                    'monto' => 0.00,
                    'tipo_tarjeta' => '',
                    'banco_emisor' => '',
                    'pos_cobrado' => '',
                    'banco_transferencia' => '',
                    'num_referencia' => '',
                    'tipo_pago_mixto' => '',
                    'banco_boton_pago' => '',
                    'num_referencia_boton_pago' => '',
                    'banco_transferencia_local' => '',
                    'num_referencia_transferencia_local' => '',
                    'banco_transferencia_internacional' => '',
                    'num_referencia_transferencia_internacional' => ''
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
                'tipo_pago_mixto' => '',
                'banco_boton_pago' => '',
                'num_referencia_boton_pago' => '',
                'banco_transferencia_local' => '',
                'num_referencia_transferencia_local' => '',
                'banco_transferencia_internacional' => '',
                'num_referencia_transferencia_internacional' => ''
            ]];
        }
    }

    // Función para agregar la nueva sección de Resumen de Cierre de Caja
    function agregarResumenCierreCaja($fechaDesde, $fechaHasta, $usuarioActual, $connect, $metodosTotales) {
        // Verificar si necesitamos una nueva página
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

        // Obtener facturas pendientes (faltantes) - REPORTE ADMINISTRATIVO
        $faltantes = $this->obtenerFaltantesAdmin($fechaDesde, $fechaHasta, $connect);
        
        // Obtener sobrante de caja del período - REPORTE ADMINISTRATIVO
        $sobranteCaja = $this->obtenerSobranteCajaAdmin($fechaDesde, $fechaHasta, $connect);
        
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
                // Obtener número de facturas para este método - REPORTE ADMINISTRATIVO
                $numFacturas = $this->obtenerNumeroFacturasAdmin($metodo, $fechaDesde, $fechaHasta, $connect);
                
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

                // Sección de Gastos y Honorarios - REPORTE ADMINISTRATIVO
        $this->agregarSeccionHonorariosAdmin($fechaDesde, $fechaHasta, $connect);
        
        // Cálculo final - REPORTE ADMINISTRATIVO
        $totalGastos = $this->calcularTotalGastosAdmin($fechaDesde, $fechaHasta, $connect);
        $totalFinal = $totalMonto - $totalGastos;

        $this->Ln(5);
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(95, 10, mb_convert_encoding('TOTAL FACTURADO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R', true);
        $this->Cell(95, 10, 'LPS ' . number_format($totalFinal, 2), 1, 1, 'R', true);
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
                'credito' => 'CREDITO'
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

    // Función para agregar sección de honorarios
    function agregarSeccionHonorarios($fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        // Verificar si necesitamos una nueva página
        if ($this->GetY() > 220) {
            $this->AddPage();
        } else {
            $this->Ln(8);
        }

        // Título de honorarios
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(6, 173, 191);
        $this->Cell(0, 8, mb_convert_encoding('GASTOS Y HONORARIOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(3);

        // Header de la tabla de honorarios
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(30, 8, mb_convert_encoding('Fecha', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(35, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(45, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(45, 8, mb_convert_encoding('Médico', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(35, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);

        // Obtener honorarios del período
        $honorarios = $this->obtenerHonorarios($fechaDesde, $fechaHasta, $usuarioActual, $connect);
        $totalHonorarios = 0;

        foreach ($honorarios as $honorario) {
            // Verificar espacio
            if ($this->GetY() > 270) {
                $this->AddPage();
                // Repetir header
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(6, 173, 191);
                $this->SetTextColor(255, 255, 255);
                $this->Cell(30, 8, mb_convert_encoding('Fecha', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $this->Cell(35, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $this->Cell(45, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $this->Cell(45, 8, mb_convert_encoding('Médico', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $this->Cell(35, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 8);
            }

            // Si el honorario no está pagado, usar fondo rojo
            if ($honorario['estado_pago'] != 'pagado') {
                $this->SetFillColor(220, 53, 69); // Rojo
                $this->SetTextColor(255, 255, 255); // Texto blanco
                $usarFondo = true;
            } else {
                $usarFondo = false;
            }

            $this->Cell(30, 6, $honorario['fecha'], 1, 0, 'C', $usarFondo);
            $this->Cell(35, 6, $honorario['numero_factura'], 1, 0, 'C', $usarFondo);
            $this->Cell(45, 6, mb_convert_encoding($this->truncateText($honorario['paciente'], 20), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', $usarFondo);
            $this->Cell(45, 6, mb_convert_encoding($this->truncateText($honorario['medico'], 20), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', $usarFondo);
            $this->Cell(35, 6, 'LPS ' . number_format($honorario['monto'], 2), 1, 1, 'R', $usarFondo);

            // Restaurar colores
            if ($usarFondo) {
                $this->SetFillColor(255, 255, 255);
                $this->SetTextColor(0, 0, 0);
            }

            $totalHonorarios += $honorario['monto'];
        }

        // Total de gastos
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(155, 8, mb_convert_encoding('Total Honorarios:', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R', true);
        $this->Cell(35, 8, 'LPS ' . number_format($totalHonorarios, 2), 1, 1, 'R', true);
    }

    // Función para obtener honorarios del período
    function obtenerHonorarios($fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        try {
            $stmt = $connect->prepare("
                SELECT 
                    DATE(o.placed_on) as fecha,
                    o.invoice_number as numero_factura,
                    o.nomcl as paciente,
                    CONCAT(d.nodoc, ' ', d.apdoc) as medico,
                    h.monto_honorario as monto,
                    h.estado_pago
                FROM honorarios_medicos h
                INNER JOIN orders o ON h.id_factura = o.idord
                INNER JOIN doctor d ON h.id_doctor = d.idodc
                WHERE DATE(o.placed_on) BETWEEN ? AND ?
                AND h.estado_pago = 'pagado'
                ORDER BY d.nodoc ASC, o.placed_on ASC
            ");
            $stmt->execute([$fechaDesde, $fechaHasta]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // Función para calcular total de gastos
    function calcularTotalGastos($fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        try {
            $stmt = $connect->prepare("
                SELECT COALESCE(SUM(h.monto_honorario), 0) as total
                FROM honorarios_medicos h
                INNER JOIN orders o ON h.id_factura = o.idord
                WHERE DATE(o.placed_on) BETWEEN ? AND ?
                AND h.estado_pago = 'pagado'
            ");
            $stmt->execute([$fechaDesde, $fechaHasta]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    // Función para agregar la nueva sección de totales de efectivo y sobrante/faltante
    function agregarTotalesEfectivo($fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        // Verificar si necesitamos una nueva página
        if ($this->GetY() > 200) {
            $this->AddPage();
        } else {
            $this->Ln(10);
        }

        // Título de la nueva sección
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(6, 173, 191);
        $this->Cell(0, 8, mb_convert_encoding('TOTALES DE EFECTIVO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(5);

        // Obtener efectivo inicial del período - REPORTE ADMINISTRATIVO
        $efectivoInicial = $this->obtenerEfectivoInicialAdmin($fechaDesde, $fechaHasta, $connect);
        
        // Obtener sobrante de caja del período - REPORTE ADMINISTRATIVO
        $sobranteCaja = $this->obtenerSobranteCajaAdmin($fechaDesde, $fechaHasta, $connect);
        
        // Obtener fecha de inicio del turno - REPORTE ADMINISTRATIVO
        $fechaInicioTurno = $this->obtenerFechaInicioTurnoAdmin($fechaDesde, $fechaHasta, $connect);
        
        // Obtener fecha de cierre - REPORTE ADMINISTRATIVO
        $fechaCierre = $this->obtenerFechaCierreAdmin($fechaDesde, $fechaHasta, $connect);
        
        // Header de la tabla de totales de efectivo (4 columnas: 47.5mm + 47.5mm + 47.5mm + 47.5mm = 190mm)
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(47.5, 8, mb_convert_encoding('Concepto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(47.5, 8, mb_convert_encoding('Fecha Inicio', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(47.5, 8, mb_convert_encoding('Fecha Cierre', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(47.5, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);

        // Mostrar efectivo inicial
        $this->Cell(47.5, 6, mb_convert_encoding('EFECTIVO INICIAL', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        $this->Cell(47.5, 6, $fechaInicioTurno, 1, 0, 'C');
        $this->Cell(47.5, 6, $fechaCierre, 1, 0, 'C');
        $this->Cell(47.5, 6, 'LPS ' . number_format($efectivoInicial, 2), 1, 1, 'R');

        // Mostrar sobrante de caja si existe
        if ($sobranteCaja > 0) {
            $this->SetFillColor(220, 53, 69); // Rojo
            $this->SetTextColor(255, 255, 255); // Texto blanco
            $this->Cell(47.5, 6, mb_convert_encoding('SOBRANTE DE CAJA', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
            $this->Cell(47.5, 6, $fechaInicioTurno, 1, 0, 'C', true);
            $this->Cell(47.5, 6, $fechaCierre, 1, 0, 'C', true);
            $this->Cell(47.5, 6, 'LPS ' . number_format($sobranteCaja, 2), 1, 1, 'R', true);
            
            // Restaurar colores normales
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0, 0, 0);
        }

        // Mostrar faltantes con franja roja si existen - REPORTE ADMINISTRATIVO
        $faltantes = $this->obtenerFaltantesAdmin($fechaDesde, $fechaHasta, $connect);
        if ($faltantes['cantidad'] > 0) {
            $this->SetFillColor(220, 53, 69); // Rojo
            $this->SetTextColor(255, 255, 255); // Texto blanco
            $this->Cell(47.5, 6, mb_convert_encoding('FALTANTES', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
            $this->Cell(47.5, 6, $fechaInicioTurno, 1, 0, 'C', true);
            $this->Cell(47.5, 6, $fechaCierre, 1, 0, 'C', true);
            $this->Cell(47.5, 6, 'LPS ' . number_format($faltantes['monto'], 2), 1, 1, 'R', true);
            
            // Restaurar colores normales
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0, 0, 0);
        }

        // Línea de subtotal
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(142.5, 8, mb_convert_encoding('Subtotal de efectivo', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
        $this->Cell(47.5, 8, 'LPS ' . number_format($efectivoInicial + $sobranteCaja - $faltantes['monto'], 2), 1, 1, 'R', true);
    }

    // Función para obtener fecha de inicio del turno
    function obtenerFechaInicioTurno($fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        try {
            $stmt = $connect->prepare("
                SELECT DATE(fecha_inicio) as fecha_inicio
                FROM turnos_iniciados 
                WHERE DATE(fecha_inicio) BETWEEN ? AND ?
                AND usuario = ?
                ORDER BY fecha_inicio ASC
                LIMIT 1
            ");
            $stmt->execute([$fechaDesde, $fechaHasta, $usuarioActual]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['fecha_inicio'] ?? 'N/A';
        } catch (Exception $e) {
            return 'N/A';
        }
    }

    // Función para obtener fecha de cierre
    function obtenerFechaCierre($fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        try {
            $stmt = $connect->prepare("
                SELECT DATE(fecha_cierre) as fecha_cierre
                FROM cierre_caja 
                WHERE DATE(fecha_cierre) BETWEEN ? AND ?
                AND usuario_cierre = ?
                ORDER BY fecha_cierre DESC
                LIMIT 1
            ");
            $stmt->execute([$fechaDesde, $fechaHasta, $usuarioActual]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['fecha_cierre'] ?? 'N/A';
        } catch (Exception $e) {
            return 'N/A';
        }
    }

    // Función para obtener efectivo inicial del período
    // NOTA: El campo efectivo_inicial fue eliminado del sistema, siempre retorna 0.00
    function obtenerEfectivoInicial($fechaDesde, $fechaHasta, $usuarioActual, $connect) {
        // El campo efectivo_inicial fue eliminado del sistema, siempre retorna 0.00
        return 0.00;
    }

    // FUNCIONES ADMINISTRATIVAS (SIN FILTRO DE USUARIO)
    
    // Función para obtener sobrante de caja del período (ADMIN, opcional por usuario de caja)
    function obtenerSobranteCajaAdmin($fechaDesde, $fechaHasta, $connect) {
        try {
            $sql = "SELECT COALESCE(SUM(sobrante_caja), 0) as total_sobrante FROM cierre_caja WHERE DATE(fecha_cierre) BETWEEN ? AND ?";
            $params = [$fechaDesde, $fechaHasta];
            if (!empty($this->usuarioCaja)) {
                $sql .= " AND nombre_completo = ?";
                $params[] = $this->usuarioCaja;
            }
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total_sobrante'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    // Función para obtener facturas pendientes (faltantes) - ADMIN, opcional por usuario de caja
    function obtenerFaltantesAdmin($fechaDesde, $fechaHasta, $connect) {
        try {
            $sql = "SELECT COUNT(*) as cantidad, COALESCE(SUM(total_price), 0) as monto FROM orders WHERE DATE(placed_on) BETWEEN ? AND ? AND invoice_status = 'Pendiente'";
            $params = [$fechaDesde, $fechaHasta];
            if (!empty($this->usuarioCaja)) {
                $sql .= " AND (processed_by = ? OR updated_by = ?)";
                $params[] = $this->usuarioCaja;
                $params[] = $this->usuarioCaja;
            }
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'cantidad' => $result['cantidad'] ?? 0,
                'monto' => $result['monto'] ?? 0
            ];
        } catch (Exception $e) {
            return ['cantidad' => 0, 'monto' => 0];
        }
    }

    // Función para obtener número de facturas por método de pago - ADMIN, opcional por usuario de caja
    function obtenerNumeroFacturasAdmin($metodo, $fechaDesde, $fechaHasta, $connect) {
        try {
            $metodosMap = [
                'EFECTIVO' => 'EFECTIVO',
                'TARJETA' => 'TARJETA',
                'efectivo' => 'EFECTIVO',
                'tarjeta' => 'TARJETA',
                'CREDITO' => 'CREDITO',
                'credito' => 'CREDITO'
            ];
            
            $metodoConsulta = $metodosMap[$metodo] ?? $metodo;
            
            $sql = "SELECT COUNT(*) as cantidad FROM orders WHERE DATE(placed_on) BETWEEN ? AND ? AND invoice_status = 'Cobrada' AND method = ?";
            $params = [$fechaDesde, $fechaHasta, $metodoConsulta];
            if (!empty($this->usuarioCaja)) {
                $sql .= " AND (processed_by = ? OR updated_by = ?)";
                $params[] = $this->usuarioCaja;
                $params[] = $this->usuarioCaja;
            }
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['cantidad'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    // Función para agregar sección de honorarios - ADMIN
    function agregarSeccionHonorariosAdmin($fechaDesde, $fechaHasta, $connect) {
        // Verificar si necesitamos una nueva página
        if ($this->GetY() > 220) {
            $this->AddPage();
        } else {
            $this->Ln(8);
        }

        // Título de honorarios
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(6, 173, 191);
        $this->Cell(0, 8, mb_convert_encoding('GASTOS Y HONORARIOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(3);

        // Header de la tabla de honorarios
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(6, 173, 191);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(30, 8, mb_convert_encoding('Fecha', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(35, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(45, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(45, 8, mb_convert_encoding('Médico', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->Cell(35, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);

        // Obtener honorarios del período - ADMIN
        $honorarios = $this->obtenerHonorariosAdmin($fechaDesde, $fechaHasta, $connect);
        $totalHonorarios = 0;

        foreach ($honorarios as $honorario) {
            // Verificar espacio
            if ($this->GetY() > 270) {
                $this->AddPage();
                // Repetir header
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(6, 173, 191);
                $this->SetTextColor(255, 255, 255);
                $this->Cell(30, 8, mb_convert_encoding('Fecha', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $this->Cell(35, 8, mb_convert_encoding('# Factura', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $this->Cell(45, 8, mb_convert_encoding('Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $this->Cell(45, 8, mb_convert_encoding('Médico', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $this->Cell(35, 8, mb_convert_encoding('Monto', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 8);
            }

            // Si el honorario no está pagado, usar fondo rojo
            if ($honorario['estado_pago'] != 'pagado') {
                $this->SetFillColor(220, 53, 69); // Rojo
                $this->SetTextColor(255, 255, 255); // Texto blanco
                $usarFondo = true;
            } else {
                $usarFondo = false;
            }

            $this->Cell(30, 6, $honorario['fecha'], 1, 0, 'C', $usarFondo);
            $this->Cell(35, 6, $honorario['numero_factura'], 1, 0, 'C', $usarFondo);
            $this->Cell(45, 6, mb_convert_encoding($this->truncateText($honorario['paciente'], 20), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', $usarFondo);
            $this->Cell(45, 6, mb_convert_encoding($this->truncateText($honorario['medico'], 20), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', $usarFondo);
            $this->Cell(35, 6, 'LPS ' . number_format($honorario['monto'], 2), 1, 1, 'R', $usarFondo);

            // Restaurar colores
            if ($usarFondo) {
                $this->SetFillColor(255, 255, 255);
                $this->SetTextColor(0, 0, 0);
            }

            $totalHonorarios += $honorario['monto'];
        }

        // Total de gastos
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(155, 8, mb_convert_encoding('Total Honorarios:', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R', true);
        $this->Cell(35, 8, 'LPS ' . number_format($totalHonorarios, 2), 1, 1, 'R', true);
    }

    // Función para obtener honorarios del período - ADMIN, opcional por usuario de caja
    function obtenerHonorariosAdmin($fechaDesde, $fechaHasta, $connect) {
        try {
            $sql = "
                SELECT 
                    DATE(o.placed_on) as fecha,
                    o.invoice_number as numero_factura,
                    o.nomcl as paciente,
                    CONCAT(d.nodoc, ' ', d.apdoc) as medico,
                    h.monto_honorario as monto,
                    h.estado_pago
                FROM honorarios_medicos h
                INNER JOIN orders o ON h.id_factura = o.idord
                INNER JOIN doctor d ON h.id_doctor = d.idodc
                WHERE DATE(o.placed_on) BETWEEN ? AND ?
                AND h.estado_pago = 'pagado'
            ";
            $params = [$fechaDesde, $fechaHasta];
            if (!empty($this->usuarioCaja)) {
                $sql .= " AND (o.processed_by = ? OR o.updated_by = ?)";
                $params[] = $this->usuarioCaja;
                $params[] = $this->usuarioCaja;
            }
            $sql .= " ORDER BY d.nodoc ASC, o.placed_on ASC";
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // Función para calcular total de gastos - ADMIN, opcional por usuario de caja
    function calcularTotalGastosAdmin($fechaDesde, $fechaHasta, $connect) {
        try {
            $sql = "
                SELECT COALESCE(SUM(h.monto_honorario), 0) as total
                FROM honorarios_medicos h
                INNER JOIN orders o ON h.id_factura = o.idord
                WHERE DATE(o.placed_on) BETWEEN ? AND ?
                AND h.estado_pago = 'pagado'
            ";
            $params = [$fechaDesde, $fechaHasta];
            if (!empty($this->usuarioCaja)) {
                $sql .= " AND (o.processed_by = ? OR o.updated_by = ?)";
                $params[] = $this->usuarioCaja;
                $params[] = $this->usuarioCaja;
            }
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    // Función para obtener fecha de inicio del turno - ADMIN, opcional por usuario de caja
    function obtenerFechaInicioTurnoAdmin($fechaDesde, $fechaHasta, $connect) {
        try {
            $sql = "SELECT DATE(fecha_inicio) as fecha_inicio FROM turnos_iniciados WHERE DATE(fecha_inicio) BETWEEN ? AND ?";
            $params = [$fechaDesde, $fechaHasta];
            if (!empty($this->usuarioCaja)) {
                $sql .= " AND nombre_completo = ?";
                $params[] = $this->usuarioCaja;
            }
            $sql .= " ORDER BY fecha_inicio ASC LIMIT 1";
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['fecha_inicio'] ?? 'N/A';
        } catch (Exception $e) {
            return 'N/A';
        }
    }

    // Función para obtener fecha de cierre - ADMIN, opcional por usuario de caja
    function obtenerFechaCierreAdmin($fechaDesde, $fechaHasta, $connect) {
        try {
            $sql = "SELECT DATE(fecha_cierre) as fecha_cierre FROM cierre_caja WHERE DATE(fecha_cierre) BETWEEN ? AND ?";
            $params = [$fechaDesde, $fechaHasta];
            if (!empty($this->usuarioCaja)) {
                $sql .= " AND nombre_completo = ?";
                $params[] = $this->usuarioCaja;
            }
            $sql .= " ORDER BY fecha_cierre DESC LIMIT 1";
            $stmt = $connect->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['fecha_cierre'] ?? 'N/A';
        } catch (Exception $e) {
            return 'N/A';
        }
    }

    // Función para obtener efectivo inicial del período - ADMIN
    // NOTA: El campo efectivo_inicial fue eliminado del sistema, siempre retorna 0.00
    function obtenerEfectivoInicialAdmin($fechaDesde, $fechaHasta, $connect) {
        // El campo efectivo_inicial fue eliminado del sistema, siempre retorna 0.00
        return 0.00;
    }
}

// Crear documento PDF (con filtro opcional por usuario de caja)
$pdf = new PDF($fecha_desde, $fecha_hasta, $usuario_caja);
$pdf->AliasNbPages();
$pdf->AddPage();

// Variables para totales generales (REPORTE ADMINISTRATIVO)
$totalVentasGeneral = 0;
$totalFacturasGeneral = 0;
$totalCobradasGeneral = 0;
$totalPendientesGeneral = 0;
$metodosTotales = [];

// Nota según tipo de reporte (todos los cajeros o por usuario de caja)
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(102, 102, 102);
if ($usuario_caja) {
    $pdf->Cell(0, 5, mb_convert_encoding('Reporte por usuario de caja: ' . $usuario_caja, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} else {
    $pdf->Cell(0, 5, mb_convert_encoding('Reporte administrativo consolidado - Todos los cajeros', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
}
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

// Calcular totales reales directamente de las transacciones (todos los cajeros o por usuario)
try {
    $whereTotales = "DATE(o.placed_on) BETWEEN ? AND ? AND o.invoice_status = 'Cobrada'";
    $paramsTotales = [$fecha_desde, $fecha_hasta];
    if ($usuario_caja) {
        $whereTotales .= " AND (o.processed_by = ? OR o.updated_by = ?)";
        $paramsTotales[] = $usuario_caja;
        $paramsTotales[] = $usuario_caja;
    }
    $stmtTotalesReales = $connect->prepare("
        SELECT 
            o.method,
            COUNT(*) as num_facturas,
            SUM(o.total_price) as total_monto
        FROM orders o
        WHERE $whereTotales
        GROUP BY o.method
        ORDER BY total_monto DESC
    ");
    $stmtTotalesReales->execute($paramsTotales);
    $totalesReales = $stmtTotalesReales->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir array de métodos totales
    foreach ($totalesReales as $total) {
        $metodosTotales[$total['method']] = $total['total_monto'];
        $totalVentasGeneral += $total['total_monto'];
        $totalFacturasGeneral += $total['num_facturas'];
    }
    
    // Obtener facturas cobradas y pendientes (con filtro por usuario si aplica)
    $whereEstad = "DATE(o.placed_on) BETWEEN ? AND ?";
    $paramsEstad = [$fecha_desde, $fecha_hasta];
    if ($usuario_caja) {
        $whereEstad .= " AND (o.processed_by = ? OR o.updated_by = ?)";
        $paramsEstad[] = $usuario_caja;
        $paramsEstad[] = $usuario_caja;
    }
    $stmtEstadisticas = $connect->prepare("
        SELECT 
            COUNT(CASE WHEN invoice_status = 'Cobrada' THEN 1 END) as cobradas,
            COUNT(CASE WHEN invoice_status = 'Pendiente' THEN 1 END) as pendientes
        FROM orders o
        WHERE $whereEstad
    ");
    $stmtEstadisticas->execute($paramsEstad);
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
        // Verificar espacio antes de agregar nueva sección
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

// Verificar espacio para el resumen
if ($pdf->GetY() > 250) {
    $pdf->AddPage();
}

// Resumen general del período
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(6, 173, 191);
$pdf->Cell(0, 8, mb_convert_encoding('RESUMEN GENERAL DEL PERÍODO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(5);

// Tabla resumen
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(6, 173, 191);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(95, 8, mb_convert_encoding('CONCEPTO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
$pdf->Cell(95, 8, mb_convert_encoding('TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(95, 8, mb_convert_encoding('Total Ventas del Período', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell(95, 8, 'LPS ' . number_format($totalVentasGeneral, 2), 1, 1, 'R');

$pdf->Cell(95, 8, mb_convert_encoding('Total Facturas Procesadas', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell(95, 8, number_format($totalFacturasGeneral), 1, 1, 'R');

$pdf->Cell(95, 8, mb_convert_encoding('Total Facturas Cobradas', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell(95, 8, number_format($totalCobradasGeneral), 1, 1, 'R');

$pdf->Cell(95, 8, mb_convert_encoding('Total Facturas Pendientes', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell(95, 8, number_format($totalPendientesGeneral), 1, 1, 'R');

$pdf->Ln(6);

// Nueva sección: Resumen de Cierre de Caja
$pdf->agregarResumenCierreCaja($fecha_desde, $fecha_hasta, null, $connect, $metodosTotales);

// Agregar sección de totales de efectivo y sobrante/faltante
$pdf->agregarTotalesEfectivo($fecha_desde, $fecha_hasta, null, $connect);

// VERIFICACIÓN CRÍTICA DE ESPACIO PARA ANÁLISIS DETALLADO - RESPETAR FOOTER
// El footer está en Y = -25, necesitamos al menos 60mm libres para esta sección completa
if ($pdf->GetY() > 200) {
    $pdf->AddPage();
} else {
    $pdf->Ln(6);
}

// Resumen de productos/servicios
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(6, 173, 191);
$pdf->Cell(0, 8, mb_convert_encoding('ANÁLISIS DETALLADO DEL PERÍODO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(5);

// Obtener y mostrar productos/servicios más vendidos (con filtro por usuario de caja si aplica)
try {
    $sqlProductos = "
        SELECT 
            od.descripcion,
            od.item_type,
            SUM(od.cantidad) as total_cantidad,
            SUM(od.total_after_discount) as total_vendido,
            COUNT(DISTINCT od.order_id) as num_ordenes
        FROM order_details od
        INNER JOIN orders o ON od.order_id = o.idord
        WHERE DATE(o.placed_on) BETWEEN ? AND ?
            AND o.invoice_status = 'Cobrada'
    ";
    $paramsProductos = [$fecha_desde, $fecha_hasta];
    if ($usuario_caja) {
        $sqlProductos .= " AND (o.processed_by = ? OR o.updated_by = ?)";
        $paramsProductos[] = $usuario_caja;
        $paramsProductos[] = $usuario_caja;
    }
    $sqlProductos .= " GROUP BY od.descripcion, od.item_type ORDER BY total_vendido DESC LIMIT 10";
    $stmt = $connect->prepare($sqlProductos);
    $stmt->execute($paramsProductos);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($productos)) {
        // VERIFICACIÓN CRÍTICA DE ESPACIO - RESPETAR FOOTER
        // El footer está en Y = -25, necesitamos al menos 40mm libres
        if ($pdf->GetY() > 220) {
            $pdf->AddPage();
        }
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(6, 173, 191);
        $pdf->Cell(0, 8, mb_convert_encoding('TOP 10 PRODUCTOS/SERVICIOS MÁS VENDIDOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Header de tabla de productos (Total: 190mm)
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(6, 173, 191);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(80, 8, mb_convert_encoding('Descripción', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(30, 8, mb_convert_encoding('Tipo', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, mb_convert_encoding('Cantidad', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, mb_convert_encoding('Órdenes', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(30, 8, mb_convert_encoding('Total Vendido', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 8);
        
        foreach ($productos as $producto) {
            // VERIFICACIÓN PROFESIONAL DE ESPACIO - RESPETAR FOOTER COMPLETAMENTE
            // Altura del footer: 25mm + margen de seguridad: 15mm = 40mm mínimo
            // Cada fila de datos: 6mm, header: 8mm, título: 13mm
            // Total mínimo necesario: 27mm + margen de seguridad
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
                // Repetir header completo
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(6, 173, 191);
                $pdf->Cell(0, 8, mb_convert_encoding('TOP 10 PRODUCTOS/SERVICIOS MÁS VENDIDOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
                $pdf->Ln(5);
                
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetFillColor(6, 173, 191);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(80, 8, mb_convert_encoding('Descripción', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(30, 8, mb_convert_encoding('Tipo', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(25, 8, mb_convert_encoding('Cantidad', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(25, 8, mb_convert_encoding('Órdenes', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(30, 8, mb_convert_encoding('Total Vendido', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 8);
            }
            
            $descripcion = $pdf->truncateText($producto['descripcion'], 45);
            $tipo = convertirMayusculas($producto['item_type']);
            
            $pdf->Cell(80, 6, mb_convert_encoding($descripcion, 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
            $pdf->Cell(30, 6, mb_convert_encoding($tipo, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $pdf->Cell(25, 6, number_format($producto['total_cantidad']), 1, 0, 'C');
            $pdf->Cell(25, 6, number_format($producto['num_ordenes']), 1, 0, 'C');
            $pdf->Cell(30, 6, 'LPS ' . number_format($producto['total_vendido'], 2), 1, 1, 'R');
        }
    }
} catch (Exception $e) {
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, mb_convert_encoding('Error al obtener análisis de productos: ' . $e->getMessage(), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
}

// VERIFICACIÓN CRÍTICA DE ESPACIO PARA ESTADÍSTICAS - RESPETAR FOOTER
// El footer está en Y = -25, necesitamos al menos 50mm libres para esta sección completa
if ($pdf->GetY() > 210) {
    $pdf->AddPage();
} else {
    $pdf->Ln(6);
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(6, 173, 191);
$pdf->Cell(0, 6, mb_convert_encoding('ESTADÍSTICAS DEL PERÍODO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Ln(3);

try {
    // Resumen por tipo de paciente (con filtro por usuario de caja si aplica)
    $sqlTipos = "
        SELECT 
            o.tipo,
            COUNT(*) as num_facturas,
            SUM(o.total_price) as total_monto,
            AVG(o.total_price) as promedio_factura
        FROM orders o
        WHERE DATE(o.placed_on) BETWEEN ? AND ?
            AND o.invoice_status = 'Cobrada'
    ";
    $paramsTipos = [$fecha_desde, $fecha_hasta];
    if ($usuario_caja) {
        $sqlTipos .= " AND (o.processed_by = ? OR o.updated_by = ?)";
        $paramsTipos[] = $usuario_caja;
        $paramsTipos[] = $usuario_caja;
    }
    $sqlTipos .= " GROUP BY o.tipo ORDER BY total_monto DESC";
    $stmt = $connect->prepare($sqlTipos);
    $stmt->execute($paramsTipos);
    $tiposPaciente = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($tiposPaciente)) {
        // VERIFICACIÓN ADICIONAL DE ESPACIO ANTES DE CREAR TABLA
        if ($pdf->GetY() > 230) {
            $pdf->AddPage();
        }
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 6, mb_convert_encoding('Resumen por Tipo de Paciente:', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $pdf->Ln(2);
        
        // Header (Total: 190mm)
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(6, 173, 191);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(70, 8, mb_convert_encoding('Tipo de Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(40, 8, mb_convert_encoding('Facturas', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(40, 8, mb_convert_encoding('Total Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(40, 8, mb_convert_encoding('Promedio', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 9);
        
        foreach ($tiposPaciente as $tipo) {
            // VERIFICACIÓN PROFESIONAL DE ESPACIO - RESPETAR FOOTER COMPLETAMENTE
            // Altura del footer: 25mm + margen de seguridad: 20mm = 45mm mínimo
            // Cada fila de datos: 6mm, header: 8mm, subtítulo: 8mm, título: 9mm
            // Total mínimo necesario: 31mm + margen de seguridad
            if ($pdf->GetY() > 235) {
                $pdf->AddPage();
                // Repetir header completo
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(6, 173, 191);
                $pdf->Cell(0, 6, mb_convert_encoding('ESTADÍSTICAS DEL PERÍODO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
                $pdf->Ln(3);
                
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell(0, 6, mb_convert_encoding('Resumen por Tipo de Paciente:', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
                $pdf->Ln(2);
                
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetFillColor(6, 173, 191);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(70, 8, mb_convert_encoding('Tipo de Paciente', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(40, 8, mb_convert_encoding('Facturas', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(40, 8, mb_convert_encoding('Total Monto', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(40, 8, mb_convert_encoding('Promedio', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 9);
            }
            
            $pdf->Cell(70, 6, mb_convert_encoding(convertirMayusculas($tipo['tipo']), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
            $pdf->Cell(40, 6, number_format($tipo['num_facturas']), 1, 0, 'C');
            $pdf->Cell(40, 6, 'LPS ' . number_format($tipo['total_monto'], 2), 1, 0, 'R');
            $pdf->Cell(40, 6, 'LPS ' . number_format($tipo['promedio_factura'], 2), 1, 1, 'R');
        }
    }
    

} catch (Exception $e) {
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, mb_convert_encoding('Error al obtener estadísticas: ' . $e->getMessage(), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
}

// Salida del PDF
try {
    $filename = 'Cierre Caja' . date('Y-m-d', strtotime($fecha_desde)) . '_al_' . date('Y-m-d', strtotime($fecha_hasta)) . '.pdf';
    $pdf->Output('D', $filename);
} catch (Exception $e) {
    echo 'Error al generar el PDF: ' . $e->getMessage();
}
?>