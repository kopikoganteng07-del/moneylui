<?php
/**
 * ---------------------------------------------------------
 * Template Golden-Gallery (Bimasakti / Hoksiong runtime).
 * Satu file, semua CSS ditanam. Membaca /data/data.json yang ditulis pipeline.
 * Ganti template = ganti file ini, TIDAK perlu sentuh data.json.
 *
 * Kontrak data (sama dengan T1):
 *   per halaman : title, meta_description, h1, content_html, image_src,
 *                 canonical_url, amp_url, noindex, lang, lastmod, category, keyword
 *   homepage    : + logo_src, favicon_src, banner_src, cta_url, gsc_token (opsional)
 *
 * Banner tipe PORTRAIT (kotak 6:7). Gambar persegi tetap tampil utuh (object-fit: contain).
 * Gambar asset-game-1..5.jpg tetap diunggah pipeline tetapi tidak dipakai template ini.
 */

$id   = (isset($_GET['id']) && is_string($_GET['id']) && $_GET['id'] !== '') ? $_GET['id'] : 'homepage';
$file = __DIR__ . '/data/data.json';

if (!file_exists($file)) {
    http_response_code(500);
    exit('data.json tidak ditemukan.');
}

$data = json_decode(file_get_contents($file), true);
if (!is_array($data)) {
    http_response_code(500);
    exit('data.json tidak valid.');
}
$page = (isset($data[$id]) && is_array($data[$id])) ? $data[$id] : null;

