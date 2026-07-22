<?php
/**
 * Campos del formulario de solicitud de empleo (Hospital MEDICASA).
 */

if (!function_exists('medidata_rrhh_employee_form_field_keys')) {
    /** @return list<string> */
    function medidata_rrhh_employee_form_field_keys(): array
    {
        return [
            'fullname', 'address_street', 'colony', 'city', 'phone_home', 'phone_cell',
            'birthdate', 'age', 'birth_place', 'nationality', 'gender', 'height',
            'email', 'religion', 'id_number', 'ihss_number', 'rtn', 'license_number', 'vehicle_type',
            'marital_status', 'marriage_date', 'lives_with', 'dependents_count', 'dependents_dependency',
            'father_name', 'father_address', 'father_work', 'father_phone',
            'mother_name', 'mother_address', 'mother_work', 'mother_phone',
            'spouse_name', 'spouse_address', 'spouse_work', 'spouse_phone',
            'children_siblings_info',
            'edu_secondary_inst', 'edu_secondary_title', 'edu_secondary_from', 'edu_secondary_to', 'edu_secondary_diploma',
            'edu_bachillerato_inst', 'edu_bachillerato_title', 'edu_bachillerato_from', 'edu_bachillerato_to', 'edu_bachillerato_diploma',
            'edu_university_inst', 'edu_university_title', 'edu_university_from', 'edu_university_to', 'edu_university_diploma',
            'edu_maestria_inst', 'edu_maestria_title', 'edu_maestria_from', 'edu_maestria_to', 'edu_maestria_diploma',
            'edu_otros_inst', 'edu_otros_title', 'edu_otros_from', 'edu_otros_to', 'edu_otros_diploma',
            'studying_now', 'current_studies', 'current_studies_inst', 'current_studies_schedule',
            'lang_english_speak', 'lang_english_read', 'lang_english_write', 'lang_english_years',
            'lang_other_name', 'lang_other_speak', 'lang_other_read', 'lang_other_write', 'lang_other_years',
            'job1_company', 'job1_address', 'job1_phone', 'job1_start', 'job1_end', 'job1_salary_start', 'job1_salary_end',
            'job1_role', 'job1_people', 'job1_boss', 'job1_leave_reason', 'job1_current',
            'job2_company', 'job2_address', 'job2_phone', 'job2_start', 'job2_end', 'job2_salary_start', 'job2_salary_end',
            'job2_role', 'job2_people', 'job2_boss', 'job2_leave_reason', 'job2_current',
            'job3_company', 'job3_address', 'job3_phone', 'job3_start', 'job3_end', 'job3_salary_start', 'job3_salary_end',
            'job3_role', 'job3_people', 'job3_boss', 'job3_leave_reason', 'job3_current',
            'can_contact_last_job', 'no_contact_reason',
            'ref1_name', 'ref1_occupation', 'ref1_workplace', 'ref1_address', 'ref1_phone', 'ref1_known_since',
            'ref2_name', 'ref2_occupation', 'ref2_workplace', 'ref2_address', 'ref2_phone', 'ref2_known_since',
            'ref3_name', 'ref3_occupation', 'ref3_workplace', 'ref3_address', 'ref3_phone', 'ref3_known_since',
            'position_applied', 'min_salary_expected', 'other_areas_interest',
            'how_found_job', 'job_search_websites', 'relatives_at_medicasa', 'relatives_names',
            'associations', 'has_illness', 'illness_detail', 'takes_medication', 'medication_detail',
            'hobbies', 'has_car', 'car_brand_model', 'car_year', 'available_start_date',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'spouse_works', 'spouse_salary', 'spouse_workplace',
            'owns_home', 'home_value', 'pays_rent', 'rent_amount', 'other_income', 'other_income_amount',
            'declaration_city_date', 'observations',
            'bank_name', 'bank_account', 'dependents_names',
            'direction', 'disclaimer_agreed',
        ];
    }
}

