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
session_start();
?><link rel="shortcut icon" type="image/x-icon" href="common/src/img/cs.ico" /><?php
include(dirname(__FILE__) . '/var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}

?>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<html>
<head>
<!--  <link rel="stylesheet" href="bootstrap/bootstrap.min.css"> -->
  <script src="../common/bootstrap/jquery.min.js"></script>
  <script src="../common/bootstrap/bootstrap.min.js"></script>
</head>
<title>Margin Calculator</title>
<style>
  table td,th {                   
      border-top: none !important;
  }                               
  body {
      background-color: #272822;
      color: #cacaca;
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
      background-color: #555f6b;
      border: none;
      width: 150px;
      color: #cacaca;
      font-family: consolas;
      font-size: 16px;
  }
  input:focus {
      //border: 1px solid blue;
  }
  textarea {
      width: 100%;
      height: 100%;
      
  }
fieldset {
    border: 1px dotted darkslategrey;
}
</style>
<script language="javascript" type="text/javascript">
<!--
function popitup(url) {
	newwindow=window.open(url,'name','height=400,width=300');
	if (window.focus) {newwindow.focus()}
	return false;
}
// -->
</script>
<body>

<!--<a href="popupex.html" onclick="return popitup('http://key/scancoord/item/marginCalc.php')"
	>popout</a>-->

<?php

$actualMargin = 0;
$roundedSRP = 0;
$rawSRP = 0;
$rounder = new \COREPOS\Fannie\API\item\PriceRounder();

/*  How to call PriceRounder::round() using namespace.
$var = 12.32;
$var = COREPOS\Fannie\API\item\PriceRounder::round($var);
echo $var;
*/

$_SESSION['dept_margin'] = $_GET['dept_margin'];
$cost = $_GET['cost'];
$price = $_GET['price'];
$dept_marg = $_GET['dept_margin'];

print '
  <fieldset>
    <legend>Price Calculator</legend>
    <form method="get" id="id1">
    <table>
        <th></th><th></th><th></th>
        <tr><td>cost:</td>
        <td><input type="text" class="invisInput" name="cost" autofocus autocomplete="off"></tr>
        <td>price:</td><td><input type="text" class="invisInput" name="price" autocomplete="off"></tr>
        <td>dept_mg: &nbsp;</td><td><input type="text" class="invisInput" autocomplete="off" name="dept_margin" value="
            '; 
                if ($_GET['dept_margin']) { 
                    echo sprintf('%.4f', $_GET['dept_margin']);
                }
print '"></td></tr><tr><td><button type="submit" class="invisInput" style="width:70px;">Submit</button></td><td><a value="back" onClick="history.go(-1);return false;">back</a></td></tr>
        <td></td><td align="center"><input type="submit" hidden></tr>
    </table> 

    </form>
  </fieldset>
';
echo "<fieldset><legend>Results</legend>";
echo "<table class=\"table\" align=\"center\">";

//  Find SRP
if ($cost && $dept_marg){
    $dept_marg *= .01;
    $srp = $cost / (1 - $dept_marg);
    $rounder = new \COREPOS\Fannie\API\item\PriceRounder();
    $round_srp = $rounder->round($srp);
    echo "<tr><td>Raw SRP</td><td>" . sprintf('%.4f', $srp) . "</tr>";
    echo "<tr><td>Rounded SRP</td><td><strong class='success'>" . $round_srp . "</strong></tr>";
}

//  Find Marginal Data
if ($cost && $price) {
    $actualMargin = ($price - $cost) / $price;
    echo "<tr><td style='width:180px;'>Actual Margin</td><td>" . sprintf('%.4f', $actualMargin) . "</tr>";
} elseif ($round_srp) {
    echo "<tr><td style='width:180px;'>Marg @ round srp </td><td><strong>" . sprintf('%.4f', ($round_srp - $cost) / $round_srp) . "</strong></tr>";
}

//  Find Cost
if ($price && $dept_marg) {
    $dept_marg *= .01;
    $cost = - ( $price * ($dept_marg - 1)  );
    echo "<tr><td>Approximate Cost</td><td>" . sprintf('%.2f', $cost) . "</tr>";
}
echo "</table>";
echo "</fieldset>";
echo "</div>";


echo "</div>";


