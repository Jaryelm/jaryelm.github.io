<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">





    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../servicioalcliente/menu.php';
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
include_once '../admin/perfil.php';
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
            
<form id="antecedentes-form" action="procesar_antecedentes.php" method="POST" class="antecedentes-form">
    <h2>Antecedentes</h2>

    <!-- Antecedentes Familiares -->
    <div class="form-group">
        <label for="antecedentes-familiares">Antecedentes Familiares:</label>
        <textarea id="antecedentes-familiares" name="antecedentes_familiares" rows="4" placeholder="Describa los antecedentes familiares aquí"></textarea>
    </div>

    <!-- Alergias -->
    <div class="form-group">
        <label for="alergias">Alergias:</label>
        <textarea id="alergias" name="alergias" rows="4" placeholder="Especifique las alergias"></textarea>
    </div>

    <!-- Medicamentos Actuales -->
    <div class="form-group">
        <label for="medicamentos-actuales">Medicamentos Actuales:</label>
        <textarea id="medicamentos-actuales" name="medicamentos_actuales" rows="4" placeholder="Ingrese los medicamentos actuales"></textarea>
    </div>

    <!-- Tipo Sanguíneo -->
    <div class="form-group">
        <label for="tipeo-sanguineo">Tipeo Sanguíneo:</label>
        <input id="tipeo-sanguineo" name="tipeo_sanguineo" type="text" placeholder="Ingrese el tipo sanguíneo">
    </div>

    <!-- Antecedentes Médicos -->
    <fieldset>
        <legend>Antecedentes Médicos</legend>
        <div class="checkbox-group">
            <label><input type="checkbox" name="antecedentes_medicos[]" value="hipertension_arterial"> Hipertensión Arterial</label>
            <label><input type="checkbox" name="antecedentes_medicos[]" value="cancer"> Cáncer</label>
            <label><input type="checkbox" name="antecedentes_medicos[]" value="fumador"> Fumador</label>
            <label><input type="checkbox" name="antecedentes_medicos[]" value="diabetes"> Diabetes</label>
            <label><input type="checkbox" name="antecedentes_medicos[]" value="endocrinos"> Endocrinos</label>
            <label><input type="checkbox" name="antecedentes_medicos[]" value="pulmonares"> Pulmonares</label>
            <label><input type="checkbox" name="antecedentes_medicos[]" value="otros"> Otros</label>
        </div>
        <div class="form-group">
            <label for="notas-medicas">Notas de Antecedentes Médicos:</label>
            <textarea id="notas-medicas" name="notas_medicas" rows="4"></textarea>
        </div>
    </fieldset>

    <!-- Complicaciones Agudas en Diabetes -->
    <fieldset>
        <legend>Complicaciones Agudas en Diabetes</legend>
        <div class="checkbox-group">
            <label><input type="checkbox" name="complicaciones_diabetes[]" value="hipoglucemia"> Hipoglucemia</label>
            <label><input type="checkbox" name="complicaciones_diabetes[]" value="estado_hiperosmolar"> Estado Hiperosmolar</label>
            <label><input type="checkbox" name="complicaciones_diabetes[]" value="cetoacidosis"> Cetoacidosis</label>
            <label><input type="checkbox" name="complicaciones_diabetes[]" value="otros"> Otros</label>
        </div>
        <div class="form-group">
            <label for="notas-diabetes">Notas:</label>
            <textarea id="notas-diabetes" name="notas_diabetes" rows="4"></textarea>
        </div>
    </fieldset>

    <!-- Enfermedades Crónicas -->
    <fieldset>
        <legend>Enfermedades Crónicas</legend>
        <div class="checkbox-group">
            <label><input type="checkbox" name="enfermedades_cronicas[]" value="nefropatia"> Nefropatía</label>
            <label><input type="checkbox" name="enfermedades_cronicas[]" value="neuropatia_diabetica"> Neuropatía Diabética</label>
            <label><input type="checkbox" name="enfermedades_cronicas[]" value="cardiopatia"> Cardiopatía</label>
            <label><input type="checkbox" name="enfermedades_cronicas[]" value="tiroideopatias"> Tiroideopatías</label>
            <label><input type="checkbox" name="enfermedades_cronicas[]" value="retinopatia_diabetica"> Retinopatía Diabética</label>
            <label><input type="checkbox" name="enfermedades_cronicas[]" value="otros"> Otros</label>
        </div>
        <div class="form-group">
            <label for="notas-cronicas">Notas:</label>
            <textarea id="notas-cronicas" name="notas_cronicas" rows="4"></textarea>
        </div>
    </fieldset>

    <!-- Antecedentes Quirúrgicos -->
    <fieldset>
        <legend>Antecedentes Quirúrgicos</legend>
        <div class="checkbox-group">
            <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="apendicectomia"> Apendicectomía</label>
            <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="colecistectomia"> Colecistectomía</label>
            <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="esterilizacion"> Esterilización Quirúrgica</label>
            <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="cirugia_mama"> Cirugía de Mama</label>
            <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="cesareas"> Cesáreas</label>
            <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="otros"> Otros</label>
        </div>
        <div class="form-group">
            <label for="notas-quirurgicas">Notas:</label>
            <textarea id="notas-quirurgicas" name="notas_quirurgicas" rows="4"></textarea>
        </div>
    </fieldset>

    <!-- Botón de Envío -->
    <button type="submit" class="btn-submit">Guardar Antecedentes</button>
</form>

<style>
    .antecedentes-form {
        max-width: 1500px;
        margin: 20px auto;
        background-color: #f9f9f9;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .antecedentes-form h2 {
        color: #035c67;
        text-align: center;
        font-size: 1.8rem;
        margin-bottom: 15px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
    display: block;
    margin-bottom: 10px;
    font-size: 1rem;
    color: #035c67;
    font-weight: bold;
    border-bottom: 2px solid #ddd; /* Subrayar el título */
    padding-bottom: 5px; /* Espacio entre el subrayado y el input */
}


    .form-group textarea,
    .form-group input {
        width: 100%;
        padding: 15px;
        font-size: 1rem;
        border: 1px solid #ddd;
        border-radius: 10px;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease-in-out;
        transition: border-color 0.2s;
    }

    .form-group textarea:focus,
    .form-group input:focus {
        border-color: #06adbf;
        box-shadow: 0 0 8px rgba(6, 173, 191, 0.4);
        outline: none;
    }

    textarea {
    resize: vertical;
}

    fieldset {
        margin-bottom: 25px;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
    }

    fieldset legend {
        font-weight: bold;
        color: #035c67;
        padding: 0 10px;
    }

    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .checkbox-group label {
        font-size: 0.95rem;
        color: #035c67;
        cursor: pointer;
    }

    label {
    font-size: 1rem; /* Fuente unificada con campos */
    margin-bottom: 8px; /* Separación con el campo */
    display: block; /* Asegurar que esté encima del input */
}

    .btn-submit {
        width: 100%;
        padding: 12px;
        font-size: 1.2rem;
        color: white;
        background-color: #035c67;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .btn-submit:hover {
        background-color: #06adbf;
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


