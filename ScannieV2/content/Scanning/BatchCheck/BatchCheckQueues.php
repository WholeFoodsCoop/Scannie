<?php 
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../../common/sqlconnect/SQLManager.php');
}

class BatchCheckQueues extends PageLayoutA 
{
    
    protected $title = "Batch Check";
    protected $ui = TRUE;
    protected $options = array(
        0 => 'Unchecked',
        1 => 'Good',
        2 => 'Miss',
        3 => 'Note',
        4 => 'Add',
        5 => 'Shelf-Tag',
        6 => 'Generic-Signs',
        9 => 'Disco/Supplies Last',
        11 => 'Edited',
        99 => 'Main Menu',
    );

    public function preprocess()
    {
        $dbc = scanLib::getConObj();
        if (FormLib::get('loginSubmit', false)) {
            $this->loginSubmitHandler($dbc);
        }
        $sessionName = $_SESSION['sessionName'];
        $storeID = $_SESSION['storeID'];
        if ($sessionName && $storeID) {
            $this->displayFunction = $this->view($sessionName,$storeID);
        } else {
            $this->displayFunction = $this->loginView();
        }

        return false;
    }

    private function loginSubmitHandler($dbc)
    {
        $resume = FormLib::get('resumeSession');
        $new = FormLib::get('newSession');
        $storeID = FormLib::get('storeID');
        $session = (isset($resume)) ? $resume : $new;

        $args = array($session);
        $prep = $dbc->prepare("SELECT session FROM woodshed_no_replicate.batchCheckQueues WHERE session = ?");
        $res = $dbc->execute($prep,$args);
        $sessIsSet = $dbc->numRows($res);
        if ($sessIsSet > 0) {
            $_SESSION['sessionName'] = $session;
        } else {
            $args = array($new,$storeID);
            $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckQueues (session,storeID) values (?,?);");
            $res = $dbc->execute($prep,$args);
            $_SESSION['sessionName'] = $new;
        }
        $_SESSION['storeID'] = $storeID;
        $json = array();
        $json['error'] = $dbc->error();

        //echo json_encode($json);
        return false;
    }

    private function loginView()
    {
        $dbc = scanLib::getConObj();
        $sessions = ''; 
        $prep = $dbc->prepare("SELECT session FROM woodshed_no_replicate.batchCheckQueues GROUP BY session;");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $s = $row['session'];
            $sessions .= "<option value='$s'>$s</option>"; 
        }

