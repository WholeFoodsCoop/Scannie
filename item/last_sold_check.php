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

class last_sold_check extends scancoordDispatch
{
    
    protected $title = "Last Sold Check";
    protected $description = "[Last Sold Check] Tracks last sale date, most 
        recent purchase order and displays Vendor Item information relavant
        to the matching SKU in relation to the purchase order.";
    protected $ui = TRUE;
    
    private function last_sold_check_list($dbc)
    {
        $ret = "";
        $ret .= '
            <form method="get">
                <textarea class="form-control" style="width:170px" name="upcs"></textarea>
                <input type="hidden" name="paste_list" value="1">
                <button type="submit" class="btn btn-xs">Submit</button>
            </form>
        ';

        if ($_GET['upcs']) {
            $upcs = $_GET['upcs'];
            $plus = array();
            $chunks = explode("\r\n", $upcs);
            foreach ($chunks as $key => $str) {
                $plus[] = $str;
            }
        }
        
        $ret .= '<table class="table table-striped table-condensed small" style="width:900 px;border:2px solid lightgrey">';
        $ret .= '
            <th>UPC</th>
            <th>Last Date Sold</th>
            <th>Store ID</th>
            <th>Descriptoin</th>
            <th>Brand</th>
        ';
        $curY = date('Y');
        $curM = date('m');
        $curD = date('d');
        foreach ($plus as $upc) {
            $query = "SELECT
                        last_sold, store_id, description, brand
                    FROM products 
                    WHERE upc = {$upc}
                    ORDER BY store_id
                ";
            $result = mysql_query($query, $dbc);
            while ($row = mysql_fetch_assoc($result)) {
                $last_sold = $row['last_sold'];
                if (is_null($last_sold)) {
                    $last_sold = "<span class='text-danger'>no recorded sales</span>";
                } else {
                    $year = substr($last_sold, 0, 4);
                    $month = substr($last_sold, 5, 2);
                    $day = substr($last_sold, 8, 2);

                    if (($year < $curY) or ($month < ($curM - 1)) or ($month < $curM && $day < $curD)) {
                        $last_sold = '<span class="text-danger">' . $last_sold = substr($last_sold,0,10) . '</span>';
                    } else {
                        $last_sold = '<span class="text-info">' . $last_sold = substr($last_sold,0,10) . '</span>';
                    }                       
                }
                $upcLink = '<a href="http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc=' . $upc . '" target="_blank">' . $upc . '</a>';
                $ret .= "<tr>";
                $ret .= "<td>" . $upcLink . "</td><td>" . $last_sold . "</td><td>" . $row['store_id'] . "</td><td>" . $row['description'] . "</td><td>" . $row['brand'] . "</td>";
                $ret .= "</tr>";
            }
        }
        $ret .= "</table>";
        
        return $ret;
    }
    
