<?php
/**
 * Genera el Manual de Usuario del módulo RRHH (MEDIDATA) en PDF.
 *
 * Uso CLI:  php backend/scripts/generar_manual_rrhh_pdf.php
 * Salida:   backend/docs/manual_usuario_rrhh.pdf
 */
declare(strict_types=1);

require_once __DIR__ . '/../fpdf/fpdf.php';

date_default_timezone_set('America/Tegucigalpa');

final class ManualRrhhPdf extends FPDF
{
    private string $docTitle = 'Manual de Usuario — Recursos Humanos';

    public function t(string $text): string
    {
        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    }

    public function Header(): void
    {
        if ($this->PageNo() === 1) {
            return;
        }
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(3, 92, 103);
        $this->Cell(0, 8, $this->t($this->docTitle), 0, 1, 'L');
        $this->SetDrawColor(6, 173, 191);
        $this->Line(10, 18, $this->GetPageWidth() - 10, 18);
        $this->Ln(2);
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 8, $this->t('Hospital MEDICASA — MEDIDATA | Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    public function coverPage(): void
    {
        $this->AddPage();
        $logo = __DIR__ . '/../img/factura_logo.png';
        if (is_readable($logo)) {
            $this->Image($logo, 55, 22, 95);
        }
        $this->Ln(55);
        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(3, 92, 103);
        $this->MultiCell(0, 10, $this->t('Manual de Usuario'), 0, 'C');
        $this->Ln(4);
        $this->SetFont('Arial', 'B', 16);
        $this->MultiCell(0, 8, $this->t('Módulo de Recursos Humanos (RRHH)'), 0, 'C');
        $this->Ln(10);
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(60, 60, 60);
        $this->MultiCell(0, 7, $this->t('Sistema MEDIDATA — Hospital MEDICASA S. de R.L.'), 0, 'C');
        $this->Ln(8);
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 6, $this->t('Versión del manual: ' . date('d/m/Y')), 0, 'C');
        $this->Ln(20);
        $this->SetFont('Arial', 'I', 10);
        $this->MultiCell(0, 5, $this->t(
            'Este documento describe el uso funcional del módulo RRHH: reclutamiento, vacantes, postulantes web, gestión de candidatos, entrevistas y colaboradores.'
        ), 0, 'C');
    }

    public function sectionTitle(string $title): void
    {
        $this->Ln(4);
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(3, 92, 103);
        $this->MultiCell(0, 7, $this->t($title), 0, 'L');
        $this->SetDrawColor(6, 173, 191);
        $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
        $this->Ln(4);
        $this->SetTextColor(0, 0, 0);
    }

    public function subTitle(string $title): void
    {
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(40, 40, 40);
        $this->MultiCell(0, 6, $this->t($title), 0, 'L');
        $this->Ln(1);
        $this->SetTextColor(0, 0, 0);
    }

    public function paragraph(string $text): void
    {
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 5.5, $this->t($text), 0, 'J');
        $this->Ln(2);
    }

    /** @param array<int, string> $items */
    public function bulletList(array $items): void
    {
        $this->SetFont('Arial', '', 10);
        foreach ($items as $item) {
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetX($x + 4);
            $this->Cell(4, 5.5, $this->t('•'), 0, 0);
            $this->SetXY($x + 10, $y);
            $this->MultiCell(0, 5.5, $this->t($item), 0, 'J');
        }
        $this->Ln(2);
    }

    public function noteBox(string $text): void
    {
        $this->SetFillColor(240, 249, 250);
        $this->SetDrawColor(6, 173, 191);
        $this->SetFont('Arial', 'I', 9);
        $this->MultiCell(0, 5, $this->t('Nota: ' . $text), 1, 'J', true);
        $this->Ln(3);
    }

    public function tableOfContents(): void
    {
        $this->AddPage();
        $this->sectionTitle('Tabla de contenido');
        $entries = [
            '1. Introducción',
            '2. Acceso y navegación',
            '3. Panel principal (Escritorio RRHH)',
            '4. Proceso de reclutamiento',
            '   4.1 Puestos de trabajo',
            '   4.2 Vacantes de trabajo',
            '   4.3 Postulantes del sitio web',
            '   4.4 Postulantes y candidatos por vacante',
            '   4.5 Detalle del candidato',
            '   4.6 Entrevistas, pruebas y requisitos',
            '5. Colaboradores y registro de personal',
            '6. Reloj biométrico',
            '7. Búsqueda, paginación y exportación',
            '8. Estados del candidato',
            '9. Buenas prácticas y resolución de problemas',
        ];
        $this->bulletList($entries);
    }

