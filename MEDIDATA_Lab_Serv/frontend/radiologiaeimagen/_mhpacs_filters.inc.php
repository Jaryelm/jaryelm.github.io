<?php
/**
 * Panel de filtros MH-PACS.
 *
 * Variables antes del include:
 *   $mhpacs_filter_mode = 'worklist' | 'studies' | 'studies_medico' | 'transcriptions'
 */
$mode = $mhpacs_filter_mode ?? 'worklist';

$showSearch = in_array($mode, ['worklist', 'studies', 'studies_medico', 'transcriptions'], true);
$showPriority = in_array($mode, ['worklist', 'transcriptions'], true);
$showRadiologistFilter = ($mode === 'worklist');
$showStatusFilter = !$showRadiologistFilter;

$radiologistOptions = [];
if ($showRadiologistFilter) {
    if (!isset($connect)) {
        require_once __DIR__ . '/../../backend/bd/Conexion.php';
    }
    require_once __DIR__ . '/mhpacs_filters_lib.php';
    $radiologistOptions = mhpacs_list_radiologists($connect);
}

$filterHeaderHint = $showRadiologistFilter
    ? 'Monitoree la carga laboral por médico radiólogo, modalidad y rango de fechas'
    : 'Refine la lista por paciente, modalidad, estado y rango de fechas';

$statusSets = [
    'worklist' => [
        '' => 'Todos los Estados',
        'pending' => 'Pendiente',
        'in_progress' => 'En Progreso',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
    ],
    'studies' => [
        '' => 'Todos los Estados',
        'pending' => 'Pendientes de interpretación',
        'draft' => 'Borradores',
        'pending_transcription' => 'En transcripción',
        'final' => 'Finalizados',
        'completed' => 'Completados (incl. transcritos)',
    ],
    'studies_medico' => [
        '' => 'Todos los Estados',
        'pending' => 'Pendientes',
        'draft' => 'Borradores',
        'pending_transcription' => 'En transcripción',
        'final' => 'Finalizados',
        'completed' => 'Completados',
    ],
    'transcriptions' => [
        '' => 'Todos los Estados',
        'pending_transcription' => 'Pendientes',
        'in_progress' => 'En progreso',
        'completed' => 'Completados',
        'needs_review' => 'Requieren revisión',
    ],
];

$statusKey = $mode;
if (!isset($statusSets[$statusKey])) {
    $statusKey = 'worklist';
}
$statusOptions = $statusSets[$statusKey];

$modalities = [
    '' => 'Todas las Modalidades',
    'DX' => 'Radiografía (DX)',
    'CR' => 'Radiografía Computarizada (CR)',
    'CT' => 'Tomografía (CT)',
    'MR' => 'Resonancia (MR)',
    'US' => 'Ultrasonido (US)',
    'RF' => 'Fluoroscopia (RF)',
    'MG' => 'Mamografía (MG)',
    'NM' => 'Medicina Nuclear (NM)',
    'PT' => 'PET (PT)',
    'XA' => 'Angiografía (XA)',
];

$mainRowClass = $showPriority
    ? 'mhpacs-filters__row--main'
    : 'mhpacs-filters__row--main mhpacs-filters__row--no-priority';
?>
<section class="mhpacs-filters" aria-label="Filtros de búsqueda">
    <header class="mhpacs-filters__header">
        <span class="mhpacs-filters__header-icon" aria-hidden="true"><i class='bx bx-filter-alt'></i></span>
        <div class="mhpacs-filters__header-text">
            <h2>Filtros de búsqueda</h2>
            <p><?php echo htmlspecialchars($filterHeaderHint, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </header>

    <div class="mhpacs-filters__body">
        <?php if ($showSearch): ?>
        <div class="mhpacs-filters__search">
            <label class="mhpacs-filters__search-label" for="searchBar">
                ID paciente, nombre o acceso al estudio
            </label>
            <div class="mhpacs-filters__search-input">
                <i class='bx bx-search' aria-hidden="true"></i>
                <input
                    type="search"
                    id="searchBar"
                    name="searchBar"
                    placeholder="Ej.: López Martínez, 0801-1990-12345, ACC-78421…"
                    autocomplete="off"
                    enterkeyhint="search"
                />
            </div>
            <p class="mhpacs-filters__search-hint">
                Coincide con <strong>nombre del paciente</strong>, <strong>documento de identidad</strong>,
                <strong>ID de acceso</strong> o <strong>descripción del estudio</strong>.
            </p>
        </div>
        <?php endif; ?>

        <div class="mhpacs-filters__row <?php echo $mainRowClass; ?>">
            <div class="mhpacs-filters__field">
                <label for="modalityFilter">Modalidad</label>
                <select id="modalityFilter">
                    <?php foreach ($modalities as $val => $label): ?>
                    <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($showPriority): ?>
            <div class="mhpacs-filters__field">
                <label for="priorityFilter">Prioridad</label>
                <select id="priorityFilter">
                    <option value="">Todas las Prioridades</option>
                    <option value="routine">Rutina</option>
                    <option value="urgent">Urgente</option>
                    <option value="emergency">Emergencia</option>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($showRadiologistFilter): ?>
            <div class="mhpacs-filters__field">
                <label for="radiologistFilter">Médico radiólogo</label>
                <select id="radiologistFilter">
                    <option value="">Todos los radiólogos</option>
                    <option value="_unassigned">Sin asignar</option>
                    <?php foreach ($radiologistOptions as $rad): ?>
                    <option value="<?php echo (int) $rad['id']; ?>">
                        <?php echo htmlspecialchars($rad['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="mhpacs-filters__field-hint">Use con <strong>fecha desde / hasta</strong> para ver la carga mensual de cada médico.</p>
            </div>
            <?php endif; ?>

            <?php if ($showStatusFilter): ?>
            <div class="mhpacs-filters__field">
                <label for="statusFilter">Estado</label>
                <select id="statusFilter">
                    <?php foreach ($statusOptions as $val => $label): ?>
                    <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="mhpacs-filters__field">
                <label for="dateFromFilter">Fecha desde</label>
                <input type="date" id="dateFromFilter" />
            </div>

            <div class="mhpacs-filters__field">
                <label for="dateToFilter">Fecha hasta</label>
                <input type="date" id="dateToFilter" />
            </div>
        </div>
    </div>

    <footer class="mhpacs-filters__actions">
        <button type="button" onclick="applyFilters()" class="mhpacs-filters__btn mhpacs-filters__btn--primary filter-btn">Aplicar filtros</button>
        <button type="button" id="clearFiltersBtn" class="mhpacs-filters__btn mhpacs-filters__btn--secondary">Limpiar</button>
    </footer>
</section>
