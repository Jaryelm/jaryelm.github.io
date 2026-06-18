<?php
require '../../backend/bd/Conexion.php';

echo '<option value="0">Seleccione proveedor</option>';

$stmt = $connect->prepare('SELECT * FROM `proveedor_comercial` ORDER BY nombre_empresa ASC');
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    extract($row);
    ?>
    <option value="<?php echo $nombre_empresa; ?>"><?php echo $nombre_empresa; ?></option>
    <?php
}