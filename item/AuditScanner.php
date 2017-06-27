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
    protected $description = "[Audit Scanner] is a light-weight, all around product
        scanner for use with iUnfi iPod Touch scanners.";
    protected $ui = FALSE;
    protected $add_css_content = TRUE;
    protected $add_javascript_content = TRUE;
    protected $use_preprocess = TRUE;
    protected $must_authenticate = TRUE;

    public function preprocess()
    {

        include('../config.php');

        $username = scanLib::getUser();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        if (isset($_GET['note'])) {
            $note = $_GET['note'];
            $error = $this->notedata_handler($dbc,$note,$username);
            if (!$error) {
                header('location: http://192.168.1.2/scancoord/item/AuditScanner.php?success=true');
            } else {
                header('location: http://12.168.1.2/scancoord/item/AuditScanner.php?success=false');
            }
        }

    }

    private function notedata_handler($dbc,$note,$username)
    {
        $ret = '';
        $upc = $_GET['upc'];
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
    
    public function get_scan()
    {
        return 'hi, this is get_scan()';
    }

    public function body_content()
    {

        $ret = '';
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

        include('../config.php');
        include('../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $storeID = scanLib::getStoreID();
        /*$upc = str_pad($_POST['upc'], 13, 0, STR_PAD_LEFT);
        if (substr($upc,2,1) == '2') {
            $upc = '002' . substr($upc,3,4) . '000000';
        }*/
        $upc = scanLib::upcPreparse($_POST['upc']);

        $ret .= $this->mobile_menu($upc);
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
                AND bl.upc = ?;");
        $saleQres = $dbc->execute($saleQuery,$saleQueryArgs);
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
                case when pu.narrow=1 then '<span class=\'alert-warning\'>Flagged Narrow</span>' else NULL end as narrow
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
            $markup = $row['shippingMarkup'];
            $discount = $row['discountRate'];
            $ret .= '<input type="hidden" id="auto_par_value" value="'.($row['auto_par']*7).'"/>';

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

        /* This was used before $adjcost was introduced.
        $margin = ($price - $cost) / $price;
        $rSrp = $cost / (1 - $dMargin);
        $srp = $rounder->round($rSrp);
        $sMargin = ($srp - $cost ) / $srp;
        */
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
            $adjCostStr = '<span class="text-tiny">adj cost: </span><span style="color: grey; text-shadow: 0px  0px 1px white">'.sprintf('%0.2f',$adjcost).'</span>';
        } else {
            $adjCostStr = '&nbsp;';
        }
        $ret .= '
            <div align="center">
                <div class="container">
                    <div class="row">
                        <div class="col-xs-4 info" >
                            <div style="float: left; color: grey">cost</div><br />'.$cost.'<br />
                                '.$adjCostStr.'
                        </div>
                        <div class="col-xs-4 info" >
                            <div style="float: left; color: grey">price</div><br />
                                <span class="text-'.$warning['price'].'" style="font-weight: bold; ">
                                    '.$price.'</span>
                                    '.$price_rule.'<br />&nbsp;
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
                        <!-- <div class="col-xs-4  clear btn btn-warning" onClick="queue('.$storeID.'); return false;">Print</div> -->
                        <div class="col-xs-4 clear">
                            <form method="get" type="hidden">
                            <button class="btn btn-warning" onClick="alert(\''.$upc.' queued to print\'); return true;" type="submit"
                                style="width: 100%;">Print
                            </button>
                            <input type="hidden" name="note" value="Print Tag" />
                            <input type="hidden" name="upc" value="'.$upc.'" />
                        </div>
                        </form>
                        <div class="col-xs-4  clear "><a class="btn btn-surprise" href="http://192.168.1.2/scancoord/item/AuditScanner.php ">Refresh</a></div>
                        <div class="col-xs-4  clear">
                            <button class="btn btn-danger" data-toggle="collapse" data-target="#notepad"
                                style="width: 100%;">Note
                            </button></div>
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
        
        //  Get easy re-use notes for this session
        $args = array($username,$storeID);
        $prep = $dbc->prepare("SELECT notes FROM woodshed_no_replicate.AuditScanner WHERE username = ? AND store_id = ?");
        $res = $dbc->execute($prep,$args);
        $notes = array();
        $preLoadedNotes = array(
            'Change Sign Text: ',
            'Line Price',
            'Change Price: ',
            'Print Tag ',
            'Missing Sale Sign',
            'Queue Narrow Tag ',
            'Product Not In Use',
            'Remove This Item From Queue',
            'n/a'
        );
        while ($row = $dbc->fetchRow($res)) {
            //echo $row['notes'];
            if (!in_array($row['notes'],$notes) && !in_array($row['notes'],$preLoadedNotes)) {
                $notes[] = $row['notes'];
            }
        }
        
        //  Commonly used NOTES.
        $ret .= '
            <div id="notepad" class="collapse" >
                <div style="position: relative; top: 10%; opacity: 1;">
                    <form method="get" name="notepad" class="form-inline " >
                        <input type="text" name="note" id="note" class="form-control" style="max-width: 90%; "><br /><br />
                        <input type="hidden" name="upc" value="'.$upc.'">
                        <button type="submit" class="btn btn-danger" onClick="$("#notepad").collapse("hide"); return false;">Submit Note</button>
                    </form>
                    <!-- Purple Buttons -->
                    <div align="left" style="padding: 10px; float: left; text-align:center; width: 45vw">
                        <div>
                            <span onClick="qm(\'Change Sign Text: \'); return false; ">
                                <b>Change Sign Text</b></span><br /><br />
                            <span onClick="qm(\'Line Price\'); return false; ">
                                <b>Line Price</b></span><br /><br />
                            <span onClick="qm(\'Change Price: \'); return false; ">
                                <b>Change Price</b></span><br /><br />
                            <span onClick="qm(\'Print Tag \'); return false; ">
                                <b>Print Tag</b></span><br /><br />';
                                
        foreach ($notes as $note) {
            $ret .= '<span onClick="qm(\''.$note.'\'); return false; ">
                         <b>'.$note.'</b></span><br /><br />';
        }
        
        $ret .= '
                        </div>
                    <!-- Green Buttons -->
                    </div>
                    <div align="left" style="padding: 10px; float: left; width: 5vw"></div>
                    <div align="left" style="padding: 10px; float: left; text-align:center; width: 45vw">
                        <span onClick="qm(\'Missing Sale Sign\'); return false; ">
                            <b>Sale Sign Missing</b></span><br /><br />
                        <span onClick="qm(\'Queue Narrow Tag \'); return false; ">
                            <b>Narrow Tag</b></span><br /><br />
                        <span onClick="qm(\'Product Not In Use\'); return false; ">
                            <b>Not In Use</b></span><br /><br />
                        <span onClick="qm(\'Remove This Item From Queue\'); return false; ">
                            <b>Remove From Queue</b></span><br /><br />
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
                <form method="post" class="form-inline" id="my-form" name="main_form">
                    <input class="form-control input-sm info" name="upc" id="upc" value="'.$upc.'"
                        style="text-align: center; width: 140px; border: none;">
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
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANALTDB, $SCANUSER, $SCANPASS);
        //echo '<h1>' . $data['upc'] . '</h1>';
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
            //echo $data[$upc];
            if ($dbc->error()) {
                return '<div class="alert alert-danger">' . $dbc->error() . '</div>';
            } else {
                return false;
            }
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
            .norm-text {
                font-size: 12px;
                color: black;
            }
        ';
    }

    private function mobile_menu($upc)
    {
        $ret = '';
        $ret .= '
            <style>
                .btn-mobile {
                    width: 50px;
                    height: 45px;
                    background-color: #272822;
                    color: #cacaca;
                    position: fixed;
                    top: 20px;
                    right: 5px;
                    border-radius: 3px;
                    padding: 8px;
                    border: none;
                    opacity: 0.2;
                }
                .btn-mobile-lines {
                    border: 2px solid #cacaca;
                    width: 35px;
                    height: 1px;
                    border-radius: 5px;
                }
                .btn-mobile-sp {
                    height: 8px;
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
            </style>
        ';
        $ret .= '
            <button class="btn-mobile" data-toggle="modal" data-target="#keypad" id="btn-modal">
                <div class="btn-mobile-lines">&nbsp;</div>
                <div class="btn-mobile-sp">&nbsp;</div>
                <div class="btn-mobile-lines">&nbsp;</div>
                <div class="btn-mobile-sp">&nbsp;</div>
                <div class="btn-mobile-lines">&nbsp;</div>
                <div class="btn-mobile-sp">&nbsp;</div>
            </button>';

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

    public function javascript_content()
    {
        ob_start();
        ?>
<script type="text/javascript" src="/git/fannie/src/javascript/jquery.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/cordova-2.2.0.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/ScannerLib-Linea-2.0.0.js"></script>
<script type="text/javascript" src="../item/SalesChange/scanner.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    enableLinea('#upc', function(){$('#my-form').submit();});
});
</script>
<script type="text/javascript">
function queue(store_id)
{
    var upcB = document.getElementById("upc").value;
    $.ajax({
		type: 'post',
        url: 'AuditUpdate.php',
        data: 'upc='+upcB+'&store_id='+store_id,
		error: function(xhr, status, error)
		{
			alert('error:' + status + ':' + error + ':' + xhr.responseText)
		},
        success: function(response)
        {
            $('#ajax-resp').html(response);
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
<script type="text/javascript">
$(document).ready(function(){
    var upc = $('#upc').val() + '';
    $( "#keyCL" ).click(function() {
        $('#upc').val('0');
        upc = 0;
       //alert( $('#upc').val() );
       updateModalText();
    });
    $( "#key1" ).click(function() {
        $('#upc').val(upc+'1');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key2" ).click(function() {
        $('#upc').val(upc+'2');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key3" ).click(function() {
        $('#upc').val(upc+'3');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key4" ).click(function() {
        $('#upc').val(upc+'4');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key5" ).click(function() {
        $('#upc').val(upc+'5');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key6" ).click(function() {
        $('#upc').val(upc+'6');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key7" ).click(function() {
        $('#upc').val(upc+'7');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key8" ).click(function() {
        $('#upc').val(upc+'8');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key9" ).click(function() {
        $('#upc').val(upc+'9');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key0" ).click(function() {
        $('#upc').val(upc+'0');
        upc = $('#upc').val();
        updateModalText();
    });
});

function get_auto_par()
{
    var par = $('#auto_par_value').val();
    par = parseFloat(par);
    par = par.toPrecision(3);
    $('#auto_par').text('PAR: ');
    $('#par_val').text(par);
}
$(document).ready( function() {
   get_auto_par();
   updateModalOnload();
});

function formSubmitter()
{
    $('#my-form').submit();
}

function updateModalOnload()
{
    $('#btn-modal').click( function () {
        updateModalText();
    });
}

function updateModalText()
{
    $text = $('#upc').val();
    $('#modal-text').text($text);
}

</script>
        <?php
        return ob_get_clean();
    }

}
ScancoordDispatch::conditionalExec();
