<?php 
include('/var/www/html/git/fannie/config.php');
include('/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
session_start();
?>
<html>
<head>
  <link rel="stylesheet" href="../../common/bootstrap/bootstrap.min.css">
  <script src="../../common/bootstrap/jquery.min.js"></script>
  <script src="../../common/bootstrap/bootstrap.min.js"></script>
</head>
<title>
  Missing Shelf Tags
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
            $(button).closest('tr').hide();
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

/*
$query = "SELECT upc
        FROM SaleChangeQueues
        WHERE queue=99
        ";
$result = mysql_query($query, $dbc);
while ($row = mysql_fetch_assoc($result)) {
    $upc[] = $row['upc'];
}
if (mysql_errno() > 0) {
    echo mysql_errno() . ": " . mysql_error(). "<br>";
}
*/

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


$query = "SELECT 
        p.description,
        p.brand,
        p.upc,
        pu.description AS puDesc,
        scq.session
    FROM is4c_op.products AS p
        LEFT JOIN is4c_op.productUser AS pu ON pu.upc=p.upc
        INNER JOIN woodshed_no_replicate.SaleChangeQueues AS scq 
            ON p.upc = LPAD(scq.upc, '13', 0)
    WHERE scq.queue = 7
        AND scq.store_id = {$_SESSION['store_id']}
        AND scq.session='{$_SESSION['session']}'
    GROUP BY p.upc;
";
$result = $dbc->query($query);
while ($row = $dbc->fetchRow($result)) {
    $desc[] = $row['description'];
    $brand[] = $row['brand'];
    $longDesc[] = $row['puDesc'];
    $upc[] = $row['upc'];
    $upcLink[] = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc=" 
                    . $row['upc'] 
                    . "&ntype=UPC&searchBtn=' class='blue' target='_blank'>{$row['upc']}
                    </a>";
}
if ($dbc->error()) {
    echo $dbc->error(). "<br>";
}

echo "<h1 align='center'>  Missing Shelf Tags</h1>";
echo "<div align='center'>";
if ($_SESSION['store_id'] == 1) {
    echo "Hillside<br>";
} else {
    echo "Denfeld<br>";
}
echo $_SESSION['session'];
echo "</div>";
echo "<p align='center'>" . count($upc) . " tags in this queue</p>";
echo '<div style="text-align:left;"><a href="http://192.168.1.2/git/fannie/item/AdvancedItemSearch.php" target="_blank">&nbsp;&nbsp;Advanced Search</a></div><br>';
echo "<table class=\"table\">";
echo "<th>UPC</th>";
echo "<th>Brand</th>";
echo "<th>Description</th>";
echo "<th>Long Description</th>";
for ($i=0; $i<count($upc); $i++) {
    if ($upc[$i] >= 0) {
        echo "<tr><td>" . $upcLink[$i] . "</td>"; 
        echo "<td>" . $brand[$i] . "</td>"; 
        echo "<td>" . $desc[$i] . "</td>"; 
        echo "<td>" . $longDesc[$i] . "</td>"; 
    }
    echo "<td><button class=\"btn btn-default\" type=\"button\" onclick=\"sendToQueue(this, '{$upc[$i]}', 98, '{$_SESSION['session']}'); return false;\">Remove from Queues</button></td>";    
    echo "<td><a class=\"btn btn-default\" href=\"http://192.168.1.2/git/fannie/item/handheld/ItemStatusPage.php?id={$upc[$i]}\" target=\"_blank\">status check</a></tr>";
}
echo "</table>";
