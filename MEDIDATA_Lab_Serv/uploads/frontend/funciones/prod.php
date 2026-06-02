<?php
require '../../backend/bd/Conexion.php';

echo '<option value="0">Seleccione producto</option>';

$stmt = $connect->prepare('SELECT * FROM `product` ORDER BY nompro ASC');
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    extract($row);
    ?>
    <option value="<?php echo $idprcd; ?>"><?php echo $nompro; ?></option>
    <?php
}