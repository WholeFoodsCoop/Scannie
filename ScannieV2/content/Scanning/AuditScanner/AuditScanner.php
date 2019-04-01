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
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../../common/sqlconnect/SQLManager.php');
}
class AuditScanner extends PageLayoutA 
{

    protected $title = "Audit Scanner";
    protected $description = "[Audit Scanner] is a light-weight, all around product
        scanner for use with iUnfi iPod Touch scanners.";
    protected $ui = false;
    protected $use_preprocess = TRUE;
    protected $must_authenticate = TRUE;
    protected $enable_linea = true;

    public function preprocess()
    {

        include(__DIR__.'/../../../config.php');

        $username = scanLib::getUser();
        $dbc = scanLib::getConObj();
        if (!$username) {
            header('location: ../../../auth/login.php');
        }

        $action = FormLib::get('action');
        echo $action;
        $upc = FormLib::get('upc');

        if ($action == 'mod-narrow') {
            $this->mod_narrow_handler($upc);
            die();
        } elseif ($action == 'mod-in-use') {
            $this->mod_inuse_handler($upc);
            die();
        } elseif ($action == 'mod-edit') {
            $this->mod_edit_handler($upc);
            die();
        }

        if (isset($_GET['note'])) {
            $note = $_GET['note'];
            $error = $this->notedata_handler($dbc,$note,$username);
            if (!$error) {
                header('location: http://'.$MY_ROOTDIR.'/content/Scanning/AuditScanner/AuditScanner.php?success=true');
            } else {
                header('location: http://'.$MY_ROOTDIR.'/content/Scanning/AuditScanner/AuditScanner.php?success=false');
            }
        }

    }

    private function mod_edit_handler($upc)
    {
        $dbc = scanLib::getConObj();
        $table = FormLib::get('table');
        $column = FormLib::get('column');
        $newtext = FormLib::get('newtext');

        $args = array($newtext, $upc);
        $query = "UPDATE $table SET $column = ? WHERE upc = ?";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);

        return false;
    }

    private function mod_narrow_handler($upc)
    {
        $dbc = scanLib::getConObj();
        $args = array($upc);
        $prep = $dbc->prepare("SELECT upc FROM productUser WHERE upc = ? AND narrow = 1");
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $narrow = $row['upc'];
        }
        echo $narrow;
        if ($narrow > 0) {
            $prep = $dbc->prepare("UPDATE productUser SET narrow = 0 WHERE upc = ?");
            $res = $dbc->execute($prep, $args);
        } else {
            $prep = $dbc->prepare("UPDATE productUser SET narrow = 1 WHERE upc = ?");
            $res = $dbc->execute($prep, $args);
        }

        return false;
    }

    private function mod_inuse_handler($upc)
    {
        $dbc = scanLib::getConObj();
        $store = scanLib::getStoreID();
        $args = array($upc, $store);
        $prep = $dbc->prepare("SELECT inUse FROM products WHERE upc = ? AND store_id = ?;");
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $inUse = $row['inUse'];
        }
        echo "\n";
        if ($inUse == 0) {
            $prep = $dbc->prepare("UPDATE products SET inUse = 1 WHERE upc = ? AND store_id = ?");
            $res = $dbc->execute($prep, $args);
            echo "Product now IN-use";
        } else {
            $prep = $dbc->prepare("UPDATE products SET inUse = 0 WHERE upc = ? AND store_id = ?");
            $res = $dbc->execute($prep, $args);
            echo "Product now NOT in-use";
        }

        return false;
    }

    public function pBar($weekPar,$deptNo,$storeID,$dbc)
    {
        if ($_SESSION['audieDept'] != $deptNo) {
            $args = array($storeID,$deptNo);
            $multiplier = ($storeID == 1) ? 3 : 7;
            $query = "
                SELECT auto_par, auto_par*$multiplier as par, upc, brand, description
                FROM products
                WHERE store_id = ?
                    AND department = ?
                ORDER BY auto_par DESC
                LIMIT 1";
            $prep = $dbc->prepare($query);
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $max = $row['par'];
            }
        } else {
            $max = $_SESSION['maxPar'];
        }
        $_SESSION['audieDept'] = $deptNo;
        $_SESSION['maxPar'] = $max;

        $percent = 100*($weekPar/$max);
        $oppo = $max-$percent;
        return <<<HTML
