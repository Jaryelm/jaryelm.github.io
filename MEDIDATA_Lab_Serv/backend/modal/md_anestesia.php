<!-- Modal para registrar Anestesia -->
<form method="POST">
    <input type="checkbox" id="anestesia-modal">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;"> <!-- Scroll vertical -->
            <h2>Registro de Anestesia</h2>
            <hr>
<br>
            <label for="tiempo_anestesia"><b>Tiempo de Anestesia (min)</b></label>
            <input type="number" name="tiempo_anestesia" id="tiempo_anestesia" required>

            <h3>Variables Monitorizadas</h3>
            <br>
            <label for="temp"><b>Temperatura (°C)</b></label>
            <input type="number" name="temp" id="temp" step="0.1" required>

            <label for="tension_arterial"><b>Tensión Arterial (mmHg)</b></label>
            <input type="text" name="tension_arterial" id="tension_arterial" required>

            <label for="pulso"><b>Pulso (latidos/min)</b></label>
            <input type="number" name="pulso" id="pulso" required>

            <label for="frecuencia_respiratoria"><b>Frecuencia Respiratoria (rpm)</b></label>
            <input type="number" name="frecuencia_respiratoria" id="frecuencia_respiratoria" required>

            <label for="frecuencia_cardiaca"><b>Frecuencia Cardiaca (lat/min)</b></label>
            <input type="number" name="frecuencia_cardiaca" id="frecuencia_cardiaca" required>

            <h3>Procedimientos Anestésicos</h3>
            <label for="diagnostico"><b>Diagnóstico</b></label>
            <select name="diagnostico" id="diagnostico" required>
                <option value="preoperatorio">Preoperatorio</option>
                <option value="operatorio">Operatorio</option>
                <option value="postoperatorio">Postoperatorio</option>
                <option value="transoperacion">Transoperacion</option>
            </select>

            <label for="operacion"><b>Operación</b></label>
            <input type="text" name="operacion" id="operacion" required>

            <label for="metodo_anestesia"><b>Método y Técnica Anestésica</b></label>
            <select name="metodo_anestesia" id="metodo_anestesia" required>
                <option value="Inducción IV">Inducción IV</option>
                <option value="Inhalación">Inhalación</option>
                <option value="Mixto">Mixto</option>
            </select>

            <label><b>Uso de dispositivos</b></label><br>

<!-- Mascarilla -->
<label><b>Mascarilla</b></label><br>
<input type="radio" name="mascarilla" value="Si"> Sí
<input type="radio" name="mascarilla" value="No" checked> No
<br>

<!-- Cánula -->
<label><b>Cánula</b></label><br>
<input type="radio" name="canula" value="Nasal" checked> Nasal
<input type="radio" name="canula" value="Oral"> Oral
<br>

<!-- Tubo Endotraqueal -->
<label><b>Tubo Endotraqueal</b></label>
<input type="text" name="tubo_endotraqueal" id="tubo_endotraqueal" placeholder="Ingrese datos...">
<br>

<!-- Globo Inflable -->
<label><b>Globo Inflable</b></label>
<input type="text" name="globo_inflable" id="globo_inflable" placeholder="Ingrese datos...">
<br>

<!-- Complicaciones -->
<label><b>Complicaciones</b></label><br>
<input type="radio" name="complicaciones" value="Si"> Sí
<input type="radio" name="complicaciones" value="No" checked> No
<br>

<!-- Sangre y Soluciones -->
<label><b>Sangre y Soluciones</b></label><br>
<textarea name="sangre_soluciones" id="sangre_soluciones" rows="3" cols="40" placeholder="Detalles..."></textarea>
            
<br>
            <label for="medicamentos"><b>Fármacos y Soluciones Administradas</b></label>
            <textarea name="medicamentos" id="medicamentos" rows="3" required></textarea>

            <h3>Casos Obstétricos (Si Aplica)</h3>
            <label for="caso_obstetrico"><b>Tipo</b></label>
            <select name="caso_obstetrico" id="caso_obstetrico">
                <option value="">No aplica</option>
                <option value="expulsion_placenta">Expulsión de Placenta</option>
                <option value="cesarea">Cesárea</option>
                <option value="parto_normal">Parto Normal</option>
            </select>

            <!-- Datos del Recién Nacido -->
            <h3>Datos del Recién Nacido (Si Aplica)</h3>
            <label for="nombre_recien_nacido"><b>Nombre Completo</b></label>
            <input type="text" name="nombre_recien_nacido" id="nombre_recien_nacido">

            <label for="hora_nacimiento"><b>Hora de Nacimiento</b></label>
            <input type="time" name="hora_nacimiento" id="hora_nacimiento">
<br><br>
            <label for="sexo"><b>Sexo</b></label>
            <select name="sexo" id="sexo">
                <option value="">No aplica</option>
                <option value="masculino">Masculino</option>
                <option value="femenino">Femenino</option>
            </select>

            <label for="peso"><b>Peso (kg)</b></label>
            <input type="number" name="peso" id="peso" step="0.1">

            <label for="talla"><b>Talla (cm)</b></label>
            <input type="number" name="talla" id="talla">

            <h3>Personal Médico Asignado</h3>
            <br>
            <label for="anestesiologo"><b>Anestesiólogo</b></label>
            <input type="text" name="anestesiologo" id="anestesiologo" required>

            <label for="clave"><b>Clave</b></label>
            <input type="text" name="clave" id="clave" required>

            <label for="cirujano"><b>Cirujano</b></label>
            <input type="text" name="cirujano" id="cirujano" required>

            <label for="ayudante"><b>Ayudante</b></label>
            <input type="text" name="ayudante" id="ayudante" required>

            <label for="instrumentista"><b>Instrumentista</b></label>
            <input type="text" name="instrumentista" id="instrumentista" required>

            <label for="circulante"><b>Circulante</b></label>
            <input type="text" name="circulante" id="circulante" required>

            <label for="observaciones"><b>Observaciones</b></label>
            <textarea name="observaciones" id="observaciones" rows="3"></textarea>

            <input type="hidden" id="idpa" name="idpa" value="<?php echo $id; ?>">
            <input type="hidden" id="procesado_por" name="procesado_por" value="<?php echo $name; ?>">

            <input type="button" class="registerbtn" name="submit" value="Guardar" onclick="enviarAnestesia();">
        </div>
        <label for="anestesia-modal" class="cerrar-modal"></label>
    </div>
</form>
