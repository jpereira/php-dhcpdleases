<?php
/**
 * Author: Jorge Pereira <jpereiran@gmail.com>
 */
require_once("class.DhcpdLeases.php");

$isc_dhcpd_leases = "dhcpd.leases.sample";
$dl = new DhcpdLeases($isc_dhcpd_leases);
?>

<html>
<head>
<title>DHCPd Leases</title>
<style>
table {
  border-collapse: collapse;
  width: 90%;
}

table, th, td {
  border: 1px solid black;
}

tr:nth-child(even) {
  background-color: #f2f2f2;
}

</style>
</head>
<body bgcolor="#FFFFFF">
<center>
  <h1>DHCPd Leases</h1>
</center>

Last DHCP Clients with "<b>binding state active</b>"
<?php
if ($dl->process() < 1) {
  echo "No one values in our $isc_dhcpd_leases";
} else {
  $leases = $dl->GetResultArray();
  // echo "DEBUG: <pre>"; print_r($leases); echo "</pre>";
?>
<table>
  <tr align="center">
    <td><b>hardware-ethernet</b></td>
    <td><b>agent.remote-id</b></td>
    <td><b>agent.circuit-id</b></td>
    <td><b>vendor-class-identifier</b></td>
    <td><b>client-hostname</b></td>
    <td><b>IPv4</b></td>
  </tr>
<?php
  foreach ($leases as $k => $v) {
    $ip = $k;
    if ($v["binding"] != "state active")
      continue;
?>
  <tr>
    <td><?php echo @$v["hardware-ethernet"]; ?></td>
    <td><?php echo @$v["agent.remote-id"]; ?></td>
    <td><?php echo @$v["agent.circuit-id"]; ?></td>
    <td><?php echo @$v["vendor-class-identifier"]; ?></td>
    <td><?php echo @$v["client-hostname"]; ?></td>
    <td><?php printf("<a href=\"http://%s\">%s</a>", $ip, $ip); ?></td>
  </tr>
<?php
  }
?>
</table>
<?php
} 
?>
</body>
</html>
