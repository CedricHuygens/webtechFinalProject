<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Database reset before it tests all the tests (needed because after all the tests still data is in the test db which conflicts when we run the iteration a second time)
passthru('php bin/console doctrine:database:drop --force --env=test');
passthru('php bin/console doctrine:database:create --env=test');
passthru('php bin/console doctrine:schema:create --env=test');
