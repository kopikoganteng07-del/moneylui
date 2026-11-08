<?php
/**
 * Lemon-Grey - Template T1 (adaptasi dari VN Publisher T1 v3c)
 * ---------------------------------------------------------
 * Render engine SAMA untuk semua domain ber-template T1.
 * Baca /data/data.json (di-generate ulang oleh workflow "03 Generate Konten"
 * tiap kali ada perubahan konten - homepage atau subpage manapun).
 * Ganti template = ganti file ini, TIDAK perlu sentuh data.json.
 * Nambah/edit artikel = update data.json lewat sheet, TIDAK perlu sentuh file ini.
 *
 * Gambar grid 5 game generik (asset-game-1.jpg s/d asset-game-5.jpg) di-upload
 * KE SETIAP DOMAIN SENDIRI-SENDIRI (bukan nebeng 1 domain pusat) - keputusan
 * user supaya tidak ada single point of failure antar domain.
 *
 * Grid provider berisi NAMA provider software slot yang memang beroperasi
 * di industri ini (bukan logo/gambar bermerek) - dipakai sebagai referensi
 * faktual, sama seperti template asalnya.
 */

$id   = (isset($_GET['id']) && $_GET['id'] !== '') ? $_GET['id'] : 'homepage';
$file = __DIR__ . '/data/data.json';

if (!file_exists($file)) {
    http_response_code(500);
    exit('data.json tidak ditemukan.');
}

$data = json_decode(file_get_contents($file), true);
$page = isset($data[$id]) ? $data[$id] : null;

if (!$page) {
    http_response_code(404);
    exit('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="robots" content="noindex,nofollow"><title>404</title></head><body><h1>404</h1><p><a href="/">Kembali ke Beranda</a></p></body></html>');
}

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$homepage    = isset($data['homepage']) ? $data['homepage'] : [];
$isHomepage  = ($page['category'] === 'homepage');
$domainName  = $_SERVER['HTTP_HOST'];

$logoSrc      = $homepage['logo_src']      ?? '/img/logo.png';
$faviconSrc   = $homepage['favicon_src']   ?? '/img/favicon.png';
$bannerSrc    = $homepage['banner_src']    ?? ($homepage['image_src'] ?? '');
// Hoksiong memakai satu CTA global. Field lama tetap fallback agar template
// kompatibel dengan data LG saat pengujian terisolasi.
$ctaUrl        = $homepage['cta_url'] ?? ($homepage['cta_daftar_url'] ?? ($homepage['cta_login_url'] ?? '#'));
$ctaUrl        = trim((string)$ctaUrl) !== '' ? trim((string)$ctaUrl) : '#';
$whatsappCta   = $ctaUrl;
$telegramCta   = $ctaUrl;
$ctaDaftarUrl  = $ctaUrl;
$ctaLoginUrl   = $ctaUrl;

$gscToken = $homepage['gsc_token'] ?? '';
$noindex = !empty($page['noindex']);
$lang    = $page['lang'] ?? 'id';
$heroImg = $isHomepage ? $bannerSrc : ($page['image_src'] ?? '');

$allPosts = array_filter($data, function ($item) {
    return isset($item['category']) && $item['category'] === 'post';
});

$origin = 'https://' . $domainName;

// og:image dan schema WAJIB URL mutlak. Jalur relatif membuat pratinjau bagikan
// di WhatsApp dan Facebook gagal memuat gambar.
$ogRel = $page['image_src'] ?? $bannerSrc ?? '';
$ogAbs = $ogRel ? ((strpos($ogRel, 'http') === 0) ? $ogRel : $origin . $ogRel) : '';

// datePublished dan dateModified sengaja bernilai sama: kita tidak menyimpan
// tanggal terbit terpisah, dan mengarang tanggal terbit yang lebih tua justru
// klaim palsu. Sama seperti T2, T3, T4.
$dateMod = ($page['lastmod'] ?? date('Y-m-d')) . 'T00:00:00+07:00';