    public function buildContent(): void
    {
        $this->AddPage();
        $this->sectionTitle('1. Introducción');
        $this->paragraph(
            'El módulo de Recursos Humanos (RRHH) de MEDIDATA centraliza la gestión del ciclo de reclutamiento del Hospital MEDICASA: desde la publicación de vacantes y la recepción de postulaciones del sitio web, hasta el seguimiento del candidato en entrevistas, pruebas psicométricas, requisitos de contratación y contratación final.'
        );
        $this->paragraph(
            'El módulo se integra con la base de datos de postulaciones web (medic9ue_postulaciones) y la base de datos de entrevistas RRHH (medic9ue_medi_rrhh_interviews). Las pantallas están disponibles en variantes administrativas y de usuario (_usr), con la misma funcionalidad adaptada al menú de cada perfil.'
        );

        $this->sectionTitle('2. Acceso y navegación');
        $this->paragraph('Inicie sesión en MEDIDATA con su usuario autorizado. En el menú lateral, expanda RECURSOS HUMANOS para acceder a las opciones principales.');
        $this->bulletList([
            'Panel: escritorio con indicadores y agenda.',
            'Lista colaboradores: personal activo en el hospital.',
            'Posiciones de trabajo: catálogo de posiciones organizacionales.',
            'Proceso de reclutamiento: puestos, vacantes, solicitudes, postulantes, entrevistas, pruebas y requisitos.',
            'Personal: postulantes website y reloj biométrico.',
            'Colaboradores: registro de enfermería, administrativo, servicios generales y médicos.',
        ]);

        $this->sectionTitle('3. Panel principal (Escritorio RRHH)');
        $this->paragraph('La pantalla Escritorio muestra un resumen ejecutivo del módulo:');
        $this->bulletList([
            'Colaboradores: total de personal registrado en el hospital.',
            'Vacantes activas: vacantes abiertas con fecha vigente.',
            'Postulantes web: total de solicitudes recibidas desde el sitio web.',
            'En entrevista: candidatos con estado Entrevista o Agendado.',
            'Agenda: calendario con entrevistas programadas y panel de notificaciones.',
        ]);

        $this->sectionTitle('4. Proceso de reclutamiento');
        $this->paragraph(
            'El flujo recomendado es: definir puestos → abrir vacantes → recibir postulaciones web → incorporar candidatos al proceso → gestionar estados → contratar o descartar.'
        );

        $this->subTitle('4.1 Puestos de trabajo');
        $this->bulletList([
            'Menú: Proceso de reclutamiento → Puestos de trabajo.',
            'Visualice los puestos en tarjetas con departamento, jefe inmediato y objetivo.',
            'Use el buscador para filtrar por nombre, departamento u objetivo.',
            'Registre o edite puestos desde las opciones del módulo.',
        ]);

        $this->subTitle('4.2 Vacantes de trabajo');
        $this->bulletList([
            'Menú: Proceso de reclutamiento → Vacantes de trabajo.',
            'Las vacantes se agrupan por prioridad: Urgente, Alta, Media y Baja.',
            'Cada tarjeta muestra puesto, plazas, número de postulantes, fechas y motivo.',
            'Use el interruptor para marcar la vacante como Abierta o Cerrada.',
            'Botón Postulantes: abre el listado de candidatos de esa vacante.',
            'Botón editar: modifica los datos de la vacante.',
            'Búsqueda: filtre por nombre de vacante, puesto, departamento o motivo.',
            'Paginación: se muestran 8 vacantes por página (Anterior / Siguiente).',
        ]);

        $this->subTitle('4.3 Postulantes del sitio web');
        $this->paragraph(
            'Menú: Personal → Postulantes website (también disponible en Recursos → Reclutamiento para administradores).'
        );
        $this->bulletList([
            'Tabla server-side con paginación, ordenamiento y búsqueda global.',
            'Columnas: DNI, nombre, puesto aspirado, vacante sugerida, estado, contacto y fecha.',
            'Icono ojo: visualiza la hoja de vida en modal (sin descarga obligatoria).',
            'Incorporar: confirma y asigna el candidato a la vacante sugerida del sistema.',
            'Descartar: registra la postulación como descartada (motivo opcional).',
            'Ver candidato: disponible cuando el estado es Incorporado; abre el detalle RRHH.',
            'Reasignar vacante: cambia la vacante de un candidato ya incorporado.',
        ]);
        $this->noteBox(
            'Un registro en Postulantes website no es lo mismo que un candidato RRHH. Debe usar Incorporar para que aparezca en el listado de postulantes por vacante.'
        );

        $this->subTitle('4.4 Postulantes y candidatos por vacante');
        $this->bulletList([
            'Acceda desde Vacantes de trabajo → botón Postulantes.',
            'Listado en tarjetas con nombre, DNI, teléfono, email, fecha de aplicación y puntaje.',
            'Badge de estado en una sola línea (En Espera, Entrevista, Contratado, etc.).',
            'Ver Perfil: abre el detalle completo del candidato.',
            'Búsqueda por nombre, DNI o correo electrónico.',
            'Paginación de 8 candidatos por página.',
        ]);

        $this->subTitle('4.5 Detalle del candidato');
        $this->bulletList([
            'Muestra información general, vacante, puesto, origen web (si aplica) y observaciones.',
            'Avance rápido: botones para cambiar a Formulario, Entrevista, Psicométricas, Expediente, Contratado o Descartar.',
            'Cambiar estado: selector con todos los estados válidos y campo de observaciones.',
            'Guardar estado: persiste el cambio en la base RRHH.',
            'Volver: regresa a la pantalla anterior conservando el contexto de navegación.',
        ]);

        $this->subTitle('4.6 Entrevistas, pruebas y requisitos');
        $this->bulletList([
            'Entrevistas: gestión de entrevistas programadas (menú Proceso de reclutamiento).',
            'Pruebas psicométricas: seguimiento de evaluaciones aplicadas a candidatos.',
            'Requisitos de contratación: checklist documental previo a la contratación.',
            'La agenda del escritorio complementa la visualización de entrevistas próximas.',
        ]);

        $this->sectionTitle('5. Colaboradores y registro de personal');
        $this->bulletList([
            'Lista colaboradores: consulta del personal registrado.',
            'Posiciones de trabajo: catálogo organizacional.',
            'Registrar enfermería, administrativo, servicios generales o médico desde el submenú Colaboradores.',
            'Cada tipo de personal tiene formularios de alta y edición dedicados.',
        ]);

        $this->sectionTitle('6. Reloj biométrico');
        $this->paragraph(
            'Desde Personal → Reloj biométrico puede consultar marcaciones importadas del dispositivo, filtrar por fechas y exportar datos. Esta función complementa la gestión de asistencia del personal.'
        );

        $this->sectionTitle('7. Búsqueda, paginación y exportación');
        $this->bulletList([
            'Postulantes website: buscador DataTables + botones copy, CSV, Excel, PDF e impresión.',
            'Vacantes y candidatos por vacante: buscador integrado con botón Buscar o tecla Enter.',
            'Paginación de tarjetas: 8 registros por página en vacantes y listado de candidatos.',
            'CV en modal: vista previa inline; descarga opcional desde el pie del modal.',
        ]);

        $this->sectionTitle('8. Estados del candidato');
        $this->paragraph('Estados disponibles en el proceso RRHH:');
        $this->bulletList([
            'En Espera — recién incorporado, pendiente de gestión.',
            'Formulario Empleados — completando documentación inicial.',
            'Entrevista / Agendado / Entrevistado — fases de entrevista.',
            'Pruebas Psicométricas — evaluación psicométrica.',
            'Llenando Expediente — integración documental.',
            'Contratado — proceso exitoso.',
            'Descartado — candidato no continúa.',
        ]);
        $this->paragraph('Estados de postulación web (tabla aplica): Pendiente, Incorporado, Descartado.');

        $this->sectionTitle('9. Buenas prácticas y resolución de problemas');
        $this->bulletList([
            'Mantenga vacantes abiertas solo mientras estén vigentes; cierre las que ya no aplican.',
            'Incorpore postulantes web con vacante sugerida antes de buscarlos en postulantes por vacante.',
            'Use observaciones en el detalle del candidato para dejar trazabilidad de cada cambio.',
            'Si la búsqueda no devuelve resultados, verifique ortografía o pruebe con DNI parcial.',
            'Recargue con Ctrl+F5 si no ve cambios recientes en pantalla (actualización de caché).',
            'Si aparece error de base de datos, contacte al administrador del sistema MEDIDATA.',
        ]);
        $this->noteBox(
            'Para soporte técnico interno, indique pantalla, acción realizada, mensaje de error y captura de pantalla si es posible.'
        );
    }
}

function generarManualRrhhPdf(string $outputPath): string
{
    $dir = dirname($outputPath);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('No se pudo crear el directorio: ' . $dir);
    }

    $pdf = new ManualRrhhPdf('P', 'mm', 'Letter');
    $pdf->AliasNbPages();
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->SetMargins(15, 22, 15);

    $pdf->coverPage();
    $pdf->tableOfContents();
    $pdf->buildContent();

    $pdf->Output('F', $outputPath);

    return $outputPath;
}

$isCli = PHP_SAPI === 'cli';
$output = __DIR__ . '/../docs/manual_usuario_rrhh.pdf';

try {
    $path = generarManualRrhhPdf($output);
    if ($isCli) {
        echo "Manual generado: {$path}\n";
        echo 'Tamaño: ' . round(filesize($path) / 1024, 1) . " KB\n";
    } else {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="manual_usuario_rrhh.pdf"');
        readfile($path);
    }
} catch (Throwable $e) {
    if ($isCli) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    echo 'Error al generar el manual.';
}