    public function body_content()
    {
        $ret = '';
        $item = array ( array() );
        $curY = date('Y');
        $curM = date('m');
        $curD = date('d');

        include('../config.php');
        $dbc = mysql_connect($SCANHOST, $SCANUSER, $SCANPASS);
        mysql_select_db($SCANDB, $dbc);
        
        $ret .= '<div class="container"><h4>Item Last Sold Check</h4>';
        $ret .= self::form_content();
        $ret .= '<a value="back" onClick="history.go(-1);return false;">BACK</a>';
		$ret .= "<span class='pipe'>&nbsp;|&nbsp;</span>";
		$upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        $ret .= '<a href="http://key/scancoord/item/TrackChangeNew.php?upc=' . $upc . '">TRACK CHANGE PAGE</a><br>';

        
        if ($_GET['paste_list']) {
            $ret .= self::last_sold_check_list($dbc);
        }
        
        $ret .= '<div class="container">';
        

        $query = "SELECT
                    last_sold, store_id
                FROM products 
                WHERE upc = {$_GET['upc']}
                ORDER BY store_id
            ";
        //$ret .= '<table class="table">';
        //$ret .= '<tr><td>upc: ' . $_GET['upc'] . '</td><td></td></tr>';

        /*
        $ret .= '
            <div class="row">
            <div class="col-mg-4">
            ' . $_GET['upc'] . '
            </div>
            </div>
        ';
        */ 
         
        if($_GET['upc']){
            $result = mysql_query($query, $dbc);
            $ret .= '
                
                    <div class="row">
                        <div class="col-md-4 panel panel-default">
                        <label>Product Last Sold On</label><br><br>
            ';
            $i = 0;
            while ($row = mysql_fetch_assoc($result)) {
                if ($row['store_id'] == 1) $row['store_id'] = 'Hillside';
                else $row['store_id'] = 'Denfeld';
                if ($i == 0) {
                    if ($row['last_sold'] == NULL) $ret .= '<div class="alert alert-danger sm">No sale records for this item at ' . $row['store_id'] . '.</div>';
                }
                $i++;
                //$ret .= '<tr><td>' . $row['store_id'] . '</td><td>' . substr($row['last_sold'], 0, 10) . '</tr>';
                $year = substr($row['last_sold'], 0, 4);
                $month = substr($row['last_sold'], 5, 2);
                $day = substr($row['last_sold'], 8, 2);
                
                //$ret .= $year . '<br>' . $month . '<br>'  . $day . '<br>'  . '<br>';
                //<div class="col-md-2">' . substr($row['last_sold'], 0, 10) . '</div>
                
                $ret .= '
                <div class="container">
                    <div class="row">
                        <div class="col-md-1">' . $row['store_id'] . '</div>
                ';
                        
                if (($year < $curY) or ($month < ($curM - 2))) $ret .= '<div class="col-md-2" style="color:red;">' . substr($row['last_sold'], 0, 10) . '</div>';
                else $ret .= '<div class="col-md-1">' . substr($row['last_sold'], 0, 10) . '</div>';
                        
                $ret .= '        
                        
                    </div><br>
                </div>
                ';
            }
            if (mysql_errno() > 0) {
                $ret .= mysql_errno() . ": " . mysql_error(). "<br>";
            }
            
            //$ret .= "</table>";
            $ret .= "</div>";
            $ret .= "</div>";
            $ret .= "</div>";

            //  Purchase Order Scanner
            $item = array ( array() );
            if ($_GET['id'] && isset($_GET['id'])) {
                $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
                $query = "
                    SELECT * 
                    FROM PurchaseOrderItems 
                    WHERE internalUPC={$_GET['upc']} 
                        AND receivedDate = (SELECT max(receivedDate) FROM PurchaseOrderItems WHERE internalUPC = {$_GET['upc']}); 
                ";
                $result = mysql_query($query, $dbc);
                $sku = 0;
                $ret .= '<div class="panel panel-default" style="width: 390px;">';
                $ret .= '
                    <div class="panel-heading">
                        <label style="color:darkslategrey;">Purchase Order Items</label>
                    </div>';
                $ret .= '<table class="table">';
                $ret .= '<th>SKU</th>';
                $ret .= '<th>Case Size</th>';
                $ret .= '<th>Received Date</th>';
                while ($row = mysql_fetch_assoc($result)) {
                    $ret .= '<tr><td>' . $row['sku'] . '</td>';
                    $ret .= '<td>' . $row['caseSize'] . '</td>';
                    $ret .= '<td>' . substr($row['receivedDate'], 0, 10) . '</td>';
                    $sku = $row['sku'];
                }
                if (mysql_errno() > 0) {
                    $ret .= mysql_errno() . ": " . mysql_error(). "<br>";
                } 
                $ret .= '</table>';
                $ret .='</div>';
                
                if ($sku) {
                    $ret .= '
                        <span class="alert-success">
                            Below is the most recent purchase order for this 
                            item.
                        </span><br><br>
                    ';
                }
                
                $query = "
                    SELECT upc
                    FROM VendorBreakdowns
                    WHERE vendorID = 1
                        AND upc = {$_GET['upc']}
                ";
                /*
                $result = mysql_query($query, $dbc);
                while ($row = mysql_fetch_assoc($result)){
                    $ret .= $row['upc'];
                }*/
                
                if (!$sku && $result = mysql_query($query, $dbc)) {
                    $ret .= '
                        <span class="alert-danger" align="center"> 
                        No purchase orders were found for this item. 
                        </span><br><br>
                    ';
                }
                    
                $query = "
                    SELECT
                        *
                    FROM vendorItems 
                    WHERE upc = {$_GET['upc']}
                        AND vendorID = 1
                ";
                $result = mysql_query($query, $dbc);
                $ret .= '<div class="panel panel-default">';
                $ret .= '
                    <div class="panel-heading">
                        <label style="color:darkslategrey;">Vendor Items</label>
                        <br><i>Latest cost on file for listed sku</i>
                    </div>';
                $ret .= '<table class="table">';
                $ret .= '<th>UPC</th>';
                $ret .= '<th>SKU</th>';
                $ret .= '<th>Brand</th>';
                $ret .= '<th>Description</th>';
                $ret .= '<th>Size</th>';
                $ret .= '<th>Units</th>';
                $ret .= '<th>Cost</th>';
                $ret .= '<th>Modified</th>';
                while ($row = mysql_fetch_assoc($result)) {
                    if ($row['sku'] == $sku) {
                        $ret .= '<tr class="success">';
                    } else {
                        $ret .= '<tr>';
                    }
                    $ret .= '<td><a href="http://key/git/fannie/item/ItemEditorPage.php?searchupc=' 
                        . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a></td>';
                    $ret .= '<td><b>' . $row['sku'] . '</b></td>';
                    $ret .= '<td>' . $row['brand'] . '</td>';
                    $ret .= '<td>' . $row['description'] . '</td>';
                    $ret .= '<td>' . $row['size'] . '</td>';
                    $ret .= '<td>' . $row['units'] . '</td>';
                    $ret .= '<td><b>' . $row['cost'] . '</b></td>';
                    $ret .= '<td>' . substr($row['modified'], 0, 10) . '</td>';
                }
                if (mysql_errno() > 0) {
                    $ret .= mysql_errno() . ": " . mysql_error(). "<br>";
                }    
                $ret .= '</table>';
                $ret .= '</div>';
                
                
                /*
                $query = "
                    SELECT
                        upc,
                        sku, 
                        size, 
                        cost, 
                        srp
                    FROM woodshed_no_replicate.unfipf_july
                    WHERE upc = {$_GET['upc']}
                ";
                $ret .= '
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <label style="color:darkslategrey;">UNFI Price File</label>
                            <br><i>Info from CMW final commitment for this UPC<br>I.T. - month => July. This must be changed manually.</i>
                        </div>
                ';
                $ret .= '<table class="table">';
                $ret .= '<th>UPC</th>';
                $ret .= '<th>SKU</th>';
                $ret .= '<th>Size</th>';
                $ret .= '<th>Cost</th>';
                $ret .= '<th>SRP</th>';
                $result = mysql_query($query, $dbc);
                while ($row = mysql_fetch_assoc($result)) {
                    if ($row['sku'] == $sku) {
                        $ret .= '<tr class="success">';
                    } else {
                        $ret .= '<tr>';
                    }
                    $ret .= '<td>' . $row['upc'] . '</td>';
                    $ret .= '<td>' . $row['sku'] . '</td>';
                    $ret .= '<td>' . $row['size'] . '</td>';
                    $ret .= '<td>' . $row['cost'] . '</td>';
                    $ret .= '<td>' . $row['srp'] . '</tr>';

                    
                }
                */
                               
                $ret .='<br>** if you are using this page while working on UNFI price change files -> if you cannot 
                figure out why the Batch Page is suggesting an incorrect price AND there is more than one row in 
                the <b>Vendor Items</b> panel, Batch Page is probably using the incorrect cost for this item. You
                should correct the cost of the item in the Item Editor Page.';
            } else {
                $ret .= 'This UPC is not recoginzed by Office.';
            }
            
            //$ret .= '</div><br>';
        }
        
        return $ret;
    }
    
    private function form_content()
    {
        return '
            <form method="get" class="form-inline">
                <input type="text" name="upc" class="form-control" placeholder="Enter upc" autofocus>
                <input type="hidden" name="id" value=1>
                <input type="submit" value="submit" id="btn-core" class="btn btn-default btn-core">
            </form>
            <form method="get">
                or <button type="submit" class="btn  btn-xs" " name="paste_list" value="1">Copy/Paste a List of UPCs</button>
            </form>
        ';
    }
    
}

scancoordDispatch::conditionalExec();

