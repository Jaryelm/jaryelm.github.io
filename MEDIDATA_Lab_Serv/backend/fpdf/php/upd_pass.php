<?php
require '../../backend/bd/Conexion.php';
session_start();

if (isset($_POST['upd_profile_pass'])) {
    $id = $_POST['newid'];
    $newPassword = MD5(trim($_POST['newpass'])); // Encriptar la nueva contraseña usando MD5

    try {
        $query = "UPDATE users SET password = :password WHERE id = :id LIMIT 1";
        $statement = $connect->prepare($query);

        $data = [
            ':password' => $newPassword,
            ':id' => $id
        ];
        
        $query_execute = $statement->execute($data);

        if ($query_execute) {
            $_SESSION['successMsg'] = "Contraseña actualizada correctamente.";
            header("Location: ../../frontend/usuarios/mostrar.php");
            exit();
        } else {
            $_SESSION['errorMsg'] = "Error al actualizar la contraseña. Contacte a Soporte TI.";
            header("Location: ../../frontend/usuarios/mostrar.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['errorMsg'] = "Error en la base de datos: " . $e->getMessage();
        header("Location: ../../frontend/usuarios/mostrar.php");
        exit();
    }
}
