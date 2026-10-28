<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$file = __DIR__ . '/visitors.txt';
$salt = '3feh78f0832er=349qri320-998=039408v47n830257dfdfcd';

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$id = hash('sha256', $salt . $ip . $ua);

$fh = fopen($file, 'c+');
if (!$fh) { echo json_encode(['count' => 0]); exit; }
flock($fh, LOCK_EX);

$ids = array_filter(explode("\n", stream_get_contents($fh)));
if (!in_array($id, $ids, true)) {
    fwrite($fh, $id . "\n");
    $ids[] = $id;
}

flock($fh, LOCK_UN);
fclose($fh);

echo json_encode(['count' => count($ids)]);
