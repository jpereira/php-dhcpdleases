<?php

/**
 * This file is part of https://github.com/jpereira/php-dhcpdleases/
 *
 * php-dhcpdleases is free software: you can redistribute it and/or modify it under the terms
 * of the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * php-dhcpdleases is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with php-dhcpdleases. If not, see <http://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2014, Jorge Pereira <jpereiran@gmail.com>
 */

declare(strict_types=1);

namespace Jpereira\DhcpdLeases;

use Countable;
use JsonSerializable;

/**
 * Parser for ISC dhcpd.leases files.
 *
 * Leases are indexed by IP (IPv4 "lease", IPv6 "ia-na/ia-ta/ia-pd" addresses and prefixes). If an
 * address shows up more than once (the file is append-only), the last entry wins.
 *
 * Fields follow what dhcpd writes in server/db.c:
 *  - times: starts, ends, tstp, tsfp, atsfp, cltt, timestamp as "YYYY/MM/DD HH:MM:SS" (UTC), or
 *    "never". Both dhcpd formats are read ("W YYYY/MM/DD ..." and "epoch N")
 *  - binding-state, next-binding-state, rewind-binding-state, hardware-<type>, uid,
 *    client-hostname, billing-class, billing-subclass, reserved/dynamic-bootp ("true")
 *  - "option agent.*" and "set" values by name (agent.circuit-id, vendor-class-identifier, ...)
 *  - on-expiry, on-release, on-expiry-or-release: the statements, as text
 *  - IPv6 only: ia-type, ia-id, preferred-life, max-life
 *
 * Query methods (filter/order/unique) return a new instance, the original is untouched.
 *
 *     $leases = (new DhcpdLeases('/var/lib/dhcpd/dhcpd.leases'))
 *         ->where('binding-state', 'active')
 *         ->orderBy('client-hostname');
 *
 *     foreach ($leases->all() as $ip => $lease) { ... }
 */
final class DhcpdLeases implements Countable, JsonSerializable
{
    public const DEFAULT_LEASE_FILE = '/var/lib/dhcpd/dhcpd.leases';

    /** @var array<string, string> */
    private array $filters = [];
    private ?string $orderBy = null;
    private ?string $uniqueBy = null;
    /** @var array<string, array<string, string>>|null */
    private ?array $cache = null;

    public function __construct(
        private readonly string $leaseFile = self::DEFAULT_LEASE_FILE,
        private readonly ?string $contents = null,
    ) {
    }

    /** Build from the raw text instead of a file. */
    public static function fromString(string $contents): self
    {
        return new self('(string)', $contents);
    }

    public function getLeaseFile(): string
    {
        return $this->leaseFile;
    }

    /** Keep only leases whose $field equals $value (case-insensitive). Calls are ANDed. */
    public function where(string $field, string $value): self
    {
        $new = clone $this;
        $new->filters[strtolower($field)] = strtolower($value);

        return $new;
    }

    /** Sort by $field (leases without it go first). Default is by IP. */
    public function orderBy(string $field): self
    {
        $new = clone $this;
        $new->orderBy = $field;

        return $new;
    }

    /** Drop leases that repeat the value of $field, keeping the first one. */
    public function uniqueBy(string $field): self
    {
        $new = clone $this;
        $new->uniqueBy = $field;

        return $new;
    }

    /** @return array<string, array<string, string>> leases indexed by IP */
    public function all(): array
    {
        $leases = $this->parsed();

        foreach ($this->filters as $field => $value) {
            $leases = array_filter(
                $leases,
                static fn(array $l): bool => isset($l[$field]) && strtolower($l[$field]) === $value,
            );
        }

        if ($this->orderBy !== null) {
            $by = $this->orderBy;
            uasort($leases, static fn(array $a, array $b): int => strnatcmp($a[$by] ?? '', $b[$by] ?? ''));
        }

        if ($this->uniqueBy !== null) {
            $by = $this->uniqueBy;
            $seen = [];
            $leases = array_filter($leases, static function (array $l) use ($by, &$seen): bool {
                if (!isset($l[$by])) {
                    return true;
                }

                return !isset($seen[$l[$by]]) && ($seen[$l[$by]] = true);
            });
        }

        return $leases;
    }

    public function count(): int
    {
        return count($this->all());
    }

