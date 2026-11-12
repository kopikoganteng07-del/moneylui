<?php
/* fortune-vn: versi region Vietnam dari fortune moneylui (teks antarmuka bahasa Vietnam). Dibuat 12 Nov 2026. */
?><?php
/**
 * ---------------------------------------------------------
 * Template fortune (Bimasakti / Makmur, runtime Hoksiong).
 * Sumber: fortune.php (adapter "Project Pisang", HTML di dalam heredoc + token %%…%%).
 * Versi pemulihan: fortune_new.php, berdasarkan fortune_REKAP_PERUBAHAN.md
 * dan payload HTML dalam fortune.php.zip. Meta Pixel, reCAPTCHA, dan beacon
 * Cloudflare sumber dipulihkan. Runtime data.json dan schema otomatis tetap.
 * Skrip src="#" disimpan sebagai komentar karena isinya bukan JavaScript.
 * Alt kedua sumber disimpan sebagai data-original-alt agar alt tidak ganda.
 * Daftar lengkap perubahan: fortune_REKAP_PERUBAHAN.md.
 * CSS / JS / gambar tetap dari https://batik138-aset.pages.dev (keputusan 10 Nov 2026).
 * Membaca /data/data.json yang ditulis pipeline. Ganti template = ganti file ini.
 *
 * Kontrak data (sama dengan T1):
 *   per halaman : title, meta_description, h1, content_html, image_src,
 *                 canonical_url, amp_url, noindex, lang, lastmod, category, keyword
 *   homepage    : + logo_src, favicon_src, banner_src, cta_url, gsc_token (opsional)
 *
 * BANNER_TYPE: LANDSCAPE (banner satu gambar lebar penuh).
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
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="application-name" content="<?= h($namaSitus) ?>">
		
		
				
				
				
		<meta name="keywords" content="<?= h($namaSitus) ?>, <?= h($namaSitus) ?>, Slot, Mahjong, <?= h($namaSitus) ?> Login">
		
		<meta name="author" content="">
		
		
		
<!-- GLOBAL: loads on every pages -->

	<link href="https://batik138-aset.pages.dev/assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	

<link href="https://batik138-aset.pages.dev/assets/libs/font-awesome/css/all.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link href="https://batik138-aset.pages.dev/assets/libs/font-awesome/css/fontawesome-450.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link href="https://batik138-aset.pages.dev/assets/libs/jquery-ui/jquery-ui.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link href="https://batik138-aset.pages.dev/assets/libs/scroll-bar/jquery.mCustomScrollbar.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">


<link href="https://batik138-aset.pages.dev/assets/assets/css/fonts.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
	<link href="https://batik138-aset.pages.dev/assets/libs/font-awesome/css/all.min.css" rel="stylesheet">
	<link href="https://batik138-aset.pages.dev/assets/libs/font-awesome/css/fontawesome-450.min.css" rel="stylesheet">
	<link href="https://batik138-aset.pages.dev/assets/libs/jquery-ui/jquery-ui.css" rel="stylesheet">
	<link href="https://batik138-aset.pages.dev/assets/libs/scroll-bar/jquery.mCustomScrollbar.min.css" rel="stylesheet">
	<link href="https://batik138-aset.pages.dev/assets/assets/css/fonts.css" rel="stylesheet">
</noscript>



<!--- CSS SEMENTARA DEVELOPING UNTUK DIPINDAH --->
<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-sementara-d97d7c12da.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
	<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-sementara-d97d7c12da.css" rel="stylesheet">
</noscript>

<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/jquery-toast/css/jquery.toast.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
	<link href="https://batik138-aset.pages.dev/assets/libs/jquery-toast/css/jquery.toast.css" rel="stylesheet">
</noscript>
<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/sweetalert/sweetalert.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
	<link href="https://batik138-aset.pages.dev/assets/libs/sweetalert/sweetalert.css" rel="stylesheet">
</noscript>

	<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/daterangepicker/css/daterangepicker.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
	<noscript>
		<link href="https://batik138-aset.pages.dev/assets/libs/daterangepicker/css/daterangepicker.css" rel="stylesheet">
	</noscript>
	
<!-- HOME: loads on index.php -->
<!-- OWL Carousel 2 Core CSS -->
<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/owl-carousel/owl.carousel.css" as="style">
<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/owl-carousel/sa-owl-theme.css" as="style">
<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/owl-carousel/animate.min.css" as="style">

<link href="https://batik138-aset.pages.dev/assets/libs/owl-carousel/owl.carousel.css" rel="stylesheet">
<link href="https://batik138-aset.pages.dev/assets/libs/owl-carousel/sa-owl-theme.css" rel="stylesheet">
<link href="https://batik138-aset.pages.dev/assets/libs/owl-carousel/animate.min.css" rel="stylesheet">

<!-- Odometer Core CSS -->
<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/odometer/odometer-theme-minimal.css" as="style">

<link href="https://batik138-aset.pages.dev/assets/libs/odometer/odometer-theme-minimal.css" rel="stylesheet">
<!-- Main Theme Core CSS -->

<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-root-7cc0e5ad07.css" rel="stylesheet">
<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-280a70ccf5.css" rel="stylesheet">






		<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-FORMINBODY-7cc0e5ad07.css" rel="stylesheet">
				<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-FORMFLOATINGINBODY-7cc0e5ad07.css" rel="stylesheet">
				<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-OWLCAROUSEL-7cc0e5ad07.css" rel="stylesheet">
				<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-DATERANGEPICKER-7cc0e5ad07.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
		
		<noscript>
			<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-DATERANGEPICKER-7cc0e5ad07.css" rel="stylesheet">
		</noscript>
				<link href="https://batik138-aset.pages.dev/assets/assets/css/theme-TOGEL-7cc0e5ad07.css" rel="stylesheet">
				<link href="https://batik138-aset.pages.dev/assets/assets/css/theme_pid_home-339ab579e3.css" rel="stylesheet">
				
						


	<title><?= h($page['title'] ?? '') ?></title>
<meta name="description" content="<?= h($page['meta_description'] ?? '') ?>">
<meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow' ?>">
<link rel="canonical" href="<?= h($page['canonical_url'] ?? '') ?>">
<link rel="icon" href="<?= h($faviconSrc) ?>">
<link rel="apple-touch-icon" href="<?= h($faviconSrc) ?>">
<?php if (!empty($gscToken)): ?>
<meta name="google-site-verification" content="<?= h($gscToken) ?>">
<?php endif; ?>
<?php if ($adaAmp): ?>
<link rel="amphtml" href="<?= h($ampUrl) ?>">
<link rel="alternate" href="<?= h($ampUrl) ?>">
<link rel="alternate" href="<?= h($ampUrl) ?>" hreflang="vi-vn">
<link rel="alternate" href="<?= h($ampUrl) ?>" hreflang="vi">
<link rel="alternate" href="<?= h($ampUrl) ?>" hreflang="en">
<link rel="alternate" href="<?= h($ampUrl) ?>" hreflang="x-default">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:url" content="<?= h($page['canonical_url'] ?? '') ?>">
<meta property="og:title" content="<?= h($page['title'] ?? '') ?>">
<meta property="og:description" content="<?= h($page['meta_description'] ?? '') ?>">
<meta property="og:image" content="<?= h($ogAbs) ?>">
<meta property="og:site_name" content="<?= h($namaSitus) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($page['title'] ?? '') ?>">
<meta name="twitter:description" content="<?= h($page['meta_description'] ?? '') ?>">
<meta name="twitter:image" content="<?= h($ogAbs) ?>">
<style>
/* Judul seksi diubah dari h1 ke h2 (hanya satu h1 per halaman); gaya h1 tema disalin ke h2. */
.jackpot-play .title h2{font-size:3rem}
@media(max-width:575px){.jackpot-play .title h2{font-size:1.4em}}
.fun-demo-games .title-fun-demo-games h2{font-size:2.5rem;background:linear-gradient(180deg,#fffadf 0%,#f9df7a 15%,#d4a017 30%,#f9df7a 50%,#b8820a 65%,#f0c040 80%,#7a4e00 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0px 3px 3px rgba(0,0,0,0.9)) drop-shadow(0px 6px 10px rgba(0,0,0,0.7)) drop-shadow(0px -1px 1px rgba(255,230,120,0.5))}
.main-games .title-games h2{font-size:2rem}
</style>
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
                'logo' => ['@type' => 'ImageObject', 'url' => (strpos($logoSrc, 'http') === 0 ? $logoSrc : $origin . $logoSrc)]],
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
    ])),
]) ?>

</script>
</head>
	
	<body class=" homepage page-index" style="">

				
				
		<!-- back button native mobile -->
				
		<!-- DOWNLOAD ANDROID -->
<div class="d-sm-none top-download-apk">
	<div class="container">
		<div class="main-download-apk">
			<div class="logo-download-apk">
				<img class="img-fluid d-block" src="https://batik138-aset.pages.dev/assets/assets/images/icon/android-download-apk.webp">
			</div>
			
			<div class="content-download-apk">
				<p><strong><?= h($namaSitus) ?></strong> Ứng dụng di động</p>
				<!--p>Tải ngay!</p-->
			</div>
			
			<div class="action-download-apk">
				<a href="#" id="close-download-apk" class="close-download-apk" title="Đóng">x</a>
				
										<button class="btn btn-sm btn-main btn-capsule" onclick="document.location = '#';">Tải về</button>
									</div>
		</div>
	</div>
</div>

<header class="header-front pt-md-4 pt-sm-0">
	<div class="identity-header mb-sm-3 mt-sm-2 mb-md-4 mt-md-1">
		<div class="container">
			<div class="row logo-header-row align-items-center">
				<div class="col-xl-4 col-lg-3 col-12 logo-header-col">
					<div class="logo-header mt-sm-4 mt-md-0">
								<a class="d-table" href="/">
			<img class="img-fluid d-block" src="<?= h($logoSrc) ?>" alt="<?= h($namaSitus) ?>">
					</a>
							</div>
				</div>
				
				<div class="col-xl-8 col-lg-9 col-12 login-header-col">
				
									<div id="login-with-header" class="dropdown">
						<a href="#" role="button">
							Đăng nhập bằng&nbsp;
						</a>
						
						<!--a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
							Login with&nbsp;
						</a>
						
						<ul class="dropdown-menu bg-secondary">
							<li>
								<a class="dropdown-item" href="#">
									<i class="fa-regular fa-circle-user me-2"></i>Username
								</a>
							</li>
							
							<li>
								<a class="dropdown-item" href="#">
									<i class="fa-solid fa-phone me-2"></i>Số điện thoại
								</a>
							</li>
							
							<li>
								<a class="dropdown-item" href="#">
									<i class="fa-regular fa-envelope me-2"></i>Email
								</a>
							</li>
							
							<li>
								<a class="dropdown-item" href="#">
									<i class="fa-brands fa-google me-2"></i>Gmail
								</a>
							</li>
						</ul-->
					</div>
					
					<div class="login-header">
						<form class="" id="form_login_head" method="post" action="" autocomplete="off" novalidate>
							<input type="hidden" id="form_token_user_login_head" name="form_token_user_login_head" class="collect-this" value="">
							<input type="hidden" id="hmterm_user_login_head" name="hmterm_user_login_head" class="collect-this" value="">
							<input type="hidden" id="form_instance" name="form_instance" class="collect-this" value="login_head_one">
						
							<div class="col-12">
								<a class="btn btn-white-dark btn-sm flex-fill btn-google" href="" data-hmterm="user_login_with_google" onclick="user_login_with_google(); return false;"><img src="https://batik138-aset.pages.dev/assets/assets/images/icon/Google__logo-f67bbdbffc.webp">Google</a>
								<a class="btn btn-white-dark btn-sm flex-fill btn-google" href="<?= h($ctaUrl) ?>"><img src="https://batik138-aset.pages.dev/assets/assets/images/icon/Telegram__logo-f67bbdbffc.webp">Telegram<!--i class="fa fa-telegram fs-6" aria-hidden="true"></i> Telegram--></a>
							</div>
							
							<div class="col-12">
								<div class="input-group">
									<span class="input-group-text input-group-text-sm"><i class="fa-solid fa-user"></i></span>
									<div class="form-floating form-floating-sm" style="overflow: hidden;">
										<input type="text" class="form-control auto-lowercase collect-this" id="UsernameHead" name="emailorphonehead" placeholder="Tên đăng nhập / Email / Số điện thoại">
										<label for="UsernameHead">Tên đăng nhập / Email / Số điện thoại</label>
									</div>
								</div>
							</div>
							
							<!-- additional validator fields -->
															<input type="hidden" class="formvalidator collect-this" id="FormValidatorTop" name="formvalidator" value="1">
															
							<div class="col-12">
								<div class="input-group">
									<span class="input-group-text input-group-text-sm"><i class="fa-solid fa-lock"></i></span>
									<div class="form-floating form-floating-sm">
										<input type="password" class="form-control with-eye collect-this" id="PasswordHead" name="passwordhead" placeholder="Mật khẩu" onchange="allowPassword(this);" onkeyup="allowPassword(this);" onpaste="allowPassword(this);">
										<label for="PasswordHead">Mật khẩu</label>
									</div>
									<span class="input-group-text-sm bg-transparent password-eye-form-floating" id="PasswordHead-addon">
										<i class="fa fa-eye-slash" id="PasswordHeadEye" onclick="seehidepassword('#PasswordHead','#'+this.id);"></i>
									</span>
								</div>
								<small id="passwordHelp" class="form-text text-muted" style="font-size: 0.6em;"><a href="#">Quên mật khẩu?</a></small>
							</div>
							
							<div class="col-12 d-flex mt-sm-2 mt-md-0 flex-column" style="height: 2em;">
								<div class="col-12 d-flex">
									<button type="submit" class="btn btn-main btn-sm flex-fill" data-hmterm="user_login_head" 
									
											data-mainsf1="form_login_head" 
											data-mainsf2="submit_form_login" 
											data-mainsf3="bunker/pageload/ajax/ajax_login_form_head_login" 
											data-mainsf4="false" 
											data-mainsf5="true" 
									
										onclick="return false;" disabled>Đăng nhập</button>
								
									<!--
									<button type="submit" class="btn btn-main btn-sm flex-fill" onclick="SubmitForm('form_login_head','submit_form_login','ajax_login_form_head_login',false,true); return false;" disabled>Đăng nhập</button>
									-->
									<button type="button" class="btn btn-main-dark btn-sm flex-fill ms-md-1 ms-lg-2" onclick="window.location = '<?= h($ctaUrl) ?>'; return false;">Đăng ký</button>
								</div>
																<div class="col-12 mt-2">
									<!--download apk-->
																			<button type="button" class="btn btn-sm btn-warning btn-shortcut w-100" onclick="document.location = '#';">Download APK</button>
																			
								</div>
															</div>
						</form>	
						
												
												
					</div>
								
				</div>
			</div>
		</div>
	</div>
	
	<!-- menu -->
