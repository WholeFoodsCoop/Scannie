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

include('/var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include('/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}

?>

<html>
<head>
  <link rel="stylesheet" href="bootstrap/bootstrap.min.css">
  <script src="bootstrap/jquery.min.js"></script>
  <script src="bootstrap/bootstrap.min.js"></script>
</head>
<title>Percent Sale Calculator</title>
<style>
body {
    background-color: lightblue;
}
table td,th {
    border-top: none !important;
}
</style>
<script language="javascript" type="text/javascript">
<!--
function popitup(url) {
	newwindow=window.open(url,'name','height=600,width=300');
	if (window.focus) {newwindow.focus()}
	return false;
}
// -->
</script>
<body>
<a class="btn btn-sm btn-default" href="popupex.html" onclick="return popitup('http://key/scancoord/item/percentOffCalc.php')"
	>popout</a>

<?php

$actualMargin = 0;
$roundedSRP = 0;
$rawSRP = 0;
$rounder = new \COREPOS\Fannie\API\item\PriceRounder();
//  $rounded_price = $rounder->round ($price);

$_SESSION['percent'] = $_GET['percent'];
$cost = $_GET['cost'];
$percent = $_GET['percent'];
$ret = "";


$ret .= '
    <form method="get" id="id1" class="form-horizontal">
        <div class="form-group">
            <label class="col-xs-4">Price</label>
            <div class="col-xs-4">
                <input type="text" class="form-control" name="cost" autofocus>
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-4">Percent</label>
            <div class="col-xs-4">
                <input type="text" class="form-control" name="percent" value="
                    '; 
                        if ($_GET['percent']) { 
                            $ret .= sprintf('%.3f', $_GET['percent']);
                        }
                        
$ret .= '"
            </div>
        </div>
        <input type="submit" hidden>
    </form>
    
';

if ($percent && $cost) {
    $percent *= .01;
    $price = 0;
    $roundPrice = 0;
    $actualPercentoff = 0;
    $price = $cost - ($cost * $percent);
    $roundPrice = $rounder->round($price);
    $actualPercentOff = $roundPrice / $cost;
    $actualPercentOff += -1;
    $actualPercentOff *= -100;
    
    //oneup 
/*
    $oneup = $roundPrice + .30;
	$oneup = $rounder->round($oneup);
    $oneupPercentOff = (($oneup / $cost) + (-1)) * (-100);
*/

	$oneup = $roundPrice;
	while($rounder->round($oneup) == $roundPrice) {
		$oneup += 0.01;
	}
	$oneup = $rounder->round($oneup);
    $oneupPercentOff = (($oneup / $cost) + (-1)) * (-100);

/*    //onedown 
    $down = $roundPrice - .30;
	$down = $rounder->round($down);
    $downPercentOff = (($down / $cost) + (-1)) * (-100);
  */

	$down = $roundPrice;
	while($rounder->round($down) == $roundPrice) {
		$down -= 0.01;
	}
	$down = $rounder->round($down);
    $downPercentOff = (($down / $cost) + (-1)) * (-100);
  
    $ret .= '
        <div class="container">
            <table class="table">
            <div class="row">
                <tr><td>Price</td><td>' . sprintf("%0.2f",$cost) . '</td>
                <tr><td>Price (at ' . ($percent*100) . '%)</td><td>' . sprintf("%0.2f",$price) . '</td>
                <tr><td> - </td></tr>
                
                <tr><td>Rounded (up) </td><td>' . $roundPrice . '</td>
                <tr><td>End % Off </td><td>' . sprintf("%0.2f",$actualPercentOff) . '%</td>
                <tr><td> - </td></tr>
                
                <tr><td>Up </td><td>' . sprintf("%0.2f",$oneup) . '</td>
                <tr><td>up %</td><td>' . sprintf("%0.2f",$oneupPercentOff) . '%</td>
                <tr><td> - </td></tr>
                
                <tr><td>Down </td><td>' . sprintf("%0.2f",$down) . '</td>
                <tr><td>down %</td><td>' . sprintf("%0.2f",$downPercentOff) . '%</td>
            </table>
        </div>
    ';
}

print $ret;







