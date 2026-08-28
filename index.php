<?php
require __DIR__ . '/cms.php';
require __DIR__ . '/routes.php';

// Which page does this URL ask for? (clean slug -> internal id)
$current = id_for_path($_SERVER['REQUEST_URI'] ?? '/');
if ($current === '') $current = 'home';

// id -> path map for the client router, and the reverse for popstate
$PATHS = [];
foreach (ROUTES as $path => $id) $PATHS[$id] = '/' . $path;

// per-page browser title
$siteTitle = cms('global.meta-title');
$pageNames = [
  'home' => '', 'about' => 'About', 'sell' => 'Sell', 'homevalue' => 'Home Value Strategy',
  'buy' => 'Buy', 'speaking' => 'Speaking', 'collaborations' => 'Collaborations',
  'lifestyle' => 'Lifestyle & Magazine', 'media' => 'Media & Press', 'testimonials' => 'Testimonials',
  'resources' => 'Resources', 'products' => 'Digital Products', 'explains' => 'Erika Explains',
  'mentorship' => 'Mentorship', 'investing' => 'Investing', 'pm' => 'Property Management',
  'transportation' => 'Transportation & Logistics', 'living' => 'Escaluxe Living',
  'loc-atlanta' => 'Atlanta Metro', 'loc-gwinnett' => 'Gwinnett / Lawrenceville',
  'loc-fayette' => 'Fayette / Peachtree City', 'gallery' => 'Gallery', 'contact' => 'Contact',
];
$pt = $pageNames[$current] ?? '';
$docTitle = ($pt && $current !== 'home') ? ($pt . ' · Erika Page') : $siteTitle;

