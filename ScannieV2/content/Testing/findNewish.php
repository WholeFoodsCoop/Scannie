<?php
include(__DIR__.'/../../config.php');
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}

class findNewish extends PageLayoutA
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
        $upcs = array();
        $range1 = $_GET['range1'];
        $range2 = $_GET['range2'];
        $department = $_GET['department'];
        echo <<<HTML
<form method="get">
    <input type="number" name="range1" id="range1" value="$range1"/>
    <input type="number" name="range2" id="range2" value="$range2"/>
    <input type="number" name="department" id="department" value="$department"/>
    <button type="submit">Submit</button>
</form>
<div>
    <audio id="audio" autoplay>
        <source id="horseSound" src="horse.ogg" type="audio/ogg">
    </audio>
</div>
HTML;

        $dbc = scanLib::getConObj();
        $args = array();
        $args[] = $department;
        $prep = $dbc->prepare("
            SELECT p.upc
            FROM products AS p
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE m.superID = ? 
                AND store_id = 1 
                AND inUse = 1;
        ");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $upcs[] = $row['upc'];
        }
        $upctotal = count($upcs);
        echo "all inUse products at Hillside ATM: $upctotal<br/>";
        echo "<input type='hidden' name='upctotal' id='upctotal' value='$upctotal'\>";
        
        $newItems = array();
        $dbc->startTransaction();
        foreach ($upcs as $k => $upc) {
            if ($k >= $range1 && $k <= $range2) {
                $args = array($upc);
                // this doesn't work because dbc not choosing the max-row lower than 
                // the date given.
                $prep = $dbc->prepare("SELECT upc, inUse FROM prodUpdate WHERE upc = ? 
                    AND modified < '2018-02-01 00:00:00' AND storeID = 1 
                    ORDER BY modified DESC LIMIT 1");
                $res = $dbc->execute($prep,$args);
                $row = $dbc->fetchRow($res);
                $inUse = $row['inUse'];
                $upc = $row['upc'];
                // echo "$inUse<br/>";
                if ($inUse == 0) {
                    $newItems[] = $upc;
                }
                
            }
        }
        $dbc->commitTransaction();
        echo "items that returned in February 2018: ".count($newItems)."<br/>";

        if (count($newItems) > 0) {
            foreach ($newItems as $k => $upc) {
                $out = "$upc\r\n";
                file_put_contents('file.txt',$out,FILE_APPEND);
            }
        }
        
        return <<<HTML
        no fatal errors
HTML;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
var upctotal = $('#upctotal').val();
upctotal = parseInt(upctotal, 10);
var dept = $('#department').val();
var r1 = $('#range1').val();
var r2 = $('#range2').val();
r1 = parseInt(r1,10);
r2 = parseInt(r2,10);
var range = r2 - r1;
var r3 = r1 + range; 
var r4 = r2 + range;
$(document).ready(function(){
    if (r3 <= upctotal) {
        window.location.href = "http://key/scancoord/ScannieV2/content/Testing/findNewish.php?department="+dept+"&range1="+r3+"&range2="+r4;
    } else {
        alert('Scan Complete!');
    }
});
JAVASCRIPT;
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
