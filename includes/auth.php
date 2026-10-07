<?php
/**
 * Authentication & Authorization
 */
declare(strict_types=1);

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']['id']);
}

function user_role(): ?string {
    return $_SESSION['user']['role'] ?? null;
}

function require_login(?string $redirect = null): void {
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        redirect($redirect ?? BASE_URL . '/auth/login.php');
    }
}

function require_role(string|array $roles, ?string $redirect = null): void {
    require_login($redirect);
    $roles = (array)$roles;
    if (!in_array(user_role(), $roles, true)) {
        flash('danger', 'You do not have permission to access this page.');
        $home = match(user_role()) {
            'admin' => BASE_URL . '/admin/index.php',
            'vendor' => BASE_URL . '/vendor/index.php',
            'customer' => BASE_URL . '/customer/dashboard.php',
            default => BASE_URL . '/index.php',
        };
        redirect($home);
    }
}

function login_user(array $user): void {
    session_regenerate_id(true);
    unset($user['password']);
    $_SESSION['user'] = $user;
    global $pdo;
    try {
        $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
    } catch (Exception $e) {}
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function attempt_login(string $email, string $password): array {
    global $pdo;
    $st = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = ? LIMIT 1');
    $st->execute([$email, 'active']);
    $user = $st->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
    // For vendors, check vendor status
    if ($user['role'] === 'vendor') {
        $vs = $pdo->prepare('SELECT status FROM vendors WHERE user_id = ?');
        $vs->execute([$user['id']]);
        $vstatus = $vs->fetchColumn();
        if ($vstatus === 'suspended' || $vstatus === 'rejected') {
            return ['success' => false, 'message' => 'Your vendor account is ' . $vstatus . '. Contact support.'];
        }
        if ($vstatus === 'pending') {
            return ['success' => false, 'message' => 'Your vendor account is pending approval.'];
        }
    }
    login_user($user);
    return ['success' => true, 'user' => $user];
}

function register_customer(array $data): array {
    global $pdo;
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');

    if (!$name || !$email || !$password) {
        return ['success' => false, 'message' => 'All required fields must be filled.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }

    try {
        $pdo->beginTransaction();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO users (name, email, password, role, phone, status) VALUES (?,?,?,?,?,?)');
        $st->execute([$name, $email, $hash, 'customer', $phone ?: null, 'active']);
        $userId = (int)$pdo->lastInsertId();
        $st2 = $pdo->prepare('INSERT INTO customers (user_id, full_name, phone) VALUES (?,?,?)');
        $st2->execute([$userId, $name, $phone ?: null]);
        $pdo->commit();
        return ['success' => true, 'user_id' => $userId];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

function register_vendor(array $data): array {
    global $pdo;
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $business = trim($data['business_name'] ?? '');
    $owner = trim($data['owner_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');
    $city = trim($data['city'] ?? '');
    $description = trim($data['description'] ?? '');

    if (!$business || !$owner || !$email || !$password) {
        return ['success' => false, 'message' => 'Business name, owner name, email and password are required.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }

    try {
        $pdo->beginTransaction();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO users (name, email, password, role, phone, status) VALUES (?,?,?,?,?,?)');
        $st->execute([$owner, $email, $hash, 'vendor', $phone ?: null, 'active']);
        $userId = (int)$pdo->lastInsertId();
        $slug = slugify($business);
        // Ensure unique slug
        $sc = $pdo->prepare('SELECT id FROM vendors WHERE slug = ?');
        $sc->execute([$slug]);
        if ($sc->fetch()) $slug .= '-' . substr(uniqid(), -4);
        $st2 = $pdo->prepare('INSERT INTO vendors (user_id, business_name, slug, owner_name, email, phone, address, city, description, status) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $st2->execute([$userId, $business, $slug, $owner, $email, $phone ?: null, $address ?: null, $city ?: null, $description ?: null, 'pending']);
        $pdo->commit();
        // Notify admin
        $admin = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
        if ($admin) notify((int)$admin, 'vendor', 'New Vendor Registration', "$business has registered and awaits approval.", BASE_URL . '/admin/vendors.php');
        return ['success' => true, 'user_id' => $userId];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

function get_vendor_id(): ?int {
    global $pdo;
    if (user_role() !== 'vendor' || empty($_SESSION['user']['id'])) return null;
    static $vid = null;
    if ($vid !== null) return $vid;
    $st = $pdo->prepare('SELECT id FROM vendors WHERE user_id = ?');
    $st->execute([$_SESSION['user']['id']]);
    $vid = $st->fetchColumn();
    return $vid ? (int)$vid : null;
}

function get_customer_id(): ?int {
    global $pdo;
    if (user_role() !== 'customer' || empty($_SESSION['user']['id'])) return null;
    static $cid = null;
    if ($cid !== null) return $cid;
    $st = $pdo->prepare('SELECT id FROM customers WHERE user_id = ?');
    $st->execute([$_SESSION['user']['id']]);
    $cid = $st->fetchColumn();
    return $cid ? (int)$cid : null;
}

/** Run a prepared statement and return it (use instead of interpolating values into SQL). */
function db_q(string $sql, array $params = []): PDOStatement {
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st;
}
