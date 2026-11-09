<?php
require_once __DIR__ . '/includes/functions.php';
$categories = db()->query('SELECT id,name FROM gallery_categories ORDER BY name')->fetchAll();
$selected = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$sql = 'SELECT g.*,gc.name AS category_name FROM gallery g LEFT JOIN gallery_categories gc ON gc.id=g.category_id WHERE g.is_active=1';
$params = [];
if ($selected > 0) { $sql .= ' AND g.category_id=?'; $params[] = $selected; }
$sql .= ' ORDER BY g.created_at DESC LIMIT 48';
$stmt = db()->prepare($sql);$stmt->execute($params);$projects = $stmt->fetchAll();
$pageTitle = 'Spaces and projects | Yucca Fabrics';
require __DIR__ . '/includes/header.php';
?>
<section class="bg-[#e9e7df] px-5 py-16 text-center"><p class="eyebrow">A little inspiration</p><h1 class="mt-3 font-serif text-5xl">Spaces with a story</h1><p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-gray-600">A collection of ideas, textures and projects. Our portfolio grows as new work is shared.</p></section>
<section class="mx-auto max-w-7xl px-5 py-12"><nav class="mb-8 flex flex-wrap gap-2"><a class="rounded-full px-4 py-2 text-xs <?= !$selected?'bg-forest text-white':'bg-white' ?>" href="<?= e(url('gallery.php')) ?>">All spaces</a><?php foreach($categories as $category): ?><a class="rounded-full px-4 py-2 text-xs <?= $selected===(int)$category['id']?'bg-forest text-white':'bg-white' ?>" href="?category=<?= (int)$category['id'] ?>"><?= e($category['name']) ?></a><?php endforeach; ?></nav>
<?php if ($projects): ?><div class="columns-1 gap-4 sm:columns-2 lg:columns-3"><?php foreach($projects as $project): ?><figure class="mb-4 break-inside-avoid bg-white"><button class="block w-full text-left" data-lightbox="<?= e(url('uploads/gallery/' . rawurlencode(basename($project['image'])))) ?>" data-title="<?= e($project['title']) ?>"><img class="w-full" src="<?= e(url('uploads/gallery/' . rawurlencode(basename($project['image'])))) ?>" alt="<?= e($project['title']) ?>" loading="lazy"><figcaption class="p-4"><p class="text-[10px] uppercase tracking-widest text-sage"><?= e($project['category_name'] ?? 'Yucca project') ?></p><h2 class="mt-1 font-serif text-xl"><?= e($project['title']) ?></h2><p class="mt-2 text-sm text-gray-600"><?= e($project['description']) ?></p></figcaption></button></figure><?php endforeach; ?></div>
<dialog id="gallery-lightbox" class="max-h-[90vh] max-w-5xl bg-transparent p-0 backdrop:bg-black/80"><button id="lightbox-close" class="absolute right-3 top-3 rounded-full bg-white px-3 py-2" aria-label="Close image">✕</button><img id="lightbox-image" class="max-h-[80vh] w-full object-contain"><p id="lightbox-title" class="bg-white p-3 text-sm"></p></dialog>
<script>const dialog=document.getElementById('gallery-lightbox');document.querySelectorAll('[data-lightbox]').forEach(el=>el.addEventListener('click',()=>{document.getElementById('lightbox-image').src=el.dataset.lightbox;document.getElementById('lightbox-title').textContent=el.dataset.title;dialog.showModal()}));document.getElementById('lightbox-close')?.addEventListener('click',()=>dialog.close());</script>
<?php else: ?><div class="bg-white px-6 py-16 text-center"><h2 class="font-serif text-2xl">Our gallery is taking shape</h2><p class="mt-3 text-sm text-gray-600">We’re preparing a collection of completed spaces and upholstery work. In the meantime, tell us about the room you’re dreaming of.</p><a class="mt-5 inline-block underline" href="<?= e(url('consultations.php')) ?>">Book a consultation</a></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
