<?php
require __DIR__ . '/../swiftmailer-master/lib/swift_required.php';

use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;

// Configuración del correo
$transport = (new Swift_SmtpTransport('smtp.gmail.com', 587, 'tls'))
    ->setUsername('contact@delnorte.io') // Reemplaza con tu dirección de correo Gmail
    ->setPassword('iwgxygaybgrbdwcn'); // Reemplaza con tu contraseña de aplicación sin espacios

$mailer = new Swift_Mailer($transport);

// Crear el mensaje
$message = (new Swift_Message('Support'))
    ->setFrom(['contact@delnorte.io' => 'Support']) // Reemplaza con tu dirección de correo Gmail
    ->setTo(['contact@delnorte.io', 'moises.castillo@medicasa.hn']) // Correo de destino
    ->setBody(
        '<html>
          <head>
            <meta charset="UTF-8">
            <style>
              body { font-size: 12px; }
              p { font-size: 20px; }
            </style>
          </head>
          <body>
            <h1>Nuevo Mensaje de Soporte</h1>
            <p><strong>Nombre:</strong> ' . htmlspecialchars($_POST['name']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($_POST['email']) . '</p>
            <p><strong>Teléfono:</strong> ' . htmlspecialchars($_POST['phone']) . '</p>
            <p><strong>Asunto:</strong> ' . htmlspecialchars($_POST['subject']) . '</p>
            <p><strong>Tema:</strong> ' . htmlspecialchars($_POST['property_type']) . '</p>
            <p><strong>Mensaje:</strong> ' . htmlspecialchars($_POST['message']) . '</p>
            <div class="firma">
              <p>DELNORTE Website</p>
            </div>
          </body>
        </html>',
        'text/html'
    );

try {
    $result = $mailer->send($message);
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
            text: 'El mensaje no pudo ser enviado. Error: {$e->getMessage()}',
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