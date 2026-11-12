<?php
/* pagesdev-vn: versi region Vietnam dari pagesdev moneylui (teks antarmuka bahasa Vietnam). Dibuat 12 Nov 2026. */
?><?php
/**
 * ---------------------------------------------------------
 * Template pagesdev (Bimasakti / Hoksiong runtime).
 * Versi pemulihan: pagesdev_new.php.
 * Bagian yang dibuang dipulihkan dari pagesdev.html berdasarkan rekap.
 * Stylesheet, modal, chat, produk, footer, dan skrip sumber kembali disertakan.
 * Runtime, konten data.json, dan satu schema otomatis tetap dipertahankan.
 * Integrasi toko dan tautan eksternal tetap memerlukan layanan asalnya.
 * Membaca /data/data.json yang ditulis pipeline. Ganti template = ganti file ini.
 *
 * Kontrak data (sama dengan T1):
 *   per halaman : title, meta_description, h1, content_html, image_src,
 *                 canonical_url, amp_url, noindex, lang, lastmod, category, keyword
 *   homepage    : + logo_src, favicon_src, banner_src, cta_url, gsc_token (opsional)
 *
 * BANNER_TYPE disarankan SQUARE (galeri produk 1:1).
 * Gambar asset-game-1..5.jpg tetap diunggah pipeline tetapi tidak dipakai template ini.
 */

$id   = (isset($_GET['id']) && is_string($_GET['id']) && $_GET['id'] !== '') ? $_GET['id'] : 'homepage';
$file = __DIR__ . '/data/data.json';

if (!file_exists($file)) {
    http_response_code(500);
    exit('Không tìm thấy data.json.');
}

$data = json_decode(file_get_contents($file), true);
if (!is_array($data)) {
    http_response_code(500);
    exit('data.json không hợp lệ.');
}
$page = (isset($data[$id]) && is_array($data[$id])) ? $data[$id] : null;

if (!$page) {
    http_response_code(404);
    exit('<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="robots" content="noindex,nofollow"><title>404</title></head><body><h1>404</h1><p><a href="/">Về Trang chủ</a></p></body></html>');
}

function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }
function j($v) { return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
function judulKata($s) {
    $s = (string)$s;
    return function_exists('mb_convert_case') ? mb_convert_case($s, MB_CASE_TITLE, 'UTF-8') : ucwords($s);
}

$homepage   = (isset($data['homepage']) && is_array($data['homepage'])) ? $data['homepage'] : [];
$isHomepage = ($id === 'homepage') || (($page['category'] ?? '') === 'homepage');
$domainName = $_SERVER['HTTP_HOST'] ?? '';
$origin     = 'https://' . $domainName;

$logoSrc    = $homepage['logo_src']    ?? '/img/logo.png';
$faviconSrc = $homepage['favicon_src'] ?? '/img/favicon.png';
$bannerSrc  = $homepage['banner_src']  ?? ($homepage['image_src'] ?? '');

// Satu CTA global untuk semua tombol (Daftar, Masuk, BUKA AKSES, tab Akses).
$ctaUrl = $homepage['cta_url'] ?? ($homepage['cta_daftar_url'] ?? ($homepage['cta_login_url'] ?? '#'));
$ctaUrl = trim((string)$ctaUrl) !== '' ? trim((string)$ctaUrl) : '#';

$gscToken = $homepage['gsc_token'] ?? '';
$noindex  = !empty($page['noindex']);
$lang     = $page['lang'] ?? 'vi';
$ampUrl   = trim((string)($page['amp_url'] ?? ''));
$adaAmp   = $ampUrl !== '' && $ampUrl !== '#';

$heroImg = $isHomepage ? $bannerSrc : ($page['image_src'] ?? $bannerSrc);
$ogRel   = $page['image_src'] ?? $bannerSrc;
$ogAbs   = $ogRel ? ((strpos($ogRel, 'http') === 0) ? $ogRel : $origin . $ogRel) : '';

$lm = (string)($page['lastmod'] ?? '');
$dateMod = (preg_match('/^\d{4}-\d{2}-\d{2}/', $lm) ? substr($lm, 0, 10) : date('Y-m-d')) . 'T00:00:00+07:00';

// Nama situs = keyword beranda (fallback: label pertama domain).
$namaSitus = trim((string)($homepage['keyword'] ?? ''));
if ($namaSitus === '') { $namaSitus = explode('.', $domainName)[0]; }

// Semua post sesuai urutan data.json.
$allPosts = [];
foreach ($data as $slug => $item) {
    if (is_array($item) && ($item['category'] ?? '') === 'post') { $allPosts[(string)$slug] = $item; }
}
$labelPost = function ($slug, $item) { return judulKata(($item['keyword'] ?? '') !== '' ? $item['keyword'] : str_replace('-', ' ', $slug)); };
$menuHeader = array_slice($allPosts, 0, 3, true);
$postLain   = $allPosts;
if (!$isHomepage) { unset($postLain[$id]); }

// Tautan internal: kemunculan PERTAMA nama situs di content_html ditautkan ke beranda.
// Hanya bila diapit spasi / batas teks, supaya teks polos halaman tetap sama dengan artikel
// (misalnya "Cinta77," tidak berubah menjadi "Cinta77 ," saat tag dibuang).
$isiArtikel = (string)($page['content_html'] ?? '');
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

$tahun = date('Y');

// FAQ dari HTML sumber (apa adanya); "mawar88" diganti keyword beranda (tampil di semua halaman, schema FAQPage hanya di beranda).
$faq = array_map(function ($q) use ($namaSitus) { return [str_ireplace('mawar88', $namaSitus, $q[0]), str_ireplace('mawar88', $namaSitus, $q[1])]; }, [
    ['mawar88 là gì?', 'mawar88 là nền tảng cung cấp thông tin thể thao, sự kiện thưởng, khuyến mãi hấp dẫn cùng nhiều cập nhật về giải bóng đá thế giới 2026.'],
    ['Đăng nhập mawar88 như thế nào?', 'Người dùng chỉ cần vào trang đăng nhập chính thức, nhập thông tin tài khoản hợp lệ rồi sử dụng mọi tính năng có sẵn.'],
    ['Có thể truy cập mawar88 bằng điện thoại không?', 'Có, nền tảng đã được tối ưu cho thiết bị di động nên dùng được trên cả Android lẫn iPhone.'],
    ['Có khuyến mãi đặc biệt trong mùa bóng đá thế giới 2026 không?', 'Có nhiều khuyến mãi và sự kiện thưởng, có thể thay đổi theo từng giai đoạn và điều khoản áp dụng.'],
    ['Làm sao để nhận thông tin khuyến mãi mới nhất?', 'Hãy theo dõi trang khuyến mãi và kênh hỗ trợ chính thức; chúng tôi cũng hỗ trợ nạp tiền qua mã QR để thành viên nạp nhanh và chơi ngay không phải chờ lâu.'],
]);

// Detail kartu produk dari HTML asli, sesuai urutan post.
$relatedCardSnapshots = [
    ['discount' => <<<'PD_DISCOUNT_0'
<span class="mini-disc">-10%</span>
PD_DISCOUNT_0,
     'details' => <<<'PD_DETAILS_0'
                    <div class="spec-chips">I5 <span class="spec-divider">|</span> 16GB <span class="spec-divider">|</span> 512GB <span class="spec-divider">|</span> RTX3050</div>                    <span class="price">
                        <span class="final">18.555.000 VNĐ</span>
                                                  <span class="ori">28.888.000 VNĐ</span>
                                            </span>
                    <span class="sold">Đã bán 200+</span>                    <span class="stock-dot low" title="Số lượng có hạn"></span>
PD_DETAILS_0
    ],
    ['discount' => <<<'PD_DISCOUNT_1'
<span class="mini-disc">-18%</span>
PD_DISCOUNT_1,
     'details' => <<<'PD_DETAILS_1'
                    <div class="spec-chips">I7 <span class="spec-divider">|</span> 16GB <span class="spec-divider">|</span> 512GB <span class="spec-divider">|</span> RTX5050</div>                    <span class="price">
                        <span class="final">8.887.000 VNĐ</span>
                                                  <span class="ori">36.887.000 VNĐ</span>
                                            </span>
                    <span class="sold">Đã bán 20+</span>                    <span class="stock-dot out" title="Hết hàng"></span>
PD_DETAILS_1
    ],
    ['discount' => <<<'PD_DISCOUNT_2'
<span class="mini-disc">-1%</span>
PD_DISCOUNT_2,
     'details' => <<<'PD_DETAILS_2'
                    <div class="spec-chips">R7AI <span class="spec-divider">|</span> 16GB <span class="spec-divider">|</span> 1TB <span class="spec-divider">|</span> RTX5060</div>                    <span class="price">
                        <span class="final">5.600.000 VNĐ</span>
                                                  <span class="ori">6.600.000 VNĐ</span>
                                            </span>
                    <span class="sold">Đã bán 6</span>                    <span class="stock-dot ready" title="Còn hàng"></span>
PD_DETAILS_2
    ],
    ['discount' => <<<'PD_DISCOUNT_3'
<span class="mini-disc">-3%</span>
PD_DISCOUNT_3,
     'details' => <<<'PD_DETAILS_3'
                    <div class="spec-chips">R5AI <span class="spec-divider">|</span> 16GB <span class="spec-divider">|</span> 1TB <span class="spec-divider">|</span> RTX5060</div>                    <span class="price">
                        <span class="final">3.800.000 VNĐ</span>
                                                  <span class="ori">6.799.000 VNĐ</span>
                                            </span>
                    <span class="sold">Đã bán 5</span>                    <span class="stock-dot ready" title="Còn hàng"></span>
PD_DETAILS_3
    ],
    ['discount' => <<<'PD_DISCOUNT_4'
<span class="mini-disc">-25%</span>
PD_DISCOUNT_4,
     'details' => <<<'PD_DETAILS_4'
                    <div class="spec-chips">I7 <span class="spec-divider">|</span> 16GB <span class="spec-divider">|</span> 512GB <span class="spec-divider">|</span> RTX5050</div>                    <span class="price">
                        <span class="final">14.600.080 VNĐ</span>
                                                  <span class="ori">44.999.000 VNĐ</span>
                                            </span>
                    <span class="sold">Đã bán 6</span>                    <span class="stock-dot low" title="Số lượng có hạn"></span>
PD_DETAILS_4
    ],
    ['discount' => <<<'PD_DISCOUNT_5'
<span class="mini-disc">-5%</span>
PD_DISCOUNT_5,
     'details' => <<<'PD_DETAILS_5'
                    <div class="spec-chips">CORE5 <span class="spec-divider">|</span> 16GB <span class="spec-divider">|</span> 512GB <span class="spec-divider">|</span> RTX4050</div>                    <span class="price">
                        <span class="final">13.258.000 VNĐ</span>
                                                  <span class="ori">18.258.000 VNĐ</span>
                                            </span>
                                        <span class="stock-dot low" title="Số lượng có hạn"></span>
PD_DETAILS_5
    ],
];
$relatedCardSlots = array_flip(array_keys($allPosts));
?>


<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="<?= h($faviconSrc) ?>">
  <title><?= h($page['title'] ?? '') ?></title>
  <meta name="description" content="<?= h($page['meta_description'] ?? '') ?>">
  <meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow' ?>">
<?php if (!empty($gscToken)): ?>
  <meta name="google-site-verification" content="<?= h($gscToken) ?>">
<?php endif; ?>
  <link rel="canonical" href="<?= h($page['canonical_url'] ?? '') ?>">
<?php if ($adaAmp): ?>
  <link rel="amphtml" href="<?= h($ampUrl) ?>">
  <link rel="alternate" href="<?= h($ampUrl) ?>">
  <link rel="alternate" hreflang="vi" href="<?= h($ampUrl) ?>">
  <link rel="alternate" hreflang="en" href="<?= h($ampUrl) ?>">
  <link rel="alternate" hreflang="x-default" href="<?= h($ampUrl) ?>">
  <link rel="alternate" hreflang="vi-vn" href="<?= h($ampUrl) ?>">
<?php endif; ?>
  
  
  <script nonce="se3iyzEHMc9FQEB4wSyd0w==">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag("consent","default",{ad_storage:"denied",ad_user_data:"denied",ad_personalization:"denied",analytics_storage:"granted",wait_for_update:500});gtag("js",new Date);gtag("config","G-0LSM1YJSPZ",{send_page_view:!0,linker:{domains:["plazait.co.id","www.plazait.co.id"]}});window.ga4Ecomm={viewItem:function(e){if(!window.gtag)return;try{gtag("event","view_item",{currency:e.currency||"IDR",value:e.value||0,items:[{item_id:e.item_id||"",item_name:e.item_name||"",item_brand:e.item_brand||"",item_category:e.item_category||"",price:e.price||0,quantity:1}]})}catch(t){}},addToCart:function(e){if(!window.gtag)return;try{gtag("event","add_to_cart",{currency:e.currency||"IDR",value:(e.price||0)*(e.quantity||1),items:[{item_id:e.item_id||"",item_name:e.item_name||"",item_brand:e.item_brand||"",item_category:e.item_category||"",price:e.price||0,quantity:e.quantity||1}]})}catch(t){}}};</script>
  <meta property="og:locale" content="vi_VN">
  <meta property="og:site_name" content="<?= h($namaSitus) ?>">

  <meta property="og:type" content="product">
  <meta property="og:title" content="<?= h($page['title'] ?? '') ?>">
  <meta property="og:description" content="<?= h($page['meta_description'] ?? '') ?>">
  <meta property="og:url" content="<?= h($page['canonical_url'] ?? '') ?>">
  <meta property="og:image" content="<?= h($ogAbs) ?>">
  <meta property="og:image:alt" content="<?= h($page['title'] ?? '') ?>">
  <meta property="product:price:amount" content="5000">
  <meta property="product:price:currency" content="VND">
  <meta property="product:availability" content="out of stock">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($page['title'] ?? '') ?>">
  <meta name="twitter:description" content="<?= h($page['meta_description'] ?? '') ?>">
  <meta name="twitter:image" content="<?= h($ogAbs) ?>">

<script type="application/ld+json">
<?= j([
    '@context' => 'https://schema.org',
    '@graph' => array_values(array_filter([
        [
            '@type' => 'Article',
            'headline' => (string)($page['h1'] ?? ''),
            'description' => (string)($page['meta_description'] ?? ''),
            'image' => $ogAbs,
            'datePublished' => $dateMod,
            'dateModified' => $dateMod,
            'inLanguage' => 'vi-VN',
            'author' => ['@type' => 'Organization', 'name' => $domainName, 'url' => $origin . '/'],
            'publisher' => ['@type' => 'Organization', 'name' => $domainName, 'url' => $origin . '/',
                'logo' => ['@type' => 'ImageObject', 'url' => $origin . $logoSrc]],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => (string)($page['canonical_url'] ?? '')],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $isHomepage
                ? [['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => $origin . '/']]
                : [['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => $origin . '/'],
                   ['@type' => 'ListItem', 'position' => 2, 'name' => (string)($page['h1'] ?? ''), 'item' => (string)($page['canonical_url'] ?? '')]],
        ],
        $isHomepage ? ['@type' => 'WebSite', 'name' => (string)($page['title'] ?? ''), 'url' => $origin . '/'] : null,
        $isHomepage ? ['@type' => 'FAQPage', 'mainEntity' => array_map(function ($q) {
            return ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]];
        }, $faq)] : null,
    ])),
]) ?>

</script>
  <!-- User Session Data -->
    <meta name="csrf-token" content="9ce6e4a17f7cc189893df9e47789b13e98829450d516a0cae5ca7b5f43e5dadc">
  <meta name="user-logged-in" content="false">
  <meta name="user-id" content="0">
  
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

  <link rel="shortcut icon" href="<?= h($faviconSrc) ?>" type="image/x-icon">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/style.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/bottom-nav.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/search-suggest.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/product-detail.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/product-grid.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/site-notice.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/benchmark.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/product-filters.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/mega-menu-tiles.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/product-compare.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/product-card-actions.css">
<link rel="stylesheet" href="https://lancarterus.pages.dev/assets/css/modal.css">
            
</head>
<body>
<header class="topbar">
  <div class="container topbar-inner">
    <div class="topbar-left">
      <?= h($page['title'] ?? '') ?>

    </div>
    <div class="topbar-right">
      <a href="/"><?= h($namaSitus) ?></a>
      <a href="/">NHÀ CÁI BÓNG ĐÁ</a>
      <a href="<?= h($ctaUrl) ?>">Đăng ký</a>
      <a href="<?= h($ctaUrl) ?>">Đăng nhập</a>
    </div>
  </div>
</header>
<nav class="navbar">
  <nav class="navbar-logo" style="width: 100%;">
    <div class="container nav-inner" style="justify-items: center; justify-content: center;">
        <a href="/" class="logo-mobile">
          <img src="<?= h($logoSrc) ?>" alt="<?= h($namaSitus) ?>" height="40">
        </a>
    </div>
</nav>
  <div class="container nav-inner">
    <a href="/" class="logo">
      <img src="<?= h($logoSrc) ?>" alt="<?= h($namaSitus) ?>" height="40">
    </a>

    
    <form class="search" method="get" action="#">
      <input type="hidden" name="page" value="produk">
      
      <!-- Search Icon (Desktop) -->
      <button type="submit" class="search-icon-btn" id="searchBtn" aria-label="Tìm kiếm">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/>
          <path d="m21 21-4.35-4.35"/>
        </svg>
      </button>
      
      <input type="text" name="q" id="searchInput" placeholder="Tìm <?= h($namaSitus) ?> trên Google - Nhà cái bóng đá chính thức World Cup 2026"
             value="" autocomplete="off"> 
      
      <div class="search-suggest" id="searchSuggest" hidden></div>
    </form>

    <div class="nav-actions">
      <!--<button class="icon-btn" id="notifBtn" title="Thông báo"></button>-->
      <button class="icon-btn cart-indicator" id="cartBtn" title="Giỏ hàng">
        🛒 <span id="cartCount" class="badge" data-role="cart-badge" aria-live="polite">0</span>
      </button>
      
      
        <span id="compareCount" class="badge" data-role="compare-badge" aria-live="polite">0</span>
      </button>
      
      <!-- Dynamic login/logout button -->
            <button class="login-btn" id="loginBtn" aria-label="Đăng nhập">
        <span class="login-text">Đăng nhập</span>
        <span class="login-ico" aria-hidden="true">😊</span>
      </button>
          </div>
  </div>

  <div class="container" style="display: flex; align-items: center;">
      

      <div class="mega-cats-wrapper">
        <div class="mega-cats container" id="megaCatsScroll" style="padding-left: 5px !important;">
                      <a class="dynamic-post-menu<?= $isHomepage ? ' active' : '' ?>" href="/">Trang chủ</a><?php foreach ($allPosts as $slug => $item): ?><a class="dynamic-post-menu<?= (!$isHomepage && (string)$slug === (string)$id) ? ' active' : '' ?>" href="/<?= h($slug) ?>"><?= h($labelPost($slug, $item)) ?></a><?php endforeach; ?>

                  </div>
        <div class="mega-cats-fade" id="megaCatsFade"></div>
      </div>
  </div>
</nav>

<!-- Panel Mega Menu - Outside navbar for full-screen -->
<div class="mm2-panel" data-mm2="panel" role="dialog" aria-label="Danh mục">
  <div class="mm2-view is-active" data-mm2="top"></div>
  <div class="mm2-view" data-mm2="detail"></div>
</div>

<!-- Backdrop - Outside navbar for full-screen -->
<div class="mm2-backdrop" data-mm2="backdrop"></div>

<!-- Mobile Bottom Navigation -->
<nav class="bottom-nav">
  <div class="bottom-nav-inner">
    <!-- Home -->
    <a href="/" class="bottom-nav-item" id="bottomHomeBtn" data-page="home">
      <div class="bottom-nav-item-icon">
        <!-- Outlined icon (default) -->
        <svg class="icon-outlined" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <!-- Filled icon (active) -->
        <svg class="icon-filled" viewBox="0 0 24 24" fill="currentColor">
          <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 010 1.06l-.03.03a.75.75 0 01-1.06 0l-8.16-8.16-8.16 8.16a.75.75 0 01-1.06 0l-.03-.03a.75.75 0 010-1.06l8.69-8.69z"/>
          <path d="M12 5.5l-7.5 7.5v8.25c0 .414.336.75.75.75h3.75v-6h6v6h3.75a.75.75 0 00.75-.75V13l-7.5-7.5z"/>
        </svg>
      </div>
      <span class="bottom-nav-item-label">Trang chủ</span>
    </a>

    <!-- Kategori -->
    <button class="bottom-nav-item" id="bottomCategoryBtn" data-mm2="trigger" aria-label="Danh mục">
      <div class="bottom-nav-item-icon">
        <!-- Outlined icon (default) -->
        <svg class="icon-outlined" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
        <!-- Filled icon (active) -->
        <svg class="icon-filled" viewBox="0 0 24 24" fill="currentColor">
          <path d="M3 5.25A.75.75 0 013.75 4.5h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 5.25zM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 12zm0 6.75a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z"/>
        </svg>
      </div>
      <span class="bottom-nav-item-label">Danh mục</span>
    </button>

    <!-- Keranjang -->
    <button class="bottom-nav-item" id="bottomCartBtn">
      <div class="bottom-nav-item-icon">
        <!-- Outlined icon (default) -->
        <svg class="icon-outlined" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"/>
          <circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <!-- Filled icon (active) -->
        <svg class="icon-filled" viewBox="0 0 24 24" fill="currentColor">
          <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"/>
        </svg>
        <span class="bottom-nav-item-badge" id="bottomCartCount">0</span>
      </div>
      <span class="bottom-nav-item-label">Giỏ hàng</span>
    </button>

    <!-- Compare -->
    <button class="bottom-nav-item" id="bottomCompareBtn">
      <div class="bottom-nav-item-icon">
        <!-- Outlined icon (default) -->
        <svg class="icon-outlined" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="17 11 21 7 17 3"/>
          <path d="M21 7H9"/>
          <polyline points="7 21 3 17 7 13"/>
          <path d="M15 17H3"/>
        </svg>
        <!-- Filled icon (active) -->
        <svg class="icon-filled" viewBox="0 0 24 24" fill="currentColor">
          <path d="M21.75 6.75a.75.75 0 00-.75-.75H9a.75.75 0 000 1.5h10.19L15.22 11.47a.75.75 0 001.06 1.06l4.5-4.5a.75.75 0 00.22-.53.75.75 0 00-.22-.53l-.03-.03zM8.78 12.47L4.28 16.97a.75.75 0 000 1.06l4.5 4.5a.75.75 0 001.06-1.06L6.06 18H15a.75.75 0 000-1.5H4.81l3.97-3.97a.75.75 0 00-1.06-1.06z"/>
        </svg>
        <span class="bottom-nav-item-badge" id="bottomCompareCount" style="display:none;">0</span>
      </div>
      <span class="bottom-nav-item-label">So sánh</span>
    </button>

    <!-- Chat - Always show, check login in JavaScript -->
    <button class="bottom-nav-item" id="bottomChatBtn" data-page="chat">
      <div class="bottom-nav-item-icon">
        <!-- Outlined icon (default) -->
        <svg class="icon-outlined" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" opacity="0.85">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <!-- Filled icon (active) -->
        <svg class="icon-filled" viewBox="0 0 24 24" fill="currentColor">
          <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
        </svg>
        <span class="bottom-nav-item-badge" id="bottomChatCount" style="display:none;">0</span>
      </div>
      <span class="bottom-nav-item-label">Trò chuyện</span>
    </button>

    <!-- Akun -->
        <button class="bottom-nav-item" id="bottomLoginBtn">
      <div class="bottom-nav-item-icon">
        <!-- Outlined icon (default) -->
        <svg class="icon-outlined" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        <!-- Filled icon (active) -->
        <svg class="icon-filled" viewBox="0 0 24 24" fill="currentColor">
          <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/>
        </svg>
      </div>
      <span class="bottom-nav-item-label">Đăng nhập</span>
    </button>
        </svg>
      </div>
    </button>
      </div>
</nav>

<!-- Mobile User Menu Bottom Sheet -->

<script nonce="se3iyzEHMc9FQEB4wSyd0w==">
(function(){
  const btn = document.getElementById('megaMoreBtn');
  const dd  = document.getElementById('megaMoreDropdown');
  if(!btn || !dd) return;

  function openDD(){
    dd.hidden = false;
    btn.setAttribute('aria-expanded','true');
    document.addEventListener('click', outside, {capture:true});
    document.addEventListener('keydown', esc);
  }
  function closeDD(){
    if(dd.hidden) return;
    dd.hidden = true;
    btn.setAttribute('aria-expanded','false');
    document.removeEventListener('click', outside, {capture:true});
    document.removeEventListener('keydown', esc);
  }
  function toggle(){ dd.hidden ? openDD() : closeDD(); }
  function outside(e){
    if(!dd.contains(e.target) && e.target !== btn){
      closeDD();
    }
  }
  function esc(e){
    if(e.key === 'Escape'){
      closeDD();
      btn.focus();
    }
  }
  btn.addEventListener('click', toggle);
})();

// ========== HORIZONTAL SCROLL FADE INDICATOR ==========
(function(){
  const megaCats = document.getElementById('megaCatsScroll');
  const fade = document.getElementById('megaCatsFade');
  if (!megaCats || !fade) return;

  function updateFade() {
    const scrollLeft = megaCats.scrollLeft;
    const scrollWidth = megaCats.scrollWidth;
    const clientWidth = megaCats.clientWidth;
    const maxScroll = scrollWidth - clientWidth;
    
    // Hide fade when scrolled to end (within 10px tolerance)
    if (maxScroll - scrollLeft <= 10) {
      fade.style.opacity = '0';
    } else {
      fade.style.opacity = '1';
    }
  }

  // Update on scroll
  megaCats.addEventListener('scroll', updateFade, { passive: true });
  
  // Update on resize
  window.addEventListener('resize', updateFade, { passive: true });
  
  // Initial check
  setTimeout(updateFade, 100);
})();

document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('searchInput');
  if (!input) return;
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      var form = input.form;
      if (!form) return;
      if (typeof form.requestSubmit === 'function') form.requestSubmit();
      else form.submit();
    }
  });
});


(function(){
  const cartBtn = document.getElementById('cartBtn');
  if (cartBtn) {
    cartBtn.addEventListener('click', function(e) {
      e.preventDefault();
      
      const checkLogin = () => {
        return window.isUserLoggedIn ? window.isUserLoggedIn() : false;
      };
      
      if (checkLogin()) {
        window.location.href = '/keranjang';
      } else {
        showCartLoginModal();
      }
    });
  }

  const loginBtn = document.getElementById('loginBtn');
  if (loginBtn) {
    loginBtn.addEventListener('click', function(e) {
      e.preventDefault();
      window.location.href = <?= j($ctaUrl) ?>;
    });
  }

  const userMenuBtn = document.getElementById('userMenuBtn');
  const userDropdown = document.getElementById('userDropdown');
  
  if (userMenuBtn && userDropdown) {
    function openDropdown() {
      userDropdown.hidden = false;
      userMenuBtn.setAttribute('aria-expanded', 'true');
      document.addEventListener('click', closeOnOutside, { capture: true });
      document.addEventListener('keydown', closeOnEscape);
    }
    
    function closeDropdown() {
      if (userDropdown.hidden) return;
      userDropdown.hidden = true;
      userMenuBtn.setAttribute('aria-expanded', 'false');
      document.removeEventListener('click', closeOnOutside, { capture: true });
      document.removeEventListener('keydown', closeOnEscape);
    }
    
    function closeOnOutside(e) {
      if (!userDropdown.contains(e.target) && e.target !== userMenuBtn) {
        closeDropdown();
      }
    }
    
    function closeOnEscape(e) {
      if (e.key === 'Escape') {
        closeDropdown();
        userMenuBtn.focus();
      }
    }
    
    userMenuBtn.addEventListener('click', function(e) {
      e.preventDefault();
      userDropdown.hidden ? openDropdown() : closeDropdown();
    });
    
    // Close dropdown when clicking profile links
    userDropdown.querySelectorAll('[data-close-dropdown]').forEach(link => {
      link.addEventListener('click', function() {
        closeDropdown();
      });
    });
  }

  // Bottom nav profile button - toggle mobile bottom sheet
  const bottomProfileBtn = document.getElementById('bottomProfileBtn');
  const mobileUserSheet = document.getElementById('mobileUserSheet');
  const mobileUserSheetBackdrop = document.getElementById('mobileUserSheetBackdrop');
  
  if (bottomProfileBtn && mobileUserSheet) {
    function openMobileSheet() {
      mobileUserSheet.hidden = false;
      document.body.style.overflow = 'hidden';
      // Trigger animation
      requestAnimationFrame(() => {
        mobileUserSheet.classList.add('active');
      });
    }
    
    function closeMobileSheet() {
      mobileUserSheet.classList.remove('active');
      document.body.style.overflow = '';
      setTimeout(() => {
        mobileUserSheet.hidden = true;
      }, 300);
    }
    
    bottomProfileBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openMobileSheet();
    });
    
    if (mobileUserSheetBackdrop) {
      mobileUserSheetBackdrop.addEventListener('click', closeMobileSheet);
    }
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && !mobileUserSheet.hidden) {
        closeMobileSheet();
      }
    });
    
    // Close sheet when clicking profile links
    mobileUserSheet.querySelectorAll('[data-close-sheet]').forEach(link => {
      link.addEventListener('click', function() {
        closeMobileSheet();
      });
    });
  }
  
  // Handle Google avatar error in bottom-nav (fallback to initials)
  const bottomNavAvatar = document.querySelector('.bottom-nav-avatar-img');
  if (bottomNavAvatar) {
    bottomNavAvatar.addEventListener('error', function() {
      const initials = this.getAttribute('data-fallback-initials') || 'U';
      const initialsDiv = document.createElement('div');
      initialsDiv.className = 'bottom-nav-avatar-initials';
      initialsDiv.textContent = initials;
      this.parentNode.replaceChild(initialsDiv, this);
    });
  }

  // Mobile logout button
  const mobileLogoutBtn = document.getElementById('mobileLogoutBtn');
  if (mobileLogoutBtn) {
    mobileLogoutBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      
      // Check push notification before logout
      if (window.ChatNotification && typeof window.ChatNotification.confirmLogoutWithPushNotification === 'function') {
        const confirmLogout = await window.ChatNotification.confirmLogoutWithPushNotification();
        if (!confirmLogout) {
          return; // User cancelled logout
        }
      }
      
      const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      
      try {
        const response = await fetch('/auth/logout', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken()
          },
          body: JSON.stringify({
            csrf_token: getCsrfToken()
          })
        });
        
        if (response.ok) {
          if (window.guestCart && typeof window.guestCart.clear === 'function') {
            window.guestCart.clear();
          }
          window.location.href = '/login?logged_out=1';
        } else {
          console.error('Logout failed');
          window.location.href = '/logout';
        }
      } catch (error) {
        console.error('Logout error:', error);
        window.location.href = '/logout';
      }
    });
  }

  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      
      // Check push notification before logout
      if (window.ChatNotification && typeof window.ChatNotification.confirmLogoutWithPushNotification === 'function') {
        const confirmLogout = await window.ChatNotification.confirmLogoutWithPushNotification();
        if (!confirmLogout) {
          return; // User cancelled logout
        }
      }
      
      const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      
      try {
        const response = await fetch('/auth/logout', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken()
          },
          body: JSON.stringify({
            csrf_token: getCsrfToken()
          })
        });
        
        if (response.ok) {
          if (window.guestCart && typeof window.guestCart.clear === 'function') {
            window.guestCart.clear();
          }
          
          window.location.href = '/login?logged_out=1';
        } else {
          console.error('Logout failed');
          window.location.href = '/logout';
        }
      } catch (error) {
        console.error('Logout error:', error);
        window.location.href = '/logout';
      }
    });
  }

  const wishlistBtn = document.getElementById('wishlistBtn');
  const notifBtn = document.getElementById('notifBtn');
  
  if (wishlistBtn) {
    wishlistBtn.addEventListener('click', function(e) {
      e.preventDefault();
      alert('Tính năng danh sách yêu thích sắp ra mắt!');
    });
  }
  
  if (notifBtn) {
    notifBtn.addEventListener('click', function(e) {
      e.preventDefault();
      alert('Tính năng thông báo sắp ra mắt!');
    });
  }
})();

