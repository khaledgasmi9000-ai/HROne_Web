<?php

use App\Kernel;

$sessionPath = dirname(__DIR__).'/var/sessions/'.($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev');
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
ini_set('session.save_path', $sessionPath);

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
