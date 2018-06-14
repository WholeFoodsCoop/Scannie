<?php
/*******************************************************************************
    Copyright 2016 Whole Foods Community Co-op.

    This file is a part of Scannie.

    Scannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
    Scannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    in the file LICENSE along with Scannie; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include(__DIR__.'/../../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../../common/ui/CorePage.php');
}
class SCScanner extends scancoordDispatch
{
    protected $title = "Sales Change Scanner";
    protected $description = "[Sales Change Scanner] is the portion of 
        batch check tools used for scanning barcodes.";
    protected $ui = FALSE;
    protected $enable_linea = true;
    
    public function body_content()
    {

        $_GET['upc'] = scanLib::padUPC($_GET['upc']);
        $upc = $_GET['upc']; 
        $upc = substr($upc,0,-1);
        
        $ret = '';
        $ret .= $this->form_content();
        
        if (isset($_POST['session'])) {
            $_SESSION['session'] = $_POST['session'];
        }
        if (isset($_POST['store_id'])) {
            $_SESSION['store_id'] = $_POST['store_id'];
        }
        $item = array ( array() );

        if (isset($_GET['notes'])) {
            $note = $_GET['notes'];
            $ret .= "<script type='text/javascript'>" .
                "sendToQueue(this, '{$upc}', 2, '{$_SESSION['session']}','{$note}');" .
                "</script>"
            ;
            unset($_GET['notes']);
        }

        $ret .= "<div align=\"center\">";
        if ($_SESSION['store_id'] == 1) {
            $ret .= "<h2>Hillside</h2>";
        } else {
            $ret .= "<h2>Denfeld</h2>";
        }
        $ret .= "<strong>" . $_SESSION['session'] . "</strong>";
        $ret .= "</div>";
        if (isset($upc)) {
            $ret .= '
                    <div align="center">
                        <h5><b>UPC:</b> ' . $upc . '</h5>
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

        if ($upc) {
            
            $sesh = $_SESSION['session'];
            $sid = $_SESSION['store_id'];
            
            if ($upc < 99999 && $upc > 9999) {
                $upc = ltrim($upc, '0');
                $upc = $this->skuToUpc($upc, $dbc);
            }
            
            $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
            $ret .= "<table class='table'  align='center' width='100%'>";
            
            /* find all queues for upc */
            $args = array($upc,$sid,$sesh);
            $prep = $dbc->prepare("SELECT s.queue, q.name FROM SaleChangeQueues AS s
                LEFT JOIN queues AS q ON q.queue=s.queue
                WHERE upc = ? AND store_id = ? AND session = ?");
            $res = $dbc->execute($prep,$args);
            $allQueues = array();
            while ($row = $dbc->fetchRow($res)) {
                $allQueues[$row['queue']] = $row['name'];
            }
            if ($er = $dbc->error()) {
                echo '<div class="alert alert-danger">'.$er.'</div>';
            }

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
                WHERE p.upc={$upc}
                    AND p.store_id={$sid}
                    AND q.session='{$sesh}'
                GROUP BY p.upc;";
            $result = $dbc->query($query);
            while ($row = $dbc->fetchRow($result)) {
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
            
            $queueCode = array(1=>'success',2=>'danger',0=>'default',99=>'info',8=>'warning',7=>'surprise',9=>'inverse');
            if (count($allQueues) > 0)  {
                foreach ($allQueues as $q => $name) {
                    $ret .= "<tr><td></td><td><span class='alert-".$queueCode[$q]."'
                        style='padding: 5px;'>" . $q . " - " . $name . "</span></tr>";
                }
            } elseif ($row['queue'] == NULL) {
                $ret .= "<tr><td></td><td><i class=\"red\">This items is queue-less</tr>";
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
                    AND l.upc={$upc}
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
        $ret .= "<div class='btn-container'><button class=\"btn btn-success\" type=\"button\" onclick=\"sendToQueue(this, '{$upc}', 1, '{$_SESSION['session']}','NULL'); return false;\">Check Sign</button></div>";
        $ret .= "<div class='btn-container'><button class=\"btn btn-info\" type=\"button\" onclick=\"sendToQueue(this, '{$upc}', 99, '{$_SESSION['session']}','NULL'); return false;\">Add Item to Queue</button></div>";
        $ret .= "<div class='btn-container'><button class=\"btn btn-warning\" type=\"button\" onclick=\"sendToQueue(this, '{$upc}', 8, '{$_SESSION['session']}'); return false;\">Missing Sign</button></div>";
        $ret .= '<tr id="noteTr" class="collapse"><div id="ajax-form"></div>';
        $ret .= "<div class='btn-container'><button class=\"btn btn-danger\" id=\"errBtn\" type=\"button\" onclick=\"getErrNote('{$upc}'); return false;\">Write Note</button></div>";
        $ret .= "<div class='btn-container'><button class=\"btn btn-surprise\" type=\"button\" onclick=\"sendToQueue(this, '{$upc}', 7, '{$_SESSION['session']}','NULL'); return false;\">Shelf Tag Missing</button></div>";
        $ret .= "<div class='btn-container'><button class=\"btn btn-default btn-inverse\" type=\"button\" onclick=\"sendToQueue(this, '{$upc}', 9, '{$_SESSION['session']}','NULL'); return false;\">Generic Sign Needed</button></div>";
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
            $this->addScript('SCScanner.js');
            
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
            <form class="form-inline" method="get" name="MyForm" id="upc-form">
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
        include(__DIR__.'/../../config.php');
        return <<<HTML
body {
    background: red; /* For browsers that do not support gradients */
    background: -webkit-linear-gradient(left top, lightblue, white); /* For Safari 5.1 to 6.0 */
    background: -o-linear-gradient(bottom right, lightblue, white); /* For Opera 11.1 to 12.0 */
    background: -moz-linear-gradient(bottom lightblue, white); /* For Firefox 3.6 to 15 */
    background: linear-gradient(130deg, #EC804E 0%, #44311F 100%); /* Standard syntax */
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
    background-size: cover;
}
input, select, .form-control {
    background-color: rgba(255,255,255,0.2);
    border: rgba(255,255,255,0.8);
    color: rgba(0,0,0,0.4);
}
.btn {
    border: rgba(255,255,255,1);
}
.alert {
    width: 90%;
}
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
HTML;
    }
    
}
scancoordDispatch::conditionalExec();