// Menu halaman informasi. Dicocokkan dengan AKHIRAN slug, bukan slug persis,
// karena staf memakai pola berawalan nama domain (mis. domain-link-alternatif).
// Pola yang lebih panjang didahulukan supaya 'cara-daftar' tidak tertelan 'daftar'.
// Satu label hanya dipakai sekali, sehingga menu tidak pernah punya dua tautan sama.
// Halaman yang belum ada otomatis tidak ditampilkan, jadi tidak pernah ada tautan mati.
$polaMenu = [
    // Kategori permainan: tampil di menu utama (header) begitu halamannya ada.
    'slot'            => 'Slot',
    'sportsbook'      => 'Sportsbook',
    'casino'          => 'Casino',
    'togel'           => 'Togel',
    // Halaman informasi: cukup tampil di footer.
    'about-us'        => 'Tentang Kami',
    'tentang-kami'    => 'Tentang Kami',
    'contact-us'      => 'Kontak',
    'kontak'          => 'Kontak',
    'link-alternatif' => 'Link Alternatif',
    'cara-daftar'     => 'Cara Daftar',
    'daftar'          => 'Cara Daftar',
    'cara-masuk-akun' => 'Cara Masuk',
    'cara-masuk'      => 'Cara Masuk',
    'login'           => 'Cara Masuk',
    'disclaimer'      => 'Disclaimer',
];

$menuHalaman = [];
$labelDipakai = [];
foreach ($polaMenu as $pola => $label) {
    if (isset($labelDipakai[$label])) { continue; }
    foreach ($allPosts as $slug => $item) {
        if (isset($menuHalaman[$slug])) { continue; }
        $s = strtolower(trim($slug));
        if ($s === $pola || substr($s, -(strlen($pola) + 1)) === '-' . $pola) {
            $menuHalaman[$slug] = $label;
            $labelDipakai[$label] = true;
            break;
        }
    }
}
// Artikel biasa dipisah dari menu supaya Disclaimer tidak nongol di grid
// "Artikel Terbaru" lengkap dengan thumbnail.
$bacaanLain = array_diff_key($allPosts, $menuHalaman);

// Pembagian menu (revisi 17/9): menu utama (header) berisi kategori permainan
// plus halaman navigasi berguna (Link Alternatif, Cara Daftar, Cara Masuk).
// Halaman informasi (Tentang Kami, Kontak, Disclaimer) cukup tampil di footer.
$labelHanyaFooter = ['Tentang Kami', 'Kontak', 'Disclaimer'];
/* HOKSIONG_SHORT_POST_MENU_V1: label navigasi pendek dari pola slug. */
$menuTetap = ['disclaimer'=>true,'contact-us'=>true,'about-us'=>true,'kontak'=>true,'kontak-kami'=>true,'tentang'=>true,'tentang-kami'=>true];
$polaLabelPendek = [
    'link-alternatif'=>'Link Alternatif', 'cara-masuk-akun'=>'Masuk',
    'sportsbook'=>'Sportsbook', 'cashback'=>'Cashback', 'indonesia'=>'Indonesia',
    'cara-daftar'=>'Daftar', 'daftar'=>'Daftar', 'login'=>'Masuk',
    'akses'=>'Akses', 'resmi'=>'Resmi', 'slot'=>'Slot', 'casino'=>'Casino',
    'togel'=>'Togel', 'idn'=>'IDN', 'asia'=>'Asia', 'agen'=>'Agen',
    'mobile'=>'Mobile', 'promo'=>'Promo', 'bonus'=>'Bonus', 'layanan'=>'Layanan',
    'link'=>'Link'
];
$menuHeader = [];
foreach ($allPosts as $slugMenu => $itemMenu) {
    if (isset($menuTetap[strtolower((string)$slugMenu)])) { continue; }
    if (strtolower(trim((string)($itemMenu['category'] ?? ''))) !== 'post') { continue; }
    $slugLower = strtolower(trim((string)$slugMenu));
    $labelMenu = '';
    foreach ($polaLabelPendek as $polaPendek => $labelPendek) {
        $slugBerpagar = '-' . trim($slugLower, '-') . '-';
        if ($slugLower === $polaPendek || strpos($slugBerpagar, '-' . $polaPendek . '-') !== false) {
            $labelMenu = $labelPendek;
            break;
        }
    }
    if ($labelMenu === '') { $labelMenu = ucwords(str_replace(['-','_'], ' ', (string)$slugMenu)); }
    $menuHeader[$slugMenu] = $labelMenu;
}

