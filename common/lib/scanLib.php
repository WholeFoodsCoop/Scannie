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
*
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
        $date = $year . '-' . $month . '-' . $day;
        $curY = date('Y');
        $curM = date('m');
        $curD = date('d');
        if (($year < $curY) or ($month < ($curM - 1)) or ($month < $curM && $day < $curD)) {
            echo "<td style='color:red'>" . $date . "</td>";
        } else {
            echo "<td>" . $date . "</td>";
        }   
    }
}


