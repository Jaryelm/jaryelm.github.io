<?php
/**
 * Resolución del "Jefe inmediato" (modelo por departamento).
 * Un jefe es el usuario asignado como `id_jefe` de un departamento
 * (medic9ue_medi_rrhh_interviews.departaments). Su equipo son todos los colaboradores
 * (id_user) que pertenecen a ese departamento (id_departamento en las tablas de staff).
 *
 * Todas las funciones degradan a vacío si la columna `id_jefe` aún no existe (migración
 * 20260724-03 no aplicada), para no romper la app.
 */

if (!function_exists('medidata_jefe_departamentos_de')) {
    /** @return int[] ids de departamentos que encabeza este usuario */
    function medidata_jefe_departamentos_de(PDO $connect, int $jefe_user_id): array
    {
        if ($jefe_user_id <= 0) return [];
        try {
            $stmt = $connect->prepare("SELECT id FROM medic9ue_medi_rrhh_interviews.departaments WHERE id_jefe = ?");
            $stmt->execute([$jefe_user_id]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('medidata_jefe_es_jefe')) {
    function medidata_jefe_es_jefe(PDO $connect, int $user_id): bool
    {
        return count(medidata_jefe_departamentos_de($connect, $user_id)) > 0;
    }
}

if (!function_exists('medidata_user_departamento')) {
    /**
     * Departamento (id) al que pertenece un colaborador, buscando su id_user en las
     * tablas de personal de la BD principal. Null si no pertenece a ninguno.
     */
    function medidata_user_departamento(PDO $connect, int $user_id): ?int
    {
        if ($user_id <= 0) return null;
        try {
            $stmt = $connect->prepare("
                SELECT id_departamento FROM (
                    SELECT id_user, id_departamento FROM staff_administrative    WHERE id_user IS NOT NULL
                    UNION SELECT id_user, id_departamento FROM doctor            WHERE id_user IS NOT NULL
                    UNION SELECT id_user, id_departamento FROM nurse             WHERE id_user IS NOT NULL
                    UNION SELECT id_user, id_departamento FROM staff_general_services WHERE id_user IS NOT NULL
                    UNION SELECT id_user, id_departamento FROM staff_medifarma   WHERE id_user IS NOT NULL
                ) s
                WHERE s.id_user = ? AND s.id_departamento IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $dep = $stmt->fetchColumn();
            return $dep !== false && $dep !== null ? (int) $dep : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('medidata_departamento_jefe')) {
    /**
     * Jefe asignado a un departamento (departaments.id_jefe).
     *
     * @return array{id:int, name:string}|null
     */
    function medidata_departamento_jefe(PDO $connect, int $departamento_id): ?array
    {
        if ($departamento_id <= 0) return null;
        try {
            $stmt = $connect->prepare("SELECT id_jefe FROM medic9ue_medi_rrhh_interviews.departaments WHERE id = ?");
            $stmt->execute([$departamento_id]);
            $idJefe = $stmt->fetchColumn();
            if (!$idJefe) return null;

            $stmt = $connect->prepare("SELECT id, name, username FROM users WHERE id = ?");
            $stmt->execute([(int) $idJefe]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$u) return null;

            $nombre = trim((string) ($u['name'] ?? ''));
            if ($nombre === '') $nombre = trim((string) ($u['username'] ?? ''));
            return ['id' => (int) $u['id'], 'name' => $nombre];
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('medidata_jefe_equipo_user_ids')) {
    /**
     * @return int[] id_user de todos los colaboradores de los departamentos que encabeza el jefe.
     */
    function medidata_jefe_equipo_user_ids(PDO $connect, int $jefe_user_id): array
    {
        $deps = medidata_jefe_departamentos_de($connect, $jefe_user_id);
        if (!$deps) return [];
        $in = implode(',', array_fill(0, count($deps), '?'));
        $sql = "
            SELECT DISTINCT id_user FROM (
                SELECT id_user, id_departamento FROM staff_administrative    WHERE id_user IS NOT NULL
                UNION SELECT id_user, id_departamento FROM doctor            WHERE id_user IS NOT NULL
                UNION SELECT id_user, id_departamento FROM nurse             WHERE id_user IS NOT NULL
                UNION SELECT id_user, id_departamento FROM staff_general_services WHERE id_user IS NOT NULL
                UNION SELECT id_user, id_departamento FROM staff_medifarma   WHERE id_user IS NOT NULL
            ) s
            WHERE s.id_departamento IN ($in)
        ";
        try {
            $stmt = $connect->prepare($sql);
            $stmt->execute($deps);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {
            return [];
        }
    }
}
