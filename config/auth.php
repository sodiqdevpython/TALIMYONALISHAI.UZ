<?php
require_once __DIR__.'/session.php';
require_once __DIR__.'/database.php';

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user !== null) return $user;
    $stmt = db()->prepare("SELECT u.*, r.role_key, r.role_name, s.school_name, s.school_code
                           FROM users u
                           JOIN roles r ON r.id=u.role_id
                           LEFT JOIN schools s ON s.id=u.school_id
                           WHERE u.id=? AND u.status='active' LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function auth_web_prefix(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos = strpos($script, '/web/');
    if ($pos === false) return '';
    $after = substr($script, $pos + 5);
    $dir = trim(dirname($after), '.');
    if ($dir === '' || $dir === '/') return '';
    $depth = substr_count(trim($dir, '/'), '/') + 1;
    return str_repeat('../', $depth);
}

function auth_url(string $path): string {
    return auth_web_prefix().ltrim($path, '/');
}

function login_user(string $username, string $password): array {
    $stmt = db()->prepare("SELECT u.*, r.role_key, r.role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.username=? LIMIT 1");
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    $ok = false;
    $message = 'Login yoki parol noto‘g‘ri.';

    if ($u && $u['status'] === 'active') {
        $hash = (string)$u['password_hash'];
        if (password_verify($password, $hash)) {
            $ok = true;
        } elseif (str_starts_with($hash, '$2y$10$change_this_hash_after_install')) {
            $demoPasswords = [
                'admin' => 'admin123',
                'director_demo' => 'director123',
                'vice_demo' => 'vice123',
                'teacher_demo' => 'teacher123',
            ];
            if (($demoPasswords[$username] ?? null) === $password) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$newHash, $u['id']]);
                $ok = true;
            }
        }
        if (!$ok && $u['status'] !== 'active') $message = 'Foydalanuvchi bloklangan yoki faol emas.';
    }

    try {
        $log = db()->prepare('INSERT INTO login_logs(user_id, username, role_id, ip_address, user_agent, success, message) VALUES(?,?,?,?,?,?,?)');
        $log->execute([$u['id'] ?? null, $username, $u['role_id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $ok ? 1 : 0, $ok ? 'ok' : $message]);
    } catch (Throwable $e) {}

    if (!$ok) return ['ok'=>false, 'message'=>$message];

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['role_key'] = $u['role_key'];
    $_SESSION['school_id'] = $u['school_id'];
    $_SESSION['last_seen'] = time();
    db()->prepare('UPDATE users SET last_login=NOW() WHERE id=?')->execute([$u['id']]);
    return ['ok'=>true, 'message'=>'ok'];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        header('Location: '.auth_url('login.php'));
        exit;
    }
    if (!empty($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 7200) {
        logout_user();
        header('Location: '.auth_url('login.php?timeout=1'));
        exit;
    }
    $_SESSION['last_seen'] = time();
    return $u;
}

function require_role(array $roles): array {
    $u = require_login();
    if (!in_array($u['role_key'], $roles, true)) {
        http_response_code(403);
        die('Ruxsat yo‘q. Sizda bu sahifani ko‘rish huquqi mavjud emas.');
    }
    return $u;
}

function role_home(string $role): string {
    return match($role) {
        'super_admin' => 'admin/index.php',
        'director' => 'director/index.php',
        'vice_director' => 'vice/index.php',
        'teacher' => 'teacher/index.php',
        'student' => 'student/index.php',
        default => 'dashboard.php'
    };
}

function auth_h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
