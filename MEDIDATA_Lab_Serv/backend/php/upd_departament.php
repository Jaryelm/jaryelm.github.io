<?php  
if(isset($_POST['upd_departament']))
{
    $id = $_POST['dep_id'];
    $departament_code = trim($_POST['dep_code']);
    $name = trim($_POST['dep_name']);
    $head_departament = trim($_POST['dep_head']);
    $description = trim($_POST['dep_description']);
    $email = trim($_POST['dep_email']);
    $phone = trim($_POST['dep_phone']);
    $status = trim($_POST['dep_status']);
    $observations = trim($_POST['dep_observations']);
    $updated_by = $_SESSION['name'] ?? 'System';

    try {

        $query = "UPDATE departaments SET departament_code=:departament_code, name=:name, head_departament=:head_departament, description=:description, email=:email, phone=:phone, status=:status, observations=:observations, updated_by=:updated_by WHERE id=:id LIMIT 1";
        $statement = $connect_rrhh->prepare($query);

        $data = [
            ':departament_code' => $departament_code,
            ':name' => $name,
            ':head_departament' => $head_departament,
            ':description' => $description,
            ':email' => $email,
            ':phone' => $phone,
            ':status' => $status,
            ':observations' => $observations,
            ':updated_by' => $updated_by,
            ':id' => $id
        ];
        $query_execute = $statement->execute($data);

        if($query_execute)
        {
            echo '<script type="text/javascript">
Swal.fire("Actualizado!", "Departamento actualizado correctamente", "success").then(function() {
            window.location = "departamentos.php";
        });
        </script>';
            exit(0);
        }
        else
        {
           echo '<script type="text/javascript">
Swal.fire("Error!", "Error al actualizar", "error").then(function() {
            window.location = "departamentos.php";
        });
        </script>';
            exit(0);
        }

    } catch (PDOException $e) {
        echo $e->getMessage();
    }

}
?>