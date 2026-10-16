<?php

declare(strict_types=1);

use Jpereira\DhcpdLeases\DhcpdLeases;
use Jpereira\DhcpdLeases\LeaseFileException;

require __DIR__ . '/../autoload.php';

// Try: php -S localhost:8000 -t examples  ->  http://localhost:8000/html.php?state=active
$file = __DIR__ . '/../dhcpd.leases.sample';
$state = $_GET['state'] ?? '';

$e = static fn(?string $s): string => htmlspecialchars($s ?? '', ENT_QUOTES);

try {
    $all = new DhcpdLeases($file);
    $leases = $state !== '' ? $all->where('binding-state', $state) : $all;
    $rows = $leases->orderBy('ends')->all();
    $rows = array_reverse($rows, true); // newest first

    $counts = [];
    foreach ($all->all() as $l) {
        $counts[$l['binding-state'] ?? 'unknown'] = ($counts[$l['binding-state'] ?? 'unknown'] ?? 0) + 1;
    }
    ksort($counts);
    $error = null;
} catch (LeaseFileException $ex) {
    $rows = $counts = [];
    $error = $ex->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DHCPd Leases</title>
<style>
:root {
  --bg: #f6f7f9; --card: #fff; --text: #1d2330; --muted: #6b7385; --line: #e6e8ee;
  --accent: #3b6cf6; --active: #1a9d5c; --free: #8a93a6; --expired: #d98a1f; --other: #c4457a;
}
@media (prefers-color-scheme: dark) {
  :root { --bg: #0f1218; --card: #171b24; --text: #e6e9f0; --muted: #8b93a7; --line: #262c3a; --accent: #6b8cff; }
}
* { box-sizing: border-box; }
body { margin: 0; background: var(--bg); color: var(--text); font: 14px/1.45 system-ui, sans-serif; }
main { max-width: 1280px; margin: 0 auto; padding: 2rem 1rem; }
h1 { margin: 0 0 .25rem; font-size: 1.6rem; }
.sub { color: var(--muted); margin-bottom: 1.5rem; }
.toolbar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: 1rem; }
.chip { padding: .3rem .75rem; border-radius: 999px; background: var(--card); border: 1px solid var(--line);
  color: var(--text); text-decoration: none; font-size: .85rem; }
.chip b { color: var(--muted); font-weight: 500; margin-left: .3rem; }
.chip.on { background: var(--accent); border-color: var(--accent); color: #fff; }
.chip.on b { color: #ffffffcc; }
input[type=search] { margin-left: auto; min-width: 16rem; padding: .45rem .8rem; border-radius: 8px;
  border: 1px solid var(--line); background: var(--card); color: var(--text); font: inherit; }
.card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; overflow: auto;
  max-height: 75vh; box-shadow: 0 1px 3px #0000000d; }
table { border-collapse: collapse; width: 100%; white-space: nowrap; }
th { position: sticky; top: 0; background: var(--card); text-align: left; font-size: .72rem;
  text-transform: uppercase; letter-spacing: .05em; color: var(--muted); padding: .7rem 1rem;
  border-bottom: 1px solid var(--line); }
td { padding: .55rem 1rem; border-bottom: 1px solid var(--line); }
tbody tr:last-child td { border-bottom: 0; }
tbody tr:hover { background: color-mix(in srgb, var(--accent) 7%, transparent); }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .85em; }
.muted { color: var(--muted); }
a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }
.badge { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; }
.badge::before { content: ""; width: .55rem; height: .55rem; border-radius: 50%; background: var(--other); }
.badge.active::before { background: var(--active); }
.badge.free::before { background: var(--free); }
.badge.expired::before, .badge.released::before { background: var(--expired); }
.empty, .error { padding: 2rem; text-align: center; color: var(--muted); }
.error { color: #d33; }
footer { margin-top: 1rem; color: var(--muted); font-size: .8rem; }
</style>
</head>
<body>
<main>
  <h1>DHCPd Leases</h1>
  <div class="sub"><?= $e(basename($file)) ?> &middot; <?= count($rows) ?> shown</div>

<?php if ($error !== null): ?>
  <div class="card"><div class="error"><?= $e($error) ?></div></div>
<?php else: ?>
  <div class="toolbar">
    <a class="chip <?= $state === '' ? 'on' : '' ?>" href="?">all<b><?= array_sum($counts) ?></b></a>
<?php foreach ($counts as $name => $n): ?>
    <a class="chip <?= $state === $name ? 'on' : '' ?>" href="?state=<?= $e($name) ?>"><?= $e($name) ?><b><?= $n ?></b></a>
<?php endforeach ?>
    <input type="search" id="q" placeholder="Filter by IP, MAC, host, vendor..." autofocus>
  </div>

  <div class="card">
<?php if (!$rows): ?>
    <div class="empty">No leases found.</div>
<?php else: ?>
    <table id="t">
      <thead>
        <tr>
          <th>IPv4</th><th>State</th><th>MAC</th><th>Hostname</th><th>Vendor class</th>
          <th>Circuit ID</th><th>Remote ID</th><th>Starts</th><th>Ends</th>
        </tr>
      </thead>
      <tbody>
<?php foreach ($rows as $ip => $l): $st = $l['binding-state'] ?? 'unknown'; ?>
        <tr>
          <td class="mono"><a href="http://<?= $e($ip) ?>"><?= $e($ip) ?></a></td>
          <td><span class="badge <?= $e($st) ?>"><?= $e($st) ?></span></td>
          <td class="mono"><?= $e($l['hardware-ethernet'] ?? '') ?></td>
          <td><?= $e($l['client-hostname'] ?? '') ?: '<span class="muted">-</span>' ?></td>
          <td><?= $e($l['vendor-class-identifier'] ?? '') ?: '<span class="muted">-</span>' ?></td>
          <td class="mono"><?= $e($l['agent.circuit-id'] ?? '') ?: '<span class="muted">-</span>' ?></td>
          <td class="mono"><?= $e($l['agent.remote-id'] ?? '') ?: '<span class="muted">-</span>' ?></td>
          <td class="mono muted"><?= $e($l['starts'] ?? '') ?></td>
          <td class="mono muted"><?= $e($l['ends'] ?? '') ?></td>
        </tr>
<?php endforeach ?>
      </tbody>
    </table>
<?php endif ?>
  </div>
  <footer>Times are UTC. <a href="https://github.com/jpereira/php-dhcpdleases">php-dhcpdleases</a></footer>
<?php endif ?>
</main>
<script>
const q = document.getElementById('q');
q?.addEventListener('input', () => {
  const needle = q.value.toLowerCase();
  document.querySelectorAll('#t tbody tr').forEach(tr => {
    tr.hidden = !tr.textContent.toLowerCase().includes(needle);
  });
});
</script>
</body>
</html>
