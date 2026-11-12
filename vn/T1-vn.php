<?php
/* T1-vn: versi region Vietnam dari T1 moneylui (teks antarmuka bahasa Vietnam). Dibuat 12 Nov 2026. */
?><?php
/**
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
    exit('Không tìm thấy data.json.');
}

$data = json_decode(file_get_contents($file), true);
$page = isset($data[$id]) ? $data[$id] : null;

if (!$page) {
    http_response_code(404);
    exit('<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="robots" content="noindex,nofollow"><title>404</title></head><body><h1>404</h1><p><a href="/">Về Trang chủ</a></p></body></html>');
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
$lang    = $page['lang'] ?? 'vi';
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
    'no-hu'           => 'Nổ hũ',
    'sportsbook'      => 'Thể thao',
    'the-thao'        => 'Thể thao',
    'casino'          => 'Casino',
    'togel'           => 'Xổ số',
    'xo-so'           => 'Xổ số',
    // Halaman informasi: cukup tampil di footer.
    'about-us'        => 'Giới thiệu',
    've-chung-toi'    => 'Giới thiệu',
    'gioi-thieu'      => 'Giới thiệu',
    'tentang-kami'    => 'Giới thiệu',
    'contact-us'      => 'Liên hệ',
    'lien-he'         => 'Liên hệ',
    'kontak'          => 'Liên hệ',
    'link-vao'        => 'Link vào',
    'link-alternatif' => 'Link vào',
    'huong-dan-dang-ky' => 'Cách đăng ký',
    'cach-dang-ky'    => 'Cách đăng ký',
    'dang-ky'         => 'Cách đăng ký',
    'cara-daftar'     => 'Cách đăng ký',
    'daftar'          => 'Cách đăng ký',
    'huong-dan-dang-nhap' => 'Cách đăng nhập',
    'cach-dang-nhap'  => 'Cách đăng nhập',
    'dang-nhap'       => 'Cách đăng nhập',
    'cara-masuk-akun' => 'Cách đăng nhập',
    'cara-masuk'      => 'Cách đăng nhập',
    'login'           => 'Cách đăng nhập',
    'mien-tru-trach-nhiem' => 'Miễn trừ trách nhiệm',
    'disclaimer'      => 'Miễn trừ trách nhiệm',
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
$labelHanyaFooter = ['Giới thiệu', 'Liên hệ', 'Miễn trừ trách nhiệm'];
/* HOKSIONG_SHORT_POST_MENU_V1: label navigasi pendek dari pola slug. */
$menuTetap = ['disclaimer'=>true,'contact-us'=>true,'about-us'=>true,'kontak'=>true,'kontak-kami'=>true,'tentang'=>true,'tentang-kami'=>true,'lien-he'=>true,'gioi-thieu'=>true,'ve-chung-toi'=>true,'mien-tru-trach-nhiem'=>true];
$polaLabelPendek = [
    'link-vao'=>'Link vào', 'link-alternatif'=>'Link vào', 'cara-masuk-akun'=>'Đăng nhập',
    'dang-ky'=>'Đăng ký', 'dang-nhap'=>'Đăng nhập', 'khuyen-mai'=>'Khuyến mãi',
    'the-thao'=>'Thể thao', 'xo-so'=>'Xổ số', 'no-hu'=>'Nổ hũ', 'ban-ca'=>'Bắn cá',
    'chinh-thuc'=>'Chính thức', 'viet-nam'=>'Việt Nam', 'tai-app'=>'Tải app',
    'nap-tien'=>'Nạp tiền', 'rut-tien'=>'Rút tiền', 'uy-tin'=>'Uy tín',
    'sportsbook'=>'Thể thao', 'cashback'=>'Hoàn tiền', 'indonesia'=>'Indonesia',
    'cara-daftar'=>'Đăng ký', 'daftar'=>'Đăng ký', 'login'=>'Đăng nhập',
    'akses'=>'Truy cập', 'resmi'=>'Chính thức', 'slot'=>'Slot', 'casino'=>'Casino',
    'togel'=>'Xổ số', 'idn'=>'IDN', 'asia'=>'Châu Á', 'agen'=>'Đại lý',
    'mobile'=>'Di động', 'promo'=>'Khuyến mãi', 'bonus'=>'Thưởng', 'layanan'=>'Dịch vụ',
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
// Hanya bila diapit spasi / batas teks (aturan sama dengan template lain), supaya
// teks polos halaman tetap sama dengan artikel: "Dora88," / "(Dora88)" / "Dora88x"
// tidak ditautkan, sehingga live gate tidak membaca "Dora88 ," saat tag dibuang.
$isiArtikel = (string)($page['content_html'] ?? '');
$namaSitus = !empty($homepage['keyword']) ? (string)$homepage['keyword'] : explode('.', $domainName)[0];
if ($namaSitus !== '' && $isiArtikel !== '') {
    $bagian = preg_split('/(<[^>]*>)/', $isiArtikel, -1, PREG_SPLIT_DELIM_CAPTURE);
    $sudah = false;
    $panjang = strlen($namaSitus);
    foreach ($bagian as $i => $b) {
        if ($sudah || $b === '' || $b[0] === '<') { continue; }
        $mulai = 0;
        while (($pos = stripos($b, $namaSitus, $mulai)) !== false) {
            $sebelum = $pos === 0 ? ' ' : $b[$pos - 1];
            $sesudah = ($pos + $panjang) >= strlen($b) ? ' ' : $b[$pos + $panjang];
            if (ctype_space($sebelum) && ctype_space($sesudah)) {
                $bagian[$i] = substr($b, 0, $pos) . '<a href="/">' . substr($b, $pos, $panjang) . '</a>' . substr($b, $pos + $panjang);
                $sudah = true;
                break;
            }
            $mulai = $pos + 1;
        }
    }
    if ($sudah) { $isiArtikel = implode('', $bagian); }
}

// Data generik hardcode (sama di semua domain T1 - dikonfirmasi user)
$genericGames = ['Rồng Vàng May Mắn','Kho Báu Hoàng Gia','Vòng Quay Thần Tài','Pháo Hoa Chiến Thắng','Sư Tử Dũng Mãnh'];
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
<link rel="alternate" hreflang="vi-vn" href="<?= h($page['amp_url']) ?>">
<link rel="alternate" href="<?= h($page['amp_url']) ?>">
<link rel="alternate" hreflang="vi" href="<?= h($page['amp_url']) ?>">
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
"inLanguage":"vi-VN",
"author":{"@type":"Organization","name":<?= json_encode($domainName, JSON_UNESCAPED_UNICODE) ?>,"url":<?= json_encode($origin . '/', JSON_UNESCAPED_UNICODE) ?>},
"publisher":{"@type":"Organization","name":<?= json_encode($domainName, JSON_UNESCAPED_UNICODE) ?>,"url":<?= json_encode($origin . '/', JSON_UNESCAPED_UNICODE) ?>,"logo":{"@type":"ImageObject","url":<?= json_encode($origin . $logoSrc, JSON_UNESCAPED_UNICODE) ?>}},
"mainEntityOfPage":{"@type":"WebPage","@id":<?= json_encode($page['canonical_url'], JSON_UNESCAPED_UNICODE) ?>}
},
{
"@type":"BreadcrumbList",
"itemListElement":[
{"@type":"ListItem","position":1,"name":"Trang chủ","item":<?= json_encode($origin . '/', JSON_UNESCAPED_UNICODE) ?>}<?php if (!$isHomepage): ?>,
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
  {"@type":"Question","name":"Làm thế nào để đăng ký tài khoản tại <?= h($domainName) ?>?","acceptedAnswer":{"@type":"Answer","text":"Nhấn nút Đăng ký, điền vài thông tin cá nhân, sau đó xác minh qua số điện thoại hoặc email. Chỉ mất vài phút là xong."}},
  {"@type":"Question","name":"Nạp tiền tối thiểu tại <?= h($domainName) ?> là bao nhiêu?","acceptedAnswer":{"@type":"Answer","text":"Nạp tối thiểu chỉ từ 50.000 VNĐ, hỗ trợ nhiều phương thức thanh toán phổ biến và được xử lý tự động trong vài giây."}},
  {"@type":"Question","name":"<?= h($domainName) ?> có an toàn và uy tín không?","acceptedAnswer":{"@type":"Answer","text":"Chúng tôi áp dụng mã hóa dữ liệu tiêu chuẩn cao cùng hệ thống giám sát 24/7 để bảo vệ mọi giao dịch và thông tin cá nhân của thành viên."}},
  {"@type":"Question","name":"Có hỗ trợ CSKH 24 giờ không?","acceptedAnswer":{"@type":"Answer","text":"Có. Đội ngũ CSKH luôn sẵn sàng hỗ trợ qua WhatsApp và Telegram bất cứ lúc nào, mỗi ngày không nghỉ."}},
  {"@type":"Question","name":"Quy trình rút tiền diễn ra như thế nào?","acceptedAnswer":{"@type":"Answer","text":"Lệnh rút được xử lý tự động và thường hoàn tất trong vài phút sau khi hệ thống phê duyệt."}}
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
        <a href="<?= h($ctaDaftarUrl) ?>" class="h-8 md:h-9 px-3 md:px-4 flex items-center gap-1 bg-brand-primary text-white text-[12px] md:text-sm font-bold rounded-lg hover:bg-brand-primary/90 transition-all tracking-wide whitespace-nowrap">Đăng ký</a>
        <a href="<?= h($ctaLoginUrl) ?>" class="h-8 md:h-9 px-3 md:px-4 flex items-center gap-1 bg-white/10 text-brand-accent text-[12px] md:text-sm font-bold rounded-lg border border-white/10 hover:bg-white/20 transition-all whitespace-nowrap">Đăng nhập</a>
      </div>
    </div>
  </div>
<?php if (count($menuHeader) > 0): ?>
<nav class="bg-bg-surface/60 border-t border-border" aria-label="Điều hướng chính">
  <div class="max-w-[1200px] mx-auto px-4 flex items-center gap-1 overflow-x-auto scrollbar-hide">
    <a href="/" class="min-h-[44px] flex items-center px-3 text-[12px] md:text-sm font-semibold whitespace-nowrap rounded-md text-text-onDark/70 hover:text-brand-accent hover:bg-white/5 transition-all<?= $id === 'homepage' ? ' is-active' : '' ?>"<?= $id === 'homepage' ? ' aria-current="page"' : '' ?>>Trang chủ</a>
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
      <a href="/" class="text-text-muted hover:text-brand-primary transition-colors">Trang chủ</a>
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
            <div class="bg-brand-primary text-white text-xs font-black uppercase tracking-widest px-5 py-1.5 rounded-br-lg shadow-glow">Khuyến mãi</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="hidden lg:block">
        <div class="relative bg-bg-card rounded-lg border border-border p-6 overflow-hidden shadow-soft">
          <h2 class="text-heading-2 mb-5 flex items-center gap-2 text-brand-accent">Đánh giá thành viên</h2>
          <div class="flex items-center gap-6">
            <div class="text-center">
              <div class="text-[44px] font-black text-text-onDark leading-none">4.9</div>
              <div class="text-yellow-400 flex justify-center mt-1.5 gap-0.5">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
              <div class="text-[10px] text-text-onDark/50 mt-2 uppercase tracking-widest font-bold">10.000+ đánh giá</div>
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
            <span class="border border-brand-primary/40 text-brand-accent text-[11px] font-bold px-2.5 py-1 rounded-md bg-brand-primary/5">Uy tín</span>
            <span class="border border-green-500/40 text-green-400 text-[11px] font-bold px-2.5 py-1 rounded-md bg-green-500/5">24/7 Online</span>
            <span class="border border-warning/40 text-warning text-[11px] font-bold px-2.5 py-1 rounded-md bg-warning/5">Chất lượng cao</span>
          </div>

          <h1 class="text-heading-1 md:text-[30px] font-bold text-brand-secondary leading-tight"><?= h($page['h1']) ?></h1>

          <div class="max-w-none text-text-onDark/80 [&_p]:text-justify [&_p]:hyphens-auto [&_p]:mb-4 [&_p]:leading-relaxed [&_h2]:text-brand-accent [&_h2]:text-xl [&_h2]:font-bold [&_h2]:mt-6 [&_h2]:mb-2 [&_h3]:text-base [&_h3]:font-bold [&_h3]:mt-4 [&_h3]:mb-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mb-4 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mb-4 [&_li]:mb-1.5 [&_li]:text-justify [&_a]:text-brand-accent [&_a]:underline">
            <?= $isiArtikel ?>
          </div>

          <div class="flex items-center gap-2">
            <div class="flex text-yellow-400">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <span class="text-body-small font-semibold text-brand-accent">10.000+ đánh giá</span>
          </div>

          <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-text-muted font-mono bg-bg-card/30 px-4 py-2.5 rounded-lg border border-border/60 mb-6">
            <div class="flex items-center gap-1.5"><span>Cập nhật: <time class="font-bold text-text-main"><?= h($page['lastmod'] ?? '') ?></time></span></div>
            <div class="hidden sm:block text-border/40">|</div>
            <div class="flex items-center gap-1.5">
              <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
              </span>
              <span class="text-green-400 font-bold uppercase text-[10px] tracking-wider">Máy chủ hoạt động 24/7</span>
          </div>

          <div class="relative p-4 md:p-5 bg-bg-card rounded-xl border border-border shadow-soft overflow-hidden">
            <div class="text-[9px] md:text-[10px] font-bold text-text-onDark/50 uppercase tracking-widest mb-1">Nạp tối thiểu</div>
            <div class="flex items-end gap-2 md:gap-3">
              <div class="text-2xl md:text-[34px] font-black text-text-onDark leading-none">50.000 VNĐ</div>
              <span class="bg-brand-primary text-white text-[9px] md:text-[10px] font-black px-1.5 md:px-2 py-0.5 rounded mb-0.5">Khuyến mãi</span>
            </div>
            <p class="text-[10px] md:text-xs text-text-onDark/40 mt-2">Xử lý tự động, an toàn, không phí ẩn.</p>
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

          <a href="<?= h($ctaDaftarUrl) ?>" target="_blank" rel="noopener" class="block w-full text-center bg-brand-primary hover:bg-brand-primary/90 text-white font-black text-base md:text-lg py-4 rounded-xl shadow-glow uppercase tracking-wide transition-all">Đăng ký ngay</a>

        </div>
      </div>

      <?php if ($isHomepage): ?>
      <div class="mt-8 space-y-6">
        <div class="flex items-center gap-3">
          <div class="h-px flex-1 bg-border"></div>
          <h2 class="text-sm font-bold uppercase tracking-widest text-brand-accent">Nhà cung cấp game</h2>
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
        <h2 class="text-sm font-bold uppercase tracking-widest text-brand-accent">Câu hỏi thường gặp</h2>
        <div class="space-y-2">
          <?php
          $faqs = [
            ['q' => 'Làm thế nào để đăng ký tài khoản tại ' . $domainName . '?', 'a' => 'Nhấn nút "Đăng ký", điền vài thông tin cá nhân, sau đó xác minh qua số điện thoại hoặc email. Chỉ mất vài phút là xong.'],
            ['q' => 'Nạp tiền tối thiểu tại ' . $domainName . ' là bao nhiêu?', 'a' => 'Nạp tối thiểu chỉ từ 50.000 VNĐ, hỗ trợ nhiều phương thức thanh toán phổ biến và được xử lý tự động trong vài giây.'],
            ['q' => $domainName . ' có an toàn và uy tín không?', 'a' => 'Chúng tôi áp dụng mã hóa dữ liệu tiêu chuẩn cao cùng hệ thống giám sát 24/7 để bảo vệ mọi giao dịch và thông tin cá nhân của thành viên.'],
            ['q' => 'Có hỗ trợ CSKH 24 giờ không?', 'a' => 'Có. Đội ngũ CSKH luôn sẵn sàng hỗ trợ qua WhatsApp và Telegram bất cứ lúc nào, mỗi ngày không nghỉ.'],
            ['q' => 'Quy trình rút tiền diễn ra như thế nào?', 'a' => 'Lệnh rút được xử lý tự động, thường hoàn tất trong vài phút sau khi hệ thống phê duyệt.'],
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
        <h2 class="text-sm font-bold uppercase tracking-widest text-brand-accent">Bài viết mới nhất</h2>
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
        <p class="text-sm text-text-onDark/70 leading-relaxed"><?= h($domainName) ?> là nền tảng giải trí trực tuyến uy tín với RTP Live cao nhất, mang đến trải nghiệm chơi game an toàn và công bằng cho người chơi.</p>
      </div>
      <div class="space-y-4 md:text-right">
        <h3 class="text-sm font-bold text-text-onDark uppercase tracking-wider">Liên hệ với chúng tôi</h3>
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
    <nav class="border-t border-white/5 mt-8 pt-6 flex flex-wrap justify-center gap-2" aria-label="Trang thông tin">
      <a href="/" class="inline-flex items-center min-h-[44px] px-4 text-xs font-semibold text-text-onDark/70 hover:text-brand-accent border border-border rounded-lg bg-bg-card transition-all">Trang chủ</a>
      <?php foreach ($menuHalaman as $slug => $label): ?>
      <a href="/<?= h($slug) ?>" class="inline-flex items-center min-h-[44px] px-4 text-xs font-semibold text-text-onDark/70 hover:text-brand-accent border border-border rounded-lg bg-bg-card transition-all"><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="border-t border-white/5 mt-8 pt-6 text-center">
      <p class="text-xs text-text-onDark/40">&copy; <?= date('Y') ?> <span class="text-brand-accent font-bold"><?= h($domainName) ?></span>. Bảo lưu mọi quyền.</p>
    </div>
    <div class="border-t border-white/5 pt-4 mt-4 text-center max-w-[800px] mx-auto">
      <p class="text-[11px] text-text-onDark/40 leading-relaxed"><strong>Cảnh báo quan trọng:</strong> Trang web này chỉ dành cho người dùng từ 18 tuổi trở lên. Vui lòng chơi có trách nhiệm và hiểu rõ rủi ro tài chính trước khi tham gia. Đây là nội dung giải trí, không đảm bảo chiến thắng.</p>
    </div>
  </div>
</footer>

</body>
</html>
