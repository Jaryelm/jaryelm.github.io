<!-- Ventana Modal -->
<form method="POST">
    <input type="checkbox" id="btnTransfusionModal">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;"> <!-- Scroll vertical -->
            <h2>Registro de Transfusión de Hemoderivados</h2>
            <hr>

            <input type="hidden" name="idpa" id="idpa" value="<?php echo $_GET['id']; ?>">

            <!-- Información General -->
            <h3>Información General</h3>
            <label for="tipo_rh"><b>Tipo RH</b></label>
            <input type="text" name="tipo_rh" id="tipo_rh" required>

            <label for="diagnostico_hemoderivados"><b>Diagnóstico</b></label>
            <input type="text" name="diagnostico_hemoderivados" id="diagnostico_hemoderivados">

            <label for="medico_tratante_hemoderivados"><b>Médico Tratante</b></label>
            <input type="text" name="medico_tratante_hemoderivados" id="medico_tratante_hemoderivados" required>

            <label for="enfermero_responsable_hemoderivados"><b>Enfermero Responsable</b></label>
            <input type="text" name="enfermero_responsable_hemoderivados" id="enfermero_responsable_hemoderivados" required>

            <hr>

            <!-- Componentes Sanguíneos -->
            <h3>Tipo de Hemoderivado a Transfundir</h3>
            <label for="sangre_completa_hemoderivados"><b>Sangre Completa</b></label>
            <input type="text" name="sangre_completa_hemoderivados" id="sangre_completa_hemoderivados">

            <label for="globulos_rojos_hemoderivados"><b>Glóbulos Rojos</b></label>
            <input type="text" name="globulos_rojos_hemoderivados" id="globulos_rojos_hemoderivados">

            <label for="plasma_normal_hemoderivados"><b>Plasma Normal</b></label>
            <input type="text" name="plasma_normal_hemoderivados" id="plasma_normal_hemoderivados">

            <label for="plasma_fresco_congelado_hemoderivados"><b>Plasma Fresco Congelado</b></label>
            <input type="text" name="plasma_fresco_congelado_hemoderivados" id="plasma_fresco_congelado_hemoderivados">

            <label for="plaquetas_hemoderivados"><b>Plaquetas</b></label>
            <input type="text" name="plaquetas_hemoderivados" id="plaquetas_hemoderivados">

            <label for="plaquetas_aferesis_hemoderivados"><b>Plaquetas Aféresis</b></label>
            <input type="text" name="plaquetas_aferesis_hemoderivados" id="plaquetas_aferesis_hemoderivados">

            <label for="crio_precipitado_hemoderivados"><b>Crio-Precipitado</b></label>
            <input type="text" name="crio_precipitado_hemoderivados" id="crio_precipitado_hemoderivados">

            <label for="otros_hemoderivados"><b>Otros</b></label>
            <input type="text" name="otros_hemoderivados" id="otros_hemoderivados">

            <label for="cantidad_unidades_hemoderivados"><b>Cantidad de Unidades</b></label>
            <input type="text" name="cantidad_unidades_hemoderivados" id="cantidad_unidades_hemoderivados" required>

            <hr>

            <!-- Tiempos de Transfusión -->
            <h3>Tiempos de Transfusión</h3>
            <label for="hora_inicio_hemoderivados"><b>Hora de Inicio</b></label>
            <input type="time" name="hora_inicio_hemoderivados" id="hora_inicio_hemoderivados" required>

            <label for="hora_finalizacion_hemoderivados"><b>Hora de Finalización</b></label>
            <input type="time" name="hora_finalizacion_hemoderivados" id="hora_finalizacion_hemoderivados" required>

            <hr>

            <!-- Valores Antes de la Transfusión -->
            <h3>Estado Hemodinamico del Paciente</h3>
            <label for="pa_antes_transfundir"><b>PA</b></label>
            <input type="text" name="pa_antes_transfundir" id="pa_antes_transfundir" required>

            <label for="fc_antes_transfundir"><b>FC</b></label>
            <input type="text" name="fc_antes_transfundir" id="fc_antes_transfundir" required>

            <label for="ta_antes_transfundir"><b>TA</b></label>
            <input type="text" name="ta_antes_transfundir" id="ta_antes_transfundir" required>

            <label for="fr_antes_transfundir"><b>FR</b></label>
            <input type="text" name="fr_antes_transfundir" id="fr_antes_transfundir" required>

            <label for="spo2_antes_transfundir"><b>SPO2</b></label>
            <input type="text" name="spo2_antes_transfundir" id="spo2_antes_transfundir" required>

            <hr>

            <!-- Seguimiento Post-Transfusión -->
            <h3>Seguimiento Post-Transfusión</h3>

            <label for="pa_30minutos_iniciar"><b>PA (30 min)</b></label>
            <input type="text" name="pa_30minutos_iniciar" id="pa_30minutos_iniciar">

            <label for="fc_30minutos_iniciar"><b>FC (30 min)</b></label>
            <input type="text" name="fc_30minutos_iniciar" id="fc_30minutos_iniciar">

            <label for="ta_30minutos_iniciar"><b>TA (30 min)</b></label>
            <input type="text" name="ta_30minutos_iniciar" id="ta_30minutos_iniciar">

            <label for="fr_30minutos_iniciar"><b>FR (30 min)</b></label>
            <input type="text" name="fr_30minutos_iniciar" id="fr_30minutos_iniciar">

            <label for="spo2_30minutos_iniciar"><b>SPO2 (30 min)</b></label>
            <input type="text" name="spo2_30minutos_iniciar" id="spo2_30minutos_iniciar">

            <!-- Seguimiento Post-Transfusión -->
            <h3>Seguimiento Post-Transfusión</h3>

            <label for="pa_1hora_iniciar"><b>PA (1 hora)</b></label>
            <input type="text" name="pa_1hora_iniciar" id="pa_1hora_iniciar">

            <label for="fc_1hora_iniciar"><b>FC (1 hora)</b></label>
            <input type="text" name="fc_1hora_iniciar" id="fc_1hora_iniciar">

            <label for="ta_1hora_iniciar"><b>TA (1 hora)</b></label>
            <input type="text" name="ta_1hora_iniciar" id="ta_1hora_iniciar">

            <label for="fr_1hora_iniciar"><b>FR (1 hora)</b></label>
            <input type="text" name="fr_1hora_iniciar" id="fr_1hora_iniciar">

            <label for="spo2_1hora_iniciar"><b>SPO2 (1 hora)</b></label>
            <input type="text" name="spo2_1hora_iniciar" id="spo2_1hora_iniciar">

            <!-- Seguimiento Post-Transfusión -->
            <h3>Seguimiento Post-Transfusión</h3>

            <label for="pa_2horas_iniciar"><b>PA (2 horas)</b></label>
            <input type="text" name="pa_2horas_iniciar" id="pa_2horas_iniciar">

            <label for="fc_2horas_iniciar"><b>FC (2 horas)</b></label>
            <input type="text" name="fc_2horas_iniciar" id="fc_2horas_iniciar">

            <label for="ta_2horas_iniciar"><b>TA (2 horas)</b></label>
            <input type="text" name="ta_2horas_iniciar" id="ta_2horas_iniciar">

            <label for="fr_2horas_iniciar"><b>FR (2 horas)</b></label>
            <input type="text" name="fr_2horas_iniciar" id="fr_2horas_iniciar">

            <label for="spo2_2horas_iniciar"><b>SPO2 (2 horas)</b></label>
            <input type="text" name="spo2_2horas_iniciar" id="spo2_2horas_iniciar">

            <!-- Seguimiento Post-Transfusión -->
            <h3>Seguimiento Post-Transfusión</h3>

            <label for="pa_3horas_iniciar"><b>PA (3 horas)</b></label>
            <input type="text" name="pa_3horas_iniciar" id="pa_3horas_iniciar">

            <label for="fc_3horas_iniciar"><b>FC (3 horas)</b></label>
            <input type="text" name="fc_3horas_iniciar" id="fc_3horas_iniciar">

            <label for="ta_3horas_iniciar"><b>TA (3 horas)</b></label>
            <input type="text" name="ta_3horas_iniciar" id="ta_3horas_iniciar">

            <label for="fr_3horas_iniciar"><b>FR (3 horas)</b></label>
            <input type="text" name="fr_3horas_iniciar" id="fr_3horas_iniciar">

            <label for="spo2_3horas_iniciar"><b>SPO2 (3 horas)</b></label>
            <input type="text" name="spo2_3horas_iniciar" id="spo2_3horas_iniciar">

            <hr>

            <!-- Reacciones a la Transfusión -->
            <h3>Reacciones a la Transfusión</h3>
            <label for="transfusion_reacciones"><b>Registrar Reacciones</b></label>
            <textarea name="transfusion_reacciones" id="transfusion_reacciones" rows="4"></textarea>

            <hr>

            <input type="button" class="registerbtn" id="btnGuardar" name="submit" value="Guardar">
        </div>
        <label for="btnTransfusionModal" class="cerrar-modal"></label>
    </div>
</form>
<!-- Fin de Ventana Modal -->
