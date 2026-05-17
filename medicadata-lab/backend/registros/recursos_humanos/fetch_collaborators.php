<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/bd/Conexion.php';

try {
    $sql = "WITH colaboradores AS (
	            SELECT 
	            	idodc AS `ID`,
                    ceddoc AS `Cedula`,
                    nodoc AS `Nombre`,
                    'Doctor' AS `Tipo_Empleado`,
                    sexd as `Sexo`,
                    nomesp as `Especialidad`
                FROM medic9ue_medi_data.doctor
                UNION ALL
                SELECT
	            	idnur AS `ID`,
                    numide AS `Cedula`,
                    nomnur AS `Nombre`,
                    'Enfermero' AS `Tipo_Empleado`,
                     sexnur as `Sexo`,
                     'Enfermero'as `Especialidad`
	            FROM medic9ue_medi_data.nurse
                UNION ALL
	            SELECT
	            	id AS `ID`,
                    'N/A' AS `Cedula`,
                    name AS `Nombre`,
	            	'Usuario' AS `Tipo_Empleado`,
	            	'N/A' as `Sexo`,
	            	'Usuario' as `Especialidad`
                FROM medic9ue_medi_data.users
            )
            SELECT * FROM colaboradores;";
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $collaborators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($collaborators);
}
catch (PDOException $e)
{
    echo json_encode(['error' => $e->getMessage()]);
}
?>