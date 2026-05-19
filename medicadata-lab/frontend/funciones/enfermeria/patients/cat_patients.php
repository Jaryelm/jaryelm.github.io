<?php
require $_SERVER['DOCUMENT_ROOT'] . '/backend/bd/Conexion.php';

echo '<option value=0>Seleccione Paciente</option>';

$stmt = $connect->prepare('SELECT idpa, CONCAT(nompa, apepa) AS paciente FROM `patients` ORDER BY nompa ASC');
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  extract($row);
?>
  <option value="<?php echo $idpa; ?>"><?php echo $paciente ?></option>
<?php
}
