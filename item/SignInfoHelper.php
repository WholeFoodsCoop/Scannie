<?php
include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}

class SignInfoHelper extends ScancoordDispatch
{
    
    protected $title = "Sign Info Helper";
    protected $description = "[Sign Info Helper] Find product information in relation 
        to sales on a specified date.";
    protected $ui = TRUE;
    protected $add_javascript_content = TRUE;
    
    public function body_content()
    {
        include('../config.php');
        $item = array();
        $startdate = $_POST['startdate'].' 00:00:00';
        $pDept = $_POST['dept'];
        $store_id = $_POST['store_id'];
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

        echo $this->form_content();
        
        $args = array($startdate,$store_id);
        $query = $dbc->prepare("
            SELECT u.brand, u.description, p.brand AS pbrand, p.description AS pdesc,
                u.upc, p.size, p.normal_price, ba.batchName,
                bl.salePrice, ba.batchID, p.last_sold
            FROM products AS p
                LEFT JOIN is4c_op.productUser as u on u.upc=p.upc
                LEFT JOIN is4c_op.batchList as bl on bl.upc=p.upc
                LEFT JOIN is4c_op.batches as ba on ba.batchID=bl.batchID
            WHERE startDate = ?
                AND store_id = ?
            GROUP BY upc
            ORDER BY p.brand ASC;
        ");
        $result = $dbc->execute($query,$args);
        $item = array();
        while ($row = $dbc->fetch_row($result)) {
            $upc = $row['upc'];
            $item[$upc]['brand'] = $row['brand'];
            $item[$upc]['pbrand'] = strtoupper($row['pbrand']);
            $item[$upc]['desc'] = $row['description'];
            $item[$upc]['pdesc'] = $row['pdesc'];
            $item[$upc]['size'] = $row['size'];
            $item[$upc]['price'] = $row['normal_price'];
            $item[$upc]['batch'] = $row['batchName'];
            $item[$upc]['salePrice'] = $row['salePrice'];
            $item[$upc]['batchID'] = $row['batchID'];
            $item[$upc]['last_sold'] = $row['last_sold'];
        }
        if ($dbc->error()) $ret .=  '<div class="alert alert-warning">'.$dbc->error().'</div>';
        
        echo count($item) . " items found for this sales period for ";
        $deptResp = array(
            1=>"All Departments",
            2=>"Bulk Department",
            3=>"Cool Department",
            4=>"Grocery Department",
            5=>"Wellness Department"
        );
        echo $deptResp[$pDept];
        echo " on ".$startdate;
        echo " for ".$_POST['store_id']." <br>";
        
        echo '<div class="panel panel-default">';
        echo '<table id="mytable" class="table table-striped table-condensed small">';
        $headers = array('Brand','Brand','Description','Description','size','upc,','Norm $',
            'Sale $','Batch Name','BatchID','last_sold');
        echo '<thead>';
        foreach ($headers as $header) echo '<th>'.$header.'</th>';
        echo '</thead>';
        $wTD = '<td class="btn-danger">';
        $TD = '<td>';
        foreach ($item as $upc => $row) {
            echo '<tr>';
            echo '<td>'.$row['pbrand'].'</td>';
            echo '<td>'.$row['brand'].'</td>';
            echo '<td>'.$row['desc'].'</td>';
            echo '<td>'.$row['pdesc'].'</td>';
            echo '<td>'.$row['size'].'</td>';
            echo '<td><a href="http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc='.$upc.
                '" target="_BLANK">'.$upc.'</td>';
            echo '<td>'.$row['price'].'</td>';
            echo '<td>'.$row['salePrice'].'</td>';
            echo '<td>'.$row['batch'].'</td>';
            echo '<td><a href="http://192.168.1.2/git/fannie/batches/newbatch/EditBatchPage.php?id='.$row['batchID'].
                '" target="_BLANK">'.$row['batchID'].'</td>';
            if ($row['last_sold'] == NULL) { echo $wTD; } else { echo $TD; }
            echo $row['last_sold'].'</td>';
            echo '</tr>';
        }
        echo "</table>";
        echo '</div>';
    }
    
    private function form_content()
    {
        $default_date = date("Y-m-d");
        if ($_POST['startdate']) { 
            $default_date = sprintf('%s', ltrim($_POST['startdate']));
        }
        
        $ret = '
            <a class="" onClick="document.location.href=\'http://192.168.1.2/scancoord/item/SalesChange/SalesChangeIndex.php\'"><span class="text-primary">Sales Change Tools</span></a>
            <h4>Sign Info Report</h4><br>
            Enter date as yyyy-mm-dd

            <form method="post" id=\'form1\'>
                <fieldset class="form-inline">
                        <input type="text" class ="form-control" name="startdate" value="'.$default_date.'">
                        <select class="form-control" name="store_id">
                            <option value="1">Hillside</option>
                            <option value="2">Denfeld</option>
                        </select>
                        <input type="submit" class="btn btn-default" value="GO!">
                </fieldset>
            </form>


            <div class="row">
                <div class="col-md-2">
                </div>
            </div><br>
        ';
        
        return $ret;
    }
    
    public function javascript_content()
    {
        
        $ret = '';
        $ret .= '
<script type="text/javascript">
    var $table = $(\'#mytable\');
    $table.floatThead();
</script>
<script src="/scancoord/common/javascript/jquery.floatThead.min.js"></script>
        ';
        return $ret;
    }
    
}

ScancoordDispatch::conditionalExec();


