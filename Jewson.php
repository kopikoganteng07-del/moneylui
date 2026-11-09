<?php
/**
 * ---------------------------------------------------------
 * Template jewson (Bimasakti / Hoksiong runtime) — VERSI LENGKAP.
 *
 * Semua blok P1–P20 dari jewson_REKAP_PERUBAHAN.md DIKEMBALIKAN
 * apa adanya dari jewson.html. Variabel PHP TIDAK diganti.
 *
 * Kontrak data (sama dengan T1):
 *   per halaman : title, meta_description, h1, content_html, image_src,
 *                 canonical_url, amp_url, noindex, lang, lastmod, category, keyword
 *   homepage    : + logo_src, favicon_src, banner_src, cta_url, gsc_token (opsional)
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

$namaSitus = trim((string)($homepage['keyword'] ?? ''));
if ($namaSitus === '') { $namaSitus = explode('.', $domainName)[0]; }

$allPosts = [];
foreach ($data as $slug => $item) {
    if (is_array($item) && ($item['category'] ?? '') === 'post') { $allPosts[(string)$slug] = $item; }
}
$labelPost = function ($slug, $item) { return judulKata(($item['keyword'] ?? '') !== '' ? $item['keyword'] : str_replace('-', ' ', $slug)); };
$menuHeader = array_slice($allPosts, 0, 3, true);
$postLain   = $allPosts;
if (!$isHomepage) { unset($postLain[$id]); }

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

$faq = [
    ['Apa itu ' . $namaSitus . ' Login?', $namaSitus . ' Login merupakan platform game online yang menyediakan berbagai pilihan permainan digital populer dengan akses cepat, tampilan modern, dan sistem yang mudah digunakan.'],
    ['Mengapa banyak pengguna memilih ' . $namaSitus . '?', $namaSitus . ' menawarkan pengalaman bermain yang nyaman melalui akses stabil, navigasi sederhana, serta beragam pilihan permainan yang dapat dinikmati kapan saja.'],
    ['Apa saja kategori permainan yang tersedia di ' . $namaSitus . '?', $namaSitus . ' menyediakan berbagai kategori game online populer, termasuk permainan slot digital modern dengan fitur interaktif dan tampilan yang menarik.'],
    ['Apakah ' . $namaSitus . ' dapat diakses melalui perangkat mobile?', 'Ya, ' . $namaSitus . ' dapat diakses melalui smartphone, tablet, maupun desktop sehingga pengguna dapat menikmati pengalaman bermain yang fleksibel di berbagai perangkat.'],
    ['Apa keunggulan bermain di ' . $namaSitus . '?', $namaSitus . ' menghadirkan akses cepat, tampilan responsif, pilihan permainan yang beragam, serta pengalaman hiburan digital yang nyaman untuk berbagai kalangan pengguna.'],
];

$tags = [$namaSitus, $namaSitus . ' SLOT GACOR', $namaSitus . ' LOGIN', $namaSitus . ' DAFTAR', $namaSitus . ' GAME ONLINE', 'LINK ' . $namaSitus, 'SITUS SLOT GACOR'];

$labelKartu = ['Bonus Eksklusif Member Baru', 'Akses VIP Tanpa Batas', 'Server Stabil 24 Jam', 'RTP Tinggi Setiap Hari', 'Bonus Harian Member Aktif', 'Akses Anti Lemot Nasional', 'Link Alternatif Super Cepat'];

$infoPerusahaan = [$namaSitus . ' Entertainment Network', 'Platform Hiburan Game Online Modern', 'Pusat Layanan Digital Asia Tenggara', 'Akses Global Server Stabil 24 Jam', 'Support Online Professional & Aman', 'Teknologi Enkripsi Sistem Terbaru'];

// P15 | teks testimoni asli dari sumber (tidak ada di data.json)
$testimoniMember = [
    ['Andhika Saputra', '24 Mei 2026', $namaSitus . ' Login sangat mudah digunakan. Saya bisa mengakses berbagai permainan online dengan cepat tanpa kendala dan tampilannya juga sangat nyaman di mata.'],
    ['Rina Oktaviani', '20 Mei 2026', 'Salah satu platform game online yang paling ringan menurut saya. Proses login cepat dan semua menu tersusun rapi sehingga mudah digunakan setiap hari.'],
    ['Dewi Maharani', '17 Mei 2026', 'Saya sering bermain melalui smartphone dan pengalaman menggunakan ' . $namaSitus . ' selalu lancar. Tidak ada hambatan berarti dan navigasinya sangat responsif.'],
    ['Fajar Ramadhan', '13 Mei 2026', 'Yang saya suka dari ' . $namaSitus . ' adalah tampilannya yang modern dan akses yang stabil. Semua fitur dapat digunakan dengan baik baik di perangkat mobile maupun desktop.'],
    ['Nabila Putri', '08 Mei 2026', 'Platform ini memberikan pengalaman hiburan digital yang nyaman. Proses login cepat dan pilihan permainan yang tersedia cukup beragam untuk dinikmati kapan saja.'],
    ['Galang Pratama', '03 Mei 2026', $namaSitus . ' menjadi salah satu situs game online favorit saya karena performanya stabil dan mudah diakses. Cocok untuk pengguna yang menginginkan pengalaman bermain yang praktis.'],
];
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">

<head class="at-element-marker">
    <title><?= h($page['title'] ?? '') ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="<?= h($page['meta_description'] ?? '') ?>" />
    <meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow' ?>" />
<?php if (!empty($gscToken)): ?>
    <meta name="google-site-verification" content="<?= h($gscToken) ?>" />
<?php endif; ?>
    <link rel="shortcut icon" type="image/x-icon" media="all" href="<?= h($faviconSrc) ?>" />
    <link rel="canonical" href="<?= h($page['canonical_url'] ?? '') ?>" />

    <!-- P1 | LinkedIn + Rakuten -->
    <script type="text/javascript" async="" src="https://snap.licdn.com/li.lms-analytics/insight.min.js"></script>
    <script type="text/javascript" async="" src="https://tag.rmp.rakuten.com/124165.ct.js"></script>

<?php if ($adaAmp): ?>
    <link rel="amphtml" href="<?= h($ampUrl) ?>" />
<link rel="alternate" href="<?= h($ampUrl) ?>" />
<link rel="alternate" hreflang="id" href="<?= h($ampUrl) ?>" />
<link rel="alternate" hreflang="id-id" href="<?= h($ampUrl) ?>" />
<link rel="alternate" hreflang="x-default" href="<?= h($ampUrl) ?>" />
<link rel="alternate" hreflang="en" href="<?= h($ampUrl) ?>" />
<?php endif; ?>
    <meta property="og:type" content="<?= $isHomepage ? 'website' : 'article' ?>" />
    <meta property="og:title" content="<?= h($page['title'] ?? '') ?>" />
    <meta property="og:description" content="<?= h($page['meta_description'] ?? '') ?>" />
    <meta property="og:image" content="<?= h($ogAbs) ?>" />
    <meta property="og:url" content="<?= h($page['canonical_url'] ?? '') ?>" />
    <meta name="twitter:card" content="summary_large_image" />

    <script type="application/ld+json">
<?= j([
    '@context' => 'https://schema.org',
    '@graph' => array_values(array_filter([
        // P2 | Organization (nilai statis dari sumber; name/url/logo dari variabel)
        [
            '@type' => 'Organization',
            'address' => 'Jl. Asia Afrika No. 88, Bandung, Jawa Barat 40111',
            'url' => $origin . '/',
            'name' => $namaSitus,
            'telephone' => '08526 888888',
            'logo' => $origin . $logoSrc,
            'email' => 'gacor138@gmail.com',
        ],
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
    ])),
]) ?>

    </script>

    <!-- P3 | Adobe Typekit -->
    <link rel="preconnect" href="https://use.typekit.net" crossorigin />
    <link rel="preload" as="style" href="https://use.typekit.net/gdr0xyc.css" />
    <link rel="stylesheet" href="https://use.typekit.net/gdr0xyc.css" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="https://use.typekit.net/gdr0xyc.css" />
    </noscript>

    <!-- P4 | stylesheet core/site/print Jewson -->
    <link rel="stylesheet" type="text/css" media="all"
        href="https://www.jewson.co.uk/_ui/responsive/theme-jewson/css/core.css?version=1f4233737e50f6d7a25fd1c1a0884a0f" />
    <link rel="preload" type="text/css" media="all"
        href="https://www.jewson.co.uk/_ui/responsive/theme-jewson/css/site.css?version=1f4233737e50f6d7a25fd1c1a0884a0f"
        as="style" onload="this.onload=null;this.rel='stylesheet'" />
    <noscript>
        <link rel="stylesheet" type="text/css" media="all"
            href="https://www.jewson.co.uk/_ui/responsive/theme-jewson/css/site.css?version=1f4233737e50f6d7a25fd1c1a0884a0f" />
    </noscript>
    <link rel="stylesheet" type="text/css" media="print"
        href="https://www.jewson.co.uk/_ui/responsive/theme-jewson/css/print.css?version=1f4233737e50f6d7a25fd1c1a0884a0f" />
    <script>
        // Picture element HTML5 shiv
        document.createElement("picture");
    </script>

    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- P5 | Civic Cookie Control #ccc (27 KB) -->
    <style>
        #ccc {
            position: fixed;
            z-index: 2147483647;
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4em
        }

        @media (min-width:1024px) {
            #ccc {
                font-size: 16px
            }
        }

        #ccc-icon {
            position: fixed;
            bottom: 0;
            width: 80px;
            height: 80px;
            cursor: pointer;
            z-index: 20;
            padding: 0;
            border: 0;
            background: transparent
        }

        #ccc-icon.ccc-icon--left {
            left: 0
        }

        #ccc-icon.ccc-icon--right {
            right: 0
        }

        #ccc-icon.ccc-icon--dark {
            fill: #000025
        }

        #ccc-icon.ccc-icon--dark #star path {
            fill: #fff
        }

        #ccc-icon.ccc-icon--light {
            fill: #555
        }

        #ccc-icon.ccc-icon--light #star path {
            fill: #f4f4f4
        }

        #ccc-overlay {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1;
            background: rgba(0, 0, 0, .4);
            opacity: 1;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            perspective: 0;
            will-change: opacity;
            -webkit-animation-name: fade-in;
            animation-name: fade-in;
            -webkit-animation-duration: .4s;
            animation-duration: .4s;
            -webkit-animation-iteration-count: 1;
            animation-iteration-count: 1
        }

        #ccc-module {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: 2
        }

        #ccc-module.ccc-module--slideout {
            top: 0;
            bottom: 0;
            width: 90%;
            max-width: 520px
        }

        #ccc-module.ccc-module--slideout.ccc-module--left {
            left: 0;
            -webkit-animation-name: slide-right;
            animation-name: slide-right;
            -webkit-animation-duration: .4s;
            animation-duration: .4s;
            -webkit-animation-iteration-count: 1;
            animation-iteration-count: 1
        }

        #ccc-module.ccc-module--slideout.ccc-module--right {
            right: 0;
            -webkit-animation-name: slide-left;
            animation-name: slide-left;
            -webkit-animation-duration: .4s;
            animation-duration: .4s;
            -webkit-animation-iteration-count: 1;
            animation-iteration-count: 1
        }

        #ccc-content {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow-y: auto;
            padding: 24px;
            box-sizing: border-box
        }

        .ccc-content--dark {
            color: #fff;
            fill: #fff;
            background-color: #313147
        }

        .ccc-content--light {
            color: #333;
            fill: #333;
            background-color: #f4f4f4
        }

        #ccc-close {
            position: absolute;
            right: 16px;
            top: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            border: 0;
            background-color: transparent;
            padding: 0 !important;
            z-index: 10
        }

        #ccc-title,
        .ccc-title {
            font-size: 1.2em;
            line-height: 1.5em;
            font-weight: 700;
            padding-right: 32px;
            margin: 0;
            text-align: left
        }

        #ccc #ccc-recommended-settings {
            margin-left: 0;
            float: none;
            clear: both
        }

        #ccc h3 {
            margin: 0;
            font-weight: 700;
            padding-right: 120px
        }

        #ccc h3,
        #ccc p,
        .ccc-intro {
            font-size: 1em;
            line-height: 1.5em;
            text-align: left
        }

        #ccc p,
        .ccc-intro {
            font-weight: 400;
            margin: 1em 0
        }

        #ccc hr {
            height: 1px;
            border: 0;
            margin: 24px 0;
            opacity: .25
        }

        #ccc .ccc-svg-element {
            width: 16px;
            height: 16px;
            display: inline-block;
            position: relative;
            top: 6px;
            margin-left: 8px
        }

        #ccc .ccc-content--dark hr {
            background-color: #fff
        }

        #ccc .ccc-content--dark a,
        #ccc .ccc-content--dark h2,
        #ccc .ccc-content--dark h3,
        #ccc .ccc-content--dark p,
        #ccc .ccc-content--dark span {
            color: #fff;
            fill: #fff
        }

        #ccc .ccc-content--light hr {
            background-color: #333
        }

        #ccc .ccc-content--light a,
        #ccc .ccc-content--light h2,
        #ccc .ccc-content--light h3,
        #ccc .ccc-content--light p,
        #ccc .ccc-content--light span {
            color: #333;
            fill: #333
        }

        #ccc .optional-cookie {
            position: relative
        }

        .checkbox-toggle {
            position: absolute;
            top: -3px;
            right: 0;
            margin: 0
        }

        #ccc .checkbox-toggle-input {
            margin: 0;
            padding: 0;
            display: block;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0
        }

        #ccc .checkbox-toggle--checkbox {
            border: 2px solid #fff;
            border-radius: 4px
        }

        #ccc .checkbox-toggle--checkbox .checkbox-toggle-label {
            display: block;
            width: 24px;
            height: 24px
        }

        #ccc .checkbox-toggle--checkbox .checkbox-toggle-off,
        #ccc .checkbox-toggle--checkbox .checkbox-toggle-on {
            display: none
        }

        #ccc .checkbox-toggle--checkbox .checkbox-toggle-toggle {
            position: relative;
            display: block;
            width: 100%;
            height: 100%;
            border-radius: 4px
        }

        #ccc .checkbox-toggle--checkbox input:checked~.checkbox-toggle-toggle:after {
            content: "";
            position: absolute;
            left: 9px;
            top: 5px;
            width: 5px;
            height: 10px;
            border: solid #fff;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg)
        }

        #ccc .checkbox-toggle--slider {
            border-radius: 50px;
            border: 4px solid #fff
        }

        #ccc .checkbox-toggle--slider .checkbox-toggle-label {
            display: flex;
            margin: 0;
            cursor: pointer;
            z-index: 1
        }

        #ccc .checkbox-toggle--slider .checkbox-toggle-off,
        #ccc .checkbox-toggle--slider .checkbox-toggle-on {
            flex: 1;
            flex-basis: 50%;
            width: 50%;
            min-width: 40px;
            text-align: center;
            z-index: 2;
            font-size: .75em;
            font-weight: 700;
            padding: 0 6px;
            white-space: nowrap
        }

        #ccc .checkbox-toggle--slider .checkbox-toggle-off {
            opacity: .65
        }

        #ccc .checkbox-toggle--slider .checkbox-toggle-toggle {
            display: block;
            width: 50%;
            height: 100%;
            cursor: pointer;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 3;
            border-radius: 50px;
            transition: all .4s ease;
            background-color: #000
        }

        #ccc .checkbox-toggle--slider input:checked~.checkbox-toggle-toggle {
            left: 50%
        }

        #ccc .checkbox-toggle--slider.checkbox-toggle--dark {
            background-color: #111125;
            border-color: #111125
        }

        #ccc .checkbox-toggle--checkbox.checkbox-toggle--dark {
            background-color: #111125;
            border-color: #fff
        }

        #ccc .checkbox-toggle--dark .checkbox-toggle-toggle {
            background-color: #2f2f5f
        }

        #ccc .checkbox-toggle--light {
            background-color: #555;
            border-color: #555
        }

        #ccc .checkbox-toggle--light .checkbox-toggle-toggle {
            background-color: #000
        }

        #ccc .checkbox-toggle--light .checkbox-toggle-on,
        #ccc .checkbox-toggle--slider.checkbox-toggle--light .checkbox-toggle-off {
            color: #fff
        }

        #ccc .ccc-alert {
            border-radius: 8px;
            margin: 24px 0;
            padding: 16px
        }

        #ccc .ccc-alert-title {
            display: flex;
            align-items: center;
            padding: 0
        }

        #ccc .ccc-alert-icon {
            display: inline-block;
            width: 30px;
            height: 30px;
            margin: 0 8px 0 0;
            flex-shrink: 0
        }

        #ccc .ccc-alert-break {
            margin: 12px 0
        }

        .third-party-cookie-link {
            display: flex;
            align-items: center;
            margin: 16px 0
        }

        .third-party-cookie-name {
            font-size: 1em;
            font-weight: 700
        }

        .third-party-cookie-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-left: 16px
        }

        #ccc .ccc-content--dark .ccc-alert {
            color: #fff;
            fill: #fff;
            background: #111125
        }

        #ccc .ccc-content--dark .third-party-cookie-link {
            color: #fff;
            background: #111125
        }

        #ccc .ccc-content--light .ccc-alert,
        #ccc .ccc-content--light .third-party-cookie-link {
            background: #eaeaea
        }

        #ccc .ccc-module--popup {
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            opacity: 1;
            -webkit-animation-name: slide-up;
            animation-name: slide-up;
            -webkit-animation-duration: .4s;
            animation-duration: .4s;
            -webkit-animation-iteration-count: 1;
            animation-iteration-count: 1
        }

        #ccc .ccc-module--popup #ccc-content {
            width: 90%;
            height: 90%;
            max-height: 600px;
            max-width: 600px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 12px
        }

        #ccc .ccc-notify-box-dismiss {
            display: none !important
        }

        @media (min-width:600px) {
            #ccc #ccc-notify.ccc-notify__box {
                opacity: 1;
                -webkit-animation-name: box-slide-up;
                animation-name: box-slide-up;
                -webkit-animation-duration: .8s;
                animation-duration: .8s;
                -webkit-animation-iteration-count: 1;
                animation-iteration-count: 1;
                width: 90%;
                height: 90%;
                max-height: 240px;
                max-width: 480px;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                border-radius: 12px;
                flex-direction: column;
                justify-content: space-around;
                box-sizing: border-box;
                padding: 32px 64px
            }

            #ccc .ccc-notify-box-dismiss {
                display: block !important;
                position: absolute !important;
                top: 16px !important;
                right: 16px !important
            }
        }

        #ccc #ccc-notify {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: auto;
            min-height: 48px;
            padding: 16px;
            margin: 0;
            border-radius: 0;
            overflow: hidden;
            z-index: 30;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            opacity: 1;
            -webkit-animation-name: slide-up;
            animation-name: slide-up;
            -webkit-animation-iteration-count: 1;
            animation-iteration-count: 1
        }

        #ccc #ccc-notify,
        #ccc #ccc-notify.ccc-notify__top {
            -webkit-animation-duration: .4s;
            animation-duration: .4s
        }

        #ccc #ccc-notify.ccc-notify__top {
            top: 0;
            bottom: auto;
            -webkit-animation-name: slide-down;
            animation-name: slide-down;
            -webkit-animation-iteration-count: 1;
            animation-iteration-count: 1
        }

        #ccc #ccc-notify h3 {
            padding: 0
        }

        #ccc #ccc-notify p {
            margin: 0;
            opacity: .8;
            font-size: .9em
        }

        #ccc .ccc-notify-buttons {
            position: relative;
            margin-top: 16px;
            padding-right: 24px
        }

        #ccc .ccc-notify-button {
            border: 2px solid;
            border-radius: 4px;
            padding: 8px 16px !important;
            font-size: 1em;
            margin: 4px 8px 4px 0;
            font-weight: 400;
            line-height: 1.5em;
            background: transparent;
            cursor: pointer
        }

        #ccc #ccc-notify-dismiss {
            cursor: pointer;
            position: absolute;
            top: -4px;
            right: 0;
            width: 24px;
            height: 24px;
            display: inline-flex;
            padding: 0 !important;
            border: 0;
            background-color: transparent
        }

        @media (min-width:400px) {
            #ccc #ccc-notify-dismiss {
                position: relative;
                right: auto
            }

            #ccc .ccc-notify-buttons {
                padding-right: 0
            }
        }

        @media (min-width:600px) {
            #ccc #ccc-notify {
                font-size: 12px;
                flex-direction: row;
                align-items: center
            }

            #ccc .ccc-notify-buttons {
                margin-top: 0;
                white-space: nowrap;
                flex-shrink: 0
            }
        }

        @media (min-width:1200px) {
            #ccc #ccc-notify {
                z-index: 10;
                font-size: 16px;
                padding: 16px 100px
            }
        }

        #ccc .ccc-content--dark #ccc-notify,
        #ccc .ccc-content--dark #ccc-notify span {
            color: #fff;
            fill: #fff;
            background-color: #313147
        }

        #ccc .ccc-content--dark .ccc-notify-button,
        #ccc .ccc-content--dark .ccc-notify-button span {
            color: #fff;
            border-color: #fff
        }

        #ccc .ccc-content--dark .ccc-button-solid,
        #ccc .ccc-content--dark .ccc-button-solid span {
            color: #111125;
            background: #fff;
            font-weight: 700
        }

        .ccc-notify-link {
            border: 0 !important;
            text-decoration: underline
        }

        #ccc .ccc-content--light #ccc-notify,
        #ccc .ccc-content--light #ccc-notify span {
            color: #000;
            fill: #000;
            background: #f4f4f4
        }

        #ccc .ccc-content--light .ccc-notify-button,
        #ccc .ccc-content--light .ccc-notify-button span {
            color: #000;
            border-color: #000
        }

        #ccc .ccc-content--light .ccc-button-solid,
        #ccc .ccc-content--light .ccc-button-solid span {
            color: #fff;
            background: #000;
            font-weight: 700
        }

        .ccc-info-link {
            font-size: .8em;
            font-weight: 700;
            text-decoration: none
        }

        .ccc-info-link .svg-element {
            width: 14px;
            height: 14px;
            margin-left: 6px
        }

        #ccc .ccc-module--slideout .ccc-info {
            height: 100px
        }

        #ccc[popup] .ccc-info {
            height: 40px
        }

        @media print {
            #ccc {
                display: none !important
            }
        }

        #ccc-close-label {
            display: block;
            font-size: 16px
        }

        #ccc .ccc-panel--indent #ccc-title {
            padding-right: 180px
        }

        #ccc-close.ccc-close--labelled {
            top: 0;
            right: 0;
            padding: 8px !important
        }

        #ccc-content.ccc-content--light #ccc-close.ccc-close--labelled {
            background-color: #eaeaea
        }

        #ccc-content.ccc-content--dark #ccc-close.ccc-close--labelled {
            background-color: #111125
        }

        #ccc-end {
            margin: 16px 0 32px
        }

        #ccc-dismiss-button {
            display: block
        }

        #ccc .ccc-content--highlight .ccc-link.ccc-tabbable:focus {
            padding: 8px;
            filter: invert(100%)
        }

        #ccc .ccc-content--highlight.ccc-content--light .ccc-link.ccc-tabbable:focus {
            border-color: #eaeaea !important;
            background-color: #eaeaea !important
        }

        #ccc .ccc-content--highlight.ccc-content--dark .ccc-link.ccc-tabbable:focus {
            border-color: #111125 !important;
            background-color: #111125 !important
        }

        #ccc .ccc-content--highlight #ccc-close:focus,
        #ccc .ccc-content--highlight .ccc-button-solid:focus,
        #ccc .ccc-content--highlight .checkbox-toggle-input:focus~.checkbox-toggle-toggle,
        #ccc .ccc-content--highlight .third-party-cookie-link:focus {
            filter: invert(100%)
        }

        #ccc .ccc-content--highlight.ccc-content--light .ccc-tabbable:focus {
            box-shadow: 0 4px 8px 0 hsla(0, 0%, 100%, .2), 0 6px 20px 0 hsla(0, 0%, 100%, .2)
        }

        #ccc .ccc-content--highlight.ccc-content--dark .ccc-tabbable:focus {
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, .2), 0 6px 20px 0 rgba(0, 0, 0, .2)
        }

        #ccc .checkbox-toggle:focus-within {
            outline: auto
        }

        #ccc #ccc-icon.ccc-icon--no-outline,
        #ccc .ccc-content--no-outline *,
        #ccc .ccc-content--no-outline .ccc-tabbable {
            outline: none
        }

        #ccc .ccc-content--highlight .ccc-tabbable:active,
        #ccc .ccc-content--highlight .ccc-tabbable:hover,
        #ccc .ccc-icon--highlight:active,
        #ccc .ccc-icon--highlight:hover {
            box-shadow: none !important;
            filter: none !important
        }

        .ccc-panel {
            position: absolute;
            top: 24px;
            left: 24px;
            right: 24px
        }

        .ccc-panel.hidden {
            opacity: 0;
            height: 0;
            overflow: hidden
        }

        .ccc-panel.visible {
            opacity: 1;
            height: auto;
            transition: opacity .4s
        }

        #ccc .iab-vendor,
        #ccc .optional-vendor {
            margin: 0 0 8px
        }

        #ccc .optional-vendor {
            font-size: .875em
        }

        #ccc .optional-vendor-heading {
            font-weight: 700;
            padding-left: 24px;
            text-decoration: underline
        }

        #ccc .ccc-vendor-reveal {
            background: transparent;
            display: flex;
            align-items: center;
            border: 1px dotted;
            font-size: .875em;
            opacity: .5;
            transition: opacity .4s
        }

        #ccc .ccc-vendor-reveal:focus,
        #ccc .ccc-vendor-reveal:hover {
            opacity: 1
        }

        #ccc .ccc-legal-description {
            margin-top: 1rem
        }

        #ccc .ccc-vendor-reveal svg {
            width: 16px;
            height: 16px;
            margin-left: 8px
        }

        #ccc .optional-vendor-warning {
            display: flex;
            align-items: center
        }

        #ccc .optional-vendor-warning svg {
            width: 16px;
            height: 16px;
            margin-right: 8px
        }

        #ccc .optional-vendor-content {
            padding-left: 24px
        }

        #ccc .iab-vendor-heading {
            font-size: .875em;
            font-weight: 400;
            text-decoration: underline
        }

        #ccc .cmp-button-holder {
            font-size: .75em;
            display: flex;
            justify-content: space-between
        }

        #ccc .cmp-back {
            opacity: .5;
            transition: opacity .4s
        }

        #ccc .cmp-back:hover {
            opacity: 1
        }

        .ccc-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0
        }

        @-webkit-keyframes fade-in {
            0% {
                opacity: 0
            }

            to {
                opacity: 1
            }
        }

        @keyframes fade-in {
            0% {
                opacity: 0
            }

            to {
                opacity: 1
            }
        }

        @-webkit-keyframes slide-right {
            0% {
                transform: translate3d(-100%, 0, 0)
            }

            to {
                transform: translateZ(0)
            }
        }

        @keyframes slide-right {
            0% {
                transform: translate3d(-100%, 0, 0)
            }

            to {
                transform: translateZ(0)
            }
        }

        @-webkit-keyframes slide-left {
            0% {
                transform: translate3d(100%, 0, 0)
            }

            to {
                transform: translateZ(0)
            }
        }

        @keyframes slide-left {
            0% {
                transform: translate3d(100%, 0, 0)
            }

            to {
                transform: translateZ(0)
            }
        }

        @-webkit-keyframes slide-up {
            0% {
                transform: translate3d(0, 100%, 0)
            }

            to {
                transform: translateZ(0)
            }
        }

        @keyframes slide-up {
            0% {
                transform: translate3d(0, 100%, 0)
            }

            to {
                transform: translateZ(0)
            }
        }

        @-webkit-keyframes box-slide-up {
            0% {
                transform: translate3d(-50%, 100%, 0)
            }

            to {
                transform: translate3d(-50%, -50%, 0)
            }
        }

        @keyframes box-slide-up {
            0% {
                transform: translate3d(-50%, 100%, 0)
            }

            to {
                transform: translate3d(-50%, -50%, 0)
            }
        }

        @-webkit-keyframes slide-down {
            0% {
                transform: translate3d(0, -100%, 0)
            }

            to {
                transform: translateZ(0)
            }
        }

        @keyframes slide-down {
            0% {
                transform: translate3d(0, -100%, 0)
            }

            to {
                transform: translateZ(0)
            }
        }

        #ccc .iab-object {
            position: relative;
            height: 48px;
            width: 98%
        }

        #ccc .iab-object.iab-object--vendor {
            width: 85%
        }

        #ccc .iab-object .checkbox-toggle {
            top: 4px;
            left: 0;
            right: auto
        }

        #ccc .iab-object .checkbox-toggle-label {
            width: 18px;
            height: 18px
        }

        #ccc .iab-object .checkbox-toggle--checkbox input:checked~.checkbox-toggle-toggle:after {
            left: 7px;
            top: 3px;
            width: 3px;
            height: 8px;
            border-width: 0 2px 2px 0
        }

        #ccc .iab-object-label {
            display: block;
            margin-left: 32px;
            font-size: .75em
        }

        #ccc .vendor-details {
            font-size: .75rem;
            margin-bottom: 16px
        }

        #ccc .vendor-details h4 {
            margin: .5rem 0
        }

        #ccc .ccc-title.ccc-title--iab {
            margin-bottom: 1rem
        }

        #ccc .iab-header-toggle {
            display: flex;
            align-items: center;
            margin-bottom: 1rem
        }

        #ccc .iab-header-toggle .ccc-title {
            margin: 0 0 0 16px
        }

        #ccc .iab-header-toggle .ccc-vendor-reveal svg {
            margin: 0
        }

        #ccc .iab-vendor-toggle .checkbox-toggle {
            top: auto
        }

        #ccc .iab-vendor-toggle h3 {
            padding-right: 180px
        }

        #ccc .iab-disclosure-load {
            display: flex;
            align-items: center
        }

        #ccc .iab-disclosure-load button {
            margin-right: 12px
        }

        #ccc .iab-disclosure-load a {
            text-decoration: none
        }

        #ccc .iab-disclosure-load a>span {
            position: relative;
            top: -2px
        }

        #ccc .iab-disclosure-load a>.ccc-svg-element {
            top: 4px
        }

        #ccc .iab-disclosure-table {
            width: 100%;
            margin: 2em 0;
            border-collapse: collapse
        }

        #ccc .iab-disclosure-table td,
        #ccc .iab-disclosure-table th {
            border: 1px solid
        }

        #ccc .iab-disclosure-table thead td {
            border: none
        }

        #ccc .ccc-content--dark td,
        #ccc .ccc-content--dark th {
            border-color: #fff
        }

        #ccc .iab-disclosure-table dl {
            display: grid;
            grid-template: auto/1fr 1fr;
            width: 150px
        }

        #ccc .iab-disclosure-table ul {
            list-style: none
        }

        #ccc .iab-disclosure-table dl,
        #ccc .iab-disclosure-table ul {
            margin: 0;
            padding: 0
        }

        #ccc .iab-disclosure-table dd,
        #ccc .iab-disclosure-table dt,
        #ccc .iab-disclosure-table li {
            margin: 0;
            padding: .3em .5em;
            border-bottom: 1px solid rgba(0, 0, 0, .1)
        }

        #ccc .ccc-content--dark .iab-disclosure-table dd,
        #ccc .ccc-content--dark .iab-disclosure-table dt,
        #ccc .ccc-content--dark .iab-disclosure-table li {
            border-bottom: 1px solid hsla(0, 0%, 100%, .1)
        }

        #ccc .ccc-content--dark .iab-disclosure-table li:last-child {
            border: none
        }

        #ccc .iab-disclosure-table td,
        #ccc .iab-disclosure-table th {
            vertical-align: top;
            padding: 1em
        }

        #ccc .iab-disclosure-table tr:nth-child(2n) {
            background: rgba(0, 0, 0, .1)
        }
    </style>

    <!-- P6 | Twitter + FB Pixel + Adobe + Google Ads + Hotjar -->
    <script src="https://static.ads-twitter.com/uwt.js" async=""></script>
    <script
        src="https://connect.facebook.net/signals/config/556364111565678?v=2.9.256&amp;r=stable&amp;domain=www.jewson.co.uk&amp;hme=18f1088ef3e8892a74f943ddaee05e3bbd28c980d3b65c4c57d9fe86969d4bce&amp;ex_m=95%2C158%2C135%2C20%2C67%2C68%2C128%2C63%2C42%2C129%2C72%2C62%2C10%2C142%2C81%2C15%2C94%2C123%2C116%2C70%2C73%2C122%2C139%2C103%2C144%2C7%2C3%2C4%2C6%2C5%2C2%2C82%2C92%2C145%2C226%2C170%2C56%2C228%2C229%2C49%2C185%2C27%2C69%2C234%2C233%2C173%2C29%2C55%2C9%2C58%2C88%2C89%2C90%2C96%2C119%2C28%2C26%2C121%2C118%2C117%2C136%2C71%2C138%2C137%2C44%2C54%2C112%2C14%2C141%2C39%2C215%2C217%2C180%2C23%2C24%2C25%2C17%2C18%2C38%2C34%2C36%2C35%2C77%2C83%2C87%2C101%2C127%2C130%2C40%2C102%2C21%2C19%2C108%2C64%2C32%2C132%2C131%2C133%2C124%2C22%2C31%2C53%2C100%2C140%2C65%2C16%2C134%2C105%2C76%2C61%2C30%2C195%2C165%2C285%2C213%2C156%2C198%2C191%2C166%2C98%2C120%2C75%2C110%2C48%2C41%2C43%2C104%2C109%2C115%2C52%2C59%2C114%2C47%2C50%2C46%2C91%2C143%2C0%2C113%2C13%2C111%2C11%2C1%2C51%2C84%2C57%2C60%2C107%2C80%2C79%2C146%2C147%2C85%2C86%2C8%2C93%2C45%2C125%2C78%2C74%2C66%2C106%2C97%2C37%2C126%2C33%2C99%2C12%2C148"
        async=""></script>
    <script src="https://connect.facebook.net/en_US/fbevents.js" async=""></script>
    <script src="https://assets.adobedtm.com/extensions/EP31dbb9c60e404ba1aa6e746d49be6f29/AppMeasurement.min.js"
        async=""></script>
    <script
        src="https://assets.adobedtm.com/extensions/EP31dbb9c60e404ba1aa6e746d49be6f29/AppMeasurement_Module_ActivityMap.min.js"
        async=""></script>
    <script
        src="https://assets.adobedtm.com/extensions/EP31dbb9c60e404ba1aa6e746d49be6f29/AppMeasurement_Module_AudienceManagement.min.js"
        async=""></script>
    <script
        src="https://assets.adobedtm.com/f177fcea2274/0ec023f9574e/e92a583b7661/RC12ce32e9f66640e2b76cc120cbedf80e-source.min.js"
        async=""></script>
    <script src="https://www.googletagmanager.com/gtag/js?id=AW-758687637&amp;l=dataLayer" async=""></script>
    <script
        src="https://assets.adobedtm.com/f177fcea2274/0ec023f9574e/e92a583b7661/RCc56a441bf32c495a955b1cc3d1f924e4-source.min.js"
        async=""></script>
    <script
        src="https://assets.adobedtm.com/f177fcea2274/0ec023f9574e/e92a583b7661/RCbff5920b0b484108a0d62f8e46f3122f-source.min.js"
        async=""></script>
    <script
        src="https://assets.adobedtm.com/f177fcea2274/0ec023f9574e/e92a583b7661/RC67855ee2c298479096687632678a397b-source.min.js"
        async=""></script>
    <script type="text/javascript" async=""
        src="https://googleads.g.doubleclick.net/pagead/viewthroughconversion/758687637/?random=1770194987916&amp;cv=11&amp;fst=1770194987916&amp;bg=ffffff&amp;guid=ON&amp;async=1&amp;en=page_view&amp;gtm=45be6221v9118069527za200zd9118069527xec&amp;gcd=13l3l3l3l1l1&amp;dma=0&amp;tag_exp=103116026~103200004~104527907~104528501~115616986~115938465~115938468~116185181~116185182~116988316~117041587&amp;u_w=1920&amp;u_h=1080&amp;url=https%3A%2F%2Fwww.jewson.co.uk%2Fp%2Fcaple-7030-frost-free-in-column-fridge-freezer-b4tm4n&amp;ref=https%3A%2F%2Fwww.jewson.co.uk%2Fp%2Fcaple-7030-frost-free-in-column-fridge-freezer-b4tm4n&amp;frm=0&amp;tiba=Caple%2070%2F30%20Frost%20Free%20In-Column%20Fridge%20Freezer&amp;hn=www.googleadservices.com&amp;npa=0&amp;pscdl=noapi&amp;auid=800775795.1770194988&amp;uaa=x86&amp;uab=64&amp;uafvl=Not(A%253ABrand%3B8.0.0.0%7CChromium%3B144.0.7559.110%7CGoogle%2520Chrome%3B144.0.7559.110&amp;uamb=0&amp;uam=&amp;uap=Windows&amp;uapv=19.0.0&amp;uaw=0&amp;data=event%3Dpage_view&amp;rfmt=3&amp;fmt=4">
    </script>
    <script async="" src="https://static.hotjar.com/c/hotjar-4965657.js?sv=6"></script>
    <script async="" src="https://script.hotjar.com/modules.ddd41caee2adfc4aedb8.js" charset="utf-8"></script>

<?php if ($isHomepage): ?>
    <script type="application/ld+json">
<?= j(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(function ($q) {
    return ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]];
}, $faq)]) ?>

    </script>
<?php endif; ?>

    <!-- P7 | pengulangan tracker -->
    <script src="https://static.ads-twitter.com/uwt.js" async=""></script>
    <script src="https://connect.facebook.net/en_US/fbevents.js" async=""></script>
    <script src="https://assets.adobedtm.com/extensions/EP31dbb9c60e404ba1aa6e746d49be6f29/AppMeasurement.min.js"
        async=""></script>
    <script
        src="https://assets.adobedtm.com/extensions/EP31dbb9c60e404ba1aa6e746d49be6f29/AppMeasurement_Module_ActivityMap.min.js"
        async=""></script>
    <script
        src="https://assets.adobedtm.com/extensions/EP31dbb9c60e404ba1aa6e746d49be6f29/AppMeasurement_Module_AudienceManagement.min.js"
        async=""></script>
    <script src="https://www.googletagmanager.com/gtag/js?id=&amp;l=dataLayer" async=""></script>
    <script
        src="https://assets.adobedtm.com/f177fcea2274/0ec023f9574e/e92a583b7661/RC67855ee2c298479096687632678a397b-source.min.js"
        async=""></script>
    <script src="https://static.ads-twitter.com/uwt.js" async=""></script>
    <script src="https://connect.facebook.net/en_US/fbevents.js" async=""></script>
    <script src="https://assets.adobedtm.com/extensions/EP31dbb9c60e404ba1aa6e746d49be6f29/AppMeasurement.min.js"
        async=""></script>
    <script
        src="https://assets.adobedtm.com/extensions/EP31dbb9c60e404ba1aa6e746d49be6f29/AppMeasurement_Module_ActivityMap.min.js"
        async=""></script>
    <script
        src="https://assets.adobedtm.com/extensions/EP31dbb9c60e404ba1aa6e746d49be6f29/AppMeasurement_Module_AudienceManagement.min.js"
        async=""></script>
    <script src="https://www.googletagmanager.com/gtag/js?id=&amp;l=dataLayer" async=""></script>
    <script
        src="https://assets.adobedtm.com/f177fcea2274/0ec023f9574e/e92a583b7661/RC67855ee2c298479096687632678a397b-source.min.js"
        async=""></script>
</head>



<body
    class="page-productDetails pageType-ProductPage template-pages-product-productLayout2Page  language-<?= h($lang) ?>">
    <header class="header-alt js-header header-alt--sticky js-sticky-header" data-tracking-block="header">
        <style>
            .header-alt__top-bar {
                background: black;
            }
        </style>
        <section class="header-alt__top-bar d-none d-lg-block">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="d-lg-flex justify-content-center">
                            <div class="yCmsComponent header-alt__top-bar-link ">
                                <a href="/" title="Service Kami"><span
                                        class="header-alt__top-bar-link-icon glyph glyph--build-filled glyph--light"></span><span
                                        class="header-alt__top-bar-link-text">Service Kami</span></a></div>
                            <div class="yCmsComponent header-alt__top-bar-link ">
                                <a href="/"
                                    title="Simulator Projek"><span
                                        class="header-alt__top-bar-link-icon glyph glyph--calculator glyph--light"></span><span
                                        class="header-alt__top-bar-link-text">Simulator Projek</span></a></div>
                            <div class="yCmsComponent header-alt__top-bar-link ">
                                <a href="/" title="Butuh Bantuan?"><span
                                        class="header-alt__top-bar-link-icon glyph glyph--headset glyph--light"></span><span
                                        class="header-alt__top-bar-link-text">Butuh Bantuan?</span></a></div>
                        </div>
                        <!-- P8 | Toggle VAT desktop -->
                        <div class="toggle-alt">
                            <form id="command" action="/_s/vat" method="post"><label for="toggle-alt_vat"
                                    class="toggle-alt__label" tabindex="0">
                                    <input type="checkbox" class="toggle-alt__input js-submit-on-toggle"
                                        id="toggle-alt_vat" name="showVatPrices" value="true" />
                                    <div class="toggle-alt__ui">
                                        <span class="toggle-alt__vat">VAT</span>
                                        <span class="toggle-alt__ex">EX</span>
                                        <div class="toggle-alt__track">
                                            <span class="toggle-alt__handler"></span>
                                        </div>
                                        <span class="toggle-alt__inc">INC</span>
                                    </div>
                                </label>
                                <div class="form-group form-group--cta hide">
                                    <button type="submit" class="btn btn-primary">Toggle</button>
                                </div>
                                <div>
                                    <input type="hidden" name="CSRFToken"
                                        value="9352966a-0293-482c-9e24-b9ac73a5ad5c" />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="header-alt__middle">
            <div class="container">
                <div class="row">
                    <div class="col-12">

                        <div class="header-alt__components">
                            <div class="d-flex justify-content-between align-items-center">

                                <div class="header-alt__logo order-1" data-tracking-position="logo">
                                    <a href="/" class="d-block mr-lg-1">
                                        <img class="header-alt__logo-img"
                                            src="<?= h($logoSrc) ?>"
                                            alt="<?= h($namaSitus) ?> Logo" title="<?= h($namaSitus) ?> Logo" />
                                    </a></div>

                                <div class="header-alt__search order-2" data-tracking-position="search">
                                    <div class="search">
                                        <div class="form-group form-group--search m-0">
                                            <div class="sr-only">
                                                <label for="siteSearchMirror" class="sr-only">Search</label>
                                            </div>
                                            <div class="form-group__element">
                                                <input type="text" role="search" class="form-control js-trigger-search"
                                                    id="siteSearchMirror" name="text" value="" />
                                            </div>
                                            <div class="search__form-cta form-group form-group--cta">
                                                <button type="submit"
                                                    class="btn btn-search glyph glyph--search">Search</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="header-alt__widgets order-3 d-lg-flex">
                                    <div class="header-alt__quick-bar d-flex order-lg-4">
                                        <div class="header-alt__quick-bar-icon d-none d-lg-block">
                                        </div>

                                        <div class="header-alt__quick-bar-icon d-none d-lg-block">
                                        </div>

                                        <!-- P9 | Panel Set your location + Temukan Kami -->
                                        <div inert="" class="offsite-panel offsite-panel--full js-ld-wrapper"
                                            data-panel-id="location-picker-location"
                                            data-panel-name="location picker location" data-drawer-type="location">
                                            <div class="offsite-panel__content">
                                                <div class="offsite-panel__toolbar">
                                                    <div class="offsite-panel__close js-toggle-panel">
                                                        <button
                                                            class="offsite-panel__close-icon glyph glyph--cross glyph--light glyph--small"></button>
                                                        <span>Close</span>
                                                    </div>
                                                </div>

                                                <div class="offsite-panel__body">
                                                    <div class="js-branch-form ">
                                                        <h2>Set your location</h2>
                                                        <p>Get local prices and products that are more readily available
                                                            in your area.</p>

                                                        <form id="modal-location-form" name="sessionLocationForm"
                                                            novalidate="true" data-form-id="sessionLocationForm"
                                                            class="js-form-validate" action="/_s/location" method="post"
                                                            data-np-autofill-form-type="identity" data-np-checked="1"
                                                            data-np-watching="1">
                                                            <div
                                                                class="form-group form-group--search-group form-group__postcode">
                                                                <div class="form-group__label">
                                                                    <label for="locationQuery-location">
                                                                    </label>
                                                                </div>
                                                                <div class="d-flex">
                                                                    <div class="form-group__validation mr-0">
                                                                        <div class="form-group__element">
                                                                            <input id="locationQuery-location"
                                                                                name="postcode" required="true"
                                                                                class="form-control" type="text"
                                                                                placeholder="Postcode"
                                                                                data-validation-for="postcode"
                                                                                data-np-checked="1"
                                                                                data-np-autofill-field-type="zip"
                                                                                data-np-uid="300c9c47-ac8f-4290-8894-bfe06dd2f83d" />
                                                                            <nordpass-icon
                                                                                data-np-uid="300c9c47-ac8f-4290-8894-bfe06dd2f83d"
                                                                                tabindex="-1"
                                                                                style="all: initial !important;">
                                                                            </nordpass-icon>
                                                                        </div>
                                                                    </div>
                                                                    <input type="hidden" name="geolocationPostcode"
                                                                        value="false" />
                                                                    <button type="button"
                                                                        class="btn btn-tertiary btn--branch-search-location w-auto m-0 ml-2 glyph glyph--current-location js-geolocate"
                                                                        data-interaction="Use Lokasi Saya">
                                                                        <span class="sr-only">
                                                                            Use Lokasi Saya</span>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="form-group form-group__fulfilment">
                                                                <fieldset>
                                                                    <legend class="form-group__fulfilment-legend">
                                                                        I want: (optional)</legend>
                                                                    <div class="d-flex flex-wrap">
                                                                        <div class="radio mr-2">
                                                                            <input type="radio"
                                                                                class="radio__input sr-only"
                                                                                id="drawer-location-delivery"
                                                                                name="delivery" value="true" checked
                                                                                data-validation-for="radioGroup"
                                                                                required="" />
                                                                            <label class="radio__label"
                                                                                for="drawer-location-delivery">
                                                                                Delivery</label>
                                                                        </div>
                                                                        <div class="radio">
                                                                            <input type="radio"
                                                                                class="radio__input sr-only"
                                                                                id="drawer-location-collection"
                                                                                name="delivery" value="false"
                                                                                data-validation-for="radioGroup"
                                                                                required="" />
                                                                            <label class="radio__label"
                                                                                for="drawer-location-collection">
                                                                                Click &amp; Collect</label>
                                                                        </div>
                                                                    </div>
                                                                </fieldset>
                                                            </div>
                                                            <input type="hidden" name="changeFulfilmentType"
                                                                value="true" />
                                                            <div class="form-group form-group--cta">
                                                                <button type="submit"
                                                                    class="btn btn-primary js-location-cta"
                                                                    data-interaction="Enter">
                                                                    Enter</button>
                                                            </div>
                                                            <div>
                                                                <input type="hidden" name="CSRFToken"
                                                                    value="9352966a-0293-482c-9e24-b9ac73a5ad5c" />
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <div class="offsite-panel__results js-branch-list">
                                                    </div>
                                                </div>

                                                <div class="offsite-panel__footer js-drawer-cookies ">
                                                    <hr class="hr" />
                                                    <div>Don&#39;t worry, we won&#39;t share your location! Read more
                                                        about how we use <a
                                                            href="/">cookies</a>.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div inert="" class="offsite-panel offsite-panel--full js-ld-wrapper"
                                            data-panel-id="location-picker-branch"
                                            data-panel-name="location picker branch" data-drawer-type="branch">
                                            <div class="offsite-panel__content">
                                                <div class="offsite-panel__toolbar">
                                                    <div class="offsite-panel__close js-toggle-panel">
                                                        <button
                                                            class="offsite-panel__close-icon glyph glyph--cross glyph--light glyph--small"></button>
                                                        <span>Close</span>
                                                    </div>
                                                </div>

                                                <div class="offsite-panel__body">
                                                    <div class="js-branch-form ">
                                                        <h2>Temukan Kami</h2>
                                                        <p>Enter your postcode to find branches near you.</p>

                                                        <form id="modal-branch-form" name="sessionLocationForm"
                                                            novalidate="true" data-form-id="sessionLocationForm"
                                                            class="js-form-validate"
                                                            action="/data"
                                                            method="post">
                                                            <div
                                                                class="form-group form-group--search-group form-group__postcode">
                                                                <div class="form-group__label">
                                                                    <label for="locationQuery-branch">
                                                                    </label>
                                                                </div>
                                                                <div class="d-flex">
                                                                    <div class="form-group__validation mr-0">
                                                                        <div class="form-group__element">
                                                                            <input id="locationQuery-branch"
                                                                                name="postcode" required="true"
                                                                                class="form-control" type="text"
                                                                                placeholder="Postcode"
                                                                                data-validation-for="postcode" />
                                                                        </div>
                                                                    </div>
                                                                    <input type="hidden" name="geolocationPostcode"
                                                                        value="false" />
                                                                    <button type="button"
                                                                        class="btn btn-tertiary btn--branch-search-location w-auto m-0 ml-2 glyph glyph--current-location js-geolocate-branch"
                                                                        data-interaction="Use Lokasi Saya">
                                                                        <span class="sr-only">
                                                                            Use Lokasi Saya</span>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="form-group form-group--cta">
                                                                <button type="submit"
                                                                    class="btn btn-primary js-branch-cta"
                                                                    data-interaction="Enter">
                                                                    Enter</button>
                                                            </div>
                                                            <div>
                                                                <input type="hidden" name="CSRFToken"
                                                                    value="9352966a-0293-482c-9e24-b9ac73a5ad5c" />
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <div class="offsite-panel__results js-branch-list">
                                                    </div>
                                                </div>

                                                <div class="offsite-panel__footer js-drawer-cookies ">
                                                    <hr class="hr" />
                                                    <div>Don&#39;t worry, we won&#39;t share your location! Read more
                                                        about how we use <a
                                                            href="/">cookies</a>.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="header-alt__quick-bar-icon header-alt__quick-bar-cart order-2 order-lg-1">
                                            <a href="/"
                                                class="header-alt__quick-bar-link js-mini-basket"
                                                data-mini-cart-url="/rollover/MiniCart"
                                                data-mini-cart-refresh-url="/miniCart/TOTAL_WITHOUT_DELIVERY"
                                                data-mini-cart-name="Trolley" data-mini-cart-empty-name="Empty Trolley"
                                                rel="nofollow">

                                                <span
                                                    class="header-alt__quick-bar-link-icon glyph glyph--cart-alt"></span>
                                                <div class="js-mini-cart-totals" data-v="2">
                                                    <span class="d-none d-xxl-block">
                                                        Produk <?= h($namaSitus) ?></span>
                                                </div>
                                            </a>
                                        </div>

                                        <div
                                            class="header-alt__quick-bar-icon order-1 order-lg-2 header-alt__quick-bar-account js-header-qb-account">
                                            <a href="<?= h($ctaUrl) ?>" rel="nofollow"
                                                class="header-alt__quick-bar-link header-alt__quick-bar-link--account"
                                                data-section="header-link-login-register">
                                                <span class="header-alt__quick-bar-link-icon glyph glyph--user"></span>
                                                <span class="d-none d-xxl-block">Login / Daftar</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="header-alt__menu order-4 d-lg-none js-header-menu-alt">
                                    <span class="header-alt__menu-icon">
                                        <span class="header-alt__menu-icon-bar"></span>
                                        <span class="header-alt__menu-icon-bar"></span>
                                        <span class="header-alt__menu-icon-bar"></span>
                                    </span>
                                </div>

                                <!-- P10 | overlay pencarian -->
                                <div class="header-alt__search-overlay search">
                                    <div class="search__wrap">
                                        <span class="glyph glyph--small glyph--cross"></span>
                                        <form class="search__form" novalidate="" autocomplete="off" method="get"
                                            action="/search">
                                            <div class="form-group form-group--search m-0">
                                                <div class="sr-only">
                                                    <label for="siteSearch1" class="sr-only">Search</label>
                                                </div>
                                                <div class="form-group__element">
                                                    <input type="text" role="search" class="form-control js-site-search"
                                                        id="siteSearch1" name="text" value="" maxlength="100"
                                                        placeholder="" data-blur-results="false" />
                                                </div>
                                                <div class="search__form-cta form-group form-group--cta">
                                                    <button type="submit"
                                                        class="btn btn-search glyph glyph--search">Search</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <div class="d-lg-none jewson-mobile-legacy-bar">
            <div class="header-alt__location-picker">
                <div href="#" class="header-alt__location-picker-link js-toggle-panel"
                    data-panel-id="location-picker-location" data-modal-selection="Enter your postcode">
                    <span class="glyph glyph--location-marker"></span>
                    <span class="header-alt__location-picker-text">LOKASI <?= h($namaSitus) ?></span>
                </div>

                <div href="#" class="header-alt__location-picker-link js-toggle-panel"
                    data-panel-id="location-picker-branch" data-modal-selection="Enter your postcode">
                    <span class="glyph glyph--store"></span>
                    <span class="header-alt__location-picker-text">ARTIKEL <?= h($namaSitus) ?></span>
                </div>
            </div>
        </div>
        <nav class="menu-alt js-main-menu-alt menu-alt--overlay" data-tracking-block="header"
            data-tracking-position="main-nav">
            <div class="container">
                <div class="row menu-alt__wrap">
                    <div class="col-12 menu-alt__inner">

                        <section class="header-alt__top-bar d-lg-none">
                            <!-- P11 | toggle VAT mobile -->
                            <div class="toggle-alt">
                                <form id="command" action="/_s/vat" method="post"><label for="toggle-alt_vat-mobile"
                                        class="toggle-alt__label" tabindex="0">
                                        <input type="checkbox" class="toggle-alt__input js-submit-on-toggle"
                                            id="toggle-alt_vat-mobile" name="showVatPrices" value="true" />
                                        <div class="toggle-alt__ui">
                                            <span class="toggle-alt__vat">VAT</span>
                                            <span class="toggle-alt__ex">EX</span>
                                            <div class="toggle-alt__track">
                                                <span class="toggle-alt__handler"></span>
                                            </div>
                                            <span class="toggle-alt__inc">INC</span>
                                        </div>
                                    </label>
                                    <div class="form-group form-group--cta hide">
                                        <button type="submit" class="btn btn-primary">Toggle</button>
                                    </div>
                                    <div>
                                        <input type="hidden" name="CSRFToken"
                                            value="9352966a-0293-482c-9e24-b9ac73a5ad5c" />
                                    </div>
                                </form>
                            </div>
                            <div class="header-alt__top-bar-links">
                                <div class="yCmsComponent header-alt__top-bar-link ">
                                    <a href="/"
                                        title="Service Kami"><span
                                            class="header-alt__top-bar-link-icon glyph glyph--build-filled"></span><span
                                            class="header-alt__top-bar-link-text">Service Kami</span></a></div>
                                <div class="yCmsComponent header-alt__top-bar-link ">
                                    <a href="/"
                                        title="Simulator Projek"><span
                                            class="header-alt__top-bar-link-icon glyph glyph--calculator"></span><span
                                            class="header-alt__top-bar-link-text">Simulator Projek</span></a></div>
                                <div class="yCmsComponent header-alt__top-bar-link ">
                                    <a href="/"
                                        title="Butuh Bantuan?"><span
                                            class="header-alt__top-bar-link-icon glyph glyph--headset"></span><span
                                            class="header-alt__top-bar-link-text">Butuh Bantuan?</span></a></div>
                            </div>
                        </section>

                        <div class="d-lg-none">
                            <span class="menu-alt__title">all categories</span>
                        </div>

                        <style>
                            .jewson-mobile-legacy-bar{display:none!important}
                            .dynamic-menu-wrap{display:flex;width:100%;overflow-x:auto;overflow-y:hidden;white-space:nowrap;scrollbar-width:thin;scroll-behavior:smooth}
                            .dynamic-menu-wrap .dynamic-post-menu{flex:0 0 auto}
                            .dynamic-post-menu__link{border-bottom:4px solid transparent!important;box-sizing:border-box;font-weight:650;transition:background-color .2s,color .2s,border-color .2s}
                            .dynamic-post-menu__link.active,.dynamic-post-menu__link[aria-current="page"]{background:#003c97!important;color:#fff!important;border-bottom:4px solid #ffb400!important;font-weight:800!important}
                            .dynamic-post-menu__link:focus-visible{outline:3px solid #ffb400;outline-offset:-3px}
                            .jewson-banner-column{flex:0 0 100%!important;max-width:100%!important;width:100%!important;order:1!important}
                            .jewson-banner-column .product-details-carousel{max-width:900px;margin-left:auto;margin-right:auto}
                            .jewson-copy-column{flex:0 0 100%!important;max-width:100%!important;width:100%!important;order:2!important}
                            .jewson-article{display:block!important;background:#fff;border-top:4px solid #003c97;margin-top:16px;padding:24px;border-radius:4px;box-shadow:0 4px 18px rgba(0,0,0,.08)}
                            .jewson-article__content{max-width:900px;margin:0 auto;font-size:17px;line-height:1.8;color:#20242a}
                            .jewson-article__content h2{margin-top:32px;color:#003c97}.jewson-article__content h3{margin-top:24px;color:#183b68}
                            .footer-floating-star{display:none!important}
                            @media(max-width:991px){.dynamic-menu-wrap{padding:0 8px}.dynamic-post-menu__link{min-height:48px;padding:13px 16px!important}.jewson-article{padding:18px 16px}.jewson-article__content{font-size:16px;line-height:1.75}}
                        </style>
                        <div class="menu-alt__tray dynamic-menu-wrap">

                            <div class="yCmsComponent d-lg-flex justify-content-between w-100" data-starname="NavigationBar"></div>
                            <!-- P12 | More from Jewson -->
                            <div class="d-lg-none menu-alt__tray-item">
                                <span class="menu-alt__title">More from Jewson</span>
                                <div>
                                    <a href="/"
                                        class="menu-alt__link at-element-marker">Click &amp; Collect in 15 mins</a>
                                    <a href="/"
                                        class="menu-alt__link at-element-marker">FREE next day delivery*</a>
                                    <a href="/"
                                        class="menu-alt__link at-element-marker" style="display: none;">Making better
                                        homes</a>
                                </div>
                            </div>

                            <div class="d-lg-none menu-alt__close js-menu-close-alt">
                                <button class="btn btn-tertiary w-100">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

    </header>
    <style>
        .jewson-post-nav{display:block!important;width:100%;background:#fff;border-top:1px solid #d7dde6;border-bottom:1px solid #c4ccd8;box-shadow:0 2px 8px rgba(0,0,0,.08);position:relative;z-index:30}
        .jewson-post-nav__scroll{display:flex!important;align-items:stretch;width:100%;max-width:1280px;margin:0 auto;overflow-x:auto;overflow-y:hidden;white-space:nowrap;scrollbar-width:thin;scroll-behavior:smooth}
        .jewson-post-nav .dynamic-post-menu{flex:1 0 auto;min-width:max-content}
        .jewson-post-nav .dynamic-post-menu__link{display:flex!important;align-items:center;justify-content:center;min-height:52px;padding:12px 18px!important;text-decoration:none!important;border-bottom:4px solid transparent!important;box-sizing:border-box;color:#002864!important;background:#fff!important;font-weight:700!important}
        .jewson-post-nav .dynamic-post-menu__link.active,.jewson-post-nav .dynamic-post-menu__link[aria-current="page"]{background:#003c97!important;color:#fff!important;border-bottom-color:#ffb400!important;font-weight:800!important}
        .jewson-post-nav__hint{display:none;text-align:center;padding:3px 8px;background:#edf3fb;color:#003c97;font-size:11px;font-weight:700}
        @media(max-width:991px){.jewson-post-nav .dynamic-post-menu{flex:0 0 auto}.jewson-post-nav .dynamic-post-menu__link{min-height:50px;padding:12px 16px!important}.jewson-post-nav__scroll{padding:0 6px}.jewson-post-nav__hint{display:block}}
    </style>
    <nav class="jewson-post-nav" aria-label="Navigasi artikel">
        <div class="jewson-post-nav__scroll"><div class="menu-alt__tray-item js-menu-tray-item dynamic-post-menu<?= $isHomepage ? ' active' : '' ?>"><a href="/" title="Beranda" class="menu-alt__link menu-alt__link--main js-menu-parent dynamic-post-menu__link<?= $isHomepage ? ' active" aria-current="page' : '' ?>">Beranda</a></div><?php foreach ($allPosts as $slug => $item): $aktif = !$isHomepage && (string)$slug === (string)$id; ?><div class="menu-alt__tray-item js-menu-tray-item dynamic-post-menu<?= $aktif ? ' active' : '' ?>"><a href="/<?= h($slug) ?>" title="<?= h($labelPost($slug, $item)) ?>" class="menu-alt__link menu-alt__link--main js-menu-parent dynamic-post-menu__link<?= $aktif ? ' active" aria-current="page' : '' ?>"><?= h($labelPost($slug, $item)) ?></a></div><?php endforeach; ?></div>
        <div class="jewson-post-nav__hint" aria-hidden="true">Geser menu ke kiri atau kanan</div>
    </nav>
    <script>
        document.addEventListener('DOMContentLoaded',function(){
            var active=document.querySelector('.jewson-post-nav [aria-current="page"]');
            if(active&&window.matchMedia('(max-width: 991px)').matches){
                active.scrollIntoView({behavior:'auto',block:'nearest',inline:'center'});
            }
        });
    </script>
    <div class="CTAgacor138468516465" id="ctaMeme">
        <a href="<?= h($ctaUrl) ?>" class="m-btn">DAFTAR</a>
        <a href="<?= h($ctaUrl) ?>" class="m-btn">LOGIN</a>
    </div>
    <style>
        .CTAgacor138468516465 {
            display: flex;
            width: 100%;
            max-width: 2000px;
            border: 2px solid #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(106, 13, 173, .6);
            animation: glowing 2s infinite alternate
        }

        .m-btn {
            flex: 1;
            padding: 15px 0;
            text-align: center;
            text-decoration: none;
            color: #fff;
            font: 900 18px Arial, sans-serif;
            letter-spacing: 1px;
            background: linear-gradient(180deg, #0047cc 0, #000 100%);
            position: relative;
            overflow: hidden;
            transition: .3s;
            display: block;
            border-right: 1px solid rgba(255, 255, 255, .3)
        }

        .m-btn:last-child {
            border-right: none
        }

        .m-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -150%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .6), transparent);
            transform: skewX(-25deg);
            animation: sh 3s infinite
        }

        .m-btn::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, .1);
            transform: scaleX(0);
            transition: .3s;
            transform-origin: right
        }

        .m-btn:hover::after {
            transform: scaleX(1);
            transform-origin: left
        }

        .m-btn:hover {
            color: #ff0;
            text-shadow: 0 0 10px #fff
        }

        @keyframes glowing {
            0% {
                box-shadow: 0 0 5px #0047cc, 0 0 10px #000
            }

            50% {
                box-shadow: 0 0 20px #3294cc, 0 0 10px #fff
            }

            100% {
                box-shadow: 0 0 5px #0047cc, 0 0 20px #000
            }
        }

        @keyframes sh {
            0% {
                left: -150%
            }

            20% {
                left: 150%
            }

            100% {
                left: 150%
            }
        }
    </style>
    <main id="main" class="main" tabindex="-1" data-tracking-block="main">
        <div class=" main-container container">
            <div class="yCmsContentslot ">
                <div class="row">
                    <div class="col-12">
                        <ol class="breadcrumb js-breadcrumbs " itemscope="" itemtype="http://schema.org/BreadcrumbList"
                            data-section="breadcrumbs">
                            <li class="breadcrumb__item d-none" itemprop="itemListElement" itemscope
                                itemtype="http://schema.org/ListItem" data-section="breadcrumb-item-home">
                                <a class="breadcrumb__link" itemprop="item" href="/">
                                    <span itemprop="name" class="breadcrumb__inner breadcrumb__inner--text">
                                        <?= h($namaSitus) ?></span>
                                    <span class="breadcrumb__inner breadcrumb__inner--icon" data-name="arrow">
                                        <span class="glyph glyph--chevron glyph--small"></span>
                                    </span>
                                </a>

                                <meta itemprop="position" content="1" />
                            </li>
                            <li class="breadcrumb__item d-none" itemprop="itemListElement" itemscope
                                itemtype="http://schema.org/ListItem" data-section="breadcrumb-item-2">
                                <a class="breadcrumb__link" itemprop="item"
                                    href="/">
                                    <span itemprop="name" class="breadcrumb__inner breadcrumb__inner--text">
                                        SLOT ONLINE</span>
                                    <span class="breadcrumb__inner breadcrumb__inner--icon" data-name="arrow">
                                        <span class="glyph glyph--chevron glyph--small"></span>
                                    </span>
                                </a>
                                <meta itemprop="position" content="2" />
                            </li>
                            <li class="breadcrumb__item d-none" itemprop="itemListElement" itemscope
                                itemtype="http://schema.org/ListItem" data-section="breadcrumb-item-3">
                                <a class="breadcrumb__link" itemprop="item"
                                    href="/">
                                    <span itemprop="name" class="breadcrumb__inner breadcrumb__inner--text">
                                        SLOT RESMI</span>
                                    <span class="breadcrumb__inner breadcrumb__inner--icon" data-name="arrow">
                                        <span class="glyph glyph--chevron glyph--small"></span>
                                    </span>
                                </a>
                                <meta itemprop="position" content="3" />
                            </li>
                            <li class="breadcrumb__item d-none" itemprop="itemListElement" itemscope
                                itemtype="http://schema.org/ListItem" data-section="breadcrumb-item-4">
                                <a class="breadcrumb__link" itemprop="item"
                                    href="/">
                                    <span itemprop="name" class="breadcrumb__inner breadcrumb__inner--text">
                                        SITUS SLOT ONLINE </span>
                                </a>
                                <meta itemprop="position" content="4" />
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="">
                <div class="row">
                    <div class="col-12 ">
                    </div>
                </div>
            </div>
            <div id="adobe-wrap">
                <div itemscope="" class="product product--main row mb-3 at-element-marker">
                    <div class="col-12 col-lg-8 float-left">
                        <div class="row">
                            <div class="col-12 col-lg-6 order-2 order-lg-1 jewson-banner-column">
                                <div class="glider-slider--nav js-glider-nav">
                                    <div class="js-glider-slider glider-slider glider-slider--main">
                                        <div id="gallery-product-main"
                                            class="product-details-carousel js-glider-nav-main" data-zoom-parent="">






                                            <div class="glider-slider__slide">
                                                <div class="zoom product-details-carousel__zoom">
                                                    <a href="<?= h($heroImg) ?>"
                                                        data-component="zoom">
                                                        <img src="<?= h($heroImg) ?>"
                                                            width="100%"
                                                            alt="<?= h($page['title'] ?? '') ?>"
                                                            title="<?= h($page['title'] ?? '') ?>"
                                                            itemprop="image"
                                                            content="<?= h($ogAbs) ?>" />
                                                    </a>
                                                    <div class="product-details-carousel__zoom-text small">
                                                        <span class="d-none d-lg-block">Click image to zoom in</span>
                                                        <span class="d-lg-none">Tap image to zoom in</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="controls">
                                            <button
                                                class="glider-prev glider-arrow glyph glyph--chevron glyph--small glyph--rotate-180"></button>
                                            <button
                                                class="glider-next glider-arrow glyph glyph--chevron glyph--small"></button>
                                            <div class="dots"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="js-glider-slider glider-slider glider-slider--thumb">
                                    <div id="gallery-product-thumb"
                                        class="product-details-carousel product-details-carousel--track">
                                        <div class="js-glider-thumb">
                                            <div class="product-details-carousel__track-block">
                                                <div class="product-details-carousel__thumbnail">
                                                    <img alt="<?= h($page['title'] ?? '') ?>"
                                                        title="<?= h($page['title'] ?? '') ?>"
                                                        src="<?= h($heroImg) ?>"
                                                        width="100%" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6 order-1 order-lg-2 jewson-copy-column">
                                <h1 itemprop="name" class="product-title mb-1"><?= h($page['h1'] ?? '') ?></h1>
                                <div class="product-sku mb-2"><?= h($namaSitus) ?> <?= h($tahun) ?></div>
                                <div class="mb-4 product__description jewson-article">
                                    <style>
                                        .starlinkresmi {
                                            width: 100%;
                                            max-width: 668px;
                                            margin: 0 auto;
                                            overflow: hidden;
                                            border-radius: 6px;
                                            position: relative;
                                        }

                                        .starlinkresmi .carousel-track {
                                            display: flex;
                                            width: calc(110px * 3);
                                            /* total lebar = 3 slide */
                                            animation: sigma-slide 20s infinite;
                                        }

                                        .starlinkresmi .slide {
                                            width: 110px;
                                            flex-shrink: 0;
                                        }

                                        .starlinkresmi .slide img {
                                            width: 100%;
                                            height: auto;
                                            display: block;
                                            border-radius: 6px;
                                        }

                                        @keyframes sigma-slide {
                                            0% {
                                                transform: translateX(0);
                                            }

                                            33% {
                                                transform: translateX(-110px);
                                            }

                                            66% {
                                                transform: translateX(-600px);
                                            }

                                            100% {
                                                transform: translateX(0);
                                            }
                                        }
                                    </style>
