<?php
/**
 * Vincula doctor (idodc) ↔ users (rol Radiologo).
 * - Matching tolerante a nombres parciales (ej. ROXANA AGUILAR vs ROXANA MARGARITA AGUILAR ESCOBAR)
 * - Creación con username/email acotados a límites reales de la tabla users
 */
if (!isset($connect) || !($connect instanceof PDO)) {
    require_once __DIR__ . '/../../backend/bd/Conexion.php';
}

if (!function_exists('medidata_normalize_person_name')) {
    function medidata_normalize_person_name(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($name, 'UTF-8');
        }
        return strtoupper($name);
    }
}

if (!function_exists('medidata_name_tokens')) {
    /**
     * @return list<string>
     */
    function medidata_name_tokens(string $name): array
    {
        $norm = medidata_normalize_person_name($name);
        if ($norm === '') {
            return [];
        }
        $parts = preg_split('/\s+/u', $norm) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim((string) $p);
            if ($p === '') {
                continue;
            }
            // Ignorar partículas muy cortas
            $len = function_exists('mb_strlen') ? mb_strlen($p, 'UTF-8') : strlen($p);
            if ($len < 2) {
                continue;
            }
            $out[] = $p;
        }
        return $out;
    }
}

if (!function_exists('medidata_names_compatible')) {
    /**
     * True si todos los tokens del nombre más corto están en el más largo
     * (mín. 2 tokens, o 1 si coinciden exactamente).
     */
    function medidata_names_compatible(string $a, string $b): bool
    {
        $ta = medidata_name_tokens($a);
        $tb = medidata_name_tokens($b);
        if ($ta === [] || $tb === []) {
            return false;
        }
        if (medidata_normalize_person_name($a) === medidata_normalize_person_name($b)) {
            return true;
        }
        $short = count($ta) <= count($tb) ? $ta : $tb;
        $long = count($ta) <= count($tb) ? $tb : $ta;
        if (count($short) < 2) {
            return false;
        }
        foreach ($short as $tok) {
            if (!in_array($tok, $long, true)) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('medidata_find_radiologist_user')) {
    /**
     * @return array{id:int,name:string}|null
     */
    function medidata_find_radiologist_user(PDO $connect, string $doctorFullName): ?array
    {
        $full = trim($doctorFullName);
        if ($full === '') {
            return null;
        }

        // 1) Exacto
        $stmt = $connect->prepare("SELECT id, name FROM users WHERE name = ? AND rol = 'Radiologo' LIMIT 1");
        $stmt->execute([$full]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return ['id' => (int) $user['id'], 'name' => (string) $user['name']];
        }

        // 2) Todos los radiólogos y matching por tokens (cubre ROXANA AGUILAR ↔ nombre largo)
        $stmt = $connect->query("SELECT id, name FROM users WHERE rol = 'Radiologo' ORDER BY id ASC");
        $candidates = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $best = null;
        $bestScore = 0;
        $docTokens = medidata_name_tokens($full);

        foreach ($candidates as $cand) {
            $uname = (string) ($cand['name'] ?? '');
            if (!medidata_names_compatible($full, $uname)) {
                continue;
            }
            $uTokens = medidata_name_tokens($uname);
            $score = count(array_intersect($docTokens, $uTokens));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $cand;
            }
        }

        if ($best) {
            error_log('Radiólogo vinculado por nombre compatible: doctor=[' . $full . '] user=[' . $best['name'] . '] id=' . $best['id']);
            return ['id' => (int) $best['id'], 'name' => (string) $best['name']];
        }

        // 3) LIKE por primer nombre + primer apellido
        if (count($docTokens) >= 2) {
            $first = $docTokens[0];
            $last = $docTokens[count($docTokens) - 1];
            $stmt = $connect->prepare(
                "SELECT id, name FROM users
                 WHERE rol = 'Radiologo'
                   AND UPPER(name) LIKE ?
                   AND UPPER(name) LIKE ?
                 ORDER BY CHAR_LENGTH(name) ASC
                 LIMIT 5"
            );
            $stmt->execute(['%' . $first . '%', '%' . $last . '%']);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                if (medidata_names_compatible($full, (string) $row['name'])) {
                    return ['id' => (int) $row['id'], 'name' => (string) $row['name']];
                }
            }
            // No forzar el primer LIKE: evita cruce (ej. MARCELA vs MARIELA).
        }

        return null;
    }
}

