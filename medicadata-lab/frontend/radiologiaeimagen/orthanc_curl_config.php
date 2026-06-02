<?php
declare(strict_types=1);

/**
 * TLS para peticiones HTTPS a Orthanc (medicloud / API interna).
 *
 * Error frecuente en servidor: "SSL certificate ... unable to get local issuer certificate (20)"
 * → PHP no encuentra el archivo de CA (curl.cainfo / openssl.cafile vacíos en php.ini).
 *
 * Solución recomendada (producción):
 *   1) Descargue https://curl.se/ca/cacert.pem
 *   2) Guárdelo como: frontend/radiologiaeimagen/cacert.pem
 *   3) Se usará CURLOPT_CAINFO y la verificación SSL quedará activa.
 *
 * Sin cacert.pem: se desactiva verificación solo para estas llamadas (equivalente al uso anterior
 * del proyecto). También se fuerza VERIFYHOST=0 por compatibilidad con OpenSSL 3 / algunos hosts.
 *
 * @param resource|\CurlHandle $ch
 */
function medicasa_orthanc_apply_curl_tls($ch): void
{
    if ($ch === false) {
        return;
    }

    $cacert = __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem';
    if (is_readable($cacert) && (int) @filesize($cacert) > 1024) {
        curl_setopt($ch, CURLOPT_CAINFO, $cacert);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        return;
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    // Algunos builds (p. ej. Windows + Schannel/OCSP) siguen comprobando cadena; bits extra de compatibilidad
    if (defined('CURLOPT_SSL_OPTIONS')) {
        $opts = 0;
        if (defined('CURLSSLOPT_NO_REVOKE')) {
            $opts |= CURLSSLOPT_NO_REVOKE;
        }
        if ($opts !== 0) {
            @curl_setopt($ch, CURLOPT_SSL_OPTIONS, $opts);
        }
    }
}
