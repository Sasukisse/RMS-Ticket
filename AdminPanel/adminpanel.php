<?php
session_start();
require_once '../Database/config.php';

// Vérifier si l'utilisateur est connecté et a les droits admin
if (!isset($_SESSION['user_id']) || $_SESSION['droit'] < 1) {
    header('Location: ../Login/login.php');
    exit();
}

// Configuration
const APP_NAME = 'RMS Ticket Admin';
const SESSION_NAME = 'rms_admin_sess';

// Headers de sécurité
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// =========================
// HELPERS & DB
// =========================
function getConnection() {
    static $mysqli = null;
    if ($mysqli === null) {
        try {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $mysqli->set_charset(DB_CHARSET);
            
            if ($mysqli->connect_error) {
                throw new Exception('Erreur de connexion MySQL: ' . $mysqli->connect_error);
            }
        } catch (Exception $e) {
            http_response_code(500);
            die('Erreur de connexion à la base de données.');
        }
    }
    return $mysqli;
}

function e(string $s): string { 
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); 
}

function now(): string { 
    return (new DateTime('now', new DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s'); 
}

// CSRF Protection
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $ok = isset($_POST['_csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf']);
        if (!$ok) { 
            http_response_code(400); 
            exit('CSRF token invalide'); 
        }
    }
}

// Fonctions d'authentification et d'autorisation
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    
    $conn = getConnection();
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}

function require_admin(): void {
    $user = current_user();
    if (!$user || $user['droit'] < 1) {
        http_response_code(403);
        exit('Accès refusé - Droits administrateur requis');
    }
}

function require_super_admin(): void {
    $user = current_user();
    if (!$user || $user['droit'] < 2) {
        http_response_code(403);
        exit('Accès refusé - Droits super administrateur requis');
    }
}

// Fonctions de logging
function log_admin_action($action, $details = '', $user_id = null) {
    $conn = getConnection();
    $user_id = $user_id ?? $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    // Vérifier si la table admin_logs possède des colonnes optionnelles (ex: entity_type, entity_id)
    $has_entity_type = false;
    $has_entity_id = false;
    $res = $conn->query("SHOW COLUMNS FROM admin_logs LIKE 'entity_type'");
    if ($res && $res->num_rows > 0) $has_entity_type = true;
    $res = $conn->query("SHOW COLUMNS FROM admin_logs LIKE 'entity_id'");
    if ($res && $res->num_rows > 0) $has_entity_id = true;

    // Construire la requête et les paramètres dynamiquement
    $columns = ['user_id', 'action', 'details', 'ip_address', 'user_agent'];
    $placeholders = ['?', '?', '?', '?', '?'];
    $types = 'issss';
    $params = [$user_id, $action, $details, $ip_address, $user_agent];

    if ($has_entity_type) {
        // Si la colonne existe mais qu'aucune valeur n'est fournie, insérer une chaîne vide pour respecter NOT NULL
        $columns[] = 'entity_type';
        $placeholders[] = '?';
        $types .= 's';
        $params[] = '';
    }
    if ($has_entity_id) {
        $columns[] = 'entity_id';
        $placeholders[] = '?';
        $types .= 'i';
        $params[] = 0;
    }

    $columns[] = 'created_at';
    $placeholders[] = 'NOW()';

    $sql = 'INSERT INTO admin_logs (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Ne pas interrompre l'exécution; enregistrer l'erreur si possible
        error_log('log_admin_action prepare failed: ' . $conn->error);
        return;
    }

    // bind_param requires variables by reference
    $bind_names = [];
    $bind_names[] = & $types;
    foreach ($params as $i => $value) {
        $bind_names[] = & $params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    try {
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        // Si l'erreur concerne la colonne 'details', tenter un second essai en réduisant details à une chaîne vide
        error_log('log_admin_action execute failed: ' . $e->getMessage());
        $msg = $e->getMessage();
        if (stripos($msg, 'details') !== false) {
            // Trouver l'indice de 'details' dans $params (normalement à l'index 2)
            // Défensive: chercher la première valeur qui correspond au contenu original $details
            $found = false;
            foreach ($params as $k => $v) {
                if ($v === $details) {
                    $params[$k] = '';
                    $found = true;
                    break;
                }
            }
            if (!$found && isset($params[2])) {
                $params[2] = '';
            }

            // Rebind et retenter
            $bind_names = [];
            $bind_names[] = & $types;
            foreach ($params as $i => $value) {
                $bind_names[] = & $params[$i];
            }
            try {
                call_user_func_array([$stmt, 'bind_param'], $bind_names);
                $stmt->execute();
            } catch (Exception $e2) {
                error_log('log_admin_action second execute failed: ' . $e2->getMessage());
                // On abandonne silencieusement pour ne pas planter l'interface admin
                return;
            }
        } else {
            // Erreur non liée à details — consigner et ne pas remonter l'exception
            error_log('log_admin_action non-details error: ' . $e->getMessage());
            return;
        }
    }
}

