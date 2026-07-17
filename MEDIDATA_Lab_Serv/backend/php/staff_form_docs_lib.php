<?php
/**
 * Helpers para campos de documentos en formularios de edición de personal.
 */

if (!function_exists('medidata_staff_blob_doc_url')) {
    function medidata_staff_blob_doc_url(int $id, string $doc, ?string $table = null, ?string $idcol = null): string
    {
        $url = '../../backend/php/view_staff_doc.php?id=' . $id . '&doc=' . rawurlencode($doc);
        if ($table !== null && $table !== '' && $idcol !== null && $idcol !== '') {
            $url .= '&table=' . rawurlencode($table) . '&idcol=' . rawurlencode($idcol);
        }
        return $url;
    }
}

if (!function_exists('medidata_staff_path_doc_url')) {
    function medidata_staff_path_doc_url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return '../..' . (strpos($path, '/') === 0 ? $path : '/' . $path);
    }
}

if (!function_exists('medidata_staff_has_path_doc')) {
    function medidata_staff_has_path_doc(?string $path): bool
    {
        return trim((string) $path) !== '';
    }
}

if (!function_exists('medidata_staff_doc_flags_from_row')) {
    function medidata_staff_doc_flags_from_row(object $row): array
    {
        $hasBlob = static function (string $hasKey, string $urlKey) use ($row): bool {
            if (isset($row->$hasKey) && $row->$hasKey) {
                return true;
            }
            return isset($row->$urlKey) && $row->$urlKey !== null && $row->$urlKey !== '';
        };

        return [
            'solicitud' => $hasBlob('has_solicitud', 'url_solicitud'),
            'psicometricas' => $hasBlob('has_psicometricas', 'url_psicometricas'),
            'contrato' => $hasBlob('has_contrato', 'url_contrato'),
        ];
    }
}

if (!function_exists('medidata_staff_render_doc_field')) {
    /**
     * @param array{label:string,name:string,doc_key:string,doc_kind?:string,accept:string,has:bool,view_url:?string,view_title?:string,highlight?:bool} $opts
     */
    function medidata_staff_render_doc_field(array $opts): void
    {
        $label = (string) ($opts['label'] ?? 'Documento');
        $name = (string) ($opts['name'] ?? '');
        $docKey = (string) ($opts['doc_key'] ?? '');
        $docKind = (string) ($opts['doc_kind'] ?? 'blob');
        $accept = (string) ($opts['accept'] ?? '.pdf,.jpg,.png');
        $has = !empty($opts['has']);
        $viewUrl = $opts['view_url'] ?? null;
        $viewTitle = (string) ($opts['view_title'] ?? $label);
        $highlight = !empty($opts['highlight']);
        $inputId = 'staff_doc_' . preg_replace('/[^a-z0-9_]+/i', '_', $name);

        $cardClass = 'staff-doc-card' . ($has ? ' staff-doc-card--uploaded' : ' staff-doc-card--empty');
        if ($highlight) {
            $cardClass .= ' staff-doc-card--highlight';
        }
        ?>
        <div class="<?php echo $cardClass; ?>" data-doc-key="<?php echo htmlspecialchars($docKey); ?>"
             data-doc-kind="<?php echo htmlspecialchars($docKind); ?>"
             data-doc-label="<?php echo htmlspecialchars($label); ?>">
            <div class="staff-doc-card__head">
                <span class="staff-doc-card__label"><?php echo htmlspecialchars($label); ?></span>
                <span class="staff-doc-card__status <?php echo $has ? 'staff-doc-card__status--ok' : 'staff-doc-card__status--pending'; ?>">
                    <i class="bx <?php echo $has ? 'bx-check-circle' : 'bx-info-circle'; ?>"></i>
                    <?php echo $has ? 'Documento cargado' : 'Sin documento'; ?>
                </span>
            </div>
            <div class="staff-doc-card__actions">
                <?php if ($has && $viewUrl): ?>
                <a href="#" class="doc-btn" title="Ver documento"
                   onclick="verDocumentoStaff(<?php echo htmlspecialchars(json_encode($viewUrl), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($viewTitle), ENT_QUOTES, 'UTF-8'); ?>); return false;">
                    <i class="bx bx-show"></i> Ver
                </a>
                <?php endif; ?>
                <?php if ($has): ?>
                <button type="button" class="doc-btn doc-btn--delete" title="Eliminar documento"
                        onclick="eliminarDocumentoStaff(this); return false;">
                    <i class="bx bx-trash"></i> Eliminar
                </button>
                <?php endif; ?>
                <label class="doc-btn doc-btn--upload" for="<?php echo htmlspecialchars($inputId); ?>" title="Subir o reemplazar">
                    <i class="bx bx-upload"></i> <?php echo $has ? 'Reemplazar' : 'Subir'; ?>
                </label>
                <input type="file" class="staff-doc-card__file" id="<?php echo htmlspecialchars($inputId); ?>"
                       name="<?php echo htmlspecialchars($name); ?>" accept="<?php echo htmlspecialchars($accept); ?>">
            </div>
            <p class="staff-doc-card__filename" data-empty="Ningún archivo seleccionado">Ningún archivo seleccionado</p>
        </div>
        <?php
    }
}
