<!--Ventana Modal-->
<form id="form">
    <input type="checkbox" id="btn-modal">
    <div class="container-modal">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto;">
            <h2>Nuevo Exámen</h2>
            <hr>
            <br>

            <input type="hidden" id="geidpa" name="geidpa" value="<?php echo $d->idpa; ?>">
            <input type="hidden" id="genopa" name="genopa" value="<?php echo $d->nompa; ?>">
            <input type="hidden" id="procesado_por" name="procesado_por" value="<?php echo $name; ?>">

            <!-- Antecedentes Familiares -->
            <div class="form-group">
                <label for="antecedentes-familiares"><b>Antecedentes Familiares:</b></label>
                <textarea id="antecedentes-familiares" name="antecedentes_familiares" rows="4" placeholder="Describa los antecedentes familiares aquí"></textarea>
            </div>

            <!-- Alergias -->
            <div class="form-group">
                <label for="alergias"><b>Alergias:</b></label>
                <textarea id="alergias" name="alergias" rows="4" placeholder="Especifique las alergias"></textarea>
            </div>

            <!-- Medicamentos Actuales -->
            <div class="form-group">
                <label for="medicamentos-actuales"><b>Medicamentos Actuales:</b></label>
                <textarea id="medicamentos-actuales" name="medicamentos_actuales" rows="4" placeholder="Ingrese los medicamentos actuales"></textarea>
            </div>

            <!-- Tipo Sanguíneo -->
            <div class="form-group">
                <label for="tipeo-sanguineo"><b>Tipo Sanguíneo:</b></label>
                <input id="tipeo-sanguineo" name="tipeo_sanguineo" type="text" placeholder="Ingrese el tipo sanguíneo">
            </div>

            <!-- Antecedentes Médicos -->
            <fieldset>
                <legend><b>Antecedentes Médicos</b></legend>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="antecedentes_medicos[]" value="Hipertensión Arterial"> Hipertensión Arterial</label>
                    <label><input type="checkbox" name="antecedentes_medicos[]" value="Cáncer"> Cáncer</label>
                    <label><input type="checkbox" name="antecedentes_medicos[]" value="Fumador"> Fumador</label>
                    <label><input type="checkbox" name="antecedentes_medicos[]" value="Diabetes"> Diabetes</label>
                    <label><input type="checkbox" name="antecedentes_medicos[]" value="Endocrinos"> Endocrinos</label>
                    <label><input type="checkbox" name="antecedentes_medicos[]" value="Pulmonares"> Pulmonares</label>
                    <label><input type="checkbox" name="antecedentes_medicos[]" value="Otros"> Otros</label>
                </div>
                <div class="form-group">
                    <label for="notas-medicas"><b>Notas de Antecedentes Médicos:</b></label>
                    <textarea id="notas-medicas" name="notas_medicas" rows="4"></textarea>
                </div>
            </fieldset>

            <!-- Complicaciones Agudas en Diabetes -->
            <fieldset>
                <legend><b>Complicaciones Agudas en Diabetes</b></legend>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="complicaciones_diabetes[]" value="Hipoglucemia"> Hipoglucemia</label>
                    <label><input type="checkbox" name="complicaciones_diabetes[]" value="Estado Hiperosmolar"> Estado Hiperosmolar</label>
                    <label><input type="checkbox" name="complicaciones_diabetes[]" value="Cetoacidosis"> Cetoacidosis</label>
                    <label><input type="checkbox" name="complicaciones_diabetes[]" value="Otros"> Otros</label>
                </div>
                <div class="form-group">
                    <label for="notas-diabetes"><b>Notas:</b></label>
                    <textarea id="notas-diabetes" name="notas_diabetes" rows="4"></textarea>
                </div>
            </fieldset>

            <!-- Enfermedades Crónicas -->
            <fieldset>
                <legend><b>Enfermedades Crónicas</b></legend>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="enfermedades_cronicas[]" value="Nefropatía"> Nefropatía</label>
                    <label><input type="checkbox" name="enfermedades_cronicas[]" value="Neuropatía Diabética"> Neuropatía Diabética</label>
                    <label><input type="checkbox" name="enfermedades_cronicas[]" value="Cardiopatía"> Cardiopatía</label>
                    <label><input type="checkbox" name="enfermedades_cronicas[]" value="Tiroideopatías"> Tiroideopatías</label>
                    <label><input type="checkbox" name="enfermedades_cronicas[]" value="Retinopatía Diabética"> Retinopatía Diabética</label>
                    <label><input type="checkbox" name="enfermedades_cronicas[]" value="Otros"> Otros</label>
                </div>
                <div class="form-group">
                    <label for="notas-cronicas"><b>Notas:</b></label>
                    <textarea id="notas-cronicas" name="notas_cronicas" rows="4"></textarea>
                </div>
            </fieldset>

            <!-- Antecedentes Quirúrgicos -->
            <fieldset>
                <legend><b>Antecedentes Quirúrgicos</b></legend>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="Apendicectomía"> Apendicectomía</label>
                    <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="Colecistectomía"> Colecistectomía</label>
                    <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="Esterilización Quirúrgica"> Esterilización Quirúrgica</label>
                    <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="Cirugía de Mama"> Cirugía de Mama</label>
                    <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="Cesáreas"> Cesáreas</label>
                    <label><input type="checkbox" name="antecedentes_quirurgicos[]" value="Otros"> Otros</label>
                </div>
                <div class="form-group">
                    <label for="notas-quirurgicas"><b>Notas:</b></label>
                    <textarea id="notas-quirurgicas" name="notas_quirurgicas" rows="4"></textarea>
                </div>
            </fieldset>

            <button type="submit" name="submit" id="submit" class="registerbtn">Guardar</button>
        </div>
        <label for="btn-modal" class="cerrar-modal"></label>
    </div>
</form>
<!--Fin de Ventana Modal-->