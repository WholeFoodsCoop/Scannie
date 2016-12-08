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
    
    function preprocess()
    {
        
    }
    
    public function body_content()
    {           
        $ret = '';
        //include(dirname(__FILE__).'/ExceptionSaleItems.php');
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $ret .= '<div class="container">';
        $ret .= '<h4>Exception Sale Items</h4>';
        $ret .= $this->form_content();
        
        if (strlen($_POST['addItem']) == 13) {
            $upc = $_POST['addItem'];
			$note = $_POST['note'];
			$args = array($upc,$note);
            $prep = $dbc->prepare("insert into woodshed_no_replicate.exceptionItems (upc,note) values (?,?)");
            $dbc->execute($prep,$args);
            unset($_POST['addItem']);
			unset($_POST['note']);
        }
        if ($_POST['rmItem']) {
            $rmItem = $_POST['rmItem'];
            $prep = $dbc->prepare("delete from woodshed_no_replicate.exceptionItems where upc = ?");
            $dbc->execute($prep,$rmItem);
            unset($_POST['rmItem']);
        }
         
        //var_dump($items);
        
        list($in_sql, $args) = $dbc->safeInClause($items);
        $query = '
            select 
                p.upc, 
                p.brand, 
                p.description,
                p.special_price,
				e.note
            from products as p
				INNER JOIN woodshed_no_replicate.exceptionItems AS e ON e.upc=p.upc
            WHERE p.upc IN (SELECT upc FROM woodshed_no_replicate.exceptionItems)
        ';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);
        $data = array();
        while ($row = $dbc->fetch_row($result)) {
            $data[$row['upc']]['brand'] = $row['brand'];
            $data[$row['upc']]['desc'] = $row['description'];
            $data[$row['upc']]['salePrice'][] = $row['special_price'];
			$data[$row['upc']]['note'] = $row['note'];
        }
        if ($dbc->error()) $ret .=  $dbc->error();
        
        $ret .=  '<div class="panel panel-default" style="width:90%"><table class="table table-striped">';
        $ret .=  '
            <thead>
                <th>upc</th>
                <th>brand</th>
                <th>description</th>
                <th>Hill | Den</th>
				<th>Notation</th>
            </thead>';
        
        foreach ($data as $upc => $array) { 
            $upcLink = '<a href="http://192.168.1.2/git/fannie/reports/ItemBatches/ItemBatchesReport.php?upc=' . $upc . '" target="_blank">' . $upc . '</a>';
            $ret .= '<tr>';
            $ret .= '<td>' . $upcLink . '</td>';
            $ret .= '<td>' . $array['brand'] . '</td>';
            $ret .= '<td>' . $array['desc'] . '</td>';
            $ret .= '<td>' . $array['salePrice'][0];
            $ret .= ' | ' . $array['salePrice'][1] . '</td>';
			$ret .= '<td>' . $array['note'] . '</td>';
        }
        $ret .=  '</table></div>';
        $ret .= '</div>';
        
        return $ret;
    }
    
    public function form_content()
    {
        $ret .= '
            <form method="post" class="form-inline">
                <div class="input-group">
                    <span class="input-group-addon">Add Item:</span>
                    <input type="text" class="form-control" id="addItem" style="width: 120px; font-size: 10px" name="addItem" >&nbsp;&nbsp;
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Remove Item:</span>
                    <input type="text" class="form-control" id="rmItem" style="width: 120px; font-size: 10px" name="rmItem" >&nbsp;&nbsp;
                </div>
				<div class="input-group">
					<span class="input-group-addon">Notate:</span>
					<input type="text" class="form-control" id="note" style="width: 175px" name="note" >&nbsp;&nbsp;
				</div>
                <button type="submit" class="btn btn-default">Add/Remove Item</button>
            </form>
        ';
        
        return $ret;
    }
    
}

ScancoordDispatch::conditionalExec();

 
