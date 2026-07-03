<?php
declare(strict_types=1);

/**
 * Listado MH-PACS desde worklist (MySQL local), no desde Orthanc en vivo.
 * La sincronización pesada queda en sync_orthanc.php (manual o cron).
 */

/**
 * @return array{studies: list<array<string, mixed>>, total: int, page: int, limit: int, totalPages: int, last_sync: string|null}
 */
function medidata_mh_pacs_fetch_studies(PDO $connect, int $page, int $limit, string $search = ''): array
{
    if ($page < 1) {
        $page = 1;
    }
    if ($limit < 1) {
        $limit = 10;
    }
    if ($limit > 500) {
        $limit = 500;
    }

    $offset = ($page - 1) * $limit;
    $params = [];
    $where = ' WHERE 1=1';

    if ($search !== '') {
        $where .= ' AND (
            w.patient_name LIKE ?
            OR w.patient_id LIKE ?
            OR w.study_description LIKE ?
            OR w.modality LIKE ?
        )';
        $term = '%' . $search . '%';
        $params = [$term, $term, $term, $term];
    }

    $countStmt = $connect->prepare('SELECT COUNT(*) FROM worklist w' . $where);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = '
        SELECT
            w.study_id,
            w.series_id,
            w.patient_id,
            w.patient_name,
            w.study_date,
            w.modality,
            w.study_description,
            w.last_sync
        FROM worklist w
        ' . $where . '
        ORDER BY w.study_date DESC, w.id DESC
        LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $studies = [];
    foreach ($rows as $row) {
        $studies[] = medidata_mh_pacs_map_worklist_row($row);
    }

    $lastSyncStmt = $connect->query('SELECT MAX(last_sync) FROM worklist');
    $lastSync = $lastSyncStmt ? $lastSyncStmt->fetchColumn() : null;

    return [
        'studies'    => $studies,
        'total'      => $total,
        'page'       => $page,
        'limit'      => $limit,
        'totalPages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
        'last_sync'  => $lastSync ? (string) $lastSync : null,
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function medidata_mh_pacs_map_worklist_row(array $row): array
{
    $studyDateRaw = $row['study_date'] ?? null;
    $studyDate = 'N/A';

    if ($studyDateRaw !== null && $studyDateRaw !== '') {
        $ts = strtotime((string) $studyDateRaw);
        if ($ts !== false) {
            $studyDate = date('Ymd', $ts);
        }
    }

    return [
        'ID'                     => $row['study_id'] ?? 'N/A',
        'PatientName'            => $row['patient_name'] ?? 'N/A',
        'PatientSex'             => 'N/A',
        'PatientID'              => $row['patient_id'] ?? 'N/A',
        'StudyDate'              => $studyDate,
        'Modality'               => $row['modality'] ?? 'N/A',
        'StudyDescription'       => $row['study_description'] ?? 'N/A',
        'InstitutionName'        => 'HOSPITAL MEDICASA',
        'ReferringPhysicianName' => 'N/A',
        'FirstSeriesId'          => $row['series_id'] ?? null,
    ];
}

/**
 * @return array{total: int, last_sync: string|null}
 */
function medidata_mh_pacs_studies_summary(PDO $connect): array
{
    $stmt = $connect->query('SELECT COUNT(*), MAX(last_sync) FROM worklist');
    $row = $stmt ? $stmt->fetch(PDO::FETCH_NUM) : false;

    return [
        'total'     => $row ? (int) $row[0] : 0,
        'last_sync' => ($row && $row[1]) ? (string) $row[1] : null,
    ];
}
