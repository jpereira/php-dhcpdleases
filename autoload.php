<?php

declare(strict_types=1);

// Fallback when not using composer.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Jpereira\\DhcpdLeases\\';

    if (str_starts_with($class, $prefix)) {
        require __DIR__ . '/src/' . substr($class, strlen($prefix)) . '.php';
    }
});
