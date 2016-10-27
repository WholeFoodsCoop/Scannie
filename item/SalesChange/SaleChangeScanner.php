<?php 
include('/var/www/html/git/fannie/config.php');
include('/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
session_start();
?>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<head>
  <link rel="stylesheet" href="../../common/bootstrap/bootstrap.min.css">
  <script src="../../common/bootstrap/jquery.min.js"></script>
  <script src="../../common/bootstrap/bootstrap.min.js"></script>
<script language="javascript" type="text/javascript">
$('#myTabs a').click(function (e) {
  e.preventDefault()
  $(this).tab('show')
})
</script>
<script>
function button(button, href) {
    window.open(href, '_blank');
}
</script>
<script language="javascript" type="text/javascript">
function getErrNote(upc)
{
    $.ajax({
        url: 'salesChangeAjaxErrSigns.php',
        data: 'upc='+upc,
        success: function(response)
        {
            $('#ajax-form').html(response);
        }
    });
}
</script>
</head>
<head>

<style>
button {
	width:300px;
	height: 75px;
    border-radius: 5px;
    font-size: 18;
    
}
.good {
	background-color: lightgreen;
}
.error {
	background-color: #fa7d7d;
}
.missing {
	background-color: #f0f56c;
}
.addItem {
    background-color: #27e5f2;
}
.blue {
    color: blue;
}
.black {
    color: white;
    background-color: black;
}
.lightgrey {
    #background-color: lightblue;
    border: 1px solid lightblue;
    width: 150px;
    
}
.red {
    color: red;
}
a {
    font-size: 18;
    text-align: center;
}
table, tr, td, th {
    border-top: none !important;
	padding: none;   
	font-size: 12px;
}
.purple {
    background-color: purple;
    color: white;
}
.code {
    padding: 3px;
    border-radius: 3px;
}
</style>
<script type="text/javascript" src="/git/fannie/src/javascript/jquery.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/cordova-2.2.0.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/ScannerLib-Linea-2.0.0.js"></script>
<script type="text/javascript" src="scanner.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    enableLinea('#upc', function(){$('#my-form').submit();});
});
function sendToQueue(button, upc, queue_id, session,notes)
{
    $.ajax({

        // Info will be sent to this URL
        url: 'salesChangeAjax2.php',

        // The actual data to send
        data: 'upc='+upc+'&queue='+queue_id+'&session='+session+'&notes='+notes,

        // callback to process the response
        success: function(response)
        {
            // display the response in element w/ id=ajax-resp
            $('#ajax-resp').html(response);

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
    <title>SalesChangeScanner</title>
</head>
<body><br>
<form class='form-inline' method='get' name='MyForm' id="my-form">
  <div class="text-center container" style="text-align:center">
    <input class='form-control' type='text' name='upc' id="upc" placeholder="Scan Item">
    <input type='submit' value='go' hidden>
  </div>
</form>

<div id="ajax-resp" style="font-weight:bold; font-size: 8pt;"></div>

<script>
function myFunction() {
    document.getElementById("field2").value = document.getElementById("field1").value;
}
</script>

<?php
if(isset($_POST['session'])) $_SESSION['session'] = $_POST['session'];
if(isset($_POST['store_id'])) $_SESSION['store_id'] = $_POST['store_id'];
$item = array ( array() );


if (isset($_GET['notes'])) {
    $note = $_GET['notes'];
    echo "<script type='text/javascript'>" .
        "sendToQueue(this, '{$_GET['upc']}', 2, '{$_SESSION['session']}','{$note}');" .
        "</script>"
    ;
    unset($_GET['notes']);
}

/*
foreach ($_SESSION as $key => $value) {
    echo $key . '<br>';
    foreach ($value as $keyb => $valueb) {
        echo $keyb . ' :: ' . $valueb . '<br>';
    }
}
*/

echo "<div align=\"center\">";
if ($_SESSION['store_id'] == 1) {
    echo "<h2>Hillside</h2>";
} else {
    echo "<h2>Denfeld</h2>";
}
echo "<strong>" . $_SESSION['session'] . "</strong>";
echo "</div>";
if (isset($_GET['upc'])) {
    echo '
            <div align="center">
                <h5><b>UPC:</b> ' . str_pad($_GET['upc'], 13, '0', STR_PAD_LEFT) . '</h5>
            </div>
    ';
}

if ($_SESSION['store_id'] == NULL) {
    echo "<strong class=\"red\" text-align=\"justified\">
        WARNING : YOU HAVE NOT SELECTED
        A <b>STORE</b>.<br> NO ITEMS WILL BE UPDATED IN BATCH 
        CHECK. <br>PLEASE SELECT A STORE AT BOTTOM OF 
        PAGE.</strong><br><br>";
}

if ($_SESSION['session'] == NULL) {
    echo "<strong class=\"red\" text-align=\"center\">
        WARNING : YOU HAVE NOT SELECTED
        A <b>SESSION</b>.<br> NO ITEMS WILL BE UPDATED IN BATCH 
        CHECK. <br>PLEASE SELECT A SESSION AT BOTTOM OF 
        PAGE.</strong>";
}

include('../../config.php');
$dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANALTDB, $SCANUSER, $SCANPASS);

if ($_GET['upc']) {
    $_GET['upc'] = str_pad($_GET['upc'], 13, '0', STR_PAD_LEFT);
    echo "<table class='table'  align='center' width='100%'>";
    
    //* Find UPCs and Queues in Woodshed */
    $query = "
        SELECT 
            q.queue AS queue_no, 
            u.brand as ubrand, 
            u.description as udesc,
            p.upc, 
            p.size as psize, 
            p.normal_price, 
            v.size as vsize,
            p.brand as pbrand, 
            p.description as pdesc,
            qu.name
        FROM is4c_op.products as p
            LEFT JOIN is4c_op.productUser as u on u.upc=p.upc 
            LEFT JOIN SaleChangeQueues as q ON (q.upc=p.upc)
            LEFT JOIN is4c_op.vendorItems as v on v.upc=p.upc
            LEFT JOIN queues AS qu ON q.queue=qu.queue
        WHERE p.upc={$_GET['upc']}
            AND p.store_id={$_SESSION['store_id']}
            AND q.session='{$_SESSION['session']}'
        GROUP BY p.upc
            ;";
    $result = $dbc->query($query);
    while ($row = $dbc->fetchRow($result)) {
        //echo "<tr><td><b>upc</td><td>" . $row['upc'] . "</tr>";
        if ($row['ubrand'] != NULL) {
            echo "<tr><td><b>brand</td><td>" . $row['ubrand'] . "</tr>";
        } else {
            echo "<tr><td><b>brand</td><td>" . $row['pbrand'] . "</tr>";
        }
        
        if ($row['udesc'] != NULL) {
            echo "<tr><td><b>product </td><td>" . $row['udesc'] . "</tr>";
        } else {
            echo "<tr><td><b>product </td><td>" . $row['pdesc'] . "</tr>";
        }
        
        if ($row['psize'] == NULL) {
            echo "<tr><td><b>size</td><td>" . $row['psize'] . "</tr>";
        } else {
            echo "<tr><td><b>size</td><td>" . $row['vsize'] . "</tr>";
        }
        
	
        if ($row['queue_no'] != NULL)  {
            if ($row['queue_no'] === "1") {
                echo "<tr><td><b>queue</td><td><span class='code good'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
            }
            if($row['queue_no'] === "2") {
                echo "<tr><td><b>queue</td><td><span class='code error'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
            } 
            if($row['queue_no'] === "0") {
                echo "<tr><td><b>queue</td><td><span class='code lightgrey'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
            }
            if($row['queue_no'] === "99") {
                echo "<tr><td><b>queue</td><td><span class='code addItem'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
            } 
            if($row['queue_no'] === "8") {
                echo "<tr><td><b>queue</td><td><span class='code missing'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
            } 
            if($row['queue_no'] === "7") {
                echo "<tr><td><b>queue</td><td><span class='code purple'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
            } 
            if($row['queue_no'] === "9") {
                echo "<tr><td><b>queue</td><td><span class='code black'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
            }
        } else if ($row['queue'] == NULL) {
            echo "<tr><td><b>queue</td><td><i class=\"red\">This items is queue-less</tr>";
        }
        echo "<tr><td><b>Price</td><td>" . "$" . $row['normal_price'] . "</tr>";
	
        
    /*    
        if ($row['queue'] == 0) {
            echo "<tr><td><b>queue</td><td><i>item has not been checked yet</tr>";
        } else if ($row['queue'] == 1) {
            echo "<tr><td><b>queue</td><td>good tag</tr>";
        } else if ($row['queue'] >=2 && $row['queue'] <=7) {
            echo "<tr><td><b>queue</td><td>tag error</tr>";
        } else if ($row['queue'] == 8) {
            echo "<tr><td><b>queue</td><td>tag missing</tr>";
        } else if ($row['queue'] == NULL){
            echo "<tr><td><b>queue</td><td>tag is not in a queue</tr>";
        }
    */  
    }
	

    //  Procure batches from stardate
    $query = "select batchID, owner 
            from is4c_op.batches 
            where CURDATE() BETWEEN startDate AND endDate
            ;";
    $result = $dbc->query($query);
    while ($row = $dbc->fetchRow($result)) {
        $batchID[] = $row['batchID'];
        $owner[] = $row['owner'];
    }
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }

    // Procure Product Information from batchList
    $query = "SELECT l.upc, l.salePrice, b.batchName
        FROM is4c_op.batches AS b 
        LEFT JOIN is4c_op.batchList AS l ON l.batchID=b.batchID 
        WHERE CURDATE() BETWEEN b.startDate AND b.endDate 
            AND l.upc={$_GET['upc']}
        ;";
    $result = $dbc->query($query);
    while ($row = $dbc->fetchRow($result)) {
        echo "<tr><td><b>sale price</td><td class=\"blue\">" . $row['salePrice'] . "</tr>";
        echo "<tr><td><b>batch name</td><td>" . $row['batchName'] . "</tr>";
    } 
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }

    echo "</table>";
}

echo "<table class='table'>";
echo "<tr><td><button class=\"btn btn-success\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 1, '{$_SESSION['session']}','NULL'); return false;\">Check Sign</button></tr>";
echo "<tr><td><button class=\"btn btn-info\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 99, '{$_SESSION['session']}','NULL'); return false;\">Add Item to Queue</button></tr>";
echo "<tr><td><button class=\"btn btn-warning\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 8, '{$_SESSION['session']}'); return false;\">Missing Sign</button></tr>";
echo '<tr><td><div id="ajax-form"></div></td></tr>';
echo "<tr><td><button class=\"btn btn-danger\" type=\"button\" onclick=\"getErrNote('{$_GET['upc']}'); return false;\">Write Note</button></tr>";
echo "<tr><td><button class=\"btn btn-default purple\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 7, '{$_SESSION['session']}','NULL'); return false;\">Shelf Tag Missing</button></tr>";
echo "<tr><td><button class=\"btn btn-default black\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 9, '{$_SESSION['session']}','NULL'); return false;\">Generic Sign Needed</button></tr>";
echo "</table>";

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
            <select class="form-control" name="session">
                <option value="">select a session</option>');
                
    foreach ($session as $key => $sessID) {
        print '<option value="' . $sessID . '">' . $sessID . '</option>';
    }

    print ('    
            </select>
        </div>');
        
    print '
        <div class="text-center container">
        <form method="post" class="form-inline">
            <select class="form-control" name="store_id">
                <option value="">select a store</option>
                <option value="1">Hillside</option>
                <option value="2">Denfeld</option>
            </select>
            <br>
            <input type="submit" class="btn btn-default" value="Update Session & Store ID">
        </form>
    ';
        
?>

<br><br>
<span class="btn-group">
    <a class="btn btn-default btn-sm iframe fancyboxLink" href="http://192.168.1.2/scancoord/item/SalesChange/SalesChangeIndex.php" title="Scanning Tools">Scanning<br>Tools</a>
    <a class="btn btn-default btn-sm iframe fancyboxLink" href="http://192.168.1.2/git/fannie/item/handheld/ItemStatusPage.php" title="Status Check">Status<br>Check</a>
    <a class="btn btn-default btn-sm iframe fancyboxLink" href="http://192.168.1.2/git/fannie/item/CoopDealsLookupPage.php" title="cd_check">Co-op Deals<br>File Check</a>
</span>


<div align="center">
<br><br><br>
<a href="http://192.168.1.2/scancoord/marginCalc.php">link to margin calc</a>