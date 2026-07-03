<?php
require_once __DIR__ . '/../../backend/bd/Conexion.php';

echo '<option value="0">Seleccione Paciente</option>';

$stmt = $connect->prepare('SELECT idpa, TRIM(CONCAT(COALESCE(nompa,\'\'), \' \', COALESCE(apepa,\'\'))) AS paciente FROM `patients` ORDER BY nompa ASC');
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  extract($row);
?>
  <option value="<?php echo (int) $idpa; ?>"><?php echo htmlspecialchars((string) $paciente, ENT_QUOTES, 'UTF-8'); ?></option>
<?php
}
