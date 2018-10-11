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
class mobile extends ScancoordDispatch
{
    
    protected $title = "Moblie Scan Home";
    protected $description = "[Mobile Scancoord Home Page] .";
    protected $ui = FALSE;
    
    
    public function body_content()
    {           
        include(__DIR__.'/../config.php'); 
        $ret = '';
        $ret .= '
            <style>
                body {
                    min-height: 100%;
                }
                .btn-mobile-menu { 
                    width: 90vw;     
                }
            </style>
        ';
        
        $ret .= '
            <div style="width: 100%;" align="center">
                <h1>Mobile Scannie</h1>
                <a class="btn btn-default btn-mobile-menu" href="http://'.$HTTP_HOST.'/scancoord/item/AuditScanner.php">Audie <i>Scanner</i></a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="http://'.$HTTP_HOST.'/scancoord/item/AuditScannerReport.php">Audie <i>Report</i></a><br /><br />
                <a class="btn btn-default btn-mobile-menu" 
                    href="http://'.$HTTP_HOST.'/scancoord/ScannieV2/content/Scanning/BatchCheck/BatchCheckMenu.php">
                    <span class="new">NEW</span> Batch Check</a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="http://'.$HTTP_HOST.'/git/fannie/item/CoopDealsLookupPage.php">Coop Deals Lookup</a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="http://'.$HTTP_HOST.'/git/fannie/modules/plugins2.0/ShelfAudit/SaMenuPage.php">
                    CORE-POS/ Fannie</a><br /><br />
                <br/><br/>
                <a class="btn btn-default btn-mobile-menu" href="http://'.$HTTP_HOST.'/scancoord/item/TrackChangeNew.php">Scannie Home</a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="#" onClick="window.history.back(); return false;">return</a>
            </div><br /><br />
        ';
        return $ret;
    }
        
    public function cssContent()
    {
        return <<<HTML
body {
    font-family: Arial, Helvetica, sans-serif;
    background-color: rgba(255,255,255,0.9);
    background: linear-gradient(135deg, #42a7f4, #0a1528);
    background-color: linear-gradient(135deg, #42a7f4, #0a1528);
    background-repeat: no-repeat;
    background-attachment: fixed;
}
.old {
    color: tomato;
    font-weight: bold;
}
.new {
    color: orange;
    font-weight: bold;
}
.btn, .btn-default {
    background-color: rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(0,0,0,0.6);
}
HTML;
    }
    
}
ScancoordDispatch::conditionalExec();
