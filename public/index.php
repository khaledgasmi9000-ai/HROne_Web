<?php

use App\Kernel;

// Force session files into the project tree to avoid permission issues with the PHP default (e.g. C:\Xampp\tmp)
$sessionPath = __DIR__ . '/../var/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
ini_set('session.save_path', $sessionPath);

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
