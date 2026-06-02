<?php
require_once('../../backend/bd/Conexion.php');
date_default_timezone_set('America/Tegucigalpa');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validación de datos obligatorios
        if (!isset($_POST['nombre_paciente']) || !isset($_POST['edad_paciente']) || !isset($_POST['fecha_registro'])) {
            throw new Exception("Faltan datos obligatorios.");
        }

        // Capturar los datos del formulario
        $nombre_paciente = trim($_POST['nombre_paciente']);
        $edad_paciente = intval($_POST['edad_paciente']);
        $fecha_registro = $_POST['fecha_registro'];
        $dx_paciente = trim($_POST['dx_paciente'] ?? '');
        $medico_paciente = trim($_POST['medico_paciente'] ?? '');
        $peso_kg = $_POST['peso_kg'] ?? null;
        $talla_cm = $_POST['talla_cm'] ?? null;
        $sonda_foley = trim($_POST['sonda_foley'] ?? '');
        $sng = trim($_POST['sng'] ?? '');
        $dias_hospitalizacion = $_POST['dias_hospitalizacion'] ?? null;
        $ventilacion_mecanica = $_POST['ventilacion_mecanica'] ?? '';
        $monitor_cardiaco = $_POST['monitor_cardiaco'] ?? '';

        // Signos Vitales
        $presion_arterial = $_POST['presion_arterial'] ?? '';
        $frecuencia_cardiaca = $_POST['frecuencia_cardiaca'] ?? null;
        $frecuencia_respiratoria = $_POST['frecuencia_respiratoria'] ?? null;
        $temperatura = $_POST['temperatura'] ?? null;
        $saturacion = $_POST['saturacion'] ?? null;
        $pvc = $_POST['pvc'] ?? '';
        $pic = $_POST['pic'] ?? '';
        $pia = $_POST['pia'] ?? '';
        $glucometria = $_POST['glucometria'] ?? null;

        // Aportes
        $soluciones_endovenosas = trim($_POST['soluciones_endovenosas'] ?? '');

        // Balance - Ingestas
        $agua_endogena = trim($_POST['agua_endogena'] ?? '');
        $alimentacion = trim($_POST['alimentacion'] ?? '');
        $hemoderivados = trim($_POST['hemoderivados'] ?? '');

        // Balance - Excretas
        $perdidas_insensibles = trim($_POST['perdidas_insensibles'] ?? '');
        $residuo_gastrico = trim($_POST['residuo_gastrico'] ?? '');
        $hemovac = trim($_POST['hemovac'] ?? '');
        $succion_drenos = trim($_POST['succion_drenos'] ?? '');
        $vomitos_sng = trim($_POST['vomitos_sng'] ?? '');
        $heces = trim($_POST['heces'] ?? '');
        $diuresis_por = $_POST['diuresis_por'] ?? '';
        $diuresis_acumulada = trim($_POST['diuresis_acumulada'] ?? '');

        // Verificar si ya existe un registro para el mismo paciente y fecha
        $checkStmt = $connect->prepare("SELECT COUNT(*) AS total FROM cuidados_intensivos WHERE nombre_paciente = :nombre AND fecha_registro = :fecha");
        $checkStmt->bindParam(':nombre', $nombre_paciente, PDO::PARAM_STR);
        $checkStmt->bindParam(':fecha', $fecha_registro, PDO::PARAM_STR);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['total'] > 0) {
            echo "<script>
                Swal.fire({
                    title: '¡Atención!',
                    text: 'Este paciente ya tiene un registro para esta fecha.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location = 'uci.php'; 
                });
            </script>";
            exit;
        }

        // Insertar en la base de datos
        $stmt = $connect->prepare("INSERT INTO cuidados_intensivos 
            (nombre_paciente, edad, fecha_registro, dx_paciente, medico_paciente, peso_kg, talla_cm, 
            sonda_foley, sng, dias_hospitalizacion, ventilacion_mecanica, monitor_cardiaco,
            presion_arterial, frecuencia_cardiaca, frecuencia_respiratoria, temperatura, saturacion, pvc, pic, pia, glucometria,
            soluciones_endovenosas, agua_endogena, alimentacion, hemoderivados,
            perdidas_insensibles, residuo_gastrico, hemovac, succion_drenos, vomitos_sng, heces, diuresis_por, diuresis_acumulada, fecha_creacion) 
            VALUES 
            (:nombre_paciente, :edad_paciente, :fecha_registro, :dx_paciente, :medico_paciente, :peso_kg, :talla_cm, 
            :sonda_foley, :sng, :dias_hospitalizacion, :ventilacion_mecanica, :monitor_cardiaco,
            :presion_arterial, :frecuencia_cardiaca, :frecuencia_respiratoria, :temperatura, :saturacion, :pvc, :pic, :pia, :glucometria,
            :soluciones_endovenosas, :agua_endogena, :alimentacion, :hemoderivados,
            :perdidas_insensibles, :residuo_gastrico, :hemovac, :succion_drenos, :vomitos_sng, :heces, :diuresis_por, :diuresis_acumulada, NOW())");

        $stmt->execute([
            ':nombre_paciente' => $nombre_paciente,
            ':edad_paciente' => $edad_paciente,
            ':fecha_registro' => $fecha_registro,
            ':dx_paciente' => $dx_paciente,
            ':medico_paciente' => $medico_paciente,
            ':peso_kg' => $peso_kg,
            ':talla_cm' => $talla_cm,
            ':sonda_foley' => $sonda_foley,
            ':sng' => $sng,
            ':dias_hospitalizacion' => $dias_hospitalizacion,
            ':ventilacion_mecanica' => $ventilacion_mecanica,
            ':monitor_cardiaco' => $monitor_cardiaco,
            ':presion_arterial' => $presion_arterial,
            ':frecuencia_cardiaca' => $frecuencia_cardiaca,
            ':frecuencia_respiratoria' => $frecuencia_respiratoria,
            ':temperatura' => $temperatura,
            ':saturacion' => $saturacion,
            ':pvc' => $pvc,
            ':pic' => $pic,
            ':pia' => $pia,
            ':glucometria' => $glucometria,
            ':soluciones_endovenosas' => $soluciones_endovenosas,
            ':agua_endogena' => $agua_endogena,
            ':alimentacion' => $alimentacion,
            ':hemoderivados' => $hemoderivados,
            ':perdidas_insensibles' => $perdidas_insensibles,
            ':residuo_gastrico' => $residuo_gastrico,
            ':hemovac' => $hemovac,
            ':succion_drenos' => $succion_drenos,
            ':vomitos_sng' => $vomitos_sng,
            ':heces' => $heces,
            ':diuresis_por' => $diuresis_por,
            ':diuresis_acumulada' => $diuresis_acumulada
        ]);

        echo "<script>
            Swal.fire({
                title: '¡Éxito!',
                text: 'Registro guardado correctamente.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location = 'uci.php'; 
            });
        </script>";
    } catch (Exception $e) {
        echo "<script>
            Swal.fire({
                title: '¡Error!',
                text: '" . addslashes($e->getMessage()) . "',
                icon: 'error',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location = 'uci.php';
            });
        </script>";
    }
}
?>
