<?php 
include('/var/www/html/git/fannie/config.php');
//include('/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(dirname(__FILE__))) . '/common/sqlconnect/SQLManager.php');
}
session_start();
?>
<html>
<head>
  <link rel="stylesheet" href="../../common/bootstrap/bootstrap.min.css">
  <script src="../../common/bootstrap/jquery.min.js"></script>
  <script src="../../common/bootstrap/bootstrap.min.js"></script>
</head>
<title>
  Sales Change Queues
</title>
<style>

</style>
<script type="text/javascript" src="/git/fannie/src/javascript/jquery.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/cordova-2.2.0.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/ScannerLib-Linea-2.0.0.js"></script>
<script type="text/javascript" src="scanner.js"></script>
<script type="text/javascript">

function sendToQueue(button, upc, queue_id, session)
{
    $.ajax({

        // Info will be sent to this URL
        url: 'salesChangeAjax2.php',

        // The actual data to send
        data: 'upc='+upc+'&queue='+queue_id+'&session='+session,

        // callback to process the response
        success: function(response)
        {
            // display the response in element w/ id=ajax-resp
            $('#ajax-resp').html('AJAX call returned: ' + response);

            // search DOM upword for a <tr> tag and hide that element
            // as well as its children
            $(button).closest('tr').hide();
        }
    });
}
function changeStoreID(button, store_id)
{
    $.ajax({
        
        url: 'salesChangeAjax3.php',
        data: 'store_id='+store_id,
        success: function(response)
        {
            $('#ajax-resp').html(response);
            window.location.reload();
        }
    });
}
</script>
<body>
<?php include 'SalesChangeLinks.html'; ?>
<div align="right">
    <br>
    <button class="btn btn-default" type="button" onclick="changeStoreID(this, 1); return false; window.location.reload();">Hillside</button>
    <button class="btn btn-default" type="button" onclick="changeStoreID(this, 2); return false; window.location.reload();">Denfeld</button>
</div>
<?php
if(isset($_POST['session'])) $_SESSION['session'] = $_POST['session'];
    
include('../../config.php');
$dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANALTDB, $SCANUSER, $SCANPASS);

if($_SESSION['store_id'])
{
    draw_table($dbc);
} else {
    echo '<h1 class="text text-danger" align="right">Select a store</h1>';
}

function draw_table($dbc)
{
    $query = "SELECT session 
        FROM SaleChangeQueues
        GROUP BY session
        ;";
    $result = $dbc->query($query);
    while ($row = $dbc->fetchRow($result)) {
        $session[] = $row['session'];
    }
    print ('
        <div class="text-center container">
        <form method="post" class="form-inline">
            <select class="form-control" name="session">');
                
    foreach ($session as $key => $sessID) {
        print '<option value="' . $sessID . '">' . $sessID . '</option>';
    }

    print ('
            </select>
            <input type="submit" class ="form-control" value="Change Session">
        </form>
        </div>');
        
    $id = $_SESSION['store_id'];
    $sess = $_SESSION['session'];
    $args = array($id,$sess);
    $prep = $dbc->prepare("
        SELECT q.queue, u.brand, u.description,
                u.upc, p.size, p.normal_price, ba.batchName,
                p.special_price as price, ba.batchID
                FROM SaleChangeQueues as q
                    LEFT JOIN is4c_op.products as p on p.upc=q.upc
                    LEFT JOIN is4c_op.productUser as u on u.upc=p.upc
                    LEFT JOIN is4c_op.batchList as bl on bl.upc=p.upc
                    LEFT JOIN is4c_op.batches as ba on ba.batchID=bl.batchID
                WHERE q.queue=1
                    AND q.store_id= ?
                    AND q.session= ?
                GROUP BY upc
                ORDER BY u.brand ASC
    ");
    $res = $dbc->execute($prep,$args);
    while ($row = $dbc->fetch_row($res)) {
        $upc = $row['upc'];
        $upcs[$upc] = $row['upc'];
        $brand[$upc] = $row['brand'];
        $desc[$upc] = $row['description'];
        $queue[$upc] = $row['queue'];
        $size[$upc] = $row['size'];
        $price[$upc] = $row['price'];
        $upcLink[$upc] = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc=" 
                    . $row['upc'] 
                    . "&ntype=UPC&searchBtn=' class='blue' target='_blank'>{$row['upc']}
                    </a>";
    }
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }
    
    $batch = array();
    foreach ($upcs as $upc) {
        $prep = $dbc->prepare("
            SELECT 
                ba.batchName,
                ba.batchID
            FROM is4c_op.batches AS ba 
            LEFT JOIN is4c_op.batchList AS bl ON ba.batchID=bl.batchID
            WHERE bl.upc = ?
                AND CURDATE() BETWEEN ba.startDate AND ba.endDate;
        ");
        $res = $dbc->execute($prep,$upc);
        while ($row = $dbc->fetch_row($res)) {
            $batch[$upc] = "<a href='http://key/git/fannie/batches/newbatch/EditBatchPage.php?id="
			. $row['batchID'] . "' target='_blank'>" . $row['batchName'] . "</a>";
        }
    }
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }

    echo "<h1 align='center'>Good Tags</h1>";
    echo "<div align='center'>";
    if ($_SESSION['store_id'] == 1) {
        echo "<strong style='color:purple'>Hillside</strong><br>";
    } else {
        echo "<strong style='color:purple'>Denfeld</strong><br>";
    }
    echo "<span style='color:purple'>" . $_SESSION['session'] . "</span>";
    echo "</div>";
    echo "<p align='center'>" . count($upcs) . " tags in this queue</p>";
    echo "<table class=\"table table-striped\">";
    echo "<th>Brand</th>
          <th>Name</th>
          <th>Size</th>
          <th>Price</th>
          <th>UPC</th>
          <th>Batch</th>
          <th></th><th></th><th></th>";
    foreach ($upcs as $upc => $v) {
        if ($upc[$i] >= 0) {
            echo "<tr><td>" . $brand[$upc] . "</td>"; 
            echo "<td>" . $desc[$upc] . "</td>"; 
            echo "<td>" . $size[$upc] . "</td>"; 
            echo "<td>" . $price[$upc] . "</td>"; 
            echo "<td>" . $upcLink[$upc] . "</td>"; 
            echo "<td>" . $batch[$upc] . "</td>";    
            echo "<td><button class=\"btn btn-default\" type=\"button\" onclick=\"sendToQueue(this, '{$upc[$i]}', 2, '{$_SESSION['session']}'); return false;\">Error</button></td>";    
            echo "<td><button class=\"btn btn-default\" type=\"button\" onclick=\"sendToQueue(this, '{$upc[$i]}', 8, '{$_SESSION['session']}'); return false;\">Missing</button></td>";  
            echo "<td><button class=\"btn btn-default\" type=\"button\" onclick=\"sendToQueue(this, '{$upc[$i]}', 0, '{$_SESSION['session']}'); return false;\">Unchecked</button></tr>";  
        }
    }
    echo "</table>";
    } 
