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
        if (FormLib::get('option', false) == 99) {
            header('location: BatchCheckMenu.php');
        }
        include(__DIR__.'/../../../config.php');

        $dbc = scanLib::getConObj(); 
        if (FormLib::get('signout', false)) {
            session_unset();
            $this->addOnloadCommand('window.location.href = "SCS.php"');
        }
        if (FormLib::get('edit', false)) {
            $this->editHandler($dbc);
            die();
        } elseif (FormLib::get('clearAll', false)) {
            $this->clearAllHandler($dbc); 
            die();
        } elseif (FormLib::get('queue', false)) {
            $this->queueHandler($dbc); 
            die();
        } elseif (FormLib::get('removeQueue', false)) {
            $this->removeQueueHandler($dbc); 
            die();
        } elseif (FormLib::get('forceBatch', false)) {
            //method to force a batch goes here.
            //currently this does not exist
            die(); 
        } elseif (FormLib::get('loginSubmit', false)) {
            $this->loginSubmitHandler($dbc);
        } elseif (FormLib::get('lineCheck', false)) {
            $this->lineCheckHandler($dbc);
            die();
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

        return true;
    }

    private function lineCheckHandler($dbc)
    {

        $upc = FormLib::get('upc');
        $queue = FormLib::get('q');
        $qval = FormLib::get('qval');
        $sessionName = $_SESSION['sessionName'];
        $storeID = $_SESSION['storeID'];

        // get prod info to fine line family
        $args = array($upc, $storeID);
        $prep = $dbc->prepare("SELECT department FROM products WHERE upc = ? AND store_id = ?");
        $res = $dbc->execute($prep, $args);
        $row = $dbc->fetchRow($res);
        $department = $row['department'];

        $args = array($upc, $storeID, $department);
        $prep = $dbc->prepare("SELECT SUBSTR(upc, 1, 8) AS prefix, upc, brand, description, department
            FROM products WHERE upc like CONCAT('%',SUBSTR(?, 1, 8),'%') AND store_id = ? 
            AND department = ? GROUP BY upc;");
        $res = $dbc->execute($prep, $args);
        $upcs = array();
        while ($row = $dbc->fetchRow($res)) {
            $upcs[] = $row['upc']; 
        }

        foreach ($upcs as $upc) {
            //check how upc is curently queued
            $inQueues = array();
            $args = array($sessionName,$upc,$storeID);
            $prep = $dbc->prepare("SELECT inQueue FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND upc = ? AND storeID = ?");
            $res = $dbc->execute($prep,$args); 
            while ($row = $dbc->fetchRow($res)) {
                $inQueues[] = $row['inQueue'];
            }
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
        }

        $json = array();
        $json['error'] = $dbc->error();

        echo json_encode($json);
        return false;
    }

    private function clearAllHandler($dbc)
    {
        $qv = FormLib::get('qval');
        $sessionName = FormLib::get('sessionName');
        $storeID = FormLib::get('storeID');

        $capQueues = array();
        if ($qv == 6) {
            $capQueues = array(6, 7, 8);
            list($inClause, $args) = $dbc->safeInClause($capQueues);
        } elseif ($qv == 9) {
            $capQueues = array(9, 10);
            list($inClause, $args) = $dbc->safeInClause($capQueues);
        }
        $choice = ($qv == 6 || $qv == 9) ? $inClause : $qv;
        $andCond = ($qv == 11) ? "" : " AND session = ? AND storeID = ?";
        $query = "DELETE FROM woodshed_no_replicate.batchCheckQueues
            WHERE inQueue IN ($choice) $andCond";
        $args[] = $sessionName;
        $args[] = $storeID;
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep,$args);
        $json = array();
        $json['error'] = $dbc->error();

        echo json_encode($json);
        return false;
    }

    private function removeQueueHandler($dbc)
    {
        $qid = FormLib::get('qid');
        $args = array($qid);
        $prep = $dbc->prepare("delete from woodshed_no_replicate.batchCheckQueues where id = ?");
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
        include(__DIR__.'/../../../config.php');
        $storeID = scanLib::getStoreID();
        $sessions = ''; 
        $args = array($storeID);
        $prep = $dbc->prepare("SELECT session FROM woodshed_no_replicate.batchCheckQueues WHERE storeID = ? GROUP BY session;");
        $res = $dbc->execute($prep,$args);
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
            <input type="hidden" name="storeID" value="$storeID">
            <!-- 
            <select class="loginForm" name="storeID" required>
                <option value="0">Select a Store ID</option>
                <option value="1">Hillside</option>
                <option value="2">Denfeld</option>
            </select>-->
        </div>
        <div class="form-group">
            <button type="submit" name="loginSubmit" value="1" class="loginForm">Submit</button>
        </div>
    </form>    
    <form action="http://$FANNIE_ROOTDIR/item/ProdLocationEditor.php" method="get">
        <div class="form-group">
            <input type="hidden" name="start" value="CURRENT">
            <input type="hidden" name="end" value="CURRENT">
            <input type="hidden" name="store_id" value="$storeID">
            <button type="submit" name="batchCheck" value="1" class="loginForm">
                Click to Update <br/>Product Locations</button>
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
        $args = array($sessionName,$upc,$storeID);
        $prep = $dbc->prepare("SELECT inQueue FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND upc = ? AND storeID = ?");
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
            case 'Disco':
            case 'While':
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
            case 'Clear':
                if ($qval == 6) {
                    $args = array($upc,$sessionName,$storeID);
                    $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckQueues 
                        WHERE upc = ? AND session = ? AND storeID = ? AND inQueue in (6,7,8)");
                    $dbc->execute($prep,$args);
                } elseif ($qval == 9) {
                    $args = array($upc,$sessionName,$storeID);
                    $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckQueues 
                        WHERE upc = ? AND session = ? AND storeID = ? AND inQueue in (9,10)");
                    $dbc->execute($prep,$args);
                } elseif ($qval == 3) {
                    $args = array($upc,$sessionName);
                    $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckNotes 
                        WHERE upc = ? AND session = ?");
                    $dbc->execute($prep,$args);
                } elseif ($qval == 11) {
                    $args = array($upc);
                    $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckQueues 
                        WHERE upc = ?");
                    $dbc->execute($prep,$args);
                } else {
                    $args = array($upc,$sessionName,$storeID,$qval);
                    $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckQueues 
                        WHERE upc = ? AND session = ? AND storeID = ? AND inQueue = ?");
                    $dbc->execute($prep,$args);
                }
                break;
            case 'DNC':
                if (in_array(98,$inQueues)) {
                    // do nothing
                } else {
                    $args = array($upc,$sessionName,$storeID,98);
                    $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckQueues 
                        (upc,session,storeID,inQueue) VALUES (?,?,?,?)");
                    $dbc->execute($prep,$args);
                    // also, set op.products.inUse = 0
                    $a = array($upc,$storeID);
                    $p = $dbc->prepare("UPDATE is4c_op.products SET inUse = 0 WHERE upc = ? AND store_id = ?");
                    $r = $dbc->execute($p,$a);
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
        $storeID = scanLib::getStoreID();
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
                    $args = array($upc,$sessionName);
                    $query = "SELECT notes FROM $tempTable WHERE upc = ? AND session = ?";
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
        // make the actual edit in POS to the appropriate table
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

        //add to queue 11 
        $inQueues = array();
        $args = array($sessionName,$upc);
        $prep = $dbc->prepare("SELECT inQueue FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND upc = ?");
        $res = $dbc->execute($prep,$args); 
        while ($row = $dbc->fetchRow($res)) {
            $inQueues[] = $row['inQueue'];
        }
        //do NOT do this if notes were entered. 
        if ($field != 'notes') {
            if (in_array(11,$inQueues)) {
                //do nothing
            } else { 
                $args = array($upc,$sessionName,$storeID,11);
                $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckQueues 
                    (upc,session,storeID,inQueue) VALUES (?,?,?,?)");
                $dbc->execute($prep,$args);
            }
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
        $storeID = scanLib::getStoreID();
        $upc = FormLib::get('upc');
        $upc = scanLib::padUPC($upc);
        $args = array($upc,$storeID);
        $prep = $dbc->prepare("
            SELECT bl.upc, bl.salePrice, bl.batchID AS bid, p.brand AS pbrand, p.description AS pdesc, pu.brand AS pubrand, p.size, p.special_price, pu.description AS pudesc, b.batchName, f.sections 
            FROM batchList AS bl 
                LEFT JOIN products AS p ON bl.upc=p.upc 
                LEFT JOIN productUser AS pu ON p.upc=pu.upc 
                LEFT JOIN batches AS b ON bl.batchID=b.batchID 
                LEFT JOIN FloorSectionsListTable AS f ON p.upc=f.upc AND p.store_id=f.storeID 
            WHERE bl.batchID IN ( SELECT b.batchID FROM batches AS b WHERE NOW() BETWEEN startDate AND DATE_ADD(endDate, INTERVAL 1 DAY)) 
                AND bl.upc = ? 
                AND p.store_id = ?
            GROUP BY bl.batchID;
        ");
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
            $args = array($upc,$storeID);
            $prep = $dbc->prepare("
                SELECT p.upc, p.brand AS pbrand, p.description AS pdesc, p.size, p.special_price, pu.brand AS pubrand, pu.description AS pudesc, f.sections 
                FROM products AS p 
                    LEFT JOIN productUser AS pu ON pu.upc=p.upc 
                    LEFT JOIN FloorSectionsListTable AS f ON p.upc=f.upc AND p.store_id=f.storeID 
                WHERE p.upc = ? 
                    AND p.store_id = ?
                GROUP BY p.upc;
            "); 
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

        $deviceType = $this->deviceType;

        $lastMsg = $_SESSION['lastMsg'];
        $prep = $dbc->prepare("SELECT max(id) AS maxid FROM woodshed_no_replicate.batchCheckChat;");
        $res = $dbc->execute($prep);
        $row = $dbc->fetchRow($res);
        if ($row['maxid'] != $lastMsg) {
            //show #msgBtn
            $this->addOnloadCommand("$('#msgBtn').show();");
        }

        $upc = FormLib::get('upc');
        // fix: confirmed, this does not work.
        // scans should also be able to convert UPCs to
        // scale items. 
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
        $location = (!is_null($this->data[$upc]['sections'])) ? $this->data[$upc]['sections'] : 'n/a';

        $args = array($upc,$session);
        $prep = $dbc->prepare("SELECT notes FROM woodshed_no_replicate.batchCheckNotes WHERE upc = ? AND session = ?");
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
            10 => 'danger',
            11 => 'danger',
            98 => 'success',
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
        
        $this->addScript('scs.js?time='.$timestamp);

        return <<<HTML
<div id="response"></div>
{$this->hiddenContent()}
<div id="menuBtn"></div>
<a href="BatchCheckChat.php#message"><div id="msgBtn"></div></a>
<div align="center" id="grandparent">
    <form class="form-inline" id="upcForm" name="upcForm" method="post">
        <div class="form-group" align="center">
            <input type="text" class="form-control" id="upc" name="upc" pattern="\d*" 
                value="$upc" placeholder="upc">
        </div>
    </form>
    <div class="container containerBtns">
        <div class="row">
            <div class="col-4">
                <button class="btn btn-danger btn-sub-queue" id="discoBtn">Disco</button>
            </div>
            <div class="col-4">
                <button class="btn btn-warning btn-queue" value="2">Miss</button>
            </div>
            <div class="col-4">
                <button class="btn btn-success btn-queue" value="1" id="goodBtn">Good</button>
            </div>
        </div>
        <div class="row secondRow">
            <div class="col-4">
                <button class="btn btn-surprise btn-queue" value="5">Tag</button>
            </div>
            <div class="col-4">
                <button class="btn btn-inverse btn-sub-queue" id="capBtn">Cap</button>
            </div>
            <div class="col-4">
                <button class="btn btn-primary btn-queue" value="4">Add</button>
            </div>
        </div>
    </div>

    <input type="hidden" name="storeID" id="formStoreID" value="$storeID">
    <input type="hidden" name="sessionName" id="formSession" value="$session">

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

<div class='header' id='submitUpc'><h5>$store $session</h5></div>

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
        $css = <<<HTML
body {
    font-family: Arial, Helvetica, sans-serif;
    background-color: rgba(255,255,255,0.9);
    background: linear-gradient(135deg, #42a7f4, #0a1528);
    background-color: linear-gradient(135deg, #42a7f4, #0a1528);
    background-repeat: no-repeat;
    background-attachment: fixed;
}
#grandparent {
}
.secondRow {
    margin-top: 15px;
    margin-bottom: 15px;
}
.containerBtns {
    margin-top: 15px;
}
h2.menuOption {
    background: rgba(255,255,255,0.1);
    background-color: rgba(255,255,255,0.1);
    padding: 25px;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 3px;
    margin: 5px;
}
.menuOption {
    color: rgba(255,255,255,0.6);
    margin-top: 15px;
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
    top: 15px;
    left: 15px;
    height: 50px;
    width: 50px;
    background-color: rgba(255,255,255,0.1);
    overflow: hidden;
    cursor: pointer;
    opacity: 0.5;
    background-image: url("http://$MY_ROOTDIR/common/src/img/icons/mobileMenu.png");
    background-size: cover;
}
#msgBtn {
    position: absolute;
    background-color: rgba(255,255,255,0.1);
    overflow: hidden;
    cursor: pointer;
    opacity: 0.9;
    background-image: url("http://$MY_ROOTDIR/common/src/img/icons/msgBtn.png");
    background-size: cover;
    z-index: 999;
    display:none;
}
.close {
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
    margin: 25px;
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
#discoButtons {
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
#response {
    position: fixed;
    top: 15px;
    right: 15px;
    height: 50px;
    width: 100px;
    text-align: center;
    display: none;
    padding: 5px;
}
.editable {
}
.queued {
}
.showQueue {
    width: 50px;
    height: 50px;
    margin-left: 15px;
    margin-top: 15px;
    background-color: white;
    float: left;
    opacity: 0.8;
}
.btn-queue {
    height: 75px;
    width: 75px;
    border-radius: 10%;
    color: white;
    opacity: 0.9;
}
.btn-sub-queue {
    height: 75px;
    width: 75px;
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
    color: rgba(255,255,255,0.8);
    overflow-x: hidden;
}
h5 {
    color: rgba(255,255,255,0.5);
}
.header {
    position: absolute;
    margin-top: 20px;
    top: 0px;
    right: 0px;
    width: 25vw;
    text-align: center;
}
.smtext {
    color: rgba(255,255,255,0.2);
}
HTML;
        if ($this->deviceType == 'mobile') {
            // Use vw sizing for handheld devices
            $css .= <<<HTML
#msgBtn {
    font-size: 3vw;
}
.close {
    font-size: 8vw;
}
#response {
    font-size: 4vw;
}
.showQueue {
    font-size: 5vw;
}
.btn-queue {
    font-size: 5vw;
}
.btn-sub-queue {
    font-size: 5vw;
}
.curPrice {
    font-size: 10vw;
}
#upc {
    font-size: 5vw;
}
body {
    font-size: 6vw;
}
h3 {
    font-size: 15vw;
}
h4 {
    font-size: 6vw;
}
h5 {
    font-size: 4vw;
}
.smtext {
    font-size: 3vw;
}
.menuOption {
    font-size: 10vw;
}
.btn-queue {
    height: 20vw;
    width: 20vw;
}
.btn-sub-queue {
    height: 20vw;
    width: 20vw;
}
.secondRow {
    margin-top: 8vw;
    margin-bottom: 4vw;
}
.containerBtns {
    margin-top: -8vw;
}

.menuOption {
    //margin-top: 10vw;
}
#menuBtn {
    top: 6vw;
    left: 6vw;
    height: 9vw;
    width: 9vw;
}
#msgBtn {
    position: absolute;
    top: 6vw;
    right: 6vw;
    height: 9vw;
    width: 9vw;
}
#response {
    top: 2vw;
    right: 2vw;
    height: 10vw;
    width: 20vw;
}
.queued {
    margin-top: 5vw;
}
.showQueue {
    width: 10vw;
    height: 10vw;
    margin-left: 5vw;
    margin-top: 5vw;
}
HTML;
        }

        return $css;
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
<!-- End Cap Options -->
<div align="center" id="capButtons">
    <div class="capButtons container">
        <div class="row">
            <div class="col-4">
                <button class="btn btn-inverse btn-queue" value="6">12UP</button>
            </div>
            <div class="col-4">
                <button class="btn btn-inverse btn-queue" value="7">4UP</button>
            </div>
            <div class="col-4">
                <button class="btn btn-inverse btn-queue" value="8">2UP</button>
            </div>
        </div>
    </div>
</div>
<div id="allBatches">
    $allBatches
    <button class="close" id="closeAllBatches" style="float: left; margin-top:25px;">Close</div>
</div>
<!-- SCS Menu -->
<div id="menu">
    <div align="center">
        <h1>Scanner Options</h1>
        <h2 class="menuOption"><a class="menuOption" href="BatchCheckMenu.php">Main Menu</a></h2>
        <h2 class="menuOption"><a class="menuOption" href="SCS.php?signout=1">Sign Out</a></h2>
        <br/>
        <button class="close" id="closeMenu" style="margin-right:40vw;">Close</div>
    </div>
</div>
<!-- Disco/While Supplies Last Options -->
<div align="center" id="discoButtons">
    <div class="discoButtons container">
        <div class="row">
            <div class="col-4">
                <button class="btn btn-danger btn-queue" value="9">Disco</button>
            </div>
            <div class="col-4">
                <button class="btn btn-danger btn-queue" value="10">While</button>
            </div>
            <div class="col-4">
            </div>
        </div>
    </div>
</div>
HTML;
    }

}
WebDispatch::conditionalExec();
