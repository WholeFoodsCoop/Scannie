<?php
include(__DIR__.'/../../config.php');
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
/*
**  @class PluRanges
**  find "pockets" of null, potential new UPCs
*/
class PluRanges extends PageLayoutA
{
    public function preprocess()
    {
        $this->displayFunction = $this->pageContent();

        return false;
    }

    public function pageContent()
    {
        $ret = '';
        include(__DIR__.'/../../config.php');

        $dbc = scanLib::getConObj();

        $prep = $dbc->prepare("select upc from products WHERE upc LIKE '002%000000'");
        $res = $dbc->execute($prep);
        $upcs = array();
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            //echo "$upc<br/>";
            $upcs[] = $upc;
        }

        $pockets = array();
        foreach ($upcs as $k => $upc) {
            $upc = substr($upc,2,5);
            $next = substr($upcs[$k+1],2,5);
            $diff = $next - $upc;
            $diff--;
            if ($diff > 0) {
                $pockets[$upc] = $diff;
            }
        }
        foreach ($pockets as $upc => $pocket) {
            //echo "$upc: $pocket<br/>";
            $size = $pocket-1;
            if ($pocket > 30 && $pocket < 99) {
                echo "starting upc: $upc, pocket: $pocket<span class='size' style='width: $size;'></span><br/>";
            }
        }
        /*
        $all = array();
        $total = 0;
        for ($i = 1; $i < 9999; $i++) {
            $upc_str = str_pad($i,4,"0",STR_PAD_LEFT);
            $upc = "002".$upc_str."000000";
            if (!in_array($upc,$upcs)) {
                $total++;
                //echo "$upc<br/>";
            }
        }
        echo "TOTAL OPEN PLUS: $total<br/>";
        */
        
        return <<<HTML
        no fatal errors
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
HTML;
    }

    public function cssContent()
    {
        return <<<HTML
p {
    margin-top: 50px;
    font-size: 38px;
    color: white;
    color: rgba(0,0,0,0.2);
}
a {
    color: lightgreen;
}
div {
    font-size: 42px;
    color: rgba(0,0,0,0.2);
}
span.size {
    display: inline-block;
    border: 1px solid white;
    height: 25px;
    width: 100px;
}
HTML;
    }

}
WebDispatch::conditionalExec();
