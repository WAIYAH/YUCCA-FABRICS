<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function absolute_url(string $path = ''): string
{
    return APP_SITE_URL !== '' ? APP_SITE_URL . url($path) : url($path);
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Your session expired. Refresh the page and try again.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function current_user(): ?array
{
    if (array_key_exists('current_user_cache_loaded', $GLOBALS)) {
        return $GLOBALS['current_user_cache'];
    }
    $GLOBALS['current_user_cache_loaded'] = true;
    $stored = $_SESSION['user'] ?? null;
    if (!$stored || empty($stored['id'])) {
        $GLOBALS['current_user_cache'] = null;
        return null;
    }
    $stmt = db()->prepare("SELECT id,name,email,phone,role FROM users WHERE id=? AND status='active' LIMIT 1");
    $stmt->execute([(int) $stored['id']]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        $GLOBALS['current_user_cache'] = null;
        return null;
    }
    $_SESSION['user'] = $user;
    $GLOBALS['current_user_cache'] = $user;
    return $user;
}

function set_current_user(array $user): void
{
    $_SESSION['user'] = $user;
    $GLOBALS['current_user_cache_loaded'] = true;
    $GLOBALS['current_user_cache'] = $user;
}

function require_login(): void
{
    if (!current_user()) {
        flash('error', 'Please sign in to continue.');
        redirect('auth/login.php');
    }
}

function require_admin(): void
{
    if (!current_user() || (current_user()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

function money(float|int|string $amount): string
{
    return CURRENCY . ' ' . number_format((float) $amount, 2);
}

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, name, slug, price, sale_price, stock, image FROM products WHERE is_active = 1 AND id IN ({$placeholders})");
    $stmt->execute($ids);
    $items = [];
    foreach ($stmt->fetchAll() as $product) {
        $quantity = max(1, min((int) $cart[$product['id']], (int) $product['stock']));
        $price = $product['sale_price'] !== null ? (float) $product['sale_price'] : (float) $product['price'];
        $items[] = $product + ['quantity' => $quantity, 'unit_price' => $price, 'line_total' => $price * $quantity];
    }
    return $items;
}

function cart_count(): int
{
    return array_sum(array_map('intval', $_SESSION['cart'] ?? []));
}

function coupon_for_checkout(string $code, float $subtotal, ?int $userId = null, bool $lock = false): ?array
{
    $sql = 'SELECT * FROM coupons WHERE code=? AND is_active=1 AND (starts_at IS NULL OR starts_at<=NOW()) AND (expires_at IS NULL OR expires_at>=NOW()) LIMIT 1';
    if ($lock) $sql .= ' FOR UPDATE';
    $stmt = db()->prepare($sql);
    $stmt->execute([strtoupper(trim($code))]);
    $coupon = $stmt->fetch();
    if (!$coupon || $subtotal < (float) $coupon['minimum_order']) return null;
    if ($coupon['usage_limit'] !== null) {
        $usage = db()->prepare('SELECT COUNT(*) FROM coupon_usage WHERE coupon_id=?');
        $usage->execute([$coupon['id']]);
        if ((int) $usage->fetchColumn() >= (int) $coupon['usage_limit']) return null;
    }
    if ($userId !== null) {
        $usage = db()->prepare('SELECT COUNT(*) FROM coupon_usage WHERE coupon_id=? AND user_id=?');
        $usage->execute([$coupon['id'], $userId]);
        if ((int) $usage->fetchColumn() > 0) return null;
    }
    $discount = $coupon['discount_type'] === 'percent'
        ? $subtotal * (float) $coupon['discount_value'] / 100
        : (float) $coupon['discount_value'];
    $coupon['discount_amount'] = round(min($subtotal, $discount), 2);
    return $coupon;
}

function persist_cart(): void
{
    $user = current_user();
    if (!$user) {
        return;
    }
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO cart (user_id) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $stmt->execute([(int) $user['id']]);
    $cartId = (int) $pdo->lastInsertId();
    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
    if (!empty($_SESSION['cart'])) {
        $insert = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) SELECT ?, id, ? FROM products WHERE id = ? AND is_active = 1');
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $insert->execute([$cartId, max(1, (int) $quantity), (int) $productId]);
        }
    }
}

function restore_user_cart(): void
{
    $user = current_user();
    if (!$user) {
        return;
    }
    $stmt = db()->prepare('SELECT ci.product_id, ci.quantity FROM cart_items ci JOIN cart c ON c.id = ci.cart_id WHERE c.user_id = ?');
    $stmt->execute([(int) $user['id']]);
    $stored = [];
    foreach ($stmt->fetchAll() as $row) {
        $stored[(int) $row['product_id']] = (int) $row['quantity'];
    }
    $_SESSION['cart'] = array_replace($stored, $_SESSION['cart'] ?? []);
    persist_cart();
}

const PRODUCT_PLACEHOLDER_IMAGE = 'assets/images/fabrics/rust-tweed-texture.jpeg';

function product_image(?string $image): string
{
    return $image ? url('uploads/products/' . rawurlencode(basename($image))) : url(PRODUCT_PLACEHOLDER_IMAGE);
}

function fabric_image(string $file): string
{
    return url('assets/images/fabrics/' . rawurlencode(basename($file)));
}

function fabric_collection(): array
{
    static $collection;
    return $collection ??= require __DIR__ . '/fabric-collection.php';
}

function save_uploaded_image(string $field, string $folder): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Image upload failed or exceeds the 5 MB limit.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Upload a valid JPG, PNG or WebP image.');
    }
    $directory = APP_ROOT . '/uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Image storage is unavailable.');
    }
    $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('The image could not be saved.');
    }
    return $filename;
}

function setting(string $key, string $fallback = ''): string
{
    static $settings;
    if ($settings === null) {
        $settings = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM website_settings')->fetchAll() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $exception) {
            error_log('Unable to load website settings: ' . $exception->getMessage());
        }
    }
    return (string) ($settings[$key] ?? $fallback);
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

function send_business_email(string $recipient, string $subject, string $message): bool
{
    $from = setting('business_email', 'hello@yuccafabrics.co.ke');
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        error_log('Email was not sent because a recipient or sender address is invalid.');
        return false;
    }
    $headers = "From: Yucca Fabrics <{$from}>\r\nContent-Type: text/plain; charset=UTF-8";
    if (!@mail($recipient, $subject, $message, $headers)) {
        error_log('Email delivery failed for recipient ' . $recipient);
        return false;
    }
    return true;
}
