# Changelog

All notable changes to this project. Format based on [Keep a Changelog](https://keepachangelog.com/),
versions follow [SemVer](https://semver.org/).

## [Unreleased]

## [2.0.1] - 2026-10-15

Big cleanup of the library. **Heads up:** the API and the result keys changed, see "Changed".

### Added
- Namespace `Jpereira\DhcpdLeases`, PSR-4 autoload (`composer.json`) and `autoload.php` fallback
- Lease file path is a constructor parameter (default `/var/lib/dhcpd/dhcpd.leases`) and
  `DhcpdLeases::fromString()` for raw contents
- Fluent, immutable query API: `where()`, `orderBy()`, `uniqueBy()`, plus `all()`, `count()`,
  `toJson()` and `JsonSerializable`
- `LeaseFileException` for missing/unreadable files
- Parser follows ISC dhcpd `server/db.c`: `starts`, `ends`, `tstp`, `tsfp`, `atsfp`, `cltt`,
  `timestamp` (`never`, `W Y/m/d H:i:s [tzoff]` and `epoch N`), `rewind binding state`,
  `reserved`, `dynamic-bootp`, `billing class/subclass`, `on expiry|release` and IPv6
  `ia-na`/`ia-ta`/`ia-pd`
- Tests (`php tests/run.php`), `Makefile` (`make help`, `make test`), GitHub Actions CI and
  release workflow, `VERSION` file with `DhcpdLeases::version()`
- Bigger real world `dhcpd.leases.sample` (375 leases) with `vendor-class-identifier` and
  option 82 (`agent.circuit-id`, `agent.remote-id`) on top
- `examples/html.php`: state filter, search box, more columns, light/dark theme

### Changed
- Requires PHP >= 8.2 (`declare(strict_types=1)`, readonly/promoted properties)
- Renamed methods: `process()`, `GetResultArray()`, `GetResultJson()`, `setFilter()`,
  `setOrderField()`, `setUniqKeysBy()` are replaced by the new API above
- Result keys: `binding => "state active"` is now `binding-state => "active"` (same for
  `next-binding-state` and `hardware-ethernet`). Times are normalized to UTC
  `YYYY/MM/DD HH:MM:SS` without the weekday
- Duplicated IPs in the file: the last lease wins
- `where()` is now an exact, case-insensitive match (the old filter was disabled in the code)
- `uniqueBy()` keeps leases that don't have the field instead of failing
- Missing file throws instead of `die()`; no more `print` on parse errors
- `examples/example.php` and `example2.php` became `examples/json.php` and `examples/html.php`
  (output is escaped now)
- README rewritten in en_US

### Removed
- `class.DhcpdLeases.php`

## [2.0] - 2018-04-06

### Added
- `setOrderField()`: order the result
- `setUniqKeysBy()`: avoid repeated values
- `example2.php`: HTML version of the example

### Changed
- Replaced the parser approach, `set`/`option` parsing fixed
- Tabs replaced by spaces
- Removed `wrapper_array2json()`, fixed `example.php`

## [1.1] - 2015-07-21

### Fixed
- `;` inside the `circuit-id` field
- `value` and `field` of the filter are always lower case

## [1.0] - 2015-05-13

### Fixed
- Removed all new-lines (`\n`) from the result
- Wrong agreement in the filter (refs #1, #2)

## [0.1rc0] - 2015-03-26

First release candidate: reads `dhcpd.leases`, returns the leases as an array or JSON, with
filter by field/value and a README example.

[Unreleased]: https://github.com/jpereira/php-dhcpdleases/compare/v2.0.1...HEAD
[2.0.1]: https://github.com/jpereira/php-dhcpdleases/compare/v2.0...v2.0.1
[2.0]: https://github.com/jpereira/php-dhcpdleases/compare/v1.1...v2.0
[1.1]: https://github.com/jpereira/php-dhcpdleases/compare/v1.0...v1.1
[1.0]: https://github.com/jpereira/php-dhcpdleases/compare/v0.1rc0...v1.0
[0.1rc0]: https://github.com/jpereira/php-dhcpdleases/releases/tag/v0.1rc0
