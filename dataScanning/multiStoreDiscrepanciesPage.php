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

include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}

class multiStoreDiscrepanciesPage extends ScancoordDispatch
{
    
    protected $title = "Multi Store Discrepancy Page";
    protected $description = "[Multi Store Discrepancy Page] Checks for discrepancies in 
        product data between 2 store locations.";
    protected $ui = TRUE;
    
    private function getDiscrepancies($dbc, $field)
    {
        
        //$rounder = new \COREPOS\Fannie\API\item\PriceRounder();
        $itemA = array();
        $itemB = array();
        $ret = '<span class="text-success">' . ucwords($field)  . ' Discrepances : </span>';
        
        $diffR = $dbc->query("
            SELECT upc, description
            FROM products
            WHERE inUse = 1
            GROUP BY upc
            HAVING MIN({$field}) <> MAX({$field})
            ORDER BY department
        ");
        $count = $dbc->numRows($diffR);
        $msg = "";
        if ($count > 0 ) {
            while ($row = $dbc->fetchRow($diffR)) {
                $itemA[$row['upc']] = $row['description'];
            }
        }
        
        
        $ret .=  $count . ' discrepancies were discovered.<br>';
        
        $ret .=  '<div align="left"><table class="table-striped" style="border: none">';
        $ret .=  '
            <thead>
                <th style="width:120px;text-align:center"></th>
                <th style="width:220px;text-align:center"></th>
                <th style="width:50px;text-align:center"></th>
            </thead>
        ';
        foreach ($itemA as $upc => $value)  {
            $ret .=  '<tr>';
            $ret .=  '<td><a href="http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc=' . $upc . '&ntype=UPC&searchBtn=" target="_blank">' . $upc . '</a></td>';
            $ret .= '<td>'.$value.'</td>';
            $ret .= '<td><div align="center"><a href="http://key/scancoord/item/TrackChangeNew.php?upc=' . $upc . '" target="_blank"><img src="../common/src/img/q.png" style="width:20px"></a></div></td>';
            $ret .=  '</tr>';
        }
        $ret .=  '</table></div>';
        if (mysql_errno() > 0) {
            $ret .=  mysql_errno() . ": " . mysql_error(). "<br>";
        }    

        $ret .=  '<br>';     
        
        if ($count > 0) {
            return $ret;
        } else { 
            return false;
        }
    }
    
    public function body_content()
    {        
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $ret .= '';
        $ret .= '<div class="container">';
        $ret .=  '<h4><span class="purple">Multi Store Discrepancies Page</span></h4><br>
            This page scans for discrepancies between multiple stores.<br>
            <span>If this page is blank - no discrepancies were found.</span><br><br>';
      
        $fields = array(
            'normal_price',
            'cost',
            'tax',
            'foodstamp',
            'wicable',
            'discount',
            'scale',
            'department', 
            'description',
            'brand',
            'local',
            'price_rule_id',
        );
        
        $getDiscreps = false;
        foreach ($fields as $field) $ret .=  $this->getDiscrepancies($dbc,$field);
        $ret .=  "</div>"; 
        
        
        return $ret;
    }
    
}

ScancoordDispatch::conditionalExec();