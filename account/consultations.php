<?php
require_once __DIR__.'/../includes/functions.php';require_login();
$stmt=db()->prepare('SELECT consultation_type,preferred_date,preferred_time,status,admin_notes FROM consultations WHERE user_id=? ORDER BY preferred_date DESC,preferred_time DESC');$stmt->execute([current_user()['id']]);$rows=$stmt->fetchAll();
$pageTitle='My consultations | Yucca Fabrics';require __DIR__.'/../includes/header.php';
?>
<section class="mx-auto max-w-5xl px-5 py-12"><h1 class="font-serif text-4xl">My consultations</h1><a class="mt-4 inline-block text-sm underline" href="<?= e(url('consultations.php')) ?>">Request a consultation</a><?php if(!$rows): ?><p class="mt-8 bg-white p-8 text-sm">You don’t have any consultation requests yet.</p><?php else: ?><div class="mt-7 space-y-4"><?php foreach($rows as $row): ?><article class="bg-white p-5"><h2 class="font-serif text-xl"><?= e($row['consultation_type']) ?></h2><p class="mt-2 text-sm"><?= e(date('M j, Y',strtotime($row['preferred_date']))) ?> · <?= e(date('g:i A',strtotime($row['preferred_time']))) ?> · <span class="capitalize"><?= e($row['status']) ?></span></p><?php if($row['admin_notes']): ?><p class="mt-3 text-sm text-gray-600"><?= e($row['admin_notes']) ?></p><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
