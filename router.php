<?php
// Роутер для встроенного сервера PHP: не отдаём служебные файлы наружу.
$name = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (in_array($name, ['visitors.txt', 'tg_cache.json', 'router.php'], true)) {
    http_response_code(404);
    exit;
}
return false; // всё остальное отдаётся как обычно
