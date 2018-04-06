<?php
/**
 *  This file is part of https://github.com/jpereira/php-dhcpdleases/
 *
 *    php-dhcpdleases is free software: you can redistribute it and/or modify it under the terms
 *  of the GNU Lesse General Public License as published by the Free Software Foundation, either
 *  version 3 of the License, or (at your option) any later version.
 *
 *  php-dhcpdleases is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *  See the GNU Lesse General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesse General Public License
 *  along with php-dhcpdleases.
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Copyright (C) 2014, Jorge Pereira <jpereiran@gmail.com>
 */

/**
 * Example of return.
 *
 *   [
 *       {
 *           "ip": "136.53.29.7",
 *           "time-start": "2014/11/03 17:33:12",
 *           "time-end": "2014/11/03 17:43:12",
 *           "binding-state": "active",
 *           "next-binding-state": "free",
 *           "hardware-ethernet": "9c:65:b0:c4:17:11",
 *           "uid": "\\001\\234e\\260\\304\\327\\024",
 *           "circuit-id": "\"Wsample",
 *           "client-hostname": "samsung-tv-122344"
 *       },
 *   ]
 */

class DhcpdLeases {
    var $lease_file   = "/var/lib/dhcpd/dhcpd.leases";
    var $row_array    = array();
    var $filter_field = null;
    var $filter_value = null;
    var $order_by     = null;
    var $uniq_by      = null;

    public function __construct($lease_file = null)
    {
        if ($lease_file != null)
            $this->lease_file = $lease_file;

        if (!file_exists($this->lease_file))
            die("new DhcpdLeases(): No such file or directory in \"" . $this->lease_file . "\"");
    }

    public function __destruct()
    {

    }

    /**
     * the valid fields.
     * 
     * "ip", "time-start", "time-end", "binding-state", "next-binding-state", "hardware-ethernet"
     * "uid", "circuit-id" and "client-hostname"
     */
    function setFilter($field, $value)
    {
        $this->filter_field = strtolower($field);
        $this->filter_value = strtolower($value);
    }

    function setOrderField($order_by) {
        $this->order_by = $order_by;
    }

    function setUniqKeysBy($uniq_by) {
        $this->uniq_by = $uniq_by;
    }

    /**
     * return total of results
     */
    function process()
    {
        $contents = file_get_contents($this->lease_file);
        $contents = explode("\n", $contents);
        $current  = 0;

        foreach ($contents as $line) {
            switch ($current) {
                case 0:
                    if (preg_match("/^\s*(|#.*)$/", $line, $m)) {
                        continue;
                    } else if (preg_match("/^lease (.*) {/", $line, $m)) {
                        $current = $m[1];
                    } else if (preg_match("/^(server-duid|authoring-byte-order)/", $line)) {
                        continue;
                    } else {
                        print "Failed parsing '$line'\n";
                    }
                    break;

                default: {
                    if (preg_match("/^\s*([a-zA-Z0-9\-\.]+) (.*);$/", $line, $m)) {
                        switch ($m[1]) {
                            case "hardware":
                                $h = explode(" ", $m[2]);
                                $m[1] = $m[1] . "-" . $h[0];
                                $m[2] = $h[1];
                            break;

                            case "set";
                            case "option";
                                if (!preg_match("/^\s*([a-zA-Z0-9\-\.]+)\s*[=]? (.*)$/", $m[2], $t)) {
                                    continue;
                                }
                                $m = $t;
                            break;
                        }

                        $m[2] = trim($m[2], '"');
                        if ($this->filter_field && $this->filter_value) {

                            if ($this->filter_value != $m[1]) {
                                //echo "TESTANDO: (".$this->filter_value." == ".$m[2].")<br>";
                                //continue;
                            }
                        }

                        $this->row_array[$current][$m[1]] = $m[2];
                    } elseif (preg_match("/}/", $line, $m)) {
                        $current = 0;
                    } else {
                        print "Failed parsing '$line'\n";
                    }
                }
            }
        }

        if ($this->order_by != null) {
            asort($this->row_array, function($a, $b) {
                return strcmp($a[$this->order_by], $b[$this->order_by]);
            });
        } else {
            ksort($this->row_array);
        }

        if ($this->uniq_by != null) {
            if (($arrTmp = array_column($this->row_array, $this->uniq_by)) == null) {
                echo "error: The field '$this->uniq_by' does not exist in the informed array";
                return -1;
            }

            $this->row_array = array_intersect_key($this->row_array, array_unique($arrTmp));
        }

        return count($this->row_array);
    }

    /**
     * return array with all results
     */
    function GetResultArray()
    {
        return $this->row_array;
    }

    /**
     * return json with all results
     */
    function GetResultJson()
    {
        return json_encode($this->row_array);
    }
} /* class DhcpdLeases */
?>
