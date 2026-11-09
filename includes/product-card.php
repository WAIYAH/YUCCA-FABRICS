<article class="group overflow-hidden rounded-sm bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
    <a href="<?= e(url('product-details.php?slug=' . rawurlencode($product['slug']))) ?>" class="relative block aspect-[4/5] overflow-hidden bg-[#e6e0d6]">
        <img src="<?= e(product_image($product['image'] ?? null)) ?>" alt="<?= e($product['name']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
    </a>
    <div class="p-4"><p class="text-[10px] uppercase tracking-[.18em] text-sage"><?= e($product['category_name'] ?? 'Yucca collection') ?></p>
        <h3 class="mt-2 font-serif text-lg"><a href="<?= e(url('product-details.php?slug=' . rawurlencode($product['slug']))) ?>"><?= e($product['name']) ?></a></h3>
        <p class="mt-1 text-xs text-gray-500">SKU <?= e($product['sku']) ?> · <?= (int) $product['stock'] > 0 ? 'In stock' : 'Out of stock' ?></p>
        <?php if(isset($product['average_rating'])&&$product['average_rating']!==null): ?><p class="mt-2 text-xs text-clay" aria-label="Average customer rating <?= e(number_format((float)$product['average_rating'],1)) ?> out of 5">★ <?= e(number_format((float)$product['average_rating'],1)) ?> / 5</p><?php endif; ?>
        <div class="mt-4 flex items-center justify-end">
            <div class="flex items-center gap-2"><?php if (current_user() && current_user()['role']==='customer'): ?><button data-add-wishlist="<?= (int)$product['id'] ?>" class="px-1 text-clay" aria-label="Save <?= e($product['name']) ?> to favourites">♡</button><?php endif; ?><?php if ((int) $product['stock'] > 0): ?><button data-add-cart="<?= (int) $product['id'] ?>" class="rounded-full border border-forest px-4 py-2 text-xs font-semibold text-forest transition hover:bg-forest hover:text-white">Add to bag</button><?php endif; ?></div>
        </div>
    </div>
</article>