<style>
/* Main navigation contains only Beranda + data.json posts; keep mobile login sidebar intact. */
.navbar-nav-slide > li.nav-item:not(.dynamic-post-menu):not(.d-md-none){display:none!important;}
.navbar-nav-slide > .dynamic-post-menu{display:flex!important;flex:0 0 auto!important;}
.navbar-nav-slide > .dynamic-post-menu > .nav-link{white-space:nowrap!important;}
</style>
	
<div class="menu-header-wrap">
	<div class="menu-header menu-slider line-border line-border-full">
		<div class="container">
						<!-- Navigation Navbar Slide -->
			<button class="navbar-slide-control prev"><i class="fas fa-angle-left"></i></button>
			<button class="navbar-slide-control next"><i class="fas fa-angle-right"></i></button>
						
			<nav class="navbar navbar-expand-md navbar-slide">
				
				<div class="login-register-aside-navbar-toggler d-none">
					<a href="<?= h($ctaUrl) ?>" class="btn btn-main btn-sm flex-fill">Đăng nhập</a>
					<a href="<?= h($ctaUrl) ?>" class="btn btn-main-dark btn-sm flex-fill ms-md-1 ms-lg-1">Đăng ký</a>
				</div>
			
				<div class="m-auto d-md-none navbar-toggler-wrap">
					<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
						<span class="navbar-toggler-icon"></span>
					</button>
				</div>
				
				<div class="offcanvas offcanvas-start bg-offcanvas" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
					<div class="offcanvas-header pt-2 pb-1">
								<a class="navbar-brand d-table" href="/">
			<img src="<?= h($logoSrc) ?>" width="1774" height="887" srcset="" class="img-fluid d-block" data-original-alt="logo_batik--1789461757" alt="<?= h($namaSitus) ?>" style="width: 100%; height: auto; aspect-ratio: 2;">		</a>
								
						<button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
					</div>
					
					
					<div class="offcanvas-body p-0">
						<ul class="navbar-nav navbar-nav-slide justify-content-center align-items-center flex-grow-1">
							<li class="nav-item d-md-none">
								
								
								
								
								<div class="login-sidebar">
								
									<div class="login-header">
										<form class="" id="form_login_head" method="post" action="" autocomplete="off" novalidate>
											<input type="hidden" id="form_token_user_login_head" name="form_token_user_login_head" class="collect-this" value="">
											<input type="hidden" id="hmterm_user_login_head" name="hmterm_user_login_head" class="collect-this" value="">
											<input type="hidden" id="form_instance" name="form_instance" class="collect-this" value="login_head_side_one">
											
											<div class="row">
												<div class="col-12">
													<small id="passwordHelp" class="form-text text-light" style="font-size: 0.8em;">Đăng nhập bằng</small>
												</div>
												<div class="col-6">
													<a class="btn btn-white-dark btn-sm flex-fill btn-google" data-hmterm="user_login_with_google" href="" onclick="user_login_with_google(); return false;"><img src="https://batik138-aset.pages.dev/assets/assets/images/icon/Google__logo-f67bbdbffc.webp">Google</a>
												</div>
												<div class="col-6">
													<a class="btn btn-white-dark btn-sm flex-fill btn-google" href="<?= h($ctaUrl) ?>"><img src="https://batik138-aset.pages.dev/assets/assets/images/icon/Telegram__logo-f67bbdbffc.webp">Telegram<!--i class="fa fa-telegram fs-6" aria-hidden="true"></i> Telegram --></a>
												</div>
											</div>
											
											<div class="or-separator my-4">
												<span>HOẶC</span>
											</div>
											
											<div class="col-12">
												<div class="input-group">
													<span class="input-group-text input-group-text-sm"><i class="fa-solid fa-user"></i></span>
													<div class="form-floating form-floating-sm">
														<input type="text" class="form-control auto-lowercase collect-this" id="UsernameHead" name="emailorphonehead" placeholder="Tên đăng nhập, Email hoặc Số điện thoại">
														<label for="UsernameHead">Tên đăng nhập, Email hoặc Số điện thoại</label>
													</div>
												</div>
											</div>
											
											<!-- additional validator fields -->
																							<input type="hidden" class="formvalidator collect-this" id="FormValidatorTop" name="formvalidator" value="1">
																							
											<div class="col-12">
												<div class="input-group">
													<span class="input-group-text input-group-text-sm"><i class="fa-solid fa-lock"></i></span>
													<div class="form-floating form-floating-sm">
														<input type="password" class="form-control with-eye collect-this" id="PasswordHead" name="passwordhead" placeholder="Mật khẩu">
														<label for="PasswordHead">Mật khẩu</label>
													</div>
													<span class="input-group-text-sm bg-transparent password-eye-form-floating" id="PasswordHead-addon">
														<i class="fa fa-eye-slash" id="PasswordHeadEye" onclick="seehidepassword('#PasswordHead','#'+this.id);"></i>
													</span>
												</div>
												<small id="passwordHelp" class="form-text" style="font-size: 0.7em;"><a href="#">Quên mật khẩu?</a></small>
											</div>
											
											<div class="col-12 d-flex mt-sm-2 mt-md-0" style="height: 2em;">
												<button type="submit" class="btn btn-main btn-sm flex-fill" data-hmterm="user_login_head"
												
													data-mainsf1="form_login_head" 
													data-mainsf2="submit_form_login" 
													data-mainsf3="bunker/pageload/ajax/ajax_login_form_head_login" 
													data-mainsf4="false" 
													data-mainsf5="true"
												
													onclick="return false;" disabled>Đăng nhập</button>
											
												<!--
												<button type="submit" class="btn btn-main btn-sm flex-fill" onclick="SubmitForm('form_login_head','submit_form_login','ajax_login_form_head_login',false,true); return false;" disabled>Đăng nhập</button>
												-->
												<button type="button" class="btn btn-main-dark btn-sm flex-fill ms-md-1 ms-lg-3" onclick="window.location = '<?= h($ctaUrl) ?>'; return false;">Đăng ký</button>
											</div>
										</form>	
										
																				
																				
									</div>
									
								</div>
								
								
								
								
								
								
							</li>
