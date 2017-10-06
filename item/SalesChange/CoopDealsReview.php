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
include('../../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(dirname(__FILE__))) . '/common/sqlconnect/SQLManager.php');
}
/**
*   @class CoopDealsBadPriceCheck
*
*   Find bad deals & missing sign info in Batches.
*/
class CoopDealsReview extends ScancoordDispatch
{
    
    protected $title = 'Coop Deals Review Page';
    protected $description = "[CDRP] All-in-one Co-op Deals Reviews.";
    protected $ui = TRUE;
    
    public function body_content() {
        
        include('../../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $ret = '';
        $ret .= $this->form_content();
        $start = $_GET['startDate'];
        if (isset($start)) {
            $ret .= $this->getBadPriceItems($dbc);
            $ret .= $this->getMissingSignText($dbc);
            $ret .= $this->getBadPrices($dbc);
            $lineAlerts = substr_replace($this->getLineSales($dbc),"",-1);
            echo "$lineAlerts<br/><br/>";
        }
        
        return $ret;
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
        
        $ret .='<div class="panel panel-default" style="float:left" >
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
        $ret .='<div class="panel panel-default" style="float:left" >
            <legend class="panel-heading small">Items Missing Sign Text</legend>';
         
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
        $ret .= '<table class="table table-default table-condensed small">';
        while ($row = $dbc->fetchRow($result)) {
            $ret .= '<tr>';
            $ret .= '<td>' . $row['upc'] . '</td>';
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
        $ret .='<div class="panel panel-default" style="float:left" >
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
                    <input type="date" class="form-control" name="startDate">
                    <button type="submit" class="btn btn-default">Submit</button>
                </form>
            
        ';
        return $ret;
    }
    
    
}

ScancoordDispatch::conditionalExec();

