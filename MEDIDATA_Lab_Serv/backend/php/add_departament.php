<?php 
require_once('../../backend/bd/Conexion.php');
require_once __DIR__ . '/departament_schema.php';

if (isset($connect_rrhh) && $connect_rrhh instanceof PDO) {
    medidata_ensure_departament_phone_ext($connect_rrhh);
}

 if(isset($_POST['add_departament']))
 {
    $departament_code=trim($_POST['dep_code']);
    $name=trim($_POST['dep_name']);
    $head_departament=trim($_POST['dep_head']);
    $description=trim($_POST['dep_description']);
    $email=trim($_POST['dep_email']);
    $phone=trim($_POST['dep_phone']);
    $phone_ext=trim($_POST['dep_phone_ext'] ?? '');
    $status=trim($_POST['dep_status']);
    $observations=trim($_POST['dep_observations']);
    $created_by = $_SESSION['name'] ?? 'System';
    $returnList = medidata_departament_return_list_url();
    $returnNew = medidata_departament_return_new_url();

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
            window.location = "' . $returnNew . '";
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
   $stmt = $connect_rrhh->prepare("INSERT INTO departaments(departament_code, name, head_departament, description, email, phone, phone_ext, status, observations, created_by) VALUES(:departament_code, :name, :head_departament, :description, :email, :phone, :phone_ext, :status, :observations, :created_by)");


$stmt->bindParam(':departament_code',$departament_code);
$stmt->bindParam(':name',$name);
$stmt->bindParam(':head_departament',$head_departament);
$stmt->bindParam(':description',$description);
$stmt->bindParam(':email',$email);
$stmt->bindParam(':phone',$phone);
$stmt->bindParam(':phone_ext',$phone_ext);
$stmt->bindParam(':status',$status);
$stmt->bindParam(':observations',$observations);
$stmt->bindParam(':created_by',$created_by);


   if($stmt->execute())
   {
    echo '<script type="text/javascript">
Swal.fire("Agregado!", "Departamento agregado correctamente", "success").then(function() {
            window.location = "' . $returnList . '";
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
            window.location = "' . $returnNew . '";
        });
        </script>';

}
  

  }
 
 }
?>