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
include('../../../../../../var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include('../../../../../../var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}
/**
*   @class CoopDealsBadPriceCheck
*
*   Find bad deals & missing sign info in Batches.
*/
class CoopDealsReviewPage
{
    
    protected $title = 'Bad Price Check';
    
    public function view() {
        include('../../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        print self::form_content();
        $start = FormLib::get('startDate');
        if (isset($start)) {
            print self::getBadPriceItems($dbc) 
                . self::getMissingSignText($dbc)    
                . self::getBadPrices($dbc);
        }
        
        return false;
    }
    
    private function getBadPrices($dbc)
    {
        $ret = '';
        
        $startDate = FormLib::get('startDate');
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
        
        $ret .='<fieldset style="border: 1px dotted black; width: 650px; float:left">
            <legend>Items with HIGH % Deals</legend>';
        $ret .= '<table>';
        foreach ($discount as $upc => $percent) {
            $batchL = '<a href="http://192.168.1.2/git/fannie/batches/newbatch/EditBatchPage.php?id=' 
				. $percent['batchID'] .'" target="_blank">' . $percent['batchID'] . '</a>';
            if ($percent['off'] > 60) {
                $ret .= '<tr>';
                $ret .= '<td>' . $upc . '</td><td>' . sprintf('%d',$percent['off']) . '% OFF</td>';
                $ret .= '<td>' . $percent['description'] . '</td>';
                $ret .= '<td>' . $batchL . '</td>';
                $ret .= '</tr>';
            }
        }
        $ret .= '</table></fieldset>';
        
        return $ret;
    }
    
    private function getMissingSignText($dbc)
    {
        $startDate = FormLib::get('startDate');
        $ret = '';
        $ret .='<fieldset style="border: 1px dotted black; width: 650px; float:left">
            <legend>Items Missing Sign Text</legend>';
         
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
        $ret .= '<table>';
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
        
        $ret .= '</fieldset>';
        return $ret;
    }
    
    private function getBadPriceItems($dbc)
    {           
        $startDate = FormLib::get('startDate');
        $ret = '';
        $ret .='<fieldset style="border: 1px dotted black; width: 500px; float:left">
            <legend>Items with Bad Sales Prices</legend>';
        
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
		$ret .= '<table>';
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
        $ret .= '
            &nbsp;&nbsp;<span style="color:grey">copy/paste</span><br>
            &nbsp;&nbsp;<textarea class="" style="height:100px;">
        ';
        while ($row = $dbc->fetchRow($result)) {
            $ret .= $row['upc'] . "\r";
        }
        $ret .= '</textarea><br>';
        $ret .= '<span class="danger">' . $dbc->error() . '</span>';
        
        $ret .= '</fieldset>';
        
        return $ret;
    }
    
    private function form_content()
    {
        $ret = ''; 
        $ret .= '
            <fieldset style="border: 1px dotted black; width: 300px;height:50px">
                <legend>Start Date</legend>
                <form method="get">
                    <input type="date" name="startDate">
                    <button type="submit">Submit</button>
                </form>
            </fieldset>
        ';
        return $ret;
    }
    
    
}
CoopDealsReviewPage::view();

