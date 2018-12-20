<?php
include(__DIR__.'/../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../common/sqlconnect/SQLManager.php');
}

class scaleCheckPage extends ScancoordDispatch
{

    protected $title = "Scale Check";
    protected $description = "[Scale Check] Checks all 3 digit PLUs for scale settings.";
    protected $ui = TRUE;

    public function body_content()
    {
        $ret = '';
        include(__DIR__.'/../config.php');
        include(__DIR__.'/../common/lib/PriceRounder.php');
        $ret .= '<div class="container">';
        $rounder = new PriceRounder();
        $dbc = scanlib::getConObj();

        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }

        $dontCheck = array( 0, 0, 5, 6, 40, 52, 103, 104, 105, 107, 109, 111, 112, 123, 160, 184, 
            194, 195, 234, 237, 245, 247, 248, 250, 256, 265, 324, 549, 550, 666, 759, 799, 800, 
            852, 868, 869, 918, 919, 920, 958, 983, 984, 985, 917, 154, 155, 193, 197, 198, 199,
            211, 228);

        $dontCheck[] = '189'; //  Marlenes bulk inventory debacle.
        $dontCheck[] = '190'; //  Marlenes bulk inventory debacle.

        $query = $dbc->prepare("SELECT upc, brand, description, normal_price FROM products WHERE upc < 1000 AND scale = 0 GROUP BY upc;");
        $result = $dbc->execute($query);
        $data = array();
        $headers = array();
        $i = 0;
        $flag = array();
        while ($row = $dbc->fetch_row($result)) {
            foreach ($row as $k => $v) {
                if(!is_numeric($k)) {
                    $data[$i][$k] = $v;
                    $headers[$k] = $k;
                }
            }
            $i++;
        }

        //Add a flags
        $i = 0;
        $flags = array();
        foreach ($data as $k => $array) {
            if (in_array($array['upc'],$dontCheck)) {
                $flags['danger'][] = $i;
            }
            $i++;
        }
        $ret .=  '<h4>Bulk Product Scale Settings Check</h4>
            <p>If no table is drawn, there are no bulk products that need to be checked for scale settings.
            Products that appear in table should be investigated.</p>
            <p><span class="alert-info" style="font-weight: bold;">&nbsp;I.T. </span>&nbsp;if a new item was entered with
            a 3 digit PLU and it is not a scale item, you need to add it to the list of exceptions
            <span class="text-info">$dontCheck</span></p>';
        $ret .=  '<div class="panel panel-default" style="width:1000px"><table class="table table-striped">';
        $ret .=  '<thead>';
        foreach ($headers as $v) {
            $ret .=  '<th>' . $v . '</th>';
        }
        $ret .=  '</thead>';
        $prevKey = '1';
        $ret .=  '<tr>';
        $i=0;
        foreach ($data as $k => $array) {
            foreach ($array as $kb => $v) {
                if ($i!=0) $ret .=  '<td> ' . $v . '</td>';
            }
            $i++;
            if($prevKey != $k) {
                if (!in_array(($k+1),$flags['danger'])) {
                    $ret .=  '</tr><tr class="" style="">';
                } else {
                    $ret .=  '</tr><tr class="hidden">';
                }
            }
            $prevKey = $k;
        }
        $ret .=  '</table></div>';
        if ($dbc->error()) $ret .=  $dbc->error();
        $ret .= '</div>';

        return $ret;
    }

}
ScancoordDispatch::conditionalExec();
