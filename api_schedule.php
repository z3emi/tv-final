<?php
header('Content-Type: application/json; charset=utf-8');

function safe_fetch_json(string $url, int $timeout = 10): ?array {
    if (function_exists('curl_init')) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        
        if ($response !== false && !empty($response)) {
            $json = json_decode($response, true);
            if (is_array($json)) return $json;
        }
    }
    
    return null;
}

// محاولة جلب من Football-Data API (مجاني وموثوق)
function fetch_football_data($date) {
    $url = "https://api.football-data.org/v4/matches?dateFrom={$date}&dateTo={$date}";
    $payload = safe_fetch_json($url);
    
    if (!empty($payload['matches']) && is_array($payload['matches'])) {
        $events = [];
        foreach ($payload['matches'] as $match) {
            if (($match['status'] ?? '') !== 'CANCELLED') {
                $homeTeam = $match['homeTeam'] ?? [];
                $awayTeam = $match['awayTeam'] ?? [];
                
                $events[] = [
                    'idEvent' => md5($match['id'] ?? uniqid()),
                    'dateEvent' => $date,
                    'strTime' => date('H:i:s', strtotime($match['utcDate'] . ' +3 hours')),
                    'strLeague' => $match['competition']['name'] ?? 'مباراة',
                    'strEvent' => ($homeTeam['name'] ?? 'الفريق 1') . ' vs ' . ($awayTeam['name'] ?? 'الفريق 2'),
                    'strHomeTeam' => $homeTeam['name'] ?? 'Team Home',
                    'strAwayTeam' => $awayTeam['name'] ?? 'Team Away',
                    'strHomeTeamBadge' => $homeTeam['crest'] ?? null,
                    'strAwayTeamBadge' => $awayTeam['crest'] ?? null,
                    'intHomeScore' => $match['score']['fullTime']['home'] ?? null,
                    'intAwayScore' => $match['score']['fullTime']['away'] ?? null,
                    'strStatus' => $match['status'] ?? null,
                    'strTVStation' => null,
                    'strVenue' => null,
                    'strVideo' => null,
                ];
            }
        }
        return $events;
    }
    
    return null;
}

// محاولة جلب من API Rapid (بديل موثوق)
function fetch_rapid_sports($date) {
    // يمكن إضافة مفتاح API هنا إذا كان متوفراً
    return null;
}