ob_start();
?>
<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= esc($docTitle) ?></title>
<meta content="<?= cms_e('global.meta-desc') ?>" name="description"/>
<meta content="#453230" name="theme-color"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600&amp;family=Archivo:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<style>
:root{
  --ink:#453230; --ink-deep:#332423;
  --cream:#FBF7F3; --cream-2:#F5EBE4;
  --gold:#C9A15E; --gold-soft:#DCC08C; --gold-deep:#8F6B2E;
  --merlot:#C67F72; --merlot-deep:#B05E51; --merlot-ink:#9C4A3E;
  --blush:#D49388; --blush-soft:#EFD3CC;
  --body:#5C4B47; --line:rgba(69,50,48,.14);
  --shadow-s:0 2px 8px rgba(69,50,48,.06),0 1px 2px rgba(69,50,48,.05);
  --shadow-m:0 14px 30px rgba(69,50,48,.10),0 4px 10px rgba(69,50,48,.05);
  --shadow-l:0 34px 70px rgba(69,50,48,.18),0 12px 24px rgba(69,50,48,.08);
  --ease:cubic-bezier(.22,.68,.32,1);
}
*{margin:0;padding:0;box-sizing:border-box}
@media(prefers-reduced-motion:no-preference){html{scroll-behavior:smooth}}
body{font-family:'Archivo',sans-serif;background:var(--cream);color:var(--body);line-height:1.65;font-size:16px;-webkit-font-smoothing:antialiased}
h1,h2,h3,.serif{font-family:'Fraunces',serif;color:var(--ink);font-weight:500;line-height:1.12;text-wrap:balance}
a{text-decoration:none;color:inherit}
img{max-width:100%;display:block}
::selection{background:var(--blush-soft);color:var(--ink)}
:focus-visible{outline:2px solid var(--merlot-deep);outline-offset:3px;border-radius:2px}
.skip{position:absolute;left:-9999px;top:0;background:var(--ink);color:var(--cream);padding:12px 22px;z-index:200;font-size:13px;letter-spacing:.1em;text-transform:uppercase;font-weight:600}
.skip:focus{left:0}
.wrap{max-width:1160px;margin:0 auto;padding:0 24px}
.eyebrow{font-size:11px;letter-spacing:.24em;text-transform:uppercase;font-weight:700;color:var(--merlot-ink);display:inline-flex;align-items:center;gap:10px}
.eyebrow::before{content:"";width:22px;height:1px;background:var(--gold);flex:none}
.eyebrow.on-dark{color:var(--merlot-ink)}
.center-h .eyebrow{justify-content:center}
.center-h .eyebrow::after{content:"";width:22px;height:1px;background:var(--gold);flex:none}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:15px 30px;font-size:13px;letter-spacing:.14em;text-transform:uppercase;font-weight:600;cursor:pointer;border:1px solid transparent;transition:transform .25s var(--ease),box-shadow .25s var(--ease),background .25s,color .25s,border-color .25s}
.btn:hover{transform:translateY(-2px);box-shadow:var(--shadow-m)}
.btn:active{transform:translateY(0);box-shadow:var(--shadow-s)}
.btn-primary{background:var(--merlot-deep);color:#fff}
.btn-primary:hover{background:var(--merlot-ink)}
.btn-gold{background:linear-gradient(120deg,var(--gold) 0%,var(--gold-soft) 130%);color:var(--ink-deep)}
.btn-gold:hover{background:linear-gradient(120deg,#BC9251 0%,var(--gold) 130%)}
.btn-outline{border-color:var(--merlot-deep);color:var(--merlot-ink)}
.btn-outline:hover{background:var(--merlot-deep);color:#fff}
.btn-outline-dark{border-color:var(--merlot);color:var(--merlot-ink)}
.btn-outline-dark:hover{background:var(--merlot-deep);border-color:var(--merlot-deep);color:var(--cream)}
.rule{width:56px;height:2px;background:linear-gradient(90deg,var(--gold),var(--blush));margin:18px 0 22px}
.ph{background:linear-gradient(145deg,#EBD2CA 0%,#DFB3A8 55%,#D49388 100%);position:relative;overflow:hidden}
.ph::after{content:attr(data-label);position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:rgba(69,50,48,.6);font-size:11px;letter-spacing:.2em;text-transform:uppercase;text-align:center;padding:20px}
.ph::before{content:"";position:absolute;inset:14px;border:1px solid rgba(255,255,255,.55);z-index:1}
/* drop an <img> or <video> inside any .ph box to replace the placeholder */
.ph>img,.ph>video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.ph:has(>img)::after,.ph:has(>video)::after{display:none}
/* A slot holding a real video needs no imitation play button on top of it, and
   should not be cropped square: the box takes the clip's own shape instead.
   object-fit:contain is the backstop so a clip can never be stretched, only
   letterboxed against the dark ground. aspect-ratio has to be forced because the
   per-context rules below (.res-card .ph, .hero-media, .video-frame ...) are more
   specific than this one. */
.ph:has(>video){aspect-ratio:auto!important;width:fit-content;max-width:100%;margin-inline:auto;background:none}
.ph:has(>video)>video{position:static;display:block;width:auto;height:auto;max-width:100%;max-height:min(70vh,620px)}
.ph:has(>video) .play{display:none}
/* An empty picture slot is a to-do note for the admin, not content. Visitors should
   never see a pink box captioned "GUIDE COVER" — the slot simply collapses until a
   picture is set. Every slot is still listed in the admin whether it is filled or not. */
.ph:not(:has(>img)):not(:has(>video)),.res-card .ph:not(:has(>img)){display:none}
.topbar{background:var(--ink-deep);color:var(--cream);font-size:12.5px;letter-spacing:.06em;padding:10px 16px;text-align:center;white-space:nowrap;overflow-x:auto;scrollbar-width:none}
.topbar::-webkit-scrollbar{display:none}
.topbar b{color:var(--gold-soft);font-weight:600}
.topbar .mkt{cursor:pointer;padding:2px 4px;transition:color .2s}
.topbar .mkt:hover,.topbar .mkt:focus-visible{color:var(--gold-soft)}
.topbar .sep{color:var(--blush);margin:0 6px}
nav{background:rgba(255,255,255,.86);backdrop-filter:blur(14px) saturate(1.5);-webkit-backdrop-filter:blur(14px) saturate(1.5);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:50;transition:box-shadow .3s var(--ease)}
nav.scrolled{box-shadow:0 10px 30px rgba(69,50,48,.09)}
.nav-inner{display:flex;align-items:center;justify-content:space-between;gap:32px;padding:16px 24px;max-width:1320px;margin:0 auto}
.brand{font-family:'Fraunces',serif;font-size:22px;color:var(--ink);cursor:pointer;flex:none;white-space:nowrap}
.brand span{color:var(--merlot-deep)}
.nav-links{display:flex;gap:18px;list-style:none;align-items:center}
.nav-links>li{position:relative}
.nav-links a{font-size:11.5px;letter-spacing:.1em;text-transform:uppercase;font-weight:600;color:var(--ink);padding:6px 0;border-bottom:2px solid transparent;cursor:pointer;display:inline-block;white-space:nowrap;transition:color .2s,border-color .2s}
.nav-links a:hover,.nav-links a.active{border-color:var(--gold);color:var(--merlot-ink)}
.drop{display:block;visibility:hidden;opacity:0;transform:translateY(6px);position:absolute;top:100%;left:-14px;background:#fff;border:1px solid var(--line);min-width:220px;padding:10px 0;box-shadow:var(--shadow-m);z-index:60;transition:opacity .22s var(--ease),transform .22s var(--ease),visibility .22s}
.has-drop:hover .drop,.has-drop:focus-within .drop{visibility:visible;opacity:1;transform:none}
.drop a{display:block;padding:9px 18px;border-bottom:none!important;font-size:11.5px}
.drop a:hover,.drop a:focus-visible{background:var(--cream-2);color:var(--merlot-ink)}
.nav-cta{background:var(--merlot-deep);color:var(--cream)!important;padding:10px 16px!important;border-bottom:none!important;transition:background .25s,box-shadow .25s!important}
.nav-cta:hover{background:var(--merlot-ink);box-shadow:var(--shadow-s)}
.burger{display:none;background:none;border:none;color:var(--ink);cursor:pointer;padding:8px;line-height:0}
.burger svg{width:24px;height:24px}
.page{display:none}
.page.active{display:block;animation:pagein .5s var(--ease)}
@keyframes pagein{from{opacity:0}to{opacity:1}}
.hero{background:linear-gradient(150deg,#FBF3EF 0%,#F5DFD8 55%,#EFCFC6 100%);color:var(--ink);position:relative;overflow:hidden}
.hero::before{content:"";position:absolute;right:-140px;top:-140px;width:520px;height:520px;border-radius:50%;background:radial-gradient(circle,rgba(212,147,136,.45),transparent 68%);animation:drift 14s ease-in-out infinite alternate}
.hero::after{content:"";position:absolute;left:-120px;bottom:-180px;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(201,161,94,.22),transparent 70%)}
@keyframes drift{from{transform:translate(0,0) scale(1)}to{transform:translate(-40px,30px) scale(1.06)}}
.hero-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:56px;align-items:center;padding-block:88px 72px;position:relative;z-index:2}
.hero h1{color:var(--ink);font-size:clamp(42px,6vw,72px);font-weight:400}
.hero h1 em{font-style:italic;color:var(--merlot-deep)}
.hero .pos{font-size:14px;letter-spacing:.3em;text-transform:uppercase;color:var(--merlot-ink);margin:18px 0 20px;font-weight:600}
.hero p.lede{color:var(--body);font-size:18px;max-width:520px;margin-bottom:34px}
.hero-ctas{display:flex;gap:16px;flex-wrap:wrap}
.hero-badges{display:flex;gap:22px;flex-wrap:wrap;margin-top:34px;font-size:12px;letter-spacing:.08em;color:var(--merlot-ink);font-weight:600}
.hero-badges span{display:inline-flex;align-items:center;gap:8px}
.hero-badges svg{width:15px;height:15px;color:var(--gold-deep)}
.hero-photo{aspect-ratio:4/5;box-shadow:var(--shadow-l)}
.hero-photo::before{inset:16px;border-color:rgba(255,255,255,.7)}
.proof{background:linear-gradient(120deg,#3D2B29 0%,#543B37 100%);color:var(--cream);position:relative;overflow:hidden}
.proof::before{content:"";position:absolute;inset:0;background:radial-gradient(640px 220px at 78% 0%,rgba(201,161,94,.16),transparent)}
.proof-grid{display:grid;grid-template-columns:repeat(3,1fr);text-align:center;position:relative}
.proof-item{padding:40px 20px;border-left:1px solid rgba(251,247,243,.12)}
.proof-item:first-child{border-left:none}
.proof-item .num{font-family:'Fraunces',serif;font-size:44px;color:var(--gold-soft);line-height:1;font-variant-numeric:tabular-nums}
.proof-item .lbl{font-size:11px;letter-spacing:.22em;text-transform:uppercase;margin-top:10px;color:rgba(251,247,243,.72)}
section{padding:84px 0}
.split{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center}
.split h2{font-size:clamp(30px,3.6vw,44px)}
.split p{margin:16px 0 26px;max-width:480px}
.tall{aspect-ratio:4/5;box-shadow:var(--shadow-m)}
.wide{aspect-ratio:16/10;box-shadow:var(--shadow-m)}
.alt{background:var(--cream-2)}
.dark{background:var(--blush-soft);color:var(--ink)}
.dark h2,.dark h3{color:var(--ink)}
.dark p{color:var(--body)}
.center-h{text-align:center}
.center-h h2{font-size:clamp(28px,3.4vw,40px);margin-top:10px}
.video-frame{aspect-ratio:16/9;margin-top:36px;cursor:pointer;box-shadow:var(--shadow-m);transition:box-shadow .3s var(--ease)}
.video-frame:hover{box-shadow:var(--shadow-l)}
.play{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:76px;height:76px;border-radius:50%;background:var(--merlot-deep);display:flex;align-items:center;justify-content:center;z-index:2;transition:background .25s,transform .25s var(--ease);cursor:pointer}
.play::before{content:"";position:absolute;inset:-11px;border-radius:50%;border:1px solid rgba(176,94,81,.45);animation:pulse 2.6s var(--ease) infinite}
@keyframes pulse{0%{transform:scale(.9);opacity:0}35%{opacity:1}100%{transform:scale(1.18);opacity:0}}
.play::after{content:"";border-left:20px solid #fff;border-top:12px solid transparent;border-bottom:12px solid transparent;margin-left:5px}
.video-frame:hover .play,.ph:hover .play{background:var(--merlot-ink);transform:translate(-50%,-50%) scale(1.06)}
.stars{display:flex;gap:3px;color:var(--gold);margin-bottom:14px}
.stars svg{width:15px;height:15px;fill:currentColor;stroke:none}
.tst-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;margin-top:44px}
.tst{background:var(--cream);padding:36px 30px;border-top:3px solid var(--gold);box-shadow:var(--shadow-s);transition:transform .3s var(--ease),box-shadow .3s var(--ease)}
.tst:hover{transform:translateY(-4px);box-shadow:var(--shadow-m)}
.tst blockquote{font-family:'Fraunces',serif;font-size:18px;color:var(--ink);line-height:1.5;font-style:italic}
.tst .who{margin-top:18px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;font-weight:700;color:var(--merlot-ink)}
.dark .tst{background:#fff}
.dark .tst blockquote{color:var(--ink)}
.dark .tst .who{color:var(--merlot-ink)}
.eco-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:44px}
.eco{background:#fff;border:1px solid var(--line);padding:34px 28px;transition:border-color .25s,transform .3s var(--ease),box-shadow .3s var(--ease);position:relative}
.eco:hover{border-color:var(--gold);transform:translateY(-4px);box-shadow:var(--shadow-m)}
.eco h3{font-size:22px;margin-bottom:10px}
.eco p{font-size:14.5px;margin-bottom:18px}
.eco a{font-size:12px;letter-spacing:.16em;text-transform:uppercase;font-weight:700;color:var(--merlot-ink);cursor:pointer;display:inline-flex;align-items:center;gap:7px}
.eco a::after{content:"";width:16px;height:2px;background:currentColor;clip-path:polygon(0 40%,72% 40%,72% 0,100% 50%,72% 100%,72% 60%,0 60%);transition:transform .25s var(--ease)}
.eco a:hover::after{transform:translateX(4px)}
.eco .tag{position:absolute;top:0;right:0;background:var(--merlot-deep);color:#fff;font-size:10px;letter-spacing:.16em;text-transform:uppercase;font-weight:700;padding:5px 12px}
.res-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;margin-top:44px}
.res-card{background:#fff;border:1px solid var(--line);cursor:pointer;transition:border-color .25s,transform .3s var(--ease),box-shadow .3s var(--ease);overflow:hidden}
.res-card:hover{border-color:var(--gold);transform:translateY(-4px);box-shadow:var(--shadow-m)}
.res-card .ph{aspect-ratio:16/10;transition:transform .5s var(--ease)}
.res-card:hover .ph{transform:scale(1.03)}
.res-card .body{padding:24px;position:relative;background:#fff;z-index:2}
.res-card .cat{font-size:10px;letter-spacing:.2em;text-transform:uppercase;font-weight:700;color:var(--gold-deep);margin-bottom:8px}
.res-card h3{font-size:19px;line-height:1.3}
.res-card p{font-size:14px;margin-top:8px}
.final{background:linear-gradient(120deg,var(--merlot-ink),var(--merlot-deep) 55%,var(--blush));color:#fff;text-align:center;position:relative;overflow:hidden}
.final::before{content:"";position:absolute;right:-100px;top:-120px;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.12),transparent 70%)}
.final h2{color:#fff;font-size:clamp(30px,4vw,48px);max-width:720px;margin:0 auto;position:relative}
.final p{max-width:560px;margin:18px auto 34px;color:rgba(255,255,255,.92);position:relative}
.final .btn-outline{border-color:rgba(255,255,255,.75);color:#fff}
.final .btn-outline:hover{background:#fff;color:var(--merlot-ink)}
.page-hero{background:linear-gradient(150deg,#FBF3EF 0%,#F4DCD4 100%);color:var(--ink);padding:68px 0 54px;position:relative;overflow:hidden}
.page-hero::before{content:"";position:absolute;right:-160px;top:-160px;width:440px;height:440px;border-radius:50%;background:radial-gradient(circle,rgba(212,147,136,.35),transparent 68%)}
.page-hero>.wrap{position:relative}
.page-hero h1{color:var(--ink);font-size:clamp(34px,4.4vw,54px);font-weight:400;max-width:780px}
.page-hero h1 em{color:var(--merlot-deep)}
.page-hero p{max-width:640px;margin-top:16px;color:var(--body);font-size:17px}
.form-card{background:#fff;border:1px solid var(--line);border-top:3px solid var(--gold);padding:44px;box-shadow:var(--shadow-m)}
.form-card h3{font-size:24px;margin-bottom:6px}
.form-card .sub{font-size:14px;margin-bottom:26px}
.fld{margin-bottom:16px}
.fld label{display:block;font-size:11px;letter-spacing:.16em;text-transform:uppercase;font-weight:700;color:var(--ink);margin-bottom:6px}
.fld input,.fld select,.fld textarea{width:100%;padding:13px 14px;border:1px solid var(--line);background:var(--cream);font-family:'Archivo';font-size:15px;color:var(--ink);transition:border-color .2s,background .2s,box-shadow .2s}
.fld input:hover,.fld select:hover,.fld textarea:hover{border-color:rgba(69,50,48,.3)}
.fld input:focus,.fld select:focus,.fld textarea:focus{outline:none;border-color:var(--merlot-deep);background:#fff;box-shadow:0 0 0 3px rgba(176,94,81,.15)}
.fld input::placeholder,.fld textarea::placeholder{color:rgba(92,75,71,.55)}
.fld-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.consent{display:flex;gap:10px;align-items:flex-start;font-size:12.5px;margin:18px 0 22px;cursor:pointer}
.consent input{margin-top:3px;accent-color:var(--merlot-deep);width:15px;height:15px;flex:none}
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin-top:44px}
.step{border-top:2px solid var(--gold);padding-top:20px;transition:transform .3s var(--ease)}
.step:hover{transform:translateY(-3px)}
.step .k{font-family:'Fraunces',serif;font-size:15px;color:var(--merlot-ink);font-style:italic;margin-bottom:8px}
.step h3{font-size:19px;margin-bottom:8px}
.step p{font-size:14px}
.dark .step h3{color:var(--ink)}
.topics{display:grid;grid-template-columns:repeat(2,1fr);gap:22px;margin-top:44px}
.topic{background:#fff;border:1px solid var(--line);border-left:3px solid var(--gold);padding:30px 28px;transition:transform .3s var(--ease),box-shadow .3s var(--ease),border-color .25s}
.topic:hover{transform:translateY(-3px);box-shadow:var(--shadow-m);border-left-color:var(--merlot-deep)}
.topic h3{font-size:21px;color:var(--merlot-ink);margin-bottom:8px}
.topic p{font-size:14px}
.topic .fit{display:inline-block;margin-top:12px;font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:var(--gold-deep);font-weight:700}
.faq{max-width:760px;margin:0 auto}
.faq details{border-bottom:1px solid var(--line);padding:20px 0}
.faq summary{font-family:'Fraunces',serif;font-size:19px;color:var(--ink);cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center;gap:16px;transition:color .2s}
.faq summary::-webkit-details-marker{display:none}
.faq summary:hover{color:var(--merlot-ink)}
.faq summary::after{content:"";flex:none;width:14px;height:14px;background:var(--gold-deep);clip-path:polygon(45% 0,55% 0,55% 45%,100% 45%,100% 55%,55% 55%,55% 100%,45% 100%,45% 55%,0 55%,0 45%,45% 45%);transition:transform .3s var(--ease)}
.faq details[open] summary::after{transform:rotate(135deg)}
.faq details p{margin-top:12px;font-size:15px;max-width:680px}
.dark .faq details{border-color:rgba(69,50,48,.18)}
.dark .faq summary{color:var(--ink)}
.rev-filter{display:flex;gap:10px;flex-wrap:wrap;margin-top:36px}
.chip{padding:9px 18px;border:1px solid var(--line);font-size:12px;letter-spacing:.1em;text-transform:uppercase;font-weight:600;cursor:pointer;background:#fff;transition:background .2s,color .2s,border-color .2s,transform .2s var(--ease)}
.chip:hover{border-color:var(--merlot);color:var(--merlot-ink);transform:translateY(-1px)}
.chip.on{background:var(--merlot-deep);color:var(--cream);border-color:var(--merlot-deep)}
.page-hero .chip{background:rgba(255,255,255,.55);border-color:rgba(69,50,48,.2);color:var(--ink)}
.page-hero .chip.on{background:var(--merlot-deep);color:#fff;border-color:var(--merlot-deep)}
.prod-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:26px;margin-top:44px}
.prod{background:#fff;border:1px solid var(--line);padding:0;display:flex;flex-direction:column;transition:border-color .25s,transform .3s var(--ease),box-shadow .3s var(--ease)}
.prod:hover{border-color:var(--gold);transform:translateY(-4px);box-shadow:var(--shadow-m)}
.prod .ph{aspect-ratio:4/3}
.prod .body{padding:26px;display:flex;flex-direction:column;flex:1}
.prod .aud{font-size:10px;letter-spacing:.18em;text-transform:uppercase;font-weight:700;color:var(--gold-deep);margin-bottom:8px}
.prod h3{font-size:20px;margin-bottom:6px}
.prod .prob{font-size:13px;font-style:italic;color:var(--merlot-ink);margin-bottom:10px;font-family:'Fraunces',serif}
.prod p{font-size:14px;flex:1}
.prod .btn{margin-top:18px;text-align:center}
.cats{display:flex;gap:10px;flex-wrap:wrap;margin-top:28px}
.cat-pill{padding:8px 18px;background:#fff;border:1px solid var(--line);font-size:12px;letter-spacing:.1em;text-transform:uppercase;font-weight:600}
.dark .cat-pill{background:#fff;border-color:var(--line);color:var(--ink)}
.press-strip{display:flex;gap:20px;flex-wrap:wrap;margin-top:36px}
.press-logo{flex:1;min-width:150px;height:72px;background:#fff;border:1px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:12px;letter-spacing:.2em;text-transform:uppercase;color:var(--body);font-weight:600;transition:border-color .25s,box-shadow .3s var(--ease)}
.press-logo:hover{border-color:var(--gold);box-shadow:var(--shadow-s)}
.lead-mag{background:linear-gradient(120deg,var(--merlot-ink),var(--merlot-deep));color:#fff;padding:44px;display:grid;grid-template-columns:1.2fr .8fr;gap:36px;align-items:center;margin-top:56px;box-shadow:var(--shadow-m)}
.lead-mag h3{color:#fff;font-size:26px}
.lead-mag p{color:rgba(255,255,255,.9);margin-top:10px;font-size:15px}
.lead-mag .eyebrow::before{background:var(--gold-soft)}
.lead-mag .fld input{background:#fff;border:none}
.checklist{list-style:none;margin-top:24px}
.checklist li{padding:12px 0 12px 34px;position:relative;border-bottom:1px solid var(--line);font-size:15px}
.checklist li::before{content:"";position:absolute;left:2px;top:19px;width:15px;height:10px;border-left:2px solid var(--gold-deep);border-bottom:2px solid var(--gold-deep);transform:rotate(-45deg)}
.dark .checklist li{border-color:rgba(69,50,48,.18);color:var(--body)}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:44px;align-items:start}
.info-card{background:#fff;border:1px solid var(--line);border-top:3px solid var(--gold);padding:32px;box-shadow:var(--shadow-s)}
.info-card h3{font-size:20px;margin-bottom:10px}
.info-card p{font-size:14.5px}
.contact-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:26px;margin-top:44px}
footer{background:#3D2B29;color:rgba(251,247,243,.75);padding:64px 0 32px;font-size:14px}
.foot-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr 1fr;gap:38px;padding-bottom:44px;border-bottom:1px solid rgba(246,241,231,.12)}
footer h4{font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:var(--blush);margin-bottom:16px;font-weight:700}
footer ul{list-style:none}
footer li{margin-bottom:10px}
footer a{cursor:pointer;transition:color .2s}
footer a:hover,footer a:focus-visible{color:var(--gold-soft)}
.foot-brand{font-family:'Fraunces',serif;font-size:22px;color:#FBF7F3}
.foot-bottom{display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;padding-top:26px;font-size:12px;color:rgba(246,241,231,.5)}
#backtop{position:fixed;right:22px;bottom:22px;width:46px;height:46px;border-radius:50%;background:var(--ink);color:var(--cream);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-m);opacity:0;pointer-events:none;transform:translateY(10px);transition:opacity .3s var(--ease),transform .3s var(--ease),background .25s;z-index:80}
#backtop.show{opacity:1;pointer-events:auto;transform:none}
#backtop:hover{background:var(--merlot-deep)}
#backtop svg{width:18px;height:18px}

/* moving ticker tape */
.ticker{background:linear-gradient(120deg,#3D2B29 0%,#543B37 100%);color:var(--cream);overflow:hidden;padding:20px 0;position:relative}
.ticker-track{display:flex;width:max-content;animation:tickermove 40s linear infinite}
.ticker:hover .ticker-track{animation-play-state:paused}
.ticker-group{display:flex;align-items:center;flex:none}
.ticker-group span{display:inline-flex;align-items:center;white-space:nowrap;padding:0 26px;font-size:12px;letter-spacing:.2em;text-transform:uppercase;font-weight:600;color:rgba(251,247,243,.8)}
.ticker-group span b{color:var(--gold-soft);font-family:'Fraunces',serif;font-size:22px;font-weight:500;letter-spacing:0;margin-right:12px;font-variant-numeric:tabular-nums}
.ticker-group span::after{content:"";width:6px;height:6px;background:var(--gold);transform:rotate(45deg);margin-left:52px}
@keyframes tickermove{from{transform:translateX(0)}to{transform:translateX(-50%)}}
@media(prefers-reduced-motion:reduce){
  .ticker-track{animation:none;width:auto;flex-wrap:wrap;justify-content:center}
  .ticker-group{flex-wrap:wrap;justify-content:center}
  .ticker-group[aria-hidden]{display:none}
}

/* full-body cutout photo (transparent background) */
.cutout{position:relative;aspect-ratio:4/5;display:flex;align-items:flex-end;justify-content:center;overflow:hidden;background:radial-gradient(62% 52% at 50% 66%,rgba(212,147,136,.55),transparent 74%)}
.cutout svg{height:90%;width:auto;display:block}
.cutout>img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;object-position:bottom}
.cutout::after{content:attr(data-label);position:absolute;left:0;right:0;bottom:14px;text-align:center;color:rgba(69,50,48,.55);font-size:11px;letter-spacing:.2em;text-transform:uppercase;pointer-events:none}
.cutout:has(>img) svg,.cutout:has(>img)::after{display:none}

/* homepage thumbnail gallery strip */
.thumb-strip{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-top:44px}
.thumb-strip .g{position:relative;cursor:pointer;overflow:hidden}
.thumb-strip .ph{aspect-ratio:1/1;transition:transform .5s var(--ease)}
.thumb-strip .ph::before{inset:8px}
.thumb-strip .ph::after{font-size:9px;letter-spacing:.14em;padding:10px}
.thumb-strip .g:hover .ph{transform:scale(1.05)}

/* scroll reveal */
.reveal{opacity:0;transform:translateY(26px);transition:opacity .7s var(--ease),transform .7s var(--ease)}
.reveal.in{opacity:1;transform:none}

/* two-column page heroes with media */
.hero2{display:grid;grid-template-columns:1.15fr .85fr;gap:48px;align-items:center}
/* Erika's photography is almost all portrait. A 4:3 frame cropped 44% off every
   page hero — the head-and-shoulders sliver the client flagged. A square keeps
   the shot readable and still balances the text column beside it. */
.hero-media{aspect-ratio:1/1;box-shadow:var(--shadow-l);width:100%}
/* gallery */
.gal{columns:3;column-gap:18px;margin-top:44px}
.gal .g{break-inside:avoid;margin-bottom:18px;position:relative;cursor:pointer;overflow:hidden}
.gal .g .ph{width:100%;transition:transform .5s var(--ease)}
.gal .g:hover .ph{transform:scale(1.04)}
.gal .cap,.thumb-strip .cap{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(51,36,35,.85));color:#fff;padding:34px 16px 14px;font-size:11px;letter-spacing:.16em;text-transform:uppercase;font-weight:600;opacity:0;transition:opacity .3s;z-index:3}
.thumb-strip .cap{padding:26px 12px 10px;font-size:9px}
.gal .g:hover .cap,.gal .g:focus-visible .cap,.thumb-strip .g:hover .cap,.thumb-strip .g:focus-visible .cap{opacity:1}

@media(max-width:1279px){
  .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:var(--cream);flex-direction:column;padding:20px 24px;border-bottom:1px solid var(--line);gap:14px;align-items:flex-start;max-height:70vh;overflow:auto;box-shadow:var(--shadow-m)}
  .nav-links.open{display:flex}
  .nav-links a{font-size:12px;letter-spacing:.12em}
  .drop{visibility:visible;opacity:1;transform:none;position:static;box-shadow:none;border:none;background:transparent;padding:4px 0 0 14px;min-width:0;transition:none}
  .burger{display:block}
}
@media(max-width:920px){
  .hero-grid,.split,.foot-grid,.two-col,.lead-mag{grid-template-columns:1fr}
  .hero-grid{padding-block:56px 48px;gap:36px}
  .tst-grid,.eco-grid,.res-grid,.steps,.prod-grid,.contact-grid{grid-template-columns:1fr}
  .topics,.fld-row{grid-template-columns:1fr}
  .hero2{grid-template-columns:1fr}
  .thumb-strip{grid-template-columns:repeat(3,1fr)}
  .gal{columns:2}
  .proof-grid{grid-template-columns:1fr}
  .proof-item{border-left:none;border-top:1px solid rgba(251,247,243,.12)}
  .proof-item:first-child{border-top:none}
  section{padding:54px 0}
  .form-card{padding:32px 24px}
}
@media(max-width:560px){
  .gal{columns:2;column-gap:10px}
  .gal .g{margin-bottom:10px}
  .gal .cap{opacity:1;font-size:9px;letter-spacing:.1em;padding:22px 8px 8px}
  .thumb-strip{grid-template-columns:repeat(2,1fr)}
  .hero-ctas .btn{width:100%}
  /* the trust badges ran off the right edge on a phone */
  .hero-badges{gap:10px 16px;font-size:11px;letter-spacing:.04em}
  .hero-badges span{max-width:100%}
}
@media(prefers-reduced-motion:reduce){
  *,*::before,*::after{animation:none!important;transition:none!important}
  .reveal{opacity:1;transform:none}
}
.formflash{max-width:1160px;margin:18px auto 0;padding:14px 22px;font-size:14.5px;letter-spacing:.01em;border-radius:2px}
.formflash.ok{background:#EEF6EC;border:1px solid #9CC493;color:#3E6B36}
.formflash.err{background:#FBEAEA;border:1px solid #D9908B;color:#8C3B32}
@media(max-width:1180px){.formflash{margin:18px 24px 0}}

/* ---- gutter guard -------------------------------------------------------
   .wrap is the page gutter. When another class is stacked on the same element
   (.wrap.hero-grid, for instance) its `padding` shorthand silently resets the
   side padding and the section renders edge to edge — which is exactly what
   broke the home hero on phones. Declared last, so the gutter always survives. */
.wrap{padding-inline:24px}
/* Nothing may push the document wider than the screen. */
html,body{max-width:100%;overflow-x:clip}
</style>
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>
<div class="topbar"><span class="mkt" onclick="go('loc-atlanta')">Atlanta Metro</span><span aria-hidden="true" class="sep">·</span><span class="mkt" onclick="go('loc-gwinnett')">Gwinnett County</span><span aria-hidden="true" class="sep">·</span><span class="mkt" onclick="go('loc-gwinnett')">Lawrenceville</span><span aria-hidden="true" class="sep">·</span><span class="mkt" onclick="go('loc-fayette')">Peachtree City / Fayette</span><span aria-hidden="true" class="sep">·</span><span class="mkt" onclick="alert('Sandy Springs / Roswell / Alpharetta local page — coming soon (mockup)')">Sandy Springs / Roswell / Alpharetta</span><span aria-hidden="true" class="sep">·</span><span class="mkt" onclick="alert('East Cobb / Marietta local page — coming soon (mockup)')">East Cobb / Marietta</span><span aria-hidden="true" class="sep">·</span><span class="mkt" onclick="alert('Brookhaven / Decatur / Tucker local page — coming soon (mockup)')">Brookhaven / Decatur / Tucker</span><span aria-hidden="true" class="sep">·</span><span class="mkt" onclick="alert('McDonough / Henry local page — coming soon (mockup)')">McDonough / Henry</span>  ·  <b><?= cms_e('global.phone') ?></b></div>
<nav>
<div class="nav-inner">
<a class="brand" href="/" data-nav="home" onclick="return _nav(event,'home')">Erika<span>K</span>Page</a>
<button aria-controls="nl" aria-expanded="false" aria-label="Toggle navigation menu" class="burger"><svg aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewbox="0 0 24 24"><line x1="3" x2="21" y1="6" y2="6"></line><line x1="3" x2="21" y1="12" y2="12"></line><line x1="3" x2="21" y1="18" y2="18"></line></svg></button>
<ul class="nav-links" id="nl">
<li><a class="active" data-p="home" href="/" data-nav="home" onclick="return _nav(event,'home')">Home</a></li>
<li class="has-drop"><a data-p="sell" href="/sell" data-nav="sell" onclick="return _nav(event,'sell')">Sell ▾</a>
<div class="drop"><a href="/sell" data-nav="sell" onclick="return _nav(event,'sell')">Sell Your Home</a><a href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')">Home Value Strategy</a></div></li>
<li><a data-p="buy" href="/buy" data-nav="buy" onclick="return _nav(event,'buy')">Buy</a></li>
<li><a data-p="speaking" href="/speaking" data-nav="speaking" onclick="return _nav(event,'speaking')">Speaking</a></li>
<li class="has-drop"><a data-p="lifestyle" href="/lifestyle" data-nav="lifestyle" onclick="return _nav(event,'lifestyle')">Lifestyle &amp; Media ▾</a>
<div class="drop"><a href="/lifestyle" data-nav="lifestyle" onclick="return _nav(event,'lifestyle')">Lifestyle Magazine</a><a href="/media" data-nav="media" onclick="return _nav(event,'media')">Media &amp; Press</a><a href="/collaborations" data-nav="collaborations" onclick="return _nav(event,'collaborations')">Collaborations</a></div></li>
<li class="has-drop"><a data-p="resources" href="/resources" data-nav="resources" onclick="return _nav(event,'resources')">Resources ▾</a>
<div class="drop"><a href="/resources" data-nav="resources" onclick="return _nav(event,'resources')">All Resources</a><a href="/digital-products" data-nav="products" onclick="return _nav(event,'products')">Digital Products</a><a href="/erika-explains" data-nav="explains" onclick="return _nav(event,'explains')">Erika Explains</a><a href="/mentorship" data-nav="mentorship" onclick="return _nav(event,'mentorship')">Mentorship</a><a href="/investing" data-nav="investing" onclick="return _nav(event,'investing')">Investing / C&amp;A</a><a href="/property-management" data-nav="pm" onclick="return _nav(event,'pm')">Property Management</a><a href="/transportation-logistics" data-nav="transportation" onclick="return _nav(event,'transportation')">Transportation &amp; Logistics</a><a href="/escaluxe-living" data-nav="living" onclick="return _nav(event,'living')">Escaluxe Living</a></div></li>
<li><a data-p="gallery" href="/gallery" data-nav="gallery" onclick="return _nav(event,'gallery')">Gallery</a></li>
<li><a data-p="testimonials" href="/testimonials" data-nav="testimonials" onclick="return _nav(event,'testimonials')">Testimonials</a></li>
<li><a data-p="contact" href="/contact" data-nav="contact" onclick="return _nav(event,'contact')">Contact</a></li>
<li><a class="nav-cta" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')">Home Value Strategy</a></li>
</ul>
</div>
</nav>
<main id="main">
<!-- ==================== HOME / ==================== -->
<div class="page active" id="page-home">
<header class="hero">
<div class="wrap hero-grid">
<div>
<p class="eyebrow on-dark"><?= cms_e('home.atlanta-metro-established-au.eyebrow1') ?></p>
<h1 style="margin-top:14px"><?= cms_rich('home.atlanta-metro-established-au.heading1') ?></h1>
<p class="pos"><?= cms_e('home.atlanta-metro-established-au.eyebrow2') ?></p>
<p class="lede"><?= cms_rich('home.atlanta-metro-established-au.p1') ?></p>
<div class="hero-ctas">
<a class="btn btn-gold" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')"><?= cms_e('home.atlanta-metro-established-au.btn1') ?></a>
<a class="btn btn-outline" href="/speaking" data-nav="speaking" onclick="return _nav(event,'speaking')"><?= cms_e('home.atlanta-metro-established-au.btn2') ?></a>
<a class="btn btn-outline" onclick="document.getElementById('ecosystem').scrollIntoView({behavior:'smooth'})"><?= cms_e('home.atlanta-metro-established-au.btn3') ?></a>
</div>
<div class="hero-badges">
<span><svg aria-hidden="true" fill="currentColor" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><?= cms_e('home.atlanta-metro-established-au.badge1') ?></span>
<span><svg aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg><?= cms_e('home.atlanta-metro-established-au.badge2') ?></span>
<span><svg aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M3 11l9-8 9 8"></path><path d="M5 9.5V21h14V9.5"></path></svg><?= cms_e('home.atlanta-metro-established-au.badge3') ?></span>
</div>
</div>
<!-- Any .ph box (this headshot, the page heroes, listing photos...) accepts real media:
           photo: <div class="ph hero-photo"><img src="erika-headshot.jpg" alt="Erika Page"></div>
           video: <div class="ph hero-photo"><video src="intro.mp4" autoplay muted loop playsinline></video></div> -->
<div class="ph hero-photo" data-label="Erika — Headshot · Photo or Video"><?= cms_img('home.atlanta-metro-established-au.img-erika-headshot-photo-o', true, 'hero') ?></div>
</div>
</header>
<div aria-label="Erika Page career highlights" class="ticker">
<div class="ticker-track">
<div class="ticker-group"><span><?= cms_rich('home.ticker.item1') ?></span><span><?= cms_rich('home.ticker.item2') ?></span><span><?= cms_rich('home.ticker.item3') ?></span><span><?= cms_rich('home.ticker.item4') ?></span><span><?= cms_rich('home.ticker.item5') ?></span><span><?= cms_rich('home.ticker.item6') ?></span><span><?= cms_rich('home.ticker.item7') ?></span><span><?= cms_rich('home.ticker.item8') ?></span></div>
<div aria-hidden="true" class="ticker-group"><span><?= cms_rich('home.ticker.item1') ?></span><span><?= cms_rich('home.ticker.item2') ?></span><span><?= cms_rich('home.ticker.item3') ?></span><span><?= cms_rich('home.ticker.item4') ?></span><span><?= cms_rich('home.ticker.item5') ?></span><span><?= cms_rich('home.ticker.item6') ?></span><span><?= cms_rich('home.ticker.item7') ?></span><span><?= cms_rich('home.ticker.item8') ?></span></div>
</div>
</div>
<section class="alt" id="ecosystem">
<div class="wrap">
<p class="eyebrow"><?= cms_e('home.one-name-every-lane.eyebrow1') ?></p>
<h2 style="font-size:clamp(28px,3.4vw,40px);margin-top:10px"><?= cms_e('home.one-name-every-lane.heading1') ?></h2>
<div class="eco-grid">
<div class="eco"><div class="tag">Priority</div><h3><?= cms_e('home.one-name-every-lane.eco1-title') ?></h3><p><?= cms_e('home.one-name-every-lane.eco1-text') ?></p><a href="/sell" data-nav="sell" onclick="return _nav(event,'sell')"><?= cms_e('home.one-name-every-lane.eco1-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('home.one-name-every-lane.eco2-title') ?></h3><p><?= cms_e('home.one-name-every-lane.eco2-text') ?></p><a href="/speaking" data-nav="speaking" onclick="return _nav(event,'speaking')"><?= cms_e('home.one-name-every-lane.eco2-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('home.one-name-every-lane.eco3-title') ?></h3><p><?= cms_e('home.one-name-every-lane.eco3-text') ?></p><a href="/mentorship" data-nav="mentorship" onclick="return _nav(event,'mentorship')"><?= cms_e('home.one-name-every-lane.eco3-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('home.one-name-every-lane.eco4-title') ?></h3><p><?= cms_e('home.one-name-every-lane.eco4-text') ?></p><a href="/investing" data-nav="investing" onclick="return _nav(event,'investing')"><?= cms_e('home.one-name-every-lane.eco4-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('home.one-name-every-lane.eco5-title') ?></h3><p><?= cms_e('home.one-name-every-lane.eco5-text') ?></p><a href="/property-management" data-nav="pm" onclick="return _nav(event,'pm')"><?= cms_e('home.one-name-every-lane.eco5-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('home.one-name-every-lane.eco6-title') ?></h3><p><?= cms_e('home.one-name-every-lane.eco6-text') ?></p><a href="/digital-products" data-nav="products" onclick="return _nav(event,'products')"><?= cms_e('home.one-name-every-lane.eco6-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('home.one-name-every-lane.eco7-title') ?></h3><p><?= cms_e('home.one-name-every-lane.eco7-text') ?></p><a href="/collaborations" data-nav="collaborations" onclick="return _nav(event,'collaborations')"><?= cms_e('home.one-name-every-lane.eco7-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('home.one-name-every-lane.eco8-title') ?></h3><p><?= cms_e('home.one-name-every-lane.eco8-text') ?></p><a href="/transportation-logistics" data-nav="transportation" onclick="return _nav(event,'transportation')"><?= cms_e('home.one-name-every-lane.eco8-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('home.one-name-every-lane.eco9-title') ?></h3><p><?= cms_e('home.one-name-every-lane.eco9-text') ?></p><a href="/escaluxe-living" data-nav="living" onclick="return _nav(event,'living')"><?= cms_e('home.one-name-every-lane.eco9-btn') ?></a></div>
</div>
</div>
</section>

<section>
<div class="wrap split">
<div class="ph tall" data-label="Atlanta Luxury Listing Photo"><?= cms_img('home.sellers-first.img-atlanta-luxury-listing', false, 'half') ?></div>
<div>
<p class="eyebrow"><?= cms_e('home.sellers-first.eyebrow1') ?></p>
<h2><?= cms_e('home.sellers-first.heading1') ?></h2>
<div class="rule"></div>
<p><?= cms_rich('home.sellers-first.p1') ?></p>
<a class="btn btn-primary" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')"><?= cms_e('home.sellers-first.btn1') ?></a>
</div>
</div>
</section>
<section class="alt">
<div class="wrap split">
<div>
<p class="eyebrow"><?= cms_e('home.speaking-influence.eyebrow1') ?></p>
<h2><?= cms_e('home.speaking-influence.heading1') ?></h2>
<div class="rule"></div>
<p><?= cms_rich('home.speaking-influence.p1') ?></p>
<a class="btn btn-outline-dark" href="/speaking" data-nav="speaking" onclick="return _nav(event,'speaking')"><?= cms_e('home.speaking-influence.btn1') ?></a>
</div>
<div class="cutout" data-label="Erika — Full-Body Photo · No Background"><?= cms_img('home.speaking-influence.img-erika-full-body-photo', false, 'half') ?>
<svg aria-hidden="true" viewbox="0 0 200 320"><ellipse cx="100" cy="306" fill="rgba(69,50,48,.14)" rx="66" ry="10"></ellipse><circle cx="100" cy="40" fill="#C98D80" r="23"></circle><path d="M100 66c-27 0-39 19-41 45l-6 72c-1 10 4 16 12 16h9l7 108h38l7-108h9c8 0 13-6 12-16l-6-72c-2-26-14-45-41-45z" fill="#C98D80"></path></svg>
</div>
</div>
</section>
<section>
<div class="wrap center-h" style="max-width:760px">
<p class="eyebrow"><?= cms_e('home.meet-erika.eyebrow1') ?></p>
<h2><?= cms_e('home.meet-erika.heading1') ?></h2>
<div class="ph video-frame" data-label="60–90 second video intro (with on-page transcript for AI search)"><?= cms_img('home.meet-erika.img-60-90-second-video-int', false, 'video') ?><div class="play"></div></div>
</div>
</section>
<section class="alt">
<div class="wrap">
<p class="eyebrow"><?= cms_e('home.erika-explains-latest-resour.eyebrow1') ?></p>
<h2 style="font-size:clamp(28px,3.4vw,40px);margin-top:10px"><?= cms_e('home.erika-explains-latest-resour.heading1') ?></h2>
<div class="res-grid">
<div class="res-card" onclick="go('explains')"><div class="ph" data-label="Video Thumbnail"><?= cms_img('home.erika-explains-latest-resour.img-video-thumbnail', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('home.erika-explains-latest-resour.res1-cat') ?></div><h3><?= cms_e('home.erika-explains-latest-resour.res1-title') ?></h3><p><?= cms_e('home.erika-explains-latest-resour.res1-text') ?></p></div></div>
<div class="res-card" onclick="go('resources')"><div class="ph" data-label="Article Image"><?= cms_img('home.erika-explains-latest-resour.img-article-image', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('home.erika-explains-latest-resour.res2-cat') ?></div><h3><?= cms_e('home.erika-explains-latest-resour.res2-title') ?></h3><p><?= cms_e('home.erika-explains-latest-resour.res2-text') ?></p></div></div>
<div class="res-card" onclick="go('loc-gwinnett')"><div class="ph" data-label="Guide Cover"><?= cms_img('home.erika-explains-latest-resour.img-guide-cover', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('home.erika-explains-latest-resour.res3-cat') ?></div><h3><?= cms_e('home.erika-explains-latest-resour.res3-title') ?></h3><p><?= cms_e('home.erika-explains-latest-resour.res3-text') ?></p></div></div>
</div>
</div>
</section>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('home.moments-stages.eyebrow1') ?></p><h2><?= cms_e('home.moments-stages.heading1') ?></h2></div>
<div class="thumb-strip">
<div class="g" onclick="go('gallery')"><div class="ph" data-label="Keynote — Atlanta Summit"><?= cms_img('home.moments-stages.img-keynote-atlanta-summit', false, 'tile') ?></div><div class="cap"><?= cms_e('home.moments-stages.cap1') ?></div></div>
<div class="g" onclick="go('gallery')"><div class="ph" data-label="Industry Panel"><?= cms_img('home.moments-stages.img-industry-panel', false, 'tile') ?></div><div class="cap"><?= cms_e('home.moments-stages.cap2') ?></div></div>
<div class="g" onclick="go('gallery')"><div class="ph" data-label="ADTV — On Set"><?= cms_img('home.moments-stages.img-adtv-on-set', false, 'tile') ?></div><div class="cap"><?= cms_e('home.moments-stages.cap3') ?></div></div>
<div class="g" onclick="go('gallery')"><div class="ph" data-label="Women's Summit"><?= cms_img('home.moments-stages.img-women-s-summit', false, 'tile') ?></div><div class="cap"><?= cms_e('home.moments-stages.cap4') ?></div></div>
<div class="g" onclick="go('gallery')"><div class="ph" data-label="Agent Mastermind"><?= cms_img('home.moments-stages.img-agent-mastermind', false, 'tile') ?></div><div class="cap"><?= cms_e('home.moments-stages.cap5') ?></div></div>
<div class="g" onclick="go('gallery')"><div class="ph" data-label="Community Event"><?= cms_img('home.moments-stages.img-community-event', false, 'tile') ?></div><div class="cap"><?= cms_e('home.moments-stages.cap6') ?></div></div>
</div>
<div style="text-align:center;margin-top:36px"><a class="btn btn-outline-dark" href="/gallery" data-nav="gallery" onclick="return _nav(event,'gallery')"><?= cms_e('home.moments-stages.btn1') ?></a></div>
</div>
</section>
<section class="dark">
<div class="wrap">
<div class="center-h"><p class="eyebrow on-dark"><?= cms_e('home.client-stories.eyebrow1') ?></p><h2><?= cms_e('home.client-stories.heading1') ?></h2></div>
<div class="tst-grid">
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('home.client-stories.tst1-quote') ?></blockquote><div class="who"><?= cms_e('home.client-stories.tst1-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('home.client-stories.tst2-quote') ?></blockquote><div class="who"><?= cms_e('home.client-stories.tst2-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('home.client-stories.tst3-quote') ?></blockquote><div class="who"><?= cms_e('home.client-stories.tst3-who') ?></div></div>
</div>
<div style="text-align:center;margin-top:40px"><a class="btn btn-outline" href="/testimonials" data-nav="testimonials" onclick="return _nav(event,'testimonials')"><?= cms_e('home.client-stories.btn1') ?></a></div>
</div>
</section>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('home.questions.eyebrow1') ?></p><h2><?= cms_e('home.questions.heading1') ?></h2></div>
<div class="faq" style="margin-top:24px">
<details open=""><summary><?= cms_e('home.questions.faq1-q') ?></summary><p><?= cms_rich('home.questions.faq1-a') ?></p></details>
<details><summary><?= cms_e('home.questions.faq2-q') ?></summary><p><?= cms_rich('home.questions.faq2-a') ?></p></details>
<details><summary><?= cms_e('home.questions.faq3-q') ?></summary><p><?= cms_rich('home.questions.faq3-a') ?></p></details>
<details><summary><?= cms_e('home.questions.faq4-q') ?></summary><p><?= cms_rich('home.questions.faq4-a') ?></p></details>
</div>
</div>
</section>
<section class="final">
<div class="wrap">
<h2><?= cms_e('home.thinking-about-selling-your.heading1') ?></h2>
<p><?= cms_rich('home.thinking-about-selling-your.p1') ?></p>
<a class="btn btn-gold" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')"><?= cms_e('home.thinking-about-selling-your.btn1') ?></a>
</div>
</section>
</div>
<!-- ==================== ABOUT /about ==================== -->
<div class="page" id="page-about">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('about.about-erika-page.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('about.about-erika-page.heading1') ?></h1>
<p><?= cms_rich('about.about-erika-page.p1') ?></p>
</div><div class="ph hero-media" data-label="Erika's Story — Featured Video"><?= cms_img('about.about-erika-page.img-erika-s-story-featured', false, 'media') ?><div class="play"></div></div></div>
</header>
<section>
<div class="wrap split" style="align-items:flex-start">
<div class="ph tall" data-label="Erika — Editorial Portrait"><?= cms_img('about.professional-bio.img-erika-editorial-portra', false, 'half') ?></div>
<div>
<p class="eyebrow"><?= cms_e('about.professional-bio.eyebrow1') ?></p>
<h2><?= cms_e('about.professional-bio.heading1') ?></h2>
<div class="rule"></div>
<p><?= cms_rich('about.professional-bio.p1') ?></p>
<p><?= cms_rich('about.professional-bio.p2') ?></p>
<a class="btn btn-primary" href="/contact" data-nav="contact" onclick="return _nav(event,'contact')"><?= cms_e('about.professional-bio.btn1') ?></a>
</div>
</div>
</section>
<section class="alt">
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('about.career-proof.eyebrow1') ?></p><h2><?= cms_e('about.career-proof.heading1') ?></h2></div>
<div class="steps">
<div class="step"><div class="k"><?= cms_e('about.career-proof.step1-k') ?></div><h3><?= cms_e('about.career-proof.step1-title') ?></h3><p><?= cms_e('about.career-proof.step1-text') ?></p></div>
<div class="step"><div class="k"><?= cms_e('about.career-proof.step2-k') ?></div><h3><?= cms_e('about.career-proof.step2-title') ?></h3><p><?= cms_e('about.career-proof.step2-text') ?></p></div>
<div class="step"><div class="k"><?= cms_e('about.career-proof.step3-k') ?></div><h3><?= cms_e('about.career-proof.step3-title') ?></h3><p><?= cms_e('about.career-proof.step3-text') ?></p></div>
<div class="step"><div class="k"><?= cms_e('about.career-proof.step4-k') ?></div><h3><?= cms_e('about.career-proof.step4-title') ?></h3><p><?= cms_e('about.career-proof.step4-text') ?></p></div>
</div>
</div>
</section>
<section>
<div class="wrap split">
<div>
<p class="eyebrow"><?= cms_e('about.entrepreneurship-ecosystem.eyebrow1') ?></p>
<h2><?= cms_e('about.entrepreneurship-ecosystem.heading1') ?></h2>
<div class="rule"></div>
<p><?= cms_rich('about.entrepreneurship-ecosystem.p1') ?></p>
<p><?= cms_rich('about.entrepreneurship-ecosystem.p2') ?></p>
</div>
<div class="ph tall" data-label="Escaluxe Ecosystem / Team Photo"><?= cms_img('about.entrepreneurship-ecosystem.img-escaluxe-ecosystem-tea', false, 'half') ?></div>
</div>
</section>
<section class="dark">
<div class="wrap split">
<div class="ph wide" data-label="Featured Video — Erika's Story"><?= cms_img('about.speaking-media-authority.img-featured-video-erika-s', false, 'half') ?></div>
<div>
<p class="eyebrow on-dark"><?= cms_e('about.speaking-media-authority.eyebrow1') ?></p>
<h2><?= cms_e('about.speaking-media-authority.heading1') ?></h2>
<div class="rule"></div>
<p><?= cms_rich('about.speaking-media-authority.p1') ?></p>
<a class="btn btn-outline" href="/speaking" data-nav="speaking" onclick="return _nav(event,'speaking')"><?= cms_e('about.speaking-media-authority.btn1') ?></a>
</div>
</div>
</section>
<section>
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('about.lifestyle-values.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px)"><?= cms_e('about.lifestyle-values.heading1') ?></h2>
<div class="rule"></div>
<p style="max-width:480px"><?= cms_rich('about.lifestyle-values.p1') ?></p>
</div>
<div>
<p class="eyebrow"><?= cms_e('about.lifestyle-values.eyebrow2') ?></p>
<div class="press-strip" style="margin-top:20px">
<div class="press-logo">ADTV</div><div class="press-logo">Podcast Feature</div><div class="press-logo">Media Outlet</div><div class="press-logo">Press Logo</div>
</div>
</div>
</div>
</section>
<section class="alt">
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('about.questions.eyebrow1') ?></p><h2><?= cms_e('about.questions.heading1') ?></h2></div>
<div class="faq" style="margin-top:24px">
<details open=""><summary><?= cms_e('about.questions.faq1-q') ?></summary><p><?= cms_rich('about.questions.faq1-a') ?></p></details>
<details><summary><?= cms_e('about.questions.faq2-q') ?></summary><p><?= cms_rich('about.questions.faq2-a') ?></p></details>
<details><summary><?= cms_e('about.questions.faq3-q') ?></summary><p><?= cms_rich('about.questions.faq3-a') ?></p></details>
</div>
</div>
</section>
<section class="final">
<div class="wrap">
<h2><?= cms_e('about.ready-to-work-with-erika.heading1') ?></h2>
<p><?= cms_rich('about.ready-to-work-with-erika.p1') ?></p>
<a class="btn btn-gold" href="/contact" data-nav="contact" onclick="return _nav(event,'contact')"><?= cms_e('about.ready-to-work-with-erika.btn1') ?></a>  <a class="btn btn-outline" href="/speaking" data-nav="speaking" onclick="return _nav(event,'speaking')"><?= cms_e('about.ready-to-work-with-erika.btn2') ?></a>
</div>
</section>
</div>
<!-- ==================== SELL /sell ==================== -->
<div class="page" id="page-sell">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('sell.sell-with-erika.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('sell.sell-with-erika.heading1') ?></h1>
<p><?= cms_rich('sell.sell-with-erika.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')"><?= cms_e('sell.sell-with-erika.btn1') ?></a></div>
</div>
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Book a Seller Strategy Call"><input type="hidden" name="_page" value="sell"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card">
<h3><?= cms_e('sell.sell-with-erika.heading2') ?></h3>
<p class="sub"><?= cms_rich('sell.sell-with-erika.p2') ?></p>
<div class="fld-row">
<div class="fld"><label>First name</label><input name="f_first_name" placeholder="First name"/></div>
<div class="fld"><label>Last name</label><input name="f_last_name" placeholder="Last name"/></div>
</div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Phone</label><input type="tel" name="f_phone" placeholder="(___) ___-____"/></div>
<div class="fld"><label>Property address</label><input name="f_property_address" placeholder="Street, City, GA"/></div>
<div class="fld"><label>Ideal selling timeline</label>
<select name="f_ideal_selling_timeline"><option>Now</option><option>30–60 days</option><option>3–6 months</option><option>6+ months</option><option>Not sure yet</option></select></div>
<label class="consent"><input name="f_consent" value="Yes, I agree" type="checkbox"/> I agree to be contacted by Erika Page about selling my home. Your information stays private.</label>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('sell.sell-with-erika.btn2') ?></button>
<p style="font-size:12px;margin-top:14px;text-align:center"><?= cms_rich('sell.sell-with-erika.p3') ?></p>
</div></form></div>
</header>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('sell.why-strategy-matters.eyebrow1') ?></p><h2><?= cms_e('sell.why-strategy-matters.heading1') ?></h2></div>
<p style="max-width:680px;margin:18px auto 0;text-align:center"><?= cms_rich('sell.why-strategy-matters.p1') ?></p>
<div class="steps">
<div class="step"><div class="k"><?= cms_e('sell.why-strategy-matters.step1-k') ?></div><h3><?= cms_e('sell.why-strategy-matters.step1-title') ?></h3><p><?= cms_e('sell.why-strategy-matters.step1-text') ?></p></div>
<div class="step"><div class="k"><?= cms_e('sell.why-strategy-matters.step2-k') ?></div><h3><?= cms_e('sell.why-strategy-matters.step2-title') ?></h3><p><?= cms_e('sell.why-strategy-matters.step2-text') ?></p></div>
<div class="step"><div class="k"><?= cms_e('sell.why-strategy-matters.step3-k') ?></div><h3><?= cms_e('sell.why-strategy-matters.step3-title') ?></h3><p><?= cms_e('sell.why-strategy-matters.step3-text') ?></p></div>
<div class="step"><div class="k"><?= cms_e('sell.why-strategy-matters.step4-k') ?></div><h3><?= cms_e('sell.why-strategy-matters.step4-title') ?></h3><p><?= cms_e('sell.why-strategy-matters.step4-text') ?></p></div>
</div>
</div>
</section>
<section class="dark">
<div class="wrap">
<div class="center-h"><p class="eyebrow on-dark"><?= cms_e('sell.seller-stories.eyebrow1') ?></p><h2><?= cms_e('sell.seller-stories.heading1') ?></h2></div>
<div class="tst-grid">
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('sell.seller-stories.tst1-quote') ?></blockquote><div class="who"><?= cms_e('sell.seller-stories.tst1-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('sell.seller-stories.tst2-quote') ?></blockquote><div class="who"><?= cms_e('sell.seller-stories.tst2-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('sell.seller-stories.tst3-quote') ?></blockquote><div class="who"><?= cms_e('sell.seller-stories.tst3-who') ?></div></div>
</div>
<div class="center-h" style="margin-top:56px"><p class="eyebrow on-dark"><?= cms_e('sell.seller-stories.eyebrow2') ?></p></div>
<div class="res-grid">
<div class="res-card" style="background:#fff"><div class="ph" data-label="Seller Video Story 1"><?= cms_img('sell.seller-stories.img-seller-video-story-1', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('sell.seller-stories.res1-cat') ?></div><h3><?= cms_e('sell.seller-stories.res1-title') ?></h3></div></div>
<div class="res-card" style="background:#fff"><div class="ph" data-label="Seller Video Story 2"><?= cms_img('sell.seller-stories.img-seller-video-story-2', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('sell.seller-stories.res2-cat') ?></div><h3><?= cms_e('sell.seller-stories.res2-title') ?></h3></div></div>
<div class="res-card" style="background:#fff"><div class="ph" data-label="Seller Video Story 3"><?= cms_img('sell.seller-stories.img-seller-video-story-3', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('sell.seller-stories.res3-cat') ?></div><h3><?= cms_e('sell.seller-stories.res3-title') ?></h3></div></div>
</div>
</div>
</section>
<section class="alt">
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('sell.seller-questions.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('sell.seller-questions.heading1') ?></h2>
<div class="faq" style="margin:24px 0 0;max-width:none">
<details open=""><summary><?= cms_e('sell.seller-questions.faq1-q') ?></summary><p><?= cms_rich('sell.seller-questions.faq1-a') ?></p></details>
<details><summary><?= cms_e('sell.seller-questions.faq2-q') ?></summary><p><?= cms_rich('sell.seller-questions.faq2-a') ?></p></details>
<details><summary><?= cms_e('sell.seller-questions.faq3-q') ?></summary><p><?= cms_rich('sell.seller-questions.faq3-a') ?></p></details>
<details><summary><?= cms_e('sell.seller-questions.faq4-q') ?></summary><p><?= cms_rich('sell.seller-questions.faq4-a') ?></p></details>
<details><summary><?= cms_e('sell.seller-questions.faq5-q') ?></summary><p><?= cms_rich('sell.seller-questions.faq5-a') ?></p></details>
</div>
</div>
<div class="ph tall" data-label="Sold Atlanta Listing — Photo or Video"><?= cms_img('sell.seller-questions.img-sold-atlanta-listing-p', false, 'half') ?></div>
</div>
</section>
</div>
<!-- ==================== HOME VALUE /home-value ==================== -->
<div class="page" id="page-homevalue">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('homevalue.atlanta-home-value-strategy.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('homevalue.atlanta-home-value-strategy.heading1') ?></h1>
<p><?= cms_rich('homevalue.atlanta-home-value-strategy.p1') ?></p>
</div><div class="ph hero-media" data-label="Erika Preparing a Home Valuation"><?= cms_img('homevalue.atlanta-home-value-strategy.img-erika-preparing-a-home', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('homevalue.why-online-estimates-are-not.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('homevalue.why-online-estimates-are-not.heading1') ?></h2>
<div class="rule"></div>
<p style="max-width:480px"><?= cms_rich('homevalue.why-online-estimates-are-not.p1') ?></p>
<p class="eyebrow" style="margin-top:34px"><?= cms_e('homevalue.why-online-estimates-are-not.eyebrow2') ?></p>
<ul class="checklist">
<li><?= cms_e('homevalue.why-online-estimates-are-not.check1') ?></li>
<li><?= cms_e('homevalue.why-online-estimates-are-not.check2') ?></li>
<li><?= cms_e('homevalue.why-online-estimates-are-not.check3') ?></li>
<li><?= cms_e('homevalue.why-online-estimates-are-not.check4') ?></li>
<li><?= cms_e('homevalue.why-online-estimates-are-not.check5') ?></li>
</ul>
<div class="info-card" style="margin-top:34px">
<h3><?= cms_e('homevalue.why-online-estimates-are-not.info1-title') ?></h3>
<p><?= cms_rich('homevalue.why-online-estimates-are-not.info1-text') ?></p>
</div>
</div>
<div>
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Request My Atlanta Home Value Strategy"><input type="hidden" name="_page" value="homevalue"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card">
<h3><?= cms_e('homevalue.why-online-estimates-are-not.heading2') ?></h3>
<p class="sub"><?= cms_rich('homevalue.why-online-estimates-are-not.p2') ?></p>
<div class="fld-row">
<div class="fld"><label>First name *</label><input name="f_first_name" placeholder="First name"/></div>
<div class="fld"><label>Last name *</label><input name="f_last_name" placeholder="Last name"/></div>
</div>
<div class="fld"><label>Email *</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Phone *</label><input type="tel" name="f_phone" placeholder="(___) ___-____"/></div>
<div class="fld"><label>Property address *</label><input name="f_property_address" placeholder="Street, City, GA"/></div>
<div class="fld"><label>Ideal selling timeline *</label>
<select name="f_ideal_selling_timeline"><option>Now</option><option>30–60 days</option><option>3–6 months</option><option>6+ months</option><option>Not sure yet</option></select></div>
<div class="fld"><label>Estimated home value (optional)</label><input name="f_estimated_home_value_optional" placeholder="$"/></div>
<div class="fld"><label>What matters most? (optional)</label>
<select name="f_what_matters_most_optional"><option>Price</option><option>Timing</option><option>Privacy</option><option>Repairs</option><option>Relocation</option><option>Divorce / probate</option><option>Investment</option><option>Other</option></select></div>
<label class="consent"><input name="f_consent" value="Yes, I agree" type="checkbox"/> I consent to be contacted by Erika Page by phone, text, or email about my home value strategy. *</label>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('homevalue.why-online-estimates-are-not.btn1') ?></button>
</div></form>
<div class="info-card" style="margin-top:20px;text-align:center">
<h3><?= cms_e('homevalue.why-online-estimates-are-not.info2-title') ?></h3>
<p><?= cms_rich('homevalue.why-online-estimates-are-not.info2-text') ?></p>
<a class="btn btn-outline-dark" onclick="alert('Opens Google Workspace booking (Calendly backup) — mockup')" style="margin-top:14px"><?= cms_e('homevalue.why-online-estimates-are-not.btn2') ?></a>
</div>
</div>
</div>
</section>
<section class="alt">
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('homevalue.trusted-by-1-000-families.eyebrow1') ?></p><h2><?= cms_e('homevalue.trusted-by-1-000-families.heading1') ?></h2></div>
<div class="tst-grid">
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('homevalue.trusted-by-1-000-families.tst1-quote') ?></blockquote><div class="who"><?= cms_e('homevalue.trusted-by-1-000-families.tst1-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('homevalue.trusted-by-1-000-families.tst2-quote') ?></blockquote><div class="who"><?= cms_e('homevalue.trusted-by-1-000-families.tst2-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('homevalue.trusted-by-1-000-families.tst3-quote') ?></blockquote><div class="who"><?= cms_e('homevalue.trusted-by-1-000-families.tst3-who') ?></div></div>
</div>
<div class="faq" style="margin-top:64px">
<details open=""><summary><?= cms_e('homevalue.trusted-by-1-000-families.faq1-q') ?></summary><p><?= cms_rich('homevalue.trusted-by-1-000-families.faq1-a') ?></p></details>
<details><summary><?= cms_e('homevalue.trusted-by-1-000-families.faq2-q') ?></summary><p><?= cms_rich('homevalue.trusted-by-1-000-families.faq2-a') ?></p></details>
<details><summary><?= cms_e('homevalue.trusted-by-1-000-families.faq3-q') ?></summary><p><?= cms_rich('homevalue.trusted-by-1-000-families.faq3-a') ?></p></details>
</div>
</div>
</section>
</div>
<!-- ==================== BUY /buy ==================== -->
<div class="page" id="page-buy">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('buy.buy-with-erika.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('buy.buy-with-erika.heading1') ?></h1>
<p><?= cms_rich('buy.buy-with-erika.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" href="/contact" data-nav="contact" onclick="return _nav(event,'contact')"><?= cms_e('buy.buy-with-erika.btn1') ?></a>  <a class="btn btn-outline" onclick="alert('Links to Axen Realty / Lofty IDX home search (mockup)')"><?= cms_e('buy.buy-with-erika.btn2') ?></a></div>
</div><div class="ph hero-media" data-label="Buyers Touring an Atlanta Home"><?= cms_img('buy.buy-with-erika.img-buyers-touring-an-atla', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap split">
<div>
<p class="eyebrow"><?= cms_e('buy.buying-strategy.eyebrow1') ?></p>
<h2><?= cms_e('buy.buying-strategy.heading1') ?></h2>
<div class="rule"></div>
<p><?= cms_rich('buy.buying-strategy.p1') ?></p>
<ul class="checklist">
<li><?= cms_e('buy.buying-strategy.check1') ?></li>
<li><?= cms_e('buy.buying-strategy.check2') ?></li>
<li><?= cms_e('buy.buying-strategy.check3') ?></li>
</ul>
</div>
<div class="ph tall" data-label="Buyers at New Home — Keys Photo"><?= cms_img('buy.buying-strategy.img-buyers-at-new-home-key', false, 'half') ?></div>
</div>
</section>
<section class="alt">
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('buy.every-kind-of-buyer.eyebrow1') ?></p><h2><?= cms_e('buy.every-kind-of-buyer.heading1') ?></h2></div>
<div class="eco-grid">
<div class="eco"><h3><?= cms_e('buy.every-kind-of-buyer.eco1-title') ?></h3><p><?= cms_e('buy.every-kind-of-buyer.eco1-text') ?></p><a href="/contact" data-nav="contact" onclick="return _nav(event,'contact')"><?= cms_e('buy.every-kind-of-buyer.eco1-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('buy.every-kind-of-buyer.eco2-title') ?></h3><p><?= cms_e('buy.every-kind-of-buyer.eco2-text') ?></p><a href="/contact" data-nav="contact" onclick="return _nav(event,'contact')"><?= cms_e('buy.every-kind-of-buyer.eco2-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('buy.every-kind-of-buyer.eco3-title') ?></h3><p><?= cms_e('buy.every-kind-of-buyer.eco3-text') ?></p><a onclick="alert('Opens Axen / Lofty IDX (mockup)')"><?= cms_e('buy.every-kind-of-buyer.eco3-btn') ?></a></div>
</div>
</div>
</section>
<section class="dark">
<div class="wrap">
<div class="center-h"><p class="eyebrow on-dark"><?= cms_e('buy.buyer-stories.eyebrow1') ?></p><h2><?= cms_e('buy.buyer-stories.heading1') ?></h2></div>
<div class="tst-grid">
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('buy.buyer-stories.tst1-quote') ?></blockquote><div class="who"><?= cms_e('buy.buyer-stories.tst1-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('buy.buyer-stories.tst2-quote') ?></blockquote><div class="who"><?= cms_e('buy.buyer-stories.tst2-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('buy.buyer-stories.tst3-quote') ?></blockquote><div class="who"><?= cms_e('buy.buyer-stories.tst3-who') ?></div></div>
</div>
</div>
</section>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('buy.buyer-questions.eyebrow1') ?></p><h2><?= cms_e('buy.buyer-questions.heading1') ?></h2></div>
<div class="faq" style="margin-top:24px">
<details open=""><summary><?= cms_e('buy.buyer-questions.faq1-q') ?></summary><p><?= cms_rich('buy.buyer-questions.faq1-a') ?></p></details>
<details><summary><?= cms_e('buy.buyer-questions.faq2-q') ?></summary><p><?= cms_rich('buy.buyer-questions.faq2-a') ?></p></details>
<details><summary><?= cms_e('buy.buyer-questions.faq3-q') ?></summary><p><?= cms_rich('buy.buyer-questions.faq3-a') ?></p></details>
</div>
<div style="text-align:center;margin-top:44px"><a class="btn btn-primary" href="/contact" data-nav="contact" onclick="return _nav(event,'contact')"><?= cms_e('buy.buyer-questions.btn1') ?></a></div>
</div>
</section>
</div>
<!-- ==================== SPEAKING /speaking ==================== -->
<div class="page" id="page-speaking">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('speaking.speaking.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('speaking.speaking.heading1') ?></h1>
<p><?= cms_rich('speaking.speaking.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" onclick="document.getElementById('spk-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('speaking.speaking.btn1') ?></a>  <?php if (($onesheet = cms('speaking.speaking.onesheet')) !== ''): ?><a class="btn btn-outline" href="<?= esc($onesheet) ?>" download><?= cms_e('speaking.speaking.btn2') ?></a><?php endif; ?></div>
</div><div class="ph hero-media" data-label="Speaker Reel — Preview"><?= cms_img('speaking.speaking.img-speaker-reel-preview', false, 'media') ?><div class="play"></div></div></div>
</header>
<section>
<div class="wrap split">
<div class="ph tall" data-label="Erika Keynote — Stage Photo"><?= cms_img('speaking.speaker-bio.img-erika-keynote-stage-ph', false, 'half') ?></div>
<div>
<p class="eyebrow"><?= cms_e('speaking.speaker-bio.eyebrow1') ?></p>
<h2><?= cms_e('speaking.speaker-bio.heading1') ?></h2>
<div class="rule"></div>
<p><?= cms_rich('speaking.speaker-bio.p1') ?></p>
<p><?= cms_rich('speaking.speaker-bio.p2') ?></p>
<?php if (($onesheet = cms('speaking.speaking.onesheet')) !== ''): ?><a class="btn btn-outline-dark" href="<?= esc($onesheet) ?>" download><?= cms_e('speaking.speaker-bio.btn1') ?></a><?php endif; ?>
</div>
</div>
</section>
<section class="dark">
<div class="wrap">
<div class="center-h"><p class="eyebrow on-dark"><?= cms_e('speaking.signature-topics.eyebrow1') ?></p><h2><?= cms_e('speaking.signature-topics.heading1') ?></h2></div>
<div class="topics">
<div class="topic"><h3><?= cms_e('speaking.signature-topics.topic1-title') ?></h3><p><?= cms_e('speaking.signature-topics.topic1-text') ?></p><span class="fit"><?= cms_e('speaking.signature-topics.topic1-fit') ?></span></div>
<div class="topic"><h3><?= cms_e('speaking.signature-topics.topic2-title') ?></h3><p><?= cms_e('speaking.signature-topics.topic2-text') ?></p><span class="fit"><?= cms_e('speaking.signature-topics.topic2-fit') ?></span></div>
<div class="topic"><h3><?= cms_e('speaking.signature-topics.topic3-title') ?></h3><p><?= cms_e('speaking.signature-topics.topic3-text') ?></p><span class="fit"><?= cms_e('speaking.signature-topics.topic3-fit') ?></span></div>
<div class="topic"><h3><?= cms_e('speaking.signature-topics.topic4-title') ?></h3><p><?= cms_e('speaking.signature-topics.topic4-text') ?></p><span class="fit"><?= cms_e('speaking.signature-topics.topic4-fit') ?></span></div>
<div class="topic"><h3><?= cms_e('speaking.signature-topics.topic5-title') ?></h3><p><?= cms_e('speaking.signature-topics.topic5-text') ?></p><span class="fit"><?= cms_e('speaking.signature-topics.topic5-fit') ?></span></div>
<div class="topic"><h3><?= cms_e('speaking.signature-topics.topic6-title') ?></h3><p><?= cms_e('speaking.signature-topics.topic6-text') ?></p><span class="fit"><?= cms_e('speaking.signature-topics.topic6-fit') ?></span></div>
</div>
</div>
</section>
<section class="alt">
<div class="wrap split">
<div class="ph wide" data-label="Speaker Reel / Stage Clips"><?= cms_img('speaking.audience-types.img-speaker-reel-stage-cli', false, 'half') ?><div class="play"></div></div>
<div>
<p class="eyebrow"><?= cms_e('speaking.audience-types.eyebrow1') ?></p>
<h2><?= cms_e('speaking.audience-types.heading1') ?></h2>
<div class="rule"></div>
<ul class="checklist">
<li><?= cms_e('speaking.audience-types.check1') ?></li>
<li><?= cms_e('speaking.audience-types.check2') ?></li>
<li><?= cms_e('speaking.audience-types.check3') ?></li>
<li><?= cms_e('speaking.audience-types.check4') ?></li>
<li><?= cms_e('speaking.audience-types.check5') ?></li>
</ul>
</div>
</div>
</section>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('speaking.event-planner-proof.eyebrow1') ?></p><h2><?= cms_e('speaking.event-planner-proof.heading1') ?></h2></div>
<div class="tst-grid">
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('speaking.event-planner-proof.tst1-quote') ?></blockquote><div class="who"><?= cms_e('speaking.event-planner-proof.tst1-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('speaking.event-planner-proof.tst2-quote') ?></blockquote><div class="who"><?= cms_e('speaking.event-planner-proof.tst2-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('speaking.event-planner-proof.tst3-quote') ?></blockquote><div class="who"><?= cms_e('speaking.event-planner-proof.tst3-who') ?></div></div>
</div>
</div>
</section>
<section class="alt" id="spk-form">
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('speaking.speaking-questions.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('speaking.speaking-questions.heading1') ?></h2>
<div class="faq" style="margin:24px 0 0;max-width:none">
<details open=""><summary><?= cms_e('speaking.speaking-questions.faq1-q') ?></summary><p><?= cms_rich('speaking.speaking-questions.faq1-a') ?></p></details>
<details><summary><?= cms_e('speaking.speaking-questions.faq2-q') ?></summary><p><?= cms_rich('speaking.speaking-questions.faq2-a') ?></p></details>
<details><summary><?= cms_e('speaking.speaking-questions.faq3-q') ?></summary><p><?= cms_rich('speaking.speaking-questions.faq3-a') ?></p></details>
<details><summary><?= cms_e('speaking.speaking-questions.faq4-q') ?></summary><p><?= cms_rich('speaking.speaking-questions.faq4-a') ?></p></details>
</div>
</div>
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Speaking Inquiry"><input type="hidden" name="_page" value="speaking"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card">
<h3><?= cms_e('speaking.speaking-questions.heading2') ?></h3>
<p class="sub"><?= cms_rich('speaking.speaking-questions.p1') ?></p>
<div class="fld-row">
<div class="fld"><label>Your name</label><input name="f_your_name" placeholder="Full name"/></div>
<div class="fld"><label>Organization</label><input name="f_organization" placeholder="Company / event"/></div>
</div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Event date &amp; location</label><input name="f_event_date_amp_location" placeholder="Date · City"/></div>
<div class="fld"><label>Audience &amp; format</label><textarea name="f_audience_amp_format" placeholder="Who's in the room? Keynote, panel, or workshop?" rows="3"></textarea></div>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('speaking.speaking-questions.btn1') ?></button>
</div></form>
</div>
</section>
</div>
<!-- ==================== COLLABORATIONS /collaborations ==================== -->
<div class="page" id="page-collaborations">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('collaborations.collaborations.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('collaborations.collaborations.heading1') ?></h1>
<p><?= cms_rich('collaborations.collaborations.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" onclick="document.getElementById('collab-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('collaborations.collaborations.btn1') ?></a></div>
</div><div class="ph hero-media" data-label="Brand Collaboration Shoot"><?= cms_img('collaborations.collaborations.img-brand-collaboration-sh', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('collaborations.collaboration-lanes.eyebrow1') ?></p><h2><?= cms_e('collaborations.collaboration-lanes.heading1') ?></h2></div>
<div class="eco-grid">
<div class="eco"><h3><?= cms_e('collaborations.collaboration-lanes.eco1-title') ?></h3><p><?= cms_e('collaborations.collaboration-lanes.eco1-text') ?></p><a onclick="document.getElementById('collab-form').scrollIntoView()"><?= cms_e('collaborations.collaboration-lanes.eco1-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('collaborations.collaboration-lanes.eco2-title') ?></h3><p><?= cms_e('collaborations.collaboration-lanes.eco2-text') ?></p><a href="/media" data-nav="media" onclick="return _nav(event,'media')"><?= cms_e('collaborations.collaboration-lanes.eco2-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('collaborations.collaboration-lanes.eco3-title') ?></h3><p><?= cms_e('collaborations.collaboration-lanes.eco3-text') ?></p><a href="/speaking" data-nav="speaking" onclick="return _nav(event,'speaking')"><?= cms_e('collaborations.collaboration-lanes.eco3-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('collaborations.collaboration-lanes.eco4-title') ?></h3><p><?= cms_e('collaborations.collaboration-lanes.eco4-text') ?></p><a onclick="document.getElementById('collab-form').scrollIntoView()"><?= cms_e('collaborations.collaboration-lanes.eco4-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('collaborations.collaboration-lanes.eco5-title') ?></h3><p><?= cms_e('collaborations.collaboration-lanes.eco5-text') ?></p><a onclick="document.getElementById('collab-form').scrollIntoView()"><?= cms_e('collaborations.collaboration-lanes.eco5-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('collaborations.collaboration-lanes.eco6-title') ?></h3><p><?= cms_e('collaborations.collaboration-lanes.eco6-text') ?></p><a onclick="document.getElementById('collab-form').scrollIntoView()"><?= cms_e('collaborations.collaboration-lanes.eco6-btn') ?></a></div>
</div>
</div>
</section>
<section class="alt">
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('collaborations.brand-safe-positioning.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('collaborations.brand-safe-positioning.heading1') ?></h2>
<div class="rule"></div>
<p style="max-width:480px"><?= cms_rich('collaborations.brand-safe-positioning.p1') ?></p>
<div class="info-card" style="margin-top:30px">
<h3><?= cms_e('collaborations.brand-safe-positioning.info1-title') ?></h3>
<p><?= cms_rich('collaborations.brand-safe-positioning.info1-text') ?></p>
</div>
</div>
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Collaboration Inquiry"><input type="hidden" name="_page" value="collaborations"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card" id="collab-form">
<h3><?= cms_e('collaborations.brand-safe-positioning.heading2') ?></h3>
<p class="sub"><?= cms_rich('collaborations.brand-safe-positioning.p2') ?></p>
<div class="fld-row">
<div class="fld"><label>Your name</label><input name="f_your_name" placeholder="Full name"/></div>
<div class="fld"><label>Brand / company</label><input name="f_brand_company" placeholder="Brand name"/></div>
</div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Collaboration type</label>
<select name="f_collaboration_type"><option>Brand partnership</option><option>Media / interview</option><option>Panel / event</option><option>Content collaboration</option><option>Luxury / local Atlanta</option><option>Other</option></select></div>
<div class="fld"><label>Tell us about the idea</label><textarea name="f_tell_us_about_the_idea" placeholder="Goals, timing, audience..." rows="3"></textarea></div>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('collaborations.brand-safe-positioning.btn1') ?></button>
</div></form>
</div>
</section>
</div>
<!-- ==================== LIFESTYLE /lifestyle ==================== -->
<div class="page" id="page-lifestyle">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('lifestyle.lifestyle-magazine.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('lifestyle.lifestyle-magazine.heading1') ?></h1>
<p><?= cms_rich('lifestyle.lifestyle-magazine.p1') ?></p>
<div class="rev-filter">
<span class="chip on">All</span><span class="chip">Atlanta Living</span><span class="chip">Real Estate</span><span class="chip">Wealth &amp; Investing</span><span class="chip">Luxury Lifestyle</span><span class="chip">Travel &amp; Hospitality</span><span class="chip">Agent Growth</span><span class="chip">Leadership &amp; Speaking</span>
</div>
</div><div class="ph hero-media" data-label="Atlanta Lifestyle Editorial"><?= cms_img('lifestyle.lifestyle-magazine.img-atlanta-lifestyle-edit', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap">
<p class="eyebrow"><?= cms_e('lifestyle.featured-stories.eyebrow1') ?></p>
<div class="res-grid">
<div class="res-card"><div class="ph" data-label="Atlanta Skyline Feature"><?= cms_img('lifestyle.featured-stories.img-atlanta-skyline-featur', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('lifestyle.featured-stories.res1-cat') ?></div><h3><?= cms_e('lifestyle.featured-stories.res1-title') ?></h3><p><?= cms_e('lifestyle.featured-stories.res1-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Luxury Interior Feature"><?= cms_img('lifestyle.featured-stories.img-luxury-interior-featur', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('lifestyle.featured-stories.res2-cat') ?></div><h3><?= cms_e('lifestyle.featured-stories.res2-title') ?></h3><p><?= cms_e('lifestyle.featured-stories.res2-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Travel Feature"><?= cms_img('lifestyle.featured-stories.img-travel-feature', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('lifestyle.featured-stories.res3-cat') ?></div><h3><?= cms_e('lifestyle.featured-stories.res3-title') ?></h3><p><?= cms_e('lifestyle.featured-stories.res3-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Wealth Feature"><?= cms_img('lifestyle.featured-stories.img-wealth-feature', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('lifestyle.featured-stories.res4-cat') ?></div><h3><?= cms_e('lifestyle.featured-stories.res4-title') ?></h3><p><?= cms_e('lifestyle.featured-stories.res4-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Food Feature"><?= cms_img('lifestyle.featured-stories.img-food-feature', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('lifestyle.featured-stories.res5-cat') ?></div><h3><?= cms_e('lifestyle.featured-stories.res5-title') ?></h3><p><?= cms_e('lifestyle.featured-stories.res5-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Leadership Feature"><?= cms_img('lifestyle.featured-stories.img-leadership-feature', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('lifestyle.featured-stories.res6-cat') ?></div><h3><?= cms_e('lifestyle.featured-stories.res6-title') ?></h3><p><?= cms_e('lifestyle.featured-stories.res6-text') ?></p></div></div>
</div>
</div>
</section>
<section class="final">
<div class="wrap">
<h2><?= cms_e('lifestyle.create-something-with-erika.heading1') ?></h2>
<p><?= cms_rich('lifestyle.create-something-with-erika.p1') ?></p>
<a class="btn btn-gold" href="/collaborations" data-nav="collaborations" onclick="return _nav(event,'collaborations')"><?= cms_e('lifestyle.create-something-with-erika.btn1') ?></a>  <a class="btn btn-outline" href="/erika-explains" data-nav="explains" onclick="return _nav(event,'explains')"><?= cms_e('lifestyle.create-something-with-erika.btn2') ?></a>
</div>
</section>
</div>
<!-- ==================== MEDIA /media ==================== -->
<div class="page" id="page-media">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('media.media-press.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('media.media-press.heading1') ?></h1>
<p><?= cms_rich('media.media-press.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" href="/speaking" data-nav="speaking" onclick="return _nav(event,'speaking')"><?= cms_e('media.media-press.btn1') ?></a>  <a class="btn btn-outline" href="/contact" data-nav="contact" onclick="return _nav(event,'contact')"><?= cms_e('media.media-press.btn2') ?></a></div>
</div><div class="ph hero-media" data-label="ADTV Feature Clip"><?= cms_img('media.media-press.img-adtv-feature-clip', false, 'media') ?><div class="play"></div></div></div>
</header>
<section>
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('media.media-bio.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('media.media-bio.heading1') ?></h2>
<div class="rule"></div>
<p style="max-width:500px"><?= cms_rich('media.media-bio.p1') ?></p>
<p style="font-size:13px;color:var(--merlot)"><?= cms_rich('media.media-bio.p2') ?></p>
</div>
<div>
<p class="eyebrow"><?= cms_e('media.media-bio.eyebrow2') ?></p>
<div class="press-strip" style="margin-top:20px">
<div class="press-logo">Headshot Pack</div><div class="press-logo">Logo Files</div><div class="press-logo">One-Sheet PDF</div>
</div>
<a class="btn btn-outline-dark" onclick="alert('Downloads media kit (mockup)')" style="margin-top:22px"><?= cms_e('media.media-bio.btn1') ?></a>
</div>
</div>
</section>
<section class="dark">
<div class="wrap">
<div class="center-h"><p class="eyebrow on-dark"><?= cms_e('media.on-screen-on-air.eyebrow1') ?></p><h2><?= cms_e('media.on-screen-on-air.heading1') ?></h2></div>
<div class="res-grid">
<div class="res-card" style="background:#fff"><div class="ph" data-label="ADTV Clip"><?= cms_img('media.on-screen-on-air.img-adtv-clip', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('media.on-screen-on-air.res1-cat') ?></div><h3><?= cms_e('media.on-screen-on-air.res1-title') ?></h3></div></div>
<div class="res-card" style="background:#fff"><div class="ph" data-label="Podcast Episode"><?= cms_img('media.on-screen-on-air.img-podcast-episode', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('media.on-screen-on-air.res2-cat') ?></div><h3><?= cms_e('media.on-screen-on-air.res2-title') ?></h3></div></div>
<div class="res-card" style="background:#fff"><div class="ph" data-label="Press Feature"><?= cms_img('media.on-screen-on-air.img-press-feature', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('media.on-screen-on-air.res3-cat') ?></div><h3><?= cms_e('media.on-screen-on-air.res3-title') ?></h3></div></div>
</div>
<p style="text-align:center;margin-top:36px;font-size:14px"><?= cms_rich('media.on-screen-on-air.p1') ?></p>
</div>
</section>
<section class="alt">
<div class="wrap" style="max-width:680px">
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Media & Interview Requests"><input type="hidden" name="_page" value="media"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card">
<h3><?= cms_e('media.section-4.heading1') ?></h3>
<p class="sub"><?= cms_rich('media.section-4.p1') ?></p>
<div class="fld-row">
<div class="fld"><label>Your name</label><input name="f_your_name" placeholder="Full name"/></div>
<div class="fld"><label>Outlet / show</label><input name="f_outlet_show" placeholder="Publication or podcast"/></div>
</div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Topic &amp; deadline</label><textarea name="f_topic_amp_deadline" placeholder="What's the story? When do you need Erika?" rows="3"></textarea></div>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('media.section-4.btn1') ?></button>
</div></form>
</div>
</section>
</div>
<!-- ==================== TESTIMONIALS /testimonials ==================== -->
<div class="page" id="page-testimonials">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('testimonials.google-zillow-video-testimon.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('testimonials.google-zillow-video-testimon.heading1') ?></h1>
<p><?= cms_rich('testimonials.google-zillow-video-testimon.p1') ?></p>
<div class="rev-filter">
<span class="chip on">All</span><span class="chip">Sellers</span><span class="chip">Buyers</span><span class="chip">Investors</span><span class="chip">Mentorship</span><span class="chip">Luxury</span>
</div>
</div><div class="ph hero-media" data-label="Client Story Highlight — Video"><?= cms_img('testimonials.google-zillow-video-testimon.img-client-story-highlight', false, 'media') ?><div class="play"></div></div></div>
</header>
<section>
<div class="wrap">
<p class="eyebrow"><?= cms_e('testimonials.video-testimonials.eyebrow1') ?></p>
<div class="res-grid">
<div class="res-card"><div class="ph" data-label="Video Testimonial 1"><?= cms_img('testimonials.video-testimonials.img-video-testimonial-1', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('testimonials.video-testimonials.res1-cat') ?></div><h3><?= cms_e('testimonials.video-testimonials.res1-title') ?></h3><p><?= cms_e('testimonials.video-testimonials.res1-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Video Testimonial 2"><?= cms_img('testimonials.video-testimonials.img-video-testimonial-2', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('testimonials.video-testimonials.res2-cat') ?></div><h3><?= cms_e('testimonials.video-testimonials.res2-title') ?></h3><p><?= cms_e('testimonials.video-testimonials.res2-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Video Testimonial 3"><?= cms_img('testimonials.video-testimonials.img-video-testimonial-3', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('testimonials.video-testimonials.res3-cat') ?></div><h3><?= cms_e('testimonials.video-testimonials.res3-title') ?></h3><p><?= cms_e('testimonials.video-testimonials.res3-text') ?></p></div></div>
</div>
</div>
</section>
<section class="alt">
<div class="wrap">
<p class="eyebrow"><?= cms_e('testimonials.written-testimonials.eyebrow1') ?></p>
<div class="tst-grid">
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('testimonials.written-testimonials.tst1-quote') ?></blockquote><div class="who"><?= cms_e('testimonials.written-testimonials.tst1-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('testimonials.written-testimonials.tst2-quote') ?></blockquote><div class="who"><?= cms_e('testimonials.written-testimonials.tst2-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('testimonials.written-testimonials.tst3-quote') ?></blockquote><div class="who"><?= cms_e('testimonials.written-testimonials.tst3-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('testimonials.written-testimonials.tst4-quote') ?></blockquote><div class="who"><?= cms_e('testimonials.written-testimonials.tst4-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('testimonials.written-testimonials.tst5-quote') ?></blockquote><div class="who"><?= cms_e('testimonials.written-testimonials.tst5-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('testimonials.written-testimonials.tst6-quote') ?></blockquote><div class="who"><?= cms_e('testimonials.written-testimonials.tst6-who') ?></div></div>
</div>
<p style="text-align:center;margin-top:36px;font-size:14px"><?= cms_rich('testimonials.written-testimonials.p1') ?></p>
</div>
</section>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('testimonials.testimonial-questions.eyebrow1') ?></p><h2><?= cms_e('testimonials.testimonial-questions.heading1') ?></h2></div>
<div class="faq" style="margin-top:24px">
<details open=""><summary><?= cms_e('testimonials.testimonial-questions.faq1-q') ?></summary><p><?= cms_rich('testimonials.testimonial-questions.faq1-a') ?></p></details>
<details><summary><?= cms_e('testimonials.testimonial-questions.faq2-q') ?></summary><p><?= cms_rich('testimonials.testimonial-questions.faq2-a') ?></p></details>
<details><summary><?= cms_e('testimonials.testimonial-questions.faq3-q') ?></summary><p><?= cms_rich('testimonials.testimonial-questions.faq3-a') ?></p></details>
</div>
<div style="text-align:center;margin-top:44px"><a class="btn btn-primary" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')"><?= cms_e('testimonials.testimonial-questions.btn1') ?></a></div>
</div>
</section>
</div>
<!-- ==================== RESOURCES /resources ==================== -->
<div class="page" id="page-resources">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('resources.resources.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('resources.resources.heading1') ?></h1>
<p><?= cms_rich('resources.resources.p1') ?></p>
<div class="rev-filter">
<span class="chip on">All</span><span class="chip">Seller</span><span class="chip">Buyer</span><span class="chip">Agent Tools</span><span class="chip">Wealth / Investing</span><span class="chip">Lifestyle</span><span class="chip">Speaking / Leadership</span>
</div>
</div><div class="ph hero-media" data-label="Guides &amp; Resources Flat-Lay"><?= cms_img('resources.resources.img-guides-resources-flat', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap">
<p class="eyebrow"><?= cms_e('resources.featured-guides.eyebrow1') ?></p>
<div class="res-grid">
<div class="res-card"><div class="ph" data-label="Seller Guide Cover"><?= cms_img('resources.featured-guides.img-seller-guide-cover', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('resources.featured-guides.res1-cat') ?></div><h3><?= cms_e('resources.featured-guides.res1-title') ?></h3><p><?= cms_e('resources.featured-guides.res1-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Buyer Guide Cover"><?= cms_img('resources.featured-guides.img-buyer-guide-cover', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('resources.featured-guides.res2-cat') ?></div><h3><?= cms_e('resources.featured-guides.res2-title') ?></h3><p><?= cms_e('resources.featured-guides.res2-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Investing Guide Cover"><?= cms_img('resources.featured-guides.img-investing-guide-cover', false, 'card') ?></div><div class="body"><div class="cat"><?= cms_e('resources.featured-guides.res3-cat') ?></div><h3><?= cms_e('resources.featured-guides.res3-title') ?></h3><p><?= cms_e('resources.featured-guides.res3-text') ?></p></div></div>
</div>
<p class="eyebrow" style="margin-top:56px"><?= cms_e('resources.featured-guides.eyebrow2') ?></p>
<div class="res-grid">
<div class="res-card" onclick="go('explains')"><div class="ph" data-label="Erika Explains Ep. 12"><?= cms_img('resources.featured-guides.img-erika-explains-ep-12', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('resources.featured-guides.res4-cat') ?></div><h3><?= cms_e('resources.featured-guides.res4-title') ?></h3></div></div>
<div class="res-card" onclick="go('explains')"><div class="ph" data-label="Erika Explains Ep. 11"><?= cms_img('resources.featured-guides.img-erika-explains-ep-11', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('resources.featured-guides.res5-cat') ?></div><h3><?= cms_e('resources.featured-guides.res5-title') ?></h3></div></div>
<div class="res-card" onclick="go('explains')"><div class="ph" data-label="Erika Explains Ep. 10"><?= cms_img('resources.featured-guides.img-erika-explains-ep-10', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('resources.featured-guides.res6-cat') ?></div><h3><?= cms_e('resources.featured-guides.res6-title') ?></h3></div></div>
</div>
</div>
</section>
<section class="alt">
<div class="wrap">
<div class="two-col" style="align-items:center">
<div>
<p class="eyebrow"><?= cms_e('resources.digital-products.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('resources.digital-products.heading1') ?></h2>
<p style="margin-top:14px;max-width:460px"><?= cms_rich('resources.digital-products.p1') ?></p>
<a class="btn btn-primary" href="/digital-products" data-nav="products" onclick="return _nav(event,'products')" style="margin-top:10px"><?= cms_e('resources.digital-products.btn1') ?></a>
</div>
<div class="ph wide" data-label="Digital Products Preview"><?= cms_img('resources.digital-products.img-digital-products-previ', false, 'half') ?></div>
</div>
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Get the Atlanta Seller's Pricing Checklist"><input type="hidden" name="_page" value="resources"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="lead-mag">
<div>
<p class="eyebrow" style="color:#fff"><?= cms_e('resources.digital-products.eyebrow2') ?></p>
<h3><?= cms_e('resources.digital-products.heading2') ?></h3>
<p><?= cms_rich('resources.digital-products.p2') ?></p>
</div>
<div>
<div class="fld"><input type="email" name="f_your_email_address" placeholder="Your email address"/></div>
<button type="submit" class="btn btn-gold" style="width:100%;text-align:center"><?= cms_e('resources.digital-products.btn2') ?></button>
</div>
</div></form>
</div>
</section>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('resources.resource-questions.eyebrow1') ?></p><h2><?= cms_e('resources.resource-questions.heading1') ?></h2></div>
<div class="faq" style="margin-top:24px">
<details open=""><summary><?= cms_e('resources.resource-questions.faq1-q') ?></summary><p><?= cms_rich('resources.resource-questions.faq1-a') ?></p></details>
<details><summary><?= cms_e('resources.resource-questions.faq2-q') ?></summary><p><?= cms_rich('resources.resource-questions.faq2-a') ?></p></details>
<details><summary><?= cms_e('resources.resource-questions.faq3-q') ?></summary><p><?= cms_rich('resources.resource-questions.faq3-a') ?></p></details>
</div>
</div>
</section>
</div>
<!-- ==================== DIGITAL PRODUCTS /resources/digital-products ==================== -->
<div class="page" id="page-products">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('products.resources-digital-products.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('products.resources-digital-products.heading1') ?></h1>
<p><?= cms_rich('products.resources-digital-products.p1') ?></p>
<div class="rev-filter">
<span class="chip on">All</span><span class="chip">Home Seller</span><span class="chip">Buyer &amp; Relocation</span><span class="chip">Agent Tools</span><span class="chip">Wealth / Investing</span><span class="chip">Mindset / Reinvention</span><span class="chip">Workshops</span>
</div>
</div><div class="ph hero-media" data-label="Digital Product Mockups"><?= cms_img('products.resources-digital-products.img-digital-product-mockup', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap">
<div class="prod-grid">
<div class="prod"><div class="ph" data-label="Product Cover"><?= cms_img('products.section-2.img-product-cover', false, 'card') ?></div><div class="body"><div class="aud"><?= cms_e('products.section-2.prod1-aud') ?></div><h3><?= cms_e('products.section-2.prod1-title') ?></h3><div class="prob"><?= cms_e('products.section-2.prod1-prob') ?></div><p><?= cms_e('products.section-2.prod1-text') ?></p><a class="btn btn-gold" onclick="alert('Opens Stan product · fires stan_store_click + UTM (mockup)')"><?= cms_e('products.section-2.prod1-btn') ?></a></div></div>
<div class="prod"><div class="ph" data-label="Product Cover"><?= cms_img('products.section-2.img-product-cover-2', false, 'card') ?></div><div class="body"><div class="aud"><?= cms_e('products.section-2.prod2-aud') ?></div><h3><?= cms_e('products.section-2.prod2-title') ?></h3><div class="prob"><?= cms_e('products.section-2.prod2-prob') ?></div><p><?= cms_e('products.section-2.prod2-text') ?></p><a class="btn btn-gold" onclick="alert('Opens Stan product (mockup)')"><?= cms_e('products.section-2.prod2-btn') ?></a></div></div>
<div class="prod"><div class="ph" data-label="Product Cover"><?= cms_img('products.section-2.img-product-cover-3', false, 'card') ?></div><div class="body"><div class="aud"><?= cms_e('products.section-2.prod3-aud') ?></div><h3><?= cms_e('products.section-2.prod3-title') ?></h3><div class="prob"><?= cms_e('products.section-2.prod3-prob') ?></div><p><?= cms_e('products.section-2.prod3-text') ?></p><a class="btn btn-gold" onclick="alert('Opens Stan product (mockup)')"><?= cms_e('products.section-2.prod3-btn') ?></a></div></div>
<div class="prod"><div class="ph" data-label="Product Cover"><?= cms_img('products.section-2.img-product-cover-4', false, 'card') ?></div><div class="body"><div class="aud"><?= cms_e('products.section-2.prod4-aud') ?></div><h3><?= cms_e('products.section-2.prod4-title') ?></h3><div class="prob"><?= cms_e('products.section-2.prod4-prob') ?></div><p><?= cms_e('products.section-2.prod4-text') ?></p><a class="btn btn-gold" onclick="alert('Opens Stan product (mockup)')"><?= cms_e('products.section-2.prod4-btn') ?></a></div></div>
<div class="prod"><div class="ph" data-label="Product Cover"><?= cms_img('products.section-2.img-product-cover-5', false, 'card') ?></div><div class="body"><div class="aud"><?= cms_e('products.section-2.prod5-aud') ?></div><h3><?= cms_e('products.section-2.prod5-title') ?></h3><div class="prob"><?= cms_e('products.section-2.prod5-prob') ?></div><p><?= cms_e('products.section-2.prod5-text') ?></p><a class="btn btn-gold" onclick="alert('Opens Stan product (mockup)')"><?= cms_e('products.section-2.prod5-btn') ?></a></div></div>
<div class="prod"><div class="ph" data-label="Product Cover"><?= cms_img('products.section-2.img-product-cover-6', false, 'card') ?></div><div class="body"><div class="aud"><?= cms_e('products.section-2.prod6-aud') ?></div><h3><?= cms_e('products.section-2.prod6-title') ?></h3><div class="prob"><?= cms_e('products.section-2.prod6-prob') ?></div><p><?= cms_e('products.section-2.prod6-text') ?></p><a class="btn btn-gold" onclick="alert('Opens Stan Store (mockup)')"><?= cms_e('products.section-2.prod6-btn') ?></a></div></div>
</div>
<p style="text-align:center;margin-top:40px;font-size:14px"><?= cms_rich('products.section-2.p1') ?></p>
</div>
</section>
<section class="alt">
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('products.product-questions.eyebrow1') ?></p><h2><?= cms_e('products.product-questions.heading1') ?></h2></div>
<div class="faq" style="margin-top:24px">
<details open=""><summary><?= cms_e('products.product-questions.faq1-q') ?></summary><p><?= cms_rich('products.product-questions.faq1-a') ?></p></details>
<details><summary><?= cms_e('products.product-questions.faq2-q') ?></summary><p><?= cms_rich('products.product-questions.faq2-a') ?></p></details>
<details><summary><?= cms_e('products.product-questions.faq3-q') ?></summary><p><?= cms_rich('products.product-questions.faq3-a') ?></p></details>
</div>
<div style="text-align:center;margin-top:40px"><p class="eyebrow"><?= cms_e('products.product-questions.eyebrow2') ?></p><div style="margin-top:16px"><a class="btn btn-outline-dark" href="/resources" data-nav="resources" onclick="return _nav(event,'resources')"><?= cms_e('products.product-questions.btn1') ?></a>  <a class="btn btn-outline-dark" href="/erika-explains" data-nav="explains" onclick="return _nav(event,'explains')"><?= cms_e('products.product-questions.btn2') ?></a></div></div>
</div>
</section>
</div>
<!-- ==================== ERIKA EXPLAINS /resources/erika-explains ==================== -->
<div class="page" id="page-explains">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('explains.resources-erika-explains.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('explains.resources-erika-explains.heading1') ?></h1>
<p><?= cms_rich('explains.resources-erika-explains.p1') ?></p>
</div><div class="ph hero-media" data-label="Erika Explains — Series Trailer"><?= cms_img('explains.resources-erika-explains.img-erika-explains-series', false, 'media') ?><div class="play"></div></div></div>
</header>
<section>
<div class="wrap">
<div class="center-h" style="max-width:820px;margin:0 auto">
<p class="eyebrow"><?= cms_e('explains.latest-episode.eyebrow1') ?></p>
<div class="ph video-frame" data-label="Ep. 12 — Why Online Estimates Miss the Mark (full transcript below)"><?= cms_img('explains.latest-episode.img-ep-12-why-online-estim', false, 'video') ?><div class="play"></div></div>
<p style="margin-top:20px;max-width:640px;margin-left:auto;margin-right:auto"><?= cms_rich('explains.latest-episode.p1') ?></p>
</div>
<p class="eyebrow" style="margin-top:64px"><?= cms_e('explains.latest-episode.eyebrow2') ?></p>
<div class="res-grid">
<div class="res-card"><div class="ph" data-label="Ep. 11"><?= cms_img('explains.latest-episode.img-ep-11', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('explains.latest-episode.res1-cat') ?></div><h3><?= cms_e('explains.latest-episode.res1-title') ?></h3><p><?= cms_e('explains.latest-episode.res1-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Ep. 10"><?= cms_img('explains.latest-episode.img-ep-10', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('explains.latest-episode.res2-cat') ?></div><h3><?= cms_e('explains.latest-episode.res2-title') ?></h3><p><?= cms_e('explains.latest-episode.res2-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Ep. 9"><?= cms_img('explains.latest-episode.img-ep-9', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('explains.latest-episode.res3-cat') ?></div><h3><?= cms_e('explains.latest-episode.res3-title') ?></h3><p><?= cms_e('explains.latest-episode.res3-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Ep. 8"><?= cms_img('explains.latest-episode.img-ep-8', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('explains.latest-episode.res4-cat') ?></div><h3><?= cms_e('explains.latest-episode.res4-title') ?></h3><p><?= cms_e('explains.latest-episode.res4-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Ep. 7"><?= cms_img('explains.latest-episode.img-ep-7', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('explains.latest-episode.res5-cat') ?></div><h3><?= cms_e('explains.latest-episode.res5-title') ?></h3><p><?= cms_e('explains.latest-episode.res5-text') ?></p></div></div>
<div class="res-card"><div class="ph" data-label="Ep. 6"><?= cms_img('explains.latest-episode.img-ep-6', false, 'card') ?><div class="play" style="width:52px;height:52px"></div></div><div class="body"><div class="cat"><?= cms_e('explains.latest-episode.res6-cat') ?></div><h3><?= cms_e('explains.latest-episode.res6-title') ?></h3><p><?= cms_e('explains.latest-episode.res6-text') ?></p></div></div>
</div>
</div>
</section>
<section class="final">
<div class="wrap">
<h2><?= cms_e('explains.have-a-question-erika-should.heading1') ?></h2>
<p><?= cms_rich('explains.have-a-question-erika-should.p1') ?></p>
<a class="btn btn-gold" href="/contact" data-nav="contact" onclick="return _nav(event,'contact')"><?= cms_e('explains.have-a-question-erika-should.btn1') ?></a>  <a class="btn btn-outline" onclick="alert('Links to YouTube channel (mockup)')"><?= cms_e('explains.have-a-question-erika-should.btn2') ?></a>
</div>
</section>
</div>
<!-- ==================== MENTORSHIP /mentorship ==================== -->
<div class="page" id="page-mentorship">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('mentorship.mentorship.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('mentorship.mentorship.heading1') ?></h1>
<p><?= cms_rich('mentorship.mentorship.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" onclick="document.getElementById('ment-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('mentorship.mentorship.btn1') ?></a></div>
</div><div class="ph hero-media" data-label="Erika Coaching Agents — Photo"><?= cms_img('mentorship.mentorship.img-erika-coaching-agents', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap split">
<div class="ph tall" data-label="Erika Coaching / Mastermind Photo"><?= cms_img('mentorship.who-it-s-for.img-erika-coaching-masterm', false, 'half') ?></div>
<div>
<p class="eyebrow"><?= cms_e('mentorship.who-it-s-for.eyebrow1') ?></p>
<h2><?= cms_e('mentorship.who-it-s-for.heading1') ?></h2>
<div class="rule"></div>
<ul class="checklist">
<li><?= cms_e('mentorship.who-it-s-for.check1') ?></li>
<li><?= cms_e('mentorship.who-it-s-for.check2') ?></li>
<li><?= cms_e('mentorship.who-it-s-for.check3') ?></li>
<li><?= cms_e('mentorship.who-it-s-for.check4') ?></li>
</ul>
</div>
</div>
</section>
<section class="alt">
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('mentorship.what-erika-teaches.eyebrow1') ?></p><h2><?= cms_e('mentorship.what-erika-teaches.heading1') ?></h2></div>
<div class="steps">
<div class="step"><div class="k"><?= cms_e('mentorship.what-erika-teaches.step1-k') ?></div><h3><?= cms_e('mentorship.what-erika-teaches.step1-title') ?></h3><p><?= cms_e('mentorship.what-erika-teaches.step1-text') ?></p></div>
<div class="step"><div class="k"><?= cms_e('mentorship.what-erika-teaches.step2-k') ?></div><h3><?= cms_e('mentorship.what-erika-teaches.step2-title') ?></h3><p><?= cms_e('mentorship.what-erika-teaches.step2-text') ?></p></div>
<div class="step"><div class="k"><?= cms_e('mentorship.what-erika-teaches.step3-k') ?></div><h3><?= cms_e('mentorship.what-erika-teaches.step3-title') ?></h3><p><?= cms_e('mentorship.what-erika-teaches.step3-text') ?></p></div>
<div class="step"><div class="k"><?= cms_e('mentorship.what-erika-teaches.step4-k') ?></div><h3><?= cms_e('mentorship.what-erika-teaches.step4-title') ?></h3><p><?= cms_e('mentorship.what-erika-teaches.step4-text') ?></p></div>
</div>
</div>
</section>
<section class="dark">
<div class="wrap split">
<div>
<p class="eyebrow on-dark"><?= cms_e('mentorship.agent-results.eyebrow1') ?></p>
<h2><?= cms_e('mentorship.agent-results.heading1') ?></h2>
<div class="rule"></div>
<div class="tst" style="margin-bottom:20px"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('mentorship.agent-results.tst1-quote') ?></blockquote><div class="who"><?= cms_e('mentorship.agent-results.tst1-who') ?></div></div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('mentorship.agent-results.tst2-quote') ?></blockquote><div class="who"><?= cms_e('mentorship.agent-results.tst2-who') ?></div></div>
</div>
<div class="ph tall" data-label="Mentorship Video — Program Overview"><?= cms_img('mentorship.agent-results.img-mentorship-video-progr', false, 'half') ?><div class="play"></div></div>
</div>
</section>
<section class="alt" id="ment-form">
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('mentorship.related-resources.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('mentorship.related-resources.heading1') ?></h2>
<p style="margin-top:14px;max-width:440px"><?= cms_rich('mentorship.related-resources.p1') ?></p>
<div style="margin-top:20px"><a class="btn btn-outline-dark" href="/digital-products" data-nav="products" onclick="return _nav(event,'products')"><?= cms_e('mentorship.related-resources.btn1') ?></a>  <a class="btn btn-outline-dark" href="/erika-explains" data-nav="explains" onclick="return _nav(event,'explains')"><?= cms_e('mentorship.related-resources.btn2') ?></a></div>
</div>
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Mentorship Inquiry"><input type="hidden" name="_page" value="mentorship"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card">
<h3><?= cms_e('mentorship.related-resources.heading2') ?></h3>
<p class="sub"><?= cms_rich('mentorship.related-resources.p2') ?></p>
<div class="fld-row">
<div class="fld"><label>Your name</label><input name="f_your_name" placeholder="Full name"/></div>
<div class="fld"><label>Brokerage</label><input name="f_brokerage" placeholder="Brokerage / team"/></div>
</div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Years in real estate</label><select name="f_years_in_real_estate"><option>New / pre-license</option><option>0–2 years</option><option>3–7 years</option><option>8+ years</option></select></div>
<div class="fld"><label>Biggest challenge right now</label><textarea name="f_biggest_challenge_right_now" placeholder="Listings, leads, systems, brand..." rows="3"></textarea></div>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('mentorship.related-resources.btn3') ?></button>
</div></form>
</div>
</section>
</div>
<!-- ==================== INVESTING / C&A /investing-capital-acquisitions ==================== -->
<div class="page" id="page-investing">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('investing.investing-capital-acquisitio.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('investing.investing-capital-acquisitio.heading1') ?></h1>
<p><?= cms_rich('investing.investing-capital-acquisitio.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" onclick="document.getElementById('inv-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('investing.investing-capital-acquisitio.btn1') ?></a></div>
</div><div class="ph hero-media" data-label="Atlanta Investment Property"><?= cms_img('investing.investing-capital-acquisitio.img-atlanta-investment-pro', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('investing.who-should-inquire.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('investing.who-should-inquire.heading1') ?></h2>
<div class="rule"></div>
<ul class="checklist">
<li><?= cms_e('investing.who-should-inquire.check1') ?></li>
<li><?= cms_e('investing.who-should-inquire.check2') ?></li>
<li><?= cms_e('investing.who-should-inquire.check3') ?></li>
<li><?= cms_e('investing.who-should-inquire.check4') ?></li>
</ul>
<p style="margin-top:26px;font-size:14px"><?= cms_rich('investing.who-should-inquire.p1') ?></p>
</div>
<div>
<p class="eyebrow"><?= cms_e('investing.who-should-inquire.eyebrow2') ?></p>
<div class="eco-grid" style="grid-template-columns:1fr;margin-top:20px">
<div class="eco"><h3><?= cms_e('investing.who-should-inquire.eco1-title') ?></h3><p><?= cms_e('investing.who-should-inquire.eco1-text') ?></p><a onclick="document.getElementById('inv-form').scrollIntoView()"><?= cms_e('investing.who-should-inquire.eco1-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('investing.who-should-inquire.eco2-title') ?></h3><p><?= cms_e('investing.who-should-inquire.eco2-text') ?></p><a onclick="document.getElementById('inv-form').scrollIntoView()"><?= cms_e('investing.who-should-inquire.eco2-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('investing.who-should-inquire.eco3-title') ?></h3><p><?= cms_e('investing.who-should-inquire.eco3-text') ?></p><a onclick="document.getElementById('inv-form').scrollIntoView()"><?= cms_e('investing.who-should-inquire.eco3-btn') ?></a></div>
</div>
</div>
</div>
</section>
<section class="alt" id="inv-form">
<div class="wrap" style="max-width:680px">
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Investment & Acquisition Inquiry"><input type="hidden" name="_page" value="investing"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card">
<h3><?= cms_e('investing.section-3.heading1') ?></h3>
<p class="sub"><?= cms_rich('investing.section-3.p1') ?></p>
<div class="fld-row">
<div class="fld"><label>Your name</label><input name="f_your_name" placeholder="Full name"/></div>
<div class="fld"><label>Phone</label><input type="tel" name="f_phone" placeholder="(___) ___-____"/></div>
</div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>I am a...</label><select name="f_i_am_a"><option>Investor seeking opportunities</option><option>Owner selling investment property</option><option>Homeowner exploring off-market sale</option><option>Potential partner</option></select></div>
<div class="fld"><label>Details</label><textarea name="f_details" placeholder="Property, capital, or opportunity details..." rows="3"></textarea></div>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('investing.section-3.btn1') ?></button>
</div></form>
</div>
</section>
</div>
<!-- ==================== PROPERTY MANAGEMENT /property-management ==================== -->
<div class="page" id="page-pm">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('pm.property-management.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('pm.property-management.heading1') ?></h1>
<p><?= cms_rich('pm.property-management.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" onclick="alert('Links to dedicated PM website (mockup)')"><?= cms_e('pm.property-management.btn1') ?></a></div>
</div><div class="ph hero-media" data-label="Managed Property — Exterior"><?= cms_img('pm.property-management.img-managed-property-exter', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('pm.who-it-s-for.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('pm.who-it-s-for.heading1') ?></h2>
<div class="rule"></div>
<ul class="checklist">
<li><?= cms_e('pm.who-it-s-for.check1') ?></li>
<li><?= cms_e('pm.who-it-s-for.check2') ?></li>
<li><?= cms_e('pm.who-it-s-for.check3') ?></li>
<li><?= cms_e('pm.who-it-s-for.check4') ?></li>
</ul>
</div>
<div>
<p class="eyebrow"><?= cms_e('pm.who-it-s-for.eyebrow2') ?></p>
<div class="eco-grid" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr));margin-top:20px">
<div class="eco"><h3><?= cms_e('pm.who-it-s-for.eco1-title') ?></h3><p><?= cms_e('pm.who-it-s-for.eco1-text') ?></p></div>
<div class="eco"><h3><?= cms_e('pm.who-it-s-for.eco2-title') ?></h3><p><?= cms_e('pm.who-it-s-for.eco2-text') ?></p></div>
<div class="eco"><h3><?= cms_e('pm.who-it-s-for.eco3-title') ?></h3><p><?= cms_e('pm.who-it-s-for.eco3-text') ?></p></div>
<div class="eco"><h3><?= cms_e('pm.who-it-s-for.eco4-title') ?></h3><p><?= cms_e('pm.who-it-s-for.eco4-text') ?></p></div>
</div>
<p style="margin-top:22px;font-size:14px"><?= cms_rich('pm.who-it-s-for.p1') ?></p>
</div>
</div>
</section>
<section class="alt">
<div class="wrap" style="max-width:680px">
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Property Management Inquiry"><input type="hidden" name="_page" value="pm"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card">
<h3><?= cms_e('pm.section-3.heading1') ?></h3>
<p class="sub"><?= cms_rich('pm.section-3.p1') ?></p>
<div class="fld-row">
<div class="fld"><label>Your name</label><input name="f_your_name" placeholder="Full name"/></div>
<div class="fld"><label>Phone</label><input type="tel" name="f_phone" placeholder="(___) ___-____"/></div>
</div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Property address</label><input name="f_property_address" placeholder="Street, City, GA"/></div>
<div class="fld"><label>Current status</label><select name="f_current_status"><option>Vacant</option><option>Tenant in place</option><option>Owner-occupied (moving soon)</option><option>Multiple properties</option></select></div>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('pm.section-3.btn1') ?></button>
</div></form>
</div>
</section>
</div>
<!-- ==================== ESCALUXE TRANSPORTATION & LOGISTICS /transportation-logistics ==================== -->
<div class="page" id="page-transportation">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('transportation.escaluxe-transportation-logi.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('transportation.escaluxe-transportation-logi.heading1') ?></h1>
<p><?= cms_rich('transportation.escaluxe-transportation-logi.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" onclick="document.getElementById('tl-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('transportation.escaluxe-transportation-logi.btn1') ?></a>  <a class="btn btn-outline" onclick="alert('tel:678-404-1562 · fires phone_click (mockup)')"><?= cms_e('transportation.escaluxe-transportation-logi.btn2') ?></a></div>
</div><div class="ph hero-media" data-label="Executive Fleet — Photo or Video"><?= cms_img('transportation.escaluxe-transportation-logi.img-executive-fleet-photo', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('transportation.services.eyebrow1') ?></p><h2><?= cms_e('transportation.services.heading1') ?></h2></div>
<div class="eco-grid">
<div class="eco"><h3><?= cms_e('transportation.services.eco1-title') ?></h3><p><?= cms_e('transportation.services.eco1-text') ?></p><a onclick="document.getElementById('tl-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('transportation.services.eco1-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('transportation.services.eco2-title') ?></h3><p><?= cms_e('transportation.services.eco2-text') ?></p><a onclick="document.getElementById('tl-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('transportation.services.eco2-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('transportation.services.eco3-title') ?></h3><p><?= cms_e('transportation.services.eco3-text') ?></p><a onclick="document.getElementById('tl-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('transportation.services.eco3-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('transportation.services.eco4-title') ?></h3><p><?= cms_e('transportation.services.eco4-text') ?></p><a onclick="document.getElementById('tl-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('transportation.services.eco4-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('transportation.services.eco5-title') ?></h3><p><?= cms_e('transportation.services.eco5-text') ?></p><a onclick="document.getElementById('tl-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('transportation.services.eco5-btn') ?></a></div>
<div class="eco"><h3><?= cms_e('transportation.services.eco6-title') ?></h3><p><?= cms_e('transportation.services.eco6-text') ?></p><a onclick="document.getElementById('tl-form').scrollIntoView({behavior:'smooth'})"><?= cms_e('transportation.services.eco6-btn') ?></a></div>
</div>
</div>
</section>
<section class="alt">
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('transportation.the-escaluxe-standard.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('transportation.the-escaluxe-standard.heading1') ?></h2>
<div class="rule"></div>
<ul class="checklist">
<li><?= cms_e('transportation.the-escaluxe-standard.check1') ?></li>
<li><?= cms_e('transportation.the-escaluxe-standard.check2') ?></li>
<li><?= cms_e('transportation.the-escaluxe-standard.check3') ?></li>
<li><?= cms_e('transportation.the-escaluxe-standard.check4') ?></li>
</ul>
<div class="info-card" style="margin-top:30px">
<h3><?= cms_e('transportation.the-escaluxe-standard.info1-title') ?></h3>
<p><?= cms_rich('transportation.the-escaluxe-standard.info1-text') ?></p>
</div>
</div>
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Transportation & Logistics Inquiry"><input type="hidden" name="_page" value="transportation"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card" id="tl-form">
<h3><?= cms_e('transportation.the-escaluxe-standard.heading2') ?></h3>
<p class="sub"><?= cms_rich('transportation.the-escaluxe-standard.p1') ?></p>
<div class="fld-row">
<div class="fld"><label>Your name</label><input name="f_your_name" placeholder="Full name"/></div>
<div class="fld"><label>Phone</label><input type="tel" name="f_phone" placeholder="(___) ___-____"/></div>
</div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Service needed</label>
<select name="f_service_needed"><option>Executive transportation</option><option>Airport transfer</option><option>Moving services</option><option>Courier &amp; delivery</option><option>Specialty logistics</option><option>Event transportation</option><option>Not sure yet</option></select></div>
<div class="fld"><label>Date needed</label><input name="f_date_needed" placeholder="Date &amp; time"/></div>
<div class="fld"><label>Details</label><textarea name="f_details" placeholder="Pickup, drop-off, passengers or items..." rows="3"></textarea></div>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('transportation.the-escaluxe-standard.btn1') ?></button>
</div></form>
</div>
</section>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('transportation.questions.eyebrow1') ?></p><h2><?= cms_e('transportation.questions.heading1') ?></h2></div>
<div class="faq" style="margin-top:24px">
<details open=""><summary><?= cms_e('transportation.questions.faq1-q') ?></summary><p><?= cms_rich('transportation.questions.faq1-a') ?></p></details>
<details><summary><?= cms_e('transportation.questions.faq2-q') ?></summary><p><?= cms_rich('transportation.questions.faq2-a') ?></p></details>
<details><summary><?= cms_e('transportation.questions.faq3-q') ?></summary><p><?= cms_rich('transportation.questions.faq3-a') ?></p></details>
</div>
</div>
</section>
</div>
<!-- ==================== ESCALUXE LIVING /escaluxe-living ==================== -->
<div class="page" id="page-living">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('living.escaluxe-living.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('living.escaluxe-living.heading1') ?></h1>
<p><?= cms_rich('living.escaluxe-living.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" onclick="alert('Opens Escaluxe Living shop · fires shop_click + UTM (mockup)')"><?= cms_e('living.escaluxe-living.btn1') ?></a></div>
</div><div class="ph hero-media" data-label="Escaluxe Living — Product Editorial · Photo or Video"><?= cms_img('living.escaluxe-living.img-escaluxe-living-produc', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap">
<div class="center-h"><p class="eyebrow"><?= cms_e('living.the-collection.eyebrow1') ?></p><h2><?= cms_e('living.the-collection.heading1') ?></h2></div>
<div class="prod-grid">
<div class="prod"><div class="ph" data-label="Body Care Collection"><?= cms_img('living.the-collection.img-body-care-collection', false, 'card') ?></div><div class="body"><div class="aud"><?= cms_e('living.the-collection.prod1-aud') ?></div><h3><?= cms_e('living.the-collection.prod1-title') ?></h3><div class="prob"><?= cms_e('living.the-collection.prod1-prob') ?></div><p><?= cms_e('living.the-collection.prod1-text') ?></p><a class="btn btn-gold" onclick="alert('Opens Escaluxe Living shop (mockup)')"><?= cms_e('living.the-collection.prod1-btn') ?></a></div></div>
<div class="prod"><div class="ph" data-label="Home Fragrance Collection"><?= cms_img('living.the-collection.img-home-fragrance-collect', false, 'card') ?></div><div class="body"><div class="aud"><?= cms_e('living.the-collection.prod2-aud') ?></div><h3><?= cms_e('living.the-collection.prod2-title') ?></h3><div class="prob"><?= cms_e('living.the-collection.prod2-prob') ?></div><p><?= cms_e('living.the-collection.prod2-text') ?></p><a class="btn btn-gold" onclick="alert('Opens Escaluxe Living shop (mockup)')"><?= cms_e('living.the-collection.prod2-btn') ?></a></div></div>
<div class="prod"><div class="ph" data-label="Living Essentials Collection"><?= cms_img('living.the-collection.img-living-essentials-coll', false, 'card') ?></div><div class="body"><div class="aud"><?= cms_e('living.the-collection.prod3-aud') ?></div><h3><?= cms_e('living.the-collection.prod3-title') ?></h3><div class="prob"><?= cms_e('living.the-collection.prod3-prob') ?></div><p><?= cms_e('living.the-collection.prod3-text') ?></p><a class="btn btn-gold" onclick="alert('Opens Escaluxe Living shop (mockup)')"><?= cms_e('living.the-collection.prod3-btn') ?></a></div></div>
</div>
</div>
</section>
<section class="alt">
<div class="wrap split">
<div class="ph tall" data-label="Escaluxe Living — Lifestyle Photo"><?= cms_img('living.the-story.img-escaluxe-living-lifest', false, 'half') ?></div>
<div>
<p class="eyebrow"><?= cms_e('living.the-story.eyebrow1') ?></p>
<h2><?= cms_e('living.the-story.heading1') ?></h2>
<div class="rule"></div>
<p><?= cms_rich('living.the-story.p1') ?></p>
<p><?= cms_rich('living.the-story.p2') ?></p>
<a class="btn btn-primary" onclick="alert('Opens Escaluxe Living shop (mockup)')"><?= cms_e('living.the-story.btn1') ?></a>
</div>
</div>
</section>
<section class="final">
<div class="wrap">
<h2><?= cms_e('living.give-the-gift-of-escaluxe.heading1') ?></h2>
<p><?= cms_rich('living.give-the-gift-of-escaluxe.p1') ?></p>
<a class="btn btn-gold" onclick="alert('Opens Escaluxe Living shop (mockup)')"><?= cms_e('living.give-the-gift-of-escaluxe.btn1') ?></a>  <a class="btn btn-outline" href="/contact" data-nav="contact" onclick="return _nav(event,'contact')"><?= cms_e('living.give-the-gift-of-escaluxe.btn2') ?></a>
</div>
</section>
</div>
<!-- ==================== LOCATION: ATLANTA METRO ==================== -->
<div class="page" id="page-loc-atlanta">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('loc-atlanta.locations-atlanta-metro.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('loc-atlanta.locations-atlanta-metro.heading1') ?></h1>
<p><?= cms_rich('loc-atlanta.locations-atlanta-metro.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')"><?= cms_e('loc-atlanta.locations-atlanta-metro.btn1') ?></a></div>
</div><div class="ph hero-media" data-label="Atlanta Skyline — Photo"><?= cms_img('loc-atlanta.locations-atlanta-metro.img-atlanta-skyline-photo', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('loc-atlanta.selling-in-atlanta-metro.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('loc-atlanta.selling-in-atlanta-metro.heading1') ?></h2>
<div class="rule"></div>
<p style="max-width:480px"><?= cms_rich('loc-atlanta.selling-in-atlanta-metro.p1') ?></p>
<p class="eyebrow" style="margin-top:30px"><?= cms_e('loc-atlanta.selling-in-atlanta-metro.eyebrow2') ?></p>
<p style="max-width:480px;margin-top:10px"><?= cms_rich('loc-atlanta.selling-in-atlanta-metro.p2') ?></p>
<div style="margin-top:24px"><a class="btn btn-outline-dark" href="/gwinnett-lawrenceville" data-nav="loc-gwinnett" onclick="return _nav(event,'loc-gwinnett')"><?= cms_e('loc-atlanta.selling-in-atlanta-metro.btn1') ?></a>  <a class="btn btn-outline-dark" href="/fayette-peachtree-city" data-nav="loc-fayette" onclick="return _nav(event,'loc-fayette')"><?= cms_e('loc-atlanta.selling-in-atlanta-metro.btn2') ?></a></div>
</div>
<div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('loc-atlanta.selling-in-atlanta-metro.tst1-quote') ?></blockquote><div class="who"><?= cms_e('loc-atlanta.selling-in-atlanta-metro.tst1-who') ?></div></div>
<div class="faq" style="margin-top:34px;max-width:none">
<details open=""><summary><?= cms_e('loc-atlanta.selling-in-atlanta-metro.faq1-q') ?></summary><p><?= cms_rich('loc-atlanta.selling-in-atlanta-metro.faq1-a') ?></p></details>
<details><summary><?= cms_e('loc-atlanta.selling-in-atlanta-metro.faq2-q') ?></summary><p><?= cms_rich('loc-atlanta.selling-in-atlanta-metro.faq2-a') ?></p></details>
<details><summary><?= cms_e('loc-atlanta.selling-in-atlanta-metro.faq3-q') ?></summary><p><?= cms_rich('loc-atlanta.selling-in-atlanta-metro.faq3-a') ?></p></details>
</div>
</div>
</div>
</section>
</div>
<!-- ==================== LOCATION: LAWRENCEVILLE / GWINNETT ==================== -->
<div class="page" id="page-loc-gwinnett">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('loc-gwinnett.locations-lawrenceville-gwin.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('loc-gwinnett.locations-lawrenceville-gwin.heading1') ?></h1>
<p><?= cms_rich('loc-gwinnett.locations-lawrenceville-gwin.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')"><?= cms_e('loc-gwinnett.locations-lawrenceville-gwin.btn1') ?></a></div>
</div><div class="ph hero-media" data-label="Downtown Lawrenceville Square"><?= cms_img('loc-gwinnett.locations-lawrenceville-gwin.img-downtown-lawrenceville', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('loc-gwinnett.selling-in-gwinnett.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('loc-gwinnett.selling-in-gwinnett.heading1') ?></h2>
<div class="rule"></div>
<p style="max-width:480px"><?= cms_rich('loc-gwinnett.selling-in-gwinnett.p1') ?></p>
<p class="eyebrow" style="margin-top:30px"><?= cms_e('loc-gwinnett.selling-in-gwinnett.eyebrow2') ?></p>
<p style="max-width:480px;margin-top:10px"><?= cms_rich('loc-gwinnett.selling-in-gwinnett.p2') ?></p>
</div>
<div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('loc-gwinnett.selling-in-gwinnett.tst1-quote') ?></blockquote><div class="who"><?= cms_e('loc-gwinnett.selling-in-gwinnett.tst1-who') ?></div></div>
<div class="faq" style="margin-top:34px;max-width:none">
<details open=""><summary><?= cms_e('loc-gwinnett.selling-in-gwinnett.faq1-q') ?></summary><p><?= cms_rich('loc-gwinnett.selling-in-gwinnett.faq1-a') ?></p></details>
<details><summary><?= cms_e('loc-gwinnett.selling-in-gwinnett.faq2-q') ?></summary><p><?= cms_rich('loc-gwinnett.selling-in-gwinnett.faq2-a') ?></p></details>
<details><summary><?= cms_e('loc-gwinnett.selling-in-gwinnett.faq3-q') ?></summary><p><?= cms_rich('loc-gwinnett.selling-in-gwinnett.faq3-a') ?></p></details>
</div>
</div>
</div>
</section>
</div>
<!-- ==================== LOCATION: FAYETTE / PEACHTREE CITY ==================== -->
<div class="page" id="page-loc-fayette">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('loc-fayette.locations-fayette-county-pea.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('loc-fayette.locations-fayette-county-pea.heading1') ?></h1>
<p><?= cms_rich('loc-fayette.locations-fayette-county-pea.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')"><?= cms_e('loc-fayette.locations-fayette-county-pea.btn1') ?></a></div>
</div><div class="ph hero-media" data-label="Peachtree City — Golf Cart Path"><?= cms_img('loc-fayette.locations-fayette-county-pea.img-peachtree-city-golf-ca', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap two-col">
<div>
<p class="eyebrow"><?= cms_e('loc-fayette.selling-in-fayette-peachtree.eyebrow1') ?></p>
<h2 style="font-size:clamp(26px,3vw,36px);margin-top:10px"><?= cms_e('loc-fayette.selling-in-fayette-peachtree.heading1') ?></h2>
<div class="rule"></div>
<p style="max-width:480px"><?= cms_rich('loc-fayette.selling-in-fayette-peachtree.p1') ?></p>
<p class="eyebrow" style="margin-top:30px"><?= cms_e('loc-fayette.selling-in-fayette-peachtree.eyebrow2') ?></p>
<p style="max-width:480px;margin-top:10px"><?= cms_rich('loc-fayette.selling-in-fayette-peachtree.p2') ?></p>
</div>
<div>
<div class="tst"><div aria-label="Rated 5 out of 5 stars" class="stars" role="img"><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg><svg aria-hidden="true" viewbox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.13 5.11 5.52.44a.56.56 0 0 1 .32.99l-4.2 3.6 1.28 5.38a.56.56 0 0 1-.84.61L12 16.73l-4.73 2.9a.56.56 0 0 1-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44z"></path></svg></div><blockquote><?= cms_e('loc-fayette.selling-in-fayette-peachtree.tst1-quote') ?></blockquote><div class="who"><?= cms_e('loc-fayette.selling-in-fayette-peachtree.tst1-who') ?></div></div>
<div class="faq" style="margin-top:34px;max-width:none">
<details open=""><summary><?= cms_e('loc-fayette.selling-in-fayette-peachtree.faq1-q') ?></summary><p><?= cms_rich('loc-fayette.selling-in-fayette-peachtree.faq1-a') ?></p></details>
<details><summary><?= cms_e('loc-fayette.selling-in-fayette-peachtree.faq2-q') ?></summary><p><?= cms_rich('loc-fayette.selling-in-fayette-peachtree.faq2-a') ?></p></details>
<details><summary><?= cms_e('loc-fayette.selling-in-fayette-peachtree.faq3-q') ?></summary><p><?= cms_rich('loc-fayette.selling-in-fayette-peachtree.faq3-a') ?></p></details>
</div>
</div>
</div>
</section>
</div>
<!-- ==================== CONTACT /contact ==================== -->
<div class="page" id="page-contact">
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('contact.contact.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('contact.contact.heading1') ?></h1>
<p><?= cms_rich('contact.contact.p1') ?></p>
<div style="margin-top:30px"><a class="btn btn-gold" onclick="alert('Opens Google Workspace booking link (Calendly backup) — mockup')"><?= cms_e('contact.contact.btn1') ?></a>  <a class="btn btn-outline" onclick="alert('tel:678-404-1562 · fires phone_click (mockup)')"><?= cms_e('contact.contact.btn2') ?></a></div>
</div><div class="ph hero-media" data-label="Erika — Welcome Photo"><?= cms_img('contact.contact.img-erika-welcome-photo', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap">
<div class="contact-grid">
<div class="form-card">
<h3><?= cms_e('contact.section-2.heading1') ?></h3>
<p class="sub"><?= cms_rich('contact.section-2.p1') ?></p>
<div class="fld"><label>Name</label><input placeholder="Full name"/></div>
<div class="fld"><label>Email</label><input placeholder="you@email.com"/></div>
<div class="fld"><label>Property address</label><input placeholder="Street, City, GA"/></div>
<a class="btn btn-primary" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')" style="width:100%;text-align:center"><?= cms_e('contact.section-2.btn1') ?></a>
</div>
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Speaking Inquiry"><input type="hidden" name="_page" value="contact"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card">
<h3><?= cms_e('contact.section-2.heading2') ?></h3>
<p class="sub"><?= cms_rich('contact.section-2.p2') ?></p>
<div class="fld"><label>Name</label><input name="f_name" placeholder="Full name"/></div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Event &amp; date</label><input name="f_event_amp_date" placeholder="Event · Date"/></div>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('contact.section-2.btn2') ?></button>
</div></form>
<form method="post" action="/submit.php" class="cmsform"><input type="hidden" name="_form" value="Collaboration Inquiry"><input type="hidden" name="_page" value="contact"><input type="hidden" name="_t" value="<?= time() ?>"><div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div><div class="form-card">
<h3><?= cms_e('contact.section-2.heading3') ?></h3>
<p class="sub"><?= cms_rich('contact.section-2.p3') ?></p>
<div class="fld"><label>Name</label><input name="f_name" placeholder="Full name"/></div>
<div class="fld"><label>Email</label><input type="email" name="f_email" placeholder="you@email.com"/></div>
<div class="fld"><label>Brand / idea</label><input name="f_brand_idea" placeholder="Tell us briefly"/></div>
<button type="submit" class="btn btn-primary" style="width:100%;text-align:center"><?= cms_e('contact.section-2.btn3') ?></button>
</div></form>
</div>
<div class="two-col" style="margin-top:56px">
<div class="info-card">
<h3><?= cms_e('contact.section-2.info1-title') ?></h3>
<p><?= cms_rich('contact.section-2.info1-text') ?></p>
</div>
<div class="info-card">
<h3><?= cms_e('contact.section-2.info2-title') ?></h3>
<p><?= cms_rich('contact.section-2.info2-text') ?></p>
</div>
</div>
</div>
</section>
</div>
<!-- ==================== GALLERY /gallery ==================== -->
<div class="page" id="page-gallery">
<?php $galItems = gallery_items(); $galCats = array_column($galItems, 'cat'); ?>
<header class="page-hero">
<div class="wrap hero2"><div>
<p class="eyebrow on-dark"><?= cms_e('gallery.gallery.eyebrow1') ?></p>
<h1 style="margin-top:12px"><?= cms_rich('gallery.gallery.heading1') ?></h1>
<p><?= cms_rich('gallery.gallery.p1') ?></p>
<div class="rev-filter">
<span class="chip on" data-cat="">All</span><?php foreach (GALLERY_CATS as $cid => $clabel): if (!in_array($cid, $galCats, true)) continue; ?><span class="chip" data-cat="<?= esc($cid) ?>"><?= esc($clabel) ?></span><?php endforeach; ?>
</div>
</div><div class="ph hero-media" data-label="Featured — Gallery Highlight"><?= cms_img('gallery.gallery.img-featured-gallery-highl', false, 'media') ?></div></div>
</header>
<section>
<div class="wrap">
<div class="gal">
<?php foreach ($galItems as $gi): ?>
<div class="g" data-cat="<?= esc($gi['cat']) ?>"><div class="ph" style="aspect-ratio:<?= esc($gi['ar']) ?>"><?= media_tag($gi['src'], $gi['cap'], false, 'gal') ?></div><div class="cap"><?= esc($gi['cap']) ?></div></div>
<?php endforeach; ?>
</div>
<p style="text-align:center;margin-top:36px;font-size:14px"><?= cms_rich('gallery.section-2.p1') ?></p>
<div style="text-align:center;margin-top:24px"><a class="btn btn-primary" href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')"><?= cms_e('gallery.section-2.btn1') ?></a></div>
</div>
</section>
</div>
</main>
<!-- ==================== FOOTER ==================== -->
<footer>
<div class="wrap">
<div class="foot-grid">
<div>
<div class="foot-brand"><?= cms_e('global.footer.brand') ?></div>
<p style="margin-top:8px;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:var(--blush)"><?= cms_rich('global.footer.p1') ?></p>
<p style="margin-top:16px"><?= cms_rich('global.footer.p2') ?></p>
</div>
<div>
<h4>Quick Links</h4>
<ul><li><a href="/about" data-nav="about" onclick="return _nav(event,'about')">About</a></li><li><a href="/sell" data-nav="sell" onclick="return _nav(event,'sell')">Sell</a></li><li><a href="/home-value" data-nav="homevalue" onclick="return _nav(event,'homevalue')">Home Value</a></li><li><a href="/speaking" data-nav="speaking" onclick="return _nav(event,'speaking')">Speaking</a></li><li><a href="/testimonials" data-nav="testimonials" onclick="return _nav(event,'testimonials')">Testimonials</a></li><li><a href="/gallery" data-nav="gallery" onclick="return _nav(event,'gallery')">Gallery</a></li><li><a href="/resources" data-nav="resources" onclick="return _nav(event,'resources')">Resources</a></li><li><a href="/contact" data-nav="contact" onclick="return _nav(event,'contact')">Contact</a></li></ul>
</div>
<div>
<h4>Ecosystem</h4>
<ul><li><a href="/property-management" data-nav="pm" onclick="return _nav(event,'pm')">Property Management</a></li><li><a href="/investing" data-nav="investing" onclick="return _nav(event,'investing')">Capital &amp; Acquisitions</a></li><li><a href="/transportation-logistics" data-nav="transportation" onclick="return _nav(event,'transportation')">Transportation &amp; Logistics</a></li><li><a href="/escaluxe-living" data-nav="living" onclick="return _nav(event,'living')">Escaluxe Living</a></li><li><a onclick="alert('Axen Realty / Lofty (mockup)')">Axen Realty / Lofty</a></li><li><a onclick="alert('stan.store/erikakpage (mockup)')">Stan Store</a></li><li><a href="/erika-explains" data-nav="explains" onclick="return _nav(event,'explains')">Erika Explains</a></li><li><a href="/mentorship" data-nav="mentorship" onclick="return _nav(event,'mentorship')">Mentorship</a></li></ul>
</div>
<div>
<h4>Local Expertise</h4>
<ul><li><a href="/atlanta-metro" data-nav="loc-atlanta" onclick="return _nav(event,'loc-atlanta')">Atlanta Metro</a></li><li><a href="/gwinnett-lawrenceville" data-nav="loc-gwinnett" onclick="return _nav(event,'loc-gwinnett')">Gwinnett County / Lawrenceville</a></li><li><a href="/fayette-peachtree-city" data-nav="loc-fayette" onclick="return _nav(event,'loc-fayette')">Peachtree City / Fayette</a></li><li><a onclick="alert('Local page coming soon (mockup)')">Sandy Springs / Roswell / Alpharetta</a></li><li><a onclick="alert('Local page coming soon (mockup)')">East Cobb / Marietta</a></li><li><a onclick="alert('Local page coming soon (mockup)')">Brookhaven / Decatur / Tucker</a></li><li><a onclick="alert('Local page coming soon (mockup)')">McDonough / Henry</a></li><li><a href="/lifestyle" data-nav="lifestyle" onclick="return _nav(event,'lifestyle')">Lifestyle &amp; Magazine</a></li><li><a href="/media" data-nav="media" onclick="return _nav(event,'media')">Media &amp; Press</a></li><li><a href="/collaborations" data-nav="collaborations" onclick="return _nav(event,'collaborations')">Collaborations</a></li></ul>
</div>
<div>
<h4>Follow</h4>
<ul><li><a>Instagram</a></li><li><a>Facebook</a></li><li><a>YouTube</a></li><li><a>LinkedIn</a></li><li><a>TikTok</a></li></ul>
</div>
</div>
<div class="foot-bottom">
<span><?= cms_e('global.footer.bottom1') ?></span>
<span><?= cms_e('global.footer.bottom2') ?></span>
</div>
</div>
</footer>
<button aria-label="Back to top" id="backtop"><svg aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" viewbox="0 0 24 24"><path d="M18 15l-6-6-6 6"></path></svg></button>
<script>
(function(){
var navEl=document.querySelector('nav');
var nl=document.getElementById('nl');
var burger=document.querySelector('.burger');
var reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- page routing (real per-page URLs + working history) ---------- */
var PATHS=window.__paths||{};                 // id -> "/clean-path"
var PATH2ID={};for(var k in PATHS){PATH2ID[PATHS[k]]=k;}
function closeNav(){nl.classList.remove('open');burger.setAttribute('aria-expanded','false');}
function setNavActive(p){
  var map={homevalue:'sell',media:'lifestyle',collaborations:'lifestyle',products:'resources',explains:'resources',mentorship:'resources',investing:'resources',pm:'resources',transportation:'resources',living:'resources','loc-atlanta':'home','loc-gwinnett':'home','loc-fayette':'home',about:'home'};
  var key=map[p]||p;
  document.querySelectorAll('.nav-links a[data-p]').forEach(function(a){a.classList.toggle('active',a.dataset.p===key)});
}
/* render a page without touching history (used on load + back/forward) */
function render(p){
  var t=document.getElementById('page-'+p);
  if(!t)return false;
  document.querySelectorAll('.page').forEach(function(el){el.classList.remove('active')});
  t.classList.add('active');
  setNavActive(p);
  closeNav();
  return true;
}
/* navigate: change page, push a real URL so Back/Forward work */
window.go=function(p){
  if(!render(p))return;
  var path=PATHS[p]||'/';
  if(location.pathname!==path && history.pushState) history.pushState({p:p},'',path);
  window.scrollTo({top:0,behavior:'instant'});
};
/* click handler for real links: SPA-navigate on plain left-click, else let the browser do its thing */
window._nav=function(e,p){
  if(e.metaKey||e.ctrlKey||e.shiftKey||e.altKey||e.button)return true;
  e.preventDefault();go(p);return false;
};
/* Back / Forward buttons */
window.addEventListener('popstate',function(e){
  var p=(e.state&&e.state.p)||PATH2ID[location.pathname]||'home';
  render(p);
  window.scrollTo({top:0,behavior:'instant'});
});
/* Initial load: server already marked the right page active; sync nav + seed history state.
   Also upgrade any legacy #/page hash link to the real URL. */
(function(){
  var start=window.__page||'home';
  var legacy=location.hash.match(/^#\/(.+)$/);
  if(legacy&&document.getElementById('page-'+legacy[1])){
    start=legacy[1];
    if(history.replaceState)history.replaceState({p:start},'',PATHS[start]||'/');
  } else if(history.replaceState){
    history.replaceState({p:start},'',location.pathname);
  }
  render(start);
})();

/* ---------- mobile nav ---------- */
burger.addEventListener('click',function(){
  var open=nl.classList.toggle('open');
  burger.setAttribute('aria-expanded',String(open));
});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeNav();});

/* ---------- keyboard access for script-driven links ---------- */
document.querySelectorAll('a[onclick]:not([href])').forEach(function(a){
  a.setAttribute('tabindex','0');
  a.setAttribute('role','button');
  a.addEventListener('keydown',function(e){
    if(e.key==='Enter'||e.key===' '){e.preventDefault();a.click();}
  });
});

/* ---------- filter chips ---------- */
document.querySelectorAll('.chip').forEach(function(c){
  c.setAttribute('tabindex','0');
  c.setAttribute('role','button');
  function activate(){
    c.parentElement.querySelectorAll('.chip').forEach(function(x){x.classList.remove('on')});
    c.classList.add('on');
    /* Only the gallery chips carry a category; the ones on other pages stay
       decorative until those sections have real categories of their own. */
    var cat=c.getAttribute('data-cat');
    if(cat===null)return;
    var page=c.closest('.page');
    (page||document).querySelectorAll('.gal .g').forEach(function(g){
      g.style.display=(cat===''||g.getAttribute('data-cat')===cat)?'':'none';
    });
  }
  c.addEventListener('click',activate);
  c.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();activate();}});
});

/* ---------- scroll reveal ---------- */
if(!reduced&&'IntersectionObserver' in window){
  var sel='.split>*,.center-h,.tst,.eco,.res-card,.step,.prod,.topic,.gal .g,.thumb-strip .g,.form-card,.info-card,.two-col>*,.press-logo,.lead-mag,.faq';
  var els=document.querySelectorAll(sel);
  els.forEach(function(el){
    el.classList.add('reveal');
    var i=Array.prototype.indexOf.call(el.parentElement.children,el);
    el.style.transitionDelay=Math.min(i*60,360)+'ms';
  });
  var io=new IntersectionObserver(function(entries){
    entries.forEach(function(en){
      if(en.isIntersecting){en.target.classList.add('in');io.unobserve(en.target);}
    });
  },{threshold:.1,rootMargin:'0px 0px -40px 0px'});
  els.forEach(function(el){io.observe(el);});
}

/* ---------- animated stat counters ---------- */
function animateCount(el){
  var target=parseFloat(el.dataset.count),
      prefix=el.dataset.prefix||'',
      suffix=el.dataset.suffix||'',
      dur=1400,start=null;
  function step(ts){
    if(!start)start=ts;
    var p=Math.min((ts-start)/dur,1),
        eased=1-Math.pow(1-p,3);
    el.textContent=prefix+Math.round(target*eased).toLocaleString('en-US')+suffix;
    if(p<1)requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}
if(!reduced&&'IntersectionObserver' in window){
  var cio=new IntersectionObserver(function(entries){
    entries.forEach(function(en){
      if(en.isIntersecting){
        en.target.querySelectorAll('[data-count]').forEach(animateCount);
        cio.unobserve(en.target);
      }
    });
  },{threshold:.4});
  document.querySelectorAll('.proof').forEach(function(p){cio.observe(p);});
}

/* ---------- nav shadow + back to top ---------- */
var backtop=document.getElementById('backtop');
window.addEventListener('scroll',function(){
  navEl.classList.toggle('scrolled',window.scrollY>8);
  backtop.classList.toggle('show',window.scrollY>600);
},{passive:true});
backtop.addEventListener('click',function(){
  window.scrollTo({top:0,behavior:reduced?'instant':'smooth'});
});
})();
</script>
</body>
</html>
<?php
$html = ob_get_clean();

// Server-side active page: clear the hard-coded home default, then activate the requested page.
$html = str_replace('<div class="page active" id="page-home">', '<div class="page" id="page-home">', $html);
$html = str_replace('<div class="page" id="page-' . $current . '">',
                    '<div class="page active" id="page-' . $current . '">', $html);

// Success / error banner after a form submission (submit.php redirects here with ?sent / ?senterr).
$banner = '';
if (isset($_GET['sent'])) {
    $banner = '<div class="formflash ok" role="status">Thank you — your message has been sent. Erika&rsquo;s team will be in touch shortly.</div>';
} elseif (isset($_GET['senterr'])) {
    $msg = $_GET['senterr'] === 'config'
        ? 'Sorry — the site&rsquo;s email isn&rsquo;t set up yet, so this form could not be sent. Please call 678-404-1562.'
        : 'Sorry — something went wrong sending your message. Please try again or call 678-404-1562.';
    $banner = '<div class="formflash err" role="alert">' . $msg . '</div>';
}
if ($banner !== '') {
    $html = preg_replace('/(<main id="main">)/', '$1' . $banner, $html, 1);
}

// Router data for the client (real URLs + history) — must load before the router script.
$inject = '<script>window.__page=' . json_encode($current)
        . ';window.__paths=' . json_encode($PATHS) . ';</script>';
$html = str_replace('</head>', $inject . '</head>', $html);

echo $html;

