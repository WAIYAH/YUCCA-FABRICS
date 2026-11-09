<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_user()) redirect((current_user()['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'account/dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $now = time();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $attempts = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE email=? AND ip_address=? AND attempted_at>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)');
    $attempts->execute([$email, $ip]);
    if ((int) $attempts->fetchColumn() >= 8) {
        $error = 'Too many sign-in attempts. Please wait 15 minutes and try again.';
    } else {
        $stmt = db()->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && $user['status'] === 'active' && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            session_regenerate_id(true);
            unset($user['password_hash'], $user['status']);
            set_current_user($user);
            db()->prepare('DELETE FROM login_attempts WHERE email=? AND ip_address=?')->execute([$email, $ip]);
            restore_user_cart();
            redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'account/dashboard.php');
        }
        db()->prepare('INSERT INTO login_attempts (email,ip_address) VALUES (?,?)')->execute([$email, $ip]);
        $error = 'The email or password is incorrect, or the account is unavailable.';
    }
}
$pageTitle = 'Sign in | Yucca Fabrics';
require __DIR__ . '/../includes/header.php';
?>
<section class="mx-auto max-w-md px-5 py-20"><p class="eyebrow">Your Yucca account</p><h1 class="mt-3 font-serif text-4xl">Welcome back</h1>
<?php if ($error): ?><p class="mt-5 rounded bg-red-50 p-3 text-sm text-red-800"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="mt-8 grid gap-4"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="text-sm">Email<input class="form-control mt-1" type="email" name="email" required autocomplete="email"></label><label class="text-sm">Password<input class="form-control mt-1" type="password" name="password" required autocomplete="current-password"></label><button class="rounded-full bg-forest px-6 py-3 font-semibold text-white">Sign in</button></form>
<div class="mt-5 flex justify-between text-sm"><a class="underline" href="<?= e(url('auth/forgot-password.php')) ?>">Forgot password?</a><a class="underline" href="<?= e(url('auth/register.php')) ?>">Create an account</a></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
