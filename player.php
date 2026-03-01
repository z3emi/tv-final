<?php
// player.php — Uruk Minimal Pro Skin - محسن للـ iPhone
require_once 'config.php'; // يستخدم ملف الاتصال المركزي

// ---- Params ----
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo 'Missing or invalid channel id.';
    exit;
}

$name = '';
$url = '';
$audio_url = '';
$poster_url = '';

if (isset($mysqli)) {
    $stmt = $mysqli->prepare("SELECT name, url, is_direct, image_url FROM channels WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $name = (string)($row['name'] ?? '');

        if (!empty($row['is_direct']) && (int)$row['is_direct'] === 1 && !empty($row['url'])) {
            $url = trim((string)$row['url']);
        } else {
            $links = build_local_stream_links((string)$id);
            $url = $links['stream_link'];
            $audio_url = $links['audio_link'];
        }

        if (!empty($row['image_url'])) {
            $safeImage = rawurlencode(basename((string)$row['image_url']));
            $poster_url = 'stream/stream/uploads/' . $safeImage;
        }
    }

    $stmt->close();
}

if ($name === '' && isset($_GET['name'])) {
    $name = trim((string)$_GET['name']);
}

if ($url === '' && isset($_GET['url'])) {
    $url = trim((string)$_GET['url']);
}

if ($name === '' || $url === '') {
    http_response_code(404);
    echo 'Channel not found or stream URL unavailable.';
    exit;
}

// تأكيد URL مطلق
if (!preg_match('~^https?://~i', $url)) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = ltrim($url, '/');
    $url = "$scheme://$host/$path";
}

if ($audio_url !== '' && !preg_match('~^https?://~i', $audio_url)) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = ltrim($audio_url, '/');
    $audio_url = "$scheme://$host/$path";
}

// عند فتح الصفحة عبر HTTPS، بعض المتصفحات تمنع بث HTTP (Mixed Content).
// نمرر روابط HTTP عبر proxy.php لكي تعمل القنوات بشكل طبيعي.
function maybe_proxy_insecure_stream(string $streamUrl): string {
    if ($streamUrl === '' || !preg_match('~^http://~i', $streamUrl)) {
        return $streamUrl;
    }

    $isHttpsRequest = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (!$isHttpsRequest) {
        return $streamUrl;
    }

    return 'proxy.php?url=' . rawurlencode($streamUrl);
}

$url = maybe_proxy_insecure_stream($url);
if ($audio_url !== '') {
    $audio_url = maybe_proxy_insecure_stream($audio_url);
}

