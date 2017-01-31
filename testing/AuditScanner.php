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

include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}

class AuditScanner extends ScancoordDispatch
{
    
    protected $title = "Audit Scanner";
    protected $description = "[Audit Scanner] Scan page designed solely for shelf 
        tag auditing.";
    protected $ui = FALSE;
    protected $add_css_content = TRUE;
    protected $add_javascript_content = TRUE;
    
    private function notedata_handler($dbc,$note)
    {
        $ret = '';
        $upc = $_GET['upc'];
        $args = array($note,$upc);
        $query = $dbc->prepare("UPDATE woodshed_no_replicate.AuditScanner SET notes = ? WHERE upc = ?;");
        $result = $dbc->execute($query,$args);
        if ($dbc->error()) $ret .= '<div class="alert alert-danger">'.$dbc->error().'</div>';
        
        if ($dbc->affectedRows()) {
            $ret .= '<div align="center" id="note-resp" class="alert alert-success" style="posotion: fixed; top: 0; left: 0; ">
                    Note Saved Successfully<br /><br />
                    <a class="btn btn-success" href="http://192.168.1.2/scancoord/testing/AuditScanner.php">Continue</a><br />
                </div>';
        } else {
            $ret .= '<div align="center" id="note-resp" class="alert alert-danger">
                    Error Saving Note<br /><br />
                    <a class="btn btn-danger" href="http://192.168.1.2/scancoord/testing/AuditScanner.php">Continue</a><br />
                </div>';
        }
        
        return $ret;
        
    }
    
