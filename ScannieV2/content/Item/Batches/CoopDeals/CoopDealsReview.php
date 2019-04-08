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
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../../../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../../../common/sqlconnect/SQLManager.php');
}
/**
*   @class CoopDealsBadPriceCheck
*
*   Find bad deals & missing sign info in batches,
*   make suggestions for sign style.
*/
class CoopDealsReview extends WebDispatch
{

    protected $title = 'Coop Deals Review Page';
    protected $description = "[CDRP] All-in-one Co-op Deals Reviews.";
    protected $ui = TRUE;

    public function body_content() {

        include(__DIR__.'/../../../../config.php');
        $dbc = ScanLib::getConObj('SCANDB');

        $ret = '';
        $ret .= "
            <p>
                Coop Deals Review Page | 
                <a href='UnfiBreakdowns.php'>Breakdown Items</a> | 
                <a href='http://$FANNIE_ROOTDIR/item/ProdLocationEditor.php' >Product Locations</a>
            </p>
        ";
        $ret .= $this->form_content();
        $start = $_GET['startDate'];
        $dealSet = $_GET['dealset'];
        $upcs = $this->getProdsInBatches($dbc);
        if (isset($start)) {
            $ret .= "
                <div class='row'>
                    <div class='col-lg-3'>
                    </div>
                    <div class='col-lg-4'>
                        {$this->getLineSales($dbc)}
                    </div>
                    <div class='col-lg-2'>
                        {$this->getGeneric($dbc,$start)}
                    </div>
                    <div class='col-lg-2'>
                        {$this->getNarrowItems($dbc,$upcs)}
                    </div>
                </div><br/>
                <div class='row'>
                    <div class='col-lg-4'>
                        <div class='table-responsive'>
                            {$this->getBadPriceItems($dbc)}
                        </div>
                    </div>
                    <div class='col-lg-4'>
                        {$this->getMissingSignText($dbc,$start)}
                    </div>
                    <div class='col-lg-3'>
                        <div class='table-responsive'>
                            {$this->getBadPrices($dbc,$upcs)}
                        </div>
                    </div>
                </div>
                <div class='row'>
                    <div class='col-lg-4'>
                        <div class='table-responsive'>
                            {$this->getSkuMatch($dbc,$dealSet)}
                        </div>
                    </div>
                    <div class='col-lg-4'>
                    </div>
                    <div class='col-lg-3'>
                        <div class='table-responsive'>
                        </div>
                    </div>
                </div>
            ";
        }
        $this->addOnloadCommand("$('#startDate').datepicker({dateFormat: 'yy-mm-dd'});");

        return <<<HTML
<div class="container-fluid">
$ret
</div>
HTML;
    }

