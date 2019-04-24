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
include(__DIR__.'/../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../common/sqlconnect/SQLManager.php');
}
class specialPriceCheck extends ScancoordDispatch
{

    protected $title = "Special Price Check";
    protected $description = "[Special Price Check] Scans both Back
        and Front End DBMS for products that are either erroneously 
        priced in respect to current sales batches or are erroneously 
        missing a special_price at the lanes.";
    protected $ui = false;
    protected $upcs = array();

    public function body_content()
    {
        ini_set('memory_limit', '1G');
        $this->getSales();
        $opdb = $this->checkSales();

        $posdb = "";
        $regNos = array(1,2,3,4,5,6);
        foreach ($regNos as $regNo) {
            $posdb .= $this->getMissingSales("SCANHOST","POSOPDB",$regNo,2);
        }
        $regNos = array(11,12,13,14,15);
        foreach ($regNos as $regNo) {
            //cannot access denfeld lanes from key anymore for some reason
            //$posdb .= $this->getMissingSales("SCANDENHOST","POSOPDB",$regNo,2);
        }

        return <<<HTML
<!--<h3>Sale Price Discrepancies </h3>-->
<div id="salePriceDiscrepContainer">
    <button type="button" class="close btn-default" aria-label="Close" onclick="
        $('#salePriceDiscrepContainer').hide();
        var elm = parent.document.getElementById('specIframe');
        elm.style.height = '202px';
        elm.style.display = 'none';
    ">
        <span aria-hidden="true">&times;</span>
    </button>
    <h4><b>OP</b><span style="font-size: 12px;"> Operational Data Conflicts</span></h4>
    <div style="border: 2px solid lightgrey"> </div>
        {$opdb}
    <div style="height: 5px;">&nbsp;</div>
    <h4><b>POS</b><span style="font-size: 12px;"> Point of Sale Data Conflicts</span></h4>
    <div style="border: 2px solid lightgrey"> </div>
    <table class="table table-condensed small">
        <thead><th>UPC</th><th>Brand</th><th>Description</th><th>[H] | [D]</th><th>Current Batches</th>
            <th>Register</th></thead><tbody>
            {$posdb}
        </tbody>
    </table>
    <div style="height: 5px;">&nbsp;</div>
</div>
HTML;
    }