// ========== MOBILE BOTTOM NAVIGATION ==========
(function initBottomNav() {
  // Sync cart badge with bottom nav
  function syncBottomCartBadge() {
    const mainBadge = document.getElementById('cartCount');
    const bottomBadge = document.getElementById('bottomCartCount');
    if (mainBadge && bottomBadge) {
      const count = mainBadge.textContent;
      bottomBadge.textContent = count;
      bottomBadge.style.display = parseInt(count) > 0 ? 'flex' : 'none';
    }
  }

  // Sync compare badge with bottom nav
  function syncBottomCompareBadge() {
    const mainBadge = document.getElementById('compareCount');
    const bottomBadge = document.getElementById('bottomCompareCount');
    if (mainBadge && bottomBadge) {
      const count = mainBadge.textContent;
      bottomBadge.textContent = count;
      bottomBadge.style.display = parseInt(count) > 0 ? 'flex' : 'none';
    }
  }

  // Initial sync
  syncBottomCartBadge();
  syncBottomCompareBadge();

  // Observe badge changes
  const observer = new MutationObserver(function() {
    syncBottomCartBadge();
    syncBottomCompareBadge();
  });

  const cartBadge = document.getElementById('cartCount');
  const compareBadge = document.getElementById('compareCount');
  
  if (cartBadge) observer.observe(cartBadge, { childList: true, characterData: true, subtree: true });
  if (compareBadge) observer.observe(compareBadge, { childList: true, characterData: true, subtree: true });

  // Bottom Cart Button
  const bottomCartBtn = document.getElementById('bottomCartBtn');
  if (bottomCartBtn) {
    bottomCartBtn.addEventListener('click', function(e) {
      e.preventDefault();
      
      const checkLogin = () => {
        return window.isUserLoggedIn ? window.isUserLoggedIn() : false;
      };
      
      if (checkLogin()) {
        window.location.href = '/keranjang';
      } else {
        showCartLoginModal();
      }
    });
  }

  // Bottom Compare Button
  const bottomCompareBtn = document.getElementById('bottomCompareBtn');
  if (bottomCompareBtn) {
    bottomCompareBtn.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Close chat widget if open
      if (window.plazaChat && window.plazaChat.isWidgetOpen) {
        window.plazaChat.closeWidget();
      }
      
      const mainCompareBtn = document.getElementById('compareBtn');
      if (mainCompareBtn) {
        mainCompareBtn.click();
      }
    });
  }

  // Bottom Category Button - triggers mega menu
  const bottomCategoryBtn = document.getElementById('bottomCategoryBtn');
  if (bottomCategoryBtn) {
    bottomCategoryBtn.addEventListener('click', function(e) {
      e.preventDefault();
      // Trigger desktop mega menu
      const desktopTrigger = document.querySelector('.mm2-trigger:not(#bottomCategoryBtn)');
      if (desktopTrigger) {
        desktopTrigger.click();
      }
    });
  }

  // Bottom Login Button
  const bottomLoginBtn = document.getElementById('bottomLoginBtn');
  if (bottomLoginBtn) {
    bottomLoginBtn.addEventListener('click', function(e) {
      e.preventDefault();
      window.location.href = <?= j($ctaUrl) ?>;
    });
  }

  // Bottom Chat Button
  const bottomChatBtn = document.getElementById('bottomChatBtn');
  if (bottomChatBtn) {
    bottomChatBtn.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Trigger chat widget (try multiple times if needed)
      const triggerChat = () => {
        const chatToggle = document.getElementById('chat-toggle-btn');
        if (chatToggle) {
          chatToggle.click();
          return true;
        }
        return false;
      };
      
      // Immediate attempt
      if (!triggerChat()) {
        // Retry after 100ms if not found
        setTimeout(() => {
          if (!triggerChat()) {
            // Last resort: manually show popup
            const popup = document.getElementById('chat-popup');
            if (popup) {
              popup.style.display = 'block';
            }
          }
        }, 100);
      }
    });
  }

  // Sync chat unread badge
  function syncBottomChatBadge() {
    const chatBadge = document.getElementById('chat-unread-badge');
    const bottomChatBadge = document.getElementById('bottomChatCount');
    if (chatBadge && bottomChatBadge) {
      const count = chatBadge.textContent;
      bottomChatBadge.textContent = count;
      bottomChatBadge.style.display = chatBadge.style.display;
    }
  }

  // Observe chat badge changes
  const chatBadge = document.getElementById('chat-unread-badge');
  if (chatBadge) {
    observer.observe(chatBadge, { attributes: true, childList: true, characterData: true, subtree: true });
    syncBottomChatBadge();
  }

  // Active state management based on current page
  function updateActiveState() {
    const currentPath = window.location.pathname;
    const bottomNavItems = document.querySelectorAll('.bottom-nav-item');
    
    bottomNavItems.forEach(item => {
      item.classList.remove('active');
      
      const itemId = item.id;
      const page = item.getAttribute('data-page');
      const href = item.getAttribute('href');
      
      // Check by ID for specific pages
      if (itemId === 'bottomHomeBtn' && currentPath === '/') {
        item.classList.add('active');
      } else if (itemId === 'bottomCartBtn' && (currentPath === '/keranjang' || currentPath === '/cart')) {
        item.classList.add('active');
      } else if (itemId === 'bottomProfileBtn' && currentPath.startsWith('/profile')) {
        item.classList.add('active');
      } else if (itemId === 'bottomCategoryBtn' && currentPath.startsWith('/kategori')) {
        item.classList.add('active');
      } else if (itemId === 'bottomCompareBtn' && currentPath === '/compare') {
        item.classList.add('active');
      } else if (itemId === 'bottomChatBtn' && currentPath === '/chat') {
        item.classList.add('active');
      }
      // Fallback: check by data-page attribute
      else if (page === 'home' && currentPath === '/') {
        item.classList.add('active');
      } else if (page === 'profile' && currentPath.startsWith('/profile')) {
        item.classList.add('active');
      }
      // Fallback: check by href
      else if (href && currentPath === href) {
        item.classList.add('active');
      }
    });
  }

  // Update active state on load
  updateActiveState();

  // Bottom nav always visible - auto-hide disabled for better UX
  // const bottomNav = document.querySelector('.bottom-nav');
  // if (bottomNav && window.innerWidth <= 640) {
  //   // Auto-hide feature disabled
  // }
})();
// Cart Login Modal Handler (Global scope)
function showCartLoginModal() {
  const modal = document.getElementById('cartLoginModal');
  if (!modal) return;
  
  modal.classList.add('show');
  
  const confirmBtn = document.getElementById('cartLoginConfirm');
  const registerBtn = document.getElementById('cartRegisterBtn');
  const cancelBtn = document.getElementById('cartLoginCancel');
  
  const handleLogin = () => {
    window.location.href = '/login?return=' + encodeURIComponent('/keranjang');
  };
  
  const handleRegister = () => {
    window.location.href = '/register?return=' + encodeURIComponent('/keranjang');
  };
  
  const handleCancel = () => {
    modal.classList.remove('show');
    confirmBtn.removeEventListener('click', handleLogin);
    registerBtn.removeEventListener('click', handleRegister);
    cancelBtn.removeEventListener('click', handleCancel);
  };
  
  confirmBtn.removeEventListener('click', handleLogin);
  registerBtn.removeEventListener('click', handleRegister);
  cancelBtn.removeEventListener('click', handleCancel);
  
  confirmBtn.addEventListener('click', handleLogin);
  registerBtn.addEventListener('click', handleRegister);
  cancelBtn.addEventListener('click', handleCancel);
  
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      handleCancel();
    }
  });
}
</script>

<!-- Product Compare Sidebar -->
<div class="compare-sidebar" id="compareSidebar">
  <div class="compare-header">
    <h4 class="compare-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z"/>
      </svg>
      So sánh sản phẩm
      <span class="compare-count" id="compareCountSidebar">(0)</span>
    </h4>
    <button class="compare-close" id="compareClose" aria-label="Đóng">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
        <path d="M18.3 5.7a1 1 0 0 0-1.4 0L12 10.6 7.1 5.7a1 1 0 1 0-1.4 1.4L10.6 12l-4.9 4.9a1 1 0 0 0 1.4 1.4L12 13.4l4.9 4.9a1 1 0 0 0 1.4-1.4L13.4 12l4.9-4.9a1 1 0 0 0 0-1.4z"/>
      </svg>
    </button>
  </div>
  
  <div class="compare-content">
    <div class="compare-empty" id="compareEmpty">
      <div class="empty-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" opacity="0.3">
          <path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z"/>
        </svg>
      </div>
      <h5>Chưa chọn sản phẩm nào</h5>
      <p>Chọn sản phẩm muốn so sánh bằng cách nhấn nút "So sánh sản phẩm" trên trang sản phẩm.</p>
    </div>
    
    <div class="compare-list" id="compareList">
      <!-- Products will be populated here -->
    </div>
  </div>
  
  <div class="compare-actions" id="compareActions">
    <button class="btn btn-outline-danger btn-sm" id="compareClearAll">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
        <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
      </svg>
      Xóa tất cả
    </button>
    <button class="btn btn-primary btn-sm" id="compareAnalyze" disabled>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z"/>
      </svg>
      Phân tích AI
    </button>
  </div>
  
  <!-- AI Disclaimer in Sidebar -->
  <div class="compare-disclaimer">
    <div class="ai-disclaimer">
      <div class="disclaimer-text">
        <strong>💡 Lưu ý:</strong> Phân tích này được tạo tự động và có thể còn hạn chế.
        Hãy dùng làm tham khảo thêm và cân nhắc theo nhu cầu của bạn.
        Để biết thêm thông tin, vui lòng liên hệ đội ngũ <?= h($namaSitus) ?>.
      </div>
    </div>
  </div>
