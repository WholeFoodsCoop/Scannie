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
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../..//common/sqlconnect/SQLManager.php');
}
class CashlessCheckPage extends PageLayoutA 
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
        include(__DIR__.'/../../config.php');
        
        if ($_GET['store_id']) {
            $_SESSION['store_id'] = $_GET['store_id'];
        } else {
            if (empty($_SESSION['store_id'])) {
                $ret .= '<div align="center"><div class="alert alert-danger md-w">You must select a store to view Cashless Transactions.</div></div>';
            }
        }
        $view_by = $_GET['view_by'];
        $ext = $_GET['ext'];
        $issuer = $_GET['issuer'];
        $LU = $_GET['LU'];
        
        $formcontent = $this->form_content();
        

        $ret .= '
                        </div><!-- column 2 -->
        ';
        $ret .= '</div></div></div>';
        $hillActive = ($_SESSION['store_id'] == 1) ? 'active' : '';
        $denActive = ($_SESSION['store_id'] == 2) ? 'active' : '';
        $control = "";
        $control .= '
            <form method="get" id="tabs">
                <div align="center"><div class="container">
                    <button class="btn btn-default btn-xs '.$hillActive.'" name="store_id" value="1" >Hillside</button>
                    <button class="btn btn-default btn-xs '.$denActive.'" name="store_id" value="2" >Denfeld</button>
                    <button class="btn btn-default btn-xs" name="store_id" value="*" >ALL Stores</button>
                    <input type="hidden" name="view_by" value="time" />
            </form>
                    </div>
        ';
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
                    .card {
                        box-shadow: 5px 5px 5px #cacaca;
                        margin: 25px;
                    }
                </style>
            ';
            
            $lane = ($lane == '') ? FormLib::get('regNo') : $lane;
            $ret .= '<div align="center"><div class="card" style="" id="table'.$lane.'">
                <div class="title"><strong>Register No.'.$lane.'</strong></div>
                <table class="table table-striped small">';
                
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
                $tCheckPath = '<a href="http://' . $FANNIEROOT_DIR.
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
        
        return <<<HTML
<div class="row">
    <div class="col-lg-4">
        $formcontent
    </div>
    <div class="col-lg-4">
        <div style="margin: 25px">$control</div>
    </div>
</div>
$ret
HTML;
    }
    
    private function form_content()
    {
        
        $dateID = FormLib::get('dateID');
        $regno = FormLib::get('regNo');
        $this->addOnloadCommand("$('#dateID').datepicker({dateFormat: 'yymmdd'});");

        return <<<HTML
        <div class="card">
            <div class="card-body">
                <div class="card-title h5">Transaction Lookup</div>
                <form method="get" class="" id="transLookup">
                    <div class="form-group">
                        <label for="dateID">DateID</label>
                        <input type="text" class="form-control" id="dateID" value="$dateID" name="dateID" placeholder="YYYYMMDD"/>
                    </div>
                    
                    <div class="form-group">
                        <label for="dateID">Reg No.</label>
                        <input type="text" class="form-control" id="regNo" name="regNo" value="$regno"/>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="LU" value="1">
                    </div>
                    <div class="form-group">
                        <button class="btn btn-default btn-xs form-control" type="submit" class="btn btn-default">L/U Transactions</button>
                    </div>
                </form>
            </div>
        </div>
HTML;
    }
    
}
WebDispatch::conditionalExec();
