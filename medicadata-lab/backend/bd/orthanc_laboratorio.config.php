<?php
/**
 * Orthanc / PACS — solo laboratorio local.
 * Edita URLs y credenciales según tu instalación (p. ej. Docker Orthanc en 8042).
 *
 * @return array{
 *   curl_base: string,
 *   curl_user: string,
 *   curl_pass: string,
 *   viewer_series_prefix: string,
 *   study_archive_prefix: string
 * }
 */
return [
    'curl_base' => 'http://127.0.0.1:8042',
    'curl_user' => 'orthanc',
    'curl_pass' => 'orthanc',
    'viewer_series_prefix' => 'http://127.0.0.1:8042/web-viewer/app/viewer.html?series=',
    'study_archive_prefix' => 'http://127.0.0.1:8042/studies/',
];
