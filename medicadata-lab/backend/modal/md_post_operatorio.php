<!-- Formulario Modal -->
<form method="POST">
    <input type="checkbox" id="postoperativo-modal">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;">
            <h2>Periodo Post Operatorio</h2>
            <hr>
            <br>

            <h3>Evaluación del Riesgo</h3>
            <br>

            <div style="display: flex; align-items: center; gap: 10px;">
                <span>Nivel Bajo</span>
                <input type="radio" name="riesgo_caidas" value="Bajo" required>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>Nivel Medio</span>
                <input type="radio" name="riesgo_caidas" value="Medio">
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>Nivel Alto</span>
                <input type="radio" name="riesgo_caidas" value="Alto">
            </div>
            <br><br>

<h3>Medidas de Seguridad Utilizadas en la Prevención</h3>
<br>
<?php 
$medidas = [
    "Identificación de Riesgo Real o Potencial", "Continuidad en las Medidas de Seguridad",
    "Barandales de Camilla Arriba", "Comunicación Enfermero/a Paciente",
    "Ambiente Libre de Riesgo", "Vigilancia Estrecha", "Frenos de Camilla",
    "Sujeción", "Movilización Asistida", "Deambulación Asistida"
];
foreach ($medidas as $medida): ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input type="checkbox" name="medidas_seguridad[]" value="<?php echo htmlspecialchars($medida, ENT_QUOTES, 'UTF-8'); ?>">
        <label><?php echo htmlspecialchars($medida, ENT_QUOTES, 'UTF-8'); ?></label>
    </div>
<?php endforeach; ?>
<br>


<h3>Evaluación del Dolor</h3>
<br>

<div>
    <label for="hora_dolor">Hora</label>
    <br>
    <input type="time" name="hora_dolor" required>
</div>
<br>

<div>
    <label for="grado_dolor">Grado (0-10)</label>
    <br>
    <select name="grado_dolor">
        <?php for ($i = 0; $i <= 10; $i++): ?>
            <option value="<?php echo $i; ?>"> <?php echo $i; ?> </option>
        <?php endfor; ?>
    </select>
</div>
<br>

<div>
    <label for="localizacion_dolor">Localización</label>
    <br>
    <input type="text" name="localizacion_dolor" required>
</div>
<br>

<div>
    <label for="actividad_dolor">Actividad</label>
    <br>
    <input type="text" name="actividad_dolor" required>
</div>
<br>


<h3>Escala de Valoración de Aldrete</h3>
<br>

<table style="width: 100%; border-collapse: collapse; text-align: left; border: 1px solid #ddd;">
    <tr>
        <th style="border: 1px solid #ddd; padding: 8px;">Parámetro</th>
        <th style="border: 1px solid #ddd; padding: 8px;">Opción</th>
    </tr>
    <tr>
        <td style="border: 1px solid #ddd; padding: 8px;">Actividad Muscular</td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <label><input type="radio" name="actividad_muscular" value="Movimientos Voluntarios (4 extremidades)"> Movimientos Voluntarios (4 extremidades)</label><br>
            <label><input type="radio" name="actividad_muscular" value="Movimientos Voluntarios (2 extremidades)"> Movimientos Voluntarios (2 extremidades)</label><br>
            <label><input type="radio" name="actividad_muscular" value="Completamente Inmóvil"> Completamente Inmóvil</label>
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid #ddd; padding: 8px;">Respiración</td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <label><input type="radio" name="respiracion" value="Respiraciones Amplias y Capaz de Toser"> Respiraciones Amplias y Capaz de Toser</label><br>
            <label><input type="radio" name="respiracion" value="Respiraciones Limitadas y Tos Débil"> Respiraciones Limitadas y Tos Débil</label><br>
            <label><input type="radio" name="respiracion" value="APNEA"> APNEA</label>
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid #ddd; padding: 8px;">Circulación</td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <label><input type="radio" name="circulacion" value="Tensión Arterial ≤ 20% de Cifras Control"> Tensión Arterial ≤ 20% de Cifras Control</label><br>
            <label><input type="radio" name="circulacion" value="Tensión Arterial 20-50% de Cifras Control"> Tensión Arterial 20-50% de Cifras Control</label><br>
            <label><input type="radio" name="circulacion" value="Tensión Arterial ≥ 50% de Cifras Control"> Tensión Arterial ≥ 50% de Cifras Control</label>
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid #ddd; padding: 8px;">Estado de Conciencia</td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <label><input type="radio" name="estado_conciencia" value="Completamente Despierto"> Completamente Despierto</label><br>
            <label><input type="radio" name="estado_conciencia" value="Responde Cuando se le Llama"> Responde Cuando se le Llama</label><br>
            <label><input type="radio" name="estado_conciencia" value="No Responde"> No Responde</label>
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid #ddd; padding: 8px;">Coloración</td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <label><input type="radio" name="coloracion" value="Mucosas Rosadas"> Mucosas Rosadas</label><br>
            <label><input type="radio" name="coloracion" value="Palidez"> Palidez</label><br>
            <label><input type="radio" name="coloracion" value="Cianosis"> Cianosis</label>
        </td>
    </tr>
</table>

            <br>

<h3>Sala de Recuperación</h3>
<br>
<?php 
$tiempos = ["Al Salir", "20 Minutos", "60 Minutos", "90 Minutos", "120 Minutos"];
foreach ($tiempos as $tiempo): ?>
    <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
        <span style="font-weight: bold; text-align: center;"><?php echo $tiempo; ?></span>
        <div style="display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px; width: 100%; justify-content: center;">
            <?php for ($i = 1; $i <= 8; $i++): ?>
                <input type="number" name="sala_recuperacion[<?php echo strtolower(str_replace(' ', '_', $tiempo)); ?>][]" 
                       min="0" max="100" 
                       style="width: 60px; text-align: center;">
            <?php endfor; ?>
        </div>
    </div>
<?php endforeach; ?>
<br>



<h3>Alta</h3>
<div style="display: flex; align-items: center; gap: 20px;">
    <label><input type="radio" name="alta_si" value="Si"> Si</label>
    <label><input type="radio" name="alta_no" value="No"> No</label>
</div>
            <br>

            <h3>Hora</h3>
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="hora_alta">Hora de Alta</label>
                <input type="time" name="hora_alta" required>
            </div>
            <br>

            <h3>A su cuarto</h3>
            <div style="display: flex; align-items: center; gap: 20px;">
                <label><input type="radio" name="a_cuarto" value="Si"> Si</label>
                <label><input type="radio" name="a_cuarto" value="No"> No</label>
            </div>
            <br>

            <h3>A su domicilio</h3>
            <div style="display: flex; align-items: center; gap: 20px;">
                <label><input type="radio" name="a_domicilio" value="Si"> Si</label>
                <label><input type="radio" name="a_domicilio" value="No"> No</label>
            </div>
            <br>

            <input type="button" class="registerbtn" name="submit" value="Guardar" onclick="enviarPostOperativo();"> 
        </div>
        <label for="postoperativo-modal" class="cerrar-modal"></label>
    </div>
</form>