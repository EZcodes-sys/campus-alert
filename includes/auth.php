<?php
require_once __DIR__ . '/db.php';

/** Roles used across the CEAS 4-tier RBAC model (Chapter 2, section 2.6) */
const VALID_ROLES = ['admin', 'security_officer', 'lecturer', 'student'];

/* ------------------------------------------------------------------ *
 *  Helpers
 * ------------------------------------------------------------------ */

/** Cleans user-supplied input: trims whitespace, strips tags, escapes HTML. */
function sanitise(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function isValidEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Enforces a minimum password strength: 8+ chars, 1 uppercase, 1 number. */
function isStrongPassword(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/** One-time flash message stored in the session, shown then cleared. */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function getFlash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/* ------------------------------------------------------------------ *
 *  CSRF protection
 * ------------------------------------------------------------------ */

function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(?string $token): bool
{
    return !empty($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ------------------------------------------------------------------ *
 *  Registration
 * ------------------------------------------------------------------ */

function registerUser(string $name, string $email, string $password, string $confirm, string $department = ''): array
{
    $name  = sanitise($name);
    $email = sanitise(strtolower($email));
    $department = sanitise($department);

    if ($name === '' || $email === '') {
        return ['success' => false, 'message' => 'Name and email are required.'];
    }
    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }
    if (!isStrongPassword($password)) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters and include an uppercase letter and a number.'];
    }
    if ($password !== $confirm) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

    $stmt = getDB()->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'This email is already registered.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Public self-registration always creates a 'student' account.
    // Staff roles (lecturer / security_officer / admin) are elevated by an admin afterwards.
    $stmt = getDB()->prepare(
        'INSERT INTO users (name, email, department, password_hash, role, is_active)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $stmt->execute([$name, $email, $department, $hash, 'student']);

    return ['success' => true, 'message' => 'Registration successful! You can now log in.'];
}

/* ------------------------------------------------------------------ *
 *  Login / logout / session
 * ------------------------------------------------------------------ */

function loginUser(string $email, string $password): array
{
    $email = sanitise(strtolower($email));

    $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    // Prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['user_id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['logged_in']     = true;
    $_SESSION['last_activity'] = time();

    getDB()->prepare('UPDATE users SET last_login = NOW() WHERE user_id = ?')->execute([$user['user_id']]);

    return ['success' => true, 'role' => $user['role']];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/** True if the current session is authenticated and not timed out. */
function isLoggedIn(): bool
{
    if (empty($_SESSION['logged_in'])) {
        return false;
    }

    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT_SECONDS) {
        logoutUser();
        return false;
    }

    $_SESSION['last_activity'] = time(); // refresh the idle timer
    return true;
}

/** Redirects to login if the user is not authenticated. Call at the top of protected pages. */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/** Redirects home (403-style) unless the logged-in user has one of the allowed roles. */
function requireRole(array $allowedRoles): void
{
    requireLogin();
    if (!in_array($_SESSION['user_role'], $allowedRoles, true)) {
        http_response_code(403);
        setFlash('error', 'You do not have permission to access that page.');
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireRole(['admin']);
}

/** Returns the currently logged-in user's full row, or null. */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    $stmt = getDB()->prepare('SELECT user_id, name, email, phone, department, role, profile_photo, is_active, last_login, created_at FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/* ------------------------------------------------------------------ *
 *  Password reset (token flow)
 * ------------------------------------------------------------------ */

function requestPasswordReset(string $email): array
{
    $email = sanitise(strtolower($email));
    $stmt = getDB()->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always return a generic message — never reveal whether the email exists.
    $generic = ['success' => true, 'message' => 'If that email is registered, a reset link has been sent.'];

    if (!$user) {
        return $generic;
    }

    $token     = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires   = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $stmt = getDB()->prepare('UPDATE users SET reset_token_hash = ?, reset_token_expires = ? WHERE user_id = ?');
    $stmt->execute([$tokenHash, $expires, $user['user_id']]);

    // In production this token would be emailed via PHPMailer, e.g.:
    // sendResetEmail($email, BASE_URL . '/auth/reset_password.php?token=' . $token);
    // For local demo/testing we surface it directly:
    $generic['debug_token'] = $token;

    return $generic;
}

function resetPasswordWithToken(string $token, string $newPassword, string $confirm): array
{
    if (!isStrongPassword($newPassword)) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters and include an uppercase letter and a number.'];
    }
    if ($newPassword !== $confirm) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

    $tokenHash = hash('sha256', $token);
    $stmt = getDB()->prepare('SELECT user_id FROM users WHERE reset_token_hash = ? AND reset_token_expires > NOW()');
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'This reset link is invalid or has expired.'];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = getDB()->prepare('UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires = NULL WHERE user_id = ?');
    $stmt->execute([$hash, $user['user_id']]);

    return ['success' => true, 'message' => 'Your password has been reset. You can now log in.'];
}

/**
 * A human-readable label for each role — used across dashboard/nav views.
 */
function roleLabel(string $role): string
{
    $labels = [
        'admin'            => 'Administrator',
        'security_officer' => 'Security Officer',
        'lecturer'         => 'Lecturer',
        'student'          => 'Student',
    ];
    return $labels[$role] ?? ucfirst($role);
}
