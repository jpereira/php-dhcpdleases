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

/**
 * Example of returned array
 *
 *   [0] => Array
 *   (
 *       [ip] => 210.243.29.123
 *       [time_start] => Array
 *           (
 *               [date] => 2014/11/03
 *               [hour] => 17:33:12;
 *           )
 *       [time_end] => Array
 *           (
 *               [date] => 2014/11/03
 *               [hour] => 17:43:12;
 *           )
 *       [binding-state] => active
 *       [next-binding-state] => free
 *       [hw] => 1c:65:20:b4:a7:aa
 *       [uid] => \001\234e\260\304\327\024
 *       [circuit_id] => WLAN:wlan3:a
 *       [client-hostname] => android-aa1be67476c410
 *   )
 */

class DhcpdLeases {
    var $lease_file = "/var/lib/dhcpd/dhcpd.leases";
    var $fp = -1;
    var $row_array = array();
    var $filter_field = null;
    var $filter_value = null;

    public function __construct($lease_file = null)
    {
        if ($lease_file != null)
            $this->lease_file = $lease_file;

        $this->fp = @fopen($this->lease_file, "r");
        if (!$this->fp)
            die("new DhcpdLeases(): No such file or directory in \"" . $this->lease_file . "\"");
    }

    public function __destruct()
    {
        if ($this->fp != null)
            fclose($this->fp);
    }

    function setFilter($field, $value)
    {
        $this->filter_field = $field;
        $this->filter_value = $value;
    }
    
    function process()
    {
        if (!$this->fp)
            return false;

        while (!feof($this->fp))
        {
            $read_line = fgets($this->fp, 4096);
            if (substr($read_line, 0, 1) != "#")
            {
	            $tok = strtok($read_line, " ");
                if ($tok == "lease")                // lease <ip> {
                {
                    unset($arr);
                    $arr['ip'] = strtok(" ");
                }
                elseif ($tok == "starts")           // start
                {
                    strtok(" ");
                    $arr['time_start']['date'] = strtok(" ");
                    $arr['time_start']['hour'] = strtok(" ");
                }
                elseif ($tok == "ends")             // ends
                {
                    strtok(" ");
                    $arr['time_end']['date'] = strtok(" ");
                    $arr['time_end']['hour'] = strtok(" ");
                }
                elseif ($tok == "hardware")         // hardware
                {
                    $field = strtok(" ");
                    if ($field == "ethernet")
                    {
                        $arr['hw'] = strtolower(strtok(";\n"));
                    }
                }
                elseif ($tok == "next")             // next binding state:
                {
                    $tok = strtok(" ");
                    if ($tok == "binding")
                    {
                        $tok = strtok(" ");
                        if ($tok == "state")
                            $arr['next-binding-state'] = strtok(";\n");
                    }
                }
                elseif ($tok == "binding")          // binding state:
                {
                    $tok = strtok(" ");
                    if ($tok == "state")
                    {
                        $arr['binding-state'] = strtok(";\n");
                    }
                }
                elseif ($tok == "client-hostname")  // client-hostname
                {
                    $arr['client-hostname'] = str_replace('"', "", strtok(";\n"));
                }
                elseif ($tok == "uid")              // uid
                {
                    $arr['uid'] = str_replace('"', "", strtok(";\n"));
                }
                elseif ($tok == "option")           // option { }
                {
                    $tok = strtok(" ");
                    if ($tok == "agent.circuit-id")
                    {
                       $arr['circuit_id'] = strtok("\";\n");
                    }
                    if ($tok == "agent.remote-id")
                    {
                       $arr['remote_id'] = strtok("\";\n");
                    }
                }
                elseif ($tok == "}\n")              // }
                {
                    unset($arr);
                }

                // check 
                if (isset($arr['ip']) &&
                    isset($arr['time_start']) &&
                    isset($arr['time_end']) &&
                    isset($arr['hw']) &&
                    isset($arr['next-binding-state']) &&
                    isset($arr['binding-state']) &&
                    isset($arr['client-hostname']) &&
                    isset($arr['uid'])
                    )
                {
                    if ($this->filter_value == $arr[$this->filter_field])
                        $this->row_array[] = $arr;
                    elseif (!$this->filter_field && !$this->filter_value)
                        $this->row_array[] = $arr;
                }
            }
        }

        return count($this->row_array) > 0;
    }

    function GetResult()
    {
        return $this->row_array;
    }
}

?>
