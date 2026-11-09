<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_user()) {
    redirect('account/dashboard.php');
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($name === '' || mb_strlen($name) > 150) $errors[] = 'Enter your full name.';
    if (!preg_match('/^[a-zA-Z0-9_]{3,60}$/', $username)) $errors[] = 'Username must be 3–60 letters, numbers or underscores.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) $errors[] = 'Password must be at least 10 characters and include uppercase, lowercase and a number.';
    if (!isset($_POST['terms'])) $errors[] = 'Please accept the terms and privacy policy.';
    if (!$errors) {
        $check = db()->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            $errors[] = 'That email or username is already registered.';
        } else {
            $stmt = db()->prepare("INSERT INTO users (name, username, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $username, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]);
            set_current_user(['id' => (int) db()->lastInsertId(), 'name' => $name, 'email' => $email, 'phone' => $phone, 'role' => 'customer']);
            session_regenerate_id(true);
            restore_user_cart();
            redirect('account/dashboard.php');
        }
    }
}
$pageTitle = 'Create an account | Yucca Fabrics';
require __DIR__ . '/../includes/header.php';
?>
<section class="mx-auto max-w-xl px-5 py-16"><p class="eyebrow">Welcome to Yucca</p><h1 class="mt-3 font-serif text-4xl">Create your account</h1><p class="mt-3 text-sm text-gray-600">Keep track of orders, service requests and consultations.</p>
<?php foreach ($errors as $error): ?><p class="mt-4 rounded bg-red-50 p-3 text-sm text-red-800"><?= e($error) ?></p><?php endforeach; ?>
<form method="post" class="mt-8 grid gap-4"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label class="text-sm">Full name<input class="form-control mt-1" name="name" required maxlength="150" value="<?= e($_POST['name'] ?? '') ?>"></label>
<label class="text-sm">Username<input class="form-control mt-1" name="username" required minlength="3" maxlength="60" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"></label>
<label class="text-sm">Email<input class="form-control mt-1" type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
<label class="text-sm">Phone (optional)<input class="form-control mt-1" type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
<label class="text-sm">Password<input class="form-control mt-1" type="password" name="password" required minlength="10" autocomplete="new-password"></label>
<label class="flex items-start gap-2 text-sm"><input type="checkbox" name="terms" required class="mt-1"><span>I agree to the <a class="underline" href="<?= e(url('terms.php')) ?>">terms</a> and <a class="underline" href="<?= e(url('privacy-policy.php')) ?>">privacy policy</a>.</span></label>
<button class="rounded-full bg-forest px-6 py-3 font-semibold text-white">Create account</button></form>
<p class="mt-6 text-sm">Already have an account? <a class="underline" href="<?= e(url('auth/login.php')) ?>">Sign in</a></p></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