if (!function_exists('medidata_rrhh_employee_form_collect')) {
    /** @param array<string, mixed> $source @return array<string, string> */
    function medidata_rrhh_employee_form_collect(array $source): array
    {
        $fields = [];
        foreach (medidata_rrhh_employee_form_field_keys() as $key) {
            if (array_key_exists($key, $source)) {
                $fields[$key] = trim((string) $source[$key]);
            }
        }
        $dir = $fields['direction'] ?? '';
        $street = $fields['address_street'] ?? '';
        $colony = $fields['colony'] ?? '';
        $city = $fields['city'] ?? '';
        if ($dir === '' && ($street !== '' || $colony !== '' || $city !== '')) {
            $fields['direction'] = trim(
                $street
                . ($colony !== '' ? ', ' . $colony : '')
                . ($city !== '' ? ', ' . $city : '')
            );
        }
        return $fields;
    }
}

if (!function_exists('medidata_rrhh_employee_form_prefill')) {
    /**
     * @param array<string, mixed> $ctx from medidata_rrhh_employee_form_by_token
     * @return array<string, string>
     */
    function medidata_rrhh_employee_form_prefill(array $ctx): array
    {
        $payload = json_decode((string) ($ctx['payload'] ?? '{}'), true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $base = [
            'fullname' => (string) ($ctx['fullname'] ?? ''),
            'email' => (string) ($ctx['email'] ?? ''),
            'phone_cell' => (string) ($ctx['phonenumber'] ?? ''),
            'id_number' => (string) ($ctx['dni'] ?? ''),
            'birthdate' => (string) ($ctx['birthdate'] ?? ''),
            'marital_status' => (string) ($ctx['marital_status'] ?? ''),
            'direction' => (string) ($ctx['direction'] ?? ''),
        ];
        if ($base['direction'] === 'Pendiente de completar') {
            $base['direction'] = '';
        }
        $merged = array_merge($base, $payload);
        foreach ($merged as $k => $v) {
            $merged[$k] = is_scalar($v) ? trim((string) $v) : '';
        }
        return $merged;
    }
}

if (!function_exists('medidata_rrhh_employee_form_sync_candidate')) {
    function medidata_rrhh_employee_form_sync_candidate(PDO $pdo, int $candidateId, array $fields): void
    {
        $map = [];
        if (!empty($fields['birthdate'])) {
            $map['birthdate'] = $fields['birthdate'];
        }
        if (!empty($fields['marital_status'])) {
            $map['marital_status'] = $fields['marital_status'];
        }
        if (!empty($fields['direction'])) {
            $map['direction'] = $fields['direction'];
        }
        if (!empty($fields['email'])) {
            $map['email'] = $fields['email'];
        }
        if (!empty($fields['phone_cell'])) {
            $map['phonenumber'] = $fields['phone_cell'];
        } elseif (!empty($fields['phone_home'])) {
            $map['phonenumber'] = $fields['phone_home'];
        }
        if (!empty($fields['id_number'])) {
            $map['dni'] = $fields['id_number'];
        }
        if (!empty($fields['fullname'])) {
            $map['fullname'] = $fields['fullname'];
        }
        if (!empty($fields['position_applied'])) {
            $map['profession'] = $fields['position_applied'];
        }
        if (!empty($fields['min_salary_expected'])) {
            $map['salary_expectation'] = $fields['min_salary_expected'];
        }
        $eduParts = array_filter([
            $fields['edu_university_title'] ?? '',
            $fields['edu_bachillerato_title'] ?? '',
            $fields['edu_secondary_title'] ?? '',
        ]);
        if ($eduParts !== []) {
            $map['academic_level'] = implode(' / ', $eduParts);
        }
        $jobs = array_filter([
            trim(($fields['job1_company'] ?? '') . ' — ' . ($fields['job1_role'] ?? '')),
            trim(($fields['job2_company'] ?? '') . ' — ' . ($fields['job2_role'] ?? '')),
            trim(($fields['job3_company'] ?? '') . ' — ' . ($fields['job3_role'] ?? '')),
        ], static fn ($j) => trim(str_replace('—', '', $j)) !== '');
        if ($jobs !== []) {
            $map['previous_experience'] = implode("\n", $jobs);
        }
        if (!empty($fields['available_start_date'])) {
            $map['schedule_availability'] = $fields['available_start_date'];
        }
        if ($map === []) {
            return;
        }
        $sql = 'UPDATE candidates SET updated_at = NOW()';
        $params = [];
        foreach ($map as $col => $val) {
            $sql .= ', `' . $col . '` = ?';
            $params[] = $val;
        }
        $sql .= ' WHERE id = ?';
        $params[] = $candidateId;
        $pdo->prepare($sql)->execute($params);
    }
}
