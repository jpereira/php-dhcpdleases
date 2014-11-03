Example of use

            <?php
            /* sample using class.DhcpLeases.php */

            require_once("class.DhcpdLeases.php");

            $dl = new DhcpdLeases("dhcpd.leases");

            $dl->setFilter("hw", "1c:65:20:b4:a7:aa");

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

Example of returned array

          [0] => Array
          (
              [ip] => 210.243.29.123
              [time_start] => Array
                  (
                      [date] => 2014/11/03
                      [hour] => 17:33:12;
                  )
              [time_end] => Array
                  (
                      [date] => 2014/11/03
                      [hour] => 17:43:12;
                  )
              [binding-state] => active
              [next-binding-state] => free
              [hw] => 1c:65:20:b4:a7:aa
              [uid] => \001\234e\260\304\327\024
              [circuit_id] => WLAN:wlan3:a
              [client-hostname] => android-aa1be67476c410
          )

