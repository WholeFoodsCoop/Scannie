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
            $size = $pocket-1;
            if ($pocket > 30 && $pocket < 99) {
                $ret .= "starting upc: $upc, pocket: $pocket<span class='size' style='width: $size;'></span><br/>";
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
<div class="container-fluim">
$ret
</div>
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
HTML;
    }

}
WebDispatch::conditionalExec();