        return <<<HTML
<div align="center">
    <form name="login" id="login" method="post">
        <h3>Batch Check Sign-In</h3>
        <p>Please select a Session & Store ID -OR-
            create a new Session by entering a Session Name.</p>
        <div class="form-group">
            <select class="loginForm" name="resumeSession" id="session">
                <option value="0">Resume a Session</option>
                $sessions
            </select>
            <input class="loginForm" name="newSession" type="text" placeholder="Name a New Session">
        </div>
        <div class="form-group">
            <select class="loginForm" name="storeID" required>
                <option value="0">Select a Store ID</option>
                <option value="1">Hillside</option>
                <option value="2">Denfeld</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" name="loginSubmit" value="1" class="loginForm">Submit</button>
        </div>
    </form>    
</div>
HTML;
    }

    private function getTableContents($dbc)
    {
        $option = FormLib::get('option');
        $sessionName = $_SESSION['sessionName'];
        $storeID = $_SESSION['storeID'];

        //get all data for products on sale
        $args = array($storeID);
        $query = "
            SELECT bl.upc, bl.salePrice, bl.batchID AS bid, p.brand AS pbrand, 
                p.description AS pdesc, pu.brand AS pubrand, p.size, p.special_price, 
                pu.description AS pudesc, b.batchName, f.sections 
            FROM batchList AS bl 
                LEFT JOIN products AS p ON bl.upc=p.upc 
                LEFT JOIN productUser AS pu ON p.upc=pu.upc 
                LEFT JOIN batches AS b ON bl.batchID=b.batchID 
                INNER JOIN FloorSectionsListView AS f ON p.store_id=f.storeID AND p.upc=f.upc
            WHERE bl.batchID IN ( SELECT b.batchID FROM batches AS b WHERE NOW() BETWEEN startDate AND endDate) 
                AND f.storeID = ?
            GROUP BY p.upc ORDER BY f.sections
        ";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep,$args);
        $fields = array('upc','salePrice','bid','pbrand','pubrand','pdesc','pudesc','size','special_price',
            'batchName','sections');
        $hiddenContent = '';
        foreach ($fields as $field) {
            $hiddenContent .= "<button class='col-filter btn btn-info' id='col-filter-$field'>$field</button>";
        }
        $hiddenContent .= "
            <input type='hidden' id='sessionName' name='sessionName' value='{$_SESSION['sessionName']}'>
            <input type='hidden' id='storeID' name='storeID' value='{$_SESSION['storeID']}'>
        ";
        while ($row = $dbc->fetchRow($res)) {
            foreach ($fields as $field) {
                ${$field}[$row['upc']] = $row[$field];
            }
        }

        //additional query to limit results shown
        $inQueueItems = array();
        if ($option != 0) {
            $args = array($sessionName,$storeID,$option);
            $prep = $dbc->prepare("SELECT upc FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND storeID = ? AND inQueue = ?");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $inQueueItems[] = $row['upc'];
            }
        } else {
            $args = array($sessionName,$storeID);
            $prep = $dbc->prepare("SELECT upc FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND storeID = ?");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $inQueueItems[] = $row['upc'];
            }
        }

        $thead = '';
        foreach ($fields as $field) {
            $thead .= "<th class='col-hide col-$field'>$field</th>";
        }
        $queueBtns = array(0,1,2);
        foreach ($queueBtns as $qv) {
            $thead .= "<th class='col-{$this->options[$qv]}'>{$this->options[$qv]}</th>";
        }
        $table = "<div class='table-responsive'><table class='table table-stiped table-compressed small'><thead>$thead</thead><tbody>";
        //this is what will be different based on queues
        $r = 1;
        foreach ($upc as $k => $v) {
            if ($option == 0 && !in_array($k,$inQueueItems)) {
                $table .= ($r % 2 == 0) ? "<tr>" : "<tr class='altRow'>";
                $table .= "<td class='col-upc'>$k</td>";
                foreach ($fields as $field) {
                    if ($field != 'upc') {
                        $temp = ${$field}[$k];
                        $table .= "<td class='col-$field'>$temp</td>";
                    }
                }
                foreach ($queueBtns as $qv) {
                    $table .= "<td><button id='queue$k' value='$qv' class='queue-btn btn btn-info'>{$this->options[$qv]}</button></td>";
                }
                $table .= "</tr>";
                $r++;
            } elseif (in_array($k,$inQueueItems)) {
                $table .= ($r % 2 == 0) ? "<tr>" : "<tr class='altRow'>";
                $table .= "<td>$k</td>";
                foreach ($fields as $field) {
                    if ($field != 'upc') {
                        $temp = ${$field}[$k];
                        $table .= "<td class='col-$field'>$temp</td>";
                    }
                }
                foreach ($queueBtns as $qv) {
                    $table .= "<td><button id='queue$k' value='$qv' class='queue-btn btn btn-info'>{$this->options[$qv]}</button></td>";
                }
                $table .= "</tr>";
                $r++;
            }
        }
        $table .= "</tbody></table></div>";

        if ($er = $dbc->error()) {
            return "<div class='alert alert-danger'>$er</div>";
        } else {
            return $hiddenContent.$table;
        }

    }

    private function view($sessionName,$storeID)
    {
        include(__DIR__.'/../../../config.php');

        $ret = "";
        $ret .= $this->queueToggle();
        $stores = array(1=>'Hillside',2=>'Denfeld');
        $ret .= "<div align='center'>";
        $ret .= "<h2>$stores[$storeID]</h2>";
        $ret .= "<h1>$sessionName</h1>";
        $ret .= "</div>";

        foreach ($_GET as $key => $value) {
            if ($key == 'queue') $thisQueue = $value;
        }
        if(isset($_POST['session'])) $_SESSION['session'] = $_POST['session'];
        $dbc = Scanlib::getConObj();
        $curQueue = $_GET['queue'];

        $table = $this->getTableContents($dbc);

        $timestamp = time();
        $this->addScript("SalesChangeQueues.js?time=".$timestamp);
        $this->addScript("batchCheckQueues.js?time=".$timestamp);

        return <<<HTML
$ret
$table
HTML;
    }

    public function queueToggle()
    {
        $options = '';
        foreach ($this->options as $id => $name) {
            $options .= "<div align='center'><button type='submit' class='btn-primary toggle-btn' name='option' value='$id'><div class='mobilePage'>$name</div></a></div>";
        }
        return <<<HTML
<div class="switchQContainer">
    <button id="switchBtn" class="mobilePage switchBtn draggable" data-toggle="collapse" data-target="#switchQ">
        Qs
    </button>
    <form method="get">
        <div id="switchQ" class="toggle-container collapse draggable">
                $options
            <button id="" class="close close-btn" data-toggle="collapse" data-target="#switchQ">close</button>
        </div>
    </form>
</div>
HTML;
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

    public function cssContent()
    {
        return <<<HTML
h2, h1 {
    color: rgba(255,255,255,0.8);
    text-shadow: 1px 1px rgba(0,0,0,0.5);
}
.highlighted {
    background-color: blue;
    z-index: 155;
}
.altRow {
    background-color: orange;
    background: orange;
    color: rgba(0,0,0,0.7);
}
.col-filter {
    display: none;
    margin-right: 5px;
}
.close-btn {
    margin-right: 10px;
}
.toggle-btn {
    margin-top: 5px;
    width: 100%;
    border: rgba(255,255,255,0.1);
    //background-color: rgba(255,255,255,0.3); 
    padding: 5px;
    background-color: #0069D9;
    font-weight: bold;
    color: white; 
    border-bottom-right-radius: 1px;
    border-top-right-radius:1px;
    z-index: 151;
    text-shadow: 1px 1px rgba(0,0,0,0.5);
}
.toggle-btn:hover {
    background-color: #458FD8;
}
.toggle-container {
    position: fixed;
    top: 0px;
    left: 0px;
    width: 300px;
    //background-color: rgba(0,55,255,0.99);
    background-color: #005AB5;
    border-right: 4px solid #005AB5;
    border-bottom: 4px solid #005AB5;
    border-bottom-right-radius: 1%;
    z-index: 151;
}

table, th, tr, td {
    background-color: rgba(255,255,255,0.89);
    border: 2px solid transparent;
}
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
    top: 25px;
    left: 0px;
    opacity: 0.8;
    width: 70px;
    border-left: none;
    border-top-right-radius:3px;
    border-bottom-right-radius:3px;
    //background-color: rgba(155,155,255,0.6);
    background-color: #0069D9;
    //color: rgba(0,0,255,0.6);
    color: white;
    font-weight: bold;
    //border-color: rgba(155,155,255,0.7);
    border-color: #005AB5;
    z-index: 150;
    text-shadow: 1px 1px rgba(0,0,0,0.5);
}
.minimizeMenuBtn {
    z-index: 999;
    position: absolute;
}
html, body {
    //background-color: rgba(230,230,255,1);
    //background: rgba(230,230,255,1);
}
HTML;
    }

}
WebDispatch::conditionalExec();
