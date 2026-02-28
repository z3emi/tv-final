<?php
$website_title = 'Stream System';

<<<<<<< HEAD
$mysqli = new mysqli("localhost", "tv_admin", "TvPassword2026!", "tv_db");
if ($mysqli->connect_error) { http_response_code(500); echo "Database connection failed."; exit; }

// نفس طريقة الرئيسية لسحب العنوان
$website_title = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'")
    ->fetch_assoc()['setting_value'] ?? 'Stream System';
=======
mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = @new mysqli("localhost", "tv_admin", "TvPassword2026!", "tv_db");
if (!$mysqli->connect_errno) {
    $res = $mysqli->query("SELECT setting_value FROM settings WHERE setting_key = 'website_title'");
    if ($res && $row = $res->fetch_assoc()) {
        $website_title = $row['setting_value'] ?? $website_title;
    }
    $mysqli->close();
}
>>>>>>> 77d5effecafcb8d1738a653072c13bcced3940a8
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($website_title) ?></title>
    <link rel="icon" type="image/png" href="favicon.png" />

    <!-- نفس مراجع index.php -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-color: #101418;
            --card-color: #1a2027;
            --border-color: #2c3a47;
            --accent-color: #E86824;
            --text-color: #e0e0e0;
            --text-muted: #888;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body{
            font-family:'Cairo',sans-serif;
            background-color:var(--bg-color);
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%231a2027' fill-opacity='0.4'%3E%3Cpath d='M0 38.59l2.83-2.83 1.41 1.41L1.41 40H0v-1.41zM0 1.4l2.83 2.83 1.41-1.41L1.41 0H0v1.41zM38.59 40l-2.83-2.83 1.41-1.41L40 38.59V40h-1.41zM40 1.41l-2.83 2.83-1.41-1.41L38.59 0H40v1.41zM20 18.6l2.83-2.83 1.41 1.41L21.41 20l2.83 2.83-1.41 1.41L20 21.41l-2.83 2.83-1.41-1.41L18.59 20l-2.83-2.83 1.41-1.41L20 18.59z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            color:var(--text-color);
            overflow-x:hidden;
        }

        /* ======= نفس قياسات الهيدر بالضبط مثل index.php ======= */
        .header{background:var(--card-color);border-bottom:1px solid var(--border-color)}
        /* ملاحظة: عنصر الهيدر نفسه عليه class p-3 مثل index لقياس الحشوة بالضبط */

        /* ======= نفس شريط التصنيفات مثل index.php ======= */
        .category-nav{background:rgba(26,32,39,.8);backdrop-filter:blur(10px);padding:.75rem 0;position:sticky;top:0;z-index:1020;border-bottom:1px solid var(--border-color)}
        .category-nav .nav-link{color:var(--text-muted);padding:.5rem 1.25rem;font-weight:700;transition:.2s;border-radius:50px;white-space:nowrap}
        .category-nav .nav-link:hover{color:#fff}
        .category-nav .nav-link.active{color:#000;background:var(--accent-color);box-shadow:0 0 15px rgba(255,140,0,.5)}

        /* ======= باقي ستايلات جدول المباريات (مثل كودك السابق) ======= */
        .date-nav {
            display:flex;justify-content:center;gap:10px;
            padding:1.5rem 1rem 0 1rem;flex-wrap:wrap;
        }
        .date-nav button{
            background-color:var(--card-color);border:1px solid var(--border-color);
            color:var(--text-muted);padding:8px 18px;border-radius:8px;
            font-family:'Cairo',sans-serif;font-size:.9rem;font-weight:500;
            cursor:pointer;transition:all .3s ease;
        }
        .date-nav button:hover{border-color:var(--accent-color);color:var(--text-color)}
        .date-nav button.active{background-color:var(--accent-color);color:#000;font-weight:700;border-color:var(--accent-color)}

        #matches-grid{
            display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));
            gap:1.5rem;padding:2rem;max-width:1400px;margin:0 auto;
        }
        .match-card{
            background:var(--card-color);border:1px solid var(--border-color);
            border-radius:12px;padding:1.25rem;transition:.25s;
            box-shadow:0 4px 15px rgba(0,0,0,.3);display:flex;flex-direction:column;cursor:pointer;
        }
        .match-card:hover{
            transform:translateY(-5px);
            box-shadow:0 8px 25px rgba(0,0,0,.5),0 0 20px rgba(232,104,36,.3);
            border-color:var(--accent-color);
        }
        .match-header{display:flex;align-items:center;gap:8px;padding-bottom:.8rem;font-size:.8rem;color:var(--text-muted)}
        .match-content-wrapper{border-top:1px solid var(--border-color);padding-top:.8rem;flex-grow:1}
        .match-content{display:flex;justify-content:space-between;align-items:center}
        .team{flex:1;display:flex;align-items:center;gap:10px;min-width:0}
        .team.home{justify-content:flex-start}
        .team.away{justify-content:flex-end;flex-direction:row-reverse}
        .team-logo{
            width:35px;height:35px;object-fit:contain;border-radius:50%;
            background-color:#2c2f3b;display:flex;align-items:center;justify-content:center;
            font-size:1.1rem;font-weight:700;flex-shrink:0;
        }
        .team-logo img{width:100%;height:100%;border-radius:50%}
        .team-name{font-size:.9rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .match-details{flex-grow:0;flex-shrink:0;padding:0 10px;text-align:center}
        .score{font-size:1.7rem;font-weight:700;letter-spacing:2px}
        .match-status{font-size:.8rem;font-weight:500;padding:4px 10px;border-radius:12px;margin-top:5px}
        .match-footer{
            border-top:1px solid var(--border-color);margin-top:.8rem;padding-top:.8rem;
            text-align:center;font-size:.8rem;color:var(--text-muted);
            display:flex;align-items:center;justify-content:center;gap:8px;
        }
        .status-live{background-color:#e74c3c;color:#fff;animation:pulse 1.5s infinite}
        .status-finished{background-color:var(--border-color);color:var(--text-muted)}
        .status-scheduled{background-color:#3f51b5;color:#fff}
        @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(231,76,60,.7)}70%{box-shadow:0 0 0 8px rgba(231,76,60,0)}100%{box-shadow:0 0 0 0 rgba(231,76,60,0)}}
        .spinner-container{display:flex;justify-content:center;align-items:center;padding:5rem}
        .spinner{border:5px solid var(--border-color);border-top:5px solid var(--accent-color);border-radius:50%;width:50px;height:50px;animation:spin 1s linear infinite}
        @keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}

        /* مودال التفاصيل */
        .modal-content{background:var(--card-color);border-color:var(--border-color)}
        .modal-header{border-bottom-color:var(--border-color)}
        #modalMatchTitle{font-size:1.2rem}
        .modal-body .row{--bs-gutter-x:2.5rem}
        .lineup-list{list-style:none;padding-right:0}
        .lineup-list li{margin-bottom:4px;font-size:.9rem}
        .lineup-title,.stats-title{font-weight:700;margin-bottom:10px;border-bottom:2px solid var(--accent-color);padding-bottom:5px;display:inline-block}
        .detail-item{display:flex;align-items:center;gap:10px;margin-bottom:12px}
        .detail-item i{color:var(--accent-color);font-size:1.2rem}
        .modal-match-header .team-logo{width:50px;height:50px}
        .modal-match-header h6{color:var(--text-muted);font-size:.9rem;margin:8px 0 0 0}
        .stats-container{border-top:1px solid var(--border-color)}
        .stat-item{display:inline-flex;align-items:center;gap:5px;font-size:1rem;font-weight:700}
        .yellow-card{color:#ffd700}
        .red-card{color:#e74c3c}

        @media (max-width: 991.98px){
            .header h4{font-size:1.2rem}
        }
    </style>
</head>
<body>

    <!-- ======= Header مطابق للرئيسية (نفس القياسات p-3) ======= -->
    <div class="header d-flex justify-content-between align-items-center p-3">
        <h4 class="mb-0">📺 القنوات</h4>
    </div>

    <!-- ======= Nav مطابق للرئيسية ======= -->
    <nav class="category-nav">
        <div class="container-fluid">
            <ul class="nav nav-pills justify-content-start flex-nowrap overflow-auto px-2">
                <li class="nav-item mx-1">
                    <a class="nav-link active" href="schedule.php" style="color:#000;">
                        <i class="bi bi-calendar-event-fill me-1"></i>جدول المباريات
                    </a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link" href="index.php" data-category="all">الكل</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link" href="index.php">الرياضة</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- ======= المحتوى ======= -->
    <main id="app-container">
        <div class="date-nav">
            <button data-day-offset="-1">الأمس</button>
            <button data-day-offset="0" class="active">اليوم</button>
            <button data-day-offset="1">الغد</button>
            <button data-day-offset="2">بعد غد</button>
        </div>
        <div id="loading-spinner" class="spinner-container"><div class="spinner"></div></div>
        <div id="matches-grid"></div>
    </main>

    <!-- ======= مودال التفاصيل ======= -->
    <div class="modal fade" id="matchDetailsModal" tabindex="-1" aria-labelledby="modalMatchTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMatchTitle"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="modal-match-header text-center mb-4 pb-3 border-bottom border-secondary">
                        <div class="row align-items-center">
                            <div class="col-4 d-flex justify-content-center" id="modalHomeLogo"></div>
                            <div class="col-4"><h6 id="modalLeagueName"></h6></div>
                            <div class="col-4 d-flex justify-content-center" id="modalAwayLogo"></div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6"><div class="detail-item"><i class="bi bi-geo-alt-fill"></i><span id="modalVenue"></span></div></div>
                        <div class="col-md-6"><div class="detail-item"><i class="bi bi-calendar-date-fill"></i><span id="modalDateTime"></span></div></div>
                    </div>
                    <div class="stats-container pt-3 mb-4">
                        <h6 class="stats-title text-center w-100">الإحصائيات</h6>
                        <div class="row text-center mt-3">
                            <div class="col-5 d-flex justify-content-center gap-3" id="modalHomeStats"></div>
                            <div class="col-2 d-flex align-items-center justify-content-center">VS</div>
                            <div class="col-5 d-flex justify-content-center gap-3" id="modalAwayStats"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 text-center"><h6 class="lineup-title" id="modalHomeTeamName"></h6><ul class="lineup-list" id="modalHomeLineup"></ul></div>
                        <div class="col-6 text-center"><h6 class="lineup-title" id="modalAwayTeamName"></h6><ul class="lineup-list" id="modalAwayLineup"></ul></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="#" id="modalHighlightsBtn" target="_blank" class="btn btn-warning" style="display: none;">
                        <i class="bi bi-youtube me-2"></i>مشاهدة الملخص
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ======= السكربتات ======= -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const matchesGrid = document.getElementById('matches-grid');
        const spinner = document.getElementById('loading-spinner');
        const dateNavButtons = document.querySelectorAll('.date-nav button');
        const matchDetailsModal = new bootstrap.Modal(document.getElementById('matchDetailsModal'));
        let allMatches = [];

        const leagueTranslations = {
            "English Premier League": "الدوري الإنجليزي الممتاز",
            "Spanish La Liga": "الدوري الإسباني",
            "UEFA Champions League": "دوري أبطال أوروبا",
            "German Bundesliga": "الدوري الألماني",
            "Italian Serie A": "الدوري الإيطالي",
            "جدول محلي": "جدول محلي"
        };

        function formatDate(date) { return date.toISOString().slice(0, 10); }

        function formatTo12Hour(dateString, timeString) {
            if (!dateString || !timeString || timeString.length < 5) return "--";
            const utcDate = new Date(`${dateString}T${timeString}Z`);
            if (Number.isNaN(utcDate.getTime())) return timeString.slice(0,5);
            utcDate.setHours(utcDate.getHours() + 3);
            let hours = utcDate.getUTCHours();
            let minutes = utcDate.getUTCMinutes();
            let period = 'ص';
            if (hours >= 12) period = 'م';
            if (hours > 12) hours -= 12;
            if (hours === 0) hours = 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            return `${hours}:${minutes} ${period}`;
        }

        async function fetchMatchesForDate(dateString) {
            spinner.style.display = 'flex';
            matchesGrid.innerHTML = '';
            try {
                const response = await fetch(`api_schedule.php?date=${encodeURIComponent(dateString)}`);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                allMatches = Array.isArray(data.events) ? data.events : [];

                if (allMatches.length === 0) {
                    matchesGrid.innerHTML = `<p style="text-align:center; width:100%;">لا توجد مباريات متاحة في هذا اليوم.</p>`;
                } else {
                    allMatches.sort((a, b) => (a.strTime || '').localeCompare(b.strTime || ''));
                    allMatches.forEach(match => matchesGrid.appendChild(createMatchCard(match)));
                }

                if (data.source === 'fallback') {
                    matchesGrid.insertAdjacentHTML('afterbegin', `<p style="grid-column:1/-1;text-align:center;color:#ffca28;">تم عرض جدول احتياطي محلي لأن مزود الـ API غير متاح حالياً.</p>`);
                }
            } catch (error) {
                matchesGrid.innerHTML = `<p style="text-align:center; width:100%; color: #ff5252;">فشل تحميل جدول المباريات.</p>`;
            } finally {
                spinner.style.display = 'none';
            }
        }

        dateNavButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                dateNavButtons.forEach(btn => btn.classList.remove('active'));
                e.currentTarget.classList.add('active');
                const offset = parseInt(e.currentTarget.dataset.dayOffset);
                const targetDate = new Date();
                targetDate.setDate(targetDate.getDate() + offset);
                fetchMatchesForDate(formatDate(targetDate));
            });
        });

        matchesGrid.addEventListener('click', (e) => {
            const card = e.target.closest('.match-card');
            if (!card) return;
            const matchId = card.dataset.matchId;
            const matchData = allMatches.find(m => m.idEvent === matchId);
            if (!matchData) return;

            document.getElementById('modalMatchTitle').textContent = matchData.strEvent;
            document.getElementById('modalLeagueName').textContent = leagueTranslations[matchData.strLeague] || matchData.strLeague;
            document.getElementById('modalHomeLogo').innerHTML = createTeamLogo(matchData.strHomeTeam, matchData.strHomeTeamBadge);
            document.getElementById('modalAwayLogo').innerHTML = createTeamLogo(matchData.strAwayTeam, matchData.strAwayTeamBadge);
            document.getElementById('modalVenue').textContent = matchData.strVenue || 'غير محدد';
            document.getElementById('modalDateTime').textContent = `${matchData.dateEvent} - ${formatTo12Hour(matchData.dateEvent, matchData.strTime)}`;
            document.getElementById('modalHomeTeamName').textContent = matchData.strHomeTeam;
            document.getElementById('modalAwayTeamName').textContent = matchData.strAwayTeam;
            document.getElementById('modalHomeStats').innerHTML = 'لا توجد بيانات';
            document.getElementById('modalAwayStats').innerHTML = 'لا توجد بيانات';
            document.getElementById('modalHomeLineup').innerHTML = '<li>(غير متوفرة)</li>';
            document.getElementById('modalAwayLineup').innerHTML = '<li>(غير متوفرة)</li>';

            const highlightsBtn = document.getElementById('modalHighlightsBtn');
            if (matchData.strVideo) {
                highlightsBtn.href = matchData.strVideo;
                highlightsBtn.style.display = 'inline-block';
            } else {
                highlightsBtn.style.display = 'none';
            }

            matchDetailsModal.show();
        });

        document.addEventListener('DOMContentLoaded', () => {
            fetchMatchesForDate(formatDate(new Date()));
        });

        const createTeamLogo = (teamName, logoUrl) => {
            if (logoUrl) return `<div class="team-logo"><img src="${logoUrl}" alt="${teamName}"></div>`;
            const initial = teamName ? teamName.charAt(0).toUpperCase() : '?';
            return `<div class="team-logo"><span>${initial}</span></div>`;
        };

        function createMatchCard(match) {
            const card = document.createElement('div');
            card.className = 'match-card';
            card.dataset.matchId = match.idEvent;

            let statusText = 'مجدولة';
            let statusClass = 'status-scheduled';
            if (match.strStatus === 'Match Finished') {
                statusClass = 'status-finished'; statusText = 'انتهت';
            } else if (match.intHomeScore !== null && match.intAwayScore !== null) {
                statusClass = 'status-live'; statusText = 'جارية';
            } else {
                statusText = formatTo12Hour(match.dateEvent, match.strTime);
            }

            const homeLogoHtml = createTeamLogo(match.strHomeTeam, match.strHomeTeamBadge);
            const awayLogoHtml = createTeamLogo(match.strAwayTeam, match.strAwayTeamBadge);
            const leagueName = leagueTranslations[match.strLeague] || match.strLeague;
            const homeTeamName = match.strHomeTeam || '';
            const awayTeamName = match.strAwayTeam || '';

            const tvStation = match.strTVStation;
            let tvStationHtml = '';
            if (tvStation) {
                tvStationHtml = `<div class="match-footer"><i class="bi bi-tv"></i><span>${tvStation}</span></div>`;
            }

            card.innerHTML = `
                <div class="match-header"><span>${leagueName}</span></div>
                <div class="match-content-wrapper">
                    <div class="match-content">
                        <div class="team home">
                            ${homeLogoHtml}
                            <span class="team-name" title="${homeTeamName}">${homeTeamName}</span>
                        </div>
                        <div class="match-details">
                            <div class="score">${match.intHomeScore ?? '-'} : ${match.intAwayScore ?? '-'}</div>
                            <div class="match-status ${statusClass}">${statusText}</div>
                        </div>
                        <div class="team away">
                            <span class="team-name" title="${awayTeamName}">${awayTeamName}</span>
                            ${awayLogoHtml}
                        </div>
                    </div>
                </div>
                ${tvStationHtml}
            `;
            return card;
        }
    </script>
</body>
</html>