    public function body_content()
    {           
        
        $ret = '';
        include('../config.php');
        include('../common/lib/scanLib.php');
        include('../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $storeID = scanLib::getStoreID(); 
        $upc = str_pad($_POST['upc'], 13, 0, STR_PAD_LEFT);
        
        if (isset($_GET['note'])) {
            $note = $_GET['note'];
            $ret .= $this->notedata_handler($dbc,$note);
        }
        
        $ret .= '<div align="center"><h4 id="heading">AUDIE: THE AUDIT SCANNER</h4></div>';
        $ret .= $this->form_content();
        
        //Gather product SALE information
        $saleQuery = $dbc->prepare("
            SELECT b.batchName, bl.salePrice, b.batchID
            FROM batches AS b 
                LEFT JOIN batchList AS bl ON b.batchID=bl.batchID 
            WHERE curdate() BETWEEN b.startDate AND b.endDate 
                AND bl.upc = ?;");
        $saleQres = $dbc->execute($saleQuery,$upc);
        //$batchList = array( 'price' => array(), 'batchID' => array(), 'batchName' => array() );
        while ($row = $dbc->fetchRow($saleQres)) {
            $batchList['price'][] = $row['salePrice'];
            $batchList['batchID'][] = $row['batchID'];
            $batchList['batchName'][] = $row['batchName'];
        }
        if (count($batchList) > 0) {
            $saleButtonClass = 'success';
            $saleStatus = 'On Sale';
        } else {
            $saleButtonClass = 'inverse';
            $saleStatus = 'not on sale';
        }
        $saleInfoStr = '';
        foreach ($batchList['price'] as $k => $v) {
            $saleInfoStr .= '
                <span class="sm-label">PRICE: </span>$<span class="text-sale">'.$v.'</span> 
                <span class="sm-label">ID: </span>'.$batchList['batchID'][$k].'<br /> 
                <span class="sm-label">BATCH: </span>'.$batchList['batchName'][$k].'
                <br /><br />
                <div style="border: 1px solid lightgrey; width: 20vw"></div>
                <br />
            ';
        }
        //echo '<h1>'.$saleInfoStr.'</h1>';
        
        //Gather product information
        $args = array($storeID,$upc);
        $query = $dbc->prepare("
            SELECT
                p.cost, 
                p.normal_price,  
                p.description,
                p.brand,
                p.default_vendor_id,
                p.inUse,
                v.vendorName,
                vi.vendorDept,
                p.department,
                d.dept_name,
                p.price_rule_id,
                vd.margin AS unfiMarg,
                d.margin AS deptMarg,
                pu.description AS signdesc,
                pu.brand AS signbrand,
                case when n.upc is not null then '<span class=\'alert-warning\'>Flagged Narrow</span>' else NULL end as narrow
            FROM products AS p
                LEFT JOIN productUser AS pu ON p.upc = pu.upc
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID 
                LEFT JOIN vendorItems AS vi 
                    ON p.upc = vi.upc
                        AND p.default_vendor_id = vi.vendorID
                LEFT JOIN vendorDepartments AS vd 
                    ON vd.vendorID = p.default_vendor_id 
                        AND vd.deptID = vi.vendorDept
                LEFT JOIN NarrowTags AS n ON p.upc=n.upc
            WHERE p.store_id = ?
                AND p.upc = ?
            LIMIT 1
        ");
        $result = $dbc->execute($query,$args);
        while ($row = $dbc->fetchRow($result)) {
            $cost = $row['cost'];
            $price = $row['normal_price'];
            $desc = $row['description'];
            $brand = $row['brand'];
            $vendor = '<span class="vid">id['.$row['default_vendor_id'].'] </span>'.$row['vendorName'];
            $vd = $row['default_vendor_id'].' '.$row['vendorName'];
            $dept = $row['department'].' '.$row['dept_name'];
            $pid = $row['price_rule_id'];
            $unfiMarg = $row['unfiMarg'];
            $deptMarg = $row['deptMarg'];
            $signDesc = $row['signdesc'];
            $signBrand = $row['signbrand'];
            $inUse = $row['inUse'];
            $narrow = $row['narrow'];
            
            if ($row['default_vendor_id'] == 1) {
                $dMargin = $row['unfiMarg'];
            } else {
                $dMargin = $row['deptMarg'];
            }   
        }
        if ($dbc->error()) echo $dbc->error();
        
        $margin = ($price - $cost) / $price;
        $rSrp = $cost / (1 - $dMargin);
        $srp = $rounder->round($rSrp);
        $sMargin = ($srp - $cost ) / $srp;
        
        $sWarn = 'default';
        if ($srp != $price) {
            if ($srp > $price) {
                
            } else { //$srp < $price
                $peroff = $srp / $price;
                if ($peroff < .05) {
                    $sWarn = '';
                } elseif ($peroff > .15 && $peroff < .30) {
                    $sWarn = 'warning';
                } else {
                    $sWarn = 'danger';
                }
            }
        }
        
        $data = array('cost'=>$cost,'price'=>$price,'desc'=>$desc,'brand'=>$brand,'vendor'=>$vd,'upc'=>$upc,
            'dept'=>$dept,'margin'=>$margin,'rsrp'=>$rSrp,'srp'=>$srp,'smarg'=>$sMargin,'warning'=>$sWarn,
            'pid'=>$pid,'dMargin'=>$dMargin);
        $this->record_data_handler($data);
        
        $warning = array();
        $margOff = ($margin / $dMargin);
        if ($margOff > 1.05) {
            $warning['margin'] = 'info';
        } elseif ($margOff > 0.95) {
            $warning['margin'] = 'none';
        } elseif ($margOff < 0.95 && $margOff > 0.90) {
            $warning['margin'] = 'warning';
        } else {
            $warning['margin'] = 'danger';
        }
        
        $priceOff = ($price / $srp);
        if ($priceOff > 1.05) {
            $warning['price'] = 'info';
        } elseif ($priceOff > 0.95) {
            $warning['price'] = 'none';
        } elseif ($priceOff < 0.95 && $priceOff > 0.90) {
            $warning['price'] = 'warning';
        } else {
            $warning['price'] = 'danger';
        }
     
        $ret .= '
            <div align="center">
                <div class="container">
                    <div class="row">
                        <div class="col-xs-4 info" > 
                            <div style="float: left; color: grey">cost</div><br />'.$cost.'<br />&nbsp;
                        </div> 
                        <div class="col-xs-4 info" > 
                            <div style="float: left; color: grey">price</div><br /><span class="text-'.$warning['price'].'" style="font-weight: bold; ">'.$price.'</span><br />&nbsp;
                        </div> 
                        <div class="col-xs-4 info" >
                            <div style="float: left; color: grey">margin</div><br /><span class="text-'.$warning['margin'].'">'.sprintf('%0.2f%%',$margin*100).'</span> 
                                <br /> <span class="text-tiny">target: </span><span style="color: grey; text-shadow: 0px  0px 1px white">'.($dMargin*100).'%</span>
                        </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-4 info" > 
                            <div style="float: left; color: grey"> raw </div><br />'.sprintf('%0.2f',$rSrp).'
                        </div> 
                        <div class="col-xs-4 info" >
                            <div style="float: left; color: grey" class="text-'.$sWarn.'">srp</div><br />'.$srp.'
                        </div> 
                        <div class="col-xs-4 info" >
                            <div style="float: left; color: grey">newMarg</div><br />'.sprintf('%0.2f%%',$sMargin*100).'
                        </div> 
                    </div>
                    <br />
                    <div class="row">
                        <div class="col-xs-12 info" >'.$desc.' </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-12 info" ><span class="sm-label">BRAND: </span> '.$brand.' </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-12 info" ><span class="sm-label">VENDOR: </span> '.$vendor.' </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-12 info" ><span class="sm-label">DEPT: </span> '.$dept.' </div> 
                    </div>'
                ;
                    
                if (!$inUse) {
                    $ret .= '
                        <div class="row">
                            <div class="col-xs-12 info" ><span class="text-danger" style="font-weight: bold;">
                                THIS PRODUCT IS NOT IN USE
                            </span></div> 
                        </div>
                    ';
                }
                    
                $ret .= '
                    <div class="row">
                        <div class="col-xs-12" > &nbsp;</div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-12 info" ><span class="sm-label">SIGN: </span> '.$signDesc.' </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-12 info" ><span class="sm-label">S.BRAND: </span> '.$signBrand.' </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-12 info" >
                            <button data-toggle="collapse" data-target="#sale-info" class="btn btn-clear btn-xs">
                                <span class="text-'.$saleButtonClass.'" style="font-weight: bold; ">'.$saleStatus.' </span>
                                <span class="caret text-'.$saleButtonClass.'"></span>
                            </button>
                            '.$narrow.'
                        </div> 
                    </div>
                    
                    
                        <div class="collapse" id="sale-info">
                            <div class="row">
                                <div class="col-xs-12 info">
                                    '.$saleInfoStr.'
                                </div>
                            </div>
                        </div>
                    
                    
                    
                    <div class="container">
                    <br />
                    <div class="row">
                        <div class="col-xs-4  clear btn btn-warning" onClick="queue('.$storeID.'); return false;">Print</div> 
                        <div class="col-xs-4  clear " ><a class="btn btn-surprise" href="http://192.168.1.2/scancoord/testing/AuditScanner.php ">Refresh</a></div> 
                        <div class="col-xs-4  clear btn btn-danger" data-toggle="collapse" data-target="#notepad">Note </div> 
                    </div>
                    <br /><br />
                    <div class="row">
                        <div class="col-xs-4">
                            <a class="text-info" href="AuditScannerReport.php ">View Report</a>                    
                        </div>
                        <div class="col-xs-4">
                        </div>
                        <div class="col-xs-4">
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="ajax-resp"></div>
        ';
        
        //  Commonly used NOTES.
        $ret .= '
            <div id="notepad" class="collapse">
                <div style="position: relative; top: 25%; opacity: 1;">
                    <form method="get" name="notepad" class="form-inline " >
                        <input type="text" name="note" id="note" class="form-control" style="max-width: 90%; "><br /><br />
                        <input type="hidden" name="upc" value="'.$upc.'">
                        <button type="submit" class="btn btn-danger">Submit Note</button>
                    </form>
                    <!-- Purple Buttons -->
                    <div align="left" style="padding: 10px; float: left; width: 40vw">
                        <div>
                            <button class="btn btn-surprise btn-xs btn-msg" onClick="qm(\'Missing Sign Info.\'); return false; ">
                                Missing Sign Info</button><br /><br />
                            <button class="btn btn-surprise btn-xs btn-msg" onClick="qm(\'Incorrect Sign Info.\'); return false; ">
                                Incorrect Sign Info.</button><br /><br />
                            <button class="btn btn-surprise btn-xs btn-msg" onClick="qm(\'Line Price Issue. Line Price: \'); return false; ">
                                Line Price Issue</button><br /><br />
                            <button class="btn btn-surprise btn-xs btn-msg" onClick="qm(\'Price Change: \'); return false; ">
                                Price Change</button><br /><br />
                            <button class="btn btn-warning btn-xs btn-msg" onClick="qm(\'Print Tag \'); return false; ">
                                Print Tag</button><br /><br />
                    </div>
                    <!-- Green Buttons -->
                    </div>
                    <div align="left" style="padding: 10px; float: left; width: 10vw"></div>
                    <div align="left" style="padding: 10px; float: left; width: 40vw">
                        <button class="btn btn-success btn-xs btn-msg" onClick="qm(\'Missing Sale Sign\'); return false; ">
                            Missing Sale Sign</button><br /><br />
                        <button class="btn btn-success btn-xs btn-msg" onClick="qm(\'Queue Narrow Tag \'); return false; ">
                            Narrow Tag</button><br /><br />
                        <button class="btn btn-success btn-xs btn-msg" onClick="qm(\'Product Not In Use\'); return false; ">
                            Not In Use</button><br /><br />
                        <button class="btn btn-success btn-xs btn-msg" onClick="qm(\'Price Discrepancy: \'); return false; ">
                            Price Discrapancy</button><br /><br />
                    </div>
                </div>
            </div>
        </div>
        ';
        
        
        
        $ret .= '<br /><br /><br /><br /><br /><br />';
        
        return $ret;
    }
    
    private function form_content($dbc) 
    {
        
        $upc = str_pad($_POST['upc'], 13, 0, STR_PAD_LEFT);
        $ret .= '';
        $ret .= '
            <div align="center">
                <form method="post" class="form-inline" id="my-form">
                    <input class="form-control input-sm info" name="upc" id="upc" value="'.$upc.'"
                        style="text-align: center; width: 140px; border: none;">
                    <button type="submit" hidden></button>
                </form>
            </div>
        ';
        
        return $ret;
        
    }
    
    private function record_data_handler($data)
    {
        
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANALTDB, $SCANUSER, $SCANPASS);
        //$storeID = scanLib::getStoreID();
        
        $prepA = $dbc->prepare("SELECT * FROM AuditScanner WHERE upc = ? LIMIT 1");
        $resA = $dbc->execute($prepA,$data['upc']);
        if ($dbc->numRows($resA) == 0) {
            $args = array(
                $data['upc'],
                $data['desc'],
                $data['price'],
                $data['margin'],
                $data['dMargin'],
                $data['dept'],
                $data['vendor'],
                $data['rsrp'],
                $data['srp'],
                $data['pid'],
                $data['warning'],
                $data['cost']
            );
            $prep = $dbc->prepare("
                INSERT INTO AuditScanner 
                (
                    upc, description, price, curMarg, desMarg, dept, 
                        vendor, rsrp, srp, prid, flag, cost
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                );
            ");
            $dbc->execute($prep,$args);
            if ($dbc->error()) echo '<div class="alert alert-warning>' . $dbc->error() . '</div>';
        } else {
            return false;
        }
        
    }
    
    public function css_content() 
    {
        return '
            #heading {
                color: grey;
                font-size: 10px;
            }
            .info {
                opacity: 0.9;
                //background: linear-gradient(#7c7c7c,#272822);
                background: rgba(39, 40, 34, .05);
                border-radius: 2px;
                padding: 5px;
            }
            body {
                background-image: url(\'../common/src/img/lbgrad.png\');
            }
            .vid {
                color: #525259;
            }
            .clear {
                opacity: 0.8;
            }
            #ajax-resp {
                position: fixed;
                top: 60;
                width: 100%;
            }
            .fixed-resp {
                position: fixed;
                top: 60;
                width: 100%;
            }
            #notepad {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                width: 100%;
                background: linear-gradient(#d99696, #d64f4f);
                opacity: 0.8
            }
            /*
            #note-resp {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                width: 100%;
                horizonal-align: middle;
                font-size: 26px;
            }*/
            .note-input {
                background-color: #fceded;
            }
            .sm-label {
                font-size: 10px; 
                color: #6f6f80;
            }
            .text-tiny {
                font-size: 8px; 
                color: #6f6f80;
            }
            .text-sale {
                color: green; 
                font-weight: bold;     
            }
            .btn-msg {
                width: 150px;
            }
        ';
    }
    
    public function javascript_content()
    {
        return '
<script type="text/javascript" src="/git/fannie/src/javascript/jquery.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/cordova-2.2.0.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/ScannerLib-Linea-2.0.0.js"></script>
<script type="text/javascript" src="../item/SalesChange/scanner.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    enableLinea(\'#upc\', function(){$(\'#my-form\').submit();});
});
</script>
<script type="text/javascript">
function queue(store_id)
{
    var upcB = document.getElementById("upc").value;
    $.ajax({
		type: \'post\',
        url: \'AuditUpdate.php\',
        data: \'upc=\'+upcB+\'&store_id=\'+store_id,
		error: function(xhr, status, error)
		{ 
			alert(\'error:\' + status + \':\' + error + \':\' + xhr.responseText) 
		},
        success: function(response)
        {
            $(\'#ajax-resp\').html(response);
        }
    })
	.done(function(data){
        
	})
}
</script>
<script>
$( "button" ).click(function() {
  var text = $( this ).text();
  $( "note" ).val( text );
});
</script>
<script>
function qm(msg)
{
    document.getElementById("note").value = msg;
}
</script>
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
