
<head>
<link rel="stylesheet" href="../../../common/bootstrap/bootstrap.min.css">
<script src="../../../common/bootstrap/jquery.min.js"></script>
<script src="../../../common/bootstrap/bootstrap.min.js"></script>
</head>

<?php

include('../../../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(dirname(__FILE__))) . '/common/sqlconnect/SQLManager.php');
}

class generic_item_Batches_BatchReview_page extends ScancoordDispatch
{
    
    protected $title = "none";
    protected $description = "[none] blank.";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        include('../../../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }
         
        echo "asdfa";
         
        /*
        $query = $dbc->prepare("");
        $result = $dbc->execute($query);
        while ($row = $dbc->fetch_row($result)) {
        }
        if ($dbc->error()) echo $dbc->error();
        */
         
        $query = $dbc->prepare("select upc, brand, description from products limit 25");
        $result = $dbc->execute($query);
        $data = array();
        $headers = array();
        $i = 0;
        while ($row = $dbc->fetch_row($result)) {
            foreach ($row as $k => $v) {
                if(!is_numeric($k)) {
                    $data[$i][$k] =  $v;
                    $headers[$k] = $k;
                }
            }
            $i++;
        }
        
        /*  Add a column
        $i = 0;
        foreach ($data as $k => $array) { 
            $newColumnName = 'column_name';
            $data[$i][$newColumnName] = 'data_to_put_into_column';
            $headers[$newColumnName] = $newColumnName;
            $i++;
        }
        */
        
        /*  Add a flags
        $i = 0;
        $flags = array();
        foreach ($data as $k => $array) { 
            if ('condition') {
                $flags['flag_type'][] = $i;
            }
            $i++;
        }
        */
        
        echo '<div class="panel panel-default"><table class="table table-striped">';
        echo '<thead>';
        foreach ($headers as $v) {
            echo '<th>' . $v . '</th>';
        }
        echo '</thead>';
        $prevKey = '1';
        echo '<tr>';
        foreach ($data as $k => $array) { 
            foreach ($array as $kb => $v) {
                echo '<td> ' . $v . '</td>'; 
            }
            if($prevKey != $k) {
                /*  highlight Flagged rows
                if (in_array(($k+1),$flags['flag_name'])) {
                    echo '</tr><tr class="" style="background-color:tomato;color:white">';
                } else {
                    echo '</tr><tr>';
                }
                */
                /*  rows w/ no flags */
                echo '</tr><tr>';
            } 
            $prevKey = $k;
        }
        echo '</table></div>';
        
        /*
        $upcLink = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc=" 
                . $upc . "&ntype=UPC&searchBtn=' target='_blank'>{$upc}</a>";
        */
        if ($dbc->error()) echo $dbc->error();
        
        return $ret;
    }
    
    private function form_content()
    {
        return '
            <div class="text-center container">
                <form class ="form-inline"  method="get" > 
                    <br>
                    <div class="form-group">    
                        <input type="text" class="form-control" name="upc" placeholder="enter plu to track" autofocus>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default" value="submit">
                    </div>
                </form>
            </div>
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
