<?php
require_once __DIR__ . '/../includes/functions.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND status = ? LIMIT 1');
        $stmt->execute([$email, 'active']);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')->execute([$user['id'], $hash]);
            $resetLink = url('auth/reset-password.php?token=' . rawurlencode($token));
            $subject = 'Reset your Yucca Fabrics password';
            $body = "Use this one-time link within one hour to reset your password:\n\n{$resetLink}";
            $from = setting('business_email', 'hello@yuccafabrics.co.ke');
            if (!@mail($email, $subject, $body, 'From: ' . $from)) {
                error_log('Password reset email could not be sent for user ID ' . $user['id']);
            }
        }
    }
    $message = 'If an active account matches that email, password reset instructions will be sent.';
}
$pageTitle = 'Reset password | Yucca Fabrics';
require __DIR__ . '/../includes/header.php';
?>
<section class="mx-auto max-w-md px-5 py-20"><h1 class="font-serif text-4xl">Forgot your password?</h1><p class="mt-3 text-sm text-gray-600">Enter your email and, if it belongs to an active account, we’ll send a one-time reset link.</p>
<?php if ($message): ?><p class="mt-5 rounded bg-green-50 p-3 text-sm text-green-800"><?= e($message) ?></p><?php endif; ?>
<form method="post" class="mt-8 grid gap-4"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="text-sm">Email<input class="form-control mt-1" type="email" name="email" required></label><button class="rounded-full bg-forest px-6 py-3 font-semibold text-white">Send reset link</button></form></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