// Tautan internal di awal artikel: kemunculan PERTAMA nama situs di dalam
// content_html ditautkan ke beranda. Berlaku juga untuk artikel lama, karena
// dikerjakan saat render, bukan saat generate.
// Penggantian HANYA pada teks di luar tag, supaya nama situs yang kebetulan
// berada di dalam atribut tidak ikut dirusak. Hanya satu kali per halaman.
$isiArtikel = $page['content_html'];
$namaSitus = !empty($homepage['keyword']) ? $homepage['keyword'] : explode('.', $domainName)[0];
if ($namaSitus !== '' && $isiArtikel !== '') {
    $bagian = preg_split('/(<[^>]*>)/', $isiArtikel, -1, PREG_SPLIT_DELIM_CAPTURE);
    $sudah = false;
    foreach ($bagian as $i => $b) {
        if ($sudah || $b === '' || $b[0] === '<') { continue; }
        $pos = stripos($b, $namaSitus);
        if ($pos !== false) {
            $asli = substr($b, $pos, strlen($namaSitus));
            $bagian[$i] = substr($b, 0, $pos) . '<a href="/">' . $asli . '</a>' . substr($b, $pos + strlen($namaSitus));
            $sudah = true;
        }
    }
    if ($sudah) { $isiArtikel = implode('', $bagian); }
}

// Data generik hardcode (sama di semua domain T1 - dikonfirmasi user)
$genericGames = ['Naga Emas Beruntung','Harta Karun Kerajaan','Roda Fortuna Ajaib','Kembang Api Kemenangan','Singa Perkasa Jaya'];
$genericProviders = ['Pragmatic Play','PG Soft','Habanero','Microgaming','Playtech','Evolution','JILI','CQ9'];
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="<?= h($faviconSrc) ?>">
<title><?= h($page['title']) ?></title>
<meta name="title" content="<?= h($page['title']) ?>">
<meta name="description" content="<?= h($page['meta_description']) ?>">
<meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow' ?>">
<?php if (!empty($gscToken)): ?><meta name="google-site-verification" content="<?= h($gscToken) ?>" /><?php endif; ?>
<link rel="canonical" href="<?= h($page['canonical_url']) ?>">
<?php if (!empty($page['amp_url']) && $page['amp_url'] !== '#'): ?>
<link rel="amphtml" href="<?= h($page['amp_url']) ?>">
<link rel="alternate" hreflang="id-id" href="<?= h($page['amp_url']) ?>">
<link rel="alternate" href="<?= h($page['amp_url']) ?>">
<link rel="alternate" hreflang="id" href="<?= h($page['amp_url']) ?>">
<link rel="alternate" hreflang="en" href="<?= h($page['amp_url']) ?>">
<link rel="alternate" hreflang="x-default" href="<?= h($page['amp_url']) ?>">
<?php endif; ?>
<meta property="og:type" content="<?= $isHomepage ? 'website' : 'article' ?>">
<meta property="og:url" content="<?= h($page['canonical_url']) ?>">
<meta property="og:title" content="<?= h($page['title']) ?>">
<meta property="og:description" content="<?= h($page['meta_description']) ?>">
<meta property="og:image" content="<?= h($ogAbs) ?>">
<meta property="twitter:card" content="summary_large_image">

