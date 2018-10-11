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
class quickLookups extends ScancoordDispatch
{
    
    protected $title = "Scan Home";
    protected $description = "[Scancoord Home Page] .";
    protected $ui = TRUE;
    protected $add_css_content = TRUE;
    protected $add_javascript_content = TRUE;
    
    public function body_content()
    {           
    
        $ret = '';
        $ret .='
            <style>
                body {
                    overflow-y:hidden;
                }
            </style>
        ';
        
        $ret .= '<div style="width:1500px; height: 97vh;">';
        
        //$ret .= '<div style="width: 26vw; min-width: 400px; height: 95vh; float:left; ">';
        $ret .= '<div class="container">';
        $ret .= $this->form_content();
        $ret .= '</div>';
        /*
        $ret .= '<div style="width: 72vw;  height: 95vh; float: left">';
        $ret .= '
            <div style="max-height: 100vh; width: 100%; float:left">
                <form method="post" target="browser">
                    <!-- <input id="txtUrl" style="width:82%; border: 1px solid lightgrey" placeholder="Put the website here" name="url" type="text" /> -->
                    <!-- <input style="width:8%;" type="button" value="Go" onclick="setBrowserFrameSource(); return false;"/> -->
                </form>
                <iframe id="browser" name="browser" src="http://key/scancoord/item/last_sold_check.php" style="height:100%; width:100%; border: none;"></iframe>
            </div>
        ';
        $ret .= '</div>'; //closing all DIVs
        */
        $ret .= '</div>';
        
        //$ret .= $this->get_data();
        
        return $ret;
        
    }
    
