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
<?php include 'SalesChangeLinksNew.html'; ?>
<div align="right">
    <br>
    <button class="btn btn-default" type="button" onclick="changeStoreID(this, 1); return false; window.location.reload();">Hillside</button>
    <button class="btn btn-default" type="button" onclick="changeStoreID(this, 2); return false; window.location.reload();">Denfeld</button>
</div>
<?php
foreach ($_GET as $key => $value) {
    //echo $key . ' ' . $value . '<br>';
    if ($key == 'queue') $thisQueue = $value;
}



if(isset($_POST['session'])) $_SESSION['session'] = $_POST['session'];
    
include('../../config.php');
$dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANALTDB, $SCANUSER, $SCANPASS);

$curQueue = $_GET['queue'];



if($_SESSION['store_id'])
{
    draw_table($dbc);
} else {
    echo '<h1 class="text text-danger" align="right">Select a store</h1>';
}

function get_queue_name($dbc)
{
    $queueNames = array();
    $prep = $dbc->prepare("select * from queues");
    $result = $dbc->execute($prep);
    while ($row = $dbc->fetch_row($result)) {
        $queueNames[$row['queue']] = $row['name'];
    }
    
    return $queueNames;
}

function draw_table($dbc)
{
   
    $curQueue = $_GET['queue'];
    $queueNames = array();
    $queueNames = get_queue_name($dbc);
    
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
    $args = array($curQueue,$id,$sess);
    $prep = $dbc->prepare("
        SELECT q.queue, 
                CASE WHEN u.brand IS NULL OR u.brand='' THEN p.brand ELSE u.brand END AS brand, 
                CASE WHEN u.description IS NULL OR u.description='' THEN p.description ELSE u.description END as description,
                u.upc, p.size, p.normal_price, ba.batchName,
                p.special_price as price, ba.batchID, q.notes,
                p.last_sold,
                f.floorSectionID,
                fs.name
                FROM SaleChangeQueues as q
                    LEFT JOIN is4c_op.products as p on p.upc=q.upc AND p.store_id=q.store_id
                    LEFT JOIN is4c_op.productUser as u on u.upc=p.upc
                    LEFT JOIN is4c_op.batchList as bl on bl.upc=p.upc
                    LEFT JOIN is4c_op.batches as ba on ba.batchID=bl.batchID
                    LEFT JOIN is4c_op.FloorSectionProductMap as f on f.upc=p.upc
                    LEFT JOIN is4c_op.FloorSections as fs on fs.floorSectionID=f.floorSectionID
                WHERE q.queue= ?
                    AND q.store_id= ?
                    AND q.session= ?
                GROUP BY upc
                ORDER BY f.floorSectionID, brand ASC
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
        $notes[$upc] = $row['notes'];
        $last_sold[$upc] = $row['last_sold'];
        $upcLink[$upc] = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc=" 
                    . $row['upc'] 
                    . "&ntype=UPC&searchBtn=' class='blue' target='_blank'>{$row['upc']}
                    </a>";
        $floorSection[$upc] = $row['floorSectionID'];
        $floorSectionName[$upc] = $row['name'];
    }
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }
    
    $batch = array();
    $batchTypes = array();
    foreach ($upcs as $upc) {
        $prep = $dbc->prepare("
            SELECT 
                ba.batchName,
                ba.batchID,
                ba.batchType
            FROM is4c_op.batches AS ba 
            LEFT JOIN is4c_op.batchList AS bl ON ba.batchID=bl.batchID
            WHERE bl.upc = ?
                AND CURDATE() BETWEEN ba.startDate AND ba.endDate;
        ");
        $res = $dbc->execute($prep,$upc);
        while ($row = $dbc->fetch_row($res)) {
            $batchName[$upc] = $row['batchName'];
            $batchTypes[$upc] = $row['batchType'];
            $batch[$upc] = "<a href='http://key/git/fannie/batches/newbatch/EditBatchPage.php?id="
			. $row['batchID'] . "' target='_blank'>" . $row['batchName'] . "</a>";
        }
    }
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }
    
    echo "<h1 align='center'>".ucwords($queueNames[$curQueue])."</h1>";
    if (!isset($curQueue)) echo "<h1 align='center'>Home Page (No Queue Selected)</h1>";
    
    echo "<div align='center'>";
    if ($_SESSION['store_id'] == 1) {
        echo "<strong style='color:purple'>Hillside</strong><br>";
    } else {
        echo "<strong style='color:purple'>Denfeld</strong><br>";
    }
    echo "<span style='color:purple'>" . $_SESSION['session'] . "</span>";
    echo "</div>";
    echo "<p align='center'>" . count($upcs) . " tags in this queue</p>";
    if ($curQueue == 0) {
        echo '
            <form method="post">
              <input type="hidden" name="queue" value="0">
              <input type="hidden" name="rmDisco" value="1">
              <!-- <button type="submit" class="btn btn-default btn-xs" >Remove Disco/Overstock batch items</button> -->
            </form>
        ';
    }