<div class="product__description-long mb-3 js-line-clamp">
    <div class="content jewson-article__content"><?= $isiArtikel ?></div>
</div>

<div class="strong attrib">Trending Tags :</div>
<ul>
<?php foreach ($tags as $t): ?>
    <li><?= h($t) ?></li>
<?php endforeach; ?>
</ul>
</div>

<div class="hide">
    <span class="sku">
        ID
        <span itemprop="sku productId"><?= h($namaSitus) ?></span>
    </span>

    <span itemprop="category">
        Entertainment &amp; Online Games &gt; Digital Gaming Platform &gt;
        <?= h($page['title'] ?? '') ?>
    </span>

    <span itemprop="url">
        <?= h($page['canonical_url'] ?? '') ?>
    </span>
</div>
</div>
</div>

                        <!-- Optional banner -->
                    </div>

                    <div class="col-12 col-lg-4 float-right product__panel">
                        <div class="simple-container__generic simple-container__product-panel">
                            <!-- P13 | See price and availability -->
                            <div class="p-2 bg-light-grey">
                                <form id="modalSessionLocationForm" name="sessionLocationForm" novalidate="true"
                                    data-form-id="sessionLocationForm" class="js-form-validate"
                                    data-disable-scroll="true" action="/fulfillment/options/delivery" method="post"
                                    data-np-autofill-form-type="identity" data-np-checked="1" data-np-watching="1">
                                    <div class="form-group form-group--search-group form-group__postcode mb-0">
                                        <div class="form-group__label">
                                            <label for="sessionLocationForm-postcode">
                                                <h3>See price and availability</h3>
                                            </label>
                                        </div>
                                        <div class="d-flex">
                                            <div class="form-group__validation">
                                                <div class="form-group__element">
                                                    <input id="sessionLocationForm-postcode" name="postcode"
                                                        required="true" class="form-control" type="text"
                                                        placeholder="SLOT ONLINE" data-np-checked="1"
                                                        data-np-autofill-field-type="zip"
                                                        data-np-uid="919130a6-8fd1-43b9-a0c4-22fa4148b832" />
                                                    <nordpass-icon data-np-uid="919130a6-8fd1-43b9-a0c4-22fa4148b832"
                                                        tabindex="-1" style="all: initial !important;"></nordpass-icon>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary px-3 py-0 js-pbp-cta"
                                                data-interaction="Enter">Enter</button>
                                        </div>
                                    </div>
                                    <div>
                                        <input type="hidden" name="CSRFToken"
                                            value="9352966a-0293-482c-9e24-b9ac73a5ad5c" />
                                    </div>
                                </form>
                            </div>
                            <div class="d-flex mt-3 align-items-center">
                                <span class="glyph glyph--delivery-fast mr-1"></span>
                                <p class="strong text-blue">Next day delivery</p>
                            </div>
                            <div class="d-flex mt-3 align-items-center">
                                <div class="mr-1"><span class="glyph glyph--click-and-collect"></span></div>
                                <p class="strong text-blue">Collect today</p>
                            </div>

                            <hr class="hr--default hr--offset-10" />
                            <div>
                                <div class="form-group d-flex mb-0">
                                    <div class="product__panel-icon mr-1">
                                        <span class="glyph glyph--wishlist"></span>
                                    </div>
