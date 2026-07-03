 <?php 
 require '../../backend/bd/Conexion.php';
 echo '<option value="0">Seleccionar... </option>';
 $stmt = $connect->prepare('SELECT * FROM `category` ORDER BY idcat   ASC');

  $stmt->execute();


  while($row=$stmt->fetch(PDO::FETCH_ASSOC))
        {
            extract($row);
            ?>
            <option value="<?php echo $idcat; ?>"><?php echo $nomcat; ?></option>

            <?php
        }

  ?>