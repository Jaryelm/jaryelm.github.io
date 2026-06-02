<?php
if (!isset($_POST['add_doctor'])) {
    return;
}

date_default_timezone_set('America/Tegucigalpa');

$ceddoc = strtoupper(trim((string) ($_POST['cem'] ?? '')));
$nodoc = strtoupper(trim((string) ($_POST['named'] ?? '')));
$apdoc = strtoupper(trim((string) ($_POST['apeme'] ?? '')));
$nomesp = strtoupper(trim((string) ($_POST['espm'] ?? '')));
$direcd = strtoupper(trim((string) ($_POST['dime'] ?? '')));
$sexd = strtoupper(trim((string) ($_POST['geme'] ?? '')));
$phd = strtoupper(trim((string) ($_POST['telme'] ?? '')));
$nacd = strtoupper(trim((string) ($_POST['cumme'] ?? '')));
$corr = strtoupper(trim((string) ($_POST['corr'] ?? '')));
$fere = date('Y-m-d H:i:s');
$comisiona = (isset($_POST['comisiona']) && $_POST['comisiona'] === 'SI') ? 'SI' : 'NO';

if ($ceddoc === '' || $nodoc === '') {
    echo '<script>Swal.fire("Campos requeridos", "Complete cédula y nombre del médico.", "warning");</script>';
    return;
}

try {
    $stmt = $connect->prepare('SELECT COUNT(*) FROM doctor WHERE ceddoc = :ceddoc');
    $stmt->execute([':ceddoc' => $ceddoc]);
    if ((int) $stmt->fetchColumn() > 0) {
        echo '<script>Swal.fire("Duplicado", "Ya existe un médico con esa cédula.", "warning");</script>';
        return;
    }

    $stmt = $connect->prepare(
        "INSERT INTO doctor(ceddoc, nodoc, apdoc, nomesp, direcd, sexd, phd, nacd, corr, fere, state, comisiona)
         VALUES(:ceddoc, :nodoc, :apdoc, :nomesp, :direcd, :sexd, :phd, :nacd, :corr, :fere, '1', :comisiona)"
    );
    $ok = $stmt->execute([
        ':ceddoc' => $ceddoc,
        ':nodoc' => $nodoc,
        ':apdoc' => $apdoc,
        ':nomesp' => $nomesp,
        ':direcd' => $direcd,
        ':sexd' => $sexd,
        ':phd' => $phd,
        ':nacd' => $nacd,
        ':corr' => $corr,
        ':fere' => $fere,
        ':comisiona' => $comisiona,
    ]);

    if ($ok) {
        echo '<script>Swal.fire("Agregado", "Médico registrado correctamente", "success").then(function(){ window.location="mostrar.php"; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo registrar el médico", "error");</script>';
    }
} catch (Throwable $e) {
    error_log('add_doctor: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
}