<script type="application/ld+json">
{
"@context":"https://schema.org",
"@graph":[
{
"@type":"Article",
"headline":<?= json_encode($page['h1'], JSON_UNESCAPED_UNICODE) ?>,
"description":<?= json_encode($page['meta_description'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
"image":<?= json_encode($ogAbs, JSON_UNESCAPED_UNICODE) ?>,
"datePublished":<?= json_encode($dateMod, JSON_UNESCAPED_UNICODE) ?>,
"dateModified":<?= json_encode($dateMod, JSON_UNESCAPED_UNICODE) ?>,
"inLanguage":"id-ID",
"author":{"@type":"Organization","name":<?= json_encode($domainName, JSON_UNESCAPED_UNICODE) ?>,"url":<?= json_encode($origin . '/', JSON_UNESCAPED_UNICODE) ?>},
"publisher":{"@type":"Organization","name":<?= json_encode($domainName, JSON_UNESCAPED_UNICODE) ?>,"url":<?= json_encode($origin . '/', JSON_UNESCAPED_UNICODE) ?>,"logo":{"@type":"ImageObject","url":<?= json_encode($origin . $logoSrc, JSON_UNESCAPED_UNICODE) ?>}},
"mainEntityOfPage":{"@type":"WebPage","@id":<?= json_encode($page['canonical_url'], JSON_UNESCAPED_UNICODE) ?>}
},
{
"@type":"BreadcrumbList",
"itemListElement":[
{"@type":"ListItem","position":1,"name":"Beranda","item":<?= json_encode($origin . '/', JSON_UNESCAPED_UNICODE) ?>}<?php if (!$isHomepage): ?>,
{"@type":"ListItem","position":2,"name":<?= json_encode($page['h1'], JSON_UNESCAPED_UNICODE) ?>,"item":<?= json_encode($page['canonical_url'], JSON_UNESCAPED_UNICODE) ?>}<?php endif; ?>

]
}<?php if ($isHomepage): ?>,
{"@type":"WebSite","name":<?= json_encode($page['title'], JSON_UNESCAPED_UNICODE) ?>,"url":<?= json_encode($origin . '/', JSON_UNESCAPED_UNICODE) ?>}<?php endif; ?>

]
}
</script>

<?php if ($isHomepage): ?>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
  {"@type":"Question","name":"Bagaimana cara daftar akun di <?= h($domainName) ?>?","acceptedAnswer":{"@type":"Answer","text":"Klik tombol Daftar, isi data diri singkat, lalu verifikasi lewat nomor HP atau email. Prosesnya cuma butuh beberapa menit."}},
  {"@type":"Question","name":"Berapa minimal deposit di <?= h($domainName) ?>?","acceptedAnswer":{"@type":"Answer","text":"Deposit minimal mulai dari Rp 10.000, mendukung berbagai metode pembayaran populer dan diproses otomatis dalam hitungan detik."}},
  {"@type":"Question","name":"Apakah <?= h($domainName) ?> aman dan terpercaya?","acceptedAnswer":{"@type":"Answer","text":"Kami menerapkan enkripsi data standar tinggi dan sistem monitoring 24/7 untuk melindungi setiap transaksi dan data pribadi member."}},
  {"@type":"Question","name":"Apakah ada layanan CS 24 jam?","acceptedAnswer":{"@type":"Answer","text":"Ya. Tim CS kami siap membantu lewat WhatsApp dan Telegram kapan saja, setiap hari tanpa libur."}},
  {"@type":"Question","name":"Bagaimana proses penarikan dana?","acceptedAnswer":{"@type":"Answer","text":"Penarikan diproses otomatis dan biasanya selesai dalam hitungan menit setelah permintaan disetujui sistem."}}
]}
</script>
<?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Inter','system-ui','sans-serif'] },
      colors: {
        'brand-primary': '#ff4757',
        'brand-secondary': '#eef1f6',
        'brand-accent': '#ffc107',
        'bg-main': '#0b0e14',
        'bg-surface': '#111623',
        'bg-card': '#171d2b',
        'text-main': '#eef1f6',
        'text-muted': '#98a2b3',
        'text-onDark': '#eef1f6',
        'border': 'rgba(255,71,87,0.18)',
        'warning': '#ffc107',
      },
      fontSize: {
        'heading-1': ['1.75rem', { fontWeight: '800' }],
        'heading-2': ['1.15rem', { fontWeight: '700' }],
        'body-small': ['0.8rem', {}],
      },
      boxShadow: {
        soft: '0 8px 24px rgba(0,0,0,.25)',
        glow: '0 0 24px rgba(255,71,87,.35)',
      },
    }
  }
}
</script>
<style>
  .scrollbar-hide::-webkit-scrollbar{display:none}
  .scrollbar-hide{-ms-overflow-style:none;scrollbar-width:none}
  body{font-family:'Inter',system-ui,sans-serif}
