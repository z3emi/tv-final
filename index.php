<?php
// index.php
require_once 'config.php';

$mysqli = new mysqli("localhost", "root", "", "stream_db");
if ($mysqli->connect_error) { http_response_code(500); echo "Database connection failed."; exit; }

$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")
    ->fetch_assoc()['setting_value'] ?? 'Stream System';

$channels_result = $mysqli->query("
    SELECT c.*, cat.name AS category_name
    FROM channels c
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.is_active = 1
    ORDER BY cat.name, c.name ASC
");

$categories_result = $mysqli->query("
    SELECT DISTINCT cat.id, cat.name
    FROM categories cat
    INNER JOIN channels c ON cat.id = c.category_id
    WHERE c.is_active = 1
    ORDER BY cat.name ASC
");

$server_ip = $_SERVER['SERVER_ADDR'] ?? '';
if ($server_ip === '::1' || $server_ip === '127.0.0.1' || $server_ip === '') {
    $server_ip = gethostbyname(gethostname());
}

function build_stream_url(array $c): string {
    if (!empty($c['is_direct']) && (int)$c['is_direct'] === 1 && !empty($c['source_url'])) {
        return $c['source_url'];
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$scheme}://{$host}/live/channel_{$c['id']}/stream.m3u8";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($website_title) ?></title>
<link rel="icon" type="image/png" href="favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
<style>
    :root {
        --bg-color:#101418; --card-color:#1a2027; --border-color:#2c3a47;
        --accent-color:#E86824; --text-color:#e0e0e0; --text-muted:#888;
    }
    body{
        background-color:var(--bg-color);
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%231a2027' fill-opacity='0.4'%3E%3Cpath d='M0 38.59l2.83-2.83 1.41 1.41L1.41 40H0v-1.41zM0 1.4l2.83 2.83 1.41-1.41L1.41 0H0v1.41zM38.59 40l-2.83-2.83 1.41-1.41L40 38.59V40h-1.41zM40 1.41l-2.83 2.83-1.41-1.41L38.59 0H40v1.41zM20 18.6l2.83-2.83 1.41 1.41L21.41 20l2.83 2.83-1.41 1.41L20 21.41l-2.83 2.83-1.41-1.41L18.59 20l-2.83-2.83 1.41-1.41L20 18.59z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        color:var(--text-color); font-family:'Cairo',sans-serif;
    }
    .header{background:var(--card-color);border-bottom:1px solid var(--border-color)}
    .category-nav{background:rgba(26,32,39,.8);backdrop-filter:blur(10px);padding:.75rem 0;position:sticky;top:0;z-index:1020;border-bottom:1px solid var(--border-color)}
    .category-nav .nav-link{color:var(--text-muted);padding:.5rem 1.25rem;font-weight:700;transition:.2s;border-radius:50px;white-space:nowrap}
    .category-nav .nav-link:hover{color:#fff}
    .category-nav .nav-link.active{color:#000;background:var(--accent-color);box-shadow:0 0 15px rgba(255,140,0,.5)}
    .channel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1.75rem;padding:2rem}
    .channel-card{background:var(--card-color);border-radius:12px;text-decoration:none;color:var(--text-color);text-align:center;transition:.25s;border:1px solid var(--border-color);cursor:pointer;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.3);position:relative}
    .channel-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,.5),0 0 20px rgba(255,140,0,.3);border-color:var(--accent-color)}
    .live-indicator{position:absolute;top:10px;left:10px;background:#e74c3c;color:#fff;padding:1px 6px;border-radius:4px;font-size:.6rem;font-weight:700;text-transform:uppercase;box-shadow:0 0 10px #e74c3c}
    .live-indicator::before{content:'';position:absolute;right:-4px;top:50%;transform:translateY(-50%);width:6px;height:6px;background:#e74c3c;border-radius:50%;animation:pulse 1.5s infinite}
    @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(231,76,60,.7)}70%{box-shadow:0 0 0 8px rgba(231,76,60,0)}100%{box-shadow:0 0 0 0 rgba(231,76,60,0)}}
    .channel-logo-container{height:100px;display:flex;align-items:center;justify-content:center;padding:1rem}
    .channel-logo{max-width:100%;max-height:80px;object-fit:contain}
    .channel-name{font-size:1rem;font-weight:700;padding:1rem .5rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;background:rgba(0,0,0,.2);border-top:1px solid var(--border-color)}
    
    /* Modal & iframe Styles */
    .modal-content{background:var(--card-color);border:1px solid var(--border-color)}
    .modal-header{border-bottom-color:var(--border-color)}
    .modal-body{padding:0;background:#000;overflow:hidden}
    #playerFrame{width:100%;aspect-ratio:16/9;height:auto;max-height:85vh;border:0; display:block;}
    
    @media (min-width: 992px){
        #playerModal .modal-dialog { max-width: 60%; }
    }
    @media (max-width: 991.98px){
        .channel-grid{grid-template-columns:repeat(2,1fr);gap:1rem;padding:1rem}
        .channel-logo-container{height:80px}
        .channel-name{font-size:.8rem;padding:.75rem .5rem}
        .header h4{font-size:1.2rem}
        
        /* --- ▼▼▼▼▼▼▼▼▼▼▼ الكود المضاف لتوسيط المشغل على الهاتف ▼▼▼▼▼▼▼▼▼▼▼ --- */
        #playerModal .modal-dialog {
            width: auto;
            max-width: 95%; /* اجعل أقصى عرض للنافذة 95% من عرض الشاشة */
            margin: 1rem auto; /* توسيط أفقي مع هامش علوي وسفلي */
        }
        /* --- ▲▲▲▲▲▲▲▲▲▲▲ نهاية الإضافة ▲▲▲▲▲▲▲▲▲▲▲ --- */
    }
</style>
</head>
<body>
<div class="header d-flex justify-content-between align-items-center p-3">
    <h4 class="mb-0">📺 القنوات</h4>
</div>

<nav class="category-nav">
    <div class="container-fluid">
        <ul class="nav nav-pills justify-content-start flex-nowrap overflow-auto px-2">
            <li class="nav-item mx-1">
                <a class="nav-link" href="schedule.php" style="color: #ffc107;">
                    <i class="bi bi-calendar-event-fill me-1"></i>جدول المباريات
                </a>
            </li>
            <li class="nav-item mx-1"><a class="nav-link active" href="#" data-category="all">الكل</a></li>
            <?php while($cat = $categories_result->fetch_assoc()): ?>
                <li class="nav-item mx-1">
                    <a class="nav-link" href="#" data-category="<?= htmlspecialchars($cat['name']) ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</nav>

<div class="container-fluid">
    <div class="channel-grid">
        <?php while($c = $channels_result->fetch_assoc()): ?>
            <?php
                $stream_url = build_stream_url($c);
                $img = $c['image_url']
                    ? 'stream/stream/uploads/' . str_replace('\\', '/', $c['image_url'])
                    : 'https://via.placeholder.com/120x80/2a2a2a/6c757d?text=No+Logo';
            ?>
            <div class="channel-card open-player"
                 data-id="<?= (int)$c['id'] ?>"
                 data-name="<?= htmlspecialchars($c['name']) ?>"
                 data-url="<?= htmlspecialchars($stream_url) ?>"
                 data-category="<?= htmlspecialchars($c['category_name']) ?>">
                <div class="live-indicator">LIVE</div>
                <div class="channel-logo-container">
                    <img src="<?= htmlspecialchars($img) ?>" class="channel-logo" alt="<?= htmlspecialchars($c['name']) ?>" loading="lazy">
                </div>
                <div class="channel-name"><?= htmlspecialchars($c['name']) ?></div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="playerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="playerModalLabel">...</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <iframe id="playerFrame" src="" allow="autoplay; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
    // فلترة التصنيفات
    document.querySelectorAll('.category-nav .nav-link').forEach(link=>{
        if(link.getAttribute('href') === 'schedule.php') { return; }

        link.addEventListener('click', function(e){
            e.preventDefault();
            const current = document.querySelector('.category-nav .nav-link.active');
            if(current) current.classList.remove('active');
            this.classList.add('active');

            const selected = this.dataset.category;
            document.querySelectorAll('.channel-card').forEach(card=>{
                const match = (selected === 'all') || (card.dataset.category === selected);
                card.style.display = match ? 'flex' : 'none';
            });
        });
    });

    const modal = document.getElementById('playerModal');
    const title = document.getElementById('playerModalLabel');
    const frame = document.getElementById('playerFrame');

    function resizeFrame(){
        const body = modal.querySelector('.modal-body');
        const w = body.clientWidth || frame.clientWidth;
        const h = Math.min((w * 9) / 16, window.innerHeight * 0.85);
        frame.style.height = h + 'px';
    }

    // فتح المودال وتحميل الـ iframe
    document.querySelectorAll('.open-player').forEach(card=>{
        card.addEventListener('click', ()=>{
            const id   = card.dataset.id;
            const name = card.dataset.name;
            const url  = card.dataset.url;

            title.textContent = 'أنت تشاهد الآن: ' + name;
            const src = 'player.php?id=' + encodeURIComponent(id) +
                          '&name=' + encodeURIComponent(name) +
                          '&url=' + encodeURIComponent(url);
            frame.src = src;

            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        });
    });

    modal.addEventListener('shown.bs.modal', resizeFrame);
    window.addEventListener('resize', ()=>{ if (modal.classList.contains('show')) resizeFrame(); });
    
    // تنظيف عند الإغلاق
    modal.addEventListener('hidden.bs.modal', ()=>{ frame.src = 'about:blank'; });
})();
</script>
</body>
</html>