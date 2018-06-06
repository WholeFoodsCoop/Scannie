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
include(__DIR__.'/../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../common/sqlconnect/SQLManager.php');
}
class coopBasicsScanPage extends ScancoordDispatch
{

    public function body_content()
    {
        include(__DIR__.'/../config.php');
        $username = scanLib::getUser();
        $storeID = scanLib::getStoreID();
        $storename = scanLib::getStoreName($storeID);
        $dbc = scanLib::getConObj($SCANALTDB);

        $ret = "";
        $heading = "";
        
        $userhead =  ($username == false) ? '<div class="alert alert-danger">You must be logged in to use Audit Scan queue to check Coop Basics Signs.</div>'
            : "<div class='well'>You are logged in as $username</div>";
        $heading .=  '<h4>Coop Basics Review for <strong>'.$storename.'</strong></h4>';
        $heading .=  "Don't forget to upload a Coop Basics checklist to <strong>Generic Upload</strong>.<br>";

        if (isset($_GET['session'])) {
            $session = substr($_GET['session'],0,-1);
            $ret .=  $session  . "<br>";
        }

        // get a list of BASICS items
        $list = array();
        $products = array();
        $argsA = array($storeID);
        $prepA = $dbc->prepare("
            SELECT g.upc,p.brand,p.description FROM is4c_op.GenericUpload AS g 
            LEFT JOIN is4c_op.products AS p ON g.upc=p.upc 
            WHERE p.store_id = ? 
        ");
        $resA = $dbc->execute($prepA,$argsA);
        while ($row = $dbc->fetchRow($resA))  {
            $list[] = $row['upc'];
            $products[$row['upc']]['brand'] = $row['brand'];
            $products[$row['upc']]['description'] = $row['description'];
        }
        if ($er = $dbc->error()) $ret .=  "<div class='alert alert-warning>$er</div>";

        if (count($list) < 1) {
            $ret .=  '<div class="alert alert-danger">No scanned items were found.</div>
                <div class="alert alert-danger">Check that barcodes are in the correct queue.</div>
                <div class="alert alert-danger">Check your query.</div>';
        }

        // get list of products scanned
        $scanned = array();
        $argsB = array($username,$storeID);
        $prepB = $dbc->prepare("
            SELECT upc FROM woodshed_no_replicate.AuditScanner WHERE username = ? and store_id = ?
        ");
        $resB = $dbc->execute($prepB,$argsB);
        while ($row = $dbc->fetchRow($resB))  {
            $scanned[] = $row['upc'];
        }
        if ($er = $dbc->error()) $ret .=  "<div class='alert alert-warning>$er</div>";

        // get list of sale items
        $saleitems = array();
        $prepC = $dbc->prepare('select bl.upc from is4c_op.batchList as bl left join is4c_op.batches as b on bl.batchID=b.batchID where CURDATE() between b.startDate and b.endDate group by upc;');
        $resC = $dbc->query($prepC);
        while ($row = $dbc->fetchRow($resC))  {
            $saleitems[] = $row['upc'];
        }
        if ($er = $dbc->error()) $ret .=  "<div class='alert alert-warning>$er</div>";

        $missing = array();  //<--sings that are missing, to put up
        $remove = array();   //<--signs to take down
        //  Find tags that are on the floor that should be taken down
        foreach ($scanned AS $key => $upc) {
            if (in_array($upc,$list)) {
                //  do nothing
            } else {
                $remove[] = $upc;
            }
        }
        //  Find tags that are missing/need to be put up on the floor
        foreach ($list AS $key => $upc) {
            if (in_array($upc,$scanned)) {
                //  do nothing - these signs were scanned, they don't need to go up.
            } else {
                $missing[] = $upc;
            }
        }
        foreach ($missing as $key => $upc) {
            if (in_array($upc,$saleitems)) {
                $missing[$key] = NULL;
            } else {
                //  do nothing
            }
        }

        $notInUse = array();
        $curM = date('m');
        foreach ($missing as $key => $upc) {
            $args = array($storeID);
            $query = ("select inUse from products where upc = {$upc} and store_id = ?");
            $res = $dbc->query($query, $args);
            while ($row = $dbc->fetchRow($res))  {
                if ($row['inUse'] == 0) {
                    unset($missing[$key]);
                    $notInUse[] = $upc;
                } else  {
                    // do nothing
                }
            }
        }
        if ($er = $dbc->error()) $ret .=  "<div class='alert alert-warning>$er</div>";

        foreach ($missing as $key => $upc) {
            if (is_null($upc)) unset($missing[$key]);
        }

        $tableA .=  '<table class="table table-condensed small"><thead>';
        $tableA .=  '<tr><th colspan="3" style="text-align: center"><strong>Signs missing from sales floor.</strong></th></tr></thead><tbody>';
        $missingCopyPaste = '';
        foreach ($missing as $upc) {
            $product = "<tr><td>".$upc."</td><td>".$products[$upc]['brand']."</td><td>"
                .$products[$upc]['description']."</td></tr>";
            $tableA .=  $product;
            $missingCopyPaste .= $upc . "\n";
        }
        $tableA .=  '</tbody></table>';
        $tableA .=  '<textarea rows="3" cols="15">' . "MISSING\r\n" . $missingCopyPaste . '</textarea><br />';
        $tableB .=  '<table class="table table-condensed small"><thead>';
        $tableB .=  '<tr><th colspan="3">These signs are on the sales floor and should be taken down</th></tr></thead><tbody>';
        $removeCopyPaste = '';
        foreach ($remove as $upc) {
            $product = "<tr><td>".$upc."</td><td>".$products[$upc]['brand']."</td><td>"
                .$products[$upc]['description']."</td></tr>";
            $tableB .=  $product;
            $removeCopyPaste .= $upc . "\n";
        }
        $tableB .=  "</tbody></table>";
        $tableB .=  '<textarea rows="3" cols="15">' . "TAKE DOWN\r\n" . $removeCopyPaste . '</textarea><br />';

        $html = <<<HTML
<div class="row">
    <div class="col-lg-6">
        <div align="center">$userhead</div>
    </div>
    <div class="col-lg-6">
        $heading
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        $tableA
    </div>
    <div class="col-lg-6">
        $tableB
    </div>
</div>
HTML;

        return $html;
    }

}
ScancoordDispatch::conditionalExec();