<div class="flex-grow-1">
    <button disabled class="btn btn-secondary btn--neat mb-2 w-100">
        Simpan ke daftar favorit
    </button>

    <p>
        Silakan <a href="<?= h($ctaUrl) ?>">login atau daftar akun</a>
        untuk mengakses fitur eksklusif dan menyimpan aktivitas permainan Anda.
    </p>
</div>
                                </div>
                                <!-- P14 | modal New Project List -->
                                <div class="modal micromodal-slide js-wishlist-form" id="modal-wishlist-create"
                                    aria-hidden="true">
                                    <div class="modal__overlay" tabindex="-1" data-micromodal-close="">
                                        <div class="modal__container " role="dialog" aria-modal="true"
                                            aria-labelledby="modal-wishlist-create">
                                            <header class="modal__header">
                                                <button class="modal__close" aria-label="Close"
                                                    data-micromodal-close="">
                                                    Close</button>
                                            </header>
                                            <div class="modal__content mt-0" id="modal-wishlist-createcontent">
                                                <h2 class="modal__title" id="modal-wishlist-create-title">
                                                    New Project List</h2>
                                                <p class="large mb-3 mt-2 mt-md-0">
                                                    Save items to a project list which you can come back later.</p>

                                                <form id="wishlistCreateForm" novalidate="true"
                                                    class="refine mb-3 js-wishlist-form-validate"
                                                    action="/my-account/project-list/create" method="post"><input
                                                        id="backUrl" name="backUrl"
                                                        value="<?= h($page['canonical_url'] ?? '/') ?>"
                                                        type="hidden" /><input id="productCode" name="productCode"
                                                        value="<?= h(strtoupper((string)$id)) ?>" type="hidden" />
                                                    <div class="form-group">
                                                        <div class="form-group__label">
                                                            <label for="wishlist-createNewListName" class="">
                                                                Project list name</label>
                                                        </div>
                                                        <div class="form-group__validation">
                                                            <div class="form-group__element">
                                                                <input id="wishlist-createNewListName" name="name"
                                                                    class="form-control js-wishlist-new-name"
                                                                    required="true" type="text" value=""
                                                                    data-np-intersection-state="observed" /></div>
                                                        </div>
                                                    </div>
                                                    <div class="row no-gutters mt-md-4">
                                                        <div class="col-md-6 pl-md-1 mb-2 mb-md-0 order-md-2">
                                                            <button type="submit" class="btn btn-primary btn--store">
                                                                Create Project list</button>
                                                        </div>
                                                        <div class="col-md-6 pr-md-1 order-md-1">
                                                            <button type="button"
                                                                class="btn btn-tertiary btn--store js-reset-form"
                                                                data-micromodal-close="">
                                                                Cancel</button>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <input type="hidden" name="CSRFToken"
                                                            value="9352966a-0293-482c-9e24-b9ac73a5ad5c" />
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wrapstarlinkalternatif">
<div class="wrapstarlink">
    <p>
        <?= h($page['meta_description'] ?? '') ?>
    </p>
