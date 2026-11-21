<?php
require_once __DIR__ . '/includes/functions.php';
$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$limit = 9;
$where = "bp.status='published' AND bp.published_at<=NOW()";
$params=[];
if ($search !== '') {$where .= ' AND (bp.title LIKE ? OR bp.excerpt LIKE ? OR bp.body LIKE ?)';array_push($params,"%{$search}%","%{$search}%","%{$search}%");}
$stmt=db()->prepare("SELECT COUNT(*) FROM blog_posts bp WHERE {$where}");$stmt->execute($params);$pages=max(1,(int)ceil((int)$stmt->fetchColumn()/$limit));$page=min($pages,$page);
$stmt=db()->prepare("SELECT bp.*,bc.name AS category_name FROM blog_posts bp LEFT JOIN blog_categories bc ON bc.id=bp.category_id WHERE {$where} ORDER BY bp.published_at DESC LIMIT {$limit} OFFSET ".(($page-1)*$limit));$stmt->execute($params);$posts=$stmt->fetchAll();
$pageTitle='Notes on fabrics and home | Yucca Fabrics';
require __DIR__ . '/includes/header.php';
?>
<section class="bg-cream px-5 py-16 text-center"><p class="eyebrow">The Yucca journal</p><h1 class="mt-3 font-serif text-5xl">Notes on making a home</h1><p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-gray-600">Ideas for choosing fabrics, caring for furniture and making a space feel a little more like you.</p></section>
<section class="mx-auto max-w-7xl px-5 py-12"><form class="mb-8 flex max-w-lg gap-2"><label class="sr-only" for="blog-search">Search stories</label><input id="blog-search" class="form-control" type="search" name="q" value="<?= e($search) ?>" placeholder="Search journal"><button class="rounded-full bg-forest px-5 text-sm text-white">Search</button></form>
<?php if($posts): ?><div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"><?php foreach($posts as $post): ?><article class="overflow-hidden bg-white"><a href="<?= e(url('article.php?slug='.rawurlencode($post['slug']))) ?>"><img class="aspect-[4/3] w-full object-cover" src="<?= e($post['image'] ? url('uploads/blog/'.rawurlencode(basename($post['image']))) : fabric_image('soho-inverted-leather.jpeg')) ?>" alt="<?= e($post['title']) ?>" loading="lazy"></a><div class="p-5"><p class="text-[10px] uppercase tracking-widest text-sage"><?= e($post['category_name']??'Yucca notes') ?> · <?= e(date('M j, Y',strtotime($post['published_at']))) ?></p><h2 class="mt-3 font-serif text-2xl"><a href="<?= e(url('article.php?slug='.rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2><p class="mt-3 line-clamp-2 text-sm leading-6 text-gray-600"><?= e($post['excerpt'] ?: strip_tags($post['body'])) ?></p><a class="mt-4 inline-block text-sm font-semibold" href="<?= e(url('article.php?slug='.rawurlencode($post['slug']))) ?>">Read the story →</a></div></article><?php endforeach; ?></div><?php else: ?><div class="bg-white px-6 py-16 text-center"><h2 class="font-serif text-2xl">Good stories are on their way</h2><p class="mt-3 text-sm text-gray-600">We’ll be sharing ideas from the world of fabrics, furniture and home.</p></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
