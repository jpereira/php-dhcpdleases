<?php

declare(strict_types=1);

// Dependency-free tests. Run: php tests/run.php (or: composer test)

use Jpereira\DhcpdLeases\DhcpdLeases;
use Jpereira\DhcpdLeases\LeaseFileException;

require __DIR__ . '/../autoload.php';

$sample = __DIR__ . '/../dhcpd.leases.sample';
$failed = 0;
$total = 0;

function check(string $name, bool $ok): void
{
    global $failed, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n";
    $failed += $ok ? 0 : 1;
}

$all = new DhcpdLeases($sample);

check('375 unique leases', count($all) === 375);
check('default order is by ip', array_key_first($all->all()) === '10.107.248.102'
    && array_key_last($all->all()) === '192.168.3.240');

$l = $all->all()['192.168.3.143'];
check('hardware-ethernet', $l['hardware-ethernet'] === 'a0:36:9f:17:93:fa');
check('binding-state', $l['binding-state'] === 'free');
check('starts drops weekday', $l['starts'] === '2016/10/03 12:49:52');
check('cltt/tstp drop weekday', $l['cltt'] === '2016/10/03 12:49:52' && $l['tstp'] === '2016/10/03 18:49:52');
check('set vendor-class-identifier', $l['vendor-class-identifier'] === 'MSFT 5.0');
check('option agent.circuit-id', $l['agent.circuit-id'] === 'SW-ACC-02:Gi1/0/16:vlan3');
check('option agent.remote-id', $l['agent.remote-id'] === '00:25:45:d1:3e:00');
check('uid with quotes stripped', $all->all()['192.168.3.113']['uid'] === 'Cisco Systems Inc|+|UCSC-C240-M4S2|+|FCH1827V06M');

$active = $all->where('binding-state', 'active');
check('where active', count($active) === 127);
check('next-binding-state', $active->all()[array_key_first($active->all())]['next-binding-state'] === 'free');
check('where is case-insensitive', count($all->where('hardware-ethernet', 'F4:0F:1B:1E:79:C4')) === 2);
check('where vendor', count($all->where('vendor-class-identifier', 'MSFT 5.0')) === 85);
check('where remote-id', count($all->where('agent.remote-id', '00:25:45:d1:3e:00')) === 96);
check('where no match', count($all->where('client-hostname', 'nope')) === 0);
check('where is immutable', count($all) === 375);
check('where ANDed', count($active->where('vendor-class-identifier', 'MSFT 5.0')) < 85
    && count($active->where('vendor-class-identifier', 'MSFT 5.0')) > 0);
check('uniqueBy mac', count($all->uniqueBy('hardware-ethernet')) === 287);
check('orderBy', array_key_first($all->orderBy('client-hostname')->all()) === '10.107.248.103');
check('json', json_decode($all->toJson(), true) === $all->all());
check('jsonSerialize', json_encode($all) === $all->toJson());

$str = DhcpdLeases::fromString("lease 10.0.0.1 {\n  set vendor = \"abc\";\n  option host-name \"h1\";\n}\n");
check('fromString + set/option', $str->all() === ['10.0.0.1' => ['vendor' => 'abc', 'host-name' => 'h1']]);
check('rewind binding', DhcpdLeases::fromString("lease 1.1.1.1 {\n  rewind binding state free;\n}\n")->all()['1.1.1.1']['rewind-binding-state'] === 'free');
check('empty content', count(DhcpdLeases::fromString("# nothing\n")) === 0);

$t = DhcpdLeases::fromString(<<<'LEASES'
server-duid "\000\001";

lease 10.0.0.2 {
  starts epoch 1475564830; # Tue Oct  4 07:07:10 2016
  ends never;
  tstp 2 2016/10/04 08:00:16 -3600;
  cltt 2 2016/10/04 07:07:10;
  binding state active;
  rewind binding state free;
  reserved;
  dynamic-bootp;
  billing class "gold";
  billing subclass "vendors" "acme";
  hardware ethernet 00:11:22:33:44:55;
  uid 01:00:11:22:33:44:55;
  set ddns-fwd-name = "pc1.example.com";
  set n = %42;
  option agent.circuit-id 0:4:0:1;
  client-hostname "pc;1";
  on expiry or release {
    set x = "y";
    log (info, "bye");
  }
}
lease 10.0.0.3 {
  binding state free;
}
ia-na "\000\001\002" {
  cltt 2 2016/10/04 07:07:10;
  iaaddr 2001:db8::10 {
    binding state active;
    preferred-life 3600;
    max-life 7200;
    ends 2 2016/10/04 09:07:10;
  }
}
ia-pd "pd1" {
  iaprefix 2001:db8:1::/48 {
    binding state free;
  }
}
LEASES);
$x = $t->all()['10.0.0.2'];
check('epoch time -> utc string', $x['starts'] === '2016/10/04 07:07:10');
check('ends never', $x['ends'] === 'never');
check('tz offset applied', $x['tstp'] === '2016/10/04 07:00:16');
check('flags', $x['reserved'] === 'true' && $x['dynamic-bootp'] === 'true');
check('billing', $x['billing-class'] === 'gold' && $x['billing-subclass'] === 'vendors acme');
check('hex uid', $x['uid'] === '01:00:11:22:33:44:55');
check('set string/int', $x['ddns-fwd-name'] === 'pc1.example.com' && $x['n'] === '%42');
check('hostname with ;', $x['client-hostname'] === 'pc;1');
check('on expiry or release', $x['on-expiry-or-release'] === 'set x = "y"; log (info, "bye");');
check('on-block does not leak fields', !isset($x['x']) && !isset($x['log']));
check('lease after on-block parsed', $t->all()['10.0.0.3']['binding-state'] === 'free');
$v6 = $t->all()['2001:db8::10'];
check('ia-na', $v6['ia-type'] === 'na' && $v6['preferred-life'] === '3600' && $v6['max-life'] === '7200');
check('ia cltt/ends', $v6['cltt'] === '2016/10/04 07:07:10' && $v6['ends'] === '2016/10/04 09:07:10');
check('ia-pd prefix', $t->all()['2001:db8:1::/48']['ia-type'] === 'pd');
check('total entries', count($t) === 4);

try {
    (new DhcpdLeases('/nonexistent/dhcpd.leases'))->all();
    check('missing file throws', false);
} catch (LeaseFileException) {
    check('missing file throws', true);
}

check('version is semver and matches VERSION', preg_match('/^\d+\.\d+\.\d+$/', DhcpdLeases::version()) === 1
    && DhcpdLeases::version() === trim(file_get_contents(__DIR__ . '/../VERSION')));

echo "\n" . ($total - $failed) . "/$total passed\n";
exit($failed === 0 ? 0 : 1);
