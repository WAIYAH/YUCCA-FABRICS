<?php
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['remove'])) {
        unset($_SESSION['cart'][(int) $_POST['remove']]);
    } elseif (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $id => $quantity) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
            if (!$id || !$quantity || $quantity < 1) continue;
            $check = db()->prepare('SELECT stock FROM products WHERE id=? AND is_active=1');
            $check->execute([$id]);
            $stock = $check->fetchColumn();
            if ($stock !== false) $_SESSION['cart'][$id] = min((int) $stock, $quantity);
        }
    }
    persist_cart();
    redirect('cart.php');
}
$items = cart_items();
$subtotal = array_sum(array_column($items, 'line_total'));
$coupon = !empty($_SESSION['coupon']) ? coupon_for_checkout((string)$_SESSION['coupon'],$subtotal,current_user()?(int)current_user()['id']:null) : null;
$pageTitle = 'Your shopping bag | Yucca Fabrics';
require __DIR__ . '/includes/header.php';
?>
<section class="mx-auto max-w-7xl px-5 py-12"><p class="eyebrow">The things you love</p><h1 class="mt-3 font-serif text-4xl">Your shopping bag</h1>
<?php if (!$items): ?><div class="mt-8 bg-white px-6 py-16 text-center"><h2 class="font-serif text-2xl">Your bag is waiting for something lovely</h2><p class="mt-3 text-sm text-gray-600">Explore the collection and find a fabric that feels like you.</p><a class="mt-6 inline-block rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white" href="<?= e(url('shop.php')) ?>">Browse fabrics</a></div>
<?php else: ?><div class="mt-8 grid gap-8 lg:grid-cols-[1fr_340px]"><div class="bg-white p-5 sm:p-8"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php foreach ($items as $item): ?><article class="grid grid-cols-[90px_1fr_auto] gap-4 border-b border-black/10 py-5 first:pt-0 sm:grid-cols-[120px_1fr_auto]"><img class="h-28 w-full object-cover" src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['name']) ?>"><div><a class="font-serif text-lg" href="<?= e(url('product-details.php?slug='.$item['slug'])) ?>"><?= e($item['name']) ?></a><p class="mt-1 text-xs text-gray-500"><?= e(money($item['unit_price'])) ?> / <?= e($item['unit']) ?></p><label class="mt-3 inline-flex items-center gap-2 text-xs">Qty<input class="form-control w-20 py-2" type="number" min="1" max="<?= (int)$item['stock'] ?>" name="quantities[<?= (int)$item['id'] ?>]" value="<?= (int)$item['quantity'] ?>"></label></div><div class="text-right"><p class="text-sm font-semibold"><?= e(money($item['line_total'])) ?></p><button class="mt-3 text-xs text-gray-500 underline" name="remove" value="<?= (int)$item['id'] ?>">Remove</button></div></article><?php endforeach; ?><button class="mt-5 text-sm font-semibold underline">Update bag</button></form></div>
<aside class="h-fit bg-white p-6"><h2 class="font-serif text-2xl">Your summary</h2><form data-ajax-form data-reload="true" action="<?= e(url('api/coupon.php')) ?>" method="post" class="mt-5 flex gap-2"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="sr-only" for="coupon-code">Coupon code</label><input id="coupon-code" class="form-control min-w-0" name="code" placeholder="Coupon code" value="<?= e($_SESSION['coupon']??'') ?>"><button class="rounded-full border border-forest px-4 text-xs">Apply</button></form><?php if(!empty($_SESSION['coupon'])): ?><div class="mt-2 flex items-center justify-between text-xs"><?php if($coupon): ?><span><?= e($_SESSION['coupon']) ?> · −<?= e(money($coupon['discount_amount'])) ?></span><?php else: ?><span class="text-red-700">This coupon is no longer available.</span><?php endif; ?><form data-ajax-form data-reload="true" action="<?= e(url('api/coupon.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="remove"><button class="underline">Remove</button></form></div><?php endif; ?><div class="mt-6 flex justify-between text-sm"><span>Subtotal</span><span><?= e(money($subtotal)) ?></span></div><?php if($coupon): ?><div class="mt-3 flex justify-between text-sm"><span>Coupon</span><span>−<?= e(money($coupon['discount_amount'])) ?></span></div><?php endif; ?><div class="mt-3 flex justify-between text-sm"><span>Delivery</span><span><?= e(money(DELIVERY_FEE)) ?></span></div><p class="mt-3 text-xs leading-5 text-gray-500">Taxes and delivery are confirmed at checkout. Fabric is sold by the metre.</p><a href="<?= e(url('checkout.php')) ?>" class="mt-6 block rounded-full bg-forest px-6 py-3 text-center text-sm font-semibold text-white">Continue to checkout</a><a href="<?= e(url('shop.php')) ?>" class="mt-4 block text-center text-xs underline">Continue browsing</a></aside></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
