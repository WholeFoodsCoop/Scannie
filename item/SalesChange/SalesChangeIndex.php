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

include('../../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once($SCANROOT.'/common/sqlconnect/SQLManager.php');
}

class SalesChangeIndex extends ScancoordDispatch
{
    
    protected $title = "Batch Check Index";
    protected $description = "[Batch Check Index] Index of Batch Checking Utilities directory.";
    protected $ui = TRUE;
    protected $readme = "This is a generic page for running and drawing a query to table.
        By default, every column returned by query will be drawn in table, in the order 
        selected.";
    
    public function body_content()
    {           
        
        return '
            <br>
                <div class="container" align="center">
                <div class="panel panel-default" style="width:300px;">
                <div class="panel-heading">Batch Check</div>
                <table class="table">
                    <tr><td class="highlight"><a class="btn" href="ListGen.php"> Generate List </a>&nbsp;|&nbsp;
                        <a class="btn" href="ListRemove.php"> Delete List </a></td></tr>                                   
                    <tr><td class="highlight"><a class="btn" href="SalesChangeQueues.php"> Review Scan Data  </a></td></tr>        
                    <tr><td class="highlight"><a class="btn" href="SCScanner.php"> Scanner </a></td></tr>                               
                    <tr><td class="highlight"><a class="btn" href="../SignInfoHelper.php"> Sale Item Report <i>info in POS</i></a></td></tr>                                
                    <tr><td class="highlight"><a class="btn" href="http://192.168.1.2/git/fannie/item/CoopDealsLookupPage.php"> Coop Deals Check <i>by item</i> </a></td></tr>                                
                    <tr><td class="highlight"><a class="btn" href="http://192.168.1.2/scancoord/testing/CoopDealsSearchPage.php"> Coop Deals Check <i>complete list</i> </a></td></tr>                                
                </table></div></div></div>
        ';
        
    }
    
}

ScancoordDispatch::conditionalExec();

 
