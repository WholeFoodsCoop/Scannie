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
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(dirname(__FILE__))) . '/common/sqlconnect/SQLManager.php');
}
class search
{

    protected $data = array();
    protected $pagelist = array();

    public function run()
    {
        $s = $_GET['search'];
        $this->getList();

        $ret = '';
        $ret .= '<u style="color:#c7bda3">Search Results</u><br />';
        foreach ($this->data as $name => $path) {
            if ( (strstr($name,$s) || strstr($name,ucwords($s))) && strlen($s) > 2) {
                $ret .= '<a href="'.$path.$name.'">';
                $replace = '<b>'.$s.'</b>';
                $newstring = str_replace($s,$replace,$name);
                $ret .= $newstring;
                $ret .= '</a><br />';
            }
        }
        return $ret;
    }

    public function getList()
    {

        include('../../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

        $this->getDirContents('.','/scancoord/testing/');
        $this->getDirContents('.','/scancoord/');
        $this->getDirContents('../.././item','/scancoord/item/');
        $this->getDirContents('../.././item/Batches','/scancoord/item/Batches/');
        $this->getDirContents('../.././item/Batches/BatchReview','/scancoord/item/Batches/BatchReview/');
        $this->getDirContents('../.././item/SalesChange','/scancoord/item/SalesChange/');
        $this->getDirContents('../.././dataScanning','/scancoord/dataScanning/');
        $this->getDirContents('../.././misc','/scancoord/misc/');

        $i = FALSE;
        $prevDir = '';

        foreach ($this->data as $name => $path) {
            if ($prevDir != $path) {
                $prevDir = $path;
                if ($i) {}
                //$ret .= '<h4><span style="color:purple">'.substr($path,1,-1).'</span></h4>';
                $this->pagelist[] = substr($path,1,-1) . ' | <a href="'.$path.$name.'">'.$name.'</a>';
            }
            //$ret .= '<a href="'.$path.$name.'">'.$name.'</a><br>';
            $i = TRUE;
        }

        return $this->pagelist;
    }

    private function getDirContents($dirname,$path)
    {
        $directories = array();
        $exceptions = array();
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
        $exceptions[] = "DiscrepanciesEmail.php";
        $exceptions[] = "searchbar.php";
        $exceptions[] = "CorePage.php";
        $exceptions[] = "MenuClass.php";
        $exceptions[] = "DenfeldDeptMap.php";
        $exceptions[] = "DenfeldDeptMap2.php";
        $exceptions[] = "BatchReviewPage.html";
        $exceptions[] = "getProcesslist.php";

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
$obj = new search();
echo $obj->run();