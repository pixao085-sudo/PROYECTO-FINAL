<?php
// Funciones compartidas. Requiere PHP 7.4+ y extensión mysqli.
// Mantener los datos de sesión el tiempo suficiente para los roles.
if (session_status() === PHP_SESSION_NONE) @ini_set('session.gc_maxlifetime', '3600');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function db(): mysqli {
    static $db = null;
    if ($db instanceof mysqli) return $db;
    require_once dirname(__DIR__) . '/Conexion.php';
    // Compatible con el nombre original $duro y el nuevo $conn.
    if (isset($conn) && $conn instanceof mysqli) $db = $conn;
    elseif (isset($duro) && $duro instanceof mysqli) $db = $duro;
    else throw new RuntimeException('Conexion.php no proporciona $conn ni $duro.');
    $db->set_charset('utf8mb4');
    return $db;
}
function utf_len(string $v): int { return function_exists('mb_strlen') ? mb_strlen($v, 'UTF-8') : strlen($v); }
function utf_cut(string $v, int $length): string { return function_exists('mb_substr') ? mb_substr($v, 0, $length, 'UTF-8') : substr($v, 0, $length); }
function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function role(): string {
    $r = strtolower(trim((string)($_SESSION['rol'] ?? '')));
    return $r === 'estudiante' ? 'aspirante' : $r;
}
// Límite de inactividad (no es un proceso en segundo plano):
// Aspirante: 15 min, Empresa: 25 min, Administrador: 30 min.
function session_limit(): int {
    $r=role();
    return $r==='empresa' ? 25*60 : ($r==='admin' ? 30*60 : 15*60);
}
function clear_login_session(): void {
    $_SESSION=[];
    if (ini_get('session.use_cookies')) {
        $p=session_get_cookie_params();
        setcookie(session_name(), '', time()-3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function authenticated(): bool {
    if (empty($_SESSION['id_usuario']) || empty($_SESSION['usuario']) || !in_array(role(),['admin','empresa','aspirante'],true)) return false;
    if (isset($_SESSION['ultima_actividad']) && time()-(int)$_SESSION['ultima_actividad']>session_limit()) {
        clear_login_session();
        return false;
    }
    return true;
}
function auth_required(): void {
    if (!authenticated()) { header('Location: login.php?sesion=expirada'); exit; }
    $_SESSION['ultima_actividad']=time();
}
function session_remaining(): int {
    return max(0, session_limit()-(time()-(int)($_SESSION['ultima_actividad'] ?? time())));
}

function roles_required(array $allowed): void {
    auth_required();
    if (!in_array(role(), $allowed, true)) {
        http_response_code(403);
        ui_simple_error('Acceso no autorizado', 'Tu cuenta no dispone de permisos para abrir esta sección.');
        exit;
    }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        ui_simple_error('Solicitud no válida', 'Actualiza la página e inténtalo nuevamente.');
        exit;
    }
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function flash_set(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function flash_html(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash']; unset($_SESSION['flash']);
    return '<div class="alert ' . (($f[0] ?? '') === 'ok' ? 'alert-ok' : 'alert-error') . '" role="status">' . e($f[1] ?? '') . '</div>';
}
function go(string $url): void { header('Location: ' . $url, true, 303); exit; }
function user_id(): int { return (int)($_SESSION['id_usuario'] ?? 0); }
function linked_id(string $type): int {
    $field = $type === 'empresa' ? 'id_empresa' : 'id_aspirante';
    $st = db()->prepare('SELECT ' . $field . ' FROM Usuarios WHERE ID=? LIMIT 1');
    $uid = user_id(); $st->bind_param('i', $uid); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    return (int)($row[$field] ?? 0);
}
function query_all(string $sql): array { return db()->query($sql)->fetch_all(MYSQLI_ASSOC); }
function run_sql(string $sql, string $types = '', array $values = []): mysqli_stmt {
    $st = db()->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$values);
    $st->execute();
    return $st;
}
function ui_simple_error(string $title, string $msg): void {
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/inac.css?v=4"><title>' . e($title) . '</title></head><body><main class="shell"><section class="surface" style="margin-top:10vh;padding:32px"><h1>' . e($title) . '</h1><p>' . e($msg) . '</p><a class="btn btn-secondary" href="dashboard.php">Volver al panel</a><footer class="page-footer">© ' . e(date('Y')) . ' Instituto Nacional Albert Camus. Todos los derechos reservados.</footer></section></main></body></html>';
}
function ui_head(string $title, string $subtitle, string $icon='bi-grid'): void {
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . ' | Empleabilidad INAC</title>';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"><link rel="stylesheet" href="assets/inac.css?v=4"></head><body>';
    echo '<div class="shell"><nav class="module-topbar" aria-label="Navegación"><div class="brand-inline"><span class="brand-mark"><i class="bi bi-briefcase-fill"></i></span><span>Empleabilidad <b>INAC</b><small>Bolsa de trabajo</small></span></div><a class="back-link" href="dashboard.php"><i class="bi bi-arrow-left-circle" aria-hidden="true"></i> <span>Regresar al panel principal</span></a></nav>';
    echo '<header class="page-banner"><div class="banner-icon"><i class="bi ' . e($icon) . '"></i></div><div><div class="eyebrow">EMPLEABILIDAD INAC / MÓDULOS</div><h1>' . e($title) . '</h1><p>' . e($subtitle) . '</p></div></header>';
    echo flash_html();
}
function ui_foot(): void { echo '<footer class="page-footer">© ' . e(date('Y')) . ' Instituto Nacional Albert Camus. Todos los derechos reservados.<span class="footer-subline">Empleabilidad INAC · Bolsa de trabajo y formación</span></footer></div></body></html>'; }
function fail_message(Throwable $ex): string {
    error_log('[Empleabilidad INAC] ' . $ex->getMessage());
    return 'No se pudo completar la operación. Comprueba los datos y la estructura de la base de datos.';
}
