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

include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}

class CashlessCheckPage extends ScancoordDispatch
{
    
    protected $title = "Check Cashless Status";
    protected $description = "[Check Cashless Status] Check the status of recent 
        cashless transactions for every lane.";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANTRANSDB, $SCANUSER, $SCANPASS);
        
        if ($_GET['store_id']) $_SESSION['store_id'] = $_GET['store_id'];
        $view_by = $_GET['view_by'];
        $ext = $_GET['ext'];
        $issuer = $_GET['issuer'];
        $LU = $_GET['LU'];
        
        $ret .= $this->form_content();
        
        $ret .= '
            <form method="get" id="tabs">
            <div align="center"><div class="container">
                <button name="store_id" value="1" >Hillside</button>
                <button name="store_id" value="2" >Denfeld</button>
                <button name="store_id" value="*" >* Stores</button>
                    <br>
                <button name="view_by" value="time" >*/Time</button>
                <button onclick="$(\'#issuer\').show(); return false;" >/issuer</button>
                <button onclick="$(\'#cardType\').show(); return false;" >/cardType</button>
                <button onclick="$(\'#processor\').show(); return false;" name="view_by" value="processor">/processor</button>
                <br>
            </form>
        ';
        $issuers = array('American Express','AMEX','DCVR','DEBIT','Discover','EBT','M/C','MasterCard','Mercury','Visa');
        $cardTypes = array('Credit',' Debit', 'EBTFOOD', 'EMV', 'Gift');
        $processors = array('GoEMerchant','MercuryE2E','MercuryGift');
        $ret .= '<div id="processor" class="collapse"><strong>Issuer : </strong>';
        foreach ($processors as $processor) {
            $ret .= '<button name="ext" value="'.$processor.'" >'.$processor.'</button>';
        }
        $ret .= '</div>';
        $ret .= '<div id="cardType" class="collapse"><strong>Issuer : </strong>';
        foreach ($cardTypes as $cardType) {
            $ret .= '<button name="ext" value="'.$cardType.'" >'.$cardType.'</button>';
        }
        $ret .= '</div>';
        $ret .= '<div id="issuer" class="collapse"><strong>Issuer : </strong>';
        foreach ($issuers as $issuer) {
            $ret .= '<button name="ext" value="'.$issuer.'" >'.$issuer.'</button>';
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
                AND empNo = ?
                AND registerNo = ?
            ORDER BY requestDatetime DESC LIMIT 5;
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
                $empNo = $_GET['empNo'];
                $regNo = $_GET['regNo'];
                //$transNo = $_GET['transNo'];
                //$amount = $_GET['amount'];
                $args = array($dateID,$empNo,$regNo);
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
            }
            
            $ret .= '<div align="center"><div class="panel panel-default" style="width:800px;" id="table'.$lane.'">
                <div class="panel-heading"><strong>Register No.'.$lane.'</strong></div>
                <table class="table table-striped table-condensed small">';
                
            $headers = array('Issuer','trans_no','Processor','Result','TransType','Amount','Date/Time','PAN');
            $ret .=  '<thead>';
            foreach ($headers as $header) $ret .= '<th>' . $header . '</th>';
            $ret .= '</thead>';
                
            foreach ($data as $PID => $row) {
                if ($LU) $lane = $regNo;
                $ret .= '<tr>';
                $ret .= '<td>' . $row['issuer'] . '</td>';
                $ret .= '<td>' . $row['empNo'] . '-' . $lane . '-' . $row['transNo'] . '</td>';
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
        return '
            <div class="container" align="center">
            <button onclick="$(\'#transLookup\').show(); return false;" >Transaction Lookup</button>
            <form method="get" class="form-inline collapse" id="transLookup" >
                <div class="input-group" >
                    <span class="input-group-addon">
                        DateID 
                    </span>
                    <input type="text" class="form-control" id="dateID" style="width: 175px" name="dateID">&nbsp;&nbsp;
                </div>
                <div class="input-group">
                    <span class="input-group-addon">
                        Emp No.
                    </span>
                    <input type="text" class="form-control" id="empNo" style="width: 175px" name="empNo" >&nbsp;&nbsp;
                </div>
                
                <div class="input-group">
                    <span class="input-group-addon">
                        Reg No.
                    </span>
                    <input type="text" class="form-control" id="regNo" style="width: 175px" name="regNo" >&nbsp;&nbsp;
                </div>
                <input type="hidden" name="LU" value="1">
                <button type="submit" class="btn btn-default">L/U Transaction</button>
                <br>
            </form>
            </div>
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