    /** @return array<string, array<string, string>> */
    public function jsonSerialize(): array
    {
        return $this->all();
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->all(), $flags | JSON_THROW_ON_ERROR);
    }

    /** @return array<string, array<string, string>> */
    private function parsed(): array
    {
        return $this->cache ??= self::parse($this->contents ?? $this->read());
    }

    private function read(): string
    {
        if (!is_file($this->leaseFile) || !is_readable($this->leaseFile)) {
            throw new LeaseFileException("No such readable file: \"{$this->leaseFile}\"");
        }

        $contents = file_get_contents($this->leaseFile);

        return $contents === false
            ? throw new LeaseFileException("Unable to read \"{$this->leaseFile}\"")
            : $contents;
    }

    /**
     * Mirrors what ISC dhcpd writes in server/db.c (write_lease, write_ia).
     *
     * @return array<string, array<string, string>>
     */
    private static function parse(string $contents): array
    {
        $leases = [];

        // IPv4 leases. dhcpd closes them with a "}" in the first column; "on expiry {...}" blocks
        // inside are closed by an indented one.
        preg_match_all('/^lease\s+(\S+)\s*\{(.*?)^\}/ms', $contents, $blocks, PREG_SET_ORDER);

        foreach ($blocks as [, $ip, $body]) {
            unset($leases[$ip]); // last one wins, and goes last
            $leases[$ip] = self::parseFields($body);
        }

        // IPv6: ia-na/ia-ta/ia-pd "iaid+duid" { cltt ...; iaaddr|iaprefix <addr> { ... } }
        preg_match_all('/^ia-(na|ta|pd)\s+(.*?)\s*\{(.*?)^\}/ms', $contents, $ias, PREG_SET_ORDER);

        foreach ($ias as [, $type, $id, $body]) {
            $cltt = preg_match('/^\s*cltt\s+(.*?)\s*;/m', $body, $m) ? self::time($m[1]) : null;

            preg_match_all('/^\s*(?:iaaddr|iaprefix)\s+(\S+)\s*\{(.*?)^ {2}\}/ms', $body, $subs, PREG_SET_ORDER);

            foreach ($subs as [, $addr, $subBody]) {
                $fields = ['ia-type' => $type, 'ia-id' => trim($id, '"')]
                    + ($cltt !== null ? ['cltt' => $cltt] : [])
                    + self::parseFields($subBody);

                unset($leases[$addr]);
                $leases[$addr] = $fields;
            }
        }

        ksort($leases, SORT_NATURAL);

        return $leases;
    }

    /** @return array<string, string> */
    private static function parseFields(string $body): array
    {
        $fields = [];

        // on expiry|release|expiry or release { statements }
        $body = preg_replace_callback(
            '/^\s*on\s+(expiry(?:\s+or\s+release)?|release)\s*\{(.*?)^\s{0,4}\}/ms',
            static function (array $m) use (&$fields): string {
                $name = 'on-' . preg_replace('/\s+/', '-', $m[1]);
                $fields[$name] = trim(preg_replace('/\s+/', ' ', $m[2]) ?? '');

                return '';
            },
            $body,
        ) ?? $body;

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            // "key value;" with an optional trailing "# comment" (dhcpd writes one after epoch times)
            if (!preg_match('/^\s*([\w.\-]+)(?:\s+(.*?))?\s*;\s*(?:#.*)?$/', $line, $m)) {
                continue;
            }

            [$key, $value] = self::normalize($m[1], $m[2] ?? '');

            if ($key !== '') {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }

    /** @return array{string, string} */
    private static function normalize(string $key, string $value): array
    {
        switch ($key) {
            case 'starts':
            case 'ends':
            case 'tstp':
            case 'tsfp':
            case 'atsfp':
            case 'cltt':
            case 'timestamp':
                return [$key, self::time($value)];

            case 'binding':
            case 'hardware':
                // "binding state active", "hardware ethernet aa:.."
                [$sub, $val] = preg_split('/\s+/', $value, 2) + [1 => ''];

                return ["$key-$sub", trim($val, '"')];

            case 'next':
            case 'rewind':
                // "next binding state free", "rewind binding state free"
                [, $sub, $val] = preg_split('/\s+/', $value, 3) + [1 => '', 2 => ''];

                return ["$key-binding-$sub", $val];

            case 'billing':
                // billing class "x"; / billing subclass "x" "data";
                [$sub, $val] = preg_split('/\s+/', $value, 2) + [1 => ''];

                return ["billing-$sub", str_replace('" "', ' ', trim($val, '"'))];

            case 'set':
            case 'option':
                return preg_match('/^([\w.\-]+)\s*=?\s*(.*)$/', $value, $m) ? [$m[1], trim($m[2], '"')] : ['', ''];

            case 'reserved':
            case 'dynamic-bootp':
                return [$key, 'true']; // flags, no value

            default:
                return [$key, trim($value, '"')];
        }
    }

    /**
     * dhcpd writes "never", "W YYYY/MM/DD HH:MM:SS[ tzoff]" (UTC) or, with db-time-format local,
     * "epoch N" (the "# human date" comment is dropped before we get here).
     * Everything but "never" becomes "YYYY/MM/DD HH:MM:SS" in UTC.
     */
    private static function time(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^epoch\s+(\d+)$/', $value, $m)) {
            return gmdate('Y/m/d H:i:s', (int) $m[1]);
        }

        if (preg_match('#^(?:\d\s+)?(\d{4}/\d\d/\d\d\s+\d\d:\d\d:\d\d)(?:\s+(-?\d+))?$#', $value, $m)) {
            $utc = gmmktime(
                (int) substr($m[1], 11, 2), (int) substr($m[1], 14, 2), (int) substr($m[1], 17, 2),
                (int) substr($m[1], 5, 2), (int) substr($m[1], 8, 2), (int) substr($m[1], 0, 4),
            );

            return isset($m[2]) && $m[2] !== '' ? gmdate('Y/m/d H:i:s', $utc + (int) $m[2]) : $m[1];
        }

        return $value; // "never"
    }
}
