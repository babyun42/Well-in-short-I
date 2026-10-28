<?php
$name = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (in_array($name, ['visitors.txt', 'tg_cache.json', 'router.php'], true)) {
    http_response_code(404);
    exit;
}
return false;
