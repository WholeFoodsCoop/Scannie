<?php 
include('/var/www/html/git/fannie/config.php');
include('/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
?>
<style>
p {
    font-size: 14;
    text-align: center;
    //background: linear-gradient(#9edef7, lightblue);
}
</style>

<?php
session_start();
$upc = $_GET['upc'];
$queue = $_GET['queue'];
$session = $_GET['session'];
$note = $_GET['notes'];

include('../../config.php');
$dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANALTDB, $SCANUSER, $SCANPASS);

if ($queue != 99 && $queue != 7) {
    
    /*
    $preQuery = "
        SELECT count(*) 
        FROM SaleChangeQueues 
        WHERE upc = {$upc}
            AND session = '{$session}'
            AND store_id = {$_SESSION['store_id']}";*/
        //if ($dbc->query($preQuery) > 0 || !is_null($dbc->query($preQuery))) {
            $query = "UPDATE SaleChangeQueues
                    SET queue={$queue}
                    WHERE upc={$upc}
                        AND store_id={$_SESSION['store_id']}
                        AND session='{$session}'
                        AND type='1'
                    ;";
            $dbc->query($query);  
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            }    
        //} 
        
        /*else {
            $query = "INSERT INTO SaleChangeQueues
                    (queue, upc, store_id, session) 
                    VALUES 
                    ({$queue}, {$upc}, {$_SESSION['store_id']}, '{$session}')
                    ;";
            $dbc->query($query);  
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            }    
        }*/
    
    if (isset($note)) {
        $query = "UPDATE SaleChangeQueues
                SET notes='{$note}'
                WHERE upc={$upc}
                    AND store_id={$_SESSION['store_id']}
                    AND session='{$session}'
                ;";
        $dbc->query($query);  
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        }    
    }
} 

if ($queue == 99) {
    $query = "INSERT INTO SaleChangeQueues (queue, upc, store_id, session, type)
        VALUES (
        99,
        '{$upc}',
        '{$_SESSION['store_id']}',
        '{$session}',
        1
        )
        ;";
    $result = $dbc->query($query);
    if (mysql_errno() > 0) {
        echo mysql_errno() . ": " . mysql_error(). "<br>";
    } 
} elseif ($queue == 7) {
    $query = "INSERT INTO SaleChangeQueues (queue, upc, store_id, session, type)
        VALUES (
        7,
        '{$upc}',
        '{$_SESSION['store_id']}',
        '{$session}',
        2
        )
        ;";
    $result = $dbc->query($query);
    if (mysql_errno() > 0) {
        echo mysql_errno() . ": " . mysql_error(). "<br>";
    } 
} elseif ($queue == 9) {
    $query = "INSERT INTO SaleChangeQueues (queue, upc, store_id, session, type)
        VALUES (
        9,
        '{$upc}',
        '{$_SESSION['store_id']}',
        '{$session}',
        2
        )
        ;";
    $result = $dbc->query($query);
    if (mysql_errno() > 0) {
        echo mysql_errno() . ": " . mysql_error(). "<br>";
    } 
}

//echo '<p>' . $upc . '<br>Sent to Queue ' . $queue . '<br>' . $session . '</p';


$alert_class = array(1=>"success",2=>"danger",99=>"info",8=>"warning",7=>"surprise",9=>"inverse");
echo '<p id="result" class="alert alert-'.$alert_class[$queue].'">
    ' . $upc . '<br />Sent to Queue ' . $queue . '
    <a href="" onClick="$(\'#result\').hide(); return false;" style="float: right; color: grey; font-weight: 300">X</a><br />' 
    . $session . '<br />
    
</p>';

