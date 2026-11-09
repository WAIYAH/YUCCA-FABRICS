<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME . ' | A Softer Place to Land';
$description = $description ?? 'Discover beautiful fabrics, upholstery craftsmanship and thoughtful interior design at Yucca Fabrics.';
$canonicalPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
if (BASE_URL !== '' && str_starts_with($canonicalPath, BASE_URL)) $canonicalPath = substr($canonicalPath, strlen(BASE_URL)) ?: '/';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="theme-color" content="#34483a">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php if(APP_SITE_URL!==''): ?><link rel="canonical" href="<?= e(APP_SITE_URL . BASE_URL . $canonicalPath) ?>"><?php endif; ?>
    <link rel="icon" type="image/jpeg" href="<?= e(url('assets/images/yucca-fabrics-logo.jpeg')) ?>">
    <?php if(isset($structuredData)): ?><script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{forest:'#2E7D32',sage:'#F9A825',clay:'#FF9800',sand:'#F8F9FA',ink:'#333333'},fontFamily:{sans:['Inter','sans-serif'],serif:['Poppins','sans-serif']}}}}</script>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <script>window.YUCCA={base:<?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP) ?>,csrf:<?= json_encode(csrf_token()) ?>};</script>
</head>
<body class="bg-sand text-ink antialiased">
<header class="site-header sticky top-0 z-40 border-b border-white/15 bg-[#556B2F] text-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
        <a href="<?= e(url('index.php')) ?>" aria-label="Yucca Fabrics home" class="shrink-0"><img src="<?= e(url('assets/images/yucca-fabrics-logo.jpeg')) ?>" alt="Yucca Fabrics" class="h-16 w-16 rounded bg-white object-contain"></a>
        <button id="menu-toggle" class="rounded-md p-2 text-white md:hidden" aria-label="Toggle navigation" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
        <nav id="main-nav" class="hidden items-center gap-7 text-sm md:flex">
            <a class="nav-link" href="<?= e(url('index.php')) ?>">Home</a>
            <details class="services-menu relative">
                <summary class="nav-link cursor-pointer list-none">Services <span aria-hidden="true">⌄</span></summary>
                <div class="services-submenu absolute left-0 top-full z-50 hidden min-w-48 bg-[#465927] py-2 text-white shadow-lg">
                    <a class="block px-4 py-2 text-sm hover:bg-white/10" href="<?= e(url('services.php')) ?>">Upholstery services</a>
                    <a class="block px-4 py-2 text-sm hover:bg-white/10" href="<?= e(url('shop.php')) ?>">Shop fabrics</a>
                    <a class="block px-4 py-2 text-sm hover:bg-white/10" href="<?= e(url('consultations.php')) ?>">Interior design</a>
                </div>
            </details>
            <a class="nav-link" href="<?= e(url('contact.php')) ?>">Contact</a>
        </nav>
        <div class="hidden items-center gap-4 md:flex">
            <?php if(current_user() && current_user()['role']==='customer'): ?><a href="<?= e(url('account/wishlist.php')) ?>" class="text-white" aria-label="Saved favourites">♡</a><?php endif; ?><a href="<?= e(url('cart.php')) ?>" class="relative text-white" aria-label="Shopping bag"><i class="fa-solid fa-bag-shopping"></i><span id="cart-count" class="ml-1 rounded-full bg-clay px-1.5 py-0.5 text-xs text-white"><?= cart_count() ?></span></a>
            <?php if (current_user()): ?>
                <a class="text-sm" href="<?= e(url((current_user()['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'account/dashboard.php')) ?>">My account</a>
                <form action="<?= e(url('auth/logout.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="text-sm">Sign out</button></form>
            <?php else: ?>
                <a class="text-sm" href="<?= e(url('auth/login.php')) ?>">Sign in</a>
            <?php endif; ?>
        </div>
    </div>
    <div id="mobile-nav" class="hidden border-t border-white/20 px-5 pb-5 text-white md:hidden">
        <nav class="grid gap-3 pt-4 text-sm">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <details class="services-menu">
                <summary class="cursor-pointer list-none">Services <span aria-hidden="true">⌄</span></summary>
                <div class="services-submenu hidden space-y-3 border-l border-white/30 pl-4 pt-3">
                    <a class="block" href="<?= e(url('services.php')) ?>">Upholstery services</a>
                    <a class="block" href="<?= e(url('shop.php')) ?>">Shop fabrics</a>
                    <a class="block" href="<?= e(url('consultations.php')) ?>">Interior design</a>
                </div>
            </details>
            <a href="<?= e(url('contact.php')) ?>">Contact</a>
            <a href="<?= e(url('cart.php')) ?>">Shopping bag (<?= cart_count() ?>)</a><a href="<?= e(url(current_user() ? 'account/dashboard.php' : 'auth/login.php')) ?>"><?= current_user() ? 'My account' : 'Sign in / Register' ?></a><?php if(current_user() && current_user()['role']==='customer'): ?><a href="<?= e(url('account/wishlist.php')) ?>">Saved favourites</a><?php endif; ?><?php if(current_user()): ?><form action="<?= e(url('auth/logout.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button>Sign out</button></form><?php endif; ?>
        </nav>
    </div>
</header>
<main>
