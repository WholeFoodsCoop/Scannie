<?php
include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}

class SignInfoNaturalizer extends ScancoordDispatch
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
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

        $startID = $_POST['startID'];
        $endID = $_POST['endID'];
        
        //  dev. temp. delete me
        $startID = 7578;
        $endID = 7599;
        
        
        $args = array($startID,$endID);
        $query = $dbc->prepare("
            SELECT p.brand FROM productUser AS p LEFT JOIN batchList AS b ON p.upc=b.upc WHERE b.batchID BETWEEN ? AND ?;
        ");
        $result = $dbc->execute($query,$args);
        $brands = array();
        while ($row = $dbc->fetch_row($result)) {
            $upc = $row['upc'];
            $brand = $row['brand'];
            $brands[$brand][] = $upc;
        }
        if ($dbc->error()) $ret .=  '<div class="alert alert-warning">'.$dbc->error().'</div>';
        
        
        echo '<div class="panel panel-default" style="width: 200px;">';
        echo '<table id="mytable" class="table table-striped table-condensed small" >';
        $headers = array();
        echo '<thead>';
        foreach ($headers as $header) echo '<th>'.$header.'</th>';
        echo '</thead>';
        
        /** Don't order these yet. At least for Co-op Deals, the order is following batch order, which will put like 
         *  brands together, which is very useful for finding products that are off buy a brand.
         *  
         *  What to add next? In this loop, create a hidden div with id="$brand"; 
         *  Click on a brand to view all of the upcs associated with that brand.
         *  
         */
        foreach ($brands as $brand => $ary) {
            echo '<tr>';
            if ($brand == '') {
                $brand = 'NO_SIGN_INFO';
            }
            echo '<td>'.$brand.'</td>';
            echo '<td>'.count($ary).'</td>';
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


