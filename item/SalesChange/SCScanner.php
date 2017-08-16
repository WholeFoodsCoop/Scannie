<?php 
session_start();
?>

<script type="text/javascript" src="/git/fannie/src/javascript/jquery.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/cordova-2.2.0.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/ScannerLib-Linea-2.0.0.js"></script>
<script type="text/javascript" src="scanner.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    enableLinea('#upc', function(){$('#my-form').submit();});
});
function sendToQueue(button, upc, queue_id, session,notes)
{
    $.ajax({
        url: 'salesChangeAjax2.php',
        data: 'upc='+upc+'&queue='+queue_id+'&session='+session+'&notes='+notes,
        success: function(response)
        {
            $('#ajax-resp').html(response);
        }
    });
}
function changeStoreID(button, store_id)
{
    $.ajax({
        url: 'salesChangeAjax3.php',
        data: 'store_id='+store_id,
        success: function(response)
        {
            $('#ajax-resp').html(response);
            window.location.reload();
        }
    });
}
</script>
    <title>SalesChangeScanner</title>
</head>
<body><br>

<?php
include('../../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
class SCScanner extends scancoordDispatch
{
    
    protected $title = "Sales Change Scanner";
    protected $description = "[] ";
    protected $ui = FALSE;
    
    public function body_content()
    {
        
        $ret = '';
        $ret .= $this->form_content();
        
        if(isset($_POST['session'])) $_SESSION['session'] = $_POST['session'];
        if(isset($_POST['store_id'])) $_SESSION['store_id'] = $_POST['store_id'];
        $item = array ( array() );


        if (isset($_GET['notes'])) {
            $note = $_GET['notes'];
            $ret .= "<script type='text/javascript'>" .
                "sendToQueue(this, '{$_GET['upc']}', 2, '{$_SESSION['session']}','{$note}');" .
                "</script>"
            ;
            unset($_GET['notes']);
        }

        /*
        foreach ($_SESSION as $key => $value) {
            $ret .= $key . '<br>';
            foreach ($value as $keyb => $valueb) {
                $ret .= $keyb . ' :: ' . $valueb . '<br>';
            }
        }
        */

        $ret .= "<div align=\"center\">";
        if ($_SESSION['store_id'] == 1) {
            $ret .= "<h2>Hillside</h2>";
        } else {
            $ret .= "<h2>Denfeld</h2>";
        }
        $ret .= "<strong>" . $_SESSION['session'] . "</strong>";
        $ret .= "</div>";
        if (isset($_GET['upc'])) {
            $ret .= '
                    <div align="center">
                        <h5><b>UPC:</b> ' . str_pad($_GET['upc'], 13, '0', STR_PAD_LEFT) . '</h5>
                    </div>
            ';
        }

        if ($_SESSION['store_id'] == NULL) {
            $ret .= "<strong class=\"red\" text-align=\"justified\">
                WARNING : YOU HAVE NOT SELECTED
                A <b>STORE</b>.<br> NO ITEMS WILL BE UPDATED IN BATCH 
                CHECK. <br>PLEASE SELECT A STORE AT BOTTOM OF 
                PAGE.</strong><br><br>";
        }

        if ($_SESSION['session'] == NULL) {
            $ret .= "<strong class=\"red\" text-align=\"center\">
                WARNING : YOU HAVE NOT SELECTED
                A <b>SESSION</b>.<br> NO ITEMS WILL BE UPDATED IN BATCH 
                CHECK. <br>PLEASE SELECT A SESSION AT BOTTOM OF 
                PAGE.</strong>";
        }

        include('../../config.php');
        include('../../common/sqlconnect/SQLManager.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANALTDB, $SCANUSER, $SCANPASS);

        if ($_GET['upc']) {
            
            if ($_GET['upc'] < 99999 && $_GET['upc'] > 9999) {
                $_GET['upc'] = ltrim($_GET['upc'], '0');
                $_GET['upc'] = $this->skuToUpc($_GET['upc'], $dbc);
            }
            
            $_GET['upc'] = str_pad($_GET['upc'], 13, '0', STR_PAD_LEFT);
            $ret .= "<table class='table'  align='center' width='100%'>";

            //* Find UPCs and Queues in Woodshed */
            $query = "
                SELECT 
                    q.queue AS queue_no, 
                    u.brand as ubrand, 
                    u.description as udesc,
                    p.upc, 
                    p.size as psize, 
                    p.normal_price, 
                    v.size as vsize,
                    p.brand as pbrand, 
                    p.description as pdesc,
                    qu.name
                FROM is4c_op.products as p
                    LEFT JOIN is4c_op.productUser as u on u.upc=p.upc 
                    LEFT JOIN SaleChangeQueues as q ON (q.upc=p.upc)
                    LEFT JOIN is4c_op.vendorItems as v on v.upc=p.upc
                    LEFT JOIN queues AS qu ON q.queue=qu.queue
                WHERE p.upc={$_GET['upc']}
                    AND p.store_id={$_SESSION['store_id']}
                    AND q.session='{$_SESSION['session']}'
                GROUP BY p.upc
                    ;";
            $result = $dbc->query($query);
            while ($row = $dbc->fetchRow($result)) {
                //$ret .= "<tr><td><b>upc</td><td>" . $row['upc'] . "</tr>";
                if ($row['ubrand'] != NULL) {
                    $ret .= "<tr><td><b>brand</td><td>" . $row['ubrand'] . "</tr>";
                } else {
                    $ret .= "<tr><td><b>brand</td><td>" . $row['pbrand'] . "</tr>";
                }
                
                if ($row['udesc'] != NULL) {
                    $ret .= "<tr><td><b>product </td><td>" . $row['udesc'] . "</tr>";
                } else {
                    $ret .= "<tr><td><b>product </td><td>" . $row['pdesc'] . "</tr>";
                }
                
                if ($row['psize'] == NULL) {
                    $ret .= "<tr><td><b>size</td><td>" . $row['psize'] . "</tr>";
                } else {
                    $ret .= "<tr><td><b>size</td><td>" . $row['vsize'] . "</tr>";
                }
                
                /*
                if ($row['queue_no'] != NULL)  {
                    if ($row['queue_no'] === "1") {
                        $ret .= "<tr><td><b>queue</td><td><span class='code btn-success'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
                    }
                    if($row['queue_no'] === "2") {
                        $ret .= "<tr><td><b>queue</td><td><span class='code btn-danger'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
                    } 
                    if($row['queue_no'] === "0") {
                        $ret .= "<tr><td><b>queue</td><td><span class='code btn-inverse'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
                    }
                    if($row['queue_no'] === "99") {
                        $ret .= "<tr><td><b>queue</td><td><span class='code btn-info'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
                    } 
                    if($row['queue_no'] === "8") {
                        $ret .= "<tr><td><b>queue</td><td><span class='code btn-warning'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
                    } 
                    if($row['queue_no'] === "7") {
                        $ret .= "<tr><td><b>queue</td><td><span class='code btn-surprise'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
                    } 
                    if($row['queue_no'] === "9") {
                        $ret .= "<tr><td><b>queue</td><td><span class='code btn-inverse'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
                    }
                } elseif ($row['queue'] == NULL) {
                    $ret .= "<tr><td><b>queue</td><td><i class=\"red\">This items is queue-less</tr>";
                }
                $ret .= "<tr><td><b>Price</td><td>" . "$" . $row['normal_price'] . "</tr>";
            */
            
            $queueCode = array(1=>'success',2=>'danger',0=>'default',99=>'info',8=>'warning',7=>'surprise',9=>'inverse');
            if ($row['queue_no'] != NULL)  {
                $ret .= "<tr><td><b>queue</td><td><span class='code btn-".$queueCode[$row['queue_no']]."'>" . $row['queue_no'] . " - " . $row['name'] . "</span></tr>";
            } elseif ($row['queue'] == NULL) {
                $ret .= "<tr><td><b>queue</td><td><i class=\"red\">This items is queue-less</tr>";
            }
            $ret .= "<tr><td><b>Price</td><td>" . "$" . $row['normal_price'] . "</tr>";
                
            }
            

            //  Procure batches from stardate
            $query = "select batchID, owner 
                    from is4c_op.batches 
                    where CURDATE() BETWEEN startDate AND endDate
                    ;";
            $result = $dbc->query($query);
            while ($row = $dbc->fetchRow($result)) {
                $batchID[] = $row['batchID'];
                $owner[] = $row['owner'];
            }
            if ($dbc->error()) {
                $ret .= $dbc->error(). "<br>";
            }

            // Procure Product Information from batchList
            $query = "SELECT l.upc, l.salePrice, b.batchName
                FROM is4c_op.batches AS b 
                LEFT JOIN is4c_op.batchList AS l ON l.batchID=b.batchID 
                WHERE CURDATE() BETWEEN b.startDate AND b.endDate 
                    AND l.upc={$_GET['upc']}
                ;";
            $result = $dbc->query($query);
            while ($row = $dbc->fetchRow($result)) {
                $ret .= "<tr><td><b>sale price</td><td class=\"text-info\">" . $row['salePrice'] . "</tr>";
                $ret .= "<tr><td><b>batch name</td><td>" . $row['batchName'] . "</tr>";
            } 
            if ($dbc->error()) {
                $ret .= $dbc->error(). "<br>";
            }

            $ret .= "</table>";
        }

        $ret .= "<div align='center'>";
        $ret .= "<div class='btn-container'><button class=\"btn btn-success\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 1, '{$_SESSION['session']}','NULL'); return false;\">Check Sign</button></div>";
        $ret .= "<div class='btn-container'><button class=\"btn btn-info\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 99, '{$_SESSION['session']}','NULL'); return false;\">Add Item to Queue</button></div>";
        $ret .= "<div class='btn-container'><button class=\"btn btn-warning\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 8, '{$_SESSION['session']}'); return false;\">Missing Sign</button></div>";
        $ret .= '<tr id="noteTr" class="collapse"><div id="ajax-form"></div>';
        $ret .= "<div class='btn-container'><button class=\"btn btn-danger\" id=\"errBtn\" type=\"button\" onclick=\"getErrNote('{$_GET['upc']}'); return false;\">Write Note</button></div>";
        $ret .= "<div class='btn-container'><button class=\"btn btn-surprise\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 7, '{$_SESSION['session']}','NULL'); return false;\">Shelf Tag Missing</button></div>";
        $ret .= "<div class='btn-container'><button class=\"btn btn-default btn-inverse\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 9, '{$_SESSION['session']}','NULL'); return false;\">Generic Sign Needed</button></div>";
        $ret .= '<br />';
        $ret .= "</div>";

        $query = "SELECT session 
                FROM SaleChangeQueues
                GROUP BY session
                ;";
            $result = $dbc->query($query);
            while ($row = $dbc->fetchRow($result)) {
                $session[] = $row['session'];
            }
            $ret .=  ('
                <div class="text-center container">
                <div align="center">
                <form method="post" class="form-inline">
                    <select class="form-control" name="session" style="width: 80%">
                        <option value="">select a session</option>');
                        
            foreach ($session as $key => $sessID) {
                $ret .=  '<option value="' . $sessID . '">' . $sessID . '</option>';
            }

            $ret .=  ('    
                    </select>
                </div>
                </div>');
                
            $ret .=  '
                <div class="text-center container">
                <div align="center">
                <form method="post" class="form-inline">
                    <select class="form-control" name="store_id" style="width: 80%;">
                        <option value="">select a store</option>
                        <option value="1">Hillside</option>
                        <option value="2">Denfeld</option>
                    </select>
                    <br>
                    <input type="submit" class="btn btn-default btn-wide" value="Update Session & Store ID">
                </form>
                </div>
            ';
            
            $ret .= $this->EOP();
            
            return $ret;
    }
    
    private function skuToUpc($upc,$dbc)
    {
        $queryStr = 'SELECT upc
            FROM is4c_op.vendorItems
            WHERE vendorID = 1
            AND sku like "%'.$upc.'%" 
                AND (size = "#" 
                    OR size = "LB" 
                    OR size = "3/3.33LB" 
                    OR size = "5 GAL" 
                    OR size = "5 LB")
        ';
        $query = $dbc->prepare($queryStr);
        $result = $dbc->execute($query);
        while ($row = $dbc->fetchRow($result)) {
            $ourUpc = $row['upc'];
        }
        
        return $ourUpc;
    }
    
    public function form_content()
    {
        return '
            <form class="form-inline" method="get" name="MyForm" id="my-form">
              <div class="text-center container" style="text-align:center">
                <input class="form-control" type="text" name="upc" id="upc" placeholder="Scan Item">
                <input type="submit" value="go" hidden>
              </div>
            </form>
            <div id="ajax-resp" style="font-weight:bold; font-size: 8pt; position: fixed; top: 75px; width: 100%;"></div>
        ';
    }
    
    private function EOP()
    {
        return '<br><br>
<span class="btn-group">
    <a class="btn btn-default btn-sm fancyboxLink" href="http://192.168.1.2/scancoord/item/SalesChange/SalesChangeIndex.php" title="Scanning Tools">Scanning<br>Tools</a>
    <a class="btn btn-default btn-sm fancyboxLink" href="http://192.168.1.2/git/fannie/item/handheld/ItemStatusPage.php" title="Status Check">Status<br>Check</a>
    <a class="btn btn-default btn-sm fancyboxLink" href="http://192.168.1.2/git/fannie/item/CoopDealsLookupPage.php" title="cd_check">Co-op Deals<br>File Check</a>
</span>


<div align="center">
<br><br><br>
<br /><br />';

    }
    
    public function css_content()
    {
        return '
            button {
                width: 80%;
                border-radius: 5px;
                font-size: 18;
            }
            .btn-wide {
                width: 80%;
            }
            .btn-group {
                width: 80%;
            }
            .btn-container {
                padding: 10px;
            }
            .fancyboxLink {
                width: 29vw;
            }
            .red {
                color: tomato;
            }
            a {
                font-size: 18;
                text-align: center;
            }
            table, tr, td, th {
                border-top: none !important;
                padding: none;   
                font-size: 12px;
            }
            .code {
                padding: 3px;
                border-radius: 3px;
            }            
        ';
    }
    
    
    public function javascriptContent() 
    {
        
        ob_start();
        ?>
$('#myTabs a').click(function (e) {
  e.preventDefault()
  $(this).tab('show')
})

function button(button, href) {
    window.open(href, '_blank');
}

function getErrNote(upc)
{
    $.ajax({
        url: 'salesChangeAjaxErrSigns.php',
        data: 'upc='+upc,
        success: function(response)
        {
            $('#ajax-form').html(response);
            $('#errBtn').hide();
            $('#noteTr').show();
        }
    });
}
        <?php
        return ob_get_clean();
        
    }
}
scancoordDispatch::conditionalExec();
