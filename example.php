<?php
/* sample using class.DhcpLeases.php */

require_once("class.DhcpdLeases.php");

$dl = new DhcpdLeases("dhcpd.leases");

$dl->setFilter("hw", "9c:65:b0:c4:d7:aa");

if (!$dl->process())
{
    echo "Not Found!";
}
else
{
    $arr = $dl->GetResult();
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}
?>
