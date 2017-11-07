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
class specialPriceCheck extends ScancoordDispatch
{

    protected $title = "Special Price Check";
    protected $description = "[Special Price Check] Check for products that are in 
        current batches that have 0.00 special price set.";
    protected $ui = TRUE;
    protected $upcs = array();

    public function body_content()
    {
        ini_set('memory_limit', '1G');
        $this->getSales();
        $opdb = $this->checkSales();

/*
        $opdb = $this->getMissingSales("SCANHOST","SCANDB","back");
        $regNos = array(1,2,3);
        foreach ($regNos as $regNo) {
            $posdb .= $this->getMissingSales("SCANHOST","POSOPDB","front",$regNo);
        }
*/
        return <<<HTML
<h3>Sale Price Discrepancies </h3>
<h4 data-toggle="popover" data-trigger="hover" data-content="hello"><b>OP</b><span style="font-size: 12px;"> Operational Data Conflicts</span></h4>
<div id="mydiv" style="border: 2px solid lightgrey"> </div>
    {$opdb}
<div style="height: 5px;">&nbsp;</div>

<h4><b>POS</b><span style="font-size: 12px;"> Point of Sale Data Conflicts</span></h4>
<div id="mydiv" style="border: 2px solid lightgrey"> </div>
    <div class="hovA">This is some text.</div>
    {$posdb}
    This part of the page is not yet functional. It will find items that OP says
    should be on sale but POS does not (special_price = 0 in registers) 
<div style="height: 5px;">&nbsp;</div>
HTML;
    }

