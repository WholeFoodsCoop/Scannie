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
    
    public function body_content()
    {
        include('../config.php');
        $item = array();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

        $startID = $_POST['startID'];
        $endID = $_POST['endID'];
        
        //  dev. temp. delete me
        $startID = 7962;
        $endID = 7984;
        
        echo '
            <style>
                a:hover {
                    cursor: pointer;
                }
            </style>
        ';
        
        
        $args = array($startID,$endID);
        $query = $dbc->prepare("
            SELECT p.brand, p.upc FROM productUser AS p LEFT JOIN batchList AS b ON p.upc=b.upc WHERE b.batchID BETWEEN ? AND ?;
        ");
        $result = $dbc->execute($query,$args);
        $brands = array();
        while ($row = $dbc->fetch_row($result)) {
            $upc = $row['upc'];
            $brand = $row['brand'];
            $brands[$brand][] = $upc;
        }
        if ($dbc->error()) $ret .=  '<div class="alert alert-warning">'.$dbc->error().'</div>';
        
        $count = 0;
        echo '<div align="center"><div  style="width: 1300px;">';
        echo '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true" style="width: 250px; float: left">';
        foreach ($brands as $brand => $ary) {
            if ($brand == '') {
                $brand = '<span class="text-danger">MISSING BRAND</span>';
            }
            
            echo '
              <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="heading'.$count.'">
                  <h4 class="panel-title" style="text-align: left;">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse'.$count.'" aria-expanded="true" aria-controls="collapse'.$count.'">
                      '.$brand.'<span style="float: right">'.count($ary).'</span>
                    </a>
                  </h4>
                </div>
                <div id="collapse'.$count.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'.$count.'">
                  <div class="panel-body">
                    
            ';
            foreach ($ary as $k => $v) {
                // $upcln = '<a class="upc" href="http://'.$CORE_POS_PATH.'/item/ItemEditorPage.php?searchupc='.$v.'&ntype=UPC&searchBtn=0#ProdUserFieldsetContent" target="_BLANK">'.$v.'</a>';
                $upcln = '<a class="upc" value="'.$v.'">'.$v.'</a>';
                echo $upcln . '<br />';
            }            
            echo '
                  </div>
                </div>
              </div>
            ';
            
            $count++;
        }
        //echo "</table>";
        echo '</div>'; //accordion //accordion wrapper
        
        
        echo '
            <iframe id="iframe" src="http://192.168.1.2" style="position: fixed; width: 1000px;height: 600px;; left: 600px; border: 2px solid grey;" seamless></iframe>
            </div></div>
        ';
        
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
    
    public function javascriptContent()
    {
        ob_start();
        ?>
function load_iframe() {
    $('.upc').click(function () {
        var upc = $(this).html();
        var addy = "http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc="+upc+"&ntype=UPC&searchBtn=0 #ProdUserFieldsetContent";
        $('#iframe').attr('src',addy);
        $('#iframe').contentWindow.$('#lf_brand').focus();
    });
}

$(document).ready(function () {
   load_iframe(); 
});
        <?php
        return ob_get_clean();
    }
    
}

ScancoordDispatch::conditionalExec();


