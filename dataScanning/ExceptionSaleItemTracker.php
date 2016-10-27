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

class ExceptionSaleItemTracker extends ScancoordDispatch
{
    
    protected $title = "Exception Sales";
    protected $description = "[Exception Sales] Monitor special prices of sale items.";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        include('../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $ret .= '<div class="container">';
        $ret .= '<h4>Exception Sale Items</h4>';
         
        $query = $dbc->prepare("
            select 
                upc, 
                brand, 
                description,
                special_price 
            from products 
            where upc = 0081878001252
        ");
        $result = $dbc->execute($query);
        $data = array();
        while ($row = $dbc->fetch_row($result)) {
            $data[$row['upc']]['brand'] = $row['brand'];
            $data[$row['upc']]['desc'] = $row['description'];
            $data[$row['upc']]['salePrice'][] = $row['special_price'];
        }
        if ($dbc->error()) $ret .=  $dbc->error();
        
        $ret .=  '<div class="panel panel-default" style="width:800px"><table class="table table-striped">';
        $ret .=  '
            <thead>
                <th>upc</th>
                <th>brand</th>
                <th>description</th>
                <th>Hill | Den</th>
            </thead>';
        
        foreach ($data as $upc => $array) { 
            $ret .= '<tr>';
            $ret .= '<td>' . $upc . '</td>';
            $ret .= '<td>' . $array['brand'] . '</td>';
            $ret .= '<td>' . $array['desc'] . '</td>';
            $ret .= '<td>' . $array['salePrice'][0];
            $ret .= ' | ' . $array['salePrice'][1] . '</td>';
        }
        $ret .=  '</table></div>';
        $ret .= '</div>';
        
        return $ret;
    }
    
}

ScancoordDispatch::conditionalExec();

 
