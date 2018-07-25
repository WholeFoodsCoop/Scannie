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
    protected $queueCounts = array();
    protected $options = array(
        0 => 'Unchecked',
        1 => 'Good',
        2 => 'Miss',
        3 => 'Note',
        4 => 'Add',
        5 => 'Shelf-Tag',
        6 => 'Cap-Signs',
        9 => 'Disco/Supplies Last',
        11 => 'Edited',
        98 => 'DNC',
        99 => 'Main Menu',
    );

    public function __construct()
    {
        foreach (array(1,2,3,4,5,6,7,11,98) as $id) {
            $this->queueCounts[$id] = 0;
        }
    }

    public function preprocess()
    {
        if (FormLib::get('option', false) == 99) {
            header('location: BatchCheckMenu.php');
        }
        $dbc = scanLib::getConObj();
        if (FormLib::get('loginSubmit', false)) {
            $this->loginSubmitHandler($dbc);
        }
        $sessionName = $_SESSION['sessionName'];
        $storeID = $_SESSION['storeID'];
        $this->getQueueCounts($dbc, $sessionName);
        if ($sessionName && $storeID) {
            $this->displayFunction = $this->view($sessionName,$storeID);
        } else {
            $this->displayFunction = $this->loginView();
        }

        return false;
    }

    private function getQueueCounts($dbc, $session)
    {
        $args = array($session);
        $prep = $dbc->prepare("SELECT inQueue, COUNT(*) AS count FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? GROUP BY inQueue, upc");
        $prepB = $dbc->prepare("SELECT COUNT(*) AS count FROM woodshed_no_replicate.batchCheckNotes WHERE session = ?");
        $res = $dbc->execute($prep,$args);
        $resB = $dbc->execute($prepB,$args);
        while ($row = $dbc->fetchrow($res)) {
            $inQueue = $row['inQueue'];
            $count = $row['count'];
            $this->queueCounts[$inQueue] += $count;
        }
        while ($row = $dbc->fetchrow($resB)) {
            $count = $row['count'];
            $this->queueCounts[3] += $count;
        }
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
        $storeID = scanLib::getStoreID();
        $dbc = scanLib::getConObj();
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
</div>
HTML;
    }

    // get current batch info, only called if option != 0
    private function getCurrentBatches($dbc)
    {
        $storeID = scanLib::getStoreID();
        $args = array($storeID);
        $prep = $dbc->prepare("
            SELECT bl.upc, bl.batchID AS bid, b.batchName
            FROM batchList AS bl 
                LEFT JOIN products AS p ON bl.upc=p.upc 
                LEFT JOIN batches AS b ON bl.batchID=b.batchID 
                LEFT JOIN StoreBatchMap AS sbm ON b.batchID=sbm.batchID 
            WHERE bl.batchID IN ( SELECT b.batchID FROM batches AS b WHERE NOW() BETWEEN startDate AND endDate)
                AND p.inUse = 1
                AND sbm.storeID = ? 
        ");
        $res = $dbc->execute($prep,$args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[$row['upc']]['bid'] = $row['bid'];
            $data[$row['upc']]['batchName'] = $row['batchName'];
        }

        return $data;
    }

    private function getTableContents($dbc)
    {
        include(__DIR__.'/../../../config.php');
        $option = FormLib::get('option');
        $sessionName = $_SESSION['sessionName'];
        $storeID = $_SESSION['storeID'];
        $batches = array();

        $upcs = array();
        $args = array();
        $options = array();
        if ($option == 6) {
             $options = array(6,7,8);
        } else {
            $options[] = $option;
        }
        list($inStr, $args) = $dbc->safeInClause($options);
        $args[] = $sessionName;
        $query = "SELECT upc FROM woodshed_no_replicate.batchCheckQueues WHERE inQueue IN ($inStr) AND session = ?";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $textarea = "<textarea rows=5 id='textarea' class='form-control filter'>";
        while ($row = $dbc->fetchRow($res)) {
              $textarea .= $row['upc']."\r\n"; 
              $upcs[] = $row['upc'];
        }
        $count = '';
        $textarea .= "</textarea>";
        $textarea = "
            <div class='row'>
                <div class='col-xs-3'>
                    $textarea
                </div>
            </div>
        ";

        //get all data for products on sale
        $args = array($storeID,$storeID);
        if ($option == 0) {
            $optionZeroFilter = "WHERE bl.batchID IN ( SELECT b.batchID FROM batches AS b WHERE NOW() BETWEEN startDate AND endDate)";
        } else {
            $batches = $this->getCurrentBatches($dbc);
            $optionZeroFilter = 'WHERE 1 ';
        }
        $query = "
            SELECT bl.upc, bl.salePrice, bl.batchID AS bid, p.brand AS pbrand, 
                p.description AS pdesc, pu.brand AS pubrand, p.size, p.special_price, 
                pu.description AS pudesc, b.batchName, f.sections, 
                DATE(p.last_sold) as last_sold
            FROM batchList AS bl 
                LEFT JOIN products AS p ON bl.upc=p.upc 
                LEFT JOIN productUser AS pu ON p.upc=pu.upc 
                LEFT JOIN batches AS b ON bl.batchID=b.batchID 
                INNER JOIN FloorSectionsListView AS f ON p.upc=f.upc AND p.store_id=f.storeID 
                LEFT JOIN StoreBatchMap AS sbm ON b.batchID=sbm.batchID AND p.store_id=sbm.storeID
            $optionZeroFilter
                AND p.store_id = ?
                AND p.inUse = 1
                AND sbm.storeID = ?
            GROUP BY p.upc 
            ORDER BY f.sections
        ";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep,$args);
        $fields = array('upc','salePrice','bid','pbrand','pubrand','pdesc','pudesc','size','special_price',
            'batchName','sections','last_sold');
        $optBatchName = array();
        $optBrand = array();
        $optSections = array();
        while ($row = $dbc->fetchRow($res)) {
            foreach ($fields as $field) {
                // get data for rows
                ${$field}[$row['upc']] = $row[$field];
                // get batch names & bids for products not in option 1
                if ($option != 0) {
                    if (in_array($field,array('bid','batchName')) && $field == 'upc') {
                        ${$field}[$row['upc']] = ($batches[$row['upc']][$field]) ? $batches[$row['upc']][$field] : 'n/a';
                    }
                }
                $value = $row[$field];
                if ($field == 'batchName') {
                    if (!in_array($value,$optBatchName)) {
                        $optBatchName[] = $row[$field];
                    }
                } elseif ($field == 'pbrand') {
                    if (!in_array($value,$optBrand)) {
                        $optBrand[] = $row[$field];
                    }
                } elseif ($field == 'sections') {
                    if (!in_array($value,$optSections)) {
                        $optSections[] = $row[$field];
                    }
                }
            }
        }
        if ($er = $dbc->error()) echo "<div class='alert alert-danger'>$er</div>";

        //additional query to limit results shown
        $inQueueItems = array();
        if ($option != 0 && $option != 11 && $option != 6 && $option != 9) {
            $args = array($sessionName,$storeID,$option);
            $prep = $dbc->prepare("SELECT upc FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND storeID = ? AND inQueue = ?");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $inQueueItems[] = $row['upc'];
            }
        } elseif ($option == 11) {
            $prep = $dbc->prepare("SELECT upc FROM woodshed_no_replicate.batchCheckQueues WHERE inQueue = 11");
            $res = $dbc->execute($prep);
            while ($row = $dbc->fetchRow($res)) {
                $inQueueItems[] = $row['upc'];
            }
        } elseif ($option == 6) { 
            $args = array($sessionName,$storeID);
            $prep = $dbc->prepare("SELECT upc, inQueue FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND storeID = ? 
                AND inQueue IN (6,7,8)");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $inQueueItems[] = $row['upc'];
                $inQueue[$row['upc']][] = $row['inQueue'];
            }
        } elseif ($option == 9) {
            $args = array($sessionName,$storeID);
            $prep = $dbc->prepare("SELECT upc, inQueue FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND storeID = ? 
                AND inQueue in (9,10)");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $inQueueItems[] = $row['upc'];
                $inQueue[$row['upc']][] = $row['inQueue'];
            }
        } elseif ($option == 98) {
            $args = array($sessionName,$storeID);
            $prep = $dbc->prepare("SELECT upc, inQueue FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND storeID = ? 
                AND inQueue = 98");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $inQueueItems[] = $row['upc'];
                $inQueue[$row['upc']][] = $row['inQueue'];
            }
        } else {
            $args = array($sessionName,$storeID);
            $prep = $dbc->prepare("SELECT upc FROM woodshed_no_replicate.batchCheckQueues WHERE session = ? AND storeID = ?");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $inQueueItems[] = $row['upc'];
            }
        }

        foreach ($inQueueItems as $abc) {
            // echo "$abc<br/>";
        }

        $thead = '';
        $theadFields = array();
        
        $theadFields[] = 'upc';
        $theadFields[] = 'salePrice';
        $theadFields[] = 'bid';
        $theadFields[] = 'pbrand';
        $theadFields[] = 'pubrand';
        $theadFields[] = 'pdesc';
        $theadFields[] = 'pudesc';
        $theadFields[] = 'size';
        $theadFields[] = 'special_price';
        $theadFields[] = 'batchName';
        $theadFields[] = 'sections';
        $theadFields[] = 'last_sold';
        if ($option == 6) {
            $theadFields[] = 'Needed';
        } elseif ($option == 9) {
            $theadFields[] = 'InQueue';
        }

        $hiddenContent = '';
        foreach ($theadFields as $field) {
            $hiddenContent .= "<button class='col-filter btn btn-info btn-sm' id='col-filter-$field'>$field</button>";
        }

        // Add filters/filter options
        $filter = '';
        $filters = array('pbrand','batchName','sections','date','Coop+Deals');
        sort($optBrand);
        sort($optSections);
        sort($optBatchName);
        foreach ($filters as $name) {
            $filter .= "<select name='$name' id='select-$name' class='filter'>
                <option>Filter by $name</option>
                <option>View All</option>";
            if ($name == 'pbrand') {
                foreach ($optBrand as $filterName) {
                    $filter .= "<option>$filterName</option>";
                }
            } elseif ($name == 'batchName') {
                foreach ($optBatchName as $filterName) {
                    $filter .= "<option>$filterName</option>";
                }
            } elseif ($name == 'sections') {
                foreach ($optSections as $filterName) {
                    $filter .= "<option>$filterName</option>";
                }
            } elseif ($name == 'date') {
                $filter .= "<option>Hide Yellow</option>";
                $filter .= "<option>Hide Red & Yellow</option>";
            } elseif ($name =='Coop+Deals') {
                $filter .= "<option>Show Only Coop+Deals</option>";
            }
            $filter.= "</select>";
        }

        $hiddenContent .= "
            <input type='hidden' id='sessionName' name='sessionName' value='{$_SESSION['sessionName']}'>
            <input type='hidden' id='formSession' value='{$_SESSION['sessionName']}'>
            <input type='hidden' id='storeID' name='storeID' value='{$_SESSION['storeID']}'>
            <input type='hidden' id='curOption' name='curOption' value='$option'>
        ";

        foreach ($theadFields as $field) {
            $thead .= "
                <th class='col-$field '>
                    <div class='thLine'>
                        <span class='name'>$field</span> &nbsp;&nbsp;
                        <button class='scanicon-tablesorter sorter'>&nbsp;</button>&nbsp;&nbsp;
                        <button class='col-hide' value='$field'>-</button>
                    </div>
                </th>";
        }
        $queueBtns = array(1,2,0);
        if ($option == 0)
            $queueBtns[] = 98;
        foreach ($queueBtns as $qv) {
            $thead .= "<th class='col-{$this->options[$qv]}'>{$this->options[$qv]}</th>";
        }
        if (in_array($option,array(1,2,4,5,6,9,11))) {
            $thead .= "<th class=''>Clear</th>";
        }
        $thead .= "<th class='blank-th' id='blank-th'></th>";
        $table = "<div class='table-responsive'><table id='mytable' class='table table-stiped table-compressed tablesorter small'><thead id='mythead' class='mythead'>$thead</thead><tbody>";

        $hiddenThead = "<div class='table-responsive'><table id='mytable-clone' class='table table-stiped table-compressed tablesorter small'><thead id='mythead-clone' class='mythead'>$thead</thead><tbody></tbody></table></div>";
        $hiddenContent .= $hiddenThead;

        $r = 1;
        $upcsInTable = array();
        foreach ($upc as $k => $v) {
            $upcLink = "<a href='http://$FANNIE_ROOTDIR/item/ItemEditorPage.php?searchupc=$k' target='_BLANK'>$k</a>";
            if ($option == 0) {
                if (!in_array($k,$inQueueItems)) { 
                    $table .= "<tr>";
                    $table .= "<td class='col-upc'>$upcLink</td>";
                    foreach ($fields as $field) {
                        if ($field != 'upc') {
                            $temp = ${$field}[$k];
                            $extraClass = ($field == 'sections') ? 'editLocation' : '';
                            $table .= "<td class='col-$field $extraClass'>$temp</td>";
                        }
                    }
                    foreach ($queueBtns as $qv) {
                        $table .= "<td><button id='queue$k' value='$qv' class='queue-btn btn btn-info'>{$this->options[$qv]}</button></td>";
                    }
                    $table .= "</tr>";
                    $r++;
                }
            } elseif (in_array($option,array(1,2,4,5,11,98))) {
                if (in_array($k,$inQueueItems)) {
                    $table .= "<tr>";
                    $table .= "<td>$upcLink</td>";
                    $upcsInTable[] = $k;
                    foreach ($fields as $field) {
                        if ($field == 'batchName') {
                            $temp = "";
                            $temp = $batches[$k]['batchName'];
                            $extraClass = ($field == 'sections') ? 'editLocation' : '';
                            $table .= "<td class='col-$field $extraClass'>$temp</td>";
                        } elseif ($field != 'upc') {
                            $temp = ${$field}[$k];
                            $extraClass = ($field == 'sections') ? 'editLocation' : '';
                            $table .= "<td class='col-$field $extraClass'>$temp</td>";
                        }
                    }
                    foreach ($queueBtns as $qv) {
                        // echo "$qv<br/>";
                        $table .= "<td><button id='queue$k' value='$qv' class='queue-btn btn btn-info'>{$this->options[$qv]}</button></td>";
                    }
                    $table .= "<td><button id='queue$k' value='$option' class='queue-btn btn btn-info'>Clear</button></td>";
                    $table .= "</tr>";
                    $r++;
                }             
            } elseif (in_array($option,array(6,9))) {
                if (in_array($k,$inQueueItems)) {
                    $table .= "<tr>";
                    $upcLink = "<a href='http://$FANNIE_ROOTDIR/item/ItemEditorPage.php?searchupc=$k' target='_BLANK'>$k</a>";
                    $table .= "<td class='col-upc'>$upcLink</td>";
                    $upcsInTable[] = $k;
                    foreach ($fields as $field) {
                        if ($field != 'upc') {
                            $temp = ${$field}[$k];
                            $table .= "<td class='col-$field'>$temp</td>";
                        }
                    }
                    $tempQueueNames = array(6=>'12UP',7=>'4UP',8=>'2UP',9=>'Disco',10=>'While Supplies Last');
                    $tempQueueString = '';
                    foreach ($inQueue as $upc => $queues) {
                        if ($k == $upc) {
                            foreach ($queues as $v) {
                                $tempQueueString .= $tempQueueNames[$v].',';
                            }
                            $tempQueueString = trim($tempQueueString,',');
                            $colname = ($option == 6) ? 'Needed' : 'InQueue';
                            $table .= "<td class='col-$colname'><b>$tempQueueString</b></td>";
                        }
                    }
                    foreach ($queueBtns as $qv) {
                        $table .= "<td><button id='queue$k' value='$qv' class='queue-btn btn btn-info'>{$this->options[$qv]}</button></td>";
                    }
                    $table .= "<td><button id='queue$k' value='$option' class='queue-btn btn btn-info'>Clear</button></td>";
                    $table .= "</tr>";
                    $r++;
                }             
            }
        }
        if ($option == 3) {
            $table = '<div class="table-responsive"><table class="table table-stiped table-compressed small"><thead><th>upc</th><th>notes</th><th>Clear</th></thead><tbody>';
            $args = array($sessionName);
            $prep = $dbc->prepare("SELECT upc, session, notes FROM woodshed_no_replicate.batchCheckNotes WHERE session = ?");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $table .= "<tr>";
                $curUpc = $row['upc'];
                $upcLink = "<a href='http://$FANNIE_ROOTDIR/item/ItemEditorPage.php?searchupc=$curUpc' target='_BLANK'>$curUpc</a>";
                $upcsInTable[] = $k;
                $temp = $row['notes'];
                $table .= "<td>$upcLink</td>";
                $table .= "<td class='col-$field editable' id='editnotes'>$temp</td>";
                $table .= "<td><button id='queue$curUpc' value='$option' class='queue-btn btn btn-info'>Clear</button></td>";
                $table .= "</tr>";
                $r++;
            }
            $table .= '</tbody></table></div>';
        }
        
        // get those missing upcs into the table.
        // REM: buttons and clicks don't work (except clear)
        $missingUpcs = array();
        foreach ($upcs as $k => $upc) {
            if (!in_array($upc, $upcsInTable)) {
                $missingUpcs[] = $upc;
            }
        }
        $emptyd = "<td></td>";
        list($inStr,$args) = $dbc->safeInClause($missingUpcs); 
        switch($option) {
            case 6:
                $args[] = $storeID;
                $args[] = $sessionName;
                $args[] = "6, 7, 8";
                $query = "SELECT products.upc, brand, description, 
                    CASE WHEN inQueue THEN inQueue ELSE 'n/a' END AS inQueue FROM products 
                    LEFT JOIN woodshed_no_replicate.batchCheckQueues ON products.upc=batchCheckQueues.upc 
                    WHERE products.upc IN ($inStr) AND storeID = ? AND session = ? AND inQueue IN (?)";
                break;
            case 9:
                $args[] = $storeID;
                $args[] = $sessionName;
                $args[] = "9, 10";
                $query = "SELECT products.upc, brand, description, 
                    CASE WHEN inQueue THEN inQueue ELSE 'n/a' END AS inQueue FROM products 
                    LEFT JOIN woodshed_no_replicate.batchCheckQueues ON products.upc=batchCheckQueues.upc 
                    WHERE products.upc IN ($inStr) AND storeID = ? AND session = ? AND inQueue IN (?)";
                break;
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 11:
            case 98:
                $query = "SELECT upc, brand, description FROM products WHERE upc IN ($inStr)";
                break;
        }
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $brand = $row['brand'];
            $description = $row['description'];
            $upc = $row['upc'];
            $upcLink = "<a href='http://$FANNIE_ROOTDIR/item/ItemEditorPage.php?searchupc=$upc' target='_BLANK'>$upc</a>";
            $table .= "<tr>";
            // table data
            $table .= "<td class='col-upc'>$upcLink</td><td class='col-salePrice'>n/a</td><td class='col-bid'>n/a</td><td class='col-pbrand'>$brand</td><td class='col-pubrand'>n/a</td><td class='col-pdesc'>$description</td>";
            $table .=  "<td class='col-pudesc'>n/a</td>";
            $table .=  "<td class='col-size'>n/a</td>";
            $table .=  "<td class='col-special_price'>n/a</td>";
            $table .=  "<td class='col-batchName'>n/a</td>";
            $table .=  "<td class='col-sections'>n/a</td>";
            $table .=  "<td class='col-last_sold'>n/a</td>";
            if (in_array($option,array(6,9))) {
                $tempQueueNames = array(6=>'12UP',7=>'4UP',8=>'2UP',9=>'Disco',10=>'While Supplies Last');
                $tempQueueString = '';
                $tempQueueString = $tempQueueNames[$row['inQueue']];
                $tempQueueString = trim($tempQueueString,',');
                $colname = ($option == 6) ? 'Needed' : 'InQueue';
                $table .= "<td class='col-$colname'><b>$tempQueueString</b></td>";
            }
            foreach ($queueBtns as $qv) {
                $table .= "<td><button id='queue$upc' value='$qv' class='queue-btn btn btn-info'>{$this->options[$qv]}</button></td>";
            }
            $table .= "<td><button id='queue$upc' value='$option' class='queue-btn btn btn-info'>Clear</button></td>";
            $table .= "</tr>";
            $r++;
        }
        
        $table .= "</tbody></table></div>";
        $timestamp = time();
        $this->addScript('scs.js?time='.$timestamp);

        if ($er = $dbc->error()) {
            return "<div class='alert alert-danger'>$er</div>";
        } else {
            return $filter
                .$count
                ."<div id='filter-buttons'>$hiddenContent</div>"
                .$table
                .$textarea;
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
        $q = FormLib::get('option');
        //$ret .= "<h4>{$this->options[$q]}</h4>";
        if(isset($_POST['session'])) $_SESSION['session'] = $_POST['session'];
        $dbc = Scanlib::getConObj();
        $curQueue = $_GET['queue'];

        if ($this->options[$q]) {
            if (in_array($q,array(0))) {
                $ret .= "<h4 id='qcount'>{$this->options[$q]}</h4>";
            } elseif ($q == 6) {
                $qCount = $this->queueCounts[6] + $this->queueCounts[7] + $this->queueCounts[8];
                $ret .= "<h4 id='qcount'>{$this->options[$q]} [$qCount]</h4>";
                $table = $this->getTableContents($dbc);
            } elseif ($q == 9) {
                $qCount = $this->queueCounts[9] + $this->queueCounts[10];
                $ret .= "<h4 id='qcount'>{$this->options[$q]} [$qCount]</h4>";
                $table = $this->getTableContents($dbc);
                
            } else {
                $qCount = $this->queueCounts[$q];
                $ret .= "<h4 id='qcount'>{$this->options[$q]} [$qCount]</h4>";
                $table = $this->getTableContents($dbc);
            }
            $table = $this->getTableContents($dbc);
        } else {
            $ret .= "<h4 class='alert-danger'>NO QUEUE SELECTED</h4>";
            $table = "";
        }
        $ret .= "</div>";

        foreach ($_GET as $key => $value) {
            if ($key == 'queue') $thisQueue = $value;
        }
        if(isset($_POST['session'])) $_SESSION['session'] = $_POST['session'];
        $dbc = Scanlib::getConObj();
        $curQueue = (array_key_exists('queue', $_GET)) ? $_GET['queue'] : null;

        $table = $this->getTableContents($dbc);
        $clearAll = "<button id='clearAll' value='$q' class='btn btn-danger'>ClearAll</button>";

        $timestamp = time();
        $this->addScript("SalesChangeQueues.js?time=".$timestamp);
        $this->addScript("batchCheckQueues.js?time=".$timestamp);
        $this->addScript('http://'.$MY_ROOTDIR.'/common/javascript/tablesorter/js/jquery.tablesorter.min.js');
        $this->addScript('http://'.$MY_ROOTDIR.'/common/javascript/tablesorter/js/jquery.metadata.js');

        return <<<HTML
$ret
$table
<div align="center">$clearAll</div>
HTML;
    }

    public function queueToggle()
    {
        $options = '';
        foreach ($this->options as $id => $name) {
            $queueCount = $this->queueCounts[$id];
            if ($id == 6) {
                $queueCount = $this->queueCounts[6] + $this->queueCounts[7] + $this->queueCounts[8];
            } elseif ($id == 9) {
                $queueCount = $this->queueCounts[9] + $this->queueCounts[10];
            }
            $queueShow = ($queueCount > 0) ? "[$queueCount]" : "";
            $options .= "
                <div align='center'>
                    <button type='submit' class='btn-primary toggle-btn' name='option' value='$id'>
                        <div class='mobilePage'>
                            $name <span class='lightweight'>$queueShow</span>
                        </div>
                    </button>
                </div>";
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
textarea {
    margin-left: 25px;
}
body {
    font-family: Arial, Helvetica, sans-serif;
    background-color: rgba(255,255,255,0.9);
    background: linear-gradient(135deg, #42a7f4, #0a1528);
    background-color: linear-gradient(135deg, #42a7f4, #0a1528);
    background-repeat: no-repeat;
    background-attachment: fixed;
}
#clearAll {
    margin-bottom: 25px;
}
#mythead-clone {
    position: fixed;
    top: -1px;
    left: -1px;
    display: none;
}
#mytable-clone, #mythead-clone {
    width: 100%;
    background: white;
    background-color: white;
    border-bottom: 1px solid grey;
}
#filter-buttons {
    position: fixed; 
    bottom: -10px;
}
span.lightweight {
    font-weight: normal;
}
select.filter {
    float: right;
    background-color: rgba(255,255,255,0.3);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 2px;
    margin-right: 5px;
    margin-top: 5px;
}
.thLine {
    overflow: hidden; 
    white-space: nowrap;
}
.col-hide, .sorter {
    padding: 0px;
    height: 15px;
    width: 15px;
    border: 1px solid grey;
}
.disable {
    color: grey;
    background-color: red;
    background: rgba(0,0,0,0.5);
}
#blank-th {
    display: none;
}
h4, h2, h1 {
    color: rgba(255,255,255,0.8);
    text-shadow: 1px 1px rgba(0,0,0,0.5);
}
h4 {
    color: orange;
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
    background-color: grey;
    background: grey;
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
    bottom: 50px;
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
