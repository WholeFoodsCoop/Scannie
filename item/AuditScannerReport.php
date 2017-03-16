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
            <div class="alert alert-success">Data Cleared<br /> <a href="http://192.168.1.2/scancoord/item/AuditScannerReport.php" 
                class="btn btn-success btn-xs">Please - Click Me - </a>
            </div>
        </div>
        ';
    }
    
    public function body_content()
    {           
    
        $ret = '';
        
        include('../config.php');
        include('../common/lib/PriceRounder.php');
        include('../common/lib/scanLib.php');
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $storeID = scanLib::getStoreID(); 
        $username = scanLib::getUser();
        
        if ($_POST['cleardata']) {
            $ret .= $this->clear_scandata_hander($dbc,$storeID,$username);
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
                <tr><td>Key</td><td></td></tr>
                <tr><td style="background-color: lightgrey">&nbsp;</td><td>Product Missing Cost</td>
                <td style="background-color: lightblue; width: 30px">&nbsp;</td><td>Price Above Margin</td></tr>
                <tr><td style="background-color: #FFF457">&nbsp;</td><td>Price Below Margin (M)</td>
                <td style="background-color: tomato; width: 30px; ">&nbsp;</td><td>Price Below Margin (L)</td></tr>
            </table>
        ';
        
        $query = $dbc->prepare("
        	SELECT upc, brand, description, cost, price, curMarg, desMarg, rsrp, srp, prid, flag, dept, vendor, notes, store_id
			FROM woodshed_no_replicate.AuditScanner 
            WHERE username = ?
            ORDER BY vendor, dept;
        ");    
        $result = $dbc->execute($query,$username);
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
        
        //  Add columns to talbe
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
            if ($margOff > 1.05) {
                $flags['info'][] = $i;
            } elseif ($margOff > 0.95) {
            } elseif ($margOff < 0.95 && $margOff > 0.90) {
                $flags['warning'][] = $i;
            } else {
                $flags['danger'][] = $i;
            }
            $i++;
        }
        
        $ret .=  '<div class="panel panel-default">
            <table class="table table-condensed">';
        $ret .=  '<thead class="float">';
        foreach ($headers as $v) {
            if ($v == 'notes') {
                $ret .=  '<th>' . $v . $noteStr . '</th>';
            } elseif($v == 'store_id') {
                $ret .=  '<th>' . 'store' . '</th>';
            } else {
                $ret .=  '<th>' . $v . '</th>';
            }
        }
        $ret .=  '</thead>';
        $prevKey = '1';
        $ret .= '<tbody id="mytable">';
        $ret .=  '<tr class="highlight">';
        foreach ($data as $k => $array) { 
            foreach ($array as $column_name  => $v) {
                if ($column_name == 'store_id') {
                    $ret .=  '<td class="store_id">' . $v . '</td>'; 
                } elseif ($column_name == 'notes') {
                    $ret .=  '<td class="notescell">' . $v . '</td>'; 
                } else {
                    $ret .=  '<td>' . $v . '</td>'; 
                }
            }
            if($prevKey != $k) {
                if ($data[$k+1]['cost'] == 0) {
                    $ret .=  '</tr><tr class="highlight" style="background-color:lightgrey; ">';
                } elseif (in_array(($k+1),$flags['danger']) && $data[$k+1]['prid'] == 0) {
                    $ret .=  '</tr><tr class="highlight" style="background-color:tomato; color:#700404">';
	            } elseif (in_array(($k+1),$flags['warning']) && $data[$k+1]['prid'] == 0) {
                    $ret .=  '</tr><tr class="highlight" style="background-color:#FFF457; color: #635d00">';
                } elseif (in_array(($k+1),$flags['info']) && $data[$k+1]['prid'] == 0) {
                    $ret .=  '</tr><tr class="highlight" style="background-color:lightblue; color: #344c57">';
                } else {
                    $ret .=  '</tr><tr class="highlight">';
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
        return '
            <form method="post" id="myform">
                <div align="right">
                    <button type="submit" name="cleardata" id="cleardata" value="1" class="btn btn-danger " 
                        onclick="return confirm(\'Are you sure?\')" style="border: 2px solid red; ">Clear Scan Data</button><br />
                        <br />
                    <a class="text-info" style="width: 132px" href="AuditScanner.php ">Goto Scanner</a>&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
            </form>
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
        <?php
        return ob_get_clean();
    }
    
    
}
ScancoordDispatch::conditionalExec();