$website_title = 'Player';
if (isset($mysqli)) {
    $rs = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'");
    if ($rs && $row = $rs->fetch_assoc()) $website_title = $row['setting_value'] . ' | Player';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,user-scalable=no">
<!-- إعدادات خاصة بـ iOS -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="format-detection" content="telephone=no">
<title><?= htmlspecialchars($website_title) ?></title>
<link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
<script src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
<style>
  :root{
    --brand:#ff7a1a;--brand-2:#ff9a4d;--brand-glow:rgba(255,122,26,.45);
    --bg:#0d0f12;--fg:#e9eaee;--card-radius:16px;
    --card-shadow:0 10px 28px rgba(0,0,0,.35), 0 2px 6px rgba(0,0,0,.25);
    --ctrl-h:52px;
    --bar-bg:rgba(15,16,20,.60);--bar-border:rgba(255,255,255,.08);
    --bar-fg:#f4f5f7;--bar-fg-muted:#cfd2d8;--bar-accent:#fff;
    --progress-track:rgba(255,255,255,.16);--progress-buffer:rgba(255,255,255,.26);
    --progress-play:linear-gradient(90deg, var(--brand), var(--brand-2));
    --progress-thumb:#fff;--volume-bg:rgba(255,255,255,.14);--volume-fill:#fff;
    --chip-bg:rgba(15,16,20,.7);--chip-border:rgba(255,255,255,.18);
    --focus:#9ec5ff
  }

  html,body{height:100%;margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,'Cairo',sans-serif;overflow:hidden}
  .wrap{width:100%;height:100svh;min-height:100svh;display:flex;align-items:center;justify-content:center;padding:0}
  .player-box{width:100%;height:100%;max-width:1500px}
  .video-js{width:100%;height:100%;background:#000;border-radius:var(--card-radius);overflow:hidden;box-shadow:var(--card-shadow)}
  .video-js.vjs-fullscreen{border-radius:0;box-shadow:none;background:#000;position:relative}

  /* زر التشغيل الكبير */
  .vjs-big-play-button{
    width:78px;height:78px;border-radius:999px;border:1px solid rgba(255,255,255,.25);
    background:linear-gradient(180deg,#fff,#f5f5f5);color:#000;
    box-shadow:0 10px 24px rgba(0,0,0,.35),0 0 0 8px rgba(255,122,26,.12);
    transition:transform .18s,box-shadow .18s,background .25s,border-color .25s
  }
  .vjs-big-play-button .vjs-icon-placeholder:before{line-height:78px;font-size:42px;color:#000}
  .vjs-big-play-button:hover{transform:scale(1.05);box-shadow:0 14px 30px rgba(0,0,0,.45),0 0 0 10px rgba(255,122,26,.16)}
  .vjs-has-started .vjs-big-play-button{display:none!important}
  .vjs-loading-spinner{display:none!important}

  /* شريط التحكم */
  .vjs-control-bar{
    background:var(--bar-bg);
    backdrop-filter:blur(8px) saturate(1.05);
    -webkit-backdrop-filter:blur(8px) saturate(1.05);
    border-top:1px solid var(--bar-border);
    min-height:var(--ctrl-h); padding:6px 10px; z-index:5;
    transition:transform .25s,opacity .25s;
    display:flex !important;
  }
  .vjs-control{color:var(--bar-fg);transition:color .12s,opacity .12s,transform .12s;margin-left:0 !important}
  .vjs-control .vjs-icon-placeholder:before{color:var(--bar-fg)}
  .vjs-control:focus .vjs-icon-placeholder:before,.vjs-control:hover .vjs-icon-placeholder:before{color:var(--bar-accent)}
  .vjs-control:focus-visible{outline:2px solid var(--focus);outline-offset:2px;border-radius:10px}
  .vjs-time-control{color:var(--bar-fg-muted);font-variant-numeric:tabular-nums}
  .video-js.vjs-user-inactive .vjs-control-bar{transform:translateY(14px);opacity:0}
  .video-js.vjs-user-active .vjs-control-bar{transform:none;opacity:1}

  @media (pointer:coarse){
    .vjs-control{min-width:46px;min-height:46px}
    .vjs-volume-panel.vjs-volume-panel-horizontal .vjs-volume-control{width:96px}
    .vjs-control-bar{min-height:var(--ctrl-h)}
  }

  /* شريط التقدم */
  .vjs-progress-control{height:18px}
  .vjs-progress-control .vjs-progress-holder{background:rgba(255,255,255,.16);height:6px;border-radius:999px;overflow:hidden;box-shadow:inset 0 0 0 1px rgba(255,255,255,.08)}
  .vjs-load-progress,.vjs-load-progress div{background:rgba(255,255,255,.26)!important}
  .vjs-play-progress,.vjs-slider-bar{background:var(--progress-play)!important}
  .vjs-play-progress::after{
    content:"";position:absolute;right:-6px;top:50%;width:12px;height:12px;border-radius:50%;
    background:var(--progress-thumb);transform:translateY(-50%);
    box-shadow:0 2px 8px var(--brand-glow),0 0 0 6px rgba(255,255,255,.06)
  }

  /* الصوت */
  .vjs-volume-panel .vjs-volume-control .vjs-volume-bar{
    height:6px;border-radius:999px;background:var(--volume-bg);
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.08)
  }
  .vjs-volume-bar .vjs-slider-bar,.vjs-volume-level{background:#fff!important}

  /* LIVE */
  .vjs-live-control{
    color:#ff7a1a !important;  
    font-weight:700; letter-spacing:.3px; position:relative; padding-inline:6px;
  }
  .vjs-live-control::before{
    content:"LIVE";display:inline-block;margin-inline-end:8px;
    background:linear-gradient(180deg,var(--brand-2),var(--brand));color:#fff;
    padding:3px 8px;border-radius:999px;font-size:11px;line-height:1;
    box-shadow:0 6px 14px var(--brand-glow)
  }

  /* زر Cast */
  .vjs-cast-button .vjs-icon-cast{
    background-image:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path fill="white" d="M1 18v3h3c0-1.66-1.34-3-3-3zm0-4v2c2.76 0 5 2.24 5 5h2c0-3.87-3.13-7-7-7zm18-7H5v1.63c3.96 1.28 7.09 4.41 8.37 8.37H19V7zM1 10v2c4.97 0 9 4.03 9 9h2c0-6.08-4.93-11-11-11zm20-7H3c-1.1 0-2 .9-2 2v3h2V5h18v14h-7v2h7c1.1 0 2-.9 2-2z"/></svg>');
    background-repeat:no-repeat;background-position:center;width:24px;height:24px;display:inline-block
  }
  .vjs-cast-button.available{opacity:1}
  .vjs-cast-button.not-available{cursor:not-allowed;opacity:.55}

  /* زر ViewMode */
  .vjs-viewmode-toggle .vjs-icon-viewmode{
    width:20px;height:20px;display:inline-block;background-repeat:no-repeat;background-position:center;
    margin-inline-end:6px;filter:drop-shadow(0 0 8px var(--brand-glow))
  }
  .vjs-viewmode-toggle.mode-crop .vjs-icon-viewmode{
    background-image:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><rect x="2" y="5" width="16" height="10" rx="3" ry="3" fill="none" stroke="white" stroke-width="2"/><rect x="4" y="7.5" width="12" height="5" fill="white"/></svg>')
  }
  .vjs-viewmode-toggle.mode-fit .vjs-icon-viewmode{
    background-image:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><rect x="2" y="5" width="16" height="10" rx="3" ry="3" fill="none" stroke="white" stroke-width="2"/><rect x="6" y="8" width="8" height="4" fill="white"/></svg>')
  }
  .vjs-viewmode-toggle.mode-stretch .vjs-icon-viewmode{
    background-image:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><rect x="2" y="5" width="16" height="10" rx="3" ry="3" fill="white"/></svg>')
  }

  /* إظهار الشيبّات فقط بوضع ملء الشاشة */
  .video-js:not(.vjs-fullscreen) .vjs-cast-button,
  .video-js:not(.vjs-fullscreen) .vjs-viewmode-toggle{display:none!important}
  .video-js.vjs-fullscreen .vjs-cast-button,
  .video-js.vjs-fullscreen .vjs-viewmode-toggle{
    position:absolute;bottom:calc(12px + var(--ctrl-h));z-index:6;display:inline-flex!important;align-items:center;gap:8px;
    padding:9px 12px;border-radius:12px;background:var(--chip-bg);border:1px solid var(--chip-border);color:#fff;
    height:auto;width:auto;pointer-events:auto;opacity:0;transform:translateY(6px);transition:opacity .2s,transform .2s
  }
  .video-js.vjs-fullscreen .vjs-cast-button{right:12px}
  .video-js.vjs-fullscreen .vjs-viewmode-toggle{right:120px}
  .video-js.vjs-fullscreen.vjs-user-active .vjs-cast-button,
  .video-js.vjs-fullscreen.vjs-user-active .vjs-viewmode-toggle{opacity:1;transform:none}
  .video-js.vjs-fullscreen .vjs-cast-button::after{content:' Cast';font-size:13px;line-height:1;color:#fff;margin-inline-start:6px}
  .video-js.vjs-fullscreen .vjs-viewmode-toggle .vmode-label{font-size:13px;line-height:1}

  /* أوضاع العرض */
  .vmode-crop .vjs-tech{object-fit:cover!important;object-position:center}
  .vmode-fit .vjs-tech{object-fit:contain!important;object-position:center}
  .vmode-stretch .vjs-tech{object-fit:fill!important;object-position:center}

  /* أخطاء */
  .vjs-error .vjs-error-display,.vjs-error .vjs-modal-dialog,.vjs-errors-dialog{display:none!important}
  .vjs-error .vjs-control-bar{display:flex!important}
  .vjs-error .vjs-big-play-button{display:none!important}

  /* 🔄 شارة إعادة الاتصال — شفافة تماماً */
  .reconnect-badge{
    position:absolute;left:50%;bottom:calc(var(--ctrl-h) + 18px);
    transform:translateX(-50%);background:transparent !important;color:#fff;
    border:none !important;padding:8px 12px;border-radius:12px;font-size:13px;line-height:1;
    display:none;box-shadow:none !important;
  }
  .reconnect-badge::before{
    content:"";display:inline-block;width:10px;height:10px;border-radius:50%;
    margin-inline-end:8px;background:radial-gradient(circle at 30% 30%,#fff,var(--brand));
    animation:pulse 1s ease-in-out infinite alternate
  }
  @keyframes pulse{from{opacity:.6;transform:scale(.9)}to{opacity:1;transform:scale(1)}}

  /* ⏳ شاشة الانتظار — شفافة */
  #startup-overlay{background:transparent !important}

  /* ترتيب الأزرار: Fullscreen يمين، وPiP يساره مباشرة */
  .vjs-control-bar .vjs-fullscreen-control{margin-left:auto !important;order:1000 !important}
  .vjs-control-bar .vjs-picture-in-picture-control{order:999 !important}
  .vjs-control-bar .vjs-cast-button{order:950 !important}
  .vjs-control-bar .vjs-viewmode-toggle{order:940 !important}

  /* إخفاء الشريط والذهاب للبث المباشر */
  .vjs-progress-control, .vjs-seek-to-live-control{display:none !important}
  .vjs-live-control{pointer-events:none;cursor:default}

  /* توحيد الشكل لكل المشغّلات */
  #delay-video.video-js, .delay-player .video-js{
    border-radius:var(--card-radius); box-shadow:var(--card-shadow);
  }

  /* إعدادات خاصة بـ iOS */
  @supports (-webkit-touch-callout: none) {
    .video-js {
      -webkit-tap-highlight-color: transparent;
    }
    
    .vjs-control-bar {
      padding-bottom: env(safe-area-inset-bottom, 6px);
    }
  }

  /* منع النوم على iOS */
  .video-js video {
    -webkit-playsinline: true;
    object-fit: contain;
  }
</style>

</head>
<body>
<div class="wrap">
  <div class="player-box">
    <video id="my-video"
      class="video-js vjs-big-play-centered"
      controls preload="none"
      playsinline webkit-playsinline x5-playsinline
      x-webkit-airplay="allow"
      autoplay="false"
      crossorigin="anonymous"
      poster="<?= htmlspecialchars($poster_url) ?>"></video>
  </div>
</div>

<!-- Wake Lock API لمنع النوم -->
<script>
let wakeLock = null;
async function requestWakeLock() {
  try {
    if ('wakeLock' in navigator) {
      wakeLock = await navigator.wakeLock.request('screen');
    }
  } catch (err) {
    console.log('Wake Lock failed:', err);
  }
}

document.addEventListener('visibilitychange', () => {
  if (wakeLock !== null && document.visibilityState === 'visible') {
    requestWakeLock();
  }
});
</script>

<script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>
<script>
(function(){
    const CHANNEL_ID   = <?= (int)$id ?>;
    const CHANNEL_NAME = <?= json_encode($name, JSON_UNESCAPED_UNICODE) ?>;
    const STREAM_URL   = <?= json_encode($url) ?>;
    const AUDIO_URL    = <?= json_encode($audio_url) ?>;
    let audioFallbackTried = false;

    function detectStreamType(url) {
        if (!url) return 'application/x-mpegURL';
        if (/\.m3u8($|\?)/i.test(url)) return 'application/x-mpegURL';
        if (/\.(mp4|m4v)($|\?)/i.test(url)) return 'video/mp4';
        if (/\.(ts|mpegts)($|\?)/i.test(url)) return 'video/mp2t';
        // روابط Xtream بدون امتداد غالباً TS مباشر
        if (!/\.\w+($|\?)/.test(url) && /\/\d+(\/|$|\?)/.test(url)) return 'video/mp2t';
        return 'application/x-mpegURL';
    }

    const STREAM_TYPE_CANDIDATES = Array.from(new Set([
        detectStreamType(STREAM_URL),
        'application/x-mpegURL',
        'video/mp2t',
        'video/mp4'
    ]));
    let streamTypeIndex = 0;

    function getCurrentStreamType() {
        return STREAM_TYPE_CANDIDATES[Math.min(streamTypeIndex, STREAM_TYPE_CANDIDATES.length - 1)];
    }

    function setStreamSource(url, cacheKey) {
        const sourceUrl = cacheKey
            ? (url + (url.includes('?') ? '&' : '?') + cacheKey + '=' + Date.now())
            : url;
        const sourceType = getCurrentStreamType();
        console.log('Setting stream source:', { sourceUrl, sourceType, streamTypeIndex });
        player.src({ src: sourceUrl, type: sourceType });
    }

    // تحسين كشف Safari و iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    const isSafariAny = isIOS || isSafari;
    
    // كشف الشبكة البطيئة
    const isSlowConnection = navigator.connection && 
                           (navigator.connection.effectiveType === 'slow-2g' || 
                            navigator.connection.effectiveType === '2g' ||
                            navigator.connection.saveData);

    console.log('Device Info:', { isIOS, isSafari, isSafariAny, isSlowConnection });

    const Button = videojs.getComponent('Button');
    class GoogleCastButton extends Button { 
        constructor(player, options) { 
            super(player, options); 
            this.controlText('Cast'); 
            this.addClass('vjs-cast-button'); 
            const icon = videojs.dom.createEl('span', { className: 'vjs-icon-cast' }); 
            this.el().appendChild(icon); 
            this.addClass('not-available'); 
        } 
    } 
    videojs.registerComponent('GoogleCastButton', GoogleCastButton);
    
    class ViewModeToggle extends Button { 
        constructor(player, options) { 
            super(player, options); 
            this.modes = ['fit', 'stretch', 'crop']; 
            this.index = 0; 
            this.controlText('View: Fit'); 
            this.addClass('vjs-viewmode-toggle'); 
            this.iconEl = videojs.dom.createEl('span', { className: 'vjs-icon-viewmode' }); 
            this.labelEl = videojs.dom.createEl('span', { className: 'vmode-label', innerHTML: 'Fit' }); 
            this.el().appendChild(this.iconEl); 
            this.el().appendChild(this.labelEl); 
            this.updateUI(); 
            this.on(['tap', 'click'], this.onClick); 
            player.el().classList.add('vmode-fit'); 
        } 
        onClick(e) { 
            e.preventDefault(); 
            this.index = (this.index + 1) % this.modes.length; 
            this.applyMode(); 
        } 
        applyMode() { 
            const mode = this.modes[this.index]; 
            const root = this.player().el(); 
            root.classList.remove('vmode-fit', 'vmode-stretch', 'vmode-crop'); 
            root.classList.add('vmode-' + mode); 
            this.updateUI(); 
        } 
        updateUI() { 
            const mode = this.modes[this.index]; 
            this.el().classList.remove('mode-fit', 'mode-stretch', 'mode-crop'); 
            this.el().classList.add('mode-' + mode); 
            const titles = { fit: 'Fit', stretch: 'Stretch', crop: 'Crop' }; 
            const label = titles[mode] || 'Fit'; 
            this.controlText('View: ' + label); 
            if (this.labelEl) this.labelEl.textContent = label; 
        } 
    } 
    videojs.registerComponent('ViewModeToggle', ViewModeToggle);

    // إعدادات محسنة للـ iOS
    const playerOptions = {
        autoplay: false, // منع التشغيل التلقائي على iOS
        muted: false,
        preload: 'none', // لا نحمل البيانات مسبقاً
        liveui: true, 
        controls: true, 
        fluid: true, 
        responsive: true,
        inactivityTimeout: isIOS ? 5000 : 3000, // مهلة أطول على iOS
        
        // إعدادات خاصة بـ HLS
        html5: {
            vhs: {
                overrideNative: !isSafariAny, // نستخدم Native HLS على Safari
                handleManifestRedirects: true,
                enableLowInitialPlaylist: true,
                liveSyncDurationCount: isIOS ? 3 : 7, // أقل على iOS
                liveMaxLatencyDurationCount: isIOS ? 10 : 20,
                maxPlaylistRetries: 3,
                playlistExclusionDuration: 10,
                useBandwidthFromLocalStorage: false,
                useDevicePixelRatio: true,
                smoothQualityChange: true,
                // إعدادات Buffer خاصة بـ iOS
                experimentalBufferBasedABR: !isIOS,
                experimentalLLHLS: false,
                cacheEncryptionKeys: false
            },
            // إعدادات Native تحسينات
            nativeAudioTracks: isSafariAny,
            nativeVideoTracks: isSafariAny,
            nativeTextTracks: isSafariAny
        },
        
        controlBar:{
            children:[
                'playToggle',
                'volumePanel',
                'liveDisplay',
                'customControlSpacer',
                'ViewModeToggle',
                'GoogleCastButton',
                'pictureInPictureToggle',
                'fullscreenToggle'
            ]
        }
    };

    const player = videojs('my-video', playerOptions);

    // --- شاشة الانتظار مع النصائح ---
    (function injectSpinKeyframes(){
      if (document.getElementById('spin-keyframes')) return;
      const st = document.createElement('style'); 
      st.id = 'spin-keyframes'; 
      st.textContent = '@keyframes vspin { to { transform: rotate(360deg); } }'; 
      document.head.appendChild(st);
    })();
    
    const overlay = document.createElement('div');
    overlay.id = 'startup-overlay';
    overlay.setAttribute('style', [
        'position:absolute','inset:0','display:flex','flex-direction:column',
        'align-items:center','justify-content:center','background:rgba(0,0,0,.78)',
        'color:#fff','z-index:9999','text-align:center','gap:10px',
        'font-family:inherit','pointer-events:none'
    ].join(';'));
    
    overlay.innerHTML = `
        <div id="overlay-title" style="font-size:18px;font-weight:700">جاري تهيئة البث…</div>
        <div style="width:44px;height:44px;border:4px solid #fff;border-top-color:#ff7a1a;border-radius:50%;animation:vspin 1s linear infinite"></div>
        <div id="tip-line" style="font-size:13px;opacity:.9">نجهّز مسار البث…</div>
    `;
    
    player.el().appendChild(overlay);
    
    const tips = [
        'نصيحة: فعّل ملء الشاشة للمشاهدة أوضح.',
        'نصيحة: تأكد من استقرار الاتصال بالإنترنت.',
        'نصيحة: إذا حدث توقف، سنعيد الاتصال تلقائيًا.',
        'نصيحة: أبقِ التطبيق مفتوحاً لتجنب انقطاع البث.'
    ];
    
    let tipIdx = 0;
    const tipEl = overlay.querySelector('#tip-line');
    const tipInterval = setInterval(() => { 
        tipIdx = (tipIdx + 1) % tips.length; 
        if (tipEl) tipEl.textContent = tips[tipIdx]; 
    }, 4000);

    function showOverlay(visible, isReconnect = false){ 
        if (!overlay) return; 
        const titleEl = overlay.querySelector('#overlay-title');
        if (titleEl) {
            if(isReconnect) {
                titleEl.textContent = 'جارٍ إعادة الاتصال...';
                if (tipEl) tipEl.style.display = 'none';
            } else {
                titleEl.textContent = 'جاري تهيئة البث…';
                if (tipEl) tipEl.style.display = 'block';
            }
        }
        overlay.style.display = visible ? 'flex' : 'none';
    }
    
    showOverlay(true);

    // مراقب الشبكة المحسن للـ iOS
    const NetworkWatchdog = {
        stallThresholdMs: isIOS ? 20000 : 12000, // مهلة أطول على iOS
        waitingTimeoutMs: isIOS ? 25000 : 15000,
        maxRetries: 5,
        retryCount: 0,
        lastTimeUpdate: Date.now(),
        lastCurrentTime: 0,
        stallTimer: null,
        healthCheckTimer: null,
        isRecovering: false,
        consecutiveFailures: 0,

        markProgress() {
            try {
                const currentTime = player.currentTime();
                if (!isNaN(currentTime) && currentTime !== this.lastCurrentTime) {
                    this.lastCurrentTime = currentTime;
                    this.lastTimeUpdate = Date.now();
                    // إذا تحسن التقدم، نقلل العدادات
                    this.consecutiveFailures = 0;
                    if (this.retryCount > 0) {
                        this.retryCount = Math.max(0, this.retryCount - 1);
                    }
                }
            } catch (e) {
                console.error('Error in markProgress:', e);
            }
        },

        // محاولة إصلاح بسيط (خاص بـ iOS)
        softRecovery() {
            if (this.isRecovering) return;
            this.isRecovering = true;
            
            console.log('iOS Soft recovery attempt...');
            
            try {
                // للـ iOS: توقف ثم تشغيل مع تأخير أطول
                player.pause();
                
                setTimeout(() => {
                    try {
                        // محاولة تحديث المصدر قبل التشغيل
                        if (isIOS && this.retryCount > 2) {
                            setStreamSource(STREAM_URL, 't');
                        }
                        
                        const playPromise = player.play();
                        if (playPromise && playPromise.catch) {
                            playPromise.catch(e => {
                                console.log('iOS play promise failed:', e);
                                // في حالة فشل التشغيل، نحاول إعادة التحميل
                                this.hardReload();
                            });
                        }
                    } catch (e) {
                        console.error('iOS soft recovery failed:', e);
                        this.hardReload();
                    }
                    
                    setTimeout(() => {
                        this.isRecovering = false;
                    }, 2000);
                }, isIOS ? 2000 : 1000); // تأخير أطول على iOS
                
            } catch (e) {
                console.error('iOS soft recovery setup failed:', e);
                this.isRecovering = false;
                this.hardReload();
            }
        },

        // إعادة تحميل صعبة مع تحسينات iOS
        hardReload() {
            this.retryCount++;
            this.consecutiveFailures++;
            
            if (this.retryCount > this.maxRetries) {
                console.log('Max retries reached, giving up...');
                showOverlay(true, true);
                return;
            }

            const backoffSteps = isIOS ? [2, 5, 10, 15, 25] : [1, 3, 5, 10, 15];
            const waitSec = backoffSteps[Math.min(this.retryCount - 1, backoffSteps.length - 1)];
            
            console.log(`iOS Hard reload attempt ${this.retryCount}, waiting ${waitSec}s...`);
            
            // إظهار حالة إعادة الاتصال بعد تأكد من المشكلة
            setTimeout(() => {
                if (this.shouldShowReconnectOverlay()) {
                    showOverlay(true, true);
                }
            }, 1000);

            setTimeout(() => {
                try {
                    // تنظيف المشغل
                    player.pause();
                    player.reset();
                    
                    // إعادة تعيين المصدر مع timestamp لتجنب cache
                    setStreamSource(STREAM_URL, 'r');
                    
                    player.addClass('vjs-has-started');
                    
                    // للـ iOS: انتظار إضافي قبل التشغيل
                    setTimeout(() => {
                        const playPromise = player.play();
                        if (playPromise && playPromise.catch) {
                            playPromise.catch(e => {
                                console.log('Hard reload play failed:', e);
                                // محاولة أخرى بعد فترة
                                setTimeout(() => this.hardReload(), 3000);
                            });
                        }
                        
                        // تحديث Wake Lock
                        if (isIOS) {
                            requestWakeLock();
                        }
                    }, isIOS ? 1500 : 500);
                    
                } catch (e) {
                    console.error('Hard reload failed:', e);
                    setTimeout(() => this.hardReload(), 5000);
                }
            }, waitSec * 1000);
        },

        shouldShowReconnectOverlay() {
            try {
                const readyState = player.readyState();
                const hasError = !!player.error();
                const noProgress = Date.now() - this.lastTimeUpdate > this.stallThresholdMs;
                return hasError || readyState === 0 || (noProgress && !player.paused());
            } catch (e) {
                return true;
            }
        },

        onError() {
            this.consecutiveFailures++;
            console.log('iOS Player error detected, consecutive failures:', this.consecutiveFailures);
            
            try {
                player.error(null);
                player.removeClass('vjs-error');
                player.addClass('vjs-has-started');
                player.controls(true);
            } catch (e) {
                console.error('Error cleanup failed:', e);
            }
            
            // للـ iOS: انتظار أطول قبل إعادة المحاولة
            if (isIOS && this.consecutiveFailures > 3) {
                setTimeout(() => this.hardReload(), 5000);
            } else {
                this.hardReload();
            }
        },

        startHealthCheck() {
            if (this.healthCheckTimer) return;
            
            // فحص دوري أقل تكراراً على iOS
            const checkInterval = isIOS ? 5000 : 3000;
            
            this.healthCheckTimer = setInterval(() => {
                this.markProgress();
                
                if (player.paused()) return;
                
                const noProgressMs = Date.now() - this.lastTimeUpdate;
                const isStalled = noProgressMs > this.stallThresholdMs;
                
                if (isStalled) {
                    console.log('iOS Health check: Stream stalled for', noProgressMs, 'ms');
                    
                    // على iOS: محاولة soft recovery أولاً
                    if (isIOS && this.consecutiveFailures < 2) {
                        this.softRecovery();
                    } else {
                        this.hardReload();
                    }
                }
            }, checkInterval);
        },

        stopHealthCheck() {
            if (this.healthCheckTimer) {
                clearInterval(this.healthCheckTimer);
                this.healthCheckTimer = null;
            }
        },

        clearTimers() {
            this.stopHealthCheck();
            if (this.stallTimer) {
                clearTimeout(this.stallTimer);
                this.stallTimer = null;
            }
        },

        resetCounters() {
            this.retryCount = 0;
            this.consecutiveFailures = 0;
            this.lastTimeUpdate = Date.now();
        }
    };

    // إعداد التأخير الأولي (محسن للـ iOS)
    function setInitialDelay() {
        if (isSafariAny) return; // Safari يدير هذا تلقائياً
        
        player.one('loadedmetadata', function() {
            try {
                const seekable = player.seekable();
                if (seekable && seekable.length > 0) {
                    const dvrWindow = seekable.end(0) - seekable.start(0);
                    if (dvrWindow > 50) { 
                        const delaySeconds = isSlowConnection ? 90 : 60; // تأخير أكثر للشبكة البطيئة
                        const startTime = seekable.end(0) - delaySeconds;
                        if (startTime > seekable.start(0)) { 
                            player.currentTime(startTime);
                            console.log('Initial delay set to:', delaySeconds, 'seconds');
                        } else { 
                            player.currentTime(seekable.start(0)); 
                        }
                    }
                }
            } catch(e) { 
                console.error("Error setting initial delay:", e); 
            }
        });
    }

    // تطبيق التأخير المستمر (محسن للـ iOS)
    function enforceDelay() {
        if (isSafariAny || player.paused()) return;
        
        try {
            const seekable = player.seekable();
            if (seekable && seekable.length > 0) {
                const liveEdge = seekable.end(0);
                const currentTime = player.currentTime();
                const currentDelay = liveEdge - currentTime;
                const targetDelay = isSlowConnection ? 90 : 60;
                
                if (currentDelay < (targetDelay - 10)) {
                    let newTime = currentTime - 15;
                    const bufferStart = seekable.start(0);
                    if (newTime < bufferStart) { 
                        newTime = bufferStart; 
                    }
                    player.currentTime(newTime);
                    console.log('Delay enforced, moved back to:', newTime);
                }
            }
        } catch(e) { 
            console.error("Error in delay enforcer:", e); 
        }
    }

    // تشغيل المصدر مع تحسينات iOS
    function setSrcAndPlay() {
        setStreamSource(STREAM_URL, 'init');

        // إعدادات خاصة بـ iOS
        if (isIOS) {
            try {
                const tech = player.tech(true);
                if (tech && tech.el()) {
                    // تأكد من إعدادات playsinline
                    tech.el().setAttribute('playsinline', '');
                    tech.el().setAttribute('webkit-playsinline', '');
                    tech.el().setAttribute('x5-playsinline', '');
                }
            } catch(e) {
                console.log('iOS tech setup warning:', e);
            }
        }

        player.addClass('vjs-has-started');
        
        player.ready(() => {
            console.log('Player ready, attempting play...');
            const playPromise = player.play();
            if (playPromise && typeof playPromise.catch === 'function') { 
                playPromise.catch(e => {
                    console.log('Initial play failed:', e);
                    // على iOS قد نحتاج تفاعل المستخدم
                    if (isIOS) {
                        showOverlay(true, false);
                    }
                });
            }
        });
    }
    
    // مستمعي الأحداث المحسنة للـ iOS
    let loadStartAt = 0;
    let initialDelayApplied = false;
    let isFirstPlay = true;

    player.on('loadstart', () => {
        loadStartAt = Date.now();
        console.log('Load start at:', new Date(loadStartAt));
        showOverlay(true, false);
        NetworkWatchdog.resetCounters();
    });

    player.on('canplay', () => {
        console.log('Can play - hiding overlay');
        showOverlay(false);
        // بدء مراقب الصحة
        NetworkWatchdog.startHealthCheck();
    });

    player.on('timeupdate', () => {
        NetworkWatchdog.markProgress();
        
        // إخفاء الأوفرلاي عند أي تحديث وقت فعلي
        if (overlay && overlay.style.display !== 'none') {
            showOverlay(false);
        }
    });

    player.on('playing', () => {
        console.log('Playing event - stream started successfully');
        showOverlay(false);
        NetworkWatchdog.resetCounters();
        
        // تطبيق التأخير الأولي مرة واحدة فقط
        if (!initialDelayApplied && !isSafariAny) {
            setTimeout(() => {
                try {
                    const seekable = player.seekable();
                    if (seekable && seekable.length > 0) {
                        const dvrWindow = seekable.end(0) - seekable.start(0);
                        if (dvrWindow > 50) {
                            const delaySeconds = isSlowConnection ? 90 : 60;
                            const startTime = seekable.end(0) - delaySeconds;
                            player.currentTime(Math.max(startTime, seekable.start(0)));
                            console.log('Applied playing delay:', delaySeconds, 'seconds');
                        }
                    }
                } catch(e) { 
                    console.error("Error setting playing delay:", e); 
                }
                initialDelayApplied = true;
            }, 2000);
        }

        // طلب Wake Lock للـ iOS
        if (isIOS && isFirstPlay) {
            requestWakeLock();
            isFirstPlay = false;
        }
    });

    // أحداث المشاكل
    player.on('waiting', () => {
        console.log('Player waiting/buffering...');
        // لا نعرض الأوفرلاي فوراً، ننتظر قليلاً
        NetworkWatchdog.stallTimer = setTimeout(() => {
            if (NetworkWatchdog.shouldShowReconnectOverlay()) {
                console.log('Long waiting detected, showing overlay');
                showOverlay(true, true);
                NetworkWatchdog.softRecovery();
            }
        }, NetworkWatchdog.waitingTimeoutMs);
    });

    player.on('stalled', () => {
        console.log('Player stalled...');
        // مثل waiting، انتظار قبل إظهار المشكلة
        if (!NetworkWatchdog.stallTimer) {
            NetworkWatchdog.stallTimer = setTimeout(() => {
                console.log('Stream stalled for too long');
                NetworkWatchdog.softRecovery();
            }, NetworkWatchdog.waitingTimeoutMs);
        }
    });

    player.on('ended', () => {
        console.log('Stream ended');
        showOverlay(true, true);
        NetworkWatchdog.hardReload();
    });

    player.on('error', (e) => {
        console.log('Player error:', e, player.error());

        if (streamTypeIndex < STREAM_TYPE_CANDIDATES.length - 1) {
            streamTypeIndex++;
            console.log('Retrying with alternate stream type:', getCurrentStreamType());
            showOverlay(true, true);
            player.error(null);
            setStreamSource(STREAM_URL, 'stype');
            player.play().catch(() => {
                NetworkWatchdog.onError();
            });
            return;
        }

        if (!audioFallbackTried && AUDIO_URL && STREAM_URL !== AUDIO_URL) {
            audioFallbackTried = true;
            console.log('Trying audio-only fallback stream...');
            showOverlay(true, true);
            player.error(null);
            player.src({ src: AUDIO_URL, type: detectStreamType(AUDIO_URL) });
            player.play().catch(() => {
                NetworkWatchdog.onError();
            });
            return;
        }

        showOverlay(true, true);
        NetworkWatchdog.onError();
    });

    // إدارة الشاشة الكاملة على الموبايل
    const isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    if (isMobile) {
        player.on('fullscreenchange', async function() {
            try {
                if (screen.orientation && typeof screen.orientation.lock === 'function') {
                    if (player.isFullscreen()) {
                        await screen.orientation.lock('landscape');
                        console.log('Locked to landscape');
                    } else {
                        screen.orientation.unlock();
                        console.log('Unlocked orientation');
                    }
                }
            } catch (error) {
                console.log("Screen orientation lock failed:", error);
            }
        });
    }

    // تنظيف عند إغلاق الصفحة
    window.addEventListener('beforeunload', () => {
        console.log('Page unloading, cleaning up...');
        NetworkWatchdog.clearTimers();
        if (tipInterval) {
            clearInterval(tipInterval);
        }
        if (wakeLock) {
            wakeLock.release();
        }
        if (iosMasterWatchdogInterval) {
            clearInterval(iosMasterWatchdogInterval);
        }
        try {
            player.pause();
            player.reset();
        } catch(e) {
            console.log('Cleanup warning:', e);
        }
    });

    // معالجة تغيير visibility (مهم للـ iOS)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            console.log('Page hidden');
            NetworkWatchdog.clearTimers();
        } else {
            console.log('Page visible again');
            NetworkWatchdog.startHealthCheck();
            if (isIOS) {
                requestWakeLock();
                // فحص حالة المشغل وإعادة تشغيله إذا لزم
                setTimeout(() => {
                    if (player.paused() && !player.ended()) {
                        console.log('Resuming after visibility change');
                        player.play().catch(e => console.log('Resume play failed:', e));
                    }
                }, 1000);
            }
        }
    });

    // مراقب إضافي للـ iOS للتأكد من استمرارية التشغيل
    let iosMasterWatchdogInterval = null;
    if (isIOS) {
        iosMasterWatchdogInterval = setInterval(() => {
            if (!player.paused()) {
                const noProgressMs = Date.now() - NetworkWatchdog.lastTimeUpdate;
                const readyState = player.readyState();
                
                // إذا لم يكن هناك تقدم لفترة طويلة والمشغل ليس في حالة buffering عادية
                if (noProgressMs > NetworkWatchdog.stallThresholdMs && readyState <= 2) {
                    console.log('iOS Master watchdog: Detected long stall, attempting recovery');
                    NetworkWatchdog.hardReload();
                }
            }
        }, 15000); // فحص كل 15 ثانية
    }

    // بدء تشغيل التطبيق
    console.log('Starting iOS-optimized player...');
    setSrcAndPlay();
    setInitialDelay();
    
    // بدء تطبيق التأخير المستمر (للشبكات غير Safari)
    if (!isSafariAny) {
        setInterval(enforceDelay, 90000); // كل دقيقة ونصف
    }

    console.log('Player initialization completed');
})();
</script>
</body>
</html>
