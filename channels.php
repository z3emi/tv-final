<?php
// 1. استخدام ملف الإعدادات المركزي
require_once 'config.php';

// 2. التحقق من تسجيل الدخول (config.php يبدأ الجلسة)
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// 3. استخدام متغير الاتصال الموحد $mysqli من config.php
$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")->fetch_assoc()['setting_value'] ?? 'Admin Panel';

// 4. الاستعلام المحسّن لعد المشاهدين وتحسين سرعة تحميل الصفحة
$channels_result = $mysqli->query("
    SELECT
        c.*,
        cat.name AS category_name,
        COUNT(v.viewer_uid) AS view_count
    FROM
        channels c
    LEFT JOIN
        categories cat ON c.category_id = cat.id
    LEFT JOIN
        viewers v ON c.id = v.channel_id AND v.last_active > NOW() - INTERVAL 20 SECOND
    GROUP BY
        c.id
    ORDER BY
        c.id DESC
");

$categories_result = $mysqli->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة القنوات - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .channel-list-item {
            border: 1px solid #dee2e6;
            border-radius: 0.75rem;
            transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .channel-list-item:hover {
            background-color: #f8f9fa;
            box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.05);
        }
        .channel-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
			background-color: #212529;
            border-radius: 0.5rem;
			padding: 5px;
			box-sizing: border-box;
        }
        .stats-group {
            border-left: 1px solid #dee2e6;
        }
        @media (max-width: 767px) {
            .stats-group {
                border-left: none;
                border-top: 1px solid #dee2e6;
                border-bottom: 1px solid #dee2e6;
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
                margin-top: 1rem;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div id="content">
        <h1 class="h2 mb-4 fw-bold d-flex align-items-center"><i class="bi bi-broadcast-pin me-3"></i>إدارة القنوات</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                <a href="channel_add.php" class="btn btn-success"><i class="bi bi-plus-circle-fill me-2"></i>إضافة قناة</a>
                <div class="ms-auto d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control" placeholder="ابحث عن قناة...">
                    <select id="categoryFilter" class="form-select" style="width: 200px;">
                        <option value="">كل التصنيفات</option>
                        <?php while($cat = $categories_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="vstack gap-3" id="channels-list">
            <?php if($channels_result && $channels_result->num_rows > 0): ?>
                <?php while($c = $channels_result->fetch_assoc()): ?>
                    <div class="channel-row p-3 channel-list-item" data-name="<?= htmlspecialchars(strtolower($c['name'])) ?>" data-category="<?= htmlspecialchars($c['category_name'] ?? '') ?>">
                        <div class="row align-items-center">
                            <div class="col-md-5 d-flex align-items-center">
                                <div class="id-display text-center">
                                    <small class="text-muted d-block">ID</small>
                                    <strong class="fs-5 text-primary"><?= $c['id'] ?></strong>
                                </div>
                                <div class="vr mx-3"></div>
                                <img src="stream/stream/uploads/<?= htmlspecialchars($c['image_url'] ?? 'default.png') ?>" class="channel-img" alt="" onerror="this.onerror=null;this.src='https://placehold.co/60/eee/313435?text=Img';">
                                <div class="ms-3 flex-grow-1" style="min-width: 0;">
                                    <strong class="d-block text-truncate"><?= htmlspecialchars($c['name']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($c['category_name'] ?? 'N/A') ?></small>
                                </div>
                            </div>
                            <div class="col-md-3 stats-group">
                                <div class="d-flex justify-content-around text-center">
                                    <div id="views-<?= $c['id'] ?>">
                                        <small class="text-muted d-block">المشاهدون</small>
                                        <strong class="fs-5"><?= number_format($c['view_count']) ?></strong>
                                    </div>
                                    <div id="traffic-<?= $c['id'] ?>">
                                        <small class="text-muted d-block">الترافيك</small>
                                        <div class="spinner-border spinner-border-sm mt-1" role="status"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <span class="badge fs-6 rounded-pill <?= $c['is_active'] ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' ?>">
                                        <?= $c['is_active'] ? 'مفعلة' : 'معطلة' ?>
                                    </span>
                                    <div class="vr mx-2"></div>
                                    <a href="channel_log.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary" title="سجل القناة"><i class="bi bi-clipboard-data-fill"></i></a>
                                    <a href="channel_edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="تعديل"><i class="bi bi-pencil-square"></i></a>
                                    <a href="channel_delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" title="حذف" onclick="return confirm('هل أنت متأكد؟')"><i class="bi bi-trash3-fill"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center p-5">
                    <i class="bi bi-tv-fill text-muted" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">لا توجد قنوات مضافة بعد.</p>
                </div>
            <?php endif; ?>
            <div id="no-results-row" class="text-center p-5 d-none">
                <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-0">لا توجد نتائج تطابق بحثك.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateStats() {
        fetch('api_channel_stats.php')
            .then(response => response.ok ? response.json() : Promise.reject('Network response was not ok'))
            .then(data => {
                const statsData = data.stats;
                for (const channelId in statsData) {
                    const stats = statsData[channelId];
                    const viewsCell = document.getElementById(`views-${channelId}`);
                    const trafficCell = document.getElementById(`traffic-${channelId}`);
                    if (viewsCell) viewsCell.innerHTML = `<small class="text-muted d-block">المشاهدون</small><strong class="fs-5">${new Intl.NumberFormat().format(stats.view_count)}</strong>`;
                    if (trafficCell) trafficCell.innerHTML = `<small class="text-muted d-block">الترافيك</small><strong class="fs-5">${stats.traffic}</strong>`;
                }
            })
            .catch(error => console.error('Error fetching stats:', error));
    }
    
    // يمكنك تعديل سرعة التحديث هنا (مثلاً 3000 لـ 3 ثوانٍ)
    setInterval(updateStats, 5000);

    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const channelRows = document.querySelectorAll('.channel-row');
    const noResultsRow = document.getElementById('no-results-row');

    function filterChannels() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value;
        let visibleCount = 0;

        channelRows.forEach(item => {
            const name = item.dataset.name;
            const category = item.dataset.category;
            const nameMatch = name.includes(searchTerm);
            const categoryMatch = selectedCategory === "" || category === selectedCategory;

            if (nameMatch && categoryMatch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        noResultsRow.classList.toggle('d-none', visibleCount > 0);
    }

    searchInput.addEventListener('input', filterChannels);
    categoryFilter.addEventListener('change', filterChannels);
});
</script>

</body>
</html>