    private function get_data()
    {
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $queries = array(
            'select count(*) as count from products AS p LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_id 
                where p.inUse=1 and m.super_name NOT IN (\'BRAND\',\'DELI\',\'PRODUCE\',\'MISC\') GROUP BY p.store_id;' => 'InUse',
            'select count(*) as count from products AS p LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_id 
                where p.inUse=1 and m.super_name NOT IN (\'BRAND\',\'DELI\',\'PRODUCE\',\'MISC\') and p.cost = 0 group by p.store_id;' => 'Cost',
            'select count(*) as count from products AS p LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_id 
                where p.inUse=1 and m.super_name NOT IN (\'BRAND\',\'DELI\',\'PRODUCE\',\'MISC\') and p.default_vendor_id = 0 group by p.store_id;' => 'Vendor',
            'select count(*) as count from products AS p LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_id 
                where p.inUse=1 and m.super_name NOT IN (\'BRAND\',\'DELI\',\'PRODUCE\',\'MISC\') and p.modified < DATE(NOW()-INTERVAL 1 YEAR) group by p.store_id;' => 'Modified',
            'select count(*) as count FROM vendors WHERE inactive = 0;' => 'VendorCount'
        );
        foreach ($queries as $query => $suffix) {
            ${'prep'.$suffix} = $dbc->prepare($query);
            ${'res'.$suffix} = $dbc->execute(${"prep$suffix"});
            if ($dbc->error()) {
                $ret .= '<div class="alert alert-danger"><strong>'.$suffix.'</strong>:'.$dbc->error().'</div>';
            }
            ${$suffix} = array();
            while ($row = $dbc->fetch_row(${"res$suffix"})) {
                ${"$suffix"}[] .= number_format($row['count'],0,'',',');
            }
            
        }
        
        $headers = array('','Hillside','Denfeld');
        $ret .= '<table class="table-condensed small"><tbody><thead>';
        foreach ($headers as $header) {
            $ret .= '<th>'.$header.'</th>';
        }
        $ret .= '</thead>';
        $ret .= '<td>active vendors</td><td>'.$VendorCount[0].'</td><td>"</td></tr><tr>';
        $ret .= '<td>products in use</td><td>'.$InUse[0].'</td><td>'.$InUse[1].'</td></tr><tr>';
        $ret .= '<td>missing cost</td><td>'.$Cost[0].'</td><td>'.$Cost[1].'</td></tr><tr>';
        $ret .= '<td>missing vendor</td><td>'.$Vendor[0].'</td><td>'.$Vendor[1].'</td></tr><tr>';
        $ret .= '<td>unchanged this year</td><td>'.$Modified[0].'</td><td>'.$Modified[1].'</td></tr><tr>';
        $ret .= '</tbody></table>';
        
        $ret .= '
        
        ';
        
        
        return $ret;
    }
    
    private function form_content()
    {
        include(__DIR__.'/../config.php'); 
        $ret = '';
        $ret .= '
            
        ';
        $subBtn = '&nbsp;<button type="submit" class="btn btn-info btn-xs" href=""><span class="go-icon">&nbsp;</span></a>';
        $TrackChange = 'http://key/scancoord/item/TrackChangeNew.php';
        $ItemEditor = 'http://key/git/fannie/item/ItemEditorPage.php';
        $batch = 'http://key/git/fannie/batches/newbatch/EditBatchPage.php';
        $LastSold = 'http://'.$HTTP_HOST.'/scancoord/item/last_sold_check.php';
        $ItemBatchHistory = 'http://'.$HTTP_HOST.'/scancoord/item/Batches/prodBatchHistory.php';
        $SalesBatchPercent = 'http://'.$HTTP_HOST.'/scancoord/item/Batches/CheckBatchPercent.php';
        
        
        $ret .= '<h4 >Quick Lookups</h4>';
        
        $ret .= '
            <form class="form-inline" method="get" target="_BLANK" action="'.$TrackChange.'">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; ">Track Change</span>
                    <input type="text" class="form-control" id="trackchange" name="upc" placeholder="enter upc" style="width: 200px; " autofocus>
                    '.$subBtn.'
                </div>
            </form>     
        ';
        
        $ret .= '
            <form class="form-inline" method="get" target="_BLANK" action="'.$LastSold.'">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; ">Last Sold</span>
                    <input type="text" class="form-control" id="lastsold" name="upc" placeholder="enter upc" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="id" value="1">
            </form>
        ';
        
        $ret .= '
            <form class="form-inline" method="get" target="_BLANK" action="'.$ItemEditor.'">
                <div class="input-group">
                    <span class="input-group-addon alert-warning" style="width: 100px; ">Item Editor</span>
                    <input type="text" class="form-control" id="itemeditor" name="searchupc" placeholder="enter upc" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="ntype" value="UPC">
                <input type="hidden" name="searchBtn" value="">
            </form>     
        ';
        
        $ret .= '
            <form class="form-inline" method="get" target="_BLANK" action="'.$batch.'">
                <div class="input-group">
                    <span class="input-group-addon alert-warning" style="width: 100px; ">Sales Batches</span>
                    <input type="text" class="form-control" id="itemeditor" name="id" placeholder="enter batch ID" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="ntype" value="UPC">
                <input type="hidden" name="searchBtn" value="">
            </form>     
        ';
        
        $ret .= '
            <form class="form-inline" method="get" target="_BLANK" action="'.$ItemBatchHistory.'">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; ">Item Batch H</span>
                    <input type="text" class="form-control" id="itembatchhistory" name="upc" placeholder="enter upc" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="id" value="1">
            </form>     
        ';
        
        
        $ret .= '
            <form class="form-inline" method="get" target="_BLANK" action="'.$SalesBatchPercent.'">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; ">Sales Batch %</span>
                    <input type="text" class="form-control" id="salesbatchpercent" name="batchID" placeholder="enter batch id" style="width: 200px; ">
                    '.$subBtn.'
                </div>
            </form>     
        '; 
        
        
        return $ret;
    }
    
    public function css_content()
    {
        return '

        ';
    }
    
    public function javascript_content()
    {
return '
<script type="text/javascript">
    function setBrowserFrameSource(){
        var browserFrame = document.getElementById("browser");
        browserFrame.src= document.getElementById("txtUrl").value;
    }
</script>
';
    }
    
}
ScancoordDispatch::conditionalExec();
