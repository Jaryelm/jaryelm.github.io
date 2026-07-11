<?php
/**
 * Filtros compartidos MH-PACS (worklist, estudios, transcripciones).
 */

if (!function_exists('mhpacs_filters_parse_payload')) {
    /**
     * @param array<string, mixed> $payload
     * @return array{search:string,modality:string,priority:string,status:string,date_from:string,date_to:string,page:int,limit:int,offset:int}
     */
    function mhpacs_filters_parse_payload(array $payload, int $defaultLimit = 10): array
    {
        $dateFrom = trim((string) ($payload['date_from'] ?? ''));
        $dateTo = trim((string) ($payload['date_to'] ?? ''));
        $legacyDate = trim((string) ($payload['date'] ?? ''));

        if ($dateFrom === '' && $legacyDate !== '') {
            $dateFrom = $legacyDate;
        }
        if ($dateTo === '' && $legacyDate !== '') {
            $dateTo = $legacyDate;
        }

        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $page = max(1, (int) ($payload['page'] ?? 1));
        $limit = max(5, min(50, (int) ($payload['limit'] ?? $defaultLimit)));

        return [
            'search'          => trim((string) ($payload['search'] ?? '')),
            'modality'        => trim((string) ($payload['modality'] ?? '')),
            'priority'        => trim((string) ($payload['priority'] ?? '')),
            'status'          => trim((string) ($payload['status'] ?? '')),
            'radiologist_id'  => trim((string) ($payload['radiologist_id'] ?? '')),
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
            'page'            => $page,
            'limit'           => $limit,
            'offset'          => ($page - 1) * $limit,
        ];
    }
}

if (!function_exists('mhpacs_sql_date_range')) {
    /**
     * @param list<mixed> $params
     */
    function mhpacs_sql_date_range(string $columnExpr, string $dateFrom, string $dateTo, array &$params): string
    {
        $sql = '';
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $sql .= " AND DATE({$columnExpr}) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $sql .= " AND DATE({$columnExpr}) <= ?";
            $params[] = $dateTo;
        }
        return $sql;
    }
}

if (!function_exists('mhpacs_sql_search')) {
    /**
     * @param list<string> $columns
     * @param list<mixed> $params
     */
    function mhpacs_sql_search(array $columns, string $search, array &$params): string
    {
        if ($search === '' || $columns === []) {
            return '';
        }
        $parts = [];
        foreach ($columns as $col) {
            $parts[] = "{$col} LIKE ?";
            $params[] = '%' . $search . '%';
        }
        return ' AND (' . implode(' OR ', $parts) . ')';
    }
}

if (!function_exists('mhpacs_json_paginated')) {
    /**
     * @param list<array<string, mixed>> $rows
     */
    function mhpacs_json_paginated(array $rows, int $total, int $page, int $limit): void
    {
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;
        echo json_encode([
            'success'    => true,
            'data'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'totalPages' => $totalPages,
        ], JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('mhpacs_is_radiologo_role')) {
    function mhpacs_is_radiologo_role(string $rol): bool
    {
        return strcasecmp($rol, 'Radiologo') === 0;
    }
}

if (!function_exists('mhpacs_is_tecnico_role')) {
    function mhpacs_is_tecnico_role(string $rol): bool
    {
        return strcasecmp($rol, 'Tecnico') === 0;
    }
}

if (!function_exists('mhpacs_list_radiologists')) {
    /**
     * Médicos radiólogos (tabla doctor) para filtros de carga laboral.
     *
     * @return list<array{id:int,name:string}>
     */
    function mhpacs_list_radiologists(PDO $pdo): array
    {
        $stmt = $pdo->prepare(
            "SELECT idodc AS id, TRIM(CONCAT(COALESCE(nodoc, ''), ' ', COALESCE(apdoc, ''))) AS name
             FROM doctor
             WHERE UPPER(COALESCE(nomesp, '')) LIKE '%RADIOLOGIA%'
             ORDER BY nodoc ASC, apdoc ASC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = [
                'id'   => (int) ($row['id'] ?? 0),
                'name' => $name,
            ];
        }
        return $out;
    }
}

if (!function_exists('mhpacs_worklist_radiologist_sql')) {
    /**
     * @param list<mixed> $params
     */
    function mhpacs_worklist_radiologist_sql(string $radiologistId, array &$params): string
    {
        if ($radiologistId === '') {
            return '';
        }
        if ($radiologistId === '_unassigned') {
            return " AND (w.radiologist_id IS NULL OR w.radiologist_id = 0 OR TRIM(COALESCE(w.radiologist_name, '')) = '')";
        }
        $id = (int) $radiologistId;
        if ($id <= 0) {
            return '';
        }
        $params[] = $id;
        return ' AND w.radiologist_id = ?';
    }
}
