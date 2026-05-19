<?php 
require $_SERVER['DOCUMENT_ROOT'] . '/backend/bd/Conexion.php';

echo '<option value="0">Seleccione Paciente Ambulatorio</option>';

$stmt = $connect->prepare('SELECT id, CONCAT(nompa, " ", apepa) AS paciente FROM `patients_ambulatorios` ORDER BY nompa ASC');
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    extract($row);
    ?>
    <option value="<?php echo $id; ?>"><?php echo $paciente ?></option>
    <?php
}