<div align="center" id="pBar" style="height: 1px;">
    <div class="progress" style="width: 100px; height: 11px;">
        <div class="progress-bar progress-bar-success" role="progressbar" style="width:{$percent}%;"></div>
        <div class="progress-bar progress-bar-default" role="progressbar" style="width:{$oppo}%; "></div>
    </div>
</div>
HTML;
    }

    private function notedata_handler($dbc,$note,$username)
    {
        $ret = '';
        $upc = ScanLib::upcParse($_GET['upc']);
        //echo $upc;
        $args = array($note,$upc,$username);
        $query = $dbc->prepare("UPDATE woodshed_no_replicate.AuditScanner
            SET notes = ? WHERE upc = ? AND username = ?;");
        $result = $dbc->execute($query,$args);
        $error = 0;
        if ($dbc->error()) {
            $error = 1;
        }

        if ($dbc->affectedRows()) {
            $error = 0;
        } else {
            $error = 2;

        }

        return $error;

    }

    public function body_content()
    {

        $ret = '';
        $dbc = scanLib::getConObj('SCANALTDB');
        $p = $dbc->prepare("SELECT scanBeep FROM ScannieConfig WHERE session_id = ?");
        $r = $dbc->execute($p, session_id());
        $beep = $dbc->fetchRow($r);
        $beep = $beep[0];
        if ($beep == true) {
            $this->addOnloadCommand("
                WebBarcode.Linea.emitTones(
                    [
                        { 'tone':300, 'duration':50 },
                        { 'tone':600, 'duration':50 },
                        { 'tone':300, 'duration':50 },
                    ] 
                );
            ");
        }
        $dbc = scanLib::getConObj();
        $username = scanLib::getUser();
        $response = $_GET['success'];
        $newscan = $_POST['success'];
        if ($response && $newscan != 'empty') {
            if ($response == TRUE) {
                $ret .= '<div align="center" id="note-resp" class="alert alert-success" style="posotion: fixed; top: 0; left: 0; ">
                    Saved! <span style="font-size: 14px; font-weight: bold; float: right; cursor: pointer;" onclick="$(\'#note-resp\').hide(); return false;"> &nbsp;x </span>
                    </div>';
            } elseif ($response == FALSE) {
                $ret .= '<div align="center" id="note-resp" class="alert alert-danger">
                    Error Saving <span style="font-size: 14px; font-weight: bold; float: right; cursor: pointer;" onclick="$(\'#note-resp\').hide(); return false;"> &nbsp;x </span>
                    </div>';
            }
        }

        include(__DIR__.'/../../../config.php');
        include(__DIR__.'/../../../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        $storeID = scanLib::getStoreID();
        $upc = scanLib::upcParse($_POST['upc']);
        // echo $int = scanLib::getSku($_POST['upc'], $dbc);
        // echo $_POST['upc'];
        // echo $sku = $_POST['sku'];

        $loading .= '
            <div class="progress" id="progressBar">
                <div class="progress-bar progress-bar-striped active" role="progressbar"
                    aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width:100%">
                </div>
            </div>
        ';

        $uid = '<span class="userSymbol-plus"><b>'.strtoupper(substr($username,0,1)).'</b></span>';

        $ret .= $uid;
        $ret .= $this->mobile_menu($upc);
        $ret .= $loading;
        $ret .= '<div align="center"><h4 id="heading">AUDIE: THE AUDIT SCANNER</h4></div>';
        $ret .= $this->form_content();

        //Gather product SALE information
        $saleQueryArgs = array($storeID,$upc);
        $saleQuery = $dbc->prepare("
            SELECT b.batchName, bl.salePrice, b.batchID
            FROM batches AS b
                LEFT JOIN batchList AS bl ON b.batchID=bl.batchID
                INNER JOIN StoreBatchMap AS sbm ON b.batchID=sbm.batchID
            WHERE curdate() BETWEEN b.startDate AND b.endDate
                AND sbm.storeID = ?
                AND bl.upc = ?
                AND b.batchType BETWEEN 1 AND 12;");
        $saleQres = $dbc->execute($saleQuery,$saleQueryArgs);
        //$batchList = array( 'price' => array(), 'batchID' => array(), 'batchName' => array() );
        while ($row = $dbc->fetchRow($saleQres)) {
            $batchList['price'][] = $row['salePrice'];
            $batchList['batchID'][] = $row['batchID'];
            $batchList['batchName'][] = $row['batchName'];
        }
        $isOnSale = false;
        if (count($batchList) > 0) {
            $saleButtonClass = 'success';
            $saleStatus = '* On Sale *';
            $isOnSale = 'true';
        } else {
            $saleButtonClass = 'inverse';
            $saleStatus = 'not on sale';
        }
        if ($isOnSale == true) {
            $ret .= "<style>
                background: green;
                background-color: green;
                color: purple;
            </style>";
        }
        $saleInfoStr = '';
        foreach ($batchList['price'] as $k => $v) {
            $saleInfoStr .= '
                <span class="sm-label">PRICE: </span>$<span class="text-sale">'.$v.'</span>
                <span class="sm-label">ID: </span>'.$batchList['batchID'][$k].'<br />
                <span class="sm-label">BATCH: </span>'.$batchList['batchName'][$k].'
                <br /><br />
                <div style="border: 1px solid lightrgba(255,255,255,0.6); width: 20vw"></div>
                <br />
            ';
        }

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
                p.auto_par,
                v.vendorName,
                vi.vendorDept,
                p.department,
                d.dept_name,
                p.price_rule_id,
                vd.margin AS unfiMarg,
                d.margin AS deptMarg,
                pu.description AS signdesc,
                pu.brand AS signbrand,
                v.shippingMarkup,
                v.discountRate,
                case when pu.narrow=1 then '<span class=\'badge badge-warning\'>Flagged Narrow</span>' else NULL end as narrow
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
            WHERE p.store_id = ?
                AND p.upc = ?
            LIMIT 1
        ");
        $result = $dbc->execute($query,$args);
        $multiplier = ($storeID == 1) ? 3 : 7;
        while ($row = $dbc->fetchRow($result)) {
            $cost = $row['cost'];
            $price = $row['normal_price'];
            $desc = $row['description'];
            $brand = $row['brand'];
            $vendor = '<span class="vid">id['.$row['default_vendor_id'].'] </span>'.$row['vendorName'];
            $vd = $row['default_vendor_id'].' '.$row['vendorName'];
            $dept = $row['department'].' '.$row['dept_name'];
            $deptNo = $row['department'];
            $pid = $row['price_rule_id'];
            $unfiMarg = $row['unfiMarg'];
            $deptMarg = $row['deptMarg'];
            $signDesc = $row['signdesc'];
            $signBrand = $row['signbrand'];
            $inUse = $row['inUse'];
            $narrow = $row['narrow'];
            $markup = $row['shippingMarkup'];
            $discount = $row['discountRate'];
            // Hillside multiplier = 3, Denfeld = 7
            $weekPar = $row['auto_par'] * $multiplier;
            $ret .= '<input type="hidden" id="auto_par_value" value="'.$weekPar.'"/>';
            $ret .= $this->pBar($weekPar,$deptNo,$storeID,$dbc);

            $adjcost = $cost;
            if ($markup > 0) $adjcost += $cost * $markup;
            if ($discount > 0) $adjcost -= $cost * $discount;

            if ($row['default_vendor_id'] == 1) {
                $dMargin = $row['unfiMarg'];
            } else {
                $dMargin = $row['deptMarg'];
            }
        }
        if ($dbc->error()) echo $dbc->error();
        $margin = ($price - $adjcost) / $price;
        $rSrp = $adjcost / (1 - $dMargin);
        $srp = $rounder->round($rSrp);
        $sMargin = ($srp - $adjcost ) / $srp;

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

        $passcost = $cost;
        if ($cost != $adjcost) $passcost = $adjcost;
        $data = array('cost'=>$passcost,'price'=>$price,'desc'=>$desc,'brand'=>$brand,'vendor'=>$vd,'upc'=>$upc,
            'dept'=>$dept,'margin'=>$margin,'rsrp'=>$rSrp,'srp'=>$srp,'smarg'=>$sMargin,'warning'=>$sWarn,
            'pid'=>$pid,'dMargin'=>$dMargin,'storeID'=>$storeID,'username'=>$username);
        $ret .= $this->record_data_handler($data,$username,$storeID);

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

        if ($pid != 0) {
            $price_rule = '<span style="text-shadow: 0.5px 0.5px tomato; color: orange">*</span>
                <span class="text-tiny">pid</span>';
        } else {
            $price_rule = '';
        }

        if ($adjcost != $cost) {
            $adjCostStr = '<span class="text-tiny">adj cost: </span><span style="color: rgba(255,255,255,0.6); text-shadow: 0px  0px 1px white">'.sprintf('%0.2f',$adjcost).'</span>';
        } else {
            $adjCostStr = '&nbsp;';
        }
        $ret .= '
            <div align="center">
                <div class="container" align="center">
                    <div class="row">
                        <div class="col-4 info" >
                            <div style="float: left; color: rgba(255,255,255,0.6)">cost</div><br />'.$cost.'<br />
                                '.$adjCostStr.'
                        </div>
                        <div class="col-4 info" >
                            <div style="float: left; color: rgba(255,255,255,0.6)">price</div><br />
                                <span class="text-'.$warning['price'].'" style="font-weight: bold; font-size: 18px; text-shadow: 1px 1px darkslategrey">
                                    '.$price.'</span>
                                    '.$price_rule.'<br />&nbsp;
                        </div>
                        <div class="col-4 info" >
                            <div style="float: left; color: rgba(255,255,255,0.6)">margin</div><br /><span class="text-'.$warning['margin'].'">'.sprintf('%0.2f%%',$margin*100).'</span>
                                <br /> <span class="text-tiny">target: </span><span style="color: rgba(255,255,255,0.6); text-shadow: 0px  0px 1px white">'.($dMargin*100).'%</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 info" >
                            <div style="float: left; color: rgba(255,255,255,0.6)"> raw </div><br />'.sprintf('%0.2f',$rSrp).'
                        </div>
                        <div class="col-4 info" >
                            <div style="float: left; color: rgba(255,255,255,0.6)" class="text-'.$sWarn.'">srp</div><br />'.$srp.'
                        </div>
                        <div class="col-4 info" >
                            <div style="float: left; color: rgba(255,255,255,0.6)">newMarg</div><br />'.sprintf('%0.2f%%',$sMargin*100).'
                        </div>
                    </div>
                    <br />
                    <div class="row">
                        <div class="col-12 info" >'.$desc.' </div>
                    </div>
                    <div class="row">
                        <div class="col-12 info" ><span class="sm-label">BRAND: </span> '.$brand.' </div>
                    </div>
                    <div class="row">
                        <div class="col-12 info" ><span class="sm-label">VENDOR: </span> '.$vendor.' </div>
                    </div>
                    <div class="row">
                        <div class="col-12 info" ><span class="sm-label">DEPT: </span> '.$dept.' </div>
                    </div>'
                ;

                if (!$inUse) {
                    $ret .= '
                        <div class="row">
                            <div class="col-12 info" ><span class="text-danger" style="font-weight: bold;">
                                THIS PRODUCT IS NOT IN USE
                            </span></div>
                        </div>
                    ';
                }

                $ret .= '
                    <div class="row">
                        <div class="col-12" > &nbsp;</div>
                    </div>
                    <div class="row">
                        <div class="col-12 info" ><span class="sm-label">SIGN: </span> '.$signDesc.' </div>
                    </div>
                    <div class="row">
                        <div class="col-12 info" ><span class="sm-label">S.BRAND: </span> '.$signBrand.' </div>
                    </div>
                    <div class="row">
                        <div class="col-12 info" >
                            <button data-toggle="collapse" data-target="#sale-info" class="btn btn-clear btn-xs">
                                <span class="text-'.$saleButtonClass.'" style="font-weight: bold; ">'.$saleStatus.' </span>
                                <span class="caret text-'.$saleButtonClass.'"></span>
                            </button>
                            '.$narrow.'
                        </div>
                    </div>


                        <div class="collapse" id="sale-info">
                            <div class="row">
                                <div class="col-12 info">
                                    '.$saleInfoStr.'
                                </div>
                            </div>
                        </div>



                    <div class="container">
                    <br />
                    <div class="row">
                        <!-- <div class="col-4  clear btn btn-warning" onClick="queue('.$storeID.'); return false;">Print</div> -->
                        <div class="col-4 clear">
                            <form method="get" type="hidden">
                            <button class="btn btn-warning" onClick="alert(\''.$upc.' queued to print\'); return true;" type="submit"
                                style="width: 100%;">Print
                            </button>
                            <input type="hidden" name="note" value="Print Tag" />
                            <input type="hidden" id="upc" name="upc" value="'.$upc.'" />
                        </div>
                        </form>
                        <div class="col-4  clear">
                            <button class="btn btn-danger" data-toggle="collapse" data-target="#notepad"
                                style="width: 100%;">Note
                            </button></div>
                        <div class="col-4  clear "><a class="btn btn-success" style="width: 100%" href="http://'.$MY_ROOTDIR.'/content/Scanning/BatchCheck/SCS.php">B.C.</a></div>
                    </div>
                    <br /><br />
                    <div class="row">
                        <div class="col-4">
                            <a class="text-info" href="AuditScannerReport.php ">View Report</a>
                        </div>
                        <div class="col-4">
                            <a class="text-info" href="http://'.$MY_ROOTDIR.'/auth/logout.php">Logout</a>
                        </div>
                        <div class="col-4">
                        </div>
                    </div>
                </div>
            </div>

            <div id="ajax-resp"></div>
        ';

        //  Get easy re-use notes for this session
        $args = array($username,$storeID);
        $prep = $dbc->prepare("SELECT notes FROM woodshed_no_replicate.AuditScanner WHERE username = ? AND store_id = ?");
        $res = $dbc->execute($prep,$args);
        $notes = array();
        while ($row = $dbc->fetchRow($res)) {
            //echo $row['notes'];
            if (!in_array($row['notes'],$notes)) {
                $notes[] = $row['notes'];
            }
        }

        //  Commonly used NOTES.
        $ret .= '
            <div id="notepad" class="collapse" >
                <div style="position: relative; top: 10%; opacity: 1;">
                    <form method="get" name="notepad" class=" " >
                        <input type="text" name="note" id="note" class="form-control" style="max-width: 90%; "><br />
                        <input type="hidden" name="upc" value="'.$upc.'">
                        <button type="submit" class="btn btn-danger" onClick="$("#notepad").collapse("hide"); return false;">Submit Note</button>
                    </form>
        ';

        foreach ($notes as $note) {
            if ($note != NULL) {
                $ret .= '<span class="qmBtn"  onClick="qm(\''.$note.'\'); return false; ">
                    <b>'.$note.'</b></span>';
            }
        }

        $ret .= '
                </div>
            </div>';
        $touchicon = "<img class=\"scanicon-pointer\" src=\"../../../common/src/img/icons/pointer-light.png\"
            style=\"margin-left: 20px; margin-top: -5px;\"/>";
        $count = $this->getCount($dbc,$storeID,$username);
        $ret .= '<div class="counter"><span id="counter">'.$count.'</span>'.$touchicon.'</div>';

        $ret .= '<br /><br /><br /><br /><br /><br />';
        $this->addOnloadCommand("$('#progressBar').hide();");
        $timestamp = time();
        $this->addScript('auditScanner.js?unique='.$timestamp);
        $ret .= "<input type='hidden' id='isOnSale' name='isOnSale' value=$isOnSale/>";
        $hiddenContent = $this->hiddenContent();

        return $ret.$hiddenContent;
    }

    private function getCount($dbc,$storeID,$username)
    {
        $args = array($username,$storeID);
        $prep = $dbc->prepare("SELECT count(*) from woodshed_no_replicate.AuditScanner
            WHERE username = ? AND store_id = ?");
        $res = $dbc->execute($prep,$args);
        $count = $dbc->fetchRow($res);
        return $count[0]-1;
    }

    private function form_content($dbc)
    {

        $upc = ScanLib::upcParse($_POST['upc']);
        $ret .= '';
        $ret .= '
            <div align="center">
                <form method="post" class="" id="my-form" name="main_form">
                    <input class="form-control input-sm info" name="upc" id="upc" value="'.$upc.'"
                        style="text-align: center; width: 140px; border: none;" pattern="\d*">
                    <input type="hidden" id="sku" name="sku" />
                    <input type="hidden" name="success" value="empty"/>
                    <span id="auto_par" class="sm-label"></span><span id="par_val" class="norm-text"></span>
                    <!-- <button type="submit" class="btn btn-xs"><span class="go-icon"></span></button> -->
                </form>
            </div>
        ';

        return $ret;

    }

    private function record_data_handler($data,$username,$storeID)
    {

        $ret = '';
        include(__DIR__.'/../../../config.php');
        $dbc = scanLib::getConObj('SCANALTDB');
        $argsA = array($data['upc'],$username,$storeID);
        $prepA = $dbc->prepare("SELECT * FROM AuditScanner WHERE upc = ? AND username = ? AND store_id = ? LIMIT 1");
        $resA = $dbc->execute($prepA,$argsA);

        if ($dbc->numRows($resA) == 0) {
            $args = array(
                $data['upc'],
                $data['brand'],
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
                $data['cost'],
                $data['storeID'],
                $data['username']
            );
            $prep = $dbc->prepare("
                INSERT INTO AuditScanner
                (
                    upc, brand, description, price, curMarg, desMarg, dept,
                        vendor, rsrp, srp, prid, flag, cost, store_id,
                        username
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                );
            ");
            $dbc->execute($prep,$args);
            if ($dbc->error()) {
                return '<div class="alert alert-danger">' . $dbc->error() . '</div>';
            } else {
                return false;
            }
        }

    }

    public function cssContent()
    {
        return <<<HTML
.grey {
    color: grey;
}
.menu-list-space {
    background-color: rgba(0,0,0,0);
    list-style-type: none;
    height: 10px;
}
.menu-exit {
}
#menu-action {
    display: none;
    height: 100vh;
    width: 100vw;
    z-index: 999;
    background: linear-gradient(135deg, #42a7f4, #0a1528);
    background-color: linear-gradient(135deg, #42a7f4, #0a1528);
    position: fixed;
    top: 0px;
    left: 0px;
}
ul.menu-list {
     padding: 15px;
}
li.menu-list {
    background-color: rgba(255, 255, 255, 0.5);
    list-style-type: none;
    margin-top: 15px;
    padding: 10px;
    color: black;
    font-weight: #CACACA;
    cursor: pointer;
}
.text-xs {
    font-size: 8px;
    padding: 10px;
}
body {
    font-family: Arial, Helvetica, sans-serif;
    background-color: rgba(255,255,255,0.9);
    background: linear-gradient(135deg, #42a7f4, #0a1528);
    background-color: linear-gradient(135deg, #42a7f4, #0a1528);
    background-repeat: no-repeat;
    background-attachment: fixed;
    color: #cacaca;
}
.btn-mobile {
    position: fixed;
    top: 20px;
    right: 50px;
    padding: 1px;
    height: 25px;
    width: 25px;
    border: rgba(255,255,255,0.3);
    background-color: rgba(255,255,255,0.2);
    box-shadow: 1px 1px rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.4);
}
.btn-action {
    position: fixed;
    top: 15px;
    right: 15px;
    padding: 1px;
    height: 25px;
    width: 25px;
    border: rgba(255,255,255,0.3);
    background-color: rgba(255,255,255,0.2);
    box-shadow: 1px 1px rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.4);
}
.btn-keypad {
    height: 50px;
    width: 50px;
    border: 5px solid white;
    //border-radius: 2px;
    background-color: lightgrey;
    text-align: center;
    cursor: pointer;
}
#progressBar {
    display: none;
}
#heading {
color: rgba(255,255,255,0.6);
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
//background-image: url(\'../common/src/img/lbgrad.png\');
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
opacity: 0.8;
}
.qmBtn {
   background-clip: padding-box;
   padding: 5px;
   padding-top: 10px;
   border-radius: 5px;
   background-color: white;
   border: 3px solid transparent;
   //height: auto;
   //min-height: 50px;
   width: 75px;
   height: 75px;
   float: left;
   font-size: 12px;
   color: grey;
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
color: rgba(255,255,255,0.6);
}
.text-tiny {
font-size: 8px;
color: #6f6f80;
}
.text-sale {
color: lightgreen;
font-weight: bold;
}
.btn-msg {
width: 150px;
}
.norm-text {
font-size: 12px;
color: black;
}
.counter {
position: absolute;
top: 5;
left: 5;
width: 25;
height: 25;
font-size: 40;
font-weigth: bold;
opacity: 0.5;
}
.userSymbol-plus {
    position: absolute;
    top: 50;
    left: 8;
    padding: 5px;
    opacity: 0.5;
}
#pBar {
    opacity: 0.5;
    position: relative;
    margin-bottom: 0px;
    margin-top: -4px;
    padding: 0px;
    bottom: 27px;
}
HTML;
    }

    private function mobile_menu($upc)
    {
        include(__DIR__.'/../../../config.php');
        $ret = '';
        //$ret .= '<a href="../misc/mobile.php"><button class="btn-mobile">M</button></a>';
        $ret .= '<a href="#" id="btn-action"><button class="btn-action">A</button></a>';
        $ret .= '
            <div class="modal" tabindex="-1" role="dialog" id="keypad">
            <br /><br /><br /><br /><br />
              <div class="" role="document">
                <div class="" >
                    <h4 class="modal-title"></h4>
                  <div class=""  align="center">

                    <table><form type="hidden" method="get">
                        <input type="hidden" name="upc" id="keypadupc" value="0" />
                        <input type="hidden" name="success" value="empty"/>
                        <div id="modal-text" style="background-color: white; width: 170px; padding: 5px; border-radius: 5px;">&nbsp;</div><br />
                        <thead></thead>
                        <tbody>
                            <tr>
                            <td class="btn-keypad" id="key7">7</td>
                                 <td class="btn-keypad" id="key8">8</td>
                                  <td class="btn-keypad" id="key9">9</td>
                            </tr><tr>
                                <td class="btn-keypad" id="key4">4</td>
                                 <td class="btn-keypad" id="key5">5</td>
                                  <td class="btn-keypad" id="key6">6</td>

                            </tr><tr>
                                <td class="btn-keypad" id="key1">1</td>
                                 <td class="btn-keypad" id="key2">2</td>
                                  <td class="btn-keypad" id="key3">3</td>

                            </tr><tr>
                                <td ></td>
                                 <td class="btn-keypad" id="key0">0</td>
                                  <td ></td>
                            </tr><tr>
                                <td class="btn-keypad btn-info" id="keyCL">CL</td>
                                 <td></td>
                                  <!-- <td><button type="button" class="btn-keypad" data-dismiss="modal" aria-label="Close"><span style="color: white; font-weight: bold">X</span></button></td> -->
                                  <!-- <td><button type="submit" onClick="formSubmitter(); return false;" class="btn-keypad btn-success">GO</button></td> -->
                                  <td onClick="formSubmitter(); return false;" class="btn-keypad btn-success">GO</td>
                            </tr>
                        </tbody>
                    </form></table>

                  </div>

                </div><!-- /.modal-content -->
              </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->
        ';

        return $ret;

    }

    private function hiddenContent()
    {
        return <<<HTML
<div id="menu-action" style="margin-top: -10px">
    <ul class="menu-list">
        <li class="menu-list" id="mod-narrow">change <b>narrow</b> status</li>
        <li class="menu-list" id="mod-in-use">change <b>in-use</b> status</li>
        <li class="menu-list edit-btn" data-table="products" data-column="brand"><span class="grey">Edit</span> POS-Brand</li>
        <li class="menu-list edit-btn" data-table="products" data-column="description"><span class="grey">Edit</span> POS-Description</li>
        <li class="menu-list edit-btn" data-table="products" data-column="size"><span class="grey">Edit</span> POS-Size</li>
        <li class="menu-list edit-btn" data-table="productUser" data-column="brand"><span class="grey">Edit</span> SIGN-Brand</li>
        <li class="menu-list edit-btn" data-table="productUser" data-column="description"><span class="grey">Edit</span> SIGN-Description</li>
        <li class="menu-list" data-table="productUser" data-column="description">
            <a href="../../">Scannie Menu</a>
        </li>
        <li class="menu-list menu-exit" id="exit-action-menu">Exit Menu</li>
    </ul>
</div>
HTML;
    }

}
WebDispatch::conditionalExec();