// =========================
// ROUTER
// =========================
$action = $_GET['action'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
verify_csrf();

require_admin();

switch ($action) {
    case 'dashboard': 
        $stats = dashboard_stats(); 
        page_layout('Tableau de bord', dashboard_view($stats)); 
        break;
        
    case 'tickets':
        if ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'update_status') tickets_update_status();
            if ($op === 'assign') tickets_assign();
            if ($op === 'delete') tickets_delete();
            header('Location: ?action=tickets'); exit;
        }
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category' => $_GET['category'] ?? '',
            'search' => trim($_GET['search'] ?? '')
        ];
        $tickets = tickets_list($filters);
        page_layout('Gestion des tickets', tickets_view($tickets, $filters));
        break;
        
    case 'ticket_detail':
        $ticket_id = intval($_GET['id'] ?? 0);
        if ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'add_response') ticket_add_response($ticket_id);
            if ($op === 'update_status') ticket_update_status_direct($ticket_id);
            header("Location: ?action=ticket_detail&id=$ticket_id"); exit;
        }
        $ticket = ticket_get_detail($ticket_id);
        if (!$ticket) {
            header('Location: ?action=tickets'); exit;
        }
        $responses = ticket_get_responses($ticket_id);
        page_layout('Détail du ticket #' . $ticket_id, ticket_detail_view($ticket, $responses));
        break;
        
    case 'users':
        if ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'create') users_create();
            if ($op === 'update') users_update();
            if ($op === 'delete') users_delete();
            if ($op === 'toggle_status') users_toggle_status();
            if ($op === 'reset_password') users_reset_password();
            header('Location: ?action=users'); exit;
        }
        $search = trim($_GET['search'] ?? '');
        $users = users_list($search);
        page_layout('Gestion des comptes', users_view($users, $search));
        break;
        
    case 'permissions':
        require_super_admin();
        if ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'update_role') permissions_update_role();
            if ($op === 'create_role') permissions_create_role();
            if ($op === 'delete_role') permissions_delete_role();
            header('Location: ?action=permissions'); exit;
        }
        $roles = permissions_list();
        page_layout('Gestion des permissions', permissions_view($roles));
        break;
        
    case 'logs':
        $filters = [
            'action' => $_GET['action_filter'] ?? '',
            'user' => $_GET['user'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        $logs = logs_list($filters);
        page_layout('Logs système', logs_view($logs, $filters));
        break;
        
    case 'settings':
        require_super_admin();
        if ($method === 'POST') {
            settings_update();
            header('Location: ?action=settings&saved=1'); exit;
        }
        page_layout('Paramètres système', settings_view(isset($_GET['saved'])));
        break;
        
    case 'logout': 
        log_admin_action('logout', 'Déconnexion du panneau admin');
        session_destroy(); 
        header('Location: ../HomePage/index.php'); 
        exit;
        
    default: 
        http_response_code(404); 
        page_layout('404', '<div class="card"><h2>Page introuvable</h2><p>La page demandée n\'existe pas.</p></div>');
}

// =========================
// HANDLERS - DASHBOARD
// =========================
function dashboard_stats(): array {
    $conn = getConnection();
    
    // Stats des tickets
    $tickets_total = $conn->query("SELECT COUNT(*) as count FROM tickets")->fetch_assoc()['count'];
    $tickets_open = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE status IN ('open', 'in_progress')")->fetch_assoc()['count'];
    $tickets_urgent = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE priority = 'urgent'")->fetch_assoc()['count'];
    
    // Stats des utilisateurs
    $users_total = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $users_active = $conn->query("SELECT COUNT(*) as count FROM users WHERE droit >= 0")->fetch_assoc()['count'];
    
    // Tickets récents
    $recent_tickets = $conn->query("
        SELECT t.*, u.nom, u.prenom 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC 
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
    
    return [
        'tickets_total' => $tickets_total,
        'tickets_open' => $tickets_open,
        'tickets_urgent' => $tickets_urgent,
        'users_total' => $users_total,
        'users_active' => $users_active,
        'recent_tickets' => $recent_tickets
    ];
}

// =========================
// HANDLERS - TICKETS
// =========================
function tickets_list($filters): array {
    $conn = getConnection();
    
    $where = ['1=1'];
    $params = [];
    $types = '';
    
    if (!empty($filters['status'])) {
        $where[] = 't.status = ?';
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['priority'])) {
        $where[] = 't.priority = ?';
        $params[] = $filters['priority'];
        $types .= 's';
    }
    
    if (!empty($filters['category'])) {
        $where[] = 't.category = ?';
        $params[] = $filters['category'];
        $types .= 's';
    }
    
    if (!empty($filters['search'])) {
        $where[] = '(t.title LIKE ? OR t.description LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)';
        $search_term = '%' . $filters['search'] . '%';
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
        $types .= 'ssss';
    }
    
    $sql = "
        SELECT t.*, u.nom, u.prenom, u.email,
               CASE 
                   WHEN t.priority = 'urgent' THEN 4
                   WHEN t.priority = 'high' THEN 3
                   WHEN t.priority = 'medium' THEN 2
                   ELSE 1
               END as priority_order
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE " . implode(' AND ', $where) . "
        ORDER BY priority_order DESC, t.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function tickets_update_status(): void {
    $conn = getConnection();
    $ticket_id = $_POST['ticket_id'] ?? 0;
    $new_status = $_POST['status'] ?? '';
    
    $stmt = $conn->prepare('UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $new_status, $ticket_id);
    $stmt->execute();
    
    log_admin_action('ticket_status_update', "Ticket ID: $ticket_id, Nouveau statut: $new_status");
}

function tickets_delete(): void {
    require_super_admin();
    $conn = getConnection();
    $ticket_id = $_POST['ticket_id'] ?? 0;
    
    $stmt = $conn->prepare('DELETE FROM tickets WHERE id = ?');
    $stmt->bind_param('i', $ticket_id);
    $stmt->execute();
    
    log_admin_action('ticket_delete', "Ticket ID: $ticket_id supprimé");
}

function ticket_get_detail($ticket_id): ?array {
    $conn = getConnection();
    
    $stmt = $conn->prepare("
        SELECT t.*, u.nom, u.prenom, u.email, u.numero_telephone
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?
    ");
    $stmt->bind_param('i', $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc() ?: null;
}

function ticket_get_responses($ticket_id): array {
    $conn = getConnection();
    
    // Créer la table des réponses si elle n'existe pas (sans contraintes FK pour éviter les erreurs)
    $conn->query("
        CREATE TABLE IF NOT EXISTS ticket_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            response_text TEXT NOT NULL,
            is_admin_response BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket_id (ticket_id),
            INDEX idx_user_id (user_id)
        )
    ");
    
    // Vérifier si la table existe et a des données
    $result = $conn->query("SHOW TABLES LIKE 'ticket_responses'");
    if ($result->num_rows === 0) {
        return [];
    }
    
    $stmt = $conn->prepare("
        SELECT tr.*, u.nom, u.prenom, u.droit
        FROM ticket_responses tr
        LEFT JOIN users u ON tr.user_id = u.id
        WHERE tr.ticket_id = ?
        ORDER BY tr.created_at ASC
    ");
    $stmt->bind_param('i', $ticket_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function ticket_add_response($ticket_id): void {
    $conn = getConnection();
    $response_text = trim($_POST['response_text'] ?? '');
    
    if (empty($response_text)) {
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $is_admin = current_user()['droit'] >= 1;
    
    $stmt = $conn->prepare('INSERT INTO ticket_responses (ticket_id, user_id, response_text, is_admin_response) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iisi', $ticket_id, $user_id, $response_text, $is_admin);
    $stmt->execute();
    
    // Mettre à jour le statut du ticket si c'est une réponse admin
    if ($is_admin) {
        $stmt = $conn->prepare('UPDATE tickets SET status = "in_progress", updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
    }
    
    log_admin_action('ticket_response_add', "Réponse ajoutée au ticket ID: $ticket_id");
}

function ticket_update_status_direct($ticket_id): void {
    $conn = getConnection();
    $new_status = $_POST['status'] ?? '';
    
    $stmt = $conn->prepare('UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $new_status, $ticket_id);
    $stmt->execute();
    
    log_admin_action('ticket_status_update', "Ticket ID: $ticket_id, Nouveau statut: $new_status");
}

// =========================
// HANDLERS - USERS
// =========================
function users_list($search): array {
    $conn = getConnection();
    
    $where = '1=1';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where = '(nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR username LIKE ?)';
        $search_term = '%' . $search . '%';
        $params = [$search_term, $search_term, $search_term, $search_term];
        $types = 'ssss';
    }
    
    $sql = "SELECT * FROM users WHERE $where ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function users_create(): void {
    require_super_admin();
    $conn = getConnection();
    
    $username = trim($_POST['username'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $droit = intval($_POST['droit'] ?? 0);
    $telephone = trim($_POST['telephone'] ?? '');
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare('INSERT INTO users (username, nom, prenom, email, password_hash, numero_telephone, droit) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssssi', $username, $nom, $prenom, $email, $password_hash, $telephone, $droit);
    $stmt->execute();
    
    log_admin_action('user_create', "Utilisateur créé: $username ($email)");
}

function users_update(): void {
    $conn = getConnection();
    $user_id = $_POST['user_id'] ?? 0;
    $username = trim($_POST['username'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $droit = intval($_POST['droit'] ?? 0);
    $telephone = trim($_POST['telephone'] ?? '');
    
    // Vérifier les permissions pour modifier les droits
    $current_user = current_user();
    if ($droit != $_POST['original_droit'] && $current_user['droit'] < 2) {
        exit('Seuls les super administrateurs peuvent modifier les droits');
    }
    
    $stmt = $conn->prepare('UPDATE users SET username = ?, nom = ?, prenom = ?, email = ?, numero_telephone = ?, droit = ? WHERE id = ?');
    $stmt->bind_param('sssssii', $username, $nom, $prenom, $email, $telephone, $droit, $user_id);
    $stmt->execute();
    
    log_admin_action('user_update', "Utilisateur modifié: $username (ID: $user_id)");
}

function users_reset_password(): void {
    require_super_admin();
    $conn = getConnection();
    $user_id = $_POST['user_id'] ?? 0;
    $new_password = $_POST['new_password'] ?? '';
    
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->bind_param('si', $password_hash, $user_id);
    $stmt->execute();
    
    log_admin_action('user_password_reset', "Mot de passe réinitialisé pour l'utilisateur ID: $user_id");
}

function users_delete(): void {
    require_super_admin();
    $conn = getConnection();
    $user_id = $_POST['user_id'] ?? 0;
    
    // Ne pas supprimer son propre compte
    if ($user_id == $_SESSION['user_id']) {
        exit('Vous ne pouvez pas supprimer votre propre compte');
    }
    
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    
    log_admin_action('user_delete', "Utilisateur supprimé ID: $user_id");
}

// =========================
// HANDLERS - PERMISSIONS
// =========================
function permissions_list(): array {
    $conn = getConnection();
    return $conn->query("
        SELECT 
            droit,
            COUNT(*) as user_count,
            CASE 
                WHEN droit = 0 THEN 'Utilisateur standard'
                WHEN droit = 1 THEN 'Administrateur'
                WHEN droit = 2 THEN 'Super administrateur'
                ELSE 'Rôle personnalisé'
            END as role_name
        FROM users 
        GROUP BY droit 
        ORDER BY droit DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

function permissions_update_role(): void {
    require_super_admin();
    $conn = getConnection();
    $user_id = $_POST['user_id'] ?? 0;
    $new_role = intval($_POST['new_role'] ?? 0);
    
    // Ne pas modifier son propre rôle
    if ($user_id == $_SESSION['user_id']) {
        exit('Vous ne pouvez pas modifier votre propre rôle');
    }
    
    $stmt = $conn->prepare('UPDATE users SET droit = ? WHERE id = ?');
    $stmt->bind_param('ii', $new_role, $user_id);
    $stmt->execute();
    
    log_admin_action('permission_update', "Rôle modifié pour l'utilisateur ID: $user_id, nouveau rôle: $new_role");
}

// =========================
// HANDLERS - LOGS
// =========================
function logs_list($filters): array {
    $conn = getConnection();
    
    // Créer la table des logs si elle n'existe pas
    $conn->query("
        CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100),
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    $where = ['1=1'];
    $params = [];
    $types = '';
    
    if (!empty($filters['action'])) {
        $where[] = 'al.action LIKE ?';
        $params[] = '%' . $filters['action'] . '%';
        $types .= 's';
    }
    
    if (!empty($filters['user'])) {
        $where[] = '(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
        $search_term = '%' . $filters['user'] . '%';
        $params = array_merge($params, [$search_term, $search_term, $search_term]);
        $types .= 'sss';
    }
    
    if (!empty($filters['date_from'])) {
        $where[] = 'DATE(al.created_at) >= ?';
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = 'DATE(al.created_at) <= ?';
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $sql = "
        SELECT al.*, u.nom, u.prenom, u.email 
        FROM admin_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE " . implode(' AND ', $where) . "
        ORDER BY al.created_at DESC 
        LIMIT 100
    ";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// =========================
// VUES
// =========================
function page_layout(string $title, string $content, array $opts = []): void {
    $current_user = current_user();
    $current_action = $_GET['action'] ?? 'dashboard';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME . ' · ' . $title) ?></title>
    <link rel="stylesheet" href="adminpanel.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="adminpanel.js" defer></script>
</head>
<body>
    <div class="noise"></div>
    
    <?php if (!($opts['hide_nav'] ?? false)): ?>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../HomePage/index.php" class="nav-brand" style="text-decoration: none; color: inherit;">
                <div class="brand-logo">
                    <span>🛡️</span>
                </div>
                <div class="brand-text"><?= e(APP_NAME) ?></div>
            </a>
            
            <div class="nav-menu">
                <a href="../HomePage/index.php" class="nav-link">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="?action=dashboard" class="nav-link <?= $current_action === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a href="?action=tickets" class="nav-link <?= $current_action === 'tickets' ? 'active' : '' ?>">
                    <i class="fas fa-ticket-alt"></i> Tickets
                </a>
                <a href="?action=users" class="nav-link <?= $current_action === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Comptes
                </a>
                <?php if ($current_user['droit'] >= 2): ?>
                <a href="?action=permissions" class="nav-link <?= $current_action === 'permissions' ? 'active' : '' ?>">
                    <i class="fas fa-shield-alt"></i> Permissions
                </a>
                <?php endif; ?>
                <a href="?action=logs" class="nav-link <?= $current_action === 'logs' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i> Logs
                </a>
                
                <div class="nav-user">
                    <div class="user-info">
                        <span class="user-name"><?= e($current_user['prenom'] . ' ' . $current_user['nom']) ?></span>
                        <span class="user-role">
                            <?php
                            switch($current_user['droit']) {
                                case 2: echo 'Super Admin'; break;
                                case 1: echo 'Admin'; break;
                                default: echo 'Utilisateur'; break;
                            }
                            ?>
                        </span>
                    </div>
                    <a href="?action=logout" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
  </nav>
<?php endif; ?>
    
    <main class="main-content">
<div class="container">
            <div class="page-header">
                <h1><?= e($title) ?></h1>
            </div>
  <?= $content ?>
</div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <small>© <?= date('Y') ?> — <?= e(APP_NAME) ?> • Panneau d'administration</small>
        </div>
</footer>
</body>
</html>
<?php }

// =========================
// VUES - DASHBOARD
// =========================
function dashboard_view($stats): string {
    ob_start();
?>
    <div class="dashboard-grid">
        <div class="stats-grid">
            <div class="stat-card urgent">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['tickets_urgent'] ?></div>
                    <div class="stat-label">Tickets urgents</div>
                </div>
            </div>
            
            <div class="stat-card open">
                <div class="stat-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['tickets_open'] ?></div>
                    <div class="stat-label">Tickets ouverts</div>
                </div>
            </div>
            
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['tickets_total'] ?></div>
                    <div class="stat-label">Total tickets</div>
                </div>
            </div>
            
            <div class="stat-card users">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $stats['users_total'] ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
            </div>
        </div>
        
        <div class="recent-section">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> Tickets récents</h3>
                    <a href="?action=tickets" class="btn btn-sm btn-secondary">Voir tous</a>
                </div>
                <div class="card-content">
                    <?php if (empty($stats['recent_tickets'])): ?>
                        <p class="empty-state">Aucun ticket récent</p>
                    <?php else: ?>
                        <div class="ticket-list">
                            <?php foreach ($stats['recent_tickets'] as $ticket): ?>
                                <div class="ticket-item">
                                    <div class="ticket-info">
                                        <div class="ticket-title"><?= e($ticket['title']) ?></div>
                                        <div class="ticket-meta">
                                            Par <?= e($ticket['prenom'] . ' ' . $ticket['nom']) ?> • 
                                            <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                                        </div>
                                    </div>
                                    <div class="ticket-badges">
                                        <span class="badge priority-<?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span>
                                        <span class="badge status-<?= $ticket['status'] ?>"><?= ucfirst($ticket['status']) ?></span>
                                    </div>
                                    <div class="ticket-action">
                                        <a href="?action=ticket_detail&id=<?= $ticket['id'] ?>" class="btn btn-sm btn-secondary">Voir</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

// =========================
// VUES - TICKETS
// =========================
function tickets_view($tickets, $filters): string {
    ob_start();
?>
    <div class="page-actions">
        <div class="filters">
            <form method="GET" class="filter-form">
                <input type="hidden" name="action" value="tickets">
                
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Rechercher..." value="<?= e($filters['search']) ?>" class="input">
                </div>
                
                <div class="filter-group">
                    <select name="status" class="input">
                        <option value="">Tous les statuts</option>
                        <option value="open" <?= $filters['status'] === 'open' ? 'selected' : '' ?>>Ouvert</option>
                        <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                        <option value="resolved" <?= $filters['status'] === 'resolved' ? 'selected' : '' ?>>Résolu</option>
                        <option value="closed" <?= $filters['status'] === 'closed' ? 'selected' : '' ?>>Fermé</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="priority" class="input">
                        <option value="">Toutes les priorités</option>
                        <option value="low" <?= $filters['priority'] === 'low' ? 'selected' : '' ?>>Faible</option>
                        <option value="medium" <?= $filters['priority'] === 'medium' ? 'selected' : '' ?>>Moyenne</option>
                        <option value="high" <?= $filters['priority'] === 'high' ? 'selected' : '' ?>>Élevée</option>
                        <option value="urgent" <?= $filters['priority'] === 'urgent' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="category" class="input">
                        <option value="">Toutes les catégories</option>
                        <option value="materiel" <?= $filters['category'] === 'materiel' ? 'selected' : '' ?>>Matériel</option>
                        <option value="logiciel" <?= $filters['category'] === 'logiciel' ? 'selected' : '' ?>>Logiciel</option>
                        <option value="reseau" <?= $filters['category'] === 'reseau' ? 'selected' : '' ?>>Réseau</option>
                        <option value="autre" <?= $filters['category'] === 'autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                
                <a href="?action=tickets" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-ticket-alt"></i> Tickets (<?= count($tickets) ?>)</h3>
        </div>
        <div class="card-content">
            <?php if (empty($tickets)): ?>
                <p class="empty-state">Aucun ticket trouvé</p>
            <?php else: ?>
                <div class="table-responsive no-scrollbar">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Titre</th>
                                <th>Utilisateur</th>
                                <th>Catégorie</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr class="ticket-row" onclick="window.location.href='?action=ticket_detail&id=<?= $ticket['id'] ?>'" style="cursor: pointer;">
                                <td>#<?= $ticket['id'] ?></td>
                                <td>
                                    <div class="ticket-title-cell">
                                        <strong><?= e($ticket['title']) ?></strong>
                                        <div class="ticket-description"><?= e(substr($ticket['description'], 0, 100)) ?>...</div>
                                    </div>
                                </td>
                                <td><?= e($ticket['prenom'] . ' ' . $ticket['nom']) ?></td>
                                <td><span class="badge category-<?= $ticket['category'] ?>"><?= ucfirst($ticket['category']) ?></span></td>
                                <td><span class="badge priority-<?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span></td>
                                <td><span class="badge status-<?= $ticket['status'] ?>"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                                <td onclick="event.stopPropagation()">
                                    <div class="action-buttons">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="op" value="update_status">
                                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                            <div class="select-wrapper">
                                                <select name="status" onchange="this.form.submit()" class="input input-sm" data-custom="true">
                                                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Ouvert</option>
                                                <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                                                <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Résolu</option>
                                                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Fermé</option>
                                            </select>
                                            </div>
                                        </form>
                                        
                                        <?php if (current_user()['droit'] >= 2): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce ticket ?')">
                                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="op" value="delete">
                                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

// =========================
// VUES - USERS
// =========================
function users_view($users, $search): string {
    ob_start();
    $current_user = current_user();
?>
    <div class="page-actions">
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="hidden" name="action" value="users">
                <div class="search-group">
                    <input type="text" name="search" placeholder="Rechercher par nom, prénom, email..." value="<?= e($search) ?>" class="input">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <a href="?action=users" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
        
        <?php if ($current_user['droit'] >= 2): ?>
        <div class="action-section">
            <button onclick="toggleModal('createUserModal')" class="btn btn-success">
                <i class="fas fa-plus"></i> Créer un utilisateur
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users"></i> Utilisateurs (<?= count($users) ?>)</h3>
        </div>
        <div class="card-content">
            <?php if (empty($users)): ?>
                <p class="empty-state">Aucun utilisateur trouvé</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Nom complet</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Rôle</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <div class="user-info">
                                        <strong><?= e($user['username']) ?></strong>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge badge-info">Vous</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= e($user['prenom'] . ' ' . $user['nom']) ?></td>
                                <td><?= e($user['email']) ?></td>
                                <td><?= e($user['numero_telephone'] ?? '-') ?></td>
                                <td>
                                    <span class="badge role-<?= $user['droit'] ?>">
                                        <?php
                                        switch($user['droit']) {
                                            case 2: echo 'Super Admin'; break;
                                            case 1: echo 'Admin'; break;
                                            default: echo 'Utilisateur'; break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if ($current_user['droit'] >= 2 && $user['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="resetPassword(<?= $user['id'] ?>, '<?= e($user['username']) ?>')" class="btn btn-sm btn-warning">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="op" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Créer Utilisateur -->
    <?php if ($current_user['droit'] >= 2): ?>
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Créer un nouvel utilisateur</h3>
                <button onclick="toggleModal('createUserModal')" class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="op" value="create">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nom d'utilisateur</label>
                            <input type="text" name="username" required class="input">
                        </div>
                        <div class="form-group">
                            <label>Mot de passe</label>
                            <input type="password" name="password" required class="input">
                        </div>
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="nom" required class="input">
                        </div>
                        <div class="form-group">
                            <label>Prénom</label>
                            <input type="text" name="prenom" required class="input">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required class="input">
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="telephone" class="input">
                        </div>
                        <div class="form-group">
                            <label>Rôle</label>
                            <select name="droit" class="input">
                                <option value="0">Utilisateur</option>
                                <option value="1">Administrateur</option>
                                <option value="2">Super Administrateur</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="toggleModal('createUserModal')" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-success">Créer</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Éditer Utilisateur -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier l'utilisateur</h3>
                <button onclick="toggleModal('editUserModal')" class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="op" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">
                <input type="hidden" name="original_droit" id="edit_original_droit">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nom d'utilisateur</label>
                            <input type="text" name="username" id="edit_username" required class="input">
                        </div>
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="nom" id="edit_nom" required class="input">
                        </div>
                        <div class="form-group">
                            <label>Prénom</label>
                            <input type="text" name="prenom" id="edit_prenom" required class="input">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" required class="input">
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="telephone" id="edit_telephone" class="input">
                        </div>
                        <?php if ($current_user['droit'] >= 2): ?>
                        <div class="form-group">
                            <label>Rôle</label>
                            <select name="droit" id="edit_droit" class="input">
                                <option value="0">Utilisateur</option>
                                <option value="1">Administrateur</option>
                                <option value="2">Super Administrateur</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="toggleModal('editUserModal')" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary">Modifier</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Réinitialiser Mot de Passe -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Réinitialiser le mot de passe</h3>
                <button onclick="toggleModal('resetPasswordModal')" class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="op" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-body">
                    <p>Utilisateur : <strong id="reset_username"></strong></p>
                    <div class="form-group">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="new_password" required class="input" minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="toggleModal('resetPasswordModal')" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-warning">Réinitialiser</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
<?php
    return ob_get_clean();
}

// =========================
// VUES - PERMISSIONS
// =========================
function permissions_view($roles): string {
    ob_start();
?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-shield-alt"></i> Gestion des permissions</h3>
        </div>
        <div class="card-content">
            <div class="permissions-grid">
                <?php foreach ($roles as $role): ?>
                <div class="permission-card">
                    <div class="permission-header">
                        <h4><?= e($role['role_name']) ?></h4>
                        <span class="user-count"><?= $role['user_count'] ?> utilisateur(s)</span>
                    </div>
                    <div class="permission-content">
                        <div class="permission-level">
                            Niveau : <strong><?= $role['droit'] ?></strong>
                        </div>
                        <div class="permission-description">
                            <?php
                            switch($role['droit']) {
                                case 2:
                                    echo "Accès complet : gestion des utilisateurs, permissions, tickets et logs";
                                    break;
                                case 1:
                                    echo "Accès administrateur : gestion des tickets et consultation des logs";
                                    break;
                                default:
                                    echo "Accès utilisateur standard : création et suivi de ses propres tickets";
                                    break;
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users-cog"></i> Modifier les rôles utilisateurs</h3>
        </div>
        <div class="card-content">
            <form method="GET" class="search-form">
                <input type="hidden" name="action" value="permissions">
                <div class="search-group">
                    <input type="text" name="user_search" placeholder="Rechercher un utilisateur..." class="input">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
            
            <?php if (isset($_GET['user_search']) && !empty($_GET['user_search'])): ?>
            <?php
            $conn = getConnection();
            $search = '%' . $_GET['user_search'] . '%';
            $stmt = $conn->prepare("SELECT * FROM users WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR username LIKE ?");
            $stmt->bind_param('ssss', $search, $search, $search, $search);
            $stmt->execute();
            $found_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>
            
            <div class="user-results">
                <h4>Résultats de recherche :</h4>
                <?php if (empty($found_users)): ?>
                    <p>Aucun utilisateur trouvé</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle actuel</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($found_users as $user): ?>
                                <tr>
                                    <td><?= e($user['prenom'] . ' ' . $user['nom']) ?></td>
                                    <td><?= e($user['email']) ?></td>
                                    <td>
                                        <span class="badge role-<?= $user['droit'] ?>">
                                            <?php
                                            switch($user['droit']) {
                                                case 2: echo 'Super Admin'; break;
                                                case 1: echo 'Admin'; break;
                                                default: echo 'Utilisateur'; break;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="op" value="update_role">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="new_role" class="input input-sm">
                                                <option value="0" <?= $user['droit'] == 0 ? 'selected' : '' ?>>Utilisateur</option>
                                                <option value="1" <?= $user['droit'] == 1 ? 'selected' : '' ?>>Administrateur</option>
                                                <option value="2" <?= $user['droit'] == 2 ? 'selected' : '' ?>>Super Administrateur</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Modifier</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-muted">Votre compte</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

// =========================
// VUES - LOGS
// =========================
function logs_view($logs, $filters): string {
    ob_start();
?>
    <div class="page-actions">
        <div class="filters">
            <form method="GET" class="filter-form">
                <input type="hidden" name="action" value="logs">
                
                <div class="filter-group">
                    <input type="text" name="action_filter" placeholder="Action..." value="<?= e($filters['action']) ?>" class="input">
                </div>
                
                <div class="filter-group">
                    <input type="text" name="user" placeholder="Utilisateur..." value="<?= e($filters['user']) ?>" class="input">
                </div>
                
                <div class="filter-group">
                    <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>" class="input">
                </div>
                
                <div class="filter-group">
                    <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>" class="input">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                
                <a href="?action=logs" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Logs d'activité (<?= count($logs) ?>)</h3>
            <small>Dernières 100 entrées</small>
        </div>
        <div class="card-content">
            <?php if (empty($logs)): ?>
                <p class="empty-state">Aucun log trouvé</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date/Heure</th>
                                <th>Utilisateur</th>
                                <th>Action</th>
                                <th>Détails</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <?php if ($log['nom']): ?>
                                        <?= e($log['prenom'] . ' ' . $log['nom']) ?>
                                        <br><small><?= e($log['email']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Utilisateur supprimé</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge action-<?= str_replace(['_', ' '], '-', strtolower($log['action'])) ?>">
                                        <?= e($log['action']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="log-details">
                                        <?= e($log['details']) ?>
                                    </div>
                                </td>
                                <td>
                                    <small><?= e($log['ip_address']) ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

// =========================
// VUES - TICKET DETAIL
// =========================
function ticket_detail_view($ticket, $responses): string {
    ob_start();
    $current_user = current_user();
?>
    <div class="ticket-detail-container">
        <!-- Informations du ticket -->
        <div class="card ticket-info-card">
            <div class="card-header">
                <div class="ticket-header-info">
                    <h2><?= e($ticket['title']) ?></h2>
                    <div class="ticket-meta">
                        <span class="badge priority-<?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span>
                        <span class="badge status-<?= $ticket['status'] ?>"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span>
                        <span class="badge category-<?= $ticket['category'] ?>"><?= ucfirst($ticket['category']) ?></span>
                    </div>
                </div>
                <div class="ticket-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="op" value="update_status">
                                <div class="select-wrapper">
                                <select name="status" onchange="this.form.submit()" class="input" data-custom="true">
                            <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Ouvert</option>
                            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                            <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Résolu</option>
                            <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Fermé</option>
                        </select>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-content">
                <div class="ticket-info-grid">
                    <div class="info-section">
                        <h4>Informations du ticket</h4>
                        <div class="info-item">
                            <label>ID :</label>
                            <span>#<?= $ticket['id'] ?></span>
                        </div>
                        <div class="info-item">
                            <label>Type :</label>
                            <span><?= ucfirst($ticket['type']) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Créé le :</label>
                            <span><?= date('d/m/Y à H:i', strtotime($ticket['created_at'])) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Mis à jour :</label>
                            <span><?= date('d/m/Y à H:i', strtotime($ticket['updated_at'])) ?></span>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h4>Informations utilisateur</h4>
                        <div class="info-item">
                            <label>Nom :</label>
                            <span><?= e($ticket['prenom'] . ' ' . $ticket['nom']) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email :</label>
                            <span><?= e($ticket['email']) ?></span>
                        </div>
                        <?php if ($ticket['numero_telephone']): ?>
                        <div class="info-item">
                            <label>Téléphone :</label>
                            <span><?= e($ticket['numero_telephone']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="ticket-description-section">
                    <h4>Description</h4>
                    <div class="ticket-description-content">
                        <?= nl2br(e($ticket['description'])) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chat des réponses -->
        <!-- Chat du ticket -->
        <div id="ticket-chat" style="margin-top:1.25rem;">
          <h3>Discussion</h3>
          <div id="admin-chat-box" style="border:1px solid rgba(255,255,255,0.07);padding:.8rem;height:300px;overflow:auto;background:rgba(0,0,0,0.03);">
            <!-- messages injectés ici -->
            <div style="color:var(--muted);">Chargement des messages…</div>
          </div>

          <form id="admin-chat-form" style="display:flex;gap:.5rem;margin-top:.6rem;align-items:stretch;" onsubmit="return false;">
            <input id="admin-chat-input" name="message" type="text" placeholder="Écrire une réponse..." style="flex:1;min-width:0;padding:.7rem .6rem;border-radius:6px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.04);color:inherit;font-size:.95rem;line-height:1.4;" autocomplete="off" />
            <button id="admin-chat-send" type="button" class="submit-btn" style="width:auto;min-width:100px;flex-shrink:0;padding:.7rem 20px;display:flex;align-items:center;justify-content:center;font-size:.95rem;">Envoyer</button>
          </form>
        </div>
        
        <!-- Bouton Retour à la liste -->
        <div style="margin-top: 1.5rem; display: flex; justify-content: center;">
            <a href="?action=tickets" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
    
    <script>
    (function(){
        const ticketId = <?= (int)$ticket['id'] ?>;
        const box = document.getElementById('admin-chat-box');
        const input = document.getElementById('admin-chat-input');
        const sendBtn = document.getElementById('admin-chat-send');

        function esc(s){
            return String(s)
                .replace(/&/g,'&amp;')
                .replace(/</g,'&lt;')
                .replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;')
                .replace(/'/g,'&#039;');
        }

        function render(messages){
            if (!Array.isArray(messages)) return;
            box.innerHTML = messages.map(m=>{
                const who = m.username ? esc(m.username) : ('ID:'+m.sender_id);
                const time = new Date(m.created_at).toLocaleString();
                return '<div style="margin-bottom:.6rem;"><strong>'+who+'</strong> <small style="color:#777;margin-left:.4rem;">'+time+'</small><div style="margin-top:.25rem;">'+esc(m.message)+'</div></div>';
            }).join('');
            box.scrollTop = box.scrollHeight;
        }

        async function fetchMessages(){
            try{
                const res = await fetch('../Tickets/chat_api.php?ticket_id='+ticketId);
                if (!res.ok) return;
                const data = await res.json();
                render(data);
            }catch(e){console.error(e)}
        }

        async function sendMessage(){
            const v = input.value.trim();
            if (!v) return;
            try{
                const params = new URLSearchParams();
                params.append('ticket_id', ticketId);
                params.append('message', v);
                const res = await fetch('../Tickets/chat_api.php', { method: 'POST', body: params });
                if (!res.ok) {
                    alert('Erreur lors de l\'envoi');
                    return;
                }
                input.value = '';
                fetchMessages();
            }catch(e){console.error(e)}
        }

        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keydown', function(e){ if (e.key === 'Enter') sendMessage(); });

        // polling régulier
        fetchMessages();
        setInterval(fetchMessages, 2500);
    })();
    </script>
<?php
    return ob_get_clean();
}
?>
