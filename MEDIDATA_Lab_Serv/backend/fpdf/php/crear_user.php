<?php 
require_once('../../backend/bd/Conexion.php'); 

// Agregar usuario
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $rol = trim($_POST['rol']);
    $uidBiometrico = trim($_POST['uid_biometrico'] ?? '');

    // Configurar la zona horaria de Tegucigalpa, Honduras
    date_default_timezone_set('America/Tegucigalpa');
    $currentDateTime = date('Y-m-d H:i:s'); // Obtener la fecha y hora actual

    if (empty($username)) {
        echo "<script>
            Swal.fire({
                title: 'Campo vacío',
                text: 'Por favor ingrese el nombre de usuario.',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
        </script>";
    } elseif (empty($email)) {
        echo "<script>
            Swal.fire({
                title: 'Campo vacío',
                text: 'Por favor ingrese el correo electrónico.',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
        </script>";
    } else {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->fetchColumn() == 0) {
            if ($uidBiometrico !== '') {
                $sqlUid = "SELECT id FROM users WHERE uid_biometrico = :uid_biometrico";
                $stmtUid = $connect->prepare($sqlUid);
                $stmtUid->bindParam(':uid_biometrico', $uidBiometrico);
                $stmtUid->execute();
                if ($stmtUid->fetchColumn() != 0) {
                    echo "<script>
                        Swal.fire({
                            title: 'ID biométrico en uso',
                            text: 'El ID biométrico ya está asignado a otro usuario.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    </script>";
                    return;
                }
            }
            // Encriptar la contraseña con MD5
            $hashedPassword = MD5($password);
            $sql = "INSERT INTO users (username, name, email, password, rol, created_at, uid_biometrico) VALUES (:username, :name, :email, :password, :rol, :created_at, :uid_biometrico)";
            $stmt = $connect->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':created_at', $currentDateTime);
            $uidValue = $uidBiometrico !== '' ? $uidBiometrico : null;
            $stmt->bindParam(':uid_biometrico', $uidValue);

            if ($stmt->execute()) {
                echo "<script>
                    Swal.fire({
                        title: 'Usuario agregado',
                        text: 'El usuario se agregó correctamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(function() {
                        window.location = 'crear_user.php';
                    });
                </script>";
            } else {
                echo "<script>
                    Swal.fire({
                        title: 'Error',
                        text: 'Hubo un problema al agregar el usuario.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                </script>";
            }
        } else {
            echo "<script>
                Swal.fire({
                    title: 'Usuario existente',
                    text: 'El usuario ya está registrado.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            </script>";
        }
    }
}
