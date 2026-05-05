<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// ==========================
// BASIC ENV DETECTION
// ==========================
$isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true);

// ==========================
// QUERY HANDLING (SAFE)
// ==========================
if (isset($_GET['q'])) {
    $query = $_GET['q'];

    // Allow-list approach
    if ($query === 'info') {

        // phpinfo allowed ONLY on localhost
        if ($isLocal) {
            phpinfo();
            exit;
        }

        http_response_code(403);
        exit('Forbidden! phpinfo allowed ONLY on localhost');
    }

    // Unknown query
    http_response_code(404);
    exit('Invalid query parameter.');
}
$page    = $_GET['page']    ?? 'anasayfa';
$subpage = $_GET['subpage'] ?? '';

$valid_pages = ['anasayfa','matbaa','branda-baski','bayrak-baski','fuar-tanitim','blog','hakkimizda','iletisim','Animasyon','promosyon'];
if (!in_array($page, $valid_pages)) $page = 'anasayfa';

$matbaa_items = [
    'amerikan-servis'     => 'Amerikan Servis',
    'antetli-kagit'       => 'Antetli Kağıt',
    'bloknot-cesitleri'   => 'Bloknot Çeşitleri',
    'brosur-el-ilani'     => 'Broşür / El İlanı',
    'cepli-dosya'         => 'Cepli (Tanıtım) Dosyası',
    'karton-canta'        => 'Karton Çanta',
    'etiket-sticker'      => 'Etiket / Sticker',
    'kartvizit'           => 'Kartvizit',
    'magnet'              => 'Magnet (Mıknatıslı)',
    'otokopili-evraklar'  => 'Otokopili Evraklar',
    'poster-afis'         => 'Poster / Afiş',
    'zarf-cesitleri'      => 'Zarf Çeşitleri',
];
$bayrak_items = [
    'ataturk-bayraklari' => 'ATATÜRK Bayrakları',
    'gonder-bayragi'     => 'Gönder Bayrağı',
    'kirlangic-bayrak'   => 'Kırlangıç Bayrak',
    'masa-bayragi'       => 'Masa Bayrağı',
    'yelken-olta-bayrak' => 'Yelken (Olta) Bayrak',
];
$branda_items = [
    'branda-baski'    => 'Branda Baskı',
    'folyo-baski'     => 'Folyo Baskı',
    'onevision-baski' => 'Onevision Baskı',
    'mesh-branda'     => 'Mesh Branda',
];
$fuar_items = [
    'back-drop'       => 'Back Drop',
    'forex-dekota'    => 'Forex (Dekota) Baskı',
    'reklam-dubasi'   => 'Reklam Dubası',
    'roll-up-banner'  => 'Roll Up Banner',
    'tanitim-standi'  => 'Tanıtım Standı',
];
$blog_posts = [
    ['slug'=>'ankara-cankaya-kartvizit','title'=>'İstanbul Kadıköy Kartvizit Firmaları ile Profesyonel Kartvizit Çözümleri','date'=>'13.11.2025','excerpt'=>'İstanbul\'da iş dünyasında öne çıkmak ve profesyonel bir izlenim bırakmak için kaliteli kartvizitler büyük önem taşır.'],
    ['slug'=>'kartvizit-katalog-hatalar','title'=>'Kartvizit ve Katalog Baskısında Dikkat Edilecek Sık Yapılan Hatalar','date'=>'13.11.2025','excerpt'=>'Kartvizit ve katalog baskısı sürecinde yapılan küçük hatalar, marka imajınızı olumsuz etkileyebilir.'],
];

// POST handler
$form_success     = !empty($_SESSION['form_success']);
unset($_SESSION['form_success']);
$form_error       = false;
$form_phone_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ad_soyad'])) {
    $ad      = trim($_POST['ad_soyad'] ?? '');
    $telefon = trim($_POST['telefon']  ?? '');
    $urun    = trim($_POST['urun']     ?? '');

    $digits_only = preg_replace('/\D/', '', $telefon);

    if (!$ad || !$telefon) {
        $form_error = true;
    } elseif (strlen($digits_only) < 10 || strlen($digits_only) > 11) {
        $form_phone_error = true;
    } else {
        // Redirect user immediately, send email in background
        $_SESSION['form_success'] = true;
        session_write_close();

        header('Location: ' . url($page, $subpage));
        header('Connection: close');
        header('Content-Length: 0');
        while (ob_get_level()) ob_end_clean();
        flush();
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

        ignore_user_abort(true);
        set_time_limit(30);

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'kadikoymatba@gmail.com';
            $mail->Password   = 'ukfr easb pwhv quot'; // https://myaccount.google.com/apppasswords
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('kadikoymatba@gmail.com', 'Kadıköy Matbaa');
            $mail->addAddress('kadikoymatba@gmail.com');
            $mail->Subject = 'Yeni Teklif Talebi – ' . $urun;
            $mail->Body    = "Yeni bir teklif talebi:\n\nMüşteri Ad Soyad: $ad\nTelefon: $telefon\nÜrün: $urun";
            $mail->send();
        } catch (\Exception $e) {
            // silent — user already redirected
        }
        exit;
    }
}

// Contact form handler
$contact_success = !empty($_SESSION['contact_success']);
unset($_SESSION['contact_success']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['contact_name'])) {
    $cname  = trim($_POST['contact_name']  ?? '');
    $cemail = trim($_POST['contact_email'] ?? '');
    $ctel   = trim($_POST['contact_tel']   ?? '');
    $ckonu  = trim($_POST['contact_konu']  ?? '');
    $cmesaj = trim($_POST['contact_mesaj'] ?? '');

    $_SESSION['contact_success'] = true;
    session_write_close();
    header('Location: ?page=iletisim');
    header('Connection: close');
    header('Content-Length: 0');
    while (ob_get_level()) ob_end_clean();
    flush();
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    ignore_user_abort(true);
    set_time_limit(30);
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kadikoymatba@gmail.com';
        $mail->Password   = 'ukfr easb pwhv quot';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('kadikoymatba@gmail.com', 'Kadıköy Matbaa');
        $mail->addAddress('kadikoymatba@gmail.com');
        $mail->Subject = 'Yeni İletişim Mesajı – ' . ($ckonu ?: 'Konu belirtilmedi');
        $mail->Body    = "Yeni bir mesaj alındı:\n\nAd Soyad : $cname\nE-posta  : $cemail\nTelefon  : $ctel\nKonu     : $ckonu\n\nMesaj:\n$cmesaj";
        $mail->send();
    } catch (\Exception $e) { /* silent */ }
    exit;
}

