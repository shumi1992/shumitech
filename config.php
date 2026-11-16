<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "Insurance@123";
$databasename = "website";

$conn = new mysqli($servername, $username, $password, $databasename);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (!$conn->set_charset("utf8mb4")) {
    die("Could not configure the database connection for Unicode text.");
}

// Essential functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function normalizeRoleIdentifier($role) {
    $role = strtolower(trim((string) $role));
    $role = preg_replace('/[^a-z0-9]+/', ' ', $role);
    $role = preg_replace('/\s+/', ' ', $role);
    return trim($role);
}

function resolveRoleAccessGroup($role) {
    $normalized_role = normalizeRoleIdentifier($role);

    if (in_array($normalized_role, ['admin', 'manager', 'user'], true)) {
        return $normalized_role;
    }

    if ($normalized_role === 'supervisor') {
        return 'supervisor';
    }

    $compact_role = str_replace(' ', '', $normalized_role);
    if ($normalized_role !== '' && (strpos($normalized_role, 'supervisor') !== false || substr($compact_role, -10) === 'supervisor')) {
        return 'supervisor';
    }

    return 'user';
}

function getCurrentSessionRole() {
    return $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
}

function hasRole($role) {
    $expected_role = normalizeRoleIdentifier($role);
    $current_role = normalizeRoleIdentifier(getCurrentSessionRole());

    if (in_array($expected_role, ['admin', 'supervisor', 'manager', 'user'], true)) {
        return resolveRoleAccessGroup($current_role) === $expected_role;
    }

    return $current_role !== '' && $current_role === $expected_role;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getDashboardPathForRole($role) {
    $resolved_role = resolveRoleAccessGroup($role);
    $default_dashboard = 'user_dashboard.php';
    $dashboard_map = [
        'admin' => 'admin_dashboard.php',
        'supervisor' => 'supervisor_dashboard.php',
        'manager' => 'manager_dashboard.php',
        'user' => $default_dashboard
    ];

    $target_dashboard = $dashboard_map[$resolved_role] ?? $default_dashboard;

    if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . $target_dashboard)) {
        return $default_dashboard;
    }

    return $target_dashboard;
}

function redirectToRoleDashboard($role = null) {
    $resolved_role = $role ?? ($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user');
    redirect(getDashboardPathForRole($resolved_role));
}

function setAuthenticatedUserSession(array $user, $id_column = null) {
    if ($id_column === null || !isset($user[$id_column])) {
        if (isset($user['user_id'])) {
            $id_column = 'user_id';
        } elseif (isset($user['id'])) {
            $id_column = 'id';
        }
    }

    if ($id_column === null || !isset($user[$id_column])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user[$id_column];
    $_SESSION['username'] = $user['username'] ?? '';
    $_SESSION['full_name'] = $user['full_name'] ?? '';
    $_SESSION['role'] = $user['role'] ?? 'user';
    $_SESSION['user_role'] = $user['role'] ?? 'user';
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['phone'] = $user['phone'] ?? '';
    $_SESSION['department'] = $user['department'] ?? '';
    $_SESSION['created_at'] = $user['created_at'] ?? null;
    $_SESSION['last_login'] = $user['last_login'] ?? null;
    $_SESSION['profile_photo'] = !empty($user['profile_photo']) ? $user['profile_photo'] : 'uploads/profile/default.png';

    return true;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function esc($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
