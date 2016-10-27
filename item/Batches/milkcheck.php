<html>
<head>
  <title>Milk Check</title>
  <link rel="stylesheet" href="../common/bootstrap/bootstrap.min.css">
  <script src="../common/bootstrap/jquery.min.js"></script>
  <script src="../common/bootstrap/bootstrap.min.js"></script>
  <style>
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
    //color: red;
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
table, tr, th, td {
    border: 2px dotted #4d4d4d;
    border-collapse: collapse;
    padding: 3px;
}
td {
    padding: 3px 3px 0px 25px;
}
th {
    text-align: center;
}
  </style>
</head>
<body>
<div class="container">
<form method="get" class="form-inline">
    <input type="hidden" name="id" value="1">
</form>
</div>
<h4 align="center">UNFI Milk Cost / Price Scan</h4>

<?php
include(dirname(__FILE__) . '/var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}
//echo "--beginning of page drawn--<br><br>";
//$item = array ( array() );

include('../../config.php');
$dbc = mysql_connect($SCANHOST, $SCANUSER, $SCANPASS);
mysql_select_db($SCANDB, $dbc);

$rounder = new \COREPOS\Fannie\API\item\PriceRounder();

$query = "select p.upc, p.brand, p.description, p.cost, p.normal_price, p.price_rule_id from products as p where p.department = 30 or p.department = 32 group by p.upc;";
$result = mysql_query($query, $dbc);
$margin = 0.2550;
echo '<div align="center"><table>';
echo '
    <thead>
        <th>upc</th>
        <th>brand</th>
        <th>desc</th>
        <th>cost</th>
        <th>price</th>
        <th>variable</th>
        <th>rawSRP</th>
        <th>SRP</th>
        <th>change</th>
    </thead>
';
while ($row = mysql_fetch_row($result))  {
    $numColsInRow = count($row);
    echo '<tr>';
    for ($i=0; $i<$numColsInRow; $i++) {
        if ($i == 4) { 
            echo '<td><span class="info">' . $row[$i] . '</span></td>';
        } elseif($i == 5) {
            if ($row[$i] == 1) {
                echo '<td><i>x</i></td>';            
            } elseif ($row[$i] > 1) {
                echo '<td><i>x</i>edlp</td>';            
            } else {
                echo '<td></td>';
            }
        } else {
            echo '<td>' . $row[$i] . '</td>';
        }          
    }
    $srp = $row[3] / (1 - $margin);
    echo sprintf('<td>%0.2f</td>', $srp);
    $roundSRP = $rounder->round($srp);
    echo sprintf('<td>%0.2f</td>', $roundSRP);
    $change = $roundSRP - $row[4];
    if ($change > 0) {
        $symbol = '+';
        echo sprintf('<td><span class="success">%s%0.2f</span></td>', $symbol, $change);
    } else {
        $symbol = '';
        echo sprintf('<td><span class="warning">%s%0.2f</span></td>', $symbol, $change);
    }
    echo '</tr>';
}
echo '</table></div>';
if (mysql_errno() > 0) {
    echo mysql_errno() . ": " . mysql_error(). "<br>";
}    

