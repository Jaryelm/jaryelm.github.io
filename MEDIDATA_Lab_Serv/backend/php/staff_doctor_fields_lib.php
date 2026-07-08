<?php
/**
 * Campos legacy de la tabla doctor (especialidad, dirección, comisión).
 */

if (!function_exists('medidata_staff_doctor_especialidades')) {
    function medidata_staff_doctor_especialidades(): array
    {
        return [
            'RADIOLOGIA',
            'NEURO RADIOLOGIA',
            'MEDICINA INTERNA',
            'MEDICO INTERNISTA',
            'PEDIATRIA',
            'GINECOLOGIA Y OBSTETRICIA',
            'ORTOPEDIA Y TRAUMATOLOGIA',
            'CIRUGIA ORTOPEDICA',
            'DERMATOLOGIA',
            'ENDODONCIA',
            'HEMATOLOGIA',
            'ONCOLOGIA',
            'OTRA',
        ];
    }
}

if (!function_exists('medidata_staff_doctor_legacy_from_post')) {
    function medidata_staff_doctor_legacy_from_post(array $post, string $telefono, string $correoPersonal, string $correoInstitucional): array
    {
        $esp = strtoupper(trim((string) ($post['doctor_especialidad'] ?? '')));
        if ($esp === 'OTRA') {
            $esp = strtoupper(trim((string) ($post['doctor_especialidad_otra'] ?? '')));
        }

        $direcd = trim((string) ($post['doctor_direccion'] ?? ''));
        if ($direcd === '') {
            $direcd = 'N/A';
        }

        $phd = trim($telefono);
        if ($phd === '') {
            $phd = 'N/A';
        }

        $corr = trim($correoPersonal);
        if ($corr === '') {
            $corr = trim($correoInstitucional);
        }
        if ($corr === '') {
            $corr = 'N/A';
        }

        $comisiona = !empty($post['doctor_comisiona']) ? 'SI' : 'NO';

        return [
            'nomesp' => $esp,
            'direcd' => $direcd,
            'phd' => $phd,
            'corr' => $corr,
            'comisiona' => $comisiona,
        ];
    }
}

if (!function_exists('medidata_staff_doctor_resolve_especialidad_value')) {
    function medidata_staff_doctor_resolve_especialidad_value(?string $nomesp): array
    {
        $nomespRaw = trim((string) $nomesp);
        $nomespUpper = strtoupper($nomespRaw);
        if ($nomespUpper === 'RADIOLOGIA E IMAGENES' || $nomespUpper === 'RADIOLOGIA E IMAGEN') {
            return ['select' => 'RADIOLOGIA', 'otra' => ''];
        }
        foreach (medidata_staff_doctor_especialidades() as $esp) {
            if (strtoupper($esp) === $nomespUpper) {
                return ['select' => $esp, 'otra' => ''];
            }
        }
        return [
            'select' => $nomespUpper !== '' ? 'OTRA' : '',
            'otra' => $nomespRaw,
        ];
    }
}
