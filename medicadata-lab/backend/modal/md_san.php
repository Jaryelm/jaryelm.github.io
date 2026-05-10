<!-- Ventana Modal -->
<form method="POST">
    <input type="checkbox" id="btnapoyo-modal">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;"> <!-- Scroll vertical -->
            <h2>Servicio de Apoyo Nutrición</h2>
            <hr>

            <!-- Apartado: Diagnostico Médico -->
            <h3>Diagnóstico Médico</h3>

            <label for="pt-apoyo"><b>PT</b></label>
            <input type="text" name="pt" id="pt-apoyo" placeholder="Ingrese PT">

            <label for="mb-apoyo"><b>MB</b></label>
            <input type="text" name="mb" id="mb-apoyo" placeholder="Ingrese MB">

            <label for="cb-apoyo"><b>CB</b></label>
            <input type="text" name="cb" id="cb-apoyo" placeholder="Ingrese CB">

            <label for="ar-apoyo"><b>AR</b></label>
            <input type="text" name="ar" id="ar-apoyo" placeholder="Ingrese AR">

            <label for="peso-habitual-apoyo"><b>Peso Habitual (KG)</b></label>
            <input type="number" step="0.01" name="peso_habitual" id="peso-habitual-apoyo" placeholder="Ingrese peso habitual">

            <label for="peso-actual-apoyo"><b>Peso Actual (KG)</b></label>
            <input type="number" step="0.01" name="peso_actual" id="peso-actual-apoyo" placeholder="Ingrese peso actual">

            <label for="talla-apoyo"><b>Talla (CM)</b></label>
            <input type="number" name="talla" id="talla-apoyo" placeholder="Ingrese talla en cm">

            <label for="imc-apoyo"><b>IMC (KG/M<sup>2</sup>)</b></label>
            <input type="number" step="0.01" name="imc" id="imc-apoyo" placeholder="Ingrese IMC">

            <hr>

            <!-- Apartado: Primera Etapa "Tamizaje inicial" -->
            <h3>Primera Etapa: Tamizaje Inicial</h3>

            <label><b>1. IMC &lt; 20.5?</b></label><br>
            <input type="radio" name="imc_20_5" value="Si" id="imc-si-apoyo"> <label for="imc-si-apoyo">Si</label>
            <input type="radio" name="imc_20_5" value="No" id="imc-no-apoyo"> <label for="imc-no-apoyo">No</label><br>

            <label><b>2. Ha perdido peso en los últimos tres meses?</b></label><br>
            <input type="radio" name="perdida_peso" value="Si" id="perdida-si-apoyo"> <label for="perdida-si-apoyo">Si</label>
            <input type="radio" name="perdida_peso" value="No" id="perdida-no-apoyo"> <label for="perdida-no-apoyo">No</label><br>

            <label><b>3. Ha reducido su ingesta en la última semana?</b></label><br>
            <input type="radio" name="ingesta_reducida" value="Si" id="ingesta-si-apoyo"> <label for="ingesta-si-apoyo">Si</label>
            <input type="radio" name="ingesta_reducida" value="No" id="ingesta-no-apoyo"> <label for="ingesta-no-apoyo">No</label><br>

            <label><b>4. Es un paciente grave?</b></label><br>
            <input type="radio" name="paciente_grave" value="Si" id="grave-si-apoyo"> <label for="grave-si-apoyo">Si</label>
            <input type="radio" name="paciente_grave" value="No" id="grave-no-apoyo"> <label for="grave-no-apoyo">No</label><br>

            <hr>

<!-- Apartado: Segunda Etapa "Valoración Riesgo Nutricional" -->
<h3>Segunda Etapa: Valoración Riesgo Nutricional</h3>
<p><b>A. Daño Estado Nutricional</b><br>
   De acuerdo a la evaluación, seleccione el marcador considerado como la variable más afectada del paciente.</p>

<!-- Perdida de Peso -->
<h4><b>Pérdida de Peso</b></h4>

<fieldset>
    <legend><b>Grado 1: Leve</b></legend>
    <label><input type="radio" name="grado1" value="5_en_dos_meses"> 5% en dos meses</label><br>
</fieldset>

<fieldset>
    <legend><b>Grado 2: Moderado</b></legend>
    <label><input type="radio" name="grado2" value="5_en_dos_meses"> 5% en dos meses</label><br>
</fieldset>

<fieldset>
    <legend><b>Grado 3: Severo</b></legend>
    <label><input type="radio" name="grado3" value="5_un_mes_15_tres_meses"> 5% en un mes o 15% en tres meses</label><br>
</fieldset>
<br>
<!-- IMC -->
<h4><b>IMC</b></h4>

<fieldset>
    <legend><b>Grado 2: Moderado</b></legend>
    <label><input type="radio" name="grado2" value="18.5_20.5"> Entre 18.5 y 20.5</label><br>
</fieldset>

<fieldset>
    <legend><b>Grado 3: Severo</b></legend>
    <label><input type="radio" name="grado3" value="menor_18.5"> Menor 18.5</label><br>
</fieldset>
<br>
<!-- Ingesta de Alimentos -->
<h4><b>Ingesta de Alimentos</b></h4>

