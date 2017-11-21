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
include(__DIR__.'/../../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
/**
*   @class CoopDealsBadPriceCheck
*
*   Find bad deals & missing sign info in batches,
*   make suggestions for sign style.
*/
class CoopDealsReview extends ScancoordDispatch
{

    protected $title = 'Coop Deals Review Page';
    protected $description = "[CDRP] All-in-one Co-op Deals Reviews.";
    protected $ui = TRUE;

    public function body_content() {

        include(__DIR__.'/../../config.php');
        $dbc = ScanLib::getConObj('SCANDB');

        $ret = '';
        $ret .= "
            <p>
                Coop Deals Review Page | 
                <a href='../Batches/unfiBreakdowns.php'>Breakdown Items</a>
            </p>
        ";
        $ret .= $this->form_content();
        $start = $_GET['startDate'];
        $upcs = $this->getProdsInBatches($dbc);
        if (isset($start)) {
            $ret .= $this->getBadPriceItems($dbc);
            $ret .= $this->getMissingSignText($dbc);
            $ret .= $this->getBadPrices($dbc);
            $ret .= $this->getNarrowItems($dbc,$upcs);
            $lineAlerts = substr_replace($this->getLineSales($dbc),"",-1);
            echo "$lineAlerts<br/><br/>";
        }

        return $ret;
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
        $prep = $dbc->prepare("SELECT upc FROM NarrowTags WHERE upc IN ({$inStr})");
        $res = $dbc->execute($prep,$args);
        $td = "";
        while ($row = $dbc->fetchRow($res)) {
            $td .= "<tr><td>{$row['upc']}</td></tr>";
        }

        return <<<HTML
<div class="panel panel-default mypanel">
    <legend class="panel-heading small">Print Narrow Signs</legend>
    <table class="table table-default table-condensed small">
    {$td}
    </table>
</div>
HTML;
    }

    private function getLineSales($dbc)
    {
        $ret = '';
        $startDate = $_GET['startDate'];
        $brands = array(
            "%Aura%" => "Aura Cacia Essential Oils",
            "%Life Factory%" => "Life Factory Products",
            "%Hydro Flase%" => "Hydro Flask Products",
            "%Emergen%" => "Emergen-C Products",
            "%Ener%" => "Ener-C Products",
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
                $ret .= " <b>" . $description . "</b>: <span class=\"alert-danger\">$rows items counted.</span> |";
            }
        }

        return $ret;

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
        $ret .= '<table class="table table-default table-condensed small">';
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
        $ret .= '<table class="table table-default table-condensed small" id="signText">';
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
        $ret .= '</table>';

        $ret .= '</div>';

        return $ret;
    }

    private function getBadPriceItems($dbc)
    {
        $startDate = $_GET['startDate'];
        $ret = '';
        $ret .='<div class="panel panel-default mypanel">
            <legend class="panel-heading small">Items with Bad Sales Prices</legend>';

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
		$ret .= '<table class="table table-default table-condensed small">';
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
        $ret = '';
        $ret .= '

                <strong>Start Date</strong>
                <form method="get" class="form-inline">
                    <input type="input" class="form-control" name="startDate">
                    <button type="submit" class="btn btn-default">Submit</button>
                </form>
        ';
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

    public function cssContent()
    {
        return <<<HTML
.mypanel {
}
@media only screen and (min-width: 1170px) {
    .mypanel {
        float: left;
        margin-left: 5px;
    }
}
HTML;
    }

}
ScancoordDispatch::conditionalExec();

