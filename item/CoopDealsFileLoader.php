<!--/*******************************************************************************

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
    
*********************************************************************************/-->

<html>
<head>
  <title>Coop Deals File Loader</title>
  <link rel="stylesheet" href="../common/bootstrap/bootstrap.min.css">
  <script src="../common/bootstrap/jquery.min.js"></script>
  <script src="../common/bootstrap/bootstrap.min.js"></script>
  <style>
    .ourprice {
        color: slategrey;
    }
  </style>
</head>
<br><br><br>
<body>
<div class="container">
<h4 align="center">Coop Deals Price File</h4>
<h5 align="center">June Coop Deals</h5>

<?php
include(dirname(__FILE__) . '/var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}

$ret = '';
$brands = array();
$curBrand = '';
$rounder = new \COREPOS\Fannie\API\item\PriceRounder();
if ($_GET['brands']) $curBrand = $_GET['brands'];
if ($_GET['month']) $month = $_GET['month'];


include('../config.php');
$dbc = mysql_connect($SCANHOST, $SCANUSER, $SCANPASS);
mysql_select_db('woodshed_no_replicate', $dbc);

$query = "SELECT * 
FROM CoopDealsNov
    WHERE upc != 0
";
if (isset($_GET['brands'])) $query .= ' AND brand = "' . $curBrand . '" ';
    
$result = mysql_query($query, $dbc);
$ret .= '<table class="table table-bordered table-striped"><th>upc</th><th>period</th><th>dept</th><th>sku</th>
    <th>brand</th><th>description</th><th>size</th><th>cost</th><th>srp</th>
    <th>notes</th>';    
while ($row = mysql_fetch_assoc($result)) {
    $ret .= '<tr><td>' . $row['upc'] . '</td>';
    $ret .= '<td>' . $row['flyerPeriod'] . '</td>';
    $ret .= '<td>' . $row['department'] . '</td>';
    $ret .= '<td>' . $row['sku'] . '</td>';
    $ret .= '<td>' . $row['brand'] . '</td>';
    $ret .= '<td>' . $row['description'] . '</td>';
    $ret .= '<td>' . $row['packSize'] . '</td>';
    $ret .= '<td>' . $row['saleUnitCost'] . '</td>';
    $ret .= '<td>' . $row['srp'] . '</td>';
    //$ret .= '<td class="ourprice"><b>' . $rounder->round($row['srp']) . '</b></td>';
    $ret .= '<td>' . $row['lineNotes'] . '</td></tr>';
    if (!isset($brands[$row['brand']])) $brands[$row['brand']] = $row['brand'];
}
if (mysql_errno() > 0) {
    echo mysql_errno() . ": " . mysql_error(). "<br>";
}    

echo '
    Search Options<br>
        <form method="get" class="form-inline" id="search">
            <select name="brands" class="form-control" form="search">
                <option value="all">All Brands</option>
';
foreach ($brands as $brand => $num) {
    echo '<option value="' . $brand . '">' . $brand . '</option>';
}
echo '
            </select>
            <input type="submit" value="Apply Search Criteria" class="form-control btn btn-default">
            <a class="btn btn-default" href="http://key/scancoord/item/CoopDealsFileLoader.php">Clear/Start New Search</a>
        </form>
';
print $ret;
