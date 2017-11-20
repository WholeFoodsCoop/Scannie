<?php
session_start();
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
include(__DIR__.'/../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)).'/common/sqlconnect/SQLManager.php');
}
class CashlessCheckPage extends ScancoordDispatch
{
    
    protected $title = "Check Cashless Status";
    protected $description = "[Check Cashless Status] Check the status of recent 
        cashless transactions for every lane.";
    protected $ui = TRUE;
    protected $must_authenticate = TRUE;
   
    public function body_content()
    {           
        $ret = '';
        $dbc = scanLib::getConObj('SCANTRANSDB');
        
        if ($_GET['store_id']) {
            $_SESSION['store_id'] = $_GET['store_id'];
        } else {
            if (empty($_SESSION['store_id'])) {
                $ret .= '<div align="center"><div class="alert alert-danger md-w">You must select a store ID to view Cashless Transactions.</div></div>';
            }
        }
        $view_by = $_GET['view_by'];
        $ext = $_GET['ext'];
        $issuer = $_GET['issuer'];
        $LU = $_GET['LU'];
        
        $ret .= $this->form_content();
        
        $ret .= '
            <div align="center" class="container" style="padding:5px;">
                <!-- <button class="btn btn-default btn-xs" onclick="$(\'#xResScan\').show(); return false;" >View xResData</button> -->
                <button class="btn btn-default btn-xs" data-toggle="collapse" data-target="#xResScan" >View xResData</button>
        ';
        $xResScan = $this->getResultScan($dbc,100); //subtracting 100 goes back 1 month UNLESS the month you're looking at is Jan. 
        $xResScanT = $this->getResultScan($dbc,0);
        $ret .= '
                <div class="collapse" id="xResScan">
                    <div class="row" align="center">
                        <div class="col-xs-6">
                            <h4>xResultMessages in last 30 days</h4>
                            <table class="table table-condensed small" style="width:500px">
        ';
        $ret .= '
                        
        ';
        foreach ($xResScan as $error => $count) {
            $ret .= '<tr>';
            $ret .= '<td>' . $error . '</td><td>' . $count . '</tr>';
            $ret .= '</tr>';
        }
        $ret .= '
                            </table>
                        </div><!-- column 1 -->
        ';
        $ret .= '
                        <div class="col-xs-6">
                            <h4>xResultMessages Today</h4>
                            <table class="table table-condensed small" style="width:500px">
                        ';
        
        foreach ($xResScanT as $error => $count) {
            $ret .= '<tr>';
            $ret .= '<td>' . $error . '</td><td>' . $count . '</tr>';
            $ret .= '</tr>';
        }
        $ret .= '
                            </table>
                        </div><!-- column 2 -->
        ';
        $ret .= '</div></div></div>';
        
        $ret .= '
            <form method="get" id="tabs">
                <div align="center"><div class="container">
                    <button class="btn btn-default btn-xs" name="store_id" value="1" >Hillside</button>
                    <button class="btn btn-default btn-xs" name="store_id" value="2" >Denfeld</button>
                    <button class="btn btn-default btn-xs" name="store_id" value="*" >ALL Stores</button>
                    <button class="btn btn-warning btn-xs" name="inProcess" value="1">SCAN: Yesterday</button>
                    <br /><br />
                    <div class="well md-w" style="padding: 10px;"><label>View transactions in respect to</label><br />
                        <button class="btn btn-default btn-xs" name="view_by" value="time" >time/recent</button>
            </form>
                        <a class="btn btn-default btn-xs" data-toggle="collapse" data-target="#issuer">issuer</a>
                        <a class="btn btn-default btn-xs" data-toggle="collapse" data-target="#cardType">cardType</a>
                        <a class="btn btn-default btn-xs" data-toggle="collapse" data-target="#processor" name="view_by" value="processor">processor</a>
                        <br />
                    </div>
        ';
        $issuers = array('American Express','AMEX','DCVR','DEBIT','Discover','EBT','M/C','MasterCard','Mercury','Visa');
        $cardTypes = array('Credit',' Debit', 'EBTFOOD', 'EMV', 'Gift');
        $processors = array('GoEMerchant','MercuryE2E','MercuryGift');
        $ret .= '<div id="processor" class="collapse"><strong>Processor : </strong>';
        foreach ($processors as $processor) {
            $ret .= '<button class="btn btn-default btn-xs" name="ext" value="'.$processor.'" >'.$processor.'</button>';
        }
        $ret .= '</div>';
        $ret .= '<div id="cardType" class="collapse"><strong>Card Type : </strong>';
        foreach ($cardTypes as $cardType) {
            $ret .= '<button class="btn btn-default btn-xs" name="ext" value="'.$cardType.'" >'.$cardType.'</button>';
        }
        $ret .= '</div>';
        $ret .= '<div id="issuer" class="collapse"><strong>Issuer : </strong>';
        foreach ($issuers as $issuer) {
            $ret .= '<button class="btn btn-default btn-xs" name="ext" value="'.$issuer.'" >'.$issuer.'</button>';
        }
        $ret .= '</div>';
        $ret .= '</div></div><br>';
        
        $curDate = date('Ymd');
        
        $qTime = $dbc->prepare("
            SELECT registerNo, transNo, empNo, processor, refnum, 
                cardType, amount, PAN, issuer, name, requestDatetime, 
                commErr, xResultMessage, paycardTransactionID as PID,
                transType
            FROM PaycardTransactions 
            WHERE registerNo = ?
                AND dateID = ?
            ORDER BY requestDatetime DESC LIMIT 5;
        ");
        
        $qCardType = $dbc->prepare("
            SELECT registerNo, transNo, empNo, processor, refnum, 
                cardType, amount, PAN, issuer, name, requestDatetime, 
                commErr, xResultMessage, paycardTransactionID as PID,
                transType
            FROM PaycardTransactions 
            WHERE registerNo = ?
                AND cardType = ?
                AND dateID = ?
            ORDER BY requestDatetime DESC LIMIT 5;
        ");
        
        $qProcessor = $dbc->prepare("
            SELECT registerNo, transNo, empNo, processor, refnum, 
                cardType, amount, PAN, issuer, name, requestDatetime, 
                commErr, xResultMessage, paycardTransactionID as PID,
                transType
            FROM PaycardTransactions 
            WHERE registerNo = ?
                AND processor = ?
                AND dateID = ?
            ORDER BY requestDatetime DESC LIMIT 5;
        ");
        
        $qIssuer = $dbc->prepare("
            SELECT registerNo, transNo, empNo, processor, refnum, 
                cardType, amount, PAN, issuer, name, requestDatetime, 
                commErr, xResultMessage, paycardTransactionID as PID,
                transType
            FROM PaycardTransactions 
            WHERE registerNo = ?
                AND issuer = ?
                AND dateID = ?
            ORDER BY requestDatetime DESC LIMIT 5;
        ");
        
        $qTransLU = $dbc->prepare("
            SELECT registerNo, transNo, empNo, processor, refnum, 
                cardType, amount, PAN, issuer, name, requestDatetime, 
                commErr, xResultMessage, paycardTransactionID as PID,
                transType
            FROM PaycardTransactions 
            WHERE dateID = ?
                AND registerNo = ?
            ORDER BY requestDatetime DESC;
        ");
        
        $sTransLU = $dbc->prepare("
            SELECT registerNo, transNo, empNo, processor, refnum, 
                cardType, amount, PAN, issuer, name, requestDatetime, 
                commErr, xResultMessage, paycardTransactionID as PID,
                transType
            FROM PaycardTransactions 
            WHERE dateID = ?
                AND registerNo = ?
            ORDER BY requestDatetime DESC;
        ");
        
        
        if ($_SESSION['store_id'] == 1) {
            $lanes = array(1,2,3,4,5,6);
        } elseif ($_SESSION['store_id'] == 2) {
            $lanes = array(11,12,13,14,15);
        } elseif ($_SESSION['store_id'] == '*') {
            $lanes = array(1,2,3,4,5,6,11,12,13,14,15);
        }
        if ($LU) {
            $lanes = array($GET['regNo']);
        }
        
        if ($_GET['inProcess']) {
            $ret .= $this->getVoidErrors($dbc);
        }
        
        foreach ($lanes as $lane) {
            if ($ext) {
                if (in_array($ext,$issuers)) {
                    $args = array($lane,$ext,$curDate);
                    $result = $dbc->execute($qIssuer,$args);
                } elseif (in_array($ext,$cardTypes)) {
                    $args = array($lane,$ext,$curDate);
                    $result = $dbc->execute($qCardType,$args);
                } elseif (in_array($ext,$processors)) {
                    $args = array($lane,$ext,$curDate);
                    $result = $dbc->execute($qProcessor,$args);
                }        
            } elseif ($view_by == 'time') {
                $args = array($lane,$curDate);
                $result = $dbc->execute($qTime,$args);
            } elseif ($LU) {
                $dateID = $_GET['dateID'];
                //$empNo = $_GET['empNo'];
                $regNo = $_GET['regNo'];
                //$transNo = $_GET['transNo'];
                //$amount = $_GET['amount'];
                $args = array($dateID,$regNo);
                $result = $dbc->execute($qTransLU,$args);
            } else {
                $result = $dbc->execute($qTime,$args);
            }
            //$result = $dbc->execute($query,$lane);
            
            $data = array();
            while ($row = $dbc->fetch_row($result)) {
                $data[$row['PID']]['issuer'] = $row['issuer'];
                $data[$row['PID']]['transNo'] = $row['transNo'];
                $data[$row['PID']]['empNo'] = $row['empNo'];
                $data[$row['PID']]['processor'] = $row['processor'];
                $data[$row['PID']]['requestDatetime'] = $row['requestDatetime'];
                $data[$row['PID']]['xResultMessage'] = $row['xResultMessage'];
                $data[$row['PID']]['commErr'] = $row['commErr'];
                $data[$row['PID']]['amount'] = $row['amount'];
                $data[$row['PID']]['PAN'] = substr($row['PAN'],-5);
                $data[$row['PID']]['transType'] = $row['transType'];
				$data[$row['PID']]['refnum'] = '<span style="color: grey; font-size: 10;">'.$row['refnum'].'</span>';
            }
            
            $ret .= '
                <style>
                    .panel-default {
                        box-shadow: 5px 5px 5px #cacaca;
                    }
                </style>
            ';
            
            $ret .= '<div align="center"><div class="panel panel-default" style="width:800px;" id="table'.$lane.'">
                <div class="panel-heading"><strong>Register No.'.$lane.'</strong></div>
                <table class="table table-striped table-condensed small">';
                
            $headers = array('Issuer','trans_no','Processor','Result','TransType','Amount','Date/Time','PAN','refnum');
            $ret .=  '<thead>';
            foreach ($headers as $header) $ret .= '<th>' . $header . '</th>';
            $ret .= '</thead>';
                
            foreach ($data as $PID => $row) {
                if ($LU) {
                    $lane = $regNo;
                }
                $transNo = $row['empNo'].'-'.$lane.'-'.$row['transNo'];
                $transDate = $row['requestDatetime'];
                $tCheckPath = '<a href="http:\\' . $CORE_POS_DIR .
                    '/admin/LookupReceipt/RenderReceiptPage.php?date='.$transDate.'&receipt='.$transNo.
                    '" target="_BLANK">' . $transNo . '</a>';
                $ret .= '<tr>';
                $ret .= '<td>' . $row['issuer'] . '</td>';
                $ret .= '<td>' . $tCheckPath . '</td>';
                //$ret .= '<td>' . $row['empNo'] . '</td>';
                $ret .= '<td>' . $row['processor'] . '</td>';
                
                $xRes = $row['xResultMessage'];
                if (strstr($xRes,'Approved') || strstr($xRes,'APPROVED')) {
                    $ret .= '<td>' . $xRes . '</td>';
                } else {
                    $ret .= '<td class="text-danger">' . $xRes . '</td>';
                }
                $ret .= '<td>' . $row['transType'] . '</td>';
                
                $ret .= '<td>$' . $row['amount'] . '</td>';
                //$ret .= '<td>' . $row['commErr'] . '</td>';
                $ret .= '<td>' . substr($row['requestDatetime'],5,-3) . '</td>';
                $ret .= '<td>' . $row['PAN'] . '</td>';
                $ret .= '<td>' . $row['refnum'] . '</td>';
                
                $ret .= '</tr>';
            
            }
            $ret .= '</table></div></div>';
            unset($data);
        }
        if ($dbc->error()) $ret .=  $dbc->error();
        
        return $ret;
    }
    
    private function form_content()
    {
        
        $dateID = $_GET['dateID'];
        return '
            <div class="container" align="center">
            <!-- <button class="btn btn-default btn-xs" onclick="$(\'#transLookup\').show(); return false;" >Transaction Lookup</button> -->
            <button class="btn btn-default btn-xs" data-toggle="collapse" data-target="#transLookup">Transaction Lookup</button> 
            <form method="get" class="form-inline collapse" id="transLookup" >
               <br />
                <div class="input-group" >
                    <span class="input-group-addon">
                        DateID 
                    </span>
                    <input type="text" class="form-control" id="dateID" value="'.$dateID.'" style="width: 175px" name="dateID" placeholder="YYYYMMDD">&nbsp;&nbsp;
                </div>
                
                <div class="input-group">
                    <span class="input-group-addon">
                        Reg No.
                    </span>
                    <input type="text" class="form-control" id="regNo" style="width: 175px" name="regNo" >&nbsp;&nbsp;
                </div>
                <input type="hidden" name="LU" value="1">
                <button class="btn btn-default btn-xs" type="submit" class="btn btn-default">L/U Transactions</button>
                <br>
            </form>
            </div>
        ';
    }
    
    private function getResultScan($dbc,$minusDate)
    {
        $date = date('Ymd');
        $date -= $minusDate; 
        $data = array();
        
        $prep = $dbc->prepare("
            select 
                count(xResultMessage) AS count, 
                xResultMessage 
            from PaycardTransactions 
            where xResultMessage not like '%approved%' 
                and xResultMessage not like '%declined%' 
                and dateID >= ? 
            group by xResultMessage 
            order by count(xResultMessage);");
        $result = $dbc->execute($prep,$date);
        while ($row = $dbc->fetchRow($result)) {
            $data[$row['xResultMessage']] = $row['count'];
        }
        if ($dbc->error()) echo '<div class="alert alert-danger small">'.$dbc->error().'</div>';
        
        return $data;
    }
    
    private function getVoidErrors($dbc)
    {
        $date = date('Ymd');
        $date -= 1; //look at previous day. Doesn't work for the 1st of the month. 
        $rDate = 
        
        $prep = $dbc->prepare("SELECT transNo, registerNo FROM PaycardTransactions WHERE dateid = ? AND xResultMessage = 'In Process!';");
        $result = $dbc->execute($prep,$date);
        $transactions = array();
        while ($row = $dbc->fetchRow($result)) {
            $transactions[$row['transNo']] = $row['registerNo'];
        }
        if ($dbc->error()) echo '<div class="alert alert-danger small">'.$dbc->error().'</div>';
        
        foreach ($transactions as $transNo => $registerNo) {
            $args = array($date,$transNo,$registerNo);
            $prep = $dbc->prepare("select * from PaycardTransactions where dateid = ? and transNo = ? and registerNo = ?;");
            $result = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($result)) {
                if ($row['xResultMessage'] == 'In Process!') {
                    $curPTID = $row['paycardTransactionID'];
                    $curEmpNo = $row['empNo'];
                    $curAmount = $row['amount'];
                }
                if ($row['paycardTransactionID'] == ($curPTID+1)) {
                    $xRes = $row['xResultMessage'];
                    $amount = $row['amount'];
                    $qln = 1;
                    
                    if ($xRes == 'Cancelled at POS.') {
                    } elseif ((strstr($xRes,'Approved') || strstr($xRes,'APPROVED')) && $amount == $curAmount) {
                        //do nothing
                        //do nothing
                    } else {
                        echo '
                            <div align="center">
                                <div class="alert alert-danger md-w" >
                                    POSSIBLE INCOMPLETE TRANS <br />('.$xRes.') '.$curEmpNo.'-'.$registerNo.'-'.$transNo.' on '.$date.' <br />
                                </div>
                            </div>
                        ';
                    }
                    
                    
                }
            }
        }
        
        return $ret;
    }
    
    
}
ScancoordDispatch::conditionalExec();
