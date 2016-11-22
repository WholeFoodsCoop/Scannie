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

class PercentCalc extends ScancoordDispatch
{
    
    protected $title = "none";
    protected $description = "[none] blank.";
    protected $ui = FALSE;
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        include('../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        //$dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
		if ($_GET['price'] && $_GET['percent']) {
			$price = $_GET['price'];
			$percent = $_GET['percent'];
			$newprice = $price - ($price * ($percent * 0.01));
			$newprice = $rounder->round($newprice);
		}

		$ret .= '
<div align="center">
<form method="get">
<table>

	<tr>
	<td style="width:90px">Price </td><td><input type="text" name="price" style="width:90px" autofocus></td></tr>
	<td>% </td><td><input type="text" name="percent" value="'.$percent.'" style="width:90px"></td></tr>
	<td></td><td><button type="submit" class="btn btn-default btn-sm">submit</button></td>
	<tr><td>.</td></tr>
	<td>New Price</td><td>'.$newprice.'</td>

</table>
</form>
</div>
		';        

        return $ret;
    }
    
    private function form_content()
    {
        return '
            <div class="text-center container">
                <form class ="form-inline"  method="get" > 
                    <br>
                    <div class="form-group">    
                        <input type="text" class="form-control" name="upc" placeholder="enter plu to track" autofocus>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default" value="submit">
                    </div>
                </form>
            </div>
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
