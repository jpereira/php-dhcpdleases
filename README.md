Example of use

            <?php
            /* sample using class.DhcpLeases.php */

            require_once("class.DhcpdLeases.php");

            $dl = new DhcpdLeases("dhcpd.leases.sample");

            $dl->setFilter("hardware-ethernet", strtolower("9c:65:b0:c4:17:11"));

            if ($dl->process() < 1)
            {
                echo "Not Found!";
            }
            else
            {
                echo $dl->GetResultJson();
            }
            ?>

Returning jSON object!

            [
                {
                    "ip": "136.53.29.7",
                    "time-start": "2014/11/03 17:33:12",
                    "time-end": "2014/11/03 17:43:12",
                    "binding-state": "active",
                    "next-binding-state": "free",
                    "hardware-ethernet": "9c:65:b0:c4:17:11",
                    "uid": "\\001\\234e\\260\\304\\327\\024",
                    "circuit-id": "\"Wsample",
                    "client-hostname": "samsung-tv-122344"
                },
            ]

