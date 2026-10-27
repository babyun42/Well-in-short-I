<?php
// Отдаёт последние посты публичного Telegram-канала в JSON (с кэшем на 5 минут).
header('Content-Type: application/json; charset=utf-8');

$channel = 'Babyun_posts';
$cache   = __DIR__ . '/tg_cache.json';
$ttl     = 300;
$limit   = 10;

if (is_file($cache) && time() - filemtime($cache) < $ttl) { readfile($cache); exit; }

$ctx  = stream_context_create(['http' => [
    'header'  => "User-Agent: Mozilla/5.0\r\n",
    'timeout' => 5,
]]);
$html = @file_get_contents("https://t.me/s/$channel", false, $ctx);
if (!$html) {                       // Telegram не ответил — отдаём старый кэш
    if (is_file($cache)) readfile($cache); else echo '[]';
    exit;
}

$html = preg_replace('~<br\s*/?>~i', "\n", $html);   // сохраняем переносы строк
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
$xp = new DOMXPath($dom);

$posts = [];
foreach ($xp->query('//div[contains(@class,"tgme_widget_message_wrap")]') as $m) {
    $t = $xp->query('.//div[contains(@class,"tgme_widget_message_text")]', $m)->item(0);
    if (!$t) continue;              // посты без текста (чистое фото) пропускаем
    $a = $xp->query('.//a[contains(@class,"tgme_widget_message_date")]', $m)->item(0);
    $d = $xp->query('.//time', $m)->item(0);
    $posts[] = [
        'text' => trim($t->textContent),
        'link' => $a ? $a->getAttribute('href') : '',
        'date' => $d ? $d->getAttribute('datetime') : '',
    ];
}

$posts = array_reverse(array_slice($posts, -$limit));   // новые сверху
$json  = json_encode($posts, JSON_UNESCAPED_UNICODE);
file_put_contents($cache, $json, LOCK_EX);
echo $json;