    private function getSales()
    {
        $dbc = scanLib::getConObj("FANNIE_OP_DB",1);
        $prep = $dbc->prepare("
            SELECT bl.salePrice, bl.upc, s.storeID, b.batchID, b.batchName, p.special_price, p.store_id,
                p.brand, p.description
            FROM batches AS b 
                LEFT JOIN batchList AS bl ON b.batchID=bl.batchID 
                LEFT JOIN StoreBatchMap AS s ON b.batchID=s.batchID
                LEFT JOIN products AS p ON bl.upc=p.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE NOW() BETWEEN b.startDate AND b.endDate 
                AND bl.upc NOT LIKE 'LC%'
            GROUP BY b.batchID, bl.upc, p.store_id, s.storeID; 
        ");
        $res = $dbc->execute($prep);
        $cols = array('upc','batchID','batchName','salePrice','storeID','store_id','special_price',
            'brand','description');
        while ($row = $dbc->fetchRow($res)) {
            foreach ($cols as $col) {
                ${$col} = $row[$col];
            }
            $this->upcs[$upc]['B'][$batchID]['batchName'] = $batchName;
            $this->upcs[$upc]['B'][$batchID]['salePrice'][$storeID] = $salePrice;

            $this->upcs[$upc]['P'][$store_id] = $special_price;
            $this->upcs[$upc]['P']['brand'] = $brand;
            $this->upcs[$upc]['P']['description'] = $description;
        }

        //var_dump($this->upcs);

        return false; 
    }

    private function checkSales($numstores=2)
    {
        $ret = "";
        $td = "";
        $stores = array();
        for ($i=1; $i<=$numstores; $i++) {
            $stores[] = $i;
        }
        $alphaStore = array(1=>'[H]',2=>'[D]');
        foreach ($this->upcs as $upc => $data) {
            $bids = array();
            $sps = array();
            $spstr = "";
            foreach ($data['B'] as $k => $v) {
                $bids[] = $k;
            }
            foreach ($stores as $store) {
                foreach ($bids as $bid) {
                    $sps[] = $data['B'][$bid]['salePrice'][$store];
                    $curHref = "http://192.168.1.2/git/fannie/batches/newbatch/EditBatchPage.php?id=";
                    $l = "<span style='color: grey'> | </span>";
                    $spstr .= "{$l}<a href='{$curHref}{$bid}' target='_blank'>"
                        .$data['B'][$bid]['salePrice'][$store] ."</a>";
                }
                foreach ($bids as $bid) {
                    if ($saleprice = $data['B'][$bid]['salePrice'][$store]) {
                        $specialprice = $this->upcs[$upc]['P'][$store];
                        if ($saleprice != $specialprice && !in_array($speicalprice, $sps)) {
                            //echo $upc . " " . $saleprice . " " . $specialprice . "<br/>";
                            $curHref = "http://192.168.1.2/git/fannie/batches/batchhistory/BatchHistoryPage.php?upc=";
                            $ln = "<a href='{$curHref}{$upc}' target='_blank'><span class=\"scanicon-book\"></span></a>";
                            $ieHref = "<a href='http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc={$upc}
                                &ntype=UPC&searchBtn=' target='_blank'>{$upc}</a>";
                            $td .= "
                                <tr>
                                <td>{$ieHref}</td>
                                <td>{$this->upcs[$upc]['P']['brand']}</td>
                                <td>{$this->upcs[$upc]['P']['description']}</td>
                                <td>{$this->upcs[$upc]['P'][1]} | {$this->upcs[$upc]['P'][2]}</td>
                                <td>{$ln}{$spstr}</td>
                                <td>{$alphaStore[$store]}</td>
                                {$regtd}
                                </tr>";
                        }
                    }
                }
            }
            unset($bids);
            unset($sps);
        }

        return <<<HTML
<table class="table table-condensed small">
    <thead><th>upc</th><th>Brand</th><th>Description</th><th>[H] | [D]</th>
        <th>Current Batches</th><th>Reported</th></thead><tbody>
        {$td}
    </tbody>
</table>
HTML;
    }

    private function getMissingSales($h,$db,$end,$regNo="")
    {
        include('../config.php');
        $dbc = new SQLManager(${$h}.$regNo, 'pdo_mysql', ${$db}, $SCANUSER, $SCANPASS);

        /* backend is more straight-forward. Check items on sale against price they should be on sale for. */
        $backA= array();
        $backP = $dbc->prepare("
            SELECT b.batchName, p.special_price, bl.salePrice, p.upc, p.brand, p.description, p.store_id 
            FROM batches AS b 
                LEFT JOIN batchList AS bl ON b.batchID=bl.batchID 
                LEFT JOIN products AS p ON bl.upc=p.upc 
            WHERE NOW() BETWEEN b.startDate AND b.endDate 
                AND p.special_price <> bl.salePrice 
                AND b.batchName like '%Co-op Deals%' 
            GROUP BY bl.upc, p.store_id; 
        ");
        /* front-end: check if item should be on sale, if it is not, return the upc. */
        $frontA = array();
        list($inStr, $frontA) = $dbc->safeInClause($this->upcs);
        $frontQ = "SELECT upc FROM products WHERE upc IN ({$inStr}) 
            AND special_price = 0";
        $frontP = $dbc->prepare($frontQ);
        $res = $dbc->execute(${$end."P"},${$end}."A");
        $td = "";
        if ($regNo != "") {
            $regtd = "<td>Reg[{$regNo}]</td>";
        } else {
            $regtd = "";
        }
        while ($row = $dbc->fetchRow($res)) {
            $cols = array('upc','brand','description','batchName','salePrice','special_price','store_id');
            foreach ($cols as $col) {
                ${$col} = $row[$col];
                if ($col == 'store_id') {
                    $this->upcs[$k][$store_id] = $specialPrice;
                } else {
                    $this->upcs[$upc][$col] = ${$col};
                }
            }

            $td .= "<tr><td>{$upc}</td><td>{$brand}</td><td>{$description}</td>
                <td>{$batchName}</td><td>OP: {$salePrice}</td><td>POS: {$special_price}</td><td>STORE: {$store_id}</td>{$regtd}</tr>";
        }

        return <<<HTML
<table class="table table-condensed small">
    {$td}
</table>
HTML;
    }

/* how i'll create the table after I have data collected
$cols = array('upc','brand','description','batchName','salePrice','special_price');
foreach ($cols as $col) ${$col} = $row[$col];
$td .= "<tr><td>{$upc}</td><td>{$brand}</td><td>{$description}</td>
    <td>{$batchName}</td><td>OP: {$salePrice}</td><td>POS: {$special_price}</td>{$regtd}</tr>";
*/
    
    public function css_content()
    {
return <<<HTML
#logview {
    height: 300px;
    overflow-y: auto;
}
.hovA {
    display: none;
}
b:hover + .hovA {
    display: block;
}
HTML;
    }

}
ScancoordDispatch::conditionalExec();
