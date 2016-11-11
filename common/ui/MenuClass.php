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
class menu
{
    public function nav_menu()
    {
        return '
<div class="container-fluid"  align="center" style="height:80px;width:900px;">   
    <div class="navbar navbar-default collapse in hidden-xs hidden-print" style="background-color:white;border:none">
        <ul class="nav navbar-nav">
        
            <li class="dropdown"><a style="width:160px;" class="dropdown-toggle" data-toggle="dropdown" data-target="#" href="#">Item<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                <li><a href="http://192.168.1.2/scancoord/item/last_sold_check.php">Last Sold</a></li>
                <li><a href="http://192.168.1.2/scancoord/item/TrackChangeNew.php">Track Change</a></li>
                <li><a href="" onclick="popitup(\'http://key/scancoord/item/marginCalc.php\')">Margin Calc</a></li>
            </ul></li>
            
            <li class="dropdown"><a style="width:160px;" class="dropdown-toggle" data-toggle="dropdown" data-target="#" href="#">Batches<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                <li class="dropdown-header">Basics</li>
                    <li><a href="http://192.168.1.2/scancoord/item/coopBasicsScanPage.php">Basics Scan</a></li>
                <li class="divider"></li><!-- divider with no header -->
                <li class="dropdown-header">UNFI Sales Change</li>
                    <li><a href="http://192.168.1.2/scancoord/item/SalesChange/SalesChangeIndex.php">Batch Check Index</a></li>
                    <li><a href="http://192.168.1.2/scancoord/item/SalesChange/CoopDealsReview.php">Quality Assurance</a></li>
                    <li><a href="http://192.168.1.2/scancoord/item/Batches/unfiBreakdowns.php">Breakdown</a></li>
                    <li><a href="http://192.168.1.2/scancoord/item/SignInfoHelper.php">Sign Info</a></li>
                    <li><a href="http://192.168.1.2/scancoord/item/Batches/prodBatchHistory.php">Item Batch History</a></li>
                <li class="divider"></li><!-- divider with no header -->
                <li class="dropdown-header">Price Changes</li>
                    <li><a href="http://192.168.1.2/scancoord/item/Batches/batchForceCheck.php">Batch Force Check</a></li>
                    <li><a href="http://192.168.1.2/scancoord/item/Batches/BatchReview/">Batch Review</a></li>
            </ul>
            </li>    
            
            <li class="dropdown"><a style="width:160px;" class="dropdown-toggle" data-toggle="dropdown" data-target="#" href="#">Data Monitor<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                <li class="dropdown-header">Discrepancy Tasks</li>
                    <li><a href="http://192.168.1.2/scancoord/dataScanning/zeroPriceCheck.php">Bad Price Scan</a></li>
                    <li><a href="http://192.168.1.2/scancoord/dataScanning/ExceptionSaleItemTracker.php">Exception Sale Items</a></li>
                    <li><a href="http://192.168.1.2/scancoord/dataScanning/multiStoreDiscrepanciesPage.php">Multi-Store Prod Discrep</a></li>
                    
            </ul>
            </li>
            
            <li class="dropdown"><a style="width:160px;" class="dropdown-toggle" data-toggle="dropdown" data-target="#" href="#">Misc.<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                <li class="dropdown-header">Testing</li>
                <li><a href="http://192.168.1.2/scancoord/testing/ShelfAuditPage.php">Shelf Audit</a></li>
                <li><a href="http://192.168.1.2/scancoord/item/Batches/CheckBatchPercent.php">Sales Batch %</a></li>
            </ul>
            </li> 
            <li class="dropdown"><a style="width:160px;" href="http://key/git/fannie/item/ItemEditorPage.php">Office<span class=""></span></a>
    </div>
</div>
    ';
    }
    
    public function css_content()
    {
        
        /*
        *   This function is not being called anywhere. 
        */
        
        // ../src/img
        $dirname = dirname(dirname(__FILE__));
        
        return '
.menu-bar {
    
}
.left-sidebar {
    padding: 0px;
    margin: 0;
    margin-left: 25px;
    height: 100%;
    width: 150px;
    border: 1px solid lightgreen;
    position: absolute;
}
.col-body {
    border: 1px solid lightblue;
    margin-left: 300px;
    margin-right: 25px;
}
.row-menu {
    width: 300px;
}
.top-toolbar {
    width: 100%;
}
td.toolbar {
    width: 100px;
    text-align: center;
    font-size: 10px;
}
td.ltgrey {
    color: lightgrey;
}
.image {
    width: 150px;
    height: 100px;
    border: 1px dotted black;
    padding: 50px;
}
#border {
    border: 10px solid transparent;
    border-image-repeat: repeat;
    border-image: url(../common/src/img/greyborder.png) 25 round;
}
        ';
    }
}

?>

<script language="javascript" type="text/javascript">
function popitup(url) {
	newwindow=window.open(url,'name','height=320,width=300');
	if (window.focus) {newwindow.focus()}
	return false;
}
</script>



