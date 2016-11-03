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

class generic_testing_page extends ScancoordDispatch
{
    
    protected $title = "none";
    protected $description = "[none] blank.";
    protected $ui = TRUE;
    protected $readme = "This is a generic page for running and drawing a query to table.
        By default, every column returned by query will be drawn in table, in the order 
        selected.";
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        //include('../common/lib/PriceRounder.php');
        //$rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }

        $ret .= $this->form_content();
        $upcs = array();
        //$upcString = '';
        if ($_GET['upcs']) {
            foreach ($_GET['upcs'] as $value) {
                $upcs[] = $value;
                echo $value;
            }
        }
        $upcString = implode(",",$upcs);
        echo $upcString;
         
        $query = $dbc->prepare("
            select 
                pu.upc, 
                pu.description, 
                pu.modified, 
                pu.cost,
                p.brand
            from prodUpdate as pu
                left join products as p on pu.upc=p.upc
            where pu.upc in (411,818,4011,218,217,425,495,666) 
            group by pu.cost, pu.upc 
            order by pu.upc, pu.modified;");
        $result = $dbc->execute($query);
        if ($dbc->error()) echo $dbc->error();
        
        $item = array( array(
            'cost',
            'modified',
            'days',
            'description',
            'brand'
        ));
        
        while ($row = $dbc->fetch_row($result)) {
            $item[$row['upc']]['cost'][$row['modified']] = $row['cost'];
            $item[$row['upc']]['description'] = $row['description'];
            $item[$row['upc']]['brand'] = $row['brand'];
        }

		echo '<div class="panel panel-default">';        
        echo '<table class="table table-condensed table-striped small">';
        echo '
            <thead>
                <th>upc</th>
                <th>brand</th>
                <th>description</th>
                <th>cur cost</th>
                <th>prev cost</th>
                <th>modified</th>
				<th>ago</th>
            </thead>
        ';
        foreach ($item as $upc => $data) {
            echo '<tr>';
            echo '<td>' . $upc . '</td>';
            echo '<td>' . $data['brand'] . '</td>';
            echo '<td>' . $data['description'] . '</td>';
            echo '<td>' . end($data['cost']) . '</td>';
            echo '<td><span style="color:grey">' . prev($data['cost']) . '</span></td>';
            echo '<td>' . substr(key($data['cost']),0,10) . '<span style="color:lightgrey">';
            echo ' ' . substr(key($data['cost']),11,5) . '</span></td>';
			$new = strtotime(key($data['cost']));
			end($data['cost']);
			$prev = strtotime(key($data['cost']));
			$elapsed = $prev - $new;
			$elapsed = $this->time_elapsed($elapsed);
			
            echo '<td>' . $elapsed . '</td>';

        }
        echo '</table></div>';

        

        
        return $ret;
    }

	private function time_elapsed($secs)
	{
		$bit = array(
			'y' => $secs / 31556926 % 12,
			'w' => $secs / 604800 % 52,
			'd' => $secs / 86400 % 7
		);
		foreach($bit as $k => $v) 
		{
			if ($v>0) $ret[] = $v . $k;
		}

		return join(' ',$ret);
	}
    
    private function form_content()
    {
        return '
            <div class="text-center container">
                <form class ="form-inline"  method="get" > 
                    <br>
                    <div class="form-group">    
                        <textarea class="form-control" name="upcs"></textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default btn-sm" value="submit">
                    </div>
                </form>
            </div>
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
