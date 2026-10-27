<?php
// Счётчик уникальных посетителей. Уникальность = хеш(IP + User-Agent + соль).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$file = __DIR__ . '/visitors.txt';          // лучше положить ВНЕ публичной папки
$salt = '3feh78f0832er=349qri320-998=039408v47n830257dfdfcd';

// За прокси (nginx / Cloudflare) реальный IP лежит в заголовке, а не в REMOTE_ADDR
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$id = hash('sha256', $salt . $ip . $ua);

$fh = fopen($file, 'c+');
if (!$fh) { echo json_encode(['count' => 0]); exit; }
flock($fh, LOCK_EX);

$ids = array_filter(explode("\n", stream_get_contents($fh)));
if (!in_array($id, $ids, true)) {
    fwrite($fh, $id . "\n");   // указатель уже в конце файла — дописываем
    $ids[] = $id;
}

flock($fh, LOCK_UN);
fclose($fh);

echo json_encode(['count' => count($ids)]);
