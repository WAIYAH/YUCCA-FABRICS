<?php
require_once __DIR__.'/../includes/functions.php';require_login();
$stmt=db()->prepare('SELECT sr.*,s.name AS service_name FROM service_requests sr LEFT JOIN services s ON s.id=sr.service_id WHERE sr.user_id=? ORDER BY sr.created_at DESC');$stmt->execute([current_user()['id']]);$rows=$stmt->fetchAll();
$pageTitle='My service requests | Yucca Fabrics';require __DIR__.'/../includes/header.php';
?>
<section class="mx-auto max-w-5xl px-5 py-12"><h1 class="font-serif text-4xl">My service requests</h1><a class="mt-4 inline-block text-sm underline" href="<?= e(url('services.php')) ?>">Request a service</a><?php if(!$rows): ?><p class="mt-8 bg-white p-8 text-sm">You don’t have any service requests yet.</p><?php else: ?><div class="mt-7 space-y-4"><?php foreach($rows as $row): ?><article class="bg-white p-5"><h2 class="font-serif text-xl"><?= e($row['service_name']??'Project enquiry') ?></h2><p class="mt-2 line-clamp-2 text-sm text-gray-600"><?= e($row['description']) ?></p><p class="mt-3 text-xs capitalize"><?= e($row['status']) ?> · <?= e(date('M j, Y',strtotime($row['created_at']))) ?></p><?php if($row['admin_response']): ?><p class="mt-3 border-t border-black/10 pt-3 text-sm"><?= e($row['admin_response']) ?></p><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