    private function getProdsInBatches($dbc) {
        $startDate = $_GET['startDate'];
        $args = array($startDate);
        $prep = $dbc->prepare("SELECT bl.upc FROM batchList AS bl
            LEFT JOIN batches AS b ON bl.batchID=b.batchID
            LEFT JOIN products AS p ON bl.upc=p.upc
            LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE startDate = ?
                AND m.superID in (0,1,3,4,7,8,9,13,17)
            GROUP BY bl.upc
        ");
        $res = $dbc->execute($prep,$args);
        $upcs = array();
        while ($row = $dbc->fetchRow($res)) {
            $upcs[] = $row['upc'];
        }

        return $upcs;
    }

    private function getNarrowItems($dbc,$upcs) {
        list($inStr, $args) = $dbc->safeInClause($upcs);
        $prep = $dbc->prepare("SELECT upc FROM productUser WHERE upc IN ({$inStr}) AND narrow = 1");
        $res = $dbc->execute($prep,$args);
        $td = "";
        $rUpcs = array();
        while ($row = $dbc->fetchRow($res)) {
            $td .= "<tr><td>{$row['upc']}</td></tr>";
            $rUpcs[] = $row['upc'];
        }
        $ret = "<p><b>Print as Narrow Signs</b></p>";
        $ret .= "<textarea class='form-control'>";
        foreach ($rUpcs as $upc) {
            $ret .= $upc."\r\n";
        }
        $ret .= "</textarea>";

        return $ret;
    }

    private function getLineSales($dbc)
    {
        $ret = '<p><b>Print as List Signage</b></p>';
        $startDate = $_GET['startDate'];
        $brands = array(
            "%Aura%" => "Aura Cacia Essential Oils",
            "%Life Factory%" => "Life Factory Products",
            "%Hydro Flask%" => "Hydro Flask Products",
            "%Emergen%" => "Emergen-C Products",
            "%Ener%" => "Ener-C Products",
            "%Klean%" => "Klean Kanteen",
        );
        $rows = array();
        foreach ($brands as $brand => $description) {
            $args = array($startDate,$brand);
            $query = $dbc->prepare("SELECT COUNT(*) FROM batches AS b
                LEFT JOIN batchList AS bl ON b.batchID=bl.batchID
                LEFT JOIN products AS p ON bl.upc=p.upc
                WHERE startDate = ?
                AND p.brand like ? GROUP BY bl.upc;
            ");
            $result = $dbc->execute($query,$args);
            if ($rows = $dbc->numRows($result)) {
                $ret .= "<li>" . $description . ": <span class=\"alert-danger\">$rows items counted.</span></li>";
            }
        }

        return $ret;
    }
    
    private function getGeneric($dbc,$startDate)
    {
        $ret = '<p><b>Print as Generic Signs</b></p>';
        $items = array(
            '0070059600011', 
            '0070059600085',
            '0070059600251',
            '0085981500287',
        );
        list($inStr,$args) = $dbc->safeInClause($items);
        $query = "SELECT p.upc, p.description, p.size FROM batches AS b
            LEFT JOIN batchList AS bl ON b.batchID=bl.batchID
            LEFT JOIN products AS p ON bl.upc=p.upc
            WHERE startDate = '$startDate'
            AND p.upc in ($inStr)
            GROUP BY bl.upc;";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);
        $upcs = array();
        while ($row = $dbc->fetchRow($result)) {
            $upcs[] = $row['upc'];
        }
        $ret .= "<textarea class='form-control' cols='5'>";
        foreach ($upcs as $upc) {
            $ret .= $upc."\r\n";
        }
        $ret .= "</textarea><br/>";

        if ($er = scanLib::getDbcError($dbc)) {
            return $er; 
        } else {
            return $ret;
        }
    }

    private function getBadPrices($dbc)
    {
        $ret = '';

        $startDate = $_GET['startDate'];;
        $query = $dbc->prepare("
            SELECT bl.*, p.brand, p.description, p.normal_price
            FROM batchList AS bl
                LEFT JOIN batches AS b ON bl.batchID=b.batchID
                LEFT JOIN products AS p ON bl.upc=p.upc
            WHERE startDate = '{$startDate}'
            GROUP BY bl.upc;
        ");

        $discount = array();
        $result = $dbc->execute($query);
        while ($row = $dbc->fetchRow($result)) {
            $salePrice = $row['salePrice'];
            $price = $row['normal_price'];
            $discount[$row['upc']]['off'] = (1 - ($salePrice / $price)) * 100;
            $discount[$row['upc']]['description'] = $row['description'];
            $discount[$row['upc']]['batchID'] = $row['batchID'];
            //$ret .= $row['upc'];
        }
        $ret .= $dbc->error();

        $ret .='<div class="panel panel-default mypanel">
            <legend class="panel-heading small">Items with HIGH % Deals</legend>';
        $ret .= '<table class="table table-default table-condensed small table-striped">';
        foreach ($discount as $upc => $percent) {
            $batchL = '<a href="http://192.168.1.2/git/fannie/batches/newbatch/EditBatchPage.php?id='
				. $percent['batchID'] .'" target="_blank">' . $percent['batchID'] . '</a>';
            if ($percent['off'] > 60 && !strstr($upc,"LC")) {
                $ret .= '<tr>';
                $ret .= '<td>' . $upc . '</td><td>' . sprintf('%d',$percent['off']) . '% OFF</td>';
                $ret .= '<td>' . $percent['description'] . '</td>';
                $ret .= '<td>' . $batchL . '</td>';
                $ret .= '</tr>';
            }
        }
        $ret .= '</table></div>';

        return $ret;
    }

    private function getMissingSignText($dbc)
    {
        $startDate = $_GET['startDate'];;
        $ret = '';
        $ret .='<div class="panel panel-default mypanel">
            <legend class="panel-heading small">Items Missing Sign Text
                <div>
                    <button type="button" class="btn btn-default btn-xs"
                        onClick="hideNoSales(); return false;">Hide <i>no sales</i> items</button>
                    <button type="button" class="btn btn-default btn-xs"
                        onClick="hideKleanKanteen(); return false;">Hide <i>Klean Kanteen</i></button>
                </div>
            </legend>';

        $query = $dbc->prepare("
            SELECT
                pu.upc,
                pu.description,
                pu.brand,
                p.description AS pDesc,
                p.brand AS pBrand,
                p.last_sold
            FROM productUser AS pu
                LEFT JOIN products AS p ON pu.upc=p.upc
                LEFT JOIN batchList AS bl ON pu.upc=bl.upc
                LEFT JOIN batches AS b ON bl.batchID=b.batchID
            WHERE (pu.description = '' OR pu.description IS NULL)
                AND b.startDate = '{$startDate}'
                AND b.batchType != 4
                AND b.batchType != 11
            GROUP BY pu.upc
        ;");

        $result = $dbc->execute($query);
        $ret .= '<div class="table-responsive"><table class="table table-default table-condensed small table-striped" id="signText">';
        while ($row = $dbc->fetchRow($result)) {
            $ret .= '<tr>';
            $ret .= '<td><a href="http://key/git/fannie/item/ItemEditorPage.php?searchupc=' . $row['upc'] . '&ntype=UPC&searchBtn=" target="_blank">'.$row['upc'].'</a></td>';
            $ret .= '<td>' . $row['pBrand'] . '</td>';
            $ret .= '<td></td>';
            $ret .= '<td>' . $row['pDesc'] . '</td>';
            $ret .= '<td></td>';
            if (is_null($row['last_sold'])) {
                $ret .= '<td><span style="color:grey"><i> no sales recorded</i></span></td>';
            } else {
                $ret .= '<td>' . $row['last_sold'] . '</td>';
            }
            $ret .= '</tr>';
        }
        $ret .= '</table></div>';

        $ret .= '</div>';

        return $ret;
    }

    private function getBadPriceItems($dbc)
    {
        $startDate = $_GET['startDate'];
        $ret = '';
        $ret .='<div class="panel panel-default mypanel">
            <legend class="panel-heading small">Items with Bad Sale Prices</legend>';

        $query = $dbc->prepare("
            SELECT bl.*, p.brand, p.description
            FROM batchList AS bl
                LEFT JOIN batches AS b ON bl.batchID=b.batchID
                LEFT JOIN products AS p ON bl.upc=p.upc
            WHERE startDate = '{$startDate}'
                AND p.normal_price <= bl.salePrice
            GROUP BY bl.upc;
        ");

        $result = $dbc->execute($query);
		$ret .= '<table class="table table-default table-condensed small table-striped">';
        while ($row = $dbc->fetchRow($result)) {
			$editL = '<a href="http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc=' . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a> ';
            $batchL = '<a href="http://192.168.1.2/git/fannie/batches/newbatch/EditBatchPage.php?id='
				. $row['batchID'] .'" target="_blank">' . $row['batchID'] . '</a>';
			$ret .= '<tr>';
			$ret .= '<td>' . $row['upc'] . '</td>';
			$ret .= '<td>' . $batchL . '</td>';
			$ret .= '<td>' . $row['brand'] . '</td>';
			$ret .= '<td>' . $row['description'] . '</td>';
			$ret .= '</tr>';
        }
		$ret .= '</table>';

        $result = $dbc->execute($query);


        $ret .= '</div>';

        return $ret;
    }

    private function form_content()
    {
        $v = $_GET['startDate'];
        $d = $_GET['dealset'];
        return <<<HTML
<strong>Start Date</strong>
<div class="row">
    <div class="col-lg-2">
        <form method="get" class="">
            <div class="form-group">
                <input type="input" class="form-control mainInput" name="startDate" id="startDate" value="{$v}">
            </div>
            <div class="form-group">
                <input type="input" class="form-control mainInput" name="dealset" placeholder="DealSet" value="{$d}">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default mainInput">Submit</button>
            </div>
        </form>
    </div>
</div>
HTML;
    }

    private function getSkuMatch($dbc, $dealset)
    {
        $args = array($dealset);
        $prep = $dbc->prepare("
            SELECT 
                p.description, p.brand, c.upc, price, abtpr, promoDiscount 
            FROM CoopDealsItems AS c
                LEFT JOIN products AS p ON c.upc=p.upc
            WHERE dealSet = ? 
                AND skuMatch <> 0 
            GROUP BY p.upc
            ORDER BY upc ASC;");
        $res = $dbc->execute($prep, $args); 
        $ret = '';
        $ret .='<div class="panel panel-default mypanel">
            <legend class="panel-heading small">Multiple SKU items in Final Commit</legend>';
        $ret .= '<table class="table table-default table-condensed small table-striped">';
        $ROWS = array('upc', 'brand', 'description', 'price', 'abtpr', 'promoDiscount');
        $upcs = array();
        $tdata = ''; 
        while ($row = $dbc->fetchRow($res)) {
            $upcs[] = $row['upc'];
            $tdata .= '<tr>';
            foreach ($ROWS as $col) {
                $temp = $row[$col];
                $tdata .= "<td>$temp</td>";
            }
            $tdata .= '</tr>';
        }
        $ret .= $tdata;
        $ret .= '</table></panel>';
        $ret .= ($dbc->error()) ? "<div class='alert alert-danger'>{$dbc->error()}</div>" : ""; 
        
        return $ret;
    }

    public function javascriptContent()
    {
        return <<<HTML
function hideNoSales()
{
    $('#signText').find('td').each(function() {
        var thisHTML = $(this).text();
        if (thisHTML.includes('no')) {
            $(this).closest('tr').hide();
        }
    });

    return false; 
}

function hideKleanKanteen()
{
    $('#signText').find('td').each(function() {
        var thisHTML = $(this).text();
        if (thisHTML.includes('KLEAN')) {
            $(this).closest('tr').hide();
        }
    });

    return false; 
}

HTML;
    }

    public function css_content()
    {
        return <<<HTML
.mainInput {
    border: 2px solid grey;
}
body {
    overflow-x: hidden;
}
HTML;
    }

}
WebDispatch::conditionalExec();