    private function getSales()
    {
        include(__DIR__.'/../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $dbc = scanLib::getConObj();
        $prep = $dbc->prepare("
            SELECT bl.salePrice, bl.upc, s.storeID, b.batchID, b.batchName, p.special_price, p.store_id,
                p.brand, p.description
            FROM batches AS b
                LEFT JOIN batchList AS bl ON b.batchID=bl.batchID
                LEFT JOIN StoreBatchMap AS s ON b.batchID=s.batchID
                LEFT JOIN products AS p ON bl.upc=p.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE CONCAT(CURDATE(),' 00:00:00') BETWEEN b.startDate AND b.endDate
                AND bl.upc NOT LIKE 'LC%'
                AND b.discountType > 0
                AND bl.salePrice <> 0
                AND bl.pricemethod = 0
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
        if ($er = $dbc->error()) {
            echo "<div class='alert alert-warning'>{$dbc->error()}</div>";
        }

        return false;
    }

    private function checkSales($numstores=2)
    {
        $ret = "";
        include(__DIR__.'/../config.php');
        $exceptions = array('0000099012219','0000099103018','0000099110318',
            '0000099111318','0000099112018','0000099120118','0000099120618',
            '0000099121118','0000099121318','0000099011919','0000099012619', 
            '0000099020219','0000099020919','0000099021219','0000099021919', 
            '0000099030219','0000099032819','0000099040619','0000099041619', 
            '0000099042719', 
            );
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
                    $curHref = "http://{$FANNIEROOT_DIR}/batches/newbatch/EditBatchPage.php?id=";
                    $l = "<span style='color: grey'> | </span>";
                    $spstr .= "{$l}<a href='{$curHref}{$bid}' target='_blank'>"
                        .$data['B'][$bid]['salePrice'][$store] ."</a>";
                }
                foreach ($bids as $bid) {
                    if ($saleprice = $data['B'][$bid]['salePrice'][$store]) {
                        $specialprice = $this->upcs[$upc]['P'][$store];
                        if (!in_array($upc, $exceptions)) {
                            if ($saleprice != $specialprice && !in_array($specialprice, $sps)) {
                                $curHref = "http://{$FANNIEROOT_DIR}/batches/batchhistory/BatchHistoryPage.php?upc=";
                                $ln = "<a href='{$curHref}{$upc}' target='_blank'><span class=\"scanicon-book\"></span></a>";
                                $ieHref = "<a href='http://{$FANNIEROOT_DIR}/item/ItemEditorPage.php?searchupc={$upc}
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

    private function getMissingSales($h,$db,$regNo="",$numstores=2)
    {
        include(__DIR__.'/../config.php');
        $dbc = new SQLManager(${$h}.$regNo, 'pdo_mysql', ${$db}, $SCANUSER, $SCANPASS);
        try {
            if ($dbc->connections[${$db}] == false) {
                throw new Exception();
            } else {
                $upcs = array();
                foreach ($this->upcs as $k => $v) {
                    $upcs[] = $k;
                }
                list($inStr, $args) = $dbc->safeInClause($upcs);
                $query = "SELECT upc, store_id FROM products WHERE upc IN ({$inStr})
                    AND special_price = 0";
                $prep = $dbc->prepare($query);
                $res = $dbc->execute($prep, $args);
                $laneupcs = array();
                while ($row = $dbc->fetchRow($res)) {
                    $laneupcs[$row['upc']] = $row['store_id'];
                }

                $ret = "";
                $td = "";
                $stores = array();
                for ($i=1; $i<=$numstores; $i++) {
                    $stores[] = $i;
                }
                $alphaStore = array(1=>'[H]',2=>'[D]');
                foreach ($laneupcs as $upc => $store_id) {
                    $bids = array();
                    $sps = array();
                    $spstr = "";
                    foreach ($this->upcs[$upc]['B'] as $k => $v) {
                        $bids[] = $k;
                    }
                    foreach ($stores as $store) {
                        foreach ($bids as $bid) {
                            $sps[] = $this->upcs[$upc]['B'][$bid]['salePrice'][$store];
                            $curHref = "http://{$FANNIEROOT_DIR}/batches/newbatch/EditBatchPage.php?id=";
                            $l = "<span style='color: grey'> | </span>";
                            $spstr .= "{$l}<a href='{$curHref}{$bid}' target='_blank'>"
                                .$this->upcs[$upc]['B'][$bid]['salePrice'][$store] ."</a>";
                        }
                        foreach ($bids as $bid) {
                            if ($saleprice = $this->upcs[$upc]['B'][$bid]['salePrice'][$store]) {
                                $specialprice = $this->upcs[$upc]['P'][$store];
                                if ($saleprice != $specialprice && !in_array($specialprice, $sps)) {
                                    $curHref = "http://{$FANNIEROOT_DIR}/batches/batchhistory/BatchHistoryPage.php?upc=";
                                    $ln = "<a href='{$curHref}{$upc}' target='_blank'><span class=\"scanicon-book\"></span></a>";
                                    $ieHref = "<a href='http://{$FANNIEROOT_DIR}/item/ItemEditorPage.php?searchupc={$upc}
                                        &ntype=UPC&searchBtn=' target='_blank'>{$upc}</a>";
                                    $td .= "
                                        <tr>
                                        <td>{$ieHref}</td>
                                        <td>{$this->upcs[$upc]['P']['brand']}</td>
                                        <td>{$this->upcs[$upc]['P']['description']}</td>
                                        <td>{$this->upcs[$upc]['P'][1]} | {$this->upcs[$upc]['P'][2]}</td>
                                        <td>{$ln}{$spstr}</td>
                                        <td>{$regNo}</td>
                                        </tr>";
                                }
                            }
                        }
                        unset($bids);
                        unset($sps);
                    }
                }
            }
        }
        catch (Exception $e) {
            //echo $e->getMessage();
        }

        return <<<HTML
{$td}
HTML;
    }

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