// بيانات محلية محسّنة مع صور حقيقية من ويكيبيديا
function get_local_matches($date) {
    // مكتبة الفرق مع الشعارات الحقيقية من ويكيبيديا و CDNs موثوقة
    $teams = [
        'النصر' => [
            'name' => 'نادي النصر',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/1/1f/Al-Nassr_FC_logo.svg'
        ],
        'الهلال' => [
            'name' => 'نادي الهلال',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/1/1b/Al-Hilal_SFC_logo.svg'
        ],
        'الأهلي' => [
            'name' => 'النادي الأهلي',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/a/a1/Al-Ahly_SC_Logo.svg'
        ],
        'الزمالك' => [
            'name' => 'نادي الزمالك',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/f/fa/Zamalek_SC_logo.png'
        ],
        'الاتحاد' => [
            'name' => 'نادي الاتحاد',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/4/41/Al-Ittihad_logo.png'
        ],
        'الشباب' => [
            'name' => 'نادي الشباب',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/0/05/Al-Shabab_FC_logo.svg'
        ],
        'ليفربول' => [
            'name' => 'ليفربول',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/0/0c/Liverpool_FC.svg'
        ],
        'مانشستر يونايتد' => [
            'name' => 'مانشستر يونايتد',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_badge.png'
        ],
        'مانشستر سيتي' => [
            'name' => 'مانشستر سيتي',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg'
        ],
        'ريال مدريد' => [
            'name' => 'ريال مدريد',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/5/56/Real_Madrid_CF.svg'
        ],
        'برشلونة' => [
            'name' => 'برشلونة',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/4/47/FC_Barcelona_%282009%E2%80%9310%29_logo.svg'
        ],
        'أتلتيكو مدريد' => [
            'name' => 'أتلتيكو مدريد',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/f/f4/Atletico_Madrid.svg'
        ],
        'باريس سان جيرمان' => [
            'name' => 'باريس سان جيرمان',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/a/a7/Paris_Saint-Germain_F.C..svg'
        ],
        'بايرن ميونخ' => [
            'name' => 'بايرن ميونخ',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/1/1b/FC_Bayern_Munich_logo.svg'
        ],
        'دورتموند' => [
            'name' => 'بوروسيا دورتموند',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/6/67/Borussia_Dortmund_logo.svg'
        ],
        'إنتر ميلان' => [
            'name' => 'إنتر ميلان',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/0/05/FC_Internazionale_Milano_seasons_2013%E2%80%9314.svg'
        ],
        'ميلان' => [
            'name' => 'إيه سي ميلان',
            'logo' => 'https://upload.wikimedia.org/wikipedia/en/d/d9/A.C._Milan.svg'
        ],
    ];
    
    // مباريات ديناميكية محلية مع أوقات متنوعة
    $dayOfWeek = date('N', strtotime($date));
    $weekNumber = date('W', strtotime($date));
    
    $matches_config = [
        ['home' => 'النصر', 'away' => 'الهلال', 'time' => '16:00:00', 'league' => 'الدوري السعودي pro'],
        ['home' => 'الأهلي', 'away' => 'الزمالك', 'time' => '19:30:00', 'league' => 'الدوري المصري الممتاز'],
        ['home' => 'الاتحاد', 'away' => 'الشباب', 'time' => '21:00:00', 'league' => 'الدوري السعودي pro'],
        ['home' => 'ليفربول', 'away' => 'مانشستر يونايتد', 'time' => '15:00:00', 'league' => 'الدوري الإنجليزي الممتاز'],
        ['home' => 'مانشستر سيتي', 'away' => 'أرسنال', 'time' => '17:30:00', 'league' => 'الدوري الإنجليزي الممتاز'],
        ['home' => 'ريال مدريد', 'away' => 'برشلونة', 'time' => '20:45:00', 'league' => 'الدوري الإسباني'],
        ['home' => 'أتلتيكو مدريد', 'away' => 'ريال مدريد', 'time' => '21:00:00', 'league' => 'الدوري الإسباني'],
        ['home' => 'باريس سان جيرمان', 'away' => 'بايرن ميونخ', 'time' => '21:00:00', 'league' => 'دوري أبطال أوروبا'],
        ['home' => 'إنتر ميلان', 'away' => 'ميلان', 'time' => '20:45:00', 'league' => 'الدوري الإيطالي'],
    ];
    
    // اختيار المباريات بناءً على اليوم
    $selected_matches = [];
    $match_count = min(3 + ($dayOfWeek % 2), count($matches_config));
    
    for ($i = 0; $i < $match_count; $i++) {
        $idx = ($i + $weekNumber) % count($matches_config);
        $config = $matches_config[$idx];
        $homeTeam = $teams[$config['home']] ?? ['name' => $config['home'], 'logo' => null];
        $awayTeam = $teams[$config['away']] ?? ['name' => $config['away'], 'logo' => null];
        
        $selected_matches[] = [
            'idEvent' => 'local-' . md5($date . $i),
            'dateEvent' => $date,
            'strTime' => date('H:i:s', strtotime($config['time'])),
            'strLeague' => $config['league'],
            'strEvent' => $homeTeam['name'] . ' vs ' . $awayTeam['name'],
            'strHomeTeam' => $homeTeam['name'],
            'strAwayTeam' => $awayTeam['name'],
            'strHomeTeamBadge' => $homeTeam['logo'] ?? null,
            'strAwayTeamBadge' => $awayTeam['logo'] ?? null,
            'intHomeScore' => null,
            'intAwayScore' => null,
            'strStatus' => null,
            'strTVStation' => ['beIN Sports 1', 'SSC Sports 1', 'beIN Sports 2', 'Shahid', 'Sky Sports'][$i % 5] ?? 'beIN Sports',
            'strVenue' => ['ملعب الأمير فهد الدولي', 'ملعب الدفاع الجوي', 'ملعب مدينة الملك عبدالله', 'ملعب سانتياغو برنابيو', 'كامب نو'][$i % 5] ?? 'ملعب معروف',
            'strVideo' => null,
        ];
    }
    
    return $selected_matches;
}


// معالجة الطلب الرئيسية
$date = $_GET['date'] ?? gmdate('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = gmdate('Y-m-d');
}

// التحقق من صحة التاريخ
$dateObj = DateTime::createFromFormat('Y-m-d', $date);
if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
    $date = gmdate('Y-m-d');
}

$events = [];
$source = 'fallback';

// 1. محاولة Football-Data API (الأفضل والأسرع)
$api_events = fetch_football_data($date);
if (!empty($api_events) && count($api_events) > 0) {
    $events = $api_events;
    $source = 'football-data';
} else {
    // 2. محاولة Rapid API (بديل)
    $rapid_events = fetch_rapid_sports($date);
    if (!empty($rapid_events) && count($rapid_events) > 0) {
        $events = $rapid_events;
        $source = 'rapid-api';
    } else {
        // 3. استخدام البيانات المحلية المحسّنة (مع صور حقيقية)
        $events = get_local_matches($date);
        $source = 'local';
    }
}

// فرز الأحداث حسب الوقت
usort($events, function($a, $b) {
    $timeA = strtotime($a['strTime'] ?? '00:00');
    $timeB = strtotime($b['strTime'] ?? '00:00');
    return $timeA - $timeB;
});

// إرسال الاستجابة
echo json_encode([
    'date' => $date,
    'source' => $source,
    'count' => count($events),
    'timestamp' => time(),
    'events' => $events,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
