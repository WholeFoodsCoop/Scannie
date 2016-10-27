<html>
<head>
  <title>Generic Query</title>
  <link rel="stylesheet" href="bootstrap/bootstrap.min.css">
  <script src="bootstrap/jquery.min.js"></script>
  <script src="bootstrap/bootstrap.min.js"></script>
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
      background-color: #272822;
      border: none;
      width: 500px;
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
  </style>
</head>
<body>
<div class="container">
<form method="get" class="form-inline">
    Enter Batch ID: <input type="text" class="invisInput" style="width: 100px" name="batchID" required autofocus>
</form>
</div>

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


if ($batchID = $_GET['batchID']) {
    $rounder = new \COREPOS\Fannie\API\item\PriceRounder();

    $query = "
        select b.*, p.normal_price, 100*(1 - b.salePrice / p.normal_price) as percent from batchList as b left join products as p on p.upc=b.upc where b.batchID = {$batchID} group by b.upc;
    ";
    $result = mysql_query($query, $dbc);
    while ($row = mysql_fetch_assoc($result))  {
        $roundPrice = $rounder->round($row['salePrice']);
        $up = $roundPrice;
        while($rounder->round($up) == $roundPrice) {
            $up += 0.01;
        }
        $up = $rounder->round($up);
        $upPer = substr(100*(1 - ($up / $row['normal_price'])), 0, 5);
        $down = $roundPrice;
        while($rounder->round($down) == $roundPrice) {
            $down -= 0.01;
        }
        $down = $rounder->round($down);
        $downPer = substr(100*(1 - ($down / $row['normal_price'])), 0, 5);
        
        foreach ($row as $key => $val) {
            if ($key == 'percent' or $key == 'upc') {
                echo ' <b> ' . $key . '</b> ' . $val . '&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            if ($key == 'percent') $percent = $val;
            //temp - use if prices already rounded
            $percent = 25.00;
        }
        $roundPer = substr(100*(1 - ($roundPrice / $row['normal_price'])), 0, 5);
        $upDiff = abs($percent - $upPer);
        $downDiff = abs($percent - $downPer);
        if ($roundPer >= $percent + 4) {
            //  Price Increase
            echo '<b>Round Price: </b>' . str_pad($roundPrice, 5, '0', STR_PAD_LEFT) . '&nbsp;&nbsp;&nbsp;&nbsp;<b>Round %:</b> <span class="warning">' . $roundPer .'</span>';
            if ($upPer < $downPer) {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;<b>Round uper:</b> ' . str_pad($up, 5, '0', STR_PAD_LEFT) . '&nbsp;&nbsp;&nbsp;&nbsp;<b>uper %:</b> <span class="success">' . $upPer .'</span>';
            } else {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;<b>Round uper:</b> ' . str_pad($up, 5, '0', STR_PAD_LEFT) . '&nbsp;&nbsp;&nbsp;&nbsp;<b>uper %:</b> ' . $upPer;
            }
            
        } elseif ($roundPer <= $percent - 2) {
            //  Price Decrease
            echo '<b>Round Price: </b>' . str_pad($roundPrice, 5, '0', STR_PAD_LEFT) . '&nbsp;&nbsp;&nbsp;&nbsp;<b>Round %:</b> <span class="warning">' . $roundPer .'</span>';
            if ($downPer < $up) {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;<b>Round uper:</b> ' . str_pad($up, 5, '0', STR_PAD_LEFT) . '&nbsp;&nbsp;&nbsp;&nbsp;<b>uper %:</b> <span class="success">' . $downPer .'</span>';
            } else {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;<b>Round down:</b> ' . str_pad($down, 5, '0', STR_PAD_LEFT) . '&nbsp;&nbsp;&nbsp;&nbsp;<b>down %:</b> ' . $downPer;
            }
        } else {
            echo '<b>Round Price: </b>' . str_pad($roundPrice, 5, '0', STR_PAD_LEFT) . '&nbsp;&nbsp;&nbsp;&nbsp;<b>Round %:</b> ' . $roundPer;
        }
        echo '<br>';
    }
    //if(isset($row)) echo 'soemthing';

    if (mysql_errno() > 0) {
        echo mysql_errno() . ": " . mysql_error(). "<br>";
    }
}
    

//echo "<br>--end of page drawn--";
//  why am I doing this: it would be nice to have a script that automatically choses the normal / up / down price for me - logic which could be added into the batch creation tool in advanced items. //    