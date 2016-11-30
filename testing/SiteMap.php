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

class SiteMap extends ScancoordDispatch
{
    
    protected $title = "SiteMap";
    protected $description = "[SiteMap] Site Map.";
    protected $ui = TRUE;
    protected $data = array(); // filename[directory];
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

        $this->getDirContents('.','/scancoord/testing/');
        $this->getDirContents('../.','/scancoord/');
        $this->getDirContents('.././item','/scancoord/item/');
        $this->getDirContents('.././item/Batches','/scancoord/item/Batches/');
        $this->getDirContents('.././item/Batches/BatchReview','/scancoord/item/Batches/BatchReview/');
        $this->getDirContents('.././item/SalesChange','/scancoord/item/SalesChange/');
        $this->getDirContents('.././dataScanning','/scancoord/dataScanning/');

        $ret .= '<div class="container">';
        $ret .= '<h3>Site Map</h3>';
        
        $i = FALSE;
        $prevDir = '';
        
        foreach ($this->data as $k => $v) {
            if ($prevDir != $v) {
                $prevDir = $v;
                /*
                $curHeader = $v;
                if ($i) $ret .= '</div>';
                $ret .= '<strong><a href="" onclick="document.getElementById(\''.$curHeader.'\').style.display = \'block\'; return false;">'
                    .$curHeader.'</a></strong><br>';
                $ret .= '<div id="'.$curHeader.'" style="display:none; width:500px" class="well">';
                $ret .= '<a href="" onclick="document.getElementById(\''.$curHeader.'\').style.display = \'none\'; 
                    return false;">[<i>Collapse</i>]</a></strong><br><br>';
                */
                if ($i) $ret .= '</div><br><br>';
                $ret .= '<div style="background: linear-gradient(#f7f7f7,white,#f7f7f7); width: 500px; padding: 15px; border: 1px solid lightgrey;">';
                $ret .= '<h4><span style="color:purple">'.substr($v,1,-1).'</span></h4>';
            } 
            $ret .= '<a href="'.$v.$k.'">'.$k.'</a><br>';
            $i = TRUE;
        }
        $ret .= '<br><br>';
        $ret .= '</div>';
        
        
        return $ret;
    }
    
    private function getDirContents($dirname,$path)
    {
        $directories = array();
        $exceptions = array();
        $exceptions[] = "SiteMap.php";
        $exceptions[] = "generic_testing_page.php";
        $exceptions[] = "config.php";
        $exceptions[] = "config.php.dist";
        $exceptions[] = "README.md";
        $exceptions[] = "index.php";
        $exceptions[] = "worklist.txt";
        $exceptions[] = "generic_dScan_page.php";
        $exceptions[] = "genericPage.php";
        $exceptions[] = "generic_item_Batches_BatchReview_page.php";
        $exceptions[] = "scanner.js";
        $exceptions[] = "salesChangeAjax3.php";
        $exceptions[] = "salesChangeAjaxErrSigns.php";
        $exceptions[] = "salesChangeAjax2.php";
        $exceptions[] = "SalesChangeLinksNew.html";
        
        $dir = opendir($dirname);
        
        while ($curData[] = readdir($dir)) {}
        
        foreach($curData as $name) {
            if (strpos($name, '.')) {
                if (!in_array($name,$exceptions)) {
                    $this->data[$name] = $path;
                }
            } 
        }
        
        return false;
    }
    
}

ScancoordDispatch::conditionalExec();

 