/*
    if ($_POST['rmDisco']) {
        $rmProduct = array();
        $args = array($upc,$sess);
        foreach ($batchName as $upc => $name) {
            if (strpos($name,"Disco")
                || strpos($name,'Disco') 
                || strpos($name,'DISCO') 
                || strpos($name,'overstock') 
                || strpos($name,'OVERSTOCK') 
                || strpos($name,'Overstock') 
                || strpos($name,'LINE') 
                || strpos($name,'Line') 
                || strpos($name,'DC') 
            ) {
                $prep = $dbc->prepare("UPDATE SaleChangeQueues SET queue = 1 WHERE upc = ? AND session = ?");
                $dbc->execute($prep,$args);
                if ($dbc->error()) {
                    echo $dbc->error(). "<br>";
                }
            }
        }
    }
*/
    if (count($upcs) > 0) {
        echo '<a href="" onclick="$(\'#cparea\').show(); return false;">Copy/paste</a>';
        echo '<textarea id="cparea" class="collapse">';
        echo implode("\n", $upcs);
        echo '</textarea>';
        echo ' | <a href="http://192.168.1.2/scancoord/item/SalesChange/SalesChangeQueues.php?rmOtherSales=1&queue=0"
            onclick="return confirm(\'Are you sure?\')">Remove Non Co-op Deals Sale Items from List</a><br />';
    }
    
    if ($_GET['rmOtherSales'] == 1) {
        removeAddBatches($dbc,$batchTypes);
    }

    $curY = date('Y');
    $curM = date('m');
    $curD = date('d');
    echo "<table class=\"table table-striped\">";
    echo "<th>Brand</th>
          <th>Name</th>
          <th>Size</th>
          <th>Price</th>
          <th>UPC</th>
          <th>Batch</th>
          <th></th><th></th><th></th>";
    foreach ($upcs as $upc => $v) {
        if ($upc >= 0) {
            echo "<tr><td>" . $brand[$upc] . "</td>"; 
            echo "<td>" . $desc[$upc] . "</td>"; 
            echo "<td>" . $size[$upc] . "</td>"; 
            echo "<td>" . $price[$upc] . "</td>"; 
            echo "<td>" . $upcLink[$upc] . "</td>"; 
            echo "<td>" . $batch[$upc] . "</td>";
            if ($curQueue == 2) echo "<td><strong>" . $notes[$upc] . "</strong></td>";
           // if ($curQueue == 0) echo "<td>".$last_sold[$upc]."</td>";
            if ($curQueue == 0) {
                $year = substr($last_sold[$upc], 0, 4);
                $month = substr($last_sold[$upc], 5, 2);
                $day = substr($last_sold[$upc], 8, 2);
				$wIcon = '<img src="../../common/src/img/warningIcon.png">';
                if (($year < $curY) or ($month < ($curM - 1)) or ($month < $curM && $day < $curD)) {
                    echo "<td style='color:red'>" . $wIcon . ' ' . substr($last_sold[$upc],0,10) . "</td>";
                } else {
                    echo "<td>" . substr($last_sold[$upc],0,10) . "</td>";
                }
            }        
            
            if ($curQueue == 7) {
                echo "<td><a class=\"btn btn-default\" href=\"http://192.168.1.2/git/fannie/item/handheld/ItemStatusPage.php?id={$upc}\" target=\"_blank\">status check</a></tr>";  
            } else {
                echo "<td><button class=\"btn btn-default\" type=\"button\" onclick=\"sendToQueue(this, '{$upc}', 1, '{$_SESSION['session']}'); return false;\">Good</button></td>";    
                echo "<td><button class=\"btn btn-default\" type=\"button\" onclick=\"sendToQueue(this, '{$upc}', 8, '{$_SESSION['session']}'); return false;\">Missing</button></td>";    
                echo "<td><button class=\"btn btn-default\" type=\"button\" onclick=\"sendToQueue(this, '{$upc}', 98, '{$_SESSION['session']}'); return false;\">DNC</button></td>";    
            }  
            if ($curQueue == 99) {
                echo "<td><button class=\"btn btn-default\" type=\"button\" onclick=\"sendToQueue(this, '{$upc}', 0, '{$_SESSION['session']}'); return false;\">Unchecked</button></td>";    
            }
        }
    }
    echo "</table>";

} 

function removeAddBatches($dbc,$batchTypes)
{
    
    $session = $_SESSION['session'];
   
    $upcs = array();
    foreach ($batchTypes as $upc => $batchType) {
        if ($batchType != 1) {
            $upcs[] = $upc;
        }
    }
    
    list($inClause,$args) = $dbc->safeInClause($upcs);
    $updateQ = 'UPDATE SaleChangeQueues SET queue = 1 WHERE upc IN ('.$inClause.')';
    $prep = $dbc->prepare($updateQ);
    $dbc->execute($prep,$args);
    if ($dbc->error()) {
        echo '<div class="alert alert-danger">'.$dbc->error().'</div>';
    } else {
        echo '<div class="alert alert-success">Items Removed from List. Refresh the page by clicking \'Unchecked\' to reload the list with products removed.</div>';
    }
    
    return false;
    
}





