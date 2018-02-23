<?php
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../../common/sqlconnect/SQLManager.php');
}

class SCS extends PageLayoutA
{
    protected $title = "Sales Change Scanner";
    protected $description = "[Sales Change Scanner] is the portion of 
        batch check tools used for scanning barcodes.";
    protected $ui = FALSE;
    protected $enable_linea = true;
    protected $data = array();
    protected $batches = array();
    protected $queues = array();

    public function preprocess()
    {
        include(__DIR__.'/../../../config.php');

        $dbc = scanLib::getConObj(); 
        if (FormLib::get('signout', false)) {
            session_unset();
            $this->addOnloadCommand('window.location.href = "SCS.php"');
        }
        if (FormLib::get('edit', false)) {
            $this->editHandler($dbc);
            die();
        } elseif (FormLib::get('queue', false)) {
            $this->queueHandler($dbc); 
            die();
        } elseif (FormLib::get('removeQueue', false)) {
            $this->removeQueueHandler($dbc); 
            die();
        } elseif (FormLib::get('forceBatch', false)) {
            //method to force a batch goes here.
            die(); 
        } elseif (FormLib::get('loginSubmit', false)) {
            $this->loginSubmitHandler($dbc);
        }
        if (FormLib::get('upc', false)) {
            $this->getProdData($dbc);
            $this->getQueueData($dbc);
        }

        //$this->testData();
        if (!$_SESSION['sessionName'] || !$_SESSION['storeID']) {
            $this->displayFunction = $this->loginView($dbc);   
        } else {
            $this->displayFunction = $this->view($dbc);
        }

        return false;
    }

