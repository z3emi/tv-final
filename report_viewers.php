<?php
require_once 'config.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }

$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")->fetch_assoc()['setting_value'] ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>التقارير - <?= htmlspecialchars($website_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root { --bs-body-bg: #f8f9fa; }
        .stat-card { border: none; border-radius: 0.75rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05); }
        .stat-card .card-body { display: flex; align-items: center; gap: 1rem; }
        .stat-card .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <h1 class="h2 mb-4 fw-bold d-flex align-items-center"><i class="bi bi-bar-chart-line-fill me-3"></i>تقارير الأداء</h1>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light border-0 py-3 fw-bold"><i class="bi bi-broadcast me-2"></i>الإحصائيات المباشرة</div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="icon-circle bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></div>
                                <div>
                                    <h6 class="card-subtitle mb-1 text-muted">المشاهدون حاليًا</h6>
                                    <h4 class="card-title fw-bold mb-0" id="live-viewers"><div class="spinner-border spinner-border-sm"></div></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="icon-circle bg-success-subtle text-success"><i class="bi bi-broadcast"></i></div>
                                <div>
                                    <h6 class="card-subtitle mb-1 text-muted">القنوات النشطة</h6>
                                    <h4 class="card-title fw-bold mb-0" id="active-channels"><div class="spinner-border spinner-border-sm"></div></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="icon-circle bg-info-subtle text-info"><i class="bi bi-speedometer2"></i></div>
                                <div>
                                    <h6 class="card-subtitle mb-1 text-muted">إجمالي الترافيك</h6>
                                    <h4 class="card-title fw-bold mb-0" id="total-traffic"><div class="spinner-border spinner-border-sm"></div></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light border-0 py-3 fw-bold"><i class="bi bi-graph-up me-2"></i>المشاهدون خلال آخر 24 ساعة (توقيت GMT+3)</div>
            <div class="card-body"><canvas id="viewersChart"></canvas></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-light border-0 py-3 fw-bold"><i class="bi bi-calendar-range-fill me-2"></i>أكثر القنوات مشاهدة (حسب السجلات)</div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end mb-4 p-3 bg-light rounded">
                            <div class="col-md-5"><label for="startDate" class="form-label small">من تاريخ</label><input type="date" id="startDate" class="form-control" value="<?= date('Y-m-d', strtotime('-7 days')) ?>"></div>
                            <div class="col-md-5"><label for="endDate" class="form-label small">إلى تاريخ</label><input type="date" id="endDate" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <div class="col-md-2"><button class="btn btn-primary w-100" id="filterButton"><i class="bi bi-funnel-fill"></i></button></div>
                        </div>
                        <ul class="list-group list-group-flush" id="historical-channels-list"><li class="list-group-item text-center p-5"><div class="spinner-border"></div></li></ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-light border-0 py-3 fw-bold"><i class="bi bi-star-fill me-2"></i>أكثر القنوات والفئات (مباشر)</div>
                    <div class="card-body">
                        <h6><i class="bi bi-tv-fill text-primary"></i> أكثر 10 قنوات مشاهدة</h6>
                        <ul class="list-group list-group-flush mb-4" id="top-channels-list"><li class="list-group-item text-center p-3"><div class="spinner-border spinner-border-sm"></div></li></ul>
                        <h6><i class="bi bi-tags-fill text-warning"></i> أكثر 5 فئات مشاهدة</h6>
                        <ul class="list-group list-group-flush" id="top-categories-list"><li class="list-group-item text-center p-3"><div class="spinner-border spinner-border-sm"></div></li></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const liveViewersElem = document.getElementById('live-viewers');
    const activeChannelsElem = document.getElementById('active-channels');
    const totalTrafficElem = document.getElementById('total-traffic');
    const topChannelsList = document.getElementById('top-channels-list');
    const topCategoriesList = document.getElementById('top-categories-list');
    const viewersChart = new Chart(document.getElementById('viewersChart'), {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'عدد المشاهدين', data: [], fill: true, borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)', tension: 0.3, pointRadius: 2, pointBackgroundColor: '#0d6efd' }] },
        options: { 
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { 
                        stepSize: 1, 
                        callback: function(value) { if (Number.isInteger(value)) return value; } 
                    } 
                } 
            }, 
            plugins: { 
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#0d6efd',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'المشاهدون: ' + context.parsed.y;
                        }
                    }
                }
            } 
        }
    });
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const filterButton = document.getElementById('filterButton');
    const historicalChannelsList = document.getElementById('historical-channels-list');

    async function updateReports(isHistorical = false) {
        let apiUrl = 'api_reports.php';
        if(isHistorical){
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            if (!startDate || !endDate) return;
            apiUrl += `?start_date=${startDate}&end_date=${endDate}`;
            historicalChannelsList.innerHTML = '<li class="list-group-item text-center p-5"><div class="spinner-border"></div></li>';
        }

        try {
            const response = await fetch(apiUrl);
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();

            if(isHistorical){
                let historicalHtml = '';
                if (data.historical_top_channels && data.historical_top_channels.length > 0) {
                    data.historical_top_channels.forEach((channel, index) => {
                        historicalHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">${index + 1}. ${channel.name} <span class="badge bg-info rounded-pill">${new Intl.NumberFormat().format(channel.total_sessions)} مشاهدة</span></li>`;
                    });
                } else {
                    historicalHtml = '<li class="list-group-item text-center text-muted">لا توجد بيانات لهذه الفترة.</li>';
                }
                historicalChannelsList.innerHTML = historicalHtml;
            } else {
                liveViewersElem.textContent = new Intl.NumberFormat().format(data.live_stats.total_viewers);
                activeChannelsElem.textContent = new Intl.NumberFormat().format(data.live_stats.active_channels);
                totalTrafficElem.textContent = data.live_stats.total_traffic;

                let channelsHtml = '';
                if (data.top_channels.length > 0) {
                    data.top_channels.forEach((channel, index) => {
                        channelsHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">${index + 1}. ${channel.name}<span class="badge bg-primary rounded-pill">${new Intl.NumberFormat().format(channel.view_count)}</span></li>`;
                    });
                } else {
                    channelsHtml = '<li class="list-group-item text-center text-muted small">لا يوجد مشاهدون حاليًا.</li>';
                }
                topChannelsList.innerHTML = channelsHtml;

                let categoriesHtml = '';
                if (data.top_categories.length > 0) {
                    data.top_categories.forEach((cat, index) => {
                        categoriesHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">${index + 1}. ${cat.name}<span class="badge bg-warning text-dark rounded-pill">${new Intl.NumberFormat().format(cat.view_count)}</span></li>`;
                    });
                } else {
                    categoriesHtml = '<li class="list-group-item text-center text-muted small">لا يوجد مشاهدون حاليًا.</li>';
                }
                topCategoriesList.innerHTML = categoriesHtml;

                viewersChart.data.labels = data.chart_data.labels;
                viewersChart.data.datasets[0].data = data.chart_data.data;
                viewersChart.update();
            }
        } catch (error) {
            console.error('Error fetching reports:', error);
            if(isHistorical) historicalChannelsList.innerHTML = '<li class="list-group-item text-center text-danger">فشل في تحميل البيانات.</li>';
        }
    }

    filterButton.addEventListener('click', () => updateReports(true));
    updateReports();
    updateReports(true);
    setInterval(updateReports, 10000);
});
</script>
</body>
</html>