<fieldset>
    <legend><b>Grado 1: Leve</b></legend>
    <label><input type="radio" name="grado1" value="50_70_semana_previa"> 50% al 70% Semana previa</label><br>
</fieldset>

<fieldset>
    <legend><b>Grado 2: Moderado</b></legend>
    <label><input type="radio" name="grado2" value="25_50_semana_previa"> 25% al 50% Semana previa</label><br>
</fieldset>

<fieldset>
    <legend><b>Grado 3: Severo</b></legend>
    <label><input type="radio" name="grado3" value="0_25_semana_previa"> 0% al 25% Semana previa</label><br>
</fieldset>


            <hr>

<!-- Apartado: B. Severidad de la enfermedad -->
<p><b>B. Severidad de la enfermedad</b><br>
   De acuerdo a la evaluación, seleccione el marcador más alto.</p>

<!-- Grado 1: Leve -->
<fieldset>
    <legend><b>Grado 1: Leve</b></legend>
    <label><input type="radio" name="severidad" value="fractura_cadera"> Fractura de cadera</label><br>
    <label><input type="radio" name="severidad" value="paciente_cronico"> Paciente crónico</label><br>
    <label><input type="radio" name="severidad" value="cirrosis"> Cirrosis</label><br>
    <label><input type="radio" name="severidad" value="diabetes"> Diabetes</label><br>
    <label><input type="radio" name="severidad" value="oncologia"> Oncología</label><br>
    <label><input type="radio" name="severidad" value="hemodialisis"> Hemodiálisis</label><br>
</fieldset>
<br>
<!-- Grado 2: Moderado -->
<fieldset>
    <legend><b>Grado 2: Moderado</b></legend>
    <label><input type="radio" name="severidad" value="cirugia_abdominal_mayor"> Cirugía Abdominal Mayor</label><br>
    <label><input type="radio" name="severidad" value="neumonia_severa"> Neumonía Severa</label><br>
    <label><input type="radio" name="severidad" value="neoplasia_hematologica"> Neoplasia Hematológica</label><br>
</fieldset>
<br>
<!-- Grado 3: Severo -->
<fieldset>
    <legend><b>Grado 3: Severo</b></legend>
    <label><input type="radio" name="severidad" value="traumatismo"> Traumatismo</label><br>
    <label><input type="radio" name="severidad" value="craneo_encefalico"> Cráneo encefálico</label><br>
    <label><input type="radio" name="severidad" value="trauma_general"> Trauma General</label><br>
    <label><input type="radio" name="severidad" value="quemadura_grave"> Quemadura Grave</label><br>
    <label><input type="radio" name="severidad" value="trasplante_medula"> Trasplante de Médula</label><br>
    <label><input type="radio" name="severidad" value="terapia_intensiva"> Terapia Intensiva</label><br>
</fieldset>

            <hr>

            <br>

<!-- Apartado: C. Edad -->
<fieldset>
    <legend><b>C. Edad</b></legend>
    <label><input type="radio" name="edad" value="0"> Menos de 70 años (0)</label><br>
    <label><input type="radio" name="edad" value="1"> 70 años o más (1)</label><br>

<p><b>A + B + C = Marcador Total</b></p>

<br>
            <legend><b>Marcador</b></legend><br>
            <input type="radio" name="marcador" value="Tres puntos o más" id="marcador-tres-apoyo"> <label for="marcador-tres-apoyo">Tres puntos o más</label><br>
            <input type="radio" name="marcador" value="Menor a tres puntos" id="marcador-menor-apoyo"> <label for="marcador-menor-apoyo">Menor a tres puntos</label><br>

            <legend><b>Interpretaci&oacute;n</b></legend><br>
            <input type="radio" name="interpretacion" value="Riesgo Nutricional" id="riesgo-apoyo"> <label for="riesgo-apoyo">Riesgo Nutricional</label><br>
            <input type="radio" name="interpretacion" value="Sin Riesgo Nutricional" id="sin-riesgo-apoyo"> <label for="sin-riesgo-apoyo">Sin Riesgo Nutricional</label><br>

            <legend><b>Acciones</b></legend><br>
            <input type="radio" name="acciones" value="Plan de Nutrici&oacute;n" id="plan-apoyo"> <label for="plan-apoyo">Plan de Nutrición</label><br>
            <input type="radio" name="acciones" value="Revaluaci&oacute;n Semanal" id="revaluacion-apoyo"> <label for="revaluacion-apoyo">Reevaluación Semanal</label><br>
</fieldset>

            <br>

            <label for="diagnosticos-apoyo"><b>Diagn&oacute;sticos</b></label><br>
            <textarea name="diagnosticos" id="diagnosticos-apoyo" placeholder="Escribe aquí el diagnóstico..." style="height: 200px;"></textarea>

            <hr>

            <input type="button" class="registerbtn" name="submit" value="Guardar" onclick="enviarApoyo();">
        </div>
        <label for="btnapoyo-modal" class="cerrar-modal"></label>
    </div>
</form>
<!-- Fin de Ventana Modal -->