<li class="nav-item dynamic-post-menu"><a class="nav-link<?= $isHomepage ? ' active' : '' ?>"<?= $isHomepage ? ' aria-current="page"' : '' ?> href="/">Trang chủ</a></li><?php foreach ($allPosts as $slug => $item): $aktif = !$isHomepage && (string)$slug === (string)$id; ?><li class="nav-item dynamic-post-menu"><a class="nav-link<?= $aktif ? ' active' : '' ?>"<?= $aktif ? ' aria-current="page"' : '' ?> href="/<?= h($slug) ?>"><?= h($labelPost($slug, $item)) ?></a></li><?php endforeach; ?>

								<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			HOT GAME		</a>
		<div class="dropdown-menu megamenu hot_game bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">

												
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('SA','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="5 Dragon" alt="h5g_5dragonslot" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>5 Dragon</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('fiveDragonKings','4',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings-7cc0e5ad07.webp" width="240" height="170" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="5 Dragon Kings" alt="fiveDragonKings" style="width: 100%; height: auto; aspect-ratio: 1.4117647058824;"></div>
											<div class="item_game_content">
												<h5>5 Dragon Kings</h5>
												<p>AFB GAMING</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('anubis-wrath','7',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/anubis-wrath-7cc0e5ad07.webp" width="360" height="270" srcset="" class="load-when-upfold" alt="Anubis Wrath" alt="anubis-wrath" style="width: 100%; height: auto; aspect-ratio: 1.3333333333333;"></div>
											<div class="item_game_content">
												<h5>Anubis Wrath</h5>
												<p>PG SOFT</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('asgardian-rs','7',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/asgardian-rs-7cc0e5ad07.webp" width="360" height="259" srcset="" class="load-when-upfold" alt="Asgardian Rising" alt="asgardian-rs" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Asgardian Rising</h5>
												<p>PG SOFT</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('vs5aztecgems','5',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems-7cc0e5ad07.webp" width="283" height="204" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Aztec Gems" alt="vs5aztecgems" style="width: 100%; height: auto; aspect-ratio: 1.3872549019608;"></div>
											<div class="item_game_content">
												<h5>Aztec Gems</h5>
												<p>PRAGMATIC SLOT</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('vs9aztecgemsdx','5',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Aztec Gems Deluxe" alt="vs9aztecgemsdx" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;"></div>
											<div class="item_game_content">
												<h5>Aztec Gems Deluxe</h5>
												<p>PRAGMATIC SLOT</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('vswaysaztec','5',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysaztec-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Aztec Gems Megaways" alt="vswaysaztec" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;"></div>
											<div class="item_game_content">
												<h5>Aztec Gems Megaways</h5>
												<p>PRAGMATIC SLOT</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('BQ','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Q Live" alt="h5g_bandarqiu" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Bandar Q Live</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('BS','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Sakong" alt="h5g_bandarsakong" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Bandar Sakong</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('MM','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bola Tangkas" alt="h5g_bolatangkas" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Bola Tangkas</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('vs20fatbook','5',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20fatbook-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Book of Monsters" alt="vs20fatbook" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;"></div>
											<div class="item_game_content">
												<h5>Book of Monsters</h5>
												<p>PRAGMATIC SLOT</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('cai-shen-wins','7',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/cai-shen-wins-7cc0e5ad07.webp" width="330" height="225" srcset="" class="load-when-upfold" alt="Cai Shen Wins" alt="cai-shen-wins" style="width: 100%; height: auto; aspect-ratio: 1.4666666666667;"></div>
											<div class="item_game_content">
												<h5>Cai Shen Wins</h5>
												<p>PG SOFT</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('SB','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Cash Cove" alt="h5g_cashcoveslot" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Cash Cove</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('cocktail-nite','7',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/cocktail-nite-7cc0e5ad07.webp" width="360" height="270" srcset="" class="load-when-upfold" alt="Cocktail Nights" alt="cocktail-nite" style="width: 100%; height: auto; aspect-ratio: 1.3333333333333;"></div>
											<div class="item_game_content">
												<h5>Cocktail Nights</h5>
												<p>PG SOFT</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('QQ','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Domino 99" alt="h5g_domino99" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Domino 99</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('dragon-hatch','7',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/dragon-hatch-7cc0e5ad07.webp" width="360" height="239" srcset="" class="load-when-upfold" alt="Dragon Hatch" alt="dragon-hatch" style="width: 100%; height: auto; aspect-ratio: 1.5062761506276;"></div>
											<div class="item_game_content">
												<h5>Dragon Hatch</h5>
												<p>PG SOFT</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														<div class="col-12">
																<div class="see-all-games line-border-top line-border-full">
									<a class="btn btn-main-dark btn-see-all d-table mb-3" href="#">Xem tất cả HOT GAME</a>
								</div>
							</div>
							
				</div>
			</div>
		</div>
	</li>
	
	<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			SLOT		</a>
		<div class="dropdown-menu megamenu slot bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-1_43" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/1_43-bc4a1838a9.webp" alt="PG SOFT">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-1_41" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/1_41-bc4a1838a9.webp" alt="PRAGMATIC PLAY POP">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_58" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_58-bc4a1838a9.webp" alt="JILI">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_47" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_47-bc4a1838a9.webp" alt="HABANERO">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_104" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_104-bc4a1838a9.webp" alt="JokerGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_109" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_109-bc4a1838a9.webp" alt="MicroGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_124" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_124-bc4a1838a9.webp" alt="Netent">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_71" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_71-bc4a1838a9.webp" alt="PLAYTECH">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_57" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_57-bc4a1838a9.webp" alt="CQ9">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_128" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_128-bc4a1838a9.webp" alt="KAGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_156" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_156-bc4a1838a9.webp" alt="HacksawGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_126" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_126-bc4a1838a9.webp" alt="NoLimitCity">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_125" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_125-bc4a1838a9.webp" alt="BigTimeGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_66" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_66-bc4a1838a9.webp" alt="PNG">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_54" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_54-bc4a1838a9.webp" alt="RED TIGER">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_64" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_64-bc4a1838a9.webp" alt="SPADE GAMING">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_140" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_140-bc4a1838a9.webp" alt="DragoonSoft">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_56" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_56-bc4a1838a9.webp" alt="JDB">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>

										<div class="col-12">
						<div class="see-all-games line-border-top line-border-full">
														<a class="btn btn-main-dark btn-see-all d-table mb-3" href="#">Xem tất cả SLOT</a>
						</div>
					</div>
									</div>
			</div>
		</div>
	</li>


	<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			SPORTS		</a>
		<div class="dropdown-menu megamenu sports bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_94" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/4_94-bc4a1838a9.webp" alt="SBO Sports">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_95" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/4_95-bc4a1838a9.webp" alt="Saba Sports">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_97" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/4_97-bc4a1838a9.webp" alt="BTiSports">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-1_40" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/1_40-bc4a1838a9.webp" alt="AFB SPORT">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-1_42" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/1_42-bc4a1838a9.webp" alt="GGCS">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_100" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/4_100-bc4a1838a9.webp" alt="APGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>

									</div>
			</div>
		</div>
	</li>

							<!--
							<li class="nav-item">
								<a class="nav-link" href="#">SPORTBOOK</a>
							</li>
							-->
							
	<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			LIVE CASINO		</a>
		<div class="dropdown-menu megamenu live_casino bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_77" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_77-bc4a1838a9.webp" alt="EvolutionGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-1_41101" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/1_41101-bc4a1838a9.webp" alt="PRAGMATIC CASINO">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_74" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_74-bc4a1838a9.webp" alt="SexyGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_85" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_85-bc4a1838a9.webp" alt="DreamGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_63" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/2_63-bc4a1838a9.webp" alt="ALLBET">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_82" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_82-bc4a1838a9.webp" alt="OGLive">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_92" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_92-bc4a1838a9.webp" alt="Ezugi">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_75" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_75-bc4a1838a9.webp" alt="IONLC">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_73" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_73-bc4a1838a9.webp" alt="BigGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_72" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_72-bc4a1838a9.webp" alt="WanMei">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_81" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_81-bc4a1838a9.webp" alt="YeeBet">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_86" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_86-bc4a1838a9.webp" alt="PlayAce">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_89" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_89-bc4a1838a9.webp" alt="ViaCasino">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_84" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_84-bc4a1838a9.webp" alt="PlayTechLiveCasino">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-1_33" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/1_33-bc4a1838a9.webp" alt="GLXPLAY">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_60" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/2_60-bc4a1838a9.webp" alt="WINFINITY">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_90" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_90-bc4a1838a9.webp" alt="KissGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-2_46" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/2_46-bc4a1838a9.webp" alt="SA GAMING">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>

										<div class="col-12">
						<div class="see-all-games line-border-top line-border-full">
														<a class="btn btn-main-dark btn-see-all d-table mb-3" href="#">Xem tất cả LIVE CASINO</a>
						</div>
					</div>
									</div>
			</div>
		</div>
	</li>

	<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			BOLATANGKAS		</a>
		<div class="dropdown-menu megamenu bolatangkas bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">

												
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('MM','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bola Tangkas" alt="h5g_bolatangkas" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Bola Tangkas</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('HR','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Happy Royal" alt="h5g_happyroyal" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Happy Royal</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
							
				</div>
			</div>
		</div>
	</li>
		<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			P2P GAME		</a>
		<div class="dropdown-menu megamenu p2p_game bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">

												
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('AA','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Adu Q Live" alt="h5g_aduqiu" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Adu Q Live</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('BD','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Dadu" alt="h5g_bandardadu" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Bandar Dadu</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('BQ','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Q Live" alt="h5g_bandarqiu" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Bandar Q Live</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('BS','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Sakong" alt="h5g_bandarsakong" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Bandar Sakong</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('CB','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Capsa Banting" alt="h5g_capsabanting" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Capsa Banting</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('QQ','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Domino 99" alt="h5g_domino99" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Domino 99</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('GP','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple-7cc0e5ad07.webp" width="360" height="255" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Gaple" alt="h5g_gaple" style="width: 100%; height: auto; aspect-ratio: 1.4117647058824;"></div>
											<div class="item_game_content">
												<h5>Gaple</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
														
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('PK','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Texas Poker" alt="h5g_texaspoker" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>Texas Poker</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
							
				</div>
			</div>
		</div>
	</li>
		<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			TOTO		</a>
		<div class="dropdown-menu megamenu toto bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">

												
							<div class="col-sm-6 col-md-4 col-lg-3 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
								<div class="item_game_menu_wrap with_details mb-3">
																			<a href="" onclick="play_games('GL','3',true); return false;">
											<div class="item_game_image"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="GLX Toto" alt="h5g_glxtoto" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;"></div>
											<div class="item_game_content">
												<h5>GLX Toto</h5>
												<p>GLXPLAY</p>
											</div>
											<div class="btn_wrapper">
												<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
											</div>
										</a>
																				
									
								</div>
							</div>
							
				</div>
			</div>
		</div>
	</li>
	
	<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			FISHING		</a>
		<div class="dropdown-menu megamenu fishing bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_128" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/fishing/4_128-bc4a1838a9.webp" alt="KAGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_118" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/fishing/4_118-bc4a1838a9.webp" alt="568winGames">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" onclick="play_games('crazyFishing','4',true); return false;">
									<div class="item_game_image">
										<img class="img-fluid d-block prv-1_1" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/fishing/1_1-bc4a1838a9.webp" alt="AFB GAMING">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_140" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/fishing/4_140-bc4a1838a9.webp" alt="DragoonSoft">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" onclick="play_games('568_1_5','10',true); return false;">
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_73" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/fishing/4_73-bc4a1838a9.webp" alt="BigGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_150" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/fishing/4_150-bc4a1838a9.webp" alt="Fastspin">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_112" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/fishing/4_112-bc4a1838a9.webp" alt="YGR">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_107" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/fishing/4_107-bc4a1838a9.webp" alt="FunkyGames">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>

									</div>
			</div>
		</div>
	</li>


	<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			COCK FIGHTING		</a>
		<div class="dropdown-menu megamenu cock_fighting bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" onclick="play_games('568_1_1070','10',true); return false;">
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_101" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/cockfight/4_101-bc4a1838a9.webp" alt="WS168Cockfighting">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>

									</div>
			</div>
		</div>
	</li>


	<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			OTHER		</a>
		<div class="dropdown-menu megamenu other bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_109" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_109-bc4a1838a9.webp" alt="MicroGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_156" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_156-bc4a1838a9.webp" alt="HacksawGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_108" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_108-bc4a1838a9.webp" alt="Yggdrasil">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" onclick="play_games('568_1_1077','10',true); return false;">
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_148" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_148-bc4a1838a9.webp" alt="Aviatrix">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_104" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_104-bc4a1838a9.webp" alt="JokerGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_121" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_121-bc4a1838a9.webp" alt="KingMidas">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_117" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_117-bc4a1838a9.webp" alt="Rich88">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" onclick="play_games('568_86_1089','10',true); return false;">
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_155" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_155-bc4a1838a9.webp" alt="RoyalSlotGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_140" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_140-bc4a1838a9.webp" alt="DragoonSoft">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_128" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_128-bc4a1838a9.webp" alt="KAGaming">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_107" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_107-bc4a1838a9.webp" alt="FunkyGames">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_135" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_135-bc4a1838a9.webp" alt="Phoenix7">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_142" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_142-bc4a1838a9.webp" alt="Nextspin">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_151" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_151-bc4a1838a9.webp" alt="Apollo">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_147" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_147-bc4a1838a9.webp" alt="PoggiPlay">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_112" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_112-bc4a1838a9.webp" alt="YGR">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-4_136" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/4_136-bc4a1838a9.webp" alt="CC88">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>


						<div class="col-sm-6 col-md-3 col-lg-2 mb-3 mb-lg-0 d-sm-nonex d-md-blockx">
							<div class="item_game_menu_wrap with_details menu_providers  mb-3">
								<!--a href=""-->
								<a href="#" >
									<div class="item_game_image">
										<img class="img-fluid d-block prv-1_1" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/other/1_1-bc4a1838a9.webp" alt="AFB GAMING">
									</div>
																		<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Truy cập</button>
									</div>
																	</a>
							</div>
						</div>

										<div class="col-12">
						<div class="see-all-games line-border-top line-border-full">
														<a class="btn btn-main-dark btn-see-all d-table mb-3" href="#">Xem tất cả OTHER</a>
						</div>
					</div>
									</div>
			</div>
		</div>
	</li>

																					
							<li class="nav-item d-none d-sm-block">
								<a class="nav-link" href="#">PROMO</a>
							</li>
								<li class="nav-item dropdown has-megamenu open-onhover d-nonex d-md-blockx">
		<a class="nav-link no-chevron dropdown-toggle" href="" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			TOTO RESULT		</a>
		<div class="dropdown-menu megamenu bg-glass" aria-labelledby="navbarDropdown" role="menu">
			<div class="container">
				<div class="row justify-content-center mt-3">
				
					<div class="col-sm-6 col-md-4 col-lg-4 mb-3 mb-lg-0 d-nonex d-md-blockx">
						<div class="item_game_menu_wrap mb-3">
							<a href="#">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Indonesia Siang" alt="indonesia_a" style="width: 100%; height: auto; aspect-ratio: 2.75;">								<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-sm-6 col-md-4 col-lg-4 mb-3 mb-lg-0 d-nonex d-md-blockx">
						<div class="item_game_menu_wrap mb-3">
							<a href="#">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Indonesia Sore" alt="indonesia_b" style="width: 100%; height: auto; aspect-ratio: 2.75;">								<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-sm-6 col-md-4 col-lg-4 mb-3 mb-lg-0 d-nonex d-md-blockx">
						<div class="item_game_menu_wrap mb-3">
							<a href="#">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/korea-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/korea--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/korea--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/korea--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/korea--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/korea--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Korea" alt="korea" style="width: 100%; height: auto; aspect-ratio: 2.75;">								<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-sm-6 col-md-4 col-lg-4 mb-3 mb-lg-0 d-nonex d-md-blockx">
						<div class="item_game_menu_wrap mb-3">
							<a href="#">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Singapore" alt="singapore" style="width: 100%; height: auto; aspect-ratio: 2.75;">								<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-sm-6 col-md-4 col-lg-4 mb-3 mb-lg-0 d-nonex d-md-blockx">
						<div class="item_game_menu_wrap mb-3">
							<a href="#">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/china-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/china--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/china--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/china--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/china--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/china--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="China" alt="china" style="width: 100%; height: auto; aspect-ratio: 2.75;">								<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-sm-6 col-md-4 col-lg-4 mb-3 mb-lg-0 d-nonex d-md-blockx">
						<div class="item_game_menu_wrap mb-3">
							<a href="#">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Singapore 49" alt="singapore-49" style="width: 100%; height: auto; aspect-ratio: 2.75;">								<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
								</div>
							</a>
						</div>
					</div>
				
				</div>
			</div>
		</li>
	</li>
								<li class="nav-item d-none d-sm-block">
								<a class="nav-link" href="#">REFERRAL</a>
							</li>

														<li class="nav-item">
								<a class="nav-link nav-link-image px-3" href="<?= h($ctaUrl) ?>" target="_blank">
									<img src="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-rtp-7cc0e5ad07.webp">
								</a>
							</li>
								

							<!--
							<li class="nav-item">
								<a class="nav-link" href="referral.php">Giới thiệu bạn bè</a>
							</li>
							<li class="nav-item dropdown open-onhover">
								<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
									Rules
								</a>
								<ul class="dropdown-menu bg-glass-white">
									<li><a class="dropdown-item" href="rules-poker.php">Rules Poker</a></li>
									<li><a class="dropdown-item" href="rules-domino99.php">Rules Domino 99</a></li>
									<li><a class="dropdown-item" href="rules-tangkas.php">Rules Tangkas</a></li>
								</ul>
							</li>
							<li class="nav-item">
								<a class="nav-link" href="download.php">Mobile</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" href="help.php">Trợ giúp</a>
							</li>
							-->
						</ul>
					</div>
					
				</div>
			</nav>
		</div>
	</div>
</div>	
	<!-- running text -->
		<div class="running-news running-news-front">
		<div class="container">
			<div class="row"><div class="col-12">
				<div class="input-group">
					<span class="input-group-text ps-2 ps-sm-0"><i class="d-none d-sm-block">INFO</i><i class="fa fa-info-circle d-block d-sm-none"></i></span>
					<div class="form-control pe-0">
						<div class="bg-marquee">
							<div class="marquee">
								<marquee class="SITEAPI" method="message" onmouseout="scrollDelay=50" onmouseover="scrollDelay=1000" scrolldelay="50" scrollamount="3">
									Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hãy tham gia cùng chúng tôi tại <?= h($namaSitus) ?> - CASINO SLOT TRỰC TUYẾN CHÍNH THỨC uy tín tại Việt Nam với đa dạng trò chơi và phương thức thanh toán đầy đủ!								</marquee>
							</div>
						</div>
					</div>
				</div>
			</div></div>
		</div>
	</div>
		
</header>

		


		<!-- above this, is header -->
				<!-- Main Content -->
		<main>
		
<?php if ($heroImg !== ''): ?>
<section class="main-slider line-border line-border-left line-border-w75 line-border-h3 line-border-zi2 extra-mb-mobile" style="width:100%;height:auto"><div id="main_slider" class="owl-carousel owl-pagination-true sa_owl_theme"><div class="item"><img src="<?= h($heroImg) ?>" class="img-fluid d-block w-100" fetchpriority="high" alt="<?= h($page['h1'] ?? $namaSitus) ?>" style="width:100%;height:auto"></div></div></section>
<?php endif; ?>
					
			<div class="row d-sm-none mobile-register-button">
				<div class="col-6 gx-0">
					<a href="<?= h($ctaUrl) ?>" class="btn btn-main-dark w-100 btn-mrb mrb-signup"><i class="fa fa-address-card me-2" aria-hidden="true"></i>ĐĂNG KÝ</a>
				</div>
				<div class="col-6 gx-0">
					<a href="<?= h($ctaUrl) ?>" class="btn btn-main w-100 btn-mrb mrb-signin"><i class="fa fa-sign-in me-2" aria-hidden="true"></i>ĐĂNG NHẬP</a>
				</div>
			</div>
			
			<!-- FOR VERIFICATION OAUTH PURPOSE, HIDE WHEN DONE -->
						
			
						
			<section class="d-sm-none mobile-slider-menu">
				<div class="container">
					<div class="main-slider-menu line-border line-border-top line-border-full">
						<div class="wrapper-slider-menu">
							<div class="item-slider-menu">
								<a href="" class="toggle-slider-menu" data-target="hotgame">
									<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-hot-game-7cc0e5ad07.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
									<div class="text">Hot Game</div>
								</a>
							</div>
							
							<div class="item-slider-menu">
								<a href="" class="toggle-slider-menu" data-target="slot">
									<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-slot-7cc0e5ad07.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
									<div class="text">Slot</div>
								</a>
							</div>
							
							<div class="item-slider-menu">
								<a href="" class="toggle-slider-menu" data-target="sportsbook">
									<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-sportsbook-7cc0e5ad07.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
									<div class="text">Sports</div>
								</a>
							</div>
							
							<div class="item-slider-menu">
								<a href="" class="toggle-slider-menu" data-target="livecasino">
									<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-live-casino-7cc0e5ad07.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
									<div class="text">Live Casino</div>
								</a>
							</div>
							
							<div class="item-slider-menu">
								<a href="" class="toggle-slider-menu" data-target="bolatangkas">
									<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-bolatangkas-7cc0e5ad07.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
									<div class="text">Bolatangkas</div>
								</a>
							</div>

							<div class="item-slider-menu">
								<a href="" class="toggle-slider-menu" data-target="p2pgame">
									<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-p2pgame-7cc0e5ad07.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
									<div class="text">P2P Game</div>
								</a>
							</div>
							
							<div class="item-slider-menu">
								<a href="" class="toggle-slider-menu" data-target="totogame">
									<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-toto-7cc0e5ad07.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
									<div class="text">Toto</div>
								</a>
							</div>

							<div class="item-slider-menu">
								<a href="" class="toggle-slider-menu" data-target="totoresult">
									<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-totoresult-7cc0e5ad07.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
									<div class="text">Toto Result</div>
								</a>
							</div>
						</div>
						
						<!-- navigation -->
						<button class="nav-slider-menu prev"><i class="fa-solid fa-angle-left"></i></button>
						<button class="nav-slider-menu next"><i class="fa-solid fa-angle-right"></i></button>
					</div>
					
					<div class="list-game-toggle" id="hotgame" style="display: none;">
								<div class="row justify-content-center mt-3">

								<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('SA','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_5dragonslot--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="5 Dragon" alt="h5g_5dragonslot" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('fiveDragonKings','4',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings-7cc0e5ad07.webp" width="240" height="170" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/afb/fiveDragonKings--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="5 Dragon Kings" alt="fiveDragonKings" style="width: 100%; height: auto; aspect-ratio: 1.4117647058824;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('anubis-wrath','7',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/anubis-wrath-7cc0e5ad07.webp" width="360" height="270" srcset="" class="load-when-upfold" alt="Anubis Wrath" alt="anubis-wrath" style="width: 100%; height: auto; aspect-ratio: 1.3333333333333;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('asgardian-rs','7',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/asgardian-rs-7cc0e5ad07.webp" width="360" height="259" srcset="" class="load-when-upfold" alt="Asgardian Rising" alt="asgardian-rs" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('vs5aztecgems','5',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems-7cc0e5ad07.webp" width="283" height="204" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs5aztecgems--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Aztec Gems" alt="vs5aztecgems" style="width: 100%; height: auto; aspect-ratio: 1.3872549019608;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('vs9aztecgemsdx','5',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs9aztecgemsdx--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Aztec Gems Deluxe" alt="vs9aztecgemsdx" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('vswaysaztec','5',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysaztec-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Aztec Gems Megaways" alt="vswaysaztec" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('BQ','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Q Live" alt="h5g_bandarqiu" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('BS','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Sakong" alt="h5g_bandarsakong" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('MM','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bola Tangkas" alt="h5g_bolatangkas" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('vs20fatbook','5',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20fatbook-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Book of Monsters" alt="vs20fatbook" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('cai-shen-wins','7',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/cai-shen-wins-7cc0e5ad07.webp" width="330" height="225" srcset="" class="load-when-upfold" alt="Cai Shen Wins" alt="cai-shen-wins" style="width: 100%; height: auto; aspect-ratio: 1.4666666666667;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('SB','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_cashcoveslot--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Cash Cove" alt="h5g_cashcoveslot" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('cocktail-nite','7',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/cocktail-nite-7cc0e5ad07.webp" width="360" height="270" srcset="" class="load-when-upfold" alt="Cocktail Nights" alt="cocktail-nite" style="width: 100%; height: auto; aspect-ratio: 1.3333333333333;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('QQ','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Domino 99" alt="h5g_domino99" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('dragon-hatch','7',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pgsoft/dragon-hatch-7cc0e5ad07.webp" width="360" height="239" srcset="" class="load-when-upfold" alt="Dragon Hatch" alt="dragon-hatch" style="width: 100%; height: auto; aspect-ratio: 1.5062761506276;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-12 relative mt-3 pt-3 line-border-top line-border-full">
												<a class="btn btn-main-dark btn-see-all d-table mb-3" href="#">Xem tất cả </a>
					</div>
					
		</div>
						</div>
					
					<div class="list-game-toggle" id="slot" style="display: none;">
												
				<div class="row justify-content-center">


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-1_43" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/1_43-cc5fd325af.webp" alt="PG SOFT">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-1_41" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/1_41-cc5fd325af.webp" alt="PRAGMATIC PLAY POP">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_58" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_58-cc5fd325af.webp" alt="JILI">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_47" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_47-cc5fd325af.webp" alt="HABANERO">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_104" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_104-cc5fd325af.webp" alt="JokerGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_109" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_109-cc5fd325af.webp" alt="MicroGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_124" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_124-cc5fd325af.webp" alt="Netent">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_71" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_71-cc5fd325af.webp" alt="PLAYTECH">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_57" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_57-cc5fd325af.webp" alt="CQ9">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_128" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_128-cc5fd325af.webp" alt="KAGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_156" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_156-cc5fd325af.webp" alt="HacksawGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_126" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_126-cc5fd325af.webp" alt="NoLimitCity">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_125" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_125-cc5fd325af.webp" alt="BigTimeGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_66" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_66-cc5fd325af.webp" alt="PNG">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_54" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_54-cc5fd325af.webp" alt="RED TIGER">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_64" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_64-cc5fd325af.webp" alt="SPADE GAMING">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_140" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/4_140-cc5fd325af.webp" alt="DragoonSoft">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" >
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_56" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/slot/2_56-cc5fd325af.webp" alt="JDB">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


										<div class="col-12">
						<div class="relative mt-3 pt-3 line-border-top line-border-full">
														<a class="btn btn-main-dark btn-see-all d-table mb-3" href="#">Xem tất cả </a>
						</div>
					</div>
					
				</div>

					</div>
					
					<div class="list-game-toggle" id="sportsbook" style="display: none;">
												
				<div class="row justify-content-center">


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_-1','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_94" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/4_94-cc5fd325af.webp" alt="SBO Sports">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_44','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_95" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/4_95-cc5fd325af.webp" alt="Saba Sports">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_1022','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_97" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/4_97-cc5fd325af.webp" alt="BTiSports">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('afb88','2',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-1_40" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/1_40-cc5fd325af.webp" alt="AFB SPORT">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('ggcs','6',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-1_42" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/1_42-cc5fd325af.webp" alt="GGCS">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_1086','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_100" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/sport/4_100-cc5fd325af.webp" alt="APGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					
				</div>

					</div>
					
					<div class="list-game-toggle" id="livecasino" style="display: none;">
												
				<div class="row justify-content-center">


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_20','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_77" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_77-cc5fd325af.webp" alt="EvolutionGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('101','5',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-1_41101" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/1_41101-cc5fd325af.webp" alt="PRAGMATIC CASINO">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_7','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_74" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_74-cc5fd325af.webp" alt="SexyGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_1030','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_85" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_85-cc5fd325af.webp" alt="DreamGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('GSC_63_1223_LOBBY','8',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_63" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/2_63-cc5fd325af.webp" alt="ALLBET">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_1021','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_82" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_82-cc5fd325af.webp" alt="OGLive">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_1088','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_92" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_92-cc5fd325af.webp" alt="Ezugi">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_12','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_75" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_75-cc5fd325af.webp" alt="IONLC">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_5','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_73" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_73-cc5fd325af.webp" alt="BigGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_0','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_72" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_72-cc5fd325af.webp" alt="WanMei">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_1019','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_81" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_81-cc5fd325af.webp" alt="YeeBet">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_1_1035','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_86" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_86-cc5fd325af.webp" alt="PlayAce">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_1_1064','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_89" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_89-cc5fd325af.webp" alt="ViaCasino">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_0_1025','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_84" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_84-cc5fd325af.webp" alt="PlayTechLiveCasino">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('RO','3',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-1_33" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/1_33-cc5fd325af.webp" alt="GLXPLAY">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('GSC_60_lobby','8',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_60" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/2_60-cc5fd325af.webp" alt="WINFINITY">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('568_1_1069','10',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-4_90" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/4_90-cc5fd325af.webp" alt="KissGaming">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


					<div class="col-4 col-menu-item-game mobile_slide_menu_providers">
						<div class="item_game_menu_wrap ">
							<a href="#" onclick="play_games('GSC_46_1185_LOBBY','8',true); return false;">
								<div class="item_game_image">
									<img class="img-fluid d-block prv-2_46" src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/oncharacter/casino/2_46-cc5fd325af.webp" alt="SA GAMING">
								</div>
																<div class="btn_wrapper">
									<button type="button" class="btn btn-info btn-sm">Truy cập</button>
								</div>
															</a>
						</div>
					</div>


										<div class="col-12">
						<div class="relative mt-3 pt-3 line-border-top line-border-full">
														<a class="btn btn-main-dark btn-see-all d-table mb-3" href="#">Xem tất cả </a>
						</div>
					</div>
					
				</div>

					</div>
					
					<div class="list-game-toggle" id="bolatangkas" style="display: none;">
								<div class="row justify-content-center mt-3">

								<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('MM','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bolatangkas--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bola Tangkas" alt="h5g_bolatangkas" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('HR','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_happyroyal--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Happy Royal" alt="h5g_happyroyal" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
					
		</div>
						</div>
					
					<div class="list-game-toggle" id="p2pgame" style="display: none;">
								<div class="row justify-content-center mt-3">

								<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('AA','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_aduqiu--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Adu Q Live" alt="h5g_aduqiu" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('BD','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandardadu--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Dadu" alt="h5g_bandardadu" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('BQ','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarqiu--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Q Live" alt="h5g_bandarqiu" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('BS','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_bandarsakong--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Bandar Sakong" alt="h5g_bandarsakong" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('CB','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_capsabanting--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Capsa Banting" alt="h5g_capsabanting" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('QQ','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_domino99--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Domino 99" alt="h5g_domino99" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('GP','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple-7cc0e5ad07.webp" width="360" height="255" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_gaple--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Gaple" alt="h5g_gaple" style="width: 100%; height: auto; aspect-ratio: 1.4117647058824;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
										<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('PK','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_texaspoker--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Texas Poker" alt="h5g_texaspoker" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
					
		</div>
						</div>
					
					<div class="list-game-toggle" id="totogame" style="display: none;">
								<div class="row justify-content-center mt-3">

								<div class="col-4 mb-3 col-menu-item-game">
						<div class="item_game_menu_wrap mb-3">
															<a href="" onclick="play_games('GL','3',true); return false;">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto-7cc0e5ad07.webp" width="360" height="259" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/h5g/h5g_glxtoto--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="GLX Toto" alt="h5g_glxtoto" style="width: 100%; height: auto; aspect-ratio: 1.3899613899614;">									<div class="btn_wrapper">
										<button type="button" class="btn btn-info btn-sm">Chơi ngay</button>
									</div>
								</a>
																
							
						</div>
					</div>
					
		</div>
						</div>
					
					<div class="list-game-toggle" id="totoresult" style="display: none;">
						
		<div class="row justify-content-center mt-3">
		
			<div class="col-6 mb-3 col-menu-item-totoresult">
				<div class="item_game_menu_wrap mb-3">
					<a href="#">
						<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_a--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Indonesia Siang" alt="indonesia_a" style="width: 100%; height: auto; aspect-ratio: 2.75;">						<div class="btn_wrapper">
							<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
						</div>
					</a>
				</div>
			</div>
			
			<div class="col-6 mb-3 col-menu-item-totoresult">
				<div class="item_game_menu_wrap mb-3">
					<a href="#">
						<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/indonesia_b--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Indonesia Sore" alt="indonesia_b" style="width: 100%; height: auto; aspect-ratio: 2.75;">						<div class="btn_wrapper">
							<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
						</div>
					</a>
				</div>
			</div>
			
			<div class="col-6 mb-3 col-menu-item-totoresult">
				<div class="item_game_menu_wrap mb-3">
					<a href="#">
						<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/korea-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/korea--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/korea--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/korea--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/korea--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/korea--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Korea" alt="korea" style="width: 100%; height: auto; aspect-ratio: 2.75;">						<div class="btn_wrapper">
							<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
						</div>
					</a>
				</div>
			</div>
			
			<div class="col-6 mb-3 col-menu-item-totoresult">
				<div class="item_game_menu_wrap mb-3">
					<a href="#">
						<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Singapore" alt="singapore" style="width: 100%; height: auto; aspect-ratio: 2.75;">						<div class="btn_wrapper">
							<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
						</div>
					</a>
				</div>
			</div>
			
			<div class="col-6 mb-3 col-menu-item-totoresult">
				<div class="item_game_menu_wrap mb-3">
					<a href="#">
						<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/china-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/china--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/china--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/china--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/china--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/china--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="China" alt="china" style="width: 100%; height: auto; aspect-ratio: 2.75;">						<div class="btn_wrapper">
							<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
						</div>
					</a>
				</div>
			</div>
			
			<div class="col-6 mb-3 col-menu-item-totoresult">
				<div class="item_game_menu_wrap mb-3">
					<a href="#">
						<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49-7cc0e5ad07.webp" width="660" height="240" srcset="https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/togel/singapore-49--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Singapore 49" alt="singapore-49" style="width: 100%; height: auto; aspect-ratio: 2.75;">						<div class="btn_wrapper">
							<button type="button" class="btn btn-info btn-sm">Xem kết quả</button>
						</div>
					</a>
				</div>
			</div>
		
		</div>

						</div>
				</div>
			</section>
			
			<!-- Jackpot -->
			<section class="jackpot-play line-border line-border-right line-border-w75 line-border-h3">
				<div class="container">
					<div class="icon">
						<img class="img-fluid d-block" src="<?= h($logoSrc) ?>" alt="<?= h($namaSitus) ?>">
					</div>
					
					<div class="content">
						<div class="title">
							<h2>Jackpot <span class="purple-light">Play</span></h2>
						</div>
						
						<div class="jackpot-value">
							<div class="inner-jackpot">
								<span class="prefix gray">VNĐ</span>
								<span class="infix" id="jackpot-value">51.744.309.360</span>
							</div>
						</div>
						
						<div class="action">
							<a class="btn btn-main btn-lg btn-shadow btn-playnow-jp" href="">Chơi ngay</a>
						</div>
					</div>
				</div>
			</section>
			
			<!-- Games -->
			<!--section class="main-games line-border line-border-left line-border-w75 line-border-h3 line-border-zi2">
				<div class="container">
					<div class="title-games">
						<h2>Popular <span class="purple-light">Games</span></h2>
					</div>
					
									</div>
			</section-->
			
			<!-- Play For Fun Games -->
			<!--section id="containerPlayFunGames">
				<section id="playFunGames" class="fun-demo-games line-border line-border-right line-border-w75 line-border-h3 line-border-zi2">
					<div class="container">
						<div class="title-fun-demo-games line-border line-border-full">
							<img class="" src=""><h2>Slots</h2>
						</div>
						
						<div class="search-fun-demo-games">	
							<div class="preload"><i class="fas fa-spinner fa-pulse fa-fw me-2"></i>Đang tải, vui lòng chờ ...</div>
						</div>
					</div>
				</section>
			</section-->
						<!--section id="playFunGames" class="fun-demo-games line-border line-border-right line-border-w75 line-border-h3 line-border-zi2"-->
			<section id="playFunGames" class="fun-demo-games">
				<div class="container">
					<div class="title-fun-demo-games line-border line-border-full">
						<img class="load-when-upfold" src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 325 234\'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/slot-machine.webp"><h2>Slot chơi thử</h2>
					</div>
					
					<div class="search-fun-demo-games">
						<div class="input-group">
							<span class="input-group-text"><i class="fa fa-search fa-lg"></i></span>
							<input type="text" class="form-control form-control-lg" id="search-fun-demo-games" placeholder="Tìm game slot ...">
						</div>					
					</div>
					
					<div class="content-fun-demo-games line-border line-border-full">
					
		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympmmd-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympmmd-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Gates of Olympus 2500" alt="vs20olympmmd" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Gates of Olympus 2500" aria-valuenow="94" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 94%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>94%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Gates of Olympus 2500</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Gates of Olympus 2500</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20olympmmd" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmgrmgw-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmgrmgw-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Forever Split Megaways" alt="vswaysmgrmgw" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Forever Split Megaways" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Forever Split Megaways</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Forever Split Megaways</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vswaysmgrmgw" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrushx-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrushx-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrushx--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrushx--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrushx--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrushx--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrushx--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Sugar Rush 1000" alt="vs20sugarrushx" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Sugar Rush 1000" aria-valuenow="93" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 93%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>93%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Sugar Rush 1000</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Sugar Rush 1000</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20sugarrushx" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympgold-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympgold-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Gates of Olympus Super Scatter" alt="vs20olympgold" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Gates of Olympus Super Scatter" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Gates of Olympus Super Scatter</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Gates of Olympus Super Scatter</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20olympgold" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayslions-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayslions-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayslions--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayslions--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayslions--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayslions--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayslions--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="5 Lions Megaways" alt="vswayslions" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for 5 Lions Megaways" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>5 Lions Megaways</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>5 Lions Megaways</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vswayslions" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugrushss-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugrushss-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Sugar Rush Super Scatter" alt="vs20sugrushss" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Sugar Rush Super Scatter" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 95%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>95%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Sugar Rush Super Scatter</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Sugar Rush Super Scatter</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20sugrushss" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayspompmr2-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayspompmr2-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Zombie School Megaways" alt="vswayspompmr2" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Zombie School Megaways" aria-valuenow="97" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 97%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>97%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Zombie School Megaways</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Zombie School Megaways</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vswayspompmr2" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympx-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympx-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympx--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympx--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympx--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympx--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympx--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Gates of Olympus 1000" alt="vs20olympx" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Gates of Olympus 1000" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Gates of Olympus 1000</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Gates of Olympus 1000</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20olympx" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysrabbits-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysrabbits-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysrabbits--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysrabbits--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysrabbits--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysrabbits--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysrabbits--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="5 Rabbits Megaways" alt="vswaysrabbits" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for 5 Rabbits Megaways" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>5 Rabbits Megaways</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>5 Rabbits Megaways</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vswaysrabbits" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20swbon2500-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20swbon2500-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Sweet Bonanza 2500" alt="vs20swbon2500" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Sweet Bonanza 2500" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 95%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>95%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Sweet Bonanza 2500</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Sweet Bonanza 2500</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20swbon2500" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysdh1000-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysdh1000-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="The Dog House Megaways 1000" alt="vswaysdh1000" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for The Dog House Megaways 1000" aria-valuenow="94" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 94%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>94%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>The Dog House Megaways 1000</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>The Dog House Megaways 1000</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vswaysdh1000" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs25pandagold-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs25pandagold-7cc0e5ad07.webp" width="283" height="204" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs25pandagold--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs25pandagold--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs25pandagold--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs25pandagold--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs25pandagold--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Pandas Fortune" alt="vs25pandagold" style="width: 100%; height: auto; aspect-ratio: 1.3872549019608;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Pandas Fortune" aria-valuenow="93" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 93%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>93%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Pandas Fortune</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Pandas Fortune</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs25pandagold" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrush-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrush-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrush--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrush--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrush--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrush--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20sugarrush--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Sugar Rush" alt="vs20sugarrush" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Sugar Rush" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 95%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>95%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Sugar Rush</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Sugar Rush</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20sugarrush" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayspowzeus-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayspowzeus-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayspowzeus--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayspowzeus--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayspowzeus--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayspowzeus--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswayspowzeus--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Power of Merlin Megaways" alt="vswayspowzeus" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Power of Merlin Megaways" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Power of Merlin Megaways</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Power of Merlin Megaways</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vswayspowzeus" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vsways5lions2-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vsways5lions2-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="5 Lions Megaways 2" alt="vsways5lions2" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for 5 Lions Megaways 2" aria-valuenow="94" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 94%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>94%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>5 Lions Megaways 2</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>5 Lions Megaways 2</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vsways5lions2" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20rockvegas-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20rockvegas-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20rockvegas--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20rockvegas--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20rockvegas--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20rockvegas--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20rockvegas--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Rock Vegas" alt="vs20rockvegas" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Rock Vegas" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Rock Vegas</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Rock Vegas</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20rockvegas" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysdogs-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysdogs-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysdogs--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysdogs--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysdogs--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysdogs--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysdogs--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="The Dog House Megaways" alt="vswaysdogs" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for The Dog House Megaways" aria-valuenow="93" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 93%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>93%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>The Dog House Megaways</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>The Dog House Megaways</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vswaysdogs" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs15eastfury-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs15eastfury-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Eastern Fury" alt="vs15eastfury" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Eastern Fury" aria-valuenow="94" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 94%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>94%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Eastern Fury</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Eastern Fury</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs15eastfury" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympuspot-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympuspot-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Helios - Triple Sun" alt="vs20olympuspot" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Helios - Triple Sun" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Helios - Triple Sun</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Helios - Triple Sun</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20olympuspot" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs10freya-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs10freya-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Freya 1000" alt="vs10freya" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Freya 1000" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Freya 1000</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Freya 1000</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs10freya" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs10swtbnz-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs10swtbnz-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Sweet Bonanza POP" alt="vs10swtbnz" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Sweet Bonanza POP" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Sweet Bonanza POP</h5>
					<p>FAT PANDA</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Sweet Bonanza POP</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs10swtbnz" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20coven-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20coven-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Coven Rising" alt="vs20coven" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Coven Rising" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Coven Rising</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Coven Rising</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20coven" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs10bbveg1000-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs10bbveg1000-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Big Bass Vegas 1000" alt="vs10bbveg1000" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Big Bass Vegas 1000" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Big Bass Vegas 1000</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Big Bass Vegas 1000</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs10bbveg1000" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmahwblck-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmahwblck-7cc0e5ad07.webp" width="325" height="234" srcset="" class="load-when-upfold" alt="Mahjong Wins 3 - Black Scatter" alt="vswaysmahwblck" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Mahjong Wins 3 - Black Scatter" aria-valuenow="93" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 93%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>93%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Mahjong Wins 3 - Black Scatter</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Mahjong Wins 3 - Black Scatter</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vswaysmahwblck" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20starlightx-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20starlightx-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20starlightx--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20starlightx--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20starlightx--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20starlightx--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20starlightx--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Starlight Princess 1000" alt="vs20starlightx" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Starlight Princess 1000" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 95%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>95%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Starlight Princess 1000</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Starlight Princess 1000</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20starlightx" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympgate-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympgate-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympgate--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympgate--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympgate--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympgate--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20olympgate--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Gates of Olympus" alt="vs20olympgate" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Gates of Olympus" aria-valuenow="94" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 94%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>94%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Gates of Olympus</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Gates of Olympus</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20olympgate" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20fruitswx-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20fruitswx-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20fruitswx--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20fruitswx--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20fruitswx--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20fruitswx--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vs20fruitswx--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Sweet Bonanza 1000" alt="vs20fruitswx" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Sweet Bonanza 1000" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Sweet Bonanza 1000</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Sweet Bonanza 1000</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vs20fruitswx" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>


		<div class="item-fun-demo-game">
			<div class="inner-fun-demo-game">
				<div class="content" data-bg="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmahwin2-7cc0e5ad07.webp">
					<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmahwin2-7cc0e5ad07.webp" width="325" height="234" srcset="https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmahwin2--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmahwin2--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmahwin2--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmahwin2--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/imports/img/game/pragm/vswaysmahwin2--xxlsize-7cc0e5ad07.webp 1320w" class="load-when-upfold" alt="Mahjong Wins 2" alt="vswaysmahwin2" style="width: 100%; height: auto; aspect-ratio: 1.3888888888889;">				</div>

									
						<div class="rtp-game">
							<div class="progress" role="progressbar" aria-label="RTP for Mahjong Wins 2" aria-valuenow="96" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped bg-success" style="width: 96%"></div>
							</div>
							
							<div class="rtp-game-percentage"><span>96%</span></div>
						</div>
				
								
				<div class="subtitle">
					<h5>Mahjong Wins 2</h5>
					<p>PRAGMATIC SLOT</p>
				</div>
				
				<div class="play-fun-demo-game">
					<h5>Mahjong Wins 2</h5>
					<button type="button" id="play-real-fun-games" class="btn btn-main btn-play"><i class="fa-solid fa-play"></i></button>
					<button type="button" id="play-demo-fun-games" data-code="vswaysmahwin2" data-wayplay="5" class="btn btn-trans btn-sm btn-capsule">Chơi thử miễn phí</button>
				</div>
			</div>
		</div>

		
					</div>
					
					<div class="loadmore-fun-demo-games">
						<button type="button" id="btn-loadmore-fun-demo-games" class="btn btn-main">Tải thêm</button>
					</div>
				</div>
			</section>
						
					</main>		<!-- and underneath this, is footer -->
		<!-- Footer -->
		
<footer>
		<section class="brand-description-wrap">
		<div class="container">
			<div class="row">
				<div class="col-12 mt-4">
					<div class="brand-description py-4">
						<div class="content-wrap content-about_description"><h1><?= h($page['h1'] ?? '') ?></h1>
<?= $isiArtikel ?></div>
					</div>
				</div>
			</div>
		</div>
	</section>
		
	
	
	<section class="about-us-wrap">
		<div class="container">
			<div class="py-4 our-mission">
				<div class="row">
					<div class="col-md-4 col-12">
						<div class="sub-page-aside">
							<div class="sub-page-aside-title">
								<div class="spat-icon">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-call-center-7cc0e5ad07.webp" width="80" height="80" srcset="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-call-center--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-call-center--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-call-center--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-call-center--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-call-center--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="icon-call-center" style="width: 100%; height: auto; aspect-ratio: 1;">								</div>
								<div class="spat-main">Dịch vụ</div>
								<div class="spat-desc">Ưu điểm dịch vụ</div>
							</div>
							<div class="sub-page-aside-content mt-3 py-3">
								<div class="card-progress">
									<div class="card-progress-item">
										<div class="cpi-title">Nạp tiền</div>
										<div class="cpi-desc mb-2">Thời gian trung bình</div>
										<div class="cpi-time">
											<div class="number-seg">
												1<span>Min</span>
											</div>
										</div>
										<div class="cpi-progress progress">
											<div class="progress-bar" role="progressbar" style="width: 18%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
										</div>
										
										<div class="realtime-latest-dpwd mt-3">
											<ul class="list-latest-dpwd" data-type="dp">
																																		<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>al71*** Deposit:</div>
													<div class="latest-amount">1.340.088 VNĐ</div>
												</li>
																							<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>de***in Deposit:</div>
													<div class="latest-amount">1.050.061 VNĐ</div>
												</li>
																							<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>***ra Deposit:</div>
													<div class="latest-amount">1.070.069 VNĐ</div>
												</li>
																							<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>***ra Deposit:</div>
													<div class="latest-amount">320.023 VNĐ</div>
												</li>
																							<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>ra85*** Deposit:</div>
													<div class="latest-amount">390.075 VNĐ</div>
												</li>
																						</ul>
										</div>
									</div>
									<div class="card-progress-item mt-3">
										<div class="cpi-title">Rút tiền</div>
										<div class="cpi-desc">Thời gian trung bình</div>
										<div class="cpi-time">
											<div class="number-seg">
												3<span>Min</span>
											</div>
										</div>
										<div class="cpi-progress progress">
											<div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
										</div>
										
										<div class="realtime-latest-dpwd mt-3">
											<ul class="list-latest-dpwd" data-type="wd">
																																		<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>fa81***on Withdraw:</div>
													<div class="latest-amount">4.880.000 VNĐ</div>
												</li>
																							<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>***as Withdraw:</div>
													<div class="latest-amount">11.040.000 VNĐ</div>
												</li>
																							<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>de*** Withdraw:</div>
													<div class="latest-amount">2.190.000 VNĐ</div>
												</li>
																							<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>li***um Withdraw:</div>
													<div class="latest-amount">5.420.000 VNĐ</div>
												</li>
																							<li class="item-latest-dpwd">
													<div class="latest-player"><i class="fa-regular fa-circle-user me-2"></i>no50*** Withdraw:</div>
													<div class="latest-amount">2.730.000 VNĐ</div>
												</li>
																						</ul>
										</div>
									</div>
								</div>
								<div class="small-desc mt-4">
									*Trong thời gian ngân hàng bảo trì hoặc ngoại tuyến, lệnh nạp và rút tiền không thể được xử lý.
								</div>
								
							</div>
						</div>
					</div>
					<div class="col-md-4 col-12">
						<div class="sub-page-aside">
							<div class="sub-page-aside-title">
								<div class="spat-icon">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-poker-chip-7cc0e5ad07.webp" width="80" height="80" srcset="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-poker-chip--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-poker-chip--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-poker-chip--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-poker-chip--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-poker-chip--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="icon-poker-chip" style="width: 100%; height: auto; aspect-ratio: 1;">								</div>
								<div class="spat-main">Tính năng trò chơi</div>
								<div class="spat-desc">Ưu điểm khi chơi tại đây</div>
							</div>
							<div class="sub-page-aside-content mt-3 py-3">
								<div class="simple-card-title-desc">
									<div class="sctd-item py-3 pt-0">
										<div class="sctd-title">Luôn có khuyến mãi</div>
										<div class="sctd-desc">Chơi lâu hơn với tiền thưởng được tặng và tận hưởng niềm vui với lợi ích tối đa.</div>
									</div>
									<div class="sctd-item py-3">
										<div class="sctd-title">Thưởng hoàn tiền</div>
										<div class="sctd-desc">Trong khoảng thời gian nhất định mỗi tuần, chúng tôi tặng bạn tiền hoàn trả miễn phí.</div>
									</div>
									<div class="sctd-item py-3">
										<div class="sctd-title">Có thưởng doanh thu cược</div>
										<div class="sctd-desc">Tối đa hóa lợi ích với thưởng doanh thu cược, tính theo phần trăm số vốn bạn sử dụng.</div>
									</div>
									<div class="sctd-item py-3">
										<div class="sctd-title">Hỗ trợ 24/7</div>
										<div class="sctd-desc">Mỗi ngày và mọi lúc, bộ phận CSKH luôn sẵn sàng hỗ trợ bất cứ khi nào bạn cần.</div>
									</div>

								</div>
							</div>
						</div>
					</div>
					<div class="col-md-4 col-12">
						<div class="sub-page-aside">
							<div class="sub-page-aside-title">
								<div class="spat-icon">
									<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-information-7cc0e5ad07.webp" width="80" height="80" srcset="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-information--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-information--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-information--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-information--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-information--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="icon-information" style="width: 100%; height: auto; aspect-ratio: 1;">								</div>
								<div class="spat-main">Trợ giúp & Hỗ trợ</div>
								<div class="spat-desc">Hỗ trợ trực tuyến 24/7</div>
							</div>
							<div class="sub-page-aside-content mt-3 py-3">
								<div class="simple-card-title-desc">
									<div class="sctd-item">
										<div class="sctd-title mb-2">Kết nối với chúng tôi</div>
									</div>
									<div class="sctd-item">
												<div class="contact-wrap my-3">
			<ul class="contact-outer display-horizontal">
								<li>
						<a target="_blank" href="" onclick="openwa('https://wa.me//?text='); return false;" class="cstyle whatsapp">
							<!--i class="fa fa-"></i-->
							<i class="fa fa-brands fa-whatsapp"></i>
						</a>
					</li>
										<li>
						<a href="#" target="_blank" class="cstyle facebook">
							<!--i class="fa fa-"></i-->
							<i class="fa fa-brands fa-facebook-f"></i>
						</a>
					</li>
										<li>
						<a href="#" target="_blank" class="cstyle twitter">
							<!--i class="fa fa-"></i-->
							<i class="fa fa-brands fa-x-twitter"></i>
						</a>
					</li>
										<li>
						<a href="#" target="_blank" class="cstyle instagram">
							<!--i class="fa fa-"></i-->
							<i class="fa fa-brands fa-instagram"></i>
						</a>
					</li>
										<li>
						<a href="#" target="_blank" class="cstyle telegram">
							<!--i class="fa fa-"></i-->
							<i class="fa fa-brands fa-telegram"></i>
						</a>
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
			<div class="row justify-content-end">
				<div class="col-lg-4 col-12">
					<div class="simple-card-title-image">
						<div class="scti-item scti-contentcenter play-responsibly">
							<div class="scti-title my-2">Chơi có trách nhiệm</div>
							<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-responsible-game-7cc0e5ad07.webp" width="291" height="32" srcset="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-responsible-game--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-responsible-game--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-responsible-game--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-responsible-game--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-responsible-game--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="icon-responsible-game" style="width: 100%; height: auto; aspect-ratio: 9.09375;">						</div>
					</div>
				</div>
				<div class="col-lg-4 col-12">
					<div class="simple-card-title-image">
						<div class="scti-item scti-contentend play-browser">
							<div class="scti-title my-2">Trình duyệt được hỗ trợ</div>
							<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-browsers-7cc0e5ad07.webp" width="200" height="62" srcset="https://batik138-aset.pages.dev/assets/assets/images/icon/icon-browsers--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-browsers--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-browsers--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-browsers--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/icon/icon-browsers--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="icon-browsers" style="width: 100%; height: auto; aspect-ratio: 3.2258064516129;">						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<section>
		<div class="container">
			<div class="row">
				<div class="col-12">
					<!--<div class="small-desc">Nền tảng nhà cung cấp dịch vụ</div>-->
					<div class="logo-group my-5 p-2">
						<!--<div class="logo-group-title">Nền tảng nhà cung cấp dịch vụ</div>-->
						<div class="logo-group-content">
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/allbet-7cc0e5ad07.webp" width="69" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/allbet--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/allbet--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/allbet--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/allbet--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/allbet--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="allbet" style="width: 100%; height: auto; aspect-ratio: 1.3269230769231;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cmd368-7cc0e5ad07.webp" width="74" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cmd368--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cmd368--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cmd368--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cmd368--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cmd368--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="cmd368" style="width: 100%; height: auto; aspect-ratio: 1.4230769230769;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet_casing-7cc0e5ad07.webp" width="71" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet_casing--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet_casing--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet_casing--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet_casing--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet_casing--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="sbobet_casing" style="width: 100%; height: auto; aspect-ratio: 1.3653846153846;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cq9-7cc0e5ad07.webp" width="75" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cq9--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cq9--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cq9--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cq9--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/cq9--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="cq9" style="width: 100%; height: auto; aspect-ratio: 1.4423076923077;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/evolution-7cc0e5ad07.webp" width="102" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/evolution--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/evolution--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/evolution--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/evolution--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/evolution--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="evolution" style="width: 100%; height: auto; aspect-ratio: 1.9615384615385;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/fastspin-7cc0e5ad07.webp" width="109" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/fastspin--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/fastspin--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/fastspin--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/fastspin--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/fastspin--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="fastspin" style="width: 100%; height: auto; aspect-ratio: 2.0961538461538;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/gemini-7cc0e5ad07.webp" width="109" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/gemini--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/gemini--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/gemini--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/gemini--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/gemini--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="gemini" style="width: 100%; height: auto; aspect-ratio: 2.0961538461538;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/habanero-7cc0e5ad07.webp" width="120" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/habanero--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/habanero--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/habanero--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/habanero--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/habanero--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="habanero" style="width: 100%; height: auto; aspect-ratio: 2.3076923076923;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ioncasino-7cc0e5ad07.webp" width="117" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ioncasino--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ioncasino--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ioncasino--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ioncasino--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ioncasino--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="ioncasino" style="width: 100%; height: auto; aspect-ratio: 2.25;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jili-7cc0e5ad07.webp" width="74" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jili--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jili--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jili--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jili--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jili--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="jili" style="width: 100%; height: auto; aspect-ratio: 1.4230769230769;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sboslot-7cc0e5ad07.webp" width="114" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sboslot--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sboslot--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sboslot--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sboslot--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sboslot--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="sboslot" style="width: 100%; height: auto; aspect-ratio: 2.1923076923077;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jokergaming-7cc0e5ad07.webp" width="85" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jokergaming--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jokergaming--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jokergaming--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jokergaming--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/jokergaming--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="jokergaming" style="width: 100%; height: auto; aspect-ratio: 1.6346153846154;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/live22-7cc0e5ad07.webp" width="79" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/live22--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/live22--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/live22--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/live22--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/live22--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="live22" style="width: 100%; height: auto; aspect-ratio: 1.5192307692308;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming-7cc0e5ad07.webp" width="126" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="microgaming" style="width: 100%; height: auto; aspect-ratio: 2.4230769230769;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/netent-7cc0e5ad07.webp" width="93" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/netent--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/netent--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/netent--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/netent--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/netent--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="netent" style="width: 100%; height: auto; aspect-ratio: 1.7884615384615;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/nolimit-7cc0e5ad07.webp" width="94" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/nolimit--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/nolimit--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/nolimit--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/nolimit--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/nolimit--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="nolimit" style="width: 100%; height: auto; aspect-ratio: 1.8076923076923;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/opusplus-7cc0e5ad07.webp" width="82" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/opusplus--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/opusplus--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/opusplus--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/opusplus--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/opusplus--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="opusplus" style="width: 100%; height: auto; aspect-ratio: 1.5769230769231;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pg-7cc0e5ad07.webp" width="66" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pg--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pg--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pg--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pg--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pg--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="pg" style="width: 100%; height: auto; aspect-ratio: 1.2692307692308;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/playstar-7cc0e5ad07.webp" width="102" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/playstar--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/playstar--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/playstar--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/playstar--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/playstar--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="playstar" style="width: 100%; height: auto; aspect-ratio: 1.9615384615385;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmatic-7cc0e5ad07.webp" width="104" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmatic--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmatic--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmatic--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmatic--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmatic--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="pragmatic" style="width: 100%; height: auto; aspect-ratio: 2;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sabasports-7cc0e5ad07.webp" width="75" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sabasports--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sabasports--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sabasports--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sabasports--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sabasports--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="sabasports" style="width: 100%; height: auto; aspect-ratio: 1.4423076923077;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sagaming-7cc0e5ad07.webp" width="89" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sagaming--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sagaming--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sagaming--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sagaming--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sagaming--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="sagaming" style="width: 100%; height: auto; aspect-ratio: 1.7115384615385;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet-7cc0e5ad07.webp" width="91" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/sbobet--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="sbobet" style="width: 100%; height: auto; aspect-ratio: 1.75;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/softgaming-7cc0e5ad07.webp" width="79" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/softgaming--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/softgaming--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/softgaming--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/softgaming--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/softgaming--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="softgaming" style="width: 100%; height: auto; aspect-ratio: 1.5192307692308;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/spadegaming-7cc0e5ad07.webp" width="120" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/spadegaming--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/spadegaming--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/spadegaming--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/spadegaming--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/spadegaming--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="spadegaming" style="width: 100%; height: auto; aspect-ratio: 2.3076923076923;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/tfgaming-7cc0e5ad07.webp" width="100" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/tfgaming--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/tfgaming--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/tfgaming--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/tfgaming--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/tfgaming--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="tfgaming" style="width: 100%; height: auto; aspect-ratio: 1.9230769230769;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming_livecasino-7cc0e5ad07.webp" width="102" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming_livecasino--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming_livecasino--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming_livecasino--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming_livecasino--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/microgaming_livecasino--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="microgaming_livecasino" style="width: 100%; height: auto; aspect-ratio: 1.9615384615385;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmaticplay_livecasino-7cc0e5ad07.webp" width="111" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmaticplay_livecasino--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmaticplay_livecasino--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmaticplay_livecasino--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmaticplay_livecasino--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/pragmaticplay_livecasino--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="pragmaticplay_livecasino" style="width: 100%; height: auto; aspect-ratio: 2.1346153846154;">							</div>
							<div class="lggr-item">
								<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E" data-src="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ws168-7cc0e5ad07.webp" width="71" height="52" srcset="https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ws168--smsize-7cc0e5ad07.webp 480w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ws168--mdsize-7cc0e5ad07.webp 767w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ws168--lgsize-7cc0e5ad07.webp 991w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ws168--xlsize-7cc0e5ad07.webp 1199w, https://batik138-aset.pages.dev/assets/assets/images/gameproviders/ws168--xxlsize-7cc0e5ad07.webp 1320w" class="img-fluid d-block load-when-upfold" alt="ws168" style="width: 100%; height: auto; aspect-ratio: 1.3653846153846;">							</div>
						</div>
					</div>
					
				</div>
			</div>
		</div>
	</section>
	
	<section>
		<div class="container">
			<div class="row">
				<div class="col-12">
						<div class="copyright-footer-wrap py-3">&copy;<?= h($tahun) ?> <?= h($namaSitus) ?> | <a href="#">Chính sách bảo mật</a></div>
					</div>
			</div>
		</div>
	</section>
</footer>

<!-- Mobile Bottom Menu -->
<div class="d-sm-none mobile-bottom-menu">
	<div class="main-bottom-menu">
		<ul class="nav-bottom-menu">
			<li class="nav-item-bottom">
				<a href="/" class="active">
					<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-home.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
					<div class="text">Trang chủ</div>
				</a>
			</li>
			
			<li class="nav-item-bottom">
				<a href="<?= h($ctaUrl) ?>" class="">
					<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-register.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
					<div class="text">Đăng ký</div>
				</a>
			</li>
			
			<li class="nav-item-bottom center-item">
				<a href="<?= h($ctaUrl) ?>" class="">
					<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-login.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
					<div class="text">Đăng nhập</div>
				</a>
			</li>
			
			<li class="nav-item-bottom">

				<a href="#" class="">
					<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-promotions.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
					<div class="text">Khuyến mãi</div>
				</a>
			</li>
			
			<li class="nav-item-bottom">
				<a href="javascript:void(0)" onclick="showChatBox()">
					<div class="ico"><img class="load-when-upfold" data-src="https://batik138-aset.pages.dev/assets/assets/images/icon/menu-contact-us.webp" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 325 234'%3E%3C/svg%3E"></div>
					<div class="text">Liên hệ</div>
				</a>
				<!--
				<a href="login.php">
					<div class="ico"><img class="" src=""></div>
					<div class="text">Tài khoản</div>
				</a>
				-->
			</li>
		</ul>
	</div>
</div>

<!-- Trigger Live Chat Dekstop -->
<div class="d-none d-sm-block live-chat-dekstop">
	<a href="javascript:void(0)" onclick="showChatBox()">
		<span>Trò chuyện ngay</span>
		<span><svg color="inherit" viewBox="0 0 32 32" class="css-1usdo54"><path fill="#FFFFFF" d="M12.63,26.46H8.83a6.61,6.61,0,0,1-6.65-6.07,89.05,89.05,0,0,1,0-11.2A6.5,6.5,0,0,1,8.23,3.25a121.62,121.62,0,0,1,15.51,0A6.51,6.51,0,0,1,29.8,9.19a77.53,77.53,0,0,1,0,11.2,6.61,6.61,0,0,1-6.66,6.07H19.48L12.63,31V26.46"></path><path fill="#CE1D1D" d="M19.57,21.68h3.67a2.08,2.08,0,0,0,2.11-1.81,89.86,89.86,0,0,0,0-10.38,1.9,1.9,0,0,0-1.84-1.74,113.15,113.15,0,0,0-15,0A1.9,1.9,0,0,0,6.71,9.49a74.92,74.92,0,0,0-.06,10.38,2,2,0,0,0,2.1,1.81h3.81V26.5Z" class="css-1arh9bj eam5rsy0"></path></svg></span>
	</a>
</div>

<!-- Live Chat Box -->
<div id="live-chat-box">
	<div id="live-chat-header">
		<button onclick="hideChatBox()" class="close-live-chat-box">Close</button>
	</div>
	<div id="live-chat-content">
		<iframe id="livechatiframe" src="" frameborder="0" scrolling="no" width="100%" height="100%"></iframe>
	</div>
</div>

<script>
FDDPWDSET = {"feedwd":{"min":"500000","max":"15000000","step":"10000"},"feeddp":{"min":"20000","max":"2000000","step":"10000"}};
</script>

<!-- Latest DP/WD -->
<div class="fixed-latest-dpwd" id="fixed-latest-dpwd">
    <i class="fa-regular fa-circle-user me-2"></i>Loading...
</div>

<script>
	window.addEventListener('load', function () {
		// Realtime Feed DP WD
		setTimeout(function() {
			feedRealtimeDPWD('dp');
			feedRealtimeDPWD('wd');	
		}, 2000);
	});		
</script>

	<div class="floating-socmed" id="floatingSocmed">
		<div class="close-flsocmed" onclick="removeElement('#floatingSocmed')">x</div>
					<div class="contact-wrap">
				<ul class="contact-outer display-vertical">
										<li>
							<a target="_blank" href="" onclick="openwa('https://wa.me//?text='); return false;" class="cstyle whatsapp">
								<!--i class="fa fa-"></i-->
								<i class="fa fa-brands fa-whatsapp"></i>
							</a>
						</li>
												<li>
							<a href="#" target="_blank" class="cstyle facebook">
								<!--i class="fa fa-"></i-->
								<i class="fa fa-brands fa-facebook-f"></i>
							</a>
						</li>
												<li>
							<a href="#" target="_blank" class="cstyle twitter">
								<!--i class="fa fa-"></i-->
								<i class="fa fa-brands fa-x-twitter"></i>
							</a>
						</li>
												<li>
							<a href="#" target="_blank" class="cstyle instagram">
								<!--i class="fa fa-"></i-->
								<i class="fa fa-brands fa-instagram"></i>
							</a>
						</li>
												<li>
							<a href="#" target="_blank" class="cstyle telegram">
								<!--i class="fa fa-"></i-->
								<i class="fa fa-brands fa-telegram"></i>
							</a>
						</li>
										</ul>
			</div>
				</div>
	
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1033238218706727');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1033238218706727&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->		
		<!-- SCRIPT CORE START HERE
		<!-- load js -->
		<!-- GLOBAL: loads on every pages -->
<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>-->
<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/jquery/jquery.min.js" as="script">
<script src="https://batik138-aset.pages.dev/assets/libs/jquery/jquery.min.js"></script>

<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/bootstrap/js/bootstrap.bundle.min.js" as="script">
<script src="https://batik138-aset.pages.dev/assets/libs/bootstrap/js/bootstrap.bundle.min.js" defer></script>

	<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/jquery-toast/js/jquery.toast.js" as="script">
	<script src="https://batik138-aset.pages.dev/assets/libs/jquery-toast/js/jquery.toast.js" defer></script>
		<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/daterangepicker/js/moment.min.js" as="script">
	<script src="https://batik138-aset.pages.dev/assets/libs/daterangepicker/js/moment.min.js" defer></script>
	
	<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/daterangepicker/js/daterangepicker.js" as="script">
	<script src="https://batik138-aset.pages.dev/assets/libs/daterangepicker/js/daterangepicker.js" defer></script>
		<!--<link rel="preload" href="https://www.google.com/recaptcha/api.js?render=" as="script">
	<script src="https://www.google.com/recaptcha/api.js?render="></script>-->
	
<script>
var callpost;
$ = jQuery.noConflict();
(function($) {
//load vars here
HOMEPAGE_URL = "/";
DOCUMENT_ROOT = "/";
FILE_NAME = "index.php";
GRECAP_SITEKEY = "6Ld6iM4qAAAAAIBEfs7CIlctLsyOfXOvnvzOwWqQ";
AJAX_SESSIONS_URL = "bunker/pageload/ajax/ajax_sessions";
AJAX_HEARTBEAT_URL = "bunker/pageload/ajax/ajax_heartbeat";
AJAX_KICKBEAT_URL = "bunker/pageload/ajax/ajax_kickbeat";
AJAX_PATH = "bunker/pageload/ajax/";
DATETIMENOW = "2026-10-07 13:34:25";
KICKCHECKER_TIME = 30;
AJAX_ONGOINGPAYMENTGATEWAY_URL = "bunker/pageload/ajax/ajax_ongoingpaygate";
LIVECHATURL = "<?= h($ctaUrl) ?>";

$('button[type="submit"][disabled=""]').each(function() {
	$(this).removeAttr("disabled");
});

	/*
	grecaptcha.ready(function() {
		grecaptcha.execute('', {action: 'submit'}).then(function(grtoken) {
			// Add your logic to submit to your backend server here.
			if ($('.formvalidator').length > 0) {
				//$('.formvalidator').val(grtoken);
				$('button[type="submit"][disabled=""]').each(function() {
					$(this).removeAttr("disabled");
				});
			}
		});
	});
	*/
	

$(document).ready(function() {
	//alert("hello i am here");
	//document.body.innerHTML.replace('', '/');
	
	});
})( jQuery );

function settoken_grecaptcha() {
	grecaptcha.ready(function() {
		grecaptcha.execute(GRECAP_SITEKEY, {action: 'submit'}).then(function(grtoken) {
			// Add your logic to submit to your backend server here.
			$.post(AJAX_SESSIONS_URL,{'grtokenv':grtoken},function(){});
		});
	});
}
</script>
<script>
$ = jQuery.noConflict();
(function($) {
	
jackpotStarted = false;
	
$(window).on('scroll', function() {
	
	timej = 51744;
	intsec = 0.5;
	ggnrand = 1;

	inttick = intsec * 1000;
	eltarget = $('#jackpot-value');

	randm = 383187;
	jpnumstr = timej.toString() + randm.toString();
	jpnum = parseInt(jpnumstr) + 100000;
	//console.log(jpnum);
	var jackpotNumber = jpnum;
				
	var odometer = new Odometer({
		el: eltarget[0],
		value: jpnum,
		format: '(.ddd)',
		duration: inttick,
		theme: 'minimal',
	});
	odometer.update(jackpotNumber);

	$(window).off('scroll');
	
	jackpot_roll(intsec)
	
});

function jackpot_roll(intsec) {
	
	inttick = intsec * 1000;
	
	let intjproll = setInterval(function() {
		
		let randm = randBalanced(10000, 9999999);
		
		jpnum = jpnum + randm;

		var jackpotNumber = jpnum;

		nextintsecrandom = (Math.floor(Math.random() * ((3.5 - 2) / 0.5 + 1)) * 0.5) + 2;
		if (nextintsecrandom <= 2) {
			nextintsecrandom = 2.1;
		}
					
		//odometerint = (nextintsecrandom * 1000);
		odometerint = Math.max(nextintsecrandom * 1000);
		var odometer = new Odometer({
			el: eltarget[0],
			value: jpnum,
			format: '(.ddd)',
			duration: odometerint,
			theme: 'minimal',
		});
		
		odometer.update(jackpotNumber);
		
		timej += intsec;
		ggnrand = 1;
		
		clearInterval(intjproll);
		
		/*
		console.log('addednum '+randm);
		console.log('totalnum '+jpnum);
		console.log('interval '+nextintsecrandom);
		console.log('ododurat '+odometerint);
		console.log('------------');
		*/
		
		jackpot_roll(nextintsecrandom);

	},inttick);
}

function randBalanced(min, max) {
    const ranges = [
        [10000, 99999],     // puluhan ribu
        [100000, 999999],   // ratusan ribu
		[1000000, 9999999]   // ratusan ribu
    ];

    // pilih range secara acak
    let selected = ranges[Math.floor(Math.random() * ranges.length)];
    // random di dalam range
    return Math.floor(Math.random() * (selected[1] - selected[0] + 1)) + selected[0];
}

})( jQuery );
</script>
<!-- Scrollbar core JS -->
<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/scroll-bar/jquery.mCustomScrollbar.concat.min.js" as="script">
<script src="https://batik138-aset.pages.dev/assets/libs/scroll-bar/jquery.mCustomScrollbar.concat.min.js" defer></script>
<!-- HOME: loads on index.php -->
<!-- OWL Carousel 2 core JS -->
<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/owl-carousel/owl.carousel.min.js" as="script">
<script src="https://batik138-aset.pages.dev/assets/libs/owl-carousel/owl.carousel.min.js"></script>

<!-- Odometer core JS -->
<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/odometer/odometer.min.js" as="script">
<script src="https://batik138-aset.pages.dev/assets/libs/odometer/odometer.min.js"></script>
<script>
$ = jQuery.noConflict();
(function($) {
	//call variable php to be available in js
	//example
	//BODY_CLASS_PAGE = 'page-index';
	GRECAP_SITEKEY = '6Ld6iM4qAAAAAIBEfs7CIlctLsyOfXOvnvzOwWqQ';
	HOMEPAGE_URL = '/';
})(jQuery);
</script>
<link rel="preload" href="https://batik138-aset.pages.dev/assets/assets/js/theme-d7c57f2c4f.js" as="script">
<script src="https://batik138-aset.pages.dev/assets/assets/js/theme-d7c57f2c4f.js" defer></script>
	<link rel="preload" href="https://batik138-aset.pages.dev/assets/assets/js/theme_pid_home-055e63fa3b.js" as="script">
	<script src="https://batik138-aset.pages.dev/assets/assets/js/theme_pid_home-055e63fa3b.js" defer></script>
			<link rel="preload" href="https://batik138-aset.pages.dev/assets/assets/js/theme-FORMINBODY-c58056a0e3.js" as="script">
		<script src="https://batik138-aset.pages.dev/assets/assets/js/theme-FORMINBODY-c58056a0e3.js" defer></script>
				<link rel="preload" href="https://batik138-aset.pages.dev/assets/assets/js/theme-DATERANGEPICKER-c58056a0e3.js" as="script">
		<script src="https://batik138-aset.pages.dev/assets/assets/js/theme-DATERANGEPICKER-c58056a0e3.js" defer></script>
				<link rel="preload" href="https://batik138-aset.pages.dev/assets/assets/js/theme-TOGEL-c58056a0e3.js" as="script">
		<script src="https://batik138-aset.pages.dev/assets/assets/js/theme-TOGEL-c58056a0e3.js" defer></script>
			<link rel="preload" href="https://batik138-aset.pages.dev/assets/assets/js/theme-FORMVALIDATION-c58056a0e3.js" as="script">
	<script src="https://batik138-aset.pages.dev/assets/assets/js/theme-FORMVALIDATION-c58056a0e3.js" defer></script>
		<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/sweetalert/sweetalert.min.js" as="script">
	<script src="https://batik138-aset.pages.dev/assets/libs/sweetalert/sweetalert.min.js" defer></script>
		<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/3d-slider/modernizr.custom.53451.js" as="script">
	<script src="https://batik138-aset.pages.dev/assets/libs/3d-slider/modernizr.custom.53451.js"></script>
	
	<link rel="preload" href="https://batik138-aset.pages.dev/assets/libs/3d-slider/jquery.gallery.js" as="script">
	<script src="https://batik138-aset.pages.dev/assets/libs/3d-slider/jquery.gallery.js" defer></script>
			
		
				
		<noscript>
			<style>
				body.no-js .no-js-page {
					display: block !important;
					position: fixed;
					top: 0;
					bottom: 0;
					left: 0;
					right: 0;
				}
				.no-js-page {
					font-size: 2em;
				}
				.no-js-page .container-fluid, 
				.no-js-page .row {
					height: 100%;
				}
				.no-js-page .row > div {
					margin: auto;
					width: auto;
					align-items: center;
					display: flex;
					padding-left: 30px;
					padding-right: 30px;
				}
				.no-js-page i {
					margin-right: 0.3em;
					font-size: 5em;
				}
				.no-js-page .separator {
					opacity: 0.6;
				}
				.no-js-page {
					position: fixed;
					background-color: #000;
					width: 100%;
					height: 100%;
					left: 0;
					top: 0;
					z-index: 10005;
				}
				@media (max-width:767px) {
					.no-js-page {
						font-size: 1.5em;
					}
					.no-js-page i {
						font-size: 3em;
					}
				}
				@media (max-width:575px) {
					.no-js-page .row > div {
						display: block;
					}
					.no-js-page span {
						width: 100%;
						display: block;
						text-align: center;
					}
					.no-js-page i {
						margin-right: 0;
						margin-bottom: 0.3em;
					}
					.no-js-page i {
						margin-right: auto;
						margin-bottom: 0.3em;
						margin-left: auto;
						display: table;
						font-size: 2.5em;
					}
				}
			</style>
			<div class="no-js-page">
				<div class="container-fluid">
					<div class="row align-items-center">
						<div class="col-12">
							<i class="fa fa-ban" aria-hidden="true"></i>
							<span>
								Trang web này cần JavaScript để hoạt động. Vui lòng bật JavaScript trong cài đặt trình duyệt của bạn.
								<hr class="separator">
								This website needs javascript to operate. Please turn-on javascript setting in your browser.
							</span>
						</div>
					</div>
				</div>
			</div>
		</noscript>
		
	<script src="https://batik138-aset.pages.dev/assets/assets/js/tombro-bankverify-7cc0e5ad07.js"></script>		<!-- TOMBRO_KD_WIDGET_INJECT_V1: GLX AI Assistant chat widget (gated by DB settings.ai_chat_enabled) -->
	<script>window.KAKDOBLEH_PROXY = "#";</script>
	<!-- Sumber dipertahankan sebagai komentar: src="#" memuat halaman HTML sebagai JavaScript.
	<script src="#" defer></script>
	-->
		<!-- TOMBRO_PRESENCE_V1 --><script src="https://batik138-aset.pages.dev/assets/assets/js/tombro_game_presence-7deacd8a88.js" defer></script>
<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v31edd6df95cf4e85bb4c19e7a9bdbcba1788362987495" integrity="sha512-iIg7k2xntmwu6/uSb5tpc/hySgZc4eoL31yB29W6tJFo2akwjPWcEqnCEdJvGexCL0KEQwVYv5BlowfhVz26hg==" data-cf-beacon='{"version":"2024.11.0","token":"8db4155f540b4466b5d1e2c09d560bea","r":1,"spa":2}' crossorigin="anonymous"></script>
</body>
</html>