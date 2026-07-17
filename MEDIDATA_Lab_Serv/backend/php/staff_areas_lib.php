<?php
/**
 * Definición central de tipos de personal (tablas staff / doctor / nurse).
 * Fuente única para formularios, validaciones y listas RRHH.
 */

if (!function_exists('medidata_staff_areas_definitions')) {
    function medidata_staff_areas_definitions(): array
    {
        return [
            'nurse' => [
                'table' => 'nurse',
                'label' => 'Enfermería',
                'context' => 'colaboradores',
                'show_in_colaborador_select' => true,
                'pk' => 'idnur',
                'numide' => 'numide',
                'nombres' => 'nomnur',
                'apellidos' => 'apenur',
                'nacimiento' => 'nacinur',
                'genero' => 'sexnur',
                'link_label' => 'enfermería',
            ],
            'staff_administrative' => [
                'table' => 'staff_administrative',
                'label' => 'Administrativo',
                'context' => 'colaboradores',
                'show_in_colaborador_select' => true,
                'pk' => 'idadm',
                'numide' => 'numide',
                'nombres' => 'nomadm',
                'apellidos' => 'apeadm',
                'nacimiento' => 'nacadm',
                'genero' => 'sexadm',
                'link_label' => 'administrativo',
            ],
            'staff_general_services' => [
                'table' => 'staff_general_services',
                'label' => 'Servicios Generales',
                'context' => 'colaboradores',
                'show_in_colaborador_select' => true,
                'pk' => 'idsg',
                'numide' => 'numide',
                'nombres' => 'nomsg',
                'apellidos' => 'apesg',
                'nacimiento' => 'nacsg',
                'genero' => 'sexsg',
                'link_label' => 'servicios generales',
            ],
            'doctor' => [
                'table' => 'doctor',
                'label' => 'Médico',
                'context' => 'medicos',
                'show_in_colaborador_select' => false,
                'pk' => 'idodc',
                'numide' => 'ceddoc',
                'nombres' => 'nodoc',
                'apellidos' => 'apdoc',
                'nacimiento' => 'nacd',
                'genero' => 'sexd',
                'link_label' => 'médico',
            ],
            'staff_medifarma' => [
                'table' => 'staff_medifarma',
                'label' => 'Medifarma',
                'context' => 'medifarma',
                'show_in_colaborador_select' => false,
                'pk' => 'idmf',
                'numide' => 'numide',
                'nombres' => 'nommf',
                'apellidos' => 'apemf',
                'nacimiento' => 'nacmf',
                'genero' => 'sexmf',
                'link_label' => 'medifarma',
            ],
        ];
    }
}

if (!function_exists('medidata_staff_area_keys')) {
    function medidata_staff_area_keys(): array
    {
        return array_keys(medidata_staff_areas_definitions());
    }
}

if (!function_exists('medidata_staff_area_is_valid')) {
    function medidata_staff_area_is_valid(?string $table): bool
    {
        return is_string($table) && isset(medidata_staff_areas_definitions()[$table]);
    }
}

if (!function_exists('medidata_staff_area_def')) {
    function medidata_staff_area_def(string $table): ?array
    {
        return medidata_staff_areas_definitions()[$table] ?? null;
    }
}

if (!function_exists('medidata_staff_area_label')) {
    function medidata_staff_area_label(string $table): string
    {
        return medidata_staff_area_def($table)['label'] ?? $table;
    }
}

if (!function_exists('medidata_staff_area_context')) {
    function medidata_staff_area_context(string $table): string
    {
        return medidata_staff_area_def($table)['context'] ?? 'colaboradores';
    }
}

if (!function_exists('medidata_staff_areas_for_colaborador_select')) {
    /** @return array<string, string> value => label */
    function medidata_staff_areas_for_colaborador_select(): array
    {
        $out = [];
        foreach (medidata_staff_areas_definitions() as $key => $def) {
            if (!empty($def['show_in_colaborador_select'])) {
                $out[$key] = $def['label'];
            }
        }
        return $out;
    }
}

if (!function_exists('medidata_staff_area_columns')) {
    /**
     * Columnas de una tabla de personal.
     * @return array{pk:string,numide:string,nombres:string,apellidos:string,nacimiento:string,genero:string,label:string,context:string}|null
     */
    function medidata_staff_area_columns(string $table): ?array
    {
        $def = medidata_staff_area_def($table);
        if (!$def) {
            return null;
        }
        return [
            'pk' => $def['pk'],
            'numide' => $def['numide'],
            'nombres' => $def['nombres'],
            'apellidos' => $def['apellidos'],
            'nacimiento' => $def['nacimiento'],
            'genero' => $def['genero'],
            'label' => $def['label'],
            'context' => $def['context'],
        ];
    }
}

if (!function_exists('medidata_staff_lista_contextos')) {
    function medidata_staff_lista_contextos(): array
    {
        return ['colaboradores', 'medicos', 'medifarma'];
    }
}

if (!function_exists('medidata_staff_return_page_for_context')) {
    function medidata_staff_return_page_for_context(string $context, bool $isAdmin = true): string
    {
        $suffix = $isAdmin ? '' : '_usr';
        if ($context === 'medicos') {
            return 'lista_colaboradores_medicos' . $suffix . '.php';
        }
        if ($context === 'medifarma') {
            return 'lista_colaboradores_medifarma' . $suffix . '.php';
        }
        return 'lista_colaboradores' . $suffix . '.php';
    }
}

if (!function_exists('medidata_staff_agregar_page_for_context')) {
    function medidata_staff_agregar_page_for_context(string $context, bool $isAdmin = true): string
    {
        $base = $isAdmin ? 'agregar_colaborador.php' : 'agregar_colaborador_usr.php';
        if ($context === 'colaboradores') {
            return $base;
        }
        return $base . '?contexto=' . rawurlencode($context);
    }
}
