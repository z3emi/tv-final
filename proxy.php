<?php
// proxy.php — يقبل url مباشرة أو u بصيغة base64url
// أمثلة:
//   proxy.php?url=https://yariga7.online/upload/images/logo1.zip
//   proxy.php?u=BASE64URL(...)

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

  $base = $parts['scheme'].'://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');
  $dir  = $base . (isset($parts['path']) ? rtrim(dirname($parts['path']), '/\\').'/' : '/');

  $lines = preg_split("/\r\n|\n|\r/", $body);
  $out = [];
  foreach ($lines as $line) {
    if ($line === '' || $line[0] === '#') { $out[] = $line; continue; }
    if (!preg_match('#^https?://#i', $line)) {
      $abs = (substr($line,0,1) === '/') ? ($base.$line) : ($dir.$line);
    } else {
      $abs = $line;
    }

    // أعِد تمرير كل الروابط عبر البروكسي (ينفع مع المانيفست المتداخلة/القطع)
    $prox = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https')
          . '://' . $_SERVER['HTTP_HOST']
          . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')
          . '/proxy.php?url=' . rawurlencode($abs);
    $out[] = $prox;
  }
  echo implode("\n", $out);
  exit;
}

// ملفات أخرى (ts/mp4) — مرّرها كما هي
if ($ctype) header('Content-Type: '.$ctype);
echo $body;