</div>
                    <style type="text/css">
                        div.advertisement-placeholder {
                            text-align: center;
                            padding-bottom: 20px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                        }

                        div.advertisement-text p {
                            margin: 0;
                            font-family: Open Sans;
                            font-style: normal;
                            font-weight: normal;
                            font-size: 10px;
                            line-height: 20px;
                            color: #999999;
                        }

                        div.advertisement-banner {
                            margin: 0px auto;
                        }

                        div#div-gpt-ad-billboard-placeholder {
                            width: 996px;
                            margin: 0 auto;
                            background: #F5F5F5;
                            padding-bottom: unset !important;
                            min-height: 200px;
                        }

                        div#div-gpt-ad-lb-placeholder {
                            background: #F5F5F5;
                            padding-bottom: unset !important;
                            min-height: 280px;
                        }

                        .faq-section {
                            max-width: 1200px;
                            margin: 50px auto;
                            padding: 40px 20px;
                            position: relative;
                            background-color: rgba(0, 0, 0, 0.1);
                            backdrop-filter: blur(25px);
                            border-radius: 20px;
                        }

                        .faq-section::before {
                            content: "";
                            position: absolute;
                            top: 0;
                            left: 50%;
                            transform: translateX(-50%);
                            width: 80%;
                            height: 2px;
                            background: linear-gradient(90deg, transparent, #0047cc, transparent)
                        }

                        .faq-section h2 {
                            text-align: center;
                            font-size: 34px;
                            font-weight: 700;
                            margin-bottom: 50px;
                            background: linear-gradient(135deg, #0047cc, #0047cc, #0047cc);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                            background-clip: text;
                            font-family: "Poppins", sans-serif;
                            letter-spacing: 1px
                        }

                        .faq-container {
                            display: flex;
                            flex-direction: column;
                            gap: 25px
                        }

                        .faq-item {
                            background: linear-gradient(145deg, #0a0a0a, #1a1a1a);
                            border: 2px solid #0047cc;
                            border-radius: 20px;
                            overflow: hidden;
                            transition: all .4s ease;
                            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.5)
                        }

                        .faq-item:hover {
                            border-color: #0047cc;
                            transform: translateX(10px);
                            box-shadow: 0 12px 35px #0047cc
                        }

                        .faq-question {
                            width: 100%;
                            padding: 28px 35px;
                            background: transparent;
                            color: #ffffff;
                            font-size: 18px;
                            font-weight: 600;
                            text-align: left;
                            border: none;
                            cursor: pointer;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            font-family: "Poppins", sans-serif;
                            transition: all .3s ease
                        }

                        .faq-question:hover {
                            color: #0047cc
                        }

                        .faq-question::after {
                            content: '+';
                            font-size: 32px;
                            font-weight: 300;
                            transition: all .4s ease;
                            color: #0047cc;
                            width: 40px;
                            height: 40px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border: 2px solid #0047cc;
                            border-radius: 50%;
                            background: rgba(0, 0, 0, 0.1)
                        }

                        .faq-item.active .faq-question::after {
                            transform: rotate(135deg);
                            background: linear-gradient(135deg#997a00#997a00);
                            color: #ffffff;
                            border-color: transparent
                        }

                        .faq-answer {
                            max-height: 0;
                            overflow: hidden;
                            transition: max-height .5s ease, padding .5s ease;
                            background: linear-gradient(180deg, rgba(253, 228, 4, 0.05), transparent);
                            padding: 0 35px
                        }

                        .faq-item.active .faq-answer {
                            color: #e0e0e0;
                            max-height: 800px;
                            padding: 30px 35px;
                            border-top: 1px solid rgba(253, 4, 4, 0.3)
                            
                        }

                        .faq-answer p {
                            color: #e0e0e0;
                            font-size: 16px;
                            line-height: 2;
                            margin: 0;
                            font-family: "Poppins", sans-serif
                        }

                        @media (max-width:768px) {
                            .faq-section {
                                padding: 30px 15px
                            }

                            .faq-section h2 {
                                font-size: 26px
                            }

                            .faq-question {
                                font-size: 16px;
                                padding: 22px 25px
                            }

                            .faq-question::after {
                                width: 35px;
                                height: 35px;
                                font-size: 28px
                            }

                            .faq-answer {
                                padding: 0 25px
                            }

                            .faq-item.active .faq-answer {
                                padding: 25px
                            }

                            .faq-item:hover {
                                transform: translateX(5px)
                            }
                        }
                    </style>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const faqItems = document.querySelectorAll('.faq-item');
                            faqItems.forEach(item => {
                                const question = item.querySelector('.faq-question');
                                question.addEventListener('click',
                                    () => {
                                        const isActive = item.classList.contains('active');
                                        faqItems.forEach(otherItem => {
                                            otherItem.classList.remove('active')
                                        });
                                        if (!isActive) {
                                            item.classList.add('active')
                                        }
                                    })
                            })
                        });
                    </script>
                    <br>
                    <br>

                    <div class="page__overlay" data-view="offCanvasNavToggle" data-off-canvas="close"
                        bis_skin_checked="1">
                    </div>
                    <!-- ========================= FAQ TITLE ========================= -->

                    <h2 class="cok-faq-title">FAQ TENTANG <?= h($namaSitus) ?></h2>

                    <!-- ========================= FAQ LIST ========================= -->

                    <div class="faq-container">
<?php foreach ($faq as $q): ?>

    <div class="faq-item">
        <h3 class="faq-question"><?= h($q[0]) ?></h3>
        <div class="faq-answer"><?= h($q[1]) ?></div>
    </div>
<?php endforeach; ?>

</div>
                    <!-- ========================= CSS ========================= -->
                    <style>
                        /* === TITLE MATCH COLOR WITH FAQ === */
                        .cok-faq-title {
                            text-align: center;
                            color: #0047cc;
                            font-size: 32px;
                            margin-top: 25px;
                            margin-bottom: 25px;
                            font-weight: 800;
                            font-family: "Segoe UI", sans-serif;
                            text-shadow: 0 0 12px #796705, 0 0 20px #50504e;
                            letter-spacing: 1px;
                        }

                        /* === FAQ CONTAINER === */
                        .cok-faq {
                            max-width: 1200px;
                            margin: 10px auto 40px auto;
                            font-family: "Segoe UI", sans-serif;
                        }

                        /* === QUESTION BUTTON === */
                        .cok-faq-q {
                            width: 100%;
                            padding: 14px;
                            background: #0047cc;
                            color: #ffffff;
                            font-weight: bold;
                            border: 1px solid #0047cc;
                            border-radius: 8px;
                            cursor: pointer;
                            text-align: left;
                            margin-bottom: 8px;
                            transition: .25s ease;
                            box-shadow: 0 0 10px #0047cc;
                        }

                        .cok-faq-q:hover {
                            background: #0047cc;
                            box-shadow: 0 0 15px #0047cc;
                        }

                        /* === ANSWER BOX === */
                        .cok-faq-a {
                            max-height: 0;
                            overflow: hidden;
                            background: #0047cc;
                            color: #05dffc;
                            margin-top: -5px;
                            border-radius: 0 0 8px 8px;
                            padding: 0 14px;
                            border-left: 1px solid #f2e26733;
                            border-right: 1px solid #f2e26733;
                            border-bottom: 1px solid #f2e26733;
                            transition: max-height .35s ease, padding .25s ease;
                            box-shadow: inset 0 0 10px #f2e26733;
                        }

                        .cok-faq-a.open {
                            max-height: 500px;
                            padding: 14px;
                        }

                        /* === RESPONSIVE === */
                        @media (max-width: 480px) {
                            .cok-faq-title {
                                font-size: 26px;
                            }
                        }
                    </style>

                    <!-- P15 | Testimoni Member -->
                    <section class="testimoni-section">
    <h2>TESTIMONI MEMBER <?= h($namaSitus) ?></h2>

    <div class="testimoni-grid">
<?php foreach ($testimoniMember as $t): ?>
    <div class="testimoni-card">
        <div class="rating-stars">&star;&star;&star;&star;&star;</div>
        <blockquote>
            <?= h($t[2]) ?>
        </blockquote>
        <div class="testimoni-author">
            <span><?= h($t[0]) ?></span>
            <small><?= h($t[1]) ?></small>
        </div>
    </div>
<?php endforeach; ?>
</div>
</section>
                    <style>
                        .testimoni-section {
                            padding: 60px 20px;
                            background: linear-gradient(135deg, #000000, #000000);
                            text-align: center;
                            margin: 40px 0;
                        }

                        .testimoni-section h2 {
                            color: #0047cc;
                            font-size: 36px;
                            margin-bottom: 20px;
                            font-weight: 800;
                            text-shadow: 0 0 15px #0047cc;
                            letter-spacing: 1px;
                        }

                        .testimoni-section>p {
                            color: #ffffff;
                            font-size: 18px;
                            margin-bottom: 40px;
                            max-width: 900px;
                            margin-left: auto;
                            margin-right: auto;
                        }


                        .testimoni-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                            gap: 30px;
                            max-width: 1200px;
                            margin: 0 auto;
                        }


                        .testimoni-card {
                            background: #111111;
                            border: 2px solid #0047cc;
                            border-radius: 16px;
                            padding: 25px;
                            box-shadow: 0 0 20px rgba(249, 253, 0, 0.4);
                            transition: transform 0.3s ease, box-shadow 0.3s ease;
                        }

                        .testimoni-card:hover {
                            transform: translateY(-10px);
                            box-shadow: 0 0 30px rgba(255, 251, 0, 0.4);
                        }

                        .rating-stars {
                            color: #0047cc;
                            font-size: 28px;
                            margin-bottom: 15px;
                            letter-spacing: 5px;
                        }

                        .testimoni-card blockquote {
                            color: #ffffff;
                            font-size: 16px;
                            line-height: 1.6;
                            margin: 0 0 20px 0;
                            font-style: italic;
                        }

                        .testimoni-author {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            color: #0047cc;
                            font-size: 15px;
                            font-weight: bold;
                        }

                        .testimoni-author small {
                            color: #aaaaaa;
                            font-weight: normal;
                            font-size: 14px;
                        }

                        .testimoni-closing {
                            color: #0047cc;
                            font-size: 20px;
                            margin-top: 50px;
                            font-style: italic;
                            text-align: justify;
                        }

                        @media (max-width: 768px) {
                            .testimoni-section h2 {
                                font-size: 30px;
                            }

                            .testimoni-grid {
                                grid-template-columns: 1fr;
                            }
                        }
                    </style>



                    <br /><br /><br /><br />
                    <style>
                        #testimonislotterbaikgacor138 {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                            gap: 20px;
                            padding: 20px;
                            max-width: 1200px;
                            margin: 0 auto;
                            font-family: 'Segoe UI', sans-serif
                        }

                        #testimonislotterbaikgacor138 .t-card {
                            position: relative;
                            background: #000;
                            border-radius: 15px;
                            overflow: hidden;
                            z-index: 1;
                            padding: 3px;
                            transition: transform .3s ease;
                            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5)
                        }

                        #testimonislotterbaikgacor138 .t-card::before {
                            content: '';
                            position: absolute;
                            top: -50%;
                            left: -50%;
                            width: 200%;
                            height: 200%;
                            background: conic-gradient(transparent, transparent, transparent, #0047cc);
                            animation: rotateBorder 4s linear infinite;
                            z-index: -2
                        }

                        #testimonislotterbaikgacor138 .t-card::after {
                            content: '';
                            position: absolute;
                            top: -50%;
                            left: -50%;
                            width: 200%;
                            height: 200%;
                            background: conic-gradient(transparent, transparent, transparent, #8B0000);
                            animation: rotateBorder 4s linear infinite;
                            animation-delay: -2s;
                            z-index: -2
                        }

                        #testimonislotterbaikgacor138 .t-content {
                            background: linear-gradient(160deg, #1a0033 0, #000 95%);
                            border-radius: 12px;
                            padding: 20px;
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                            position: relative;
                            z-index: 2;
                            overflow: hidden
                        }

                        #testimonislotterbaikgacor138 .t-content::before {
                            content: '';
                            position: absolute;
                            top: 0;
                            left: -150%;
                            width: 100%;
                            height: 100%;
                            background: linear-gradient(90deg, transparent 0, rgba(255, 255, 255, 0.15) 50%, transparent 100%);
                            transform: skewX(-25deg);
                            animation: kilatAuto 3.5s infinite ease-in-out;
                            pointer-events: none
                        }

                        #testimonislotterbaikgacor138 .t-card:hover {
                            transform: scale(1.03);
                            box-shadow: 0 0 25px rgba(138, 43, 226, 0.6)
                        }

                        #testimonislotterbaikKgacor138.t-head {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            border-bottom: 2px solid #0047cc;
                            padding-bottom: 10px;
                            margin-bottom: 15px
                        }

                        #testimonislotterbaikgacor138 .t-name {
                            color: #fff;
                            font-weight: 900;
                            font-size: 16px;
                            text-transform: uppercase;
                            text-shadow: 0 0 5px #0047cc
                        }

                        #testimonislotterbaikgacor138 .t-loc {
                            color: #bf55ec;
                            font-size: 12px;
                            font-weight: 700
                        }

                        #testimonislotterbaikgacor138 .t-text {
                            color: #e0e0e0;
                            font-size: 14px;
                            line-height: 1.6;
                            font-weight: 600;
                            font-style: italic
                        }

                        @keyframes rotateBorder {
                            0% {
                                transform: rotate(0deg)
                            }

                            100% {
                                transform: rotate(360deg)
                            }
                        }

                        @keyframes kilatAuto {
                            0% {
                                left: -150%
                            }

                            30% {
                                left: 150%
                            }

                            100% {
                                left: 150%
                            }
                        }
                    </style>
                    <script>
                        document.addEventListener("DOMContentLoaded", (function () {
                            const e = document.querySelectorAll("#testimonislotterbaikgacor138 .t-card");
                            e.forEach(((e, t) => {
                                e.style.opacity = "0", e.style.transform = "translateY(20px)",
                                    setTimeout((() => {
                                        e.style.transition =
                                            "opacity 0.6s ease, transform 0.6s ease", e
                                            .style.opacity = "1", e.style.transform =
                                            "translateY(0)"
                                    }), 200 * t)
                            }))
                        }));
                    </script>
                </div>
                <style>
                    .wrapstarlinkalternatif {
                        position: relative;
                        width: 100%;
                        max-width: 1500px;
                        margin: 5px auto;
                        padding: 30px;
                        background: linear-gradient(#000, #000) padding-box, linear-gradient(180deg, #0047cc 0, #000 100%) border-box;
                        border: 4px solid transparent;
                        border-radius: 20px;
                        text-align: center;
                        font-family: 'Segoe UI', sans-serif;
                        box-shadow: 0 0 25px rgba(138, 43, 226, 0.4), inset 0 0 20px #000;
                        overflow: hidden;
                        z-index: 1
                    }

                    .wrapstarlinkalternatif>* {
                        position: relative;
                        z-index: 10
                    }

                    .wrapstarlinkalternatif h2,
                    .judul-wrapper-gacor138 {
                        margin: 0 0 20px;
                        font-size: 28px;
                        font-weight: 900;
                        text-transform: uppercase;
                        letter-spacing: 2px;
                        color: #0047cc;
                        display: block;
                        width: 100%;
                        text-align: center
                    }

                    .wrapstarlink {
                        color: #fff;
                        font-size: 16px;
                        line-height: 1.5;
                        font-weight: 700
                    }
                </style>
            </div>
            <div><br />
                <div class="product-recommendations-carousel">
                    <h2 class="mb-1">Pelanggan juga membeli</h2>
                    <div class="glider-contain js-add-product" data-component="recommendations">
                        <div class="align-items-center d-flex">
                            <div class="glider-arrow">
                                <button aria-label="Previous" class="glider-prev disabled" aria-disabled="true"><span
                                        class="sr-only">Previous</span></button>
                            </div>
                            <div class="glider draggable">
                                <div class="glider-track" style="width: 2620px;">
<?php $n = 0; foreach ($postLain as $slug => $item):
    $namaKartu = $labelPost($slug, $item);
    $judulKartu = ($item['title'] ?? '') !== '' ? $item['title'] : $namaKartu;
    $gambarKartu = ($item['image_src'] ?? '') !== '' ? $item['image_src'] : $bannerSrc; ?>
                                    <div class="item glider-slide<?= $n === 0 ? ' active' : '' ?>" data-product-name="<?= h($namaKartu) ?>"
                                        data-product-position="<?= $n + 1 ?>"
                                        data-gslide="<?= $n ?>" style="height: auto; width: 327.5px;">
                                        <div class="product product--carousel">
                                            <a href="/<?= h($slug) ?>" class="link-header link-header--primary">
                                                <div class="product__thumb">
                                                    <picture class="product__image">
                                                        <img alt="<?= h($namaKartu) ?>" title="<?= h($namaKartu) ?>"
                                                            src="<?= h($gambarKartu) ?>" loading="lazy" />
                                                    </picture>
                                                </div>
                                                <div class="product__name"><?= h($judulKartu) ?></div>
                                            </a>
                                            <div class="d-flex flex-column flex-grow-1">
                                                <p class="flex-grow-1 small">SKU: <?= h(strtoupper($slug)) ?></p>
                                                <div class="loading-text js-recommendations-price-loading d-none">
                                                    <div class="loading-text__text">Loading<span
                                                            class="loading-text__ellipsis"></span></div>
                                                </div>
                                                <div class="price-missing__pbp js-recommendations-price-hidden">
                                                    <a href="/<?= h($slug) ?>">See
                                                        price</a>
                                                </div>
<div class="price price--medium d-none js-recommendations-price">
    <div class="price__value"><?= h($labelKartu[$n % count($labelKartu)]) ?></div>
</div>
                                                <!-- P16 | form Add to trolley per kartu -->
                                                <div class="plp__product-quantity mt-3 d-none js-recommendations-cta">
                                                    <div class="product__quantity product__quantity-full js-product-quantity-holder js-add-to-cart-price-available mb-0"
                                                        data-product-id="<?= h(strtoupper($slug)) ?>">
                                                        <form id="addToCartForm-<?= h(strtoupper($slug)) ?>" novalidate="true"
                                                            action="/"
                                                            method="post">
                                                            <input type="hidden" name="productCode" value="<?= h(strtoupper($slug)) ?>"
                                                                id="productCode-<?= h(strtoupper($slug)) ?>" />
                                                            <input type="hidden" name="CSRFToken"
                                                                value="9352966a-0293-482c-9e24-b9ac73a5ad5c" />
                                                            <div class="form-group form-group--minimal mb-0">
                                                                <div
                                                                    class="form-group product__quantity-block product__quantity-block--plp flex-wrap mb-0">
                                                                    <div
                                                                        class="js-quantity-modifier product__quantity-inputs mb-1 mr-1">
                                                                        <fieldset>
                                                                            <legend class="sr-only">Quantity</legend>
                                                                            <div class="d-flex">
                                                                                <div
                                                                                    class="product__quantity-input form-group__validation d-inline-block">
                                                                                    <div class="form-group__element">
                                                                                        <input id="quantity-<?= h(strtoupper($slug)) ?>"
                                                                                            name="qty" data-max="9999"
                                                                                            data-threshold="999"
                                                                                            value="1"
                                                                                            class="form-control form-control--quantity js-input-quantity"
                                                                                            data-min="1"
                                                                                            aria-label="Quantity input"
                                                                                            required="true"
                                                                                            type="number" size="2" />
                                                                                    </div>
                                                                                </div>
                                                                                <div
                                                                                    class="product__quantity-inputs-modifiers d-flex flex-column">
                                                                                    <button
                                                                                        class="product__quantity-modify product__quantity-modify--plus"
                                                                                        aria-label="Quantity increase"
                                                                                        data-mode="increase"></button>
                                                                                    <button
                                                                                        class="product__quantity-modify product__quantity-modify--minus"
                                                                                        aria-label="Quantity decrease"
                                                                                        data-mode="decrease"></button>
                                                                                </div>
                                                                            </div>
                                                                        </fieldset>
                                                                    </div>
                                                                    <div
                                                                        class="product__quantity-button product__quantity-button--plp d-flex mb-1">
                                                                        <button type="submit"
                                                                            class="btn btn-primary btn--plp">
                                                                            <span class="btn__text">Add to
                                                                                trolley</span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
<?php $n++; endforeach; ?>
                                </div>
                            </div>
                            <div class="glider-arrow">
                                <button aria-label="Next" class="glider-next" aria-disabled="false"><span
                                        class="sr-only">Next</span></button>
                            </div>
                        </div>
                        <div role="tablist" class="dots glider-dots"><button data-index="0" aria-label="Page 1"
                                role="tab" class="glider-dot active"></button><button data-index="1" aria-label="Page 2"
                                role="tab" class="glider-dot "></button></div>
                    </div>
                </div>
            </div>

            <div id="alternativeProductsCompponent">
                <div class="yCmsContentslot js-track-carousel" data-starname="AlternativeProducts">
                </div>
            </div>
            <div class="yCmsContentslot js-track-carousel" data-starname="UpSelling">
            </div>
            <div class="yCmsContentslot js-track-carousel" data-starname="CrossSelling">
            </div>
            <!-- P17 | modal Check for your nearest stock -->
            <div class="modal micromodal-slide check-stock-modal" id="modal-pdp-stock" aria-hidden="true">
                <div class="modal__overlay" tabindex="-1" data-micromodal-close="">
                    <div class="modal__container check-stock-modal__container" role="dialog" aria-modal="true">
                        <header class="modal__header check-stock-modal__header">
                            <h3 class="modal__title" id="modal-pdp-stock-title">
                                Check for your nearest stock</h3>
                            <button class="modal__close" aria-label="Close" data-micromodal-close="">Close</button>
                        </header>
                        <div class="modal__content check-stock-modal__content my-2" id="modal-pdp-stockcontent">
                            <div class="js-stock-check-form check-stock-modal__form">
                                <div class="row">
                                    <div class="col-md-9">
                                        <form id="pdpStockCheckFormModal" class="form-postcode js-store-search"
                                            action="#" method="post">
                                            <div class="input-group form-group mb-2">
                                                <div class="form-group__validation flex-grow-1">
                                                    <div class="form-group__element">
                                                        <input type="text" id="storeSearch" class="form-control"
                                                            name="locationQuery" placeholder="Postcode"
                                                            aria-label="Postcode"
                                                            data-np-intersection-state="observed" />
                                                    </div>
                                                </div>
                                                <div class="d-flex">
                                                    <span class="input-group-btn flex-grow-1">
                                                        <div class="form-group m-0 ml-sm-1 text-center h-100">
                                                            <button
                                                                class="btn btn-primary btn--branch-search h-100 js-modal-nearest-stock-modal">
                                                                Search</button>
                                                        </div>
                                                    </span>
                                                    <span class="input-group-btn">
                                                        <div class="form-group m-0 ml-1 text-center h-100">
                                                            <button
                                                                class="btn btn-tertiary btn--branch-search-location glyph glyph--current-location h-100"
                                                                data-modal-geolocate="">
                                                                <span class="sr-only">Use Lokasi Saya</span>
                                                            </button>
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="input-group">
                                                <div class="form-group checkbox mb-0">
                                                    <input id="inStock" data-validation-for="checkbox" required="false"
                                                        name="inStock" role="group"
                                                        class="checkbox__input sr-only js-checkbox-stock"
                                                        type="checkbox" value="inStock" aria-labelledby="inStockLabel"
                                                        checked />
                                                    <label id="inStockLabel" class="checkbox__label mb-0 strong"
                                                        for="inStock">
                                                        Show nearest branches with stock</label>
                                                </div>
                                            </div>
                                            <input id="pdpModalCollectionLastPostcode" name="collectionLastPostcode"
                                                type="hidden" value="" /><input id="pdpModalproductCode"
                                                name="productCode" type="hidden" value="<?= h(strtoupper((string)$id)) ?>" /><input
                                                id="pdpModalCurrentPage" name="currentPage" value="0" type="hidden" />
                                            <div>
                                                <input type="hidden" name="CSRFToken"
                                                    value="9352966a-0293-482c-9e24-b9ac73a5ad5c" />
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="js-stock-check-results js-remember-fulfillment check-stock-modal__results">
                                <div class="js-store-search-error"></div>
                                <hr class="hr--default" />
                                <div class="mb-2">
                                    <p class="strong mb-0">Stock is confirmed once you reserve your order</p>
                                    <p class="js-alt-stock-messaging d-none">It is your responsibility when choosing
                                        suitable alternatives for your needs</p>
                                </div>
                                <div class="tabs tabs--stretch tabs--pdp" data-tabs="">
                                    <ul class="tabs__list" role="tablist">
                                        <li class="tabs__list-item active">
                                            <a class="tabs__link"
                                                href="/"
                                                data-tabs-head="" data-pdp-select="collection"><span
                                                    class="d-none d-md-block">Click &amp; Collect Branches</span><span
                                                    class="d-md-none">Collection</span></a>
                                        </li>
                                        <li class="tabs__list-item">
                                            <a class="tabs__link"
                                                href="/"
                                                data-tabs-head="" data-pdp-select="delivery"><span
                                                    class="d-none d-md-block">Delivery</span><span
                                                    class="d-md-none">Delivery</span></a>
                                        </li>
                                    </ul>
                                    <div class="tabs__content">
                                        <div data-tabs-content="" class="tabs__content-panel active" id="collection">
                                            <div class="tabs__content-panel-results"></div>
                                            <div id="loadMoreSection" class="text-center check-stock-modal__load-more">
                                                <form id="pdpStockCheckFormSection" class="js-store-search" action="#"
                                                    method="post"><input type="hidden" id="loadMoreSectionStoreSearch"
                                                        class="form-control form-control--search"
                                                        name="locationQuery" />

                                                    <div class="text-center px-2">
                                                        <button type="submit"
                                                            class="btn btn-tertiary mt-1 mb-1 mb-md-0 js-modal-nearest-stock-modal"
                                                            id="loadMoreButton">
                                                            Load More</button>
                                                    </div>

                                                    <input type="hidden" id="pdpLoadMoreSectionCurrentPage"
                                                        name="currentPage" />
                                                    <input type="hidden" id="pdpLoadMoreSectionInStock" required="false"
                                                        name="inStock" />

                                                    <input id="pdpLoadMoreSectionProductCode" name="productCode"
                                                        type="hidden" value="<?= h(strtoupper((string)$id)) ?>" />
                                                    <div>
                                                        <input type="hidden" name="CSRFToken"
                                                            value="9352966a-0293-482c-9e24-b9ac73a5ad5c" />
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <div data-tabs-content="" class="tabs__content-panel" id="delivery">
                                            <div class="tabs__content-panel-results"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div id="js-modals-container">

        </div>

    </main>
    <footer class="footer" data-tracking-block="footer">
        <div class="social-footer" data-tracking-position="social">
            <div class="container">
            </div>
        </div>
        <style>
            .footer__inner {
                background: black;
            }
        </style>
        <div class="footer__inner" data-tracking-position="quick-links">
            <div class="container">
                <div class="row">
                    <div class="accordion accordion--xs accordion--all js-accordion col-12">
                        <div class="row m-0">
                            <div class="footer__section col-12 col-lg-3">
                                <div class="accordion-toggle__content mb-0 js-accordion-content">
                                    <span
                                        class="h3 footer__heading footer__heading--accordion accordion-toggle__title js-accordion-toggle">
                                        Helpful links</span>
                                    <div class="accordion-toggle__inner">
                                        <ul class="footer__list">
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Branch locator">Branch locator</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Delivery &amp; collection information">Delivery &amp;
                                                    collection information</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="E-billing &amp; paying your account">E-billing &amp; paying
                                                    your account</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Product recall">Product recall</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Promotions">Promotions</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Returns policy">Returns policy</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Shop by brand">Shop by brand</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Supplier updates">Supplier updates</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Tool hire help">Tool hire help</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="footer__section col-12 col-lg-3">
                                <div class="accordion-toggle__content mb-0 js-accordion-content">
                                    <span
                                        class="h3 footer__heading footer__heading--accordion accordion-toggle__title js-accordion-toggle">
                                        Our company</span>
                                    <div class="accordion-toggle__inner">
                                        <ul class="footer__list">
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="About us">About us</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Careers">Careers</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Contact us">Contact us</a></li>
                                            <!-- P18 | 3 link Our company -->
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Jewson services">Jewson services</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Jewson kitchen showrooms">Jewson kitchen showrooms</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Jewson bathroom showrooms">Jewson bathroom showrooms</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Electronic recycling service">Electronic recycling
                                                    service</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="footer__section col-12 col-lg-3">
                                <div class="accordion-toggle__content mb-0 js-accordion-content">
                                    <span
                                        class="h3 footer__heading footer__heading--accordion accordion-toggle__title js-accordion-toggle">
                                        Legal</span>
                                    <div class="accordion-toggle__inner">
                                        <ul class="footer__list">
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Content policy">Content policy</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Cookies policy">Cookies policy</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Modern slavery" target="_blank" rel="noopener">Modern
                                                    slavery</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Privacy statement" target="_blank" rel="noopener">Privacy
                                                    statement</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Terms &amp; conditions">Terms &amp; conditions</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Terms &amp; conditions of hire" target="_blank"
                                                    rel="noopener">Terms &amp; conditions of hire</a></li>
                                            <li class="yCmsComponent">
                                                <a href="/"
                                                    title="Terms of website use">Terms of website use</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="footer__section col-12 col-lg-3">
                                <div class="accordion-toggle__content mb-0 js-accordion-content">
                                    <span
                                        class="h3 footer__heading footer__heading--accordion accordion-toggle__title js-accordion-toggle">
                                        Company Information</span>
<div class="accordion-toggle__inner">
    <ul class="footer__list">
        <li class="yCmsComponent">
            <div class="content">
<?php foreach ($infoPerusahaan as $baris): ?>

                <p><?= h($baris) ?></p>
<?php endforeach; ?>

            </div>
        </li>
    </ul>
</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer__bottom" data-tracking-position="bottom">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <hr class="footer__hr" />
                    </div>
                    <div class="col-12">
                        <div class="footer__copyright">
                            <span class="footer__small">
                                <div class="content"><?= h($tahun) ?> <?= h($namaSitus) ?> 
                                </div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <div id="ariaStatusMsg" class="skip" role="status" aria-relevant="text" aria-live="polite"></div>

    <!-- P19 | Adobe Launch + Civic Cookie Control -->
    <script type="text/javascript"
        src="//assets.adobedtm.com/f177fcea2274/0ec023f9574e/launch-d6138b71f4d6.min.js?version=b97cc89f23855c8ec9feab9d57af7823"
        async=""></script>
    <script src="https://cc.cdn.civiccomputing.com/9/cookieControl-9.4.min.js"
        integrity="sha384-ck5f8pThYoM1f8j4JwF+l6iQPh5lbgeaEdXUv5n8bxd8kZe9DyY3vHU4nlyef0UW" crossorigin="anonymous">
    </script>

    <style>
        body {
            padding-bottom: 70px;
        }

        /* Mencegah konten tertutup footer */

        .footerslotstar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 65px;
            background: linear-gradient(180deg, #0047cc 0%, #000000 100%);
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 99999;
            border-top: 2px solid #0300bb;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.7);
            animation: activeBorder 2s infinite alternate;
            overflow: visible;
        }

        /* Animasi Border Putih Aktif Bergani-ganti */
        @keyframes activeBorder {
            0% {
                border-color: #fff;
                box-shadow: 0 -2px 10px #fff;
            }

            50% {
                border-color: #0047cc;
                box-shadow: 0 -4px 20px #3294cc;
            }

            100% {
                border-color: #000;
                box-shadow: 0 -2px 15px #fff;
            }
        }

        .ft-btn {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #fff;
            font-family: sans-serif;
            transition: 0.3s;
            position: relative;
        }

        .ft-btn svg {
            width: 22px;
            height: 22px;
            fill: #fff;
            margin-bottom: 3px;
            transition: 0.3s;
        }

        .ft-btn span {
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        /* Tombol Daftar di Tengah (Lebih Menonjol) */
        .ft-main {
            background: linear-gradient(145deg, #0047cc, #f8c007);
            height: 75px;
            margin-top: -30px;
            border-radius: 50% 50% 0 0;
            border-top: 3px solid #fff;
            box-shadow: 0 -5px 15px rgba(255, 255, 255, 0.4);
        }

        .ft-main svg {
            width: 28px;
            height: 28px;
        }

        /* Animasi Kilatan Otomatis di Background */
        .footerslotstar::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: skewX(-30deg);
            animation: flashBg 4s infinite;
        }

        @keyframes flashBg {
            0% {
                left: -100%;
            }

            30% {
                left: 150%;
            }

            100% {
                left: 150%;
            }
        }

        .ft-btn:hover svg {
            transform: scale(1.2) translateY(-3px);
            fill: #0047cc;
        }

        .ft-btn::after {
            content: "";
            position: absolute;
            bottom: -5px;
            width: 0;
            height: 2px;
            background: #fff;
            transition: 0.3s;
        }

        .ft-btn:hover::after {
            width: 50%;
        }

        /* Presisi Mobile */
        @media (max-width: 360px) {
            .ft-btn span {
                font-size: 8px;
            }

            .ft-btn svg {
                width: 18px;
            }
        }
    </style>

    <style>
        .footer-floating-star {
            position: fixed;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 1500px;
            display: flex;
            gap: 14px;
            padding: 16px 22px;
            background: #0047cc54;
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 2px solid rgb(0 0 0 / 50%);
            box-shadow: 0 0 30px #0047cca6;
            z-index: 999;
        }

        /* BUTTON BASE (EQUAL SIZE) */
        .footer-btn-star {
            flex: 1;
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 10px;
            font-size: 14px;
            font-weight: bold;
            color: #e3e3e3;
            background: #000000;
            border-radius: 32px;
            border: 2px solid #d7d7d7;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 14px rgba(247, 231, 6, 0.6);
            animation: floatUpDown 3s ease-in-out infinite;
            text-decoration: none;
            user-select: none;
            white-space: nowrap;
        }

        /* ICON */
        .footer-btn-star img {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            filter: drop-shadow(0 0 5px rgb(255 177 0 / 80%));
        }

        /* FLOAT DELAY */
        .footer-btn-star.login {
            animation-delay: 0.3s;
        }

        .footer-btn-star.daftar {
            animation-delay: 0.6s;
        }

        .footer-btn-star.link {
            animation-delay: 0.9s;
        }

        .footer-btn-star.chat {
            animation-delay: 1.2s;
        }

        /* HOVER GLOW */
        .footer-btn-star:hover {
            background: #ffffff;
            box-shadow:
                0 0 15px #c9c1c1,
                0 0 30px #c9c1c1,
                0 0 50px #ff7e4d;
            transform: translateY(-6px) scale(1.05);
        }

        /* ACTIVE GOLD */
        .footer-btn-star:active {
            background: linear-gradient(135deg, #0047cc, #0047cc);
            color: #ffffff;
            border-color: #dddcd4;
            box-shadow:
                0 0 18px #dddcd4,
                0 0 35px #f4f4f4;
        }

        /* FLOAT ANIMATION */
        @keyframes floatUpDown {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }

            100% {
                transform: translateY(0);
            }
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .footer-floating-star {
                flex-wrap: wrap;
            }

            .footer-btn-star {
                flex: 1 1 calc(50% - 10px);
            }
        }

        @media (max-width: 600px) {
            .footer-btn-star {
                flex: 1 1 100%;
                font-size: 13px;
                padding: 10px 12px;
            }

            .footer-btn-star img {
                width: 16px;
                height: 16px;
            }
        }
    </style>

    <div class="footer-floating-star">
        <a href="<?= h($ctaUrl) ?>" class="footer-btn-star promo"
            target="_blank" rel="nofollow noopener">
            <img src="<?= h($faviconSrc) ?>" alt="PROMOSI <?= h($namaSitus) ?>" /> PROMO
        </a>

        <a href="<?= h($ctaUrl) ?>" class="footer-btn-star login"
            target="_blank" rel="nofollow noopener">
            <img src="<?= h($faviconSrc) ?>" alt="LOGIN <?= h($namaSitus) ?>" /> LOGIN
        </a>

        <a href="<?= h($ctaUrl) ?>" class="footer-btn-star daftar"
            target="_blank" rel="nofollow noopener">
            <img src="<?= h($faviconSrc) ?>" alt="DAFTAR <?= h($namaSitus) ?>" /> DAFTAR
        </a>

        <a href="<?= h($ctaUrl) ?>" class="footer-btn-star link"
            target="_blank" rel="nofollow noopener">
            <img src="<?= h($faviconSrc) ?>" alt="LINK ALTERNATIF <?= h($namaSitus) ?>" /> LINK
            ALTERNATIF
        </a>

        <a href="<?= h($ctaUrl) ?>" class="footer-btn-star chat"
            target="_blank" rel="nofollow noopener">
            <img src="<?= h($faviconSrc) ?>" alt="LIVE CHAT <?= h($namaSitus) ?>" /> LIVE CHAT
        </a>
    </div>

    <!-- P20 | 14 beacon Cloudflare Web Analytics -->
    <script defer
        src="https://static.cloudflareinsights.com/beacon.min.js/v8c78df7c7c0f484497ecbca7046644da1771523124516"
        integrity="sha512-8DS7rgIrAmghBFwoOTujcf6D9rXvH8xm8JQ1Ja01h9QX8EzXldiszufYa4IFfKdLUKTTrnSFXLDkUEOTrZQ8Qg=="
        data-cf-beacon='{"version":"2024.11.0","token":"280ec415a452447c89a776c0383a7953","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}'
        crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v8c78df7c7c0f484497ecbca7046644da1771523124516" integrity="sha512-8DS7rgIrAmghBFwoOTujcf6D9rXvH8xm8JQ1Ja01h9QX8EzXldiszufYa4IFfKdLUKTTrnSFXLDkUEOTrZQ8Qg==" data-cf-beacon='{"version":"2024.11.0","token":"3e3a8b8a6c96425eab95dae5b845cba3","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v8c78df7c7c0f484497ecbca7046644da1771523124516" integrity="sha512-8DS7rgIrAmghBFwoOTujcf6D9rXvH8xm8JQ1Ja01h9QX8EzXldiszufYa4IFfKdLUKTTrnSFXLDkUEOTrZQ8Qg==" data-cf-beacon='{"version":"2024.11.0","token":"d74be454e55c4864a1a665a228dd847d","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v4513226cdae34746b4dedf0b4dfa099e1781791509496" integrity="sha512-ZE9pZaUXND66v380QUtch/5sE9tPFh2zg45pR2PB0CVkCtOREv2AJKkSidISWkysEuQ0EH8faUU5du78bx87UQ==" data-cf-beacon='{"version":"2024.11.0","token":"6f1a32fb8ccc473eaba1eff11e61cc6f","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v4513226cdae34746b4dedf0b4dfa099e1781791509496" integrity="sha512-ZE9pZaUXND66v380QUtch/5sE9tPFh2zg45pR2PB0CVkCtOREv2AJKkSidISWkysEuQ0EH8faUU5du78bx87UQ==" data-cf-beacon='{"version":"2024.11.0","token":"c9da8950194f4feb9f2fd94754dc919f","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v4513226cdae34746b4dedf0b4dfa099e1781791509496" integrity="sha512-ZE9pZaUXND66v380QUtch/5sE9tPFh2zg45pR2PB0CVkCtOREv2AJKkSidISWkysEuQ0EH8faUU5du78bx87UQ==" data-cf-beacon='{"version":"2024.11.0","token":"ab4456f8b0f94489bba81d5f7a45ed7b","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v4513226cdae34746b4dedf0b4dfa099e1781791509496" integrity="sha512-ZE9pZaUXND66v380QUtch/5sE9tPFh2zg45pR2PB0CVkCtOREv2AJKkSidISWkysEuQ0EH8faUU5du78bx87UQ==" data-cf-beacon='{"version":"2024.11.0","token":"ab4456f8b0f94489bba81d5f7a45ed7b","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v4513226cdae34746b4dedf0b4dfa099e1781791509496" integrity="sha512-ZE9pZaUXND66v380QUtch/5sE9tPFh2zg45pR2PB0CVkCtOREv2AJKkSidISWkysEuQ0EH8faUU5du78bx87UQ==" data-cf-beacon='{"version":"2024.11.0","token":"9d1ec46d970d42f48f5b328891ca4888","r":1}' crossorigin="anonymous"></script>
<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v3d52b47920f24c319d37e2661827c42b1787588026925" integrity="sha512-d9sL6GJLXn6fInD1+TVXhTcQOsmxeHfmHAvwGDIxp5TO+uo1fiWW7mHomMj4MLRlCsJDTqXzWLHJFFlPCEIj/A==" data-cf-beacon='{"version":"2024.11.0","token":"06c19229af0245f886b370f7a0c45847","r":1}' crossorigin="anonymous"></script>
<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v3d52b47920f24c319d37e2661827c42b1787588026925" integrity="sha512-d9sL6GJLXn6fInD1+TVXhTcQOsmxeHfmHAvwGDIxp5TO+uo1fiWW7mHomMj4MLRlCsJDTqXzWLHJFFlPCEIj/A==" data-cf-beacon='{"version":"2024.11.0","token":"b0c0a823960f45efb79d9ed205965565","r":1}' crossorigin="anonymous"></script>
<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v31edd6df95cf4e85bb4c19e7a9bdbcba1788362987495" integrity="sha512-iIg7k2xntmwu6/uSb5tpc/hySgZc4eoL31yB29W6tJFo2akwjPWcEqnCEdJvGexCL0KEQwVYv5BlowfhVz26hg==" data-cf-beacon='{"version":"2024.11.0","token":"84e65c49a91644c3a4476a99f80e0874","r":1,"spa":2}' crossorigin="anonymous"></script>
<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v31edd6df95cf4e85bb4c19e7a9bdbcba1788362987495" integrity="sha512-iIg7k2xntmwu6/uSb5tpc/hySgZc4eoL31yB29W6tJFo2akwjPWcEqnCEdJvGexCL0KEQwVYv5BlowfhVz26hg==" data-cf-beacon='{"version":"2024.11.0","token":"bfbba7c669994d869e848c5a8b8f7451","r":1,"spa":2}' crossorigin="anonymous"></script>
<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v31edd6df95cf4e85bb4c19e7a9bdbcba1788362987495" integrity="sha512-iIg7k2xntmwu6/uSb5tpc/hySgZc4eoL31yB29W6tJFo2akwjPWcEqnCEdJvGexCL0KEQwVYv5BlowfhVz26hg==" data-cf-beacon='{"version":"2024.11.0","token":"bf83155291f74a6a8120b3131b13f43e","r":1,"spa":2}' crossorigin="anonymous"></script>
<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/vce701823e78c4301a35b7e07294d87301792088814092" integrity="sha512-UKNk8GbxkABxKcXYuLkO16EleLGUkcvJWtpSNmExE0Too3ZaFAxTscNWEc1OdAv5/BikHawapSCaiD2XCmp+Bw==" data-cf-beacon='{"version":"2024.11.0","token":"4d8228892cdb42caba50789884bf09ef","r":1,"spa":2}' crossorigin="anonymous"></script>
</body>

</html>