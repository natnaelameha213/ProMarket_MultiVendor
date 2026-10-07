<?php
/**
 * Helper functions
 */
declare(strict_types=1);

function e(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    return !empty($token) && hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return $text ?: 'n-a';
}

function money(float|int|string $amount): string {
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}

function format_date(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

function product_image(?string $path): string {
    // Orange + Navy placeholder (shown when no image is available)
    $placeholder = 'data:image/svg+xml,' . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400">' .
        '<rect fill="#102A43" width="400" height="400"/>' .
        '<polygon points="280,0 400,0 400,400 160,400" fill="#FF6B00"/>' .
        '<rect x="45" y="75" width="260" height="250" rx="18" fill="#fff"/>' .
        '<circle cx="175" cy="170" r="48" fill="#102A43"/>' .
        '<text x="175" y="188" text-anchor="middle" fill="#FF6B00" font-family="sans-serif" font-size="52" font-weight="700">P</text>' .
        '<text x="175" y="270" text-anchor="middle" fill="#102A43" font-family="sans-serif" font-size="22" font-weight="700">ProMarket</text>' .
        '</svg>'
    );

    if (!$path) {
        return $placeholder;
    }

    // External URL
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }

    // Local uploaded file (assets/uploads/...)
    if (file_exists(UPLOAD_PATH . '/' . $path)) {
        return UPLOAD_URL . '/' . $path;
    }

    return $placeholder;
}

function setting(string $key, string $default = ''): string {
    global $pdo;
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $st = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $st->execute([$key]);
        $val = $st->fetchColumn();
        $cache[$key] = $val !== false ? (string)$val : $default;
    } catch (Exception $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function generate_order_number(): string {
    return 'PM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

function paginate(int $total, int $page, int $perPage = ITEMS_PER_PAGE): array {
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current' => $page,
        'pages' => $pages,
        'offset' => ($page - 1) * $perPage,
        'has_prev' => $page > 1,
        'has_next' => $page < $pages,
    ];
}

function pagination_html(array $p, string $baseUrl): string {
    if ($p['pages'] <= 1) return '';
    $html = '<nav><ul class="pagination justify-content-center flex-wrap">';
    $prevDisabled = $p['has_prev'] ? '' : ' disabled';
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . e($baseUrl) . 'page=' . ($p['current'] - 1) . '">Prev</a></li>';
    $start = max(1, $p['current'] - 2);
    $end = min($p['pages'], $p['current'] + 2);
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $p['current'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($baseUrl) . 'page=' . $i . '">' . $i . '</a></li>';
    }
    $nextDisabled = $p['has_next'] ? '' : ' disabled';
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . e($baseUrl) . 'page=' . ($p['current'] + 1) . '">Next</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

function status_badge(string $status): string {
    $map = [
        'active' => 'success', 'approved' => 'success', 'paid' => 'success', 'delivered' => 'success', 'completed' => 'success',
        'pending' => 'warning', 'processing' => 'info', 'confirmed' => 'primary', 'shipped' => 'info',
        'inactive' => 'secondary', 'suspended' => 'danger', 'rejected' => 'danger', 'cancelled' => 'danger', 'failed' => 'danger',
        'draft' => 'secondary',
    ];
    $color = $map[strtolower($status)] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . e(ucfirst($status)) . '</span>';
}

function stars(float $rating): string {
    $full = (int)floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    $html = '';
    for ($i = 0; $i < $full; $i++) $html .= '<i class="bi bi-star-fill text-warning"></i>';
    if ($half) $html .= '<i class="bi bi-star-half text-warning"></i>';
    for ($i = 0; $i < $empty; $i++) $html .= '<i class="bi bi-star text-warning"></i>';
    return $html;
}

function notify(int $userId, string $type, string $title, string $message, ?string $link = null): void {
    global $pdo;
    try {
        $st = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link) VALUES (?,?,?,?,?)');
        $st->execute([$userId, $type, $title, $message, $link]);
    } catch (Exception $e) { /* silent */ }
}

function cart_count(): int {
    global $pdo;
    if (empty($_SESSION['user']['id'])) return 0;
    try {
        $st = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?');
        $st->execute([$_SESSION['user']['id']]);
        return (int)$st->fetchColumn();
    } catch (Exception $e) { return 0; }
}

function wishlist_count(): int {
    global $pdo;
    if (empty($_SESSION['user']['id'])) return 0;
    try {
        $st = $pdo->prepare('SELECT COUNT(*) FROM wishlist WHERE user_id = ?');
        $st->execute([$_SESSION['user']['id']]);
        return (int)$st->fetchColumn();
    } catch (Exception $e) { return 0; }
}

function current_price(array $product): float {
    if (!empty($product['discount_price']) && (float)$product['discount_price'] > 0 && (float)$product['discount_price'] < (float)$product['price']) {
        return (float)$product['discount_price'];
    }
    return (float)$product['price'];
}

function has_discount(array $product): bool {
    return !empty($product['discount_price']) && (float)$product['discount_price'] > 0 && (float)$product['discount_price'] < (float)$product['price'];
}