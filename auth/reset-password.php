<?php
require_once __DIR__ . '/../includes/functions.php';
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$done = false;
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'This reset link is invalid or expired.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    if (strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Use at least 10 characters with uppercase, lowercase and a number.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() FOR UPDATE');
        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch();
        if (!$reset) {
            $pdo->rollBack();
            $error = 'This reset link is invalid or expired.';
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$reset['id']]);
            $pdo->commit();
            $done = true;
        }
    }
}
$pageTitle = 'Choose a new password | Yucca Fabrics';
require __DIR__ . '/../includes/header.php';
?>
<section class="mx-auto max-w-md px-5 py-20"><h1 class="font-serif text-4xl">Choose a new password</h1>
<?php if ($error): ?><p class="mt-5 rounded bg-red-50 p-3 text-sm text-red-800"><?= e($error) ?></p><?php endif; ?>
<?php if ($done): ?><p class="mt-5 rounded bg-green-50 p-3 text-sm text-green-800">Your password has been updated. <a class="underline" href="<?= e(url('auth/login.php')) ?>">Sign in</a>.</p><?php elseif (!$error): ?><form method="post" class="mt-8 grid gap-4"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="token" value="<?= e($token) ?>"><label class="text-sm">New password<input class="form-control mt-1" type="password" name="password" minlength="10" required autocomplete="new-password"></label><button class="rounded-full bg-forest px-6 py-3 font-semibold text-white">Update password</button></form><?php endif; ?></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
