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
class Search
{

    protected $data = array();
    protected $pagelist = array();

    private function parser($input)
    {
        if (is_numeric($input)) {
            $pages = array(
                '<i>CORE</i> Item Editor' => '/../../../../git/IS4C/fannie/item/ItemEditorPage.php?searchupc='.$input.'&ntype=UPC&searchBtn=',
                '<i>SCAN</i> Track Change' => '/../scancoord/ScannieV2/content/Item/TrackChangeNew.php?upc='.$input,
                //'Batch Edit' => 'http://'.$FANNIEROOT_DIR.'/batches/newbatch/EditBatchPage.php?id='.$input,
                //'Item Batch History' => 'http://'.$FANNIEROOT_DIR.'/reports/ItemBatches/ItemBatchesReport.php?upc='.$input,
                //'Batch Review' => 'http://'.$SCANROOT_DIR.'/item/Batches/BatchReview/BatchReviewPage.php?id='.$input,
                //'Unfi_DB_Check' => 'https://customers.unfi.com/Pages/ProductSearch.aspx?SearchTerm='.$input
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
                $ret .= (is_numeric($s)) ? '<a class="search-resp" href="'.$path.'" target="_blank">' : '<a class="search-resp" href="'.$path.$name.'">';
                $replace = '<b>'.$s.'</b>';
                $newstring = str_replace($s,$replace,$name);
                $ret .= $newstring;
                $ret .= '</a><br />';
            }
        }
        return <<<HTML
<u style="color: #cacaca; text-decoration: none;font-weight: bold;">
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    </u><br />
<div>{$ret}</div>
HTML;
    }

}
//$obj = new search();
//echo $obj->run();
if ($_GET['search']) {
    $obj = new search();
    echo $obj->run();
}
