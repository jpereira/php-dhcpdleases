<?php

declare(strict_types=1);

use Jpereira\DhcpdLeases\DhcpdLeases;
use Jpereira\DhcpdLeases\LeaseFileException;

require __DIR__ . '/../autoload.php';

// Usage: php examples/json.php [leases-file] [mac]
$file = $argv[1] ?? $_GET['file'] ?? __DIR__ . '/../dhcpd.leases.sample';
$mac = $argv[2] ?? $_GET['mac'] ?? null;

try {
    $leases = new DhcpdLeases($file);

    if ($mac !== null) {
        $leases = $leases->where('hardware-ethernet', $mac);
    }

    if (count($leases) === 0) {
        http_response_code(404);
        $out = ['status' => 'error', 'msg' => 'not found'];
    } else {
        $out = $leases;
    }
} catch (LeaseFileException $e) {
    http_response_code(500);
    $out = ['status' => 'error', 'msg' => $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
