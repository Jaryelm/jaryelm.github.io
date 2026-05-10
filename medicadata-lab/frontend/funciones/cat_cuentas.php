<?php
require '../../backend/bd/Conexion.php';

echo '<option value="0">Seleccione cuenta</option>';

// Elimina el filtro 'WHERE tipo_cuenta = :tipo_cuenta' para mostrar todas las cuentas
$stmt = $connect->prepare('SELECT * FROM `cuentas_catalogo` ORDER BY cuenta ASC');
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    extract($row);
    ?>
    <option value="<?php echo $tipo_cuenta . '  ' . $cuenta . '  ' . $nombre; ?>"><?php echo $tipo_cuenta . ' - ' . $cuenta . ' - ' . $nombre; ?></option>
    <?php
}
