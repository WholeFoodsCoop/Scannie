<!--/*******************************************************************************

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

*********************************************************************************/-->
<?php
//session_start();
?>
<html>
<head>
  <title> Coop Basics Sign Scan </title>
  <link rel="stylesheet" href="../common/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="../common/css/darkpages.css">
  <script src="../common/bootstrap/jquery.min.js"></script>
  <script src="../common/bootstrap/bootstrap.min.js"></script>
<style>
</style>
</head>
<body>

<?php
include('../../../../../var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include('../../../../../var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}
/**
*   @class coopBasicsScanPage  - All Stores.
*
*   coopBasicsScanPage checks for Coop Basics
*   missing signs for Hillside, using the woodshed.AuditScanner data.
*   Requires list of Coop Basics UPCs to be uploaded to
*   woodshed'...SaleChangeQueues which currently must be done
*   manually.
*
*   Instructions on use:
*
*   1. Upload Coop Basics Checklist to Generic Uploads (Excel Upload in Office).
*   2. Verify that $saleItems is generating a list of items on sale.
*   3. Clear the Audit Scanner Queue.
*	4. Pull up the audit scanner on a handheld device and scan each coop-basics
*	   item. No buttons pushing is necessary, just scan those items, the scanner will
*	   save a list of everything that is scanned. Use the 'admin' account.
*/
class coopBasicsScanPage
{

    protected $title = 'Coop Basics Scan Page';

    public function view()
    {

        include('../config.php');
        include('../common/lib/scanLib.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

        $store_id = scanLib::getStoreID();

        $storeName = scanLib::getStoreName($store_id);
        echo '<h4>Coop Basics Review for <strong>'.$storeName.'</strong></h4>';

        echo "don't forget to upload Coop Basics checklist to <strong>Generic Upload</strong>.<br>";

        if (isset($_GET['session'])) {
            $session = substr($_GET['session'],0,-1);
            echo $session  . "<br>";
        }

        $list = array();
        $products = array();
        $queryA = ("
            SELECT g.upc,p.brand,p.description FROM is4c_op.GenericUpload AS g
            LEFT JOIN products AS p ON g.upc=p.upc
            WHERE p.store_id = 1;
        ");
        $resA = $dbc->query($queryA);
        while ($row = $dbc->fetchRow($resA))  {
            $list[] = $row['upc'];
            $products[$row['upc']]['brand'] = $row['brand'];
            $products[$row['upc']]['description'] = $row['description'];
        }

        if (count($list) < 1) {
            echo '<span class="danger">No scanned items were found.<br>
                Check that barcodes are in the correct queue.<br>
                Check your query.</span>';
        }

        $storeID = scanLib::getStoreID();
		$scanned = array();
        $queryB = ('SELECT upc FROM woodshed_no_replicate.AuditScanner WHERE username = "admin" and store_id = '.$storeID);
        $resB = $dbc->query($queryB);
        while ($row = $dbc->fetchRow($resB))  {
            $scanned[] = $row['upc'];
        }

        $saleitems = array();
        $queryC = ('select bl.upc from batchList as bl left join batches as b on bl.batchID=b.batchID where CURDATE() between b.startDate and b.endDate group by upc;');
        $resC = $dbc->query($queryC);
        while ($row = $dbc->fetchRow($resC))  {
            $saleitems[] = $row['upc'];
        }

        $missing = array();
        $remove = array();
        //  Find tags that are on the floor that should be taken down.
        foreach ($scanned AS $key => $upc) {
            if (in_array($upc,$list)) {
                //  do nothing
            } else {
                $remove[] = $upc;
            }
        }
        //  Find tags that are missing/need to be put up on the floor.
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
            $query = ("select inUse from products where upc = {$upc} and store_id = 1");
            $res = $dbc->query($query);
            while ($row = $dbc->fetchRow($res))  {
                if ($row['inUse'] == 0) {
                    unset($missing[$key]);
                    $notInUse[] = $upc;
                } else  {
                    // do nothing
                }
            }
        }

        foreach ($missing as $key => $upc) {
            if (is_null($upc)) unset($missing[$key]);
        }

        echo '<br>Signs that are missing<br>----------------------------------<br>';
        echo '<table class="table">';
        foreach ($missing as $upc) {
            $product = "<tr><td>".$upc."</td><td>".$products[$upc]['brand']."</td><td>"
            .$products[$upc]['description']."</td></tr>";
            echo $product;
        }
        echo '<tr><td></td><td></td><td>These signs are on the sales floor and should be taken down</td></tr>';
        foreach ($remove as $upc) {
            $product = "<tr><td>".$upc."</td><td>".$products[$upc]['brand']."</td><td>"
            .$products[$upc]['description']."</td></tr>";
            echo $product;
        }
        echo "</table>";

        return false;
    }

}
coopBasicsScanPage::view();
