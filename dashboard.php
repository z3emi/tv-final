<?php
// ... (بداية الملف كما هي) ...
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
        .table-responsive { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); border-radius: 0.75rem; }
        .table-dark th { color: #fff !important; background-color: #212529 !important; }
        .status-badge { font-size: 0.9em; padding: 0.4em 0.8em; }
        .url-truncate { word-break: break-all; white-space: normal; max-width: 250px; text-align: start; }
        .nav-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15)!important; transition: all 0.3s ease-in-out; }
        .channel-img-sm { width: 50px; height: 50px; object-fit: contain; background-color: #212529; border-radius: 0.5rem; padding: 5px; box-sizing: border-box; }
        .action-btn { transition: all 0.2s ease-in-out; }
        .action-btn:hover { transform: scale(1.1); }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0 fw-bold d-flex align-items-center"><i class="bi bi-grid-1x2-fill me-3"></i>لوحة التحكم الرئيسية</h1>
        </div>

        <div class="row g-4 mb-4">
            </div>

        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-light border-0 py-3">
                <h5 class="mb-0">مراقبة البث المباشر</h5>
                <div class="text-muted" id="last-update-time"><i class="bi bi-arrow-clockwise"></i> جاري التحديث...</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-borderless align-middle text-center mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th class="text-end">اسم القناة</th>
                                <th>الحالة</th>
                                <th>وقت التشغيل</th>
                                <th>إعادة تشغيل</th>
                                <th>رابط المصدر</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="channels-status-table">
                            <tr><td colspan="7" class="p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">جاري تحميل بيانات البث...</p></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusTableBody = document.getElementById('channels-status-table');
    const lastUpdateTimeElem = document.getElementById('last-update-time');

    const statusClasses = {
        running: { badge: 'bg-success', icon: 'bi-check-circle-fill' },
        stalled: { badge: 'bg-danger', icon: 'bi-pause-circle-fill' },
        restarting: { badge: 'bg-warning text-dark', icon: 'bi-arrow-repeat' },
        starting: { badge: 'bg-info text-dark', icon: 'bi-hourglass-split' },
        stopped: { badge: 'bg-secondary', icon: 'bi-x-circle-fill' }
    };

    function renderActionButtons(channel) {
        if (channel.status_code === 'stopped') {
            return `
                <button class="btn btn-sm btn-success action-btn" data-action="start" data-id="${channel.id}" title="تشغيل">
                    <i class="bi bi-play-circle"></i>
                </button>
            `;
        } else {
            return `
                <button class="btn btn-sm btn-warning action-btn" data-action="restart" data-id="${channel.id}" title="إعادة تشغيل">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button class="btn btn-sm btn-danger action-btn" data-action="stop" data-id="${channel.id}" title="إيقاف">
                    <i class="bi bi-stop-circle"></i>
                </button>
            `;
        }
    }

    async function fetchStatus() {
        try {
            const response = await fetch('api_status.php');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();

            const updateTime = new Date(data.last_update).toLocaleTimeString('ar-EG');
            lastUpdateTimeElem.innerHTML = `<i class="bi bi-clock-history"></i> آخر تحديث: ${updateTime}`;
            statusTableBody.innerHTML = '';

            if (data.channels && data.channels.length > 0) {
                data.channels.forEach(channel => {
                    const statusConfig = statusClasses[channel.status_code] || statusClasses.starting;
                    const row = `
                        <tr>
                            <td><strong>${channel.id}</strong></td>
                            <td class="text-end">
                                <div class="d-flex align-items-center">
                                    <img src="stream/stream/uploads/${channel.image_url}" class="channel-img-sm" onerror="this.onerror=null;this.src='https://placehold.co/40/eee/313435?text=...';">
                                    <span class="ms-3 fw-medium">${channel.name}</span>
                                </div>
                            </td>
                            <td><span class="badge rounded-pill ${statusConfig.badge} status-badge"><i class="bi ${statusConfig.icon}"></i> ${channel.status_text}</span></td>
                            <td>${channel.uptime}</td>
                            <td><span class="badge bg-dark">${channel.restarts}</span></td>
                            <td><div class="url-truncate" title="${channel.url}">${channel.url}</div></td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center">
                                    ${renderActionButtons(channel)}
                                </div>
                            </td>
                        </tr>
                    `;
                    statusTableBody.innerHTML += row;
                });
            } else {
                statusTableBody.innerHTML = '<tr><td colspan="7" class="text-center p-4">لم يتم العثور على قنوات في ملف channels.txt.</td></tr>';
            }
        } catch (error) {
            console.error("Failed to fetch status:", error);
            statusTableBody.innerHTML = `<tr><td colspan="7" class="text-center p-4 text-danger">فشل في تحميل البيانات. تأكد من أن سكربت البث يعمل وأن ملف api_status.php صحيح.</td></tr>`;
        }
    }
    
    async function sendChannelAction(action, channelId) {
        const confirmMessages = {
            restart: 'هل أنت متأكد أنك تريد إعادة تشغيل هذه القناة؟',
            stop: 'هل أنت متأكد أنك تريد إيقاف هذه القناة؟',
            start: 'هل أنت متأكد أنك تريد تشغيل هذه القناة؟'
        };

        if (!confirm(confirmMessages[action])) {
            return;
        }

        const formData = new FormData();
        formData.append('action', action);
        formData.append('id', channelId);

        try {
            const response = await fetch('api_action.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                const row = statusTableBody.querySelector(`button[data-id="${channelId}"]`).closest('tr');
                if(row) {
                    row.style.opacity = '0.5';
                }
                setTimeout(fetchStatus, 1500); 
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Failed to send action:', error);
            alert('فشل في إرسال الأمر.');
        }
    }

    statusTableBody.addEventListener('click', function(e) {
        const button = e.target.closest('button.action-btn');
        if (button) {
            const action = button.dataset.action;
            const id = button.dataset.id;
            sendChannelAction(action, id);
        }
    });

    fetchStatus();
    setInterval(fetchStatus, 5000);
});
</script>
</body>
</html>