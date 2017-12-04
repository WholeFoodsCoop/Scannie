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

class MarginCalcNew extends ScancoordDispatch
{
    
    protected $title = "Margin Calculator";
    protected $description = "[] ";
    protected $ui = FALSE;
    protected $add_javascript_content = TRUE;
    protected $add_css_content = TRUE;
    
    public function body_content()
    {           
    
        $ret = '<br/>';
        
        include('../config.php');
        include('../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        
        $actualMargin = 0;
        $roundedSRP = 0;
        $rawSRP = 0;

        $_SESSION['dept_margin'] = $_GET['dept_margin'];
        $cost = $_GET['cost'];
        $price = $_GET['price'];
        $dept_marg = $_GET['dept_margin'];
        
        $ret .= '
            <form method="get">
            <div class="container-fluid">
                <div class="input-group">
                    <span class="input-group-addon input-sm">Cost</span>
                    <input class="form-control" name="cost" value="'.$cost.'">
                </div>
                <div class="input-group">
                    <span class="input-group-addon input-sm">Price</span>
                    <input class="form-control" name="price" value="'.$price.'">
                </div>
                <div class="input-group">
                    <span class="input-group-addon input-sm">dMarg</span>
                    <input class="form-control" name="dept_margin" value="'.$dept_marg.'">
                </div>
                <br />
                <div align="right"><button class="btn  btn-xs">submit</button>&nbsp;&nbsp;</div>
            </div>
            </form>
        ';
        $ret .= "<div class='container-fluid'>";
        $ret .= "<table class=\"table\" align=\"center\">";

        //  Find SRP
        if ($cost && $dept_marg){
            $dept_marg *= .01;
            $srp = $cost / (1 - $dept_marg);
            $round_srp = $rounder->round($srp);
            $ret .= "<tr><td>Raw SRP</td><td>" . sprintf('%.4f', $srp) . "</tr>";
            $ret .= "<tr><td>Rounded SRP</td><td><strong class='success'>" . $round_srp . "</strong></tr>";
        }

        //  Find Marginal Data
        if ($cost && $price) {
            $actualMargin = ($price - $cost) / $price;
            $ret .= "<tr><td style='width:180px;'>Actual Margin</td><td>" . sprintf('%.4f', $actualMargin) . "</tr>";
        } elseif ($round_srp) {
            $ret .= "<tr><td style='width:180px;'>Marg @ round srp </td><td><strong>" . sprintf('%.4f', ($round_srp - $cost) / $round_srp) . "</strong></tr>";
        }

        //  Find Cost
        if ($price && $dept_marg) {
            $dept_marg *= .01;
            $cost = - ( $price * ($dept_marg - 1)  );
            $ret .= "<tr><td>Approximate Cost</td><td>" . sprintf('%.2f', $cost) . "</tr>";
        }
        $ret .= "</table>";
        $ret .= "</div>";
        $ret .= "</div>";


        $ret .= "</div>";
        $ret .= '<div style="height: 250px;"></div>';

        
        return $ret;
    }
    
    private function form_content()
    {

    }
    
    public function css_content()
    {
        return '
body {
    overflow: -moz-scrollbars-horizontal;
    overflow-x: hidden;
    overflow-y: hidden;
}
input {
    background-color: black;
}
.form-control {
    border: 2px solid white;;
    background: linear-gradient(#fffcf7,#fff5e8);
}
.input-group-addon {
    border: 2px solid white;;
    width: 50px;
    background: linear-gradient(#fcf0cc,#ffe9ab);
}
.btn {
    background: linear-gradient(#fcf0cc,#ffe9ab);
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
    
    public function javascript_content()
    {
    
    }
    
    
}

ScancoordDispatch::conditionalExec();

 
