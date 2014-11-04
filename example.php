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

$hw = @$_GET['hw'];
if (empty($hw))
    die("need to set \$_GET['hw']");

$dl->setFilter("hardware-ethernet", strtolower($hw));

if ($dl->process() < 1)
{
    echo "Not Found!";
}
else
{
    echo $dl->GetResultJson();
}
?>
