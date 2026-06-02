<?php
declare(strict_types=1);

/* Ruta estable (no depende del CWD del worker) + una sola carga por petición */
try {
    require_once dirname(__DIR__) . '/bd/Conexion.php';
} catch (PDOException $e) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $msg = strtolower($e->getMessage());
    if (strpos($msg, 'too many connections') !== false || strpos($msg, '[1040]') !== false) {
        $_SESSION['errMsg'] = 'El servidor de datos está al límite de conexiones. Espere unos segundos e intente iniciar sesión de nuevo.';
    } else {
        $_SESSION['errMsg'] = 'No se puede conectar a la base de datos en este momento. Intente de nuevo más tarde o contacte a Soporte TI.';
    }
    header('Location: ../frontend/login.php');
    exit();
}

session_start();

if (isset($_POST['login'])) {
    $errMsg = '';
    $username = trim($_POST['username']);
    $password = MD5(trim($_POST['password']));

    if (empty($username)) {
        $errMsg = 'Digite su usuario';
    }
    if (empty($password)) {
        $errMsg = 'Digite su contraseña';
    }

    if ($errMsg == '') {
        try {
            $stmt = $connect->prepare('SELECT id, username, name, email, password, rol FROM users WHERE username = :username');
            $stmt->execute([':username' => $username]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data == false) {
                $_SESSION['errMsg'] = "El usuario: $username no se encuentra registrado.";
                header('Location: ../frontend/login.php');
                exit();
            } else {
                if ($password == $data['password']) {
                    session_regenerate_id(true); // Regenerar para nueva sesión
                    $_SESSION['id'] = $data['id'];
                    $_SESSION['username'] = $data['username'];
                    $_SESSION['name'] = $data['name'];
                    $_SESSION['email'] = $data['email'];
                    $_SESSION['rol'] = $data['rol'];

                    $nowLogin = time();
                    $_SESSION['last_activity_ts'] = $nowLogin;
                    $_SESSION['last_activity_db_sync_ts'] = $nowLogin;

                    $localTime = date('Y-m-d H:i:s');
                    $updateStmt = $connect->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
                    $updateStmt->execute([$localTime, $data['id']]);

                    $redirectPaths = [
                        'Administrador' => 'admin/escritorio.php',
                        'Caja' => 'caja/escritorio.php',
                        'Contabilidad' => 'contabilidad/escritorio.php',
                        'Auxiliar Contable' => 'auxcontable/escritorio.php',
                        'Facturación' => 'facturacion/escritorio.php',
                        'Recursos_Humanos' => 'recursos_humanos/escritorio.php',
                        'Mantenimiento' => 'mantenimiento/escritorio.php',
                        'Médico' => 'medico/escritorio.php',
                        'Enfermero' => 'enfermeria/escritorio.php',
                        'Paciente' => 'paciente/escritorio.php',
                        'Proveedor' => 'proveedor/escritorio.php',
                        'Servicio al Cliente' => 'servicioalcliente/escritorio.php',
                        'Almacen' => 'almacen/escritorio.php',
                        'Almacen Hospitalario' => 'almacen_hospitalario/escritorio.php',
                        'Radiologo' => 'radiologiaeimagen/escritorio.php',
                        'Tecnico' => 'radiologiaeimagen/escritorio.php',
                        'Medifarma Almacen' => 'medifarma_almacen/escritorio.php'
                    ];

                    if (isset($redirectPaths[$_SESSION['rol']])) {
                        header('Location: ' . $redirectPaths[$_SESSION['rol']]);
                        exit();
                    } else {
                        $_SESSION['errMsg'] = 'Rol no reconocido. Por favor contacte a Soporte TI.';
                        header('Location: ../frontend/login.php');
                        exit();
                    }
                } else {
                    $_SESSION['errMsg'] = 'Contraseña incorrecta. Por favor contacte a Soporte TI.';
                    header('Location: ../frontend/login.php');
                    exit();
                }
            }
        } catch (PDOException $e) {
            $lower = strtolower($e->getMessage());
            if (strpos($lower, 'too many connections') !== false || strpos($lower, '[1040]') !== false) {
                $_SESSION['errMsg'] = 'El servidor de datos está saturado temporariamente. Espere unos segundos e intente de nuevo.';
            } else {
                $_SESSION['errMsg'] = 'Error en la base de datos. Por favor contacte a Soporte TI.';
            }
            header('Location: ../frontend/login.php');
            exit();
        }
    } else {
        $_SESSION['errMsg'] = $errMsg;
        header('Location: ../frontend/login.php');
        exit();
    }
}
