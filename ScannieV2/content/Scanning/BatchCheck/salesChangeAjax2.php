<?php 
include('/var/www/html/git/fannie/config.php');
include('/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
include('/var/www/html/scancoord/common/sqlconnect/SQLManager.php');
?>
<style>
p {
    font-size: 14;
    text-align: center;
}
</style>

<?php

session_start();
$upc = $_GET['upc'];
$queue = $_GET['queue'];
$session = $_GET['session'];
$store_id = $_SESSION['store_id'];
$note = '';
if ($note = $_GET['notes']) {} 
else {
    $note = 'NULL';
}
$delQ = 0;
$delQ = $_GET['delQ'];

include('../../config.php');
$dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANALTDB, $SCANUSER, $SCANPASS);

$chkQArgs = array($upc,$store_id,$session);
$chkQprep = $dbc->prepare("SELECT queue FROM SaleChangeQueues WHERE upc = ?
    AND store_id = ? AND session = ?");
$chkQRes = $dbc->execute($chkQprep,$chkQArgs);
$inQueues = array();
while ($row = $dbc->fetchRow($chkQRes)) {
    $inQueues[] = $row['queue'];
}

if (in_array($queue,array(1,8,98))) {
    /*
        good,missingSign
    */
    $updateQs = array(0,1,8,98,99);
    $ar = array_intersect($updateQs,$inQueues);
    sort($ar);
    if (count($inQueues) > 0) {
        $update = $ar[0];
        $args = array($queue,$note,$upc,$store_id,$session,$update);
        $prep = $dbc->prepare("
            UPDATE SaleChangeQueues 
            SET queue = ?, notes = ? 
            WHERE upc = ? AND store_id = ? AND session = ? 
                AND queue = ?");
        $dbc->execute($prep,$args);   
    } else {
        echo alertDanger("This item not in list, item must be 
            added to list in order to queue.");
    }
} elseif (in_array($queue,array(7,9))) {
    /*
        shelftagMissing,genericSignNeeded
    */
    if (in_array($queue,inQueues)) {
        $update = $queue;
        $args = array($queue,$note,$upc,$store_id,$session,$update);
        $prep = $dbc->prepare("UPDATE SaleChangeQueues 
            SET queue = ? AND notes = ? 
            WHERE upc = ? AND store_id = ? AND session = ?
                AND queue = ?");
        $dbc->execute($prep,$args);
    } else {
        $args = array($upc,$queue,$store_id,$session);
        $prep = $dbc->prepare("INSERT INTO SaleChangeQueues 
            (upc,queue,store_id,session,date) 
                VALUES 
            ( ?, ?, ?, ?, curdate() )");
        $dbc->execute($prep,$args);
    }
} elseif ($queue == 99) {
    /*
        add item to list
    */
    if (count($inQueues) > 0) {
        echo alertDanger("This item already in list.");
        return false;
    } else {
        $args = array($upc,$queue,$store_id,$session);
        $prep = $dbc->prepare("INSERT INTO SaleChangeQueues 
            (upc,queue,store_id,session,date) 
                VALUES 
            ( ?, ?, ?, ?, curdate() )");
        $dbc->execute($prep,$args);        
    }
} elseif ($queue == 2) {
    /*
        writeNote
    */
    if (in_array(2,$inQueues)) {
        /*update*/
    sort($ar);
    if (count($inQueues) > 0) {
        $update = $ar[0];
        $args = array($queue,$note,$upc,$store_id,$session);
        $prep = $dbc->prepare("
            UPDATE SaleChangeQueues 
            SET queue = ?, notes = ? 
            WHERE upc = ? AND store_id = ? AND session = ? 
                AND queue = 2");
        $dbc->execute($prep,$args);   
    }    
    } else {
        /* insert */
        $args = array($upc,$queue,$store_id,$session,$note);
        $prep = $dbc->prepare("INSERT INTO SaleChangeQueues 
            (upc,queue,store_id,session,date,notes) 
                VALUES 
            ( ?, ?, ?, ?, curdate(), ? )");
        $dbc->execute($prep,$args);   
    }
}
if ($delQ != 0) {
    $args = array($upc,$delQ,$store_id,$session);
    $prep = $dbc->prepare("DELETE FROM SaleChangeQueues 
        WHERE upc = ? AND queue = ? AND store_id = ? AND session = ? ");
    $dbc->execute($prep,$args);   
}

if ($dbc->error()) {
    alertDanger('Error[sql]: '.$dbc->error());
}

$alert_class = array(1=>"success",2=>"danger",99=>"info",8=>"warning",7=>"surprise",9=>"inverse");
if (!$er) {
    echo '<p id="result" class="alert alert-'.$alert_class[$queue].'">
        ' . $upc . '<br />Sent to Queue ' . $queue . '
        <a href="" onClick="$(\'#result\').hide(); return false;" style="float: right; color: grey; font-weight: 300">X</a><br />' 
        . $session . '<br /></p>';   
}

function alertDanger($msg)
{
    $ret = '';
    $ret .= '<div class="alert alert-danger" style="text-align:center;">';
    $ret .= $msg;
    $ret .= '</div>';
    return $ret;
}
