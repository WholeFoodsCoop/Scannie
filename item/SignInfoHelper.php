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

<meta name="viewport" content="width=device-width, initial-scale=1">
<html>
<body>
<head>
  <link rel="stylesheet" href="../common/bootstrap/bootstrap.min.css">
  <script src="../common/bootstrap/jquery.min.js"></script>
  <script src="../common/bootstrap/bootstrap.min.js"></script>
<style>
    .back {
        position: fixed;
        right: 0px;
        top: 0px;
    }
    a.blue{
        color: blue;
    }
    a.red{
        color: red;    
    }
    
    td.red {
        color: red;
    }
    td.blue {
        color: lightblue;
    }
    .lightblue {
        background-color: white;
    }
    td.warning {
        background-color: #fa7575;
    }
    p.blue {
		color: blue;
    }
    p.black {
		color: black;
	}
</style>
<title>Sale Item Info</title>
</head>
<body><br><br>
<a class="btn btn-default" onClick="document.location.href='http://192.168.1.2/scancoord/item/SalesChange/SalesChangeIndex.php'"><span class="text-primary">Sales Change Tools</span></a>
<h4>Sign Info Report</h4><br>
Enter date as yyyy-mm-dd

<form method="post" id='form1'>
    <fieldset class="form-inline">
            <input type="text" class ="form-control" name="startdate" value="
            <?php 
					$default_date = date("Y-m-d");
                    if ($_POST['startdate']) { 
                        echo sprintf('%s', ltrim($_POST['startdate']));
                    } else {
						print trim($default_date, " ");
					}
            ?>
            ">
            <select form='form1' class ="form-control" name='dept' required='true'>
                <option value="1">All Departments</option>
                <option value="2">Bulk</option>
                <option value="3">Cool</option>
                <option value="4">Grocery</option>
                <option value="5">Wellness</option>
            </select>
            <select class="form-control" name="store_id">
                <option value="hillside">Hillside</option>
                <option value="denfeld">Denfeld</option>
            </select>
            <input type="submit" class="btn btn-default" value="GO!">
    </fieldset>
</form>


<div class="row">
    <div class="col-md-2"><button class="btn btn-info"></button>
         = item is not in use.
    </div>
</div><br>

<?php
include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}

$item = array ();

$i = 0;
$n = 0;
$batchID = array();
$batchID_count = 0;
$upc = array();
$upc_count = 0;
$salePrice = array();
$owner = array();

$dbc = mysql_connect($SCANHOST, $SCANUSER, $SCANPASS);
mysql_select_db($SCANDB, $dbc);

$query = "select batchID, owner from batches where startDate='{$_POST['startdate']} 00:00:00'";
//$query = "select batchID, owner from batches WHERE {$_POST['startdate']} BETWEEN startDate AND endDate";
if($_POST['dept'] == 2) {
    $query .= " and (owner='Bulk' or owner='BULK')";
} else if($_POST['dept'] == 3) {
    $query .= " and (owner='Cool' or owner='COOL')";
} else if($_POST['dept'] == 4) {
    $query .= " and (owner='Grocery' or owner='GROCERY')";
} else if($_POST['dept'] == 5) {
    $query .= " and (owner='HBC')";
} 
$query .= ";";
$result = mysql_query($query, $dbc);
while ($row = mysql_fetch_assoc($result)) {
    $batchID[$i] = $row['batchID'];
    $batchID_count++;
    $owner[$i] = $row['owner'];
    $i++;
}

//procure upcs from 'batchList' --this is going to pull every upc of every item that is going on sale
for ($i = 0; $i < $batchID_count; $i++){
    $query = "select upc, salePrice from batchList where batchID='$batchID[$i]';";   
    $result = mysql_query($query, $dbc);
    while ($row = mysql_fetch_assoc($result)) {
        $upc[$upc_count] = $row['upc'];
        $salePrice[$upc_count] = $row['salePrice'];
        $upc_count++;
        
        $item[$row['upc']] = 0;
    }
}
echo $upc_count . " items found for this sales period for ";
if ($_POST['dept'] == 1){
    echo "<b>All Departments";
}
if ($_POST['dept'] == 2){
    echo "<b>Bulk Department";
}
if ($_POST['dept'] == 3){
    echo "<b>Cool Department";
}
if ($_POST['dept'] == 4){
    echo "<b>All Grocerery Department";
}
if ($_POST['dept'] == 5){
    echo "<b>Wellness Department";
}
echo " on {$_POST['startdate']} ";
echo " for {$_POST['store_id']} <br>";
echo "<a href=\"http://key/scancoord/BatchBreakdowns.php\" tarPOST='_blank'>Check For Breakdown Items in Batches</a><br>";

