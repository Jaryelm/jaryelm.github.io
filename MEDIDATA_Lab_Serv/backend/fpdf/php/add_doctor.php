<?php 
require_once('../../backend/bd/Conexion.php'); 

// Establecer la zona horaria de Tegucigalpa, Honduras
date_default_timezone_set('America/Tegucigalpa');

if (isset($_POST['add_doctor'])) {
    $ceddoc = strtoupper(trim($_POST['cem']));
    $nodoc = strtoupper(trim($_POST['named']));
    $apdoc = strtoupper(trim($_POST['apeme']));
    $nomesp = strtoupper(trim($_POST['espm']));
    $direcd = strtoupper(trim($_POST['dime']));
    $sexd = strtoupper(trim($_POST['geme']));
    $phd = strtoupper(trim($_POST['telme']));
    $nacd = strtoupper(trim($_POST['cumme']));
    $corr = strtoupper(trim($_POST['corr'])); // Convertir a mayúsculas
    $fere = date('Y-m-d H:i:s');  // Capturar la fecha y hora actual
    $comisiona = isset($_POST['comisiona']) && $_POST['comisiona'] === 'SI' ? 'SI' : 'NO';

    if (empty($ceddoc)) {
        $errMSG = "Please enter cedula.";
    } else if (empty($nodoc)) {
        $errMSG = "Please enter your name.";
    }

    // Validar si el doctor ya existe
    $sql = "SELECT * FROM doctor WHERE ceddoc = :ceddoc";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':ceddoc', $ceddoc);
    $stmt->execute();

    if ($stmt->fetchColumn() > 0) {
        echo '<div id="cookiePopup" class="hide">
                <img src="../../backend/img/error.png" />
                <p>Ya existe el Doctor/a!</p>
                <button id="acceptCookie" type="button">OK</button>
              </div>';
    } else {
        // Si no hay error, proceder con la inserción
        if (!isset($errMSG)) {
            $stmt = $connect->prepare("INSERT INTO doctor(ceddoc, nodoc, apdoc, nomesp, direcd, sexd, phd, nacd, corr, fere, state, comisiona) VALUES(:ceddoc, :nodoc, :apdoc, :nomesp, :direcd, :sexd, :phd, :nacd, :corr, :fere, '1', :comisiona)");

            // Asignar los parámetros
            $stmt->bindParam(':ceddoc', $ceddoc);
            $stmt->bindParam(':nodoc', $nodoc);
            $stmt->bindParam(':apdoc', $apdoc);
            $stmt->bindParam(':nomesp', $nomesp);
            $stmt->bindParam(':direcd', $direcd);
            $stmt->bindParam(':sexd', $sexd);
            $stmt->bindParam(':phd', $phd);
            $stmt->bindParam(':nacd', $nacd);
            $stmt->bindParam(':corr', $corr);
            $stmt->bindParam(':fere', $fere);  // Parametro para la fecha y hora
            $stmt->bindParam(':comisiona', $comisiona);

            if ($stmt->execute()) {
                echo '<div id="cookiePopup" class="hide">
                        <img src="../../backend/img/404-tick.png" />
                        <p>Agregado correctamente</p>
                        <button id="acceptCookie" type="button">OK</button>
                      </div>';
            } else {
                $errMSG = "Error while inserting....";
            }
        }
    }
}
?>
