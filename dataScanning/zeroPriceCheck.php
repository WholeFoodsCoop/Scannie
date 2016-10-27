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

include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}

class zeroPriceCheck extends ScancoordDispatch
{
    
    protected $title = "Bad Price Scan";
    protected $description = "[Bad Price Scan] Scan for in-use items priced at 0.00 
        or greater than 99.00.";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        include('../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        
        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }
        
        $ret .= '<div class="container"><h5>Bad Prices for Products Page</h5>';
        $item = array ( array() );
        $ret = "";

        $query = $dbc->query("SELECT 
                upc,
                normal_price,
                brand,
                description,
                store_id,
                last_sold
            FROM products
            WHERE inUse=1
                AND (normal_price = 0 OR normal_price > 99)
                AND department NOT BETWEEN 508 AND 998
                AND department NOT BETWEEN 250 AND 259
                AND department NOT BETWEEN 225 AND 234
                AND department NOT BETWEEN 61 AND 78
                AND department != 46
                AND department != 150
                AND department != 208
                AND department != 235
                AND department != 240
                AND department != 500
                AND last_sold is not NULL
                AND upc <> 0001440035017
                AND upc <> 0085068400634
            GROUP BY upc
        ");
        $result = $dbc->execute($query);
        if($dbc->numRows($result) == 0 ) {
            echo '<div class="success">No badly priced items discovered.</div>';
        } else {
            echo  '<div class="danger" align="center">
                Items found with \'0.00\' or >\'99.00\' price!<br></div>';

            $ret .=  '<table class="table">';
            $ret .=  '<table class="container">';
            while ($row = mysql_fetch_assoc($result)) {
                $ret .=  '<tr><td><a href="http://key/git/fannie/item/ItemEditorPage.php?searchupc=' . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a>';
                $ret .=  '<td>' . $row['description'];
                $ret .=  '<td>' . $row['brand'];
                $ret .=  '<td> <i>store_id</i><b>: ' . $row['store_id'] . '</b>';
                $ret .=  '<td> <i>last sold on</i>: ' . $row['last_sold'];
                $ret .=  '<td> <a href="http://key/scancoord/TrackChangeNew.php?upc=' . $row['upc'] . '" target="_blank">Track Changes Made</a>';
            }
            if (mysql_errno() > 0) {
                $ret .=  mysql_errno() . ": " . mysql_error(). "<br>";
            }
            $ret .=  '</table>';
        }
        
        return $ret;
    }
    
}

ScancoordDispatch::conditionalExec();