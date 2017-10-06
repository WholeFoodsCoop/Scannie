<html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<head>
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</head>
<title>
    Sales Change ListGen
</title>
<style>
</style>
<body>
<fieldset>
    <div class="container" ><h3>
<br><br>
<a class="btn btn-default" onClick="document.location.href='http://192.168.1.2/scancoord/item/SalesChange/SalesChangeIndex.php'"><span class="text-primary">Sales Change Tools</span></a>
<h1>List Generator</h1>


    <form method="get" id='form1' class="form-inline">
        <label>Name Your Session</label>
        <input type="text" class="form-control" name="session_name" required><br><br>
        <label>Choose A Store</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <select class="form-control" name="store_id" required>
            <option value="0"> </option>
            <option value="1">Hillside - store #1</option>
            <option value="2">Denfeld  - store #2</option>
        </select><br><br>
        <input type="submit" class="btn btn-default" value="populate queues">
    </form> 
</div>
</fieldset>
<?php
$store_id = 0;
include('../../config.php');
$dbc = mysql_connect($SCANHOST, $SCANUSER, $SCANPASS);
mysql_select_db($SCANALTDB, $dbc);

$upc = array();

if($_GET['store_id'] > 0 && $_GET['session_name'] != NULL) {
    
    $_GET['session_name'] = strtoupper($_GET['session_name']);
    
    //  Procure batches FROM stardate
    $query = "SELECT batchID, 
            owner
        FROM is4c_op.batches 
        WHERE CURDATE() BETWEEN startDate AND endDate 
    ;";
    $result = mysql_query($query, $dbc);
    while ($row = mysql_fetch_assoc($result)) {
        $batchID[] = $row['batchID'];
        $owner[] = $row['owner'];
    }
    if (mysql_errno() > 0) {
        echo mysql_errno() . ": " . mysql_error(). "<br>";
    }
    
    for ($i = 0; $i < count($batchID); $i++) {
       $query = "SELECT bl.upc
            FROM is4c_op.batches AS b                
                LEFT JOIN is4c_op.batchList AS bl ON bl.batchID=b.batchID
                LEFT JOIN is4c_op.products AS p ON bl.upc=p.upc
                INNER JOIN is4c_op.StoreBatchMap AS sbm ON (b.batchID=sbm.batchID AND sbm.storeID={$_GET['store_id']})
            WHERE b.batchID='{$batchID[$i]}'
                AND p.store_id={$_GET['store_id']}
                AND p.brand != 'WFC-U'
                AND p.upc != 0000000004792
                AND p.inUse = 1
                AND b.discountType <> 0 
            ;";
        $result = mysql_query($query, $dbc);
        while ($row = mysql_fetch_assoc($result)) {
            $upc[] = $row['upc'];
            $store_id[] = $row['store_id'];
            if(!isset($sessionName)) $sessionName = $_GET['session_name'];
        } 
    }

    $date = date('Y-m-d');

    // Insert Items into SaleChangeQueues
    for ($i = 0; $i < count($upc); $i++) {
        $query = "INSERT INTO SaleChangeQueues (session, queue, upc, store_id, date, type) VALUES (
            '{$_GET['session_name']}',
            '0',
            '{$upc[$i]}',
            '{$_GET['store_id']}',
            '{$date}',
            1
            )
            ;";
        $result = mysql_query($query, $dbc);
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        }  
    }
    echo '<div class="container">' . count($upc) . " items added to Sales Change Queues.</div>";
}

