<?php
function b64u_dec($s){
  $s = strtr((string)$s, '-_', '+/');
  $pad = strlen($s) % 4;
  if ($pad) $s .= str_repeat('=', 4 - $pad);
  return base64_decode($s);
}
$raw = $_GET['url'] ?? null;
$u   = $_GET['u']   ?? null;

if ($raw) {
  $target = $raw;
} elseif ($u) {
  $target = b64u_dec($u);
} else {
  http_response_code(400); exit('Missing url/u');
}

if (!preg_match('#^https?://#i', $target)) { http_response_code(400); exit('Bad URL'); }

$parts = parse_url($target);
$host  = $parts['host'] ?? '';
if (!$host) {
  http_response_code(400); exit('Bad host');
}

function is_https_request(): bool {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $proto = strtolower(trim((string)$_SERVER['HTTP_X_FORWARDED_PROTO']));
    if (strpos($proto, 'https') !== false) return true;
  }
  if (!empty($_SERVER['REQUEST_SCHEME']) && strtolower((string)$_SERVER['REQUEST_SCHEME']) === 'https') return true;
  if (!empty($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443') return true;
  return false;
}

function build_proxy_url(string $absoluteUrl): string {
  $scheme = is_https_request() ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '/proxy.php');
  $basePath = trim(str_replace('\\', '/', dirname($scriptName)), '/');
  $prefix = $basePath === '' || $basePath === '.' ? '' : ('/' . $basePath);
  return $scheme . '://' . $host . $prefix . '/proxy.php?url=' . rawurlencode($absoluteUrl);
}

function absolutize_hls_url(string $url, string $base, string $dir, string $fallbackScheme): string {
  if (preg_match('#^https?://#i', $url)) {
    return $url;
  }
  if (strpos($url, '//') === 0) {
    return $fallbackScheme . ':' . $url;
  }
  if (substr($url, 0, 1) === '/') {
    return $base . $url;
  }
  return $dir . $url;
}

$ch = curl_init($target);
curl_setopt_array($ch, [
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HEADER => false,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_SSL_VERIFYHOST => false,
  CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0',
  CURLOPT_HTTPHEADER => [
    'Accept: */*',
    'Accept-Language: ar,en;q=0.9',
    'Connection: keep-alive',
  ],
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_TIMEOUT => 20,
]);
$res = curl_exec($ch);
if ($res === false) { http_response_code(502); exit('Upstream error'); }
$body  = $res;
$code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
curl_close($ch);

http_response_code($code);
header('Access-Control-Allow-Origin: *');

// كشف إن كان HLS حتى لو الامتداد/MIME مو قياسي
$probe = ltrim($body, "\xEF\xBB\xBF\x00\x09\x0A\x0D\x20");
$isHls = (stripos($ctype,'application/vnd.apple.mpegurl') !== false)
      || (stripos($ctype,'application/x-mpegurl') !== false)
      || (preg_match('/\.m3u8(\?|$)/i', $target))
      || (strncmp($probe, "#EXTM3U", 7) === 0)
      || (strpos($body, "#EXT-X-STREAM-INF") !== false);

if ($isHls) {
  header('Content-Type: application/vnd.apple.mpegurl; charset=UTF-8');

  $scheme = $parts['scheme'] ?? 'http';
  $base = $scheme.'://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');
  $dir  = $base . (isset($parts['path']) ? rtrim(dirname($parts['path']), '/\\').'/' : '/');

  $lines = preg_split("/\r\n|\n|\r/", $body);
  $out = [];
  foreach ($lines as $line) {
    if ($line === '') { $out[] = $line; continue; }

    if ($line[0] === '#') {
      $rewrittenMeta = preg_replace_callback('/URI=("[^"]*"|[^,]+)/i', function ($match) use ($base, $dir, $scheme) {
        $rawValue = (string)$match[1];
        $isQuoted = strlen($rawValue) >= 2 && $rawValue[0] === '"' && substr($rawValue, -1) === '"';
        $value = $isQuoted ? substr($rawValue, 1, -1) : $rawValue;
        $value = trim($value);

        if ($value === '' || preg_match('#^(data:|skd:|urn:|#)#i', $value)) {
          return $match[0];
        }

        $absolute = absolutize_hls_url($value, $base, $dir, $scheme);
        $proxied = build_proxy_url($absolute);
        return 'URI=' . ($isQuoted ? ('"' . $proxied . '"') : $proxied);
      }, $line);

      $out[] = $rewrittenMeta;
      continue;
    }

    $streamLine = trim($line);
    if ($streamLine === '' || preg_match('#^(data:|skd:|urn:|#)#i', $streamLine)) {
      $out[] = $line;
      continue;
    }

    $abs = absolutize_hls_url($streamLine, $base, $dir, $scheme);
    $out[] = build_proxy_url($abs);
  }
  echo implode("\n", $out);
  exit;
}

// ملفات أخرى (ts/mp4) — مرّرها كما هي
if ($ctype) header('Content-Type: '.$ctype);
echo $body;
