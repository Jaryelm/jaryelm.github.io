<?php 
require_once('../../backend/bd/Conexion.php'); 

if (isset($_POST['add_patiens'])) {
    $numhs = trim($_POST['nhi']);
    $nompa = trim($_POST['namp']);
    $apepa = trim($_POST['apep']);
    $direc = trim($_POST['dip']);
    $sex = trim($_POST['gep']);
    $grup = trim($_POST['grp']);
    $phon = trim($_POST['telp']);
    $cump = trim($_POST['cump']);
    $resnamp = trim($_POST['resnamp']);
    $resgep = trim($_POST['resgep']);
    $dip_correo = trim($_POST['dip_correo']); // Nuevo campo
    $profesion = trim($_POST['profesion']); // Nuevo campo
    $estado_civic = trim($_POST['estado_civic']); // Nuevo campo

    if (empty($numhs)) {
        $errMSG = "Please enter number.";
    } else if (empty($nompa)) {
        $errMSG = "Please Enter your name.";
    }

    $stmt = "SELECT * FROM patients WHERE numhs = '$numhs'";
    if (empty($numhs)) {
        echo '<div id="cookiePopup" class="hide">
                <img src="../../backend/img/error.png" />
                <p>Ya existe el registro a agregar!</p>
                <button id="acceptCookie" type="button">OK</button>
              </div>';
    } else {
        $sql = "SELECT * FROM patients WHERE numhs = '$numhs'";
        $stmt = $connect->prepare($sql);
        $stmt->execute();

        if ($stmt->fetchColumn() == 0) {
            if (!isset($errMSG)) {
                $stmt = $connect->prepare("INSERT INTO patients(
                    numhs, nompa, apepa, direc, sex, grup, phon, cump, resnamp, resgep, dip_correo, profesion, estado_civic, state
                ) VALUES(
                    :numhs, :nompa, :apepa, :direc, :sex, :grup, :phon, :cump, :resnamp, :resgep, :dip_correo, :profesion, :estado_civic, '1'
                )");

                $stmt->bindParam(':numhs', $numhs);
                $stmt->bindParam(':nompa', $nompa);
                $stmt->bindParam(':apepa', $apepa);
                $stmt->bindParam(':direc', $direc);
                $stmt->bindParam(':sex', $sex);
                $stmt->bindParam(':grup', $grup);
                $stmt->bindParam(':phon', $phon);
                $stmt->bindParam(':cump', $cump);
                $stmt->bindParam(':resnamp', $resnamp);
                $stmt->bindParam(':resgep', $resgep);
                $stmt->bindParam(':dip_correo', $dip_correo); // Nuevo campo
                $stmt->bindParam(':profesion', $profesion); // Nuevo campo
                $stmt->bindParam(':estado_civic', $estado_civic); // Nuevo campo

                if ($stmt->execute()) {
                    echo '<div id="cookiePopup" class="hide">
                            <img src="../../backend/img/404-tick.png" />
                            <p>Agregado correctamente</p>
                            <button id="acceptCookie" type="button">OK</button>
                          </div>';
                } else {
                    $errMSG = "Error al insertar el registro.";
                }
            }
        } else {
            echo '<div id="cookiePopup" class="hide">
                    <img src="../../backend/img/error.png" />
                    <p>Paciente Ya Existe</p>
                    <button id="acceptCookie" type="button">OK</button>
                  </div>';
        }
    }
}
?>
