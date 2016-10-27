<html>
<head>
  <title> Breakdowns </title>
  <link rel="stylesheet" href="../../common/bootstrap/bootstrap.min.css">
  <script src="../../common/bootstrap/jquery.min.js"></script>
  <script src="../../common/bootstrap/bootstrap.min.js"></script>
<style>
</style>
</head>
<body>

<?php
include('/var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class unfiBreakdowns 
{
    
    protected $title = 'UNFI Breakdowns Page';
    protected $description = '';
    
    public function view()
    {        
        include('../../config.php');
        $rounder = new \COREPOS\Fannie\API\item\PriceRounder();
        
        print self::form_content();
        
        $start = FormLib::get('start');
        
        if (FormLib::get('end')) {
            $end = FormLib::get('end');
        } else {
            $end = FormLib::get('start');
        }
        
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', 'woodshed_no_replicate', $SCANUSER, $SCANPASS);
        $prep = $dbc->prepare("
            SELECT upcUp, upcDn, size
            FROM UnfiBreakdowns AS u 
        ");
        $res = $dbc->execute($prep);
        $parents = array();
        $children = array();
        $size = array();
        while ($row = $dbc->fetchRow($res)) {
            $parents[] = $row['upcUp'];
            $children[] = $row['upcDn'];
            $size[$row['upcUp']] = $row['size']; //size is saved only to parent UPCs
        }
        if ($dbc->error()) print $dbc->error() . '<br>';
        
        $args = array($start,$end);
        $prep = $dbc->prepare("
            SELECT 
                bl.upc, 
                bl.salePrice,
                bl.batchID,
                p.description
            FROM is4c_op.batchList AS bl
                LEFT JOIN is4c_op.batches AS b ON bl.batchID=b.batchID
                LEFT JOIN is4c_op.products AS p ON bl.upc=p.upc
            WHERE b.batchID >= {$start}
                AND b.batchID <= {$end}
        ");
        $res = $dbc->execute($prep);
        $batchList = array();
        $batchListUpcs = array();
        $batchIDs = array();
        while ($row = $dbc->fetchRow($res)) {
           $batchList[$row['upc']] = $row['salePrice'];
           $bathListUpcs[] = $row['upc'];
           $batchIDs[$row['upc']] = $row['batchID'];
           $batchDesc[$row['upc']] = $row['description'];
        }
        
        print "<p style='width:350px'>If there are UPCs listed below, they should be added to<br>the appropriate batches</p>";
        
        print '<table class="table table-striped table-bordered" style="width:250px;border:2px solid lightgrey">
                    <th></ht><th>upc</th><th>batchID</th><th>saleprice</th>';
        foreach ($batchList as $upc => $salePrice) {
            //  GET Child
            if (in_array($upc,$parents) && !in_array($upc,$child)) {
                $curSize = $size[$upc];
                $price = ($salePrice / $curSize);
                $price = $rounder->round($price);
                $batch = '<a href="http://192.168.1.2/git/fannie/batches/newbatch/EditBatchPage.php?id=' . $batchIDs[$upc] . '" target="_blank">' . $batchIDs[$upc] . '</a>';
                while ($price*$curSize < $salePrice) {
                    $price++;
                }
                $childKey = array_keys($parents,$upc);
                foreach ($childKey as $value) $child = $children[$value];
                $child = str_pad($child, 13, 0, STR_PAD_LEFT);
                if (!in_array($child,$bathListUpcs)) {
                    print sprintf('<tr><td>child</td><td>%s</td><td>%s</td><td>%0.2f</td></tr>',$child,$batch,$price);
                }                
            }
            
            //  GET Parent
            if (in_array($upc,$children)) {
                $parentKey = array_keys($children,$upc);
                foreach ($parentKey as $value) $parent = $parents[$value];
                $parent = str_pad($parent,13,0,STR_PAD_LEFT);
                $curSize = $size[$parent];
                $price = ($salePrice * $curSize);
                $price = $rounder->round($price);
                $batch = '<a href=http://192.168.1.2/git/fannie/batches/newbatch/EditBatchPage.php?id=' . $batchIDs[$upc] . '" target="_blank">' . $batchIDs[$upc] . '</a>';
                while ($price > $salePrice*$curSize) {
                    $price--;
                }
                if (!in_array($parent,$bathListUpcs)) {
                    print sprintf('<tr><td>parent</td><td>%s</td><td>%s</td><td>%0.2f</td></tr>',$parent,$batch,$price);
                }
            }
        }
        print '</table>';
        
        return false;
    }
    
    private function form_content()
    {
        return '
            <form method="get"> 
                <input type="text" name="start" placeholder="start batchID" autofocus require>
                <input type="text" name="end" placeholder="end batchID (opt).">
                <button type="submit">Submit</button>
            </form>
        ';
    }
    
}
unfiBreakdowns::view();

