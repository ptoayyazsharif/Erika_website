<?php
/** PDO singleton. All queries elsewhere use prepared statements. */

function cfg(): array {
    static $c;
    if ($c === null) {
        $path = __DIR__ . '/config.php';
        if (!is_file($path)) {
            http_response_code(500);
            exit('Missing lib/config.php — copy config.sample.php to lib/config.php and fill it in.');
        }
        $c = require $path;
    }
    return $c;
}

function db(): PDO {
    static $pdo;
    if ($pdo === null) {
        $d = cfg()['db'];
        $pdo = new PDO(
            "mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4",
            $d['user'], $d['pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}
