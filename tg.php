<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

$channel = 'Babyun_posts';
$hosts   = ['t.me', 'telegram.me', 'telegram.dog'];
$proxy   = '';   // если Telegram доступен только через прокси/VPN-приложение, впиши сюда,
                 // например 'socks5h://127.0.0.1:1080' или 'http://127.0.0.1:8118' ну ты понял крч
$cache   = __DIR__ . '/tg_cache.json';
$ttl     = 300;
$limit   = 10;
$debug   = isset($_GET['debug']);

function fail($msg, $cache, $debug) {
    if ($debug) { echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE); exit; }
    if (is_file($cache)) readfile($cache); else echo '[]';
    exit;
}

function fetch($url, $proxy, &$err) {
    $ch = curl_init($url);
    $opt = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36',
    ];
    if ($proxy) $opt[CURLOPT_PROXY] = $proxy;
    curl_setopt_array($ch, $opt);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($body === false)      { $err .= curl_error($ch) . '; '; $body = false; }
    elseif ($code !== 200)    { $err .= "HTTP $code; ";        $body = false; }
    return $body;
}

if (!$debug && is_file($cache) && time() - filemtime($cache) < $ttl) { readfile($cache); exit; }

$html = false; $err = '';
foreach ($hosts as $h) {
    $err .= "[$h] ";
    $html = fetch("https://$h/s/$channel", $proxy, $err);
    if ($html) break;
}
if (!$html) fail('не удалось скачать страницу канала: ' . $err, $cache, $debug);

$html = preg_replace('~<br\s*/?>~i', "\n", $html);
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
$xp = new DOMXPath($dom);

$posts = [];
foreach ($xp->query('//div[contains(@class,"tgme_widget_message_wrap")]') as $m) {
    $t = $xp->query('.//div[contains(@class,"tgme_widget_message_text")]', $m)->item(0);
    $a = $xp->query('.//a[contains(@class,"tgme_widget_message_date")]', $m)->item(0);
    $d = $xp->query('.//time', $m)->item(0);
    $posts[] = [
        'text' => $t ? trim($t->textContent) : '[без текста (фото итд)]',
        'link' => $a ? $a->getAttribute('href') : '',
        'date' => $d ? $d->getAttribute('datetime') : '',
    ];
}

if (!$posts) {
    fail('страница скачалась (' . strlen($html) . ' байт), но постов в ней нет: возможно, у канала выключен публичный просмотр', $cache, $debug);
}

$posts = array_reverse(array_slice($posts, -$limit));
$json  = json_encode($posts, JSON_UNESCAPED_UNICODE);
file_put_contents($cache, $json, LOCK_EX);
echo $json;
