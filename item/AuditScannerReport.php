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
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}
class AuditScannerReport extends ScancoordDispatch
{
    
    protected $title = "Audit Scanner Report";
    protected $description = "[Audit Scanner Report] View data from recent scan job.";
    protected $ui = TRUE;
    protected $add_javascript_content = TRUE;
    protected $must_authenticate = TRUE;
    
    private function clear_scandata_hander($dbc,$storeID,$username) 
    {
        
        $args = array($storeID,$username);
        $query = $dbc->prepare("DELETE FROM woodshed_no_replicate.AuditScanner WHERE store_id = ? AND username = ?");
        $dbc->execute($query,$args);
        
        return '
        <div align="center">
            <div class="alert alert-success">Data Cleared - <a href="AuditScannerReport.php">collapse message</a>
            </div>
        </div>
        ';
        
    }
    
    private function clear_notes_handler($dbc,$storeID,$username)
    {
        $args = array($storeID,$username);
        $query = $dbc->prepare("UPDATE woodshed_no_replicate.AuditScanner SET notes = 'n/a' WHERE store_id = ? AND username = ?");
        $dbc->execute($query,$args);
        
        return '
        <div align="center">
            <div class="alert alert-success">Notes Cleared - <a href="AuditScannerReport.php">collapse message</a>
            </div>
        </div>
        ';
    }
    
