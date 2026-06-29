<?php

require_once __DIR__ . '/rrhh_guard.php';
require_once __DIR__ . '/postulaciones_guard.php';

if (!function_exists('medidata_demo_flujo_ids')) {
    function medidata_demo_flujo_ids(): ?array
    {
        $path = __DIR__ . '/../scripts/.demo_flujo_ids.json';
        if (!is_readable($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }
}

if (!function_exists('medidata_demo_flujo_etapas')) {
    /** @return array<int, array<string, mixed>> */
    function medidata_demo_flujo_etapas(): array
    {
        return [
            [
                'orden' => 1,
                'titulo' => 'Postulación Web',
                'estado' => 'Pendiente → Incorporado',
                'pantalla' => 'Postulantes Website',
                'descripcion' => 'El candidato envía CV desde medicasa.hn eligiendo puesto (ej. Auxiliar de Enfermería II). RRHH ve la vacante sugerida e incorpora al proceso.',
                'admin_url' => '../recursos/reclutamiento.php',
                'usr_url' => '../recursos/reclutamiento_usr.php',
            ],
            [
                'orden' => 2,
                'titulo' => 'En Espera',
                'estado' => 'En Espera',
                'pantalla' => 'Postulantes',
                'descripcion' => 'Candidato incorporado. RRHH revisa datos iniciales y prepara formulario de empleado.',
                'admin_url' => 'postulantes.php',
                'usr_url' => 'postulantes_usr.php',
            ],
            [
                'orden' => 3,
                'titulo' => 'Formulario Empleados',
                'estado' => 'Formulario Empleados',
                'pantalla' => 'Detalle candidato',
                'descripcion' => 'Se completa información extendida (dependientes, emergencia, etc.). Avance desde detalle con cambio de estado.',
                'admin_url' => 'detalle_postulante.php',
                'usr_url' => 'detalle_postulante_usr.php',
            ],
            [
                'orden' => 4,
                'titulo' => 'Entrevista',
                'estado' => 'Entrevista',
                'pantalla' => 'Entrevistas + Calendario',
                'descripcion' => 'Entrevista programada en agenda del escritorio. Resultado registrado en ficha del candidato.',
                'admin_url' => 'entrevista.php',
                'usr_url' => 'entrevista_usr.php',
            ],
            [
                'orden' => 5,
                'titulo' => 'Pruebas Psicométricas',
                'estado' => 'Pruebas Psicometricas',
                'pantalla' => 'Pruebas Psicométricas',
                'descripcion' => 'Evaluación psicométrica (16PF, Cleaver, etc.) y puntaje general.',
                'admin_url' => 'pruebas_psicometricas.php',
                'usr_url' => 'pruebas_psicometricas_usr.php',
            ],
            [
                'orden' => 6,
                'titulo' => 'Requisitos de Contratación',
                'estado' => 'Llenando Expediente',
                'pantalla' => 'Requisitos de Contratación',
                'descripcion' => 'Recolección de documentos: DNI, antecedentes, referencias, diplomas, croquis, etc.',
                'admin_url' => 'requisitos_contratacion.php',
                'usr_url' => 'requisitos_contratacion_usr.php',
            ],
            [
                'orden' => 7,
                'titulo' => 'Contratado',
                'estado' => 'Contratado',
                'pantalla' => 'Detalle candidato / Colaboradores',
                'descripcion' => 'Proceso cerrado. Siguiente paso operativo: alta en Enfermería, Administrativo o Servicios Generales.',
                'admin_url' => 'detalle_postulante.php',
                'usr_url' => 'detalle_postulante_usr.php',
            ],
        ];
    }
}

if (!function_exists('medidata_demo_flujo_candidatos')) {
    /** @return array<int, object> */
    function medidata_demo_flujo_candidatos(): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $mainDb = defined('dbname') ? (string) dbname : 'medic9ue_medi_data';
            $stmt = $pdo->query(
                "SELECT c.*, p.name AS position_name
                 FROM candidates c
                 LEFT JOIN vacant_positions v ON c.id_vacant_position = v.id
                 LEFT JOIN positions_details pd ON v.id_position = pd.id
                 LEFT JOIN {$mainDb}.positions p ON pd.id_positions = p.id
                 WHERE c.deleted = 0 AND c.fullname LIKE '[DEMO]%'
                 ORDER BY c.id ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            error_log('medidata_demo_flujo_candidatos: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('medidata_demo_flujo_vacante_id')) {
    function medidata_demo_flujo_vacante_id(): int
    {
        $ids = medidata_demo_flujo_ids();
        if ($ids && !empty($ids['vacante_id'])) {
            return (int) $ids['vacante_id'];
        }
        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare(
                "SELECT id FROM vacant_positions WHERE reason LIKE ? AND deleted = 0 LIMIT 1"
            );
            $stmt->execute(['%[DEMO]%']);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}
