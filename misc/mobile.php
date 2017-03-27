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
            <div style="width: 100vw;" align="center">
                <h1>Mobile Scannie</h1>
                <a class="btn btn-default btn-mobile-menu" href="http://192.168.1.2/scancoord/item/AuditScanner.php">Audie: Scanner</a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="http://192.168.1.2/git/fannie/modules/plugins2.0/ShelfAudit/SaMenuPage.php">CORE-POS Fannie</a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="http://192.168.1.2/git/fannie/item/CoopDealsLookupPage.php">Coop Deals Lookup Page</a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="mobile.php">&nbsp;</a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="mobile.php">&nbsp;</a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="mobile.php">&nbsp;</a><br /><br />
                <a class="btn btn-default btn-mobile-menu" href="mobile.php">return</a>
            </div><br /><br />
        ';
        return $ret;
    }
        
    
}
ScancoordDispatch::conditionalExec();