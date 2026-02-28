<?php
require_once 'config.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }

$channel_id = $_GET['id'] ?? 0;
if (!filter_var($channel_id, FILTER_VALIDATE_INT) || $channel_id <= 0) {
    die("Invalid Channel ID");
}

$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")->fetch_assoc()['setting_value'] ?? 'Admin Panel';

$stmt = $mysqli->prepare("SELECT name FROM channels WHERE id = ?");
$stmt->bind_param("i", $channel_id);
$stmt->execute();
$channel_name = $stmt->get_result()->fetch_assoc()['name'] ?? 'Unknown Channel';
$stmt->close();

if (isset($_POST['clear_log'])) {
    $clear_stmt = $mysqli->prepare("DELETE FROM viewing_history WHERE channel_id = ?");
    $clear_stmt->bind_param("i", $channel_id);
    $clear_stmt->execute();
    $clear_stmt->close();
    $_SESSION['message'] = "<div class='alert alert-success'>تم مسح سجل القناة بنجاح.</div>";
    header("Location: channel_log.php?id=$channel_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سجل المشاهدات - <?= htmlspecialchars($channel_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-dark th {
            color: #fff !important;
            background-color: #212529 !important;
        }
        /* Style for active rows */
        .table-success {
            --bs-table-bg: #d1e7dd;
            --bs-table-color: #0a3622;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div id="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 fw-bold mb-0 d-flex align-items-center"><i class="bi bi-clipboard-data-fill me-3"></i>سجل مشاهدات: <?= htmlspecialchars($channel_name) ?></h1>
            <form method="post" onsubmit="return confirm('هل أنت متأكد؟');">
                <button type="submit" name="clear_log" class="btn btn-danger"><i class="bi bi-trash3-fill me-2"></i>مسح السجل</button>
            </form>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-play-btn-fill fs-1 text-primary me-4"></i>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي الجلسات</h6>
                            <h4 class="card-title fw-bold" id="total-sessions"><div class="spinner-border spinner-border-sm"></div></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                 <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-clock-history fs-1 text-success me-4"></i>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">متوسط مدة المشاهدة</h6>
                            <h4 class="card-title fw-bold" id="avg-duration"><div class="spinner-border spinner-border-sm"></div></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-light border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h5 class="mb-0">آخر 200 جلسة مشاهدة</h5>
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="ipSearchInput" class="form-control" placeholder="ابحث عن طريق IP...">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th><i class="bi bi-person-fill"></i> ID المشاهد</th>
                                <th><i class="bi bi-display"></i> نوع الجهاز</th>
                                <th><i class="bi bi-geo-alt-fill"></i> عنوان IP</th>
                                <th><i class="bi bi-calendar-plus"></i> بداية الجلسة</th>
                                <th><i class="bi bi-calendar-x"></i> آخر ظهور</th>
                                <th><i class="bi bi-hourglass-split"></i> مدة المشاهدة</th>
                            </tr>
                        </thead>
                        <tbody id="history-tbody">
                           <tr><td colspan="6" class="text-center p-5"><div class="spinner-border"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const channelId = <?= (int)$channel_id ?>;
    const totalSessionsElem = document.getElementById('total-sessions');
    const avgDurationElem = document.getElementById('avg-duration');
    const historyTbody = document.getElementById('history-tbody');
    const ipSearchInput = document.getElementById('ipSearchInput');

    let searchTimeout;

    async function updateLog() {
        const searchQuery = ipSearchInput.value.trim();
        let apiUrl = `api_channel_log.php?id=${channelId}`;
        if (searchQuery) {
            apiUrl += `&search_ip=${encodeURIComponent(searchQuery)}`;
        }

        try {
            const response = await fetch(apiUrl);
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();

            if (!searchQuery) {
                totalSessionsElem.textContent = new Intl.NumberFormat().format(data.stats.total_sessions);
                avgDurationElem.textContent = data.stats.avg_duration_formatted;
            }

            let tableHtml = '';
            if (data.history.length > 0) {
                data.history.forEach(row => {
                    // --- بداية التعديل: إضافة كلاس للصفوف النشطة ---
                    const activeClass = row.is_active ? 'table-success' : '';
                    tableHtml += `
                        <tr class="${activeClass}">
                            <td><code title="${row.viewer_uid}">${row.viewer_uid.substring(0, 8)}...</code></td>
                            <td>
                                <span class="badge bg-light text-dark fw-normal">
                                    <i class="bi ${row.device_icon} me-1"></i>
                                    ${row.device}
                                </span>
                            </td>
                            <td><code>${row.ip_address}</code></td>
                            <td>${row.session_start}</td>
                            <td>${row.last_seen}</td>
                            <td>${row.duration_formatted}</td>
                        </tr>
                    `;
                    // --- نهاية التعديل ---
                });
            } else {
                const message = searchQuery ? 'لا توجد نتائج تطابق بحثك.' : 'لا يوجد سجل مشاهدات لهذه القناة بعد.';
                tableHtml = `<tr><td colspan="6" class="text-center p-5"><i class="bi bi-info-circle-fill text-muted fs-2"></i><p class="mt-2 mb-0">${message}</p></td></tr>`;
            }
            historyTbody.innerHTML = tableHtml;

        } catch (error) {
            console.error("Failed to fetch channel log:", error);
            historyTbody.innerHTML = `<tr><td colspan="6" class="text-center p-5 text-danger">فشل في تحميل البيانات.</td></tr>`;
        }
    }
    
    ipSearchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            updateLog();
        }, 300);
    });

    updateLog();
    setInterval(updateLog, 5000);
});
</script>
</body>
</html>
