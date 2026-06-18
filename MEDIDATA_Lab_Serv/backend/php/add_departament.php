<?php 
require_once('../../backend/bd/Conexion.php'); 
 if(isset($_POST['add_departament']))
 {
    $departament_code=trim($_POST['dep_code']);
    $name=trim($_POST['dep_name']);
    $head_departament=trim($_POST['dep_head']);
    $description=trim($_POST['dep_description']);
    $email=trim($_POST['dep_email']);
    $phone=trim($_POST['dep_phone']);
    $status=trim($_POST['dep_status']);
    $observations=trim($_POST['dep_observations']);
    $created_by = $_SESSION['name'] ?? 'System';

  if(empty($departament_code)){
   $errMSG = "Por favor ingrese el código del departamento.";
  }
  else if(empty($name)){
   $errMSG = "Por favor ingrese el nombre del departamento.";
  }
   
  $stmt = "SELECT * FROM departaments WHERE departament_code ='$departament_code'";
   if(empty($departament_code)) {
             echo '<script type="text/javascript">
Swal.fire("Error!", "Código de departamento es requerido", "error").then(function() {
            window.location = "departamentos_nuevo.php";
        });
        </script>';
         }

         else
         {  
            $sql="SELECT * FROM departaments WHERE departament_code ='$departament_code' OR name ='$name'";
            
            $stmt = $connect_rrhh->prepare($sql);
            $stmt->execute();

            if ($stmt->fetchColumn() == 0) 
            {
                if(!isset($errMSG))
  {
   $stmt = $connect_rrhh->prepare("INSERT INTO departaments(departament_code, name, head_departament, description, email, phone, status, observations, created_by) VALUES(:departament_code, :name, :head_departament, :description, :email, :phone, :status, :observations, :created_by)");


$stmt->bindParam(':departament_code',$departament_code);
$stmt->bindParam(':name',$name);
$stmt->bindParam(':head_departament',$head_departament);
$stmt->bindParam(':description',$description);
$stmt->bindParam(':email',$email);
$stmt->bindParam(':phone',$phone);
$stmt->bindParam(':status',$status);
$stmt->bindParam(':observations',$observations);
$stmt->bindParam(':created_by',$created_by);


   if($stmt->execute())
   {
    echo '<script type="text/javascript">
Swal.fire("Agregado!", "Departamento agregado correctamente", "success").then(function() {
            window.location = "departamentos.php";
        });
        </script>';
   }
   else
   {
    $errMSG = "error while inserting....";
   }

  } 
            }

                else{

                     echo '<script type="text/javascript">
Swal.fire("Error!", "El código o nombre del departamento ya existe", "error").then(function() {
            window.location = "departamentos_nuevo.php";
        });
        </script>';

}
  

  }
 
 }
?>