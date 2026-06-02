<?php
if (!isset($_POST['upd_doctors'])) {
    return;
}

$idodc = (int) ($_POST['midp'] ?? 0);
$ceddoc = trim((string) ($_POST['docce'] ?? ''));
$nodoc = trim((string) ($_POST['docna'] ?? ''));
$apdoc = trim((string) ($_POST['docap'] ?? ''));
$nomesp = trim((string) ($_POST['doces'] ?? ''));
$direcd = trim((string) ($_POST['docdi'] ?? ''));
$sexd = trim((string) ($_POST['docge'] ?? ''));
$phd = trim((string) ($_POST['docte'] ?? ''));
$nacd = trim((string) ($_POST['docda'] ?? ''));
$corr = trim((string) ($_POST['doccorr'] ?? ''));

try {
    if ($idodc <= 0) {
        throw new RuntimeException('Identificador de médico no válido.');
    }

    $query = 'UPDATE doctor SET
              ceddoc = :ceddoc,
              nodoc = :nodoc,
              apdoc = :apdoc,
              nomesp = :nomesp,
              direcd = :direcd,
              sexd = :sexd,
              phd = :phd,
              nacd = :nacd,
              corr = :corr
              WHERE idodc = :idodc';

    $statement = $connect->prepare($query);
    $ok = $statement->execute([
        ':ceddoc' => $ceddoc,
        ':nodoc' => $nodoc,
        ':apdoc' => $apdoc,
        ':nomesp' => $nomesp,
        ':direcd' => $direcd,
        ':sexd' => $sexd,
        ':phd' => $phd,
        ':nacd' => $nacd,
        ':corr' => $corr,
        ':idodc' => $idodc,
    ]);

    if ($ok) {
        echo '<script>Swal.fire("Actualizado", "Médico actualizado correctamente", "success").then(function(){ window.location="mostrar.php"; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo actualizar el médico", "error");</script>';
    }
    exit;
} catch (Throwable $e) {
    error_log('upd_doctor: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
    exit;
}