</style>
</head>
<body class="bg-bg-main text-text-main font-sans">

<header class="bg-bg-surface/90 backdrop-blur-lg border-b border-border sticky top-0 z-50">
  <div class="max-w-[1200px] mx-auto flex flex-col px-4">
    <div class="h-16 flex items-center justify-between gap-3">
      <div class="flex-shrink-0">
        <a href="/" class="flex items-center">
          <img src="<?= h($logoSrc) ?>" alt="<?= h($domainName) ?>" width="180" height="58" class="h-9 md:h-12 w-auto">
        </a>
      </div>
      <div class="flex items-center gap-1.5 md:gap-2">
        <a href="<?= h($ctaDaftarUrl) ?>" class="h-8 md:h-9 px-3 md:px-4 flex items-center gap-1 bg-brand-primary text-white text-[12px] md:text-sm font-bold rounded-lg hover:bg-brand-primary/90 transition-all tracking-wide whitespace-nowrap">Daftar</a>
        <a href="<?= h($ctaLoginUrl) ?>" class="h-8 md:h-9 px-3 md:px-4 flex items-center gap-1 bg-white/10 text-brand-accent text-[12px] md:text-sm font-bold rounded-lg border border-white/10 hover:bg-white/20 transition-all whitespace-nowrap">Masuk</a>
      </div>
    </div>
  </div>
<?php if (count($menuHeader) > 0): ?>
<nav class="bg-bg-surface/60 border-t border-border" aria-label="Navigasi utama">
  <div class="max-w-[1200px] mx-auto px-4 flex items-center gap-1 overflow-x-auto scrollbar-hide">
    <a href="/" class="min-h-[44px] flex items-center px-3 text-[12px] md:text-sm font-semibold whitespace-nowrap rounded-md text-text-onDark/70 hover:text-brand-accent hover:bg-white/5 transition-all<?= $id === 'homepage' ? ' is-active' : '' ?>"<?= $id === 'homepage' ? ' aria-current="page"' : '' ?>>Beranda</a>
    <?php foreach ($menuHeader as $slug => $label): ?>
    <a href="/<?= h(rawurlencode($slug)) ?>" class="min-h-[44px] flex items-center px-3 text-[12px] md:text-sm font-semibold whitespace-nowrap rounded-md text-text-onDark/70 hover:text-brand-accent hover:bg-white/5 transition-all<?= $id === $slug ? ' is-active' : '' ?>"<?= $id === $slug ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>
</header>

<main class="max-w-[1200px] mx-auto px-4 py-8">

<?php if (!$isHomepage): ?>
  <div class="flex flex-wrap items-center justify-between gap-4 py-2 border-b border-border mb-4">
    <nav class="flex items-center text-xs sm:text-sm overflow-x-auto whitespace-nowrap scrollbar-hide">
      <a href="/" class="text-text-muted hover:text-brand-primary transition-colors">Beranda</a>
      <?php if (!$isHomepage): ?>
      <span class="mx-1 text-text-muted">/</span>
      <span class="text-text-main font-semibold"><?= h($page['h1']) ?></span>
      <?php endif; ?>
    </nav>
  </div>