function active(string $p): string { global $page; return $page === $p ? ' class="active"' : ''; }
function url(string $p, string $s = ''): string {
    $base = '?page=' . urlencode($p);
    return $s ? $base . '&subpage=' . urlencode($s) : $base;
}
function imgpath(string $p): string {
    $p = str_replace('\\', '/', $p);
    return implode('/', array_map('rawurlencode', explode('/', $p)));
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" href="favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kadıköy Matbaa – İstanbul'un Güvenilir Matbaa &amp; Baskı Firması</title>
<meta name="description" content="Kadıköy Matbaa, 2008'den beri İstanbul'da kartvizit, broşür, katalog, branda baskı, bayrak ve promosyon ürünleri hizmetleri vermektedir.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,700&family=Barlow+Condensed:wght@700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'IBM Plex Sans',sans-serif;font-size:15px;color:#0F2340;background:#e4d0b1}
#page-wrap{max-width:1400px;margin:0 auto;background:#faf5f0;box-shadow:0 0 60px rgba(0,0,0,.35);overflow:clip}
a{text-decoration:none;color:inherit}ul{list-style:none}img{max-width:100%;display:block}
:root{
  --red:#8B1A00;--red-dark:#6B0A00;--blue:#7B4A25;--blue-light:#A0612E;
  --navy:#0F2340;--gold:#C8892A;--gold-bright:#E0AA40;--light:#faf5f0;--border:#C0A07A;--border-dark:#4A1E06;--white:#faf5f0;
}

/* ── TOP BAR ── */
.topbar{background:#bf882f;color:#bf882f;font-size:11px;display:flex;align-items:center;
  justify-content:space-between;padding:0 clamp(12px,3vw,28px);height:32px;overflow:hidden;
  border-bottom:2px solid #eae0c0}
.topbar-left{color:#bf882f;font-weight:700;white-space:nowrap}
.topbar-center{flex:1;overflow:hidden;border-right:1px solid rgba(200,137,42,.4);margin:4px 8px 4px 0;height:100%;display:flex;align-items:center}
.ticker-wrap{width:100%;overflow:hidden}
.ticker-inner{display:inline-block;white-space:nowrap;animation:ticker 32s linear infinite;font-size:15px;font-weight:600;color:#451d08}
@keyframes ticker{from{transform:translateX(0)}to{transform:translateX(-50%)}}
.topbar-right{color:#451d08;font-size:13px}
.topbar-right a{color:#451d08;font-size:13px;display:flex;align-items:center;gap:6px;white-space:nowrap;transition:color .2s}
.topbar-right a:hover{color:var(--gold)}
.ig-icon{width:20px;height:20px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:11px;color:#fff;font-weight:800;flex-shrink:0;overflow:hidden}

/* ── HEADER ── */
.site-header{background:var(--border-dark);padding:6px clamp(10px,2vw,20px);
  display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.logo-since{font-size:10px;color:#C8B090;letter-spacing:1px;padding-bottom:5px}
.logo-name-row{display:flex;align-items:baseline;gap:7px;justify-content:flex-end}
.logo-kadikoy{font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:32px;letter-spacing:2px;color:#F0E0C0;line-height:1}
.logo-matbaa{font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:32Px;letter-spacing:2px;color:var(--gold);line-height:1}
.logo-sub{font-size:9.5px;color:#C8B090;letter-spacing:1.5px;text-transform:uppercase;margin-top:5px;border-top:1px solid rgba(200,137,42,.3);padding-top:5px}
.header-center{flex:0 0 auto;display:flex;align-items:stretch;background:rgba(42,15,4,.55);border-radius:8px;overflow:hidden;margin:0 20px}
.hc-ig-block{display:flex;align-items:center;gap:18px;padding:12px 24px;text-decoration:none;transition:background .2s}
.hc-ig-block:hover{background:rgba(255,255,255,.05)}
.hc-ig-icon{flex-shrink:0;width:64px;height:64px;border-radius:14px;overflow:hidden;display:flex;align-items:center;justify-content:center;border:1px solid rgba(204,35,102,.5);box-shadow:0 0 0 1px rgba(240,148,51,.2)}
.hc-ig-text{display:flex;flex-direction:column;gap:2px}
.hc-ig-label{font-size:14px;letter-spacing:2px;color:#ede0c0;font-family:'Bebas Neue',sans-serif}
.hc-ig-handle{font-size:18px;font-family:'IBM Plex Sans',sans-serif;font-weight:700;color:#bf882f;letter-spacing:.5px}
.hc-divider{width:1px;background:rgba(191,136,47,.3);margin:10px 0;flex-shrink:0}
.hc-loc-block{display:flex;align-items:center;gap:18px;padding:12px 24px}
.hc-loc-icon{flex-shrink:0;width:64px;height:64px;background:linear-gradient(135deg,rgba(183,28,28,.18),rgba(255,82,82,.06));border:1.5px solid #e53935;border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 12px rgba(229,57,53,.45),0 0 4px rgba(255,82,82,.3)}
.hc-loc-rows{display:flex;flex-direction:column;gap:6px}
.hc-loc-row{display:flex;align-items:center;gap:10px}
.hc-city-badge{font-family:'Bebas Neue',sans-serif;font-size:15px;letter-spacing:1.5px;color:#ede0c0;background:rgba(191,136,47,.2);border:1.5px solid rgba(191,136,47,.6);border-radius:4px;padding:3px 0;flex-shrink:0;width:80px;text-align:center;box-shadow:inset 0 0 6px rgba(191,136,47,.1)}
.hc-loc-names{font-size:20px;font-family:'Bebas Neue',sans-serif;color:#ede0c0;letter-spacing:.5px;text-transform:uppercase}
.hc-loc-divider{height:1px;background:linear-gradient(to right,transparent,rgba(191,136,47,.4),transparent);margin:2px 0}
.header-icon{display:flex;align-items:center;justify-content:flex-start;flex-shrink:0;margin-left:20px}
.header-icon img{width:120px;height:120px;object-fit:cover;border:3px solid var(--gold);border-radius:50%;display:block;transition:transform .3s,box-shadow .3s}
.header-icon img:hover{transform:scale(1.06);box-shadow:0 0 18px rgba(193,155,75,.45)}
.header-right-col{display:flex;flex-direction:column;align-items:flex-end;flex-shrink:0;gap:5px}
.header-logo-text{display:block;text-align:right}
.header-contact{font-size:11px;color:#C8B090;text-align:right;line-height:1.7}
.social-handles{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:4px}
.social-handles a{display:flex;align-items:center;gap:5px;color:#F0E0C0;font-size:12px;font-weight:600;transition:color .2s}
.social-handles a:hover{color:var(--gold)}
.social-handles .ig-icon{width:16px;height:16px;font-size:9px}
.header-right{font-size:13px;line-height:1.7;text-align:right}
.loc{color:var(--gold);margin-right:4px}

/* ── NAVBAR ── */
.navbar{background:#ede0c0;border-top:3px solid var(--gold);border-bottom:3px solid var(--gold);display:flex;align-items:stretch;
  position:sticky;top:0;z-index:1000;box-shadow:0 4px 14px rgba(0,0,0,.4)}
.nav-logo-li{display:flex;align-items:center}
.nav-logo{display:flex;align-items:center;padding:0 10px;transition:transform .25s,opacity .2s}
.nav-logo:hover{transform:scale(1.1);opacity:.9;background:transparent!important;border-bottom-color:transparent!important;color:inherit!important}
.nav-logo img{width:42px;height:42px;object-fit:cover;border:2px solid var(--gold);border-radius:50%;display:block}

.nav-hamburger{background:#ede0c0;color:#bf882f;border:none;width:56px;cursor:pointer;
  font-size:20px;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:background .2s}
.nav-hamburger:hover{background:var(--red)}
.nav-menu{display:flex;align-items:stretch;flex:1;flex-wrap:wrap;justify-content:center}
.nav-menu>li{position:relative}
.nav-menu>li+li::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:1px;height:22px;background:#451d08;z-index:1}
.nav-menu>li>a{display:flex;align-items:center;padding:0 14px;height:56px;font-weight:700;
  font-size:12px;letter-spacing:1px;color:#451d08;transition:color .2s,background .2s;white-space:nowrap;
  border-bottom:3px solid transparent;margin-bottom:-3px;text-transform:uppercase}
.nav-menu>li>a:hover,.nav-menu>li.active>a{color:#451d08;background:rgba(69,29,8,.1);border-bottom-color:var(--gold)}
.nav-menu>li:hover>.dropdown,.nav-menu>li:hover>.mega-dropdown{display:block}
.dropdown{display:none;position:absolute;top:100%;left:0;background:#451d08;min-width:260px;
  box-shadow:0 6px 20px rgba(0,0,0,.5);border-top:2px solid var(--gold);z-index:999;
  border:1px solid rgba(200,137,42,.3);border-top:2px solid var(--gold)}
.dropdown li{position:relative}
.dropdown li:not(:last-child)::after{content:'';position:absolute;bottom:0;left:18px;right:18px;height:1px;background:rgba(200,137,42,.2)}
.dropdown li a{display:block;padding:11px 18px;font-size:13px;font-weight:600;color:#eae0c0;transition:background .15s,color .15s}
.dropdown li a:hover{background:var(--red);color:#fff}
.mega-dropdown{display:none;position:absolute;top:100%;left:0;background:#270d04;width:820px;
  max-width:90vw;box-shadow:0 6px 20px rgba(0,0,0,.5);border-top:2px solid var(--gold);z-index:999;padding:16px 20px;
  border:1px solid rgba(200,137,42,.3);border-top:2px solid var(--gold)}
.mega-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:0}
.mega-grid li{position:relative;padding:0 8px}
.mega-grid li:not(:last-child)::after{content:'';position:absolute;bottom:0;left:14px;right:14px;height:1px;background:rgba(200,137,42,.2)}
.mega-grid li:not(:nth-child(4n))::before{content:'';position:absolute;right:0;top:8px;bottom:8px;width:1px;background:rgba(200,137,42,.2)}
.mega-grid li a{display:flex;align-items:center;gap:6px;padding:9px 6px;font-size:13px;
  font-weight:600;color:#eae0c0;transition:background .15s,color .15s}
.mega-grid li a::before{content:'›';color:var(--gold);font-size:15px}
.mega-grid li a:hover{background:var(--red);color:#fff}
.mega-grid li a:hover::before{color:#fff}
.mega-dropdown-sm{width:440px}
.mega-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:0}
.mega-grid-2 li{position:relative;padding:0 8px}
.mega-grid-2 li:not(:last-child)::after{content:'';position:absolute;bottom:0;left:14px;right:14px;height:1px;background:rgba(200,137,42,.2)}
.mega-grid-2 li:not(:nth-child(2n)):not(:last-child)::before{content:'';position:absolute;right:0;top:8px;bottom:8px;width:1px;background:rgba(200,137,42,.2)}
.mega-grid-2 li a{display:flex;align-items:center;gap:6px;padding:9px 6px;font-size:13px;font-weight:600;color:#eae0c0;transition:background .15s,color .15s}
.mega-grid-2 li a::before{content:'›';color:var(--gold);font-size:15px}
.mega-grid-2 li a:hover{background:var(--red);color:#fff}
.mega-grid-2 li a:hover::before{color:#fff}
.nav-lang{margin-left:auto;display:flex;align-items:center;padding:0 10px}
.btn-lang{background:var(--gold);color:var(--navy);border:none;cursor:pointer;padding:8px 16px;
  font-size:13px;font-weight:700;display:flex;align-items:center;gap:6px;
  transition:background .2s;white-space:nowrap}
.btn-lang:hover{background:var(--gold-bright)}

/* ── PAGE HERO ── */
.page-hero{background:#270d04;
  color:#bf882f;text-align:center;padding:14px clamp(16px,4vw,28px);border-bottom:3px solid var(--gold)}
.page-hero h1{font-family:'Bebas Neue',sans-serif;font-size:52px;letter-spacing:4px;margin-bottom:0;color:#bf882f}
.breadcrumb{font-size:15px;opacity:1;font-weight:700;color:#bf882f;letter-spacing:.4px}
.breadcrumb a{color:#bf882f}
.breadcrumb a:hover{color:var(--gold)}
.breadcrumb span{margin:0 8px;opacity:.7;color:var(--gold)}

/* ── LAYOUT ── */
.container{max-width:1240px;margin:0 auto;padding:0 clamp(14px,3vw,28px)}
.section{padding:22px 7px}
.content-wrap{display:flex;gap:30px;align-items:flex-start;flex-wrap:wrap}
.content-main{flex:1;min-width:0}
.content-sidebar{width:320px;flex-shrink:0}

/* ── HOME HERO NEW (image + 4 cards) ── */
.home-hero-new{display:grid;grid-template-columns:3fr 3fr;border-bottom:3px solid var(--gold);
  background:var(--border-dark);gap:10px;padding:clamp(12px,2vw,20px);align-items:stretch;
  height:calc(100vh - 145px);max-height:580px;min-height:360px}
.hero-img-col{position:relative;overflow:hidden;min-height:0}
.hero-img-col img{width:100%;height:100%;object-fit:contain;display:block;position:absolute;inset:0}
.hero-img-col::before,.hero-img-col::after{content:'';position:absolute;left:0;right:0;height:5px;
  background:linear-gradient(90deg,transparent 0%,var(--border-dark) 25%,#7A3A10 50%,var(--border-dark) 75%,transparent 100%);
  box-shadow:0 0 14px 3px rgba(74,30,6,.55);z-index:2}
.hero-img-col::before{top:0}
.hero-img-col::after{bottom:0}
.hero-cards-col{display:grid;grid-template-columns:1fr 1fr;gap:8px;min-height:0}
.hero-svc-card{border:3px solid var(--gold);overflow:hidden;display:flex;flex-direction:column;
  text-decoration:none;color:inherit;transition:border-color .2s,box-shadow .2s}
.hero-svc-card:hover{border-color:var(--gold);box-shadow:0 4px 16px rgba(0,0,0,.12)}
.hero-svc-card-img{position:relative;overflow:hidden;flex:1;min-height:0}
.hero-svc-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .35s;opacity:.88}
.hero-svc-card:hover .hero-svc-card-img img{transform:scale(1.06);opacity:1}
.hero-svc-overlay{position:absolute;bottom:0;left:0;right:0;
  background:linear-gradient(transparent,rgba(0,0,0,.78));padding:32px 12px 12px}
.hero-svc-overlay span{background:var(--red);color:#faf5f0;font-size:13px;font-weight:800;
  padding:5px 12px;letter-spacing:.8px;text-transform:uppercase;display:inline-block}
.hero-svc-body{padding:10px 14px 12px;background:#2A0E03;border-top:2px solid var(--gold);flex-shrink:0}
.hero-svc-body p{font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:.2px;color:#F0E0C0;line-height:1.5;min-height:3em;font-weight:400}

/* ── HOME GRID ── */
.home-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;padding:30px 0}
.home-card{border:2px solid var(--border-dark);border-top:3px solid var(--red);overflow:hidden;transition:box-shadow .2s,transform .2s}
.home-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.12);transform:translateY(-3px)}
.home-card-img{height:200px;background:#F0E8DF;position:relative;overflow:hidden}
.home-card-img img{width:100%;height:100%;object-fit:cover}
.home-card-img .badge{position:absolute;top:10px;left:10px;background:var(--red);color:#fff;
  font-size:11px;font-weight:800;padding:4px 10px;letter-spacing:.5px;text-transform:uppercase}
.home-card-body{padding:16px}
.home-card-body h3{font-size:14px;font-weight:800;margin-bottom:8px;color:var(--navy)}
.home-card-body p{font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:.2px;color:#7A6556;line-height:1.6;font-weight:400}
.read-more{display:inline-block;margin-top:10px;font-size:12px;font-weight:700;color:var(--red)}
.read-more:hover{text-decoration:underline}
.home-left{grid-column:1;grid-row:1/3}
.home-left .home-card-img{height:100%;min-height:420px}

/* ── PRODUCT DETAIL ── */
.info-box{background:#270d04;border-left:4px solid var(--gold);padding:14px 18px;margin-bottom:20px;
  font-family:'Bebas Neue',sans-serif;font-size:26px;letter-spacing:.5px;color:#bf882f;font-weight:400}
.product-specs p{padding:7px 0;border-bottom:1px solid #f0f0f0;font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:.3px;line-height:1.6;color:#0F2340;font-weight:400}
.product-desc{font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:.2px;line-height:1.8;color:#0F2340;font-weight:400;margin:8px 0 20px}
.product-specs p:last-child{border-bottom:none}
.foto-galeri{margin-top:30px}
.foto-galeri h3{font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:1px;margin-bottom:16px;color:var(--navy)}
.galeri-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.galeri-grid a{display:block;overflow:hidden;border:1px solid var(--border);cursor:zoom-in}
.galeri-labeled{border:1px solid var(--border);overflow:hidden;position:relative}
.galeri-labeled a{display:block;cursor:zoom-in;border:none}
.galeri-labeled-name{position:absolute;bottom:0;left:0;right:0;font-size:11px;font-weight:700;text-align:center;padding:3px 6px;background:#270d04;color:#eae0c0;letter-spacing:.4px;text-transform:uppercase}
.galeri-group-title{font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:1px;color:var(--navy);margin:24px 0 10px;border-bottom:2px solid var(--gold);padding-bottom:6px}
.galeri-grid img{width:100%;height:200px;object-fit:contain;background:#f5f0e8;transition:transform .25s}
.galeri-grid a:hover img{transform:scale(1.06)}

/* ── BRANDA SUBPAGE LAYOUT ── */
.branda-sp-layout{overflow:hidden}
.branda-sp-img{float:right;width:42%;margin:0 0 16px 24px}
.branda-sp-text .info-box{overflow:hidden}

/* ── LIGHTBOX ── */
#lightbox-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);
  z-index:9999;align-items:center;justify-content:center;cursor:pointer}
#lightbox-overlay.active{display:flex}
#lightbox-img{max-width:90vw;max-height:85vh;object-fit:contain;cursor:default;
  box-shadow:0 8px 48px rgba(0,0,0,.7);display:block}
#lightbox-close{position:fixed;top:18px;right:22px;background:rgba(255,255,255,.12);
  border:2px solid rgba(255,255,255,.45);color:#fff;font-size:20px;line-height:1;
  width:44px;height:44px;cursor:pointer;border-radius:50%;display:flex;
  align-items:center;justify-content:center;transition:background .2s;z-index:10000}

/* ── SIDEBAR ── */
.teklif-box{background:#270d04;border:2px solid var(--gold);padding:24px 20px;color:#bf882f;margin-bottom:24px}
.teklif-box h3{font-family:'Bebas Neue',sans-serif;font-size:26px;letter-spacing:2px;margin-bottom:18px;color:#bf882f}
.teklif-box label{font-size:13px;font-weight:600;display:block;margin-bottom:6px;color:#bf882f}
.teklif-box input{width:100%;padding:11px 14px;border:1px solid rgba(200,137,42,.5);background:#d4c6a0;color:#451d08;
  font-size:14px;margin-bottom:14px;font-family:'IBM Plex Sans',sans-serif}
.teklif-box input::placeholder{color:#9a7d50}
.btn-gonder{background:var(--red);color:#fff;border:none;cursor:pointer;padding:11px 24px;
  font-size:14px;font-weight:700;transition:background .2s;font-family:'IBM Plex Sans',sans-serif}
.btn-gonder:hover{background:var(--red-dark)}
.form-success{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.3);padding:10px;font-weight:700;font-size:14px;text-align:center;margin-top:10px}
.sidebar-hizmetler h3{font-size:16px;font-weight:800;margin-bottom:12px;color:var(--navy)}
.sidebar-links{border:1px solid var(--border);overflow:hidden}
.sidebar-links a{display:block;padding:11px 16px;font-size:13px;font-weight:600;
  border-bottom:1px solid var(--border);color:#3A2213;transition:background .15s,color .15s}
.sidebar-links a:last-child{border-bottom:none}
.sidebar-links a:hover,.sidebar-links a.active-link{background:#451d08; color:#eae0c0}
.sidebar-links a .arr{float:right;opacity:.5}
.sidebar-social{margin-top:20px}
.sidebar-social h3{font-size:16px;font-weight:800;margin-bottom:12px;color:var(--navy)}
.ig-btn{display:inline-flex;align-items:center;gap:10px;padding:10px 14px;background:#faf5f0;border:2px solid var(--border);font-weight:700;font-size:13px;color:#3A2213;transition:transform .2s,box-shadow .2s}
.ig-btn .ig-icon{width:42px;height:42px;border-radius:50%;font-size:0}
.ig-btn:hover{transform:scale(1.03);box-shadow:0 2px 8px rgba(0,0,0,.15)}

/* ── BLOG ── */
.blog-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.blog-card{display:block;color:inherit;text-decoration:none;border:2px solid var(--border-dark);border-top:3px solid var(--red);overflow:hidden;transition:box-shadow .2s}
.blog-card:hover{box-shadow:0 4px 18px rgba(0,0,0,.09)}
.blog-card-img{height:180px;overflow:hidden}
.blog-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .25s}
.blog-card:hover .blog-card-img img{transform:scale(1.04)}
.blog-card-body{padding:18px}
.blog-card-body h3{font-size:15px;font-weight:800;margin-bottom:8px;line-height:1.45;color:var(--navy)}
.blog-card-body p{font-size:13px;color:#7A6556;line-height:1.6;margin-bottom:10px}
.blog-meta{font-size:12px;color:#7A6556;display:flex;align-items:center;gap:6px}
.btn-devam{display:inline-block;margin-top:10px;font-size:13px;font-weight:700;color:var(--gold)}
.btn-devam:hover{text-decoration:underline}
.hb-head .read-more{color:var(--gold)}

/* ── HAKKIMIZDA ── */
.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:start;margin-top:10px}
.about-img{display:flex;align-items:center;justify-content:center}
.about-img img{width:100%;max-height:420px;object-fit:contain}
.about-text h2{font-family:'Bebas Neue',sans-serif;font-size:38px;letter-spacing:3px;color:#4A1E06;margin-bottom:14px}
.about-text p{font-size:14px;line-height:1.8;color:#7A6556;margin-bottom:14px}
.about-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:24px}
.stat-box{text-align:center;background:#ede0c0;padding:16px 10px;border-top:3px solid var(--gold)}
.stat-box .num{font-family:'Bebas Neue',sans-serif;font-size:38px;color:#bf882f}
.stat-box .lbl{font-size:12px;color:#7a5c30;margin-top:4px}

/* ── İLETİŞİM ── */
.contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:30px}
.contact-info h3{font-size:20px;font-weight:800;color:var(--navy);margin-bottom:16px}
.contact-item{display:flex;gap:14px;margin-bottom:16px}
.contact-icon{width:42px;height:42px;flex-shrink:0;background:var(--red);
  display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px}
.contact-item-body p{font-size:13px;color:#7A6556;line-height:1.6}
.contact-item-body strong{font-size:14px;color:var(--navy);display:block;margin-bottom:3px}
.contact-form-card{background:#faf5f0;border:3px solid var(--border-dark);padding:28px}
.contact-form-card h3{font-size:20px;font-weight:800;color:var(--navy);margin-bottom:20px}
.form-group{margin-bottom:16px}
.form-group label{font-size:13px;font-weight:600;display:block;margin-bottom:6px;color:var(--navy)}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:11px 14px;
  border:1px solid var(--border);font-size:14px;font-family:'IBM Plex Sans',sans-serif;transition:border-color .2s}
.form-group input:focus,.form-group textarea:focus{border-color:var(--red);outline:none}
.form-group textarea{height:110px;resize:vertical}
.btn-submit{background:var(--red);color:#fff;border:none;cursor:pointer;padding:12px 32px;
  font-size:15px;font-weight:700;font-family:'IBM Plex Sans',sans-serif;transition:background .2s;width:100%}
.btn-submit:hover{background:var(--red-dark)}
.map-wrap{margin-top:30px;overflow:hidden;border:2px solid var(--border)}
.map-wrap iframe{width:100%;height:300px;display:block;border:none}

/* ── SERVICE CARDS ── */
.service-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-top:10px}
.service-card{
  border:2px solid var(--border-dark);
  border-top:4px solid var(--red);
  overflow:hidden;
  transition:box-shadow .25s,transform .25s,border-color .25s;
  display:block;text-decoration:none;color:inherit;cursor:pointer;
  background:#fff;position:relative;
  box-shadow:0 2px 14px rgba(0,0,0,.08)
}
.service-card:hover{
  box-shadow:0 10px 36px rgba(0,0,0,.16);
  transform:translateY(-5px);
  border-color:var(--gold);
  border-top-color:var(--red)
}
.service-card-img{height:235px;overflow:hidden;background-color:#f5ece0;background-image:repeating-linear-gradient(-45deg,rgba(200,137,42,.13) 0,rgba(200,137,42,.13) 1px,transparent 0,transparent 50%);background-size:10px 10px;position:relative}
.service-card-img::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(to bottom,transparent 45%,rgba(26,8,0,.5));
  opacity:0;transition:opacity .3s;pointer-events:none
}
.service-card:hover .service-card-img::after{opacity:1}
.service-card-img img{width:100%;height:100%;object-fit:contain;transition:transform .35s}
.service-card:hover .service-card-img img{transform:scale(1.07)}
.service-card-body{
  padding:18px 18px 16px;
  border-top:2px solid rgba(200,137,42,.2);
  display:flex;flex-direction:column;
  background:#faf5f0
}
.service-card-body h3{font-family:'Bebas Neue',sans-serif;font-size:26px;font-weight:400;color:#4A1E06;margin-bottom:9px;line-height:1.3;letter-spacing:.5px}
.scard-desc{font-family:'Bebas Neue',sans-serif;font-size:19px;letter-spacing:.2px;color:#6A5545;line-height:1.7;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:12px;flex:1}
.scard-link{display:inline-block;font-size:13px;font-weight:700;color:var(--gold);transition:color .2s;margin-top:2px}
.service-card:hover .scard-link{color:var(--red)}

/* ── FOOTER ── */
.footer{background:#451d08;color:#bf882f;padding:48px 0 0;border-top:1px solid var(--gold)}
.footer-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr 1fr 1.1fr;gap:24px;padding-bottom:36px}
.footer-col h4{font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:1px;margin-bottom:16px;color:#bf882f}
.footer-col p{font-size:13px;line-height:1.8;color:#eae0c0;margin-bottom:12px}
.footer-col ul li{margin-bottom:7px}
.footer-col ul li a{font-size:13px;color:#eae0c0;transition:color .2s}
.footer-col ul li a:hover{color:var(--gold)}
.footer-ig{display:inline-flex;align-items:center;gap:8px;padding:7px 11px;background:#eae0c0;border:1px solid var(--gold);font-weight:700;font-size:12px;color:#270d04;margin-top:20px;transition:transform .2s,box-shadow .2s}
.footer-ig:hover{transform:scale(1.03);box-shadow:0 2px 8px rgba(0,0,0,.15)}
.footer-ig .ig-icon{width:32px;height:32px;font-size:0;border-radius:50%}
.footer-ci{display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;font-size:13px;color:#eae0c0}
.footer-ci .ico{color:var(--gold);font-size:15px;margin-top:2px;flex-shrink:0;width:20px;text-align:center}
.footer-bottom{border-top:1px solid rgba(200,137,42,.2);padding:18px 0;text-align:center;font-size:12px;color:rgba(69,29,8,.5)}
.footer-bottom-nav{display:flex;justify-content:center;gap:20px;flex-wrap:wrap;margin-bottom:8px}
.footer-bottom-nav a{color:#bf882f;font-size:13px;transition:color .2s}
.footer-bottom-nav a:hover{color:var(--gold)}
.footer-bottom p{margin-bottom:5px;color:#a86d30}
.footer-bottom .sites{color:rgba(69,29,8,.4);margin-top:6px;font-size:11px}
.footer-bottom .sites a{color:#ede0c0}

/* ── FLOATING BUTTONS ── */
.float-btns{position:fixed;left:max(6px,calc(50vw - 700px - 40px));bottom:10px;display:flex;flex-direction:column;gap:6px;z-index:2000;align-items:center}
.float-btn{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:22px;color:#fff;box-shadow:0 4px 14px rgba(0,0,0,.25);
  cursor:pointer;transition:transform .2s,box-shadow .2s}
.float-btn:hover{transform:scale(1.1);box-shadow:0 6px 20px rgba(0,0,0,.3)}
.float-phone{background:#7B4A25}
.float-wa{background:#25d366}
.float-ig{background:radial-gradient(circle at 30% 107%,#fdf497 0%,#fd5949 45%,#d6249f 60%,#285aeb 90%)}

/* ── MATBAA HİZMET STRIP ── */
.matbaa-info-strip{background:#270d04;border-top:3px solid var(--gold);border-bottom:3px solid var(--gold);padding:44px 0}
.matbaa-info-inner{display:grid;grid-template-columns:1fr 2fr;gap:50px;align-items:center}
.mis-left .mis-label{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.mis-left .mis-label span{display:inline-block;width:32px;height:3px;background:var(--gold)}
.mis-left .mis-label strong{font-size:12px;font-weight:800;letter-spacing:1.5px;color:#ede0c0;text-transform:uppercase}
.mis-left h2{font-family:'Bebas Neue',sans-serif;font-size:34px;letter-spacing:2px;color:#ede0c0;margin-bottom:14px;line-height:1.15}
.mis-left p{font-size:15px;line-height:1.8;color:#ede0c0;margin-bottom:20px}
.mis-left .btn-hakkimizda{display:inline-block;background:var(--gold);color:var(--navy);padding:11px 28px;font-weight:800;font-size:13px;letter-spacing:1px;transition:background .2s}
.mis-left .btn-hakkimizda:hover{background:var(--gold-bright)}
.mis-features{display:grid;grid-template-columns:repeat(4,1fr);gap:0}
.mis-feature{padding:12px 20px;text-align:center;border-left:1px solid rgba(200,137,42,.2)}
.mis-feature:first-child{border-left:none}
.mis-feature-icon{height:72px;display:flex;align-items:center;justify-content:center;margin-bottom:12px}
.mis-feature-icon img{object-fit:contain;display:block}
.mis-feature h4{font-size:14px;font-weight:800;color:#ede0c0;letter-spacing:.5px;margin-bottom:6px;text-transform:uppercase}
.mis-feature p{font-size:13px;color:#ede0c0;line-height:1.6}
@media(max-width:900px){
  .matbaa-info-inner{grid-template-columns:1fr}
  .mis-features{grid-template-columns:repeat(2,1fr);gap:16px}
  .mis-feature{border-left:none;border-top:1px solid rgba(200,137,42,.2);padding:16px}
}
@media(max-width:600px){
  .mis-features{grid-template-columns:1fr 1fr}
}

/* ── SECTION TITLE ── */
.section-title{font-family:'Bebas Neue',sans-serif;font-size:30px;letter-spacing:3px;color:var(--navy);margin-bottom:20px;padding-bottom:10px;border-bottom:3px solid var(--gold)}

/* ── SCROLL REVEAL SECTIONS ── */
.reveal-sections{padding:40px 0 20px}
.reveal-row{display:flex;align-items:stretch;gap:0;margin-bottom:0}
.reveal-box{flex:1;padding:44px 48px;opacity:0;transition:opacity .7s ease,transform .7s ease}
.reveal-box.from-left{transform:translateX(-80px)}
.reveal-box.from-right{transform:translateX(80px)}
.reveal-box.visible{opacity:1;transform:translateX(0)}
.reveal-box.bg-navy{background:#ede0c0;color:#bf882f}
.reveal-box.bg-red{background:var(--red);color:#fff}
.reveal-box.bg-light{background:#f0f2f5;color:#222}
.reveal-box h2{font-family:'Bebas Neue',sans-serif;font-size:32px;letter-spacing:2px;margin-bottom:20px}
.reveal-box.bg-navy h2{color:#bf882f}.reveal-box.bg-red h2{color:var(--gold)}
.reveal-box.bg-light h2{color:var(--navy)}
.reveal-tags{display:flex;flex-wrap:wrap;gap:8px}
.reveal-tag{padding:6px 14px;font-size:13px;font-weight:600;line-height:1.4;border:1px solid}
.bg-navy .reveal-tag{background:rgba(0,0,0,.06);color:#bf882f;border-color:rgba(69,29,8,.2)}
.bg-red .reveal-tag{background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.3)}
.bg-light .reveal-tag{background:#faf5f0;color:var(--navy);box-shadow:0 1px 4px rgba(0,0,0,.08);border-color:var(--border)}
.reveal-box a.reveal-link{display:inline-block;margin-top:22px;padding:10px 24px;font-weight:700;font-size:14px;transition:background .2s,color .2s}
.bg-navy .reveal-link{background:var(--red);color:#fff}
.bg-navy .reveal-link:hover{background:#c1121f}
.bg-red .reveal-link{background:#fff;color:var(--red)}
.bg-red .reveal-link:hover{background:#F0E8DF}
.bg-light .reveal-link{background:var(--navy);color:#fff}
.bg-light .reveal-link:hover{background:#081828}
@media(max-width:700px){
  .reveal-row{flex-direction:column}
  .reveal-box{padding:30px 22px}
}

/* ── REFERANSLARIMIZ ── */
.ref-section{padding:44px 0;background:var(--border-dark);border-top:4px solid var(--gold);border-bottom:4px solid var(--gold)}
.ref-head{text-align:center;margin-bottom:30px;width:100%}
.ref-head h2{font-family:'Bebas Neue',sans-serif;font-size:28px;letter-spacing:3px;color:#F0E0C0;display:block;text-align:center;width:100%}
.ref-head p{font-size:13px;color:#E8C888;font-weight:600;margin-top:6px;letter-spacing:.3px}
.ref-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:10px}
.ref-logo{display:flex;align-items:center;justify-content:center;padding:12px 10px;
  height:80px;
  background:#faf5f0;border:2px solid var(--gold);
  transition:border-color .25s,box-shadow .25s,background .25s}
.ref-logo:hover{border-color:var(--gold-bright);box-shadow:0 4px 16px rgba(0,0,0,.3);background:#fff}
.ref-logo img{width:100%;max-height:52px;object-fit:contain;
  transition:transform .3s}
.ref-logo:hover img{transform:scale(1.1)}
@media(max-width:1100px){.ref-grid{grid-template-columns:repeat(6,1fr)}}
@media(max-width:900px){.ref-grid{grid-template-columns:repeat(5,1fr)}}
@media(max-width:600px){.ref-grid{grid-template-columns:repeat(3,1fr)}}

/* ── MATBAA SLIDER ── */
.slider-section-outer{background:var(--border-dark);border-top:3px solid var(--gold);border-bottom:3px solid var(--gold)}
.slider-section{padding:36px 0 16px}
.slider-head{display:flex;align-items:center;margin:0 0 22px 22px;padding-bottom:14px;border-bottom:3px solid var(--gold)}
.slider-head h2{font-family:'Bebas Neue',sans-serif;font-size:26px;letter-spacing:2px;color:#ede0c0}
.matbaa-slider-wrap{position:relative;padding:0 22px}
.matbaa-slider{display:flex;gap:16px;overflow-x:auto;scroll-behavior:smooth;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;padding:10px 4px 25px;border-bottom:3px solid var(--gold)}
.matbaa-slider::-webkit-scrollbar{display:none}
.msld-card{flex:0 0 210px;scroll-snap-align:start;border:2px solid rgba(191,136,47,.3);border-top:3px solid var(--gold);overflow:hidden;background:#2d1205;transition:box-shadow .2s,transform .2s}
.msld-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.4);transform:translateY(-3px)}
.msld-card a{display:block;text-decoration:none;color:inherit}
.msld-img{height:165px;overflow:hidden;background:#1a0800}
.msld-img img{width:100%;height:100%;object-fit:contain;transition:transform .3s}
.msld-card:hover .msld-img img{transform:scale(1.06)}
.msld-body{padding:12px}
.msld-body h4{font-family:'Bebas Neue',sans-serif;font-size:18px;font-weight:400;letter-spacing:.3px;color:#ede0c0;margin-bottom:4px}
.msld-body .read-more{font-size:12px;color:var(--gold)}
.slider-arrow{position:absolute;top:50%;transform:translateY(-65%);background:#2d1205;border:1px solid rgba(191,136,47,.4);width:34px;height:64px;cursor:pointer;font-size:20px;color:#bf882f;z-index:10;transition:all .2s;display:flex;align-items:center;justify-content:center;padding:0}
.slider-arrow:hover{background:#3d1a08;color:var(--gold-bright);border-color:var(--gold)}
.arrow-left{left:0}
.arrow-right{right:0}

/* ── HOME ABOUT STRIP ── */
.home-about-strip{background:#270d04;padding:50px 0;border-top:3px solid var(--gold);border-bottom:3px solid var(--gold)}
.home-about-inner{display:grid;grid-template-columns:1fr 1fr;gap:50px;align-items:center}
.ha-label{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.ha-label span{display:inline-block;width:36px;height:3px;background:var(--gold)}
.ha-label strong{font-size:12px;font-weight:800;letter-spacing:1.5px;color:#bf882f;text-transform:uppercase}
.ha-text h2{font-family:'Bebas Neue',sans-serif;font-size:30px;letter-spacing:2px;color:#bf882f;margin-bottom:16px}
.ha-text p{font-size:15px;line-height:1.9;color:#ede0c0;margin-bottom:12px}
.ha-ig-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px}
.ha-ig-item{aspect-ratio:1;overflow:hidden;background:#ddd;border:1px solid rgba(200,137,42,.3)}
.ha-ig-item img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.ha-ig-item:hover img{transform:scale(1.07)}

/* ── HOME BOTTOM: BLOG + FORM ── */
.home-bottom-outer{background:#270d04;border-bottom:3px solid var(--gold)}
.home-bottom{display:grid;grid-template-columns:1fr 360px;gap:32px;padding:44px 0 50px;align-items:start}
.hb-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:12px;border-bottom:3px solid var(--gold)}
.hb-head h2{font-family:'Bebas Neue',sans-serif;font-size:24px;letter-spacing:1px;color:#ede0c0}
.hblog-cards{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.hblog-card{display:block;border:2px solid rgba(191,136,47,.3);border-top:3px solid var(--gold);overflow:hidden;transition:box-shadow .2s;color:inherit;text-decoration:none;background:#2d1205}
.hblog-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.4)}
.hblog-img{height:170px;overflow:hidden}
.hblog-img img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.hblog-card:hover .hblog-img img{transform:scale(1.05)}
.hblog-body{padding:16px}
.hblog-body h3{font-size:14px;font-weight:800;color:#ede0c0;line-height:1.45;margin-bottom:8px}
.hblog-body p{font-size:12px;color:#C8B090;line-height:1.6;margin-bottom:8px}
.home-form-box{background:#451d08;border:2px solid var(--gold);padding:28px 24px;color:#bf882f;margin-top:59px}
.home-form-box h2{font-family:'Bebas Neue',sans-serif;font-size:26px;letter-spacing:1px;margin-bottom:6px;color:#bf882f}
.home-form-box>p{font-size:13px;color:#eae0c0;margin-bottom:4px;line-height:1.6}
.home-form-box label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px;color:#bf882f}
.home-form-box input{width:100%;padding:11px 14px;border:1px solid rgba(200,137,42,.5);background:#d4c6a0;font-size:14px;color:#451d08;outline:none}
.home-form-box .btn-gonder{width:100%;margin-top:18px;background:var(--red);color:#fff;border:none;padding:12px;font-size:15px;font-weight:700;cursor:pointer;transition:background .2s}
.home-form-box .btn-gonder:hover{background:var(--red-dark)}
.home-form-success{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);padding:12px;font-weight:700;margin-bottom:14px;text-align:center}

@media(max-width:900px){
  .home-hero-new{grid-template-columns:1fr;height:auto;max-height:none}
  .hero-cards-col{border-left:none;border-top:3px solid var(--gold)}
  .home-about-inner{grid-template-columns:1fr}
  .home-bottom{grid-template-columns:1fr}
  .footer-grid{grid-template-columns:1fr 1fr}
  .about-grid,.contact-grid,.blog-grid{grid-template-columns:1fr}
  .blog-grid{gap:16px}
  .blog-card-img{height:200px}
  .blog-card-body{padding:14px}
  .content-sidebar{width:100%}
  .service-cards{grid-template-columns:1fr 1fr}
  .page-hero h1{font-size:40px;letter-spacing:3px}
  .header-center{display:none}
  .navbar{position:relative!important}
  .nav-menu>li:hover>.dropdown,.nav-menu>li:hover>.mega-dropdown{display:none!important}
  .mis-feature-icon img{filter:none!important}
  .branda-main-desc{display:none}
  .branda-main-wrap{flex-direction:column-reverse}
  .branda-main-img{flex:none;width:100%}
  .branda-main-cards{grid-template-columns:1fr}
  .branda-sp-img{float:none;width:100%;margin:0 0 16px 0}
}
@media(max-width:600px){
  .home-hero-new{min-height:auto;padding:12px}
  .hero-cards-col{grid-template-columns:1fr}
  .hblog-cards{grid-template-columns:1fr}
  .site-header{flex-direction:row;justify-content:center;align-items:center;gap:14px;padding:8px 16px;flex-wrap:nowrap}
  .header-icon{margin-left:0}
  .header-icon img{width:74px;height:74px}
  .logo-kadikoy,.logo-matbaa{font-size:24px}
  .logo-sub{font-size:8px;letter-spacing:1px}
  .logo-since{font-size:9px}
  .header-logo-text{text-align:right}
  .header-right{text-align:right}
  .nav-menu>li>a{padding:0 8px;font-size:11px}
  .nav-logo{padding:0 6px}
  .nav-logo img{width:30px;height:30px}
  .galeri-grid{grid-template-columns:repeat(2,1fr)}
  .service-cards{grid-template-columns:1fr}
  .about-stats{grid-template-columns:1fr 1fr}
  .mega-grid{grid-template-columns:1fr 1fr}
  .page-hero{padding:20px clamp(14px,4vw,24px)}
  .page-hero h1{font-size:30px;letter-spacing:2px}
  .section-title{font-size:24px;letter-spacing:2px}
  .topbar-right{display:none}
  .footer-grid{grid-template-columns:1fr;gap:20px}
  .footer-bottom-nav{gap:12px}
  .blog-grid{gap:12px}
  .blog-card-img{height:160px}
  .blog-card-body{padding:12px}
  .blog-card-body h3{font-size:14px}
  .blog-card-body p{font-size:12px}
}
</style>
</head>
<body>
<div id="page-wrap">

<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-center">
    <div class="ticker-wrap">
      <div class="ticker-inner"><span>📰 Kartvizit Baskı ve Tasarımı: Profesyonel İzlenim İçin 5 İpucu &nbsp;&nbsp;|&nbsp;&nbsp; 📰 Katalog Baskı Rehberi: Başarılı Bir Katalog İçin 7 Adım &nbsp;&nbsp;|&nbsp;&nbsp; 📰 Kartvizit ve Katalog Baskısında Dikkat Edilecek Sık Yapılan Hatalar &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span>📰 Kartvizit Baskı ve Tasarımı: Profesyonel İzlenim İçin 5 İpucu &nbsp;&nbsp;|&nbsp;&nbsp; 📰 Katalog Baskı Rehberi: Başarılı Bir Katalog İçin 7 Adım &nbsp;&nbsp;|&nbsp;&nbsp; 📰 Kartvizit ve Katalog Baskısında Dikkat Edilecek Sık Yapılan Hatalar &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
    </div>
  </div>
  <div class="topbar-right">
    <span style="display:flex;align-items:center;gap:8px">
      <span style="line-height:1.4;text-align:right">📍 Uzunçayır Cd. Konur İş Merkezi No:2 &nbsp;Kadıköy / İSTANBUL &nbsp;&nbsp;<strong style="color:#451d08">📞 +90 532 499 28 31</strong></span>
    </span>
  </div>
</div>


<!-- HEADER -->
<header class="site-header">
  <div class="header-icon">
    <a href="<?= url('anasayfa') ?>"><img src="<?= imgpath('logo/kadikoy matbaa mainpage icon.png') ?>" alt="Kadıköy Matbaa"></a>
  </div>
  <div class="header-center">
    <a href="https://www.instagram.com/kadikoy_matbaa/" target="_blank" class="hc-ig-block">
      <span class="hc-ig-icon">
        <img src="<?= imgpath('images/instagram_icon.png') ?>" alt="Instagram" style="width:100%;height:100%;object-fit:cover;display:block">
      </span>
      <span class="hc-ig-text">
        <span class="hc-ig-label">BİZİ TAKİP EDİN</span>
        <span class="hc-ig-handle">@kadikoy_matbaa</span>
      </span>
    </a>
    <div class="hc-divider"></div>
    <div class="hc-loc-block">
      <span class="hc-loc-icon">
        <svg width="44" height="44" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 3C12.477 3 8 7.477 8 13c0 7.875 10 20 10 20s10-12.125 10-20c0-5.523-4.477-10-10-10z" fill="url(#pinGrad)"/>
          <circle cx="18" cy="13" r="4" fill="white" opacity="0.95"/>
          <ellipse cx="22" cy="7" rx="3" ry="2" fill="white" opacity="0.25" transform="rotate(-30 22 7)"/>
          <defs>
            <linearGradient id="pinGrad" x1="8" y1="3" x2="28" y2="33" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="#FF5252"/>
              <stop offset="100%" stop-color="#B71C1C"/>
            </linearGradient>
          </defs>
        </svg>
      </span>
      <div class="hc-loc-rows">
        <div class="hc-loc-row"><span class="hc-city-badge">İSTANBUL</span><span class="hc-loc-names">Kadıköy · Topkapı</span></div>
        <div class="hc-loc-divider"></div>
        <div class="hc-loc-row"><span class="hc-city-badge">ANKARA</span><span class="hc-loc-names">Çankaya · İskitler · Ulus</span></div>
      </div>
    </div>
  </div>
  <a href="<?= url('anasayfa') ?>" class="header-logo-text">
    <div class="logo-since">© 2008'den beri</div>
    <div class="logo-name-row">
      <span class="logo-kadikoy">KADIKÖY</span>
      <span class="logo-matbaa">MATBAA</span>
    </div>
    <div class="logo-sub">Matbaa · Branda · Broşür · Kartvizit · Magnet · Etiket</div>
  </a>
</header>

<!-- NAVBAR -->
<nav class="navbar">
  <ul class="nav-menu">
    <li<?= active('anasayfa') ?>><a href="<?= url('anasayfa') ?>">ANASAYFA</a></li>

    <li<?= active('matbaa') ?>>
      <a href="<?= url('matbaa') ?>">MATBAA</a>
      <div class="mega-dropdown">
        <ul class="mega-grid">
          <?php foreach($matbaa_items as $slug => $label): ?>
          <li><a href="<?= url('matbaa',$slug) ?>"><?= $label ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </li>

    <li<?= active('branda-baski') ?>>
      <a href="<?= url('branda-baski') ?>">BRANDA BASKI</a>
      <div class="mega-dropdown mega-dropdown-sm">
        <ul class="mega-grid-2">
          <?php foreach($branda_items as $slug => $label): ?>
          <li><a href="<?= url('branda-baski',$slug) ?>"><?= $label ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </li>

    <li<?= active('bayrak-baski') ?>>
      <a href="<?= url('bayrak-baski') ?>">BAYRAK BASKI</a>
      <div class="mega-dropdown mega-dropdown-sm">
        <ul class="mega-grid-2">
          <?php foreach($bayrak_items as $slug => $label): ?>
          <li><a href="<?= url('bayrak-baski',$slug) ?>"><?= $label ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </li>
    <li<?= active('fuar-tanitim') ?>>
      <a href="<?= url('fuar-tanitim') ?>">FUAR/TANITIM</a>
      <div class="mega-dropdown mega-dropdown-sm">
        <ul class="mega-grid-2">
          <?php foreach($fuar_items as $slug => $label): ?>
          <li><a href="<?= url('fuar-tanitim',$slug) ?>"><?= $label ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </li>
    <li<?= active('promosyon') ?>><a href="<?= url('promosyon') ?>">PROMOSYON</a></li>
    <li<?= active('Animasyon') ?>><a href="<?= url('Animasyon') ?>">ANİMASYON</a></li>

    <li<?= active('blog') ?>>
      <a href="<?= url('blog') ?>">BLOG</a>
    </li>

    <li<?= active('hakkimizda') ?>><a href="<?= url('hakkimizda') ?>">HAKKIMIZDA</a></li>
    <li<?= active('iletisim') ?>><a href="<?= url('iletisim') ?>">İLETİŞİM</a></li>
   
  </ul>
</nav>

<!-- FLOATING BUTTONS -->
<div class="float-btns">
  <a href="tel:+905324992831" class="float-btn float-phone" title="Telefon">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
  </a>
  <a href="https://wa.me/905324992831" class="float-btn float-wa" target="_blank" title="WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 32 32" fill="white"><path d="M16 3C9 3 3 9 3 16c0 2.3.6 4.5 1.8 6.5L3 29l6.7-1.8C11.5 28.4 13.7 29 16 29c7 0 13-6 13-13S23 3 16 3zm6.4 18.4c-.3.8-1.5 1.5-2.1 1.6-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.7-.6-3-1.3-5-4.3-5.1-4.5-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 .9-2.2.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .6l-.5.6-.2.3c.2.3.8 1.2 1.6 1.9.8.8 1.8 1.3 2.1 1.4.3.1.4 0 .6-.2l.5-.6c.2-.2.4-.3.6-.2l2 .9c.2.1.4.2.4.4v.5z"/></svg>
  </a>
  <a href="https://www.instagram.com/kadikoy_matbaa/" class="float-btn float-ig" target="_blank" title="Instagram">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 3.3.1 4.8 1.7 4.9 4.9.1 1.3.1 1.6.1 4.8 0 3.2 0 3.6-.1 4.8-.1 3.2-1.7 4.8-4.9 4.9-1.3.1-1.6.1-4.9.1-3.2 0-3.6 0-4.8-.1-3.3-.1-4.8-1.7-4.9-4.9C2.2 15.6 2.2 15.2 2.2 12c0-3.2 0-3.6.1-4.8C2.4 3.9 4 2.3 7.2 2.3c1.2-.1 1.6-.1 4.8-.1zm0-2.2C8.7 0 8.3 0 7.1.1 2.7.3.3 2.7.1 7.1.0 8.3 0 8.7 0 12s0 3.7.1 4.9c.2 4.4 2.6 6.8 7 7C8.3 24 8.7 24 12 24s3.7 0 4.9-.1c4.4-.2 6.8-2.6 7-7 .1-1.2.1-1.6.1-4.9s0-3.7-.1-4.9c-.2-4.4-2.6-6.8-7-7C15.7 0 15.3 0 12 0zm0 5.8a6.2 6.2 0 1 0 0 12.4A6.2 6.2 0 0 0 12 5.8zm0 10.2a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.4-11.8a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z"/></svg>
  </a>
</div>

<!-- ═══════ PAGE CONTENT ═══════ -->
<?php if($page==='anasayfa'): ?>

<!-- HERO + 4 CARDS -->
<div class="home-hero-new">
  <div class="hero-img-col">
    <img src="<?= imgpath('images/acilis sayfasi/ana sayfa ilk resim.jpeg') ?>" alt="Kadıköy Matbaa">
  </div>
  <div class="hero-cards-col">
    <a class="hero-svc-card" href="<?= url('branda-baski') ?>">
      <div class="hero-svc-card-img">
        <img src="<?= imgpath('images/acilis sayfasi/branda baski.jpeg') ?>" alt="Branda">
        <div class="hero-svc-overlay"><span>BRANDA BASKI</span></div>
      </div>
      <div class="hero-svc-body"><p>Branda, Folyo, Onevision, Mesh baskı. AVM kaplama...</p></div>
    </a>
    <a class="hero-svc-card" href="<?= url('promosyon') ?>">
      <div class="hero-svc-card-img">
        <img src="<?= imgpath('images/acilis sayfasi/promosyon urunleri.jpeg') ?>" alt="Promosyon">
        <div class="hero-svc-overlay"><span>PROMOSYON ÜRÜNLERİ</span></div>
      </div>
      <div class="hero-svc-body"><p>Ajanda, Kalem, Bloknot, Kupa Bardak, Vip Setler, Termos...</p></div>
    </a>
    <a class="hero-svc-card" href="<?= url('bayrak-baski') ?>">
      <div class="hero-svc-card-img">
        <img src="<?= imgpath('images/acilis sayfasi/bayrak.jpeg') ?>" alt="Bayrak">
        <div class="hero-svc-overlay"><span>BAYRAK</span></div>
      </div>
      <div class="hero-svc-body"><p>Gönder Bayrağı, Masa Bayrağı, Yelken Bayrak, Kırlangıç Bayrak...</p></div>
    </a>
    <a class="hero-svc-card" href="<?= url('fuar-tanitim') ?>">
      <div class="hero-svc-card-img">
        <img src="<?= imgpath('images/acilis sayfasi/fuar-stand.jpg') ?>" alt="Fuar">
        <div class="hero-svc-overlay"><span>FUAR / TANITIM / REKLAM</span></div>
      </div>
      <div class="hero-svc-body"><p>Roll Up Banner, Back Drop, Reklam Dubası, Tanıtım Standı...</p></div>
    </a>
  </div>
</div>

<!-- MATBAA HİZMETLERİMİZ INFO STRIP -->
<div class="matbaa-info-strip">
  <div class="container">
    <div class="matbaa-info-inner">
      <div class="mis-left">
        <div class="mis-label"><span></span><strong>Neden Biz?</strong></div>
        <h2>Kadıköy'de Matbaacılık<br>Bizim İşimiz</h2>
        <p>Yılların tecrübesi ve son teknoloji makinelerimizle kalitenizi en iyi şekilde yansıtıyoruz.</p>
      </div>
      <div class="mis-features">
        <div class="mis-feature">
          <div class="mis-feature-icon"><img src="<?= imgpath('images/ekleme satir/ana sayfa  da/logo 1.png') ?>" alt="Hızlı Teslimat" style="width:56px;height:56px;filter:drop-shadow(0 0 2px #C8892A) drop-shadow(0 0 2px #E0AA40) drop-shadow(0 0 2px #C8892A)"></div>
          <h4>Hızlı Teslimat</h4>
          <p>Zamanında teslim sözümüz var.</p>
        </div>
        <div class="mis-feature">
          <div class="mis-feature-icon"><img src="<?= imgpath('images/ekleme satir/ana sayfa  da/logo 2.png') ?>" alt="Üstün Kalite" style="width:56px;height:56px;filter:drop-shadow(0 0 2px #C8892A) drop-shadow(0 0 2px #E0AA40) drop-shadow(0 0 2px #C8892A)"></div>
          <h4>Üstün Kalite</h4>
          <p>Net baskı, canlı renkler, profesyonel sonuçlar.</p>
        </div>
        <div class="mis-feature">
          <div class="mis-feature-icon"><img src="<?= imgpath('images/ekleme satir/ana sayfa  da/logo 3.png') ?>" alt="Kolay İletişim" style="width:44px;height:44px;filter:drop-shadow(0 0 3px #C8892A) drop-shadow(0 0 2px #E0AA40) drop-shadow(0 0 2px #C8892A)"></div>
          <h4>Kolay İletişim</h4>
          <p>WhatsApp ve telefon desteği ile yanınızdayız.</p>
        </div>
        <div class="mis-feature">
          <div class="mis-feature-icon"><img src="<?= imgpath('images/ekleme satir/ana sayfa  da/logo 4.png') ?>" alt="Güvenilir Hizmet" style="width:72px;height:72px;filter:drop-shadow(0 0 1px #C8892A) drop-shadow(0 0 3px #C8892A) drop-shadow(0 0 2px #E0AA40) drop-shadow(0 0 1px #C8892A)"></div>
          <h4>Güvenilir Hizmet</h4>
          <p>Kadıköy'ün tercih ettiği matbaa.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MATBAA HİZMETLERİMİZ SLIDER -->
<div class="slider-section-outer">
<div class="container slider-section">
  <div class="slider-head">
    <h2>Matbaa Hizmetlerimiz</h2>
  </div>
  <?php
  $msld_map = [
    'amerikan-servis'    => imgpath('images/01 amerikan servis/american servis 1.jpg'),
    'antetli-kagit'      => imgpath('images/02 antetli kagit/an01.jpg'),
    'bloknot-cesitleri'  => imgpath('images/03 bloknot cesitleri/spiralli bloknot.jpg'),
    'brosur-el-ilani'    => imgpath('images/04 brosur-el ilani/brosur 01 m.jpg'),
    'cepli-dosya'        => imgpath('images/05 cepli tanitim dosyasi/cepli dosya 1.jpg'),
    'karton-canta'       => imgpath('images/06 karton canta/karton canta 1.jpg'),
    'etiket-sticker'     => imgpath('images/09 etiket/11 etiket/a5 ebat.jpg'),
    'kartvizit'          => imgpath('images/08 kartvizit/kart mockup 4.jpg'),
    'magnet'             => imgpath('images/10 magnet/magnet 1.jpg'),
    'otokopili-evraklar' => imgpath('images/11 otokopili evraklar/otokopili evrak 1.jpg'),
    'poster-afis'        => imgpath('images/12 poster-afis/afis01.jpg'),
    'zarf-cesitleri'     => imgpath('images/13 zarf/zarf 1.jpg'),
  ];
  ?>
  <div class="matbaa-slider-wrap">
    <button class="slider-arrow arrow-left" onclick="moveSlider(-1)">&#8249;</button>
    <div class="matbaa-slider" id="matbaaSlider">
      <?php foreach($matbaa_items as $slug => $label): ?>
      <div class="msld-card">
        <a href="<?= url('matbaa',$slug) ?>">
          <div class="msld-img"><img src="<?= $msld_map[$slug] ?? 'https://placehold.co/600x400?text=Resim' ?>" alt="<?= $label ?>"></div>
          <div class="msld-body">
            <h4><?= $label ?></h4>
            <span class="read-more">Detaylar »</span>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <button class="slider-arrow arrow-right" onclick="moveSlider(1)">&#8250;</button>
  </div>
</div>
</div>

<!-- HAKKIMIZDA STRIP -->
<div class="home-about-strip">
  <div class="container">
    <div class="home-about-inner">
      <div class="ha-text">
        <div class="ha-label"><span></span><strong>Kadıköy Matbaa</strong></div>
        <h2>İstanbul'un Güvenilir Baskı &amp; Reklam Ajansı</h2>
        <p>Kadıköy Matbaa olarak yaklaşık 15 yıldır baskı ve matbaa sektöründe hizmet veriyoruz. Kurulduğumuz günden bu yana önceliğimiz; işini ciddiye alan, zamanında teslim eden ve müşterisinin ne istediğini gerçekten anlayan bir ekip olmak oldu.</p>
        <p>Küçük işletmelerden kurumsal firmalara kadar geniş bir müşteri kitlesiyle çalışıyor, her projeye aynı özenle yaklaşıyoruz. Kartvizitten broşüre, cepli dosyadan özel tasarım baskı işlerine kadar farklı ihtiyaçlara pratik ve kaliteli çözümler sunuyoruz.</p>
        <p>Teknolojiyi yakından takip ediyor, üretim süreçlerimizi sürekli geliştiriyoruz. Ama bizim için asıl önemli olan; işin sonunda ortaya çıkan ürün kadar, süreç boyunca kurduğumuz güven ve iletişim.</p>
        <p>Bugün geldiğimiz noktada en büyük referansımız, bizimle tekrar çalışmayı tercih eden müşterilerimiz.</p>
      </div>
      <div>
        <a href="https://www.instagram.com/kadikoy_matbaa/" target="_blank" style="display:flex;align-items:center;justify-content:center;height:100%">
          <img src="<?= imgpath('logo/kadikoy matbaa mainpage icon.png') ?>" alt="Kadıköy Matbaa" style="max-width:100%;max-height:340px;object-fit:contain;display:block;margin:auto">
        </a>
      </div>
    </div>
  </div>
</div>

<!-- REFERANSLARIMIZ -->
<div class="ref-section">
  <div class="container">
    <div class="ref-head">
      <h2>Referanslarımız</h2>
      <p>Bize güvenen markalar</p>
    </div>
    <div class="ref-grid">
      <?php
      $ref_logos = [
        '33 tantuni mersin.png','ahmet usta tac doner.png','akg.png','akinci restaura.png',
        'ankagross.png','asil et.png','aslan donercim.png',
        'atelier derin.png','barcode.png','battal cafe.png','battin anteplim.png',
        'bedir usta.png','belen terzi.png','bi sos mars.png','bitat doner.png',
        'budaklar pide.png','bufalo joe.png','birlik menfez.png','can pide firin.png',
        'carbucs.png','celil baba.png','cevheroglu su.png',
        'cihan tantuni.png','damak pide lahmacun .png','doy doy.png',
        'dogan doner.png','donerci samet usta.png','durumcu erkan.png','durumcu fatih usta.png',
        'durumcu yusuf usta.png','elifin lezzetleri.png','elta fast food .png',
        'gazioglu doner cafe mars.png','gpteks.png','gunesin d.png','hatipoglu.png',
        'igm.png','komagene.png','kozz kanat.png','midyeci serdar.png','mn dones.png',
        'tedavi tost.png','urfa citir.png','vedat usta.png',
        'whatsapp_image_2026-01-13_at_20.45.31-removebg-preview.png','yeni diyarbakir  pide.png',
        'cinilay..png','cig koftecim.png','cinar eldektronik.png','is pool.png','sefikcan ekleristan.png', 'ornek elektronik .png','polat.png','sehir lezzetleri.png', 'dincer.png','logo.png',
      ];
      $ref_base = 'images/referanslar/referanslar/';
      foreach($ref_logos as $logo):
        $lsrc = imgpath($ref_base . $logo);
        $lname = htmlspecialchars(pathinfo($logo, PATHINFO_FILENAME));
      ?>
      <div class="ref-logo">
        <img src="<?= $lsrc ?>" alt="<?= $lname ?>">
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- BLOG + FORM -->
<div class="home-bottom-outer">
<div class="container">
  <div class="home-bottom">
    <div>
      <div class="hb-head">
        <h2>Blog &amp; Teknik Bilgiler</h2>
        <a class="read-more" href="<?= url('blog') ?>">Tümünü Gör →</a>
      </div>
      <div class="hblog-cards">
        <?php
        $hbi=[imgpath('images/blog/blog-1.jpg'),imgpath('images/blog/blog-2.jpg')];
        foreach(array_slice($blog_posts,0,2) as $k=>$bp): ?>
        <a class="hblog-card" href="<?= url('blog',$bp['slug']) ?>">
          <div class="hblog-img"><img src="<?= $hbi[$k] ?>" alt="<?= $bp['title'] ?>"></div>
          <div class="hblog-body">
            <h3><?= $bp['title'] ?></h3>
            <p><?= $bp['excerpt'] ?></p>
            <div class="blog-meta"><span>🕐</span><?= $bp['date'] ?></div>
            <span class="btn-devam">Detaylar »</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="home-form-box">
      <h2>Bize Yazın</h2>
      <p>Matbaa ve baskı hizmetlerimiz için hemen teklif almak istiyorsanız, bizimle iletişime geçebilirsiniz.</p>
      <?php if($form_success): ?><div class="home-form-success">✔ Talebiniz alındı, en kısa sürede dönüş yapılacak!</div><?php endif; ?>
      <form method="POST" action="<?= url('anasayfa') ?>" onsubmit="return validateTeklif(this, event)">
        <input type="hidden" name="urun" value="Genel Teklif">
        <label>Ad Soyad</label>
        <input type="text" name="ad_soyad" placeholder="Adınız Soyadınız" required>
        <label>Telefon</label>
        <input type="tel" name="telefon" id="teklif-telefon" placeholder="05xx xxx xx xx" required oninput="this.setCustomValidity('')">
        <button type="submit" class="btn-gonder">Gönder</button>
      </form>
    </div>
  </div>
</div>
</div>


<?php elseif($page==='promosyon'): ?>

<div class="page-hero">
  <h1>Promosyon Ürünleri</h1>
</div>
<div class="container section">
  <div class="content-wrap">
    <div class="content-main">
      <div class="info-box">Ajanda, Kalem, Bloknot, Kupa Bardak, Termos, VIP Setler ve daha fazlası</div>
      <div class="product-specs">
        <p>▸ Kurumsal logolu promosyon ürünleri tasarım ve baskısı</p>
        <p>▸ Geniş ürün yelpazesi: ajanda, kalem, kupa, termos, çanta, usb, powerbank</p>
        <p>▸ Min. 50 adet üretim, hızlı teslimat</p>
        <p>▸ Özel ambalaj ve hediye seti seçenekleri mevcuttur</p>
      </div>
      <p class="product-desc">
        Kadıköy Matbaa olarak kurumunuza özel promosyon ürünleri üretiminde geniş bir ürün portföyü sunmaktayız. Logolu ajanda, kalem, kupa bardak, termos, bez çanta, USB bellek, powerbank ve VIP setler başta olmak üzere yüzlerce farklı promosyon kalemi temin edilmektedir. Tasarım aşamasından üretime, teslimat ve ambalajlamaya kadar tüm süreç uzman ekibimiz tarafından yönetilmektedir.
      </p>
      <div class="foto-galeri">
        <h3>Foto Galeri</h3>
        <p class="galeri-group-title">Kalem</p>
        <div class="galeri-grid">
          <?php $kalem_imgs=['kalem  00.jpg','kalem 01.jpg','kalem 02.jpg','kalem 03.jpg','kalem 04.jpg','kalem 05.jpg'];
          foreach($kalem_imgs as $f):
            $src=imgpath('images/promosyon/kalem/'.$f);
          ?><a href="#" onclick="openLightbox('<?= $src ?>');return false;"><img src="<?= $src ?>" alt="Kalem" style="object-fit:contain;background:#f5f0e8"></a><?php endforeach; ?>
        </div>
        <p class="galeri-group-title">Çakmak</p>
        <div class="galeri-grid" style="align-items:start">
          <?php $cakmak_imgs=['cakmak 1.jpg','cakmak 2.jpg','cricket.jpg','doldurulabilir.jpg','telefon tutuculu.jpg','turbo ruzgar.jpg'];
          $cakmak_no_label=['cakmak 1.jpg','cakmak 2.jpg'];
          foreach($cakmak_imgs as $f):
            $src=imgpath('images/promosyon/cakmak/'.$f);
            $name=pathinfo($f,PATHINFO_FILENAME);
          ?>
          <?php if(in_array($f,$cakmak_no_label)): ?>
          <a href="#" onclick="openLightbox('<?= $src ?>');return false;"><img src="<?= $src ?>" alt="<?= $name ?>" style="object-fit:contain;background:#f5f0e8"></a>
          <?php else: ?>
          <div class="galeri-labeled">
            <a href="#" onclick="openLightbox('<?= $src ?>');return false;"><img src="<?= $src ?>" alt="<?= $name ?>" style="object-fit:contain;background:#f5f0e8"></a>
            <div class="galeri-labeled-name"><?= $name ?></div>
          </div>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <aside class="content-sidebar">
      <div class="teklif-box">
        <h3>Hemen Teklif Alın !</h3>
        <?php if($form_success): ?><div class="form-success">✔ Talebiniz alındı, en kısa sürede dönüş yapılacak!</div><?php endif; ?>
        <form method="POST" action="<?= url('promosyon') ?>" onsubmit="return validateTeklif(this, event)">
          <input type="hidden" name="urun" value="Promosyon Ürünleri">
          <label>Ad Soyad</label>
          <input type="text" name="ad_soyad" placeholder="Adınız Soyadınız" required>
          <label>Telefon</label>
          <input type="tel" name="telefon" id="teklif-telefon" placeholder="05xx xxx xx xx" required oninput="this.setCustomValidity('')">
          <button type="submit" class="btn-gonder">Gönder</button>
        </form>
      </div>
      <div class="sidebar-social">
        <a class="ig-btn" href="https://www.instagram.com/kadikoy_matbaa/" target="_blank">
          <span class="ig-icon"><img src="<?= imgpath('images/instagram_icon.png') ?>" alt="Instagram" style="width:100%;height:100%;object-fit:cover;display:block"></span>Kadıköy Matbaa Instagram
        </a>
      </div>
    </aside>
  </div>
</div>

<?php elseif($page==='matbaa' && !$subpage): ?>

<div class="page-hero">
  <h1>Matbaa Hizmetlerimiz</h1>
</div>
<div class="container section">
  <div class="service-cards">
    <?php
    $card_imgs = [
      'amerikan-servis'    => [imgpath('images/01 amerikan servis/american servis 1.jpg'),'cover'],
      'antetli-kagit'      => [imgpath('images/02 antetli kagit/an01.jpg'),'cover'],
      'bloknot-cesitleri'  => [imgpath('images/03 bloknot cesitleri/spiralli bloknot.jpg'),'contain'],
      'brosur-el-ilani'    => [imgpath('images/04 brosur-el ilani/brosur 01 m.jpg'),'contain'],
      'cepli-dosya'        => [imgpath('images/05 cepli tanitim dosyasi/cepli dosya 1.jpg'),'cover'],
      'karton-canta'       => [imgpath('images/06 karton canta/karton canta 1.jpg'),'contain'],
      'etiket-sticker'     => [imgpath('images/09 etiket/11 etiket/a5 ebat.jpg'),'cover'],
      'kartvizit'          => [imgpath('images/08 kartvizit/kart mockup 4.jpg'),'contain'],
      'magnet'             => [imgpath('images/10 magnet/magnet 1.jpg'),'cover'],
      'otokopili-evraklar' => [imgpath('images/11 otokopili evraklar/otokopili evrak 1.jpg'),'cover'],
      'poster-afis'        => [imgpath('images/12 poster-afis/afis01.jpg'),'contain'],
      'zarf-cesitleri'     => [imgpath('images/13 zarf/zarf 1.jpg'),'cover'],
    ];
    $matbaa_card_descs = [
      'amerikan-servis'    => 'Restoran, kafe ve işletmeler için 20×30 veya 28×40 cm ebatında, 105–115 gr kuşe kağıda basılan profesyonel sunum altlıkları; 2000 adet ve katları şeklinde üretilir.',
      'antetli-kagit'      => 'Kurumsal kimliğinizi yansıtan A4 veya A5 formatlı antetli kağıtlar; tek renkli veya tam renkli, minimum 250 adet üretim seçeneğiyle sunulmaktadır.',
      'bloknot-cesitleri'  => 'Tutkallı veya spiralli bloknot çeşitleri; A6\'dan A4\'e kadar tüm ebatlarda, kurumsal logolu ve renkli kapaklı olarak minimum 100 cilt üretim imkânıyla üretilir.',
      'brosur-el-ilani'    => 'A4, A5 veya A6 ebatlarında katlı broşür ve el ilanları 115–135 gr kuşe kağıda 4+4 renkli olarak basılır; minimum 500 adet, hızlı teslimat garantisiyle.',
      'cepli-dosya'        => 'Kurumsal tanıtım dosyaları; özel baskılı kapak ve cep tasarımıyla markanızı en iyi şekilde temsil eder, toplantı ve tanıtım sunumları için ideal çözümdür.',
      'karton-canta'       => 'Logo ve tasarımınızla kişiselleştirilen karton çantalar; ürün sunumlarında ve marka tanıtımında şık, dayanıklı ve sürdürülebilir bir ambalaj çözümü sunar.',
      'etiket-sticker'     => 'Her boyut ve şekilde tasarlanabilen etiket ve stickerlar; ürün ambalajından vitrin süslemesine geniş kullanım alanıyla marka görünürlüğünü artırır.',
      'kartvizit'          => '85×55 mm standart ölçüde, 350 gr kuşe kağıda mat veya parlak selofan kaplama ile çift yüz renkli baskı; minimum 250 adet, hızlı teslimat garantisiyle.',
      'magnet'             => 'Mıknatıslı kartvizit ve tanıtım magnetleri; buzdolabı, metal yüzey ve panolarda uzun süre görünür kalan, kalıcı etki yaratan tanıtım ürünleridir.',
      'otokopili-evraklar' => 'Sipariş formu, irsaliye ve fatura gibi belgeler için kullanılan otokopili evraklar; çift veya üç nüshalı, hızlı ve güvenilir üretim seçenekleriyle sunulur.',
      'poster-afis'        => 'Renkli ve dikkat çekici afiş baskıları; ürün tanıtımı, etkinlik duyurusu ve mağaza içi dekorasyonda geniş kullanım alanı ve yüksek görünürlük sunar.',
      'zarf-cesitleri'     => 'Kurumsal logolu zarf çeşitleri; A4, A5 ve diğer standart ebatlarda tek veya çok renkli olarak üretilir, kurumsal yazışmalarda profesyonel görünüm sağlar.',
    ];
    foreach($matbaa_items as $slug=>$label):
      [$cimg,$cfit] = $card_imgs[$slug] ?? ['https://placehold.co/600x400?text=Resim','cover'];
      $extra = $slug==='poster-afis' ? ' style="transform:scale(1.25);transform-origin:center"' : '';
      $cstyle = $cfit==='contain' ? ($extra ?: '') : '';
      $cdesc = $matbaa_card_descs[$slug] ?? 'Profesyonel baskı kalitesiyle hızlı ve uygun fiyata temin edilir.';
    ?>
    <a class="service-card" href="<?= url('matbaa',$slug) ?>">
      <div class="service-card-img"><img src="<?= $cimg ?>" alt="<?= $label ?>"<?= $cstyle ?>></div>
      <div class="service-card-body">
        <h3><?= $label ?></h3>
        <p class="scard-desc"><?= $cdesc ?></p>
        <span class="scard-link">Detaylar »</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<?php elseif($page==='matbaa' && $subpage):
  $label = $matbaa_items[$subpage] ?? ucfirst(str_replace('-',' ',$subpage));
  $specs = [
    'amerikan-servis'   =>['Ölçü: 20×30 cm / 28×40 cm','105 gr / 115 Kuşe kağıt veya 80-90-100 gr 1. Hamur kağıda baskı yapılır','2000 adet ve katları şeklinde üretilir','2-5 günde teslim edilir'],
    'antetli-kagit'     =>['Ölçü: A4 (21×29.7 cm) – A5','80-90-100 gr 1. Hamur veya 115 Kuşe kağıt','Tek renk veya renkli baskı','Min. 250 adet üretim'],
    'bloknot-cesitleri' =>['A6 – A5 – A4 Tutkallı Bloknot','Soldan veya Üstten Spiralli + Renkli Kapaklı','Tek renk veya renkli baskılı','Min. 100 cilt üretim'],
    'kartvizit'         =>['Ölçü: 85×55 mm standart','350 gr Kuşe kağıt, Mat veya Parlak Selofan','Çift yüz renkli baskı','Min. 250 adet, hızlı teslimat'],
    'brosur-el-ilani'   =>['A4, A5, A6 katlı broşür','115 gr veya 135 gr kuşe kağıt','4+4 renkli baskı','Hızlı teslimat, min. 500 adet'],
  ];
  $item_specs = $specs[$subpage] ?? ['105 gr / 115 Kuşe kağıt veya 80-90-100 gr 1. Hamur kağıda baskı yapılır','Renk seçenekleri mevcuttur','Hızlı ve kaliteli üretim','2-5 günde teslim edilir'];
  $gi_map = [
    'amerikan-servis'    => ['images/01 amerikan servis/american servis 1.jpg','images/01 amerikan servis/american servis 2.jpg','images/01 amerikan servis/american servis 3.jpg','images/01 amerikan servis/american servis 4.jpg','images/01 amerikan servis/american servis 5.jpg'],
    'antetli-kagit'      => ['images/02 antetli kagit/an01.jpg','images/02 antetli kagit/an02.jpg','images/02 antetli kagit/an03.jpg','images/02 antetli kagit/an06.jpg','images/02 antetli kagit/an12.jpg'],
    'bloknot-cesitleri'  => ['images/03 bloknot cesitleri/spiralli bloknot.jpg','images/03 bloknot cesitleri/spiralli bloknot1.jpg','images/03 bloknot cesitleri/tutkalli bloknot 01.jpg','images/03 bloknot cesitleri/tutkalli bloknot 02.jpg','images/03 bloknot cesitleri/tutkalli bloknot 03.jpg','images/03 bloknot cesitleri/tutkalli bloknot 04.jpg','images/03 bloknot cesitleri/tutkalli bloknot 05.jpg'],
    'brosur-el-ilani'    => ['images/04 brosur-el ilani/brosur 01 m.jpg','images/04 brosur-el ilani/brosur 02 m.jpg','images/04 brosur-el ilani/brosur 03 m.jpg','images/04 brosur-el ilani/brosur 04 m.jpg','images/04 brosur-el ilani/brosur 05 m.jpg','images/04 brosur-el ilani/iki kirimli a4.jpg','images/04 brosur-el ilani/tek kirimli a4.jpg'],
    'cepli-dosya'        => ['images/05 cepli tanitim dosyasi/cepli dosya 1.jpg','images/05 cepli tanitim dosyasi/cepli dosya 2.jpg','images/05 cepli tanitim dosyasi/cepli dosya 3.jpg','images/05 cepli tanitim dosyasi/cepli dosya 4.jpg','images/05 cepli tanitim dosyasi/cepli dosya 5.jpg'],
    'karton-canta'       => ['images/06 karton canta/karton canta 1.jpg','images/06 karton canta/karton canta 2.jpg','images/06 karton canta/karton canta 3.jpg','images/06 karton canta/karton canta 4.jpg','images/06 karton canta/karton canta 5.jpg'],
    'etiket-sticker'     => ['images/09 etiket/11 etiket/a5 ebat.jpg','images/09 etiket/11 etiket/standart.jpg'],
    'kartvizit'          => ['images/08 kartvizit/kart mockup 4.jpg','images/08 kartvizit/kart mockup1.jpg','images/08 kartvizit/kart mockup2.jpg','images/08 kartvizit/kart mockup3.jpg','images/08 kartvizit/kart mockup5.jpg'],
    'magnet'             => ['images/10 magnet/magnet 1.jpg','images/10 magnet/magnet 2.jpg','images/10 magnet/magnet 3.jpg','images/10 magnet/magnet 4.jpg','images/10 magnet/magnet 5.jpg'],
    'otokopili-evraklar' => ['images/11 otokopili evraklar/otokopili evrak 1.jpg','images/11 otokopili evraklar/otokopili evrak 2.jpg','images/11 otokopili evraklar/otokopili evrak 3.jpg','images/11 otokopili evraklar/otokopili evrak 4.jpg','images/11 otokopili evraklar/otokopili evrak 5.jpg'],
    'poster-afis'        => ['images/12 poster-afis/afis01.jpg','images/12 poster-afis/afis02.jpg','images/12 poster-afis/afis03.jpg','images/12 poster-afis/afis04.jpg','images/12 poster-afis/afis05.jpg'],
    'zarf-cesitleri'     => ['images/13 zarf/zarf 1.jpg','images/13 zarf/zarf 2.jpg','images/13 zarf/zarf 3.jpg','images/13 zarf/zarf 4.jpg','images/13 zarf/zarf 5.jpg'],
  ];
  $gi = isset($gi_map[$subpage]) ? array_map('imgpath', $gi_map[$subpage]) : array_fill(0, 8, 'https://placehold.co/600x400?text=Resim');
  $matbaa_infos = [
    'amerikan-servis'   => '105 gr / 115 Kuşe kağıt veya 80-90-100 gr 1. Hamur kağıda baskı yapılır',
    'antetli-kagit'     => 'A4 ebat (21×29,7 cm) 80-90-100-110 gr 1. Hamur kağıda, tek renk veya renkli baskılı',
    'bloknot-cesitleri' => 'Tutkallı veya spiralli bloknot: A6, A5, A4 ebatlarda; min. 100 cilt üretim.',
    'brosur-el-ilani'   => 'A5, A7 ve A4 ebat düz ve/veya katlamalı broşür; tasarım desteğimiz vardır.',
    'cepli-dosya'       => '350 gr mat veya parlak kuşe kağıt, tek yön veya çift taraf baskılı.',
    'etiket-sticker'    => 'Her ebat sticker basımı; sıvıdan etkilenmeyen, A5 veya A4 ebat baskı.',
    'karton-canta'      => '210-230 gr Amerikan Bristol kağıt baskı; hazır ipli, mat veya parlak selefonlu.',
    'kartvizit'         => 'Her firmanın vazgeçilmezi kartvizit; tasarım ve kağıt kalitesini iyi seçmeniz gereklidir.',
    'magnet'            => 'Buzdolabı reklamlarınız için mıknatıslı etiket; min. 1000 adet üretilir.',
    'otokopili-evraklar'=> 'A4-A5 ebat; 1+1, 1+2, 1+3 suret seçenekleriyle tek renk veya renkli baskılı.',
    'poster-afis'       => 'Her ebat afiş üretimi; branda, folyo veya kağıt. Üretim süresi 1-3 gün.',
    'zarf-cesitleri'    => 'Diplomat zarf, torba zarf ve A5 orta zarf; tek renk veya renkli baskılı, kendinden yapışkanlı.',
  ];
  $matbaa_descs = [
    'amerikan-servis'   => '105 gr / 115 Kuşe kağıt veya 80-90-100 gr 1. Hamur kağıda baskı yapılır<br>Ölçü: 20×30 cm / 28×40 cm<br>2000 adet ve katları şeklinde üretilir.<br>2-5 günde teslim edilir.',
    'antetli-kagit'     => 'A4 ebat (21×29,7 cm) 80-90-100-110 gr 1. Hamur kağıda, tek renk veya renkli baskılı<br>Extra Baskı: Yaldız, Gofre baskı yapılır<br>500 adet ve katları şeklinde 3-6 günde teslim edilir.<br><br>A4 antetli kağıtlarınız; seminer, toplantılarınız ve teklif evraklarınızı sunmak için idealdir.',
    'bloknot-cesitleri' => '<strong style="color:var(--gold)">Tutkallı veya Spiralli Bloknot:</strong> A6, A5, A4 tutkallı bloknot; soldan veya üstten spiralli, renkli kapaklı. Tek renk veya renkli baskılı. Min. 100 cilt üretim.<br><br><strong style="color:var(--gold)">Küp Bloknot:</strong> 8×8 cm, 500 yaprak. İç sayfalar 80 gr 1. hamur tek renk. Kutu 300 gr parlak selefonlu renkli baskılı. Min. 250 adet.<br><br>Dikey bloknot çeşitleri de mevcuttur. Tasarım desteğimiz ile üretim yapılmaktadır.<br><br>Bloknot en çok tercih edilen matbaa işlerinin başında gelir. Kendi logonuz ve iletişim bilgilerinizin olduğu bloknotu müşterilerinize dağıtmak uzun süreli reklamınızı sağlayacaktır. İş görüşmelerinizde, fuarlarda bloknot dağıtmak firmanızın insanların aklında kalmasını sağlayacak. 500 yapraklı küp bloknot en çok tercih edilen çeşittir; 8×8 cm olması ve 500 sayfası nedeniyle şirketinizin reklamını çok uzun süre yansıtacaktır.',
    'brosur-el-ilani'   => 'A5, A7 ve A4 ebat düz ve/veya katlamalı broşür. Tasarım desteğimiz vardır.<br><br>A7 Ebat (10×20 cm) · A5 Ebat (14×20 cm) · A4 Ebat (20×30 cm)<br>105-115-135-150-200-250-300-350 gr Parlak veya Mat Kuşe Kağıt olarak renkli basılabilir. İsteğe bağlı selefon atılabilir. Konik ve katlamalı seçenekler mevcuttur.<br><br>Müşterilerinizin en çok talep ettiği materyal A5, A7 ve A4 ebat broşürlerdir. Küçük ve orta ölçekli şirketler müşteri sayısını ve satış hacmini artırmak için düzenli olarak broşür bastırırlar. Broşür, maliyet olarak en düşük ve herkese ulaşabilen önemli bir reklam aracıdır.<br><br>Döner, Çiğköfte, Cafe, Pideciler, Ev Yemekleri, Su bayileri, Lokantalar, Marketler, Dershaneler, Emlakçılar ve daha birçok sektör broşür dağıtarak müşteri portföylerini artırır.<br><br>Kampanya, açılış, menü gibi birçok sebeple broşür bastırmak isterseniz grafik tasarımdan baskıya, adresinize kargoya kadar hizmetinizdeyiz. Logo, adres, telefon ve broşür içeriğini mail veya WhatsApp üzerinden gönderdiğinizde size en uygun tasarım yapılacak ve sunulacaktır.',
    'cepli-dosya'       => '350 gr Mat veya Parlak Kuşe Kağıt<br>Tek yön veya Çift Taraf baskılı<br>Kendinden özel cepli Mat veya Parlak selefonlu<br>Ekstra Kabartma Laklı<br>500-1.000 adet üretim yapılmaktadır.<br>Tasarım desteğimiz vardır.<br><br>Tekliflerinizi, A4 ebat broşürlerinizi ve basılı materyallerinizi müşterinize daha kaliteli sunmak için cepli dosya (kapaklı dosya) yaptırmanız şarttır. Cepli dosyanızın tasarımı ve baskısı şirketinizi temsil edeceği için çok önemlidir.<br><br>Cepli dosyanızı; fuar katılımlarınız, toplantılarınız, müşteri ziyaretleriniz ve firma teklifleriniz için kullanabilirsiniz. Logonuz, adresiniz, web siteniz ve mail adresiniz gibi bilgilerinizi bize ulaştırdıktan sonra grafik tasarım kısmını bize bırakın.',
    'etiket-sticker'    => 'Her ebat sticker basımı; sıvıdan etkilenmeyen, A5 veya A4 ebat baskı.<br><br>Kabartmalı Folyo (Sıvıdan Etkilenmez) · Kağıt Etiket · Altın Yaldızlı · Gümüş Yaldızlı · Forforlu · Özel Kesimli · Şişe/Ürün Etiketi · Şeffaf · Beyaz Boya Baskılı Şeffaf · Garanti Etiketi<br><br>Etiket ve sticker hemen hemen her firmanın kullandığı matbaa ürünüdür. Etiket malzemenizi kullanım alanınıza göre iyi seçmeniz gerekir. Ürün etiketi, kargo etiketi, şişe etiketi, garanti etiketi, sıvıdan etkilenmeyen etiket, özel kesimli etiket gibi onlarca çeşit etiket mevcuttur.<br><br>Bize etiketin hangi alanda gerekli olacağını iletirseniz size en uygun etiket çeşidini sunarız; çünkü her etiket her ürüne yapışmayabilir veya dayanıklı olmayabilir. Açık alanlara veya sıvı teması olan ürünler için sıvıdan etkilenmeyen etiket yapılmalıdır. Ürünlerinizin daha şık görünmesi için yaldızlı veya hologramlı etiket çeşitlerini tercih edebilirsiniz.',
    'karton-canta'      => '210-230 gr Amerikan Bristol Kağıt Baskı<br>Hazır ipli (Renk Seçilebilir)<br>Mat veya Parlak Selefonlu<br>Ölçüler: 25×37×8 cm · 38×23×9 cm · 17×24×7 cm<br>4 renk baskılı veya laklı, yaldızlı baskı yapılabilir.<br><br>Küçük büyük farketmeksizin özellikle giyim mağazaları ve dershanelerin vazgeçilmez matbaa ürünü karton çantadır. Firmanızın reklamını en iyi yapacak matbaa işleri arasında ilk sıralarda gelir. Müşteri ziyaretlerinizde, fuar katılımlarınızda ve firma tanıtımlarınızda içinde broşürleriniz, bloknotlarınız, logo baskılı kalemleriniz ve kataloglarınız bulunan karton çanta vermek çok etkili olacaktır.<br><br>Karton çanta; kağıt olduğu için pek çöpe atılmayan ve taşıma işinde hafif olduğu için uzun süre reklamınız görünür. Karton çanta tasarımı ve kağıt kalitesi firmanızı tam olarak temsil etmelidir. Çevreci ve geri dönüşümlü bir ürün olduğu için uzun süre kullanılır. Grafik tasarım desteğimizle yanınızdayız.',
    'kartvizit'         => '250 gr Amerikan Bristol Tek yön baskılı, Parlak selefonlu<br>350 gr Kuşe Çift Yön Baskılı Mat veya Parlak Selefonlu<br>350 gr kağıda özel kesimli kabartma laklı<br>280 ve 560 Tuale (Fantazi) kağıda baskı<br>700 gr sıvama kağıda Kabartma Laklı + Oval veya Özel kesimli<br>800 gr Kağıt Laklı + Altın Yaldızlı<br>Şeffaf Kartvizit üretilebilir.<br>Altın veya Gümüş Yaldızlı. Grafik tasarım desteğimizle.<br><br>Her firmanın yıllardır vazgeçilmezi kartvizit; ilk etapta sizi temsil edeceğinden dolayı tasarım ve kağıt kalitesini iyi seçmeniz gereklidir. Kartvizit kağıdı, selefon uygulaması, kabartma lak veya lak uygulaması, yaldız baskısı, özel veya oval kesim gibi birçok seçenek vardır. Eleman değişmesi, adres ve telefon değişmesi gibi birçok nedenden dolayı şirketlerin kartvizit ihtiyacı sürekli olmaktadır.<br><br>Kartvizit tasarımlarınızın hazır olması, logonuzun beklenmedik zamanlarda lazım olması gibi birçok sebepten uzun süreli ve tecrübeli bir matbaa ile çalışmak firmalar için büyük avantajdır. Tasarım sürecinde; logo, adres, telefonlar, mail, Instagram, X adresi, web sitesi gibi bilgilerinizi bize ulaştırırsanız size en şık kartvizit tasarımı yapılacaktır. Min. 1000 adet basılacak ve istediğiniz özelliklere göre teslim gün sayısı değişecektir.',
    'magnet'            => 'Buzdolabı reklamlarınız için Magnet (Mıknatıslı Etiket)<br>Min. 1000 adet üretilir.<br>Su, Dönerci, Pideci, Lokanta vs.. gibi birçok sektör için ucuz ve sürekli kullanılan reklam aracıdır.<br>60 Micron olarak basılır ve istenilen şekilde kesim yapılabilir.',
    'otokopili-evraklar'=> 'A4-A5 ebat yapılır. 1+1 suret, 1+2 suret, 1+3 suret yapılabilir.<br>Tek renk veya renkli baskılıdır.<br>Numaratör eklenebilir.<br>Perferaj atılır.<br>İstenilen koçan yapılabilir.<br><br>Teknik servis formu, Sipariş Fişi, Tahsilat Makbuzu, Asansör Bakım Formu, Sözleşme, Araç Bakım Formu, Depo Çıkış Fişi, Takip Formu ve benzeri evraklar için idealdir.',
    'poster-afis'       => 'Her ebat Afiş üretimi, Branda, Folyo veya Kağıt...<br>105 gr, 170 gr, 300 gr<br>Kuşe kağıt ve branda afiş<br>33×48 cm / 50×70 cm<br>10-25-50-100-500-1000 adet baskı<br>Üretim: 1-3 gün<br>Anlaşmalı kargo ile gönderim',
    'zarf-cesitleri'    => 'Düz veya Pencereli Diplomat Zarf (10,5×24 cm) (Fatura Zarfı) · 24×32 cm Torba Zarf · 17×25 cm A5 Orta Zarf. Tek renk veya renkli baskı. Kendinden yapışkanlı kapak. Tasarım desteğimizle.<br><br>Şirketlerin fatura ve antetli teklif kağıtlarını daha şık bir şekilde sunmak için logo baskılı zarf yaptırmanız gereklidir. Firmalarda düzenli kullanılan zarf çeşitleri kısa sürede bittiği için aynı matbaa ile çalışmanız sizin için avantajlıdır.<br><br>En çok tercih edilen ebatlar 10,5×24 cm pencereli ve penceresiz Diplomat zarf ve 24×32 cm torba zarftır. Zarflar genellikle 115 gr 1. hamur kağıda tek renk baskılı veya siparişe göre renkli baskılı olarak üretilebilir.<br><br>Zarf numunelerini ofisimizde görebilir, tasarım yaptırabilirsiniz. İsteğinize göre farklı ölçülerde ve daha kaliteli kağıtlarla zarf yaptırabilirsiniz.',
  ];
  $gi_contain_all = ['bloknot-cesitleri','brosur-el-ilani','karton-canta'];
  $gi_contain_idx = ['kartvizit' => [0,1,3]];
?>
<div class="page-hero">
  <h1><?= $label ?></h1>
</div>
<div class="container section">
  <div class="content-wrap">
    <div class="content-main">
      <div class="info-box"><?= $matbaa_infos[$subpage] ?? $item_specs[0] ?></div>
      <p class="product-desc">
        <?= $matbaa_descs[$subpage] ?? ('Kadıköy Matbaa olarak '.$label.' ürünlerinizi en yüksek kalitede, uygun fiyatlarla ve hızlı teslimat garantisiyle basıyoruz. Tasarım aşamasından baskı ve kargoya kadar tüm süreci profesyonel ekibimiz yönetmektedir.') ?>
      </p>
      <div class="foto-galeri">
        <h3>Foto Galeri</h3>
        <div class="galeri-grid">
          <?php foreach($gi as $idx => $img):
            $use_contain = in_array($subpage,$gi_contain_all) || (isset($gi_contain_idx[$subpage]) && in_array($idx,$gi_contain_idx[$subpage]));
            $istyle = $use_contain ? ' style="object-fit:contain;background:#f5f0e8"' : '';
          ?><a href="#" onclick="openLightbox('<?= $img ?>');return false;"><img src="<?= $img ?>" alt="<?= $label ?>"<?= $istyle ?>></a><?php endforeach; ?>
        </div>
      </div>
    </div>
    <aside class="content-sidebar">
      <div class="teklif-box">
        <h3>Hemen Teklif Alın !</h3>
        <?php if($form_success): ?><div class="form-success">✔ Talebiniz alındı, en kısa sürede dönüş yapılacak!</div><?php endif; ?>
        <form method="POST" action="<?= url('matbaa',$subpage) ?>" onsubmit="return validateTeklif(this, event)">
          <input type="hidden" name="urun" value="<?= $label ?>">
          <label>Ad Soyad</label>
          <input type="text" name="ad_soyad" placeholder="Adınız Soyadınız" required>
          <label>Telefon</label>
          <input type="tel" name="telefon" id="teklif-telefon" placeholder="05xx xxx xx xx" required oninput="this.setCustomValidity('')">
          <button type="submit" class="btn-gonder">Gönder</button>
        </form>
      </div>
      <div class="sidebar-hizmetler">
        <h3>Matbaa Hizmetlerimiz</h3>
        <div class="sidebar-links">
          <?php foreach($matbaa_items as $sl=>$lbl): ?>
          <a href="<?= url('matbaa',$sl) ?>" class="<?= $sl===$subpage?'active-link':'' ?>"><?= $lbl ?><span class="arr">»</span></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="sidebar-social">
        <a class="ig-btn" href="https://www.instagram.com/kadikoy_matbaa/" target="_blank">
          <span class="ig-icon"><img src="<?= imgpath('images/instagram_icon.png') ?>" alt="Instagram" style="width:100%;height:100%;object-fit:cover;display:block"></span>Kadıköy Matbaa Instagram
        </a>
      </div>
    </aside>
  </div>
</div>

<?php elseif($page==='branda-baski' && !$subpage): ?>
<div class="page-hero">
  <h1>Branda Baskı</h1>
</div>
<div class="container section" style="padding-top:10px">
  <p class="product-desc branda-main-desc" style="margin-bottom:12px">Kadıköy Matbaa olarak Branda Baskı, Folyo Baskı, Onevision Baskı, Mesh Branda, Germe Tabela ve daha pek çok alanda hizmet vermekteyiz.</p>
  <div class="branda-main-wrap" style="display:flex;gap:32px;align-items:flex-start">
    <div style="flex:1;min-width:0">
      <?php
      $branda_card_imgs = [
        'branda-baski'    => imgpath('images/branda/branda/branda baski/branda 1.jpg'),
        'folyo-baski'     => imgpath('images/branda/branda/folyo - sticker/folyo sticker 1.jpg'),
        'onevision-baski' => imgpath('images/branda/branda/one vision/one vision 1.jpg'),
        'mesh-branda'     => imgpath('images/branda/branda/mesh branda/mesh 1.jpg'),
      ];
      $branda_card_descs = [
        'branda-baski'    => 'Her ebatta UV baskı teknolojisiyle yapılan branda baskılar; kiralık, satılık, açılış ve kampanya ihtiyaçlarına yönelik, ortalama 1–2 gün teslimat garantisiyle sunulmaktadır.',
        'folyo-baski'     => 'Kendinden yapışkanlı PVC folyo baskılar; cam, cephe, tabela ve araç kaplamalarında şeffaf, buzlu veya renkli seçeneklerde, uygulama hizmetiyle birlikte temin edilir.',
        'onevision-baski' => 'Cam yüzeylerde kullanılan delikli onevision baskı; dışarıdan içerisi görünmez ve dış etkenlere dayanıklı yapısıyla bina cephe reklamcılığında öne çıkar.',
        'mesh-branda'     => 'Özel delikli yapısıyla rüzgar ve fırtınaya dayanıklı mesh branda; yüksek katlı bina cephelerinde uzun ömürlü ve canlı renkli baskı imkânı sunar.',
      ];
      ?>
      <div class="branda-main-cards" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <?php foreach($branda_items as $slug=>$label):
          $bcimg = $branda_card_imgs[$slug] ?? '';
          $bcdesc = $branda_card_descs[$slug] ?? 'Kaliteli malzeme ve profesyonel ekibimizle hızlı üretim ve teslimat.';
        ?>
        <a class="service-card" href="<?= url('branda-baski',$slug) ?>">
          <div class="service-card-img" style="height:190px"><img src="<?= $bcimg ?>" alt="<?= $label ?>"></div>
          <div class="service-card-body" style="padding:13px 16px 12px">
            <h3 style="font-size:28px;margin-bottom:6px"><?= $label ?></h3>
            <p class="scard-desc" style="font-size:17px"><?= $bcdesc ?></p>
            <span class="scard-link">Detaylar »</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="branda-main-img" style="flex:0 0 42%;min-width:0">
      <img src="<?= imgpath('images/branda/branda/branda ana ekran.jpg') ?>" alt="Branda Baskı UV" style="width:100%;height:auto;display:block;border:2px solid rgba(200,137,42,.35);border-radius:4px">
    </div>
  </div>
</div>

<?php elseif($page==='branda-baski' && $subpage):
  $blabel_b = $branda_items[$subpage] ?? ucfirst(str_replace('-',' ',$subpage));
  $branda_content = [
    'branda-baski' => [
      'info' => 'Dış mekan kullanıma dayanıklı baskılı branda hizmet... (Dikiş ve Kapsül ile...)',
      'desc' => '5 METRE TEK PARÇA branda baskımız vardır…<br><br>Anlaşmalı kargo ile tüm Türkiye\'ye gönderim sağlıyoruz…<br><br>Her ebat (vinil) Branda baskı ve tasarım hizmeti veriyoruz. (Büyük ebat brandalarda baskının güzel çıkması için deneyimli grafiker tarafından yapılması gereklidir.)<br><br>Baskılı brandalarımız dış mekanda yağmur, suya vs.. şartlara karşı dayanıklıdır. Uzun soluklu branda baskınız için UV baskı teknolojisi ile brandadaki görseliniz çok daha uzun süre solmayacaktır.<br><br>Baskılarımız 4 renk CMYK olarak kaliteli olarak basılmaktadır. İsteğinize göre İç Mekan makinemizde fotoğraf kalitesinde baskı alabilirsiniz.<br><br>50×70 cm / 70×100 cm / 100×100 cm veya istediğiniz tüm ölçülerde baskı yapılabilmektedir.<br><br>Teslim süremiz ort. 1-2 gündür. Fiyatlandırma ölçünüze ve istediğiniz adete göre hesaplanır.',
    ],
    'folyo-baski' => [
      'info' => 'Sıvıya dayanıklı PVC yapışkan reklam ürünüdür. Vitrin camları için idealdir.',
      'desc' => '<strong style="color:var(--gold);font-size:26px">Kullanım Alanları</strong><br><br>Folyo baskı, geniş bir uygulama yelpazesine sahiptir:<br><br><strong style="color:var(--gold)">Reklam ve Tanıtım:</strong> Mağaza vitrinleri ("İndirim", "Kampanya" yazıları), tabela yüzeyleri ve yönlendirme levhaları.<br><strong style="color:var(--gold)">Araç Giydirme:</strong> Şirket araçlarının logolarla veya tam kaplama ile reklam mecrasına dönüştürülmesi.<br><strong style="color:var(--gold)">İç Mekan Dekorasyonu:</strong> Ofis cam bölmeleri, duvar kaplamaları ve mobilya yenileme işlemleri.<br><strong style="color:var(--gold)">Etiket ve Sticker:</strong> Küçük ebatlı ürün etiketleri veya dekoratif stickerların üretimi.<br><br><strong style="color:var(--gold);font-size:26px">Uygulama ve Dayanıklılık</strong><br><br>Baskının ömrünü uzatmak için dış mekan uygulamalarında laminasyon (koruyucu mat veya parlak kaplama) tercih edilir; bu işlem güneş ışığı ve sürtünmeye karşı direnci artırır. Uygulama yapılacak yüzeyin tozdan ve kirden tamamen arındırılmış, pürüzsüz olması yapışkanın kalıcılığı için kritiktir.<br><br>Sıvıya dayanıklı özel kesim sticker baskımız vardır.',
    ],
    'onevision-baski' => [
      'info' => 'Cam yüzeylere uygulanır. Özelliği: dışarı görünümü sağlanır, dışardan sadece sizin görseliniz görünür. Gündüz içeriyi göstermez...',
      'desc' => '<strong style="color:var(--gold)">Dijital Baskı ve Reklamcılık (One Way Vision)</strong><br><br>Sektörde genellikle "delikli folyo" olarak bilinen bu malzeme, binaların veya araçların cam yüzeylerine reklam amacıyla uygulanır. Temel özelliği şudur:<br><br><strong style="color:var(--gold)">Dışarıdan:</strong> Sadece basılan reklam görseli görünür, içerisi görünmez.<br><strong style="color:var(--gold)">İçeriden:</strong> Sanki camda hiçbir şey yokmuş gibi dışarısı rahatça izlenebilir ve gün ışığı içeri girmeye devam eder.<br><br>Tasarım desteğimiz vardır…',
    ],
    'mesh-branda' => [
      'info' => 'Rüzgara daha dayanıklı bir üründür. Deliklerinden hava akışı olduğu için çabuk yırtılmaz.',
      'desc' => 'Büyük ebat ve rüzgar alan mekanlara asmak için MESH (DELİKLİ) BRANDA tercih edilmelidir.<br><br>Kampanya, Açılış, Tadilat, Satılık, Kiralık vs.. işleriniz için idealdir.<br><br>UV baskı ve içten kolon dikiş ile uzun süre solma yapmaz ve kolay yırtılmaz.<br><br>5 metre tek parça MESH branda baskımız vardır.<br><br>Tasarım desteğimiz vardır.<br><br>Anlaşmalı kargo ile gönderim sağlanır.',
    ],
  ];
  $bcd = $branda_content[$subpage] ?? ['info'=>'Kaliteli hizmet garantisiyle üretim yapılmaktadır.','desc'=>'Kadıköy Matbaa olarak '.$blabel_b.' konusunda hizmet vermekteyiz.'];
  $branda_gallery_images = [
    'branda-baski' => [
      'images/branda/branda/branda baski/branda 1.jpg',
      'images/branda/branda/branda baski/branda 2.jpeg',
      'images/branda/branda/branda baski/branda 3.jpeg',
      'images/branda/branda/branda baski/branda 4.jpeg',
      'images/branda/branda/branda baski/branda 5.jpeg',
    ],
    'folyo-baski' => [
      'images/branda/branda/folyo - sticker/folyo sticker 1.jpg',
      'images/branda/branda/folyo - sticker/folyo sticker 2.jpg',
      'images/branda/branda/folyo - sticker/folyo sticker 3.jpg',
      'images/branda/branda/folyo - sticker/folyo sticker 4.jpg',
      'images/branda/branda/folyo - sticker/folyo sticker 5.jpg',
    ],
    'onevision-baski' => [
      'images/branda/branda/one vision/one vision 1.jpg',
      'images/branda/branda/one vision/one vision 2.jpg',
      'images/branda/branda/one vision/one vision 3.jpg',
      'images/branda/branda/one vision/one vision 4.jpg',
      'images/branda/branda/one vision/one vision 5.jpg',
    ],
    'mesh-branda' => [
      'images/branda/branda/mesh branda/mesh 1.jpg',
      'images/branda/branda/mesh branda/mesh 2.jpg',
      'images/branda/branda/mesh branda/mesh 00.jpg',
      'images/branda/branda/mesh branda/mesh 33.jpg',
    ],
  ];
  $branda_aciklama = [];
  $bgi2 = isset($branda_gallery_images[$subpage]) ? array_map('imgpath', $branda_gallery_images[$subpage]) : [];
?>
<div class="page-hero">
  <h1><?= $blabel_b ?></h1>
</div>
<div class="container section">
  <div class="content-wrap">
    <div class="content-main">
      <?php if(isset($branda_aciklama[$subpage])): ?>
      <div style="margin-bottom:24px;text-align:center">
        <img src="<?= $branda_aciklama[$subpage] ?>" alt="<?= $blabel_b ?>" style="max-height:360px;width:auto;max-width:100%;display:inline-block;border:1px solid var(--border)">
      </div>
      <?php endif; ?>
      <?php
      $subpage_img_map = [
        'branda-baski'    => imgpath('images/branda/branda/branda ana ekran.jpg'),
        'folyo-baski'     => imgpath('images/branda/branda/folyo.jpeg'),
        'onevision-baski' => imgpath('images/branda/branda/one vision.jpeg'),
        'mesh-branda'     => imgpath('images/branda/branda/mesh.jpeg'),
      ];
      ?>
      <div class="branda-sp-layout">
        <?php if(isset($subpage_img_map[$subpage])): ?>
        <div class="branda-sp-img">
          <img src="<?= $subpage_img_map[$subpage] ?>" alt="<?= $blabel_b ?>" style="width:100%;height:auto;display:block;border:2px solid rgba(200,137,42,.35)">
        </div>
        <?php endif; ?>
        <div class="branda-sp-text">
          <div class="info-box"><?= $bcd['info'] ?></div>
          <p class="product-desc" style="margin-top:12px"><?= $bcd['desc'] ?></p>
        </div>
      </div>
      <div class="foto-galeri">
        <h3>Foto Galeri</h3>
        <div class="galeri-grid">
          <?php foreach($bgi2 as $img): ?><a href="#" onclick="openLightbox('<?= $img ?>');return false;"><img src="<?= $img ?>" alt="<?= $blabel_b ?>"></a><?php endforeach; ?>
        </div>
      </div>
    </div>
    <aside class="content-sidebar">
      <div class="teklif-box">
        <h3>Hemen Teklif Alın !</h3>
        <?php if($form_success): ?><div class="form-success">✔ Talebiniz alındı, en kısa sürede dönüş yapılacak!</div><?php endif; ?>
        <form method="POST" action="<?= url('branda-baski',$subpage) ?>" onsubmit="return validateTeklif(this, event)">
          <input type="hidden" name="urun" value="<?= $blabel_b ?>">
          <label>Ad Soyad</label>
          <input type="text" name="ad_soyad" placeholder="Adınız Soyadınız" required>
          <label>Telefon</label>
          <input type="tel" name="telefon" id="teklif-telefon" placeholder="05xx xxx xx xx" required oninput="this.setCustomValidity('')">
          <button type="submit" class="btn-gonder">Gönder</button>
        </form>
      </div>
      <div class="sidebar-hizmetler">
        <h3>Branda Hizmetlerimiz</h3>
        <div class="sidebar-links">
          <?php foreach($branda_items as $sl=>$lbl): ?>
          <a href="<?= url('branda-baski',$sl) ?>" class="<?= $sl===$subpage?'active-link':'' ?>"><?= $lbl ?><span class="arr">»</span></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="sidebar-social">
        <a class="ig-btn" href="https://www.instagram.com/kadikoy_matbaa/" target="_blank">
          <span class="ig-icon"><img src="<?= imgpath('images/instagram_icon.png') ?>" alt="Instagram" style="width:100%;height:100%;object-fit:cover;display:block"></span>Kadıköy Matbaa Instagram
        </a>
      </div>
    </aside>
  </div>
</div>

<?php elseif($page==='bayrak-baski' && !$subpage): ?>
<div class="page-hero">
  <h1>Bayrak Baskı</h1>
</div>
<div class="container section">
  <p class="product-desc" style="margin-bottom:12px">Her türlü bayrak baskısında kaliteli ve dayanıklı kumaş seçenekleriyle hizmetinizdeyiz.</p>
  <div class="service-cards">
    <?php
    $bi_imgs=[
      'ataturk-bayraklari' => imgpath('images/bayrak/bayrak/ataturk bayragi/ataturk bayragi 1.jpg'),
      'gonder-bayragi'     => imgpath('images/bayrak/bayrak/gonder bayrak/gonder bayrak 1.jpg'),
      'kirlangic-bayrak'   => imgpath('images/bayrak/bayrak/kirlangic bayrak/kirlangic 1.jpg'),
      'masa-bayragi'       => imgpath('images/bayrak/bayrak/masa bayragi/2li bayrak 1.jpg'),
      'yelken-olta-bayrak' => imgpath('images/bayrak/bayrak/yelken bayrak/y b 1.jpg'),
    ];
    $bayrak_card_descs = [
      'ataturk-bayraklari' => 'Cumhuriyet Bayramı, Atatürk\'ü Anma Günü ve ulusal bayramlarda yaygın kullanılan Atatürk bayrakları; 125 gr raşel kumaşa yüksek çözünürlüklü dijital baskıyla üretilir.',
      'gonder-bayragi'     => 'Bina cepheleri, meydan ve etkinlik alanları için 75×105 cm standart ölçüde üretilen gönder bayrakları; renk haslığı yüksek mürekkep ve overlok dikişli kenar ile solmaya karşı dayanıklıdır.',
      'kirlangic-bayrak'   => 'Çatal kesimli uçlarıyla öne çıkan kırlangıç bayrak; kurumlar, fuarlar, açılışlar ve dış mekan tanıtımlarında farklı ebat seçenekleriyle şık bir görünüm sağlar.',
      'masa-bayragi'       => 'Toplantı masaları ve tören alanlarında kullanılan masa bayrakları; tekli, ikili veya üçlü T tipi metal aparat üzerine raşel kumaşa yüksek çözünürlüklü baskıyla üretilir.',
      'yelken-olta-bayrak' => 'Rüzgarla birlikte dalgalanan yelken bayraklar; mağaza önleri, fuar alanları ve açık organizasyonlarda dikkat çeken, dört mevsim dış mekana uygun reklam ürünleridir.',
    ];
    foreach($bayrak_items as $slug=>$label):
      $bkdesc = $bayrak_card_descs[$slug] ?? 'Yüksek kaliteli kumaş ve renkli baskı ile uzun ömürlü üretim.';
    ?>
    <a class="service-card" href="<?= url('bayrak-baski',$slug) ?>">
      <div class="service-card-img"><img src="<?= $bi_imgs[$slug] ?? 'https://placehold.co/600x400?text=Resim' ?>" alt="<?= $label ?>"></div>
      <div class="service-card-body">
        <h3><?= $label ?></h3>
        <p class="scard-desc"><?= $bkdesc ?></p>
        <span class="scard-link">Detaylar »</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<?php elseif($page==='bayrak-baski' && $subpage):
  $blabel = $bayrak_items[$subpage] ?? ucfirst(str_replace('-',' ',$subpage));
  $bayrak_data = [
    'ataturk-bayraklari' => [
      'info' => 'Ulu Önderimiz ve Ebedi BAŞKOMUTANIMIZ Mustafa Kemal ATATÜRK\'ün portresinin veya siluetinin yer aldığı bayraklar..',
      'desc' => '"Atatürk bayrakları" denince genellikle Mustafa Kemal Atatürk\'ün portresinin veya siluetinin yer aldığı bayraklar kastedilir. Bu bayraklar, Türkiye Cumhuriyeti\'nin kurucusu Mustafa Kemal Atatürk\'e olan saygıyı ve sevgiyi ifade etmek amacıyla kullanılır.<br>İstenilen ebatta raşel kumaşa baskı yapılır…<br><br>Atatürk bayrakları özellikle şu zamanlarda sıkça kullanılır:<br>29 Ekim Cumhuriyet Bayramı<br>10 Kasım Atatürk\'ü Anma Günü<br>23 Nisan, 19 Mayıs, 30 Ağustos gibi milli bayramlarda<br>Okullarda, kamu binalarında, evlerde, stadyumlarda, mitinglerde veya törenlerde<br><br>Kimi kurumlar bu bayrakları binalarına asarak veya tören alanlarına yerleştirerek Atatürk\'e saygılarını gösterir.',
    ],
    'gonder-bayragi' => [
      'info' => 'Ülke Gönder Bayrakları genellikle 125 gr raşel kumaşa basılmaktadır.',
      'desc' => 'Ülke Gönder Bayrakları genellikle 125 gr raşel kumaşa basılmaktadır. Baskı aynı netlikte arka yüzden de okunmaktadır, fakat baskı bir yüzünden düz, bir yüzünden ters okunmaktadır.<br>Gönder bayrakları nadiren isteğe göre çift kat, çift yüzde üretilebilmektedir. Baskı her iki yüzde de düz okunmaktadır. Çift kat çift yüz bayraklar ağır oldukları için dalgalanması az olup yıpranması daha hızlı olmaktadır. Bu nedenle çok tercih edilmemektedir.<br>Gönder Bayraklarında tercihe göre Raşel, Saten ve Alpaka kumaşlar kullanılmaktadır.<br>Akma, solma, yıkama ve ütüye karşı garantilidir<br>Genellikle 75×105 cm ebatta üretilir. İsteğe göre özel ölçüde yapılabilir.',
    ],
    'kirlangic-bayrak' => [
      'info' => 'Kırlangıç bayrak, genellikle kurumlar, etkinlikler, açılışlar, fuarlar ve dış mekan tanıtımlarında kullanılan özel bir bayrak modelidir.',
      'desc' => 'Kırlangıç bayrak, genellikle kurumlar, etkinlikler, açılışlar, fuarlar ve dış mekan tanıtımlarında kullanılan özel bir bayrak modelidir. Adını, alt ucunun "V" şeklinde kesilmiş olmasından alır; bu şekil kırlangıç kuyruklarına benzediği için "kırlangıç bayrak" denir.<br>90, 120 veya 140 gr Raşel Kumaş Baskı…<br>Ölçüler:<br>50×75 cm &nbsp;&nbsp;&nbsp; 75×100 cm<br>50×100 cm &nbsp;&nbsp; 75×150 cm<br>50×150 cm &nbsp;&nbsp; 75×200 cm',
    ],
    'masa-bayragi' => [
      'info' => 'Masa bayrağı; kurum, kuruluş, dernek, okul veya devlet kurumlarında resmî ya da temsilî amaçlarla kullanılır.',
      'desc' => 'Masa bayrağı; kurum, kuruluş, dernek, okul veya devlet kurumlarında resmî ya da temsilî amaçlarla masalar üzerinde kullanılan küçük bayraklara verilen isimdir. Hem estetik hem de kurumsal bir kimlik göstergesi olarak kullanılır.<br>Tekli, İkili ve Üçlü olarak üretilebilir.<br>T Masa Bayrağı üretimimiz vardır.<br>Raşel kumaş baskılır.',
    ],
    'yelken-olta-bayrak' => [
      'info' => 'Yelken bayrak; reklam, tanıtım ve yönlendirme amacıyla kullanılan, rüzgârla dalgalanarak dikkat çeken bir promosyon ürünüdür.',
      'desc' => 'Yelken bayrak, genellikle reklam, tanıtım ve yönlendirme amacıyla kullanılan, rüzgârla birlikte dalgalanarak dikkat çeken bir promosyon ürünüdür. Adını, şekil olarak bir yelkeni andırmasından alır. Hafif, taşınabilir ve dış mekân koşullarına dayanıklı olduğu için etkinliklerde, fuarlarda, mağaza önlerinde, dükkan ve kurum girişlerinde sıkça tercih edilir.<br>Ölçü: 75×300 cm<br>Bidon 19 L<br>Direk: 4 m Demir<br>Kumaş: 90 gr Raşel',
    ],
  ];
  $bd = $bayrak_data[$subpage] ?? [
    'info' => 'Kadıköy Matbaa olarak yüksek kaliteli kumaş ve baskıyla üretim yapıyoruz.',
    'desc'  => 'Kadıköy Matbaa olarak ' . $blabel . ' ürünlerinizi en yüksek kalitede, uygun fiyatlarla ve hızlı teslimat garantisiyle basıyoruz.',
  ];
  $bayrak_gallery_images = [
    'ataturk-bayraklari' => [
      'images/bayrak/bayrak/ataturk bayragi/ataturk bayragi 1.jpg',
      'images/bayrak/bayrak/ataturk bayragi/ataturk bayragi 2.jpg',
      'images/bayrak/bayrak/ataturk bayragi/ataturk bayragi 3.jpg',
      'images/bayrak/bayrak/ataturk bayragi/ataturk bayragi 4.jpg',
      'images/bayrak/bayrak/ataturk bayragi/ataturk bayragi 5.jpg',
    ],
    'gonder-bayragi' => [
      'images/bayrak/bayrak/gonder bayrak/gonder bayrak 1.jpg',
      'images/bayrak/bayrak/gonder bayrak/gonder bayrak 2.jpg',
      'images/bayrak/bayrak/gonder bayrak/gonder bayrak 3.jpg',
      'images/bayrak/bayrak/gonder bayrak/gonder bayrak 4.jpg',
    ],
    'kirlangic-bayrak' => [
      'images/bayrak/bayrak/kirlangic bayrak/kirlangic 1.jpg',
      'images/bayrak/bayrak/kirlangic bayrak/kirlangic 2.jpg',
    ],
    'masa-bayragi' => [
      'images/bayrak/bayrak/masa bayragi/2li bayrak 1.jpg',
      'images/bayrak/bayrak/masa bayragi/2li bayrak.jpg',
      'images/bayrak/bayrak/masa bayragi/3lu bayrak.jpg',
      'images/bayrak/bayrak/masa bayragi/tekli bayrak.jpg',
    ],
    'yelken-olta-bayrak' => [
      'images/bayrak/bayrak/yelken bayrak/y b 1.jpg',
      'images/bayrak/bayrak/yelken bayrak/y b 2.jpg',
      'images/bayrak/bayrak/yelken bayrak/y b 3.jpg',
      'images/bayrak/bayrak/yelken bayrak/y b 4.jpg',
      'images/bayrak/bayrak/yelken bayrak/y b 5.jpg',
    ],
  ];
  $bgi = isset($bayrak_gallery_images[$subpage]) ? array_map('imgpath', $bayrak_gallery_images[$subpage]) : array_fill(0, 5, 'https://placehold.co/600x400?text=Resim');
?>
<div class="page-hero">
  <h1><?= $blabel ?></h1>
</div>
<div class="container section">
  <div class="content-wrap">
    <div class="content-main">
      <div class="info-box"><?= $bd['info'] ?? $bd['specs'][0] ?></div>
      <p class="product-desc">
        <?= $bd['desc'] ?>
      </p>
      <div class="foto-galeri">
        <h3>Foto Galeri</h3>
        <div class="galeri-grid">
          <?php foreach($bgi as $img): ?><a href="#" onclick="openLightbox('<?= $img ?>');return false;"><img src="<?= $img ?>" alt="<?= $blabel ?>"></a><?php endforeach; ?>
        </div>
      </div>
    </div>
    <aside class="content-sidebar">
      <div class="teklif-box">
        <h3>Hemen Teklif Alın !</h3>
        <?php if($form_success): ?><div class="form-success">✔ Talebiniz alındı, en kısa sürede dönüş yapılacak!</div><?php endif; ?>
        <form method="POST" action="<?= url('bayrak-baski',$subpage) ?>" onsubmit="return validateTeklif(this, event)">
          <input type="hidden" name="urun" value="<?= $blabel ?>">
          <label>Ad Soyad</label>
          <input type="text" name="ad_soyad" placeholder="Adınız Soyadınız" required oninput="this.setCustomValidity('')">
          <label>Telefon</label>
          <input type="tel" name="telefon" id="teklif-telefon" placeholder="05xx xxx xx xx" required oninput="this.setCustomValidity('')">
          <button type="submit" class="btn-gonder">Gönder</button>
        </form>
      </div>
      <div class="sidebar-hizmetler">
        <h3>Bayrak Baskı</h3>
        <div class="sidebar-links">
          <?php foreach($bayrak_items as $sl=>$lbl): ?>
          <a href="<?= url('bayrak-baski',$sl) ?>" class="<?= $sl===$subpage?'active-link':'' ?>"><?= $lbl ?><span class="arr">»</span></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="sidebar-social">
        <a class="ig-btn" href="https://www.instagram.com/kadikoy_matbaa/" target="_blank">
          <span class="ig-icon"><img src="<?= imgpath('images/instagram_icon.png') ?>" alt="Instagram" style="width:100%;height:100%;object-fit:cover;display:block"></span>Kadıköy Matbaa İnstagram
        </a>
      </div>
    </aside>
  </div>
</div>

<?php elseif($page==='fuar-tanitim' && !$subpage): ?>
<div class="page-hero">
  <h1>Fuar / Tanıtım / Baskı</h1>
</div>
<div class="container section">
  <p class="product-desc" style="margin-bottom:12px">Kadıköy Matbaa olarak Back Drop, Forex Baskı, Reklam Dubası, Roll Up Banner ve Tanıtım Standı gibi fuar ve tanıtım ürünlerinde kaliteli üretim ve hızlı teslimat sunuyoruz.</p>
  <div class="service-cards">
    <?php
    $fuar_card_imgs = [
      'back-drop'       => [imgpath('images/fuar - tanitim standi/fuar - tanitim standi/back drop/back drop 1.jpeg'),'cover'],
      'forex-dekota'    => [imgpath('images/fuar - tanitim standi/fuar - tanitim standi/forex (dekota) baski/dekota 1.jpg'),'cover'],
      'reklam-dubasi'   => [imgpath('images/fuar - tanitim standi/fuar - tanitim standi/reklam dubasi/duba 1.jpg'),'cover'],
      'roll-up-banner'  => [imgpath('images/fuar - tanitim standi/fuar - tanitim standi/roll up banner/roll up banner 1.jpg'),'cover'],
      'tanitim-standi'  => [imgpath('images/fuar - tanitim standi/fuar - tanitim standi/tanitim standi/stand 1.jpg'),'cover'],
    ];
    $fuar_card_descs = [
      'back-drop'       => 'Fuar, açılış, kongre ve organizasyonlar için portatif back drop standları; LightBox kumaş baskı seçeneği ve taşıma çantasıyla tek kişi tarafından kısa sürede kurulabilir.',
      'forex-dekota'    => 'PVC esaslı sert ve hafif Forex (Dekota) levhalar üzerine UV dijital baskı; iç ve dış mekan tabelaları, stand giydirmeleri ve AVM vitrinleri için idealdir.',
      'reklam-dubasi'   => 'Kapı önleri, kaldırım ve otopark alanları için portatif reklam dubası; su veya kum doldurulabilen ağırlık haznesiyle rüzgar ve dış hava koşullarına dayanıklıdır.',
      'roll-up-banner'  => 'Sarmalı mekanizmasıyla yalnızca 2–3 dakikada kurulan roll up banner; fuar, etkinlik ve mağaza tanıtımlarında 85×200\'den 200×300 cm\'e kadar ebat seçeneğiyle üretilir.',
      'tanitim-standi'  => 'Fuar, market ve etkinlik alanları için portatif tanıtım standları; kurumsal kimliğinizi yansıtan özel tasarım ve baskıyla marka bilinirliğini artıran, kolay kurulumlu sunum alanları.',
    ];
    foreach($fuar_items as $slug=>$label):
      [$fcimg,$fcfit] = $fuar_card_imgs[$slug] ?? ['https://placehold.co/600x400?text=Resim','cover'];
      $fcstyle = '';
      $fcdesc = $fuar_card_descs[$slug] ?? 'Profesyonel fuar ve tanıtım çözümleri, hızlı teslimat garantisiyle.';
    ?>
    <a class="service-card" href="<?= url('fuar-tanitim',$slug) ?>">
      <div class="service-card-img"><img src="<?= $fcimg ?>" alt="<?= $label ?>"<?= $fcstyle ?>></div>
      <div class="service-card-body">
        <h3><?= $label ?></h3>
        <p class="scard-desc"><?= $fcdesc ?></p>
        <span class="scard-link">Detaylar »</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<?php elseif($page==='fuar-tanitim' && $subpage):
  $flabel = $fuar_items[$subpage] ?? ucfirst(str_replace('-',' ',$subpage));
  $fuar_content = [
    'back-drop' => [
      'info' => 'Fuar, etkinlik ve basın toplantıları için profesyonel backdrop stand sistemleri. Çabuk ve kolay kurulum... ŞIK GÖRÜNÜM...',
      'desc' => 'FUAR, AÇILIŞ, KONGRE, DAVET, KONFERANS, ETKİNLİK, SERGİ VE tüm organizasyonları için YENİ NESİL STAND…<br><br><strong style="color:var(--gold)">ÖLÇÜLER</strong><br>100×230 cm / 150×230 cm / 200×230 cm / 250×230 cm / 300×230 cm / 400×230 cm / 500×230 cm / 600×230 cm<br><br>▸ Portatiftir.<br>▸ Tasarım desteğimiz vardır.<br>▸ Taşıma Çantalıdır.<br>▸ LightBox Kumaş baskıdır.<br>▸ Işıklı üretilebilir.<br>▸ Tek kişi tarafından kurulabilir.<br>▸ Çift yüz baskılı üretilebilir.<br>▸ Modern ŞIK görünüm.<br>▸ İstenilen ebatta üretim.<br><br><strong style="color:var(--gold)">İSTANBUL ve ANKARA için</strong><br>▸ Adrese teslimat ve kurulum yapılır.<br>▸ (Teslimat ve Kurulum ücreti eklenir.)',
    ],
    'forex-dekota' => [
      'info' => 'Forex baskı (diğer adıyla Dekota), PVC esaslı sert, hafif ve dayanıklı levhalar üzerine yapılan bir dijital baskı türüdür.',
      'desc' => 'Forex baskı (diğer adıyla Dekota), PVC esaslı sert, hafif ve dayanıklı levhalar üzerine yapılan bir dijital baskı türüdür. Reklam, tanıtım ve dekorasyon dünyasında sıkça tercih edilen bu yöntem, pürüzsüz yüzeyi ve taşınabilir yapısıyla öne çıkar.<br><br><strong style="color:var(--gold);font-size:26px">Forex Baskının Öne Çıkan Özellikleri</strong><br><br><strong style="color:var(--gold)">Malzeme Yapısı:</strong> Sert PVC köpükten üretilir; fotobloğa göre çok daha dayanıklı ve suya karşı dirençlidir.<br><strong style="color:var(--gold)">Kullanım Alanı:</strong> Hem iç mekan (mağaza görselleri, tablolar) hem de dış mekan (tabelalar, yönlendirme levhaları) için uygundur.<br><strong style="color:var(--gold)">Teknik Özellikler:</strong> Genellikle 3 mm ve 5 mm kalınlıklar tercih edilse de, 1 mm\'den 20 mm\'ye kadar seçenekleri mevcuttur.<br><strong style="color:var(--gold)">Uygulama Yöntemi:</strong> Görseller doğrudan UV baskı ile yüzeye işlenebilir veya folyo üzerine basıldıktan sonra forex levhaya sıvanabilir.<br><br><strong style="color:var(--gold);font-size:26px">Popüler Kullanım Alanları</strong><br><br><strong style="color:var(--gold)">AVM ve Mağaza Vitrinleri:</strong> Hafifliği sayesinde asılarak veya yapıştırılarak kolayca sergilenir.<br><strong style="color:var(--gold)">Fuar ve Etkinlikler:</strong> Tanıtım panoları, stand giydirmeleri ve bilgilendirme tabelaları için idealdir.',
    ],
    'reklam-dubasi' => [
      'info' => 'Reklam dubası; işletmelerin kapı önlerinde, kaldırımlarda veya otopark alanlarında tanıtım ve park düzenini sağlamak amacıyla kullanılır.',
      'desc' => 'Reklam dubası, işletmelerin kapı önlerinde, kaldırımlarda veya otopark alanlarında hem tanıtım yapmak hem de park düzenini sağlamak amacıyla kullandığı portatif reklam araçlarıdır. Genellikle su veya kum doldurulabilen ağırlık hazneleri sayesinde rüzgara ve dış hava koşullarına dayanıklıdırlar. Mağaza önleri, lokanta girişleri, etkinlik alanları ve otoparklar için ideal bir reklam çözümüdür. Kadıköy Matbaa olarak yüksek baskı kalitesiyle reklam dubalarınızı üretiyor ve teslim ediyoruz.',
    ],
    'roll-up-banner' => [
      'info' => 'Taşınabilir, şık ve etkili sunum çözümü: Roll-up banner tasarım ve baskısı. Kendinden sarmalı poster ile fuarlarınız için büyük kolaylık...',
      'desc' => 'Genellikle fuar sunumlarınız için kullanılan bir reklam aracıdır.<br><br>85×200 cm / 100×200 cm / 150×200 cm / 200×200 cm / 200×300 cm ebatlarında üretilebilir.<br><br>Kendinden sarmalı; kurması ve taşıması çok kolaydır. Sarmalı bir mekanizmaya sahiptir. Kurulum süresi 2-3 dk sürmektedir.<br><br>Tasarım desteğimiz vardır.<br><br>Anlaşmalı kargo ile Türkiye\'nin her yerine gönderim..',
    ],
    'tanitim-standi' => [
      'info' => 'Tanıtım standı; ürün veya hizmetlerin fuar, market ve etkinlik alanlarında sergilenmesi için kullanılan portatif veya sabit sunum alanlarıdır.',
      'desc' => 'Tanıtım standı; ürün veya hizmetlerin fuar, market, etkinlik gibi alanlarda sergilenmesi ve pazarlanması için kullanılan portatif veya sabit sunum alanlarıdır. Bu standlar, marka bilinirliğini artırmak ve müşterilerle doğrudan etkileşim kurmak amacıyla tasarlanır. Kurumsal kimliğinizi yansıtan özel tasarım ve baskı seçenekleriyle hazırlanan tanıtım standları, her organizasyonda dikkat çekici bir görünüm sunar. Kadıköy Matbaa olarak tasarımdan üretime tüm süreçte yanınızdayız.',
    ],
  ];
  $fcd = $fuar_content[$subpage] ?? ['info'=>'Kaliteli hizmet garantisiyle üretim yapılmaktadır.','desc'=>'Kadıköy Matbaa olarak '.$flabel.' konusunda hizmet vermekteyiz.'];
  $fuar_gallery_images = [
    'back-drop' => [
      'images/fuar - tanitim standi/fuar - tanitim standi/back drop/back drop 1.jpeg',
      'images/fuar - tanitim standi/fuar - tanitim standi/back drop/back drop 2.jpeg',
      'images/fuar - tanitim standi/fuar - tanitim standi/back drop/back drop 3.jpeg',
      'images/fuar - tanitim standi/fuar - tanitim standi/back drop/back dop 4.jpeg',
    ],
    'forex-dekota' => [
      'images/fuar - tanitim standi/fuar - tanitim standi/forex (dekota) baski/dekota 1.jpg',
      'images/fuar - tanitim standi/fuar - tanitim standi/forex (dekota) baski/dekota 2.jpg',
      'images/fuar - tanitim standi/fuar - tanitim standi/forex (dekota) baski/dekota 3.jpg',
      'images/fuar - tanitim standi/fuar - tanitim standi/forex (dekota) baski/dekota 4.jpg',
    ],
    'reklam-dubasi' => [
      'images/fuar - tanitim standi/fuar - tanitim standi/reklam dubasi/duba 1.jpg',
      'images/fuar - tanitim standi/fuar - tanitim standi/reklam dubasi/duba 2.jpg',
    ],
    'roll-up-banner' => [
      'images/fuar - tanitim standi/fuar - tanitim standi/roll up banner/roll up banner 1.jpg',
      'images/fuar - tanitim standi/fuar - tanitim standi/roll up banner/roll up banner 2.jpg',
      'images/fuar - tanitim standi/fuar - tanitim standi/roll up banner/roll up banner 3.jpg',
      'images/fuar - tanitim standi/fuar - tanitim standi/roll up banner/roll up banner 4.jpg',
      'images/fuar - tanitim standi/fuar - tanitim standi/roll up banner/roll up banner 5.jpg',
    ],
    'tanitim-standi' => [
      'images/fuar - tanitim standi/fuar - tanitim standi/tanitim standi/stand 1.jpg',
      'images/fuar - tanitim standi/fuar - tanitim standi/tanitim standi/stand 2.jpg',
    ],
  ];
  $fgi = isset($fuar_gallery_images[$subpage]) ? array_map('imgpath', $fuar_gallery_images[$subpage]) : [];
?>
<div class="page-hero">
  <h1><?= $flabel ?></h1>
</div>
<div class="container section">
  <div class="content-wrap">
    <div class="content-main">
      <div class="info-box"><?= $fcd['info'] ?></div>
      <p class="product-desc"><?= $fcd['desc'] ?></p>
      <?php if($fgi): ?>
      <div class="foto-galeri">
        <h3>Foto Galeri</h3>
        <div class="galeri-grid">
          <?php foreach($fgi as $img): ?><a href="#" onclick="openLightbox('<?= $img ?>');return false;"><img src="<?= $img ?>" alt="<?= $flabel ?>"></a><?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <aside class="content-sidebar">
      <div class="teklif-box">
        <h3>Hemen Teklif Alın !</h3>
        <?php if($form_success): ?><div class="form-success">✔ Talebiniz alındı, en kısa sürede dönüş yapılacak!</div><?php endif; ?>
        <form method="POST" action="<?= url('fuar-tanitim',$subpage) ?>" onsubmit="return validateTeklif(this, event)">
          <input type="hidden" name="urun" value="<?= $flabel ?>">
          <label>Ad Soyad</label>
          <input type="text" name="ad_soyad" placeholder="Adınız Soyadınız" required>
          <label>Telefon</label>
          <input type="tel" name="telefon" id="teklif-telefon" placeholder="05xx xxx xx xx" required oninput="this.setCustomValidity('')">
          <button type="submit" class="btn-gonder">Gönder</button>
        </form>
      </div>
      <div class="sidebar-hizmetler">
        <h3>Fuar / Tanıtım Hizmetleri</h3>
        <div class="sidebar-links">
          <?php foreach($fuar_items as $sl=>$lbl): ?>
          <a href="<?= url('fuar-tanitim',$sl) ?>" class="<?= $sl===$subpage?'active-link':'' ?>"><?= $lbl ?><span class="arr">»</span></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="sidebar-social">
        <a class="ig-btn" href="https://www.instagram.com/kadikoy_matbaa/" target="_blank">
          <span class="ig-icon"><img src="<?= imgpath('images/instagram_icon.png') ?>" alt="Instagram" style="width:100%;height:100%;object-fit:cover;display:block"></span>Kadıköy Matbaa Instagram
        </a>
      </div>
    </aside>
  </div>
</div>

<?php elseif($page==='blog' && !$subpage): ?>
<div class="page-hero">
  <h1>Blog</h1>
</div>
<div class="container section">
  <div class="blog-grid">
    <?php $bi=[imgpath('images/blog/blog-1.jpg'),imgpath('images/blog/blog-2.jpg')];
    foreach($blog_posts as $k=>$bp): ?>
    <a class="blog-card" href="<?= url('blog',$bp['slug']) ?>">
      <div class="blog-card-img"><img src="<?= $bi[$k] ?? $bi[0] ?>" alt="<?= $bp['title'] ?>"></div>
      <div class="blog-card-body">
        <h3><?= $bp['title'] ?></h3>
        <p><?= $bp['excerpt'] ?></p>
        <div class="blog-meta"><span>🕐</span><?= $bp['date'] ?></div>
        <span class="btn-devam">Devamı »</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<?php elseif($page==='blog' && $subpage):
  $post=null; foreach($blog_posts as $bp){if($bp['slug']===$subpage){$post=$bp;break;}} if(!$post)$post=$blog_posts[0];
  $blog_full_content = [
    'ankara-cankaya-kartvizit' => '
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">İstanbul\'da iş dünyasında öne çıkmak ve profesyonel bir izlenim bırakmak için kaliteli kartvizitler büyük önem taşır. İstanbul Kadıköy kartvizit firmaları, sadece iletişim bilgilerini değil, markanızın değerini ve imajını yansıtan tasarımlar sunar. Kadıköy Matbaa olarak, İstanbul Kadıköy bölgesinde en kaliteli kartvizit hizmetlerini sizlere sunuyoruz.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">1. Kartvizit Tasarımında Profesyonellik</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">İstanbul Kadıköy kartvizit firmaları, tasarım sürecinde markanın kimliğini ön plana çıkarır. Logo yerleşimi, renk uyumu ve yazı tipi seçimi, kartvizitlerin profesyonel görünmesini sağlar. Etkili bir tasarım, kartvizitlerinizin müşteriler üzerinde kalıcı bir izlenim bırakmasına yardımcı olur.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">2. Baskı Kalitesi ve Teknikleri</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Kartvizitler, baskı kalitesi ile doğrudan marka imajını etkiler. İstanbul Kadıköy kartvizit firmaları, dijital ve ofset baskı seçenekleri sunar. Dijital baskı hızlı ve ekonomik, ofset baskı ise yüksek adetli işler için idealdir. Her iki teknik de kaliteli ve net bir baskı için önemlidir.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">3. Kağıt ve Malzeme Seçimi</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Kartvizit baskısında kullanılacak kağıt ve malzeme, görünüm ve dokuyu belirler. İstanbul Kadıköy kartvizit firmaları, mat veya parlak yüzeyli, 300–350 gram kalınlığında kağıt seçenekleri sunar. Ayrıca çevreci ve dayanıklı kağıt türleri de tercih edilebilir.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">4. Kartvizit Adedi ve Teslim Süresi</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Sipariş adedi, kartvizit maliyetini ve teslim süresini etkiler. İstanbul Kadıköy kartvizit firmaları, ihtiyacınıza uygun adet seçenekleri ve hızlı teslimat çözümleri sunar. Kadıköy Matbaa ile siparişleriniz hem ekonomik hem de zamanında hazırlanır.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">5. Ekstra Detaylar ve Profesyonel Dokunuşlar</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Lak kaplama, kabartma ve spot UV gibi ekstra detaylar, kartvizitlerinizi daha etkileyici hale getirir. İstanbul Kadıköy kartvizit firmaları, bu profesyonel dokunuşları kullanarak kartvizitlerin kalıcı ve akılda kalıcı olmasını sağlar.</p>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">İstanbul Kadıköy kartvizit firmaları arasında markanızı ve kişisel imajınızı güçlendirmek için Kadıköy Matbaa\'yı tercih edin. Profesyonel tasarım, kaliteli baskı ve hızlı teslimat ile etkili kartvizitler elde edebilirsiniz.</p>
    ',
    'kartvizit-katalog-hatalar' => '
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Kartvizit ve katalog baskısı sürecinde yapılan küçük hatalar, marka imajınızı olumsuz etkileyebilir. Hem tasarım hem de baskı aşamasında dikkat edilmesi gereken detaylar vardır. Kadıköy Matbaa olarak, kartvizit ve katalog baskısı sürecinde sık yapılan hataları ve çözümlerini sizlerle paylaşıyoruz.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">1. Yanlış Kağıt Seçimi</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Kartvizit ve katalog baskısı sırasında en sık yapılan hatalardan biri yanlış kağıt seçmektir. İnce veya düşük kaliteli kağıt kullanımı, baskının hem dayanıklılığını hem de görselliğini azaltır. Kaliteli ve gramajı yeterli kağıt seçmek, kartvizit ve katalog baskısının profesyonel görünmesini sağlar.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">2. Renk Uyumsuzluğu</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Ekranda görünen renk ile baskı çıktısının farklı olması, baskı sürecinde sık karşılaşılan bir sorundur. Kartvizit ve katalog baskısı sırasında renk uyumu ve doğru renk profillerinin kullanılması gerekir. CMYK renk sistemi ile doğru renklerin elde edilmesi, baskının beklentileri karşılamasını sağlar.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">3. Eksik veya Yanlış Bilgi</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Kartvizit ve katalog baskısında eksik iletişim bilgileri veya hatalı ürün bilgileri ciddi sorunlar yaratabilir. Baskıya başlamadan önce tüm bilgiler doğru ve eksiksiz olmalıdır. Bu, hem kartvizit hem de katalog baskısının profesyonel ve güvenilir görünmesini sağlar.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">4. Düşük Çözünürlüklü Görseller</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Kartvizit ve katalog baskısında kullanılan görsellerin çözünürlüğü düşükse baskı kalitesi düşer ve tasarım bozulur. Profesyonel bir baskı için yüksek çözünürlüklü fotoğraflar ve vektör tabanlı grafikler kullanılmalıdır.</p>
      <h3 style="font-size:17px;font-weight:800;color:var(--navy);margin:24px 0 10px">5. Yetersiz Tasarım ve Düzen</h3>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Düzensiz ve sıkışık tasarım, kartvizit ve katalog baskısının etkisini azaltır. Boşlukları doğru kullanmak, yazı ve görselleri dengeli yerleştirmek önemlidir. Profesyonel bir tasarım, kartvizit ve katalog baskısının hem okunabilir hem de dikkat çekici olmasını sağlar.</p>
    ',
  ];
  $h1_style = ' style="font-size:28px;letter-spacing:1px"';
?>
<div class="page-hero">
  <h1<?= $h1_style ?>><?= $post['title'] ?></h1>
</div>
<div class="container section">
  <div class="content-wrap">
    <div class="content-main">
      <div class="blog-meta" style="margin-bottom:16px"><span>🕐</span><?= $post['date'] ?></div>
      <?php $blog_imgs=['ankara-cankaya-kartvizit'=>imgpath('images/blog/blog-1.jpg'),'kartvizit-katalog-hatalar'=>imgpath('images/blog/blog-2.jpg')]; ?>
      <img src="<?= $blog_imgs[$subpage] ?? 'https://placehold.co/600x400?text=Resim' ?>" alt="<?= $post['title'] ?>" style="width:100%;border-radius:6px;margin-bottom:24px">
      <?php if(isset($blog_full_content[$subpage])): ?>
        <?= $blog_full_content[$subpage] ?>
      <?php else: ?>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px"><?= $post['excerpt'] ?> Baskı kalitesi, marka imajını doğrudan etkiler. Bu nedenle doğru malzeme seçimi, renk kalibrasyonu ve profesyonel tasarım süreçlerinin eksiksiz yürütülmesi büyük önem taşımaktadır.</p>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500;margin-bottom:16px">Kadıköy Matbaa olarak 2008 yılından bu yana İstanbul'da müşterilerimize hızlı, kaliteli ve uygun fiyatlı baskı hizmetleri sunmaktayız.</p>
      <p style="font-size:15px;line-height:1.9;color:#0F2340;font-weight:500">Detaylı bilgi ve teklif almak için <a href="<?= url('iletisim') ?>" style="color:var(--red);font-weight:700">iletişim</a> sayfamızı ziyaret edebilirsiniz.</p>
      <?php endif; ?>
    </div>
    <aside class="content-sidebar">
      <div class="sidebar-hizmetler">
        <h3>Son Yazılar</h3>
        <div class="sidebar-links">
          <?php foreach($blog_posts as $bp): ?>
          <a href="<?= url('blog',$bp['slug']) ?>" class="<?= $bp['slug']===$subpage?'active-link':'' ?>"><?= $bp['title'] ?><span class="arr">»</span></a>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>
  </div>
</div>

<?php elseif($page==='hakkimizda'): ?>
<div class="page-hero">
  <h1>Hakkımızda</h1>
</div>
<div class="container section">
  <div class="about-grid">
    <div class="about-img">
      <img src="<?= imgpath('logo/kadikoy matbaa mainpage icon.png') ?>" alt="Kadıköy Matbaa">
    </div>
    <div class="about-text">
      <h2>Kadıköy Matbaa &amp; Reklam Ajansı</h2>
      <p>Kadıköy Matbaa olarak yaklaşık 15 yıldır baskı ve matbaa sektöründe hizmet veriyoruz. Kurulduğumuz günden bu yana önceliğimiz; işini ciddiye alan, zamanında teslim eden ve müşterisinin ne istediğini gerçekten anlayan bir ekip olmak oldu.</p>
      <p>Küçük işletmelerden kurumsal firmalara kadar geniş bir müşteri kitlesiyle çalışıyor, her projeye aynı özenle yaklaşıyoruz. Kartvizitten broşüre, cepli dosyadan özel tasarım baskı işlerine kadar farklı ihtiyaçlara pratik ve kaliteli çözümler sunuyoruz.</p>
      <p>Teknolojiyi yakından takip ediyor, üretim süreçlerimizi sürekli geliştiriyoruz. Ama bizim için asıl önemli olan; işin sonunda ortaya çıkan ürün kadar, süreç boyunca kurduğumuz güven ve iletişim.</p>
      <p>Bugün geldiğimiz noktada en büyük referansımız, bizimle tekrar çalışmayı tercih eden müşterilerimiz.</p>
      <div class="about-stats">
        <div class="stat-box"><div class="num">15+</div><div class="lbl">Yıllık Deneyim</div></div>
        <div class="stat-box"><div class="num">1000+</div><div class="lbl">Mutlu Müşteri</div></div>
      </div>
    </div>
  </div>
</div>

<?php elseif($page==='iletisim'): ?>
<div class="page-hero">
  <h1>İletişim</h1>
</div>
<div class="container section">
  <div class="contact-grid">
    <div class="contact-info">
      <h3>İletişim Bilgileri</h3>
      <div class="contact-item">
        <div class="contact-icon">📍</div>
        <div class="contact-item-body"><strong>Merkez Adres</strong><p>Uzunçayır Cd. Konur İş Merkezi No:2 Giriş Kat Kadıköy / İSTANBUL</p></div>
      </div>
      <div class="contact-item">
        <div class="contact-icon">📍</div>
        <div class="contact-item-body"><strong>Şube</strong><p> İSTANBUL / Kadıköy</p></div>
      </div>
      <div class="contact-item">
        <div class="contact-icon">✉</div>
        <div class="contact-item-body"><strong>E-posta</strong><p> info@kadikoymatbaa.net</p></div>
      </div>
      <div class="contact-item">
        <div class="contact-icon">📞</div>
        <div class="contact-item-body"><strong>Telefon</strong><p>+90 532 499 28 31</p></div>
      </div>
    </div>
    <div class="contact-form-card">
      <h3>Mesaj Gönderin</h3>
      <?php if($contact_success): ?>
        <div style="background:#e8f5e9;border-radius:6px;padding:14px;color:#2e7d32;font-weight:700;margin-bottom:16px">✔ Mesajınız alındı. En kısa sürede dönüş yapacağız!</div>
      <?php endif; ?>
      <form method="POST" action="<?= url('iletisim') ?>">
        <div class="form-group"><label>Ad Soyad *</label><input type="text" name="contact_name" placeholder="Adınız Soyadınız" required></div>
        <div class="form-group"><label>E-posta *</label><input type="email" name="contact_email" placeholder="ornek@email.com" required></div>
        <div class="form-group"><label>Telefon</label><input type="tel" name="contact_tel" placeholder="05xx xxx xx xx"></div>
        <div class="form-group"><label>Konu</label><input type="text" name="contact_konu" placeholder="Mesaj konusu"></div>
        <div class="form-group"><label>Mesajınız *</label><textarea name="contact_mesaj" placeholder="Mesajınızı buraya yazın..." required></textarea></div>
        <button type="submit" class="btn-submit">Gönder</button>
      </form>
    </div>
  </div>
  <div class="map-wrap" style="margin-top:40px">
    <iframe src="https://maps.google.com/maps?q=Uzun%C3%A7ay%C4%B1r+Cd.+Konur+%C4%B0%C5%9F+Merkezi+No%3A2+Kad%C4%B1k%C3%B6y+%C4%B0stanbul&output=embed" allowfullscreen="" loading="lazy"></iframe>
  </div>
</div>

<?php elseif($page==='Animasyon'): ?>
<div class="page-hero">
  <h1>Animasyon</h1>
</div>
<div class="container section">
  <p style="font-size:15px;color:#0F2340;font-weight:500;line-height:1.9">Animasyon hizmetlerimiz yakında bu sayfada görünecektir.</p>
</div>

<?php endif; ?>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col">
        <h4>Kadıköy Matbaa</h4>
        <p>Yaklaşık 15 yıldır baskı ve matbaa sektöründe hizmet veriyoruz. Kartvizitten broşüre, brandadan bayrak baskısına, fuar standından promosyona kadar her ihtiyaca kaliteli çözümler sunuyoruz.</p>
        <a href="https://www.instagram.com/kadikoy_matbaa/" class="footer-ig" target="_blank">
          <span class="ig-icon"><img src="<?= imgpath('images/instagram_icon.png') ?>" alt="Instagram" style="width:100%;height:100%;object-fit:cover;display:block"></span>Kadıköy Matbaa Instagram
        </a>
      </div>
      <div class="footer-col">
        <h4>Matbaa</h4>
        <ul>
          <?php foreach($matbaa_items as $sl=>$lbl): ?>
          <li><a href="<?= url('matbaa',$sl) ?>"><?= $lbl ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Branda Baskı</h4>
        <ul>
          <?php foreach($branda_items as $sl=>$lbl): ?>
          <li><a href="<?= url('branda-baski',$sl) ?>"><?= $lbl ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Fuar / Tanıtım</h4>
        <ul>
          <?php foreach($fuar_items as $sl=>$lbl): ?>
          <li><a href="<?= url('fuar-tanitim',$sl) ?>"><?= $lbl ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Bayrak Baskı</h4>
        <ul>
          <?php foreach($bayrak_items as $sl=>$lbl): ?>
          <li><a href="<?= url('bayrak-baski',$sl) ?>"><?= $lbl ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="footer-col">
        <h4>İletişim</h4>
        <div class="footer-ci"><span class="ico">📍</span><span>Uzunçayır Cd. Konur İş Merkezi No:2 Giriş Kat Kadıköy / İSTANBUL</span></div>
        <div class="footer-ci"><span class="ico">✉</span><span>info@kadikoymatbaa.net</span></div>
        <div class="footer-ci"><span class="ico">📞</span><span>+90 532 499 28 31</span></div>
      </div>
    </div>
    <div class="footer-bottom">
      <nav class="footer-bottom-nav">
        <a href="<?= url('anasayfa') ?>">Anasayfa</a>
        <a href="<?= url('matbaa') ?>">Matbaa</a>
        <a href="<?= url('branda-baski') ?>">Branda Baskı</a>
        <a href="<?= url('fuar-tanitim') ?>">Fuar / Tanıtım</a>
        <a href="<?= url('bayrak-baski') ?>">Bayrak Baskı</a>
        <a href="<?= url('blog') ?>">Blog</a>
        <a href="<?= url('hakkimizda') ?>">Hakkımızda</a>
        <a href="<?= url('iletisim') ?>">İletişim</a>
        <a href="<?= url('Animasyon') ?>">Animasyon</a>
      </nav>
      <p>&copy; <?= date('Y') ?> Kadıköy Matbaa. Tüm hakları saklıdır.</p>
    </div>
  </div>
</footer>

<!-- LIGHTBOX -->
<div id="lightbox-overlay" onclick="closeLightbox()">
  <button id="lightbox-close" onclick="closeLightbox()" title="Kapat">&#x2715;</button>
  <img id="lightbox-img" src="" alt="" onclick="event.stopPropagation()">
</div>

<script>
// Dropdown overflow fix
document.querySelectorAll('.nav-menu > li').forEach(function(li){
  li.addEventListener('mouseenter', function(){
    var dd = li.querySelector('.dropdown, .mega-dropdown');
    if (!dd) return;
    dd.style.left = '0';
    dd.style.right = 'auto';
    var rect = dd.getBoundingClientRect();
    if (rect.right > window.innerWidth) {
      dd.style.left = 'auto';
      dd.style.right = '0';
    }
  });
});

// Matbaa slider
// Infinite loop slider (both directions, seamless)
(function(){
  var slider = document.getElementById('matbaaSlider');
  if (!slider) return;

  var items = Array.from(slider.children);
  items.forEach(function(item){
    slider.appendChild(item.cloneNode(true));
    slider.insertBefore(item.cloneNode(true), slider.firstChild);
  });

  var itemWidth = items[0].offsetWidth + 16;
  var setWidth = itemWidth * items.length;

  // Jump to middle set instantly (no smooth animation)
  slider.style.scrollBehavior = 'auto';
  slider.scrollLeft = setWidth;
  slider.style.scrollBehavior = '';

  slider.addEventListener('scroll', function(){
    var pos = slider.scrollLeft;
    if (pos >= setWidth * 2) {
      slider.style.scrollBehavior = 'auto';
      slider.scrollLeft = pos - setWidth;
      slider.style.scrollBehavior = '';
    } else if (pos <= 0) {
      slider.style.scrollBehavior = 'auto';
      slider.scrollLeft = pos + setWidth;
      slider.style.scrollBehavior = '';
    }
  });

  window.moveSlider = function(dir) {
    slider.scrollBy({left: dir * 680, behavior: 'smooth'});
  };
})();

// Scroll reveal
(function(){
  var els = document.querySelectorAll('.reveal-box');
  if(!els.length) return;
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting){ e.target.classList.add('visible'); io.unobserve(e.target); }
    });
  },{threshold:0.15});
  els.forEach(function(el){ io.observe(el); });
})();

function openLightbox(src) {
  var img = document.getElementById('lightbox-img');
  var overlay = document.getElementById('lightbox-overlay');
  img.src = src;
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox-overlay').classList.remove('active');
  document.getElementById('lightbox-img').src = '';
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeLightbox(); });

function validateTeklif(form, e) {
  var input = form.querySelector('#teklif-telefon');
  if (!input) return true;
  var digits = input.value.replace(/\D/g, '');
  if (digits.length < 10 || digits.length > 11) {
    input.setCustomValidity('Geçersiz Numara');
    input.reportValidity();
    return false;
  }
  input.setCustomValidity('');
  var btn = form.querySelector('button[type="submit"]');
  if (btn) { btn.disabled = true; btn.textContent = 'Gönderiliyor...'; }
  return true;
}
</script>

</div><!-- /page-wrap -->
</body>
</html>
