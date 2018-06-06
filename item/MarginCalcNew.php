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

include(__DIR__.'/../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../common/sqlconnect/SQLManager.php');
}

class MarginCalcNew extends ScancoordDispatch
{
    
    protected $title = "Margin Calculator";
    protected $description = "[] ";
    protected $ui = FALSE;
    
    public function body_content()
    {           
    
        $ret = '<br/>';
        
        include(__DIR__.'/../config.php');
        include(__DIR__.'/../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        
        $actualMargin = 0;
        $roundedSRP = 0;
        $rawSRP = 0;

        $dept_marg = FormLib::get('dept_margin', false);
        $cost = FormLib::get('cost');
        if ($dept_marg != false) $_SESSION['dept_margin'] = $dept_marg;
        
        $ret .= '
            <form method="get">
            <div class="container-fluid">
                <div class="input-group">
                    <span class="input-group-addon input-sm">Cost</span>
                    <input class="form-control" name="cost" value="'.$cost.'">
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
        $dept_marg *= .01;
        $srp = $cost / (1 - $dept_marg);
        $round_srp = $rounder->round($srp);
        $ret .= "<tr><td>Raw SRP</td><td>" . sprintf('%.3f', $srp) . "</tr>";
        $ret .= "<tr><td>Rounded SRP</td><td><strong class='success'>" . $round_srp . "</strong></tr>";

        $ret .= "</table>";
        $ret .= "</div>";
        $ret .= "</div>";


        $ret .= "</div>";
        $ret .= '<div style="height: 250px;"></div>';

        
        return $ret;
    }
    
    public function css_content()
    {
        return '
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
    
    public function javascript_content()
    {
    
    }
    
    
}

ScancoordDispatch::conditionalExec();

 
