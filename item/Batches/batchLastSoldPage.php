<html>
<head>
  <title> Last Sold Check </title>
  <link rel="stylesheet" href="../../common/bootstrap/bootstrap.min.css">
  <script src="../../common/bootstrap/jquery.min.js"></script>
  <script src="../../common/bootstrap/bootstrap.min.js"></script>
<style>
</style>
</head>
<body>

<?php
session_start();
include(dirname(__FILE__) . '/var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}

class batchLastSoldPage
{
    
    protected $title = ' __ Page';
    protected $description = 'This page takes a batchID to check for items 
        that have not sold in over 1 month. Wellness items are highlighted
        in blue.
        Note: I beleive the idea behind this page was that it be used to make 
        products not INUSE to prevent re-occurances of outdated, non-stocked
        products sales signs form being printed. The intention of this 
        page is NOT to remove items from batches (uness we can see that the
        product is clearly not being carried by BOTH stores.';
    
    private function blank()
    {
        
    }
    
    public function view()
    {        
    
        include('../../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        echo '
            <h4>&nbsp;&nbsp;Batch Product Last Sold Page</h4>
            <br>
            <form method="get" id="form1">
                <fieldset class="form-inline">&nbsp;&nbsp;
                        <input type="text" class ="form-control" name="startdate" placeholder="Sale Start Date">
                        <select class="form-control" name="store_id">
                            <option value="1">Hillside</option>
                            <option value="2">Denfeld</option>
                        </select>
                        <input type="submit" class="btn btn-default" value="GO!">
                </fieldset>
            </form>
        ';
        
        echo '<div align="center">';
        if (isset($_GET['store_id'])) {
            $store_id = $_GET['store_id'];
            echo '<h4>Store ID: ' . $store_id . '</h4>';
        }
        if (isset($_GET['startdate'])) {
            $startdate = $_GET['startdate'];
            echo '<h4>' . $startdate . '</h4>';
        }echo '</div><br>';
        
        $curY = date('Y');
        $curM = date('m');
        $curD = date('d');
        
        //if (isset($store_id) && isset($startdate)) {
            $query = "
                SELECT 
                    p.brand, 
                    p.description,
                    b.upc,
                    p.last_sold,
                    p.department
                FROM batchList AS b
                    LEFT JOIN products AS p ON b.upc=p.upc
                    LEFT JOIN batches AS ba ON b.batchID=ba.batchID
                WHERE p.store_id = {$store_id}
                    AND ba.startDate = '{$startdate}'
                    AND p.last_sold < '" . $curY . "-" . ($curM-1) . "-" . $curD . "'
                    AND p.inUse = 1
                ORDER BY p.department
                ";
            $result = $dbc->query($query);
            echo '<table class="table table-striped">';
            while ($row = $dbc->fetchRow($result)) {
                $upcLink = '<a href="http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc=' . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a>';
                if ($row['department'] > 86 && $row['department'] < 128   ) {
                    echo '<tr class="info">';
                } else {
                    echo '<tr>';
                }
                echo '<td>' . $row['brand'] . '</td>';
                echo '<td>' . $row['description'] . '</td>';
                echo '<td>' . $upcLink . '</td>';
                echo '<td>' . $row['last_sold'] . '</td>';
            }
            echo '</table>';
            if ($dbc->error()) {
                echo $dbc->error(). "<br>";
            }
        //}
        
        
        
        
        return false;
    }
    
    
}
batchLastSoldPage::view();