<?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-[45%_1fr] gap-6 lg:gap-10 mt-4 md:mt-6 items-start">

    <!-- KOLOM KIRI: banner + rating breakdown -->
    <aside class="lg:sticky lg:top-[140px] space-y-6 md:space-y-8">
      <?php if (!empty($heroImg)): ?>
      <div class="relative bg-brand-primary/5 rounded-xl border border-border p-1 overflow-hidden min-h-[300px] sm:min-h-[420px] shadow-soft">
        <div class="rounded-lg overflow-hidden bg-bg-card h-full">
          <img src="<?= h($heroImg) ?>" alt="<?= h($domainName) ?>" width="728" height="240" fetchpriority="high" loading="eager" decoding="async" class="w-full h-auto object-contain">
          <div class="absolute top-0 left-0">
            <div class="bg-brand-primary text-white text-xs font-black uppercase tracking-widest px-5 py-1.5 rounded-br-lg shadow-glow">Promosi</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="hidden lg:block">
        <div class="relative bg-bg-card rounded-lg border border-border p-6 overflow-hidden shadow-soft">
          <h2 class="text-heading-2 mb-5 flex items-center gap-2 text-brand-accent">Ulasan Member</h2>
          <div class="flex items-center gap-6">
            <div class="text-center">
              <div class="text-[44px] font-black text-text-onDark leading-none">4.9</div>
              <div class="text-yellow-400 flex justify-center mt-1.5 gap-0.5">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
              <div class="text-[10px] text-text-onDark/50 mt-2 uppercase tracking-widest font-bold">10.000+ ulasan</div>
            </div>
            <div class="flex-1 space-y-2">
              <?php foreach (['5'=>100,'4'=>0,'3'=>0,'2'=>0,'1'=>0] as $star => $pct): ?>
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-text-onDark/40 w-3"><?= $star ?></span>
                <div class="flex-1 h-2.5 bg-black/40 rounded-md overflow-hidden">
                  <div class="h-full bg-brand-primary rounded-md" style="width:<?= $pct ?>%"></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- KOLOM KANAN: konten utama -->
    <section class="space-y-6 md:space-y-8">
      <div class="relative bg-bg-card rounded-xl border border-border p-5 md:p-8 shadow-soft overflow-hidden">
        <div class="space-y-5">

          <div class="flex flex-wrap gap-2">
            <span class="border border-brand-primary/40 text-brand-accent text-[11px] font-bold px-2.5 py-1 rounded-md bg-brand-primary/5">Terpercaya</span>
            <span class="border border-green-500/40 text-green-400 text-[11px] font-bold px-2.5 py-1 rounded-md bg-green-500/5">24/7 Online</span>
            <span class="border border-warning/40 text-warning text-[11px] font-bold px-2.5 py-1 rounded-md bg-warning/5">Kualitas Tinggi</span>
          </div>

          <h1 class="text-heading-1 md:text-[30px] font-bold text-brand-secondary leading-tight"><?= h($page['h1']) ?></h1>

          <div class="max-w-none text-text-onDark/80 [&_p]:text-justify [&_p]:hyphens-auto [&_p]:mb-4 [&_p]:leading-relaxed [&_h2]:text-brand-accent [&_h2]:text-xl [&_h2]:font-bold [&_h2]:mt-6 [&_h2]:mb-2 [&_h3]:text-base [&_h3]:font-bold [&_h3]:mt-4 [&_h3]:mb-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mb-4 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mb-4 [&_li]:mb-1.5 [&_li]:text-justify [&_a]:text-brand-accent [&_a]:underline">
            <?= $isiArtikel ?>
          </div>

          <div class="flex items-center gap-2">
            <div class="flex text-yellow-400">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <span class="text-body-small font-semibold text-brand-accent">10.000+ ulasan</span>
          </div>

          <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-text-muted font-mono bg-bg-card/30 px-4 py-2.5 rounded-lg border border-border/60 mb-6">
            <div class="flex items-center gap-1.5"><span>Diperbarui: <time class="font-bold text-text-main"><?= h($page['lastmod'] ?? '') ?></time></span></div>
            <div class="hidden sm:block text-border/40">|</div>
            <div class="flex items-center gap-1.5">
              <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
              </span>
              <span class="text-green-400 font-bold uppercase text-[10px] tracking-wider">Server Aktif 24/7</span>
          </div>

          <div class="relative p-4 md:p-5 bg-bg-card rounded-xl border border-border shadow-soft overflow-hidden">
            <div class="text-[9px] md:text-[10px] font-bold text-text-onDark/50 uppercase tracking-widest mb-1">Deposit Minimal</div>
            <div class="flex items-end gap-2 md:gap-3">
              <div class="text-2xl md:text-[34px] font-black text-text-onDark leading-none">Rp 10.000</div>
              <span class="bg-brand-primary text-white text-[9px] md:text-[10px] font-black px-1.5 md:px-2 py-0.5 rounded mb-0.5">Promo</span>
            </div>
            <p class="text-[10px] md:text-xs text-text-onDark/40 mt-2">Diproses otomatis, aman, tanpa biaya tersembunyi.</p>
          </div>

          <?php if ($isHomepage): ?>
          <div class="grid grid-cols-5 gap-1.5 md:gap-2">
            <?php foreach ($genericGames as $i => $g): ?>
            <a href="<?= h($ctaDaftarUrl) ?>" target="_blank" rel="noopener" class="aspect-square rounded-md md:rounded-lg overflow-hidden border border-border bg-bg-surface group cursor-pointer shadow-soft relative">
              <img src="/img/asset-game-<?= $i+1 ?>.jpg" alt="<?= h($g) ?>" width="320" height="320" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
              <span class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[8px] md:text-[9px] font-bold text-center py-1 px-0.5 leading-tight"><?= h($g) ?></span>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <a href="<?= h($ctaDaftarUrl) ?>" target="_blank" rel="noopener" class="block w-full text-center bg-brand-primary hover:bg-brand-primary/90 text-white font-black text-base md:text-lg py-4 rounded-xl shadow-glow uppercase tracking-wide transition-all">Daftar Sekarang</a>

        </div>
      </div>

      <?php if ($isHomepage): ?>
      <div class="mt-8 space-y-6">
        <div class="flex items-center gap-3">
          <div class="h-px flex-1 bg-border"></div>
          <h2 class="text-sm font-bold uppercase tracking-widest text-brand-accent">Provider Game</h2>
          <div class="h-px flex-1 bg-border"></div>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-8 gap-2 md:gap-3">
          <?php foreach ($genericProviders as $p): ?>
          <div class="aspect-[3/2] bg-bg-card border border-border rounded-lg p-1.5 md:p-2 flex items-center justify-center hover:border-brand-primary/30 hover:shadow-glow transition-all">
            <span class="text-[9px] md:text-[10px] font-bold text-text-onDark/50 text-center"><?= h($p) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="mt-8 space-y-4">
        <h2 class="text-sm font-bold uppercase tracking-widest text-brand-accent">Pertanyaan yang Sering Diajukan</h2>
        <div class="space-y-2">
          <?php
          $faqs = [
            ['q' => 'Bagaimana cara daftar akun di ' . $domainName . '?', 'a' => 'Klik tombol "Daftar", isi data diri singkat, lalu verifikasi lewat nomor HP atau email. Prosesnya cuma butuh beberapa menit.'],
            ['q' => 'Berapa minimal deposit di ' . $domainName . '?', 'a' => 'Deposit minimal mulai dari Rp 10.000, mendukung berbagai metode pembayaran populer dan diproses otomatis dalam hitungan detik.'],
            ['q' => $domainName . ' aman dan terpercaya tidak?', 'a' => 'Kami menerapkan enkripsi data standar tinggi dan sistem monitoring 24/7 untuk melindungi setiap transaksi dan data pribadi member.'],
            ['q' => 'Apakah ada layanan CS 24 jam?', 'a' => 'Ada. Tim CS kami siap membantu lewat WhatsApp dan Telegram kapan saja, setiap hari tanpa libur.'],
            ['q' => 'Bagaimana proses penarikan dana?', 'a' => 'Penarikan diproses otomatis, biasanya selesai dalam hitungan menit setelah permintaan disetujui sistem.'],
          ];
          foreach ($faqs as $faq): ?>
          <details class="group bg-bg-card border border-border rounded-lg px-4 py-3">
            <summary class="cursor-pointer list-none flex items-center justify-between gap-3 text-sm font-semibold text-text-onDark">
              <span><?= h($faq['q']) ?></span>
              <span class="text-brand-accent group-open:rotate-45 transition-transform text-lg leading-none">+</span>
            </summary>
            <p class="mt-2 text-sm text-text-onDark/60 leading-relaxed"><?= h($faq['a']) ?></p>
          </details>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if (count($bacaanLain) > 0): ?>
      <div class="mt-8 space-y-4">
        <h2 class="text-sm font-bold uppercase tracking-widest text-brand-accent">Artikel Terbaru</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <?php foreach ($bacaanLain as $slug => $item): ?>
          <a href="/<?= h($slug) ?>" class="bg-bg-card border border-border rounded-lg overflow-hidden hover:border-brand-primary/40 transition-all">
            <img src="<?= h($item['image_src'] ?? '') ?>" alt="<?= h($item['title']) ?>" width="320" height="240" loading="lazy" decoding="async" class="aspect-[4/3] object-cover w-full">
            <span class="block p-3 text-xs font-semibold text-text-onDark"><?= h($item['title']) ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>

    </section>
  </div>
