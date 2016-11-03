<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Community Co-op.
    
    This file is a part of Scannie.
    
    Scannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Scannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file LICENSE along with Scannie; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    
*********************************************************************************/

include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}

class TrackChangeNew extends ScancoordDispatch
{
    
    protected $title = "Track Change";
    protected $description = "[Track Change] Track all changes made to an item in POS/OFFICE.";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';

        $desc = array();
        $salePrice = array();
        $cost = array();
        $dept = array();
        $tax = array();
        $fs = array();
        $scale = array();
        $modified = array();
        $name = array();
        $realName = array();
        $uid = array();

        include('../config.php');
        $dbc = mysql_connect($SCANHOST, $SCANUSER, $SCANPASS);
        mysql_select_db($SCANDB, $dbc);
        
        $ret .= '<div class="container-fluid">';
        $ret .=  self::form_content();

        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
            
            //* Create the table if it doesn't exist */
            $query = "SELECT pu.description,
                        pu.salePrice,
                        pu.price,
                        pu.cost,
                        pu.dept,
                        pu.tax,
                        pu.fs,
                        pu.scale,
                        pu.modified,
                        u.name, 
                        u.real_name,
                        u.uid,
                        pu.upc,
                        pu.wic,
                        pu.storeID,
                        pu.inUse
                    FROM prodUpdate as pu
                    LEFT JOIN Users as u on u.uid=pu.user
                    WHERE pu.upc='{$upc}'
                    GROUP BY pu.modified, pu.storeID
                    ORDER BY modified DESC
                    ;";
            $result = mysql_query($query, $dbc);
            while ($row = mysql_fetch_assoc($result)) {
                $desc[] = $row['description'];
                $price[] = $row['price'];
                $salePrice[] = $row['salePrice'];
                $cost[] = $row['cost'];
                $dept[] = $row['dept'];
                $tax[] = $row['tax'];
                $fs[] = $row['fs'];
                $scale[] = $row['scale'];
                $modified[] = $row['modified'];
                $name[] = $row['name'];
                $realName[] = $row['real_name'];
                $uid[] = $row['uid'];
                $wic[] = $row['wic'];
                $store_id[] = $row['storeID'];
                $inUse[] = $row['inUse'];
            }
            $upcLink = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc=" 
                    . $upc . "&ntype=UPC&searchBtn=' target='_blank'>{$upc}</a>";
            $ret .=  "<div>Changes made to " . $upcLink . " <b>" . $desc[max(array_keys($desc))] . "</b></div>";
            $ret .=  "<div><i>*Changes now being sorted from newest to oldest.*</i></div>
              <a value='back' onClick='history.go(-1);return false;'>BACK</a>
			  <span class='pipe'>&nbsp;|&nbsp;</span>
              <a href='http://key/scancoord/item/last_sold_check.php?upc=" . $upc . "+&id=1'>LAST SOLD PAGE</a>
                <br>";
                
                //<INPUT Type="button" VALUE="Back" onClick="history.go(-1);return true;">
            if (mysql_errno() > 0) {
                $ret .=  mysql_errno() . ": " . mysql_error(). "<br>";
            }

            $ret .=  "<div class='panel panel-default'>";
            $ret .=  "<table class='table' style='color:black'>";
            $ret .=  "
                <tr><td>Description</td>
                <td>Price</td><td>Sale</td>
                <td>Cost</td>
                <td>Dept</td>
                <td>Tax</td>
                <td>FS</td>
                <td>Scale</td>
                <td>wic</td>
                <td>store</td>
                <td>In Use</td>
                <td>Modified</td>
                <td>Modified By</td>
                </tr>
            ";
            for ($i=0; $i<count($desc); $i++) {
                if($store_id[$i] == 1) $store_id[$i] = "Hillside";
                if($store_id[$i] == 2) $store_id[$i] = "Denfeld";
            }
            for ($i=0; $i<count($desc); $i++) {
                if ($cost[$i] != $cost[$i-1]
                    || $salePrice[$i] != $salePrice[$i-1]
                    || $cost[$i] != $cost[$i-1]
                    || $tax[$i] != $tax[$i-1]
                    || $fs[$i] != $fs[$i-1]
                    || $scale[$i] != $scale[$i-1]
                    || $desc[$i] != $desc[$i-1]
                    || $price[$i] != $price[$i-1]
                    || $dept[$i] != $dept[$i-1]
                    || $wic[$i] != $wic[$i-1]
                    || $realName[$i] != $realName[$i-1]
                    || $store_id[$i] != $store_id[$i-1]
                    || $inUse[$i] != $inUse[$i-1]
                ) 
                {   
                    if ($store_id[$i] == 'Hillside') {
                        $ret .=  "<tr >";
                    } else {
                        $ret .=  "<tr class='warning'>";
                    }
                    
                    $ret .=  "<td>" . $desc[$i] . "</td>";
                    $ret .=  "<td>" . $price[$i] . "</td>";
                    $ret .=  "<td>" . $salePrice[$i]  . "</td>";
                    $ret .=  "<td>" . $cost[$i] . "</td>";
                    $ret .=  "<td>" . $dept[$i] . "</td>";
                    $ret .=  "<td>" . $tax[$i] . "</td>";
                    $ret .=  "<td>" . $fs[$i] . "</td>";
                    $ret .=  "<td>" . $scale[$i] . "</td>";
                    $ret .=  "<td>" . $wic[$i] . "</td>";
                    $ret .=  "<td>" . $store_id[$i] . "</td>";
                    $ret .=  "<td>" . $inUse[$i] . "</td>";
                    $ret .=  "<td>" . $modified[$i] . "</td> ";
                    if ($realName[$i] == NULL) {
                        $ret .=  "<td><i>unknown / scheduled change " . $uid[$i] . "</i></tr>";
                    } else {
                        $ret .=  "<td>" . $realName[$i] . "</tr> ";
                    }
                }
                
            }
            $ret .=  "</table>";
            $ret .=  "</div></div>";    // <- panel / container
        }
        
        return $ret;
    }
    
    private function form_content()
    {
        return '
            <h4>Product Change Check</h4>
                <form class ="form-inline"  method="get" > 
                    <div class="form-group">    
                        <input type="text" class="form-control" name="upc" placeholder="enter plu to track" autofocus>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default" value="submit">
                    </div>
                </form>
        ';
    }
    
}
ScancoordDispatch::conditionalExec();

 
