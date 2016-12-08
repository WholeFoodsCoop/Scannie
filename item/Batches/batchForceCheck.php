<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op.
    
    This file is a part of CORE-POS.
    
    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file LICENSE along with CORE-POS; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    
*********************************************************************************/

include('../../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(dirname(__FILE__))) . '/common/sqlconnect/SQLManager.php');
}

class batchForceCheck extends ScancoordDispatch
{
    
    protected $title = "Un-forced Batches";
    protected $description = "[Batch Force Check] Locate batches in the past couple
        of months that have not been forced.";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        include('../../config.php');
        include('../../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }
         
        $batch = array ();
        $batch = array(
            'ID' => array(),
            'name' => array(),
            'item' => array(
                'plu' => 0,
                'curPrice' => 0.0,
                'newPrice' => 0.0,
                'brand' => '',
                'desc' => '',
            ),
        );

        $query = $dbc->prepare("SELECT 
                max(batchID)
            FROM batches
            ;");
        $result = $dbc->execute($query);
        while ($row = $dbc->fetch_row($result)) {
            $maxBatchID = $row['max(batchID)'] - 200;
        }
        if ($dbc->error()) $ret .=  $dbc->error();

        $query = $dbc->prepare("SELECT 
                bl.salePrice,
                p.normal_price,
                b.batchID,
                p.upc,
                p.description,
                p.brand,
                b.batchName
            FROM batches AS b
                LEFT JOIN batchList AS bl ON bl.batchID=b.batchID
                LEFT JOIN products AS p ON p.upc=bl.upc
            WHERE batchType=4
                AND b.batchID >= {$maxBatchID}
            AND p.store_id=1
                AND b.batchID != 6173
                AND b.batchID != 6139
                AND b.batchID != 6228
            ;");
        $result = $dbc->execute($query);
        while ($row = $dbc->fetch_row($result)) {
            $batch['ID'][] = $row['batchID'];
            $batch['name'][] = $row['batchName'];
            $batch['ID']['item']['plu'][] = $row['upc'];
            $batch['ID']['item']['curPrice'][] = $row['normal_price'];
            $batch['ID']['item']['newPrice'][] = $row['salePrice'];
            $batch['ID']['item']['brand'][] = $row['brand'];
            $batch['ID']['item']['desc'][] = $row['description'];
        }
        if ($dbc->error()) $ret .=  $dbc->error();


        //* Find and Print un-forced batch info
        echo "<div class='container' id='wrapper'>";
        echo "<h2>Non-Forced Batches</h2>";
        $batchList = array();
        $batchName = array();
        for ($i=0; $i<count($batch['ID']['item']['plu']); $i++) {
            if ($batch['ID']['item']['curPrice'][$i] != $batch['ID']['item']['newPrice'][$i]) {
                if (!isset($batchList[$batch['ID'][$i]])) {
                    $batchList[$batch['ID'][$i]] = 1;
                    $batchName[$batch['ID'][$i]] = $batch['name'][$i];
                }
            }
        }
        echo count($batchList) . " non-forced batches found.<br>";
        echo "<table class='alert alert-warning table'>";
        echo "<th>Batch ID</th>";
        echo "<th>Batch Name</th>";
        foreach ($batchList as $key => $value) {
            if (isset($batchList[$key])) {
                echo "<tr><td>
                    <a href=\"http://key/git/fannie/batches/newbatch/EditBatchPage.php?id=" . 
                    $key . "\" target=\"_blank\">$key</a></td>";
                echo "<td>" . $batchName[$key] . "</tr>";
            }
        }
        echo "</table><br>";
        $batchIDs = array_keys($batchList);
        $printLink = 'http://key/git/fannie/admin/labels/SignFromSearch.php?'
            . array_reduce($batchIDs, function($c, $i) {  return $c . "batch[]={$i}&"; });
        echo "<a href=\"{$printLink}\">Print Tags</a>";


        //* Info to print 
        echo "<h2>Items within Batches</h2>";
        echo "<table class='alert alert-info table'>";
        echo "<th>UPC</th>
            <th>Brand</th>
            <th>Description</th>
            <th>Batch ID</th>
            <th>Current Price</th>
            <th>New Batch Price</th>";
        for ($i=0; $i<count($batch['ID']['item']['plu']); $i++) {
            if ($batch['ID']['item']['curPrice'][$i] != $batch['ID']['item']['newPrice'][$i]) {
                echo "<tr><td>" . $batch['ID']['item']['plu'][$i] . "</td>";
                echo "<td>" . $batch['ID']['item']['brand'][$i] . "</td>";
                echo "<td>" . $batch['ID']['item']['desc'][$i] . "</td>";
                echo "<td>
                <a href=\"http://key/git/fannie/batches/newbatch/EditBatchPage.php?id=" . 
                    $batch['ID'][$i] . "\" target=\"_blank\">{$batch['ID'][$i]}</a></td>";
                echo "<td>" . $batch['ID']['item']['curPrice'][$i] . "</td>";
                echo "<td>" . $batch['ID']['item']['newPrice'][$i] . "</tr>";
            }
        }
        echo "</table>";
        echo "</div><!-- container div -->";
        
        
        return $ret;
    }
    
    private function form_content()
    {
        return '
            <div class="text-center container">
                <form class ="form-inline"  method="get" > 
                    <br>
                    <div class="form-group">    
                        <input type="text" class="form-control" name="upc" placeholder="enter plu to track" autofocus>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default" value="submit">
                    </div>
                </form>
            </div>
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