</main>

<footer class="bg-bg-surface border-t border-white/5 mt-16">
  <div class="max-w-[1200px] mx-auto px-4 py-10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="space-y-4">
        <img src="<?= h($logoSrc) ?>" alt="<?= h($domainName) ?>" width="180" height="58" loading="lazy" decoding="async" class="h-10 w-auto">
        <p class="text-sm text-text-onDark/70 leading-relaxed"><?= h($domainName) ?> adalah platform hiburan online terpercaya dengan RTP Live tertinggi, menghadirkan pengalaman gaming yang aman dan adil bagi para pemain.</p>
      </div>
      <div class="space-y-4 md:text-right">
        <h3 class="text-sm font-bold text-text-onDark uppercase tracking-wider">Hubungi Kami</h3>
        <div class="space-y-3 flex flex-col md:items-end">
          <a href="<?= h($whatsappCta) ?>" target="_blank" rel="noopener" class="flex items-center gap-3 text-sm text-text-onDark/70 hover:text-brand-accent transition-colors">
            <span class="w-9 h-9 rounded-lg bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-accent">W</span>
            <span>WhatsApp</span>
          </a>
          <a href="<?= h($telegramCta) ?>" target="_blank" rel="noopener" class="flex items-center gap-3 text-sm text-text-onDark/70 hover:text-brand-accent transition-colors">
            <span class="w-9 h-9 rounded-lg bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-accent">T</span>
            <span>Telegram</span>
          </a>
        </div>
      </div>
    </div>
    <nav class="border-t border-white/5 mt-8 pt-6 flex flex-wrap justify-center gap-2" aria-label="Halaman informasi">
      <a href="/" class="inline-flex items-center min-h-[44px] px-4 text-xs font-semibold text-text-onDark/70 hover:text-brand-accent border border-border rounded-lg bg-bg-card transition-all">Beranda</a>
      <?php foreach ($menuHalaman as $slug => $label): ?>
      <a href="/<?= h($slug) ?>" class="inline-flex items-center min-h-[44px] px-4 text-xs font-semibold text-text-onDark/70 hover:text-brand-accent border border-border rounded-lg bg-bg-card transition-all"><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="border-t border-white/5 mt-8 pt-6 text-center">
      <p class="text-xs text-text-onDark/40">&copy; <?= date('Y') ?> <span class="text-brand-accent font-bold"><?= h($domainName) ?></span>. Hak Cipta Dilindungi</p>
    </div>
    <div class="border-t border-white/5 pt-4 mt-4 text-center max-w-[800px] mx-auto">
      <p class="text-[11px] text-text-onDark/40 leading-relaxed"><strong>Peringatan Penting:</strong> Situs ini hanya ditujukan untuk pengguna berusia di atas 18 tahun. Mohon bermain secara bertanggung jawab dan pahami risiko finansial sebelum berpartisipasi. Ini adalah konten hiburan, tidak menjamin kemenangan.</p>
    </div>
  </div>
</footer>

</body>
</html>
