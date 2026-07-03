<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">


    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../contabilidad/menu.php';
// incuir el archivo menu principal
?>

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#">
                <div class="form-group">
                </div>
            </form>
            
           
            <span class="divider"></span>
            <?php
include_once '../contabilidad/perfil.php';
// incuir el archivo menu principal
?>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
        <?php
// Obtener la hora actual
$hora_actual = date('H'); // Obtiene la hora en formato de 24 horas (0-23)

if ($hora_actual >= 6 && $hora_actual < 12) {
    $saludo = "Buenos Días";
} elseif ($hora_actual >= 12 && $hora_actual < 18) {
    $saludo = "Buenas Tardes";
} else {
    $saludo = "Buenas Noches";
}
?>

<h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

<br>
            
<div class="images-container">
    <div class="flex-card-image">
        <img src="../../backend/img/logo.png" alt="MEDIDATA Logo" />
    </div>
    <div class="content-container">
    <div class="card mission">
        <h2>Misión</h2>
        <p>Somos un equipo de profesionales comprometidos con la salud y calidad de vida de nuestros pacientes...</p>
    </div>
    <div class="card vision">
        <h2>Visión</h2>
        <p>Ser un hospital líder en Honduras, reconocido en el ámbito de la salud por su filosofía de servicio...</p>
    </div>
    <div class="card values">
        <h2>Valores</h2>
        <ul>
            <li>Humanismo</li>
            <li>Actitud de Servicio</li>
            <li>Solidaridad</li>
            <li>Trabajo en equipo</li>
            <li>Profesionalismo y competencia</li>
            <li>Puntualidad</li>
            <li>Integridad</li>
            <li>Mejora Continua</li>
        </ul>
    </div>
</div>
</div>

<style>
    .content-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
    margin: 30px 0;
}

.card {
    background-color: #035c67;
    color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    max-width: 400px;
    width: 100%;
    text-align: center;
}

.card h2 {
    font-size: 1.8rem;
    margin-bottom: 10px;
    text-transform: uppercase;
    color: #06adbf;
}

.card p, 
.card ul {
    font-size: 1rem;
    line-height: 1.6;
    margin: 10px 0;
}

.card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.card ul li {
    margin: 5px 0;
    padding: 5px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.card ul li:last-child {
    border-bottom: none;
}
</style>


        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

</body>
</html>


