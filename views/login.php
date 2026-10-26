<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masuk | Titik Temu Kopi</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/style.css"></head>
<body class="login-page">
<main class="login-layout">
<section class="login-aside">
<a class="brand brand-invert" href="index.php?page=login"><img class="brand-mark coffee-logo" src="assets/coffee-mark.svg" alt="Logo cangkir kopi"><span>titik<span class="brand-light">temu</span><small>cafe operations</small></span></a>
<div class="login-quote"><span class="quote-mark">&ldquo;</span><h1>Secangkir yang baik dimulai dari perhatian pada hal kecil.</h1><p>Pesanan rapi, bahan terpantau, pelayanan terasa lebih dekat.</p></div>
<div class="login-aside-foot">TITIK TEMU KOPI <span>&bull;</span> SEJAK 2024</div>
</section>
<section class="login-main"><div class="login-box"><span class="hello-dot"></span><p class="eyebrow">SELAMAT DATANG KEMBALI</p><h2>Masuk ke Titik Temu</h2><p class="login-sub">Gunakan akun operasional Anda untuk melanjutkan.</p>
<?php if($flash):?><div class="notice <?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div><?php endif;?>
<form method="post" class="login-form"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><label>Username atau email<input name="identity" autocomplete="username" placeholder="contoh: admin" required maxlength="120" autofocus></label><label>Kata sandi<div class="password-wrap"><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required><button type="button" class="reveal" aria-label="Tampilkan kata sandi">Lihat</button></div></label><button class="button primary full" type="submit">Masuk ke dashboard <span>&rarr;</span></button></form>
<div class="login-hint">Akun demo tersedia di README setelah database diimpor.</div></div><div class="login-copyright">&copy; <?= date('Y') ?> Titik Temu Kopi. Sistem operasional kafe.</div></section>
</main><script src="assets/app.js" defer></script></body></html>