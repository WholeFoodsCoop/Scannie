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
    protected $add_css_content = TRUE;
    
    public function body_content()
    {           
        $ret = '<br/>';
        include('../config.php');
        include('../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        
        $rawprice = 0;
        $newprice = 0;
        $price = 0;
        $percent = 0;
        if (FormLib::get('price') && FormLib::get('percent')) {
            $price = FormLib::get('price');
            $percent = FormLib::get('percent');
            $newprice = $price - ($price * ($percent * 0.01));
            $rawprice = $newprice;
            $newprice = $rounder->round($newprice);
        }

		$ret .= '
<div align="center">
    <form method="get">
    <div class="container-fluid">
        <div class="input-group">
            <span class="input-group-addon input-sm" autofocus>Price</span>
            <input class="form-control" name="price" value="'.$price.'">
        </div>
        <div class="input-group">
            <span class="input-group-addon input-sm">Precent Off</span>
            <input class="form-control" name="percent" value="'.$percent.'">
        </div>
        <br />
        <div align="right"><button class="btn btn-xs" type="submit">submit</button>&nbsp;&nbsp;</div>
    </div>
    </form>

    <table>
        <tr>
        <td>Raw Price</td><td>'.sprintf('%0.2f',$rawprice).'</td>
        </tr><tr>
        <td>New Price</td><td>'.$newprice.'</td>
    </table>
    
</div>
		';        
		$ret .= '<br><br><br>';
 
        return $ret;
    }
    
public function css_content()
    {
        return '
td {
    min-width: 120px;
}
.input-group-addon{ min-width: 100px;
}
body {
    overflow: -moz-scrollbars-horizontal;
    overflow-x: hidden;
    overflow-y: hidden;
}
.form-control {
    background-color: rgba(255,255,255,0.9);
}
.input-group-addon {
    width: 50px;
}
.btn {
}
table td,th {                   
  border-top: none !important;
}                               
body {
  //color: #cacaca;
  font-family: consolas;
}
.success {
  color: #74aa04;
}
.danger {
  color: #a70334;
}
.warning {
  color: #b6b649;
}
.info {
  color: #58c2e5;
}
.purple {
  color: #89569c;
}
.primary {
  color: #1a83a6;
}
.invisInput {
  //background-color: #555f6b;
  background-color: rgbs (0, 0, 0, 0);
  border: none;
  width: 150px;
  color: #cacaca;
  font-family: consolas;
  font-size: 16px;
  opacity: 0.9
}
input:focus, button:focus, a:focus {
  //border: 1px solid blue;
  background: rgbs (0, 0, 0, 0);
  border: none;
}
textarea {
  width: 100%;
  height: 100%;
  
}
fieldset {
    border: 1px dotted grey;
}
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
