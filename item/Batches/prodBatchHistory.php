<html>
<head>
  <title>Product Batch History</title>
  <link rel="stylesheet" href="../../common/bootstrap/bootstrap.min.css">
  <script src="../../common/bootstrap/jquery.min.js"></script>
  <script src="../../common/bootstrap/bootstrap.min.js"></script>
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
table, tr, th, td {
    border: 2px dotted #4d4d4d;
    border-collapse: collapse;
    padding: 3px;
}
td {
    padding: 3px 3px 0px 25px;
}
th {
    text-align:center;
}
  </style>
</head>
<body>

<form method="get" class="form-inline">
    <input type="hidden" name="id" value="1">
    <br>&nbsp;&nbsp;<input type="text" name="upc" class="invisInput" placeholder="enter a upc" autofocus>
</form>


<?php
include(dirname(__FILE__) . '/var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}
//echo "--beginning of page drawn--<br><br>";
//$item = array ( array() );

if (isset($_GET['upc'])) {
    $_GET['upc'] = preg_replace("/[^0-9,.]/", "", $_GET['upc']);
    $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
}

if (isset($upc)) {
    echo '<div align="center">';
    echo '<div align="center">';
    echo "Table lists the batches " . $upc . " exists in from newest to oldest.<br>";
    echo '</div>';
    
    include('../../config.php');
    $dbc = mysql_connect($SCANHOST, $SCANUSER, $SCANPASS);
    mysql_select_db($SCANDB, $dbc);

    $rounder = new \COREPOS\Fannie\API\item\PriceRounder();

    $query = "
        SELECT 
            bl.upc,
            bl.salePrice,
            bl.batchID,
            b.startDate,
            b.endDate,
            b.batchName
        FROM batchList AS bl
            LEFT JOIN batches AS b ON bl.batchID=b.batchID
        WHERE upc = $upc
        ORDER BY bl.batchID DESC
    ";

    //$query .= $upc;
            
            
    $result = mysql_query($query, $dbc);
    echo '<div align="center"><table>';
    echo '
        <thead>
            <th>UPC</th>
            <th>salePrice</th>
            <th>batchID</th>
            <th>startDate</th>
            <th>endDate</th>
            <th>batchName</th>
        </thead>
    ';
    while ($row = mysql_fetch_row($result))  {
        $numColsInRow = count($row);
        echo '<tr>';
        for ($i=0; $i<$numColsInRow; $i++) {
            echo '<td>' . $row[$i] . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></div>';
    if (mysql_errno() > 0) {
        echo mysql_errno() . ": " . mysql_error(). "<br>";
    }    
}