    private function update_scandata_handler($dbc,$storeID,$username)
    {
        
        $args = array($storeID,$username);
        $query = $dbc->prepare("SELECT upc FROM woodshed_no_replicate.AuditScanner WHERE store_id = ? AND username = ?");
        $res = $dbc->execute($query,$args);
        $upcs = array();
        while ($row = $dbc->fetchRow($res)) {
            $upcs[] = $row['upc'];
        }
        
        foreach ($upcs as $upc) {
            //$args = array($upc);

            $args = array($upc,$upc,$upc,$upc,$upc,$upc);
            $prep = $dbc->prepare("
                UPDATE woodshed_no_replicate.AuditScanner 
                SET price = (select normal_price from is4c_op.products where upc = ? group by upc),
                    prid = (select price_rule_id from is4c_op.products where upc = ? group by upc),
                    cost= (select cost from is4c_op.products where upc = ? group by upc),
                    description = (select description from is4c_op.products where upc = ? group by upc),
                    brand = (select brand from is4c_op.products where upc = ? group by upc)
                WHERE upc = ?;
            ");
            $dbc->execute($prep,$args);
            
            /*
            $prep = $dbc->prepare("SELECT normal_price,cost,brand,description,price_rule_id FROM products WHERE upc = ? GROUP BY upc");
            $res = $dbc->execute($prep,$upc);
            while ($row = $dbc->fetchRow($res)) {
                $description = $row['description'];
                $price = $row['normal_price'];
                $prid = $row['price_rule_id'];
                $cost = $row['cost'];
                $brand = $row['brand'];
            }
            
            $updateA = array($description,$price,$prid,$cost,$brand,$upc,$username,$store_id);
            $updateP = $dbc->prepare("
                UPDATE woodshed_no_replicate.AuditScanner
                SET description = ?, price = ?, prid = ?, cost = ?, brand = ?
                WHERE upc = ?
                    AND username = ?
                    AND store_id = ?
            ");
            $dbc->execute($updateP,$updateA);
            unset($description);
            unset($price);
            unset($prid);
            unset($cost);
            unset($brand);
            */
        }
        
        if ($er = $dbc->error()) {
            return '
                <div align="center">
                    <div class="alert alert-danger">'.$er.'</div>
                </div>
            ';
        } else {
            return '
                <div align="center">
                    <div class="alert alert-success">
                        Update Successful - 
                        <a href="AuditScannerReport.php">collapse message</a>
                    </div>
                    
                </div>
            ';
        }
        
        return false;
        
    }
    
    public function body_content()
    {           
    
        $ret = '';
        
        include('../config.php');
        include('../common/lib/PriceRounder.php');
        
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $storeID = scanLib::getStoreID(); 
        $username = scanLib::getUser();
        
        if ($_POST['cleardata']) {
            $ret .= $this->clear_scandata_hander($dbc,$storeID,$username);
        }
        
        if ($_POST['update']) {
            $ret .= $this->update_scandata_handler($dbc,$storeID,$username);
        }
        
        if ($_POST['clearNotes']) {
            $ret .= $this->clear_notes_handler($dbc,$storeID,$username);
        }
        
        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }
        
        $ret .= $this->form_content();
        
        $options = $this->get_notes_options($dbc,$storeID);
        $noteStr = '';
        $noteStr .= '<select id="notes" class="" style="font-size: 10px; font-weight: normal; margin-left: 5px; width: 100px;">';
        $noteStr .= '<option value="viewall">View All</option>';
        foreach ($options as $k => $option) {
            $noteStr .= '<option value="'.$k.'">'.$option.'</option>';
        }
        $noteStr .= '</select>';
        
        $ret .= '
            <table class="table table-bordered table-condensed small" style="width: 500px;"> 
                <tr class="key"><td>Key</td><td>
                </td></tr>
                <tr class="key"><td id="grey-toggle" style="background-color: lightgrey">&nbsp;</td><td>Product Missing Cost</td>
                <td id="blue-toggle" style="background-color: lightblue; width: 30px">&nbsp;</td><td>Price Above Margin</td></tr>
                <tr class="key"><td id="yellow-toggle" style="background-color: #FFF457">&nbsp;</td><td>Price Below Margin (M)</td>
                <td id="red-toggle" style="background-color: tomato; width: 30px; ">&nbsp;</td><td>Price Below Margin (L)</td></tr>
            </table>
        ';
        //$ret .= $btnUpdate;
        $args = array($username,$storeID);
        $query = $dbc->prepare("
        	SELECT upc, brand, description, cost, price, curMarg, desMarg, rsrp, srp, prid, flag, dept, vendor, notes, store_id
			FROM woodshed_no_replicate.AuditScanner 
            WHERE username = ? 
                AND store_id = ?
            ORDER BY vendor, dept, brand;
        ");    
        $result = $dbc->execute($query,$args);
        $data = array();
        $headers = array();
        $i = 0;
        //  Define <th> & <td> data
        while ($row = $dbc->fetch_row($result)) {
            foreach ($row as $k => $v) {
                if(!is_numeric($k)) {
                    //  @data[x][header] = row
                    $data[$i][$k] =  $v;
                    //  @headers[header] = header
                    $headers[$k] = $k;
                }
            }
            $i++;
        }
        
        //  Add columns to table
        $i = 0;
        $flags = array();
        foreach ($data as $k => $array) { 
            $srp = $data[$k]['srp'];
            $price = $data[$k]['price'];
            $difference = sprintf("%0.3f",$srp - $price);
            $margin = $data[$k]['curMarg'];
            $dMargin = $data[$k]['desMarg'];
            //  Create flags to change color of <tr>
            $margOff = ($margin / $dMargin);
            if ($margOff > 1.05 && $srp != $price) {
                $flags['info'][] = $i;
            } elseif ($margOff > 0.95) {
            } elseif ($margOff < 0.95 && $margOff > 0.90 
                && $srp != $price
                && $srp >= $price) {
                $flags['warning'][] = $i;
            } elseif ($srp != $price && $srp >= $price) {
                $flags['danger'][] = $i;
            }
            $i++;
        }
        
        $ret .= '
            <style>
                .price {
                    color: darkslategrey;
                    font-weight: bold;
                }
            </style>
        ';
        
        $ret .=  '<div class="panel panel-default">
            <table class="table table-condensed" id="dataTable">';
        $ret .=  '<thead class="float key" id="dataTableThead">
            <tr class="key">';
        foreach ($headers as $v) {
            if ($v == 'notes') {
                $ret .=  '<th class="key">' . $v . $noteStr . '</th>';
            } elseif($v == 'store_id') {
                $ret .=  '<th class="key">' . 'store' . '</th>';
            } elseif($v == NULL ) {
                //do nothing
            } else {
                $ret .=  '<th class="key">' . $v . '</th>';
            }
        }
        $ret .=  '</tr></thead>';
        $prevKey = '1';
        $ret .= '<tbody id="mytable">';
        $ret .=  '<tr class="key" class="highlight">';
        $upcs = array();
        foreach ($data as $k => $array) { 
            foreach ($array as $column_name  => $v) {
                if ($column_name == 'store_id') {
                    $ret .=  '<td class="store_id">' . $v . '</td>'; 
                } elseif ($column_name == 'notes') {
                    if ($v == NULL) {
                        $v = 'n/a';
                    }
                    $ret .=  '<td class="notescell">' . $v . '</td>'; 
                } elseif ($column_name == 'price' || $column_name == 'srp') {
                    $ret .=  '<td class="price">' . $v . '</td>'; 
                } else {
                    $ret .=  '<td>' . $v . '</td>'; 
                }
            }
            $ret .= '</tr>';
            
            $ret .= '
                <style>
                    .grey {
                        background-color:lightgrey;
                    }
                    #grey-toggle:hover {
                        cursor: pointer;
                    }
                    .red {
                        background-color:tomato; 
                        color:#700404
                    }
                    #red-toggle:hover {
                        cursor: pointer;
                    }
                    .yellow {
                        background-color:#FFF457; 
                        color: #635d00
                    }
                    #yellow-toggle:hover {
                        cursor: pointer;
                    }
                    .blue {
                        background-color:lightblue; 
                        color: #344c57
                    }
                    #blue-toggle:hover {
                        cursor: pointer;
                    }
                </style>
            ';
            
            if($prevKey != $k) {
                if ($data[$k+1]['cost'] == 0) {
                    $ret .=  '</tr><tr class="highlight grey" style="background-color:lightgrey">';
                } elseif (in_array(($k+1),$flags['danger']) && $data[$k+1]['prid'] == 0) {
                    $ret .=  '</tr><tr class="highlight red" style="background-color:tomato; color:#700404">';
	            } elseif (in_array(($k+1),$flags['warning']) && $data[$k+1]['prid'] == 0) {
                    $ret .=  '</tr><tr class="highlight yellow" style="background-color:#FFF457; color: #635d00">';
                } elseif (in_array(($k+1),$flags['info']) && $data[$k+1]['prid'] == 0) {
                    $ret .=  '</tr><tr class="highlight blue" style="background-color:lightblue; color: #344c57">';
                } else {
                    $ret .=  '</tr><tr class="highlight normal">';
                }
            } 
            $prevKey = $k;
        }
        $ret .=  '</tbody></table></div>';
        if ($dbc->error()) $ret .=  $dbc->error();
        
        if ($storeID == 1) {
            $OppoID = 2;
        } else {
            $OppoID = 1;
        }
        $ret .= $this->javascript_content($OppoID);
        
        return $ret;
    }
    
    private function get_notes_options($dbc,$storeID)
    {
        $args = array($storeID);
        $query = $dbc->prepare("SELECT notes FROM woodshed_no_replicate.AuditScanner WHERE store_id = ? GROUP BY notes;");    
        $result = $dbc->execute($query,$args);
        $options = array();
        while ($row = $dbc->fetch_row($result)) {
            $options[] = $row['notes'];
        }
        echo $dbc->error();
        return $options;
    }
    
    private function form_content()
    {
        
        $msgClear = 'Pressing OK will delete all data from this queue.';
        $msgUpdate = 'Pressing OK will update product data from Fannie.';
        $msgClearNotes = 'Pressing OK will clear all notes from this queue.';
        
        return '
            <div style="float: right;">
                <form method="post" id="myform">
                    <button type="submit" name="cleardata" id="cleardata" value="1" class="btn btn-danger btn-xs" 
                        onclick="return confirm(\''.$msgClear.'\')"  ">&nbsp;Clear&nbsp;</button> data
                </form>
                <form method="post">
                    <button type="submit" class="btn btn-default btn-xs" onclick="return confirm(\''.$msgUpdate.'\');">
                        update</button> data
                    <input type="hidden" name="update" value="1">
                </form>
                <form method="post">
                    <button type="submit" class="btn btn-default btn-xs" onclick="return confirm(\''.$msgClearNotes.'\');">
                        &nbsp;Clear&nbsp;</button> notes
                    <input type="hidden" name="clearNotes" value="1">
                </form>
                <a class="text-info" style="width: 132px" href="AuditScanner.php ">Goto Scanner</a><br />
            </div>
        ';
    }
    
    public function javascript_content($e)
    {
        ob_start();
        ?>
<script type="text/javascript">
    $("tr").each(function() { 
        var op_store = '.$e.';
        var id = $(this).find(\'td.store_id\').text();
        if (id == op_store) {
            $(this).closest(\'tr\').hide();
        }
    });
</script>

<script type="text/javascript">
$("#notes").change( function() {
    var noteKey = $("#notes").val();
    var note = $("#notes").find(":selected").text();
    $("#mytable").each(function() {
        $(this).find("tr").each(function() {
            $(this).show();
        });
    });
    $("#mytable").each(function() {
        $(this).find("tr").each(function() {
        var notecell = $(this).find(".notescell").text();
            if (note != notecell) {
                $(this).closest("tr").hide();
            }
            if (noteKey == "viewall") {
                $(this).show();
            }
            $(".blankrow").show();
        });
    });
});
</script>

<script type="text/javascript">    
    function redrawDataTable()
    {
        $('#dataTable').each(function() {
            $('tr').each(function () {
                $(this).show();
            });
        });   
    }

    $(document).ready(function () {
        $('#red-toggle').click(function () {
            redrawDataTable();
            $('#dataTable').each(function() {
                $('tr').each(function () {
                    if ( !$(this).hasClass('red') && !$(this).hasClass('key') ) {
                        $(this).hide();
                    }
                });
            });       
        });
        $('#yellow-toggle').click(function () {
            redrawDataTable();
            $('#dataTable').each(function() {
                $('tr').each(function () {
                    if ( !$(this).hasClass('yellow') && !$(this).hasClass('key') ) {
                        $(this).hide();
                    }
                });
            });       
        });
        $('#blue-toggle').click(function () {
            redrawDataTable();
            $('#dataTable').each(function() {
                $('tr').each(function () {
                    if ( !$(this).hasClass('blue') && !$(this).hasClass('key') ) {
                        $(this).hide();
                    }
                });
            });       
        });
        $('#grey-toggle').click(function () {
            redrawDataTable();
            $('#dataTable').each(function() {
                $('tr').each(function () {
                    if ( !$(this).hasClass('grey') && !$(this).hasClass('key') ) {
                        $(this).hide();
                    }
                });
            });       
        });
    });
</script>
        <?php
        return ob_get_clean();
    }
    
    
}
ScancoordDispatch::conditionalExec();