<?php
/** Fragmento maestro producto nuevo; usar solo índice [0] — compra_unificada.js reindexa al clonar. */
$mx = '[0]';
?>
<div class="maestro-grid">
    <strong class="maestro-grid-title">Datos maestro — producto nuevo</strong>
    <label>Nombre producto <span class="badge-warning">*</span><br>
        <input type="text" name="np_nompro<?php echo $mx; ?>" class="form-control" style="width:100%"></label>
    <label>Principio activo<br>
        <input type="text" name="np_principio_activo<?php echo $mx; ?>" style="width:100%"></label>
    <label>Línea <span class="badge-warning">*</span><br>
        <select name="np_linea<?php echo $mx; ?>" class="select2 np_linea_select" style="width:100%">
            <option value="">Seleccione</option>
            <option value="MEDICAMENTOS">MEDICAMENTOS</option>
            <option value="MATERIAL DESCARTABLE">MATERIAL DESCARTABLE</option>
            <option value="INSUMOS DESCARTABLES">INSUMOS DESCARTABLES</option>
            <option value="LABORATORIO">LABORATORIO</option>
            <option value="DIAGNOSTICO RADIOLOGÍA E IMAGEN">DIAGNOSTICO RADIOLOGÍA E IMAGEN</option>
            <option value="SUMINISTRO DE OFICINA">SUMINISTRO DE OFICINA</option>
            <option value="PROMOCIONES">PROMOCIONES</option>
            <option value="ALQUILER">ALQUILER</option>
            <option value="ARRENDAMIENTO">ARRENDAMIENTO</option>
        </select></label>
    <label>Área / Sub línea <span class="badge-warning">*</span><br>
        <select name="np_sub_linea<?php echo $mx; ?>" class="select2" style="width:100%">
            <option value="">Seleccionar...</option>
            <option value="GENERAL">GENERAL</option>
            <option value="HOSPITALARIOS">HOSPITALARIOS</option>
            <option value="QUÍMICA CLÍNICA">QUÍMICA CLÍNICA</option>
            <option value="BACTERIOLOGÍA">BACTERIOLOGÍA</option>
            <option value="EMERGENCIA">EMERGENCIA</option>
            <option value="MATERIALES DE OFICINA">MATERIALES DE OFICINA</option>
        </select></label>
    <label>Código inventario (auto)<br>
        <input type="text" name="np_codpro<?php echo $mx; ?>" class="np_codpro_field" readonly style="width:100%"></label>
    <label>Código de barras<br>
        <input type="text" name="np_codbars<?php echo $mx; ?>" style="width:100%"></label>
    <label>Categoría de uso<br>
        <select name="np_medicate<?php echo $mx; ?>" class="select2 np_medicate" style="width:100%"><option value="">Seleccionar...</option></select></label>
    <label>Presentación<br>
        <select name="np_presentacion<?php echo $mx; ?>" class="select2" style="width:100%">
            <option value="">Seleccionar...</option>
            <option value="TABLETA">TABLETA</option>
            <option value="COMPRIMIDO">COMPRIMIDO</option>
            <option value="CAJA">CAJA</option>
            <option value="AMPOLLA">AMPOLLA</option>
            <option value="FRASCO">FRASCO</option>
            <option value="UNIDAD">UNIDAD</option>
        </select></label>
    <label>Forma farmacéutica<br>
        <select name="np_forma_farmaceutica<?php echo $mx; ?>" class="select2" style="width:100%">
            <option value="">Seleccionar...</option>
            <option value="CAJA X 10 COMPRIMIDOS">CAJA X 10 COMPRIMIDOS</option>
            <option value="CAJA X 30 TABLETAS">CAJA X 30 TABLETAS</option>
            <option value="FRASCO">FRASCO</option>
        </select></label>
    <label>Concentración<br>
        <select name="np_concentracion<?php echo $mx; ?>" class="select2" style="width:100%">
            <option value="">Seleccionar...</option>
            <option value="Baja Concentración (0.7 GR/2.5 ML)">Baja Concentración (0.7 GR/2.5 ML)</option>
            <option value="Media Concentración (5 MG)">Media Concentración (5 MG)</option>
            <option value="Alta Concentración (40 MG)">Alta Concentración (40 MG)</option>
        </select></label>
    <label>Vía administración<br>
        <select name="np_via_administracion<?php echo $mx; ?>" class="select2" style="width:100%">
            <option value="">Seleccionar...</option>
            <option value="ORAL">ORAL</option>
            <option value="INTRAMUSCULAR">INTRAMUSCULAR</option>
            <option value="TÓPICA">TÓPICA</option>
            <option value="INTRAVENOSA">INTRAVENOSA</option>
        </select></label>
    <label>Margen % <span class="badge-warning">*</span><br>
        <input type="number" name="np_margen_ganancia<?php echo $mx; ?>" step="0.01" min="0" value="20" style="width:100%"></label>
    <label>Impuesto producto <span class="badge-warning">*</span><br>
        <label><input type="radio" name="np_impuesto<?php echo $mx; ?>" value="G"> Gravado 15%</label>
        <label><input type="radio" name="np_impuesto<?php echo $mx; ?>" value="E" checked> Exento</label></label>
    <label>Stock mínimo reorden<br>
        <input type="number" name="np_stock_minimo<?php echo $mx; ?>" min="0" value="5" style="width:100%"></label>
    <label>Fecha vencimiento <span class="badge-warning">*</span><br>
        <input type="date" name="np_fecha_vencimiento<?php echo $mx; ?>" style="width:100%"></label>
    <label>Fotografía producto<br>
        <input type="file" name="foto_linea[0]" accept="image/*"></label>
</div>
