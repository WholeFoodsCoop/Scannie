<html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<head>
  <link rel="stylesheet" href="../../common/bootstrap/bootstrap.min.css">
  <script src="../../common/boostrap/jquery.min.js"></script>
  <script src="../../common/boostrap/bootstrap.min.js"></script>
</head>
<title>
    Remove Sessions
</title>
<style>
</style>
<body>
<fieldset>
    <div class="container" ><h3>
<br><br>
<a class="btn btn-default" onClick="document.location.href='http://192.168.1.2/scancoord/item/SalesChange/SalesChangeIndex.php'"><span class="text-primary">Sales Change Tools</span></a>
<h1>Remove Sessions</h1>
<?php
include('../../config.php');
$dbc = mysql_connect($SCANHOST, $SCANUSER, $SCANPASS);
mysql_select_db($SCANALTDB, $dbc);
$sessions = array();
$query = "select session, store_id from SaleChangeQueues group by session;";
$result = mysql_query($query, $dbc);
while ($row = mysql_fetch_assoc($result)) {
    $sessions[] = $row['session'] . $row['store_id'];
}
echo '
    <form method="get" class="form-inline">
        <select class="form-control" name="delSession">
            <option class="form-inline" name="" value="">Select a Session</option>';            
foreach ($sessions as $key => $session) {
    //echo $session . ' ' . $store_id;
    echo '
            <option class="form-inline" value="' . $session  . '">' . substr($session,0,-1) . '</option>
    ';    
}
echo   '</select>
        <input class="btn btn-default" type="submit" value="Delete Session">
    </form>';
if(isset($_GET['delSession'])) {
    foreach ($_GET as $key => $value) {     
        $session = substr($value, 0, -1);
        $store_id = substr($value, -1);
    }
    
    $query = 'DELETE FROM SaleChangeQueues WHERE session = "' . $session . '" AND store_id = ' . $store_id;
    mysql_query($query, $dbc);
    $msg = '\'' . $session . '\' Successfully Deleted.'; 
    sqlErr(mysql_errno(), 1, $msg);
} 
function sqlErr($error,$showMsg=FALSE,$msg)
{
    if ($error > 0) {
        echo '<div class="alert alert-danger">' . $error . ": " . mysql_error(). "</div>";
    } elseif ($error == 0 && $showMsg) {
        echo '<div class="alert alert-success">' . $msg . "</div>";
    }
    return $error;
}