</div>
<style>.paylater-promo{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg, #ffffff 0, #ffffff 100% 100%);border:1px solid #000000;border-radius:8px;padding:8px 12px;font-size:13px;line-height:1.4;margin:8px 0}.paylater-promo-sm{padding:6px 10px;font-size:11px;gap:6px}.paylater-promo-md{padding:8px 12px;font-size:13px;gap:8px}.paylater-promo-lg{padding:10px 16px;font-size:14px;gap:10px}.paylater-icon{font-size:1.2em;flex-shrink:0}.paylater-text{display:flex;flex-direction:column;gap:2px}.paylater-text strong{color:#000000;font-weight:600}.paylater-text small{color:#000000;font-size:.85em;opacity:.9}.paylater-desktop-only{display:none}.paylater-mobile-only{display:flex}@media (min-width:641px){.paylater-desktop-only{display:flex}.paylater-mobile-only{display:none}}@media (max-width:640px){.paylater-promo{font-size:12px;padding:7px 10px}.paylater-promo-lg{font-size:13px;padding:8px 12px}}@media print{.paylater-promo{display:none!important}}.paylater-compact{display:inline-block;font-size:11px;font-weight:600;color:#059669;background:#d1fae5;padding:3px 8px;border-radius:4px;margin:6px 0 4px;line-height:1.3}@media (max-width:640px){.paylater-compact{font-size:10px;padding:2px 6px;margin:5px 0 3px}}@media print{.paylater-compact{display:none!important}}</style><style nonce="se3iyzEHMc9FQEB4wSyd0w==">.pd-voucher-minimal{margin:10px 0;display:flex;flex-direction:column;gap:6px}.pdv-min-item{display:flex;align-items:center;gap:8px;padding:0;height:32px;transition:all .2s ease}.pdv-min-hidden{display:none}.pdv-min-disabled{opacity:.5}.pdv-badge-btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#667eea 0,#764ba2 100%);border:none;border-radius:8px;padding:3px;cursor:pointer;transition:all .2s ease;width:auto}.pdv-badge-btn:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 4px 12px rgba(102,126,234,.3)}.pdv-badge-btn:disabled{opacity:.6;cursor:not-allowed}.pdv-badge-icon{color:#fff;flex-shrink:0}.pdv-badge-text{font-size:14px;font-weight:700;color:#fff;white-space:nowrap}.pdv-badge-code{font-family:'Courier New',monospace;font-size:13px;font-weight:700;color:#fff;background:rgba(255,255,255,.2);padding:4px 8px;border-radius:4px;border:none;white-space:nowrap}.pdv-min-copy,.pdv-min-more{padding:4px 12px;font-size:12px;font-weight:600;border-radius:5px;border:1px solid #e2e8f0;background:#fff;color:#667eea;cursor:pointer;transition:all .2s ease;white-space:nowrap}.pdv-min-copy:hover:not(:disabled),.pdv-min-more:hover{background:#667eea;color:#fff;border-color:#667eea}.pdv-min-copy:disabled{opacity:.4;cursor:not-allowed}.pdv-min-copy.copied{background:#22c55e;color:#fff;border-color:#22c55e}.pdv-min-more{margin-left:auto}.modal-close{position:absolute;top:20px;right:20px;background:none;border:none;cursor:pointer;color:#64748b;padding:4px;transition:color .2s ease}.modal-close:hover{color:#1e293b}.modal-body{margin-bottom:8px}.voucher-modal-content{text-align:center}.voucher-modal-badge{display:inline-flex;flex-direction:row;align-items:center;gap:6px;background:linear-gradient(135deg,#667eea 0,#764ba2 100%);border-radius:10px;padding:8px 16px;margin-bottom:12px}.vmb-value{font-size:18px;font-weight:700;color:#fff;line-height:1}.vmb-label{font-size:18px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.5px}.voucher-modal-code{font-family:'Courier New',monospace;font-size:14px;font-weight:700;color:#667eea;background:rgba(102,126,234,.1);padding:6px 12px;border-radius:6px;border:2px dashed rgba(102,126,234,.3);display:inline-block;margin-bottom:10px}.voucher-modal-name{font-size:14px;font-weight:600;color:#1e293b;margin-bottom:16px}.voucher-modal-details{text-align:left;background:#f8fafc;border-radius:8px;padding:12px;margin-bottom:16px}.vmd-item{display:flex;align-items:flex-start;gap:8px;padding:6px 0;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0}.vmd-item:last-child{border-bottom:none}.vmd-item svg{color:#667eea;flex-shrink:0;margin-top:2px}.vmd-item strong{color:#1e293b}.modal-actions{padding:20px}.modal-btn-primary{background:linear-gradient(135deg,#667eea 0,#764ba2 100%);color:#fff}.modal-btn-primary:hover{opacity:.9;transform:translateY(-1px)}@media (max-width:640px){.pdv-min-item{gap:6px}.pdv-badge-btn{padding:3px;gap:6px}.pdv-badge-text{font-size:13px}.pdv-badge-code{font-size:12px;padding:3px 6px}.pdv-min-copy,.pdv-min-more{padding:3px 10px;font-size:11px}.modal-dialog{max-width:95%;margin:20px}.vmb-value{font-size:16px}.vmb-label{font-size:16px}.voucher-modal-code{font-size:13px;padding:5px 10px}}</style><style nonce="se3iyzEHMc9FQEB4wSyd0w==">.promo-endyear-mini-banner{display:flex;align-items:center;gap:12px;padding:12px 16px;background:rgba(255,255,255,.8);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border:2px solid rgba(242,140,26,.2);border-radius:12px;margin:16px 0;box-shadow:0 4px 16px rgba(0,0,0,.08);transition:all .3s ease}.promo-endyear-mini-banner:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.12);border-color:rgba(242,140,26,.4)}.pey-icon{font-size:32px;line-height:1;flex-shrink:0}.pey-content{flex:1;min-width:0}.pey-content strong{display:block;font-size:14px;font-weight:700;color:#F28C1A;margin-bottom:2px}.pey-content span{display:block;font-size:13px;color:#64748b;line-height:1.4}.pey-link{flex-shrink:0;padding:8px 16px;background:linear-gradient(135deg,#F28C1A,#FFB84D);color:#fff;text-decoration:none;font-size:13px;font-weight:700;border-radius:8px;white-space:nowrap;transition:all .2s ease;box-shadow:0 2px 8px rgba(242,140,26,.25)}.pey-link:hover{transform:translateX(2px);box-shadow:0 4px 12px rgba(242,140,26,.35)}@media(max-width:640px){.promo-endyear-mini-banner{flex-wrap:wrap;gap:8px;padding:10px 12px}.pey-icon{font-size:28px;align-self:flex-start}.pey-content{flex:1;min-width:0}.pey-content strong{font-size:12px}.pey-content span{font-size:11px;line-height:1.3}.pey-link{width:100%;flex-basis:100%;font-size:11px;padding:6px 12px;text-align:center}}@media print{.promo-endyear-mini-banner{display:none!important}}</style>
<style nonce="se3iyzEHMc9FQEB4wSyd0w==">
.pd-title-row{position:relative;margin-bottom:12px;padding-right:52px}.pd-title{margin:0!important}.pd-share-btn{position:absolute;top:0;right:0;width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;transition:all .2s ease;-webkit-tap-highlight-color:transparent}.pd-share-btn:hover{background:#f8fafc;border-color:#cbd5e1;color:#0f172a;transform:translateY(-1px)}.pd-share-btn:active{transform:translateY(0)}.pd-share-btn svg{display:block}

.pd-share-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.75);backdrop-filter:blur(4px);z-index:99999;display:none;align-items:center;justify-content:center;padding:0;animation:fadeIn .2s ease}.pd-share-modal-overlay.show{display:flex}.pd-share-modal{background:#fff;border-radius:20px;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);max-width:480px;width:100%;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;animation:slideUp .3s cubic-bezier(.16,1,.3,1)}@keyframes fadeIn{from{opacity:0}to{opacity:1}}@keyframes slideUp{from{opacity:0;transform:translateY(20px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}

.pd-share-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9}.pd-share-title{font-size:18px;font-weight:700;color:#0f172a;margin:0}.pd-share-close{width:32px;height:32px;border-radius:8px;border:0;background:transparent;color:#64748b;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s ease}.pd-share-close:hover{background:#f1f5f9;color:#0f172a}.pd-share-body{padding:24px;overflow-y:auto}

.pd-share-preview{display:flex;gap:12px;padding:16px;background:#f8fafc;border-radius:12px;margin-bottom:24px}.pd-share-preview-img{width:64px;height:64px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid #e2e8f0}.pd-share-preview-info{flex:1;min-width:0}.pd-share-preview-name{font-size:14px;font-weight:600;color:#0f172a;margin:0 0 4px 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.pd-share-preview-price{font-size:16px;font-weight:700;color:#0ea5e9;margin:0}

.pd-share-label{font-size:13px;font-weight:600;color:#475569;margin:0 0 12px 0;text-transform:uppercase;letter-spacing:.5px}.pd-share-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}.pd-share-option{display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px 8px;border:1.5px solid #e2e8f0;border-radius:12px;background:#fff;cursor:pointer;transition:all .2s ease;text-decoration:none;color:inherit}.pd-share-option:hover{border-color:#94a3b8;background:#f8fafc;transform:translateY(-2px)}.pd-share-option:active{transform:translateY(0)}

.pd-share-icon{width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;position:relative}.pd-share-icon svg{width:24px;height:24px;display:block}.pd-share-icon-whatsapp{background:linear-gradient(135deg,#25D366 0%,#128C7E 100%);color:#fff}.pd-share-icon-facebook{background:linear-gradient(135deg,#1877F2 0%,#0c63d4 100%);color:#fff}.pd-share-icon-twitter{background:linear-gradient(135deg,#1DA1F2 0%,#0d8bd9 100%);color:#fff}.pd-share-icon-telegram{background:linear-gradient(135deg,#0088cc 0%,#0077b3 100%);color:#fff}.pd-share-icon-email{background:linear-gradient(135deg,#EA4335 0%,#c5331f 100%);color:#fff}.pd-share-icon-copy{background:linear-gradient(135deg,#f1db63 0%,#e5d146 100%);color:#fff}.pd-share-icon-pdf{background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);color:#fff}.pd-share-name{font-size:12px;font-weight:600;color:#475569;text-align:center}

.pd-share-copy-section{background:#f8fafc;padding:16px;border-radius:12px}.pd-share-copy-input-wrap{display:flex;gap:8px}.pd-share-copy-input{flex:1;padding:12px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#0f172a;background:#fff;font-family:'Segoe UI',system-ui,sans-serif}.pd-share-copy-input:focus{outline:0;border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.1)}.pd-share-copy-btn{padding:12px 20px;border:0;border-radius:10px;background:linear-gradient(135deg,#0ea5e9 0%,#0284c7 100%);color:#fff;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s ease;white-space:nowrap;display:inline-flex;align-items:center;gap:6px}.pd-share-copy-btn:hover{background:linear-gradient(135deg,#0284c7 0%,#0369a1 100%);transform:translateY(-1px);box-shadow:0 4px 12px rgba(14,165,233,.3)}.pd-share-copy-btn:active{transform:translateY(0)}.pd-share-copy-btn svg{width:16px;height:16px}.pd-share-copy-btn.copied{background:linear-gradient(135deg,#22c55e 0%,#000000 100%);pointer-events:none}

@media(max-width:640px){.pd-share-modal{border-radius:20px 20px 0 0;margin-top:auto;max-height:85vh}.pd-share-grid{grid-template-columns:repeat(3,1fr)}.pd-share-header{padding:16px 20px}.pd-share-body{padding:20px}.pd-share-btn{width:36px;height:36px}.pd-share-btn svg{width:18px;height:18px}}@media(max-width:480px){.pd-share-grid{grid-template-columns:repeat(3,1fr);gap:10px}.pd-share-option{padding:12px 6px}.pd-share-icon{width:36px;height:36px}.pd-share-icon svg{width:20px;height:20px}.pd-share-name{font-size:11px}}

.mf-share{background:#fff;color:#ff9100;border:2px solid #ff9100;display:inline-flex;align-items:center;justify-content:center}.mf-share:active{transform:scale(.95)}.mf-share svg{display:block}

@media(max-width:640px){.pd-share-btn{display:none!important}}

.stock-badge.in{background:#e6f9ec;color:#000000;border-radius:999px;padding:4px 10px;font-weight:700}.stock-badge.out{background:#fee2e2;color:#ff9100;border-radius:999px;padding:4px 10px;font-weight:700}

.stock-info-btn{--c:#0ea5e9;--b:#bae6fd;--bg:#f0f9ff;--bg-h:#e0f2fe;--b-h:#0ea5e9;margin-left:0;display:inline-flex;align-items:center;justify-content:center;gap:0.5rem;padding:4px 10px;border-radius:999px;border:1.5px solid var(--b);background:var(--bg);color:var(--c);cursor:pointer;font-size:12px;font-weight:600;transition:all .2s ease;-webkit-tap-highlight-color:transparent}.stock-info-btn:hover{background:var(--bg-h);border-color:var(--b-h);color:#0284c7;transform:translateY(-1px);box-shadow:0 2px 8px rgba(14,165,233,.15)}.stock-info-btn:active{transform:translateY(0)}.stock-info-btn:focus-visible{outline:0;box-shadow:0 0 0 3px rgba(14,165,233,.2)}.stock-info-btn svg{width:14px;height:14px;flex-shrink:0}.stock-info-btn .btn-text{white-space:nowrap}

.stock-popover{position:absolute;z-index:9999;min-width:280px;max-width:92vw;background:#0b1220;color:#eef3ff;border:1px solid rgba(255,255,255,.08);border-radius:14px;box-shadow:0 20px 40px rgba(12,12,20,.45);padding:12px 12px 8px;display:none}.stock-popover.show{display:block}.stock-popover::before{content:"";position:absolute;top:-8px;left:24px;width:14px;height:14px;background:#0b1220;border-left:1px solid rgba(255,255,255,.08);border-top:1px solid rgba(255,255,255,.08);transform:rotate(45deg)}.sp-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}.sp-title{font-weight:800;font-size:14px}.sp-close{background:transparent;border:0;color:#98a2b3;cursor:pointer}

.sp-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px}.sp-item{display:grid;grid-template-columns:1fr auto 32px;column-gap:12px;align-items:center;padding:10px 10px;min-height:44px;border-radius:10px;background:rgba(255,255,255,.03)}.sp-left{display:flex;align-items:center;gap:10px}.sp-dot{width:10px;height:10px;border-radius:999px;background:var(--dot,#60a5fa);display:inline-block}.sp-label{font-weight:600;font-size:13px}


.sp-qty{grid-column:2;justify-self:end;min-width:40px;text-align:right;font-weight:800;font-size:13px;color:#cfe4ff;font-variant-numeric:tabular-nums}

.sp-wa{
  grid-column:3; justify-self:end;
  width:32px; height:32px; min-width:32px; min-height:32px;
  box-sizing:border-box;
  padding:0; line-height:0; text-decoration:none; outline:0;
  display:inline-flex; align-items:center; justify-content:center;
  border-radius:999px; border:1px solid rgba(37,211,102,.60);  /* 1px untuk hasil “crisp” */
  background:rgba(37,211,102,.08); color:#25D366;
  transition:background-color .15s ease, border-color .15s ease, transform .05s ease;
  -webkit-tap-highlight-color:transparent;
}
.sp-wa:hover{background:rgba(37,211,102,.16);border-color:rgba(37,211,102,.75)}
.sp-wa:active{transform:translateY(.5px) scale(.98)}

.sp-wa svg{width:18px;height:18px;display:block;vertical-align:middle}
.stock-popover a{text-decoration:none}

#deskripsi img{max-width:450px!important;height:unset!important}@media(max-width:640px){.stock-popover{position:fixed;left:0;right:0;bottom:max(12px,env(safe-area-inset-bottom,12px));margin:0 10px;padding:14px 12px 10px;border-radius:16px}.stock-popover::before{display:none}.stock-info-btn{padding:4px 8px;gap:4px;border:0;background:transparent}.stock-info-btn:hover{background:transparent;box-shadow:none;transform:none}.stock-info-btn .btn-text{display:none}.stock-info-btn svg{width:16px;height:16px}.sp-item{grid-template-columns:1fr auto 36px}.sp-wa{width:36px;height:36px;min-width:36px;min-height:36px}.sp-wa svg{width:19px;height:19px}}

.stock-dot{position:absolute;bottom:8px;right:8px;width:8px;height:8px;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,0.4);z-index:2;cursor:help}.stock-dot.ready{background:#10b981}.stock-dot.low{background:#f59e0b}.stock-dot.out{background:#ef4444}.stock-dot:hover{transform:scale(1.2);transition:transform 0.2s ease}@media(max-width:640px){.stock-dot{width:7px;height:7px;bottom:6px;right:6px}}

.pd-related-card .spec-chips{font-size:11px;color:#64748b;margin:4px 0 6px 0;line-height:1.3;font-weight:500}.pd-related-card .spec-chips .spec-divider{margin:0 4px;opacity:0.6}@media(max-width:640px){.pd-related-card .spec-chips{font-size:10px;margin:3px 0 5px 0}#deskripsi img{max-width:300px!important;height:unset!important;justify-self:center!important}}

.pwp-trigger-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 8px 4px;background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);color:#78350f;border-radius:6px;font-size:.65rem;font-weight:600;border:0}.pwp-trigger-badge svg{flex-shrink:0;width:12px;height:12px}
.pwp-deals-section{margin:0;padding:12px;background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border-radius:12px;border:2px solid #fbbf24}.pwp-deals-header{display:flex;gap:10px;margin-bottom:10px}.pwp-icon{flex-shrink:0;color:#f59e0b;width:18px;height:18px}.pwp-text .pwp-title{margin:0 0 2px 0;font-size:14px;font-weight:700;color:#78350f}.pwp-text .pwp-subtitle{margin:0;font-size:11px;color:#92400e;opacity:0.9}.pwp-deals-loading{text-align:center;padding:40px 0;color:#92400e}.pwp-spinner{width:40px;height:40px;border:3px solid #fbbf24;border-top-color:#f59e0b;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 12px}@keyframes spin{to{transform:rotate(360deg)}}
.pwp-deals-grid{display:flex;flex-direction:column;gap:6px}.pwp-deal-card{background:#fff;border-radius:6px;overflow:hidden;border:1px solid #fbbf24;display:flex;align-items:center;padding:8px;gap:10px}.pwp-deal-card:hover{background:#fef3c7}.pwp-deal-thumb{flex-shrink:0;width:50px;height:50px;background:#f8fafc;overflow:hidden;border-radius:4px}.pwp-deal-thumb img{width:100%;height:100%;object-fit:cover}.pwp-deal-badge{background:#dc2626;color:#fff;padding:2px 5px;border-radius:3px;font-size:9px;font-weight:700;margin-left:auto}.pwp-deal-info{flex:1;display:flex;flex-direction:column;gap:4px}.pwp-deal-name{font-size:12px;font-weight:600;color:#0f172a;margin:0;line-height:1.2}.pwp-deal-prices{display:flex;align-items:center;gap:6px;flex-wrap:wrap}.pwp-deal-price{font-size:14px;font-weight:800;color:#dc2626;white-space:nowrap}.pwp-deal-original{font-size:11px;color:#64748b;text-decoration:line-through;white-space:nowrap}.pwp-cart-note{margin-top:8px;padding:6px 10px;background:#e0f2fe;border-left:2px solid#0ea5e9;font-size:11px;color:#0c4a6e;border-radius:4px}
@media(max-width:640px){.pwp-deals-section{margin:12px 0;padding:10px;border-radius:10px}.pwp-deals-header{gap:8px}.pwp-text .pwp-title{font-size:13px}.pwp-text .pwp-subtitle{font-size:10px}.pwp-deal-card{padding:6px;gap:8px}.pwp-deal-thumb{width:45px;height:45px}.pwp-deal-name{font-size:11px}.pwp-deal-price{font-size:13px}.pwp-deal-original{font-size:10px}}

.preorder-badge{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:#fff;border-radius:8px;font-size:13px;font-weight:700;border:0;box-shadow:0 2px 8px rgba(139,92,246,.25)}.preorder-badge svg{width:16px;height:16px;flex-shrink:0}.preorder-info{margin:12px 0;padding:14px;background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);border:1.5px solid #c4b5fd;border-radius:12px}.preorder-info-header{display:flex;align-items:center;gap:8px;margin-bottom:10px}.preorder-info-icon{width:20px;height:20px;color:#7c3aed;flex-shrink:0}.preorder-info-title{font-size:14px;font-weight:700;color:#5b21b6;margin:0}.preorder-info-items{display:flex;flex-direction:column;gap:8px}.preorder-info-row{display:flex;align-items:center;justify-content:space-between;font-size:13px}.preorder-info-label{color:#6b21a8;font-weight:600}.preorder-info-value{color:#5b21b6;font-weight:700}.preorder-info-value.highlight{color:#dc2626;font-size:14px}.preorder-info-note{margin-top:10px;padding:8px 10px;background:#fef3c7;border-left:2px solid #f59e0b;font-size:12px;color:#78350f;border-radius:6px;line-height:1.4}@media(max-width:640px){.preorder-badge{font-size:12px;padding:5px 10px}.preorder-info{margin:10px 0;padding:12px}.preorder-info-title{font-size:13px}.preorder-info-row{font-size:12px}.preorder-info-note{font-size:11px;padding:6px 8px}}
</style>

<div class="pd-sticky-header" id="pd-sticky-header"><div class="pd-sticky-header-container"><div class="pd-sticky-thumb"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" id="pd-sticky-thumb-img"></div><div class="pd-sticky-info"><h2 class="pd-sticky-title" id="pd-sticky-title"><?= h($page['title'] ?? '') ?></h2><div class="pd-sticky-price-row"><span class="pd-sticky-price" id="pd-sticky-price">10.000 VNĐ</span><span class="pd-sticky-price-original" id="pd-sticky-price-original">1.000.000 VNĐ</span><span class="pd-sticky-discount" id="pd-sticky-discount">-99%</span></div></div><div class="pd-sticky-actions"><button type="button" class="pd-sticky-share-btn" id="pd-sticky-share-btn" aria-label="Chia sẻ sản phẩm" title="Chia sẻ"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg></button><button type="button" class="pd-sticky-btn pd-sticky-btn-chat" id="pd-sticky-chat"><svg viewBox="0 0 640 640" fill="currentColor" aria-hidden="true"><path d="M320 128C241 128 175.3 185.3 162.3 260.7C171.6 257.7 181.6 256 192 256L208 256C234.5 256 256 277.5 256 304L256 400C256 426.5 234.5 448 208 448L192 448C139 448 96 405 96 352L96 288C96 164.3 196.3 64 320 64C443.7 64 544 164.3 544 288L544 456.1C544 522.4 490.2 576.1 423.9 576.1L336 576L304 576C277.5 576 256 554.5 256 528C256 501.5 277.5 480 304 480L336 480C362.5 480 384 501.5 384 528L384 528L424 528C463.8 528 496 495.8 496 456L496 435.1C481.9 443.3 465.5 447.9 448 447.9L432 447.9C405.5 447.9 384 426.4 384 399.9L384 303.9C384 277.4 405.5 255.9 432 255.9L448 255.9C458.4 255.9 468.3 257.5 477.7 260.6C464.7 185.3 399.1 127.9 320 127.9z"/></svg>Hỏi</button><button type="button" class="pd-sticky-btn pd-sticky-btn-cart" id="pd-sticky-add-cart" disabled>+ Giỏ hàng</button><button type="button" class="pd-sticky-btn pd-sticky-btn-buy" id="pd-sticky-buy" disabled>Mua ngay</button></div></div></div>

<main class="product-single product-single--v3"><nav class="pd-breadcrumb" aria-label="breadcrumb"><a href="/"><?= h($namaSitus) ?></a><span>-</span><a href="/">CÁ CƯỢC BÓNG ĐÁ</a><span>-</span><a href="/">NHÀ CÁI BÓNG ĐÁ</a><span>-</span><a href="/">CƯỢC XIÊN</a><span>-</span><a href="/">LINK CÁ CƯỢC BÓNG ĐÁ </a><span>-</span><span class="current"><?= h($page['h1'] ?? '') ?>


</span></nav>

<div class="pd-top-layout"><section class="pd-media-card"><figure class="pd-main-image" id="pd-main-figure"><img id="pd-main-img" src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" data-slug="" loading="eager">
</figure>

<div class="pd-mobile-slider-wrapper">
<div class="pd-mobile-slider" id="pd-mobile-slider" aria-label="Thư viện ảnh (vuốt)"><div class="pd-mslide"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="eager"></div><div class="pd-mslide"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></div><div class="pd-mslide"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></div><div class="pd-mslide"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></div><div class="pd-mslide"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></div><div class="pd-mslide"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></div></div><div class="pd-gallery-counter" id="pd-gallery-counter">1/6</div></div>
<div class="pd-thumb-gallery" id="pd-thumb-gallery" aria-label="Thư viện ảnh sản phẩm"><button type="button" class="pd-thumb-btn active" data-full="<?= h($heroImg) ?>" data-alt="<?= h($namaSitus) ?>" data-slug="" aria-label="Ảnh 1"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></button><button type="button" class="pd-thumb-btn " data-full="<?= h($heroImg) ?>" data-alt="<?= h($namaSitus) ?>" data-slug="https://twomamasfarm.com/img/banner.webp" aria-label="Ảnh 2"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></button><button type="button" class="pd-thumb-btn " data-full="<?= h($heroImg) ?>" data-alt="<?= h($namaSitus) ?>" data-slug="lenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-b-1761796941-223.webp" aria-label="Ảnh 3"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></button><button type="button" class="pd-thumb-btn " data-full="<?= h($heroImg) ?>" data-alt="<?= h($namaSitus) ?>" data-slug="lenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-b-1761796941-776.webp" aria-label="Ảnh 4"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></button><button type="button" class="pd-thumb-btn " data-full="<?= h($heroImg) ?>" data-alt="<?= h($namaSitus) ?>" data-slug="lenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-b-1761796941-634.webp" aria-label="Ảnh 5"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></button><button type="button" class="pd-thumb-btn " data-full="<?= h($heroImg) ?>" data-alt="<?= h($namaSitus) ?>" data-slug="lenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-b-1761796941-892.webp" aria-label="Ảnh 6"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" loading="lazy"></button></div></section><section class="pd-info-card"><div id="pd-variant-combined" class="pd-variant-combined" hidden></div><div class="pd-title-row">
        <h1 class="pd-title" id="pd-title"><?= h($page['h1'] ?? '') ?></h1><button type="button" class="pd-share-btn" id="pd-share-btn" aria-label="Chia sẻ sản phẩm" title="Chia sẻ"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg></button></div>
<div id="pd-price-anchor">
</div>
<div class="pd-price-qr-container">
        <div class="pd-price-block" id="pd-price-block">
                <div class="pd-price-row">
                        <span class="pd-price-current" id="pd-price-current" >10.000 VNĐ</span>
                        <span class="pd-price-original" id="pd-price-original">50.000 VNĐ</span>
                        <span class="pd-discount-badge" id="pd-discount-badge">-88%</span></div>
                        <div class="pd-meta-row">
                                <span class="sold-inline" id="pd-sold-inline" data-role="sold-chip">Đã bán 5</span>
                                <span class="pwp-trigger-badge" id="pwp-trigger-badge" hidden>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>Có ưu đãi PWP</span></div>
                                                <div class="pd-meta-inline"><span class="stock-badge out" id="pd-stock-badge"><?= h($namaSitus) ?></span>
                                                        <span class="sku">SKU: <span id="pd-sku"><?= h($namaSitus) ?></span></span></div>
<div class="paylater-promo paylater-promo-sm paylater-mobile-only">
    <span class="paylater-icon">💳</span>
    <span class="paylater-text">
        <strong>NHÀ CÁI BÓNG ĐÁ UY TÍN</strong>
        <small>CƯỢC XIÊN WORLD CUP 2026</small>
    </span>
</div>

<div class="stock-popover" id="pd-stock-popover" role="dialog" aria-hidden="true" aria-label="Thông tin tồn kho chi nhánh"><div class="sp-head"><div class="sp-title">Tồn kho chi nhánh</div><button type="button" class="sp-close" id="pd-stock-popover-close" aria-label="Đóng"><svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.41 6.3 6.3-6.3 6.3 1.4 1.41 6.3-6.3 6.3 6.3 1.41-1.41-6.3-6.3 6.3-6.3z"/></svg></button></div><ul class="sp-list">
<li class="sp-item"><div class="sp-left"><span class="sp-dot" style="--dot:#60a5fa;"></span><span class="sp-label">Hà Nội</span></div><span class="sp-qty" id="sp-qty-meg">0</span><a class="sp-wa" href="https://wa.me/6281380009898?text=Halo%2C%20saya%20ingin%20tanya%20stok%20di%20Asia%20Mega%20Mas%20untuk%20produk%20https%3A%2F%2Fplazait.co.id%2Fproduct%2Flenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-black" target="_blank" rel="noopener" aria-label="WhatsApp Hà Nội"><svg class="ico-wa" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.029-.967-.273-.099-.471-.148-.669.149-.198.297-.767.966-.94 1.164-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.173.198-.297.298-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.612-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.718 2.006-1.411.248-.694.248-1.29.173-1.411-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.002a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.263c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.887 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.158 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.305-1.654a11.86 11.86 0 005.67 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.485-8.413"/></svg></a></li>
                
                    <li class="sp-item">
                      <div class="sp-left">
                        <span class="sp-dot" style="--dot:#34d399;"></span>
                        <span class="sp-label">TP. Hồ Chí Minh</span>
                      </div>
                      <span class="sp-qty" id="sp-qty-mdf">0</span>
                      <a class="sp-wa"
                         href="https://wa.me/6285388992009?text=Halo%2C%20saya%20ingin%20tanya%20stok%20di%20Medan%20Fair%20untuk%20produk%20https%3A%2F%2Fplazait.co.id%2Fproduct%2Flenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-black"
                         target="_blank" rel="noopener" aria-label="WhatsApp TP. Hồ Chí Minh">
                        <svg class="ico-wa" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.029-.967-.273-.099-.471-.148-.669.149-.198.297-.767.966-.94 1.164-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.173.198-.297.298-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.612-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.718 2.006-1.411.248-.694.248-1.29.173-1.411-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.002a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.263c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.887 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.158 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.305-1.654a11.86 11.86 0 005.67 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.485-8.413"/>
                          </svg>
                      </a>
                    </li>
                
                    <li class="sp-item">
                      <div class="sp-left">
                        <span class="sp-dot" style="--dot:#f59e0b;"></span>
                        <span class="sp-label">Đà Nẵng</span>
                      </div>
                      <span class="sp-qty" id="sp-qty-bda">0</span>
                      <a class="sp-wa"
                         href="https://wa.me/628116833411?text=Halo%2C%20saya%20ingin%20tanya%20stok%20di%20Banda%20Aceh%20untuk%20produk%20https%3A%2F%2Fplazait.co.id%2Fproduct%2Flenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-black"
                         target="_blank" rel="noopener" aria-label="WhatsApp Đà Nẵng">
                        <svg class="ico-wa" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.029-.967-.273-.099-.471-.148-.669.149-.198.297-.767.966-.94 1.164-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.173.198-.297.298-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.612-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.718 2.006-1.411.248-.694.248-1.29.173-1.411-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.002a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.263c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.887 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.158 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.305-1.654a11.86 11.86 0 005.67 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.485-8.413"/>
                          </svg>
                      </a>
                    </li>
                  </ul>
                </div>

                            </div>
            <div class="pd-qr-print" style="display:none;"><div class="pd-qr-label">Quét để xem sản phẩm:</div><img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=https%3A%2F%2Fplazait.co.id%2Fproduct%2Flenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-black" alt="Mã QR" class="pd-qr-image"></div>
            </div>
                                    <div id="pd-variants-anchor"></div>

                        

            <div class="pd-compare-section">
                <button type="button" class="pd-compare-btn" id="pd-compare-btn" data-product-id="325">
                    <svg class="compare-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21,9L17,5V8H10V10H17V13M7,11L3,15L7,19V16H14V14H7V11Z"/>
                    </svg>
                    <span class="compare-text">So sánh sản phẩm</span>
                    <span class="compare-remove" hidden>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                        </svg>
                        Xóa khỏi so sánh
                    </span>
                </button>
                <div class="pd-compare-info">
                    <small class="compare-note" id="compare-note">So sánh tối đa 5 sản phẩm với sự hỗ trợ của AI</small>
                </div>
            </div>
            
            <div class="pd-short-desc"><p><strong>Thông tin <?= h($namaSitus) ?> 2026:</strong></p><ul><li>Nạp tối thiểu: 50.000 VNĐ</li><li>Loại trò chơi: Sbobet, Casino, Slot</li><li>Phương thức thanh toán: Ngân hàng, QR, Ví điện tử</li><li>Tiền tệ: VND (Việt Nam Đồng)</li><li>Giờ hoạt động: Trực tuyến 24 giờ</li><li>Đánh giá: ⭐⭐⭐⭐⭐</li><li>Đăng ký tại đây: <a href="<?= h($ctaUrl) ?>">Nhấn vào đây</a></li></ul></div><div class="mawar88-container-v1 mini">
<div class="mawar88-container">

  <!-- HEADER -->
<style>
.topbar {
  background:linear-gradient(180deg, #000000, #000000);
  color:#fff;
  font-size:13px;
}
.navbar {
  background:linear-gradient(180deg, #000000, #000000) !important;
  backdrop-filter:blur(12px);
  -webkit-backdrop-filter:blur(12px);
  border-bottom:1px solid rgba(0, 0, 0, 0.6);
  box-shadow:0 2px 16px -4px rgba(0, 0, 0, 0.08);
  position:sticky;
  top:0;
  z-index:500;
  transition:all .3s ease;
}

*{margin:0;padding:0;box-sizing:border-box}

body{
  background:#000000;
  font-family:'Poppins',sans-serif;
  color:#fff;
  padding:20px;
}

.wrapper{
  max-width:1000px;
  margin:auto;
}

.title{
  text-align:center;
  margin-bottom:25px;
}

.title h1{
  font-family:'Orbitron',sans-serif;
  color:#00ffe1;
  font-size:34px;
  text-shadow:0 0 15px rgba(0,255,225,.7);
}

.calc-box{
  background:#0d1328;
  border-radius:20px;
  padding:20px;
  border:1px solid rgba(0,255,225,.15);
  box-shadow:0 0 25px rgba(0,255,225,.08);
}

.match-item{
  background:#121c35;
  border-radius:16px;
  padding:16px;
  margin-bottom:15px;
  border-left:4px solid #00ffe1;
}

.match-grid{
  display:grid;
  grid-template-columns:2fr 2fr 1fr 1fr auto;
  gap:10px;
}

input,select{
  width:100%;
  padding:12px;
  border:none;
  border-radius:12px;
  background:#1f1e1d;
  color:#fff;
}

button{
  border:none;
  cursor:pointer;
  transition:.2s;
}

.btn-add{
  margin-top:15px;
  width:100%;
  padding:14px;
  border-radius:14px;
  background:linear-gradient(135deg,#00ffe1,#00b894);
  color:#000;
  font-weight:800;
}

.btn-remove{
  width:45px;
  border-radius:12px;
  background:#ff9100;
  color:#fff;
  font-weight:bold;
}

.summary{
  margin-top:25px;
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:15px;
}

.summary-card{
  background:#121c35;
  border-radius:18px;
  padding:20px;
  text-align:center;
}

.summary-card h3{
  font-size:13px;
  color:#8aa6c7;
  margin-bottom:10px;
}

.summary-card .value{
  font-size:32px;
  font-family:'Orbitron',sans-serif;
  color:#00ffe1;
  font-weight:900;
}

.bet-box{
  margin-top:20px;
}

.bet-box input{
  padding:16px;
  font-size:16px;
}

.footer-btn{
  margin-top:18px;
  width:100%;
  padding:16px;
  border-radius:16px;
  background:linear-gradient(135deg,#00ffe1,#00b894);
  color:#000;
  font-weight:900;
  font-size:15px;
}

@media(max-width:768px){
  .match-grid{
    grid-template-columns:1fr;
  }

  .summary{
    grid-template-columns:1fr;
  }
}
</style>
<div class="parlay-widget">
  <h2>KÈO CƯỢC XIÊN⚡ - TRẬN CẦU ĐỈNH CAO</h2>

  <div id="parlayMatchList">

    <div class="parlay-row">
      <input value="Real Madrid">
      <input value="Manchester United">
      <input value="HOME">
      <input type="number" value="1.85" step="0.01" class="parlay-odds">
      <button onclick="removeParlayMatch(this)">×</button>
    </div>

    <div class="parlay-row">
      <input value="Barcelona">
      <input value="Bayern Munich">
      <input value="AWAY">
      <input type="number" value="1.95" step="0.01" class="parlay-odds">
      <button onclick="removeParlayMatch(this)">×</button>
    </div>

    <div class="parlay-row">
      <input value="Manchester City">
      <input value="Paris Saint-Germain">
      <input value="HOME">
      <input type="number" value="1.70" step="0.01" class="parlay-odds">
      <button onclick="removeParlayMatch(this)">×</button>
    </div>

  </div>

  <button class="parlay-add" onclick="addParlayMatch()">+ Thêm trận đấu</button>

  <input type="number" id="parlayBet" class="parlay-bet" placeholder="Nhập số tiền cược">

  <div class="parlay-result">
    <div>
      <span>Tổng tỷ lệ</span>
      <strong id="parlayTotalOdds">0</strong>
    </div>
    <div>
      <span>Tiền cược</span>
      <strong id="parlayBetValue">0</strong>
    </div>
    <div>
      <span>Tiền thắng dự kiến</span>
      <strong id="parlayWinValue">0</strong>
    </div>
  </div>

  <button class="parlay-submit" onclick="calculateParlay()">Tính tỷ lệ cược xiên</button>
</div>

<style>
.parlay-widget{
  max-width:430px;
  margin:25px auto;
  padding:24px;
  border-radius:22px;
  background:linear-gradient(160deg, #000000, #000000, #000000);
  border:2px solid #ff9100;
  box-shadow:0 8px 25px rgba(0,0,0,.6), 0 0 18px #ff9100;
  font-family:Arial,sans-serif;
}

.parlay-widget h2{
  margin:0 0 20px;
  text-align:center;
  color:#ff9100;
  font-size:25px;
  text-transform:uppercase;
  text-shadow:0 0 12px #ff9100;
}

.parlay-row{
  display:grid;
  grid-template-columns:1fr 1fr 58px 55px 36px;
  gap:7px;
  margin-bottom:12px;
  padding:11px;
  border-radius:14px;
  background:#071b18;
  border-left:4px solid #ff9100;
  box-shadow:0 0 10px rgba(0, 0, 0, 0.2);
}

.parlay-row input{
  width:100%;
  box-sizing:border-box;
  border:0;
  border-radius:8px;
  padding:9px 6px;
  background:#ffffff;
  color:#111;
  font-size:12px;
  font-weight:700;
}

.parlay-row button{
  border:0;
  border-radius:8px;
  background:#000000;
  color:#ff9100;
  font-size:18px;
  font-weight:700;
  cursor:pointer;
  box-shadow:0 0 10px rgb(234, 0, 255);
}

.parlay-add,
.parlay-submit{
  width:100%;
  border:0;
  border-radius:12px;
  padding:13px;
  margin-top:10px;
  background:linear-gradient(90deg, #000000, #000000);
  border: 2px solid #ff9100;
  color:#ff9100;
  font-weight:800;
  cursor:pointer;
  text-transform:uppercase;
  box-shadow:0 0 14px rgba(0, 0, 0, 0.5);
}

.parlay-bet{
  width:100%;
  box-sizing:border-box;
  margin:15px 0;
  padding:14px;
  border:0;
  border-radius:12px;
  background:#000000;
  color:#000000;
}

.parlay-result{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:10px;
}

.parlay-result div{
  background:#000000;
  border-radius:14px;
  padding:14px 7px;
  text-align:center;
  border:1px solid #ff9100;
  box-shadow:0 0 10px rgba(0,255,231,.15);
}

.parlay-result span{
  display:block;
  color:#ff9100;
  font-size:11px;
  margin-bottom:7px;
  text-transform:uppercase;
}

.parlay-result strong{
  color:#ffffff;
  font-size:20px;
  text-shadow:0 0 8px #ff9100;
}
</style>
<style>
.parlay-widget{
  position:relative;
  overflow:hidden;
  box-shadow:
    0 0 18px rgba(0, 0, 0, 0.55),
0 0 45px rgba(0, 0, 0, 0.35),
0 18px 35px rgba(0, 0, 0, 0.35),
inset 0 0 35px rgba(56, 61, 60, 0.08) !important;
}

.parlay-widget::before{
  content:"";
  position:absolute;
  top:-120px;
  left:-180px;
  width:180px;
  height:650px;
  background:linear-gradient(
    120deg,
    transparent,
    rgba(255,255,255,.65),
    transparent
  );
  transform:rotate(25deg);
  animation:parlayShine 4s infinite;
  pointer-events:none;
  z-index:1;
}

.parlay-widget::after{
  content:"";
  position:absolute;
  inset:12px;
  border-radius:18px;
  border:1px solid rgba(0, 0, 0, 0.22);
  box-shadow:inset 0 0 22px rgba(255,255,255,.12);
  pointer-events:none;
}

@keyframes parlayShine{
  0%{left:-220px;}
  55%{left:120%;}
  100%{left:120%;}
}

.parlay-widget > *{
  position:relative;
  z-index:2;
}

.parlay-widget h2{
  text-shadow:0 0 8px #000000, 0 0 18px #ff9100, 0 0 28px #0027ff !important
}

.parlay-row{
  position:relative;
  overflow:hidden;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, .35), 0 0 14px rgba(0, 0, 0, 0.43) !important;
}

.parlay-row::before{
  content:"";
  position:absolute;
  left:0;
  right:0;
  top:0;
  height:45%;
  background:linear-gradient(rgba(255,255,255,.28),rgba(255,255,255,0));
  pointer-events:none;
}

.parlay-add,
.parlay-submit{
  box-shadow:
    0 0 18px rgba(0, 0, 0, 0.55),
0 0 45px rgba(0, 0, 0, 0.35),
0 18px 35px rgba(0, 0, 0, 0.35),
inset 0 0 35px rgba(180, 255, 240, 0.08) !important;
  text-shadow:0 1px 2px rgba(255,255,255,.35);
}

.parlay-result div{
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, .28), 0 0 15px rgba(0, 0, 0, 0.36) !important;
}

</style>

<script>
function calculateParlay(){
  let total = 1;
  document.querySelectorAll('.parlay-odds').forEach(function(input){
    let value = parseFloat(input.value);
    if(!isNaN(value) && value > 0){
      total *= value;
    }
  });

  let bet = parseFloat(document.getElementById('parlayBet').value) || 0;
  let win = total * bet;

  document.getElementById('parlayTotalOdds').innerText = total.toFixed(2);
  document.getElementById('parlayBetValue').innerText = bet.toLocaleString('vi-VN');
  document.getElementById('parlayWinValue').innerText = win.toLocaleString('vi-VN');
}

function addParlayMatch(){
  let clubs = [
    ["Canada","Bosnia & Herzegovina","Chủ nhà","1.85"],
    ["Qatar","Thụy Sĩ","Đội khách","1.75"],
    ["Brazil","Ma-rốc","Chủ nhà","1.70"],
    ["Úc","Thổ Nhĩ Kỳ","Hòa","2.50"],
    ["Hà Lan","Nhật Bản","Chủ nhà","1.90"]
  ];

  let data = clubs[Math.floor(Math.random() * clubs.length)];

  let row = document.createElement('div');
  row.className = 'parlay-row';
  row.innerHTML = `
    <input value="${data[0]}">
    <input value="${data[1]}">
    <input value="${data[2]}">
    <input type="number" value="${data[3]}" step="0.01" class="parlay-odds">
    <button onclick="removeParlayMatch(this)">×</button>
  `;

  document.getElementById('parlayMatchList').appendChild(row);
  calculateParlay();
}

function removeParlayMatch(button){
  button.parentElement.remove();
  calculateParlay();
}

document.addEventListener('input', function(e){
  if(e.target.closest('.parlay-widget')){
    calculateParlay();
  }
});

calculateParlay();
</script>

<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"86f57583232e46f6be1afe41efad8833","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>

<style>
body{background:#ebf2fc; font-family:Poppins,sans-serif; color:white;}
.mawar88-container{
  max-width:520px;
  margin:auto;
  padding:15px;
  border-radius:12px;
  background:#ff9100;
  border:2px solid #ff9100;
  box-shadow:0 0 20px #ff9100;
}

.mawar88-header{
  font-weight:900;
  text-align:center;
  color:#ff4c4c;
  text-shadow:0 0 12px #bdaf1b;
  margin-bottom:12px;
}

.mawar88-controls{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:10px;
  margin-bottom:12px;
}
.mawar88-controls label{
  display:flex; flex-direction:column;
  font-size:11px; color:#ff9999;
}
.mawar88-controls select,
.mawar88-controls input{
  padding:8px;
  border-radius:8px;
  border:1px solid #ff4c4c;
  background:#2b0000;
  color:white;
}
#btnGenerate{
  grid-column:span 2;
  padding:12px;
  background:#bdaf1b;
  border:none;
  color:black;
  font-weight:900;
  cursor:pointer;
  margin-top:5px;
  box-shadow:0 0 15px #ff4c4c;
}

.mawar88-result{
  margin-top:12px;
  padding:12px;
  border-radius:10px;
  background:rgba(255,0,0,0.05);
  border:1px solid #ff4c4c;
}

.mawar88-main div, .mawar88-details div{
  margin:5px 0;
}

#rPrediction{
  font-size:24px;
  font-weight:900;
  color:#bdaf1b;
}

.mawar88-alts{
  margin-top:10px;
}
.mawar88-alts h4{margin-bottom:6px;}
#mawar88AltList div{
  background:#2b0000;
  padding:6px 10px;
  margin:3px 0;
  border-radius:8px;
  border-left:3px solid #bdaf1b;
}

.mawar88-alts{
  font-size:10px;
  margin-top:8px;
  text-align:center;
  color:#ff9999;
}
</style>

<script>
const leagues = ["Premier League","La Liga","Serie A"];
const tips = ["Analisis statistik tim","Performa terakhir","Cek head-to-head"];
function rand(arr){return arr[Math.floor(Math.random()*arr.length)];}

const generateButton = document.getElementById("btnGenerate");
if (generateButton) generateButton.onclick=function(){
  const type = document.getElementById("betTypeFilter").value;
  const league = rand(leagues);

  let pred;
  if(type==="1X2") pred=rand(["1","X","2"]);
  else if(type==="OVER_UNDER") pred=rand(["Over","Under"]);
  else pred=rand(["Home -1","Away +1","Draw"]);

  document.getElementById("rLeague").innerText=league;
  document.getElementById("rPrediction").innerText=pred;
  document.getElementById("rBetType").innerText=type;
  document.getElementById("rConf").innerText=Math.floor(Math.random()*40+60)+"%";
  document.getElementById("rTip").innerText=rand(tips);
  document.getElementById("rOdds").innerText=(Math.random()*3+1).toFixed(2);

  // alternatif
  const alt=document.getElementById("mawar88AltList"); alt.innerHTML="";
  for(let i=0;i<3;i++){let d=document.createElement("div"); d.innerText=rand(["1","X","2"]); alt.appendChild(d);}
  
  document.getElementById("mawar88Status").innerText="— READY —";
}
</script>


                        
            <div class="pwp-deals-section" id="pwp-deals-section" hidden data-product-id="325" data-variant-id="0" data-category-path="Komputer &amp; Laptop/Laptop/Gaming"><div class="pwp-deals-header"><svg class="pwp-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg><div class="pwp-text"><h3 class="pwp-title">Ưu đãi đặc biệt PWP (Mua kèm giá tốt)</h3><p class="pwp-subtitle">Mua sản phẩm này và nhận sản phẩm chọn lọc với giá đặc biệt</p></div></div><div class="pwp-deals-loading"><div class="pwp-spinner"></div><p>Đang tải ưu đãi...</p></div><div class="pwp-deals-grid" id="pwp-deals-grid"></div><p class="pwp-cart-note">💡 Sản phẩm PWP có thể thêm ở trang giỏ hàng sau khi thêm sản phẩm này</p></div>
        </section>

        <aside class="pd-sidebar">
            <div class="pd-side-card pd-buy-card">
                <form class="pd-buy-form" id="pd-buy-form" data-pid="325">
                    <input type="hidden" name="variant_id" id="pd-variant-id" value="0">
                    <div>
                        <label for="qty" class="qty-label">SL</label>
                        <input type="number" id="qty" name="qty" value="0" min="1"
                               max="0"
                               disabled>
                    </div>
                    <div class="pd-actions">
                        <button type="submit" class="btn-add-cart" id="btn-add-cart" disabled>+ Giỏ hàng</button>
                        <button type="button" class="btn-buy" id="btn-buy" data-id="278426" disabled >Mua</button>
                        <button type="button" class="btn-chat-icon" id="btn-chat-icon" aria-label="Hỏi về sản phẩm" title="Hỏi về sản phẩm"><svg viewBox="0 0 640 640" fill="currentColor"><path d="M320 128C241 128 175.3 185.3 162.3 260.7C171.6 257.7 181.6 256 192 256L208 256C234.5 256 256 277.5 256 304L256 400C256 426.5 234.5 448 208 448L192 448C139 448 96 405 96 352L96 288C96 164.3 196.3 64 320 64C443.7 64 544 164.3 544 288L544 456.1C544 522.4 490.2 576.1 423.9 576.1L336 576L304 576C277.5 576 256 554.5 256 528C256 501.5 277.5 480 304 480L336 480C362.5 480 384 501.5 384 528L384 528L424 528C463.8 528 496 495.8 496 456L496 435.1C481.9 443.3 465.5 447.9 448 447.9L432 447.9C405.5 447.9 384 426.4 384 399.9L384 303.9C384 277.4 405.5 255.9 432 255.9L448 255.9C458.4 255.9 468.3 257.5 477.7 260.6C464.7 185.3 399.1 127.9 320 127.9z"/></svg></button>
                                            </div>
                </form>
                <div class="paylater-promo paylater-promo-md paylater-desktop-only">
    <span class="paylater-icon">💳</span>
    <span class="paylater-text">
        <strong>Chính thức <?= h($namaSitus) ?></strong>
        <small>Nhà cái bóng đá uy tín</small>
    </span>
</div>            </div>

                        
                        <div class="pd-side-card qoala-banner-card">
                <div class="qoala-banner">
                    <div class="qoala-banner-content">
                        <div class="qoala-icon">
                            <center><img src="<?= h($faviconSrc) ?>" alt="<?= h($namaSitus) ?>" class="qoala-shield-img"></center>
                        </div>
                        <div class="qoala-text">
                            <div class="qoala-title">LINK CÁ CƯỢC BÓNG ĐÁ TRỰC TUYẾN</div>
                            <div class="qoala-subtitle">Đã xác minh</div>
                        </div>
                    </div>
                    <a href="<?= h($ctaUrl) ?>" class="qoala-cta"
                       data-ga-event="qoala_banner_click"
                       data-product-id="325"
                       data-product-name="<?= h($namaSitus) ?>"
                       data-category-path="Komputer &amp; Laptop/Laptop/Gaming" target="_blank" rel="nofollow noopener">Thông tin</a>
                </div>
<style>
.qoala-banner-card{padding:0;overflow:hidden}

.qoala-banner{
  background:linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
  padding:12px 16px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  color:rgb(0, 0, 0);
  text-decoration:none;
  min-height:60px;
}

.qoala-banner-content{
  display:flex;
  align-items:center;
  gap:12px;
}

.qoala-icon{
  display:flex;
  align-items:center;
  justify-content:center;
}

.qoala-shield-img{
  width:36px;
  height:36px;
  object-fit:contain;
  opacity:0.9;
}

.qoala-title{
  font-weight:600;
  font-size:14px;
  line-height:1.2;
}

.qoala-subtitle{
  font-size:12px;
  opacity:0.9;
  line-height:1.2;
}

.qoala-cta{
  background:#ffffff;
  color:rgb(0, 0, 0);
  padding:6px 12px;
  border-radius:6px;
  font-size:12px;
  font-weight:600;
  text-decoration:none;
  transition:background 0.2s ease;
  border:1px solid rgb(0, 0, 0);
}

.qoala-cta:hover{
  background:rgb(234, 0, 255);
  color:white;
}

@media(max-width:768px){
  .qoala-banner{padding:10px 12px}
  .qoala-title{font-size:17px}
  .qoala-subtitle{font-size:11px}
}
</style>
            </div>
            
                        <div class="pd-side-card pd-installment-card2"
                 id="inst-card"
                 data-base-price="20789000"
                 data-zero-smart="0">
                <div class="inst-head">
                    <h3>Khuyến mãi & Tính năng <?= h($namaSitus) ?></h3>
                                    </div>

                <div class="inst-bank-tabs" role="tablist" aria-label="Ngân hàng">
                    <button type="button" class="bank-tab active" data-bank="Hdc" aria-selected="true">HDC</button>
                    <button type="button" class="bank-tab" data-bank="Ht Ft" aria-selected="false">HT/FT</button>
                    <button type="button" class="bank-tab" data-bank="Correct Score" aria-selected="false">Tỷ số chính xác</button>
                </div>

                <ul class="inst-month-list" id="inst-month-list" aria-label="Bảng trả góp hằng tháng"></ul>
                <div class="inst-note" id="inst-zero-note" style="display:none;">Trả góp 0% cho ngân hàng được chọn*</div>
            </div>
            
<!-- MECHA BLUE FINAL BOSS REVIEW SLIDER -->
<div class="tnt-review-mecha">

<div class="tnt-header">
  <h2>ĐÁNH GIÁ THÀNH VIÊN BÓNG ĐÁ <?= h($namaSitus) ?></h2>
</div>

<div class="tnt-slider" id="tntSlider">

  <div class="tnt-slide active">
    <div class="tnt-name">Minh - Hà Nội</div>
    <div class="tnt-comment">"Đăng nhập rất nhanh và giao diện dễ dùng. Mình luôn cập nhật được thông tin mới nhất về các trận bóng đá thế giới."</div>
  </div>

  <div class="tnt-slide">
    <div class="tnt-name">Tuấn - TP. Hồ Chí Minh</div>
    <div class="tnt-comment">"Sự kiện thưởng khá hấp dẫn và thông tin đầy đủ. Truy cập rất thoải mái."</div>
  </div>

  <div class="tnt-slide">
    <div class="tnt-name">Hùng - Đà Nẵng</div>
    <div class="tnt-comment">"Mình thích vì có thể vào nền tảng bằng điện thoại bất cứ lúc nào. Điều hướng đơn giản, dễ hiểu."</div>
  </div>

</div>
</div>

</div>

<style>
.tnt-review-mecha{
  background: radial-gradient(circle at center, #0a1614, #0a1614);
  border: 2px solid #000000;
  border-radius: 14px;
  padding: 20px;
  color: #fff;
  font-family: 'Orbitron', sans-serif;
  position: relative;
  overflow: hidden;
  box-shadow: 0 0 25px #2b83d8, inset 0 0 20px rgba(255,0,0,0.2);
}

/* scanline */
.tnt-review-mecha::before{
  content:"";
  position:absolute;
  inset:0;
  background: repeating-linear-gradient(
    to bottom,
    rgba(255,0,0,0.08),
    rgba(255,0,0,0.08) 1px,
    transparent 2px,
    transparent 4px
  );
  pointer-events:none;
}

.tnt-header h2{
  font-size: 18px;
  color:#ff9100;
  margin-bottom:5px;
}

.tnt-header p{
  font-size:12px;
  opacity:0.85;
  margin-bottom:15px;
}

.tnt-slider{
  position:relative;
  height:90px;
}

.tnt-slide{
  position:absolute;
  width:100%;
  opacity:0;
  transform:translateX(40px);
  transition:all .6s ease;
}

.tnt-slide.active{
  opacity:1;
  transform:translateX(0);
}

.tnt-name{
  font-size:14px;
  color:#ff9100;
  margin-bottom:5px;
}

.tnt-comment{
  font-size:13px;
  line-height:1.5;
}

/* glowing border animation */
@keyframes pulseGlow{
  0%{box-shadow:0 0 10px #ff9100;}
  50%{box-shadow:0 0 25px #ff9100;}
  100%{box-shadow:0 0 10px #ff9100;}
}

.tnt-review-mecha{
  animation:pulseGlow 2.5s infinite;
}
</style>

<script>
let slides = document.querySelectorAll('.tnt-slide');
let index = 0;

setInterval(()=>{
  slides[index].classList.remove('active');
  index = (index + 1) % slides.length;
  slides[index].classList.add('active');
}, 3000);
</script>

<div id="mawar88-agen-bola">
        <canvas class="pb-canvas"></canvas>
        <div class="pb-inner">
            <h2 class="pb-title">DỰ ĐOÁN WORLD CUP 2026 <?= h($namaSitus) ?></h2>
            <p class="pb-subtitle">Phân tích trận đấu &middot; <span>Tỷ lệ trực tiếp</span> &middot; Dự đoán thông minh</p>

            <div class="pb-section-title">DỰ ĐOÁN VÒNG BẢNG WORLD CUP 2026</div>

            <div class="pb-grid">
                <!-- Match 1 -->
                <div class="pb-card pb-c1" data-match="2026-06-12T02:00:00+07:00">
                    <div class="pb-skyline pb-skyline-league">BẢNG A &middot; KHAI MẠC</div>
                    <div class="pb-skyline pb-skyline-brand"><span class="skyline-88" data-bs="skyline"></span></div>
                    <div class="pb-teams">
                        <span class="pb-team">MEXICO</span>
                        <span class="pb-vs">VS</span>
                        <span class="pb-team">NAM PHI</span>
                    </div>
                    <div class="pb-countdown">--:--:--</div>
                    <div class="pb-matchinfo">12/06/2026 &middot; 02:00 giờ VN &middot; Azteca</div>
                    <div class="pb-pred"><b>Dự đoán:</b> Đội nhà thắng &middot; <b>Tỷ số:</b> 2-1</div>
                    <div class="pb-conf">
                        <span class="pb-conf-label">CONF</span>
                        <div class="pb-conf-bar">
                            <div class="pb-conf-fill" data-conf="75"></div>
                        </div>
                        <span class="pb-conf-val">75%</span>
                    </div>
                    <div class="pb-odds">
                        <span><i>1</i> 1.75</span>
                        <span><i>X</i> 3.50</span>
                        <span><i>2</i> 6.50</span>
                    </div>
                </div>

                <!-- Match 2 -->
                <div class="pb-card pb-c2" data-match="2026-06-14T02:00:00+07:00">
                    <div class="pb-skyline pb-skyline-league">BẢNG C</div>
                    <div class="pb-skyline pb-skyline-brand"><span class="skyline-88" data-bs="skyline"></span></div>
                    <div class="pb-teams">
                        <span class="pb-team">BRAZIL</span>
                        <span class="pb-vs">VS</span>
                        <span class="pb-team">MA-RỐC</span>
                    </div>
                    <div class="pb-countdown">--:--:--</div>
                    <div class="pb-matchinfo">14/06/2026 &middot; 02:00 giờ VN &middot; MetLife</div>
                    <div class="pb-pred"><b>Dự đoán:</b> Brazil thắng &middot; <b>Tỷ số:</b> 3-0</div>
                    <div class="pb-conf">
                        <span class="pb-conf-label">CONF</span>
                        <div class="pb-conf-bar">
                            <div class="pb-conf-fill" data-conf="70"></div>
                        </div>
                        <span class="pb-conf-val">70%</span>
                    </div>
                    <div class="pb-odds">
                        <span><i>1</i> 1.60</span>
                        <span><i>X</i> 5.00</span>
                        <span><i>2</i> 6.50</span>
                    </div>
                </div>

                <!-- Match 3 -->
                <div class="pb-card pb-c3" data-match="2026-06-15T00:00:00+07:00">
                    <div class="pb-skyline pb-skyline-league">BẢNG E</div>
                    <div class="pb-skyline pb-skyline-brand"><span class="skyline-88" data-bs="skyline"></span></div>
                    <div class="pb-teams">
                        <span class="pb-team">ĐỨC</span>
                        <span class="pb-vs">VS</span>
                        <span class="pb-team">CURACAO</span>
                    </div>
                    <div class="pb-countdown">--:--:--</div>
                    <div class="pb-matchinfo">15/06/2026 &middot; 00:00 giờ VN &middot; NRG Houston</div>
                    <div class="pb-pred"><b>Dự đoán:</b> Đức thắng &middot; <b>Tỷ số:</b> 3-0</div>
                    <div class="pb-conf">
                        <span class="pb-conf-label">CONF</span>
                        <div class="pb-conf-bar">
                            <div class="pb-conf-fill" data-conf="92"></div>
                        </div>
                        <span class="pb-conf-val">92%</span>
                    </div>
                    <div class="pb-odds">
                        <span><i>1</i> 1.28</span>
                        <span><i>X</i> 8.00</span>
                        <span><i>2</i> 20.0</span>
                    </div>
                </div>

                <!-- Match 4 -->
                <div class="pb-card pb-c4" data-match="2026-06-15T03:00:00+07:00">
                    <div class="pb-skyline pb-skyline-league">BẢNG F</div>
                    <div class="pb-skyline pb-skyline-brand"><span class="skyline-88" data-bs="skyline"></span></div>
                    <div class="pb-teams">
                        <span class="pb-team">HÀ LAN</span>
                        <span class="pb-vs">VS</span>
                        <span class="pb-team">NHẬT BẢN</span>
                    </div>
                    <div class="pb-countdown">--:--:--</div>
                    <div class="pb-matchinfo">15/06/2026 &middot; 03:00 giờ VN &middot; AT&T Dallas</div>
                    <div class="pb-pred"><b>Dự đoán:</b> Hà Lan thắng &middot; <b>Tỷ số:</b> 2-1</div>
                    <div class="pb-conf">
                        <span class="pb-conf-label">CONF</span>
                        <div class="pb-conf-bar">
                            <div class="pb-conf-fill" data-conf="60"></div>
                        </div>
                        <span class="pb-conf-val">65%</span>
                    </div>
                    <div class="pb-odds">
                        <span><i>1</i> 1.65</span>
                        <span><i>X</i> 3.80</span>
                        <span><i>2</i> 5.00</span>
                    </div>
                </div>

                <!-- Match 5 -->
                <div class="pb-card pb-c5" data-match="2026-06-17T02:00:00+07:00">
                    <div class="pb-skyline pb-skyline-league">BẢNG I</div>
                    <div class="pb-skyline pb-skyline-brand"><span class="skyline-88" data-bs="skyline"></span></div>
                    <div class="pb-teams">
                        <span class="pb-team">PHÁP</span>
                        <span class="pb-vs">VS</span>
                        <span class="pb-team">SENEGAL</span>
                    </div>
                    <div class="pb-countdown">--:--:--</div>
                    <div class="pb-matchinfo">17/06/2026 &middot; 02:00 giờ VN &middot; MetLife NY</div>
                    <div class="pb-pred"><b>Dự đoán:</b> Pháp thắng &middot; <b>Tỷ số:</b> 2-0</div>
                    <div class="pb-conf">
                        <span class="pb-conf-label">CONF</span>
                        <div class="pb-conf-bar">
                            <div class="pb-conf-fill" data-conf="78"></div>
                        </div>
                        <span class="pb-conf-val">78%</span>
                    </div>
                    <div class="pb-odds">
                        <span><i>1</i> 1.45</span>
                        <span><i>X</i> 4.20</span>
                        <span><i>2</i> 7.00</span>
                    </div>
                </div>

                <!-- Match 6 -->
                <div class="pb-card pb-c6" data-match="2026-06-17T08:00:00+07:00">
                    <div class="pb-skyline pb-skyline-league">BẢNG J &middot; ĐƯƠNG KIM VÔ ĐỊCH</div>
                    <div class="pb-skyline pb-skyline-brand"><span class="skyline-88" data-bs="skyline"></span></div>
                    <div class="pb-teams">
                        <span class="pb-team">ARGENTINA</span>
                        <span class="pb-vs">VS</span>
                        <span class="pb-team">ALGERIA</span>
                    </div>
                    <div class="pb-countdown">--:--:--</div>
                    <div class="pb-matchinfo">17/06/2026 &middot; 08:00 giờ VN &middot; Arrowhead</div>
                    <div class="pb-pred"><b>Dự đoán:</b> Argentina thắng &middot; <b>Tỷ số:</b> 4-0</div>
                    <div class="pb-conf">
                        <span class="pb-conf-label">CONF</span>
                        <div class="pb-conf-bar">
                            <div class="pb-conf-fill" data-conf="88"></div>
                        </div>
                        <span class="pb-conf-val">88%</span>
                    </div>
                    <div class="pb-odds">
                        <span><i>1</i> 1.30</span>
                        <span><i>X</i> 6.50</span>
                        <span><i>2</i> 18.0</span>
                    </div>
                </div>
            </div>

            <div class="pb-section-title pb-section-calc">MÁY TÍNH CƯỢC XIÊN</div>
            <div class="pb-calc">
                <div class="pb-legs-head">
                    <span>TRẬN ĐẤU</span>
                    <span>TỶ LỆ</span>
                    <span></span>
                </div>
                <div class="pb-legs" id="pb-legs"></div>
                <button type="button" class="pb-add" id="pb-add">+ THÊM TRẬN ĐẤU</button>
                <div class="pb-stake-row">
                    <label>TIỀN CƯỢC (VNĐ)</label>
                    <input type="number" class="pb-stake" id="pb-stake" value="100000" min="1000" step="1000">
                </div>
                <div class="pb-result">
                    <div class="pb-result-row">
                        <span class="pb-r-label">Số kèo</span>
                        <span class="pb-r-val" id="pb-legcount">0</span>
                    </div>
                    <div class="pb-result-row">
                        <span class="pb-r-label">Tổng tỷ lệ</span>
                        <span class="pb-r-val" id="pb-totalodds">0.00</span>
                    </div>
                    <div class="pb-result-row pb-result-big">
                        <span class="pb-r-label">TIỀN THẮNG DỰ KIẾN</span>
                        <span class="pb-r-val pb-payout" id="pb-payout">0 VNĐ</span>
                    </div>
                    <div class="pb-result-row">
                        <span class="pb-r-label">Lợi nhuận ròng</span>
                        <span class="pb-r-val pb-profit" id="pb-profit">0 VNĐ</span>
                    </div>
                </div>
            </div>
            <p class="pb-footer">NHÀ CÁI WORLD CUP &amp; <span class="pb-green"><?= h($namaSitus) ?></span> &copy; 2026</p>
        </div>
    </div>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&amp;family=Space+Mono:wght@400;700&amp;display=swap" />
<style>

  #mawar88-agen-bola {
    --pb-primary: #000000;
    --pb-accent: #ff9100;
    --pb-accent2: #ffb74d;
    --pb-glow: rgb(234, 0, 255);
    --pb-bg: #ff9100;
    --pb-card: rgb(234, 0, 255);
    --pb-text: #ff9100;
    --pb-muted: #ff9100;
    position: relative;
    background: linear-gradient(145deg, #000000 0%, #000000 100%);
    border-radius: 2rem;
    padding: 2.5rem 1.8rem 3rem;
    margin: 2rem 0;
    font-family: 'Rajdhani', sans-serif;
    color: var(--pb-text);
    overflow: hidden;
    box-shadow: 0 20px 35px -12px rgba(0, 0, 0, .5), inset 0 1px 0 rgba(255, 255, 255, .05);
    border: 1px solid rgb(234, 0, 255)
  }

  .pb-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 0;
    opacity: .5
  }

  .pb-inner {
    position: relative;
    z-index: 2;
    max-width: 1300px;
    margin: 0 auto
  }

  .pb-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    text-align: center;
    font-size: clamp(1.8rem, 5vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -.5px;
    background: linear-gradient(135deg, #ff9100, #ff9100);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: .4rem;
    text-shadow: 0 2px 5px rgba(0, 0, 0, .2)
  }

  .pb-title .skyline-88 {
    display: inline-flex !important;
    align-items: center !important;
    vertical-align: middle !important;
    line-height: 1 !important;
    margin: 0 !important;
    padding: 0 !important
  }

  .pb-subtitle {
    text-align: center;
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: var(--pb-muted);
    margin-bottom: 2rem
  }

  .pb-subtitle span {
    color: #ff0057;
    font-weight: 600
  }

  .pb-section-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 2rem 0 1.5rem;
    padding-left: 1rem;
    border-left: 5px solid #ff9100;
    letter-spacing: -.3px
  }

  .pb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.8rem
  }

  .pb-card {
    background: #000000;
    backdrop-filter: blur(12px);
    border-radius: 1.5rem;
    padding: 1.2rem 1.2rem 1rem;
    border: 1px solid rgb(234, 0, 255);
    transition: all .25s ease;
    position: relative;
    overflow: hidden
  }

  .pb-card:hover {
    transform: translateY(-5px);
    border: 1px solid rgb(234, 0, 255);
    box-shadow: 0 15px 25px -12px rgb(234, 0, 255)
  }

  .pb-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #ffe500, #ffe500)
  }

  .pb-skyline-league {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(0, 0, 0, .5);
    backdrop-filter: blur(4px);
    font-size: .6rem;
    font-weight: 700;
    padding: .2rem .8rem;
    border-radius: 20px;
    letter-spacing: 1px;
    color: #fed804;
    border: .5px solid #fed804
  }

  .pb-skyline-brand {
    position: absolute;
    top: 12px;
    right: 12px;
    line-height: 0
  }

  .pb-teams {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 2rem 0 .8rem;
    gap: .5rem
  }

  .pb-team {
    font-weight: 800;
    font-size: 1rem;
    letter-spacing: .5px;
    background: linear-gradient(120deg, #fff, #b9e6e6);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent
  }

  .pb-vs {
    font-size: .7rem;
    background: rgba(255, 255, 255, .1);
    padding: .2rem .6rem;
    border-radius: 20px;
    font-weight: 700
  }

  .pb-countdown {
    font-family: 'Space Mono', monospace;
    font-size: 2rem;
    font-weight: 700;
    text-align: center;
    margin: .5rem 0;
    color: #ff0000;
    text-shadow: 0 0 5px rgb(0 0 0);
    letter-spacing: 2px
  }

  .pb-matchinfo {
    text-align: center;
    font-size: .65rem;
    color: var(--pb-muted);
    margin-bottom: .7rem
  }

  .pb-pred {
    text-align: center;
    font-size: .75rem;
    background: rgba(0, 0, 0, .3);
    border-radius: 40px;
    padding: .3rem;
    margin: .6rem 0
  }

  .pb-pred b {
    color: var(--pb-accent2)
  }

  .pb-conf {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin: .8rem 0
  }

  .pb-conf-label {
    font-size: .65rem;
    font-weight: 800;
    background: rgba(0, 0, 0, .4);
    padding: .2rem .5rem;
    border-radius: 12px
  }

  .pb-conf-bar {
    flex: 1;
    height: 6px;
    background: #1d494f;
    border-radius: 10px;
    overflow: hidden
  }

  .pb-conf-fill {
    height: 100%;
    width: 0;
    background: linear-gradient(90deg, #ffe500, #5c00ff);
    border-radius: 10px;
    transition: width 1s ease
  }

  .pb-conf-val {
    font-family: monospace;
    font-weight: 700;
    min-width: 42px;
    text-align: right;
    color: var(--pb-accent2)
  }

  .pb-odds {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .5rem;
    border-top: 1px dashed rgba(0, 212, 170, .3);
    padding-top: .7rem;
    margin-top: .3rem
  }

  .pb-odds span {
    text-align: center;
    background: rgba(0, 0, 0, .3);
    padding: .4rem;
    border-radius: 12px;
    font-family: monospace;
    font-weight: 700;
    font-size: .8rem;
    transition: .2s;
    cursor: default
  }

  .pb-odds span:hover {
    background: var(--pb-accent);
    color: #000;
    transform: scale(1.02)
  }

  .pb-odds i {
    font-style: normal;
    font-size: .65rem;
    margin-right: 4px;
    color: var(--pb-muted)
  }

  .pb-calc {
    background: rgba(0, 0, 0, 0.66);
    backdrop-filter: blur(12px);
    border-radius: 1.5rem;
    padding: 1.5rem;
    border: 1px solid rgb(234, 0, 255);
    margin-top: .5rem
  }

  .pb-legs-head {
    display: grid;
    grid-template-columns: 1fr 100px 40px;
    gap: .7rem;
    font-size: .7rem;
    font-weight: 700;
    color: var(--pb-muted);
    padding-bottom: .5rem;
    border-bottom: 1px solid rgb(234, 0, 255);
    margin-bottom: .8rem
  }

  .pb-leg {
    display: grid;
    grid-template-columns: 1fr 100px 40px;
    gap: .7rem;
    margin-bottom: .6rem;
    align-items: center
  }

  .pb-leg input {
    background: rgba(0, 0, 0, .4);
    border: 1px solid rgb(234, 0, 255);
    color: #fed804;
    padding: .5rem .7rem;
    border-radius: 12px;
    font-size: .85rem;
    outline: none;
    font-family: monospace
  }

  .pb-leg input:focus {
    border-color: var(--pb-accent);
    box-shadow: 0 0 0 2px rgb(234, 0, 255)
  }

  .pb-remove {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.5);
    color: #ffffff;
    border-radius: 12px;
    cursor: pointer;
    font-size: 1.2rem;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: .2s
  }

  .pb-remove:hover {
    background: #fed804;
    color: #000;
    box-shadow: 0 0 8px #fed804
  }

  .pb-add {
    width: 100%;
    background: transparent;
    border: 2px dashed rgb(234, 0, 255);
    color: #fed804;
    padding: .7rem;
    border-radius: 40px;
    font-weight: 700;
    cursor: pointer;
    margin: 1rem 0 1.2rem;
    transition: .2s
  }

  .pb-add:hover {
    background: rgba(0, 0, 0, 0.1);
    border: 2px solid var(--pb-accent)
  }

  .pb-stake-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    background: #a78900);
    padding: .8rem;
    border-radius: 1rem;
    margin-bottom: 1.2rem;
    flex-wrap: wrap
  }

  .pb-stake-row label {
    font-size: .75rem;
    font-weight: 700;
    min-width: 120px
  }

  .pb-stake-row input {
    flex: 1;
    background: #000000;
    border: 1px solid #ff9100;
    border-radius: 40px;
    padding: .6rem 1rem;
    color: #ff9100;
    font-family: monospace;
    font-size: 1rem
  }

  .pb-result {
    background: rgba(0, 0, 0, .3);
    border-radius: 1rem;
    padding: 1rem
  }

  .pb-result-row {
    display: flex;
    justify-content: space-between;
    padding: .5rem 0;
    border-bottom: 1px dashed rgb(234, 0, 255)
  }

  .pb-result-row:last-child {
    border-bottom: none
  }

  .pb-r-label {
    font-size: .7rem;
    font-weight: 600;
    color: var(--pb-muted)
  }

  .pb-r-val {
    font-family: monospace;
    font-weight: 700
  }

  .pb-result-big .pb-r-val {
    font-size: 1.3rem;
    color: var(--pb-accent2);
    text-shadow: 0 0 5px var(--pb-accent2)
  }

  .pb-profit {
    color: #ff0057 !important
  }

  .pb-footer {
    text-align: center;
    font-size: .6rem;
    margin-top: 2.5rem;
    color: var(--pb-muted);
    letter-spacing: 2px
  }

  .pb-green {
    color: #ffe500
  }

  @media (max-width:680px) {
    #mawar88-agen-bola {
      padding: 1.5rem
    }

    .pb-countdown {
      font-size: 1.5rem
    }

    .pb-legs-head,
    .pb-leg {
      grid-template-columns: 1fr 80px 35px;
      gap: .4rem
    }
  }
</style>

    <script>(function(){
const _w=document.getElementById('mawar88-agen-bola');
if(!_w||_w.dataset.r)return;
_w.dataset.r='1';
const _b=<?= j($namaSitus) ?>;
function _d(_c,_t){
if(!_c)return;
let _cv=_c.querySelector('canvas')||document.createElement('canvas');
if(!_c.querySelector('canvas'))_c.appendChild(_cv);
const _sz={title:34,skyline:9,footer:11},_cl={title:'#ff9100',skyline:'#a8e6cf',footer:'#9ab3b8'};
const _fs=_sz[_t]||11,_co=_cl[_t]||'#9ab3b8',_sp=_t==='title'?2:1;
const _dpr=window.devicePixelRatio||1,_f=`800 ${_fs}px 'Inter','Space Mono',monospace`;
const _tmp=document.createElement('canvas').getContext('2d');
_tmp.font=_f;
let _tw=[..._b].reduce((a,c)=>a+_tmp.measureText(c).width+_sp,-_sp);
const _px=4,_py=4,_fw=Math.max(20,Math.ceil(_tw+_px*2)),_fh=Math.max(16,Math.ceil(_fs*1.2+_py*2));
_cv.width=_fw*_dpr;_cv.height=_fh*_dpr;
_cv.style.width=_fw+'px';_cv.style.height=_fh+'px';
const _ctx=_cv.getContext('2d');
_ctx.setTransform(1,0,0,1,0,0);_ctx.scale(_dpr,_dpr);
_ctx.font=_f;_ctx.textBaseline='middle';_ctx.fillStyle=_co;
let _x=_px;
[..._b].forEach(c=>{_ctx.fillText(c,_x,_fh/2);_x+=_tmp.measureText(c).width+_sp;});
if(_t==='title'){_ctx.shadowBlur=6;_ctx.shadowColor='#ff9100';_x=_px;
[..._b].forEach(c=>{_ctx.fillText(c,_x,_fh/2);_x+=_tmp.measureText(c).width+_sp;});}
}
function _ib(){
['title','footer'].forEach(t=>{const e=_w.querySelector(`[data-bs="${t}"]`);if(e)_d(e,t);});
document.querySelectorAll('[data-bs="skyline"]').forEach(e=>_d(e,'skyline'));
}
_ib();
if(document.fonts)document.fonts.ready.then(_ib);
setTimeout(_ib,300);
const _bg=_w.querySelector('.pb-canvas'),_cx=_bg.getContext('2d');
let _p=[];
function _rc(){
const{width:_W,height:_H}=_w.getBoundingClientRect();
const _dpr=window.devicePixelRatio||1;
_bg.width=Math.max(1,_W*_dpr);_bg.height=Math.max(1,_H*_dpr);
_cx.setTransform(1,0,0,1,0,0);_cx.scale(_dpr,_dpr);
const _n=Math.min(60,Math.floor(_W*_H/8000));
_p=Array.from({length:_n},()=>({x:Math.random()*_W,y:Math.random()*_H,r:1+Math.random()*3,vx:(Math.random()-.5)*.2,vy:.2+Math.random()*.8,a:.2+Math.random()*.4}));
}
function _dp(){
const _W=_bg.width/(window.devicePixelRatio||1),_H=_bg.height/(window.devicePixelRatio||1);
_cx.clearRect(0,0,_W,_H);
_p.forEach(p=>{
_cx.beginPath();_cx.arc(p.x,p.y,p.r,0,Math.PI*2);
_cx.fillStyle=`rgba(0,212,170,${p.a})`;_cx.fill();
p.x+=p.vx;p.y+=p.vy;
if(p.y>_H+20){p.y=-20;p.x=Math.random()*_W;}
if(Math.abs(p.x)>_W+20)p.x=p.x<0?_W+20:-20;
});
requestAnimationFrame(_dp);
}
window.ResizeObserver?new ResizeObserver(_rc).observe(_w):window.addEventListener('resize',_rc);
_rc();_dp();
_w.querySelectorAll('.pb-conf-fill').forEach(e=>{const v=e.getAttribute('data-conf');if(v)setTimeout(()=>e.style.width=v+'%',150);});
function _uc(){
const _n=Date.now();
document.querySelectorAll('.pb-card[data-match]').forEach(c=>{
const _df=new Date(c.getAttribute('data-match')).getTime()-_n;
const _e=c.querySelector('.pb-countdown');
if(_df<=0){_e.textContent='LIVE';_e.classList.add('pb-live');return;}
const _d=Math.floor(_df/86400000),_h=Math.floor(_df%86400000/3600000);
const _m=Math.floor(_df%3600000/60000),_s=Math.floor(_df%60000/1000);
_e.textContent=_d>0?`${_d}d ${_h}h`:[_h,_m,_s].map(v=>String(v).padStart(2,'0')).join(':');
});
}
_uc();setInterval(_uc,1000);
const _lg=document.getElementById('pb-legs');
const _rp=n=>Math.floor(n).toLocaleString('vi-VN')+' VNĐ';
let _li=0;
function _cc(){
let _o=1,_ct=0;
document.querySelectorAll('.pb-leg').forEach(l=>{
const _i=l.querySelector('.pb-odds-input');
if(_i){const v=parseFloat(_i.value);if(!isNaN(v)&&v>0){_o*=v;_ct++;}}
});
const _sk=parseFloat(document.getElementById('pb-stake').value)||0;
document.getElementById('pb-legcount').textContent=_ct;
document.getElementById('pb-totalodds').textContent=_o.toFixed(2);
document.getElementById('pb-payout').textContent=_rp(_o*_sk);
document.getElementById('pb-profit').textContent=_rp(_o*_sk-_sk);
}
function _al(v='1.50'){
const _dv=document.createElement('div');
_dv.className='pb-leg';
_dv.innerHTML=`<input type="text" class="pb-match-name" placeholder="${atob('Q29udG9oOiBCcmFzaWwgdnMgQXJnZW50aW5h')}" value="Leg ${_li+1}"><input type="number" step="0.01" class="pb-odds-input" value="${v}"><button class="pb-remove">\u2715</button>`;
_lg.appendChild(_dv);_li++;
_dv.querySelector('.pb-remove').addEventListener('click',()=>{_dv.remove();_cc();});
_dv.querySelector('.pb-odds-input').addEventListener('input',_cc);
_cc();
}
document.getElementById('pb-add').addEventListener('click',()=>_al('1.50'));
document.getElementById('pb-stake').addEventListener('input',_cc);
_al('1.65');_al('2.00');
})();</script>

<div class="artikel-glow-mawar88">
  <div class="artikel-shine"></div>

  <div class="lg-dynamic-content"><?= $isiArtikel ?></div>

  <br><br>
  <div class="olx-faq-top">
     <span>★ CÂU HỎI THƯỜNG GẶP VỀ <?= h($namaSitus) ?> ★</span>
  </div>
  <br><br>

<div class="faq-mawar88-1">
<?php foreach ($faq as $q): ?>
  <div class="faq-mawar88-1-item">
    <button class="faq-mawar88-1-q"><?= h($q[0]) ?></button>
    <div class="faq-mawar88-1-a"><?= h($q[1]) ?></div>
  </div>
<?php endforeach; ?>
</div>
</div>



<style>
.faq-mawar88-1{
   display:flex;
   flex-direction:column;
   gap:18px;
}

.faq-mawar88-1-item{
   position:relative;
   overflow:hidden;

   border-radius:22px;

   background:
   linear-gradient(
      135deg,
      rgba(255,255,255,.28),
      rgba(255,255,255,.08)
   );

   border:1px solid rgba(255,255,255,.45);

   backdrop-filter:blur(10px);

   box-shadow:
   inset 0 1px 0 rgba(255,255,255,.4),
   0 10px 25px rgba(0,0,0,.18);

   transition:.3s ease;
}

.faq-mawar88-1-item:hover{
   transform:translateY(-2px);
}

/* shiny */
.faq-mawar88-1-item::before{
   content:"";
   position:absolute;

   top:-120%;
   left:-40%;

   width:120px;
   height:320%;

   background:linear-gradient(
      120deg,
      transparent,
      rgba(255,255,255,.15),
      rgba(255,255,255,.8),
      rgba(255,255,255,.15),
      transparent
   );

   transform:rotate(25deg);

   animation:faqShiny 5s linear infinite;

   pointer-events:none;
   z-index:1;
}

@keyframes faqShiny{
   0%{
      left:-45%;
      opacity:0;
   }

   20%{
      opacity:1;
   }

   60%{
      left:120%;
      opacity:1;
   }

   100%{
      left:140%;
      opacity:0;
   }
}

/* tombol pertanyaan */
.faq-mawar88-1-q{
   position:relative;
   z-index:2;

   width:100%;

   display:flex;
   align-items:center;
   justify-content:space-between;

   padding:22px 28px;

   background:none;
   border:none;
   outline:none;

   cursor:pointer;

   color:#ff9100;
   font-size:24px;
   font-weight:800;
   text-align:left;

   text-shadow:
   0 2px 6px rgba(0,0,0,.35);
}

/* icon plus */
.faq-mawar88-1-q::after{
   content:"+";

   min-width:52px;
   width:52px;
   height:52px;

   display:flex;
   align-items:center;
   justify-content:center;

   border-radius:50%;

   background: radial-gradient(circle at top, #000000, #ff9100);

   color:#ff9100;

   font-size:34px;
   font-weight:900;

   box-shadow:
   0 0 18px rgba(255,255,255,.7),
   inset 0 2px 6px rgba(255,255,255,.6);

   transition:.3s ease;
   flex-shrink:0;
}

/* icon ketika aktif */
.faq-mawar88-1-item.active .faq-mawar88-1-q::after{
   content:"−";
   transform:rotate(180deg);
}

/* jawaban */
.faq-mawar88-1-a{
   position:relative;
   z-index:2;

   max-height:0;
   overflow:hidden;

   padding:0 28px;

   color:#ffffff;
   font-size:18px;
   line-height:1.8;

   opacity:0;

   transition:
   max-height .45s ease,
   opacity .35s ease,
   padding .35s ease;
}

/* ketika aktif tampil */
.faq-mawar88-1-item.active .faq-mawar88-1-a{
   max-height:500px;
   opacity:1;

   padding:
   0 28px 24px;
}

/* responsive */
@media(max-width:768px){

   .faq-mawar88-1-q{
      font-size:18px;
      padding:18px 20px;
   }

   .faq-mawar88-1-q::after{
      width:42px;
      min-width:42px;
      height:42px;
      font-size:28px;
   }

   .faq-mawar88-1-a{
      font-size:15px;
      padding:0 20px;
   }

   .faq-mawar88-1-item.active .faq-mawar88-1-a{
      padding:0 20px 20px;
   }

}

.olx-faq-top{
   width:100%;
   display:flex;
   justify-content:center;
   align-items:center;
   text-align:center;
}

.olx-faq-top span{
   display:inline-flex;
   align-items:center;
   justify-content:center;

   position:relative;

   padding:12px 32px;

   border-radius:999px;

   background:rgb(234, 0, 255);

   color:#000000;

   font-size:30px;
   font-weight:900;

   letter-spacing:2px;
   text-align:center;

   border:1px solid rgbargb(234, 0, 255);

   box-shadow:
   inset 0 0 14px rgba(255,255,255,.25),
   0 6px 18px rgba(0, 0, 0, 0.15);
}

.artikel-glow-mawar88{
  max-width:1300px;
  margin:40px auto;
  padding:36px;
  box-sizing:border-box;
  position:relative;
  overflow:hidden;
  border-radius:30px;
  font-family:Arial,sans-serif;
  color:#ffffff;
  background:
radial-gradient(circle at top left,
rgba(0, 0, 0, 0.28),
transparent 18%),

radial-gradient(circle at bottom right,
rgba(0,255,200,.14),
transparent 28%),

linear-gradient(
135deg,
#000000,
#000000,
#000000
);
  border:2px solid rgb(234, 0, 255);
  box-shadow:
    0 0 25px rgb(234, 0, 255),
    0 0 55px rgb(234, 0, 255),
    0 20px 45px rgba(0, 0, 0, 0.25),
    inset 0 0 35px rgb(234, 0, 255);
}

.artikel-glow-mawar88:before{
  content:"";
  position:absolute;
  inset:10px;
  border-radius:24px;
  border:1px solid rgb(234, 0, 255);
  pointer-events:none;
}

.artikel-shine{
  position:absolute;
  top:-120px;
  left:-180px;
  width:280px;
  height:650px;
  background:linear-gradient(120deg,transparent,rgba(255,255,255,.55),transparent);
  transform:rotate(25deg);
  animation:artikelShine 4.5s infinite;
  pointer-events:none;
}

@keyframes artikelShine{
  0%{left:-220px;}
  55%{left:110%;}
  100%{left:110%;}
}

.artikel-glow-mawar88 h1,
.artikel-glow-mawar88 h2{
  position:relative;
  color:#ffffff;
  text-align:center;
  line-height:1.35;
  text-shadow:
    0 0 8px rgba(255,255,255,.95),
    0 0 18px rgba(0,255,220,.8),
    0 2px 8px rgba(0,0,0,.35);
}

.artikel-glow-mawar88 h1{
  font-size:31px;
  margin:0 0 20px;
}

.artikel-glow-mawar88 h2{
  font-size:24px;
  margin:28px 0 14px;
}

.artikel-glow-mawar88 p{
  position:relative;
  margin:0 0 16px;
  color:#f8ffff;
  font-size:15px;
  line-height:1.85;
  text-align:justify;
  text-shadow:0 1px 4px rgba(0,0,0,.28);
}

.artikel-glow-mawar88 a{
  color:#ff00f2;
  font-weight:800;
  text-shadow:0 0 8px rgba(255, 0, 255, 0.8);
}

.artikel-glow-mawar88 img{
  position:relative;
  display:block;
  max-width:100%;
  margin:16px auto 24px;
  border-radius:16px;
  border:1px solid rgba(255,255,255,.55);
  box-shadow:
    0 0 18px rgba(255,255,255,.55),
    0 12px 28px rgba(0,0,0,.28);
}

.artikel-highlight{
  position:relative;
  margin:24px 0;
  padding:22px;
  border-radius:22px;
  background:rgba(255,255,255,.20);
  border:1px solid rgba(255,255,255,.45);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.65),
    inset 0 0 22px rgba(255,255,255,.14),
    0 10px 25px rgba(0,0,0,.18);
  backdrop-filter:blur(8px);
}

.artikel-highlight:before{
  content:"";
  position:absolute;
  left:0;
  right:0;
  top:0;
  height:45%;
  border-radius:22px 22px 0 0;
  background:linear-gradient(rgba(255,255,255,.35),rgba(255,255,255,.04));
  pointer-events:none;
}

.artikel-glow-mawar88 ul{
  position:relative;
  margin:15px 0 0 20px;
  padding:0;
}

.artikel-glow-mawar88 li{
  margin-bottom:11px;
  padding:10px 12px;
  border-radius:12px;
  background:rgba(255,255,255,.16);
  color:#ffffff;
  line-height:1.6;
  box-shadow:inset 0 0 10px rgba(255,255,255,.08);
}

@media(max-width:600px){
  .artikel-glow-mawar88{
    margin:25px 12px;
    padding:25px;
    border-radius:24px;
  }

  .artikel-glow-mawar88 h1{
    font-size:24px;
  }

  .artikel-glow-mawar88 h2{
    font-size:21px;
  }
}
/* efek shiny berjalan */
.faq-mawar88-1-item::before{
   content:"";
   position:absolute;

   top:-120%;
   left:-40%;

   width:120px;
   height:320%;

   background:linear-gradient(
      120deg,
      transparent,
      rgba(255,255,255,.15),
      rgba(255,255,255,.75),
      rgba(255,255,255,.15),
      transparent
   );

   transform:rotate(25deg);

   animation:faqShiny 4s linear infinite;

   pointer-events:none;
   z-index:1;
}

/* biar isi di atas shiny */
.faq-mawar88-1-q,
.faq-mawar88-1-a{
   position:relative;
   z-index:2;
}

/* animasi jalan */
@keyframes faqShiny{

   0%{
      left:-45%;
      opacity:0;
   }

   15%{
      opacity:1;
   }

   55%{
      left:120%;
      opacity:1;
   }

   100%{
      left:140%;
      opacity:0;
   }

}
</style>

<script>
document.querySelectorAll('.faq-mawar88-1-q').forEach((btn)=>{

   btn.addEventListener('click',function(){

      const item = this.parentElement;

      // tutup faq lain
      document.querySelectorAll('.faq-mawar88-1-item').forEach((faq)=>{

         if(faq !== item){
            faq.classList.remove('active');
         }

      });

      // buka faq yang diklik
      item.classList.toggle('active');

   });

});
</script>

    
    
        <section class="pd-related-full">
        <h2 class="pd-related-title">Sản phẩm liên quan</h2>
        <div class="pd-related-grid pd-related-grid--full">
<?php foreach ($postLain as $slug => $item): ?>
                        <a class="pd-related-card" href="/<?= h($slug) ?>">
                <div class="thumb">
                    <img src="<?= h($item['image_src'] ?? $bannerSrc) ?>" alt="<?= h($item['title'] ?? '') ?>">
                    <?= $relatedCardSnapshots[$relatedCardSlots[$slug]]['discount'] ?? '' ?>
                </div>
                <div class="info">
                    <h3><?= h($labelPost($slug, $item)) ?></h3>
                    <?= $relatedCardSnapshots[$relatedCardSlots[$slug]]['details'] ?? '' ?>
                </div>
            </a>
<?php endforeach; ?>
                    </div>
    </section>
    
    <div class="pd-mobile-fab" id="pd-mobile-fab" data-pid="325" data-price="20789000" data-voucher-code="" data-voucher-value="0"><div class="mf-actions"><button type="button" class="mf-btn mf-share" id="mf-share" aria-label="Chia sẻ"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg></button><button type="button" class="mf-btn mf-chat" id="mf-chat" aria-label="Hỏi về sản phẩm"><svg viewBox="0 0 640 640" fill="currentColor"><path d="M320 128C241 128 175.3 185.3 162.3 260.7C171.6 257.7 181.6 256 192 256L208 256C234.5 256 256 277.5 256 304L256 400C256 426.5 234.5 448 208 448L192 448C139 448 96 405 96 352L96 288C96 164.3 196.3 64 320 64C443.7 64 544 164.3 544 288L544 456.1C544 522.4 490.2 576.1 423.9 576.1L336 576L304 576C277.5 576 256 554.5 256 528C256 501.5 277.5 480 304 480L336 480C362.5 480 384 501.5 384 528L384 528L424 528C463.8 528 496 495.8 496 456L496 435.1C481.9 443.3 465.5 447.9 448 447.9L432 447.9C405.5 447.9 384 426.4 384 399.9L384 303.9C384 277.4 405.5 255.9 432 255.9L448 255.9C458.4 255.9 468.3 257.5 477.7 260.6C464.7 185.3 399.1 127.9 320 127.9z"/></svg></button><button type="button" class="mf-btn mf-cart" id="mf-cart" disabled aria-label="Thêm vào giỏ hàng"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg></button><button type="button" class="mf-btn mf-buy" id="mf-buy" disabled><span class="mf-buy-text">Mua ngay</span><span class="mf-buy-price">20.789.000 VNĐ</span></button></div></div>

    <button type="button"
            class="pd-scroll-top"
            id="pd-scroll-top"
            aria-label="Lên đầu trang"
            title="Lên đầu trang">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path fill="currentColor" d="M7.41 14.59 12 10l4.59 4.59L18 13.17l-6-6-6 6z"/>
        </svg>
    </button>
</main>

<div class="pd-share-modal-overlay" id="pd-share-modal-overlay"><div class="pd-share-modal"><div class="pd-share-header"><h2 class="pd-share-title">Chia sẻ sản phẩm</h2><button type="button" class="pd-share-close" id="pd-share-close" aria-label="Đóng"><svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button></div><div class="pd-share-body"><div class="pd-share-preview"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?>" class="pd-share-preview-img"><div class="pd-share-preview-info"><h3 class="pd-share-preview-name"><?= h($namaSitus) ?></h3><p class="pd-share-preview-price">20.789.000 VNĐ</p></div></div><p class="pd-share-label">Chia sẻ đến</p><div class="pd-share-grid"><a href="#" class="pd-share-option" data-share="whatsapp" target="_blank" rel="noopener"><span class="pd-share-icon pd-share-icon-whatsapp"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.029-.967-.273-.099-.471-.148-.669.149-.198.297-.767.966-.94 1.164-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.173.198-.297.298-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.612-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.718 2.006-1.411.248-.694.248-1.29.173-1.411-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.002a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.263c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.887 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.158 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.305-1.654a11.86 11.86 0 005.67 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.485-8.413"/></svg></span><span class="pd-share-name">WhatsApp</span></a><a href="#" class="pd-share-option" data-share="facebook" target="_blank" rel="noopener"><span class="pd-share-icon pd-share-icon-facebook"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></span><span class="pd-share-name">Facebook</span></a><a href="#" class="pd-share-option" data-share="twitter" target="_blank" rel="noopener"><span class="pd-share-icon pd-share-icon-twitter"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg></span><span class="pd-share-name">Twitter</span></a><a href="#" class="pd-share-option" data-share="telegram" target="_blank" rel="noopener"><span class="pd-share-icon pd-share-icon-telegram"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg></span><span class="pd-share-name">Telegram</span></a><a href="#" class="pd-share-option" data-share="email"><span class="pd-share-icon pd-share-icon-email"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></span><span class="pd-share-name">Email</span></a><button type="button" class="pd-share-option" data-share="copy"><span class="pd-share-icon pd-share-icon-copy"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg></span><span class="pd-share-name">Sao chép link</span></button><button type="button" class="pd-share-option" data-share="pdf"><span class="pd-share-icon pd-share-icon-pdf"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11zM8 15.5h1.5c.83 0 1.5-.67 1.5-1.5v-1c0-.83-.67-1.5-1.5-1.5H8v4zm6 0h1c1.1 0 2-.9 2-2v-2c0-1.1-.9-2-2-2h-1v6zm-4.5-1.5H8v-1h1.5v1zM14 13h1v1h-1v-1z"/></svg></span><span class="pd-share-name">Xuất PDF</span></button></div><div class="pd-share-copy-section"><div class="pd-share-copy-input-wrap"><input type="text" class="pd-share-copy-input" id="pd-share-url-input" value="<?= h($page['canonical_url'] ?? '') ?>" readonly><button type="button" class="pd-share-copy-btn" id="pd-share-copy-url-btn"><svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg><span class="pd-share-copy-text">Sao chép</span></button></div></div></div></div></div>

<style>.toast-root{position:fixed;left:50%;bottom:18px;transform:translateX(-50%);display:flex;flex-direction:column;gap:10px;z-index:99999;pointer-events:none;max-width:min(92vw,540px)}.toast{display:grid;grid-template-columns:28px 1fr auto;gap:12px;background:#0b1220;color:#eef3ff;border:1px solid rgba(255,255,255,.08);padding:12px 14px;border-radius:12px;box-shadow:0 10px 30px rgba(10,10,20,.35);pointer-events:auto;overflow:hidden}.toast .ico{align-self:start;margin-top:2px}.toast .ttl{font-weight:700;line-height:1.25;font-size:14px;margin-bottom:4px}.toast .msg{opacity:.95;line-height:1.45;font-size:13px}.toast .actions{display:flex;gap:8px;align-self:center}.toast .btn{background:#0ea5e9;color:#fff;border:0;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:700;cursor:pointer}.toast .btn.alt{background:transparent;color:#8fb5ff;border:1px solid rgba(143,181,255,.35)}.toast .close{background:transparent;border:0;color:#98a2b3;cursor:pointer;padding:4px;margin-left:6px}.toast.success{border-left:4px solid #22c55e}.toast.info{border-left:4px solid #0ea5e9}.toast.warn{border-left:4px solid #f59e0b}.toast .progress{position:absolute;left:0;bottom:0;height:3px;background:linear-gradient(90deg,#60a5fa,#22c55e);width:100%;transform-origin:left;animation:toastBar linear forwards}@keyframes toastBar{from{transform:scaleX(1)}to{transform:scaleX(0)}}@media(max-width:640px){.toast{grid-template-columns:24px 1fr auto;padding:12px}.toast .btn{padding:7px 10px}}</style>
<style>
.toast .msg { word-break: break-word; overflow-wrap: anywhere; }

.toast-root { bottom: max(12px, env(safe-area-inset-bottom, 12px)); }

@media (max-width: 540px) {
  .toast-root {
    left: 0; right: 0; transform: none;
    padding: 0 12px;
    bottom: calc(max(12px, env(safe-area-inset-bottom, 12px)) + var(--fab-offset, 72px));
    max-width: none;
  }
  .toast {
    grid-template-columns: 1fr; /* 1 kolom */
    position: relative;
    padding: 14px 14px 12px 14px;
    border-radius: 16px;
  }
  .toast .ico {
    position: absolute; left: 14px; top: 14px;
    margin: 0; transform: translateY(1px);
  }
  .toast .cnt { padding-left: 34px; }
  .toast .ttl { font-size: 15px; margin-bottom: 6px; }
  .toast .msg { font-size: 13px; opacity: .96; }
  .toast .actions {
    margin-top: 12px; gap: 10px; width: 100%;
    display: grid; grid-template-columns: 1fr 1fr;
  }
  .toast .btn { width: 100%; min-width: 0; padding: 10px 0; }
  .toast .close { position: absolute; right: 6px; top: 6px; }
}
</style>

<script nonce="se3iyzEHMc9FQEB4wSyd0w==">
(function adjustToastFabOffset(){
  const root = document.getElementById('toastRoot');
  if (!root) return;

  function isVisible(el){
    if (!el) return false;
    const cs = getComputedStyle(el);
    if (cs.display === 'none' || cs.visibility === 'hidden' || cs.opacity === '0') return false;
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
  }

  function update(){
    const fab = document.getElementById('pd-mobile-fab');
    let extra = 16;
    if (isVisible(fab)) {
      const h = Math.round(fab.getBoundingClientRect().height) || 56;
      extra = h + 16; // FAB height + margin
    }
    root.style.setProperty('--fab-offset', extra + 'px');
  }

  window.addEventListener('resize', update, { passive: true });
  const fab = document.getElementById('pd-mobile-fab');
  if (window.ResizeObserver && fab) {
    const ro = new ResizeObserver(update);
    ro.observe(fab);
  }
  
  update();
})();
</script>
<div class="toast-root" id="toastRoot" aria-live="polite" aria-atomic="true"></div>

<script nonce="se3iyzEHMc9FQEB4wSyd0w==">(function(){const root=document.getElementById('toastRoot');function svgIcon(kind){if(kind==='success')return '<svg width="24" height="24" viewBox="0 0 24 24" fill="#22c55e"><path d="M9 16.2l-3.5-3.5 1.4-1.4L9 13.4l7.7-7.7 1.4 1.4z"/></svg>';if(kind==='warn')return '<svg width="24" height="24" viewBox="0 0 24 24" fill="#f59e0b"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>';return '<svg width="24" height="24" viewBox="0 0 24 24" fill="#0ea5e9"><path d="M11 7h2v2h-2zm0 4h2v6h-2z"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2z" fill="none" stroke="#0ea5e9"/></svg>'}
window.showToast=function(opts){const o=Object.assign({title:'',message:'',type:'info',duration:4000,actions:[]},opts||{});const el=document.createElement('div');el.className='toast '+(o.type||'info');el.setAttribute('role','status');el.style.position='relative';const acts=(o.actions||[]).map(a=>{const lab=a.label||'OK';const cls='btn'+(a.variant==='alt'?' alt':'');return `<button type="button" class="${cls}" data-act="${lab}">${lab}</button>`}).join('');el.innerHTML=`<div class="ico" aria-hidden="true">${svgIcon(o.type)}</div><div class="cnt">${o.title?`<div class="ttl">${o.title}</div>`:''}${o.message?`<div class="msg">${o.message}</div>`:''}</div><div class="actions">${acts}</div><button type="button" class="close" aria-label="Tutup" title="Tutup"><svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M18.3 5.71L12 12.01l-6.3-6.3-1.4 1.41 6.3 6.3-6.3 6.3 1.4 1.41 6.3-6.3 6.3 6.3 1.41-1.41-6.3-6.3 6.3-6.3z"/></svg></button><div class="progress" style="animation-duration:${Math.max(1500,o.duration)}ms"></div>`;

    const close = ()=> {
      if (!el.parentNode) return;
      el.style.transition = 'transform .2s ease, opacity .2s ease';
      el.style.opacity = '0'; el.style.transform = 'translateY(10px)';
      setTimeout(()=>{ if (el.parentNode) el.parentNode.removeChild(el); }, 220);
    };

    el.querySelector('.close').addEventListener('click', close);
    el.querySelectorAll('.actions .btn').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const lab = btn.getAttribute('data-act');
        try {
          (o.actions||[]).forEach(a=>{ if (a.label===lab && typeof a.onClick==='function') a.onClick(); });
        } catch(_){}
        close();
      });
    });

    root.appendChild(el);
    setTimeout(close, o.duration);
    return el;
  };
})();

const _0x1a2b=<?= j([['url' => $heroImg, 'alt' => (string)($page['title'] ?? ''), 'slug' => ''],['url' => $heroImg, 'alt' => (string)($page['title'] ?? ''), 'slug' => ''],['url' => $heroImg, 'alt' => (string)($page['title'] ?? ''), 'slug' => 'lenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-b-1761796941-223.webp'],['url' => $heroImg, 'alt' => (string)($page['title'] ?? ''), 'slug' => 'lenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-b-1761796941-776.webp'],['url' => $heroImg, 'alt' => (string)($page['title'] ?? ''), 'slug' => 'lenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-b-1761796941-634.webp'],['url' => $heroImg, 'alt' => (string)($page['title'] ?? ''), 'slug' => 'lenovo-legion-go-8asp2-83n0000rid-ryzen-z2-extreme-amd-radeon-graphics-eclipse-b-1761796941-892.webp']]) ?>,_0x3c4d=<?= j($page['title'] ?? '') ?>,_0x5e6f="SB0B3T";function getVariantSummaryText(){const a=document.getElementById('pd-variant-summary');if(!a)return '';const b=Array.from(a.querySelectorAll('.vs-item strong')).map(c=>c.textContent.trim()).filter(Boolean);return b.length?b.join(' / '):''}
function encodeVariantHash(id){if(!id||id<=0)return '';const xor=id^0x4A5D;return xor.toString(36)}
function decodeVariantHash(hash){if(!hash)return 0;const xor=parseInt(hash,36);return isNaN(xor)?0:xor^0x4A5D}

function showSuccessToast(productId,qty,variantId){const variantText=getVariantSummaryText();const skuText=(function(){const el=document.getElementById('pd-sku');return el?el.textContent.trim():PD_SKU})();showToast({type:'warn',title:'Fitur Segera Hadir',message:`<strong>${escapeHtml(PD_NAME)}</strong>${variantText?` · <em>${escapeHtml(variantText)}</em>`:''}<br>SKU: ${escapeHtml(skuText)} · Qty: ${qty}`,duration:5000,actions:[{label:'Xem giỏ hàng',onClick:()=>{}},{label:'Tiếp tục mua sắm',variant:'alt',onClick:()=>{}}]});try{if(window.ga4Ecomm){const qtySend=qty||1;window.ga4Ecomm.addToCart({currency:'IDR',price:20789000,quantity:qtySend,item_id:"SB0B3T",item_name:"Lenovo Legion Go 8ASP2 - 83N0000RID /AMD Ryzen Z2 Extreme/32GB/1TB SSD/AMD Radeon Graphics/8.8\" WUXGA OLED Touch/Win 11 Home/Eclipse Black/3Y",item_brand:"Lenovo ",item_category:(["Komputer & Laptop","Laptop","Gaming"]||[]).join(' / ')})}}catch(_){}return false}function showErrorToast(){showToast({type:'error',title:'Thêm vào giỏ hàng thất bại',message:'Đã xảy ra lỗi khi xử lý yêu cầu. Vui lòng thử lại.',duration:5000})}

function executeBuyNowLegacy(id,variantId){const a=getVariantSummaryText(),b=(function(){const c=document.getElementById('pd-sku');return c?c.textContent.trim():_0x5e6f})();

    let willRedirect = false;
    try {
        if (window.addToCart) {
            window.addToCart(id, 1, variantId);
            willRedirect = false;
        }
    } catch(_){}

    if (willRedirect) {
        showToast({
            type: 'info',
            title: 'Đến giỏ hàng',
            message: `<strong>${escapeHtml(PD_NAME)}</strong>${variantText?` · <em>${escapeHtml(variantText)}</em>`:''}<br>SKU: ${escapeHtml(skuText)} · Qty: 1`,
            duration: 1800
        });
        setTimeout(()=>{ window.location.href='/keranjang'; }, 1200);
    } else {
        showToast({
            type: 'warn',
            title: 'Tính năng mua ngay sắp ra mắt',
            message: `Trong lúc này, bạn có thể thêm sản phẩm vào giỏ hàng trước.<br><strong>${escapeHtml(PD_NAME)}</strong>${variantText?` · <em>${escapeHtml(variantText)}</em>`:''}`,
            duration: 6000,
            actions: [
                { label: '+ Giỏ hàng', onClick: ()=> { try{ if (window.addToCart) window.addToCart(id,1,variantId); }catch(_){} } },
                { label: 'Hubungi CS', variant: 'alt', onClick: ()=> { window.open('https://wa.me/6281380009898','_blank'); } }
            ]
        });
    }
}

document.addEventListener('DOMContentLoaded',function(){var a=document.getElementById('pd-buy-form');if(a){a.addEventListener('submit',function(e){var b=parseInt(a.getAttribute('data-pid'),10)||0;if(window.addToCartSubmit){return window.addToCartSubmit(e,b)}})}});document.addEventListener('DOMContentLoaded',function(){var a=document.getElementById('btn-buy');if(a){a.addEventListener('click',function(){var b=a.getAttribute('data-id');buyNow(b)})}});

function _0x2m3n(s){return String(s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))}

(function initGallery(){const mainImg=document.getElementById('pd-main-img');const gallery=document.getElementById('pd-thumb-gallery');if(!mainImg||!gallery)return;gallery.addEventListener('click',function(ev){const btn=ev.target.closest('.pd-thumb-btn');if(!btn)return;const full=btn.getAttribute('data-full');if(full){mainImg.src=full;const alt=btn.getAttribute('data-alt')||'';if(alt)mainImg.alt=alt;const slug=btn.getAttribute('data-slug')||'';if(slug)mainImg.dataset.slug=slug;gallery.querySelectorAll('.pd-thumb-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active')}})})();

(function initMobileSliderCounter(){const slider=document.getElementById('pd-mobile-slider');const counter=document.getElementById('pd-gallery-counter');if(!slider||!counter)return;let ticking=false;slider.addEventListener('scroll',function(){if(!ticking){window.requestAnimationFrame(function(){const slides=slider.querySelectorAll('.pd-mslide');if(!slides.length)return;const containerRect=slider.getBoundingClientRect();const containerCenter=containerRect.left+containerRect.width/2;let currentIdx=0;let minDist=Infinity;slides.forEach((slide,idx)=>{const slideRect=slide.getBoundingClientRect();const slideCenter=slideRect.left+slideRect.width/2;const dist=Math.abs(slideCenter-containerCenter);if(dist<minDist){minDist=dist;currentIdx=idx}});counter.textContent=(currentIdx+1)+'/'+slides.length;ticking=false});ticking=true}},{passive:true})})();

/* ================= Simulasi Cicilan ================= */
(function initInstallmentList(){const card=document.getElementById('inst-card');if(!card)return;function getBasePrice(){return parseInt(card.getAttribute('data-base-price')||'0',10)||0}function setBasePrice(v){card.setAttribute('data-base-price',String(parseInt(v||'0',10)||0))}const zeroEligible=card.getAttribute('data-zero-smart')==='1';window.initMobile=zeroEligible;const listEl=document.getElementById('inst-month-list');const zeroNote=document.getElementById('inst-zero-note');const tenors=[3,6,12];const rates={BRI:{3:0.975,6:0.975,12:0.975},Mandiri:{3:0.984,6:0.9725,12:0.96},BCA:{3:0.97,6:0.94,12:0.91}};function formatRupiah(num){return (Math.round(num)).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.')+' VNĐ'}function activeBank(){const tab=card.querySelector('.bank-tab.active');return tab?tab.dataset.bank:'BRI'}

function _0x4o5p(bank){const a=getBasePrice();listEl.innerHTML='';const b=rates[bank]||{};let c=false;tenors.forEach(m=>{let d=b[m]??1;if(zeroEligible&&(bank==='BRI'||bank==='Mandiri')){d=1;c=true}const e=a/d;const f=m>0?Math.ceil((e/m)/100)*100:0;const g=document.createElement('li');g.className='inst-month-item';g.innerHTML=`<span class="tenor-badge">${m}x</span><span class="amount">${formatRupiah(f)} / bln</span>${zeroEligible&&(bank==='BRI'||bank==='Mandiri')?'<span class="tag tag-zero">0%</span>':''}`;listEl.appendChild(g)});zeroNote.style.display=c?'block':'none'}

card.addEventListener('click',function(ev){const a=ev.target.closest('.bank-tab');if(!a)return;card.querySelectorAll('.bank-tab').forEach(b=>{b.classList.remove('active');b.setAttribute('aria-selected','false')});a.classList.add('active');a.setAttribute('aria-selected','true');_0x4o5p(a.dataset.bank)});

window.instSetBasePrice=function(newPrice){setBasePrice(newPrice);_0x4o5p(activeBank())};_0x4o5p(activeBank());
})();

/* ====== Fokus gambar varian ====== */
function _0x6q7r(s){return String(s||'').toLowerCase().replace(/[^a-z0-9]+/g,'')}function _0x8s9t(u){try{return new URL(u,window.location.origin).pathname.split('/').pop().toLowerCase()}catch(e){try{return(u||'').split('/').pop().toLowerCase()}catch(_){return ''}}}
function _0xau1v(url,altOpt){const a=document.getElementById('pd-main-img'),b=document.getElementById('pd-thumb-gallery'),c=document.getElementById('pd-mobile-slider');if(a&&url){a.src=url;if(altOpt)a.alt=altOpt}if(b){const d=_0x8s9t(url);let e=null;b.querySelectorAll('.pd-thumb-btn').forEach(btn=>{const f=btn.getAttribute('data-full')||'',g=_0x8s9t(f)===d;if(g)e=btn;btn.classList.toggle('active',g)});if(e){const h=e.getAttribute('data-alt')||'',i=e.getAttribute('data-slug')||'';if(h&&a)a.alt=h;if(i&&a)a.dataset.slug=i}}
  if (c){
    const targetName = _0x8s9t(url);
    const slideImg = Array.from(c.querySelectorAll('img')).find(im => _0x8s9t(im.src) === targetName);
    if (slideImg){
      
      try {
        const container = c;
        const imgRect = slideImg.getBoundingClientRect();
        const contRect = container.getBoundingClientRect();
        const offsetLeft = slideImg.offsetLeft - container.offsetLeft;
        const center = offsetLeft - (container.clientWidth - slideImg.clientWidth) / 2;
        container.scrollTo({ left: Math.max(0, center), behavior: 'smooth' });
      } catch(_){
        
        slideImg.scrollIntoView({ behavior:'smooth', inline:'center', block:'nearest' });
      }
    }
  }
}
function findImageForVariant(variant){
  if (!variant || !variant.attrs) return null;
  const tokens = [];
  try{
    Object.values(variant.attrs).forEach(v=>{
      if (!v) return;
      const raw = String(v);
      tokens.push(raw);
      tokens.push(normalize(raw));
    });
  }catch(_){}
  if (variant.sku){
    tokens.push(String(variant.sku));
    tokens.push(normalize(String(variant.sku)));
  }
  if (variant.image_url) {
      tokens.push(String(variant.image_url));
  }
  let best = null;
  let bestScore = -1;

  for (const g of (galleryData||[])){
    const slugN = normalize(g.slug||'');
    const altN  = normalize(g.alt||'');
    const file  = _0x8s9t(g.url||'');
    const fileN = normalize(file);
    const imgU  = g.url;

    for (const t of tokens){
      const tN = normalize(t);
      if (!tN) continue;

      let score = -1;
      if (imgU === tN) score = 300;
      if (slugN && slugN === tN) score = 300;
      else if (fileN && fileN.includes(tN)) score = 200;
      else if (altN && altN.includes(tN)) score = 120;

      if (score > bestScore){
        bestScore = score;
        best = g;
      }
      if (bestScore >= 300) break;
    }
    if (bestScore >= 300) break;
  }
  return bestScore >= 120 ? best : null;
}
function _0xey3z(variant){const a=_0xcw2x(variant);if(a&&a.url){_0xau1v(a.url,a.alt||'')}}

/* ====== Layout mobile ====== */
(function _0xg04h(){const a=document.querySelector('.pd-media-card'),b=document.getElementById('pd-price-block'),c=document.getElementById('pd-price-anchor'),d=document.querySelector('.pd-info-card'),e=document.getElementById('pd-title'),f=document.getElementById('pd-variant-combined'),g=document.getElementById('pd-variants'),h=document.getElementById('pd-variants-anchor'),i=document.getElementById('pd-variant-summary'),j=document.getElementById('pd-summary-anchor'),titleRow=document.querySelector('.pd-title-row'),promoBanner=document.querySelector('.promo-endyear-mini-banner'),compareSection=document.querySelector('.pd-compare-section');if(!a||!b||!c||!d||!e)return;

    let pricePlaced = false;
    let varPlaced = false;
    let titlePlaced = false;
    let promoPlaced = false;
    const mq = window.matchMedia('(max-width: 640px)');

    function apply(){
        if (mq.matches) {
            if (!titlePlaced && a && titleRow && d.contains(titleRow)) { try{a.appendChild(titleRow);titlePlaced=true;}catch(_){} }
            if (!pricePlaced && a && b) { try{a.appendChild(b);pricePlaced=true;}catch(_){} }
            if (!varPlaced && f && d && e) {
                try{
                    f.hidden = false;
                    if (i && !f.contains(i)) f.appendChild(i);
                    if (g && !f.contains(g)) f.appendChild(g);
                    if(d.contains(e))d.insertBefore(f, e);
                    varPlaced = true;
                }catch(_){}
            }
            if (!promoPlaced && promoBanner && compareSection && compareSection.parentNode) {
                try{
                    compareSection.parentNode.insertBefore(promoBanner, compareSection);
                    promoPlaced = true;
                }catch(_){}
            }
        } else {
            if (titlePlaced && titleRow && d) {
                try{
                    const vcombined = document.getElementById('pd-variant-combined');
                    if (vcombined && d.contains(vcombined)) {
                        d.insertBefore(titleRow, vcombined);
                    } else {
                        d.insertBefore(titleRow, d.firstChild);
                    }
                    titlePlaced=false;
                }catch(_){}
            }
            if (pricePlaced && b && c && c.parentNode) {
                try{c.parentNode.insertBefore(b, c.nextSibling);pricePlaced=false;}catch(_){}
            }
            if (varPlaced) {
                try{
                    if (i && j && j.parentNode && !j.parentNode.contains(i)) j.parentNode.insertBefore(i, j.nextSibling);
                    if (g && h && h.parentNode && !h.parentNode.contains(g)) h.parentNode.insertBefore(g, h.nextSibling);
                    if (f) { f.hidden = true; f.innerHTML = ''; }
                    varPlaced = false;
                }catch(_){}
            }
            if (promoPlaced && promoBanner && b) {
                try{
                    b.appendChild(promoBanner);
                    promoPlaced = false;
                }catch(_){}
            }
        }
    }

    mq.addEventListener ? mq.addEventListener('change', apply) : mq.addListener(apply);
    apply();

    const fab = document.getElementById('pd-mobile-fab');
    if (fab) {
        const pid = parseInt(fab.getAttribute('data-pid')||'0',10) || 325;
        const btnCart = document.getElementById('mf-cart');

        if (btnCart) btnCart.addEventListener('click', function(){
            const varInput = document.getElementById('pd-variant-id');
            const variantId = varInput ? parseInt(varInput.value||'0',10) : 0;
            try { if (window.addToCart) addToCart(pid, 1, variantId); } catch(_){}
            const variantText = getVariantSummaryText();
            const skuText = (function(){ const el=document.getElementById('pd-sku'); return el?el.textContent.trim():PD_SKU; })();
            showToast({
                type: 'success',
                title: 'Đã thêm vào giỏ hàng',
                message: `<strong>${escapeHtml(PD_NAME)}</strong>${variantText?` · <em>${escapeHtml(variantText)}</em>`:''}<br>SKU: ${escapeHtml(skuText)} · Qty: 1`,
                duration: 5000,
                actions: [
                    { label: 'Xem giỏ hàng', onClick: ()=> { window.location.href = '/keranjang'; } },
                    { label: 'Tiếp tục mua sắm', variant: 'alt', onClick: ()=>{} }
                ]
            });
        });
    }
})();

/* ====== Auto-hide installment banner if sidebar too tall (Desktop only) ====== */
(function(){
    function adjustSidebarBanner() {
        if (window.innerWidth <= 768) return;
        const sidebar = document.querySelector('.pd-sidebar');
        const infoCard = document.querySelector('.pd-info-card');
        const banner = document.querySelector('.pd-side-card.inst-alt-banner.inst-alt-banner--compact');
        if (!sidebar || !infoCard || !banner) return;
        const sidebarHeight = sidebar.offsetHeight;
        const infoCardHeight = infoCard.offsetHeight;
        if (sidebarHeight > infoCardHeight) {
            banner.style.display = 'none';
        } else {
            banner.style.display = '';
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', adjustSidebarBanner);
    } else {
        adjustSidebarBanner();
    }
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(adjustSidebarBanner, 250);
    });
    const pwpSection = document.getElementById('pwp-deals-section');
    if (pwpSection) {
        const observer = new MutationObserver(function(mutations) {
            let shouldRecheck = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'hidden') {
                    shouldRecheck = true;
                } else if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    shouldRecheck = true;
                }
            });
            if (shouldRecheck) {
                setTimeout(adjustSidebarBanner, 100);
            }
        });
        observer.observe(pwpSection, {
            attributes: true,
            childList: true,
            subtree: true
        });
    }
    window.adjustSidebarBanner = adjustSidebarBanner;
})();

/* ====== Global constants ====== */
const IS_DIGITAL=false;
const IS_PREORDER=false;

/* ====== Variants state (REVISED) ====== */
(function initVariants(){
const _0x7g8h=[];window.variants=_0x7g8h;
window.isPreorderProduct=false;
    const attrOrder = [];

    if (!variants.length || !attrOrder.length) return;

    function comboKey(sel){
        return attrOrder.map(a => a + '=' + (sel[a] ?? '')).join('|');
    }

    const variantMap = {};
    variants.forEach(v=>{
        const key = comboKey(v.attrs);
        variantMap[key] = v;
    });

    function readSelection(){
        const sel = {};
        attrOrder.forEach(a=>{
            const r = document.querySelector(`.v-group[data-attr="${CSS.escape(a)}"] input[type=radio]:checked`);
            sel[a] = r ? r.value : '';
        });
        return sel;
    }

    function findVariant(sel){
        return variantMap[comboKey(sel)] || null;
    }

    function autoFillMissing(sel){
        let changed = false;
        for (const a of attrOrder) {
            if (sel[a]) continue;
            const possible = [];
            const seen = new Set();
            variants.forEach(v=>{
                let ok = true;
                for (const a2 of attrOrder) {
                    if (a2 === a) continue;
                    if (sel[a2] && v.attrs[a2] !== sel[a2]) { ok = false; break; }
                }
                if (ok) {
                    const val = v.attrs[a];
                    if (!seen.has(val)) { seen.add(val); possible.push(val); }
                }
            });
            if (possible.length === 1) {
                const val = possible[0];
                const input = document.querySelector(`.v-group[data-attr="${CSS.escape(a)}"] input[type=radio][value="${CSS.escape(val)}"]`);
                if (input && !input.checked) { input.checked = true; sel[a] = val; changed = true; }
            }
        }
        return changed;
    }

    function rupiah(n){
        n = parseInt(n||0,10)||0;
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.') + ' VNĐ';
    }
    
    function focusGalleryByUrl(imageUrl) {
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 640px)').matches;
          if (isMobile) {
            _0xau1v(imageUrl);
            return;
          }
        
          const gallery = document.getElementById('pd-thumb-gallery');
          const targetName = _0x8s9t(imageUrl);
        
          if (gallery) {
            const btns = Array.from(gallery.querySelectorAll('.pd-thumb-btn'));
            const btn = btns.find(b => _0x8s9t(b.getAttribute('data-full') || '') === targetName);
            if (btn) { btn.click(); return; }
          }
          
          _0xau1v(imageUrl);
    }
    

function formatStockLabel(n){n=parseInt(n||0,10)||0;return(n>10)?'10+':String(n)}
function updateBranchPopover(v){try{if(!v)return;const a=document.getElementById('sp-qty-meg'),b=document.getElementById('sp-qty-mdf'),c=document.getElementById('sp-qty-bda');if(a)a.textContent=formatStockLabel(Math.max(0,v.smm|0));if(b)b.textContent=formatStockLabel(Math.max(0,v.smf|0));if(c)c.textContent=formatStockLabel(Math.max(0,v.sba|0))}catch(_){}}

    function calcVoucherDisc(type,value,price,maxDisc){if(type==='PERCENTAGE'){let d=(price*value)/100;if(maxDisc>0&&d>maxDisc)d=maxDisc;return Math.round(d)}return Math.round(value)}

    function applyVariant(v, focusImage = true){
        const elCur  = document.getElementById('pd-price-current');
        const elOri  = document.getElementById('pd-price-original');
        const elDisc = document.getElementById('pd-discount-badge');
        const elSku  = document.getElementById('pd-sku');
        const elStock= document.getElementById('pd-stock-badge');
        const elQty  = document.getElementById('qty');
        const btnAdd = document.getElementById('btn-add-cart');
        const btnBuy = document.getElementById('btn-buy');
        const hidVar = document.getElementById('pd-variant-id');

        const mfCart= document.getElementById('mf-cart');
        const mfBuy = document.getElementById('mf-buy');
        const fab = document.getElementById('pd-mobile-fab');

        if (hidVar) hidVar.value = v ? v.id : 0;
        if (elCur) elCur.textContent = rupiah(v ? v.final : 0);
        if (elSku && v && v.sku) elSku.textContent = v.sku;

        if (v && v.ori > v.final) {
            if (elOri) { elOri.style.display='inline'; elOri.textContent = rupiah(v.ori); }
            if (elDisc) { elDisc.style.display='inline'; elDisc.textContent = '-' + (v.disc||0) + '%'; }
        } else {
            if (elOri) elOri.style.display='none';
            if (elDisc) elDisc.style.display='none';
        }

        const stock = v ? (parseInt(v.stock||0,10)||0) : 0;
        let effectiveStock = stock;
        let isActivePreorder = false;
        if (window.isPreorderProduct && v && 'preorder_remaining' in v && v.preorder_remaining !== null) {
            effectiveStock = parseInt(v.preorder_remaining, 10);
            if (isNaN(effectiveStock)) effectiveStock = 0;
            isActivePreorder = true;
        }
        if (elStock){
            elStock.classList.remove('in','out');
            if (effectiveStock>0){ 
                elStock.classList.add('in'); 
                elStock.textContent = IS_DIGITAL ? 'Tersedia' : (isActivePreorder ? 'Sisa Slot: ' + effectiveStock : 'Kho: ' + formatStockLabel(effectiveStock)); 
            } else { 
                elStock.classList.add('out'); 
                elStock.textContent = 'Hết hàng'; 
            }
        }
        if (elQty){
            elQty.max = String(effectiveStock>0?effectiveStock:0);
            if (effectiveStock<=0) { elQty.value = '0'; elQty.disabled = true; }
            else { if (parseInt(elQty.value||'1',10) < 1) elQty.value='1'; elQty.disabled=false; }
        }
        
        const shouldDisable = !(v && effectiveStock>0);
        const updateButtons = () => {
            const btnAddCart = document.getElementById('btn-add-cart');
            const btnBuyNow = document.getElementById('btn-buy');
            const mobileCart = document.getElementById('mf-cart');
            const mobileBuy = document.getElementById('mf-buy');
            [btnAddCart, btnBuyNow, mobileCart, mobileBuy].forEach(btn => {
                if (!btn) return;
                if (shouldDisable) {
                    btn.disabled = true;
                    btn.setAttribute('disabled', 'disabled');
                    btn.classList.add('stock-disabled');
                    btn.setAttribute('data-stock-blocked', 'true');
                    btn.setAttribute('data-stock-reason', window.isPreorderProduct ? 'Hết suất đặt trước' : 'Hết hàng');
                } else {
                    btn.disabled = false;
                    btn.removeAttribute('disabled');
                    btn.classList.remove('stock-disabled');
                    btn.removeAttribute('data-stock-blocked');
                    btn.removeAttribute('data-stock-reason');
                }
            });
        };
        updateButtons();
        setTimeout(updateButtons, 10);
        setTimeout(updateButtons, 50);
        setTimeout(updateButtons, 100);
        if (mfBuy && v) {
            const voucherBtn = document.getElementById('btn-buy-voucher');
            const voucherCode = fab ? fab.dataset.voucherCode : '';
            let voucherDiscount = 0;
            
            if (voucherBtn && voucherCode) {
                const vType = voucherBtn.dataset.voucherType;
                const vValue = parseFloat(voucherBtn.dataset.voucherValue) || 0;
                const vMax = parseFloat(voucherBtn.dataset.voucherMax) || 0;
                const finalPrice = v.final || 0;
                if (vType && vValue > 0) {
                    voucherDiscount = calcVoucherDisc(vType, vValue, finalPrice, vMax);
                    if (fab) fab.dataset.voucherValue = voucherDiscount.toString();
                }
            }
            
            const finalPrice = v.final || 0;
            const textEl = mfBuy.querySelector('.mf-buy-text');
            const priceEl = mfBuy.querySelector('.mf-buy-price');
            
            if (effectiveStock > 0) {
                    if (IS_PREORDER) {
                        if (textEl) textEl.textContent = 'Đặt trước ngay';
                        if (priceEl) priceEl.textContent = rupiah(finalPrice);
                    } else if (voucherCode && voucherDiscount > 0) {
                        if (textEl) textEl.textContent = 'Mua bằng voucher';
                        if (priceEl) priceEl.textContent = rupiah(Math.max(0, finalPrice - voucherDiscount));
                    } else {
                        if (textEl) textEl.textContent = 'Mua ngay';
                        if (priceEl) priceEl.textContent = rupiah(finalPrice);
                    }
                } else {
                    if (textEl) textEl.textContent = 'Hết hàng';
                    if (priceEl) priceEl.textContent = rupiah(finalPrice);
                }
            }
        
        const isMobile = window.initMobile ?? 0;
        if (window.instSetBasePrice) window.instSetBasePrice(v ? (isMobile == '1' ? v.ori : v.final) : 0);
        if (window.updateSoldBySku) window.updateSoldBySku(v.sku);
        updateBranchPopover(v);
        if (v && v.image_url && focusImage) focusGalleryByUrl(v.image_url);
        if (v) {
            window.dispatchEvent(new CustomEvent('pd-variant-changed', {
                detail: {
                    price: v.final,
                    originalPrice: v.ori,
                    discount: v.disc,
                    stock: effectiveStock,
                    sku: v.sku,
                    variant_id: v.id
                }
            }));
            
            const encodedHash = encodeVariantHash(v.id);
            if (encodedHash && window.history && window.history.replaceState) {
                const newHash = '#v=' + encodedHash;
                const newUrl = window.location.pathname + window.location.search + newHash;
                window.history.replaceState(null, '', newUrl);
            }
        }
    }

    let sel = readSelection();
    if (autoFillMissing(sel)) sel = readSelection();

    let v = findVariant(sel);
    if (!v) {
        const candidates = variants.filter(variant=>{
            return attrOrder.every(a=>{
                if (!sel[a]) return true;
                return variant.attrs[a] === sel[a];
            });
        });
        if (candidates.length >= 1) {
            v = candidates[0];
            attrOrder.forEach(a=> sel[a] = v.attrs[a]);
            attrOrder.forEach(a=>{
                const val = sel[a];
                const input = document.querySelector(`.v-group[data-attr="${CSS.escape(a)}"] input[type=radio][value="${CSS.escape(val)}"]`);
                if (input) input.checked = true;
            });
        }
    }

    const initSel = readSelection();
    const initVar = findVariant(initSel) || variants[0] || null;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => applyVariant(initVar, false));
    } else {
        applyVariant(initVar, false);
    }
    
function updateSummary(sel){Object.entries(sel).forEach(([a,val])=>{const el=document.getElementById('vs-'+a);if(el)el.textContent=val||'-'})}
    function refreshOptions(sel){
        attrOrder.forEach(a=>{
            const group = document.querySelector(`.v-group[data-attr="${CSS.escape(a)}"]`);
            if (!group) return;
            const chips = group.querySelectorAll('.v-chip');
            chips.forEach(ch=>{
                const val = ch.getAttribute('data-value');
                const possible = variants.some(v=>{
                    if (v.attrs[a] !== val) return false;
                    for (const a2 of attrOrder) {
                        if (a2 === a) continue;
                        if (sel[a2] && v.attrs[a2] !== sel[a2]) return false;
                    }
                    return true;
                });
                const input = ch.querySelector('input[type=radio]');
                const disabled = !possible;
                if (input) input.disabled = disabled;
                ch.classList.toggle('disabled', disabled);

                let candidateVariant = null;
                if (possible) {
                    candidateVariant = variants.find(v=>{
                        if (v.attrs[a] !== val) return false;
                        for (const a2 of attrOrder) {
                            if (a2 === a) continue;
                            if (sel[a2] && v.attrs[a2] !== sel[a2]) return false;
                        }
                        return true;
                    });
                }
                const outOfStock = candidateVariant ? (window.isPreorderProduct && candidateVariant.preorder_remaining !== null ? candidateVariant.preorder_remaining <= 0 : candidateVariant.stock <= 0) : false;
                ch.classList.toggle('out', !disabled && outOfStock);
                const badge = ch.querySelector('.chip-badge');
                if (badge) badge.hidden = !(candidateVariant && outOfStock);
            });
            group.querySelectorAll('.v-chip').forEach(ch=>{
                const input = ch.querySelector('input[type=radio]');
                ch.classList.toggle('selected', input && input.checked);
            });
        });
    }
    updateSummary(sel);
    refreshOptions(sel);
    document.querySelectorAll('.v-chip').forEach(chip=>{const input=chip.querySelector('input[type=radio]');if(input&&input.checked){chip.classList.add('selected');}});

    const container = document.getElementById('pd-variants');
    container && container.addEventListener('change', function(ev){
        const radio = ev.target.closest('input[type=radio]');
        if (!radio) return;
        sel = readSelection();
        if (autoFillMissing(sel)) sel = readSelection();
        const chosen = findVariant(sel);
        applyVariant(chosen || null, true);
        
        updateSummary(sel);
        refreshOptions(sel);
    });
    
window.applyVariant=applyVariant;
})();

(function cleanBrokenDescImages(){
    const containers = [
        document.querySelector('.pd-full-desc-body'),
        document.querySelector('.pd-short-desc')
    ].filter(Boolean);

    containers.forEach(root => {
        const imgs = root.querySelectorAll('img');
        imgs.forEach(img => {
            if (img.complete && img.naturalWidth === 0) {
                img.remove();
                return;
            }
            img.addEventListener('error', () => {
                img.remove();
            }, { once:true });
        });
    });
})();

/* ====== Scroll To Top ====== */
(function _0xi25j(){const a=document.getElementById('pd-scroll-top');if(!a)return;function b(){if(window.matchMedia('(max-width:640px)').matches){const d=document.querySelector('.bottom-nav'),e=document.querySelector('.pd-mobile-fab');if(!d&&!e){setTimeout(b,100);return}const f=d&&window.getComputedStyle(d).display!=='none'&&window.getComputedStyle(d).visibility!=='hidden',g=e&&window.getComputedStyle(e).display!=='none'&&window.getComputedStyle(e).visibility!=='hidden';let h;if(f&&g){h='calc(68px + 54px + env(safe-area-inset-bottom))'}else if(g){h='calc(68px + 8px + env(safe-area-inset-bottom))'}else if(f){h='calc(60px + env(safe-area-inset-bottom))'}else{h='calc(20px + env(safe-area-inset-bottom))'}a.style.setProperty('--scroll-top-bottom',h)}else{a.style.removeProperty('--scroll-top-bottom')}}function c(){const d=window.pageYOffset||document.documentElement.scrollTop||0,e=d>300;a.classList.toggle('visible',e);a.setAttribute('aria-hidden',e?'false':'true');b()}window.addEventListener('scroll',c,{passive:!0});window.addEventListener('resize',b,{passive:!0});setTimeout(b,100);c();a.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'})})})();

/* Format stok 10+ */
function _0xk67l(n){n=parseInt(n||0,10)||0;return n>10?'10+':String(n)}

/* Toggle popover stok per cabang */
(function _0xm89n(){const a=document.getElementById('pd-stock-info-btn'),b=document.getElementById('pd-stock-popover'),c=document.getElementById('pd-stock-popover-close');if(!a||!b)return;function d(){if(window.matchMedia('(min-width: 641px)').matches){const e=document.getElementById('pd-stock-badge');if(!e)return;const f=e.getBoundingClientRect(),g=window.pageXOffset||document.documentElement.scrollLeft||0,h=window.pageYOffset||document.documentElement.scrollTop||0;b.style.left=(f.left+g-10)+'px';b.style.top=(f.bottom+h+10)+'px'}else{b.style.left='';b.style.top=''}}
function e(){d();b.classList.add('show');a.setAttribute('aria-expanded','true');b.setAttribute('aria-hidden','false');document.addEventListener('click',g,{capture:true});window.addEventListener('resize',d,{passive:true});window.addEventListener('scroll',d,{passive:true})}function f(){b.classList.remove('show');a.setAttribute('aria-expanded','false');b.setAttribute('aria-hidden','true');document.removeEventListener('click',g,{capture:true});window.removeEventListener('resize',d);window.removeEventListener('scroll',d)}function g(h){if(b.contains(h.target)||a.contains(h.target))return;f()}
a.addEventListener('click',function(h){h.preventDefault();b.classList.contains('show')?f():e()});if(c)c.addEventListener('click',f)})();

/* ===== Share Product Modal ===== */
(function(){const a=document.getElementById('pd-share-btn'),z=document.getElementById('mf-share'),b=document.getElementById('pd-share-modal-overlay'),c=document.getElementById('pd-share-close'),d=document.getElementById('pd-share-copy-url-btn'),e=document.getElementById('pd-share-url-input');if((!a&&!z)||!b)return;const f=<?= j($page['canonical_url'] ?? '') ?>,g=<?= j($page['title'] ?? '') ?>,h="20.789.000 VNĐ";const discountPercent=1;let i='';if(discountPercent>0){i=` Đang khuyến mãi! Giảm ${discountPercent}%\n\n✨ ${g}\n💰 Chỉ ${h}!\n\n🛒 Xem ngay kẻo hết!\n👉 `}else{i=`✨ Có món hay nè!\n\n📦 ${g}\n💸 Giá: ${h}\n\n🛍️ Xem ngay nhé!\n👉 `}function j(){b.classList.add('show');document.body.style.overflow='hidden'}function k(){b.classList.remove('show');document.body.style.overflow=''}if(a)a.addEventListener('click',j);if(z)z.addEventListener('click',j);if(c)c.addEventListener('click',k);b.addEventListener('click',function(l){if(l.target===b)k()});document.addEventListener('keydown',function(l){if(l.key==='Escape'&&b.classList.contains('show'))k()});b.addEventListener('click',function(l){const m=l.target.closest('[data-share]');if(!m)return;const n=m.getAttribute('data-share'),o=encodeURIComponent(f),p=encodeURIComponent(i),q=encodeURIComponent(g);let r='';switch(n){case 'whatsapp':r=`https://wa.me/?text=${p}${o}`;break;case 'facebook':r=`https://www.facebook.com/sharer/sharer.php?u=${o}`;break;case 'twitter':r=`https://twitter.com/intent/tweet?text=${p}${o}`;break;case 'telegram':r=`https://t.me/share/url?url=${o}&text=${p}`;break;case 'email':r=`mailto:?subject=${encodeURIComponent(' Cek Produk Keren Ini!')}&body=${p}${o}`;window.location.href=r;k();return;case 'copy':s();return;case 'pdf':const ps=document.querySelector('.product-single');if(ps){ps.setAttribute('data-print-date',new Date().toLocaleDateString('vi-VN',{year:'numeric',month:'long',day:'numeric'}))}k();setTimeout(function(){window.print();try{if(window.ga4Ecomm&&window.ga4Ecomm.trackEvent)window.ga4Ecomm.trackEvent('share',{method:'pdf_export',content_type:'product',item_id:"SB0B3T"})}catch(_){}},300);return}if(r){window.open(r,'_blank','noopener,noreferrer');try{if(window.ga4Ecomm&&window.ga4Ecomm.trackEvent)window.ga4Ecomm.trackEvent('share',{method:n,content_type:'product',item_id:"SB0B3T"})}catch(_){}}});function s(){if(!e)return;e.select();e.setSelectionRange(0,99999);try{if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(f).then(function(){t()}).catch(function(){document.execCommand('copy');t()})}else{document.execCommand('copy');t()}}catch(u){console.error('Failed to copy:',u)}}function t(){if(!d)return;const v=d.innerHTML;d.classList.add('copied');d.innerHTML='<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg><span class="pd-share-copy-text">Đã sao chép!</span>';if(window.showToast)showToast({type:'success',title:'✨ Đã sao chép link!',message:'Đã sao chép link sản phẩm. Hãy chia sẻ cho bạn bè nhé!',duration:3000});setTimeout(function(){d.classList.remove('copied');d.innerHTML=v},2000)}if(d)d.addEventListener('click',s);if(navigator.share){const shareHandler=function(w){w.preventDefault();navigator.share({title:g,text:i+f,url:f}).catch(function(x){console.log('Native share cancelled or failed:',x)})};if(a)a.addEventListener('dblclick',shareHandler);if(z)z.addEventListener('dblclick',shareHandler)}})();

/* ===== Embedding variants (server → client) ===== */
(function(){
  window.__PD_VARIANTS_WITH_BRANCH__ = [];
})();

/* ===== Helper: update angka popover ===== */
function updateStockPopoverForVariant(v){
  try{
    const elMeg = document.getElementById('sp-qty-meg');
    const elMdf = document.getElementById('sp-qty-mdf');
    const elBda = document.getElementById('sp-qty-bda');

    if (typeof v?.smm === 'number' && elMeg) elMeg.textContent = formatStockLabel(Math.max(0, v.smm|0));
    if (typeof v?.smf === 'number' && elMdf) elMdf.textContent = formatStockLabel(Math.max(0, v.smf|0));
    if (typeof v?.sba === 'number' && elBda) elBda.textContent = formatStockLabel(Math.max(0, v.sba|0));
  }catch(_){}
}

/* ===== applyVariant: 10+ dan update popover ===== */
(function patchApplyVariant(){
  if (typeof applyVariant !== 'function') return;
  const original = applyVariant;
  window.applyVariant = function(v){
    original(v);
    
    try {
      const elStock = document.getElementById('pd-stock-badge');
      if (elStock){
        let txt = elStock.textContent || '';
        let m = txt.match(/\d+/);
        if (m) {
          const n = parseInt(m[0]||'0',10)||0;
          elStock.textContent = (n>0) ? ('Kho: ' + formatStockLabel(n)) : 'Hết hàng';
        }
      }
    } catch(_){}
if(window.__PD_VARIANTS_WITH_BRANCH__&&v){const vv=window.__PD_VARIANTS_WITH_BRANCH__.find(x=>x.id===v.id)||v;updateStockPopoverForVariant(vv)}
  };
})();

(function initCurrentVariantBranch(){
  const list = window.__PD_VARIANTS_WITH_BRANCH__ || [];
  if (!list.length) return;
  const hid = document.getElementById('pd-variant-id');
  const vid = hid ? parseInt(hid.value||'0',10) : 0;
  const current = list.find(x => x.id === vid) || list[0] || null;
  if (current) updateStockPopoverForVariant(current);
})();

(function(){
    if (!window.ga4Ecomm) return;
    const catSegs = ["Komputer & Laptop","Laptop","Gaming"];
    window.ga4Ecomm.viewItem({
      currency: 'IDR',
      value: 20789000,
      item_id: "SB0B3T",
      item_name: "Lenovo Legion Go 8ASP2 - 83N0000RID /AMD Ryzen Z2 Extreme/32GB/1TB SSD/AMD Radeon Graphics/8.8\" WUXGA OLED Touch/Win 11 Home/Eclipse Black/3Y",
      item_brand: "Lenovo ",
      item_category: catSegs && catSegs.length ? catSegs.join(' / ') : ''
    });
  })();
  
(function patchApplyVariantFocus(){
  if (typeof applyVariant !== 'function') return;
  const original = applyVariant;
  window.applyVariant = function(v, focusImage = true){
    
    original(v, focusImage);
    
    if (focusImage && v && v.image_url) {
      _0xau1v(v.image_url);
    }
  };
})();

(function() {
  var soldMap = [];
  var soldTotalLabel = "Đã bán 2";

  function formatSoldLabel(qty) {
    if (qty >= 1000000) {
      var v = Math.floor((qty / 100000) ) / 10;
      return 'Đã bán ' + (String(v).replace('.', ',')) + ' triệu+';
    }
    if (qty >= 1000) {
      var v2 = Math.floor((qty / 100) ) / 10;
      return 'Đã bán ' + (String(v2).replace('.', ',')) + 'k+';
    }
    if (qty >= 100) {
      var v2 = Math.floor((qty / 100) ) * 100;
      return 'Đã bán ' + (String(v2).replace('.', ',')) + '+';
    }
    if (qty >= 10) {
      var v2 = Math.floor((qty / 10) ) * 10;
      return 'Đã bán ' + (String(v2).replace('.', ',')) + '+';
    }
    return 'Đã bán ' + (qty.toLocaleString('vi-VN'));
  }

  function updateSoldLabel(labelText) {
    var el = document.querySelector('#pd-sold-inline, [data-role="sold-chip"]');
    if (el) el.textContent = labelText;
  }

  window.updateSoldBySku = function(sku) {
    if (!sku || typeof sku !== 'string') {
      updateSoldLabel(soldTotalLabel);
      return;
    }
    var qty = soldMap[sku] || 0;
    updateSoldLabel(formatSoldLabel(qty));
  };

    updateSoldLabel(soldTotalLabel);
  })();

(function(){const h=document.getElementById('pd-sticky-header');if(!h)return;const p=document.getElementById('pd-price-anchor');const ac=document.getElementById('pd-sticky-add-cart');const by=document.getElementById('pd-sticky-buy');let lt=window.scrollY;let tk=false;function u(){if(!p)return;const r=p.getBoundingClientRect();const s=window.scrollY;if(r.top<0){h.classList.add('visible')}else{h.classList.remove('visible')}lt=s;tk=false}function rt(){if(!tk){window.requestAnimationFrame(u);tk=true}}window.addEventListener('scroll',rt,{passive:true});u();function sy(){const ma=document.getElementById('btn-add-cart');const mb=document.getElementById('btn-buy');if(ac&&ma){ac.disabled=ma.disabled}if(by&&mb){by.disabled=mb.disabled}}sy();const ob=new MutationObserver(sy);const ma=document.getElementById('btn-add-cart');const mb=document.getElementById('btn-buy');if(ma)ob.observe(ma,{attributes:true,attributeFilter:['disabled']});if(mb)ob.observe(mb,{attributes:true,attributeFilter:['disabled']});const ss=document.getElementById('pd-sticky-share-btn');const ms=document.getElementById('pd-share-btn');if(ss&&ms){ss.addEventListener('click',function(e){e.preventDefault();ms.click()})}if(ac){ac.addEventListener('click',function(e){e.preventDefault();if(ac.disabled)return;const m=document.getElementById('btn-add-cart');if(m&&!m.disabled){m.click()}})}if(by){by.addEventListener('click',function(e){e.preventDefault();if(by.disabled)return;const m=document.getElementById('btn-buy');if(m&&!m.disabled&&window.buyNow){window.buyNow(e)}})}
function getChatProductData(){const varInput=document.getElementById('pd-variant-id');const variantId=varInput?parseInt(varInput.value||'0',10):0;const variantSummary=getVariantSummaryText();const priceEl=document.getElementById('pd-price-current');const currentPrice=priceEl?priceEl.textContent.trim():'20.789.000 VNĐ';const pd={id:325,name:"Lenovo Legion Go 8ASP2 - 83N0000RID \/AMD Ryzen Z2 Extreme\/32GB\/1TB SSD\/AMD Radeon Graphics\/8.8\" WUXGA OLED Touch\/Win 11 Home\/Eclipse Black\/3Y",price:currentPrice,image:"\/uploads\/products\/lenovo-legion-go-8asp2-83n0000rid.webp",url:window.location.href};if(variantId>0){pd.variant_id=variantId}if(variantSummary){pd.variant_summary=variantSummary}return pd}
const ch=document.getElementById('pd-sticky-chat');if(ch){ch.addEventListener('click',function(e){e.preventDefault();if(!window.plazaChat){console.warn('Chat widget not loaded');return}window.plazaChat.attachProduct(getChatProductData());window.plazaChat.openWidget()})}const dch=document.getElementById('btn-chat-icon');if(dch){dch.addEventListener('click',function(e){e.preventDefault();if(!window.plazaChat){console.warn('Chat widget not loaded');return}window.plazaChat.attachProduct(getChatProductData());window.plazaChat.openWidget()})}const mch=document.getElementById('mf-chat');if(mch){mch.addEventListener('click',function(e){e.preventDefault();if(!window.plazaChat){console.warn('Chat widget not loaded');return}window.plazaChat.attachProduct(getChatProductData());window.plazaChat.openWidget()})}window.addEventListener('pd-variant-changed',function(e){const d=e.detail||{};if(d.price!==undefined){const sp=document.getElementById('pd-sticky-price');if(sp){sp.textContent=formatRupiah(d.price)}}if(d.originalPrice!==undefined&&d.originalPrice>d.price){const so=document.getElementById('pd-sticky-price-original');if(so){so.textContent=formatRupiah(d.originalPrice);so.style.display=''}}else{const so=document.getElementById('pd-sticky-price-original');if(so){so.style.display='none'}}if(d.discount!==undefined&&d.discount>0){const sd=document.getElementById('pd-sticky-discount');if(sd){sd.textContent='-'+d.discount+'%';sd.style.display=''}}else{const sd=document.getElementById('pd-sticky-discount');if(sd){sd.style.display='none'}}const hs=d.stock>0;if(ac){ac.disabled=!hs}if(by){by.disabled=!hs}});function formatRupiah(n){return Math.floor(n).toLocaleString('vi-VN')+' VNĐ'}})();

(function(){const t=document.getElementById('pdv-toggle-btn');const v=document.getElementById('pd-voucher-section');if(t&&v){const h=v.querySelectorAll('.pdv-min-hidden');const n=h.length;t.addEventListener('click',function(){const e=t.classList.toggle('expanded');h.forEach(function(i){if(e){i.classList.remove('pdv-min-hidden')}else{i.classList.add('pdv-min-hidden')}});t.textContent=e?'Tutup':'+'+n})}const m=document.getElementById('voucher-detail-modal');const mc=document.getElementById('voucher-modal-close');const ma=document.getElementById('voucher-modal-cancel');const mcp=document.getElementById('voucher-modal-copy');if(m){const bb=document.querySelectorAll('.pdv-badge-btn');bb.forEach(function(btn){btn.addEventListener('click',function(){if(btn.disabled)return;const c=btn.getAttribute('data-voucher-code');const nm=btn.getAttribute('data-voucher-name');const dl=btn.getAttribute('data-discount-label');const bl=btn.getAttribute('data-badge-label');const mp=parseInt(btn.getAttribute('data-min-purchase'));const md=parseInt(btn.getAttribute('data-max-discount'));const vu=btn.getAttribute('data-valid-until');document.getElementById('voucher-modal-badge').innerHTML='<span class="vmb-value">'+dl+'</span><span class="vmb-label">'+bl+'</span>';document.getElementById('voucher-modal-code').textContent=c;document.getElementById('voucher-modal-name').textContent=nm;const me=document.getElementById('voucher-modal-min');if(mp>0){me.style.display='';document.getElementById('voucher-modal-min-val').textContent=fr(mp)}else{me.style.display='none'}const xe=document.getElementById('voucher-modal-max');if(md>0){xe.style.display='';document.getElementById('voucher-modal-max-val').textContent=fr(md)}else{xe.style.display='none'}if(vu){const d=new Date(vu);const ft=d.toLocaleDateString('vi-VN',{day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});document.getElementById('voucher-modal-valid-val').textContent=ft}mcp.setAttribute('data-code',c);m.classList.add('show');document.body.style.overflow='hidden'})});function cm(){m.classList.remove('show');document.body.style.overflow=''}if(mc)mc.addEventListener('click',cm);if(ma)ma.addEventListener('click',cm);m.addEventListener('click',function(e){if(e.target===m)cm()});if(mcp){mcp.addEventListener('click',function(){const c=mcp.getAttribute('data-code');if(!c)return;if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(c).then(function(){smc()}).catch(function(err){fc(c)})}else{fc(c)}})}}const b=document.querySelectorAll('.pdv-min-copy');b.forEach(function(btn){btn.addEventListener('click',function(){if(btn.disabled)return;const code=btn.getAttribute('data-code');if(!code)return;if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(code).then(function(){sc(btn)}).catch(function(err){fc(code,btn)})}else{fc(code,btn)}})});function fc(text,btn){const tx=document.createElement('textarea');tx.value=text;tx.style.position='fixed';tx.style.opacity='0';document.body.appendChild(tx);tx.select();try{document.execCommand('copy');if(btn)sc(btn);else smc()}catch(err){}document.body.removeChild(tx)}function sc(btn){const o=btn.textContent;btn.classList.add('copied');btn.textContent='✓ Đã sao chép';setTimeout(function(){btn.classList.remove('copied');btn.textContent=o},2000)}function smc(){const o=mcp.textContent;mcp.textContent='✓ Đã sao chép mã';mcp.style.background='#22c55e';setTimeout(function(){mcp.textContent=o;mcp.style.background=''},2000)}function fr(a){return Math.floor(a).toLocaleString('vi-VN')+' VNĐ'}})();
</script>
<script nonce="se3iyzEHMc9FQEB4wSyd0w==">
window.PD_ID=325;window.PD_NAME=_0x3c4d;window.PD_SKU=_0x5e6f;window.PD_CATEGORY_PATH="Komputer & Laptop/Laptop/Gaming";window.PD_BEST_VOUCHER=null;
window.IS_DIGITAL=IS_DIGITAL;
window.isPreorderProduct=false;
window.PD_PREORDER_DATA=null;
</script>

<script nonce="se3iyzEHMc9FQEB4wSyd0w==">document.addEventListener('DOMContentLoaded',function(){if(typeof ProductReviews!=='undefined'){ProductReviews.init(325);}const r=document.getElementById('pd-rating-inline');if(r){r.addEventListener('click',function(e){e.preventDefault();const s=document.getElementById('reviews');if(s){s.scrollIntoView({behavior:'smooth',block:'start'});s.classList.add('highlight-flash');setTimeout(()=>{s.classList.remove('highlight-flash')},1500)}});}});</script>

<script nonce="se3iyzEHMc9FQEB4wSyd0w==">
(function(){function _0x9i0j(el){if(typeof gtag==='function'){gtag('event','qoala_insurance_click',{event_category:'Insurance',event_label:'Qoala Banner Click - Product Detail',link_url:'/plazait-protect',page_location:window.location.href,product_id:parseInt(el.getAttribute('data-product-id'))||0,product_name:el.getAttribute('data-product-name')||'',category_path:el.getAttribute('data-category-path')||'',custom_map:{custom_parameter_1:'qoala_banner_product_detail'}})}}

function _0x1k2l(){var a=document.querySelector('.qoala-cta[data-ga-event="qoala_banner_click"]');if(a){a.addEventListener('click',function(e){_0x9i0j(this)})}}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',_0x1k2l)}else{_0x1k2l()}})();

(function(){const pwpSection=document.getElementById('pwp-deals-section');const pwpBadge=document.getElementById('pwp-trigger-badge');if(!pwpSection)return;const productId=parseInt(pwpSection.getAttribute('data-product-id'))||0;const variantId=parseInt(pwpSection.getAttribute('data-variant-id'))||0;const categoryPath=pwpSection.getAttribute('data-category-path')||'';if(!productId)return;function formatRupiah(n){return Math.floor(n).toLocaleString('vi-VN')+' VNĐ'}function loadPWPDeals(){const url=`/api/pwp/get_by_product.php?product_id=${productId}${variantId?`&variant_id=${variantId}`:''}${categoryPath?`&category_path=${encodeURIComponent(categoryPath)}`:''}`;fetch(url).then(r=>r.json()).then(data=>{if(data.success&&data.promotions&&data.promotions.length>0){renderPWPDeals(data.promotions);pwpSection.hidden=false;if(pwpBadge)pwpBadge.hidden=false;setTimeout(function(){if(typeof window.adjustSidebarBanner==='function'){window.adjustSidebarBanner()}},150)}else{pwpSection.hidden=true;if(pwpBadge)pwpBadge.hidden=true}}).catch(err=>{console.error('PWP load error:',err);pwpSection.hidden=true;if(pwpBadge)pwpBadge.hidden=true})}function renderPWPDeals(promotions){const loading=pwpSection.querySelector('.pwp-deals-loading');const grid=document.getElementById('pwp-deals-grid');if(loading)loading.hidden=true;if(!grid)return;grid.innerHTML='';promotions.forEach(promo=>{promo.bundled_products.forEach(product=>{const card=document.createElement('div');card.className='pwp-deal-card';const discount=product.discount>0?product.discount:0;const discountPercent=product.regular_price>0?Math.round((discount/product.regular_price)*100):0;const imgUrl=product.image_url?`/uploads/products/${product.image_url}`:'/assets/img/no-image.png';card.innerHTML=`<div class="pwp-deal-thumb"><img src="${imgUrl}" alt="${escapeHtml(product.name)}" loading="lazy"></div><div class="pwp-deal-info"><h4 class="pwp-deal-name">${escapeHtml(product.name)}</h4><div class="pwp-deal-prices"><span class="pwp-deal-price">${formatRupiah(product.pwp_price)}</span>${product.regular_price>product.pwp_price?`<span class="pwp-deal-original">${formatRupiah(product.regular_price)}</span>`:''}</div></div>${discountPercent>0?`<span class="pwp-deal-badge">-${discountPercent}%</span>`:''}`;grid.appendChild(card)})})}function escapeHtml(str){const div=document.createElement('div');div.textContent=str||'';return div.innerHTML}loadPWPDeals();window.addEventListener('pd-variant-changed',function(e){const detail=e.detail||{};const newVid=detail.variant_id||0;const currentVid=parseInt(pwpSection.getAttribute('data-variant-id'))||0;if(newVid!==currentVid){pwpSection.setAttribute('data-variant-id',newVid);loadPWPDeals()}})})();
</script><footer class="site-footer footer-compact variant-stripes" role="contentinfo">
  <div class="ft-top ft-collapsible" id="ftTop">
    <div class="container ft-grid">

      <div class="ft-col ft-help">
        <h4 class="ft-title">Cần hỗ trợ?</h4>
        <p class="ft-desc">Liên hệ ngay với chúng tôi qua WhatsApp chính thức.</p>
        <a class="ft-wa-btn" href="<?= h($ctaUrl) ?>" target="_blank" rel="nofollow noopener">
          <span class="ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="#25D366" d="M12.04 2c-5.52 0-10 4.43-10 9.9 0 1.75.47 3.45 1.38 4.94L2 22l5.3-1.38c1.43.78 3.05 1.19 4.7 1.19 5.52 0 10-4.43 10-9.9S17.56 2 12.04 2Zm5.86 13.9c-.25.7-1.23 1.28-1.7 1.33-.45.06-1.02.08-1.65-.1-.38-.12-.88-.29-1.52-.56-2.66-1.15-4.4-3.83-4.53-4.01-.13-.18-1.08-1.44-1.08-2.75 0-1.31.68-1.95.92-2.22.25-.27.54-.33.72-.33.18 0 .36.01.52.01.17 0 .39-.06.62.47.25.6.85 2.07.92 2.22.07.15.12.33.02.53-.1.2-.15.33-.3.5-.15.17-.32.39-.46.53-.15.15-.3.31-.13.6.18.3.8 1.31 1.73 2.12 1.19 1.03 2.17 1.36 2.48 1.5.31.14.49.12.67-.07.18-.18.77-.82.98-1.1.21-.27.42-.23.7-.14.27.09 1.72.81 2.02.95.3.14.5.22.58.35.08.13.08.72-.17 1.42Z"/></svg>
          </span>
          Gửi tin nhắn
        </a>

        <p class="ft-desc sm-gap">Theo dõi chúng tôi:</p>
        <div class="ft-social">
          <a aria-label="Facebook" href="https://www.facebook.com/<?= rawurlencode($namaSitus) ?>"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M13.5 22v-8.2h2.7l.4-3.2h-3.1V8c0-.9.3-1.5 1.6-1.5H17V3.7c-.3 0-1.2-.1-2.2-.1-2.2 0-3.8 1.3-3.8 3.9v2.2H8v3.2h3v8.1h2.5Z"/></svg></a>
          <a aria-label="Instagram" href="https://www.instagram.com/<?= rawurlencode($namaSitus) ?>"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M7.8 2h8.4A5.8 5.8 0 0 1 22 7.8v8.4A5.8 5.8 0 0 1 16.2 22H7.8A5.8 5.8 0 0 1 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2Zm0 2A3.8 3.8 0 0 0 4 7.8v8.4A3.8 3.8 0 0 0 7.8 20h8.4a3.8 3.8 0 0 0 3.8-3.8V7.8A3.8 3.8 0 0 0 16.2 4H7.8Zm9.9 1.7a1.1 1.1 0 1 1 0 2.3 1.1 1.1 0 0 1 0-2.3ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/></svg></a>
          <a aria-label="TikTok" href="https://www.tiktok.com/@<?= rawurlencode($namaSitus) ?>"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18.9 7.4a6.4 6.4 0 0 1-3.2-1.2v6.3a5.47 5.47 0 1 1-5.5-5.5c.2 0 .4 0 .6.02v2.37a2.92 2.92 0 0 0-.6-.07 2.8 2.8 0 1 0 2.8 2.8V2h2.3a4.6 4.6 0 0 0 4.5 4.1v2.3Z"/></svg></a>
        </div>
      </div>

            <!-- Collapsible Content Wrapper (Non-Homepage Only) -->
      <div class="ft-collapsible-content" id="ftCollapsible">
      
      <div class="ft-col">
        <h4 class="ft-title">Về <?= h($namaSitus) ?></h4>
        <ul class="ft-links">
          <li><a href="/">Về chúng tôi</a></li>
          <li><a href="/">Liên hệ</a></li>
          <li><a href="/">Địa điểm cửa hàng</a></li>
          <li><a href="/">FAQ</a></li>
        </ul>
        <div class="ft-address">
          <p class="company"><?= h($namaSitus) ?></p>
      </div>

      <div class="ft-col">
        <h4 class="ft-title">Thông tin</h4>
        <ul class="ft-links">
          <li><a href="/">Điều khoản &amp; Điều kiện</a></li>
          <li><a href="/">Chính sách bảo mật</a></li>
          <li><a href="/">Chính sách cookie</a></li>
          <li><a href="/">Giao hàng &amp; Đổi trả</a></li>
          <li><a href="/">Thanh toán &amp; Trả góp</a></li>
          <li><a href="/">Bảo hành &amp; Dịch vụ</a></li>
        </ul>

        <div class="ft-payments payments-small" aria-label="Phương thức thanh toán">
          <div class="pay-row">
            <img src="https://imgstore.io/images/2026/02/17/mandiri.webp" alt="Mandiri" loading="lazy">
            <img src="https://imgstore.io/images/2026/02/17/bni.webp" alt="BNI" loading="lazy">
            <img src="https://imgstore.io/images/2026/02/17/bri.webp" alt="BRI" loading="lazy">
            <img src="https://imgstore.io/images/2026/02/17/bsi.webp" alt="BSI" loading="lazy">
            <img src="https://imgstore.io/images/2026/05/06/bank-cimb.gif" alt="ShopeePay" loading="lazy">
          </div>
        </div>
      </div>

            </div>
      <!-- End Collapsible Content -->
      
      <!-- Expand Button (Mobile Only) -->
      <button class="ft-expand-btn" id="ftExpandBtn" aria-label="Xem tất cả thông tin chân trang">
        <span class="expand-text">Xem thêm</span>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
          <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
        </svg>
      </button>
      
    </div>
  </div>
  <div class="ft-bottom">
    <div class="container ft-bottom-inner">
      <div class="ft-copy">
        <span><?= h($namaSitus) ?> &copy; <span id="yearNow"><?= h($tahun) ?></span></span>
        <span><?= h($namaSitus) ?>. Bảo lưu mọi quyền.</span>
      </div>
    </div>
  </div>
  
          <style nonce="se3iyzEHMc9FQEB4wSyd0w==">
            /* Chat Widget Styles - Lightweight & Minimal */
            .chat-widget {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            /* Hide floating button on mobile - use bottom nav instead */
            @media (max-width: 640px) {
                .chat-toggle-btn {
                    display: none !important;
                }
                
                /* Ensure popup is still visible on mobile even when button is hidden */
                .chat-widget {
                    display: block !important;
                }
                
                .chat-popup {
                    display: none; /* Hidden by default, shown via JS */
                }
                
                .chat-popup[style*="display: block"],
                .chat-popup[style*="display:block"] {
                    display: block !important;
                }
            }
            
            .chat-toggle-btn {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #F28C1A 0%, #D97809 100%);
                border: none;
                color: white;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(242, 140, 26, 0.3);
                position: relative;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .chat-toggle-btn:hover {
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(242, 140, 26, 0.4);
            }
            
            #chat-toggle-btn .chat-icon {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 28px !important;
                height: 28px !important;
            }
            
            .chat-unread-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #ef4444;
                color: white;
                border-radius: 12px;
                padding: 2px 6px;
                font-size: 11px;
                font-weight: 600;
                min-width: 20px;
                text-align: center;
            }
            
            .chat-popup {
                position: absolute;
                bottom: 80px;
                right: 0;
                width: 380px;
                max-width: calc(100vw - 40px);
                height: 500px;
                max-height: calc(100vh - 120px);
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            
            .chat-popup-header {
                background: linear-gradient(135deg, #F28C1A 0%, #D97809 100%);
                color: white;
                padding: 16px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-shrink: 0;
            }
            
            .chat-header-title {
                display: flex;
                flex-direction: column;
                gap: 2px;
                min-height: 0;
            }
            
            .chat-popup-header h3 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                line-height: 1.2;
            }
            
            .chat-operating-hours {
                font-size: 11px;
                opacity: 0.9;
                font-weight: 400;
                display: flex;
                align-items: center;
                gap: 4px;
                line-height: 1.2;
            }
            
            .chat-operating-hours svg {
                width: 12px;
                height: 12px;
                opacity: 0.8;
                flex-shrink: 0;
            }
            
            .chat-header-actions {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            /* Notification Toggle Button */
            .btn-notif-toggle {
                background: none;
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
                padding: 4px 8px;
                transition: all 0.2s;
                opacity: 0.8;
            }
            
            .btn-notif-toggle:hover {
                opacity: 1;
                transform: scale(1.1);
            }
            
            /* Icon states */
            .btn-notif-toggle[data-status="not-subscribed"] svg {
                opacity: 0.7;
            }
            
            .btn-notif-toggle[data-status="not-subscribed"]:hover svg {
                opacity: 1;
            }
            
            .btn-notif-toggle[data-status="subscribed"] svg {
                opacity: 1;
                animation: bellRing 2s ease-in-out;
            }
            
            .btn-notif-toggle[data-status="blocked"] svg {
                color: #ff6b6b;
            }
            
            .btn-notif-toggle[data-status="not-supported"] {
                display: none !important;
            }
            
            @keyframes bellRing {
                0%, 100% { transform: rotate(0deg); }
                10%, 30% { transform: rotate(-10deg); }
                20%, 40% { transform: rotate(10deg); }
            }
            
            /* Notification Info Bar */
            .chat-notif-info-bar {
                position: absolute;
                top: 64px;
                left: 0;
                right: 0;
                background: linear-gradient(to right, #e3f2fd, #f5f5f5);
                border-bottom: 1px solid #bbdefb;
                padding: 8px 12px;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 12px;
                color: #616161;
                z-index: 10;
                animation: slideDown 0.3s ease;
            }
            
            .chat-notif-info-bar svg {
                flex-shrink: 0;
            }
            
            .chat-notif-info-bar .small {
                flex: 1;
                line-height: 1.4;
            }
            
            .chat-notif-info-bar .btn-dismiss {
                background: none;
                border: none;
                color: #9e9e9e;
                cursor: pointer;
                padding: 0 4px;
                font-size: 16px;
                transition: color 0.2s;
            }
            
            .chat-notif-info-bar .btn-dismiss:hover {
                color: #616161;
            }
            
            @keyframes slideDown {
                from {
                    max-height: 0;
                    opacity: 0;
                }
                to {
                    max-height: 50px;
                    opacity: 1;
                }
            }
            
            .chat-close-btn {
                background: none;
                border: none;
                color: white;
                font-size: 28px;
                line-height: 1;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
            }
            
            .chat-popup-body {
                flex: 1 1 auto;
                display: flex;
                flex-direction: column;
                min-height: 0;
                overflow: hidden;
                height: calc(100% - 64px);
                position: relative;
                transition: padding-top 0.3s ease;
            }
            
            /* Add padding when info bar is visible */
            .chat-popup-body.has-info-bar {
                padding-top: 40px;
            }
            
            .chat-view {
                flex: 1 1 auto;
                display: none;
                flex-direction: column;
                min-height: 0;
                overflow: hidden;
            }
            
            #chat-list-view {
                display: flex;
            }
            
            #chat-messages-view {
                display: none;
            }
            
            #chat-messages-view.active {
                display: flex;
                flex-direction: column;
            }
            
            #chat-new-view {
                display: none;
            }
            
            #chat-new-view.active {
                display: flex;
            }
            
            .chat-conversations-list {
                flex: 1;
                overflow-y: auto;
                padding: 0;
                background: white;
            }
            
            /* Custom Scrollbar for Conversations List */
            .chat-conversations-list::-webkit-scrollbar {
                width: 6px;
            }
            
            .chat-conversations-list::-webkit-scrollbar-track {
                background: #f1f1f1;
            }
            
            .chat-conversations-list::-webkit-scrollbar-thumb {
                background: #F28C1A;
                border-radius: 3px;
            }
            
            .chat-conversations-list::-webkit-scrollbar-thumb:hover {
                background: #D97809;
            }
            
            /* Firefox */
            .chat-conversations-list {
                scrollbar-width: thin;
                scrollbar-color: #F28C1A #f1f1f1;
            }
            
            .chat-conversation-item {
                padding: 12px 16px;
                cursor: pointer;
                display: flex;
                gap: 12px;
                align-items: flex-start;
                transition: background 0.15s ease;
                color: #33371f;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .chat-conversation-item:hover {
                background: #f5f5f5;
            }
            
            .chat-conversation-item.active {
                background: #FFF5EB;
            }
            
            /* WhatsApp-like Avatar */
            .conversation-avatar {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: linear-gradient(135deg, #F28C1A 0%, #D97809 100%);
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 18px;
            }
            
            .conversation-avatar svg {
                width: 24px;
                height: 24px;
            }
            
            /* WhatsApp-like Content */
            .conversation-content {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            
            .conversation-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
            }
            
            .conversation-name {
                font-weight: 600;
                font-size: 15px;
                color: #111;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .conversation-time {
                font-size: 12px;
                color: #7d8166;
                flex-shrink: 0;
            }
            
            .conversation-preview {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
            }
            
            .conversation-message {
                font-size: 14px;
                color: #667781;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin: 0;
                flex: 1;
            }
            
            .conversation-unread {
                background: #25D366;
                color: white;
                border-radius: 12px;
                padding: 2px 8px;
                font-size: 12px;
                font-weight: 600;
                min-width: 20px;
                text-align: center;
                flex-shrink: 0;
            }
            
            /* Active item styling */
            .chat-conversation-item.active .conversation-name {
                color: #F28C1A;
            }
            
            .chat-conversation-item.active .conversation-time {
                color: #F28C1A;
            }
            
            .chat-new-btn {
                margin: 12px;
                padding: 12px;
                background: linear-gradient(135deg, #F28C1A 0%, #D97809 100%);
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
                font-size: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: all 0.2s;
                flex-shrink: 0;
            }
            
            .chat-new-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(242, 140, 26, 0.3);
            }
            
            .chat-conversation-title {
                flex: 1;
                font-weight: 600;
                font-size: 16px;
                color: #1f2937;
            }
            
            .chat-messages-container {
                flex: 1;
                overflow-y: auto;
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 8px;
                min-height: 0;
                background: #E5DDD5;
            }
            
            /* Custom Scrollbar for Messages Container */
            .chat-messages-container::-webkit-scrollbar {
                width: 6px;
            }
            
            .chat-messages-container::-webkit-scrollbar-track {
                background: rgba(0, 0, 0, 0.05);
            }
            
            .chat-messages-container::-webkit-scrollbar-thumb {
                background: rgba(242, 140, 26, 0.6);
                border-radius: 3px;
            }
            
            .chat-messages-container::-webkit-scrollbar-thumb:hover {
                background: #F28C1A;
            }
            
            /* Firefox */
            .chat-messages-container {
                scrollbar-width: thin;
                scrollbar-color: rgba(242, 140, 26, 0.6) rgba(0, 0, 0, 0.05);
            }
            
            /* WhatsApp Group-like Messages */
            .chat-message {
                display: flex;
                margin-bottom: 4px;
                animation: fadeIn 0.2s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .chat-message.mine {
                justify-content: flex-end;
            }
            
            .chat-message-bubble {
                max-width: 80%;
                padding: 6px 8px 8px 10px;
                border-radius: 8px;
                position: relative;
                box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
            }
            
            /* Bubble from others (Admin/CS) */
            .chat-message:not(.mine) .chat-message-bubble {
                background: #FFFFFF;
                border-radius: 0 8px 8px 8px;
            }
            
            /* Bubble tail for others */
            .chat-message:not(.mine) .chat-message-bubble::before {
                content: '';
                position: absolute;
                top: 0;
                left: -8px;
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 0 8px 13px 0;
                border-color: transparent #FFFFFF transparent transparent;
            }
            
            /* Bubble from user (mine) */
            .chat-message.mine .chat-message-bubble {
                background: #e4f5d0; /* Light blue for user messages */
                border-radius: 8px 0 8px 8px;
            }
            
            /* Bubble tail for mine */
            .chat-message.mine .chat-message-bubble::before {
                content: '';
                position: absolute;
                top: 0;
                right: -8px;
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 0 0 13px 8px;
                border-color: transparent transparent transparent #eaf5d0;
            }
            
            .message-sender {
                font-size: 12.5px;
                font-weight: 600;
                color: #F28C1A !important;
                margin-bottom: 4px;
            }
            
            .chat-message.mine .message-sender {
                display: none;
            }
            
            /* Message text */
            .message-text {
                font-size: 14px;
                line-height: 19px;
                color: #ffffff !important;
                word-wrap: break-word;
                white-space: pre-wrap;
                margin-bottom: 4px;
            }
            
            /* Message metadata (time + status) */
            .message-meta {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 4px;
                margin-top: 2px;
            }
            
            .message-time {
                font-size: 11px;
                color: #728166 !important;
                line-height: 15px;
            }
            
            /* Use multiple selectors to override global styles */
            .chat-message-bubble .message-status,
            .message-meta .message-status,
            .chat-message .message-status {
                width: 16px !important;
                height: 16px !important;
                min-width: 16px !important;
                min-height: 16px !important;
                max-width: 16px !important;
                max-height: 16px !important;
                flex-shrink: 0;
                color: #728166 !important;
                fill: currentColor !important;
            }
            
            .chat-message:not(.mine) .message-status {
                display: none;
            }
            
            /* Loading dots animation */
            @keyframes loadingDots {
                0%, 20% { content: '.'; }
                40% { content: '..'; }
                60%, 100% { content: '...'; }
            }
            
            .message-status.loading::after {
                content: '...';
                animation: loadingDots 1.5s infinite;
                font-size: 16px;
                line-height: 16px;
            }
            
            .message-status.loading path {
                display: none;
            }
            
            .message-status.failed {
                color: #ef4444 !important;
                cursor: pointer;
            }
            
            .message-status.sent {
                color: #667781 !important;
            }
            
            .chat-new-form {
                flex: 1;
                overflow-y: auto;
                padding: 16px;
                display: flex;
                flex-direction: column;
            }
            
            .chat-new-form form {
                display: flex;
                flex-direction: column;
                gap: 16px;
                flex: 1;
            }
            
            .chat-new-form .form-group {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            
            .chat-new-form label {
                font-size: 14px;
                font-weight: 500;
                color: #374151;
            }
            
            .chat-new-form .form-control {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 14px;
                font-family: inherit;
                transition: border-color 0.2s;
                box-sizing: border-box;
            }
            
            .chat-new-form .form-control:focus {
                outline: none;
                border-color: #F28C1A;
                box-shadow: 0 0 0 3px rgba(242, 140, 26, 0.1);
            }
            
            .chat-new-form textarea.form-control {
                resize: vertical;
                min-height: 100px;
            }
            
            .chat-new-form .btn {
                padding: 12px 20px;
                border: none;
                border-radius: 8px;
                font-size: 15px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                text-align: center;
            }
            
            .chat-new-form .btn-primary {
                background: linear-gradient(135deg, #F28C1A 0%, #D97809 100%);
                color: white;
            }
            
            .chat-new-form .btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(242, 140, 26, 0.3);
            }
            
            .chat-new-form .btn-primary:active {
                transform: scale(0.98);
            }
            
            .chat-new-form .btn-block {
                width: 100%;
                margin-top: auto;
            }
            
            .chat-message-form {
                padding: 12px;
                border-top: 1px solid #e5e7eb;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: flex-end;
                flex-shrink: 0;
                background: white;
            }
            
            .chat-product-card {
                flex: 1 1 100%;
                max-width: 100%;
                display: flex;
                gap: 12px;
                padding: 12px;
                background: #f3f4f6;
                border-radius: 8px;
                margin-bottom: 4px;
                border: 1px solid #e5e7eb;
            }
            
            .chat-product-card img {
                width: 60px;
                height: 60px;
                object-fit: cover;
                border-radius: 6px;
                flex-shrink: 0;
            }
            
            .chat-product-card-content {
                flex: 1;
                min-width: 0;
            }
            
            .chat-product-card-name {
                font-weight: 500;
                font-size: 14px;
                color: #1f2937;
                margin-bottom: 4px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .chat-product-card-variant {
                font-size: 12px;
                color: #74806b;
                margin-bottom: 4px;
            }
            
            .chat-product-card-price {
                font-size: 13px;
                color: #8bea66;
                font-weight: 600;
            }
            
            .chat-product-card-remove {
                align-self: flex-start;
                background: none;
                border: none;
                color: #9ca3af;
                cursor: pointer;
                padding: 4px;
                font-size: 18px;
                line-height: 1;
                transition: color 0.2s;
            }
            
            .chat-product-card-remove:hover {
                color: #ef4444;
            }
            
            /* Product card in messages (read-only display) */
            .message-product-card {
                display: flex;
                gap: 10px;
                padding: 10px;
                background: rgba(255, 255, 255, 0.5);
                border-radius: 8px;
                margin-bottom: 6px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                align-items: center;
            }
            
            .chat-message.mine .message-product-card {
                background: rgba(255, 255, 255, 0.7);
            }
            
            .message-product-card img {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 6px;
                flex-shrink: 0;
            }
            
            .message-product-info {
                flex: 1;
                min-width: 0;
            }
            
            .message-product-name {
                font-weight: 500;
                font-size: 13px;
                color: #33371f;
                margin-bottom: 2px;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                line-height: 1.3;
            }
            
            .message-product-variant {
                font-size: 11px;
                color: #78806b;
                margin-bottom: 2px;
            }
            
            .message-product-price {
                font-size: 12px;
                color: #c7ea66;
                font-weight: 600;
            }
            
            .message-product-link {
                flex-shrink: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(102, 126, 234, 0.1);
                border-radius: 6px;
                color: #c7ea66;
                text-decoration: none;
                transition: all 0.2s;
            }
            
            .message-product-link:hover {
                background: #b7ea66;
                color: white;
            }
            
            .message-product-link svg {
                width: 16px;
                height: 16px;
            }
            
            .chat-message-input {
                flex: 1;
                border: 1px solid #d1d5db;
                border-radius: 20px;
                padding: 10px 16px;
                resize: none;
                max-height: 100px;
                font-family: inherit;
                font-size: 14px;
                line-height: 1.5;
                box-sizing: border-box;
                overflow-y: hidden;
            }
            
            .chat-message-input:focus {
                outline: none;
                border-color: #F28C1A;
                box-shadow: 0 0 0 3px rgba(242, 140, 26, 0.1);
            }
            
            .chat-send-btn {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: linear-gradient(135deg, #F28C1A 0%, #D97809 100%);
                border: none;
                color: white;
                cursor: pointer;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }
            
            .chat-send-btn:hover {
                transform: scale(1.05);
                box-shadow: 0 2px 8px rgba(242, 140, 26, 0.3);
            }
            
            .chat-send-btn:active {
                transform: scale(0.95);
            }
            
            .chat-loading {
                text-align: center;
                padding: 20px;
                color: #6b7280;
            }
            
            /* Date Separator (WhatsApp-style) */
            .date-separator {
                display: flex;
                align-items: center;
                gap: 12px;
                margin: 24px 0 16px;
                opacity: 0;
                animation: fadeInSeparator 0.3s ease forwards;
            }

            @keyframes fadeInSeparator {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .date-separator-line {
                flex: 1;
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1) 50%, transparent);
            }

            .date-separator-text {
                flex-shrink: 0;
                padding: 4px 12px;
                background: #e9ecef;
                color: #6c757d;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            }
            
            /* Mobile responsive */
            @media (max-width: 640px) {
                .chat-popup {
                    position: fixed !important; /* Override absolute positioning */
                    bottom: 45px !important; /* Above bottom nav */
                    right: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    /* Use dvh (dynamic viewport height) for browser UI awareness */
                    height: calc(100dvh - 45px) !important;
                    max-height: calc(100dvh - 45px) !important;
                    /* Fallback for browsers that don't support dvh */
                    height: calc(var(--viewport-height, 100vh) - 45px) !important;
                    max-height: calc(var(--viewport-height, 100vh) - 45px) !important;
                    border-radius: 0 !important;
                    z-index: 9999 !important;
                }
                
                .chat-popup-header {
                    border-radius: 0;
                }
                
                .chat-messages-container,
                .chat-conversations-list {
                    /* Adjust for mobile keyboard using dynamic viewport */
                    max-height: calc(100dvh - 190px);
                    /* Fallback for browsers that don't support dvh */
                    max-height: calc(var(--viewport-height, 100vh) - 190px);
                }
            }
        </style>

            <div id="chat-popup" class="chat-popup" style="display: none;">
                <div class="chat-popup-header">
                    <div class="chat-header-title">
                        <h3>Trợ giúp</h3>
                        <span id="chatOperatingHours" class="chat-operating-hours" style="display: none;"></span>
                    </div>
                    <div class="chat-header-actions">
                        <button id="chatNotifToggle" class="btn-notif-toggle" data-status="not-supported" title="Thông báo đẩy" style="display: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                            </svg>
                        </button>
                        <button id="chat-close-btn" class="chat-close-btn" aria-label="Đóng trò chuyện">×</button>
                    </div>
                </div>
                
                <!-- Notification Info Bar (conditional) -->
                <div class="chat-notif-info-bar" id="chatNotifInfoBar" style="display:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#ffc107">
                        <path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7zm2.85 11.1l-.85.6V16h-4v-2.3l-.85-.6C7.8 12.16 7 10.63 7 9c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.63-.8 3.16-2.15 4.1z"/>
                    </svg>
                    <span class="small">Bật thông báo để nhận phản hồi</span>
                    <button class="btn-dismiss" onclick="ChatNotification.dismissInfo()">✕</button>
                </div>
                
                <div class="chat-popup-body">
                    <!-- Conversations List View -->
                    <div id="chat-list-view" class="chat-view">
                        <div id="chat-conversations-list" class="chat-conversations-list">
                            <div class="chat-loading">Đang tải...</div>
                            <!-- Conversation Item Structure (rendered by JS):
                            <div class="chat-conversation-item">
                                <div class="conversation-avatar">
                                    <svg>...</svg>
                                </div>
                                <div class="conversation-content">
                                    <div class="conversation-header">
                                        <span class="conversation-name">Hỗ trợ viên</span>
                                        <span class="conversation-time">10:30</span>
                                    </div>
                                    <div class="conversation-preview">
                                        <p class="conversation-message">Cảm ơn bạn đã liên hệ với chúng tôi...</p>
                                        <span class="conversation-unread">2</span>
                                    </div>
                                </div>
                            </div>
                            -->
                        </div>
                        <button id="chat-new-btn" class="chat-new-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Cuộc trò chuyện mới
                        </button>
                    </div>
                    
                    <!-- Chat Messages View -->
                    <div id="chat-messages-view" class="chat-view" style="display: none;">
                        <div id="chat-messages-container" class="chat-messages-container">
                            <div class="chat-loading">Đang tải tin nhắn...</div>
                            <!-- Message Structure (rendered by JS):
                            <div class="chat-message"> (or .chat-message.mine)
                                <div class="chat-message-bubble">
                                    <div class="message-sender">Hỗ trợ viên</div> (only for non-mine)
                                    <div class="message-text">Xin chào, chúng tôi có thể giúp gì cho bạn?</div>
                                    <div class="message-meta">
                                        <span class="message-time">10:30</span>
                                        <svg class="message-status">...</svg> (only for mine)
                                    </div>
                                </div>
                            </div>
                            -->
                        </div>
                        
                        <form id="chat-message-form" class="chat-message-form">
                            <input type="hidden" id="chat-current-conversation-id" value="">
                            <textarea 
                                id="chat-message-input" 
                                class="chat-message-input" 
                                placeholder="Nhập tin nhắn..."
                                rows="1"
                                maxlength="5000"
                            ></textarea>
                            <button type="submit" class="chat-send-btn" aria-label="Gửi tin nhắn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </form>
                    </div>
                    
                    <!-- New Chat View -->
                    <div id="chat-new-view" class="chat-view" style="display: none;">
                        <div class="chat-messages-header">
                            <button id="chat-new-back-btn" class="chat-back-btn" aria-label="Quay lại danh sách">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <div class="chat-conversation-title">Cuộc trò chuyện mới</div>
                        </div>
                        
                        <div class="chat-new-form">
                            <form id="chat-start-form">
                                <div class="form-group">
                                    <label for="chat-new-subject">Chủ đề</label>
                                    <input 
                                        type="text" 
                                        id="chat-new-subject" 
                                        class="form-control" 
                                        placeholder="Chăm sóc khách hàng"
                                        maxlength="255"
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="chat-new-message">Tin nhắn</label>
                                    <textarea 
                                        id="chat-new-message" 
                                        class="form-control" 
                                        rows="4" 
                                        placeholder="Viết câu hỏi của bạn..."
                                        required
                                        maxlength="5000"
                                    ></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Gửi</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Login Required Modal -->
        <div class="modal-overlay" id="chatLoginModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <div class="modal-icon info">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M11,9H13V7H11M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,17H13V11H11V17Z"/>
                            </svg>
                        </div>
                        Cần đăng nhập
                    </h3>
                </div>
                <div class="modal-body">
                    <p class="modal-message">Bạn cần đăng nhập trước để dùng tính năng trò chuyện với bộ phận CSKH.</p>
                    <div class="modal-actions">
                        <button class="modal-btn modal-btn-secondary" id="chatLoginCancel">Hủy</button>
                        <button class="modal-btn modal-btn-primary" id="chatLoginConfirm">Đăng nhập ngay</button>
                    </div>
                </div>
            </div>
        </div>
    <!-- Footer Collapse Toggle Script -->
  <script nonce="se3iyzEHMc9FQEB4wSyd0w==">
  document.addEventListener('DOMContentLoaded', function() {
    const ftTop = document.getElementById('ftTop');
    const expandBtn = document.getElementById('ftExpandBtn');
    
    if (ftTop && expandBtn) {
      const expandText = expandBtn.querySelector('.expand-text');
      
      expandBtn.addEventListener('click', function() {
        ftTop.classList.toggle('expanded');
        const isExpanded = ftTop.classList.contains('expanded');
        if (expandText) {
          expandText.textContent = isExpanded ? 'Sembunyikan' : 'Selengkapnya';
        }
      });
    }
  });
  </script>
  </footer>

<!-- Compare Sidebar Overlay -->
<div class="compare-overlay" id="compareOverlay"></div>

<!-- Floating Compare Badge (Sticky) -->
<button class="compare-floating-badge" id="compareFloatingBadge" aria-label="Xem sản phẩm đang so sánh" style="display:none;">
  <svg class="cfb-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
    <path d="M9.01 14H2v2h7.01v3L13 15l-3.99-4v3zm5.98-1v-3H22V8h-7.01V5L11 9l3.99 4z"/>
  </svg>
  <span class="cfb-text">So sánh</span>
  <span class="cfb-count" id="cfbCount">0</span>
</button>

<!-- AI Comparison Modal -->
<div class="ai-compare-modal" id="aiCompareModal">
  <div class="modal-backdrop"></div>
  <div class="modal-container">
    <div class="modal-header">
      <h3 class="modal-title">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z"/>
        </svg>
        Phân tích AI: So sánh sản phẩm
      </h3>
      <button class="modal-close" id="aiCompareModalClose" aria-label="Đóng">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M18.3 5.7a1 1 0 0 0-1.4 0L12 10.6 7.1 5.7a1 1 0 1 0-1.4 1.4L10.6 12l-4.9 4.9a1 1 0 0 0 1.4 1.4L12 13.4l4.9 4.9a1 1 0 0 0 1.4-1.4L13.4 12l4.9-4.9a1 1 0 0 0 0-1.4z"/>
        </svg>
      </button>
    </div>
    
    <div class="modal-content">
      <!-- Loading State -->
      <div class="ai-loading" id="aiLoading">
        <div class="loading-animation">
          <div class="typing-indicator">
            <span></span>
            <span></span>
            <span></span>
          </div>
          <p>AI đang phân tích sản phẩm...</p>
          <small>Quá trình này mất khoảng 10-30 giây</small>
        </div>
      </div>
      
      <!-- Error State -->
      <div class="ai-error" id="aiError" hidden>
        <div class="error-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="#ef4444">
            <path d="M12,2C17.53,2 22,6.47 22,12C22,17.53 17.53,22 12,22C6.47,22 2,17.53 2,12C2,6.47 6.47,2 12,2M15.59,7L12,10.59L8.41,7L7,8.41L10.59,12L7,15.59L8.41,17L12,13.41L15.59,17L17,15.59L13.41,12L17,8.41L15.59,7Z"/>
          </svg>
        </div>
        <h4>Phân tích AI thất bại</h4>
        <p id="aiErrorMessage">Đã xảy ra lỗi khi xử lý phân tích.</p>
        <button class="btn btn-outline-primary" id="aiRetry">Thử lại</button>
      </div>
      
      <!-- Analysis Results -->
      <div class="ai-results" id="aiResults" hidden>
        <!-- Summary Section -->
        <div class="ai-summary-card">
          <h4>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z"/>
            </svg>
            Tóm tắt phân tích
          </h4>
          <p id="aiSummary" class="summary-text"></p>
          <div class="winner-badge" id="winnerBadge">
            <strong>Đề xuất tốt nhất:</strong> <span id="winnerProduct"></span>
            <small id="winnerReason"></small>
          </div>
        </div>
        
        <!-- Comparison Table -->
        <div class="ai-comparison-table">
          <h4>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M3,3H21V5H3V3M3,7H21V9H3V7M3,11H21V13H3V11M3,15H21V17H3V15M3,19H21V21H3V19Z"/>
            </svg>
            So sánh chi tiết
          </h4>
          <div id="comparisonTable" class="comparison-grid"></div>
        </div>
        
        <!-- Detailed Analysis -->
        <div class="ai-detailed-analysis">
          <h4>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M14,2A8,8 0 0,1 22,10A8,8 0 0,1 14,18A8,8 0 0,1 6,10A8,8 0 0,1 14,2M14,4A6,6 0 0,0 8,10A6,6 0 0,0 14,16A6,6 0 0,0 20,10A6,6 0 0,0 14,4M14,6A4,4 0 0,1 18,10A4,4 0 0,1 14,14A4,4 0 0,1 10,10A4,4 0 0,1 14,6M14,8A2,2 0 0,0 12,10A2,2 0 0,0 14,12A2,2 0 0,0 16,10A2,2 0 0,0 14,8Z"/>
            </svg>
            Phân tích chuyên sâu
          </h4>
          <div id="detailedAnalysis" class="products-analysis"></div>
        </div>
        
        <!-- Recommendations -->
        <div class="ai-recommendations">
          <h4>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/>
            </svg>
            Đề xuất theo nhu cầu
          </h4>
          <div class="recommendations-grid">
            <div class="recommendation-card">
              <h5>💰 Tiết kiệm chi phí</h5>
              <p id="budgetRecommendation"></p>
            </div>
            <div class="recommendation-card">
              <h5>⚡ Ưu tiên hiệu năng</h5>
              <p id="performanceRecommendation"></p>
            </div>
            <div class="recommendation-card">
              <h5>️ Lựa chọn cân bằng</h5>
              <p id="balancedRecommendation"></p>
            </div>
          </div>
        </div>
        
        <!-- Buying Tips -->
        <div class="ai-tips">
          <h4>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M11,18H13V16H11V18M12,6A4,4 0 0,0 8,10H10A2,2 0 0,1 12,8A2,2 0 0,1 14,10C14,12 11,11.75 11,15H13C13,12.75 16,12.5 16,10A4,4 0 0,0 12,6M5,3H19A2,2 0 0,1 21,5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3Z"/>
            </svg>
            Mẹo mua hàng
          </h4>
          <ul id="buyingTips" class="tips-list"></ul>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- S E L A L U R A N K -->
<!-- C G G X M I R AC L E -->
<!-- Cart Login Modal -->
<div class="modal-overlay" id="cartLoginModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title">
        <div class="modal-icon info">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17,18A2,2 0 0,1 19,20A2,2 0 0,1 17,22C15.89,22 15,21.1 15,20C15,18.89 15.89,18 17,18M1,2H4.27L5.21,4H20A1,1 0 0,1 21,5C21,5.17 20.95,5.34 20.88,5.5L17.3,11.97C16.96,12.58 16.3,13 15.55,13H8.1L7.2,14.63L7.17,14.75A0.25,0.25 0 0,0 7.42,15H19V17H7C5.89,17 5,16.1 5,15C5,14.65 5.09,14.32 5.24,14.04L6.6,11.59L3,4H1V2M7,18A2,2 0 0,1 9,20A2,2 0 0,1 7,22C5.89,22 5,21.1 5,20C5,18.89 5.89,18 7,18M16,11L18.78,6H6.14L8.5,11H16Z"/>
          </svg>
        </div>
        Đăng nhập để tiếp tục
      </h3>
    </div>
    <div class="modal-body">
      <p class="modal-message" style="margin-bottom: 16px;">Vui lòng đăng nhập hoặc đăng ký để truy cập giỏ hàng và hoàn tất đơn hàng của bạn.</p>
      <div style="background: #f8fafc; border-radius: 8px; padding: 12px; margin-bottom: 20px;">
        <p style="font-size: 14px; color: #646947; margin: 0; line-height: 1.6;">
          <strong style="color: #2a2a0f;">Lợi ích khi có tài khoản:</strong><br>
          ✓ Lưu giỏ hàng của bạn<br>
          ✓ Theo dõi trạng thái đơn hàng theo thời gian thực<br>
          ✓ Thanh toán nhanh hơn với dữ liệu đã lưu<br>
          ✓ Nhận thông báo khuyến mãi & giảm giá độc quyền
        </p>
      </div>
      <div class="modal-actions">
        <button class="modal-btn modal-btn-secondary" id="cartLoginCancel">Hủy</button>
        <button class="modal-btn modal-btn-primary" id="cartLoginConfirm" style="background: #ff6b35; border-color: #ff6b35;">Đăng nhập</button>
        <button class="modal-btn modal-btn-primary" id="cartRegisterBtn" style="background: #f7931e; border-color: #f7931e;">Đăng ký</button>
      </div>
    </div>
  </div>
</div>

    <style>
/* =========================================================
   ⚡ FIXED FOOTER – RED ELEGANT MODE
========================================================= */

.olx-top-fixed-footer{
  position:fixed;
  left:0; right:0; bottom:0;
  z-index:999;

  display:flex;
  align-items:center;
  justify-content:space-around;
  gap:8px;

  padding: 11px 12px calc(11px + env(safe-area-inset-bottom));
  overflow:hidden;

  background:
    radial-gradient(circle at 50% 0%, #000000, rgb(0 0 0 / 92%), rgb(0 0 0 / 98%)), linear-gradient(180deg, #ff9100, rgb(0 0 0 / 98%));

  border-top: 1px solid rgb(234, 0, 255);
  border-radius: 18px 18px 0 0;

  box-shadow: 0 -12px 30px #ff9100, inset 0 1px 0 rgba(255, 255, 255, .05);

  font-family:"Orbitron","Poppins",system-ui,sans-serif;
}

/* scanline */
.olx-top-fixed-footer::before{
  content:"";
  position:absolute;
  inset:0;
  pointer-events:none;
  background:
    repeating-linear-gradient(
      0deg,
      rgba(255,255,255,.015),
      rgba(255,255,255,.015) 1px,
      transparent 1px,
      transparent 6px
    );
  opacity:.85;
  mix-blend-mode:overlay;
}

/* shine sweep */
.olx-top-fixed-footer::after{
  content:"";
  position:absolute;
  top:0;
  left:-160%;
  width:60%;
  height:100%;
  background: linear-gradient(120deg, transparent, rgba(255, 90, 90, 0.25), transparent);
  transform: skewX(-25deg);
  animation: footerFinalShine 3.8s infinite;
  mix-blend-mode: screen;
  pointer-events:none;
}

@keyframes footerFinalShine{
  0%{left:-160%;}
  68%{left:160%;}
  100%{left:160%;}
}

/* =========================================================
   ITEM
========================================================= */

.olx-top-fixed-footer a{
  flex:1;
  max-width:110px;

  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:6px;

  padding: 8px 6px;

  text-decoration:none;
  text-align:center;

  color: rgb(255 255 255 / 90%);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .25px;

  border-radius: 14px;
  position:relative;
  z-index:1;

  transition: all .18s ease;
}

.olx-top-fixed-footer a:hover{
  color:#ffffff;
  background: rgba(255, 80, 80, 0.08);
  filter: drop-shadow(0 0 12px rgba(255, 80, 80, 0.15));
  transform: translateY(-1px);
}

.olx-top-fixed-footer a:active{
  transform: translateY(0) scale(.99);
}

/* =========================================================
   ICON
========================================================= */

.olx-top-fixed-footer img{
  width:26px;
  height:26px;
  transition: .18s ease;
  filter: drop-shadow(0 0 8px rgba(255, 80, 80, 0.35));
}

.olx-top-fixed-footer a:hover img{
  transform: scale(1.08);
  filter: drop-shadow(0 0 16px rgba(255, 80, 80, 0.6));
}

/* =========================================================
   HUD CORNERS
========================================================= */

.olx-top-fixed-footer .hud-corner{
  position:absolute;
  width:18px;height:18px;
  pointer-events:none;
  border: 1px solid rgba(255, 90, 90, 0.5);
  filter: drop-shadow(0 0 10px rgba(255, 80, 80, 0.2));
  opacity:.85;
}

.olx-top-fixed-footer .hud-tl{top:8px;left:10px;border-right:none;border-bottom:none;border-radius:10px 0 0 0;}
.olx-top-fixed-footer .hud-tr{top:8px;right:10px;border-left:none;border-bottom:none;border-radius:0 10px 0 0;}
.olx-top-fixed-footer .hud-bl{bottom:8px;left:10px;border-right:none;border-top:none;border-radius:0 0 0 10px;}
.olx-top-fixed-footer .hud-br{bottom:8px;right:10px;border-left:none;border-top:none;border-radius:0 0 10px 0;}

/* =========================================================
   CENTER CTA
========================================================= */

.olx-top-fixed-footer .tada{
  color:#ffffff;
  background:
    radial-gradient(circle at 50% 0%, #000000, rgba(0, 0, 0, 0) 70%), rgba(255, 60, 60, 0.08);
  border: 1px solid #ff9100;
  box-shadow: 0 0 18px #3f51b5ab, inset 0 0 16px rgb(63 81 181 / 18%);
}

.olx-top-fixed-footer .tada img{
  width:30px;
  height:30px;
  filter: drop-shadow(0 0 16px rgba(255, 80, 80, 0.65));
}

/* pulse */
.olx-top-fixed-footer .tada::before{
  content:"";
  position:absolute;
  inset:-12px;
  border-radius:999px;
  background:
    radial-gradient(circle, #3F51B5, transparent 70%), radial-gradient(circle, rgba(255, 120, 120, 0.08), transparent 72%);
  filter: blur(12px);
  opacity:.55;
  animation: corePulse 2.9s ease-in-out infinite;
  z-index:-1;
}

@keyframes corePulse{
  0%,100%{opacity:.35; transform:scale(.99);}
  50%{opacity:.75; transform:scale(1.02);}
}

/* ACTIVE */
.olx-top-fixed-footer a.is-active{
  color:#ffffff;
  background: rgba(255, 80, 80, 0.1);
  box-shadow: inset 0 0 0 1px rgba(255, 90, 90, 0.25);
}

.olx-top-fixed-footer a.is-active img{
  filter: drop-shadow(0 0 18px rgba(255, 80, 80, 0.8));
}

/* RESPONSIVE */
@media (max-width:500px){
  .olx-top-fixed-footer{
    padding: 10px 10px calc(10px + env(safe-area-inset-bottom));
    border-radius: 16px 16px 0 0;
    gap:6px;
  }
  .olx-top-fixed-footer a{
    font-size:11px;
    padding:7px 5px;
    gap:5px;
    max-width:104px;
  }
  .olx-top-fixed-footer img{width:22px;height:22px;}
  .olx-top-fixed-footer .tada img{width:26px;height:26px;}
}

body{
  background-image:
    linear-gradient(
      rgba(0, 20, 18, 0.70),
      rgba(0, 20, 18, 0.78)
    ),
    url("<?= h($heroImg) ?>") !important;
  height:100vh;
}
</style>


<style>.SLOT{margin:0;padding:0;font-family:'Playfair Display','Segoe UI',serif;background-color:transparent;color:#cabe0c;overflow:hidden}.mawar88-overlay{position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,.4);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);display:flex;justify-content:center;align-items:center;z-index:9999}.mawar88-container{position:relative;width:90%;max-width:420px;background:linear-gradient(145deg,rgb(0 0 0 / 90%),rgb(0 0 0 / 85%));border-radius:20px;overflow:hidden;text-align:center;animation:rotateScaleIn .8s cubic-bezier(.175,.885,.32,1.275),goldPulse 3.5s infinite ease-in-out;box-shadow:0 20px 40px rgba(0,0,0,.7),0 0 0 1px rgba(212,175,55,.2);padding-bottom:20px;border:1px solid rgb(234, 0, 255);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)}@keyframes goldPulse{0%,100%{box-shadow:0 20px 40px rgba(0,0,0,.7),0 0 20px rgba(30,144,255,.3),0 0 0 1px rgba(30,144,255,.2)}50%{box-shadow:0 20px 40px rgba(0,0,0,.7),0 0 30px rgba(0,191,255,.5),0 0 0 1px rgba(0,191,255,.3)}}.mawar88-container::before{content:"";position:absolute;top:-100%;left:-100%;width:200%;height:200%;background:linear-gradient(135deg,rgba(255,255,255,0) 45%,rgba(0,191,255,.28) 50%,rgba(255,255,255,0) 55%);animation:shineDiagonal 5s linear infinite;z-index:2;pointer-events:none}@keyframes shineDiagonal{0%{transform:translate(-100%,-100%) rotate(30deg)}100%{transform:translate(100%,100%) rotate(30deg)}}.mawar88-image{width:100%;display:block;border-bottom:1px solid rgb(234, 0, 255)}.clk-btn-sgp{width:97%;display:grid;grid-template-columns:repeat(2,1fr);gap:15px;font-family:Poppins,sans-serif;font-weight:700;padding:20px;position:relative;overflow:hidden}.clk-btn-sgp::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:calc(100% - 30px);height:2px;background:linear-gradient(90deg,transparent,#faea07,gold,#faea07,transparent);opacity:.4;filter:blur(1px)}.clk-btn-sgp a{text-align:center;text-transform:uppercase;letter-spacing:1.5px;padding:16px 12px;margin:0;border-radius:14px;transition:all .4s cubic-bezier(.23,1,.32,1);position:relative;overflow:hidden;backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border:2px solid transparent;box-shadow:0 8px 0 #000;clip-path:polygon(var(--blade-cut) 0,100% 0,100% calc(100% - var(--blade-cut)),calc(100% - var(--blade-cut)) 100%,0 100%,0 var(--blade-cut));z-index:1}.clk-btn-sgp a::after{content:'';position:absolute;top:-2px;left:-2px;right:-2px;bottom:-2px;background:linear-gradient(45deg,#ff9100,#ff9100,#ff9100,#ff9100,#ff9100);border-radius:16px;z-index:-1;opacity:0;transition:opacity .3s ease}.clk-btn-sgp a:hover::after{opacity:1}.clk-btn-sgp a:hover{transform:translateY(-6px) scale(1.03);border-color:#ffbf00}.login{color:gold!important;background:linear-gradient(145deg,#535050,#000);border:2px solid #cabe0c;text-shadow:0 2px 4px rgba(0,0,0,.5);position:relative}.login::before{position:absolute;left:15px;top:50%;transform:translateY(-50%);font-size:18px;opacity:.7;transition:all .3s ease;filter:drop-shadow(0 0 3px gold)}.login:hover{background:linear-gradient(145deg,#222,#1a1a1a);box-shadow:0 12px 0 #000,0 20px 40px rgba(212,175,55,.4),0 0 30px rgba(255,215,0,.2);color:#fff8dc!important;border-color:gold}.login:hover::before{opacity:1;transform:translateY(-50%) rotate(15deg);filter:drop-shadow(0 0 8px gold)}.register{color:#000000!important;background:linear-gradient(45deg,#ff9100,#ff9100,#ff9100,#ff9100,#ff9100);border:2px solid #000;text-shadow:0 1px 2px rgba(255,255,255,.3);position:relative}.register::before{position:absolute;right:15px;top:50%;transform:translateY(-50%);font-size:18px;opacity:.7;transition:all .3s ease}.register:hover{background:linear-gradient(145deg,gold,#faea07);box-shadow:0 12px 0 #000,0 20px 40px rgba(212,175,55,.5),0 0 35px rgba(255,215,0,.3);color:#000!important;border-color:#000}.register:hover::before{opacity:1;transform:translateY(-50%) scale(1.2) rotate(360deg);filter:drop-shadow(0 0 5px #d1a000)}.clk-btn-sgp a span{display:inline-block;transition:transform .3s ease}.clk-btn-sgp a:hover span{transform:translateX(3px)}@media (max-width:768px){.clk-btn-sgp{grid-template-columns:1fr;gap:12px}.clk-btn-sgp a{padding:18px 12px}.login::before,.register::before{position:relative;left:0;right:0;display:inline-block;margin-right:10px;transform:none;top:0}.login:hover::before,.register:hover::before{transform:none}}@keyframes subtleGlow{0%,100%{box-shadow:0 15px 35px rgba(0,0,0,.6),inset 0 1px 0 rgba(255,255,255,.1)}50%{box-shadow:0 15px 35px rgba(0,0,0,.6),inset 0 1px 0 rgba(255,255,255,.1),0 0 20px rgba(212,175,55,.15)}}.info-table{width:90%;margin:20px auto;border-collapse:collapse;color:#eee;font-size:14px;position:relative;z-index:3;background:rgba(0,0,0,.3);border-radius:10px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,.3)}.info-table th{background:linear-gradient(135deg,#00b7ff,#003b8f);padding:14px 10px;font-size:15px;color:#000;border:none;font-weight:700;letter-spacing:.8px;text-transform:uppercase}.info-table td{padding:12px;border-bottom:1px solid rgba(212,175,55,.1);text-align:left}.info-table tr:last-child td{border-bottom:none}.mawar88-footer{font-size:13px;color:#ccc;padding:20px 10px;position:relative;z-index:3;line-height:1.6;font-family:'Playfair Display',serif}@keyframes rotateScaleIn{0%{opacity:0;transform:scale(.3) rotate(-10deg)}50%{transform:scale(1.05) rotate(2deg)}100%{opacity:1;transform:scale(1) rotate(0)}}.gold-particle{position:absolute;background:radial-gradient(circle,rgba(255,215,0,.8) 0,rgba(212,175,55,.4) 100%);border-radius:50%;pointer-events:none;z-index:1;animation:floatGold 10s infinite ease-in-out;box-shadow:0 0 10px rgba(255,215,0,.6)}@keyframes floatGold{0%,100%{transform:translateY(0) translateX(0);opacity:0}10%{opacity:.8}90%{opacity:.8}100%{transform:translateY(-120px) translateX(40px);opacity:0}}.luxury-border{position:absolute;top:0;left:0;right:0;bottom:0;border:1px solid rgba(212,175,55,.2);border-radius:20px;pointer-events:none;z-index:4;background:linear-gradient(135deg,rgba(212,175,55,0) 0,rgba(212,175,55,.1) 50%,rgba(212,175,55,0) 100%)}.close-btn{position:absolute;top:15px;right:15px;width:32px;height:32px;background:rgba(0,0,0,.5);border-radius:50%;display:flex;justify-content:center;align-items:center;cursor:pointer;z-index:10;transition:all .3s ease;border:1px solid #ffeb3b}.close-btn:hover{background:#ffeb3bb5;transform:rotate(90deg)}.close-btn::after,.close-btn::before{content:'';position:absolute;width:16px;height:2px;background:#fff}.close-btn::before{transform:rotate(45deg)}.close-btn::after{transform:rotate(-45deg)}@media (max-width:480px){.mawar88-container{width:95%;max-width:350px}.mawar88-buttons a{font-size:14px;padding:16px 0}.info-table{font-size:13px}.mawar88-footer{font-size:12px}.close-btn{width:28px;height:28px}}</style><div class="mawar88-overlay" id="mawar88Overlay"><div class="mawar88-container"><div class="close-btn" onclick="closemawar88()"></div><div class="gold-particle" style="width:10px;height:10px;top:15%;left:12%;animation-delay:0s"></div><div class="gold-particle" style="width:8px;height:8px;top:35%;left:88%;animation-delay:1.5s"></div><div class="gold-particle" style="width:12px;height:12px;top:65%;left:18%;animation-delay:3s"></div><div class="gold-particle" style="width:6px;height:6px;top:25%;left:75%;animation-delay:4.5s"></div><div class="gold-particle" style="width:9px;height:9px;top:80%;left:60%;animation-delay:6s"></div><div class="luxury-border"></div><div class="SLOT"><img src="<?= h($heroImg) ?>" alt="<?= h($namaSitus) ?> Banner" class="mawar88-image"><div class="clk-btn-sgp" style="font-size:20px"><a href="<?= h($ctaUrl) ?>" target="_blank" rel="nofollow noreferrer" class="login">ĐĂNG NHẬP</a><a href="<?= h($ctaUrl) ?>" target="_blank" rel="nofollow noreferrer" class="register">ĐĂNG KÝ</a></div><div class="mawar88-footer"><?= h($page['title'] ?? '') ?><br><span style="color:#89ff00;font-weight:600;text-shadow:0 0 5px rgb(255 255 255 / 0%)">&copy; BẢN QUYỀN <?= h($tahun) ?> | <?= h($namaSitus) ?></span></div></div></div></div><script>function closemawar88() {
        const mawar88Overlay = document.getElementById('mawar88Overlay');
        mawar88Overlay.style.opacity = '0';
        mawar88Overlay.style.transition = 'opacity 0.3s ease';
        
        setTimeout(() => {
            mawar88Overlay.style.display = 'none';
        }, 300);
    }
    
    // Menutup <?= h($namaSitus) ?> ketika mengklik area di luar <?= h($namaSitus) ?>

    document.getElementById('mawar88Overlay').addEventListener('click', function(event) {
        if (event.target === this) {
            closemawar88();
        }
    });</script>

<canvas id="worldcup-confetti"></canvas>

<style>
#worldcup-confetti{
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 99999;
}
</style>

<div class="olx-top-fixed-footer">
  <a href="<?= h($ctaUrl) ?>" target="_blank" rel="nofollow noopener">
    <img src="https://imgstore.io/images/2025/02/15/icon-promo.png" alt="KHUYẾN MÃI <?= h($namaSitus) ?>">
    KHUYẾN MÃI
  </a>
  <a href="<?= h($ctaUrl) ?>" target="_blank" rel="nofollow noopener">
    <img src="https://imgstore.io/images/2025/02/15/icon-login.png" alt="ĐĂNG NHẬP <?= h($namaSitus) ?>">
    ĐĂNG NHẬP
  </a>
  <a href="<?= h($ctaUrl) ?>" target="_blank" rel="nofollow noopener" class="tada">
    <img src="https://imgstore.io/images/2025/02/15/icon-daftar.png" alt="ĐĂNG KÝ <?= h($namaSitus) ?>">
    ĐĂNG KÝ
  </a>
  <a href="<?= h($ctaUrl) ?>" target="_blank" rel="nofollow noopener">
    <img src="https://ik.imagekit.io/3vzov41jr/wa-sm.png" alt="WHATSAPP <?= h($namaSitus) ?>">
    WHATSAPP
  </a>
  <a href="<?= h($ctaUrl) ?>" target="_blank" rel="nofollow noopener">
    <img src="https://imgstore.io/images/2025/02/15/icon-livechat.png" alt="LIVE CHAT <?= h($namaSitus) ?>">
    LIVE CHAT
  </a>
</div>
</body>
</html>