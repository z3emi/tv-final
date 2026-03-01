<?php
require_once 'config.php';
include 'auth_check.php';
require_admin();

$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title' LIMIT 1")->fetch_assoc()['setting_value'] ?? 'Stream System';

// ===== إحصائيات عامة =====
$statsRes = $mysqli->query("SELECT COUNT(*) AS total_channels, SUM(is_active = 1) AS active_channels FROM channels");
$stats = $statsRes->fetch_assoc();
$total_channels = (int)($stats['total_channels'] ?? 0);
$active_channels = (int)($stats['active_channels'] ?? 0);

// ===== معالجة طلبات إضافة/تعديل/حذف التصنيفات =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_category' && isset($_POST['category_name'])) {
        $name = trim($_POST['category_name']);
        if (!empty($name)) {
            $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    if ($action === 'delete_category' && isset($_POST['category_id'])) {
        $cat_id = (int)$_POST['category_id'];
        $mysqli->query("DELETE FROM categories WHERE id = $cat_id");
    }
    
    if ($action === 'edit_settings' && isset($_POST['website_title'])) {
        $title = $_POST['website_title'];
        $stmt = $mysqli->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('website_title', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $title, $title);
        $stmt->execute();
        $stmt->close();
    }
}

// ===== جلب البيانات =====
$channels_result = $mysqli->query("
    SELECT c.*, cat.name AS category_name
    FROM channels c
    LEFT JOIN categories cat ON c.category_id = cat.id
    ORDER BY c.id DESC
");

$categories_result = $mysqli->query("
    SELECT cat.*, COUNT(c.id) as channel_count
    FROM categories cat
    LEFT JOIN channels c ON cat.id = c.category_id
    GROUP BY cat.id
    ORDER BY cat.name ASC
");

$current_username = $_SESSION['user'] ?? 'unknown';
$users_sql = "SELECT * FROM users";
if ($current_username !== 'admin') {
    $users_sql .= " WHERE username != 'admin'";
}
$users_sql .= " ORDER BY id ASC";
$users_result = $mysqli->query($users_sql);

$settings_result = $mysqli->query("SELECT * FROM settings");
$settings = [];
while($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .nav-tabs .nav-link { color: #666; border: none; }
        .nav-tabs .nav-link.active { background-color: #f8f9fa; color: #000; border-bottom: 3px solid #0d6efd; }
        .nav-tabs { border-bottom: 1px solid #dee2e6; }
        .perf-card .label { font-size: .85rem; color: #6c757d; }
        .perf-card .value { font-weight: 700; font-size: 1.1rem; word-break: break-word; direction: ltr; unicode-bidi: plaintext; }
        .perf-card .sub { font-size: .8rem; color: #6c757d; direction: ltr; unicode-bidi: plaintext; margin-top: .25rem; }
        .perf-card .value.muted { direction: rtl; unicode-bidi: normal; }
        .status-badge { font-size: 0.9em; padding: 0.4em 0.8em; }
        .channel-img-sm { width: 45px; height: 45px; object-fit: contain; background-color: #212529; border-radius: 0.5rem; padding: 4px; }
        .btn-action { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; }
        .table-dark th { color: #fff !important; background-color: #212529 !important; }
        .url-truncate { word-break: break-all; white-space: normal; max-width: 200px; text-align: start; }
        .tab-content { padding-top: 1.5rem; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <div class="mb-4">
            <h1 class="h2 mb-3 fw-bold"><i class="bi bi-grid-1x2-fill me-2"></i>لوحة التحكم الموحدة</h1>
        </div>

        <!-- ===== إحصائيات سريعة ===== -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-broadcast fs-2 text-primary"></i>
                        <h6 class="text-muted mt-2 mb-1">إجمالي القنوات</h6>
                        <h3 class="mb-0"><?= $total_channels ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-play-circle fs-2 text-success"></i>
                        <h6 class="text-muted mt-2 mb-1">القنوات المفعلة</h6>
                        <h3 class="mb-0"><?= $active_channels ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-bookmarks fs-2 text-warning"></i>
                        <h6 class="text-muted mt-2 mb-1">عدد التصنيفات</h6>
                        <h3 class="mb-0" id="category-count">--</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-people fs-2 text-info"></i>
                        <h6 class="text-muted mt-2 mb-1">المستخدمين</h6>
                        <h3 class="mb-0" id="user-count">--</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== معلومات السيرفر ===== -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 perf-card">
                    <div class="card-body">
                        <div class="label mb-1"><i class="bi bi-cpu me-1"></i>حمل المعالج</div>
                        <div class="value" id="server-cpu-load">--</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 perf-card">
                    <div class="card-body">
                        <div class="label mb-1"><i class="bi bi-memory me-1"></i>استخدام الذاكرة</div>
                        <div class="value" id="server-memory-usage">--</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 perf-card">
                    <div class="card-body">
                        <div class="label mb-1"><i class="bi bi-hdd-network me-1"></i>استخدام القرص</div>
                        <div class="value" id="server-disk-usage">--</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 perf-card">
                    <div class="card-body">
                        <div class="label mb-1"><i class="bi bi-arrow-down-circle me-1"></i>الاستقبال RX</div>
                        <div class="value" id="server-network-rx">--</div>
                        <div class="sub" id="server-network-rx-total">--</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 perf-card">
                    <div class="card-body">
                        <div class="label mb-1"><i class="bi bi-arrow-up-circle me-1"></i>الإرسال TX</div>
                        <div class="value" id="server-network-tx">--</div>
                        <div class="sub" id="server-network-tx-total">--</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 perf-card">
                    <div class="card-body">
                        <div class="label mb-1"><i class="bi bi-clock-history me-1"></i>مدة التشغيل</div>
                        <div class="value muted" id="server-uptime">--</div>
                        <div class="sub" id="server-network-interface">--</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== التبويبات الرئيسية ===== -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="live-tab" data-bs-toggle="tab" data-bs-target="#live" type="button" role="tab">
                    <i class="bi bi-play-circle me-2"></i>البث المباشر
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="channels-tab" data-bs-toggle="tab" data-bs-target="#channels" type="button" role="tab">
                    <i class="bi bi-tv me-2"></i>إدارة القنوات
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                    <i class="bi bi-bookmarks me-2"></i>التصنيفات
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                    <i class="bi bi-people me-2"></i>المستخدمين
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                    <i class="bi bi-gear me-2"></i>الإعدادات
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- ===== تبويب البث المباشر ===== -->
            <div class="tab-pane fade show active" id="live" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light border-0 py-3 d-flex justify-content-between">
                        <h5 class="mb-0">مراقبة البث المباشر</h5>
                        <div class="text-muted small" id="last-update-time">جاري التحديث...</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle text-center mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th class="text-end">اسم القناة</th>
                                        <th>الحالة</th>
                                        <th>وقت التشغيل</th>
                                        <th>إعادات</th>
                                        <th>رابط البث</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody id="channels-status-table">
                                    <tr><td colspan="7" class="p-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== تبويب إدارة القنوات ===== -->
            <div class="tab-pane fade" id="channels" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">قائمة القنوات</h5>
                    <div class="d-flex gap-2">
                        <input type="text" id="searchChannels" class="form-control" placeholder="ابحث عن قناة..." style="max-width: 250px;">
                        <a href="channel_add.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>إضافة</a>
                    </div>
                </div>
                <div id="channels-list">
                    <?php while($c = $channels_result->fetch_assoc()): ?>
                    <div class="card mb-2 channel-item" data-name="<?= strtolower($c['name']) ?>">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center flex-grow-1">
                                <img src="stream/stream/uploads/<?= htmlspecialchars($c['image_url'] ?? 'default.png') ?>" class="channel-img-sm me-3" onerror="this.src='https://placehold.co/45/eee/333?text=--';">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($c['name']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($c['category_name'] ?? 'بدون تصنيف') ?></small>
                                </div>
                            </div>
                            <div>
                                <span class="badge <?= $c['is_active'] ? 'bg-success' : 'bg-danger' ?> me-2">
                                    <?= $c['is_active'] ? 'مفعلة' : 'معطلة' ?>
                                </span>
                                <a href="channel_edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="channel_delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('متأكد؟')"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- ===== تبويب التصنيفات ===== -->
            <div class="tab-pane fade" id="categories" role="tabpanel">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-light border-0 py-3">
                                <h5 class="mb-0">قائمة التصنيفات</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush" id="categories-list">
                                    <?php 
                                    $categories_result->data_seek(0);
                                    while($cat = $categories_result->fetch_assoc()): 
                                    ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                                        <div>
                                            <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                            <span class="badge bg-secondary ms-2"><?= $cat['channel_count'] ?> قناة</span>
                                        </div>
                                        <div>
                                            <a href="category_edit.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary btn-action"><i class="bi bi-pencil"></i></a>
                                            <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteCategory(<?= $cat['id'] ?>)"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-light border-0 py-3">
                                <h5 class="mb-0">إضافة تصنيف</h5>
                            </div>
                            <div class="card-body">
                                <form id="addCategoryForm">
                                    <div class="mb-3">
                                        <label class="form-label">اسم التصنيف</label>
                                        <input type="text" class="form-control" id="categoryName" required placeholder="مثال: قنوات رياضية">
                                    </div>
                                    <button type="button" class="btn btn-success w-100" onclick="addCategory()">
                                        <i class="bi bi-plus-circle me-1"></i>إضافة
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== تبويب المستخدمين ===== -->
            <div class="tab-pane fade" id="users" role="tabpanel">
                <div class="mb-3">
                    <a href="user_add.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>إضافة مستخدم</a>
                </div>
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>اسم المستخدم</th>
                                    <th>الدور</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $users_result->data_seek(0);
                                while($user = $users_result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($user['username']) ?></td>
                                    <td>
                                        <span class="badge <?= $user['role'] == 'admin' ? 'bg-danger' : 'bg-info' ?>">
                                            <?= htmlspecialchars($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $user['is_active'] ? 'مفعل' : 'معطل' ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <a href="user_delete.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('متأكد؟')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===== تبويب الإعدادات ===== -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <div class="card shadow-sm border-0" style="max-width: 600px;">
                    <div class="card-header bg-light border-0 py-3">
                        <h5 class="mb-0">إعدادات الموقع</h5>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm">
                            <div class="mb-3">
                                <label class="form-label">اسم الموقع</label>
                                <input type="text" class="form-control" id="settingsTitle" value="<?= htmlspecialchars($settings['website_title'] ?? 'My Stream') ?>">
                                <div class="form-text">سيظهر في عنوان المتصفح ولوحة التحكم</div>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="saveSettings()">
                                <i class="bi bi-check-circle me-1"></i>حفظ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const statusTableBody = document.getElementById('channels-status-table');
const lastUpdateTimeElem = document.getElementById('last-update-time');

// ===== تحديث معلومات السيرفر والبث المباشر =====
async function fetchStatus() {
    try {
        const response = await fetch('api_status.php');
        if (!response.ok) throw new Error('Network error');
        const data = await response.json();

        const updateTime = new Date(data.last_update).toLocaleTimeString('ar-EG');
        lastUpdateTimeElem.innerHTML = `آخر تحديث: ${updateTime}`;

        // تحديث معلومات السيرفر
        if (data.server_stats) {
            document.getElementById('server-cpu-load').textContent = data.server_stats.cpu_load_text || '--';
            document.getElementById('server-memory-usage').textContent = data.server_stats.memory_used_text || '--';
            document.getElementById('server-disk-usage').textContent = data.server_stats.disk_used_text || '--';
            document.getElementById('server-network-rx').textContent = data.server_stats.network_rx_rate_text || '0 B/s';
            document.getElementById('server-network-tx').textContent = data.server_stats.network_tx_rate_text || '0 B/s';
            document.getElementById('server-network-rx-total').textContent = `الإجمالي: ${data.server_stats.network_rx_total_text || '--'}`;
            document.getElementById('server-network-tx-total').textContent = `الإجمالي: ${data.server_stats.network_tx_total_text || '--'}`;
            document.getElementById('server-uptime').textContent = data.server_stats.uptime_text || '--';
            document.getElementById('server-network-interface').textContent = `Interface: ${data.server_stats.network_interface || '--'}`;
        }

        // تحديث جدول البث المباشر
        statusTableBody.innerHTML = '';
        const runningCount = (data.channels || []).filter(c => c.status_code === 'running').length;

        if (data.channels && data.channels.length > 0) {
            data.channels.forEach(channel => {
                const statusBadge = {
                    running: '<span class="badge bg-success">تشغيل</span>',
                    stalled: '<span class="badge bg-danger">متوقف</span>',
                    restarting: '<span class="badge bg-warning">إعادة تشغيل</span>',
                    starting: '<span class="badge bg-info">بدء التشغيل</span>',
                    stopped: '<span class="badge bg-secondary">متوقف</span>'
                }[channel.status_code] || '<span class="badge bg-secondary">غير معروف</span>';

                const row = `
                    <tr>
                        <td>${channel.id}</td>
                        <td class="text-end">
                            <img src="stream/stream/uploads/${channel.image_url}" class="channel-img-sm me-2" onerror="this.src='https://placehold.co/40/eee/333';">
                            ${channel.name}
                        </td>
                        <td>${statusBadge}</td>
                        <td>${channel.uptime}</td>
                        <td><span class="badge bg-dark">${channel.restarts}</span></td>
                        <td><small>${channel.stream_link}</small></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="restartChannel(${channel.id})" title="إعادة تشغيل">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </td>
                    </tr>
                `;
                statusTableBody.innerHTML += row;
            });
        } else {
            statusTableBody.innerHTML = '<tr><td colspan="7" class="text-center p-4">لا توجد قنوات</td></tr>';
        }
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

// ===== إدارة التصنيفات =====
function addCategory() {
    const name = document.getElementById('categoryName').value.trim();
    if (!name) return alert('أدخل اسم التصنيف');

    const form = new FormData();
    form.append('action', 'add_category');
    form.append('category_name', name);

    fetch(window.location.href, { method: 'POST', body: form })
        .then(() => { location.reload(); })
        .catch(err => alert('خطأ: ' + err));
}

function deleteCategory(catId) {
    if (!confirm('حذف التصنيف؟')) return;
    const form = new FormData();
    form.append('action', 'delete_category');
    form.append('category_id', catId);
    fetch(window.location.href, { method: 'POST', body: form })
        .then(() => { location.reload(); })
        .catch(err => alert('خطأ: ' + err));
}

// ===== إدارة الإعدادات =====
function saveSettings() {
    const title = document.getElementById('settingsTitle').value.trim();
    if (!title) return alert('أدخل اسم الموقع');

    const form = new FormData();
    form.append('action', 'edit_settings');
    form.append('website_title', title);

    fetch(window.location.href, { method: 'POST', body: form })
        .then(() => { alert('تم الحفظ بنجاح'); })
        .catch(err => alert('خطأ: ' + err));
}

// ===== إجراءات القنوات =====
function restartChannel(channelId) {
    const form = new FormData();
    form.append('action', 'restart');
    form.append('id', channelId);

    fetch('api_action.php', { method: 'POST', body: form })
        .then(res => res.json())
        .then(data => { if (data.status === 'success') fetchStatus(); })
        .catch(err => console.error(err));
}

// ===== البحث عن القنوات =====
document.getElementById('searchChannels').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.channel-item').forEach(item => {
        item.style.display = item.dataset.name.includes(term) ? 'block' : 'none';
    });
});

// تحديث العدادات
document.getElementById('category-count').textContent = <?= $mysqli->query("SELECT COUNT(*) as cnt FROM categories")->fetch_assoc()['cnt'] ?>;
document.getElementById('user-count').textContent = <?= $mysqli->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'] ?>;

// تحديث دوري
fetchStatus();
setInterval(fetchStatus, 5000);
</script>
</body>
</html>