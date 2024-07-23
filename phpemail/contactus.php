<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);
$mail->CharSet = 'UTF-8';

// Habilitar la depuración de PHPMailer
$mail->SMTPDebug = 3; // 3 para depuración detallada (muestra todos los mensajes SMTP)
$mail->Debugoutput = 'html'; // Formato de depuración en HTML

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'contact@delnorte.io'; // Reemplaza con tu dirección de correo Gmail
    $mail->Password = 'rgzdpvytgdjgwlza'; // Reemplaza con tu contraseña de aplicación sin espacios
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Destinatarios
    $mail->setFrom('contact@delnorte.io', 'Support'); // Reemplaza con tu dirección de correo Gmail
    $mail->addAddress('contact@delnorte.io'); // Correo de destino
    $mail->addAddress('moises.castillo@medicasa.hn'); // Segundo destinatario

    // Recogiendo datos del formulario
    $nombre = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $telefono = htmlspecialchars($_POST['phone']);
    $asunto = htmlspecialchars($_POST['subject']);
    $tipo_propiedad = htmlspecialchars($_POST['property_type']);
    $mensaje = htmlspecialchars($_POST['message']);

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Support';
    $mail->Body = "
    <html>
    <head>
      <meta charset='UTF-8'>
      <style>
        body { font-size: 12px; }
        p { font-size: 20px; }
      </style>
    </head>
    <body>
      <h1>Nuevo Mensaje de Soporte</h1>
      <p><strong>Nombre:</strong> {$nombre}</p>
      <p><strong>Email:</strong> {$email}</p>
      <p><strong>Teléfono:</strong> {$telefono}</p>
      <p><strong>Asunto:</strong> {$asunto}</p>
      <p><strong>Tema:</strong> {$tipo_propiedad}</p>
      <p><strong>Mensaje:</strong> {$mensaje}</p>
      <div class='firma'>
        <p>DELNORTE Website</p>
      </div>
    </body>
    </html>";

    $mail->send();
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
    <meta charset='UTF-8'>
    <title>Envío Exitoso</title>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '¡Enviado!',
            text: 'El mensaje ha sido enviado exitosamente.',
            icon: 'success',
            confirmButtonText: 'Cerrar'
        }).then((result) => {
            window.location.href = '/'; // Cambia esto según tu URL de redirección
        });
    </script>
    </body>
    </html>";
} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
    <meta charset='UTF-8'>
    <title>Error de Envío</title>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: 'Error',
            text: 'El mensaje no pudo ser enviado. Error: {$mail->ErrorInfo}',
            icon: 'error',
            confirmButtonText: 'Cerrar'
        }).then((result) => {
            window.history.back();
        });
    </script>
    </body>
    </html>";
}
?>