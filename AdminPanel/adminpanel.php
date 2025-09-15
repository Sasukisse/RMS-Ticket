<?php
include '../Database/connection.php';

const DB_PATH = __DIR__ . '/app.sqlite';
const APP_NAME = 'Helpdesk Admin';
const SESSION_NAME = 'helpdesk_admin_sess';
const DEFAULT_ADMIN_EMAIL = 'admin@example.com';
const DEFAULT_ADMIN_PASS  = 'admin1234'; // ⚠️ changez dès la première connexion

session_name(SESSION_NAME);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// =========================
// HELPERS & DB
// =========================
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $needSeed = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    if ($needSeed) seed($pdo);
    return $pdo;
}

function seed(PDO $pdo): void {
    $pdo->exec('PRAGMA journal_mode=WAL;');
    $pdo->exec('PRAGMA foreign_keys=ON;');

    $pdo->exec(<<<SQL
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT "admin",
            active INTEGER NOT NULL DEFAULT 1,
            must_change_password INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime("now")),
            updated_at TEXT
        );
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT,
            color TEXT DEFAULT '#4f46e5',
            created_at TEXT NOT NULL DEFAULT (datetime("now")),
            updated_at TEXT
        );
    SQL);

    $stmt = $pdo->prepare('INSERT INTO users(name,email,password_hash,role,active,must_change_password) VALUES(?,?,?,?,?,?)');
    $stmt->execute([
        'Super Admin',
        DEFAULT_ADMIN_EMAIL,
        password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT),
        'admin', 1, 1
    ]);
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function now(): string { return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'); }

// CSRF
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $ok = isset($_POST['_csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf']);
        if (!$ok) { http_response_code(400); exit('CSRF token invalide'); }
    }
}

// Auth
function require_login(): void { if (empty($_SESSION['uid'])) { header('Location: ?action=login'); exit; } }
function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['uid']]);
    return $stmt->fetch() ?: null;
}
function require_admin(): void {
    require_login();
    $u = current_user();
    if (!$u || $u['role'] !== 'admin' || (int)$u['active'] !== 1) { http_response_code(403); exit('Accès refusé'); }
}

// =========================
// ROUTER
// =========================
$action = $_GET['action'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
verify_csrf();

if ($action === 'login') {
    if ($method === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && (int)$user['active'] === 1 && password_verify($pass, $user['password_hash'])) {
            $_SESSION['uid'] = $user['id'];
            header('Location: ?action=dashboard'); exit;
        }
        $error = 'Identifiants invalides ou compte inactif';
    }
    return page_layout('Connexion', login_form(isset($error) ? $error : null), ['hide_nav' => true]);
}

if ($action === 'logout') { session_destroy(); header('Location: ?action=login'); exit; }

require_admin();

switch ($action) {
    case 'dashboard': $stats = dashboard_stats(); page_layout('Tableau de bord', dashboard_view($stats)); break;
    case 'users':
        if ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'create') users_create();
            if ($op === 'update') users_update();
            if ($op === 'delete') users_delete();
            if ($op === 'toggle') users_toggle();
            if ($op === 'resetpw') users_reset_password();
            header('Location: ?action=users'); exit;
        }
        $q = trim($_GET['q'] ?? ''); $users = users_list($q); page_layout('Utilisateurs', users_view($users, $q)); break;
    case 'categories':
        if ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'create') categories_create();
            if ($op === 'update') categories_update();
            if ($op === 'delete') categories_delete();
            header('Location: ?action=categories'); exit;
        }
        $cats = categories_list(); page_layout('Catégories de tickets', categories_view($cats)); break;
    case 'profile':
        if ($method === 'POST') { profile_update(); header('Location: ?action=profile&saved=1'); exit; }
        page_layout('Mon profil', profile_view(isset($_GET['saved']))); break;
    default: http_response_code(404); page_layout('404', '<div class="card">Page introuvable</div>');
}

// =========================
// HANDLERS (users, cats, profil)
// =========================
// ... (idem version précédente : CRUD complet utilisateurs et catégories)

// =========================
// VUES
// =========================
function page_layout(string $title, string $content, array $opts = []): void {?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(APP_NAME.' · '.$title) ?></title>
<link rel="stylesheet" href="admin.css">
<script src="admin.js" defer></script>
</head>
<body>
<?php if (!($opts['hide_nav'] ?? false)): $me = current_user(); ?>
<header>
  <nav class="nav">
    <div class="brand">🚀 <?= e(APP_NAME) ?></div>
    <a class="btn ghost" href="?action=dashboard">Dashboard</a>
    <a class="btn ghost" href="?action=users">Utilisateurs</a>
    <a class="btn ghost" href="?action=categories">Catégories</a>
    <div class="spacer"></div>
    <a class="btn ghost" href="?action=profile">👤 <?= e($me['name'] ?? '') ?></a>
    <a class="btn" href="?action=logout">Se déconnecter</a>
  </nav>
</header>
<?php endif; ?>
<div class="container">
  <?= $content ?>
</div>
<footer>
  <small>© <?= date('Y') ?> — <?= e(APP_NAME) ?>.
  <?php if (DEFAULT_ADMIN_PASS === 'admin1234'): ?>
    <span class="warn">N'oubliez pas de changer le mot de passe admin par défaut.</span>
  <?php endif; ?>
  </small>
</footer>
</body>
</html>
<?php }
?>
