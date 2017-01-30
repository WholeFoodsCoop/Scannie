<?php 
include('/var/www/html/git/fannie/config.php');
include('/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
?>
<style>
p {
    font-size: 14;
    text-align: center;
    background-color: #acfaf7;
}
</style>

<?php
session_start();
$upc = $_GET['upc'];

echo '
    <form method="get" class="form-inline">
        <input type="text" class="form-control" style="width:300px;border: 1px solid red" 
            name="notes" placeholder="Type Notes Here">
        <input type="hidden" name="upc" value="' . $upc  . '"><br>
        <input type="submit" class="btn btn-danger" value="Save Note" 
            style="width:300px;height:70px;border: 2px solid red;background-color:#fae3e3">
    </form> 
';

