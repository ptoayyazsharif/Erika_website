<?php

/*
|------------------------------------------------------------------------------
| Pin the test database before anything can be pointed anywhere else.
|------------------------------------------------------------------------------
|
| This suite uses RefreshDatabase, which drops every table before each test. So
| "which database do the tests use" is a data-destruction question, and the
| answer has to be "this one, always", not "whatever the shell happens to say".
|
| It did not used to be. phpunit.xml declares DB_DATABASE=:memory:, but a plain
| <env> does not overwrite a variable already present in the environment — and
| even with force="true" PHPUnit only rewrites getenv() and $_ENV. It never
| touches $_SERVER, and Laravel's env() reads $_SERVER FIRST:
|
|     getenv    = ':memory:'          <- forced
|     $_ENV     = ':memory:'          <- forced
|     $_SERVER  = '/tmp/hijack.sqlite' <- untouched, and this is the one that wins
|
| So in any shell with DB_DATABASE exported, `php artisan test` silently
| retargeted onto that database and destroyed it. Demonstrated on a scratch
| copy holding one user: a single filtered test run left `no such table: users`,
| and did so before the test itself even failed.
|
| That shell is not hypothetical. docker/entrypoint.sh exports DB_DATABASE,
| DEPLOY.md tells you to set it, and anyone debugging in the Coolify terminal
| has it pointing at the live journal.
|
| Setting rather than merely unsetting matters: unset alone would leave the
| config default, which is the real database file on disk.
|
*/
foreach ([
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE'   => ':memory:',
    'DB_URL'        => '',
    'DB_HOST'       => '',
    'DB_PORT'       => '',
    'DB_USERNAME'   => '',
    'DB_PASSWORD'   => '',
] as $key => $value) {
    $_SERVER[$key] = $value;
    $_ENV[$key] = $value;
    putenv("{$key}={$value}");
}

require __DIR__.'/../vendor/autoload.php';
