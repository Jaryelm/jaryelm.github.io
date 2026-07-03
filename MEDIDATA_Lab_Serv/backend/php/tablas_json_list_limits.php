<?php

/**
 * Límites para endpoints JSON tipo backend/registros/tabla_* que antes podían traer todo el dataset.
 *
 * Por defecto 2500 filas por petición (con offset 0); máximo 8000.
 * Opcional en query string: limit, offset (enteros sanitizados; no permite SQL injection).
 */
function medidata_tablas_json_limit_offset_mysql(): array
{
    static $memo = null;
    if ($memo !== null) {
        return $memo;
    }

    $defaultLimit = 2500;
    $maxLimit = 8000;

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : $defaultLimit;
    if ($limit < 1) {
        $limit = $defaultLimit;
    }
    if ($limit > $maxLimit) {
        $limit = $maxLimit;
    }

    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    if ($offset < 0) {
        $offset = 0;
    }

    $memo = [$limit, $offset];
    return $memo;
}

/**
 * Fragmento SQL "LIMIT n OFFSET m" ya escapado numéricamente.
 */
function medidata_tablas_mysql_limit_clause(): string
{
    [$limit, $offset] = medidata_tablas_json_limit_offset_mysql();

    return ' LIMIT ' . $limit . ' OFFSET ' . $offset;
}
