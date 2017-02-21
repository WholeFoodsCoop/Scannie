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
    
    private function clear_scandata_hander($dbc,$storeID) 
    {
        
        $args = array($storeID);
        $query = $dbc->prepare("DELETE FROM woodshed_no_replicate.AuditScanner WHERE store_id = ?");
        $dbc->execute($query,$args);
        
        return '
        <div align="center">
            <div class="alert alert-success">Data Cleared<br /> <a href="http://key/scancoord/testing/AuditScannerReport.php" 
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
        
        if ($_POST['cleardata']) {
            $ret .= $this->clear_scandata_hander($dbc,$storeID);
        }
        
        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }
        
        $ret .= $this->form_content();
        
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
        	SELECT upc, description, cost, price, curMarg, desMarg, rsrp, srp, prid, flag, dept, vendor, notes, store_id
			FROM woodshed_no_replicate.AuditScanner 
            ORDER BY vendor, dept;
        ");    
        $result = $dbc->execute($query);
        $data = array();
        $headers = array();
        $i = 0;
        //  Add items to data
        while ($row = $dbc->fetch_row($result)) {
            foreach ($row as $k => $v) {
                if(!is_numeric($k)) {
                    $data[$i][$k] =  $v;
                    $headers[$k] = $k;
                }
            }
            $i++;
        }
        
        //  Add a column
        $i = 0;
        $flags = array();
        foreach ($data as $k => $array) { 
            $srp = $data[$k]['srp'];
            $price = $data[$k]['price'];
            $difference = sprintf("%0.3f",$srp - $price);
            $margin = $data[$k]['curMarg'];
            $dMargin = $data[$k]['desMarg'];
            //CREATE FLAGS FOR <TR> STYLE
            $margOff = ($margin / $dMargin);
            if ($margOff > 1.05) {
                //$warning['margin'] = 'info';
                $flags['info'][] = $i;
            } elseif ($margOff > 0.95) {
                //$warning['margin'] = 'none';
                //$flags['none'] = 'none';
            } elseif ($margOff < 0.95 && $margOff > 0.90) {
                //$warning['margin'] = 'warning';
                $flags['warning'][] = $i;
            } else {
                //$warning['margin'] = 'danger';
                $flags['danger'][] = $i;
            }

            $i++;
        }
        
        $ret .=  '<div class="panel panel-default">
            <table class="table table-condensed" id="mytable">';
        $ret .=  '<thead class="float">';
        foreach ($headers as $v) {
            $ret .=  '<th>' . $v . '</th>';
        }
        $ret .=  '</thead>';
        $prevKey = '1';
        $ret .= '<tbody>';
        $ret .= '<tr style="background-color: grey"><td></td><td></td><td></td><td></td><td></td>
            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
        $ret .=  '<tr class="highlight">';
        foreach ($data as $k => $array) { 
            foreach ($array as $kb => $v) {
                if ($kb == 'store_id') {
                    $ret .=  '<td class="store_id">' . $v . '</td>'; 
                } else {
                    $ret .=  '<td>' . $v . '</td>'; 
                }
            }
            if($prevKey != $k) {
                /*
                if (in_array(($k+1),$flags['danger'])) {
                    $ret .=  '</tr><tr class="" style="background-color:tomato;color:white">';
	                } elseif (in_array(($k+1),$flags['warning'])) {
                    $ret .=  '</tr><tr class="" style="background-color:#FFF457;">';
                } else {
                    $ret .=  '</tr><tr>';
                }*/
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
        
        /*
        $upcLink = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc=" 
                . $upc . "&ntype=UPC&searchBtn=' target='_blank'>{$upc}</a>";
        */
        if ($dbc->error()) $ret .=  $dbc->error();
        
        if ($storeID == 1) {
            $OppoID = 2;
        } else {
            $OppoID = 1;
        }
        $ret .= $this->javascript_content($OppoID);
        
        return $ret;
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
        
        $ret = '';
        $ret .= '
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
    var $table = $(\'#mytable\');
    $table.floatThead();
</script>
<script src="/scancoord/common/javascript/jquery.floatThead.min.js"></script>
        ';
        return $ret;
    }
    
    
}

ScancoordDispatch::conditionalExec();

 
