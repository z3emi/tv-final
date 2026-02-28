<?php
header('Content-Type: application/json; charset=utf-8');

function safe_fetch_json(string $url, int $timeout = 12): ?array {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'header' => "User-Agent: IPTV-Schedule/1.0\r\nAccept: application/json\r\n",
        ]
    ]);

    $body = @file_get_contents($url, false, $ctx);
    if ($body === false || $body === '') {
        return null;
    }

    $json = json_decode($body, true);
    return is_array($json) ? $json : null;
}

function normalize_sportsdb_event(array $e): array {
    return [
        'idEvent' => (string)($e['idEvent'] ?? uniqid('ev_', true)),
        'dateEvent' => $e['dateEvent'] ?? '',
        'strTime' => $e['strTime'] ?? '',
        'strLeague' => $e['strLeague'] ?? 'مباراة',
        'strEvent' => $e['strEvent'] ?? (($e['strHomeTeam'] ?? 'Team A') . ' vs ' . ($e['strAwayTeam'] ?? 'Team B')),
        'strHomeTeam' => $e['strHomeTeam'] ?? 'Team A',
        'strAwayTeam' => $e['strAwayTeam'] ?? 'Team B',
        'strHomeTeamBadge' => $e['strHomeTeamBadge'] ?? null,
        'strAwayTeamBadge' => $e['strAwayTeamBadge'] ?? null,
        'intHomeScore' => isset($e['intHomeScore']) ? $e['intHomeScore'] : null,
        'intAwayScore' => isset($e['intAwayScore']) ? $e['intAwayScore'] : null,
        'strStatus' => $e['strStatus'] ?? null,
        'strTVStation' => $e['strTVStation'] ?? null,
        'strVenue' => $e['strVenue'] ?? null,
        'strVideo' => $e['strVideo'] ?? null,
    ];
}

function fallback_matches(string $date): array {
    return [
        [
            'idEvent' => 'fallback-1',
            'dateEvent' => $date,
            'strTime' => '17:00:00',
            'strLeague' => 'جدول محلي',
            'strEvent' => 'Team Alpha vs Team Beta',
            'strHomeTeam' => 'Team Alpha',
            'strAwayTeam' => 'Team Beta',
            'strHomeTeamBadge' => null,
            'strAwayTeamBadge' => null,
            'intHomeScore' => null,
            'intAwayScore' => null,
            'strStatus' => null,
            'strTVStation' => 'beIN Sports',
            'strVenue' => 'Main Stadium',
            'strVideo' => null,
        ],
        [
            'idEvent' => 'fallback-2',
            'dateEvent' => $date,
            'strTime' => '20:30:00',
            'strLeague' => 'جدول محلي',
            'strEvent' => 'Team Gamma vs Team Delta',
            'strHomeTeam' => 'Team Gamma',
            'strAwayTeam' => 'Team Delta',
            'strHomeTeamBadge' => null,
            'strAwayTeamBadge' => null,
            'intHomeScore' => null,
            'intAwayScore' => null,
            'strStatus' => null,
            'strTVStation' => 'SSC',
            'strVenue' => 'City Arena',
            'strVideo' => null,
        ]
    ];
}

$date = $_GET['date'] ?? gmdate('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = gmdate('Y-m-d');
}

$leagueIds = ['4328', '4335', '4480', '4332', '4331'];
$year = (int)gmdate('Y');
$month = (int)gmdate('n');
$season = $month >= 7 ? "$year-" . ($year + 1) : ($year - 1) . "-$year";

$events = [];
foreach ($leagueIds as $id) {
    $url = "https://www.thesportsdb.com/api/v1/json/3/eventsseason.php?id={$id}&s={$season}";
    $payload = safe_fetch_json($url);
    if (!empty($payload['events']) && is_array($payload['events'])) {
        foreach ($payload['events'] as $event) {
            if (($event['dateEvent'] ?? '') === $date) {
                $events[] = normalize_sportsdb_event($event);
            }
        }
    }
}

if (empty($events)) {
    $events = fallback_matches($date);
    $source = 'fallback';
} else {
    $source = 'thesportsdb';
}

echo json_encode([
    'date' => $date,
    'source' => $source,
    'count' => count($events),
    'events' => $events,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
