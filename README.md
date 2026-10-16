# php-dhcpdleases

[![CI](https://github.com/jpereira/php-dhcpdleases/actions/workflows/ci.yml/badge.svg)](https://github.com/jpereira/php-dhcpdleases/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/jpereira/php-dhcpdleases?sort=semver)](https://github.com/jpereira/php-dhcpdleases/releases)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.2-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-LGPL--3.0--or--later-blue)](LICENSE)
[![Last commit](https://img.shields.io/github/last-commit/jpereira/php-dhcpdleases)](https://github.com/jpereira/php-dhcpdleases/commits/master)

PHP parser for ISC DHCP `dhcpd.leases` files. Reads the leases, then filters, sorts and
de-duplicates them, returning an array or JSON.

- PHP >= 8.2, no dependencies
- Immutable: `where()`, `orderBy()` and `uniqueBy()` return a new instance
- If an IP shows up more than once in the file, the last lease wins

## Installation

```bash
composer require jpereira/php-dhcpdleases
```

Without composer: `require 'autoload.php';`

## Usage

```php
<?php

use Jpereira\DhcpdLeases\DhcpdLeases;

require 'vendor/autoload.php';

// The path is optional, defaults to /var/lib/dhcpd/dhcpd.leases
$leases = (new DhcpdLeases('/etc/dhcp/dhcpd.leases'))
    ->where('binding-state', 'active')
    ->orderBy('client-hostname')
    ->uniqueBy('hardware-ethernet');

echo count($leases), " leases\n";

foreach ($leases->all() as $ip => $lease) {
    printf("%s  %s  %s\n", $ip, $lease['hardware-ethernet'], $lease['client-hostname'] ?? '-');
}

echo $leases->toJson(JSON_PRETTY_PRINT);

// It also accepts the raw contents
$leases = DhcpdLeases::fromString(file_get_contents('dhcpd.leases.sample'));
```

A missing or unreadable file throws `Jpereira\DhcpdLeases\LeaseFileException`.

Output (indexed by IP):

```json
{
    "192.168.3.107": {
        "starts": "2016/10/25 05:08:55",
        "ends": "2016/10/25 11:08:55",
        "cltt": "2016/10/25 05:08:55",
        "binding-state": "active",
        "next-binding-state": "free",
        "hardware-ethernet": "00:26:cb:85:63:42",
        "uid": "\\000cisco-0026.cb85.6342-Vl3",
        "vendor-class-identifier": "Cisco Systems, Inc. Catalyst",
        "agent.circuit-id": "SW-ACC-02:Gi1/0/22:vlan3",
        "agent.remote-id": "00:25:45:d1:3e:00",
        "client-hostname": "MT-3750-24-A8"
    }
}
```

## Examples

```bash
php examples/json.php dhcpd.leases.sample 00:26:cb:85:63:42
php -S localhost:8000 -t examples   # http://localhost:8000/html.php
```

## Tests

```bash
make test   # or: php tests/run.php
```

Run `make` to see all targets. Without a local PHP, `make test` falls back to Docker.

## Versioning

[SemVer](https://semver.org/). The current version is in the [VERSION](VERSION) file and in
`DhcpdLeases::version()`. What changed is in the [CHANGELOG](CHANGELOG.md).

```bash
make version                # show it
make bump PART=patch        # patch|minor|major|X.Y.Z: updates VERSION and CHANGELOG.md
git commit -am "Release v2.0.2" && git tag v2.0.2 && git push --follow-tags
```

Pushing a `v*` tag runs the release workflow: it checks the tag against `VERSION`, runs the
tests and creates the GitHub release with the notes from the CHANGELOG.

## Author

Jorge Pereira <jpereiran@gmail.com>

## License

LGPL-3.0-or-later, see [LICENSE](LICENSE).
