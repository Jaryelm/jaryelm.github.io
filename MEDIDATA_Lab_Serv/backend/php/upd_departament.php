<?php
require_once __DIR__ . '/departament_schema.php';

if (isset($connect_rrhh) && $connect_rrhh instanceof PDO) {
    medidata_ensure_departament_phone_ext($connect_rrhh);
}

if(isset($_POST['upd_departament']))
{
    $id = $_POST['dep_id'];
    $departament_code = trim($_POST['dep_code']);
    $name = trim($_POST['dep_name']);
    $head_departament = trim($_POST['dep_head']);
    $description = trim($_POST['dep_description']);
    $email = trim($_POST['dep_email']);
    $phone = trim($_POST['dep_phone']);
    $phone_ext = trim($_POST['dep_phone_ext'] ?? '');
    $status = trim($_POST['dep_status']);
    $observations = trim($_POST['dep_observations']);
    $updated_by = $_SESSION['name'] ?? 'System';
    $returnList = medidata_departament_return_list_url();

    try {

        $query = "UPDATE departaments SET departament_code=:departament_code, name=:name, head_departament=:head_departament, description=:description, email=:email, phone=:phone, phone_ext=:phone_ext, status=:status, observations=:observations, updated_by=:updated_by WHERE id=:id LIMIT 1";
        $statement = $connect_rrhh->prepare($query);

        $data = [
            ':departament_code' => $departament_code,
            ':name' => $name,
            ':head_departament' => $head_departament,
            ':description' => $description,
            ':email' => $email,
            ':phone' => $phone,
            ':phone_ext' => $phone_ext,
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
            window.location = "' . $returnList . '";
        });
        </script>';
            exit(0);
        }
        else
        {
           echo '<script type="text/javascript">
Swal.fire("Error!", "Error al actualizar", "error").then(function() {
            window.location = "' . $returnList . '";
        });
        </script>';
            exit(0);
        }

    } catch (PDOException $e) {
        echo $e->getMessage();
    }

}
?>
