
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
 *       [circuit-id] => WLAN:wlan3:a
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
    
    /**
     * return total of results
     */
    function process()
    {
	$row_len = 0;

        if (!$this->fp)
            return false;

        while (!feof($this->fp))
        {
            $read_line = fgets($this->fp, 4096);
            if (substr($read_line, 0, 1) != "#")
            {
	            $tok = strtok($read_line, " ");
                switch ($tok)
                {
                    case "lease":      // lease <ip> {
                        unset($arr);
                        $arr['ip'] = strtok(" ");
                        break;

                    case "starts":    // start
                        strtok(" ");
                        $arr['time-start'] = strtok(" ") . " " . strtok(";\n");
                        break;
                    
                    case "ends":      // ends
                        strtok(" ");
                        $arr['time-end'] = strtok(" ") . " " . strtok(";\n");
                        break;

                    case "hardware":  // hardware
                        $field = strtok(" ");
                        if ($field == "ethernet")
                        {
                            $arr['hw'] = strtolower(strtok(";\n"));
                        }
                        break;

                    case "next":         // next binding state:
                        $tok = strtok(" ");
                        if ($tok == "binding")
                        {
                            $tok = strtok(" ");
                            if ($tok == "state")
                                $arr['next-binding-state'] = strtok(";\n");
                        }
                        break;

                    case "binding":     // binding state:
                        $tok = strtok(" ");
                        if ($tok == "state")
                        {
                            $arr['binding-state'] = strtok(";\n");
                        }
                        break;

                    case "client-hostname":  // client-hostname
                        $arr['client-hostname'] = strtok("\";\n");
                        break;

                    case "uid":              // uid
                        $arr['uid'] = str_replace('"', "", strtok(";\n"));
                        break;

                    case "option":           // option { }
                        $tok = strtok(" ");
                        if ($tok == "agent.circuit-id")
                        {
                           $arr['circuit-id'] = preg_replace('/"(.*)";\n/', '${1}', strtok(" "));
                        }

                        if ($tok == "agent.remote-id")
                        {
                           $arr['remote-id'] = preg_replace('/"(.*)";\n/', '${1}', strtok(" "));
                        }
                        break;

                    case "}\n":             // }
                        unset($arr);
                        break;
                }

                if (isset($arr['ip']) &&
                    isset($arr['time-start']) &&
                    isset($arr['time-end']) &&
                    isset($arr['hw']) &&
                    isset($arr['next-binding-state']) &&
                    isset($arr['binding-state']) &&
                    isset($arr['client-hostname']) &&
                    isset($arr['uid'])
                    )
                {
                    if ($this->filter_value == $arr[$this->filter_field])
                        $this->row_array[$row_len++][] = $arr;
                    elseif (!$this->filter_field && !$this->filter_value)
                        $this->row_array[$row_len++][] = $arr;
                }
            }
        }

        return count($this->row_array);
    }

    /**
     * return array with all results
     */
    function GetResult()
    {
        return $this->row_array;
    }
}

?>