    private function removeQueueHandler($dbc)
    {
        $qid = FormLib::get('qid');
        $args = array($qid);
        $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckQueues WHERE id = ?");
        $res = $dbc->execute($prep,$args);
        $json = array();
        $json['error'] = $dbc->error();

        echo json_encode($json);
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

    private function loginView($dbc)
    {
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
        <p>Please select a Session & Store ID or
            create a new Session</p>
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

    private function queueHandler($dbc)
    {
        $upc = FormLib::get('upc');
        $queue = FormLib::get('queue');
        $qval = FormLib::get('qval');
        $sessionName = $_SESSION['sessionName'];
        $storeID = $_SESSION['storeID'];

        //check how upc is curently queued
        $inQueues = array();
        $args = array($sessionName,$upc);
        $prep = $dbc->prepare("SELECT inQueue FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND upc = ?");
        $res = $dbc->execute($prep,$args); 
        while ($row = $dbc->fetchRow($res)) {
            $inQueues[] = $row['inQueue'];
        }
        switch($queue) {
            case 'Unchecked':
                //needed for the BatchCheckQueues
                //if in 'good','missing' delete row, if does not exist, do nothing.
                if (in_array(1,$inQueues)) {
                    $args = array($upc,$sessionName,$storeID);
                    $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckQueues 
                        WHERE inQueue = 1 AND upc = ? AND session = ? AND storeID = ?");
                    $dbc->execute($prep,$args);
                } elseif (in_array(2,$inQueues)) {
                    $args = array($upc,$sessionName,$storeID);
                    $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckQueues 
                        WHERE inQueue = 1 AND upc = ? AND session = ? AND storeID = ?");
                    $dbc->execute($prep,$args);
                }
                break;
            case 'Good':
                if (in_array(1,$inQueues)) {
                    //do nothing
                } elseif (in_array(2,$inQueues)) {
                    //update
                    $args = array($upc,$sessionName,$storeID);
                    $prep = $dbc->prepare("UPDATE woodshed_no_replicate.batchCheckQueues 
                        SET inQueue = 1 WHERE inQueue = 2 AND upc = ? AND session = ? AND storeID = ?");
                    $dbc->execute($prep,$args);
                } else {
                    //insert
                    $args = array($upc,$sessionName,$storeID,1);
                    $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckQueues 
                        (upc,session,storeID,inQueue) VALUES (?,?,?,?)");
                    $dbc->execute($prep,$args);
                }
                break;
            case 'Cap':
            case '12UP':
            case '4UP':
            case '2UP':
                //always insert
                $args = array($upc,$sessionName,$storeID,$qval);
                $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckQueues 
                    (upc,session,storeID,inQueue) VALUES (?,?,?,?)");
                $dbc->execute($prep,$args);
                break;
            case 'Miss':
                if (in_array(1,$inQueues)) {
                    //change queue to 2
                    $args = array($upc,$sessionName,$storeID);
                    $prep = $dbc->prepare("UPDATE woodshed_no_replicate.batchCheckQueues 
                        SET inQueue = 2 WHERE inQueue = 1 AND upc = ? AND session = ? AND storeID = ?");
                    $dbc->execute($prep,$args);
                } elseif (in_array(2,$inQueues)) {
                    //do nothing
                } else {
                    //insert
                    $args = array($upc,$sessionName,$storeID,2);
                    $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckQueues 
                        (upc,session,storeID,inQueue) VALUES (?,?,?,?)");
                    $dbc->execute($prep,$args);
                }
                break;
            case 'Add':
                if (in_array(4,$inQueues)) {
                } else {
                    $args = array($upc,$sessionName,$storeID,4);
                    $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckQueues 
                        (upc,session,storeID,inQueue) VALUES (?,?,?,?)");
                    $dbc->execute($prep,$args);
                }
                break;
            case 'Tag':
                if (in_array(5,$inQueues)) {
                } else {
                    $args = array($upc,$sessionName,$storeID,5);
                    $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckQueues 
                        (upc,session,storeID,inQueue) VALUES (?,?,?,?)");
                    $dbc->execute($prep,$args);
                }
                break;
        }

        $json = array();
        $json['error'] = $dbc->error();
        //return current list of queues to refresh Current Queues: on screen.
        $this->getQueueData($dbc);
        foreach ($this->queues as $k => $q) {
            $json['queues'][$k] = $q;
        }

        echo json_encode($json);
        return false;
    }

    private function editHandler($dbc)
    {
        $upc = FormLib::get('upc');
        $newValue = FormLib::get('newValue');
        $field = FormLib::get('edit');
        $field = substr($field,4); 
        $sessionName = $_SESSION['sessionName'];
        function switchResult($field,$newValue,$upc,$sessionName,$dbc) {
            switch($field) {
                case 'brand':
                case 'description':
                    return 'productUser';          
                case 'size':
                    return 'products';
                case 'notes':
                    $tempTable = 'woodshed_no_replicate.batchCheckNotes';
                    $args = array($upc);
                    $query = "SELECT notes FROM $tempTable WHERE upc = ?";
                    $prep = $dbc->prepare($query);
                    $res = $dbc->execute($prep,$args);
                    $rows = $dbc->numRows($res);
                    if ($rows == 0) {
                        $args = array($newValue,$upc,$sessionName);
                        $query = "INSERT INTO $tempTable (notes,upc,session) values (?,?,?);";
                        $prep = $dbc->prepare($query);
                        $res = $dbc->execute($prep,$args);
                    }
                    return $tempTable; 
            }
        }
        $table = switchResult($field,$newValue,$upc,$sessionName,$dbc);
        //editdescription
        $args = array($newValue,$upc);
        $query = "UPDATE $table SET $field = ? WHERE upc = ?";
        if ($field == 'notes') {
            $query .= " AND session = ?";
            $args[] = $sessionName;
        }
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep,$args);
        $json = array();
        $json['error'] = $dbc->error();

        //add to queue 11 (shared) so all users know sign info was changed. 
        //do this for all storeIDs
        $inQueues = array();
        $args = array($sessionName,$upc);
        $prep = $dbc->prepare("SELECT inQueue FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND upc = ?");
        $res = $dbc->execute($prep,$args); 
        while ($row = $dbc->fetchRow($res)) {
            $inQueues[] = $row['inQueue'];
        }
        $stores = array(1,2);
        foreach ($stores as $storeID) {
            if (in_array(11,$inQueues)) {
                //do nothing
            } else { 
                //insert
                $args = array($upc,$sessionName,$storeID,11);
                $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckQueues 
                    (upc,session,storeID,inQueue) VALUES (?,?,?,?)");
                $dbc->execute($prep,$args);
                $json['error'] = $dbc->error();
            }
        }

        echo json_encode($json);
        return false;
    }

    public function testData()
    {
        foreach ($this->data as $k => $v) {
            foreach ($v as $va) {
                foreach ($va as $vb) {
                    echo $vb;
                }
            }
        }
    }
    
    private function getProdData($dbc)
    {
        $upc = FormLib::get('upc');
        $upc = scanLib::padUPC($upc);
        $args = array($upc);
        $prep = $dbc->prepare("SELECT bl.upc, bl.salePrice, bl.batchID AS bid, p.brand AS pbrand, p.description AS pdesc, pu.brand AS pubrand, p.size, p.special_price, pu.description AS pudesc, b.batchName, f.sections FROM batchList AS bl LEFT JOIN products AS p ON bl.upc=p.upc LEFT JOIN productUser AS pu ON p.upc=pu.upc LEFT JOIN batches AS b ON bl.batchID=b.batchID INNER JOIN FloorSectionsListView AS f ON p.upc=f.upc WHERE bl.batchID IN ( SELECT b.batchID FROM batches AS b WHERE NOW() BETWEEN startDate AND endDate) AND bl.upc = ? GROUP BY bl.batchID;");
        $res = $dbc->execute($prep,$args);
        $rows = $dbc->numRows($res);
        if ($rows > 0) {
            $fields = array('upc','salePrice','bid','pbrand','pubrand','pdesc','pudesc','size',
                'special_price','batchName','sections');
            while ($row = $dbc->fetchRow($res)) {
                foreach ($fields as $field) {
                    ${$field} = $row[$field];
                    $this->data[$upc][$field] = $row[$field];
                }
                $this->batches[$bid]['name'] = $batchName;
                $this->batches[$bid]['saleprice'] = $salePrice;
            }
        } else {
            $args = array($upc);
            $prep = $dbc->prepare("SELECT p.upc, p.brand AS pbrand, p.description AS pdesc, p.size, p.special_price, pu.brand AS pubrand, pu.description AS pudesc, f.sections FROM products AS p LEFT JOIN productUser AS pu ON pu.upc=p.upc INNER JOIN FloorSectionsListView AS f ON p.upc=f.upc WHERE p.upc = ? GROUP BY p.upc;"); 
            $res = $dbc->execute($prep,$args);
            $fields = array('upc','brand','pbrand','pdesc','pudesc','pubrand','size','special_price','sections');
            while ($row = $dbc->fetchRow($res)) {
                foreach ($fields as $field) {
                    ${$field} = $row[$field];
                    $this->data[$upc][$field] = $row[$field];
                }
            }
        }

        return false; 
    }

    /*
        not in use yet. First, we need the ability to add items to queues.
    */
    private function getQueueData($dbc)
    {
        unset($this->queues);
        $upc = FormLib::get('upc');
        $upc = scanLib::padUPC($upc);
        $sessionName = $_SESSION['sessionName'];
        $storeID = $_SESSION['storeID'];
        $args = array($upc,$sessionName,$storeID);
        $queues = array();
        $prep = $dbc->prepare("SELECT inQueue, id FROM woodshed_no_replicate.batchCheckQueues 
            WHERE upc = ? AND session = ? AND storeID = ?");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $this->queues[$row['id']] = $row['inQueue'];
        }

        return false;
    }

    public function view($dbc)
    {
        $upc = FormLib::get('upc');
        if ($upc < 999999 && $upc > 99999) {
            echo "hi";
            $upc = ltrim($upc, '0');
            $upc = $this->skuToUpc($upc);
        }
        $upc = scanLib::padUPC($upc);
        $store = '<i>no store selected</i>';
        $stores = array(1=>'[H]',2=>'[D]');
        $store = $stores[$_SESSION['storeID']];
        $storeID = $_SESSION['storeID'];
        $session = '<i>no session selected</i>';
        $session = $_SESSION['sessionName'];
        $name = (!is_null($this->data[$upc]['pudesc'])) ? $this->data[$upc]['pudesc'] : $this->data[$upc]['pdesc'];
        $brand = (!is_null($this->data[$upc]['pubrand'])) ? $this->data[$upc]['pubrand'] : $this->data[$upc]['pbrand'];
        $retQueued = '';
        $size = $this->data[$upc]['size']; 
        $batch = $this->data[$upc]['batchName']; 
        $sale = '$'.$this->data[$upc]['salePrice'];
        $numBatches = count($this->batches);
        $editFields = array('name','brand','size');
        $notes = 'n/a';

        $location = 'n/a';
        $location = $this->data[$upc]['sections'];

        $args = array($upc);
        //replace this - notes should be gathered at the same time queues info is gathered.
        //and should be called here via object variables. 
        $prep = $dbc->prepare("SELECT notes FROM woodshed_no_replicate.batchCheckNotes WHERE upc = ?");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $notes = $row['notes'];
        }
        foreach ($editFields as $field) {
            if (!${$field}) ${$field} = 'n/a';
        }
        foreach ($this->queues as $id => $q) {
            $queued[$id] = $q;
        }
        $queues = array(
            1 => 'success',
            2 => 'warning',
            3 => 'danger',
            4 => 'primary',
            5 => 'surprise',
            6 => 'inverse',
            7 => 'inverse',
            8 => 'inverse',
            9 => 'danger',
            11 => 'danger',
        );
        foreach ($queued as $id => $queue) {
            $retQueued .= "<div class='showQueue btn btn-$queues[$queue]' id='id$id'>$queue</div>";
        }
        if ($this->data[$upc]['special_price'] == 0.00) {
            $curPrice = "Not On Sale";    
            $batchData = '<div class="line"></div>';
        } else {
            $curPrice = '$'.$this->data[$upc]['special_price'];
            $plus = ($numBatches > 1) ? '<a id="showMoreBatches" class="showMoreBatches">+</a>' : '';
            $batchData = ' 
            <div class="batchData">
                <!-- if there is more than one batch,
                     click to view more batches  -->
                '.$plus.'
                <div class="data">
                    <span class="smtext">Sale:</span>'.$sale.'
                </div>
                <div class="data">
                    <span class="smtext">Batch:</span><span class="mdtext">'.$batch.'</span>
                </div>
            </div>';
        }

        $timestamp = time();
        $this->addScript('scanning.js?time='.$timestamp);
        
        $this->addScript('scs.js?');

        return <<<HTML
<div id="response"></div>
{$this->hiddenContent()}
<div id="menuBtn">
</div>
<div align="center">
    <form class="form-inline" id="upcForm" name="upcForm" method="post" pattern="[0-9]">
        <div class="form-group" align="center">
            <input type="text" class="form-control" id="upc" name="upc" 
                value="$upc" placeholder="upc">
        </div>
    </form>
    <h5>$store $session</h5>
    <div class="line"></div>

    <div class="buttons container">
        <div class="row">
            <div class="col-xs-4">
                <button class="btn btn-invisible btn-sub-queue" id="noteBtn">&nbsp;</button>
            </div>
            <div class="col-xs-4">
                <button class="btn btn-warning btn-queue" value="2">Miss</button>
            </div>
            <div class="col-xs-4">
                <button class="btn btn-success btn-queue" value="1">Good</button>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-4">
                <button class="btn btn-surprise btn-queue" value="5">Tag</button>
            </div>
            <div class="col-xs-4">
                <button class="btn btn-inverse btn-sub-queue" id="capBtn">Cap</button>
            </div>
            <div class="col-xs-4">
                <button class="btn btn-primary btn-queue" value="4">Add</button>
            </div>
        </div>
    </div>

    <input type="hidden" name="storeID" id="formStoreID" value="$storeID">
    <input type="hidden" name="storeID" id="formSession" value="$session">

    <div class="data">
        <span class="smtext">Name:</span>
            <span class="editable" name="Description" 
                id="editdescription" value="$name">$name</span>
    </div>
    <div class="line"></div>
    <div class="data">
        <span class="smtext">Brand:</span>
            <span class="editable" name="Brand" 
                id="editbrand" value="$brand">$brand</span>
    </div>
    <div class="line"></div>
    <div class="data">
        <span class="smtext">Size:</span>
            <span class="editable" name="Size" 
                id="editsize" value="$size">$size</span>
    </div>
    <div class="line"></div>
    <div class="data">
        <span class="smtext">Location:</span>
            <span class="editlocation" name="Location" 
                id="editlocation" value="$location">$location</span>
    </div>
    <div class="line"></div>
    <div class="data">
        <span class="smtext">POS:</span><span class="curPrice">$curPrice</span>
    </div>
    
    $batchData
    
    <!-- show which queues an item is in -->
    <div class="queued">
        <div align="center" class="smtext">Current Queues:</div>
        <div align="center" class="showQueueContainer" id="showQueueContainer">$retQueued</div>
        <div style="width: 100vw;">
        <span class='smtext'>Notes:</span><br/>
            <span class='editable' name='Notes' 
                id='editnotes' value='$notes'>$notes</span>
        </div>
        
    </div>


</div>


<a id="reload" href="SCS.php">reload</a>
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
$(document).ready(function(){
    //alert('hello');
});
HTML;
    }

    public function cssContent()
    {
        include(__DIR__.'/../../../config.php');
        return <<<HTML
.menuOption {
    color: rgba(255,255,255,0.6);
    margin-top: 10vw;
    font-size: 10vw;
}
#menu {
    position: fixed;
    top:0;
    left: 0px;
    padding-bottom: 100vh; 
    background: linear-gradient(135deg, #42a7f4, #0a1528);
    background-repeat: no-repeat; 
    background-attachment: fixed;
    z-index: 100;
    width: 100vw;
    display: none;
}
#dataForm {
    display: none;
}
#menuBtn {
    position: absolute;
    top: 25px;
    left: 25px;
    height: 7vw;
    width: 7vw;
    background-color: rgba(255,255,255,0.1);
    font-size: 3vw;
    overflow: hidden;
    cursor: pointer;
    opacity: 0.5;
    background-image: url("http://$MY_ROOTDIR/common/src/img/icons/mobileMenu.png");
    background-size: cover;
}
.close {
    font-size: 8vw;
}
.useBatch {
    float: right;
}
.batchText {
    color: rgba(255,255,255,0.5);
}
#allBatches {
    position: fixed;
    top:0;
    left: 0px;
    padding-bottom: 100vh; 
    background: linear-gradient(135deg, #42a7f4, #0a1528);
    background-repeat: no-repeat; 
    background-attachment: fixed;
    z-index: 100;
    width: 100vw;
    display: none;
}
.showQueueContainer {
    width: 100vw;
    overflow: auto;
}
.loginForm {
    width: 90vw;
    margin-top: 50px;
    border-radius: 0px;
    background-color: rgba(255,255,255,0.3);
}
#submitNote {
    border-radius: 5px;
}
.submitNote {
    margin: 25px;
}
.btn-group {
    margin-top: 25px;
}
.noteInput {
    width: 100%;
    background-color: rgba(255,255,255,0.4);
    border: none;
    border-radius: 2px;
}
#noteUI {
    display: none;
    width: 100%;
    position: absolute;
    top: 30vh;
    background-color: rgba(255,0,0,0.8);
    z-index: 100;
    width: 100%;
    padding-top: 100px;
    padding-bottom: 50px;
}
#capButtons {
    display: none;
    width: 100%;
    position: absolute;
    top: 30vh;
    background-color: rgba(0,0,0,0.8);
    z-index: 100;
    width: 100%;
    padding-top: 100px;
    padding-bottom: 100px;
}
.capButtons {
}
#response {
    position: fixed;
    top: 2vw;
    right: 2vw;
    height: 10vw;
    width: 20vw;
    font-size: 4vw;
    text-align: center;
    display: none;
    padding: 5px;
}
.editable {
}
.queued {
    margin-top: 5vw;
}
.showQueue {
    width: 10vw;
    height: 10vw;
    background-color: white;
    float: left;
    margin-left: 5vw;
    margin-top: 5vw;
    font-size: 5vw;
    padding: 2vw;
    opacity: 0.8;
}
.btn-queue {
    height: 20vw;
    width: 20vw;
    font-size: 5vw;
    margin: 20px;
    border-radius: 10%;
    color: white;
    opacity: 0.9;
}
.btn-sub-queue {
    height: 20vw;
    width: 20vw;
    font-size: 5vw;
    margin: 20px;
    border-radius: 10%;
    color: white;
    opacity: 0.9;
}
.btn-invisible {
    opacity: 0;
}
.btn-success {
    background-color: green;
    background: linear-gradient(135deg, #7de074, #92f28a);
    color: rgba(0,0,0,0.5);
}
.btn-warning {
    background-color: yellow;
    background: linear-gradient(135deg, #e0da73, #f1f089);
    color: rgba(0,0,0,0.5);
}
.btn-danger {
    background-color: red;
    background: linear-gradient(135deg, #e07373, #f08888);
    color: rgba(0,0,0,0.5);
}
.btn-primary {
    background-color: blue;
    background: linear-gradient(135deg, #007BFF, #3D9EFF);
    color: rgba(0,0,0,0.5);
}
.btn-surprise {
    background-color: purple;
    background: linear-gradient(135deg, #E200FF, #EB56FF);
    color: rgba(0,0,0,0.5);
}
.btn-inverse {
    background-color: black;
    background: linear-gradient(135deg, #3F3F3F, #707070);
    color: #cacaca; 
}
.showMoreBatches {
    position: absolute;
    right: 15px;
}
.batchData {
    border: 1px solid #cacaca;
    margin-top: 15px;
}
.curPrice {
    font-weight: bold;
    font-size: 10vw;
}
.line {
    border-top: 1px solid #cacaca;
    width: 90vw;
}
#reload {
    position: absolute;
    bottom: 0px;
    display: none;
}
#upc {
    margin-top: 15px;
    opacity: 0.2;
    width: 50vw;
    height: 1wv;
    font-size: 5vw;
    text-align: center;
}
#upcForm {
    width: 50vw;
}
::-webkit-input-placeholder {
       text-align: center;
}
:-moz-placeholder { /* Firefox 18- */
   text-align: center;
}
::-moz-placeholder {  /* Firefox 19+ */
   text-align: center;
}
:-ms-input-placeholder {
       text-align: center;
}
body {
    font-size: 6vw;
    color: rgba(255,255,255,0.8);
    overflow-x: hidden;
}
h3 {
    font-size: 15vw;
}
h4 {
    font-size: 6vw;
}
h5 {
    font-size: 9vw;
    margin-top: -40px;
    color: rgba(255,255,255,0.5);
}
.mdtext {
    font-size: 4vw;
}
.smtext {
    font-size: 3vw;
    color: rgba(255,255,255,0.2);
}
HTML;
    }

    private function skuToUpc($upc)
    {
        include(__DIR__.'/../../../config.php');
        $dbc = scanLib::getConObj(); 
        $queryStr = 'SELECT upc
            FROM is4c_op.vendorItems
            WHERE vendorID = 1
            AND sku like "%'.$upc.'%" 
        ';
        $query = $dbc->prepare($queryStr);
        $result = $dbc->execute($query);
        while ($row = $dbc->fetchRow($result)) {
            $ourUpc = $row['upc'];
        }
        
        return $ourUpc;
    }

    private function hiddenContent()
    {
        $allBatches = '';
        foreach ($this->batches as $bid => $row) {
            //echo $bid . ' ' . $row['name'] . '<br/>';
            $allBatches .= '
                <div class="batchData">
                    <button class="useBatch close" id="useBid'.$bid.'">Use this Batch</button>
                    <div class="data">
                        <span class="smtext">Sale:</span>$'.$row['saleprice'].'
                    </div>
                    <div class="data">
                        <span class="smtext">Batch:</span><span class="batchText">'.$row['name'].'</span>
                    </div>
                </div>';
        }

        return <<<HTML
<div align="center" id="capButtons">
    <div class="capButtons container">
        <div class="row">
            <div class="col-xs-4">
                <button class="btn btn-inverse btn-queue" value="6">12UP</button>
            </div>
            <div class="col-xs-4">
                <button class="btn btn-inverse btn-queue" value="7">4UP</button>
            </div>
            <div class="col-xs-4">
                <button class="btn btn-inverse btn-queue" value="8">2UP</button>
            </div>
        </div>
    </div>
</div>
<div id="allBatches">
    $allBatches
    <button class="close" id="closeAllBatches" style="float: left; margin-top:25px;">Close</div>
</div>
<div id="menu">
    <div align="center">
        <h1>Main Menu</h1>
        <h2 class="menuOption"><a class="menuOption" href="#">Menu Option 1</a></h2>
        <h2 class="menuOption"><a class="menuOption" href="#">Menu Option 2</a></h2>
        <h2 class="menuOption"><a class="menuOption" href="#">Menu Option 3</a></h2>
        <h2 class="menuOption"><a class="menuOption" href="SCS.php?signout=1">Sign Out</a></h2>
        <br/>
        <button class="close" id="closeMenu" style="margin-right:40vw;">Close</div>
    </div>
</div>
HTML;
    }

}
WebDispatch::conditionalExec();