if (!$page) {
    http_response_code(404);
    exit('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="robots" content="noindex,nofollow"><title>404</title></head><body><h1>404</h1><p><a href="/">Kembali ke Beranda</a></p></body></html>');
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

// Satu CTA global untuk semua tombol (Login, Daftar, LOGIN, DAFTAR AKUN).
$ctaUrl = $homepage['cta_url'] ?? ($homepage['cta_daftar_url'] ?? ($homepage['cta_login_url'] ?? '#'));
$ctaUrl = trim((string)$ctaUrl) !== '' ? trim((string)$ctaUrl) : '#';

$gscToken = $homepage['gsc_token'] ?? '';
$noindex  = !empty($page['noindex']);
$lang     = $page['lang'] ?? 'id';
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
// (misalnya "Dora88," tidak berubah menjadi "Dora88 ," saat tag dibuang).
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

$judulSeksi = $isHomepage ? ($page['h1'] ?? '') : ('Tentang ' . judulKata($page['keyword'] ?? $namaSitus));
$remahLabel = $isHomepage ? 'Panduan Platform' : judulKata($page['keyword'] ?? '');

// FAQ statis (tampil di semua halaman; schema FAQPage hanya di beranda).
$faq = [
    ['Apa keunggulan ' . $namaSitus . ' sebagai platform online?',
     $namaSitus . ' menyusun informasi berbagai kategori permainan melalui tampilan modern yang mudah dijelajahi. Pengunjung dapat mengenali fitur dan menelusuri kategori melalui perangkat desktop maupun ponsel.'],
    ['Bagaimana cara mengakses informasi melalui perangkat mobile?',
     'Halaman dapat dibuka langsung melalui browser. Sebelum memasang aplikasi apa pun, pastikan sumber unduhan dapat dipercaya, perangkat mendukung versinya, dan izin yang diminta sesuai kebutuhan.'],
    ['Apakah istilah “gacor” menjamin hasil permainan?',
     'Tidak. Istilah tersebut merupakan istilah populer dan tidak membuktikan peluang kemenangan yang lebih tinggi. Hasil permainan mengikuti mekanisme dan aturan masing-masing game.'],
    ['Apa yang perlu diperiksa sebelum menggunakan suatu layanan?',
     'Periksa keaslian alamat situs, informasi pengelola, ketentuan penggunaan, keamanan akun, serta status perizinan yang dapat diverifikasi secara mandiri.'],
];

?>
<!doctype html>
<html lang="<?= h($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#090909">
  <title><?= h($page['title'] ?? '') ?></title>
  <meta name="description" content="<?= h($page['meta_description'] ?? '') ?>">
  <meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow' ?>">
<?php if (!empty($gscToken)): ?>
  <meta name="google-site-verification" content="<?= h($gscToken) ?>">
<?php endif; ?>
  <link rel="canonical" href="<?= h($page['canonical_url'] ?? '') ?>">
<?php if ($adaAmp): ?>
  <link rel="amphtml" href="<?= h($ampUrl) ?>"><link rel="alternate" hreflang="id-id" href="<?= h($ampUrl) ?>"><link rel="alternate" href="<?= h($ampUrl) ?>"><link rel="alternate" hreflang="id" href="<?= h($ampUrl) ?>"><link rel="alternate" hreflang="en" href="<?= h($ampUrl) ?>"><link rel="alternate" hreflang="x-default" href="<?= h($ampUrl) ?>">
<?php endif; ?>
  <link rel="icon" href="<?= h($faviconSrc) ?>">
  <meta property="og:type" content="<?= $isHomepage ? 'website' : 'article' ?>">
  <meta property="og:title" content="<?= h($page['title'] ?? '') ?>">
  <meta property="og:description" content="<?= h($page['meta_description'] ?? '') ?>">
  <meta property="og:url" content="<?= h($page['canonical_url'] ?? '') ?>">
  <meta property="og:image" content="<?= h($ogAbs) ?>">
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
            'inLanguage' => 'id-ID',
            'author' => ['@type' => 'Organization', 'name' => $domainName, 'url' => $origin . '/'],
            'publisher' => ['@type' => 'Organization', 'name' => $domainName, 'url' => $origin . '/',
                'logo' => ['@type' => 'ImageObject', 'url' => $origin . $logoSrc]],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => (string)($page['canonical_url'] ?? '')],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $isHomepage
                ? [['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => $origin . '/']]
                : [['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => $origin . '/'],
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
      color-scheme: dark;
      --bg: #070708;
      --surface: #111113;
      --surface-2: #191817;
      --line: rgba(255, 190, 38, .26);
      --line-strong: #d69a00;
      --gold: #ffd238;
      --gold-2: #f19a00;
      --orange: #ff6500;
      --text: #f5f5f3;
      --muted: #aaa9a5;
      --max: 1180px;
      --radius: 16px;
      --shadow: 0 18px 42px rgba(0, 0, 0, .42);
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      min-width: 320px;
      color: var(--text);
      background:
        radial-gradient(circle at 10% 8%, rgba(255, 161, 0, .07), transparent 25rem),
        radial-gradient(circle at 90% 28%, rgba(255, 80, 0, .045), transparent 30rem),
        var(--bg);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 16px;
      line-height: 1.6;
    }

    img { display: block; max-width: 100%; }
    a { color: inherit; text-decoration: none; }
    button, summary { font: inherit; }
    :focus-visible { outline: 3px solid #fff1a3; outline-offset: 3px; }

    .container {
      width: min(calc(100% - 32px), var(--max));
      margin-inline: auto;
    }

    .topline {
      border-bottom: 1px solid rgba(255, 186, 32, .15);
      background: #050505;
      color: #bbb7ae;
      font-size: 13px;
    }
    .topline .container {
      min-height: 36px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .topline strong { color: var(--gold); }

    .site-header {
      position: sticky;
      top: 0;
      z-index: 20;
      border-bottom: 1px solid rgba(255, 187, 35, .25);
      background: rgba(8, 8, 9, .94);
      backdrop-filter: blur(14px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, .25);
    }
    .header-row {
      min-height: 82px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 28px;
    }
    .brand img { width: 190px; height: auto; }
    .main-nav { display: flex; align-items: center; gap: 6px; }
    .main-nav a {
      padding: 10px 13px;
      border: 1px solid transparent;
      border-radius: 8px;
      color: #d8d7d3;
      font-size: 14px;
      font-weight: 700;
      white-space: nowrap;
      transition: .2s ease;
    }
    .main-nav a:hover {
      color: #fff5c1;
      border-color: var(--line);
      background: #18140d;
    }
    .main-nav .nav-cta {
      color: #190d00;
      border-color: #ffe373;
      background: linear-gradient(180deg, #ffe96f, #f3a500);
      box-shadow: 0 5px 16px rgba(255, 158, 0, .18);
    }
    .mobile-nav { display: none; position: relative; }
    .mobile-nav summary {
      cursor: pointer;
      list-style: none;
      padding: 9px 13px;
      border: 1px solid var(--line);
      border-radius: 8px;
      color: var(--gold);
      font-weight: 800;
    }
    .mobile-nav summary::-webkit-details-marker { display: none; }
    .mobile-links {
      position: absolute;
      right: 0;
      top: calc(100% + 10px);
      width: 220px;
      padding: 10px;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: #101011;
      box-shadow: var(--shadow);
    }
    .mobile-links a { display: block; padding: 10px 12px; border-radius: 7px; }
    .mobile-links a:hover { background: #21190b; color: var(--gold); }

    .ticker {
      overflow: hidden;
      border-bottom: 1px solid rgba(255, 163, 0, .18);
      background: linear-gradient(90deg, #090909, #171108, #090909);
    }
    .ticker p {
      margin: 0 auto;
      padding: 9px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #f0c75c;
      font-size: 13px;
      font-weight: 700;
      text-align: center;
      letter-spacing: .04em;
    }

    main { padding: 30px 0 54px; }
    .breadcrumbs {
      margin: 0 0 18px;
      color: #a7a39a;
      font-size: 13px;
    }
    .breadcrumbs a { color: #e6c366; }

    .product-shell {
      display: grid;
      grid-template-columns: minmax(0, 1.08fr) minmax(340px, .92fr);
      gap: 24px;
      align-items: start;
      padding: 24px;
      border: 1px solid var(--line);
      border-radius: 20px;
      background: linear-gradient(145deg, rgba(22, 22, 24, .98), rgba(7, 7, 8, .98));
      box-shadow: var(--shadow);
    }

    .media-panel, .info-panel { min-width: 0; }
    .main-art {
      overflow: hidden;
      aspect-ratio: 6 / 7;
      border: 1px solid rgba(255, 211, 78, .6);
      border-radius: 13px;
      background: #030303;
      box-shadow: 0 0 0 5px #0b0b0c, 0 0 0 6px rgba(255, 182, 16, .45);
    }
    .main-art img { width: 100%; height: 100%; object-fit: contain; }
    .eyebrow {
      margin: 0 0 8px;
      color: var(--gold);
      font-size: 13px;
      font-weight: 900;
      letter-spacing: .12em;
      text-transform: uppercase;
    }
    h1 {
      margin: 0;
      color: #fff8d6;
      font-size: clamp(27px, 3.2vw, 42px);
      line-height: 1.15;
      letter-spacing: -.035em;
    }
    .lead { margin: 16px 0 0; color: #bbb9b3; font-size: 16px; }

    .price-box {
      margin: 20px 0;
      padding: 15px 17px;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: linear-gradient(110deg, #0b0b0c, #1c160a);
    }
    .price-box small { display: block; color: #9c9991; font-size: 12px; }
    .price { color: var(--gold); font-size: 28px; font-weight: 900; }

    .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .btn {
      min-height: 50px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 12px 18px;
      border: 1px solid #ffdb61;
      border-radius: 10px;
      color: #160b00;
      background: linear-gradient(180deg, #ffe878, #f5ad00 60%, #cf7400);
      box-shadow: 0 8px 20px rgba(255, 132, 0, .16), inset 0 1px #fff6bd;
      font-size: 14px;
      font-weight: 900;
      letter-spacing: .035em;
      text-align: center;
      transition: transform .2s ease, filter .2s ease;
    }
    .btn:hover { transform: translateY(-2px); filter: brightness(1.08); }
    .btn.secondary { color: #f6d970; border-color: #9d721e; background: #14110b; }

    .quick-facts {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 9px;
      margin: 18px 0 0;
    }
    .fact {
      padding: 12px 10px;
      border: 1px solid rgba(255, 188, 30, .16);
      border-radius: 10px;
      background: rgba(255, 255, 255, .025);
      text-align: center;
    }
    .fact strong { display: block; color: #ffe070; font-size: 15px; }
    .fact span { color: #99968f; font-size: 11px; }

    .rtp-panel {
      margin-top: 18px;
      padding: 17px;
      border: 1px solid var(--line);
      border-radius: 14px;
      background: linear-gradient(145deg, #171719, #09090a);
    }
    .rtp-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }
    .rtp-head h2 { margin: 0; color: #fff0b0; font-size: 17px; }
    .demo-badge {
      padding: 4px 8px;
      border: 1px solid rgba(255, 203, 64, .4);
      border-radius: 999px;
      color: #ffd75e;
      font-size: 10px;
      font-weight: 900;
      letter-spacing: .1em;
    }
    .rtp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }
    .rtp-item { padding: 10px; border-radius: 9px; background: #202022; }
    .rtp-label { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; font-weight: 700; }
    .rtp-label b { color: var(--gold); }
    .bar { height: 6px; margin-top: 8px; overflow: hidden; border-radius: 99px; background: #050505; }
    .bar span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #ef5c00, #ffb700, #fff29d); }
    .rtp-note { margin: 11px 0 0; color: #8f8d87; font-size: 11px; line-height: 1.5; }

    .section {
      margin-top: 28px;
      padding: clamp(24px, 4vw, 42px);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      background: linear-gradient(145deg, rgba(18, 18, 20, .98), rgba(7, 7, 8, .98));
      box-shadow: 0 14px 34px rgba(0, 0, 0, .28);
    }
    .section-kicker { margin: 0 0 5px; color: var(--orange); font-size: 12px; font-weight: 900; letter-spacing: .13em; text-transform: uppercase; }
    .section h2 { margin: 0; color: var(--gold); font-size: clamp(24px, 3vw, 34px); line-height: 1.2; }
    .section-intro { max-width: 760px; margin: 10px 0 0; color: #aaa8a2; }

    .article-content h2, .article-content h3 { margin: 26px 0 8px; color: var(--gold); font-size: clamp(20px, 2.4vw, 26px); line-height: 1.25; }
    .article-content h3 { font-size: clamp(18px, 2vw, 21px); color: #f0d77f; }
    .article-content p { margin: 0 0 14px; }
    .article-content ul, .article-content ol { margin: 0 0 14px; padding-left: 22px; }
    .article-content li { margin-bottom: 6px; }
    .article-content strong { color: #e9e6df; }
    .article-content a { color: var(--gold); text-decoration: underline; text-underline-offset: 3px; }

    .guide-grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 24px; align-items: start; }
    .guide-list { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
    .guide-list li {
      padding: 13px 15px;
      border-left: 3px solid var(--gold-2);
      border-radius: 0 9px 9px 0;
      background: rgba(255, 255, 255, .035);
      color: #d7d5d0;
    }
    .guide-list a:hover { color: var(--gold); }

    .faq { display: grid; gap: 10px; margin-top: 22px; }
    .faq details {
      overflow: hidden;
      border: 1px solid rgba(255, 188, 31, .24);
      border-radius: 10px;
      background: #111113;
    }
    .faq summary {
      cursor: pointer;
      list-style: none;
      position: relative;
      padding: 15px 48px 15px 17px;
      color: #f0d77f;
      font-weight: 800;
      line-height: 1.35;
    }
    .faq summary::-webkit-details-marker { display: none; }
    .faq summary::after {
      content: "+";
      position: absolute;
      right: 17px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gold);
      font-size: 22px;
    }
    .faq details[open] summary::after { content: "−"; }
    .faq details[open] summary { background: linear-gradient(90deg, rgba(255, 145, 0, .09), transparent); }
    .faq-answer { padding: 0 17px 17px; color: #aaa8a1; font-size: 14px; }

    .testimonials {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 15px;
      margin-top: 22px;
    }
    .testimonial {
      min-width: 0;
      padding: 19px;
      border: 1px solid rgba(255, 184, 27, .18);
      border-radius: 13px;
      background: linear-gradient(145deg, #1b1b1e, #0b0b0c);
    }
    .person { display: flex; align-items: center; gap: 11px; }
    .avatar {
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      flex: 0 0 38px;
      border-radius: 50%;
      color: #1b0d00;
      background: linear-gradient(145deg, #ffe46c, #ec9100);
      font-weight: 900;
    }
    .person h3 { margin: 0; font-size: 14px; }
    .person p { margin: 1px 0 0; color: #8d8b86; font-size: 11px; }
    blockquote { margin: 15px 0 0; color: #c5c3be; font-size: 13px; line-height: 1.65; }

    .site-footer {
      border-top: 1px solid rgba(255, 181, 24, .25);
      background: #050505;
    }
    .footer-row {
      min-height: 88px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      color: #8f8d87;
      font-size: 13px;
    }
    .footer-row strong { color: #e3bb4a; }
    .footer-links { display: flex; flex-wrap: wrap; gap: 16px; }
    .footer-links a:hover { color: var(--gold); }

    @media (max-width: 1024px) {
      .main-nav a { padding-inline: 9px; font-size: 13px; }
      .product-shell { grid-template-columns: minmax(0, 1fr) minmax(320px, .82fr); gap: 19px; padding: 19px; }
    }

    @media (max-width: 820px) {
      .topline .container { justify-content: center; text-align: center; }
      .topline span:last-child { display: none; }
      .main-nav { display: none; }
      .mobile-nav { display: block; }
      .product-shell { grid-template-columns: 1fr; }
      .media-panel { max-width: 660px; margin-inline: auto; }
      .guide-grid { grid-template-columns: 1fr; }
      .footer-row { padding: 22px 0; flex-direction: column; justify-content: center; text-align: center; }
      .footer-links { justify-content: center; }
    }

    @media (max-width: 620px) {
      .container { width: min(calc(100% - 20px), var(--max)); }
      .header-row { min-height: 70px; }
      .brand img { width: 155px; }
      main { padding-top: 20px; }
      .breadcrumbs { display: none; }
      .product-shell { padding: 12px; border-radius: 14px; }
      h1 { font-size: 28px; }
      .actions { grid-template-columns: 1fr; }
      .quick-facts { grid-template-columns: 1fr 1fr 1fr; }
      .fact { padding-inline: 5px; }
      .rtp-grid { grid-template-columns: 1fr; }
      .section { margin-top: 18px; padding: 21px 15px; }
    }

    @media (prefers-reduced-motion: reduce) {
      html { scroll-behavior: auto; }
      *, *::before, *::after { transition: none !important; }
    }
  </style>
</head>
<body>
  <div class="topline">
    <div class="container">
      <span><strong>18+</strong> · Informasi dan akses member</span>
      <span>Gunakan layanan secara bijak dan bertanggung jawab</span>
    </div>
  </div>

  <header class="site-header">
    <div class="container header-row">
      <a class="brand" href="/" aria-label="<?= h($namaSitus) ?> beranda">
        <img src="<?= h($logoSrc) ?>" width="350" height="100" alt="<?= h($namaSitus) ?>">
      </a>
      <nav class="main-nav" aria-label="Navigasi utama">
        <a href="/">Beranda</a>
<?php foreach ($menuHeader as $slug => $item): ?>
        <a href="/<?= h($slug) ?>"><?= h($labelPost($slug, $item)) ?></a>
<?php endforeach; ?>
        <a href="<?= h($ctaUrl) ?>" rel="nofollow">Login</a>
        <a class="nav-cta" href="<?= h($ctaUrl) ?>" rel="nofollow">Daftar</a>
      </nav>
      <details class="mobile-nav">
        <summary>Menu</summary>
        <nav class="mobile-links" aria-label="Navigasi seluler">
          <a href="/">Beranda</a>
<?php foreach ($allPosts as $slug => $item): ?>
          <a href="/<?= h($slug) ?>"><?= h($labelPost($slug, $item)) ?></a>
<?php endforeach; ?>
          <a href="<?= h($ctaUrl) ?>" rel="nofollow">Login</a>
          <a href="<?= h($ctaUrl) ?>" rel="nofollow">Daftar</a>
        </nav>
      </details>
    </div>
  </header>

  <div class="ticker">
    <p class="container">✦ <?= h($namaSitus) ?> · INFORMASI PLATFORM · PANDUAN MEMBER · LAYANAN ONLINE 24 JAM ✦</p>
  </div>

  <main>
    <div class="container">
      <p class="breadcrumbs"><a href="/">Beranda</a> / <?= h($remahLabel) ?></p>

      <section class="product-shell" aria-labelledby="main-title">
        <div class="media-panel">
          <div class="main-art">
<?php if (!empty($heroImg)): ?>
            <img src="<?= h($heroImg) ?>" width="600" height="700" alt="<?= h(($page['keyword'] ?? '') !== '' ? $page['keyword'] : $namaSitus) ?>" fetchpriority="high" decoding="async">
<?php endif; ?>
          </div>
        </div>

        <div class="info-panel">
          <p class="eyebrow">Panduan &amp; akses resmi</p>
          <h1 id="main-title"><?= h($page['h1'] ?? '') ?></h1>
          <p class="lead"><?= h($page['meta_description'] ?? '') ?></p>

          <div class="price-box">
            <small>Deposit mulai dari</small>
            <span class="price">Rp 10.000</span>
          </div>

          <div class="actions">
            <a class="btn" href="<?= h($ctaUrl) ?>" rel="nofollow">LOGIN</a>
            <a class="btn secondary" href="<?= h($ctaUrl) ?>" rel="nofollow">DAFTAR AKUN</a>
          </div>

          <div class="quick-facts" aria-label="Informasi singkat">
            <div class="fact"><strong>24 Jam</strong><span>Layanan online</span></div>
            <div class="fact"><strong>Mobile</strong><span>Tampilan responsif</span></div>
            <div class="fact"><strong>18+</strong><span>Khusus dewasa</span></div>
          </div>

          <section class="rtp-panel" aria-labelledby="rtp-title">
            <div class="rtp-head">
              <h2 id="rtp-title">Statistik Tampilan</h2>
              <span class="demo-badge">DEMO</span>
            </div>
            <div class="rtp-grid">
              <div class="rtp-item"><div class="rtp-label"><span>Mahjong Ways 2</span><b>96.8%</b></div><div class="bar"><span style="width:96.8%"></span></div></div>
              <div class="rtp-item"><div class="rtp-label"><span>Gates of Olympus</span><b>95.7%</b></div><div class="bar"><span style="width:95.7%"></span></div></div>
              <div class="rtp-item"><div class="rtp-label"><span>Starlight Princess</span><b>94.9%</b></div><div class="bar"><span style="width:94.9%"></span></div></div>
              <div class="rtp-item"><div class="rtp-label"><span>Sweet Bonanza</span><b>93.6%</b></div><div class="bar"><span style="width:93.6%"></span></div></div>
            </div>
            <p class="rtp-note">Angka di atas merupakan demo statis, bukan data RTP real-time dari penyedia permainan.</p>
          </section>
        </div>
      </section>

      <section class="section guide-grid" aria-labelledby="guide-title">
        <div>
          <p class="section-kicker">Informasi terbaru</p>
          <h2 id="guide-title"><?= h($judulSeksi) ?></h2>
          <div class="section-intro article-content"><?= $isiArtikel ?></div>
        </div>
<?php if (count($postLain) > 0): ?>
        <ul class="guide-list"><?php foreach ($postLain as $slug => $item): ?><li><a href="/<?= h($slug) ?>"><?= h(($item['title'] ?? '') !== '' ? $item['title'] : $labelPost($slug, $item)) ?></a></li><?php endforeach; ?><?php if (!$isHomepage): ?><li><a href="/">Kembali ke Beranda <?= h($namaSitus) ?></a></li><?php endif; ?></ul>
<?php endif; ?>
      </section>

      <section class="section" aria-labelledby="faq-title">
        <p class="section-kicker">Informasi penting</p>
        <h2 id="faq-title">Pertanyaan Seputar <?= h($namaSitus) ?></h2>
        <div class="faq">
<?php foreach ($faq as $q): ?>
          <details>
            <summary><?= h($q[0]) ?></summary>
            <div class="faq-answer"><?= h($q[1]) ?></div>
          </details>
<?php endforeach; ?>
        </div>
      </section>

      <section class="section" aria-labelledby="testimonial-title">
        <p class="section-kicker">Ulasan pengguna</p>
        <h2 id="testimonial-title">Testimoni Member</h2>
        <div class="testimonials">
          <article class="testimonial">
            <div class="person"><div class="avatar">B</div><div><h3>Bagas</h3><p>Denpasar · 07 Oktober 2026</p></div></div>
            <blockquote>“Informasinya langsung terlihat tanpa harus membuka banyak menu. Susunan kategorinya praktis, terutama saat diakses lewat smartphone.”</blockquote>
          </article>
          <article class="testimonial">
            <div class="person"><div class="avatar">R</div><div><h3>Rangga</h3><p>Bandung · 02 Oktober 2026</p></div></div>
            <blockquote>“Tampilannya modern, menu mudah ditemukan, dan halaman tetap nyaman digunakan dari layar ponsel.”</blockquote>
          </article>
          <article class="testimonial">
            <div class="person"><div class="avatar">D</div><div><h3>Dimas</h3><p>Surabaya · 03 Oktober 2026</p></div></div>
            <blockquote>“Saya lebih suka situs yang menunya tidak membingungkan. Informasi permainan di halaman ini cukup mudah dicari.”</blockquote>
          </article>
          <article class="testimonial">
            <div class="person"><div class="avatar">F</div><div><h3>Fajar</h3><p>Yogyakarta · 04 Oktober 2026</p></div></div>
            <blockquote>“Variasi temanya menarik dan informasi fiturnya membantu sebelum menentukan permainan yang ingin dilihat.”</blockquote>
          </article>
          <article class="testimonial">
            <div class="person"><div class="avatar">A</div><div><h3>Aldi</h3><p>Medan · 05 Oktober 2026</p></div></div>
            <blockquote>“Saya pribadi lebih nyaman menggunakan browser karena navigasinya ringkas dan tidak perlu memasang aplikasi tambahan.”</blockquote>
          </article>
          <article class="testimonial">
            <div class="person"><div class="avatar">N</div><div><h3>Nando</h3><p>Makassar · 06 Oktober 2026</p></div></div>
            <blockquote>“Konsep visualnya menarik. Bagian navigasi terasa sederhana dan informasi kategori tidak terlalu menumpuk.”</blockquote>
          </article>
        </div>
      </section>
    </div>
  </main>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container footer-row">
      <div><strong>© <?= date('Y') ?> <?= h($namaSitus) ?></strong> · 18+ · Gunakan secara bertanggung jawab.</div>
      <nav class="footer-links" aria-label="Navigasi footer">
<?php foreach ($allPosts as $slug => $item): ?>
        <a href="/<?= h($slug) ?>"><?= h($labelPost($slug, $item)) ?></a>
<?php endforeach; ?>
      </nav>
    </div>
  </footer>
</body>
</html>
