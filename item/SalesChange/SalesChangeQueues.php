<?php 
include(__DIR__.'/../../config.php');
if (!class_exists('ScancoordDispatch')) {
        include(__DIR__.'/../../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
        include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}

class SalesChangeQueues extends scancoordDispatch 
{
    
    protected $title = "Batch Check";
    protected $ui = TRUE;

    public function css_content()
    {
        return <<<HTML
#addUpcForm {
    display: none;
}
#loading {
    background-color: purple;
    color: white;
    font-weight: bold;
    padding: 25px;
}
div.switchQContainer {
    padding: 20px;
    max-width: 500px;
}
.mobileMenu {
    width: 200px;
    top: 41px;
    left: 10px;
}
span.orange {
    color: lightblue;
}
span.aPage {
    color: pink;
}
div.aPage {
    text-align:right;
}
a.aPage:hover {
    text-decoration: none;
}
button.switchBtn {
    position: fixed;
    top: 0px;
    left: 10px;
    opacity: 0.8;
    width: 70px;
}
.minimizeMenuBtn {
    z-index: 999;
    position: absolute;
}
HTML;
    }

    public function switchQueue()
    {
        return <<<HTML
<div class="switchQContainer draggable">
    <button id="switchBtn" class="mobilePage switchBtn draggable" data-toggle="collapse" data-target="#switchQ">
        <div class="aPage">
            <span class="aPage">Qs<span class="caret"></span>&nbsp;&nbsp;</span>
        </div>
    </button>
    <form method="get">
        <div id="switchQ" class="mobileMenu collapse draggable">
            <div class="switchBlock">
                <a class="aPage" href="SalesChangeQueues.php?queue=1"><div class="mobilePage">Good</div></a>    
                <a class="aPage" href="SalesChangeQueues.php?queue=2"><div class="mobilePage">Notations</div></a>
                <a class="aPage" href="SalesChangeQueues.php?queue=8"><div class="mobilePage">Missing</div></a>
            </div>
            <div class="switchBlock">
                <a class="aPage" href="SalesChangeQueues.php?queue=0"><div class="mobilePage">Unchecked</div></a>
                <a class="aPage" href="SalesChangeQueues.php?queue=99"><div class="mobilePage">Additions</div></a>
                <a class="aPage" href="SalesChangeQueues.php?queue=98"><div class="mobilePage">Do Not Carry</div></a>
            </div>
            <div class="switchBlock">
                <a class="aPage" href="SalesChangeQueues.php?queue=7"><div class="mobilePage">Shelf Tags</div></a>
                <a class="aPage" href="SalesChangeQueues.php?queue=9"><div class="mobilePage">Generic Signs</div></a>
                <a class="aPage" href="http://192.168.1.2/git/fannie/reports/ItemBatches/ItemBatchesReport.php"><div class="mobilePage">Batch Item Hist.</div></a>
            </div>
            <div class="switchBlock">
                <a class="aPage" href="SalesChangeIndex.php"><div class="mobilePage">Batch Check Tools</div></a>
            </div>
        </div>
    </form>
</div>
HTML;
    }


    public function body_content()
    {
        include(__DIR__.'/../../config.php');

        $ret = "";
        $ret .= $this->switchQueue();
        $hillClass = ($_SESSION['store_id'] == 1) ? 'active' : '';
        $denClass = ($_SESSION['store_id'] == 2) ? 'active' : '';
        $ret .= "
            <div align=\"right\"><br/>
                <button class=\"btn btn-default $hillClass\" type=\"button\" 
                    onclick=\"changeStoreID(this, 1); 
                    return false; window.location.reload();\">Hillside</button>
                <button class=\"btn btn-default $denClass\" type=\"button\" 
                    onclick=\"changeStoreID(this, 2); 
                    return false; window.location.reload();\">Denfeld</button>
            </div>
        ";
        echo $ret;

        foreach ($_GET as $key => $value) {
            if ($key == 'queue') $thisQueue = $value;
        }
        if(isset($_POST['session'])) $_SESSION['session'] = $_POST['session'];
        $dbc = Scanlib::getConObj('SCANALTDB');
        $curQueue = $_GET['queue'];

        if($_SESSION['store_id'])
        {
            $this->draw_table($dbc);
        } else {
            echo '<h1 class="text text-danger" align="right">Select a store</h1>';
        }

        $this->addScript("/git/fannie/src/javascript/jquery.js");
        $this->addScript("/git/fannie/src/javascript/linea/cordova-2.2.0.js");
        $this->addScript("/git/fannie/src/javascript/linea/ScannerLib-Linea-2.0.0.js");
        $this->addScript("scanner.js");
        $this->addScript("SalesChangeQueues.js");

    }

    private function get_queue_name($dbc)
    {
        $queueNames = array();
        $prep = $dbc->prepare("select * from queues");
        $result = $dbc->execute($prep);
        while ($row = $dbc->fetch_row($result)) {
            $queueNames[$row['queue']] = $row['name'];
        }
        
        return $queueNames;
    }

    private function addUpc($dbc)
    {

        /*
        $queue = $_POST['curQueue'];
        $upc = ScanLib::upcParse($_POST['addUpc']);
        $store_id = $_POST['curStoreID'];
        $session = $_POST['curSession'];

        $args = array($queue, $upc, $store_id, $session);
        $prep = $dbc->prepare("INSERT INTO SaleChangeQueues (
            queue, upc, store_id, session, type
        ) values (
            ?, ?, ?, ?, 1 
        );");
        $dbc->execute($prep,$args);
        $error = ($er = $dbc->error()) ? $er : '';
        echo "<div class='alert alert-danger'>$error</div>";
        */

        return false;
    }

    private function draw_table($dbc)
    {
        include(__DIR__.'/../../config.php');
        if ($_POST['addUpc']) {
            $this->addUpc($dbc);
        }
        $curQueue = $_GET['queue'];
        $queueNames = array();
        $queueNames = $this->get_queue_name($dbc);
        
        $query = "SELECT session 
            FROM SaleChangeQueues
            GROUP BY session
            ;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $session[] = $row['session'];
        }
        print ('
            <div class="text-center container">
            <form method="post" class="form-inline">
                <select class="form-control" name="session">');
                    
        foreach ($session as $key => $sessID) {
            print '<option value="' . $sessID . '">' . $sessID . '</option>';
        }

        print ('
                </select>
                <input type="submit" class ="form-control" value="Change Session">
            </form>
            </div>');
            
        $id = $_SESSION['store_id'];
        $sess = $_SESSION['session'];
        $args = array($curQueue,$id,$sess);
        $prep = $dbc->prepare("
            SELECT q.queue, 
                    CASE WHEN u.brand IS NULL OR u.brand='' THEN p.brand ELSE u.brand END AS brand, 
                    CASE WHEN u.description IS NULL OR u.description='' THEN p.description ELSE u.description END as description,
                    q.upc, p.size, p.normal_price, ba.batchName,
                    p.special_price as price, ba.batchID, q.notes,
                    p.last_sold,
                    f.floorSectionID,
                    fs.name
                    FROM SaleChangeQueues as q
                        LEFT JOIN is4c_op.products as p on p.upc=q.upc AND p.store_id=q.store_id
                        LEFT JOIN is4c_op.productUser as u on u.upc=p.upc
                        LEFT JOIN is4c_op.batchList as bl on bl.upc=p.upc
                        LEFT JOIN is4c_op.batches as ba on ba.batchID=bl.batchID
                        LEFT JOIN is4c_op.FloorSectionProductMap as f on f.upc=p.upc
                        LEFT JOIN is4c_op.FloorSections as fs on fs.floorSectionID=f.floorSectionID
                    WHERE q.queue= ?
                        AND q.store_id= ?
                        AND q.session= ?
                    GROUP BY upc
                    ORDER BY fs.name, brand ASC
        ");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetch_row($res)) {
            $upc = $row['upc'];
            $upcs[$upc] = $row['upc'];
            $brand[$upc] = $row['brand'];
            $desc[$upc] = $row['description'];
            $queue[$upc] = $row['queue'];
            $size[$upc] = $row['size'];
            $price[$upc] = $row['price'];
            $notes[$upc] = $row['notes'];
            $last_sold[$upc] = $row['last_sold'];
            $upcLink[$upc] = "<a href='http://$FANNIEROOT_DIR/item/ItemEditorPage.php?searchupc=" 
                        . $row['upc'] 
                        . "&ntype=UPC&searchBtn=' class='blue' target='_blank'>{$row['upc']}
                        </a>";
            $floorSection[$upc] = $row['floorSectionID'];
            $floorSectionName[$upc] = $row['name'];
        }
        if ($dbc->error()) {
            echo $dbc->error(). "<br>";
        }
        
        $batch = array();
        $batchTypes = array();
        foreach ($upcs as $upc) {
            $prep = $dbc->prepare("
                SELECT 
                    ba.batchName,
                    ba.batchID,
                    ba.batchType
                FROM is4c_op.batches AS ba 
                LEFT JOIN is4c_op.batchList AS bl ON ba.batchID=bl.batchID
                WHERE bl.upc = ?
                    AND CURDATE() BETWEEN ba.startDate AND ba.endDate;
            ");
            $res = $dbc->execute($prep,$upc);
            while ($row = $dbc->fetch_row($res)) {
                $batchName[$upc] = $row['batchName'];
                $batchTypes[$upc] = $row['batchType'];
                $batch[$upc] = "<a href='http://$FANNIEROOT_DIR/batches/newbatch/EditBatchPage.php?id="
                . $row['batchID'] . "' target='_blank'>" . $row['batchName'] . "</a>";
            }
        }
        if ($dbc->error()) {
            echo $dbc->error(). "<br>";
        }
        echo "<div id='loading' align='center'><i>Please wait while this page is loading<span id='animate-load'>...</span></i></div>";
        
        echo "<h1 align='center'>".ucwords($queueNames[$curQueue])."</h1>";
        if (!isset($curQueue)) echo "<h1 align='center'>Home Page (No Queue Selected)</h1>";
        
        echo "<div align='center'>";
        if ($_SESSION['store_id'] == 1) {
            echo "<strong style='color:purple'>Hillside</strong><br>";
        } else {
            echo "<strong style='color:purple'>Denfeld</strong><br>";
        }
        echo "<span style='color:purple'>" . $_SESSION['session'] . "</span>";
        echo "</div>";
        echo "<p align='center'><span id='countUpcs'>" . count($upcs) . "</span> tags in this queue</p>";
        if ($curQueue == 0) {
            echo '
                <form method="post" id="addUpcForm">
                  <input type="hidden" name="queue" value="0">
                  <input type="hidden" name="rmDisco" value="1">
                </form>
            ';
        }

        echo '<a href="" onclick="$(\'#cparea\').show(); return false;">Copy/paste</a>';
        echo '<textarea id="cparea" class="collapse">';
        echo implode("\n", $upcs);
        echo '</textarea>';
        echo ' | <a href="SalesChangeQueues.php?rmOtherSales=1&queue=0"
            onclick="return confirm(\'Are you sure?\')">Remove Non Co-op Deals Sale Items from List</a> | ';
        echo '<a href="#" id="collapseLoc">Show/Hide Locations</a> | ';
        echo '<a href="#" onClick="hideUnsold(); return false;">Hide Unsold Items</a> | ';
        echo '<span id="clickToShowForm"><button onClick="addUpcForm(); return false;">+</button> Add Item To <b>'.$queueNames[$curQueue].'</b></span>';
            //echo '<span id="addNoteText"><button onClick="addNote(); return false;">+</button> Add Note To <b>'.$queueNames[$curQueue].'</b></span>';
        $btn = ($curQueue == 2) 
            ? '<span id="addNoteText"><button onClick="addNote(); return false;">+</button> Add Note To <b>'.$queueNames[$curQueue].'</b></span>'
            : "<button onClick='submitAddUpc()' class=' btn btn-default' href='#'>+</button>";
        echo "
            <span id='addUpcForm'>
                <input class='' type='text' name='addUpc' id='addUpcUpc' value=''>
                <input class='form-control' type='hidden' name='curQueue' id='addUpcQueue' value='$curQueue'>
                <input class='form-control' type='hidden' name='curSession' id='addUpcSession' value='".$_SESSION['session']."'>
                <input class='form-control' type='hidden' name='curStoreID' id='addUpcStoreID' value='".$_SESSION['store_id']."'>
                $btn
            </span>
        ";
        
        if ($_GET['rmOtherSales'] == 1) {
            $this->removeAddBatches($dbc,$batchTypes);
            echo "
<script type='text/javascript'>window.location.reload(); return false; </script>
            ";
        }

        echo "<table class=\"table table-striped\">";
        echo "<thead style='display: hidden'>
              <th>Brand</th>
              <th>Name</th>
              <th>Size</th>
              <th>Price</th>
              <th>UPC</th>
              <th>Batch</th>
              <th class='loc' id='locTh'>Location</th>
              <th></th><th></th><th></th></thead>";
        foreach ($upcs as $upc => $v) {
            if ($upc >= 0) {
                echo "<tr id='id$upc'><td>" . $brand[$upc] . "</td>"; 
                echo "<td>" . $desc[$upc] . "</td>"; 
                echo "<td>" . $size[$upc] . "</td>"; 
                echo "<td>" . $price[$upc] . "</td>"; 
                echo "<td>" . $upcLink[$upc] . "</td>"; 
                echo "<td>" . $batch[$upc] . "</td>";
                echo "<td class='loc'>".$floorSectionName[$upc]."</td><td class='loc-input'></td>";
                if ($curQueue == 2) echo "<td><strong>" . $notes[$upc] . "</strong></td>";
                if ($curQueue == 0) {
                    $lastsold = $last_sold[$upc];
                    $dateDiff = scanLib::dateDistance($lastsold);
                    $class = ($dateDiff >= 31 || !$lastsold) ? "red" : "";

                    $wIcon = '<img src="../../common/src/img/warningIcon.png">';
                    $lastsold = substr($lastsold,0,10);
                    echo ($lastsold) ? "<td class='$class'>$lastsold</td>" 
                        : "<td class='$class'><i>no sales</i></td>";

                }        
                
                if ($curQueue == 7) {
                    echo "<td><a class=\"btn btn-default\" 
                         href=\"http://$FANNIEROOT_DIR/item/handheld/ItemStatusPage.php?id={$upc}\" target=\"_blank\">status check</a></tr>";  
                } else {
                    echo "<td><button class=\"btn btn-default\" type=\"button\" 
                        onclick=\"sendToQueue(this, '{$upc}', 1, '{$_SESSION['session']}', '{$curQueue}'); return false;\">Good</button></td>";    
                    echo "<td><button class=\"btn btn-default\" type=\"button\" 
                        onclick=\"sendToQueue(this, '{$upc}', 8, '{$_SESSION['session']}', '{$curQueue}'); return false;\">Missing</button></td>";  
                    echo "<td><button class=\"btn btn-default\" type=\"button\" 
                        onclick=\"sendToQueue(this, '{$upc}', 98, '{$_SESSION['session']}', '{$curQueue}'); return false;\">DNC</button></td>";    
                }
            }
        }
        echo "</table>";

    } 

    private function removeAddBatches($dbc,$batchTypes)
    {
        
        $session = $_SESSION['session'];
       
        $upcs = array();
        foreach ($batchTypes as $upc => $batchType) {
            if ($batchType != 1) {
                $upcs[] = $upc;
            }
        }
        
        list($inClause,$args) = $dbc->safeInClause($upcs);
        $updateQ = 'UPDATE SaleChangeQueues SET queue = 1 WHERE upc IN ('.$inClause.')';
        $prep = $dbc->prepare($updateQ);
        $dbc->execute($prep,$args);
        if ($dbc->error()) {
            echo '<div class="alert alert-danger">'.$dbc->error().'</div>';
        } else {
            echo '<div class="alert alert-success">Items Removed from List. 
                Refresh the page by clicking \'Unchecked\' to reload the 
                list with products removed.</div>';
        }
        
        return false;
        
    }

}
scancoordDispatch::conditionalExec();