//procure description of items based on 'upc's, and return their descriptions, organized by department and brand 
for ($i = 0; $i < $upc_count; $i++) {
    $query = "select v.upc as vupc, 
                p.brand AS prodbrand,
                v.brand as vbrand, 
                v.description as vdesc, 
                v.size as vsize, 
                pro.upc as pupc, 
                pro.brand as pbrand, 
                pro.description as pdesc, 
                p.size as prodsize, 
                p.normal_price as np,
                b.batchName,
				b.batchID,
                p.inUse
            from vendorItems  as v
            left join productUser as pro ON pro.upc=v.upc
            left join products as p ON p.upc=v.upc
            left join batchList as bl ON bl.upc=p.upc
            left join batches as b ON b.batchID=bl.batchID
            where v.upc='$upc[$i]' 
                and b.startDate='{$_POST['startdate']}' 
            ";
    
        if ($_POST['store_id'] == 'hillside') {
            $query .= " AND p.store_id=1 ";
        } elseif ($_POST['store_id'] == 'denfeld') {
            $query .= " AND p.store_id=2 ";
        }

    $query .= "group by v.upc 
            
            order by v.brand
            ;"; 
    $result = mysql_query($query, $dbc);
    while ($row = mysql_fetch_assoc($result)){
        $item[$i][0] = strtoupper($row['prodbrand']); //was vbrand, changed because vendor brands was giving me some trouble.
        $item[$i][1] = $row['pbrand'];
        $item[$i][2] = strtoupper($row['vdesc']);
        $item[$i][3] = nl2br($row['pdesc']); //change new line/carriage returns into <br>
        $item[$i][4] = $row['prodsize'];
        $item[$i][5] = $row['vsize'];
        $item[$i][6] = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc=" 
                        . $row['vupc'] 
                        . "&ntype=UPC&searchBtn=' target='_blank'>{$row['vupc']}</a>";
        $item[$i][7] = $salePrice[$i];
        $item[$i][8] = $row['np'];
        //$item[$i][9] = strpos($row['vdesc'], $needle);
        $item[$i][10] = "<a href='http://key/git/fannie/batches/newbatch/EditBatchPage.php?id="
			. $row['batchID'] . "' target='_blank'>" . $row['batchName'] . "</a>"; 
        $item[$i][11] = $row['inUse'];
    }
}



print '<div class="container-fluid">';

foreach ($item as $k => $v) {
    if ($v[0] == NULL || $v[0] == ''
        && $v[1] == NULL || $v[1] == ''
        && $v[2] == NULL || $v[2] == ''
        && $v[3] == NULL || $v[3] == ''
        && $v[4] == NULL || $v[4] == ''
        && $v[5] == NULL || $v[5] == ''
        && $v[6] == NULL || $v[6] == ''
        && $v[7] == NULL || $v[7] == ''    
    ) unset($item[$k]);
}
sort($item);

print "<table class='table table-striped'>";
print "
<th>Brand</th>
<th>sign brand</th>
<th>Desc</th>
<th>sign desc</th>
<th>batch</th>
<th>psize</th>
<th>vsize</th>
<th>upc</th>
<th>np</th>
<th>sale$</th>";
for ($i = 0; $i < count($item); $i++)  {
    
    if ($item[$i][11] == 0) {
        print "<tr class='info'>";
    } else {
        print "<tr>";
    }
           
    print "<td>" . $item[$i][0] . "</td>";
    
    if ($item[$i][1] == NULL) {
        print "<td class=\"danger\">" . $item[$i][1] . "</td>";
    } else {
        print "<td>" . $item[$i][1] . "</td>";
    }
    
    if ($item[$i][2] == NULL) {
        print "<td class=\"danger\">" . $item[$i][2] . "</td>";
    } else {
        print "<td>" . $item[$i][2] . "</td>";
    }
    
    if ($item[$i][3] == NULL) {
        print "<td class=\"danger\">" . $item[$i][3] . "</td>";
    } else {
        print "<td>" . $item[$i][3] . "</td>";
    }
    
    print "<td>" . $item[$i][10] . "</td>";
   
    if ($item[$i][4] == NULL
        || ( !substr_count($item[$i][4], 'OZ')
            && !substr_count($item[$i][4], 'oz')
            && !substr_count($item[$i][4], 'fz')
            && !substr_count($item[$i][4], 'FZ')
            && !substr_count($item[$i][4], 'fl oz')
            && !substr_count($item[$i][4], 'FL OZ')
            && !substr_count($item[$i][4], 'each')
            && !substr_count($item[$i][4], 'ct')
            && !substr_count($item[$i][4], 'CT')
            && !substr_count($item[$i][4], '0')
            && !substr_count($item[$i][4], 'LTR')
            && !substr_count($item[$i][4], 'PINT')
            && !substr_count($item[$i][4], 'GAL')
            && !substr_count($item[$i][4], '#')
            && !substr_count($item[$i][4], 'single')
           )
    ) {
        print "<td class=\"danger\">" . $item[$i][4] . "</td>";
    } else {
        print "<td>" . $item[$i][4] . "</td>";
    }
    
    if ($item[$i][5] == NULL) {
        print "<td class=\"danger\">" . $item[$i][5] . "</td>";
    } else {
        print "<td>" . $item[$i][5] . "</td>";
    }
    
    if ($item[$i][6] == NULL) {
        print "<td class=\"danger\">" . $item[$i][6] . "</td>";
    } else {
        print "<td>" . $item[$i][6] . "</td>";
    }
    
    print "<td>" . $item[$i][8] . "</td>";
    
    if ($item[$i][8] <= $item[$i][7]) {  
        print "<td class='warning'><b>" . $item[$i][7] . "</td>";
    } else {
        print "<td>" . $item[$i][7] . "</td>";
    }

}
print "</table>";
print '</div>';
