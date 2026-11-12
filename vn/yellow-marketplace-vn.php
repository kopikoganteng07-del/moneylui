<?php
/* yellow-marketplace-vn: versi region Vietnam dari yellow-marketplace moneylui (teks antarmuka bahasa Vietnam). Dibuat 12 Nov 2026. */
?><?php
/**
 * ---------------------------------------------------------
 * Template yellow-marketplace (Bimasakti / Hoksiong runtime).
 * Satu file, semua CSS ditanam. Membaca /data/data.json yang ditulis pipeline.
 * Ganti template = ganti file ini, TIDAK perlu sentuh data.json.
 *
 * Kontrak data (sama dengan T1):
 *   per halaman : title, meta_description, h1, content_html, image_src,
 *                 canonical_url, amp_url, noindex, lang, lastmod, category, keyword
 *   homepage    : + logo_src, favicon_src, banner_src, cta_url, gsc_token (opsional)
 *
 * Banner tipe SQUARE (kotak 1:1, object-fit: cover).
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

$remahLabel = $isHomepage ? 'Thông tin' : judulKata($page['keyword'] ?? '');

// FAQ statis (tampil di semua halaman; schema FAQPage hanya di beranda).
$faq = [
    ['Làm sao để tìm thông tin chính?', 'Hãy dùng thanh điều hướng và phần tóm tắt trên trang này.'],
    ['Trang có hỗ trợ thiết bị di động không?', 'Có, bố cục tự điều chỉnh theo kích thước màn hình.'],
    ['Nút truy cập nằm ở đâu?', 'Nút chính thức có ở phần thông tin và cuối trang.'],
    ['Thông tin nhận diện của trang được hiển thị thế nào?', 'Tiêu đề, logo và mô tả theo dữ liệu chính thức gắn với trang.'],
    ['Làm sao để đọc thêm hướng dẫn?', 'Hãy đọc bài viết và phần tóm tắt có trong mục thông tin.'],
];
$sorotan = [
    ['Điều hướng rõ ràng', 'Thông tin chính được sắp xếp gọn gàng, dễ tìm.'],
    ['Giao diện tương thích', 'Bố cục phù hợp cả máy tính lẫn thiết bị di động.'],
    ['Truy cập tập trung', 'Nút chính thức luôn ở vị trí nhất quán.'],
];
?>
<!doctype html>
<html lang="<?= h($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#252525">
  <title><?= h($page['title'] ?? '') ?></title>
  <meta name="description" content="<?= h($page['meta_description'] ?? '') ?>">
  <meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow' ?>">
<?php if (!empty($gscToken)): ?>
  <meta name="google-site-verification" content="<?= h($gscToken) ?>">
<?php endif; ?>
  <link rel="canonical" href="<?= h($page['canonical_url'] ?? '') ?>">
<?php if ($adaAmp): ?>
  <link rel="amphtml" href="<?= h($ampUrl) ?>"><link rel="alternate" hreflang="vi-vn" href="<?= h($ampUrl) ?>"><link rel="alternate" href="<?= h($ampUrl) ?>"><link rel="alternate" hreflang="vi" href="<?= h($ampUrl) ?>"><link rel="alternate" hreflang="en" href="<?= h($ampUrl) ?>"><link rel="alternate" hreflang="x-default" href="<?= h($ampUrl) ?>">
<?php endif; ?>
  <link rel="icon" href="<?= h($faviconSrc) ?>">
  <meta property="og:type" content="<?= $isHomepage ? 'website' : 'article' ?>">
  <meta property="og:title" content="<?= h($page['title'] ?? '') ?>">
  <meta property="og:description" content="<?= h($page['meta_description'] ?? '') ?>">
  <meta property="og:image" content="<?= h($ogAbs) ?>">
  <meta property="og:url" content="<?= h($page['canonical_url'] ?? '') ?>">
  <meta name="twitter:card" content="summary_large_image">
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
  <style>
    :root {
      --yellow: #ffd400;
      --yellow-dark: #e6a800;
      --black: #050505;
      --dark: #252525;
      --text: #353535;
      --muted: #777;
      --line: #e2e5e8;
      --max: 1080px;
      --radius: 11px;
      --shadow: 0 10px 24px rgba(0, 0, 0, .2);
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      min-width: 320px;
      color: var(--text);
      background: #fff;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 14px;
      line-height: 1.5;
    }
    img { display: block; max-width: 100%; }
    a { color: inherit; text-decoration: none; }
    summary { cursor: pointer; }
    :focus-visible { outline: 3px solid #ff9f00; outline-offset: 3px; }

    .container {
      width: min(calc(100% - 32px), var(--max));
      margin-inline: auto;
    }

    .site-header {
      color: #ddd;
      background: var(--dark);
      border-top: 3px solid #f3e5ff;
    }
    .header-row {
      min-height: 108px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 28px;
    }
    .brand img { width: 220px; height: auto; }
    .desktop-nav { display: flex; align-items: center; gap: 8px; }
    .desktop-nav a {
      padding: 11px 12px;
      color: #d9d9d9;
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .desktop-nav a:hover { color: var(--yellow); }
    .desktop-nav .dark-action { background: #303030; }
    .mobile-nav { display: none; position: relative; }
    .mobile-nav summary {
      list-style: none;
      padding: 9px 12px;
      border: 1px solid #676767;
      border-radius: 6px;
      color: var(--yellow);
      font-weight: 800;
    }
    .mobile-nav summary::-webkit-details-marker { display: none; }
    .mobile-links {
      position: absolute;
      z-index: 10;
      right: 0;
      top: calc(100% + 8px);
      width: 220px;
      padding: 9px;
      border-radius: 9px;
      background: #222;
      box-shadow: var(--shadow);
    }
    .mobile-links a { display: block; padding: 10px; border-radius: 5px; }
    .mobile-links a:hover { color: var(--yellow); background: #333; }

    .page-head {
      padding: 18px 0 0;
      border-bottom: 1px solid var(--line);
      background: #fafafa;
    }
    .breadcrumbs {
      display: flex;
      flex-wrap: wrap;
      gap: 7px;
      margin: 0 0 8px;
      color: #676767;
      font-size: 12px;
      text-transform: uppercase;
    }
    .breadcrumbs a:not(:last-child)::after { content: "›"; margin-left: 7px; color: #aaa; }
    h1 {
      margin: 0;
      color: #383838;
      font-size: clamp(25px, 3vw, 32px);
      line-height: 1.2;
      letter-spacing: -.02em;
    }
    .seller-row {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-top: 8px;
      align-items: center;
      color: #333;
    }
    .tabs {
      display: flex;
      align-items: end;
      gap: 0;
      margin-top: 17px;
      overflow-x: auto;
      scrollbar-width: thin;
    }
    .tabs a {
      flex: 0 0 auto;
      padding: 12px 10px;
      border-bottom: 2px solid transparent;
      color: #555;
      font-size: 12px;
      white-space: nowrap;
    }
    .tabs a:first-child { border: 1px solid var(--line); border-bottom: 2px solid #fff; background: #fff; font-weight: 800; }

    main { padding: 20px 0 26px; }
    .product-grid {
      display: grid;
      grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
      gap: 20px;
      align-items: start;
    }
    .left-column, .right-column { min-width: 0; }
    .right-column { display: grid; gap: 20px; }
    .cta-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .cta-row.bottom { margin-top: 20px; }
    .cta {
      min-height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px;
      border: 1px solid #ffd72a;
      border-radius: 9px;
      color: #111;
      background: linear-gradient(180deg, #fff346, #ffc500 55%, #dda300);
      box-shadow: 0 0 18px rgba(255, 202, 0, .45), inset 0 1px #fffbd3;
      font-size: 15px;
      font-weight: 800;
      letter-spacing: .04em;
      text-align: center;
    }
    .cta:hover { filter: brightness(1.05); }
    .banner-frame {
      aspect-ratio: 1;
      padding: 10px;
      border: 8px solid var(--yellow);
      background: #050505;
      overflow: hidden;
    }
    .banner-frame img { width: 100%; height: 100%; object-fit: cover; }

    .info-card {
      overflow: hidden;
      border: 2px solid var(--yellow);
      border-radius: 10px;
      background: #fff;
      box-shadow: var(--shadow);
    }
    .info-card h2 {
      margin: 0;
      padding: 20px 14px;
      color: #080808;
      background: var(--yellow);
      font-size: 20px;
      line-height: 1.35;
      text-align: center;
      text-transform: uppercase;
    }
    .spec { display: grid; grid-template-columns: 1fr 1.22fr; gap: 12px; padding: 14px; border-top: 1px solid #ddd; }
    .spec strong { color: #111; }
    .spec span { color: #111; font-weight: 700; }

    .content-panel {
      margin-top: 28px;
      padding: clamp(26px, 5vw, 48px) 24px 22px;
      color: #fff;
      border: 1px solid var(--yellow);
      border-radius: 11px;
      background: #000;
      box-shadow: 0 8px 28px rgba(0, 0, 0, .3);
    }
    .content-panel h2 {
      max-width: 760px;
      margin: 0 auto;
      color: var(--yellow);
      font-size: clamp(25px, 4vw, 36px);
      line-height: 1.2;
      text-align: center;
    }
    .content-panel h3 {
      margin: 38px 0 22px;
      color: var(--yellow);
      font-size: clamp(22px, 3vw, 29px);
      text-align: center;
      text-transform: uppercase;
    }
    .content-description { max-width: 760px; margin: 24px auto 0; color: #eee; font-size: 16px; line-height: 1.75; text-align: center; }
    .article-content { max-width: 820px; margin: 28px auto 0; color: #eee; font-size: 16px; line-height: 1.75; }
    .article-content h2, .article-content h3 { margin: 30px 0 12px; color: var(--yellow); text-align: left; text-transform: none; font-size: clamp(20px, 2.6vw, 26px); }
    .article-content h3 { font-size: clamp(18px, 2.2vw, 22px); }
    .article-content p { margin: 0 0 16px; }
    .article-content ul, .article-content ol { padding-left: 22px; }
    .article-content a { color: var(--yellow); text-decoration: underline; }
    .route-links { max-width: 820px; margin: 24px auto 0; padding: 0; display: grid; gap: 8px; list-style: none; }
    .route-links a { display: block; padding: 10px 12px; border-left: 3px solid var(--yellow); color: #fff; background: #242424; }
    .route-links a:hover { color: var(--yellow); }
    .access-card { padding: 21px; border: 1px solid #d7dce1; background: #fff; box-shadow: 0 8px 17px rgba(0, 0, 0, .09); }
    .access-head { display: grid; gap: 5px; padding-bottom: 15px; border-bottom: 1px solid #d7dce1; }
    .access-head span { color: #676767; font-size: 13px; font-weight: 700; text-transform: uppercase; }
    .access-head strong { color: #111; font-size: 24px; overflow-wrap: anywhere; }
    .access-list { display: grid; gap: 9px; margin: 16px 0; padding: 0; list-style: none; color: #676767; }
    .access-list li { position: relative; padding-left: 23px; }
    .access-list li::before { content: '✓'; position: absolute; left: 0; color: #777; font-weight: 900; }
    .access-action { min-height: 44px; display: flex; align-items: center; justify-content: center; padding: 10px; color: #111; border: 1px solid #d8a900; border-radius: 7px; background: var(--yellow); font-weight: 800; text-align: center; }
    .highlights { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin-top: 18px; }
    .highlight-card { min-width: 0; min-height: 190px; display: grid; align-content: start; gap: 14px; padding: 24px; border-radius: 12px; background: #272727; }
    .highlight-card strong { color: var(--yellow); font-size: 16px; }
    .highlight-card span { color: #eee; font-size: 14px; line-height: 1.65; }
    .faq { display: grid; gap: 5px; }
    .faq details { overflow: hidden; border-radius: 13px; }
    .faq summary {
      list-style: none;
      padding: 13px 15px;
      color: #111;
      background: linear-gradient(90deg, #d49900 0%, #fff728 47%, #d79d00 100%);
      font-weight: 800;
    }
    .faq summary::-webkit-details-marker { display: none; }
    .faq-answer { padding: 14px 16px 17px; color: #ddd; background: #1c1c1c; }

    footer { padding: 0 0 24px; }
    .footer-box {
      padding: 18px;
      color: var(--yellow);
      border: 1px solid var(--yellow);
      border-radius: 8px;
      background: #000;
      font-weight: 800;
      text-align: center;
    }

    @media (max-width: 800px) {
      .header-row { min-height: 82px; }
      .brand img { width: 155px; }
      .desktop-nav { display: none; }
      .mobile-nav { display: block; }
      .page-head { padding-top: 13px; }
      .breadcrumbs { font-size: 10px; }
      h1 { font-size: 23px; }
      .product-grid { grid-template-columns: 1fr; }
      .right-column { gap: 14px; }
      .highlights { grid-template-columns: 1fr; }
      .info-card h2 { font-size: 18px; }
    }

    @media (max-width: 520px) {
      body { font-size: 13px; }
      .tabs { flex-wrap: wrap; overflow-x: visible; }
      .container { width: min(calc(100% - 20px), var(--max)); }
      .seller-row { gap: 8px 12px; }
      .tabs a { padding-inline: 8px; }
      main { padding-top: 13px; }
      .cta-row.bottom { margin-top: 12px; }
      .cta { min-height: 44px; font-size: 13px; }
      .banner-frame { padding: 5px; border-width: 5px; }
      .spec { grid-template-columns: .9fr 1.1fr; padding: 12px 11px; font-size: 12px; }
      .content-panel { margin-top: 18px; padding: 27px 14px 16px; }
      .content-panel h3 { margin-top: 30px; }
      .faq summary { padding: 12px; font-size: 12px; }
      footer { padding-bottom: 12px; }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container header-row">
      <a class="brand" href="/" aria-label="Trang chủ <?= h($namaSitus) ?>">
        <img src="<?= h($logoSrc) ?>" width="350" height="100" alt="<?= h($namaSitus) ?>">
      </a>
      <nav class="desktop-nav" aria-label="Điều hướng chính">
<?php foreach ($menuHeader as $slug => $item): ?>
        <a href="/<?= h($slug) ?>"><?= h($labelPost($slug, $item)) ?></a>
<?php endforeach; ?>
        <a href="<?= h($ctaUrl) ?>" rel="nofollow">Đăng ký</a>
        <a class="dark-action" href="<?= h($ctaUrl) ?>" rel="nofollow">Đăng nhập</a>
      </nav>
      <details class="mobile-nav">
        <summary>Menu</summary>
        <nav class="mobile-links" aria-label="Điều hướng di động">
          <a href="/">Trang chủ</a>
<?php foreach ($allPosts as $slug => $item): ?>
          <a href="/<?= h($slug) ?>"><?= h($labelPost($slug, $item)) ?></a>
<?php endforeach; ?>
          <a href="<?= h($ctaUrl) ?>" rel="nofollow">Đăng ký</a>
          <a href="<?= h($ctaUrl) ?>" rel="nofollow">Đăng nhập</a>
        </nav>
      </details>
    </div>
  </header>

  <section class="page-head">
    <div class="container">
      <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="/">Trang chủ</a>
        <a href="#informasi"><?= h($remahLabel) ?></a>
      </nav>
      <h1><?= h($page['h1'] ?? '') ?></h1>
      <div class="seller-row"><span><strong><?= h($namaSitus) ?></strong></span></div>
      <nav class="tabs" aria-label="Điều hướng trang">
        <a href="#informasi">Chi tiết</a>
        <a href="#tentang">Giới thiệu</a>
        <a href="<?= h($ctaUrl) ?>" rel="nofollow">Truy cập</a>
      </nav>
    </div>
  </section>

  <main id="informasi">
    <div class="container">
      <section class="product-grid" aria-label="Thông tin <?= h($namaSitus) ?>">
        <div class="left-column">
          <div class="banner-frame">
<?php if (!empty($heroImg)): ?>
            <img src="<?= h($heroImg) ?>" width="600" height="600" alt="<?= h(($page['keyword'] ?? '') !== '' ? $page['keyword'] : $namaSitus) ?>" fetchpriority="high" decoding="async">
<?php endif; ?>
          </div>
          <div class="cta-row bottom">
            <a class="cta" href="<?= h($ctaUrl) ?>" rel="nofollow">ĐĂNG NHẬP</a>
            <a class="cta" href="<?= h($ctaUrl) ?>" rel="nofollow">ĐĂNG KÝ</a>
          </div>
        </div>
        <aside class="right-column" aria-label="Tóm tắt và truy cập">
          <section class="access-card" aria-labelledby="access-title">
            <div class="access-head">
              <span id="access-title">Truy cập chính thức</span>
              <strong><?= h($namaSitus) ?></strong>
            </div>
            <ul class="access-list">
<?php foreach ($sorotan as $s): ?>
              <li><?= h($s[0]) ?></li>
<?php endforeach; ?>
            </ul>
            <a class="access-action" href="<?= h($ctaUrl) ?>" rel="nofollow">MỞ TRUY CẬP</a>
          </section>
          <section class="info-card">
            <h2>Thông tin <?= h($namaSitus) ?></h2>
            <div class="spec"><strong>Tên trang</strong><span><?= h($namaSitus) ?></span></div>
            <div class="spec"><strong>Thông tin</strong><span><?= h($page['meta_description'] ?? '') ?></span></div>
            <div class="spec"><strong>Giao diện</strong><span>Tương thích máy tính và di động</span></div>
            <div class="spec"><strong>Truy cập</strong><span>Qua nút chính thức trên trang này</span></div>
          </section>
        </aside>
      </section>

      <section class="content-panel" id="tentang" aria-labelledby="content-title">
        <h2 id="content-title"><?= h($page['h1'] ?? '') ?></h2>
        <p class="content-description"><?= h($page['meta_description'] ?? '') ?></p>
        <div class="article-content"><?= $isiArtikel ?></div>
<?php if (count($postLain) > 0): ?>
        <ul class="route-links"><?php foreach ($postLain as $slug => $item): ?><li><a href="/<?= h($slug) ?>"><?= h(($item['title'] ?? '') !== '' ? $item['title'] : $labelPost($slug, $item)) ?></a></li><?php endforeach; ?><?php if (!$isHomepage): ?><li><a href="/">Về Trang chủ <?= h($namaSitus) ?></a></li><?php endif; ?></ul>
<?php endif; ?>
        <section class="faq" aria-label="Câu hỏi thường gặp">
          <h3>Câu hỏi thường gặp</h3>
<?php foreach ($faq as $q): ?>
          <details><summary><?= h($q[0]) ?></summary><div class="faq-answer"><?= h($q[1]) ?></div></details>
<?php endforeach; ?>
        </section>
        <h3>Điểm nổi bật của dịch vụ</h3>
        <div class="highlights">
<?php foreach ($sorotan as $s): ?>
          <article class="highlight-card"><strong><?= h($s[0]) ?></strong><span><?= h($s[1]) ?></span></article>
<?php endforeach; ?>
        </div>
        <div class="cta-row bottom">
          <a class="cta" href="<?= h($ctaUrl) ?>" rel="nofollow">ĐĂNG NHẬP</a>
          <a class="cta" href="<?= h($ctaUrl) ?>" rel="nofollow">ĐĂNG KÝ</a>
        </div>
      </section>
    </div>
  </main>

  <footer>
    <div class="container footer-box">Bản quyền © <?= date('Y') ?> — <?= h($namaSitus) ?></div>
  </footer>
</body>
</html>
