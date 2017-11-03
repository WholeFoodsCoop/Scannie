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
        $this->getSales();
        $opdb = $this->getMissingSales("SCANHOST","SCANDB","back");
        /*$regNos = array(1,2,3);
        foreach ($regNos as $regNo) {
            $posdb .= $this->getMissingSales("SCANHOST","POSOPDB",$regNo);
        }*/

        return <<<HTML
<h4>Product/Batch Special Price Discrepancies <b>OP</b><span style="font-size: 12px;"> Operational Data Conflicts</span></h4>
<div id="mydiv" style="border: 2px solid lightgrey"> </div>
    {$opdb}
<div style="height: 5px;">&nbsp;</div>

<h4>Product/Batch Special Price Discrepancies <b>POS</b><span style="font-size: 12px;"> Point of Sale Data Conflicts</span></h4>
<div id="mydiv" style="border: 2px solid lightgrey"> </div>
    {$posdb}
<div style="height: 5px;">&nbsp;</div>
HTML;
    }

    private function getSales()
    {
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

        $prep = $dbc->prepare("
            SELECT bl.salePrice, bl.upc  
            FROM batches AS b 
                LEFT JOIN batchList AS bl ON b.batchID=bl.batchID 
            WHERE NOW() BETWEEN b.startDate AND b.endDate 
                AND b.batchName like '%Co-op Deals%' 
            GROUP BY bl.upc; 
        ");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $this->upcs[$row['upc']] = $row['salePrice'];
        }
    
        return false;
    }

    private function getMissingSales($h,$db,$end,$regNo="")
    {
        include('../config.php');
        $dbc = new SQLManager(${$h}.$regNo, 'pdo_mysql', ${$db}, $SCANUSER, $SCANPASS);

        /* backend is more straight-forward. Check items on sale against price they should be on sale for. */
        $backA= array();
        $backP = $dbc->prepare("
            SELECT b.batchName, p.special_price, bl.salePrice, p.upc, p.brand, p.description  
            FROM batches AS b 
                LEFT JOIN batchList AS bl ON b.batchID=bl.batchID 
                LEFT JOIN products AS p ON bl.upc=p.upc 
            WHERE NOW() BETWEEN b.startDate AND b.endDate 
                AND p.special_price <> bl.salePrice 
                AND b.batchName like '%Co-op Deals%' 
            GROUP BY bl.upc; 
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
            $cols = array('upc','brand','description','batchName','salePrice','special_price');
            foreach ($cols as $col) ${$col} = $row[$col];
            $td .= "<tr><td>{$upc}</td><td>{$brand}</td><td>{$description}</td>
                <td>{$batchName}</td><td>{$salePrice}</td><td>{$special_price}</td>{$regtd}</tr>";
        }

        return <<<HTML
<table class="table table-condensed small">
    {$td}
</table>
HTML;
    }
    
    public function css_content()
    {
return <<< HTML
#logview {
    height: 300px;
    overflow-y: auto;
}
HTML;
    }

    private function js()
    {
        ob_start();
        ?>
<script type="text/javascript">
$(document).ready( function () {
});

function getSpecialPriceCheck()
{
    $.ajax({
        url: 'specialPriceQuery.php',
        data: 'liveList=true',
        success: function(response) {
            $('#mydiv').html(response);
        }
    });
}

</script>
        <?php
        return ob_get_clean();
    }

}
ScancoordDispatch::conditionalExec();
