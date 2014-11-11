<?php
/**
 *  This file is part of class.DhcpLeases.php
 *
 *    class.DhcpLeases.php is free software: you can redistribute it and/or modify it under the terms
 *  of the GNU Lesse General Public License as published by the Free Software Foundation, either
 *  version 3 of the License, or (at your option) any later version.
 *
 *  class.DhcpLeases.php is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *  See the GNU Lesse General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesse General Public License
 *  along with class.DhcpLeases.php.
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Copyright (C) 2014, Jorge Pereira <jpereiran@gmail.com>
 */
require_once("class.DhcpdLeases.php");

// main()
$dl = new DhcpdLeases("dhcpd.leases.sample");

$dl->setFilter("hardware-ethernet", strtolower("ac:65:c0:c4:d7:18"));

header("Content-Type: application/json");

if ($dl->process() < 1)
{
    echo "{ status: \"error\", msg: \"not found\"; }";
}
else
{
    
    echo $dl->GetResultJson();
}
?>

