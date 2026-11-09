<?php
require_once __DIR__.'/../includes/functions.php';require_login();
$stmt=db()->prepare("SELECT p.*,c.name AS category_name FROM wishlists w JOIN wishlist_items wi ON wi.wishlist_id=w.id JOIN products p ON p.id=wi.product_id LEFT JOIN categories c ON c.id=p.category_id WHERE w.user_id=? AND p.is_active=1 ORDER BY wi.created_at DESC");$stmt->execute([current_user()['id']]);$products=$stmt->fetchAll();
$pageTitle='Saved fabrics | Yucca Fabrics';require __DIR__.'/../includes/header.php';
?>
<section class="mx-auto max-w-7xl px-5 py-12"><p class="eyebrow">Saved for later</p><h1 class="mt-3 font-serif text-4xl">Your favourites</h1><?php if($products): ?><div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"><?php foreach($products as $product): ?><div class="relative"><?php require __DIR__.'/../includes/product-card.php'; ?><button data-remove-wishlist="<?= (int)$product['id'] ?>" class="mt-3 text-xs underline">Remove from favourites</button></div><?php endforeach; ?></div><?php else: ?><div class="mt-7 bg-white p-12 text-center"><p class="font-serif text-2xl">No saved fabrics yet</p><a class="mt-4 inline-block underline" href="<?= e(url('shop.php')) ?>">Explore the collection</a></div><?php endif; ?></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
