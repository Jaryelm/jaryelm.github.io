<?php 
require_once('../../backend/bd/Conexion.php'); 

// Agregar usuario
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $rol = trim($_POST['rol']);

    // Configurar la zona horaria de Tegucigalpa, Honduras
    date_default_timezone_set('America/Tegucigalpa');
    $currentDateTime = date('Y-m-d H:i:s'); // Obtener la fecha y hora actual

    if (empty($username)) {
        echo "<script>
            swal({
                title: 'Campo Vacío',
                text: 'Por favor ingrese el nombre de usuario.',
                icon: 'warning',
                button: 'OK',
            });
        </script>";
    } elseif (empty($email)) {
        echo "<script>
            swal({
                title: 'Campo Vacío',
                text: 'Por favor ingrese el correo electrónico.',
                icon: 'warning',
                button: 'OK',
            });
        </script>";
    } else {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->fetchColumn() == 0) {
            // Encriptar la contraseña con MD5
            $hashedPassword = MD5($password);
            $sql = "INSERT INTO users (username, name, email, password, rol, created_at) VALUES (:username, :name, :email, :password, :rol, :created_at)";
            $stmt = $connect->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':created_at', $currentDateTime);

            if ($stmt->execute()) {
                echo "<script>
                    swal({
                        title: 'Usuario Agregado',
                        text: 'El usuario se agregó correctamente.',
                        icon: 'success',
                        button: 'OK',
                    }).then(function() {
                        window.location = 'crear_user.php';
                    });
                </script>";
            } else {
                echo "<script>
                    swal({
                        title: 'Error',
                        text: 'Hubo un problema al agregar el usuario.',
                        icon: 'error',
                        button: 'OK',
                    });
                </script>";
            }
        } else {
            echo "<script>
                swal({
                    title: 'Usuario Existente',
                    text: 'El usuario ya está registrado.',
                    icon: 'error',
                    button: 'OK',
                });
            </script>";
        }
    }
}
