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
    include_once(__DIR__.'/../sqlconnect/SQLManager.php');
}
class search
{

    protected $data = array();
    protected $pagelist = array();

    private function parser($input)
    {
        include(__DIR__.'/../../config.php');
        if (is_numeric($input)) {
            $pages = array(
                'Track Change' => 'http://'.$SCANROOT_DIR.'/item/TrackChangeNew.php?upc='.$input,
                'Item Edit' => 'http://'.$FANNIEROOT_DIR.'/item/ItemEditorPage.php?searchupc='.$input.'&ntype=UPC&searchBtn=',
                'Batch Edit' => 'http://'.$FANNIEROOT_DIR.'/batches/newbatch/EditBatchPage.php?id='.$input,
                'Item Batch History' => 'http://'.$FANNIEROOT_DIR.'/reports/ItemBatches/ItemBatchesReport.php?upc='.$input,
                'Batch % Check' => 'http://'.$SCANROOT_DIR.'/item/Batches/CheckBatchPercent.php?batchID='.$input,
            );
            foreach ($pages as $name => $path) {
                $this->data[$name] = $path;
            }
        } else {
            $this->getList();
        }

        return $ret;
    }

    public function run()
    {
        $s = $_GET['search'];
        $ret = $this->parser($s);

        //$ret = '';
        foreach ($this->data as $name => $path) {
            if ( (strstr($name,$s) || strstr($name,ucwords($s))) && strlen($s) > 2 || is_numeric($s) ) {
                $ret .= (is_numeric($s)) ? '<a class="search-resp" href="'.$path.'">' : '<a class="search-resp" href="'.$path.$name.'">';
                $replace = '<b>'.$s.'</b>';
                $newstring = str_replace($s,$replace,$name);
                $ret .= $newstring;
                $ret .= '</a><br />';
            }
        }
        return <<<HTML
<u style="color: #cacaca; text-decoration: none;font-weight: bold;">Search Results</u><br />
<div>{$ret}</div>
HTML;
    }

    public function getList()
    {

        include(__DIR__.'/../../config.php');
        $dbc = new SQLManager($SCAN_IP, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

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
                $this->pagelist[] = substr($path,1,-1) . ' | <a href="'.$path.$name.'">'.$name.'</a>';
            }
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
