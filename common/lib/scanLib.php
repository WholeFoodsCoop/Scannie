<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op.

    This file is a part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file LICENSE along with CORE-POS; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
*   @class scanLib
*   common methods included in all Scannie pages.
*/

class scanLib
{

    public function readStdin()
    {
        $this->read_stdin();
        return false;
    }

    public function stdin($msg)
    {
        self::read_stdin($msg);
        return false;
    }

    public function read_stdin($msg)
    {
        /**
        *	@read_stdin()
        *	Read input from command line.
        */
        echo $msg . ': ';
        $fr = fopen("php://stdin","r");
        $input = fgets($fr,128);
        $input = rtrim($input);
        fclose($fr);
        return $input;

    }

    public function check_date_downwards($year,$month,$day)
    {

        /**
        *   @function: check_date_downwards
            @purpose: In a table, take a datetime and return
            stylized table data with warning colors.
                dates < 1 month return with normal <td> color
                dates > 1 > 2 month return yellow
                dates > 2 > 3 months return orange,
                dates > 3 months return red
            @params: The year, month and date to compare
            against the current datetime.
            @returns: Table data contents
            e.g. '<td>'.[DATETIME].'</td>';
        */

        $ret = '';
        $date = $year . '-' . $month . '-' . $day;
        $curY = date('Y');
        $curM = date('m');
        $curD = date('d');
        if (($year == $curY) && ($month <= ($curM - 1)) && ($month >= ($curM-2))) {
            $ret .= "<td style='color:#ffd500'>" . $date . "</td>";
        } elseif (($year == $curY) && ($month < ($curM - 2))) {
            $ret .= "<td style='color:orange'>" . $date . "</td>";
        } elseif (($year < $curY) or ($month < ($curM - 3)) or ($month < $curM && $day < $curD)) {
            $ret .= "<td style='color:red'>" . $date . "</td>";
        } else {
            $ret .= "<td style='color:green'>" . $date . "</td>";
        }

        return $ret;
    }

    public function check_date_downwards_alert($year,$month,$day)
    {

        /**
        *   @function: check_date_downwards_alert
            @purpose: In a table, take a datetime and return
            stylized table data with warning colors.
                dates < 1 month return with normal <td> color
                dates > 1 > 2 month return yellow
                dates > 2 > 3 months return orange,
                dates > 3 months return red
            @params: The year, month and date to compare
            against the current datetime.
            @returns: <td> contents and alert level as array.
            e.g.
                'td' = '<td>'.[DATETIME].'</td>';
                'alert' = 0
        */

        $ret = '';
        $date = $year . '-' . $month . '-' . $day;
        $curY = date('Y');
        $curM = date('m');
        $curD = date('d');
        if (($year == $curY) && ($month <= ($curM - 1)) && ($month >= ($curM-2))) {
            $ret .= "<td style='color:#ffd500'>" . $date . "</td>";
            $data['alert'] = 1;
        } elseif (($year == $curY) && ($month < ($curM - 2))) {
            $ret .= "<td style='color:orange'>" . $date . "</td>";
            $data['alert'] = 2;
        } elseif (($year < $curY) or ($month < ($curM - 3)) or ($month < $curM && $day < $curD)) {
            $ret .= "<td style='color:red'>" . $date . "</td>";
            $data['alert'] = 3;
        } else {
            $ret .= "<td style='color:green'>" . $date . "</td>";
            $data['alert'] = 0;
        }

        $data['td'] = $ret;

        return $data;
    }

    public function dateAdjust($adjDay,$adjMonth,$adjYear)
    {
        /**
        *   Takes the current date and reduce (d,m,y) by values in argument.
        *   Returns the desired date in DATETIME format.
        */

        $curY = date('Y') - $adjYear;
        $curM = date('m') - $adjMonth;
        $curD = date('d') - $adjDay;

        $date = $curY . '-' . $curM . '-' . $curD;

        return $date;
    }

    public function getStoreID()
    {
        $remote_addr = $_SERVER['REMOTE_ADDR'];
        if(substr($remote_addr,0,2) == '10') {
            $store_id = 2;
        } else {
            $store_id = 1;
        }

        return $store_id;
    }

    public function getStoreName($storeID)
    {
        switch ($storeID) {
            case 1:
                return 'Hillside';
            case 2:
                return 'Denfeld';
            case 999:
                return 'UNKNOWN';
        }
    }

    public function convert_unix_time($secs) {
        $bit = array(
            'y' => $secs / 31556926 % 12,
            'w' => $secs / 604800 % 52,
            'd' => $secs / 86400 % 7,
            'h' => $secs / 3600 % 24,
            'm' => $secs / 60 % 60,
            's' => $secs % 60
            );

        foreach($bit as $k => $v)
            if($k == 's') {
                $ret[] = $v;
            } else {
                $ret[] = $v . ':';
            }
            if ($v == 0) $ret[] = '0';

        return join('', $ret);
    }

    public function getUser()
    {
        if (!empty($_SESSION['user_name'])) {
            return $_SESSION['user_name'];
        } else {
            return false;
        }
    }

    public function isDeviceIpod()
    {
        $device = $_SERVER['HTTP_USER_AGENT'];
        if (strstr($device,'iPod')) {
            return true;
        }
        return false;
    }

    /**
     *  @class upcPreparse
     *  @param str STRING upc to preparse.
     *  @return str STRING
     */

    public function upcPreparse($str)
    {
        $str = str_pad($str, 13, 0, STR_PAD_LEFT);
        if (substr($str,2,1) == '2') {
            /* UPC is for a re-pack scale item. */
            $str = '002' . substr($str,3,4) . '000000';
        } elseif (1) {

        }
        return $str;
    }
    
    public function upcParse($str)
    {
        $rstr = str_replace(" ","",$str);
        
        if (strstr($str,"-")) {
            $split = array();
            $split = preg_split("/[-]+/",$str);
        }
        $count = count($split);
        foreach ($split as $v) {
            if ($count == 4) {
                $rstr = $split[0].$split[1].$split[2];
            } elseif ($count == 2) {
                $rstr = $split[0].substr($split[1],0,5);
            }
        }
        
        if (strlen($rstr) != 13) {
            $rstr = str_pad($rstr, 13, 0, STR_PAD_LEFT);
        }
        
        return $rstr;
    }

}











