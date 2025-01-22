<?php declare(strict_types=1);
// index.php 20250122 - 20250122
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

use Markc\Pablo\Core\{Config, Init};

require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Initialize configuration
    $config = new Config(
        cfg: ['app_name' => $_ENV['APP_NAME'] ?? 'Pablo'],
        in: array_merge($_GET, $_POST),
        out: []
    );

    // Bootstrap application
    $app = new Init($config);
    echo $app;
} catch (Throwable $e) {
    if ($_ENV['APP_DEBUG'] ?? true) { // Set to true for development
        throw $e;
    }
    http_response_code(500);
    echo 'An error occurred. Please try again later.';
}