/**
 * @return array<string, mixed>|null
 */
function validateAndGetUserIds($doctor_id)
{
    global $connect;

    try {
        $stmt = $connect->prepare('SELECT idodc, nodoc, apdoc, nomesp FROM doctor WHERE idodc = ?');
        $stmt->execute([(int) $doctor_id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) {
            return null;
        }

        $full_name = trim(($doctor['nodoc'] ?? '') . ' ' . ($doctor['apdoc'] ?? ''));
        $user = medidata_find_radiologist_user($connect, $full_name);

        return [
            'doctor_id' => (int) $doctor['idodc'],
            'user_id' => $user ? (int) $user['id'] : null,
            'doctor_name' => $full_name,
            'specialty' => $doctor['nomesp'] ?? '',
            'found_user' => (bool) $user,
            'user_name_found' => $user ? $user['name'] : null,
        ];
    } catch (Exception $e) {
        error_log('Error en validateAndGetUserIds: ' . $e->getMessage());
        return null;
    }
}

/**
 * @return int|null user_id
 */
function createUserIfNotExists($doctor_id)
{
    global $connect;

    try {
        $stmt = $connect->prepare('SELECT idodc, nodoc, apdoc, nomesp FROM doctor WHERE idodc = ?');
        $stmt->execute([(int) $doctor_id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) {
            return null;
        }

        $full_name = trim(($doctor['nodoc'] ?? '') . ' ' . ($doctor['apdoc'] ?? ''));
        if ($full_name === '') {
            return null;
        }

        // Reintento matching (por si se creó entre medias)
        $found = medidata_find_radiologist_user($connect, $full_name);
        if ($found) {
            return (int) $found['id'];
        }

        // Límites reales: username varchar(25), name varchar(50), email varchar(35)
        $nameStore = function_exists('mb_substr')
            ? mb_substr($full_name, 0, 50, 'UTF-8')
            : substr($full_name, 0, 50);

        $username = 'rad' . (int) $doctor_id; // ej. rad119
        if (strlen($username) > 25) {
            $username = substr($username, 0, 25);
        }
        $email = $username . '@medicasa.hn';
        if (strlen($email) > 35) {
            $email = substr($username, 0, max(1, 35 - strlen('@medicasa.hn'))) . '@medicasa.hn';
        }

        // Colisiones username/email
        $stmt = $connect->prepare('SELECT id, name, rol FROM users WHERE username = ? OR email = ? OR name = ? LIMIT 1');
        $stmt->execute([$username, $email, $nameStore]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            // Si ya es radiólogo, reutilizar; si no, usar otro username
            if (strcasecmp((string) ($existing['rol'] ?? ''), 'Radiologo') === 0) {
                return (int) $existing['id'];
            }
            $username = 'r' . (int) $doctor_id . '.' . substr(uniqid(), -4);
            if (strlen($username) > 25) {
                $username = substr($username, 0, 25);
            }
            $email = $username . '@medicasa.hn';
            if (strlen($email) > 35) {
                $email = 'r' . (int) $doctor_id . '@medicasa.hn';
            }
        }

        $password = password_hash('default123', PASSWORD_DEFAULT);
        $stmt = $connect->prepare(
            "INSERT INTO users (username, name, email, password, rol, state, created_at)
             VALUES (?, ?, ?, ?, 'Radiologo', '1', NOW())"
        );
        $stmt->execute([$username, $nameStore, $email, $password]);
        $newId = (int) $connect->lastInsertId();
        error_log('Usuario radiólogo creado automáticamente id=' . $newId . ' name=' . $nameStore . ' user=' . $username);
        return $newId > 0 ? $newId : null;
    } catch (Exception $e) {
        error_log('Error en createUserIfNotExists: ' . $e->getMessage());
        return null;
    }
}

// Debug directo
if (isset($_GET['doctor_id']) && basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === 'validate_radiologist_user.php') {
    header('Content-Type: application/json; charset=UTF-8');
    $doctor_id = (int) $_GET['doctor_id'];
    $result = validateAndGetUserIds($doctor_id);

    if ($result) {
        echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Doctor no encontrado'], JSON_UNESCAPED_UNICODE);
    }
}
