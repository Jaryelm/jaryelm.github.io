<?php
/**
 * Genera la Guía de Pruebas — Avance rápido del candidato (RRHH).
 *
 * Uso CLI:  c:\xampp\php\php.exe backend/scripts/generar_guia_pruebas_rrhh_pdf.php
 * Salida:   backend/docs/guia_pruebas_rrhh_avance_rapido.pdf
 */
declare(strict_types=1);

require_once __DIR__ . '/../fpdf/fpdf.php';

date_default_timezone_set('America/Tegucigalpa');

final class GuiaPruebasRrhhPdf extends FPDF
{
    private string $docTitle = 'Guía de Pruebas — Avance Rápido RRHH';

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
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(3, 92, 103);
        $this->MultiCell(0, 10, $this->t('Guía de Pruebas'), 0, 'C');
        $this->Ln(4);
        $this->SetFont('Arial', 'B', 15);
        $this->MultiCell(0, 8, $this->t('Avance Rápido del Candidato (RRHH)'), 0, 'C');
        $this->Ln(10);
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(60, 60, 60);
        $this->MultiCell(0, 7, $this->t('Sistema MEDIDATA — Hospital MEDICASA S. de R.L.'), 0, 'C');
        $this->Ln(6);
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 6, $this->t('Versión: ' . date('d/m/Y')), 0, 'C');
        $this->Ln(16);
        $this->SetFont('Arial', 'I', 10);
        $this->MultiCell(0, 5.5, $this->t(
            'Este documento describe paso a paso cómo probar la nueva implementación de los botones '
            . 'Formulario, Entrevista, Psicométricas, Expediente y Contratado en el detalle del candidato.'
        ), 0, 'C');
    }

    public function sectionTitle(string $title): void
    {
        $this->Ln(3);
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
    public function numberedList(array $items): void
    {
        $this->SetFont('Arial', '', 10);
        $n = 1;
        foreach ($items as $item) {
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetX($x + 2);
            $this->Cell(8, 5.5, $this->t((string) $n . '.'), 0, 0);
            $this->SetXY($x + 10, $y);
            $this->MultiCell(0, 5.5, $this->t($item), 0, 'J');
            $n++;
        }
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

    public function warnBox(string $text): void
    {
        $this->SetFillColor(255, 243, 205);
        $this->SetDrawColor(255, 193, 7);
        $this->SetFont('Arial', 'I', 9);
        $this->MultiCell(0, 5, $this->t('Importante: ' . $text), 1, 'J', true);
        $this->Ln(3);
    }

    public function checkTable(): void
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(3, 92, 103);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(52, 7, $this->t('Prueba'), 1, 0, 'L', true);
        $this->Cell(88, 7, $this->t('Resultado esperado'), 1, 0, 'L', true);
        $this->Cell(40, 7, $this->t('OK / Falla'), 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);

        $rows = [
            ['Formulario — generar enlace', 'Modal con URL; botón Copiar funciona', ''],
            ['Formulario — enviar correo', 'Correo llega al candidato (revisar spam)', ''],
            ['Entrevista — formulario', 'Pantalla con fecha, hora y preguntas', ''],
            ['Entrevista — calendario', 'Evento visible en Entrevistas → Programación', ''],
            ['Psicométricas — pruebas', 'Checkboxes guardados; estado actualizado', ''],
            ['Psicométricas — PDF', 'Documento PDF aceptado y guardado', ''],
            ['Expediente — enlace', 'Modal con URL y lista de faltantes', ''],
            ['Expediente — subida candidato', 'Solo PDF; progreso actualizado en RRHH', ''],
            ['Expediente — checklist', 'Completados / Faltantes correctos', ''],
            ['Contratado', 'Estado Contratado + redirección a lista colaboradores', ''],
        ];

        $fill = false;
        foreach ($rows as $row) {
            $this->SetFillColor($fill ? 245 : 255, $fill ? 250 : 255, $fill ? 250 : 255);
            $this->Cell(52, 7, $this->t($row[0]), 1, 0, 'L', true);
            $this->Cell(88, 7, $this->t($row[1]), 1, 0, 'L', true);
            $this->Cell(40, 7, $this->t($row[2]), 1, 1, 'C', true);
            $fill = !$fill;
        }
        $this->Ln(4);
    }

    public function buildContent(): void
    {
        $this->AddPage();
        $this->sectionTitle('1. Objetivo de esta guía');
        $this->paragraph(
            'Validar que los botones de Avance rápido en el detalle del candidato funcionen correctamente: '
            . 'generación de enlaces, formularios internos, carga de documentos PDF, calendario de entrevistas '
            . 'y flujo hasta la contratación.'
        );

        $this->sectionTitle('2. Requisitos previos');
        $this->bulletList([
            'Usuario con acceso al módulo Recursos Humanos en MEDIDATA.',
            'Base de datos RRHH activa (medic9ue_medi_rrhh_interviews).',
            'Al menos un candidato registrado (idealmente una postulación de prueba con correo real).',
            'Navegador actualizado (Chrome, Edge o Firefox).',
            'Para pruebas de correo: acceso al buzón del candidato de prueba.',
            'Archivos PDF de ejemplo para subir en psicométricas y expediente.',
        ]);

        $this->sectionTitle('3. Cómo llegar al detalle del candidato');
        $this->numberedList([
            'Inicie sesión en MEDIDATA con su usuario de RRHH.',
            'Menú lateral → PROCESO DE RECLUTAMIENTO → Postulantes.',
            'Opcional: filtre por vacante si desea un candidato específico.',
            'En la tabla, haga clic en el ícono del ojo (Ver detalle) del candidato a probar.',
            'En la pantalla de detalle verá la sección Avance rápido con cinco botones y el formulario de cambio de estado.',
        ]);
        $this->noteBox(
            'Ruta resumida: RRHH → Proceso de reclutamiento → Postulantes → Ver detalle (ojo) → Avance rápido.'
        );

        $this->sectionTitle('4. Prueba 1 — Botón FORMULARIO');
        $this->subTitle('4.1 Generar enlace (sin depender del correo)');
        $this->numberedList([
            'En el detalle del candidato, haga clic en el botón Formulario.',
            'Debe abrirse un cuadro (modal) con el enlace para el formulario de empleado.',
            'Verifique que el enlace se muestre aunque el candidato no tenga correo registrado.',
            'Haga clic en Copiar enlace y péguelo en una pestaña nueva del navegador.',
            'Complete y envíe el formulario público como candidato.',
            'Confirme que el estado del candidato cambió a Formulario Empleados.',
        ]);
        $this->subTitle('4.2 Enviar por correo');
        $this->numberedList([
            'Use un candidato con correo electrónico válido.',
            'Clic en Formulario → Enviar por correo.',
            'Revise la bandeja del candidato (y carpeta de spam / correo no deseado).',
            'El correo debe llegar desde talentohumano@medicasa.hn con el botón Completar formulario.',
        ]);
        $this->warnBox(
            'Si el correo no llega: copie el enlace manualmente. Informe a TI si persiste el problema '
            . '(configuración SMTP del servidor).'
        );

        $this->sectionTitle('5. Prueba 2 — Botón ENTREVISTA');
        $this->numberedList([
            'En Avance rápido, haga clic en Entrevista.',
            'Se abrirá el Formulario de entrevista genérico del candidato.',
            'Indique Fecha de entrevista y Hora de entrevista (obligatorios).',
            'Complete las preguntas: motivación, experiencia, fortalezas, expectativa salarial, etc.',
            'Seleccione Resultado (Apto / No apto / Pendiente / En proceso).',
            'Haga clic en Guardar entrevista.',
            'Menú → PROCESO DE RECLUTAMIENTO → Entrevistas.',
            'En la sección Programación / Calendario, verifique que aparezca la entrevista agendada.',
            'El registro quedará guardado para anexarse al expediente cuando el candidato sea contratado.',
        ]);

        $this->sectionTitle('6. Prueba 3 — Botón PSICOMÉTRICAS');
        $this->numberedList([
            'En Avance rápido, haga clic en Psicométricas.',
            'Marque las pruebas aplicadas (16PF, Cleaver, DISC, MMPI, etc.). Debe seleccionar al menos una.',
            'Opcional: ingrese puntaje general y notas.',
            'Suba un documento PDF con los resultados (solo se acepta PDF).',
            'Haga clic en Guardar pruebas.',
            'Verifique que el estado del candidato sea Pruebas Psicometricas.',
            'Vuelva al detalle y confirme que la información quedó registrada al reabrir la pantalla.',
        ]);

        $this->sectionTitle('7. Prueba 4 — Botón EXPEDIENTE');
        $this->subTitle('7.1 Generar enlace y vista RRHH');
        $this->numberedList([
            'En Avance rápido, haga clic en Expediente.',
            'Debe mostrarse un modal con el enlace para el candidato y la cantidad de documentos faltantes.',
            'Use Copiar enlace o Enviar por correo (si hay email válido).',
            'Clic en Cerrar (Ver detalle expediente) o vaya a expediente_estado desde el enlace del modal.',
            'En la vista de expediente verifique dos columnas: Completados y Faltantes.',
        ]);
        $this->subTitle('7.2 Subida por el candidato (enlace público)');
        $this->numberedList([
            'Abra el enlace en otra pestaña o dispositivo (como si fuera el candidato).',
            'Verá la lista de 11 documentos requeridos con estado Pendiente o Recibido.',
            'Para cada documento pendiente: seleccione un archivo PDF y pulse Subir PDF.',
            'Intente subir un JPG o Word — el sistema debe rechazarlo (solo PDF).',
            'Tras cada carga exitosa, el documento debe marcarse como Recibido.',
        ]);
        $this->subTitle('7.3 Documentos del expediente');
        $this->bulletList([
            'Curriculum vitae',
            'Copia de partida de nacimiento de hijos',
            '(1) Foto a color (tamaño carnet)',
            '(2) Copia de tarjeta de identidad / licencia',
            '(1) Copia del recibo (agua, luz, teléfono) más reciente',
            'Antecedentes penales',
            'Antecedentes policiales',
            '(2) Referencias personales',
            '(2) Referencias laborales',
            'Diplomas o títulos recibidos',
            'Croquis de vivienda',
        ]);

        $this->sectionTitle('8. Prueba 5 — Botón CONTRATADO');
        $this->numberedList([
            'En Avance rápido, haga clic en Contratado.',
            'Confirme en el cuadro de diálogo (Sí, contratar).',
            'El sistema debe cambiar el estado a Contratado.',
            'Debe redirigir automáticamente a Lista de colaboradores.',
            'Opcional: desde lista, use Agregar colaborador; si entra con ?id_candidato=ID los datos básicos vienen precargados.',
        ]);

        $this->sectionTitle('9. Prueba del cambio manual de estado');
        $this->paragraph(
            'Además de los botones rápidos, puede usar el selector Cambiar estado y el campo Observaciones RRHH '
            . 'en la misma pantalla. Al guardar, el sistema actualiza el candidato y recarga la página.'
        );

        $this->sectionTitle('10. Hoja de verificación');
        $this->paragraph(
            'Marque OK o Falla al completar cada prueba. Entregue esta hoja a TI si algún punto falla.'
        );
        $this->checkTable();

        $this->sectionTitle('11. Reporte de incidencias');
        $this->paragraph('Si algo no funciona, reporte con esta información:');
        $this->bulletList([
            'Nombre del candidato y ID mostrado en pantalla.',
            'Botón o paso exacto donde ocurrió el problema.',
            'Mensaje de error visible (captura de pantalla).',
            'Fecha, hora y usuario con el que inició sesión.',
            'Navegador utilizado.',
        ]);
        $this->noteBox(
            'Contacto sugerido: área de Tecnología / Informática del Hospital MEDICASA.'
        );
    }
}

function generarGuiaPruebasRrhhPdf(string $outputPath): string
{
    $dir = dirname($outputPath);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('No se pudo crear el directorio: ' . $dir);
    }

    $pdf = new GuiaPruebasRrhhPdf('P', 'mm', 'Letter');
    $pdf->AliasNbPages();
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->SetMargins(15, 22, 15);

    $pdf->coverPage();
    $pdf->buildContent();

    $pdf->Output('F', $outputPath);

    return $outputPath;
}

$isCli = PHP_SAPI === 'cli';
$output = __DIR__ . '/../docs/guia_pruebas_rrhh_avance_rapido.pdf';

try {
    $path = generarGuiaPruebasRrhhPdf($output);
    if ($isCli) {
        echo "Guía generada: {$path}\n";
        echo 'Tamaño: ' . round(filesize($path) / 1024, 1) . " KB\n";
    } else {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="guia_pruebas_rrhh_avance_rapido.pdf"');
        readfile($path);
    }
} catch (Throwable $e) {
    if ($isCli) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    echo 'Error al generar la guía.';
}
