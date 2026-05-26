<?php
require '../../backend/bd/Conexion.php';

echo '<option value="0">Seleccione un Puesto de Trabajo</option>';

$stmt = $connect->prepare('SELECT id, name FROM positions ORDER BY name ASC');
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    extract($row);
    ?>
    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
    <?php
}