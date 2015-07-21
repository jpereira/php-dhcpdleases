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

    /**
     * the valid fields.
     * 
     * "ip", "time-start", "time-end", "binding-state", "next-binding-state", "hardware-ethernet"
     * "uid", "circuit-id" and "client-hostname"
     */
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
                            $arr['hardware-ethernet'] = strtolower(strtok(";\n"));
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
                           $arr['circuit-id'] = preg_replace('/"(.*)"\n/', '${1}', strtok("\n"));
                           $arr['circuit-id'] = preg_replace('/(;$|\")', '', $arr['circuit-id']);
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
                    isset($arr['hardware-ethernet']) &&
                    isset($arr['next-binding-state']) &&
                    isset($arr['binding-state']) &&
                    isset($arr['client-hostname']))
                    )
                {
                    if ($this->filter_value == $arr[$this->filter_field] ||
                              !$this->filter_field && !$this->filter_value) {
                        $this->row_array[] = str_replace("\n", "", $arr);
                    }
                }
            }
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
        if (function_exists("json_encode"))
            return json_encode($this->row_array);
        else
            return $this->wrapper_array2json($this->row_array);
    }

    /**
     * wrapper used to convert array to json
     */
    private function wrapper_array2json($arr)
    { 
        $parts = array(); 
        $is_list = false; 
        $keys = array_keys($arr); 
        $max_length = count($arr) - 1;

        if(($keys[0] == 0) and ($keys[$max_length] == $max_length))
        {
            $is_list = true;
            for($i=0; $i < count($keys); $i++)
            {
                if($i != $keys[$i])
                {
                    $is_list = false;
                    break;
                }
            }
        }

        foreach($arr as $key=>$value)
        { 
            if(is_array($value))
            {
                if ($is_list)
                    $parts[] = $this->wrapper_array2json($value);
                else
                    $parts[] = '"' . $key . '":' . $this->wrapper_array2json($value);
            }
            else
            { 
                $str = ''; 
                if(!$is_list)
                    $str = '"' . $key . '":'; 

                if(is_numeric($value))
                    $str .= $value;
                elseif($value === false)
                    $str .= 'false';
                elseif($value === true)
                    $str .= 'true'; 
                else
                    $str .= '"' . addslashes($value) . '"';
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?) 
                $parts[] = $str; 
            } 
        } 
        $json = implode(',',$parts); 
         
        if ($is_list)
            return '[' . $json . ']';
        return '{' . $json . '}';
    }
} /* class DhcpdLeases */
?>
